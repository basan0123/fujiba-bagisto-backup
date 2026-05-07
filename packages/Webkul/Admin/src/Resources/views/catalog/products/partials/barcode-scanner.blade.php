{{--
    Barcode-scanner widget for the POS / Mobile product views.

    Provides a hidden keyboard-wedge input (always-listening) plus a
    camera-mode toggle backed by html5-qrcode. On a successful scan it
    POSTs to admin.barcode_assign.lookup (canonical resolver: barcode OR
    scan_value) and dispatches a `pos:barcode-resolved` CustomEvent on
    `window`. Each page (pos-view / mobile-view) listens for that event
    and decides how to react in its own list.

    Usage:
        @include('admin::catalog.products.partials.barcode-scanner')
--}}

@push('styles')
<style>
.bcs-fab{
    position:fixed;right:14px;bottom:14px;z-index:9500;
    width:54px;height:54px;border-radius:50%;
    background:#2563eb;color:#fff;border:none;
    box-shadow:0 4px 14px rgba(0,0,0,.25);
    display:flex;align-items:center;justify-content:center;
    cursor:pointer;transition:transform .12s,background .15s;
}
.bcs-fab:hover{background:#1d4ed8;}
.bcs-fab:active{transform:scale(.94);}
.bcs-fab svg{width:24px;height:24px;}

.bcs-panel{
    position:fixed;left:0;right:0;bottom:0;z-index:9501;
    background:#fff;border-top:2px solid #2563eb;
    box-shadow:0 -8px 24px rgba(0,0,0,.18);
    padding:12px 14px env(safe-area-inset-bottom,12px) 14px;
    transform:translateY(100%);transition:transform .22s ease;
}
.dark .bcs-panel{background:#0f172a;border-top-color:#1d4ed8;color:#f9fafb;}
.bcs-panel.is-open{transform:translateY(0);}

.bcs-panel-head{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:8px;}
.bcs-panel-title{font-weight:700;font-size:14px;}
.bcs-close{background:transparent;border:none;font-size:22px;line-height:1;color:#6b7280;cursor:pointer;padding:4px 8px;}
.bcs-close:hover{color:#111827;}
.dark .bcs-close{color:#9ca3af;}

.bcs-tabs{display:flex;gap:6px;margin-bottom:8px;}
.bcs-tab{
    flex:1;padding:8px 10px;border-radius:6px;
    border:1.5px solid #2563eb;background:#fff;color:#2563eb;
    font-size:13px;font-weight:600;cursor:pointer;
}
.bcs-tab.is-active{background:#2563eb;color:#fff;}
.dark .bcs-tab{background:#1f2937;border-color:#3b82f6;color:#bfdbfe;}
.dark .bcs-tab.is-active{background:#1d4ed8;color:#fff;}

.bcs-hw-input{
    width:100%;padding:14px;font-size:18px;font-family:monospace;
    border:2px solid #2563eb;border-radius:8px;background:#fff;color:#111827;
    box-sizing:border-box;outline:none;
}
.dark .bcs-hw-input{background:#1f2937;color:#f9fafb;}
.bcs-hint{font-size:11px;color:#6b7280;margin-top:6px;}

#bcs-reader{width:100%;max-height:55vh;background:#000;border-radius:8px;overflow:hidden;}
.bcs-cam-error{margin-top:6px;font-size:12px;color:#dc2626;}
.bcs-status{margin-top:8px;font-size:12px;color:#374151;min-height:14px;}
.dark .bcs-status{color:#d1d5db;}

/* Brief highlight when a scanned product is located in the list */
@keyframes bcs-flash {
    0%   { box-shadow: inset 0 0 0 3px #22c55e; background:rgba(34,197,94,0.18); }
    100% { box-shadow: inset 0 0 0 0 transparent; background:transparent; }
}
.bcs-hit { animation: bcs-flash 1.4s ease forwards; }
</style>
@endpush

<button type="button" id="bcs-fab" class="bcs-fab" title="Scan barcode" aria-label="Scan barcode">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="6" width="18" height="12" rx="1"/>
        <path d="M7 9v6M10 9v6M13 9v6M16 9v6"/>
    </svg>
</button>

{{-- USB/BT keyboard-wedge scanners are caught by the document keydown listener
     in the script below — no hidden sink element needed. --}}

<div id="bcs-panel" class="bcs-panel" role="dialog" aria-label="Barcode scanner">
    <div class="bcs-panel-head">
        <span class="bcs-panel-title">Scan barcode</span>
        <button type="button" class="bcs-close" id="bcs-close" aria-label="Close">×</button>
    </div>
    <div class="bcs-tabs">
        <button type="button" class="bcs-tab is-active" id="bcs-tab-hw"  data-mode="hw">Hardware</button>
        <button type="button" class="bcs-tab"           id="bcs-tab-cam" data-mode="cam">Camera</button>
    </div>

    <div id="bcs-area-hw">
        <input type="text" id="bcs-hw-input" class="bcs-hw-input"
               placeholder="Scan or type a barcode and press Enter"
               autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
               inputmode="text" enterkeyhint="search">
        <div class="bcs-hint">Most USB/Bluetooth scanners type the code and send Enter — works automatically even when this panel is closed.</div>
    </div>

    <div id="bcs-area-cam" style="display:none;">
        <div id="bcs-reader"></div>
        <div id="bcs-cam-error" class="bcs-cam-error" style="display:none;">
            Camera unavailable. Use the Hardware tab or grant camera permission.
        </div>
    </div>

    <div id="bcs-status" class="bcs-status"></div>
</div>

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function(){
    'use strict';
    if (window.__bcsBooted) return;
    window.__bcsBooted = true;

    const LOOKUP_URL = @json(route('admin.barcode_assign.lookup'));
    const CSRF       = document.querySelector('meta[name="csrf-token"]')?.content
                       || @json(csrf_token());

    const $ = id => document.getElementById(id);

    const state = {
        mode: 'hw',
        qr: null,
        wedgeBuf: '',
        wedgeTimer: null,
        wedgeFirstAt: 0,
        wedgeLastAt: 0,
        lastCode: '',
        lastAt: 0,
        busy: false,
    };

    function setStatus(msg, isErr) {
        const el = $('bcs-status'); if (!el) return;
        el.textContent = msg || '';
        el.style.color = isErr ? '#dc2626' : '';
    }

    function beep(freq, ms) {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator(), gain = ctx.createGain();
            osc.frequency.value = freq;
            osc.connect(gain); gain.connect(ctx.destination);
            gain.gain.setValueAtTime(0.12, ctx.currentTime);
            osc.start(); osc.stop(ctx.currentTime + ms / 1000);
            osc.onended = () => ctx.close();
        } catch (_) {}
    }

    function vibrate(p) { try { navigator.vibrate && navigator.vibrate(p); } catch(_){} }

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
        let body = null; try { body = await res.json(); } catch(_){}
        return { ok: res.ok, body };
    }

    async function handleScan(rawCode) {
        const code = String(rawCode || '').trim();
        if (!code) return;

        // Dedupe rapid duplicates (camera fires repeatedly; some wedges send twice)
        const now = Date.now();
        if (code === state.lastCode && (now - state.lastAt) < 1200) return;
        state.lastCode = code; state.lastAt = now;

        if (state.busy) return;
        state.busy = true;
        setStatus('Looking up ' + code + ' …');

        try {
            const { ok, body } = await lookup(code);
            if (!ok || !body) { setStatus('Lookup failed', true); beep(220, 140); return; }

            if (body.found && body.product) {
                beep(880, 80); vibrate(40);
                setStatus('Found: ' + (body.product.name || ('#' + body.product.id)));
                window.dispatchEvent(new CustomEvent('pos:barcode-resolved', {
                    detail: { barcode: code, product: body.product, found: true }
                }));
            } else {
                beep(330, 180); vibrate([40,60,40]);
                setStatus('No product found for ' + code, true);
                window.dispatchEvent(new CustomEvent('pos:barcode-resolved', {
                    detail: { barcode: code, product: null, found: false }
                }));
            }
        } catch (e) {
            console.error('[bcs] lookup error', e);
            setStatus('Lookup error', true);
        } finally {
            state.busy = false;
        }
    }

    /* ── HARDWARE WEDGE: always-listening sink, plus visible input when panel open ── */
    function isTypableTarget(t) {
        if (!t) return false;
        if (t.id === 'bcs-hw-input') return false;
        const tag = (t.tagName || '').toLowerCase();
        if (tag === 'input' || tag === 'textarea' || tag === 'select') return true;
        if (t.isContentEditable) return true;
        return false;
    }

    // Global key listener for hardware scanners.
    //
    // We only treat a keystroke burst as a scan when ALL of these hold:
    //   • target is NOT a typable element (otherwise people typing in the URL bar
    //     or any input wouldn't see their keys),
    //   • buffer is ≥6 chars (no real barcode is shorter; manual quick-typing is filtered),
    //   • characters arrived in a tight burst (avg interval <40ms — way faster
    //     than human typing, which averages 80–250ms between keys).
    //
    // This makes the listener invisible during normal page usage while still
    // catching real wedge scanners even when the panel is closed.
    document.addEventListener('keydown', function (e) {
        if (e.target && e.target.id === 'bcs-hw-input') return; // visible input has its own handler
        if (isTypableTarget(e.target)) return;

        const now = Date.now();

        if (e.key === 'Enter') {
            const buf = state.wedgeBuf;
            const span = state.wedgeLastAt - state.wedgeFirstAt;
            state.wedgeBuf = '';
            clearTimeout(state.wedgeTimer);
            // Require ≥6 chars AND avg interval <40ms (i.e. burst from a wedge)
            if (buf.length >= 6 && (buf.length === 1 || span / Math.max(1, buf.length - 1) < 40)) {
                e.preventDefault();
                handleScan(buf);
            }
            return;
        }

        if (e.key && e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
            if (state.wedgeBuf === '') state.wedgeFirstAt = now;
            state.wedgeBuf += e.key;
            state.wedgeLastAt = now;
            clearTimeout(state.wedgeTimer);
            state.wedgeTimer = setTimeout(() => { state.wedgeBuf = ''; }, 200);
        }
    });

    // Visible input inside the panel — Enter submits its current value
    document.addEventListener('keydown', function (e) {
        if (!e.target || e.target.id !== 'bcs-hw-input') return;
        if (e.key !== 'Enter') return;
        e.preventDefault();
        const v = e.target.value.trim();
        e.target.value = '';
        if (v) handleScan(v);
        setTimeout(() => { try { e.target.focus(); } catch(_){} }, 0);
    });

    /* ── CAMERA MODE ── */
    function startCamera() {
        const errEl = $('bcs-cam-error');
        if (errEl) errEl.style.display = 'none';
        if (!window.Html5Qrcode) {
            if (errEl) errEl.style.display = 'block';
            return;
        }
        try {
            const FORMATS = window.Html5QrcodeSupportedFormats ? [
                Html5QrcodeSupportedFormats.QR_CODE,
                Html5QrcodeSupportedFormats.EAN_13,
                Html5QrcodeSupportedFormats.EAN_8,
                Html5QrcodeSupportedFormats.UPC_A,
                Html5QrcodeSupportedFormats.UPC_E,
                Html5QrcodeSupportedFormats.CODE_128,
                Html5QrcodeSupportedFormats.CODE_39,
                Html5QrcodeSupportedFormats.CODE_93,
                Html5QrcodeSupportedFormats.ITF,
                Html5QrcodeSupportedFormats.CODABAR,
            ] : undefined;
            state.qr = new Html5Qrcode('bcs-reader',
                FORMATS ? { formatsToSupport: FORMATS, verbose: false } : false);
            const qrbox = (vw, vh) => ({
                width:  Math.floor(Math.min(vw * 0.9, 380)),
                height: Math.floor(Math.min(vh * 0.5, 180)),
            });
            state.qr.start(
                { facingMode: 'environment' },
                {
                    fps: 15,
                    qrbox,
                    aspectRatio: 1.7777778,
                    formatsToSupport: FORMATS,
                    experimentalFeatures: { useBarCodeDetectorIfSupported: true },
                },
                (decoded) => handleScan(decoded),
                () => {}
            ).catch((err) => {
                console.error('[bcs] camera start failed', err);
                if (errEl) errEl.style.display = 'block';
            });
        } catch (e) {
            console.error('[bcs] camera init exception', e);
            if (errEl) errEl.style.display = 'block';
        }
    }

    function stopCamera() {
        if (state.qr) {
            try { state.qr.stop().then(() => state.qr.clear()).catch(() => {}); } catch(_){}
            state.qr = null;
        }
    }

    function setMode(m, opts) {
        state.mode = m;
        $('bcs-tab-hw').classList.toggle('is-active', m === 'hw');
        $('bcs-tab-cam').classList.toggle('is-active', m === 'cam');
        $('bcs-area-hw').style.display  = m === 'hw'  ? '' : 'none';
        $('bcs-area-cam').style.display = m === 'cam' ? '' : 'none';
        if (m === 'cam') startCamera();
        else stopCamera();
        // Focus only when the user explicitly clicks the tab/input — never on
        // panel open. Otherwise the scanner steals focus from the page.
        if (m === 'hw' && opts && opts.focus) {
            setTimeout(() => $('bcs-hw-input')?.focus(), 60);
        }
    }

    /* ── PANEL OPEN/CLOSE ── */
    function openPanel() {
        $('bcs-panel').classList.add('is-open');
        setStatus('');
        setMode(state.mode); // no auto-focus
    }
    function closePanel() {
        $('bcs-panel').classList.remove('is-open');
        stopCamera();
        const ae = document.activeElement;
        if (ae && ae.id === 'bcs-hw-input') ae.blur();
    }

    document.addEventListener('click', function (e) {
        if (e.target.closest('#bcs-fab'))   return openPanel();
        if (e.target.closest('#bcs-close')) return closePanel();
        const tab = e.target.closest('.bcs-tab');
        if (tab) setMode(tab.dataset.mode, { focus: tab.dataset.mode === 'hw' });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && $('bcs-panel').classList.contains('is-open')) {
            closePanel();
        }
    });

    console.log('[bcs] barcode scanner ready (hw+cam, lookup=' + LOOKUP_URL + ')');
})();
</script>
@endpush
