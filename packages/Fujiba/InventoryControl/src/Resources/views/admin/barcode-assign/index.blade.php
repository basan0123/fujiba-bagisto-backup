<x-admin::layouts>
    <x-slot:title>
        @lang('inventory-control::app.barcode_assign.title')
    </x-slot>

    @push('styles')
        <style>
            .ba-root { max-width: 720px; margin: 0 auto; padding: 12px; font-family: system-ui, -apple-system, sans-serif; }
            .ba-counter { display: flex; gap: 12px; justify-content: space-between; padding: 10px 12px; background: #111827; color: #fff; border-radius: 8px; font-size: 14px; font-weight: 600; }
            .ba-mode-toggle { display: flex; gap: 0; margin: 12px 0; border: 1px solid #d1d5db; border-radius: 8px; overflow: hidden; }
            .ba-mode-toggle button { flex: 1; padding: 14px 12px; min-height: 48px; background: #f9fafb; border: 0; font-size: 15px; font-weight: 600; cursor: pointer; }
            .ba-mode-toggle button.active { background: #2563eb; color: #fff; }
            .ba-camera-wrap { width: 100%; aspect-ratio: 4/3; background: #000; border-radius: 8px; overflow: hidden; position: relative; }
            .ba-camera-wrap > div { width: 100% !important; height: 100% !important; }
            .ba-flash { position: absolute; inset: 0; background: #22c55e; opacity: 0; pointer-events: none; transition: opacity 120ms; }
            .ba-flash.flash-ok { opacity: 0.5; background: #22c55e; }
            .ba-flash.flash-warn { opacity: 0.5; background: #f59e0b; }
            .ba-hw-input { width: 100%; padding: 16px; min-height: 56px; font-size: 18px; font-family: monospace; border: 2px solid #2563eb; border-radius: 8px; box-sizing: border-box; }
            .ba-status-card { margin-top: 12px; padding: 16px; border-radius: 8px; border: 2px solid; }
            .ba-status-card.assigned { background: #fef3c7; border-color: #f59e0b; }
            .ba-status-card.new { background: #dcfce7; border-color: #22c55e; }
            .ba-barcode { font-family: monospace; font-size: 16px; font-weight: 700; margin: 4px 0; }
            .ba-product-row { display: flex; gap: 12px; align-items: center; margin-top: 10px; }
            .ba-product-row img, .ba-product-row .ba-noimg { width: 56px; height: 56px; object-fit: cover; border-radius: 6px; background: #e5e7eb; flex-shrink: 0; }
            .ba-product-row .meta { flex: 1; min-width: 0; }
            .ba-product-row .name { font-weight: 600; font-size: 15px; line-height: 1.3; word-break: break-word; }
            .ba-product-row .sku { font-family: monospace; font-size: 12px; color: #6b7280; margin-top: 2px; }
            .ba-actions { display: flex; gap: 8px; margin-top: 12px; flex-wrap: wrap; }
            .ba-btn { padding: 14px 16px; min-height: 48px; border-radius: 8px; font-size: 15px; font-weight: 600; border: 0; cursor: pointer; }
            .ba-btn.primary { background: #2563eb; color: #fff; flex: 1; min-width: 140px; }
            .ba-btn.secondary { background: #fff; color: #374151; border: 1px solid #d1d5db; flex: 1; min-width: 140px; }
            .ba-btn:disabled { opacity: 0.5; cursor: not-allowed; }
            .ba-search-input { width: 100%; padding: 14px; min-height: 48px; font-size: 16px; border: 1px solid #d1d5db; border-radius: 8px; margin-top: 12px; box-sizing: border-box; }
            .ba-results { margin-top: 8px; max-height: 50vh; overflow-y: auto; }
            .ba-result-row { display: flex; gap: 10px; align-items: center; padding: 10px; border-bottom: 1px solid #e5e7eb; cursor: pointer; min-height: 48px; }
            .ba-result-row:active { background: #f3f4f6; }
            .ba-result-row img, .ba-result-row .ba-noimg-sm { width: 40px; height: 40px; object-fit: cover; border-radius: 4px; background: #e5e7eb; flex-shrink: 0; }
            .ba-result-row .badge { font-size: 11px; padding: 2px 6px; border-radius: 10px; font-weight: 600; flex-shrink: 0; }
            .ba-result-row .badge.has-bc { background: #fee2e2; color: #991b1b; }
            .ba-result-row .badge.no-bc { background: #dcfce7; color: #166534; }
            .ba-recent { margin-top: 16px; }
            .ba-recent summary { padding: 10px; background: #f3f4f6; border-radius: 6px; cursor: pointer; font-weight: 600; }
            .ba-recent-row { display: flex; gap: 8px; align-items: center; padding: 8px 4px; border-bottom: 1px solid #e5e7eb; font-size: 13px; }
            .ba-recent-row .bc { font-family: monospace; flex-shrink: 0; }
            .ba-recent-row .nm { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .ba-recent-row .undo-btn { padding: 6px 10px; font-size: 12px; min-height: 32px; background: #fff; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer; }
            .ba-toast { position: fixed; bottom: 16px; left: 50%; transform: translateX(-50%); background: #ef4444; color: #fff; padding: 12px 20px; border-radius: 8px; z-index: 9999; max-width: 90vw; }
            .ba-modal-bg { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9000; padding: 16px; }
            .ba-modal { background: #fff; border-radius: 8px; max-width: 480px; width: 100%; padding: 20px; }
            .ba-picker { background: #fff; width: 100%; max-width: 720px; margin: 0 auto; display: flex; flex-direction: column; height: 100vh; max-height: 100vh; height: 100dvh; max-height: 100dvh; }
            .ba-picker-header { padding: 12px; border-bottom: 1px solid #e5e7eb; flex-shrink: 0; }
            .ba-picker-header .ba-search-input { margin-top: 8px; }
            .ba-picker-list { flex: 1; overflow-y: auto; -webkit-overflow-scrolling: touch; }
            .ba-picker-loading { padding: 16px; text-align: center; color: #6b7280; font-size: 13px; }
            .ba-hidden { display: none !important; }
        </style>
    @endpush

    {{-- v-pre tells Bagisto's Vue 3 (mounted on #app) to skip compiling this subtree.
         Without it, Vue clobbers our class toggles and innerHTML insertions on every
         render cycle. Per memory: feedback_vue_v_pre_for_template_text.md --}}
    <div id="ba-root" class="ba-root" v-pre>
        <div class="ba-counter">
            <span>@lang('inventory-control::app.barcode_assign.scanned'): <span id="ba-cnt-scanned">0</span></span>
            <span>@lang('inventory-control::app.barcode_assign.assigned'): <span id="ba-cnt-assigned">0</span></span>
            <span>@lang('inventory-control::app.barcode_assign.skipped'): <span id="ba-cnt-skipped">0</span></span>
        </div>

        <div class="ba-mode-toggle">
            <button id="ba-mode-camera" type="button">@lang('inventory-control::app.barcode_assign.mode_camera')</button>
            <button id="ba-mode-hardware" type="button">@lang('inventory-control::app.barcode_assign.mode_hardware')</button>
        </div>

        <div id="ba-camera-area" class="ba-hidden">
            <div class="ba-camera-wrap">
                <div id="ba-reader"></div>
                <div id="ba-flash" class="ba-flash"></div>
            </div>
        </div>

        <div id="ba-hardware-area" class="ba-hidden">
            <input
                id="ba-hw-input"
                type="text"
                class="ba-hw-input"
                placeholder="@lang('inventory-control::app.barcode_assign.scan_placeholder')"
                autocomplete="off"
                autocapitalize="off"
                spellcheck="false"
            />
        </div>

        <div id="ba-camera-error" class="ba-status-card ba-hidden" style="border-color:#ef4444;background:#fee2e2;">
            @lang('inventory-control::app.barcode_assign.camera_denied')
        </div>

        <div id="ba-howto" class="ba-status-card" style="border-color:#3b82f6;background:#eff6ff;font-size:14px;line-height:1.5;">
            <div style="font-weight:600;margin-bottom:6px;">@lang('inventory-control::app.barcode_assign.howto_title')</div>
            @lang('inventory-control::app.barcode_assign.howto_body')
        </div>

        <div id="ba-status-assigned" class="ba-status-card assigned ba-hidden">
            <div>⚠️ @lang('inventory-control::app.barcode_assign.already_assigned')</div>
            <div id="ba-assigned-bc" class="ba-barcode"></div>
            <div id="ba-assigned-product" class="ba-product-row"></div>
            <div class="ba-actions">
                <button id="ba-scan-next-btn" type="button" class="ba-btn primary">@lang('inventory-control::app.barcode_assign.scan_next')</button>
                @if($canReassign)
                <button id="ba-reassign-btn" type="button" class="ba-btn secondary">@lang('inventory-control::app.barcode_assign.reassign')</button>
                @endif
            </div>
        </div>

        <div id="ba-status-new" class="ba-status-card new ba-hidden">
            <div>✅ @lang('inventory-control::app.barcode_assign.new_barcode')</div>
            <div id="ba-new-bc" class="ba-barcode"></div>
            <button id="ba-pick-product-btn" type="button" class="ba-btn primary" style="margin-top:10px;width:100%;">
                @lang('inventory-control::app.barcode_assign.pick_product')
            </button>
        </div>

        <div id="ba-picker-modal" class="ba-modal-bg ba-hidden" style="align-items: stretch; padding: 0;">
            <div class="ba-picker">
                <div class="ba-picker-header">
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                        <div style="font-weight:700;font-size:15px;">
                            @lang('inventory-control::app.barcode_assign.pick_for')
                            <span id="ba-picker-bc" class="ba-barcode" style="display:inline;font-size:14px;"></span>
                        </div>
                        <button id="ba-picker-close" type="button" class="ba-btn secondary" style="padding:8px 12px;min-height:36px;flex:initial;min-width:initial;">×</button>
                    </div>
                    <input id="ba-search" type="search" class="ba-search-input"
                        placeholder="@lang('inventory-control::app.barcode_assign.search_placeholder')"
                        autocomplete="off"
                        autocorrect="off"
                        autocapitalize="off"
                        spellcheck="false"
                        inputmode="search"
                        enterkeyhint="search" />
                    <div style="display:flex;align-items:center;gap:8px;margin-top:8px;flex-wrap:wrap;">
                        @if (count($channels) > 1)
                            <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:#374151;">
                                <span>Channel</span>
                                <select id="ba-channel" style="padding:6px 8px;min-height:36px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;background:#fff;">
                                    <option value="">All</option>
                                    @foreach ($channels as $ch)
                                        <option value="{{ $ch['code'] }}">{{ $ch['code'] }}</option>
                                    @endforeach
                                </select>
                            </label>
                        @endif
                        <label id="ba-include-inactive-wrap" style="display:flex;align-items:center;gap:6px;font-size:13px;color:#374151;cursor:pointer;user-select:none;">
                            <input id="ba-include-inactive" type="checkbox" style="width:16px;height:16px;">
                            Include inactive products
                        </label>
                    </div>
                </div>
                <div id="ba-results" class="ba-picker-list"></div>
                <div id="ba-picker-loading" class="ba-picker-loading ba-hidden">@lang('inventory-control::app.barcode_assign.loading')</div>
            </div>
        </div>

        <div id="ba-confirm-modal" class="ba-modal-bg ba-hidden">
            <div class="ba-modal">
                <div id="ba-confirm-text" style="margin-bottom:12px;"></div>
                <div id="ba-confirm-product" class="ba-product-row"></div>
                <div class="ba-actions">
                    <button id="ba-confirm-yes" type="button" class="ba-btn primary">@lang('inventory-control::app.barcode_assign.confirm')</button>
                    <button id="ba-confirm-no" type="button" class="ba-btn secondary">@lang('inventory-control::app.barcode_assign.cancel')</button>
                </div>
            </div>
        </div>

        <details id="ba-recent-wrap" class="ba-recent ba-hidden">
            <summary>@lang('inventory-control::app.barcode_assign.recent_activity') (<span id="ba-recent-count">0</span>)</summary>
            <div id="ba-recent-list"></div>
        </details>

        <div id="ba-toast" class="ba-toast ba-hidden"></div>
    </div>

    @push('scripts')
        <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
        <script>
        console.log('[barcode-assign] script tag executing');
        (function () {
            console.log('[barcode-assign] IIFE entered');
            const ROUTES = {
                lookup: @json(route('admin.barcode_assign.lookup')),
                search: @json(route('admin.barcode_assign.search')),
                assign: @json(route('admin.barcode_assign.assign')),
                undo:   @json(route('admin.barcode_assign.undo')),
            };
            const CSRF = @json(csrf_token());
            const CAN_REASSIGN = @json($canReassign);
            const L_CONFIRM_TEMPLATE = @json(__('inventory-control::app.barcode_assign.confirm_assign', ['barcode' => '__BC__']));
            const L_NO_RESULTS = @json(__('inventory-control::app.barcode_assign.no_results'));
            const L_HAS_BC = 'Has BC';
            const L_FREE = 'Free';
            const L_UNDO = @json(__('inventory-control::app.barcode_assign.undo'));

            const $ = (id) => document.getElementById(id);
            const escHtml = (s) => String(s == null ? '' : s)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#39;');

            const state = {
                mode: localStorage.getItem('ba_mode') || 'hardware',
                qr: null,
                lastBarcode: '',
                forceReassign: false,
                searchTimer: null,
                searchSeq: 0,
                pendingPickProduct: null,
                counters: { scanned: 0, assigned: 0, skipped: 0 },
                recent: [],
                lastResults: [],
                saving: false,
                pickerLoading: false,
                pickerOffset: 0,
                pickerHasMore: true,
                pickerQ: '',
                pickerSeq: 0,
                pickerIncludeInactive: false,
                pickerChannel: '',
            };
            const CHANNELS = {!! json_encode(array_map(fn ($c) => $c['code'], $channels)) !!};

            function beep(freq, ms) {
                try {
                    const ctx = new (window.AudioContext || window.webkitAudioContext)();
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.frequency.value = freq;
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    gain.gain.setValueAtTime(0.15, ctx.currentTime);
                    osc.start();
                    osc.stop(ctx.currentTime + ms / 1000);
                    osc.onended = () => ctx.close();
                } catch (e) {}
            }

            function vibrate(pattern) {
                if (navigator.vibrate) { try { navigator.vibrate(pattern); } catch (e) {} }
            }

            function flash(kind) {
                const el = $('ba-flash');
                if (!el) return;
                el.classList.add(kind === 'warn' ? 'flash-warn' : 'flash-ok');
                setTimeout(() => el.classList.remove('flash-ok', 'flash-warn'), 150);
            }

            async function api(url, opts) {
                opts = opts || {};
                const res = await fetch(url, Object.assign({
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                }, opts));
                let body = null;
                try { body = await res.json(); } catch (e) {}
                return { ok: res.ok, status: res.status, body };
            }

            function showToast(msg) {
                const t = $('ba-toast');
                t.textContent = msg;
                t.classList.remove('ba-hidden');
                clearTimeout(state.toastTimer);
                state.toastTimer = setTimeout(() => t.classList.add('ba-hidden'), 3500);
            }

            function renderCounters() {
                $('ba-cnt-scanned').textContent = state.counters.scanned;
                $('ba-cnt-assigned').textContent = state.counters.assigned;
                $('ba-cnt-skipped').textContent = state.counters.skipped;
            }

            function renderProductBlock(targetEl, p) {
                const img = p.photo_url
                    ? `<img src="${escHtml(p.photo_url)}" alt="">`
                    : `<div class="ba-noimg"></div>`;
                targetEl.innerHTML = `${img}
                    <div class="meta">
                        <div class="name">${escHtml(p.name)}</div>
                        <div class="sku">SKU: ${escHtml(p.sku)}</div>
                    </div>`;
            }

            function applyMode(mode) {
                state.mode = mode;
                localStorage.setItem('ba_mode', mode);
                $('ba-mode-camera').classList.toggle('active', mode === 'camera');
                $('ba-mode-hardware').classList.toggle('active', mode === 'hardware');
                $('ba-camera-area').classList.toggle('ba-hidden', mode !== 'camera');
                $('ba-hardware-area').classList.toggle('ba-hidden', mode !== 'hardware');

                if (mode === 'camera') {
                    startCamera();
                } else {
                    stopCamera();
                    setTimeout(() => $('ba-hw-input').focus(), 50);
                }
            }

            function startCamera() {
                $('ba-camera-error').classList.add('ba-hidden');
                if (!window.Html5Qrcode) {
                    $('ba-camera-error').classList.remove('ba-hidden');
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
                    state.qr = new Html5Qrcode('ba-reader', FORMATS ? { formatsToSupport: FORMATS, verbose: false } : false);
                    // 1D barcodes are wide & short — give the scan box a wide aspect ratio.
                    // qrbox can be a function that adapts to viewport size.
                    const qrbox = (vw, vh) => {
                        const w = Math.floor(Math.min(vw * 0.9, 380));
                        const h = Math.floor(Math.min(vh * 0.5, 180));
                        return { width: w, height: h };
                    };
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
                        console.error('[barcode-assign] camera start failed', err);
                        $('ba-camera-error').classList.remove('ba-hidden');
                    });
                } catch (e) {
                    console.error('[barcode-assign] camera init exception', e);
                    $('ba-camera-error').classList.remove('ba-hidden');
                }
            }

            function stopCamera() {
                if (state.qr) {
                    try { state.qr.stop().then(() => state.qr.clear()).catch(() => {}); } catch (e) {}
                    state.qr = null;
                }
            }

            async function handleScan(barcode) {
                console.log('[barcode-assign] handleScan called with', barcode);
                barcode = String(barcode || '').trim();
                if (!barcode) return;

                // Camera dedupe — same code while a status card is up
                const statusVisible = !$('ba-status-assigned').classList.contains('ba-hidden')
                                   || !$('ba-status-new').classList.contains('ba-hidden');
                if (barcode === state.lastBarcode && statusVisible) return;

                state.counters.scanned++;
                state.lastBarcode = barcode;
                state.forceReassign = false;
                renderCounters();

                const { ok, body } = await api(ROUTES.lookup, {
                    method: 'POST',
                    body: JSON.stringify({ barcode }),
                });

                if (!ok) { showToast('Lookup failed'); return; }

                if (body.found) {
                    showAssigned(barcode, body.product);
                    flash('warn');
                    beep(440, 80);
                    vibrate(60);
                } else {
                    showNew(barcode);
                    flash('ok');
                    beep(880, 80);
                    vibrate(40);
                }
            }

            function showAssigned(barcode, product) {
                $('ba-status-new').classList.add('ba-hidden');
                $('ba-status-assigned').classList.remove('ba-hidden');
                $('ba-assigned-bc').textContent = barcode;
                renderProductBlock($('ba-assigned-product'), product);
            }

            function showNew(barcode) {
                $('ba-status-assigned').classList.add('ba-hidden');
                $('ba-status-new').classList.remove('ba-hidden');
                $('ba-new-bc').textContent = barcode;
                // Auto-open the product picker so the staff member can scroll & tap immediately
                openPicker(barcode);
            }

            function openPicker(barcode) {
                $('ba-picker-bc').textContent = barcode;
                $('ba-search').value = '';
                $('ba-results').innerHTML = '';
                state.lastResults = [];
                state.pickerOffset = 0;
                state.pickerHasMore = true;
                state.pickerQ = '';
                state.pickerIncludeInactive = localStorage.getItem('ba_include_inactive') === '1';
                $('ba-include-inactive').checked = state.pickerIncludeInactive;
                const savedChannel = localStorage.getItem('ba_channel');
                state.pickerChannel = (savedChannel !== null && (savedChannel === '' || CHANNELS.includes(savedChannel)))
                    ? savedChannel
                    : (CHANNELS.length === 1 ? CHANNELS[0] : (CHANNELS[0] || ''));
                const channelEl = $('ba-channel');
                if (channelEl) channelEl.value = state.pickerChannel;
                $('ba-picker-modal').classList.remove('ba-hidden');
                document.body.style.overflow = 'hidden';
                loadMoreProducts();
            }

            function closePicker() {
                $('ba-picker-modal').classList.add('ba-hidden');
                document.body.style.overflow = '';
            }

            async function loadMoreProducts() {
                if (state.pickerLoading || !state.pickerHasMore) return;
                state.pickerLoading = true;
                $('ba-picker-loading').classList.remove('ba-hidden');
                const seq = ++state.pickerSeq;
                const url = ROUTES.search
                    + '?q=' + encodeURIComponent(state.pickerQ)
                    + '&limit=50&offset=' + state.pickerOffset
                    + (state.pickerIncludeInactive ? '&include_inactive=1' : '')
                    + (state.pickerChannel ? '&channel=' + encodeURIComponent(state.pickerChannel) : '');
                const { ok, body } = await api(url, { method: 'GET' });
                state.pickerLoading = false;
                $('ba-picker-loading').classList.add('ba-hidden');
                if (seq !== state.pickerSeq) return;
                if (!ok || !body) return;
                appendResults(body.results || []);
                state.pickerOffset += (body.results || []).length;
                state.pickerHasMore = !!body.has_more;
            }

            function appendResults(rows) {
                state.lastResults = state.lastResults.concat(rows);
                const startIdx = state.lastResults.length - rows.length;
                const html = rows.map((r, i) => renderRow(r, startIdx + i)).join('');
                $('ba-results').insertAdjacentHTML('beforeend', html);
            }

            function renderRow(r, idx) {
                const img = r.photo_url
                    ? `<img src="${escHtml(r.photo_url)}" alt="" loading="lazy">`
                    : `<div class="ba-noimg-sm"></div>`;
                const badgeCls = r.barcode ? 'has-bc' : 'no-bc';
                const badgeTxt = r.barcode ? L_HAS_BC : L_FREE;
                return `<div class="ba-result-row" data-idx="${idx}">
                    ${img}
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:600;font-size:14px;">${escHtml(r.name)}</div>
                        <div style="font-family:monospace;font-size:11px;color:#6b7280;">${escHtml(r.sku)}</div>
                    </div>
                    <span class="badge ${badgeCls}">${escHtml(badgeTxt)}</span>
                </div>`;
            }

            function resetPickerForQuery(q) {
                state.pickerQ = q;
                state.pickerOffset = 0;
                state.pickerHasMore = true;
                state.lastResults = [];
                $('ba-results').innerHTML = '';
                loadMoreProducts();
            }

            function resetForNext() {
                $('ba-status-assigned').classList.add('ba-hidden');
                $('ba-status-new').classList.add('ba-hidden');
                state.lastBarcode = '';
                state.forceReassign = false;
                if (state.mode === 'hardware') setTimeout(() => $('ba-hw-input').focus(), 50);
            }

            function openConfirm(product) {
                state.pendingPickProduct = product;
                $('ba-confirm-text').textContent = L_CONFIRM_TEMPLATE.replace('__BC__', state.lastBarcode);
                renderProductBlock($('ba-confirm-product'), product);
                $('ba-confirm-modal').classList.remove('ba-hidden');
            }

            function closeConfirm() {
                $('ba-confirm-modal').classList.add('ba-hidden');
                state.pendingPickProduct = null;
            }

            async function doConfirm() {
                if (!state.pendingPickProduct || state.saving) return;
                const product = state.pendingPickProduct;
                state.saving = true;
                $('ba-confirm-yes').disabled = true;
                $('ba-confirm-no').disabled = true;

                const force = state.forceReassign || (product.barcode != null);

                const { ok, body } = await api(ROUTES.assign, {
                    method: 'POST',
                    body: JSON.stringify({
                        product_id: product.id,
                        barcode: state.lastBarcode,
                        force_reassign: force,
                    }),
                });

                state.saving = false;
                $('ba-confirm-yes').disabled = false;
                $('ba-confirm-no').disabled = false;

                if (!ok) {
                    showToast(body && body.message ? body.message : 'Save failed — tap Confirm to retry');
                    return;
                }

                state.counters.assigned++;
                renderCounters();

                const logId = (body.log_ids && body.log_ids.length) ? body.log_ids[body.log_ids.length - 1] : null;
                state.recent.unshift({
                    uid: Date.now() + ':' + Math.random(),
                    barcode: state.lastBarcode,
                    product_name: product.name,
                    log_id: logId,
                    reverted: false,
                });
                if (state.recent.length > 5) state.recent.length = 5;
                renderRecent();

                closeConfirm();
                closePicker();
                resetForNext();
            }

            function renderRecent() {
                const wrap = $('ba-recent-wrap');
                if (!state.recent.length) { wrap.classList.add('ba-hidden'); return; }
                wrap.classList.remove('ba-hidden');
                $('ba-recent-count').textContent = state.recent.length;
                $('ba-recent-list').innerHTML = state.recent.map((r) => {
                    const undoCell = (r.reverted || !r.log_id)
                        ? `<span style="font-size:11px;color:#9ca3af;">—</span>`
                        : `<button type="button" class="undo-btn" data-uid="${escHtml(r.uid)}">${escHtml(L_UNDO)}</button>`;
                    return `<div class="ba-recent-row">
                        <span class="bc">${escHtml(r.barcode)}</span>
                        <span class="nm">${escHtml(r.product_name)}</span>
                        ${undoCell}
                    </div>`;
                }).join('');
            }

            async function undoEntry(uid) {
                const row = state.recent.find(r => r.uid === uid);
                if (!row || !row.log_id) return;
                const { ok, body } = await api(ROUTES.undo, {
                    method: 'POST',
                    body: JSON.stringify({ log_id: row.log_id }),
                });
                if (ok && body.success) {
                    row.reverted = true;
                    state.counters.assigned = Math.max(0, state.counters.assigned - 1);
                    renderCounters();
                    renderRecent();
                } else {
                    showToast(body && body.message ? body.message : 'Undo failed');
                }
            }

            // Event wiring — DELEGATED on document so we survive Bagisto's Vue
            // mount on #app (which happens on window.load and replaces all DOM
            // children of #app, blowing away any direct .addEventListener bindings).
            document.addEventListener('click', (e) => {
                const t = e.target.closest('button, .ba-result-row, .undo-btn, #ba-picker-modal, #ba-confirm-modal');
                if (!t) return;
                const id = t.id;
                if (id === 'ba-mode-camera') return applyMode('camera');
                if (id === 'ba-mode-hardware') return applyMode('hardware');
                if (id === 'ba-scan-next-btn') return resetForNext();
                if (id === 'ba-reassign-btn') {
                    if (!CAN_REASSIGN) return;
                    state.forceReassign = true;
                    showNew(state.lastBarcode);
                    return;
                }
                if (id === 'ba-pick-product-btn') return openPicker(state.lastBarcode);
                if (id === 'ba-picker-close') return closePicker();
                if (id === 'ba-picker-modal' && e.target.id === 'ba-picker-modal') return closePicker();
                if (id === 'ba-confirm-yes') return doConfirm();
                if (id === 'ba-confirm-no') return closeConfirm();
                if (id === 'ba-confirm-modal' && e.target.id === 'ba-confirm-modal') return closeConfirm();

                if (t.classList && t.classList.contains('ba-result-row')) {
                    const idx = parseInt(t.getAttribute('data-idx'), 10);
                    const product = state.lastResults[idx];
                    if (product) openConfirm(product);
                    return;
                }
                if (t.classList && t.classList.contains('undo-btn')) {
                    undoEntry(t.getAttribute('data-uid'));
                    return;
                }
            });

            document.addEventListener('keydown', (e) => {
                if (e.target && e.target.id === 'ba-hw-input' && e.key === 'Enter') {
                    console.log('[barcode-assign] Enter on hw-input, value=', e.target.value);
                    e.preventDefault();
                    const v = e.target.value.trim();
                    e.target.value = '';
                    if (v) handleScan(v);
                    setTimeout(() => { const el = $('ba-hw-input'); if (el) el.focus(); }, 0);
                }
            });

            document.addEventListener('input', (e) => {
                if (e.target && e.target.id === 'ba-search') {
                    clearTimeout(state.searchTimer);
                    const q = e.target.value.trim();
                    state.searchTimer = setTimeout(() => resetPickerForQuery(q), 250);
                }
            });

            // iOS Safari: tapping an input inside a fixed modal that was shown
            // via display:none → flex sometimes fails to summon the keyboard.
            // Force focus inside the touchend gesture chain to make it reliable.
            document.addEventListener('touchend', (e) => {
                const el = e.target && e.target.closest && e.target.closest('#ba-search, #ba-hw-input');
                if (!el) return;
                if (document.activeElement !== el) {
                    setTimeout(() => { try { el.focus({ preventScroll: false }); } catch (_) { el.focus(); } }, 0);
                }
            }, { passive: true });

            document.addEventListener('change', (e) => {
                if (e.target && e.target.id === 'ba-include-inactive') {
                    state.pickerIncludeInactive = e.target.checked;
                    localStorage.setItem('ba_include_inactive', e.target.checked ? '1' : '0');
                    resetPickerForQuery(state.pickerQ);
                    return;
                }
                if (e.target && e.target.id === 'ba-channel') {
                    state.pickerChannel = e.target.value;
                    localStorage.setItem('ba_channel', e.target.value);
                    resetPickerForQuery(state.pickerQ);
                }
            });

            // Infinite scroll inside the picker list (capture phase, scroll doesn't bubble)
            document.addEventListener('scroll', (e) => {
                const el = e.target;
                if (!el || el.id !== 'ba-results') return;
                if (el.scrollTop + el.clientHeight >= el.scrollHeight - 200) {
                    loadMoreProducts();
                }
            }, true);

            // Init: defer to next tick so Bagisto's Vue has finished mounting (it
            // mounts on window.load); without this, applyMode() may run against
            // pre-mount DOM that gets replaced.
            function init() {
                console.log('[barcode-assign] init() — DOM elements present?', {
                    root: !!$('ba-root'),
                    hwInput: !!$('ba-hw-input'),
                    modeBtn: !!$('ba-mode-hardware'),
                    pickerModal: !!$('ba-picker-modal'),
                });
                // v-pre on #ba-root (in the Blade template) makes Vue skip compiling
                // this subtree, so our manual DOM mutations are preserved.
                renderCounters();
                applyMode(state.mode);
            }
            if (document.readyState === 'complete') {
                setTimeout(init, 50);
            } else {
                window.addEventListener('load', () => setTimeout(init, 50));
            }

        })();
        </script>
    @endpush
</x-admin::layouts>
