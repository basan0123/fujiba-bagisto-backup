<?php

namespace Fujiba\OrdersAdmin\Providers;

use Fujiba\OrdersAdmin\Console\Commands\RebuildOrderedInventories;
use Fujiba\OrdersAdmin\Console\Commands\StandardizeProductImagesCommand;
use Illuminate\Support\ServiceProvider;

class OrdersAdminServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/admin-api.php');
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'orders-admin');
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'orders-admin');

        if ($this->app->runningInConsole()) {
            $this->commands([
                RebuildOrderedInventories::class,
                StandardizeProductImagesCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/acl.php', 'acl');
        $this->mergeConfigFrom(__DIR__ . '/../Config/carriers.php', 'carriers');
        $this->mergeConfigFrom(__DIR__ . '/../Config/menu.php', 'menu.admin');

        // Fujiba-only payment methods (currently `cash` for walk-in shop).
        $this->mergeConfigFrom(__DIR__ . '/../Config/payment_methods.php', 'payment_methods');

        // Override core /admin/sales/orders?view=datagrid grid to add the
        // shipping-status badge column + cross-check filter. The badge
        // renderer is shared with FujibaInvoiceDataGrid so both screens
        // use the same predicate (orders.fujiba_label_printed=1 = shipped).
        $this->app->bind(
            \Webkul\Admin\DataGrids\Sales\OrderDataGrid::class,
            \Fujiba\OrdersAdmin\DataGrids\FujibaOrderDataGrid::class,
        );

        // ChatPion-imported products have manage_stock=0 even though they
        // carry real product_inventories + product_ordered_inventories rows.
        // Bagisto core's ShipmentItemRepository::updateProductInventory()
        // short-circuits on manage_stock=0, so shipping never deducts stock
        // or clears reservations. Our subclass deducts based on row presence
        // instead of the flag.
        $this->app->bind(
            \Webkul\Sales\Repositories\ShipmentItemRepository::class,
            \Fujiba\OrdersAdmin\Repositories\FujibaShipmentItemRepository::class,
        );
    }
}
