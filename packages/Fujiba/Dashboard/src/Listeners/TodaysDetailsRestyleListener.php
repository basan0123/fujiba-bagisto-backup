<?php

namespace Fujiba\Dashboard\Listeners;

use Webkul\Theme\ViewRenderEventManager;

/**
 * Injects scoped CSS that restyles core Bagisto's Today's Details widget
 * (本日の詳細) to match the Fujiba bold-card design — gradient background,
 * white bold typography, centered layout — without modifying core blade files.
 *
 * Anchored to <v-dashboard-todays-details> so styles cannot leak to other pages.
 * Hooks `bagisto.admin.dashboard.todays_details.before` so it only loads on
 * the dashboard route, not on every admin page.
 */
class TodaysDetailsRestyleListener
{
    public function handle(ViewRenderEventManager $event): void
    {
        $event->addTemplate('fujiba::admin.dashboard.todays-details-restyle');
    }
}
