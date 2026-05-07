<?php

namespace Webkul\PayPay\Providers;

use Illuminate\Support\ServiceProvider;

class PayPayServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/system.php', 'core');
        $this->mergeConfigFrom(__DIR__.'/../Config/paymentmethods.php', 'payment_methods');
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'paypay');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'paypay');
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
    }
}
