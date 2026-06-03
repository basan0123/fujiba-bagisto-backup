{{--
    Global hardware-barcode listener for the admin.

    Goal: scanning a barcode on ANY admin page jumps to the POS home grid with
    that product highlighted. Listener-only (no floating button) so it adds no
    visual clutter to non-POS pages.

    It defers entirely to the full scanner partial when that is present: the full
    scanner sets window.__bcsBooted, and the POS product views (pos-view,
    list-view, mobile-view, stock-quick-fix, take-stock) include it and handle
    the scan in place. This global listener only acts when no full scanner has
    booted on the page.
--}}
@push('scripts')
<script>
(function () {
    'use strict';

    if (window.__bcsGlobalBooted) return;
    window.__bcsGlobalBooted = true;

    // The full barcode-scanner partial (on product views) renders #bcs-fab and
    // owns the scan there with in-place highlighting. If it's on the page, this
    // global listener stays dormant. Checked at DOM-ready so include order can't
    // matter. Returning here removes the keydown listener via the `active` flag.
    let active = true;
    function maybeDisable() {
        if (window.__bcsBooted || document.getElementById('bcs-fab')) active = false;
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', maybeDisable);
    } else {
        maybeDisable();
    }

    const LOOKUP_URL = @json(route('admin.barcode_assign.lookup'));
    const POS_URL    = @json(route('admin.catalog.products.pos_view'));
    const CSRF       = document.querySelector('meta[name="csrf-token"]')?.content
                       || @json(csrf_token());

    const state = { buf: '', firstAt: 0, lastAt: 0, timer: null, busy: false, last: '', lastAt2: 0 };

    function isTypable(t) {
        if (!t) return false;
        const tag = (t.tagName || '').toLowerCase();
        if (tag === 'input' || tag === 'textarea' || tag === 'select') return true;
        return !!t.isContentEditable;
    }

    async function lookup(code) {
        const res = await fetch(LOOKUP_URL, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ barcode: code }),
        });
        let body = null; try { body = await res.json(); } catch (_) {}
        return { ok: res.ok, body };
    }

    function gotoPos(skuOrCode) {
        // Carry the scan so the POS page can filter + flash-highlight it on load.
        const sep = POS_URL.indexOf('?') === -1 ? '?' : '&';
        window.location.href = POS_URL + sep + 'scan=' + encodeURIComponent(skuOrCode);
    }

    async function handleScan(raw) {
        const code = String(raw || '').trim();
        if (!code) return;
        const now = Date.now();
        if (code === state.last && (now - state.lastAt2) < 1200) return; // dedupe
        state.last = code; state.lastAt2 = now;
        if (state.busy) return;
        state.busy = true;
        try {
            const { ok, body } = await lookup(code);
            // Prefer the resolved SKU (what the POS search box matches on); fall
            // back to the raw code so an unmatched scan still lands on POS with a
            // query the user can see.
            const sku = (ok && body && body.found && body.product && body.product.sku)
                ? body.product.sku : code;
            gotoPos(sku);
        } catch (e) {
            console.error('[bcs-global] lookup error', e);
            gotoPos(code); // navigate anyway; POS shows "no results" if unmatched
        } finally {
            state.busy = false;
        }
    }

    // Keyboard-wedge detection — same heuristic as the full scanner: a tight
    // burst (avg <40ms/char) of ≥6 chars ending in Enter, while not focused in
    // a typable field. Invisible during normal typing.
    document.addEventListener('keydown', function (e) {
        if (!active) return;
        if (isTypable(e.target)) return;
        const now = Date.now();
        if (e.key === 'Enter') {
            const buf = state.buf;
            const span = state.lastAt - state.firstAt;
            state.buf = '';
            clearTimeout(state.timer);
            if (buf.length >= 6 && (buf.length === 1 || span / Math.max(1, buf.length - 1) < 40)) {
                e.preventDefault();
                handleScan(buf);
            }
            return;
        }
        if (e.key && e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
            if (state.buf === '') state.firstAt = now;
            state.buf += e.key;
            state.lastAt = now;
            clearTimeout(state.timer);
            state.timer = setTimeout(() => { state.buf = ''; }, 200);
        }
    });
})();
</script>
@endpush
