<?php

namespace Fujiba\InvoicesAdmin\Providers;

use Fujiba\InvoicesAdmin\DataGrids\FujibaCustomerDataGrid;
use Fujiba\InvoicesAdmin\DataGrids\FujibaInvoiceDataGrid;
use Fujiba\InvoicesAdmin\Listeners\AutoConfirmCodPayment;
use Fujiba\InvoicesAdmin\Reporting\FujibaProductReporting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Webkul\Admin\DataGrids\Customers\CustomerDataGrid;
use Webkul\Admin\DataGrids\Sales\OrderInvoiceDataGrid;
use Webkul\Admin\Helpers\Reporting\Product as CoreProductReporting;

class InvoicesAdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Resolve OrderInvoiceDataGrid -> Fujiba subclass so the core
        // /admin/sales/invoices controller picks up our pending-first sort.
        $this->app->bind(OrderInvoiceDataGrid::class, FujibaInvoiceDataGrid::class);

        // Resolve CustomerDataGrid -> Fujiba subclass so /admin/customers
        // picks up the avatar column + recent-activity default sort.
        $this->app->bind(CustomerDataGrid::class, FujibaCustomerDataGrid::class);

        // Reporting > Products top-selling panels: replace the SKU-as-name
        // returned by the core helper with the localized product_flat name
        // and a thumbnail URL.
        $this->app->bind(CoreProductReporting::class, FujibaProductReporting::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/admin.php');
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'invoices-admin');
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'invoices-admin');

        Event::listen('bagisto.admin.sales.invoices.index.datagrid.before', function ($event) {
            $event->addTemplate('invoices-admin::admin.invoices-summary');
        });

        Event::listen('bagisto.admin.sales.invoices.index.datagrid.after', function ($event) {
            $event->addTemplate('invoices-admin::admin.invoices-chat');
        });

        Event::listen('sales.invoice.save.after', [AutoConfirmCodPayment::class, 'handle']);
    }
}
