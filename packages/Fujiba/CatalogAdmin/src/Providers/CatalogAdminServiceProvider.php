<?php

namespace Fujiba\CatalogAdmin\Providers;

use Fujiba\CatalogAdmin\Http\Middleware\RedirectProductEditToVisibleChannel;
use Fujiba\CatalogAdmin\Listeners\AddCostAndProfitColumns;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class CatalogAdminServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'catalog-admin');

        Event::listen(
            'datagrid.product_data_grid.query_builder.prepare.after',
            [AddCostAndProfitColumns::class, 'joinCost'],
        );

        Event::listen(
            'datagrid.product_data_grid.columns.prepare.after',
            [AddCostAndProfitColumns::class, 'addColumns'],
        );

        $this->app->make(Kernel::class)->pushMiddleware(RedirectProductEditToVisibleChannel::class);
    }
}
