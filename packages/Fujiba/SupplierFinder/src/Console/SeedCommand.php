<?php

namespace Fujiba\SupplierFinder\Console;

use Fujiba\SupplierFinder\Models\CodeRule;
use Fujiba\SupplierFinder\Models\SourceShop;
use Fujiba\SupplierFinder\Models\Supplier;
use Fujiba\SupplierFinder\Support\TenantContext;
use Illuminate\Console\Command;

/** Inserts missing seed rows only — never overwrites admin edits. */
class SeedCommand extends Command
{
    protected $signature = 'supplier-finder:seed';

    protected $description = 'Seed suppliers, shops and code rules from config (insert-only)';

    public function handle(): int
    {
        TenantContext::enter();
        $cfg = config('supplier-finder');
        $sup = fn (?string $en) => $en ? Supplier::where('name_en', $en)->value('id') : null;

        foreach ($cfg['suppliers'] as $s) {
            Supplier::firstOrCreate(['name_en' => $s['name_en']], $s);
        }
        foreach ($cfg['shops'] as $s) {
            $supplier = $s['supplier'] ?? null;
            unset($s['supplier']);
            SourceShop::firstOrCreate(['code' => $s['code']], $s + ['supplier_id' => $sup($supplier), 'enabled' => false]);
        }
        foreach ($cfg['code_rules'] as $r) {
            $supplier = $r['supplier'] ?? null;
            unset($r['supplier']);
            CodeRule::firstOrCreate(['family' => $r['family']], $r + ['supplier_id' => $sup($supplier)]);
        }
        $this->info(sprintf('suppliers %d, shops %d, rules %d', Supplier::count(), SourceShop::count(), CodeRule::count()));

        return 0;
    }
}
