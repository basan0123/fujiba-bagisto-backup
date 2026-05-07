<?php

namespace Fujiba\Accounting\Providers;

use Fujiba\Accounting\Models\Account;
use Fujiba\Accounting\Models\JournalEntry;
use Fujiba\Accounting\Models\JournalLine;
use Fujiba\Accounting\Models\PayrollSlip;
use Fujiba\Accounting\Models\Receipt;
use Fujiba\Accounting\Models\Staff;
use Fujiba\Accounting\Observers\AuditObserver;
use Illuminate\Support\ServiceProvider;

class AccountingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'accounting');
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'accounting');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/admin.php');

        $this->publishes([
            __DIR__ . '/../Config/accounting.php' => config_path('accounting.php'),
        ], 'config');

        $this->registerObservers();
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/accounting.php', 'accounting');
        $this->mergeConfigFrom(__DIR__ . '/../Config/admin-menu.php', 'menu.admin');
    }

    protected function registerObservers(): void
    {
        if (! config('accounting.audit.enabled', true)) {
            return;
        }

        Account::observe(AuditObserver::class);
        JournalEntry::observe(AuditObserver::class);
        JournalLine::observe(AuditObserver::class);
        Receipt::observe(AuditObserver::class);
        PayrollSlip::observe(AuditObserver::class);
        Staff::observe(AuditObserver::class);
    }
}
