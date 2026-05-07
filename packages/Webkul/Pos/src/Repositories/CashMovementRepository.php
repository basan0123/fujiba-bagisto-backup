<?php

namespace Webkul\Pos\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Pos\Contracts\CashMovement;

class CashMovementRepository extends Repository
{
    public function model(): string
    {
        return CashMovement::class;
    }
}
