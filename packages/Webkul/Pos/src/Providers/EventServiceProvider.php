<?php

namespace Webkul\Pos\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Webkul\Pos\Listeners\AutoAssignOutletProduct;
use Webkul\Pos\Listeners\AutoRegisterProductBarcode;
use Webkul\Pos\Listeners\RevokeCustomerDisplaysOnLogout;

class EventServiceProvider extends ServiceProvider
{
    /**
     * FUJIBA PATCH 2026-04-29: gated head.before listener to fire only on
     * /admin/pos routes to prevent POS Tailwind preflight CSS bundle from
     * polluting every other admin page in the application.
     *
     * Original: unconditional Event::listen for both events in a foreach loop.
     * Symptom of original: themes/pos/build/assets/admin-*.css loaded on every
     * admin page, breaking Vue mount on non-POS pages.
     *
     * Re-apply this patch after any Bagisto core or POS package upgrade.
     *
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // POS admin layout CSS — fires only on /admin/pos/* requests.
        Event::listen('bagisto.admin.layout.head.before', function ($e) {
            if (str_contains((string) request()->path(), 'admin/pos')) {
                $e->addTemplate('pos::admin.layouts.style');
            }
        });

        // POS order-view sidebar — only renders when the order detail page is
        // viewed; safe to leave unconditional. Stays as in original code.
        Event::listen('bagisto.admin.sales.order.right_component.before', function ($e) {
            $e->addTemplate('pos::admin.sales.order.view');
        });

        // FUJIBA 2026-05-30: the storefront vanilla-JS chat widget that used to
        // be injected here ('bagisto.shop.layout.body.after' → pos::shop.chat-widget)
        // has been removed. POS chat is Messenger-only now: it surfaces the
        // existing Fujiba\LiveChat / Chatpion Facebook conversations through the
        // POS GraphQL chat resolvers. There is intentionally no storefront widget
        // and no separate POS chat database.

        // FUJIBA 2026-05-06: customer-display URL must persist across logout.
        // The cashier signing out (or any other cashier signing in on the same
        // terminal) keeps the same /pos/display/{token} URL, so the customer
        // screen never has to be re-paired in the field.
        // Event::listen('pos.outlet.agents.logout.after', RevokeCustomerDisplaysOnLogout::class);

        // Auto-register a pos_product_barcode row for every product on
        // create/update so the POS scanner can resolve it the moment the
        // product exists. Lightweight DB-only upsert; PNG is generated
        // lazily on first print.
        Event::listen('catalog.product.create.after', [AutoRegisterProductBarcode::class, 'handle']);
        Event::listen('catalog.product.update.after', [AutoRegisterProductBarcode::class, 'handle']);

        // Auto-link every newly created product to the outlet so it appears in
        // the POS catalog (getOutletProducts joins through pos_outlet_product).
        // SINGLE-OUTLET tenants only — the listener no-ops on multi-outlet
        // tenants, which curate per-store assignment. Fixes products created
        // via admin/import never showing in the POS (2026-05-24). create.after
        // only: an update shouldn't re-link a product an admin intentionally
        // unassigned via the POS visibility toggle.
        Event::listen('catalog.product.create.after', [AutoAssignOutletProduct::class, 'handle']);
    }
}
