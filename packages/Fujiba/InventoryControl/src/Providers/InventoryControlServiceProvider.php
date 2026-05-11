<?php

namespace Fujiba\InventoryControl\Providers;

use Fujiba\InventoryControl\Console\Commands\NormalizeBarcodes;
use Fujiba\InventoryControl\Observers\ProductInventoryObserver;
use Fujiba\InventoryControl\Observers\ProductOrderedInventoryObserver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Webkul\Product\Models\ProductInventoryProxy;
use Webkul\Product\Models\ProductOrderedInventoryProxy;

class InventoryControlServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'inventory-control');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/admin.php');
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'inventory-control');

        if ($this->app->runningInConsole()) {
            $this->commands([
                NormalizeBarcodes::class,
            ]);
        }

        $this->registerDashboardCard();
        $this->registerBarcodeAssignEntryPoints();
        $this->registerInventoryAutoReindex();
    }

    /**
     * Synchronously refresh product_inventory_indices whenever stock or
     * reservations change — guarantees cart sees realtime quantity even if
     * the queue worker is stopped or backed up. Bagisto core only refreshes
     * the index via a queued job fired on catalog.product.update.after; that
     * has bitten us when (a) admins edit stock without saving the product,
     * (b) the ChatPion sync writes inventories directly, (c) the queue worker
     * is dead. The observer is idempotent and per-row, so the cost is one
     * indexer pass per channel per inventory write — negligible.
     */
    protected function registerInventoryAutoReindex(): void
    {
        ProductInventoryProxy::observe(ProductInventoryObserver::class);
        ProductOrderedInventoryProxy::observe(ProductOrderedInventoryObserver::class);
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);

        $this->mergeConfigFrom(__DIR__ . '/../Config/admin-menu.php', 'menu.admin');
        $this->mergeConfigFrom(__DIR__ . '/../Config/acl.php', 'acl');
    }

    /**
     * Inject the expiry-alerts card into the admin dashboard via Bagisto's
     * view_render_event() hook. Fires before the stock-threshold section.
     * Bagisto's ViewRenderEventManager collects templates added via
     * $event->addTemplate() and renders them in order.
     */
    protected function registerDashboardCard(): void
    {
        Event::listen('bagisto.admin.dashboard.stock_threshold.before', function ($event) {
            $event->addTemplate('inventory-control::admin.dashboard.expiry-card');
        });
    }

    /**
     * Inject the "Assign Barcodes" entry button into:
     *   1. Admin product list — via native view_render_event hook
     *      'bagisto.admin.catalog.products.create.after' (renders next to Create Product)
     *   2. POS barcode-products list — via the layout-level
     *      'bagisto.admin.layout.content.before' event, gated on the current route name.
     *      The POS view extends <x-admin::layouts> so it inherits this hook with no
     *      vendor edit. Fallback (one-line view_render_event added to the POS view)
     *      is documented in VENDOR_PATCHES.md.
     */
    protected function registerBarcodeAssignEntryPoints(): void
    {
        Event::listen('bagisto.admin.catalog.products.create.after', function ($event) {
            $event->addTemplate('inventory-control::admin.barcode-assign._button');
        });

        Event::listen('bagisto.admin.layout.content.before', function ($event) {
            $routeName = optional(request()->route())->getName();
            if ($routeName === 'admin.pos.barcode_products.index') {
                $event->addTemplate('inventory-control::admin.barcode-assign._button');
            }
        });
    }
}
