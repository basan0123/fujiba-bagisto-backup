<?php

namespace Webkul\Pos\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Pos\Contracts\FixedExpenseApplication;

class FixedExpenseApplicationRepository extends Repository
{
    public function model(): string
    {
        return FixedExpenseApplication::class;
    }
}
