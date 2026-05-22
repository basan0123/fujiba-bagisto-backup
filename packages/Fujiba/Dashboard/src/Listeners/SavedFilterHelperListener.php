<?php

namespace Fujiba\Dashboard\Listeners;

use Webkul\Theme\ViewRenderEventManager;

/**
 * Injects window.fjSavedFilter() on every admin page so dashboard/reporting
 * stat widgets can self-load the saved global-filter period in their own
 * mounted() hook instead of fetching the server-default period and waiting for
 * an emitter event they may have mounted too late to receive.
 *
 * Bound to the layout-level `bagisto.admin.layout.vue-app-mount.before` event
 * (fires on all admin pages, before the Vue app mounts) so the helper is
 * defined before any widget's mounted() runs. Additive; no core override.
 */
class SavedFilterHelperListener
{
    public function handle(ViewRenderEventManager $event): void
    {
        $event->addTemplate('fujiba::admin.dashboard.saved-filter-helper');
    }
}
