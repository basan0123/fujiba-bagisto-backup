<?php

namespace Fujiba\Dashboard\Listeners;

use Webkul\Theme\ViewRenderEventManager;

/**
 * Mobile-responsive dashboard filter row + quick-range preset buttons
 * (今日 / 昨日 / 一昨日 / 今月 / 先月 / 直近3ヶ月 / 今期 / 前期 / 今年 / 全期間)
 * + a channel button rail (全店 / kimono / mmfood …) replacing the core
 * channel dropdown.
 *
 * Implementation rule (see project memory feedback_fujiba_dashboard_safety_rules):
 * no overrides of Webkul/Admin core blades, no Vite — inline <style>+<script> only.
 *
 * The core dashboard's <v-dashboard-filters> Vue component owns the filter state
 * and persists it to localStorage under
 *   `fujiba_global_filters:` + window.location.host
 * so we drive presets by writing that key + dispatching the shared `$emitter`
 * event `reporting-filter-updated` directly. Reload is then unnecessary.
 *
 * The 今期 / 前期 (this / last fiscal year) presets need the company's
 * fiscal-year start month. It's a per-tenant setting under
 * Configure → General → 会計年度; read here and handed to the blade.
 *
 * The channel rail is built from core()->getAllChannels() (same source the core
 * component uses) — no hardcoded kimono/mmfood codes — plus a synthetic 全店
 * (all) entry with code ''. We hide the core dropdown and drive filters.channel.
 */
class FilterEnhancementsListener
{
    public function handle(ViewRenderEventManager $event): void
    {
        $startMonth = (int) (core()->getConfigData('general.fujiba_fiscal_year.settings.start_month') ?: 4);

        // Guard against bad stored values; fall back to April (Japanese default).
        if ($startMonth < 1 || $startMonth > 12) {
            $startMonth = 4;
        }

        // Code + localized name for each channel, exactly as the core filter
        // component sources them. Names follow the admin locale via the
        // translatable `name` attribute. No hardcoding of store codes.
        $channels = core()->getAllChannels()->map(fn ($channel) => [
            'code' => $channel->code,
            'name' => $channel->name,
        ])->values();

        $event->addTemplate('fujiba::admin.dashboard.filter-enhancements', [
            'fiscalYearStartMonth' => $startMonth,
            'channelOptions'       => $channels,
        ]);
    }
}
