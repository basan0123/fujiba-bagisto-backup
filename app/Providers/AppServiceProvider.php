<?php

namespace App\Providers;

use App\Observers\PosUserObserver;
use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Request as RequestFacade;
use Illuminate\Support\ServiceProvider;
use Webkul\Pos\Models\User as PosUser;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $allowedIPs = array_map('trim', explode(',', config('app.debug_allowed_ips')));

        $allowedIPs = array_filter($allowedIPs);

        if (empty($allowedIPs)) {
            return;
        }

        if (in_array(RequestFacade::ip(), $allowedIPs)) {
            Debugbar::enable();
        } else {
            Debugbar::disable();
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Override Webkul's + RestApi's exception handler so /superadmin/*
        // unauthenticated requests redirect to the super-admin login instead
        // of customer login. Done in boot() (not register()) so it runs
        // AFTER all package register() methods — otherwise RestApi's binding
        // overwrites ours.
        $this->app->bind(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            \App\Exceptions\Handler::class
        );

        // --- FIX FOR POS LOGIN ---
        // This maps the non-existent 'Agent' class to the existing 'User' model
        if (! class_exists('\Webkul\Pos\Models\Agent')) {
            class_alias(\Webkul\Pos\Models\User::class, '\Webkul\Pos\Models\Agent');
        }
        // -------------------------

        // Patch config('core') BEFORE any SystemConfig call caches it.
        $this->patchCoreConfigChannelIsolation();

        PosUser::observe(PosUserObserver::class);

        RateLimiter::for('pos-login', function (Request $request) {
            return Limit::perMinute(5)->by($request->input('email') . $request->ip());
        });

        ParallelTesting::setUpTestDatabase(function (string $database, int $token) {
            Artisan::call('db:seed');
        });

        // Inject "Mark as Store Pickup" button into admin order detail action row.
        Event::listen('bagisto.admin.sales.order.page_action.after', function ($event) {
            $event->addTemplate('admin.store-pickup-button', ['order' => $event->getParam('order')]);
        });
    }

    /**
     * Force per-channel (and selected locale-aware) isolation on config sections
     * that Bagisto ships without channel_based/locale_based flags. This is
     * applied at boot to config('core') so CoreConfigRepository writes per-channel
     * rows without modifying vendor Webkul system.php.
     *
     * Locale-aware prefixes are for user-facing text (SEO, header text).
     */
    protected function patchCoreConfigChannelIsolation(): void
    {
        $channelPrefixes = [
            'general.content.',
            'catalog.products.',
            'catalog.rich_snippets.',
            'sales.order_settings.',
            'sales.checkout.',
            'pwa.settings.',
        ];

        $localePrefixes = [
            'general.content.header_offer.',
            'general.content.footer.',
            'catalog.rich_snippets.',
            'pwa.settings.',
        ];

        $core = config('core');

        if (! is_array($core)) {
            return;
        }

        $startsWithAny = function (string $key, array $prefixes): bool {
            foreach ($prefixes as $p) {
                if (str_starts_with($key, $p)) {
                    return true;
                }
            }
            return false;
        };

        foreach ($core as $i => $item) {
            if (! isset($item['key'], $item['fields']) || ! is_array($item['fields'])) {
                continue;
            }

            $sectionKey = $item['key'] . '.';

            $applyChannel = $startsWithAny($sectionKey, $channelPrefixes);
            $applyLocale  = $startsWithAny($sectionKey, $localePrefixes);

            if (! $applyChannel && ! $applyLocale) {
                continue;
            }

            foreach ($item['fields'] as $fi => $field) {
                if (! is_array($field)) {
                    continue;
                }

                if ($applyChannel && ! array_key_exists('channel_based', $field)) {
                    $core[$i]['fields'][$fi]['channel_based'] = true;
                }

                if ($applyLocale && ! array_key_exists('locale_based', $field)) {
                    // Only mark locale_based for text-like types where per-locale
                    // content makes sense; skip boolean/select switches.
                    $type = $field['type'] ?? 'text';
                    if (in_array($type, ['text', 'textarea', 'rich-text', 'editor', 'image'], true)) {
                        $core[$i]['fields'][$fi]['locale_based'] = true;
                    }
                }
            }
        }

        config(['core' => $core]);
    }
}
