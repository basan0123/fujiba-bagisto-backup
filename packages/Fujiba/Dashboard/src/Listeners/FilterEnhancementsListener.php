<?php

namespace Fujiba\Dashboard\Listeners;

use Webkul\Theme\ViewRenderEventManager;

/**
 * Mobile-responsive dashboard filter row + quick-range preset buttons
 * (今月 / 先月 / 直近3ヶ月 / 今年 / 全期間).
 *
 * Implementation rule (see project memory feedback_fujiba_dashboard_safety_rules):
 * no overrides of Webkul/Admin core blades, no Vite — inline <style>+<script> only.
 *
 * The core dashboard's <v-dashboard-filters> Vue component owns the filter state
 * and persists it to localStorage under
 *   `fujiba_global_filters:` + window.location.host
 * so we drive presets by writing that key + dispatching the shared `$emitter`
 * event `reporting-filter-updated` directly. Reload is then unnecessary.
 */
class FilterEnhancementsListener
{
    public function handle(ViewRenderEventManager $event): void
    {
        $event->addTemplate('fujiba::admin.dashboard.filter-enhancements');
    }
}
