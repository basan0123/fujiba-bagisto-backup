<style>
    /* ─── Mobile-friendly dashboard filter row ───
       The Bagisto core <v-dashboard-filters> renders a single horizontal flex
       row holding [channel?, start, end, Orders/Payments toggle]. On phones the
       toggle gets squeezed onto the calendar's row and is barely tappable.
       Force the row to wrap and let each control fill its own line. */
    @media (max-width: 640px) {
        .fujiba-filter-row {
            width: 100% !important;
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 8px !important;
        }
        /* Calendar inputs: 2-up so they share a row */
        .fujiba-filter-row v-date-picker,
        .fujiba-filter-row .v-date-picker {
            flex: 1 1 calc(50% - 4px) !important;
            min-width: 0 !important;
        }
        .fujiba-filter-row v-date-picker .relative,
        .fujiba-filter-row v-date-picker .relative > input,
        .fujiba-filter-row .v-date-picker .relative,
        .fujiba-filter-row .v-date-picker .relative > input,
        .fujiba-filter-row span.relative,
        .fujiba-filter-row span.relative > input {
            width: 100% !important;
        }
        /* Orders/Payments segmented control: full width, equal halves */
        .fujiba-filter-row [role="group"] {
            width: 100% !important;
            display: flex !important;
        }
        .fujiba-filter-row [role="group"] > button {
            flex: 1 1 50% !important;
            padding: 10px 12px !important;
            font-weight: 600 !important;
        }
    }

    /* ─── Quick-range preset bar ─── */
    .fujiba-range-presets {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 10px;
    }
    .fujiba-range-presets button {
        appearance: none;
        cursor: pointer;
        border: 1px solid rgba(15,23,42,0.12);
        background: #fff;
        color: #374151;
        font-size: 13px;
        line-height: 1.2;
        padding: 7px 12px;
        border-radius: 999px;
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
        .fujiba-range-presets {
            justify-content: stretch;
        }
        .fujiba-range-presets button {
            flex: 1 1 calc(33% - 6px);
            padding: 9px 10px;
        }
    }
</style>

<script>
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

    function presets() {
        var today = new Date();
        var thisMonthStart = startOfMonth(today);
        var lastMonthStart = startOfMonth(addMonths(today, -1));
        var lastMonthEnd   = endOfMonth(addMonths(today, -1));
        var threeMonthsAgo = startOfMonth(addMonths(today, -2)); // last 3 months including current
        var yearStart      = startOfYear(today);
        // "All time": cap at 5 years back to keep widget queries bounded.
        var allStart       = new Date(today.getFullYear() - 5, 0, 1);

        return [
            { key: 'today',      label: '今日',     start: fmt(today),          end: fmt(today) },
            { key: 'this_month', label: '今月',     start: fmt(thisMonthStart), end: fmt(today) },
            { key: 'last_month', label: '先月',     start: fmt(lastMonthStart), end: fmt(lastMonthEnd) },
            { key: 'last_3m',   label: '直近3ヶ月', start: fmt(threeMonthsAgo), end: fmt(today) },
            { key: 'this_year', label: '今年',     start: fmt(yearStart),      end: fmt(today) },
            { key: 'all',       label: '全期間',   start: fmt(allStart),       end: fmt(today) },
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

            var row = g.closest('.flex.gap-1\\.5') || g.parentElement;
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
