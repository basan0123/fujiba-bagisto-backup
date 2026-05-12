<?php

namespace Fujiba\Accounting\Providers;

use Fujiba\Accounting\Console\Commands\AuditBcPayroll;
use Fujiba\Accounting\Console\Commands\BackfillBcPayroll;
use Fujiba\Accounting\Console\Commands\PostBagistoOrders;
use Fujiba\Accounting\Console\Commands\PostBagistoRefunds;
use Fujiba\Accounting\Console\Commands\RunPayroll;
use Fujiba\Accounting\Models\Account;
use Fujiba\Accounting\Models\JournalEntry;
use Fujiba\Accounting\Models\JournalLine;
use Fujiba\Accounting\Models\PayrollSlip;
use Fujiba\Accounting\Models\Receipt;
use Fujiba\Accounting\Models\Staff;
use Fujiba\Accounting\Observers\AuditObserver;
use Fujiba\Accounting\Services\InvoiceRegistrantLookup;
use Fujiba\Accounting\Services\JournalPoster;
use Fujiba\Accounting\Services\Ocr\AzureDocIntelligenceOcr;
use Fujiba\Accounting\Services\Ocr\ReceiptOcrInterface;
use Fujiba\Accounting\Services\Ocr\StubOcr;
use Fujiba\Accounting\Services\Payroll\PayrollCalculator;
use Fujiba\Accounting\Services\Payroll\PayrollJournalBuilder;
use Fujiba\Accounting\Services\Payroll\ZenginExportService;
use Fujiba\Accounting\Services\ReceiptJournalBuilder;
use Fujiba\Accounting\Services\Reports\BsCalculator;
use Fujiba\Accounting\Services\Reports\DashboardSummary;
use Fujiba\Accounting\Services\Reports\JournalBookCalculator;
use Fujiba\Accounting\Services\Reports\LedgerCalculator;
use Fujiba\Accounting\Services\Reports\PlCalculator;
use Fujiba\Accounting\Services\Reports\ReportPeriodResolver;
use Fujiba\Accounting\Services\Reports\TrialBalanceCalculator;
use Fujiba\Accounting\Services\SettingService;
use Fujiba\Accounting\Services\Tax\ConsumptionTaxCalculator;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class AccountingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->environment('testing')) {
            $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        }

        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'accounting');
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'accounting');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/admin.php');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/kiosk.php');

        $this->publishes([
            __DIR__ . '/../Config/accounting.php' => config_path('accounting.php'),
        ], 'config');

        $this->registerObservers();
        $this->registerCommands();
        $this->registerSchedule();
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/accounting.php', 'accounting');
        $this->mergeConfigFrom(__DIR__ . '/../Config/admin-menu.php', 'menu.admin');

        $this->app->singleton(SettingService::class);
        $this->app->singleton(JournalPoster::class);
        $this->app->singleton(ConsumptionTaxCalculator::class);
        $this->app->singleton(ReceiptJournalBuilder::class);
        $this->app->singleton(InvoiceRegistrantLookup::class);
        $this->app->singleton(PayrollCalculator::class);
        $this->app->singleton(PayrollJournalBuilder::class);
        $this->app->singleton(\Fujiba\Accounting\Services\Payroll\BackfillBcPayrollService::class);
        $this->app->singleton(ZenginExportService::class);
        $this->app->singleton(ReportPeriodResolver::class);
        $this->app->singleton(TrialBalanceCalculator::class);
        $this->app->singleton(PlCalculator::class);
        $this->app->singleton(BsCalculator::class);
        $this->app->singleton(LedgerCalculator::class);
        $this->app->singleton(JournalBookCalculator::class);
        $this->app->singleton(DashboardSummary::class);

        $this->app->bind(ReceiptOcrInterface::class, function ($app) {
            return match (config('accounting.ocr.driver', 'stub')) {
                'azure' => new AzureDocIntelligenceOcr(
                    endpoint:        (string) config('accounting.ocr.azure.endpoint'),
                    key:             (string) config('accounting.ocr.azure.key'),
                    model:           (string) config('accounting.ocr.azure.model', 'prebuilt-receipt'),
                    apiVersion:      (string) config('accounting.ocr.azure.api_version', '2024-11-30'),
                    pollIntervalMs:  (int)    config('accounting.ocr.azure.poll_interval_ms', 1000),
                    pollMaxAttempts: (int)    config('accounting.ocr.azure.poll_max_attempts', 60),
                ),
                default => new StubOcr(),
            };
        });
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

    protected function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            PostBagistoOrders::class,
            PostBagistoRefunds::class,
            RunPayroll::class,
            AuditBcPayroll::class,
            BackfillBcPayroll::class,
        ]);
    }

    protected function registerSchedule(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->app->afterResolving(Schedule::class, function (Schedule $schedule, Application $app): void {
            $schedule->command('accounting:post-orders')
                ->hourly()
                ->name('accounting-post-orders')
                ->onOneServer()
                ->withoutOverlapping(10)
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/accounting-cron.log'));

            $schedule->command('accounting:post-refunds')
                ->hourlyAt(15)
                ->name('accounting-post-refunds')
                ->onOneServer()
                ->withoutOverlapping(10)
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/accounting-cron.log'));
        });
    }
}
