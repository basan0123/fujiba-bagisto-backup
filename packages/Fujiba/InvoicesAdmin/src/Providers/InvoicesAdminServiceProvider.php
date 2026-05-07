<?php

namespace Fujiba\InvoicesAdmin\Providers;

use Fujiba\InvoicesAdmin\Listeners\AutoConfirmCodPayment;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class InvoicesAdminServiceProvider extends ServiceProvider
{
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
