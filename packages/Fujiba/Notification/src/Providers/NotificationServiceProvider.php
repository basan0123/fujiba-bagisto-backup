<?php

namespace Fujiba\Notification\Providers;

use Fujiba\Notification\Channels\MailChannel;
use Fujiba\Notification\Channels\MessengerChannel;
use Fujiba\Notification\Listeners\OrderEventListener;
use Fujiba\Notification\Services\ChannelManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/fujiba-notification.php',
            'fujiba-notification'
        );

        // Bagisto admin Configuration → Emails → General page.
        // Adds a "Fujiba Notifications" section with a master kill-switch.
        // Reads via core()->getConfigData('emails.general.fujiba_notification.enabled').
        $this->mergeConfigFrom(__DIR__.'/../Config/system.php', 'core');

        $this->app->singleton(ChannelManager::class, function ($app) {
            $manager = new ChannelManager();
            $manager->register($app->make(MailChannel::class));
            $manager->register($app->make(MessengerChannel::class));

            return $manager;
        });
    }

    public function boot(): void
    {
        // Migrations live in database/migrations/tenant and are run by
        // `tenants:migrate` via the path registered in config/tenancy.php.
        // Do not loadMigrationsFrom() here — that would pull them onto the
        // central DB connection.

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'fujiba_notification');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'fujiba_notification');

        $this->loadRoutesFrom(__DIR__.'/../Http/routes/admin.php');
        $this->loadRoutesFrom(__DIR__.'/../Http/routes/shop.php');

        $this->mergeConfigFrom(__DIR__.'/../Config/menu.php', 'menu.admin');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/fujiba-notification.php' => config_path('fujiba-notification.php'),
            ], 'fujiba-notification-config');

            $this->commands([
                \Fujiba\Notification\Console\Commands\BackfillMessengerTokens::class,
                \Fujiba\Notification\Console\Commands\BackfillTokensFromCustomers::class,
                \Fujiba\Notification\Console\Commands\SweepPendingNotifications::class,
                \Fujiba\Notification\Console\Commands\InstallStatusTemplates::class,
                \Fujiba\Notification\Console\Commands\OrderNotificationAudit::class,
            ]);
        }

        $this->registerOrderEvents();

        // Bank-transfer auto flow — listens on the same checkout.order.save.after
        // event but only fires for payment_method=moneytransfer. Calls BotFlowSender
        // to replay the per-channel ChatPion "payment" flow (bank details + ATM/Mobile
        // App buttons + carousel images), same as clicking ▶ in /admin/fujiba-livechat.
        Event::listen(
            'checkout.order.save.after',
            [\Fujiba\Notification\Listeners\BankTransferOrderListener::class, 'handle'],
        );
    }

    /**
     * Register every order event in OrderNotificationMatrix::EVENTS to the same generic handler.
     * The closure captures the Bagisto event name so the listener can
     * map it to the internal event key (order.placed, order.invoiced, ...).
     */
    protected function registerOrderEvents(): void
    {
        foreach (array_keys(OrderEventListener::EVENT_MAP) as $bagistoEvent) {
            Event::listen($bagistoEvent, function (mixed $payload) use ($bagistoEvent) {
                app(OrderEventListener::class)->handle($bagistoEvent, $payload);
            });
        }
    }
}
