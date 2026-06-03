<?php

namespace Fujiba\Dashboard\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Webkul\Core\Models\Channel;
use Fujiba\Dashboard\Listeners\BusinessOverviewListener;
use Fujiba\Dashboard\Listeners\CartActivityListener;
use Fujiba\Dashboard\Listeners\CartFunnelListener;
use Fujiba\Dashboard\Listeners\CartStatusListener;
use Fujiba\Dashboard\Listeners\FilterEnhancementsListener;
use Fujiba\Dashboard\Listeners\OrdersByRegionListener;
use Fujiba\Dashboard\Listeners\SalesByStateListener;
use Fujiba\Dashboard\Listeners\SavedFilterHelperListener;
use Fujiba\Dashboard\Listeners\TodaysDetailsRestyleListener;
use Fujiba\Dashboard\Listeners\HeroStripListener;
use Fujiba\Dashboard\Listeners\InventoryValueListener;
use Fujiba\Dashboard\Listeners\PaymentMethodsListener;
use Fujiba\Dashboard\Listeners\PosVsOnlineListener;
use Fujiba\Dashboard\Listeners\ShippingPerformanceListener;
use Fujiba\Dashboard\Listeners\SubscribersListener;
use Fujiba\Dashboard\Listeners\TodaysGridCloseListener;
use Fujiba\Dashboard\Listeners\TodaysGridOpenListener;
use Fujiba\Dashboard\Listeners\TotalExpenseListener;
use Fujiba\Dashboard\Listeners\TotalWasteListener;
use Fujiba\Dashboard\Support\ShortPrice;

class FujibaDashboardServiceProvider extends ServiceProvider
{
    public const KIMONO_CHANNEL_CODE = 'default';

    public function register(): void
    {
        $this->app->singleton('fujiba.dashboard.channel_id', function () {
            return Channel::where('code', self::KIMONO_CHANNEL_CODE)->value('id');
        });

        $this->app->singleton(ShortPrice::class, fn ($app) => new ShortPrice($app));

        // Per-company fiscal-year (会計年度) start-month setting, surfaced under
        // Configure → General. Stored per-tenant in core_config and consumed by
        // the dashboard 今期 / 前期 quick-range presets.
        $this->mergeConfigFrom(__DIR__.'/../Config/system.php', 'core');
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'fujiba');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'fujiba');

        $this->loadRoutesFrom(__DIR__.'/../Routes/admin.php');

        // Locale-aware short-price directive.
        // EN: ¥X.YK / ¥X.YM   JA: ¥X.Y万 / ¥X.Y億
        // Pass any expression that evaluates to a numeric amount.
        Blade::directive('fujibaShortPrice', function ($expression) {
            return "<?php echo e(app(\\Fujiba\\Dashboard\\Support\\ShortPrice::class)->format({$expression})); ?>";
        });

        // Expose window.fjSavedFilter() on every admin page (before Vue mounts)
        // so dashboard + reporting stat widgets self-load the saved global-filter
        // period in mounted() instead of racing the filter component's emit.
        Event::listen(
            'bagisto.admin.layout.vue-app-mount.before',
            [SavedFilterHelperListener::class, 'handle']
        );

        Event::listen(
            'bagisto.admin.dashboard.overall_details.before',
            [HeroStripListener::class, 'handle']
        );

        // Mobile-responsive filter row + quick-range preset buttons (今月/先月/
        // 直近3ヶ月/今年/全期間). Inline <style>+<script>; no core override.
        Event::listen(
            'bagisto.admin.dashboard.overall_details.before',
            [FilterEnhancementsListener::class, 'handle']
        );

        // Business Overview is the unified replacement for the legacy 全体の概要
        // widget. Rendered in overall_details.before so it precedes the legacy
        // widget; an inline JS hide-script in the partial removes the legacy
        // widget from the DOM. Do not re-attach to overall_details.after.
        Event::listen(
            'bagisto.admin.dashboard.overall_details.before',
            [BusinessOverviewListener::class, 'handle']
        );

        // Cart Activity — live cart feed. Moved to the TOP (directly below the
        // Business Overview block, full width) per request. Registered right
        // after BusinessOverview so it's the first widget under 全体の概要; it
        // renders outside the 2-col Today's Details grid as its own row.
        Event::listen(
            'bagisto.admin.dashboard.overall_details.before',
            [CartActivityListener::class, 'handle']
        );

        // Order matters: Inventory Value renders before
        // Expiry alerts in the stock_threshold.before slot.
        // This is enforced by Fujiba\Dashboard appearing
        // ABOVE Fujiba\InventoryControl in
        // bootstrap/providers.php. Do not move Fujiba\Dashboard
        // below InventoryControl without also reordering
        // the listener registrations.
        // Note: Laravel's Event::listen() does not support a
        // priority argument — verified in this session.
        // Order is determined by registration order only.
        Event::listen(
            'bagisto.admin.dashboard.stock_threshold.before',
            [InventoryValueListener::class, 'handle']
        );

        // Restyle core Bagisto's 本日の詳細 (Today's Details) widget into the
        // bold gradient cards used elsewhere on the Fujiba dashboard. CSS-only,
        // anchored to <v-dashboard-todays-details> so it only applies on the
        // dashboard route. Fires `before` so the styles are parsed before the
        // widget hydrates.
        Event::listen(
            'bagisto.admin.dashboard.todays_details.before',
            [TodaysDetailsRestyleListener::class, 'handle']
        );

        // The widgets below render inside a 2-column grid wrapper opened by
        // TodaysGridOpenListener and closed by TodaysGridCloseListener.
        // Registration order = render order = grid cell order:
        //   row 1: [PaymentMethods | SalesByState]
        //   row 2: [OrdersByRegion (spans full width — see grid CSS)]
        // Cart Activity used to be the row-2 right cell but was moved to the
        // top (overall_details.before). With 3 cards the lone last one spans
        // both columns via .fujiba-todays-grid > ...:last-child:nth-child(odd).
        // CartFunnelListener is currently a no-op; kept registered so existing
        // wiring is not deleted, placed inside the wrapper so it does not break
        // the row count if it ever begins emitting markup.
        // Grid CSS lives in todays-details-restyle.blade.php (.fujiba-todays-grid).
        Event::listen(
            'bagisto.admin.dashboard.todays_details.after',
            [TodaysGridOpenListener::class, 'handle']
        );

        Event::listen(
            'bagisto.admin.dashboard.todays_details.after',
            [PaymentMethodsListener::class, 'handle']
        );

        // Sales by Customer State (horizontal bar chart) — companion to the
        // Region map; ranks revenue by shipping prefecture. Subscribes to the
        // shared `reporting-filter-updated` emitter event for live re-fetch.
        Event::listen(
            'bagisto.admin.dashboard.todays_details.after',
            [SalesByStateListener::class, 'handle']
        );

        // Orders by Region (Japan choropleth) — the 3rd (last) grid card; with
        // Cart Activity moved out it spans both columns. Phase E W3.
        Event::listen(
            'bagisto.admin.dashboard.todays_details.after',
            [OrdersByRegionListener::class, 'handle']
        );

        Event::listen(
            'bagisto.admin.dashboard.todays_details.after',
            [CartFunnelListener::class, 'handle']
        );

        Event::listen(
            'bagisto.admin.dashboard.todays_details.after',
            [TodaysGridCloseListener::class, 'handle']
        );

        // Shipping Performance: collected vs carrier-billed shipping (margin)
        // + free-shipping subsidy count. Reads `order_shipping_costs` (manual /
        // CSV) and `shipping_rule_logs`. Moved to the BOTTOM per request —
        // registered after TodaysGridClose so it renders as a full-width block
        // directly below the Today's Details grid (above stock/store-stats).
        Event::listen(
            'bagisto.admin.dashboard.todays_details.after',
            [ShippingPerformanceListener::class, 'handle']
        );

        Event::listen(
            'bagisto.admin.dashboard.stock_threshold.after',
            [CartStatusListener::class, 'handle']
        );

        Event::listen(
            'bagisto.admin.dashboard.stock_threshold.after',
            [TotalWasteListener::class, 'handle']
        );

        Event::listen(
            'bagisto.admin.dashboard.stock_threshold.after',
            [TotalExpenseListener::class, 'handle']
        );

        Event::listen(
            'bagisto.admin.dashboard.store_stats.before',
            [SubscribersListener::class, 'handle']
        );

        Event::listen(
            'bagisto.admin.dashboard.store_stats.after',
            [PosVsOnlineListener::class, 'handle']
        );
    }
}
