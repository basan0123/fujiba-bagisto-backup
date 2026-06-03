<style>
    /* ─── Mobile-friendly dashboard filter row ───
       The Bagisto core <v-dashboard-filters> renders a single horizontal flex
       row holding [channel?, start, end, Orders/Payments toggle]. On phones the
       toggle gets squeezed onto the calendar's row and is barely tappable.
       Force the row to wrap and let each control fill its own line. */
    /* NOTE: mobile-row layout for the dashboard filter bar now lives in
       packages/Webkul/Admin/.../dashboard/index.blade.php (the .fj-dash-*
       classes). The legacy block here is intentionally trimmed to avoid
       fighting that layout (it used to force the toggle to its own row;
       now channel + toggle share a row 50/50). Kept the
       date-picker input width:100% so flatpickr inputs fill their parent. */
    @media (max-width: 640px) {
        .fujiba-filter-row v-date-picker .relative,
        .fujiba-filter-row v-date-picker .relative > input,
        .fujiba-filter-row .v-date-picker .relative,
        .fujiba-filter-row .v-date-picker .relative > input,
        .fujiba-filter-row span.relative,
        .fujiba-filter-row span.relative > input {
            width: 100% !important;
        }
        .fujiba-filter-row [role="group"] > button {
            font-weight: 600 !important;
        }
    }

    /* ─── Quick-range preset bar (compact) ─── */
    .fujiba-range-presets {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        margin-bottom: 6px;
    }
    .fujiba-range-presets button {
        appearance: none;
        cursor: pointer;
        border: 1px solid rgba(15,23,42,0.12);
        background: #fff;
        color: #374151;
        font-size: 12px;
        line-height: 1.15;
        padding: 3px 9px;
        border-radius: 999px;
        min-height: 0;
        transition: background 160ms ease, color 160ms ease, border-color 160ms ease;
    }
    .fujiba-range-presets button:hover {
        background: #f3f4f6;
        border-color: rgba(15,23,42,0.22);
    }
    .fujiba-range-presets button.is-active {
        background: #2563eb;
        color: #fff;
        border-color: #2563eb;
    }
    .dark .fujiba-range-presets button {
        background: rgb(17,24,39);
        color: rgb(209,213,219);
        border-color: rgba(255,255,255,0.10);
    }
    .dark .fujiba-range-presets button:hover {
        background: rgb(31,41,55);
    }
    .dark .fujiba-range-presets button.is-active {
        background: #3b82f6;
        color: #fff;
        border-color: #3b82f6;
    }
    @media (max-width: 640px) {
        .fujiba-range-presets { gap: 4px; margin-bottom: 6px; }
        .fujiba-range-presets button {
            flex: 0 1 auto;
            padding: 3px 9px;
            font-size: 12px;
        }
    }

    /* ─── Channel button rail (全店 / kimono / mmfood …) ───
       Replaces the core channel dropdown. Same pill shape as the range
       presets but a distinct active colour (slate) so a store selection
       reads differently from a date selection. */
    .fujiba-channel-rail {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        margin-bottom: 6px;
    }
    .fujiba-channel-rail button {
        appearance: none;
        cursor: pointer;
        border: 1px solid rgba(15,23,42,0.12);
        background: #fff;
        color: #374151;
        font-size: 12px;
        line-height: 1.15;
        padding: 3px 11px;
        border-radius: 999px;
        min-height: 0;
        font-weight: 600;
        transition: background 160ms ease, color 160ms ease, border-color 160ms ease;
    }
    .fujiba-channel-rail button:hover {
        background: #f3f4f6;
        border-color: rgba(15,23,42,0.22);
    }
    .fujiba-channel-rail button.is-active {
        background: #0f172a;
        color: #fff;
        border-color: #0f172a;
    }
    .dark .fujiba-channel-rail button {
        background: rgb(17,24,39);
        color: rgb(209,213,219);
        border-color: rgba(255,255,255,0.10);
    }
    .dark .fujiba-channel-rail button:hover {
        background: rgb(31,41,55);
    }
    .dark .fujiba-channel-rail button.is-active {
        background: #e5e7eb;
        color: #0f172a;
        border-color: #e5e7eb;
    }

    /* Hide the core channel dropdown once our rail is mounted. The marker
       class is added to the filter row by the mount script. */
    .fujiba-filter-row.fujiba-channel-rail-active .fj-dash-filter-channel,
    .fujiba-filter-row.fujiba-channel-rail-active x-admin-dropdown:has(.fj-dash-filter-channel),
    .fujiba-filter-row.fujiba-channel-rail-active > div:has(> .fj-dash-filter-channel) {
        display: none !important;
    }
</style>

<script>
/* ─── Filter relay shim ───────────────────────────────────────────────
   Problem: the core store-stats widgets (総売上 / 訪問者 / よく売れている商品 /
   売上最多の顧客) subscribe to the shared `reporting-filter-updated` emitter
   event in their own mounted() hook. The <v-dashboard-filters> component sits
   EARLIER in the DOM, so its initial mounted() emit fires BEFORE those widgets
   register their listeners — mitt has no event replay, so the first emit is
   lost and those widgets fall back to their own dateless default fetch and
   then never reflect the active date range.

   Fix (additive, no core override): remember the most recent filter payload on
   window.__fjLastFilter by wrapping emitter.emit, and re-broadcast it once on
   the next tick after load so any late subscriber receives the current filter.
   Subsequent user changes already propagate normally. */
(function () {
    'use strict';

    function installRelay() {
        var em = window.emitter;
        if (! em || em.__fjRelayInstalled) return !! (em && em.__fjRelayInstalled);

        var origEmit = em.emit.bind(em);
        em.emit = function (type, evt) {
            if (type === 'reporting-filter-updated' && evt && typeof evt === 'object') {
                window.__fjLastFilter = Object.assign({}, evt);
            }
            return origEmit(type, evt);
        };
        em.__fjRelayInstalled = true;
        return true;
    }

    // Re-broadcast the latest filter so widgets that subscribed after the
    // first emit pick it up. Runs a couple of times to cover staggered mounts.
    function rebroadcast() {
        var em = window.emitter;
        if (! em || ! window.__fjLastFilter) return;
        em.emit('reporting-filter-updated', Object.assign({}, window.__fjLastFilter));
    }

    var tries = 0;
    (function waitForEmitter() {
        if (installRelay()) {
            // Give all dashboard widgets a moment to mount + subscribe, then
            // replay the current filter once so none are stuck on stale dates.
            setTimeout(rebroadcast, 400);
            setTimeout(rebroadcast, 1200);
            return;
        }
        if (tries++ > 80) return;
        setTimeout(waitForEmitter, 50);
    })();
})();

(function () {
    'use strict';

    // Format YYYY-MM-DD in local time (avoid toISOString UTC drift).
    function fmt(d) {
        var y = d.getFullYear();
        var m = String(d.getMonth() + 1).padStart(2, '0');
        var dd = String(d.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + dd;
    }

    function startOfMonth(d)   { return new Date(d.getFullYear(), d.getMonth(), 1); }
    function endOfMonth(d)     { return new Date(d.getFullYear(), d.getMonth() + 1, 0); }
    function addMonths(d, n)   { return new Date(d.getFullYear(), d.getMonth() + n, d.getDate()); }
    function startOfYear(d)    { return new Date(d.getFullYear(), 0, 1); }

    // Company fiscal-year start month (1-12), set per-company under
    // Configure → General → 会計年度. Falls back to April (Japanese default).
    var FY_START_MONTH = parseInt(@json($fiscalYearStartMonth ?? 4), 10) || 4;

    // Start of the fiscal year that contains date `d`. If the calendar month is
    // before the FY start month, the FY began in the previous calendar year.
    function startOfFiscalYear(d, startMonth) {
        var m0 = startMonth - 1; // JS month index
        var y = (d.getMonth() < m0) ? d.getFullYear() - 1 : d.getFullYear();
        return new Date(y, m0, 1);
    }

    var LABELS = {
        today:        @json(trans('fujiba::app.dashboard.range_presets.today')),
        yesterday:    @json(trans('fujiba::app.dashboard.range_presets.yesterday')),
        day_before:   @json(trans('fujiba::app.dashboard.range_presets.day_before_yesterday')),
        this_month:   @json(trans('fujiba::app.dashboard.range_presets.this_month')),
        last_month:   @json(trans('fujiba::app.dashboard.range_presets.last_month')),
        last_3m:      @json(trans('fujiba::app.dashboard.range_presets.last_3m')),
        this_fiscal:  @json(trans('fujiba::app.dashboard.range_presets.this_fiscal_year')),
        last_fiscal:  @json(trans('fujiba::app.dashboard.range_presets.last_fiscal_year')),
        this_year:    @json(trans('fujiba::app.dashboard.range_presets.this_year')),
        all:          @json(trans('fujiba::app.dashboard.range_presets.all')),
    };

    function presets() {
        var today = new Date();
        var yesterday    = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 1);
        var dayBefore    = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 2);
        var thisMonthStart = startOfMonth(today);
        var lastMonthStart = startOfMonth(addMonths(today, -1));
        var lastMonthEnd   = endOfMonth(addMonths(today, -1));
        var threeMonthsAgo = startOfMonth(addMonths(today, -2)); // last 3 months including current
        var yearStart      = startOfYear(today);

        // Fiscal year (期): 今期 runs from this FY start month → today;
        // 前期 is the full prior fiscal year (start → day before this FY start).
        var thisFyStart    = startOfFiscalYear(today, FY_START_MONTH);
        var lastFyStart    = new Date(thisFyStart.getFullYear() - 1, thisFyStart.getMonth(), 1);
        var lastFyEnd      = new Date(thisFyStart.getFullYear(), thisFyStart.getMonth(), 0); // day before thisFyStart

        // "All time": cap at 5 years back to keep widget queries bounded.
        var allStart       = new Date(today.getFullYear() - 5, 0, 1);

        return [
            { key: 'today',       label: LABELS.today,       start: fmt(today),          end: fmt(today) },
            { key: 'yesterday',   label: LABELS.yesterday,   start: fmt(yesterday),      end: fmt(yesterday) },
            { key: 'day_before',  label: LABELS.day_before,  start: fmt(dayBefore),      end: fmt(dayBefore) },
            { key: 'this_month',  label: LABELS.this_month,  start: fmt(thisMonthStart), end: fmt(today) },
            { key: 'last_month',  label: LABELS.last_month,  start: fmt(lastMonthStart), end: fmt(lastMonthEnd) },
            { key: 'last_3m',     label: LABELS.last_3m,     start: fmt(threeMonthsAgo), end: fmt(today) },
            { key: 'this_fiscal', label: LABELS.this_fiscal, start: fmt(thisFyStart),    end: fmt(today) },
            { key: 'last_fiscal', label: LABELS.last_fiscal, start: fmt(lastFyStart),    end: fmt(lastFyEnd) },
            { key: 'this_year',   label: LABELS.this_year,   start: fmt(yearStart),      end: fmt(today) },
            { key: 'all',         label: LABELS.all,         start: fmt(allStart),       end: fmt(today) },
        ];
    }

    function findFiltersHost() {
        // After Vue mounts, <v-dashboard-filters> is replaced by its template
        // root <div class="flex gap-1.5">. Anchor on the Orders/Payments
        // segmented toggle inside that row — those button labels are hardcoded
        // English in the core blade, so they're a stable per-locale signal and
        // exclusive to the dashboard filter component.
        var groups = document.querySelectorAll('[role="group"]');
        for (var i = 0; i < groups.length; i++) {
            var g = groups[i];
            var btns = g.querySelectorAll('button');
            if (btns.length !== 2) continue;
            var t0 = (btns[0].textContent || '').trim().toLowerCase();
            var t1 = (btns[1].textContent || '').trim().toLowerCase();
            if (t0 !== 'orders' || t1 !== 'payments') continue;

            var row = g.closest('.fj-dash-filters') || g.closest('.flex.gap-1\\.5') || g.parentElement;
            if (!row) continue;
            var fps = row.querySelectorAll('input.flatpickr-input');
            if (fps.length < 2) continue;
            return { startInput: fps[0], endInput: fps[1], row: row };
        }
        return null;
    }

    function applyPreset(host, p) {
        function set(input, dateStr) {
            if (input._flatpickr) {
                input._flatpickr.setDate(dateStr, true); // triggerChange = true → fires input event
            } else {
                input.value = dateStr;
                input.dispatchEvent(new Event('input',  { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
        // Set END first so START's onChange doesn't see end<start.
        set(host.endInput, p.end);
        set(host.startInput, p.start);

        // Persist + broadcast — matches what <v-dashboard-filters> writes itself.
        try {
            var KEY = 'fujiba_global_filters:' + window.location.host;
            var prev = JSON.parse(localStorage.getItem(KEY) || '{}') || {};
            prev.start = p.start;
            prev.end   = p.end;
            localStorage.setItem(KEY, JSON.stringify(prev));
        } catch (e) {}

        if (window.emitter && typeof window.emitter.emit === 'function') {
            // Re-emit with the merged state so widgets re-fetch immediately.
            try {
                var KEY2 = 'fujiba_global_filters:' + window.location.host;
                var merged = JSON.parse(localStorage.getItem(KEY2) || '{}') || {};
                if (!merged.mode) merged.mode = 'orders';
                if (!('channel' in merged)) merged.channel = '';
                window.emitter.emit('reporting-filter-updated', merged);
            } catch (e) {}
        }
    }

    function activeKey(host, all) {
        var s = host.startInput.value, e = host.endInput.value;
        for (var i = 0; i < all.length; i++) {
            if (all[i].start === s && all[i].end === e) return all[i].key;
        }
        return null;
    }

    // ─── Channel rail ───────────────────────────────────────────────────
    // Channel list comes from the server (core()->getAllChannels()), same
    // source as the core dropdown — no hardcoded store codes. Index 0 is the
    // synthetic 全店 (all, code ''); the rest mirror the core dropdown order.
    var CHANNELS = @json($channelOptions ?? []);
    var ALL_LABEL = @json(trans('fujiba::app.dashboard.range_presets.all_stores'));

    // Locate the core channel dropdown + its (v-show-hidden) menu items. The
    // toggle button carries .fj-dash-filter-channel; menu <li>s sit in the
    // same dropdown wrapper and keep the core @click="filters.channel=..."
    // handler even while hidden, so clicking one drives the Vue state natively.
    function findChannelDropdown(row) {
        var toggle = row.querySelector('.fj-dash-filter-channel');
        if (!toggle) return null;
        // Walk up to the dropdown root that also contains the menu list.
        var wrap = toggle.closest('[v-slot\\:toggle]') || toggle.parentElement;
        var root = toggle;
        for (var i = 0; i < 6 && root.parentElement; i++) {
            root = root.parentElement;
            var items = root.querySelectorAll('li, [class*="dropdown"] a, .dropdown-menu-item, ul > li');
            if (items.length >= CHANNELS.length) {
                return { toggle: toggle, items: collectMenuItems(root) };
            }
        }
        return { toggle: toggle, items: collectMenuItems(row) };
    }

    function collectMenuItems(scope) {
        // The core menu renders one item per channel via v-for. Grab the <li>s
        // inside the menu <ul>; fall back to anchors. Order matches CHANNELS.
        var lis = scope.querySelectorAll('ul li');
        if (lis.length >= CHANNELS.length) return Array.prototype.slice.call(lis, 0, CHANNELS.length);
        var any = scope.querySelectorAll('li');
        return Array.prototype.slice.call(any, 0, CHANNELS.length);
    }

    function currentChannel() {
        try {
            var KEY = 'fujiba_global_filters:' + window.location.host;
            var s = JSON.parse(localStorage.getItem(KEY) || '{}') || {};
            return ('channel' in s) ? (s.channel || '') : '';
        } catch (e) { return ''; }
    }

    function selectChannel(dd, code, index) {
        // Optimistically persist the selection now so paint reads the right
        // value immediately (the core watcher will write the same value a tick
        // later — idempotent). Then drive the actual filter.
        try {
            var KEY = 'fujiba_global_filters:' + window.location.host;
            var prev = JSON.parse(localStorage.getItem(KEY) || '{}') || {};
            prev.channel = code;
            localStorage.setItem(KEY, JSON.stringify(prev));
        } catch (e) {}

        // Preferred path: click the matching (hidden) core menu item so the Vue
        // component updates filters.channel itself and re-emits full state.
        var clicked = false;
        if (dd && dd.items && dd.items[index]) {
            try { dd.items[index].click(); clicked = true; } catch (e) {}
        }

        // Fallback (single channel / dropdown absent): emit directly so widgets
        // re-fetch. Mirrors applyPreset's persist + broadcast.
        if (! clicked && window.emitter && typeof window.emitter.emit === 'function') {
            try {
                var KEY2 = 'fujiba_global_filters:' + window.location.host;
                var merged = JSON.parse(localStorage.getItem(KEY2) || '{}') || {};
                if (!merged.mode) merged.mode = 'orders';
                window.emitter.emit('reporting-filter-updated', merged);
            } catch (e) {}
        }
    }

    function paintChannels(rail) {
        var cur = currentChannel();
        Array.prototype.forEach.call(rail.children, function (b) {
            b.classList.toggle('is-active', (b.dataset.fjChannelCode || '') === cur);
        });
    }

    function paint(bar, all, host) {
        var k = activeKey(host, all);
        Array.prototype.forEach.call(bar.children, function (b) {
            b.classList.toggle('is-active', b.dataset.fjPresetKey === k);
        });
    }

    function mount() {
        var host = findFiltersHost();
        if (!host || !host.row) return false;
        if (host.row.previousElementSibling && host.row.previousElementSibling.classList && host.row.previousElementSibling.classList.contains('fujiba-range-presets')) return true;

        var all = presets();
        var bar = document.createElement('div');
        bar.className = 'fujiba-range-presets';
        all.forEach(function (p) {
            var b = document.createElement('button');
            b.type = 'button';
            b.textContent = p.label;
            b.dataset.fjPresetKey = p.key;
            b.addEventListener('click', function () {
                applyPreset(host, p);
                paint(bar, all, host);
            });
            bar.appendChild(b);
        });

        host.row.classList.add('fujiba-filter-row');
        host.row.parentElement.insertBefore(bar, host.row);
        paint(bar, all, host);

        // Repaint when user manually edits a date.
        ['change', 'input'].forEach(function (ev) {
            host.startInput.addEventListener(ev, function () { paint(bar, all, host); });
            host.endInput.addEventListener(ev,   function () { paint(bar, all, host); });
        });

        // ─── Channel rail ───
        // Only worth showing when there's more than one real channel (the core
        // dropdown itself hides for a single channel via channels.length > 2).
        if (CHANNELS.length > 1) {
            var rail = document.createElement('div');
            rail.className = 'fujiba-channel-rail';

            var dd = findChannelDropdown(host.row);

            // [0] 全店 (all) + one button per real channel, in core order.
            var entries = [{ code: '', name: ALL_LABEL }].concat(CHANNELS);
            entries.forEach(function (ch, idx) {
                var b = document.createElement('button');
                b.type = 'button';
                b.textContent = ch.name;
                b.dataset.fjChannelCode = ch.code || '';
                b.addEventListener('click', function () {
                    selectChannel(dd, ch.code || '', idx);
                    // Re-read after Vue processes the click, then repaint.
                    setTimeout(function () { paintChannels(rail); }, 0);
                });
                rail.appendChild(b);
            });

            host.row.classList.add('fujiba-channel-rail-active');
            host.row.parentElement.insertBefore(rail, bar);
            paintChannels(rail);

            // Keep the rail in sync if the channel changes via any other path.
            if (window.emitter && typeof window.emitter.on === 'function') {
                window.emitter.on('reporting-filter-updated', function () {
                    setTimeout(function () { paintChannels(rail); }, 0);
                });
            }
        }

        return true;
    }

    // The filter component renders after Vue mounts. Retry briefly until found.
    var tries = 0;
    function tick() {
        if (mount() || tries++ > 60) return;
        setTimeout(tick, 100);
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', tick);
    } else {
        tick();
    }
})();
</script>
