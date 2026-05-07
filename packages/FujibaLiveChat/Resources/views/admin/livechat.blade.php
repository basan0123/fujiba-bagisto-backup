@extends('admin::layouts.master')

@section('page_title')
    Fujiba Live Chat
@endsection

@section('content')
<div class="page-content">

    {{-- ─── Header ──────────────────────────────────────────────── --}}
    <div class="page-header">
        <div class="page-title">
            <h1>Fujiba Live Chat</h1>
        </div>

        {{-- Channel selector --}}
        <div class="page-action" style="display:flex;gap:10px;align-items:center">
            <label for="channel-select"><strong>Channel:</strong></label>
            <select id="channel-select" class="control" style="min-width:180px">
                @foreach($channels as $ch)
                    <option value="{{ $ch->id }}">{{ $ch->name }}</option>
                @endforeach
            </select>
            <button id="open-settings-btn" class="btn btn-sm btn-primary">⚙ Settings</button>
            <button id="health-btn" class="btn btn-sm btn-secondary">🩺 Health</button>
        </div>
    </div>

    {{-- ─── Settings panel (hidden by default) ──────────────────── --}}
    <div id="settings-panel" style="display:none;background:#f8f8f8;border:1px solid #e0e0e0;border-radius:6px;padding:20px;margin-bottom:16px">
        <h3 style="margin-top:0">Channel Settings</h3>
        <form id="settings-form" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <label>Chatpion URL #1 (required)<br>
                <input name="chatpion_url" class="control" placeholder="https://chatpion1.fujiba.shop">
            </label>
            <label>Chatpion URL #2 (optional)<br>
                <input name="chatpion_url_2" class="control" placeholder="">
            </label>
            <label>Chatpion URL #3 (optional)<br>
                <input name="chatpion_url_3" class="control" placeholder="">
            </label>
            <label>API Key<br>
                <input name="api_key" class="control">
            </label>
            <label>Facebook Page ID<br>
                <input name="fb_page_id" class="control">
            </label>
            <label>Bot ID<br>
                <input name="bot_id" class="control">
            </label>
            <label style="display:flex;align-items:center;gap:8px;padding-top:24px">
                <input type="checkbox" name="enabled" value="1" checked> Enabled
            </label>
            <div style="display:flex;gap:8px;align-items:flex-end">
                <button type="submit" class="btn btn-primary">Save Settings</button>
                <button type="button" id="close-settings-btn" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
        <div id="settings-msg" style="margin-top:8px"></div>
    </div>

    {{-- ─── Chat layout ─────────────────────────────────────────── --}}
    <div style="display:flex;gap:0;border:1px solid #ddd;border-radius:6px;overflow:hidden;height:70vh">

        {{-- Left: conversation list --}}
        <div style="width:300px;border-right:1px solid #ddd;display:flex;flex-direction:column">
            <div style="padding:10px;border-bottom:1px solid #eee;display:flex;gap:6px">
                <select id="msg-type" class="control" style="flex:1">
                    <option value="all">All</option>
                    <option value="unread">Unread</option>
                    <option value="assigned">Assigned to me</option>
                </select>
                <button id="refresh-list-btn" class="btn btn-sm btn-secondary">↻</button>
            </div>
            <div id="conv-list" style="flex:1;overflow-y:auto;padding:0"></div>
            <div style="padding:8px;border-top:1px solid #eee;display:flex;gap:6px">
                <button id="load-more-btn" class="btn btn-sm btn-secondary" style="width:100%">Load more…</button>
            </div>
        </div>

        {{-- Right: conversation + reply --}}
        <div style="flex:1;display:flex;flex-direction:column">
            <div id="conv-header" style="padding:10px 14px;border-bottom:1px solid #eee;font-weight:600;background:#fafafa">
                Select a conversation
            </div>

            <div id="conv-messages" style="flex:1;overflow-y:auto;padding:12px;display:flex;flex-direction:column;gap:8px">
                <p style="color:#aaa;text-align:center;margin-top:40px">No conversation selected.</p>
            </div>

            {{-- Subscriber info sidebar --}}
            <div id="subscriber-info" style="display:none;border-top:1px solid #eee;padding:8px 14px;background:#f5f5f5;font-size:12px"></div>

            {{-- Reply bar --}}
            <div style="padding:10px;border-top:1px solid #eee;display:flex;gap:8px">
                <textarea id="reply-input" class="control" rows="2" placeholder="Type a reply…" style="flex:1;resize:none"></textarea>
                <div style="display:flex;flex-direction:column;gap:6px">
                    <button id="send-btn" class="btn btn-primary">Send</button>
                    <select id="bot-flow-select" class="control" style="font-size:12px">
                        <option value="">Send bot flow…</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Health modal --}}
    <div id="health-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:9999;align-items:center;justify-content:center">
        <div style="background:#fff;border-radius:8px;padding:24px;min-width:320px;max-width:480px">
            <h3 style="margin-top:0">Server Health</h3>
            <div id="health-body">Checking…</div>
            <button id="health-close" class="btn btn-secondary" style="margin-top:16px">Close</button>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
    const BASE  = '{{ url(config("app.admin_url")."/fujiba-livechat") }}';
    const CSRF  = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    let channelId   = document.getElementById('channel-select').value;
    let currentConv = null;   // { thread_id, from_user_id, subscriber_id }
    let botId       = null;
    let listOffset  = 0;

    // ── helpers ──────────────────────────────────────────────────────────────
    function apiFetch(path, opts = {}) {
        const defaults = {
            headers: { 'X-CSRF-TOKEN': CSRF, 'X-Channel-Id': channelId, 'Content-Type': 'application/json', 'Accept': 'application/json' }
        };
        return fetch(BASE + path, Object.assign(defaults, opts)).then(r => r.json());
    }

    function escHtml(s) {
        const d = document.createElement('div');
        d.textContent = s ?? '';
        return d.innerHTML;
    }

    // ── channel change ────────────────────────────────────────────────────────
    document.getElementById('channel-select').addEventListener('change', function () {
        channelId = this.value;
        currentConv = null;
        listOffset = 0;
        loadConversations(true);
        loadBotFlows();
        loadSettings();
    });

    // ── settings ──────────────────────────────────────────────────────────────
    document.getElementById('open-settings-btn').addEventListener('click', () => {
        document.getElementById('settings-panel').style.display = 'block';
        loadSettings();
    });
    document.getElementById('close-settings-btn').addEventListener('click', () => {
        document.getElementById('settings-panel').style.display = 'none';
    });

    function loadSettings() {
        apiFetch('/settings/' + channelId).then(data => {
            if (! data) return;
            const f = document.getElementById('settings-form');
            ['chatpion_url','chatpion_url_2','chatpion_url_3','api_key','fb_page_id','bot_id'].forEach(k => {
                if (f[k]) f[k].value = data[k] ?? '';
            });
            if (f.enabled) f.enabled.checked = !! data.enabled;
            if (data.bot_id) botId = data.bot_id;
        });
    }

    document.getElementById('settings-form').addEventListener('submit', function (e) {
        e.preventDefault();
        const payload = {};
        new FormData(this).forEach((v, k) => payload[k] = v);
        payload.enabled = this.enabled.checked ? 1 : 0;
        apiFetch('/settings/' + channelId, {
            method: 'POST',
            body: JSON.stringify(payload)
        }).then(res => {
            document.getElementById('settings-msg').textContent = res.status === 'ok' ? '✓ Saved' : '✗ Error: ' + (res.message ?? '');
            botId = payload.bot_id;
            loadBotFlows();
        });
    });

    // ── health ────────────────────────────────────────────────────────────────
    document.getElementById('health-btn').addEventListener('click', () => {
        const modal = document.getElementById('health-modal');
        modal.style.display = 'flex';
        document.getElementById('health-body').textContent = 'Checking…';
        apiFetch('/health').then(data => {
            document.getElementById('health-body').innerHTML = (Array.isArray(data) ? data : [data]).map(s =>
                `<div style="padding:4px 0;border-bottom:1px solid #eee">
                    <span style="font-size:18px">${s.status === 'ok' ? '✅' : '❌'}</span>
                    <code style="margin-left:8px">${escHtml(s.url)}</code>
                    <span style="margin-left:8px;color:${s.status === 'ok' ? 'green' : 'red'}">${escHtml(s.status)}</span>
                </div>`
            ).join('');
        });
    });
    document.getElementById('health-close').addEventListener('click', () => {
        document.getElementById('health-modal').style.display = 'none';
    });

    // ── conversation list ─────────────────────────────────────────────────────
    function loadConversations(reset = false) {
        if (reset) listOffset = 0;
        if (! botId) { loadSettings(); return; }
        const type = document.getElementById('msg-type').value;
        apiFetch('/conversations?bot_id=' + encodeURIComponent(botId) + '&message_type=' + type + '&start=' + listOffset)
            .then(data => {
                const list = document.getElementById('conv-list');
                if (reset) list.innerHTML = '';
                const rows = data.data ?? data.conversations ?? [];
                rows.forEach(c => {
                    const div = document.createElement('div');
                    div.dataset.threadId = c.thread_id ?? c.from_user_id;
                    div.dataset.fromUserId = c.from_user_id;
                    div.dataset.subscriberId = c.subscriber_id ?? '';
                    div.dataset.name = c.name ?? c.from_name ?? 'Unknown';
                    div.style.cssText = 'padding:10px 14px;cursor:pointer;border-bottom:1px solid #f0f0f0;display:flex;flex-direction:column;gap:2px';
                    div.innerHTML = `<span style="font-weight:600">${escHtml(div.dataset.name)}</span>
                        <span style="font-size:12px;color:#666;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${escHtml(c.last_message ?? '')}</span>`;
                    div.addEventListener('click', () => openConversation(div));
                    list.appendChild(div);
                });
                listOffset += rows.length;
            });
    }

    document.getElementById('refresh-list-btn').addEventListener('click', () => loadConversations(true));
    document.getElementById('load-more-btn').addEventListener('click', () => loadConversations(false));
    document.getElementById('msg-type').addEventListener('change', () => loadConversations(true));

    // ── open conversation ─────────────────────────────────────────────────────
    function openConversation(el) {
        document.querySelectorAll('#conv-list > div').forEach(d => d.style.background = '');
        el.style.background = '#e8f0fe';

        currentConv = {
            thread_id: el.dataset.threadId,
            from_user_id: el.dataset.fromUserId,
            subscriber_id: el.dataset.subscriberId,
        };
        document.getElementById('conv-header').textContent = el.dataset.name;

        apiFetch('/conversation?thread_id=' + encodeURIComponent(currentConv.thread_id)
            + '&bot_id=' + encodeURIComponent(botId)
            + '&from_user_id=' + encodeURIComponent(currentConv.from_user_id))
            .then(data => renderMessages(data));

        if (currentConv.subscriber_id) loadSubscriberInfo(currentConv.subscriber_id);
    }

    function renderMessages(data) {
        const msgs = data.data ?? data.messages ?? [];
        const box = document.getElementById('conv-messages');
        box.innerHTML = '';
        msgs.forEach(m => {
            const isOut = m.message_sent_by === 'page' || m.sent_by === 'admin';
            const div = document.createElement('div');
            div.style.cssText = `max-width:70%;padding:8px 12px;border-radius:12px;word-break:break-word;align-self:${isOut ? 'flex-end' : 'flex-start'};background:${isOut ? '#1a73e8' : '#f1f3f4'};color:${isOut ? '#fff' : '#222'}`;
            div.textContent = m.message ?? m.text ?? '';
            box.appendChild(div);
        });
        box.scrollTop = box.scrollHeight;
    }

    // ── subscriber info ───────────────────────────────────────────────────────
    function loadSubscriberInfo(subId) {
        apiFetch('/subscriber?subscriber_id=' + encodeURIComponent(subId))
            .then(data => {
                const s = data.data ?? data;
                const panel = document.getElementById('subscriber-info');
                panel.style.display = 'block';
                panel.innerHTML = `<strong>${escHtml(s.name ?? '')}</strong>
                    ${s.email ? ' · <a href="mailto:'+escHtml(s.email)+'">'+escHtml(s.email)+'</a>' : ''}
                    ${s.phone ? ' · '+escHtml(s.phone) : ''}`;
            });
    }

    // ── send reply ────────────────────────────────────────────────────────────
    document.getElementById('send-btn').addEventListener('click', () => {
        if (! currentConv) return;
        const msg = document.getElementById('reply-input').value.trim();
        if (! msg) return;
        apiFetch('/reply', {
            method: 'POST',
            body: JSON.stringify({
                bot_id: botId,
                reply_message: msg,
                from_user_id: currentConv.from_user_id,
                thread_id: currentConv.thread_id,
            })
        }).then(() => {
            document.getElementById('reply-input').value = '';
            openConversation(document.querySelector('#conv-list div[style*="e8f0fe"]'));
        });
    });

    // Send on Ctrl+Enter
    document.getElementById('reply-input').addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && e.ctrlKey) document.getElementById('send-btn').click();
    });

    // ── bot flows ─────────────────────────────────────────────────────────────
    function loadBotFlows() {
        if (! botId) return;
        apiFetch('/bot-flows?bot_id=' + encodeURIComponent(botId))
            .then(data => {
                const sel = document.getElementById('bot-flow-select');
                sel.innerHTML = '<option value="">Send bot flow…</option>';
                const flows = data.data ?? data ?? [];
                flows.forEach(f => {
                    const opt = document.createElement('option');
                    opt.value = f.id ?? f.postback_id;
                    opt.textContent = f.name ?? f.title ?? f.id;
                    sel.appendChild(opt);
                });
            });
    }

    document.getElementById('bot-flow-select').addEventListener('change', function () {
        if (! this.value || ! currentConv) return;
        apiFetch('/bot-flow', {
            method: 'POST',
            body: JSON.stringify({
                bot_id: botId,
                subscriber_id: currentConv.subscriber_id,
                postback_id: this.value,
            })
        }).then(() => { this.value = ''; });
    });

    // ── init ──────────────────────────────────────────────────────────────────
    loadSettings();
    setTimeout(() => { loadBotFlows(); loadConversations(true); }, 300);
})();
</script>
@endpush
