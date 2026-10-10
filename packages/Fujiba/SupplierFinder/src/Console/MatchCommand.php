<?php

namespace Fujiba\SupplierFinder\Console;

use Fujiba\SupplierFinder\Services\Grouper;
use Fujiba\SupplierFinder\Services\Matcher;
use Fujiba\SupplierFinder\Support\TenantContext;
use Illuminate\Console\Command;

class MatchCommand extends Command
{
    protected $signature = 'supplier-finder:match';

    protected $description = 'Decode codes → source fabrics, then score against our kimono catalog';

    public function handle(Grouper $grouper, Matcher $matcher): int
    {
        TenantContext::enter();
        $this->line('group: '.json_encode($grouper->rebuild()));
        $this->line('match: '.json_encode($matcher->run()));

        return 0;
    }
}
