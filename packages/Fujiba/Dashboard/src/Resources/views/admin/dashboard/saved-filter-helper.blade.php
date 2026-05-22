{{--
    Global-filter self-load helper.

    Problem: every dashboard / reporting stat widget fetches in its own
    mounted() with an empty filter (this.getStats({})), then subscribes to the
    shared `reporting-filter-updated` emitter event. The <v-dashboard-filters> /
    <v-reporting-filters> component restores the saved period from localStorage
    and emits once on its own mounted() — but widgets that mount AFTER that emit
    miss it (mitt has no replay), so they stay on the server-default period until
    the user changes the filter. Result: first-load shows the wrong period on
    some widgets (the late-mounting ones).

    Fix (additive, no behavioural change to the filter component): expose a tiny
    reader, window.fjSavedFilter(), that returns the validated saved filter
    ({channel,start,end,mode}) — or {} when nothing valid is stored. Each widget
    calls this.getStats(window.fjSavedFilter ? window.fjSavedFilter() : {}) in
    mounted() so it self-loads the correct period regardless of mount order.

    The validation mirrors what the filter component itself accepts (ISO dates,
    start <= end, mode in orders|payments) so a corrupt key can never push a bad
    range into a widget. It also mirrors the dashboard filter's fresh-login
    reset: Laravel migrates the session id on every login, so when the stored
    session token differs from the current one we drop start/end (channel + mode
    survive) — exactly what <v-dashboard-filters> does — so a widget never loads
    last session's date range on a fresh login. Loaded on every admin page via
    the layout vue-app-mount.before event so it is defined before any widget
    mounts.
--}}
<script>
(function () {
    'use strict';
    if (window.fjSavedFilter) return;

    var ISO     = /^\d{4}-\d{2}-\d{2}$/;
    var SESS_ID = @json(session()->getId());
    var KEY     = 'fujiba_global_filters:' + window.location.host;

    // Capture the fresh-login state ONCE, now — this script runs in
    // vue-app-mount.before, i.e. before the filter component's data() runs and
    // overwrites the stored session token. (We only read it here; the filter
    // component still owns the write.) Reading it lazily inside fjSavedFilter()
    // would race that write and never see "fresh login".
    var FRESH_LOGIN = false;
    try { FRESH_LOGIN = localStorage.getItem(KEY + ':session') !== SESS_ID; } catch (e) {}

    window.fjSavedFilter = function () {
        var out = {};
        try {
            var saved = JSON.parse(localStorage.getItem(KEY) || '{}') || {};

            if (typeof saved.channel === 'string') {
                out.channel = saved.channel;
            }
            if (! FRESH_LOGIN
                && saved.start && saved.end
                && ISO.test(saved.start) && ISO.test(saved.end)
                && saved.start <= saved.end) {
                out.start = saved.start;
                out.end   = saved.end;
            }
            if (saved.mode === 'orders' || saved.mode === 'payments') {
                out.mode = saved.mode;
            }
        } catch (e) {}
        return out;
    };
})();
</script>
