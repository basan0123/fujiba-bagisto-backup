<?php

namespace Fujiba\CustomerRanks\Console;

use Fujiba\CustomerRanks\Services\PromoteCustomerRankService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * One-time (and safe-to-repeat) backfill: promote existing customers who
 * already have >= threshold completed kimono orders into the kimono rank.
 *
 * Per-tenant — run via:
 *   php artisan tenants:run fujiba:kimono-rank-backfill
 *   php artisan tenants:run fujiba:kimono-rank-backfill --argument="apply=1"
 *
 * Dry-run by default; pass --apply to write.
 */
class BackfillKimonoRankCommand extends Command
{
    protected $signature = 'fujiba:kimono-rank-backfill
                            {--apply : write changes (default is dry-run)}';

    protected $description = 'Promote existing customers with 3+ completed kimono orders into the kimono_plus3 group (wholesale never touched).';

    public function handle(PromoteCustomerRankService $service): int
    {
        $dbName = DB::connection()->getDatabaseName();

        if (Str::startsWith((string) $dbName, ['bagisto_central', 'central_'])) {
            $this->error("Refusing to run on central DB ({$dbName}). Use: php artisan tenants:run fujiba:kimono-rank-backfill");

            return self::FAILURE;
        }

        $code = config('ranks.kimono_plus3.target_group_code');

        if (! DB::table('customer_groups')->where('code', $code)->exists()) {
            $this->error("Group \"{$code}\" does not exist on {$dbName}. Run: php artisan tenants:run fujiba:kimono-rank-create-group");

            return self::FAILURE;
        }

        $apply  = (bool) $this->option('apply');
        $catIds = $service->kimonoCategoryIds();

        $this->info("Tenant DB: {$dbName} ".($apply ? '(WRITE MODE)' : '(dry-run)'));
        $this->line('Kimono categories: '.implode(', ', $catIds));

        if (! $apply) {
            // Show who WOULD be promoted without changing anything.
            $promotableIds = DB::table('customer_groups')
                ->whereIn('code', config('ranks.kimono_plus3.promotable_from_group_codes'))
                ->pluck('id');

            $candidates = DB::table('orders as o')
                ->join('order_items as oi', 'oi.order_id', '=', 'o.id')
                ->join('product_categories as pc', 'pc.product_id', '=', 'oi.product_id')
                ->join('customers as c', 'c.id', '=', 'o.customer_id')
                ->whereIn('pc.category_id', $catIds)
                ->whereIn('o.status', config('ranks.kimono_plus3.qualifying_order_statuses'))
                ->whereIn('c.customer_group_id', $promotableIds)
                ->groupBy('o.customer_id')
                ->havingRaw('COUNT(DISTINCT o.id) >= ?', [(int) config('ranks.kimono_plus3.threshold')])
                ->pluck('o.customer_id');

            $this->warn('Would promote '.$candidates->count().' customer(s). Re-run with --apply to write.');

            return self::SUCCESS;
        }

        $promoted = $service->backfill(function ($id) {
            $this->line("  promoted customer {$id}");
        });

        $this->info("Done. Promoted {$promoted} customer(s) to {$code}.");

        return self::SUCCESS;
    }
}
