<?php

namespace Fujiba\Dashboard\Listeners;

use Webkul\Theme\ViewRenderEventManager;

/**
 * Opens a 2-column grid wrapper around the four Fujiba widgets that
 * render in `bagisto.admin.dashboard.todays_details.after`. Paired with
 * TodaysGridCloseListener; both must be registered around the four
 * widget listeners (Payment, SalesByState, OrdersByRegion, CartActivity).
 *
 * Emits raw HTML via addTemplate's string-template branch
 * (see ViewRenderEventManager::render).
 */
class TodaysGridOpenListener
{
    public function handle(ViewRenderEventManager $event): void
    {
        $event->addTemplate('<div class="fujiba-todays-grid">');
    }
}
