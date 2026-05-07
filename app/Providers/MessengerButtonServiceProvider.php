<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class MessengerButtonServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(resource_path('views/messenger-button'), 'messenger-button');

        // Floating button: show ONLY on order success page (was previously site-wide)
        Event::listen('bagisto.shop.checkout.success.image.after', function ($viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('messenger-button::partials.messenger-contact-btn');
        });

        // Disabled per user request — redundant with messenger-success-cta
        // Event::listen('bagisto.shop.checkout.success.image.before', function ($viewRenderEventManager) {
        //     $viewRenderEventManager->addTemplate('messenger-button::partials.messenger-connect-banner');
        // });

        Event::listen('bagisto.shop.checkout.success.image.before', function ($viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('messenger-button::partials.messenger-success-cta');
        });
    }
}
