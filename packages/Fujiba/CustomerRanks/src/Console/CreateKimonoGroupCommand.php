<?php

namespace Fujiba\CustomerRanks\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Idempotently create the kimono_plus3 customer group on a tenant.
 *
 *   php artisan tenants:run fujiba:kimono-rank-create-group
 *
 * The group is created as non-user-defined-safe (is_user_defined=1) so admins
 * can still edit/delete it from Customers -> Groups if needed. The discount is
 * NOT created here — attach a Catalog Rule to this group in admin.
 */
class CreateKimonoGroupCommand extends Command
{
    protected $signature = 'fujiba:kimono-rank-create-group
                            {--name=Kimono +3 : display name for the group}';

    protected $description = 'Create the kimono_plus3 customer group (idempotent).';

    public function handle(): int
    {
        $dbName = DB::connection()->getDatabaseName();

        if (Str::startsWith((string) $dbName, ['bagisto_central', 'central_'])) {
            $this->error("Refusing to run on central DB ({$dbName}). Use: php artisan tenants:run fujiba:kimono-rank-create-group");

            return self::FAILURE;
        }

        $code = config('ranks.kimono_plus3.target_group_code');

        if (DB::table('customer_groups')->where('code', $code)->exists()) {
            $this->info("Group \"{$code}\" already exists on {$dbName} — nothing to do.");

            return self::SUCCESS;
        }

        $columns = [
            'code'       => $code,
            'name'       => (string) $this->option('name'),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // is_user_defined exists on Bagisto's customer_groups; default to 1
        // so admins can manage the group from the UI.
        if (\Illuminate\Support\Facades\Schema::hasColumn('customer_groups', 'is_user_defined')) {
            $columns['is_user_defined'] = 1;
        }

        DB::table('customer_groups')->insert($columns);

        $this->info("Created group \"{$code}\" ({$this->option('name')}) on {$dbName}.");

        return self::SUCCESS;
    }
}
