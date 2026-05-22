<x-admin::layouts>
    <x-slot:title>
        {{ __('fujiba_livechat::app.chat.page_title') }}
    </x-slot>

@push('styles')
<style>
/* ---------- layout ---------- */
.lc-wrap{display:flex;height:calc(100vh - 120px);overflow:hidden;border:1px solid #e2e8f0;border-radius:8px;background:#fff}
/* iOS-safe viewport unit when supported — accounts for the dynamic browser chrome
   (URL bar, bottom toolbar) that vh ignores, so the chat area doesn't extend past
   the visible viewport on mobile Safari/Chrome. */
@supports (height: 100dvh){.lc-wrap{height:calc(100dvh - 120px)}}
/* sidebar */
.lc-sidebar{width:280px;min-width:280px;display:flex;flex-direction:column;border-right:1px solid #e2e8f0}
.lc-sidebar-header{background:linear-gradient(135deg,#3d94fd,#6cbdfc);padding:12px 14px}
.lc-channel-select{width:100%;padding:6px 10px;border:none;border-radius:6px;background:rgba(255,255,255,.2);color:#fff;font-size:12px;cursor:pointer}
.lc-channel-select option{background:#1e293b;color:#fff}
.lc-search-input{width:100%;margin-top:8px;padding:6px 12px;border:none;border-radius:20px;background:rgba(255,255,255,.25);color:#fff;font-size:12px;outline:none}
.lc-search-input::placeholder{color:rgba(255,255,255,.65)}
.lc-tabs{display:flex;border-bottom:1px solid #e2e8f0}
.lc-tab{flex:1;padding:7px 2px;font-size:11px;text-align:center;cursor:pointer;color:#64748b;border-bottom:2px solid transparent;white-space:nowrap}
.lc-tab.active{color:#3d94fd;border-color:#3d94fd;font-weight:500}
.lc-list{flex:1;overflow-y:auto}
.lc-item{display:flex;align-items:center;gap:9px;padding:9px 12px;cursor:pointer;border-bottom:1px solid #f1f5f9;transition:background .1s}
.lc-item:hover,.lc-item.active{background:#f0f7ff}
.lc-avatar{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;flex-shrink:0;color:#fff}
.lc-item-body{flex:1;min-width:0}
.lc-item-name{font-size:13px;font-weight:500;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.lc-item-preview{font-size:11px;color:#94a3b8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:1px}
.lc-unread-badge{background:#3d94fd;color:#fff;border-radius:10px;font-size:10px;padding:1px 6px;min-width:18px;text-align:center}
/* chat area */
.lc-chat{flex:1;display:flex;flex-direction:column;min-width:0}
.lc-chat-top{background:linear-gradient(135deg,#3d94fd,#6cbdfc);padding:10px 14px;display:flex;align-items:center;gap:10px;flex-shrink:0}
.lc-chat-name{font-size:13px;font-weight:500;color:#fff}
.lc-chat-sub{font-size:10px;color:rgba(255,255,255,.75)}
.lc-chat-act{display:flex;gap:5px;margin-left:auto}
.lc-icon-btn{width:28px;height:28px;border-radius:50%;border:none;background:rgba(255,255,255,.2);cursor:pointer;color:#fff;font-size:12px;display:flex;align-items:center;justify-content:center}
.lc-icon-btn:hover{background:rgba(255,255,255,.35)}
.lc-messages{flex:1;overflow-y:auto;padding:12px;display:flex;flex-direction:column;gap:8px;background:#f8fafc}
.lc-msg{display:flex;flex-direction:column;max-width:70%}
.lc-msg.has-carousel{max-width:90%}
.lc-msg.out{align-self:flex-end;align-items:flex-end}
.lc-msg.in{align-self:flex-start}
.lc-bubble{padding:8px 13px;border-radius:12px;font-size:13px;line-height:1.55;white-space:pre-wrap;word-break:break-word}
.lc-msg.in .lc-bubble{background:#fff;border:1px solid #e2e8f0;border-bottom-left-radius:3px}
.lc-msg.out .lc-bubble{background:#dfedf5;border-bottom-right-radius:3px}
.lc-msg-time{font-size:10px;color:#94a3b8;margin-top:2px}
.lc-system-msg{align-self:center;font-size:11px;color:#94a3b8;background:#f1f5f9;border:1px dashed #cbd5e1;border-radius:20px;padding:3px 12px}
.lc-template-bubble{background:#fff;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;max-width:260px}
.lc-template-body{padding:10px 12px;font-size:12px;line-height:1.5}
.lc-template-btn-row{border-top:1px solid #e2e8f0;padding:8px 12px;text-align:center;font-size:12px;color:#3d94fd;font-weight:500}
/* footer */
.lc-footer{padding:8px 10px;border-top:1px solid #e2e8f0;background:#fff;flex-shrink:0}
.lc-footer-tags{display:flex;gap:6px;align-items:center;margin-bottom:6px}
.lc-tag-sel{font-size:11px;padding:4px 8px;border-radius:6px;border:1px solid #e2e8f0;background:#f8fafc;color:#374151;height:28px;outline:none}
.lc-input-row{display:flex;align-items:flex-end;gap:5px}
.lc-act-btn{width:30px;height:30px;border-radius:50%;border:1px solid #e2e8f0;background:transparent;cursor:pointer;color:#64748b;font-size:13px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.lc-act-btn:hover{border-color:#3d94fd;color:#3d94fd;background:#f0f7ff}
.lc-act-btn.recording{border-color:#ef4444;color:#ef4444;background:#fef2f2;animation:pulse 1s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.5}}
.lc-textarea{flex:1;resize:none;border:1px solid #e2e8f0;border-radius:20px;padding:7px 14px;font-size:13px;background:#f8fafc;color:#1e293b;outline:none;height:34px;max-height:100px;line-height:1.4}
.lc-textarea:focus{border-color:#3d94fd;background:#fff}
.lc-send{width:34px;height:34px;border-radius:50%;border:none;background:#3d94fd;cursor:pointer;color:#fff;font-size:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:background .15s}
.lc-send:hover{background:#2876d4}
/* right panel */
.lc-panel{width:260px;min-width:260px;border-left:1px solid #e2e8f0;display:flex;flex-direction:column;background:#fff;overflow-y:auto}
.lc-panel-head{padding:11px 14px;border-bottom:1px solid #e2e8f0;font-size:12px;font-weight:600;color:#374151}
.lc-panel-body{padding:12px;overflow-y:auto}
.lc-section{margin-bottom:16px}
.lc-section-title{font-size:10px;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:7px;font-weight:600}
.lc-info-row{display:flex;justify-content:space-between;margin-bottom:5px;font-size:12px}
.lc-info-label{color:#64748b}
.lc-info-val{color:#1e293b;font-weight:500;text-align:right;max-width:140px;word-break:break-all}
.lc-label-tag{display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;margin:2px;background:#dbeafe;color:#1d4ed8}
.lc-flow-item{display:flex;align-items:center;gap:7px;padding:7px 10px;border-radius:7px;cursor:pointer;border:1px solid #e2e8f0;margin-bottom:5px;font-size:12px;color:#374151;background:#f8fafc;transition:all .15s}
.lc-flow-item:hover{border-color:#3d94fd;color:#3d94fd;background:#f0f7ff}
.lc-flow-icon{width:20px;height:20px;border-radius:5px;background:#3d94fd;color:#fff;font-size:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.lc-server-row{display:flex;align-items:center;gap:6px;padding:5px 9px;border-radius:7px;background:#f8fafc;border:1px solid #e2e8f0;margin-bottom:5px}
.lc-server-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0}
.lc-dot-ok{background:#22c55e}
.lc-dot-warn{background:#f59e0b}
.lc-dot-err{background:#ef4444}
.lc-server-name{font-size:12px;flex:1;color:#374151}
.lc-server-badge{font-size:10px;padding:1px 6px;border-radius:8px;font-weight:500}
.lc-badge-active{background:#dcfce7;color:#166534}
.lc-badge-standby{background:#fef9c3;color:#854d0e}
.lc-badge-down{background:#fee2e2;color:#991b1b}
/* empty state */
.lc-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;color:#94a3b8;font-size:13px;gap:8px}
/* scrollbar */
::-webkit-scrollbar{width:4px;height:4px}
::-webkit-scrollbar-track{background:#f1f5f9}
::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:4px}
/* responsive */
@media(max-width:768px){
    /* Mobile: collapse to fill the visible viewport. The admin layout above
       this view adds extra top/bottom padding that breaks the 120px constant
       used on desktop, so we override with a smaller deduction and prefer
       100dvh which excludes the iOS dynamic browser chrome. */
    .lc-wrap{height:calc(100vh - 80px)}
    @supports (height: 100dvh){.lc-wrap{height:calc(100dvh - 80px)}}
    .lc-sidebar{width:100%;position:absolute;z-index:10;height:100%;background:#fff;transition:transform .25s}
    .lc-sidebar.hidden{transform:translateX(-100%)}
    .lc-panel{display:none}
    .lc-chat{width:100%;min-height:0}
    /* Stop the messages column from pushing the textarea off-screen when the
       on-screen keyboard pops up — the flex parent must allow shrinking. */
    .lc-messages{min-height:0}
    .lc-footer{padding-bottom:max(8px,env(safe-area-inset-bottom))}
}
</style>
@endpush

    <div class="flex items-center justify-between gap-4 mb-5">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            {{ __('fujiba_livechat::app.chat.page_title') }}
        </p>
    </div>

    <div class="lc-wrap" id="lcWrap">

        {{-- SIDEBAR --}}
        <div class="lc-sidebar" id="lcSidebar">
            <div class="lc-sidebar-header">
               <select class="lc-channel-select" id="channelSelect" onchange="LC.switchChannel(this.value, this.options[this.selectedIndex].dataset.tenant)">
    @foreach($settings as $s)
        <option value="{{ $s->channel_id }}"
            data-tenant="{{ $s->tenant_id }}">
            {{ $s->channel_code }}
        </option>
    @endforeach
</select>
                <input class="lc-search-input" id="lcSearch" placeholder="{{ __('fujiba_livechat::app.chat.search_placeholder') }}"
                    oninput="LC._debounceSearch(this.value)" />
            </div>
            <div class="lc-tabs" id="lcTabs">
                <div class="lc-tab active" data-type="all" onclick="LC._switchTab(this)">{{ __('fujiba_livechat::app.chat.tab_all') }}</div>
                <div class="lc-tab" data-type="mine" onclick="LC._switchTab(this)">{{ __('fujiba_livechat::app.chat.tab_mine') }}</div>
                <div class="lc-tab" data-type="unread" onclick="LC._switchTab(this)">{{ __('fujiba_livechat::app.chat.tab_unread') }}</div>
                <div class="lc-tab" data-type="archived" onclick="LC._switchTab(this)">{{ __('fujiba_livechat::app.chat.tab_archived') }}</div>
            </div>
            <div class="lc-list" id="lcList">
                <div class="lc-empty">{{ __('fujiba_livechat::app.chat.loading') }}</div>
            </div>
        </div>

        {{-- CHAT --}}
        <div class="lc-chat">
            <div class="lc-chat-top">
                <button class="lc-icon-btn" id="backBtn" style="display:none" onclick="showSidebar()">&#8592;</button>
                <div class="lc-avatar" id="chatAvatar" style="background:#7F77DD;width:32px;height:32px;font-size:11px">&ndash;</div>
                <div>
                    <div class="lc-chat-name" id="chatName">{{ __('fujiba_livechat::app.chat.select_conversation') }}</div>
                    <div class="lc-chat-sub" id="chatSub"></div>
                </div>
                <div class="lc-chat-act">
                    <button class="lc-icon-btn" id="refreshBtn" title="{{ __('fujiba_livechat::app.chat.refresh') }}" onclick="LC.refreshMessages()">&#8635;</button>
                    <button class="lc-icon-btn" id="infoBtn" title="{{ __('fujiba_livechat::app.chat.subscriber_info') }}" onclick="if(LC.state.activeSubscriberId) LC.loadSubscriberInfo(LC.state.activeSubscriberId)">&#8505;</button>
                    <select id="markAsSelect" class="lc-tag-sel" style="width:90px;height:28px;border-radius:6px;border:none;background:rgba(255,255,255,.2);color:#fff;font-size:11px"
                        onchange="if(this.value){LC.markAs(this.value);this.value='';}">
                        <option value="">{{ __('fujiba_livechat::app.chat.mark_as') }}</option>
                        <option value="read">{{ __('fujiba_livechat::app.chat.mark_read') }}</option>
                        <option value="unread">{{ __('fujiba_livechat::app.chat.mark_unread') }}</option>
                        <option value="archived">{{ __('fujiba_livechat::app.chat.mark_archived') }}</option>
                        <option value="unarchived">{{ __('fujiba_livechat::app.chat.mark_unarchived') }}</option>
                    </select>
                </div>
            </div>

            <div class="lc-messages" id="lcMessages">
                <div class="lc-empty">{{ __('fujiba_livechat::app.chat.select_to_start') }}</div>
            </div>

            <div class="lc-footer">
                <div class="lc-footer-tags">
                    <select class="lc-tag-sel" id="messageTag">
                        <option value="HUMAN_AGENT">HUMAN_AGENT</option>
                        <option value="POST_PURCHASE_UPDATE">POST_PURCHASE_UPDATE</option>
                        <option value="ACCOUNT_UPDATE">ACCOUNT_UPDATE</option>
                        <option value="CONFIRMED_EVENT_UPDATE">CONFIRMED_EVENT_UPDATE</option>
                    </select>
                    <span style="font-size:11px;color:#94a3b8;margin-left:auto">{{ __('fujiba_livechat::app.chat.tag') }}</span>
                </div>
                <div class="lc-input-row">
                    <button class="lc-act-btn" id="botFlowBtn" title="{{ __('fujiba_livechat::app.chat.send_bot_flow') }}" onclick="LC.showBotFlowPicker()">&#129302;</button>
                    <button class="lc-act-btn" id="cannedBtn" title="{{ __('fujiba_livechat::app.chat.canned_response') }}" onclick="LC.showCannedPicker()">&#9889;</button>
                    <button class="lc-act-btn" id="attachBtn" title="{{ __('fujiba_livechat::app.chat.attach_file') }}" onclick="document.getElementById('fileInput').click()">&#128206;</button>
                    <button class="lc-act-btn" id="audioBtn" title="{{ __('fujiba_livechat::app.chat.record_audio') }}">&#127897;</button>
                    <textarea class="lc-textarea" id="replyMsg" placeholder="{{ __('fujiba_livechat::app.chat.type_a_message') }}" rows="1"
                        onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();LC.sendReply();}"
                        oninput="this.style.height='34px';this.style.height=Math.min(this.scrollHeight,100)+'px';"></textarea>
                    <button class="lc-send" id="sendBtn" onclick="LC.sendReply()">&#10148;</button>
                </div>
            </div>
        </div>

        {{-- RIGHT PANEL --}}
        <div class="lc-panel">
            <div class="lc-panel-head">{{ __('fujiba_livechat::app.chat.subscriber') }}</div>
            <div class="lc-panel-body" id="lcPanel">
                <div class="lc-empty" style="height:200px">{{ __('fujiba_livechat::app.chat.select_conversation') }}</div>
            </div>
        </div>

    </div>

    {{-- File upload input (hidden) --}}
    <input type="file" id="fileInput" style="display:none">

@push('scripts')
<script>
const LC = {
    i18n: {!! json_encode([
        'no_conversations'       => __('fujiba_livechat::app.chat.no_conversations'),
        'end'                    => __('fujiba_livechat::app.chat.end'),
        'load_more'              => __('fujiba_livechat::app.chat.load_more'),
        'load_more_loading'      => __('fujiba_livechat::app.chat.load_more_loading'),
        'loading'                => __('fujiba_livechat::app.chat.loading'),
        'select_conversation'    => __('fujiba_livechat::app.chat.select_conversation'),
        'no_messages_yet'        => __('fujiba_livechat::app.chat.no_messages_yet'),
        'now'                    => __('fujiba_livechat::app.chat.now'),
        'failed'                 => __('fujiba_livechat::app.chat.failed'),
        'no_info'                => __('fujiba_livechat::app.chat.no_info'),
        'not_linked'             => __('fujiba_livechat::app.chat.not_linked'),
        'customer'               => __('fujiba_livechat::app.chat.customer'),
        'email'                  => __('fujiba_livechat::app.chat.email'),
        'phone'                  => __('fujiba_livechat::app.chat.phone'),
        'recent_orders'          => __('fujiba_livechat::app.chat.recent_orders'),
        'no_orders'              => __('fujiba_livechat::app.chat.no_orders'),
        'messenger'              => __('fujiba_livechat::app.chat.messenger'),
        'gender'                 => __('fujiba_livechat::app.chat.gender'),
        'locale'                 => __('fujiba_livechat::app.chat.locale'),
        'page'                   => __('fujiba_livechat::app.chat.page'),
        'view_in_bagisto'        => __('fujiba_livechat::app.chat.view_in_bagisto'),
        'send_bot_flow_title'    => __('fujiba_livechat::app.chat.send_bot_flow_title'),
        'search_flows'           => __('fujiba_livechat::app.chat.search_flows'),
        'no_flows_found'         => __('fujiba_livechat::app.chat.no_flows_found'),
        'flow_default_name'      => __('fujiba_livechat::app.chat.flow_default_name'),
        'flow_sent'              => __('fujiba_livechat::app.chat.flow_sent'),
        'flow_failed'            => __('fujiba_livechat::app.chat.flow_failed'),
        'canned_responses_title' => __('fujiba_livechat::app.chat.canned_responses_title'),
        'search_short'           => __('fujiba_livechat::app.chat.search_short'),
        'no_canned'              => __('fujiba_livechat::app.chat.no_canned'),
        'canned_default_name'    => __('fujiba_livechat::app.chat.canned_default_name'),
        'error_loading_messages' => __('fujiba_livechat::app.chat.error_loading_messages'),
        'empty_message'          => __('fujiba_livechat::app.chat.empty_message'),
        'unparseable'            => __('fujiba_livechat::app.chat.unparseable'),
        'postback'               => __('fujiba_livechat::app.chat.postback'),
        'opened_thread_from'     => __('fujiba_livechat::app.chat.opened_thread_from'),
        'image_expired'          => __('fujiba_livechat::app.chat.image_expired'),
        'file'                   => __('fujiba_livechat::app.chat.file'),
        'user_prefix'            => __('fujiba_livechat::app.chat.user_prefix'),
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
    routes: {
        conversations: '{{ route("admin.fujiba.livechat.conversations") }}',
        conversation:  '{{ route("admin.fujiba.livechat.conversation") }}',
        reply:         '{{ route("admin.fujiba.livechat.reply") }}',
        botFlow:       '{{ route("admin.fujiba.livechat.bot-flow") }}',
        botFlows:      '{{ route("admin.fujiba.livechat.bot-flows") }}',
        cannedResponses: '{{ route("admin.fujiba.livechat.canned-responses") }}',
        markAs:        '{{ route("admin.fujiba.livechat.mark-as") }}',
        subscriber:    '{{ route("admin.fujiba.livechat.subscriber") }}',
        upload:        '{{ route("admin.fujiba.livechat.upload") }}',
        health:        '{{ route("admin.fujiba.livechat.health") }}',
    },
    csrf: '{{ csrf_token() }}',
    avatarColors: ['#7F77DD','#3d94fd','#e67e22','#2ecc71','#e74c3c','#1abc9c','#9b59b6','#f39c12'],
    state: {
        channelId: String('{{ $settings->first()?->channel_id ?? "" }}'),
        tenantId: '{{ $settings->first()?->tenant_id ?? "" }}',
        messageType: 'all',
        activeThread: null,
        activeSubscriberId: null,
        activeName: '',
        pollMsg: null,
        pollList: null,
        botFlows: [],
        cannedResponses: [],
        lastMsgCount: 0,
        // Pagination — list shown grows as user scrolls
        loadedThreads: [],
        listLimit: 30,
        loadingMore: false,
        noMore: false,
        searchQuery: '',
        searchTimer: null,
    },

    async api(url, options = {}) {
    try {
        let bodyData = {};

        // 🔥 HANDLE POST (VERY IMPORTANT)
        if (options.method === 'POST') {
            bodyData = options.body ? JSON.parse(options.body) : {};

            // ✅ FORCE channel_id into body
            bodyData.channel_id = this.state.channelId;

            options.body = JSON.stringify(bodyData);

        } else {
            // 🔥 HANDLE GET
            if (!url.includes('channel_id=')) {
                url += (url.includes('?') ? '&' : '?') + 'channel_id=' + this.state.channelId;
            }
        }

        console.log('[LC API]', url, bodyData); // debug

        const res = await fetch(url, {
            ...options,
            headers: {
                'X-CSRF-TOKEN': this.csrf,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                ...(options.headers || {})
            }
        });

        if (!res.ok) {
            console.error('[LC API ERROR]', res.status, res.statusText);
        }

        return await res.json();

    } catch (e) {
        console.error('[LC EXCEPTION]', e.message);
        return { status: 'error', message: e.message };
    }
},
    colorFor(n) { let h=0; for(let i=0;i<(n||'').length;i++) h=n.charCodeAt(i)+((h<<5)-h); return this.avatarColors[Math.abs(h)%8]; },
    esc(s) { const d=document.createElement('div'); d.textContent=s; return d.innerHTML; },
    initials(n) { return (n||'?').split(/\s+/).map(w=>w[0]||'').join('').substring(0,2).toUpperCase(); },
    isExpiredFbPic(url) { const m=url.match(/ext=(\d+)/); return m && parseInt(m[1])*1000 < Date.now(); },
    cleanPreview(msg) {
        if (! msg) return '';
        if (msg.startsWith('{')) {
            try {
                const j = JSON.parse(msg);
                let mg = null;
                let postback = null;
                let referral = null;
                if (j?.object === 'page' && j?.entry) {
                    const ms = j.entry[0]?.messaging?.[0] || {};
                    if (ms.delivery || ms.read || ms.optin) return '';
                    mg = ms.message;
                    postback = ms.postback;
                    referral = ms.referral || ms.message?.referral;
                } else if (j?.message) {
                    mg = j.message;
                } else if (j?.text || j?.attachment) {
                    mg = j;
                }
                if (postback?.title) return '▸ ' + postback.title.substring(0, 48);
                if (referral && !mg) return '⤷ ' + (referral.source || 'referral');
                if (mg?.text) return mg.text.substring(0, 50);
                if (mg?.attachment?.type === 'template') {
                    const p = mg.attachment.payload || {};
                    if (p.text) return p.text.substring(0, 50);
                    if (p.elements?.[0]?.title) return p.elements[0].title.substring(0, 50);
                    return '[template]';
                }
                if (mg?.attachment?.type) return '[' + mg.attachment.type + ']';
                if (mg?.attachments?.[0]?.type) return '[' + mg.attachments[0].type + ']';
                return '';
            } catch(e) {}
        }
        return msg.substring(0, 50);
    },

    // Render a Facebook `message` object (the inner one — not the wrapper).
    // Handles plain text, image/video/audio/file attachments, generic and
    // button templates, and the `attachments` array form used by the
    // webhook payload. Returns an HTML string, or '' if nothing useful.
    renderFbMessage (mg) {
        if (! mg) return '';
        const parts = [];
        const renderAttachment = (att) => {
            if (! att) return '';
            const t = att.type;
            const p = att.payload || {};
            if (t === 'image' && p.url) {
                return `<img src="${this.esc(p.url)}" style="max-width:200px;border-radius:8px" onerror="this.outerHTML='[Image expired]'">`;
            }
            if (t === 'video' && p.url) {
                return `<video controls src="${this.esc(p.url)}" style="max-width:240px;border-radius:8px"></video>`;
            }
            if (t === 'audio' && p.url) {
                return `<audio controls src="${this.esc(p.url)}" style="max-width:240px"></audio>`;
            }
            if (t === 'file' && p.url) {
                return `<a href="${this.esc(p.url)}" target="_blank" style="color:#3d94fd">[File]</a>`;
            }
            if (t === 'template' && p.template_type === 'generic' && Array.isArray(p.elements) && p.elements.length) {
                const cards = p.elements.map(e => {
                    const title = e.title && e.title !== '.' && e.title !== '。' ? `<div style="font-weight:600;font-size:12px;padding:6px 8px 2px">${this.esc(e.title)}</div>` : '';
                    const subtitle = e.subtitle ? `<div style="color:#64748b;font-size:11px;padding:0 8px 4px">${this.esc(e.subtitle)}</div>` : '';
                    const img = e.image_url ? `<img src="${this.esc(e.image_url)}" style="width:100%;height:110px;object-fit:cover;display:block">` : '';
                    const btns = (Array.isArray(e.buttons) && e.buttons.length)
                        ? e.buttons.map(b => `<a href="${this.esc(b.url || '#')}" target="_blank" style="display:block;padding:6px 8px;border-top:1px solid #e2e8f0;color:#3d94fd;text-align:center;font-size:12px;text-decoration:none">${this.esc(b.title)}</a>`).join('')
                        : '';
                    return `<div style="flex:0 0 170px;background:#fff;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden">${img}${title}${subtitle}${btns}</div>`;
                }).join('');
                return `<div style="display:flex;gap:8px;overflow-x:auto;padding:4px 0;max-width:520px;scrollbar-width:thin">${cards}</div>`;
            }
            if (t === 'template' && p.template_type === 'button') {
                let s = this.esc(p.text || '');
                if (Array.isArray(p.buttons) && p.buttons.length) {
                    s += '<br>' + p.buttons.map(b => `<a href="${this.esc(b.url || '#')}" target="_blank" style="color:#3d94fd">${this.esc(b.title)}</a>`).join(' · ');
                }
                return s;
            }
            return `[${this.esc(t || 'attachment')}]`;
        };

        if (mg.text) parts.push(this.esc(mg.text));
        if (mg.attachment) parts.push(renderAttachment(mg.attachment));
        if (Array.isArray(mg.attachments)) parts.push(...mg.attachments.map(renderAttachment));
        if (Array.isArray(mg.quick_replies) && mg.quick_replies.length) {
            const qrs = mg.quick_replies
                .filter(q => q.title)
                .map(q => `<span style="display:inline-block;padding:2px 8px;border:1px solid #e2e8f0;border-radius:12px;font-size:11px;color:#475569;margin:2px 2px 0 0">${this.esc(q.title)}</span>`)
                .join('');
            if (qrs) parts.push(qrs);
        }
        return parts.filter(Boolean).join('<br>');
    },

    // ── Conversations ──
    // Best display name for a thread row, with sensible fallbacks.
    // Prefer full_name when it's non-empty and at least as long as first+last,
    // because Facebook's first/last split drops middle words ("Moe Su Hlaing"
    // arrives as first="Moe" last="Hlaing" while full_name keeps "Moe Su Hlaing").
    threadName (t) {
        const fn = (t.first_name || '').trim();
        const ln = (t.last_name  || '').trim();
        const full = (t.full_name || '').trim();
        const joined = (fn + ' ' + ln).trim();
        if (full && full !== '0' && full.length >= joined.length) return full;
        if (joined) return joined;
        if (full && full !== '0') return full;
        const fu = (t.from_user || '').trim();
        if (fu && fu !== '0') return fu;
        return this.i18n.user_prefix + (t.from_user_id || '').slice(-4);
    },

    // Internal: fetch the current page (size = listLimit, plus current search/tab).
    async fetchList () {
        const p = new URLSearchParams({
            channel_id: this.state.channelId,
            message_type: this.state.messageType,
            media_type: 'fb',
            start: 0,
            limit: this.state.listLimit,
        });
        const q = (this.state.searchQuery || '').trim();
        if (q) p.set('search', q);
        const data = await this.api(`${this.routes.conversations}?${p}`);
        return data?.data || [];
    },

    // Initial / channel-switch load. Resets pagination AND search.
    async loadConversations () {
        this.state.listLimit = 30;
        this.state.noMore = false;
        const all = await this.fetchList();
        this.state.loadedThreads = all;
        if (all.length < this.state.listLimit) this.state.noMore = true;
        this.renderList();
    },

    // Polling refresh — keeps the user's current scroll depth (listLimit)
    // and search query. Replaces in-place so user doesn't lose context.
    async refreshList () {
        const all = await this.fetchList();
        if (all.length < this.state.listLimit) this.state.noMore = true;
        this.state.loadedThreads = all;
        this.renderList();
    },

    // Bumps the limit and re-fetches with the new page size. Backend always
    // returns sorted-by-time-desc; our paging is just "show more of the same
    // sorted result". Hooks into onscroll near the bottom of the sidebar.
    async loadMore () {
        if (this.state.loadingMore || this.state.noMore) return;
        this.state.loadingMore = true;
        const oldLen = this.state.loadedThreads.length;
        this.state.listLimit += 30;
        const all = await this.fetchList();
        if (all.length === oldLen) this.state.noMore = true;
        this.state.loadedThreads = all;
        this.state.loadingMore = false;
        this.renderList();
    },

    // Backend search across the entire DB. Debounced from the search input.
    async runSearch (q) {
        this.state.searchQuery = q || '';
        this.state.listLimit = q ? 60 : 30;
        this.state.noMore = false;
        const all = await this.fetchList();
        this.state.loadedThreads = all;
        if (all.length < this.state.listLimit) this.state.noMore = true;
        this.renderList();
    },

    _debounceSearch (q) {
        clearTimeout(this.state.searchTimer);
        this.state.searchTimer = setTimeout(() => this.runSearch(q), 300);
    },

    renderList () {
        const el = document.getElementById('lcList');
        const threads = this.state.loadedThreads || [];
        if (!threads.length) { el.innerHTML = `<div class="lc-empty">${this.esc(this.i18n.no_conversations)}</div>`; return; }
        el.innerHTML = '';
        threads.forEach(t => {
            const name = this.threadName(t);
            const pic = t.profile_pic || '';
            const unread = parseInt(t.unread_count||0);
            const isActive = t.from_user_id === this.state.activeSubscriberId;
            const item = document.createElement('div');
            item.className = 'lc-item' + (isActive ? ' active' : '');
            item.dataset.uid = t.from_user_id || '';
            item.dataset.tid = t.thread_id || t.id || '';
            const avatarHtml = pic && !this.isExpiredFbPic(pic)
                ? `<img class="lc-avatar" src="${this.esc(pic)}" style="object-fit:cover" referrerpolicy="no-referrer" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"><div class="lc-avatar" style="background:${this.colorFor(name)};display:none">${this.initials(name)}</div>`
                : `<div class="lc-avatar" style="background:${this.colorFor(name)}">${this.initials(name)}</div>`;
            const time = t.last_message_time ? this.relTime(t.last_message_time) : '';
            item.innerHTML = `${avatarHtml}
                <div class="lc-item-body">
                    <div class="lc-item-name">${this.esc(name)}</div>
                    <div class="lc-item-preview">${this.esc(this.cleanPreview(t.last_message||''))}</div>
                </div>
                <div style="text-align:right;flex-shrink:0">
                    <div style="font-size:10px;color:#94a3b8">${time}</div>
                    ${unread > 0 ? `<span class="lc-unread-badge">${unread}</span>` : ''}
                </div>`;
            item.addEventListener('click', () => this.openConversation(t.from_user_id, t.thread_id||t.id, name, pic, item));
            el.appendChild(item);
        });
        if (this.state.noMore) {
            const end = document.createElement('div');
            end.className = 'lc-empty';
            end.style.cssText = 'padding:8px;font-size:11px;color:#cbd5e1';
            end.textContent = this.i18n.end;
            el.appendChild(end);
        } else {
            // Always-visible "Load more" button — bulletproof fallback when
            // the inner list isn't the scrolling container (admin layouts
            // sometimes put scroll on the page itself, killing scroll events
            // on .lc-list). Also targeted by IntersectionObserver below to
            // auto-fire when the sentinel scrolls into view.
            const more = document.createElement('button');
            more.id = 'lcLoadMore';
            more.type = 'button';
            more.textContent = this.state.loadingMore ? this.i18n.load_more_loading : this.i18n.load_more;
            more.style.cssText = 'display:block;width:calc(100% - 16px);margin:8px;padding:8px;border:1px dashed #cbd5e1;background:#f8fafc;color:#3d94fd;border-radius:6px;cursor:pointer;font-size:12px';
            more.onclick = () => LC.loadMore();
            el.appendChild(more);

            // Replace the observer so the sentinel always points at the
            // freshly-appended button. This works whether scrolling happens
            // on .lc-list, .lc-wrap, or the document body — IntersectionObserver
            // doesn't care which ancestor is the scroll container.
            if (this._listObserver) this._listObserver.disconnect();
            this._listObserver = new IntersectionObserver((entries) => {
                if (entries.some(e => e.isIntersecting)) LC.loadMore();
            }, { rootMargin: '200px' });
            this._listObserver.observe(more);
        }
    },

    relTime(dt) {
        const d = new Date(dt.replace(' ','T')+'Z');
        const diff = (Date.now() - d.getTime())/1000;
        if (diff < 60) return this.i18n.now;
        if (diff < 3600) return Math.floor(diff/60)+'m';
        if (diff < 86400) return Math.floor(diff/3600)+'h';
        return Math.floor(diff/86400)+'d';
    },

    // ── Open conversation ──
    async openConversation(uid, tid, name, pic, itemEl) {
        document.querySelectorAll('#lcList .lc-item').forEach(x=>x.classList.remove('active'));
        itemEl?.classList.add('active');
        this.state.activeThread = tid;
        this.state.activeSubscriberId = uid;
        this.state.activeName = name;
        this.state.lastMsgCount = 0;
        document.getElementById('chatName').textContent = name;
        const av = document.getElementById('chatAvatar');
        if (pic) { av.innerHTML = `<img src="${this.esc(pic)}" style="width:32px;height:32px;border-radius:50%;object-fit:cover" onerror="this.outerHTML='${this.initials(name)}'">`;av.style.background='transparent'; }
        else { av.textContent = this.initials(name); av.style.background = this.colorFor(name); }
        document.getElementById('chatSub').textContent = `ID: ${uid}`;
        document.getElementById('lcMessages').innerHTML = `<div class="lc-empty">${this.esc(this.i18n.loading)}</div>`;
        await this.refreshMessages();
        this.loadSubscriberInfo(uid);
        this.startMsgPoll();
        if (window.innerWidth<=768) { document.getElementById('lcSidebar').classList.add('hidden'); document.getElementById('backBtn').style.display=''; }
    },

    // ── Messages ──
    async refreshMessages() {
        if (!this.state.activeSubscriberId) return;
        const p = new URLSearchParams({channel_id:this.state.channelId, thread_id:this.state.activeThread||'', from_user_id:this.state.activeSubscriberId});
        const data = await this.api(`${this.routes.conversation}?${p}`);
        const msgs = data?.data || [];
        if (msgs.length === this.state.lastMsgCount) return; // no change
        this.state.lastMsgCount = msgs.length;
        this.renderMessages(data);
    },

    renderMessages(data) {
        const el = document.getElementById('lcMessages');
        if (data?.status==='error') { el.innerHTML = `<div class="lc-empty" style="color:#ef4444">${this.esc(data.message||this.i18n.error_loading_messages)}</div>`; return; }
        const msgs = data?.data || [];
        if (!msgs.length) { el.innerHTML = `<div class="lc-empty">${this.esc(this.i18n.no_messages_yet)}</div>`; return; }
        el.innerHTML = '';
        msgs.forEach(m => {
            let dir = (m.direction==='out'||m.sender==='bot'||m.sender==='system') ? 'out' : 'in';
            const div = document.createElement('div');
            let rawMsg = m.message||m.message_content||'';
            // For raw webhook JSON, check is_echo to determine direction
            if (rawMsg.startsWith('{"object":"page"')) {
                try { const wh=JSON.parse(rawMsg); const mg=wh.entry?.[0]?.messaging?.[0]; if(mg?.message?.is_echo) dir='out'; else if(mg?.sender?.id && mg?.recipient?.id) dir='in'; } catch(e){}
            }
            div.className = `lc-msg ${dir}`;
            let content = '';
            if (rawMsg.startsWith('{')) {
                try {
                    const j = JSON.parse(rawMsg);
                    // Pick the inner payload across the formats Chatpion stores:
                    //   webhook incoming msg:  {object:'page', entry[0].messaging[0].message}
                    //   webhook postback:      {object:'page', entry[0].messaging[0].postback{title,payload}}
                    //   webhook referral:      {object:'page', entry[0].messaging[0].referral{source,type,ref}}
                    //   webhook delivery/read/optin: skip
                    //   bot send:              {recipient:{}, message:{...}, messaging_type:'RESPONSE'}
                    let mg = null;
                    let postback = null;
                    let referral = null;
                    if (j?.object === 'page' && j?.entry) {
                        const msging = j.entry[0]?.messaging?.[0] || {};
                        mg = msging.message || null;
                        postback = msging.postback || null;
                        referral = msging.referral || msging.message?.referral || null;
                        if (!mg && !postback && !referral && (msging.delivery || msging.read || msging.optin || msging.account_linking)) {
                            // FB system events (delivery/read/optin/etc.) — skip
                            return;
                        }
                    } else if (j?.message) {
                        mg = j.message;
                    } else if (j?.text || j?.attachment) {
                        mg = j;
                    }

                    if (postback) {
                        content = `<span style="color:#64748b;font-style:italic">▸ ${this.esc(postback.title || this.i18n.postback)}</span>`;
                    } else if (referral && !mg) {
                        // Referral-only event: customer opened the thread via m.me link / QR / ad
                        const src = referral.source || '';
                        const ref = referral.ref ? ' · ' + this.esc(referral.ref) : '';
                        content = `<span style="color:#94a3b8;font-style:italic;font-size:11px">⤷ ${this.esc(this.i18n.opened_thread_from)} ${this.esc(src)}${ref}</span>`;
                    } else {
                        content = this.renderFbMessage(mg) || `<span style="color:#94a3b8;font-style:italic">${this.esc(this.i18n.empty_message)}</span>`;
                    }
                } catch(e) { content = `<span style="color:#94a3b8;font-style:italic">${this.esc(this.i18n.unparseable)}</span>`; }
            } else {
                content = this.esc(rawMsg);
            }
            if (m.media_url) {
                if (/\.(jpg|jpeg|png|gif|webp)$/i.test(m.media_url)||['image','photo'].includes((m.message_type||'').toLowerCase()))
                    content = `<img src="${this.esc(m.media_url)}" style="max-width:200px;border-radius:8px">`+(content?`<br>${content}`:'');
                else content += ` <a href="${this.esc(m.media_url)}" target="_blank" style="color:#3d94fd">[file]</a>`;
            }
            if (content.includes('overflow-x:auto')) div.classList.add('has-carousel');
            div.innerHTML = `<div class="lc-bubble">${content}</div><div class="lc-msg-time">${this.esc(m.created_at||m.conversation_time||'')}</div>`;
            el.appendChild(div);
        });
        el.scrollTop = el.scrollHeight;
    },

    // ── Reply ──
    async sendReply() {
        const msgEl = document.getElementById('replyMsg');
        const msg = msgEl.value.trim();
        if (!msg || !this.state.activeSubscriberId) {
            console.warn('[LC] sendReply blocked: msg='+!!msg+' subscriberId='+this.state.activeSubscriberId);
            return;
        }
        msgEl.value=''; msgEl.style.height='34px';
        const el = document.getElementById('lcMessages');
        const div = document.createElement('div');
        div.className='lc-msg out';
        div.innerHTML=`<div class="lc-bubble">${this.esc(msg)}</div><div class="lc-msg-time">${this.esc(this.i18n.now)}</div>`;
        el.appendChild(div); el.scrollTop=el.scrollHeight;
        const r = await this.api(this.routes.reply, {method:'POST', body:JSON.stringify({
            channel_id:this.state.channelId, from_user_id:this.state.activeSubscriberId,
            thread_id:this.state.activeThread||null, message:msg, message_tag:document.getElementById('messageTag').value
        })});
        console.log('[LC] sendReply response:', r);
        if (r.status==='error') { div.querySelector('.lc-bubble').style.borderColor='#ef4444'; div.querySelector('.lc-msg-time').textContent=this.i18n.failed+': '+(r.message||'error'); }
    },

    async markAs(action) {
        if (!this.state.activeSubscriberId||!action) return;
        await this.api(this.routes.markAs, {method:'POST', body:JSON.stringify({channel_id:this.state.channelId, from_user_id:this.state.activeSubscriberId, action})});
        this.loadConversations();
    },

    // ── Subscriber ──
    async loadSubscriberInfo(sid) {
        const panel = document.getElementById('lcPanel');
        panel.innerHTML = `<div class="lc-empty" style="height:100px">${this.esc(this.i18n.loading)}</div>`;
        const data = await this.api(`${this.routes.subscriber}?subscriber_id=${sid}&channel_id=${this.state.channelId}`);
        if (! data?.subscriber_info) {
            panel.innerHTML = `<div class="lc-empty" style="height:100px">${this.esc(this.i18n.no_info)}</div>`;
            return;
        }
        const s = data.subscriber_info;
        const c = data.bagisto_customer;
        const orders = Array.isArray(data.bagisto_orders) ? data.bagisto_orders : [];

        const photo = c?.image ? `/storage/${this.esc(c.image)}` : (s.profile_pic || '');
        // Prefer Chatpion full_name when present (FB's first/last split drops middle words).
        const subFull = (s.full_name || '').trim();
        const cJoined = c ? ((c.first_name || '') + ' ' + (c.last_name || '')).trim() : '';
        const fullName = (subFull && subFull.length >= cJoined.length ? subFull : cJoined) || subFull;

        let html = '';

        // Top: avatar + name + Bagisto link
        if (photo) {
            html += `<div style="text-align:center;padding:8px 0 12px"><img src="${this.esc(photo)}" referrerpolicy="no-referrer" onerror="this.style.display='none'" style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid #e2e8f0"><div style="font-weight:600;font-size:13px;margin-top:6px;color:#1e293b">${this.esc(fullName||'?')}</div>${c ? `<a href="/admin/customers/customers/edit/${c.id}" target="_blank" style="font-size:11px;color:#3d94fd">${this.esc(this.i18n.view_in_bagisto)} · #${c.id}</a>` : ''}</div>`;
        }

        // Bagisto customer record (linked)
        html += `<div class="lc-section"><div class="lc-section-title">${this.esc(this.i18n.customer)}</div>`;
        if (c) {
            html += `<div class="lc-info-row"><span class="lc-info-label">${this.esc(this.i18n.email)}</span><span class="lc-info-val">${this.esc(c.email||'-')}</span></div>`;
            html += `<div class="lc-info-row"><span class="lc-info-label">${this.esc(this.i18n.phone)}</span><span class="lc-info-val">${this.esc(c.phone||'-')}</span></div>`;
        } else {
            html += `<div class="lc-empty" style="height:auto;padding:8px 0;font-size:11px">${this.esc(this.i18n.not_linked)}</div>`;
        }
        html += `</div>`;

        // Recent orders
        html += `<div class="lc-section"><div class="lc-section-title">${this.esc(this.i18n.recent_orders)} (${orders.length})</div>`;
        if (orders.length) {
            html += orders.map(o =>
                `<a href="/admin/sales/orders/view/${o.id}" target="_blank" style="display:block;padding:6px 8px;border:1px solid #e2e8f0;border-radius:6px;margin-bottom:4px;font-size:11px;color:#374151;text-decoration:none">
                    <div style="font-weight:500">#${this.esc(o.increment_id||o.id)}</div>
                    <div style="display:flex;justify-content:space-between;color:#64748b;font-size:10px;margin-top:2px">
                        <span>${this.esc(o.status||'')}</span>
                        <span>${this.esc((o.order_currency_code||'')+' '+ (o.grand_total||''))}</span>
                    </div>
                </a>`).join('');
        } else {
            html += `<div class="lc-empty" style="height:auto;padding:8px 0;font-size:11px">${this.esc(this.i18n.no_orders)}</div>`;
        }
        html += `</div>`;

        // Chatpion subscriber data (for reference)
        html += `<div class="lc-section"><div class="lc-section-title">${this.esc(this.i18n.messenger)}</div>
            <div class="lc-info-row"><span class="lc-info-label">${this.esc(this.i18n.gender)}</span><span class="lc-info-val">${this.esc(s.gender||'-')}</span></div>
            <div class="lc-info-row"><span class="lc-info-label">${this.esc(this.i18n.locale)}</span><span class="lc-info-val">${this.esc(s.locale||'-')}</span></div>
            <div class="lc-info-row"><span class="lc-info-label">${this.esc(this.i18n.page)}</span><span class="lc-info-val">${this.esc(s.page_name||'-')}</span></div>
        </div>`;

        panel.innerHTML = html;
    },

    // ── Bot flows ──
    async loadBotFlows() {
        console.log('[LC] loadBotFlows channel_id='+this.state.channelId);
        const data = await this.api(`${this.routes.botFlows}?channel_id=${this.state.channelId}`);
        console.log('[LC] botFlows result:', data);
        this.state.botFlows = Array.isArray(data) ? data : (data?.data||data?.flows||[]);
    },
    showBotFlowPicker() {
        if (!this.state.activeSubscriberId) return;
        const flows = this.state.botFlows;
        // Show as floating overlay on top of panel
        let overlay = document.getElementById('lc-flow-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'lc-flow-overlay';
            overlay.style.cssText = 'position:absolute;top:0;right:0;width:260px;height:100%;z-index:20;background:#fff;border-left:1px solid #e2e8f0;display:flex;flex-direction:column;overflow:hidden';
            document.getElementById('lcWrap').style.position='relative';
            document.getElementById('lcWrap').appendChild(overlay);
        }
        overlay.style.display='flex';
        overlay.innerHTML = `
            <div style="background:linear-gradient(135deg,#3d94fd,#6cbdfc);padding:10px 14px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0">
                <span style="color:#fff;font-size:13px;font-weight:500">${this.esc(this.i18n.send_bot_flow_title)}</span>
                <button onclick="document.getElementById('lc-flow-overlay').style.display='none'" style="background:rgba(255,255,255,.2);border:none;color:#fff;border-radius:50%;width:22px;height:22px;cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center">&times;</button>
            </div>
            <input placeholder="${this.esc(this.i18n.search_flows)}" oninput="LC._filterFlows(this.value)" style="margin:8px;padding:6px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;outline:none">
            <div id="lc-flow-list" style="flex:1;overflow-y:auto;padding:4px 8px">
                ${!flows.length ? `<div style="padding:20px;text-align:center;color:#94a3b8;font-size:12px">${this.esc(this.i18n.no_flows_found)}</div>` :
                flows.map(f=>`<div class="lc-flow-item" data-pb="${f.postback_id||f.id}" data-name="${this.esc(f.template_name||f.name||this.i18n.flow_default_name).toLowerCase()}"><div class="lc-flow-icon">&#9654;</div><span>${this.esc(f.template_name||f.name||this.i18n.flow_default_name)}</span></div>`).join('')}
            </div>`;
        overlay.querySelectorAll('.lc-flow-item').forEach(el=>el.addEventListener('click',()=>{ this.sendBotFlow(el.dataset.pb); overlay.style.display='none'; }));
    },
    _filterFlows(q) {
        document.querySelectorAll('#lc-flow-list .lc-flow-item').forEach(el=>{
            el.style.display = el.dataset.name.includes(q.toLowerCase()) ? '' : 'none';
        });
    },
    async sendBotFlow(pid) {
        const r = await this.api(this.routes.botFlow, {method:'POST', body:JSON.stringify({channel_id:this.state.channelId, subscriber_id:this.state.activeSubscriberId, postback_id:pid})});
        console.log('[LC] sendBotFlow response:', r);
        const ok = r.status==='success'||r.status==='1'||r?.data?.status==='success';
        this.showToast(ok ? this.i18n.flow_sent : this.i18n.flow_failed+': '+(r.message||'error'), ok ? '#22c55e' : '#ef4444');
    },

    // ── Canned Responses ──
    async loadCannedResponses() {
        const data = await this.api(`${this.routes.cannedResponses}?channel_id=${this.state.channelId}`);
        this.state.cannedResponses = Array.isArray(data) ? data : (data?.data||data?.canned_responses||[]);
        console.log('[LC] cannedResponses loaded:', this.state.cannedResponses.length);
    },
    showCannedPicker() {
        if (!this.state.activeSubscriberId) return;
        const canned = this.state.cannedResponses;
        let overlay = document.getElementById('lc-canned-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'lc-canned-overlay';
            overlay.style.cssText = 'position:absolute;bottom:58px;left:8px;right:8px;max-height:280px;z-index:50;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 4px 24px rgba(0,0,0,.12);display:flex;flex-direction:column;overflow:hidden';
            document.querySelector('.lc-chat').style.position='relative';
            document.querySelector('.lc-chat').appendChild(overlay);
            document.addEventListener('click', (e)=>{ if (!overlay.contains(e.target)&&e.target.id!=='cannedBtn') overlay.style.display='none'; });
        }
        overlay.style.display = overlay.style.display==='flex' ? 'none' : 'flex';
        overlay.innerHTML = `
            <div style="padding:8px 10px 4px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #f1f5f9">
                <span style="font-size:11px;font-weight:600;color:#374151">${this.esc(this.i18n.canned_responses_title)}</span>
                <button onclick="document.getElementById('lc-canned-overlay').style.display='none'" style="background:none;border:none;color:#94a3b8;cursor:pointer;font-size:16px;line-height:1">&times;</button>
            </div>
            <input placeholder="${this.esc(this.i18n.search_short)}" oninput="LC._filterCanned(this.value)" style="margin:6px 8px;padding:5px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;outline:none">
            <div id="lc-canned-list" style="flex:1;overflow-y:auto">
                ${!canned.length ? `<div style="padding:16px;text-align:center;color:#94a3b8;font-size:12px">${this.esc(this.i18n.no_canned)}</div>` :
                canned.map(c=>`<div class="lc-canned-item" data-msg="${this.esc(c.message||c.text||c.response||'')}" data-search="${this.esc((c.title||c.name||c.message||'').toLowerCase())}" style="padding:8px 12px;cursor:pointer;border-bottom:1px solid #f8fafc;font-size:12px">
                    <div style="font-weight:500;color:#374151">${this.esc(c.title||c.name||this.i18n.canned_default_name)}</div>
                    <div style="color:#94a3b8;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${this.esc(c.message||c.text||c.response||'')}</div>
                </div>`).join('')}
            </div>`;
        overlay.querySelectorAll('.lc-canned-item').forEach(el=>{
            el.addEventListener('mouseenter',()=>el.style.background='#f0f7ff');
            el.addEventListener('mouseleave',()=>el.style.background='');
            el.addEventListener('click',()=>{ document.getElementById('replyMsg').value=el.dataset.msg; overlay.style.display='none'; document.getElementById('replyMsg').focus(); });
        });
    },
    _filterCanned(q) {
        document.querySelectorAll('#lc-canned-list .lc-canned-item').forEach(el=>{
            el.style.display = el.dataset.search.includes(q.toLowerCase()) ? '' : 'none';
        });
    },

    // ── Toast notification ──
    showToast(msg, color='#22c55e') {
        let t = document.getElementById('lc-toast');
        if (!t) { t=document.createElement('div'); t.id='lc-toast'; t.style.cssText='position:fixed;bottom:80px;left:50%;transform:translateX(-50%);padding:8px 18px;border-radius:20px;color:#fff;font-size:13px;z-index:9999;pointer-events:none;opacity:0;transition:opacity .3s'; document.body.appendChild(t); }
        t.textContent=msg; t.style.background=color; t.style.opacity='1';
        setTimeout(()=>t.style.opacity='0', 2500);
    },

    // ── Upload ──
    async uploadFile(file) {
        if (!this.state.activeThread) return;
        const fd = new FormData(); fd.append('file',file); fd.append('channel_id',this.state.channelId);
        const res = await fetch(this.routes.upload, {method:'POST', headers:{'X-CSRF-TOKEN':this.csrf}, body:fd});
        const data = await res.json();
        if (data?.file||data?.url) { document.getElementById('replyMsg').value=data.file||data.url; this.sendReply(); }
    },

    // ── Polling: messages every 3s, conversation list every 8s ──
    startMsgPoll() {
        clearInterval(this.state.pollMsg);
        this.state.pollMsg = setInterval(()=>{ if(this.state.activeSubscriberId) this.refreshMessages(); }, 3000);
    },
    startListPoll() {
        clearInterval(this.state.pollList);
        // Use refreshList — preserves listLimit and searchQuery so we don't
        // clobber the user's scroll position or active search every 8s.
        this.state.pollList = setInterval(()=>this.refreshList(), 8000);
    },
    stopAll() { clearInterval(this.state.pollMsg); clearInterval(this.state.pollList); },

    switchChannel(channelId, tenantId) {
        console.log('[LC] switchChannel:', channelId, tenantId);
        this.state.channelId = String(channelId);
        this.state.tenantId = tenantId || '';
        this.state.activeThread = null;
        this.state.activeSubscriberId = null;
        this.state.lastMsgCount = 0;
        document.getElementById('lcList').innerHTML = `<div class="lc-empty">${this.esc(this.i18n.loading)}</div>`;
        document.getElementById('lcMessages').innerHTML = `<div class="lc-empty">${this.esc(this.i18n.select_conversation)}</div>`;
        document.getElementById('chatName').textContent = this.i18n.select_conversation;
        this.stopAll();
        this.loadConversations();
        this.loadBotFlows();
        this.loadCannedResponses();
        this.startListPoll();
    },

    _switchTab(el) {
        document.querySelectorAll('#lcTabs .lc-tab').forEach(t=>t.classList.remove('active'));
        el.classList.add('active');
        this.state.messageType = el.dataset.type;
        this.loadConversations();
    },

    init() {
        // All interactive elements use inline onclick/onkeydown/oninput
        // Only fileInput needs addEventListener (no inline handler on hidden input)
        document.getElementById('fileInput').addEventListener('change',function(){if(this.files[0]){LC.uploadFile(this.files[0]);this.value='';}});

        // Infinite scroll on the conversation list. Load next page when the
        // user is within 80px of the bottom. The list itself is the scroll
        // container (.lc-list has flex:1; overflow-y:auto).
        const list = document.getElementById('lcList');
        if (list) {
            list.addEventListener('scroll', () => {
                const nearBottom = list.scrollHeight - list.scrollTop - list.clientHeight < 80;
                if (nearBottom) LC.loadMore();
            });
        }

        // Init
        this.loadConversations();
        this.loadBotFlows();
        this.loadCannedResponses();
        this.startListPoll();

        // Deep-link: ?subscriber_id=… opens that conversation as soon as the
        // list resolves. Used by the orders advanced view to jump from an
        // order row straight into the customer's chat.
        const dl = new URLSearchParams(window.location.search).get('subscriber_id');
        if (dl) {
            this._deepLinkSubscriberId = dl;
            this._deepLinkAttempts = 0;
            const tryOpen = () => {
                const item = document.querySelector(`#lcList .lc-item[data-uid="${CSS.escape(dl)}"]`);
                if (item) { item.click(); return; }
                if (++this._deepLinkAttempts < 20) setTimeout(tryOpen, 300);
            };
            setTimeout(tryOpen, 400);
        }
    },
};
document.addEventListener('DOMContentLoaded',()=>LC.init());
function showSidebar(){document.getElementById('lcSidebar').classList.remove('hidden');document.getElementById('backBtn').style.display='none';}
</script>
@endpush

</x-admin::layouts>
