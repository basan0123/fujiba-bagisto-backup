<?php

namespace Webkul\Pos\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Pos\Contracts\FixedExpense;

class FixedExpenseRepository extends Repository
{
    public function model(): string
    {
        return FixedExpense::class;
    }
}
