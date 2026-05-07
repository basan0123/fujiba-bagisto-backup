<?php

namespace Webkul\Pos\Providers;

use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        \Webkul\Pos\Models\Bank::class,
        \Webkul\Pos\Models\CashMovement::class,
        \Webkul\Pos\Models\CustomerCredit::class,
        \Webkul\Pos\Models\FixedExpense::class,
        \Webkul\Pos\Models\FixedExpenseApplication::class,
        \Webkul\Pos\Models\Order::class,
        \Webkul\Pos\Models\Outlet::class,
        \Webkul\Pos\Models\OutletProduct::class,
        \Webkul\Pos\Models\ProductBarcode::class,
        \Webkul\Pos\Models\ProductRequest::class,
        \Webkul\Pos\Models\Receipt::class,
        \Webkul\Pos\Models\User::class,
        \Webkul\Pos\Models\UserDrawer::class,
        \Webkul\Pos\Models\WasteEntry::class,
    ];
}
