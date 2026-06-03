<?php

namespace Fujiba\LiveChat\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * fujiba:sync-flows
 *
 * Daily refresh of the local `chatpion_import` copy of the ChatPion bot-flow
 * definition tables (messenger_bot + messenger_bot_postback).
 *
 * WHY THIS EXISTS
 * ---------------
 * LiveChatController::botFlows() prefers the live ChatPion API, but that API
 * (`livechat_bot_flows`) returns ONLY postback_id + bot_name — no page_id and no
 * message body — and it ignores whatsapp_bot_id (same flow list for every page).
 * The page→flow attribution and the actual conversation `message` content live
 * ONLY in these two source tables. The picker falls back to them when the API is
 * unreachable, so without a refresh the fallback goes stale (admin edits on
 * fujiba.shop never appear).
 *
 * This command pulls the two (small: ~150/~100 row) tables from the source
 * ChatPion MySQL over SSH+mysqldump and replaces them in `chatpion_import`. It
 * touches NOTHING else — no products, orders, or product_ordered_inventories —
 * so it cannot re-trigger the phantom-reservation incident that got the old
 * hourly sync-chatpion.sh cron disabled.
 *
 * Connection details default to the documented values used by
 * scripts/sync-chatpion.sh and can be overridden via env (CHATPION_SSH_*,
 * CHATPION_SRC_DB_*).
 */
class SyncFlowsCommand extends Command
{
    protected $signature = 'fujiba:sync-flows
        {--dry-run : Connect and report row counts without replacing local tables.}';

    protected $description = 'Refresh local chatpion_import flow tables (messenger_bot + messenger_bot_postback) from the source ChatPion DB over SSH.';

    /** Flow-definition tables to mirror. Full replace each run (small tables). */
    protected array $tables = ['messenger_bot', 'messenger_bot_postback'];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $ssh = [
            'key'  => env('CHATPION_SSH_KEY',  '/var/www/Fujiba1.pem'),
            'host' => env('CHATPION_SSH_HOST', 'r9382355@www1105.onamae.ne.jp'),
            'port' => env('CHATPION_SSH_PORT', '8022'),
        ];
        $src = [
            'host' => env('CHATPION_SRC_DB_HOST', 'mysql1026.onamae.ne.jp'),
            'user' => env('CHATPION_SRC_DB_USER', 'hrlc6_fujiba_user'),
            'pass' => env('CHATPION_SRC_DB_PASS', 'Fujiba@202501'),
            'name' => env('CHATPION_SRC_DB_NAME', 'hrlc6_chatpion_db'),
        ];

        $importDb = config('database.connections.chatpion_import.database', 'chatpion_import');

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Syncing flow tables into `{$importDb}` from {$src['name']} via {$ssh['host']}");

        foreach ($this->tables as $table) {
            try {
                $this->syncTable($table, $ssh, $src, $dryRun);
            } catch (\Throwable $e) {
                Log::error('fujiba:sync-flows failed', ['table' => $table, 'error' => $e->getMessage()]);
                $this->error("  {$table}: FAILED — {$e->getMessage()}");
                return self::FAILURE;
            }
        }

        $this->info($dryRun ? 'Dry-run complete (no local writes).' : 'Flow tables refreshed.');

        return self::SUCCESS;
    }

    protected function syncTable(string $table, array $ssh, array $src, bool $dryRun): void
    {
        // Remote row count — proves connectivity and gives us a sanity number.
        $remoteCount = (int) trim($this->remoteMysql(
            $ssh, $src, "SELECT COUNT(*) FROM `{$table}`"
        ));

        $localBefore = (int) DB::connection('chatpion_import')->table($table)->count();

        $this->line("  {$table}: remote={$remoteCount} local_before={$localBefore}");

        if ($dryRun) {
            return;
        }

        if ($remoteCount === 0) {
            // Refuse to wipe a populated local table with an empty remote dump —
            // that's almost certainly a connectivity/permissions glitch, not a
            // legitimate "all flows deleted" state.
            $this->warn("  {$table}: remote count is 0; skipping replace to avoid wiping local data.");
            return;
        }

        $dump = $this->remoteDump($ssh, $src, $table);

        // mysqldump already emits DROP TABLE IF EXISTS + CREATE TABLE, so loading
        // it into chatpion_import is a full atomic-ish replace of this one table.
        $this->loadDump($table, $dump);

        $localAfter = (int) DB::connection('chatpion_import')->table($table)->count();
        $this->info("  {$table}: local_after={$localAfter}");
    }

    /**
     * Run a single SELECT on the source ChatPion DB over SSH and return stdout.
     * Mirrors scripts/sync-chatpion.sh remote_mysql().
     */
    protected function remoteMysql(array $ssh, array $src, string $sql): string
    {
        $remote = sprintf(
            "MYSQL_PWD=%s mysql -h%s -u%s %s -N --default-character-set=utf8mb4 -e %s",
            escapeshellarg($src['pass']),
            escapeshellarg($src['host']),
            escapeshellarg($src['user']),
            escapeshellarg($src['name']),
            escapeshellarg($sql)
        );

        $process = new Process([
            'ssh', '-i', $ssh['key'], '-p', $ssh['port'],
            '-o', 'StrictHostKeyChecking=no', '-o', 'ConnectTimeout=15',
            $ssh['host'], $remote,
        ]);
        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('remote SELECT failed: ' . trim($process->getErrorOutput()));
        }

        return $process->getOutput();
    }

    /**
     * mysqldump a single table on the source over SSH; return the SQL text.
     * Strips DEFINER clauses like scripts/sync-chatpion.sh does, so the dump
     * loads cleanly under a different local user.
     */
    protected function remoteDump(array $ssh, array $src, string $table): string
    {
        $remote = sprintf(
            "MYSQL_PWD=%s mysqldump -h%s -u%s --no-tablespaces --single-transaction " .
            "--skip-lock-tables --skip-triggers --set-gtid-purged=OFF " .
            "--default-character-set=utf8mb4 --quick %s %s",
            escapeshellarg($src['pass']),
            escapeshellarg($src['host']),
            escapeshellarg($src['user']),
            escapeshellarg($src['name']),
            escapeshellarg($table)
        );

        $process = new Process([
            'ssh', '-i', $ssh['key'], '-p', $ssh['port'],
            '-o', 'StrictHostKeyChecking=no', '-o', 'ConnectTimeout=15',
            $ssh['host'], $remote,
        ]);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('mysqldump failed: ' . trim($process->getErrorOutput()));
        }

        // Drop DEFINER=`user`@`host` clauses (definer won't exist locally).
        return preg_replace(
            ['#/\*!50013 DEFINER=[^*]+\*/#', '# DEFINER=`[^`]+`@`[^`]+`#'],
            '',
            $process->getOutput()
        );
    }

    /**
     * Pipe a mysqldump into the local chatpion_import database via the `mysql`
     * client (multi-statement dumps can't go through a single PDO::exec).
     */
    protected function loadDump(string $table, string $dump): void
    {
        $conn = config('database.connections.chatpion_import');

        $cmd = ['mysql', '--default-character-set=utf8mb4',
            '-h', (string) $conn['host'],
            '-P', (string) $conn['port'],
            '-u', (string) $conn['username'],
            $conn['database'],
        ];

        $process = new Process($cmd, null, ['MYSQL_PWD' => (string) $conn['password']]);
        $process->setTimeout(300);
        $process->setInput($dump);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException("loading {$table} into local failed: " . trim($process->getErrorOutput()));
        }
    }
}
