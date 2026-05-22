<?php

namespace Fujiba\InvoicesAdmin\Providers;

use Fujiba\InvoicesAdmin\DataGrids\FujibaCustomerDataGrid;
use Fujiba\InvoicesAdmin\DataGrids\FujibaInvoiceDataGrid;
use Fujiba\InvoicesAdmin\Listeners\AutoConfirmCodPayment;
use Fujiba\InvoicesAdmin\Reporting\FujibaCustomerReporting;
use Fujiba\InvoicesAdmin\Reporting\FujibaProductReporting;
use Fujiba\InvoicesAdmin\Reporting\FujibaReporting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Webkul\Admin\DataGrids\Customers\CustomerDataGrid;
use Webkul\Admin\DataGrids\Sales\OrderInvoiceDataGrid;
use Webkul\Admin\Helpers\Reporting as CoreReporting;
use Webkul\Admin\Helpers\Reporting\Customer as CoreCustomerReporting;
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

        // Reporting > Products > top-selling-by-quantity detail page: extend
        // the table-view column schema with Waste / Current Stock / 2-Month
        // Forecast (row data filled in by FujibaProductReporting).
        $this->app->bind(CoreReporting::class, FujibaReporting::class);

        // Dashboard "売上最多の顧客" + Reporting > Customers: fix the core
        // GROUP BY CONCAT(email, "-", id) that collapses every NULL-email
        // guest order into one phantom customer. Re-keys on a NULL-safe
        // per-buyer identity and honors the paid_at/created_at mode toggle.
        $this->app->bind(CoreCustomerReporting::class, FujibaCustomerReporting::class);
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

        // Cover the "Create Shipment" path (Order View > Ship form). COD =
        // courier collects cash on delivery, so shipping is also the moment
        // payment is confirmed. The "Mark Shipped" dropdown path uses
        // OrderLabelPrintedController which already auto-confirms directly.
        Event::listen('sales.shipment.save.after', [AutoConfirmCodPayment::class, 'handleShipment']);
    }
}
