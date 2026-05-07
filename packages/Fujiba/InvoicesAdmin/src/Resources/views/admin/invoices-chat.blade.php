{{-- ════════════════════════════════════════════════════════════════
     Invoices listing — chat hooks.
     - Injects a "💬 Open chat" pill below the customer name on each row.
     - Click opens a centered modal that loads the Chatpion conversation
       via /admin/fujiba-livechat/conversation (same as Orders advanced view).
════════════════════════════════════════════════════════════════ --}}

@push('scripts')
<script>
(function () {
    if (window.__fiaChatBooted) return;
    window.__fiaChatBooted = true;

    const META_URL = @json(route('fujiba.invoices.chat_metadata'));
    const CONV_URL = @json(url(config('app.admin_url')) . '/fujiba-livechat/conversation');
    const LIVECHAT = @json(url(config('app.admin_url')) . '/fujiba-livechat');
    const T = {
        title:          @json(__('invoices-admin::app.chat.title')),
        refresh:        @json(__('invoices-admin::app.chat.refresh')),
        open_external:  @json(__('invoices-admin::app.chat.open_external')),
        loading:        @json(__('invoices-admin::app.chat.loading')),
        no_messages:    @json(__('invoices-admin::app.chat.no_messages')),
        load_failed:    @json(__('invoices-admin::app.chat.load_failed')),
        open_chat:      @json(__('invoices-admin::app.chat.open_chat')),
        no_subscriber:  @json(__('invoices-admin::app.chat.no_subscriber')),
        subscriber:     @json(__('invoices-admin::app.chat.subscriber')),
    };

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    // ─── Build & attach the modal at end of <body> so it always paints
    // above the datagrid regardless of where this script is in the layout.
    const modal = document.createElement('div');
    modal.id = 'fia-chat-modal-root';
    modal.style.cssText = 'position:fixed;inset:0;display:none;z-index:99999;font-family:inherit;';
    modal.innerHTML = `
        <div data-fia-bg style="position:absolute;inset:0;background:rgba(15,23,42,.55);"></div>
        <div style="position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);
                    width:min(640px,92vw);max-height:80vh;display:flex;flex-direction:column;
                    background:#fff;border-radius:10px;box-shadow:0 10px 40px rgba(0,0,0,.25);
                    overflow:hidden;">
            <div style="padding:10px 14px;border-bottom:1px solid #e5e7eb;
                        display:flex;align-items:center;justify-content:space-between;gap:8px;">
                <div style="font-size:14px;font-weight:600;color:#111827;">
                    <span>💬 </span>
                    <span data-fia-chat-title>${escapeHtml(T.title)}</span>
                </div>
                <div style="display:flex;align-items:center;gap:6px;">
                    <button data-fia-chat-refresh type="button"
                            style="font-size:12px;padding:4px 8px;border:1px solid #e5e7eb;border-radius:6px;background:#fff;cursor:pointer;color:#374151;">
                        ⟳ ${escapeHtml(T.refresh)}
                    </button>
                    <a data-fia-chat-open target="_blank" rel="noopener"
                       style="font-size:12px;padding:4px 8px;border:1px solid #e5e7eb;border-radius:6px;text-decoration:none;color:#374151;">
                        ${escapeHtml(T.open_external)} ↗
                    </a>
                    <button data-fia-chat-close type="button"
                            style="font-size:18px;line-height:1;padding:2px 8px;border:none;background:transparent;cursor:pointer;color:#6b7280;">×</button>
                </div>
            </div>
            <div data-fia-chat-body style="padding:12px 14px;overflow-y:auto;flex:1;background:#f9fafb;">
                <div data-fia-chat-state style="text-align:center;font-size:13px;color:#6b7280;padding:24px 0;">
                    ${escapeHtml(T.loading)}
                </div>
                <div data-fia-chat-messages style="display:none;"></div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);

    const titleEl   = modal.querySelector('[data-fia-chat-title]');
    const stateEl   = modal.querySelector('[data-fia-chat-state]');
    const msgsEl    = modal.querySelector('[data-fia-chat-messages]');
    const refreshEl = modal.querySelector('[data-fia-chat-refresh]');
    const openLink  = modal.querySelector('[data-fia-chat-open]');
    const bg        = modal.querySelector('[data-fia-bg]');
    const closeBtn  = modal.querySelector('[data-fia-chat-close]');

    const metaCache = new Map();
    let active = null;

    function showModal() { modal.style.display = 'block'; }
    function hideModal() { modal.style.display = 'none'; active = null; }
    bg.addEventListener('click', hideModal);
    closeBtn.addEventListener('click', hideModal);
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.style.display !== 'none') hideModal();
    });

    function livechatLink(sub) {
        if (!sub) return '';
        return LIVECHAT + '?subscriber_id=' + encodeURIComponent(sub);
    }

    // Render an FB-message payload (text + attachments + quick replies).
    // Mirrors orders-view's renderFbMessage().
    function renderFbMessage(mg) {
        if (!mg) return '';
        let out = '';
        if (mg.text) out += escapeHtml(mg.text);
        if (mg.attachment) {
            const att = mg.attachment;
            if (att.type === 'image' && att.payload?.url) {
                out += '<a href="' + escapeHtml(att.payload.url) + '" target="_blank" rel="noopener"><img src="' + escapeHtml(att.payload.url) + '" style="max-width:240px;max-height:240px;border-radius:6px;display:block;margin-top:4px;"></a>';
            } else if (att.payload?.url) {
                out += ' <a href="' + escapeHtml(att.payload.url) + '" target="_blank" rel="noopener" style="color:#2563eb;">[' + escapeHtml(att.type || 'file') + ']</a>';
            } else if (att.type === 'template' && att.payload?.text) {
                out += escapeHtml(att.payload.text);
            }
        }
        if (Array.isArray(mg.attachments)) {
            mg.attachments.forEach(att => {
                if (att.type === 'image' && att.payload?.url) {
                    out += '<a href="' + escapeHtml(att.payload.url) + '" target="_blank" rel="noopener"><img src="' + escapeHtml(att.payload.url) + '" style="max-width:240px;max-height:240px;border-radius:6px;display:block;margin-top:4px;"></a>';
                } else if (att.payload?.url) {
                    out += ' <a href="' + escapeHtml(att.payload.url) + '" target="_blank" rel="noopener" style="color:#2563eb;">[' + escapeHtml(att.type || 'file') + ']</a>';
                }
            });
        }
        return out;
    }

    /*
     * Port of LC.renderMessages — Chatpion stores message bodies in several
     * shapes (raw FB webhook JSON, postback, referral, plain text), so we
     * have to disambiguate. Returns null for system events (delivery/read).
     */
    function parseMessage(m) {
        let dir = (m.direction === 'out' || m.sender === 'bot' || m.sender === 'system') ? 'out' : 'in';
        let raw = m.message || m.message_content || '';
        let html = '';

        if (typeof raw === 'string' && raw.startsWith('{"object":"page"')) {
            try {
                const wh = JSON.parse(raw);
                const mg = wh.entry?.[0]?.messaging?.[0];
                if (mg?.message?.is_echo) dir = 'out';
                else if (mg?.sender?.id && mg?.recipient?.id) dir = 'in';
            } catch (_) {}
        }

        if (typeof raw === 'string' && raw.startsWith('{')) {
            try {
                const j = JSON.parse(raw);
                let mg = null, postback = null, referral = null;
                if (j?.object === 'page' && j?.entry) {
                    const msging = j.entry[0]?.messaging?.[0] || {};
                    mg = msging.message || null;
                    postback = msging.postback || null;
                    referral = msging.referral || msging.message?.referral || null;
                    if (!mg && !postback && !referral && (msging.delivery || msging.read || msging.optin || msging.account_linking)) {
                        return null; // skip system events
                    }
                } else if (j?.message) {
                    mg = j.message;
                } else if (j?.text || j?.attachment) {
                    mg = j;
                }

                if (postback) {
                    html = '<span style="color:#6b7280;font-style:italic;">▸ ' + escapeHtml(postback.title || '[postback]') + '</span>';
                } else if (referral && !mg) {
                    const src = referral.source || '';
                    const ref = referral.ref ? ' · ' + escapeHtml(referral.ref) : '';
                    html = '<span style="color:#6b7280;font-style:italic;">⤷ Opened thread from ' + escapeHtml(src) + ref + '</span>';
                } else {
                    html = renderFbMessage(mg) || '<span style="color:#9ca3af;font-style:italic;">[empty]</span>';
                }
            } catch (_) {
                html = '<span style="color:#9ca3af;font-style:italic;">[unparseable]</span>';
            }
        } else if (raw) {
            html = escapeHtml(raw);
        }

        if (m.media_url) {
            const url = escapeHtml(m.media_url);
            const isImg = /\.(jpg|jpeg|png|gif|webp)$/i.test(m.media_url)
                || ['image', 'photo'].includes((m.message_type || '').toLowerCase());
            if (isImg) {
                const img = '<a href="' + url + '" target="_blank" rel="noopener"><img src="' + url + '" style="max-width:240px;max-height:240px;border-radius:6px;display:block;margin-top:4px;"></a>';
                html = html ? img + '<br>' + html : img;
            } else {
                html += ' <a href="' + url + '" target="_blank" rel="noopener" style="color:#2563eb;">[file]</a>';
            }
        }

        if (!html) return null;

        const time = m.created_at || m.conversation_time || m.timestamp || '';
        const align = dir === 'out' ? 'flex-end' : 'flex-start';
        const bubbleBg = dir === 'out' ? '#dbeafe' : '#fff';
        return '<div style="display:flex;justify-content:' + align + ';margin-bottom:6px;">'
            +    '<div style="max-width:75%;padding:8px 10px;border-radius:8px;background:' + bubbleBg + ';border:1px solid #e5e7eb;font-size:13px;color:#111827;word-break:break-word;">'
            +      html
            +      (time ? '<div style="font-size:10px;color:#9ca3af;margin-top:4px;">' + escapeHtml(time) + '</div>' : '')
            +    '</div>'
            +  '</div>';
    }

    function loadMessages() {
        if (!active) return;
        stateEl.style.display = 'block';
        stateEl.textContent = T.loading;
        msgsEl.style.display = 'none';
        msgsEl.innerHTML = '';

        const params = new URLSearchParams({
            subscriber_id: active.subscriber_id,
            channel_id:    active.channel_id,
        });

        fetch(CONV_URL + '?' + params.toString(), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
        .then(r => r.ok ? r.json() : Promise.reject(r.status))
        .then(j => {
            if (j?.status === 'error') throw new Error(j.message || 'error');
            const raw = (j && j.data) || [];
            const html = raw.map(parseMessage).filter(Boolean).join('');
            if (!html) {
                stateEl.textContent = T.no_messages;
                return;
            }
            stateEl.style.display = 'none';
            msgsEl.style.display = 'block';
            msgsEl.innerHTML = html;
            const body = msgsEl.parentElement;
            if (body) body.scrollTop = body.scrollHeight;
        })
        .catch(() => { stateEl.textContent = T.load_failed; });
    }

    refreshEl.addEventListener('click', loadMessages);

    function openFor(meta) {
        if (!meta || !meta.subscriber_id) return;
        active = meta;
        titleEl.textContent = meta.customer_name || (T.subscriber + ' ' + meta.subscriber_id);
        const link = livechatLink(meta.subscriber_id);
        if (link) { openLink.href = link; openLink.style.display = ''; }
        else { openLink.style.display = 'none'; }
        showModal();
        loadMessages();
    }

    // Single delegated click listener — survives Vue re-renders.
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-fia-chat-btn]');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        const id = parseInt(btn.getAttribute('data-fia-invoice-id') || '0', 10);
        if (!id) return;
        const meta = metaCache.get(id);
        if (meta) openFor(meta);
    }, true);

    // ─── Inject the "💬 Open chat" pill below each customer name ─────
    function fetchMetadataFor(ids) {
        const need = ids.filter(id => !metaCache.has(id));
        if (!need.length) return Promise.resolve();
        return fetch(META_URL + '?ids=' + need.join(','), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
        .then(r => r.ok ? r.json() : Promise.reject(r.status))
        .then(j => {
            (j.data || []).forEach(row => metaCache.set(row.invoice_id, row));
        })
        .catch(() => {});
    }

    function findInvoiceRows() {
        const inputs = document.querySelectorAll('input[id^="mass_action_select_record_"]');
        const map = new Map();
        inputs.forEach(inp => {
            const m = inp.id.match(/^mass_action_select_record_(\d+)$/);
            if (!m) return;
            const id = parseInt(m[1], 10);
            const row = inp.closest('.row');
            if (row) map.set(id, row);
        });
        return map;
    }

    function injectButtons() {
        const rows = findInvoiceRows();
        if (!rows.size) return;
        const ids = Array.from(rows.keys());

        fetchMetadataFor(ids).then(() => {
            rows.forEach((row, id) => {
                if (row.querySelector('[data-fia-chat-btn]')) return;
                const meta = metaCache.get(id);
                if (!meta || !meta.subscriber_id) return;

                // Customer cell = the <p> in the row that contains a small
                // round avatar (or initial) AND a sibling name span.
                const cells = row.querySelectorAll(':scope > p');
                let customerCell = null;
                cells.forEach(p => {
                    if (!customerCell && p.querySelector('.h-7.w-7.rounded-full')) {
                        customerCell = p;
                    }
                });
                if (!customerCell) return;

                const inner = customerCell.querySelector('div.flex.items-center.gap-2');
                if (!inner) return;

                const btn = document.createElement('a');
                btn.setAttribute('data-fia-chat-btn', '1');
                btn.setAttribute('data-fia-invoice-id', String(id));
                btn.setAttribute('role', 'button');
                btn.href = '#';
                btn.title = T.open_chat;
                btn.style.cssText = 'display:inline-flex;align-items:center;gap:4px;margin-top:4px;padding:2px 8px;font-size:11px;line-height:1.4;color:#1d4ed8;text-decoration:none;border:1px solid #bfdbfe;border-radius:9999px;background:#eff6ff;cursor:pointer;width:fit-content;';
                btn.innerHTML = '<span style="font-size:13px;">💬</span><span>' + escapeHtml(T.open_chat) + '</span>';

                // Force the pill to the next line below the name.
                inner.style.flexWrap = 'wrap';
                const breaker = document.createElement('div');
                breaker.style.cssText = 'flex-basis:100%;height:0;';
                breaker.setAttribute('data-fia-chat-breaker', '1');
                inner.appendChild(breaker);
                inner.appendChild(btn);
            });
        });
    }

    // ─── Watch for datagrid re-renders, but ignore mutations inside the modal
    // (otherwise opening the modal triggers re-injection and races).
    let scheduled = false;
    const observer = new MutationObserver((mutations) => {
        // Skip mutations that only touch the modal subtree.
        const relevant = mutations.some(m => !modal.contains(m.target));
        if (!relevant) return;
        if (scheduled) return;
        scheduled = true;
        setTimeout(() => { scheduled = false; injectButtons(); }, 200);
    });
    observer.observe(document.body, { childList: true, subtree: true });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', injectButtons);
    } else {
        injectButtons();
    }
})();
</script>
@endpush
