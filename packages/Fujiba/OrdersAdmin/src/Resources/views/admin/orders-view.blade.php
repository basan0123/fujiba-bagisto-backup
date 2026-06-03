{{-- ════════════════════════════════════════════════════════════════
     Advanced Orders View — Vue component
     Mounts at <v-orders-view> inside #orders-view-app
     Uses Bagisto's existing window.app + this.$axios + this.$emitter.
═════════════════════════════════════════════════════════════════ --}}

@push('styles')
    <style>
        .ov-table { width:100%; border-collapse:collapse; background:#fff; border-radius:8px; overflow:hidden; }
        .dark .ov-table { background:#0f172a; }
        .ov-table th { background:#f9fafb; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; padding:10px 12px; text-align:left; border-bottom:1px solid #e5e7eb; }
        .dark .ov-table th { background:rgba(255,255,255,.03); border-bottom-color:#1f2937; color:#9ca3af; }
        .ov-table td { padding:10px 12px; border-bottom:1px solid #f3f4f6; vertical-align:top; font-size:13px; }
        .dark .ov-table td { border-bottom-color:#1f2937; }
        .ov-table tr:hover td { background:rgba(59,130,246,.04); }
        .ov-th-sort { cursor:pointer; user-select:none; }
        .ov-th-sort:hover { color:#3b82f6; }
        .ov-sort-arr { color:#3b82f6; font-size:10px; margin-left:2px; }

        /* Per-row status tinting — full-row background, not just the badge.
           Uses the same palette as .ov-st-* so the row reads at a glance. */
        .ov-row-pending          td:first-child { box-shadow:inset 3px 0 0 #f59e0b; }
        .ov-row-pending_payment  td:first-child { box-shadow:inset 3px 0 0 #d97706; }
        .ov-row-processing       td:first-child { box-shadow:inset 3px 0 0 #3b82f6; }
        .ov-row-completed        td:first-child { box-shadow:inset 3px 0 0 #10b981; }
        .ov-row-canceled         td:first-child { box-shadow:inset 3px 0 0 #ef4444; }
        .ov-row-closed           td:first-child { box-shadow:inset 3px 0 0 #6b7280; }
        .ov-row-fraud            td:first-child { box-shadow:inset 3px 0 0 #b91c1c; }
        .ov-row-pending          { background:rgba(254,243,199,.35); }
        .ov-row-pending_payment  { background:rgba(253,230,138,.35); }
        .ov-row-processing       { background:rgba(219,234,254,.35); }
        .ov-row-completed        { background:rgba(209,250,229,.35); }
        .ov-row-canceled         { background:rgba(254,226,226,.35); }
        .ov-row-closed           { background:rgba(229,231,235,.35); }
        .ov-row-fraud            { background:rgba(254,202,202,.35); }
        .dark .ov-row-pending          { background:rgba(245,158,11,.08); }
        .dark .ov-row-pending_payment  { background:rgba(217,119,6,.08); }
        .dark .ov-row-processing       { background:rgba(59,130,246,.08); }
        .dark .ov-row-completed        { background:rgba(16,185,129,.08); }
        .dark .ov-row-canceled         { background:rgba(239,68,68,.08); }
        .dark .ov-row-closed           { background:rgba(107,114,128,.08); }
        .dark .ov-row-fraud            { background:rgba(185,28,28,.10); }

        /* Mobile: each <tr> becomes a card, with its <td>s laid out as a
           2-column grid:
              [ 顧客       ] [ 注文      ]
              [ 金額       ] [ 利益      ]
              [ 支払方法   ] [ ステータス ]
              [ 操作 — full-width row                ]
           Each cell stacks its label on top of its value (column flex)
           rather than the previous side-by-side row layout, so JP labels
           don't squeeze the value. */
        @media (max-width: 767px) {
            .ov-table thead { display:none; }
            .ov-table, .ov-table tbody { display:block; width:100%; }
            .ov-table tr {
                display:grid;
                grid-template-columns: 1fr 1fr;
                gap:8px 12px;
                border:1px solid #e5e7eb; border-radius:8px;
                margin-bottom:10px; padding:10px 12px;
                background:#fff;
            }
            .dark .ov-table tr { border-color:#1f2937; background:#0f172a; }
            .ov-table td {
                display:flex; flex-direction:column;
                gap:3px; padding:0;
                border-bottom:none;
                min-width:0; /* let long values wrap, not overflow */
            }
            .ov-table td::before {
                content: attr(data-label);
                color:#6b7280; font-size:10px; font-weight:600;
                text-transform:uppercase; letter-spacing:.04em;
                line-height:1.2;
            }
            /* Customer header spans the full card width — bigger avatar,
               bigger bold name, email + status pill all visible without
               a 200px truncate. Order/Amount/Profit/Payment/Status sit
               below in a 2-col grid; Actions row spans full width. */
            .ov-table td.ov-cell-customer {
                grid-column: 1 / -1;
                padding-bottom:8px;
                border-bottom:1px solid #f3f4f6;
                margin-bottom:2px;
            }
            .dark .ov-table td.ov-cell-customer { border-bottom-color:#1f2937; }
            .ov-table td.ov-cell-customer::before { display:none; }
            .ov-table td.ov-cell-customer .ov-cust-name {
                font-size:17px;
                font-weight:800;
                line-height:1.3;
                white-space:normal;          /* allow wrap on phones */
                overflow:visible;
                text-overflow:clip;
                max-width:none;
            }
            .ov-table td.ov-cell-customer .ov-cust-email {
                font-size:12px;
                max-width:none;
            }
            .ov-table td.ov-cell-customer .ov-av,
            .ov-table td.ov-cell-customer .ov-av-img {
                width:40px; height:40px; font-size:15px;
            }
            .ov-table td.ov-cell-customer .ov-status {
                font-size:11px; padding:3px 10px;
            }

            .ov-table td.ov-cell-actions {
                grid-column: 1 / -1;
            }
            /* Restore the status-tint on the whole card on mobile */
            .ov-row-pending          td:first-child { box-shadow:none; }
            .ov-row-pending_payment  td:first-child { box-shadow:none; }
            .ov-row-processing       td:first-child { box-shadow:none; }
            .ov-row-completed        td:first-child { box-shadow:none; }
            .ov-row-canceled         td:first-child { box-shadow:none; }
            .ov-row-closed           td:first-child { box-shadow:none; }
            .ov-row-fraud            td:first-child { box-shadow:none; }
            .ov-row-pending          { border-left:4px solid #f59e0b !important; }
            .ov-row-pending_payment  { border-left:4px solid #d97706 !important; }
            .ov-row-processing       { border-left:4px solid #3b82f6 !important; }
            .ov-row-completed        { border-left:4px solid #10b981 !important; }
            .ov-row-canceled         { border-left:4px solid #ef4444 !important; }
            .ov-row-closed           { border-left:4px solid #6b7280 !important; }
            .ov-row-fraud            { border-left:4px solid #b91c1c !important; }
        }

        /* Customer name — bumped one step up across all viewports.
           Desktop: 15px/700; mobile: 17px/800 (the screenshot showed name
           competing visually with the order id — now it leads the card). */
        .ov-cust-info { min-width:0; }
        .ov-cust-name {
            font-size:15px;
            font-weight:700;
            color:#111827;
            line-height:1.3;
            overflow:hidden;
            text-overflow:ellipsis;
            white-space:nowrap;
            max-width:200px;
        }
        .dark .ov-cust-name { color:#f3f4f6; }
        .ov-cust-email { max-width:200px; }

        .ov-av { width:32px; height:32px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:13px; flex-shrink:0; }
        .ov-av-img { width:32px; height:32px; border-radius:50%; object-fit:cover; flex-shrink:0; background:#e5e7eb; }
        .ov-chat-link { color:inherit; text-decoration:none; padding:4px 8px; border-radius:6px; transition:background .15s; }
        .ov-chat-link:hover { background:rgba(59,130,246,.08); }
        .ov-cod-warn { background:#fef3c7; color:#92400e; border:1px solid #fbbf24; padding:8px 12px; border-radius:6px; font-size:13px; }
        .dark .ov-cod-warn { background:rgba(251,191,36,.10); color:#fcd34d; border-color:rgba(251,191,36,.4); }

        /* Customer-cell photo: when subscriber linked, the photo becomes a
           click target with a small 💬 badge in the bottom-right. */
        .ov-cust-photo { position: relative; flex-shrink: 0; }
        .ov-cust-photo-link { cursor: pointer; }
        .ov-cust-photo-link:hover { transform: scale(1.05); transition: transform .12s; }
        .ov-cust-chat-badge {
            position: absolute; bottom: -2px; right: -2px;
            font-size: 11px;
            background: #fff;
            border-radius: 9999px;
            width: 16px; height: 16px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 1px 3px rgba(0,0,0,.2);
            line-height: 1;
        }
        .dark .ov-cust-chat-badge { background: #1f2937; }

        /* ChatModal — full-width, 80vh, message bubbles */
        .ov-chat-modal { max-width: 720px; width: 100%; height: 80vh; display: flex; flex-direction: column; }
        .ov-chat-body  { flex: 1 1 auto; overflow-y: auto; padding: 16px 20px; background: #f8fafc; }
        .dark .ov-chat-body { background: #0f172a; }
        .ov-msg { display: flex; margin-bottom: 10px; }
        .ov-msg.in  { justify-content: flex-start; }
        .ov-msg.out { justify-content: flex-end; }
        .ov-msg-bubble {
            max-width: 70%;
            padding: 8px 12px;
            border-radius: 14px;
            font-size: 13px;
            line-height: 1.4;
            word-wrap: break-word;
        }
        .ov-msg.in  .ov-msg-bubble { background: #fff; border: 1px solid #e5e7eb; border-top-left-radius: 4px; }
        .ov-msg.out .ov-msg-bubble { background: #3b82f6; color: #fff; border-top-right-radius: 4px; }
        .dark .ov-msg.in  .ov-msg-bubble { background: #1f2937; border-color: #374151; color: #e5e7eb; }
        .ov-msg-time { font-size: 10px; color: #94a3b8; margin-top: 4px; }
        .ov-msg.out .ov-msg-time { text-align: right; }
        .ov-msg-img { max-width: 220px; max-height: 220px; border-radius: 8px; cursor: zoom-in; display: block; }
        .ov-msg-meta { font-style: italic; color: #94a3b8; font-size: 11px; }
        .ov-msg-file { color: inherit; text-decoration: underline; font-size: 12px; }
        .ov-av-rose   { background:#f43f5e; }
        .ov-av-violet { background:#8b5cf6; }
        .ov-av-blue   { background:#3b82f6; }
        .ov-av-teal   { background:#14b8a6; }
        .ov-av-amber  { background:#f59e0b; }
        .ov-av-green  { background:#22c55e; }

        .ov-status { display:inline-block; padding:2px 8px; border-radius:9999px; font-size:11px; font-weight:600; }
        .ov-st-pending          { background:#fef3c7; color:#92400e; }
        .ov-st-pending_payment  { background:#fde68a; color:#78350f; }
        .ov-st-processing       { background:#dbeafe; color:#1e40af; }
        .ov-st-shipped          { background:#e0e7ff; color:#3730a3; }
        .ov-st-completed        { background:#d1fae5; color:#065f46; }
        .ov-st-canceled         { background:#fee2e2; color:#991b1b; }
        .ov-st-closed           { background:#e5e7eb; color:#374151; }
        .ov-st-fraud            { background:#fecaca; color:#7f1d1d; }

        .ov-btn { padding:4px 10px; border-radius:6px; font-size:12px; font-weight:500; border:1px solid #d1d5db; background:#fff; cursor:pointer; transition:all .15s; }
        .ov-btn:hover { background:#f9fafb; border-color:#9ca3af; }
        .dark .ov-btn { background:#1f2937; border-color:#374151; color:#e5e7eb; }
        .dark .ov-btn:hover { background:#111827; }
        .ov-btn-primary { background:#3b82f6; color:#fff; border-color:#3b82f6; }
        .ov-btn-primary:hover { background:#2563eb; border-color:#2563eb; }
        .ov-btn-danger  { background:#ef4444; color:#fff; border-color:#ef4444; }
        .ov-btn-success { background:#10b981; color:#fff; border-color:#10b981; }

        /* Payment-method chip — bordered, color-coded by current method so the
           active state is unambiguous next to the swap-action button. */
        .ov-pay-chip {
            display:inline-flex; align-items:center;
            padding:3px 10px; border-radius:9999px;
            font-size:12px; font-weight:600;
            border:1.5px solid; line-height:1.3;
        }
        .ov-pay-chip-cod      { background:#fef3c7; color:#92400e; border-color:#f59e0b; }
        .ov-pay-chip-transfer { background:#dbeafe; color:#1e40af; border-color:#3b82f6; }
        .ov-pay-chip-cash     { background:#dcfce7; color:#166534; border-color:#22c55e; }
        .ov-pay-chip-kpay     { background:#fce7f3; color:#9d174d; border-color:#ec4899; }
        .ov-pay-chip-other    { background:#f3f4f6; color:#374151; border-color:#9ca3af; }
        .dark .ov-pay-chip-cod      { background:rgba(245,158,11,.15); color:#fcd34d; }
        .dark .ov-pay-chip-transfer { background:rgba(59,130,246,.15); color:#93c5fd; }
        .dark .ov-pay-chip-cash     { background:rgba(34,197,94,.15); color:#86efac; }
        .dark .ov-pay-chip-kpay     { background:rgba(236,72,153,.15); color:#f9a8d4; }
        .dark .ov-pay-chip-other    { background:rgba(156,163,175,.15); color:#d1d5db; }
        .ov-pay-swap {
            display:inline-flex; align-items:center; gap:4px;
            margin-top:6px; padding:4px 10px; border-radius:6px;
            font-size:11px; font-weight:500;
            background:#fff; border:1px dashed #9ca3af; color:#4b5563;
            cursor:pointer; transition:all .15s;
        }
        .ov-pay-swap:hover { background:#f9fafb; border-color:#3b82f6; color:#1e40af; }
        .dark .ov-pay-swap { background:#1f2937; border-color:#4b5563; color:#9ca3af; }

        /* Mobile-only: action buttons laid out as a compact 3-per-row grid.
           Touch targets stay finger-friendly (~36px tall) but the overall
           card height stays low. Desktop sizes (.ov-btn defaults above) are
           left alone. Wider phones (≥420px) keep 3-up; narrow phones drop
           to 2-up so labels don't truncate. */
        @media (max-width: 767px) {
            .ov-actions-row { gap:6px !important; }
            .ov-actions-row .ov-btn {
                padding:7px 8px;
                font-size:12px;
                min-height:34px;
                flex:1 1 calc(33.333% - 4px);
                min-width:calc(33.333% - 4px);
                max-width:calc(33.333% - 4px);
                justify-content:center;
                text-align:center;
                white-space:nowrap;
                overflow:hidden;
                text-overflow:ellipsis;
            }
            .ov-pay-chip { padding:4px 12px; font-size:13px; }
            .ov-pay-swap { padding:6px 12px; font-size:12px; min-height:32px; }
        }
        @media (max-width: 419px) {
            .ov-actions-row .ov-btn {
                flex-basis:calc(50% - 3px);
                min-width:calc(50% - 3px);
                max-width:calc(50% - 3px);
            }
        }

        .ov-modal-bg { position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:1000; display:flex; align-items:center; justify-content:center; padding:16px; }
        .ov-modal { background:#fff; border-radius:10px; max-width:540px; width:100%; max-height:90vh; overflow-y:auto; }
        .dark .ov-modal { background:#1f2937; color:#e5e7eb; }
        .ov-modal-head { padding:16px 20px; border-bottom:1px solid #e5e7eb; font-weight:600; font-size:15px; display:flex; justify-content:space-between; align-items:center; }
        .dark .ov-modal-head { border-bottom-color:#374151; }
        .ov-modal-body { padding:20px; }
        .ov-modal-foot { padding:14px 20px; border-top:1px solid #e5e7eb; display:flex; justify-content:flex-end; gap:8px; }
        .dark .ov-modal-foot { border-top-color:#374151; }
        .ov-x { cursor:pointer; color:#9ca3af; font-size:18px; line-height:1; padding:4px; }
        .ov-x:hover { color:#6b7280; }

        /* Edit-items modal: one-row layout on desktop, two-row stack on mobile */
        .ov-edit-item { display:flex; align-items:center; gap:12px; }
        .ov-edit-item-top { display:flex; align-items:center; gap:12px; flex:1; min-width:0; }
        .ov-edit-item-bot { display:flex; align-items:center; gap:12px; flex-shrink:0; }
        .ov-edit-item-total { min-width:80px; }
        @media (max-width: 639px) {
            .ov-modal-bg { padding:8px; }
            .ov-modal-body { padding:12px; }
            .ov-modal-head { padding:12px 14px; font-size:14px; }
            .ov-modal-foot { padding:10px 14px; }
            .ov-edit-item { flex-direction:column; align-items:stretch; gap:8px; }
            .ov-edit-item-top { width:100%; }
            .ov-edit-item-bot { width:100%; justify-content:space-between; gap:8px; }
            .ov-edit-item-remove { padding:4px 8px; }
        }

        .ov-input, .ov-textarea, .ov-select { width:100%; padding:7px 10px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; background:#fff; }
        .dark .ov-input, .dark .ov-textarea, .dark .ov-select { background:#111827; border-color:#374151; color:#e5e7eb; }
        .ov-input:focus, .ov-textarea:focus, .ov-select:focus { outline:none; border-color:#3b82f6; box-shadow:0 0 0 2px rgba(59,130,246,.2); }
        .ov-label { display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:4px; }
        .dark .ov-label { color:#d1d5db; }
        .ov-grid2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }

        .ov-page { padding:6px 12px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; cursor:pointer; background:transparent; }
        .ov-page:hover { background:#f3f4f6; }
        .ov-page:disabled { opacity:.4; cursor:not-allowed; }
        .ov-page.is-active { background:#3b82f6; color:#fff; border-color:#3b82f6; }
        .dark .ov-page { border-color:#374151; color:#d1d5db; }
        .dark .ov-page:hover { background:#1f2937; }

        .ov-loader { display:flex; justify-content:center; padding:60px 0; }
        .ov-empty { text-align:center; padding:60px 0; color:#9ca3af; }

        .ov-pill { display:inline-block; padding:1px 8px; border-radius:4px; font-size:11px; background:#f3f4f6; color:#4b5563; margin-left:4px; }
        .dark .ov-pill { background:#374151; color:#d1d5db; }

        /* Open-orders summary pills (above filter bar) */
        .ov-summary-pill {
            display:inline-flex; align-items:center; gap:8px;
            padding:6px 12px; border-radius:9999px;
            background:#f9fafb; border:1px solid #e5e7eb;
            font-size:12px; color:#374151;
            cursor:pointer; transition:background .12s, border-color .12s;
            user-select:none;
        }
        .ov-summary-pill:hover { background:#f3f4f6; border-color:#d1d5db; }
        .dark .ov-summary-pill { background:#1f2937; border-color:#374151; color:#e5e7eb; }
        .dark .ov-summary-pill:hover { background:#111827; border-color:#4b5563; }
        .ov-summary-pill.is-kimono { background:#fff1f2; border-color:#fda4af; color:#9f1239; }
        .ov-summary-pill.is-kimono:hover { background:#ffe4e6; }
        .dark .ov-summary-pill.is-kimono { background:rgba(244,63,100,.10); border-color:rgba(244,63,100,.35); color:#fb7185; }
        .ov-summary-pill.is-food { background:#fffbeb; border-color:#fcd34d; color:#92400e; }
        .ov-summary-pill.is-food:hover { background:#fef3c7; }
        .dark .ov-summary-pill.is-food { background:rgba(251,191,36,.10); border-color:rgba(251,191,36,.35); color:#fbbf24; }
        .ov-summary-count {
            display:inline-block; min-width:22px; text-align:center;
            padding:1px 8px; border-radius:9999px;
            background:rgba(0,0,0,.08); color:inherit; font-weight:700;
        }
        .dark .ov-summary-count { background:rgba(255,255,255,.10); }
        .ov-summary-sub { font-size:11px; opacity:.75; }

        /* Multi-select filter (channel / status) */
        .ov-ms { position:relative; display:inline-block; }
        .ov-ms-trigger {
            display:inline-flex; align-items:center; gap:6px;
            min-width:180px; max-width:240px;
            padding:6px 10px; border:1px solid #d1d5db; border-radius:6px;
            background:#fff; color:#374151; font-size:13px;
            cursor:pointer;
        }
        .ov-ms-trigger:hover { border-color:#9ca3af; }
        .ov-ms-trigger:focus { outline:none; border-color:#3b82f6; box-shadow:0 0 0 2px rgba(59,130,246,.2); }
        .dark .ov-ms-trigger { background:#1f2937; border-color:#374151; color:#e5e7eb; }
        .ov-ms-trigger > .truncate { flex:1; text-align:left; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .ov-ms-caret { font-size:10px; opacity:.6; }

        .ov-ms-panel {
            position:absolute; top:calc(100% + 4px); left:0; z-index:30;
            min-width:220px; max-height:280px; overflow-y:auto;
            background:#fff; border:1px solid #e5e7eb; border-radius:8px;
            box-shadow:0 4px 12px rgba(0,0,0,.08); padding:6px;
        }
        .dark .ov-ms-panel { background:#111827; border-color:#374151; box-shadow:0 4px 12px rgba(0,0,0,.4); }
        .ov-ms-row {
            display:flex; align-items:center; gap:8px;
            padding:6px 8px; border-radius:4px;
            font-size:13px; color:#374151; cursor:pointer;
        }
        .ov-ms-row:hover { background:#f3f4f6; }
        .dark .ov-ms-row { color:#e5e7eb; }
        .dark .ov-ms-row:hover { background:#1f2937; }
        .ov-ms-foot { margin-top:4px; padding:6px 8px; border-top:1px solid #f3f4f6; text-align:right; }
        .dark .ov-ms-foot { border-top-color:#374151; }
        .ov-ms-clear {
            background:none; border:none; padding:0;
            color:#3b82f6; font-size:12px; cursor:pointer;
        }
        .ov-ms-clear:hover { text-decoration:underline; }

        /* Filter-pair: a label + 2 inputs + 〜 separator that must stay glued
           together so the pair never wraps mid-group. Desktop keeps the
           original sizes; mobile shrinks both inputs so the pair fits a
           single phone-row (≈360px wide). */
        .ov-filter-pair {
            display:inline-flex;
            align-items:center;
            gap:6px;
            flex-wrap:nowrap;
        }
        .ov-filter-label {
            font-size:12px;
            color:#6b7280;
            white-space:nowrap;
            flex-shrink:0;
        }
        .dark .ov-filter-label { color:#9ca3af; }
        .ov-filter-sep {
            font-size:12px;
            color:#6b7280;
            flex-shrink:0;
        }
        .ov-input-date { max-width:160px; min-width:0; }
        .ov-input-amount { max-width:110px; min-width:0; }
        .ov-input-perpage { max-width:110px; }
        .ov-filter-perpage { margin-left:auto; }

        @media (max-width: 767px) {
            .ov-filter-pair {
                flex:1 1 100%;        /* each pair takes a full row */
                min-width:0;
            }
            .ov-filter-pair .ov-input,
            .ov-filter-pair .ov-select {
                flex:1 1 auto;
                min-width:0;
                max-width:none;       /* fill available width inside the pair */
                font-size:13px;
                padding:6px 8px;
            }
            .ov-filter-label { font-size:11px; }
            .ov-filter-perpage { margin-left:0; }
            .ov-filter-perpage .ov-input-perpage {
                flex:0 0 auto;
                max-width:110px;
            }
        }
    </style>
@endpush

@push('scripts')
<script type="text/x-template" id="tpl-orders-view">
    <div>
        {{-- Open-orders summary pills (pending + processing per channel) --}}
        <div v-if="summary.length" class="flex flex-wrap items-center gap-2 mb-2">
            <span
                v-for="s in summary"
                :key="s.channel_id"
                class="ov-summary-pill"
                :class="summaryClass(s)"
                role="button"
                tabindex="0"
                @click="applySummaryFilter(s)"
                @keyup.enter="applySummaryFilter(s)"
                :title="t('summary_filter_hint')"
            >
                <span class="font-semibold">@{{ summaryLabel(s) }}</span>
                <span class="ov-summary-count">@{{ s.total }}</span>
                <span class="ov-summary-sub">
                    P:@{{ s.pending }} / 処:@{{ s.processing }}
                </span>
            </span>
        </div>

        {{-- Filters --}}
        <div class="flex flex-wrap items-center gap-2 mb-3 p-3 bg-white dark:bg-gray-900 rounded-lg border dark:border-gray-800">
            {{-- Channel multi-select --}}
            <div class="ov-ms" v-click-outside="() => closeMs('channel')">
                <button type="button" class="ov-ms-trigger" @click="toggleMs('channel')">
                    <span class="truncate">@{{ msLabel('channel') }}</span>
                    <span class="ov-ms-caret">▾</span>
                </button>
                <div v-if="ms.channel.open" class="ov-ms-panel">
                    <label v-for="ch in channels" :key="ch.id" class="ov-ms-row">
                        <input type="checkbox" :value="ch.id" v-model="filters.channel_ids" @change="loadPage(1)" />
                        <span>@{{ ch.name }}</span>
                    </label>
                    <div v-if="filters.channel_ids.length" class="ov-ms-foot">
                        <button class="ov-ms-clear" type="button" @click="clearMs('channel')">@{{ t('clear') }}</button>
                    </div>
                </div>
            </div>

            {{-- Status multi-select --}}
            <div class="ov-ms" v-click-outside="() => closeMs('status')">
                <button type="button" class="ov-ms-trigger" @click="toggleMs('status')">
                    <span class="truncate">@{{ msLabel('status') }}</span>
                    <span class="ov-ms-caret">▾</span>
                </button>
                <div v-if="ms.status.open" class="ov-ms-panel">
                    <label v-for="k in STATUS_KEYS" :key="k" class="ov-ms-row">
                        <input type="checkbox" :value="k" v-model="filters.statuses" @change="loadPage(1)" />
                        <span>@{{ statusLabels[k] || k }}</span>
                    </label>
                    <div v-if="filters.statuses.length" class="ov-ms-foot">
                        <button class="ov-ms-clear" type="button" @click="clearMs('status')">@{{ t('clear') }}</button>
                    </div>
                </div>
            </div>

            <input
                type="text"
                v-model="filters.search"
                @keyup.enter="loadPage(1)"
                @input="onSearchInput"
                :placeholder="t('search_placeholder')"
                class="ov-input"
                style="max-width:280px"
            />

            <div class="ov-filter-pair">
                <span class="ov-filter-label">@{{ t('date_range') }}:</span>
                <input type="date" v-model="filters.created_from" @change="loadPage(1)" class="ov-input ov-input-date" />
                <span class="ov-filter-sep">〜</span>
                <input type="date" v-model="filters.created_to"   @change="loadPage(1)" class="ov-input ov-input-date" />
            </div>

            <div class="ov-filter-pair">
                <span class="ov-filter-label">@{{ t('amount') }}:</span>
                <input type="number" min="0" step="1" v-model.number="filters.amount_min"
                       @change="loadPage(1)" @keyup.enter="loadPage(1)"
                       :placeholder="t('amount_min')"
                       class="ov-input ov-input-amount" />
                <span class="ov-filter-sep">〜</span>
                <input type="number" min="0" step="1" v-model.number="filters.amount_max"
                       @change="loadPage(1)" @keyup.enter="loadPage(1)"
                       :placeholder="t('amount_max')"
                       class="ov-input ov-input-amount" />
            </div>

            <div class="ov-filter-pair ov-filter-perpage">
                <select v-model.number="meta.per_page" @change="loadPage(1)" class="ov-select ov-input-perpage">
                    <option v-for="n in [10, 25, 50, 100]" :key="n" :value="n">@{{ n }}/page</option>
                </select>
                <span class="text-xs text-gray-500" v-if="!isLoading">
                    @{{ meta.total }} @{{ t('results') }}
                </span>
            </div>
        </div>

        {{-- Loading --}}
        <div v-if="isLoading" class="ov-loader">
            <img src="{{ bagisto_asset('images/spinner.svg') }}" class="h-8 w-8 animate-spin" />
        </div>

        {{-- Empty --}}
        <div v-else-if="!safeRecords.length" class="ov-empty">
            @{{ t('no_orders') }}
        </div>

        {{-- Table --}}
        <table v-else class="ov-table">
            <thead>
                <tr>
                    <th>@{{ t('customer') }}</th>
                    <th class="ov-th-sort" @click="toggleSort('increment_id')">
                        @{{ t('order') }} <span class="ov-sort-arr">@{{ sortArrow('increment_id') }}</span>
                        <span class="block text-[10px] font-normal text-gray-400 normal-case">
                            <span class="ov-th-sort" @click.stop="toggleSort('created_at')">
                                @{{ t('date') }} @{{ sortArrow('created_at') }}
                            </span>
                        </span>
                    </th>
                    <th class="ov-th-sort" @click="toggleSort('grand_total')">
                        @{{ t('amount') }} <span class="ov-sort-arr">@{{ sortArrow('grand_total') }}</span>
                    </th>
                    <th>@{{ t('profit') }}</th>
                    <th>@{{ t('payment') }}</th>
                    <th>@{{ t('status') }}</th>
                    <th>@{{ t('actions') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="rec in safeRecords" :key="rec.id" :class="'ov-row-' + (rec.status || 'pending')">
                    {{-- Customer: merged cell — photo (click → chat modal) on
                         left; name / email / status pill on right. --}}
                    <td class="ov-cell-customer" :data-label="t('customer')">
                        <div class="flex items-start gap-2">
                            {{-- Photo (or initial avatar). Clicking opens the
                                 chat modal when subscriber linked. --}}
                            <div class="ov-cust-photo"
                                 :class="rec.customer?.messenger_subscriber_id ? 'ov-cust-photo-link' : ''"
                                 :title="rec.customer?.messenger_subscriber_id ? t('open_chat') : ''"
                                 @click="rec.customer?.messenger_subscriber_id && openChatModal(rec)">
                                {{-- Mirrors the livechat page exactly: referrerpolicy
                                     no-referrer (Facebook CDN blocks 3rd-party referrers)
                                     and an inline onerror swap to the initial-avatar div. --}}
                                <img
                                    v-if="rec.customer?.avatar_url"
                                    :src="rec.customer?.avatar_url"
                                    :alt="rec.customer?.name"
                                    class="ov-av-img"
                                    referrerpolicy="no-referrer"
                                    onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
                                />
                                <div
                                    v-if="rec.customer?.avatar_url"
                                    class="ov-av"
                                    :class="avColor(rec.customer?.name)"
                                    style="display:none"
                                >
                                    @{{ initial(rec.customer?.name) }}
                                </div>
                                <div v-else class="ov-av" :class="avColor(rec.customer?.name)">
                                    @{{ initial(rec.customer?.name) }}
                                </div>
                                <span v-if="rec.customer?.messenger_subscriber_id" class="ov-cust-chat-badge">💬</span>
                            </div>

                            <div class="min-w-0 flex-1 ov-cust-info">
                                <div class="ov-cust-name">@{{ rec.customer?.name }}</div>
                                <div class="text-xs text-gray-500 truncate ov-cust-email">
                                    @{{ rec.customer?.email || '—' }}
                                </div>
                                <div class="mt-1">
                                    <span class="ov-status" :class="'ov-st-' + rowStatusValue(rec)">
                                        @{{ statusLabels[rowStatusValue(rec)] || rec.status }}
                                    </span>
                                </div>
                                <div v-if="rec.payment_confirmed_at" class="text-[10px] text-green-600 mt-0.5">
                                    ✓ @{{ t('paid') }} · @{{ formatShortDate(rec.payment_confirmed_at) }}
                                </div>
                            </div>
                        </div>
                    </td>

                    {{-- 2. Order --}}
                    <td :data-label="t('order')">
                        <div>
                            <div class="font-mono text-sm font-semibold">#@{{ rec.increment_id }}</div>
                            <div class="text-xs text-gray-500">@{{ formatDate(rec.created_at) }}</div>
                            <div class="text-xs text-gray-400">@{{ rec.items_count }} @{{ t('items') }}</div>
                        </div>
                    </td>

                    {{-- 3. Amount --}}
                    <td :data-label="t('amount')">
                        <div class="font-bold">¥@{{ formatYen(rec.grand_total) }}</div>
                    </td>

                    {{-- 3b. Profit --}}
                    <td :data-label="t('profit')">
                        <div v-if="rec.profit !== null && rec.profit !== undefined"
                             class="font-bold"
                             :class="rec.profit >= 0 ? 'text-green-600' : 'text-red-600'">
                            ¥@{{ formatYen(rec.profit) }}
                        </div>
                        <div v-else class="text-xs text-amber-600 font-semibold" :title="missingCostTitle(rec)">
                            ⚠ @{{ t('cost_unset') }}
                        </div>
                        <div class="text-[10px] text-gray-400 leading-tight mt-0.5">
                            <div>@{{ t('sub') }} ¥@{{ formatYen(rec.sub_total) }}</div>
                            <div v-if="rec.cost_total !== null && rec.cost_total !== undefined">
                                @{{ t('cost') }} ¥@{{ formatYen(rec.cost_total) }}
                            </div>
                            <div v-if="(rec.discount_amount||0) > 0">
                                @{{ t('discount') }} -¥@{{ formatYen(rec.discount_amount) }}
                            </div>
                            <div v-if="(rec.cool_surcharge||0) > 0">
                                @{{ t('cool') }} ¥@{{ formatYen(rec.cool_surcharge) }}
                            </div>
                        </div>
                    </td>

                    {{-- 4. Payment + COD→Manual toggle --}}
                    <td :data-label="t('payment')">
                        <div>
                            <span class="ov-pay-chip" :class="paymentChipClass(rec.payment_method)">
                                @{{ paymentLabel(rec.payment_method) }}
                            </span>
                        </div>
                        {{-- 4-way payment-method picker. Editable in every
                             status — staff sometimes need to re-classify
                             after the fact (KPay confirmed at handover etc.).
                             cashondelivery (Sagawa COD, has surcharge) /
                             moneytransfer (bank wire, accountant confirms) /
                             cash (admin-only walk-in shop, no surcharge) /
                             kpay (admin-only Myanmar mobile wallet, no surcharge). --}}
                        <select
                            v-if="canSwapPayment(rec)"
                            class="ov-select ov-pay-select"
                            :value="rec.payment_method"
                            :title="t('swap_payment_hint')"
                            @change="changePaymentMethod(rec, $event)"
                        >
                            <option value="cashondelivery">@{{ t('cashondelivery') }}</option>
                            <option value="moneytransfer">@{{ t('moneytransfer') }}</option>
                            <option value="cash">@{{ t('cash') }}</option>
                            <option value="kpay">@{{ t('kpay') }}</option>
                        </select>
                    </td>

                    {{-- 5. Status (dropdown opens modal, never fires endpoint directly) --}}
                    <td :data-label="t('status')">
                        <select
                            class="ov-select"
                            :value="rowStatusValue(rec)"
                            @change="onRowStatusChange(rec, $event)"
                        >
                            <option v-for="k in ROW_STATUS_KEYS" :key="k" :value="k">
                                @{{ statusLabels[k] || k }}
                            </option>
                        </select>
                        <span v-if="rec.label_printed" class="ov-pill mt-1">📄 printed</span>
                    </td>

                    {{-- 6. Actions --}}
                    <td class="ov-cell-actions" :data-label="t('actions')">
                        <div class="ov-actions-row flex flex-wrap gap-1">
                            <a :href="viewUrl(rec.id)" target="_blank" rel="noopener" class="ov-btn">@{{ t('view') }}</a>
                            <button class="ov-btn" @click="openAddressModal(rec)">@{{ t('edit_address') }}</button>
                            <button class="ov-btn" @click="openNoteModal(rec)">@{{ t('note') }}</button>
                            <button v-if="rec.status === 'pending' || rec.status === 'processing'"
                                class="ov-btn" @click="openEditItemsModal(rec)">
                                @{{ t('edit_items') }}
                            </button>
                            <button class="ov-btn" @click="openFulfillmentModal(rec)">
                                @{{ t('change_fulfillment') }}
                                <span v-if="rec.fulfillment_type" class="ov-pill">@{{ fulfillmentLabel(rec.fulfillment_type) }}</span>
                            </button>
                            <button v-if="needsPaymentConfirm(rec)"
                                class="ov-btn"
                                :class="rec.payment_confirmed_at ? 'ov-btn-success' : ''"
                                @click="openPaymentModal(rec)">
                                @{{ t('payment_confirm') }}
                                <span class="ov-pill">@{{ rec.payment_confirmed_at ? '✓ ' + t('paid') : t('unpaid') }}</span>
                            </button>
                            <button v-if="rec.status === 'pending'" class="ov-btn ov-btn-success" @click="confirmOrder(rec)">
                                @{{ t('confirm') }}
                            </button>
                            <button v-if="canSagawa(rec)" class="ov-btn" @click="openSagawaModal(rec)">
                                🖨 @{{ t('sagawa_print') }}
                            </button>
                            <button v-if="rec.fulfillment_type === 'pickup' && !rec.label_printed && rec.status === 'processing'" class="ov-btn ov-btn-success" @click="confirmPickup(rec)">
                                @{{ t('confirm_pickup') }}
                            </button>
                            <button v-if="rec.label_printed || (rec.status !== 'pending' && rec.fulfillment_type !== 'pickup')" class="ov-btn ov-btn-primary" @click="openTrackingModal(rec)">
                                @{{ rec.tracking_number ? t('edit_tracking') : t('assign_tracking') }}
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        {{-- Pagination --}}
        <div v-if="!isLoading && meta.last_page > 1" class="flex justify-center items-center gap-2 mt-4">
            <button class="ov-page" :disabled="meta.current_page <= 1" @click="loadPage(meta.current_page - 1)">‹ @{{ t('prev') }}</button>
            <button
                v-for="p in pageList"
                :key="p"
                class="ov-page"
                :class="{ 'is-active': p === meta.current_page }"
                @click="loadPage(p)"
            >@{{ p }}</button>
            <button class="ov-page" :disabled="meta.current_page >= meta.last_page" @click="loadPage(meta.current_page + 1)">@{{ t('next') }} ›</button>
        </div>

        {{-- ─── Status modal ──────────────────────── --}}
        <div v-if="modal.status.open" class="ov-modal-bg" @click.self="closeAll">
            <div class="ov-modal">
                <div class="ov-modal-head">
                    <span>@{{ t('update_status') }}</span>
                    <span class="ov-x" @click="closeAll">×</span>
                </div>
                <div class="ov-modal-body">
                    <p class="text-sm mb-3">
                        <span class="ov-status" :class="'ov-st-' + modal.status.from">@{{ statusLabel(modal.status.from) }}</span>
                        →
                        <span class="ov-status" :class="'ov-st-' + modal.status.to">@{{ statusLabel(modal.status.to) }}</span>
                    </p>
                    <label class="ov-label">@{{ t('customer_message') }}</label>
                    <textarea v-model="modal.status.message" class="ov-textarea" rows="5"></textarea>
                    <p class="text-xs text-gray-500 mt-1">@{{ t('customer_message_hint') }}</p>

                    {{-- Sagawa label toggle — only meaningful for delivery orders
                         that haven't already had a label issued. --}}
                    <div v-if="canPromptSagawa()" class="mt-4 p-3 rounded-lg border border-amber-200 bg-amber-50 dark:bg-amber-900/10 dark:border-amber-700/40">
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-800 dark:text-gray-100">
                            <input type="checkbox" v-model="modal.status.callSagawa" />
                            🖨 @{{ t('call_sagawa_label') }}
                        </label>
                        <p class="text-xs text-gray-500 mt-1 ltr:ml-6 rtl:mr-6">@{{ t('call_sagawa_label_hint') }}</p>
                    </div>
                </div>
                <div class="ov-modal-foot">
                    <button class="ov-btn" @click="closeAll">@{{ t('cancel') }}</button>
                    <button class="ov-btn ov-btn-primary" :disabled="modal.status.busy" @click="submitStatus">
                        @{{ modal.status.busy ? t('saving') : t('save') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- ─── Address modal ──────────────────────── --}}
        <div v-if="modal.address.open" class="ov-modal-bg" @click.self="closeAll">
            <div class="ov-modal">
                <div class="ov-modal-head">
                    <span>@{{ t('edit_address') }}</span>
                    <span class="ov-x" @click="closeAll">×</span>
                </div>
                <div class="ov-modal-body">
                    <div class="ov-grid2">
                        <div>
                            <label class="ov-label">@{{ t('name') }}</label>
                            <input v-model="modal.address.form.name" class="ov-input" />
                        </div>
                        <div>
                            <label class="ov-label">@{{ t('phone') }}</label>
                            <input v-model="modal.address.form.phone" class="ov-input" />
                        </div>
                    </div>
                    <label class="ov-label mt-3">@{{ t('address1') }}</label>
                    <input v-model="modal.address.form.address1" class="ov-input" />
                    <label class="ov-label mt-3">@{{ t('address2') }}</label>
                    <input v-model="modal.address.form.address2" class="ov-input" />
                    <div class="ov-grid2 mt-3">
                        <div>
                            <label class="ov-label">@{{ t('city') }}</label>
                            <input v-model="modal.address.form.city" class="ov-input" />
                        </div>
                        <div>
                            <label class="ov-label">@{{ t('state') }}</label>
                            <input v-model="modal.address.form.state" class="ov-input" />
                        </div>
                    </div>
                    <div class="ov-grid2 mt-3">
                        <div>
                            <label class="ov-label">@{{ t('postcode') }}</label>
                            <input v-model="modal.address.form.postcode" class="ov-input" />
                        </div>
                        <div>
                            <label class="ov-label">@{{ t('country') }}</label>
                            <input v-model="modal.address.form.country" class="ov-input" maxlength="2" />
                        </div>
                    </div>
                </div>
                <div class="ov-modal-foot">
                    <button class="ov-btn" @click="closeAll">@{{ t('cancel') }}</button>
                    <button class="ov-btn ov-btn-primary" :disabled="modal.address.busy" @click="submitAddress">
                        @{{ modal.address.busy ? t('saving') : t('save') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- ─── Edit Items modal ──────────────────────── --}}
        <div v-if="modal.editItems.open" class="ov-modal-bg" @click.self="closeAll">
            <div class="ov-modal" style="max-width: 720px; width: 95vw;">
                <div class="ov-modal-head">
                    <span>@{{ t('edit_items') }} #@{{ modal.editItems.order?.increment_id }}</span>
                    <span class="ov-x" @click="closeAll">×</span>
                </div>
                <div class="ov-modal-body">
                    <div v-if="modal.editItems.loading" class="text-center text-gray-500 py-4">
                        @{{ t('chat_loading') }}
                    </div>

                    <div v-else>
                        {{-- Current items list (with thumbnails) --}}
                        <div class="space-y-2 mb-3">
                            <div v-for="(it, idx) in modal.editItems.items" :key="it.product_id"
                                class="ov-edit-item border rounded p-2">
                                <div class="ov-edit-item-top">
                                    <img v-if="it.image" :src="it.image" alt="" class="w-12 h-12 object-cover rounded bg-gray-100 flex-shrink-0" />
                                    <div v-else class="w-12 h-12 rounded bg-gray-100 flex items-center justify-center text-gray-400 text-xs flex-shrink-0">—</div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium truncate">@{{ it.name }}</div>
                                        <div class="text-xs text-gray-500 truncate">@{{ it.sku }} · ¥@{{ formatYen(it.price) }}</div>
                                    </div>
                                    <button class="ov-btn ov-edit-item-remove flex-shrink-0" @click="removeEditItem(idx)" style="font-size: 11px;">
                                        @{{ t('remove') }}
                                    </button>
                                </div>
                                <div class="ov-edit-item-bot">
                                    <div class="flex items-center gap-1">
                                        <button type="button" class="ov-btn px-2"
                                            style="min-width: 28px;"
                                            @click="stepEditQty(idx, -1)"
                                            :disabled="(Number(it.qty) || 1) <= 1">−</button>
                                        <input type="number" min="1" :value="it.qty"
                                            class="ov-input text-center" style="width: 56px;"
                                            @input="onEditQtyInput(idx, $event)"
                                            @change="commitEditQty(idx, $event)"
                                            @blur="commitEditQty(idx, $event)" />
                                        <button type="button" class="ov-btn px-2"
                                            style="min-width: 28px;"
                                            @click="stepEditQty(idx, 1)">+</button>
                                    </div>
                                    <div class="text-right text-sm font-medium ov-edit-item-total">
                                        ¥@{{ formatYen(it.price * (Number(it.qty) || 0)) }}
                                    </div>
                                </div>
                            </div>
                            <div v-if="!modal.editItems.items.length" class="py-3 text-center text-red-600 text-sm">
                                @{{ t('items_required') }}
                            </div>
                        </div>

                        {{-- Add product (3-card grid) --}}
                        <div class="mb-3 border-t pt-3">
                            <label class="text-xs text-gray-500">@{{ t('add_item') }}</label>
                            <input v-model="modal.editItems.search" @input="searchEditProducts()"
                                class="ov-input mt-1" :placeholder="t('search_product')" />
                            <div v-if="modal.editItems.searchBusy" class="text-center text-xs text-gray-500 py-2">
                                @{{ t('chat_loading') }}
                            </div>
                            <div v-if="modal.editItems.searchResults.length"
                                ref="editItemsGrid"
                                @scroll="onEditGridScroll"
                                class="mt-2 grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-1.5 overflow-y-auto p-1"
                                style="max-height: 360px;">
                                <div v-for="p in modal.editItems.searchResults" :key="p.id"
                                    class="border rounded overflow-hidden hover:border-blue-500 transition-colors flex flex-col">
                                    <div class="aspect-square bg-gray-100 dark:bg-gray-800 flex items-center justify-center overflow-hidden">
                                        <img v-if="p.image" :src="p.image" alt="" class="w-full h-full object-cover" />
                                        <span v-else class="text-gray-400 text-[10px]">No img</span>
                                    </div>
                                    <div class="p-1 flex-1 flex flex-col gap-0.5">
                                        <div class="text-[11px] font-medium leading-tight line-clamp-2" :title="p.name">@{{ p.name }}</div>
                                        <div class="text-[9px] text-gray-500 truncate" :title="p.sku">@{{ p.sku }}</div>
                                        <div class="flex items-center justify-between gap-1">
                                            <span class="text-[11px] font-semibold">¥@{{ formatYen(p.price) }}</span>
                                            <span class="text-[9px]" :class="p.qty_in_stock > 0 ? 'text-green-600' : 'text-red-600'">@{{ p.qty_in_stock }}</span>
                                        </div>
                                        <button class="ov-btn ov-btn-primary mt-auto"
                                            style="font-size: 10px; padding: 2px 6px;"
                                            :disabled="p.qty_in_stock < 1"
                                            @click="addEditItem(p)">
                                            @{{ t('add') }}
                                        </button>
                                    </div>
                                </div>
                                <div v-if="modal.editItems.searchBusy && modal.editItems.searchPage > 1"
                                    class="col-span-3 sm:col-span-4 md:col-span-5 text-center text-xs text-gray-500 py-2">
                                    @{{ t('chat_loading') }}
                                </div>
                            </div>
                            <div v-if="modal.editItems.searchResults.length" class="text-xs text-gray-500 mt-1 text-right">
                                @{{ modal.editItems.searchResults.length }} / @{{ modal.editItems.searchTotal }}
                            </div>
                        </div>

                        {{-- Shipping --}}
                        <div class="mb-3 border-t pt-3">
                            <label class="text-xs text-gray-500">@{{ t('shipping') }}</label>
                            <div class="flex gap-3 items-center mt-1">
                                <label class="flex items-center gap-1 text-sm">
                                    <input type="radio" value="auto" v-model="modal.editItems.shippingMode" />
                                    @{{ t('shipping_auto') }}
                                </label>
                                <label class="flex items-center gap-1 text-sm">
                                    <input type="radio" value="manual" v-model="modal.editItems.shippingMode" />
                                    @{{ t('shipping_manual') }}
                                </label>
                                <input v-if="modal.editItems.shippingMode === 'manual'"
                                    type="number" min="0" v-model.number="modal.editItems.shippingOverride"
                                    class="ov-input" style="width: 120px;" placeholder="¥" />
                            </div>
                        </div>

                        {{-- Totals preview --}}
                        <div class="bg-gray-50 dark:bg-gray-800 rounded p-3 text-sm grid grid-cols-2 gap-y-1">
                            <div>@{{ t('line_total') }}</div>
                            <div class="text-right">¥@{{ formatYen(previewSubTotal) }}</div>
                            <div>@{{ t('shipping') }}</div>
                            <div class="text-right">¥@{{ formatYen(previewShipping) }}</div>
                            <template v-if="previewCodFee > 0">
                                <div>@{{ t('cod_fee') }}</div>
                                <div class="text-right">¥@{{ formatYen(previewCodFee) }}</div>
                            </template>
                            <template v-if="previewCoolSurcharge > 0">
                                <div>@{{ t('cool_surcharge') }}</div>
                                <div class="text-right">¥@{{ formatYen(previewCoolSurcharge) }}</div>
                            </template>
                            <div class="font-semibold border-t pt-1 mt-1">@{{ t('new_grand_total') }}</div>
                            <div class="text-right font-semibold border-t pt-1 mt-1">¥@{{ formatYen(previewGrandTotal) }}</div>
                            <div class="text-xs text-gray-500">@{{ t('grand_total_change') }}</div>
                            <div class="text-right text-xs"
                                :class="previewGrandTotal - modal.editItems.originalGrand > 0 ? 'text-red-600' : 'text-green-600'">
                                ¥@{{ formatYen(previewGrandTotal - modal.editItems.originalGrand) }}
                            </div>
                        </div>

                        <div v-if="Math.abs(previewGrandTotal - modal.editItems.originalGrand) > 0.5"
                            class="mt-2 text-xs text-amber-600">
                            ⚠ @{{ t('payment_warning') }}
                        </div>

                        {{-- Note --}}
                        <div class="mt-3">
                            <label class="text-xs text-gray-500">@{{ t('admin_note') }}</label>
                            <textarea v-model="modal.editItems.note" class="ov-textarea" rows="2"></textarea>
                        </div>

                        <div v-if="modal.editItems.errors.length" class="mt-2 text-sm text-red-600">
                            <ul class="list-disc pl-5">
                                <li v-for="(err, i) in modal.editItems.errors" :key="i">@{{ err }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="ov-modal-foot">
                    <button class="ov-btn" @click="closeAll">@{{ t('cancel') }}</button>
                    <button class="ov-btn ov-btn-primary"
                        :disabled="modal.editItems.busy || !modal.editItems.items.length"
                        @click="submitEditItems">
                        @{{ modal.editItems.busy ? t('saving') : t('save') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- ─── Note modal ──────────────────────── --}}
        <div v-if="modal.note.open" class="ov-modal-bg" @click.self="closeAll">
            <div class="ov-modal">
                <div class="ov-modal-head">
                    <span>@{{ t('admin_note') }}</span>
                    <span class="ov-x" @click="closeAll">×</span>
                </div>
                <div class="ov-modal-body">
                    <p v-if="modal.note.previous" class="text-xs text-gray-500 mb-2">
                        @{{ t('previous_note') }}: @{{ modal.note.previous }}
                    </p>
                    <textarea v-model="modal.note.text" class="ov-textarea" rows="6"></textarea>
                </div>
                <div class="ov-modal-foot">
                    <button class="ov-btn" @click="closeAll">@{{ t('cancel') }}</button>
                    <button class="ov-btn ov-btn-primary" :disabled="modal.note.busy" @click="submitNote">
                        @{{ modal.note.busy ? t('saving') : t('save') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- ─── Fulfillment modal ──────────────────────── --}}
        <div v-if="modal.fulfillment.open" class="ov-modal-bg" @click.self="closeAll">
            <div class="ov-modal">
                <div class="ov-modal-head">
                    <span>@{{ t('change_fulfillment') }}</span>
                    <span class="ov-x" @click="closeAll">×</span>
                </div>
                <div class="ov-modal-body">
                    <p class="text-xs text-gray-500 mb-2">
                        @{{ t('current') }}: <strong>@{{ fulfillmentLabel(modal.fulfillment.order?.fulfillment_type) }}</strong>
                    </p>
                    <div class="grid grid-cols-2 gap-2 mb-3">
                        <label v-for="opt in FULFILLMENT_OPTIONS" :key="opt.value"
                               class="flex items-center gap-2 p-2 border rounded cursor-pointer"
                               :class="modal.fulfillment.type === opt.value ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-300 dark:border-gray-700'">
                            <input type="radio" :value="opt.value" v-model="modal.fulfillment.type" @change="loadFulfillmentPreview" />
                            <span>@{{ opt.label }}</span>
                        </label>
                    </div>

                    {{-- Sagawa COD fee warning — shown when COD is selected
                         and a non-zero collection fee applies. Tier displayed
                         so admin sees why ¥330 vs ¥440. --}}
                    <div v-if="modal.fulfillment.type === 'cod' && modal.fulfillment.preview?.cod_fee > 0"
                         class="ov-cod-warn mb-3">
                        <div class="font-semibold">⚠️ @{{ t('sagawa_cod_warn_title') }}</div>
                        <div class="text-xs mt-1">
                            @{{ t('sagawa_cod_warn_subtotal') }}:
                            ¥@{{ formatYen(modal.fulfillment.order?.grand_total || 0) }} →
                            <strong>¥@{{ formatYen(modal.fulfillment.preview.cod_fee) }}</strong>
                        </div>
                        <div class="text-[10px] text-amber-700 dark:text-amber-300 mt-1">
                            @{{ t('sagawa_cod_warn_tiers') }}
                        </div>
                    </div>

                    <div v-if="modal.fulfillment.preview" class="bg-gray-50 dark:bg-gray-800 rounded p-3 text-sm">
                        <div class="font-semibold mb-2">@{{ t('fee_preview') }}</div>
                        <div class="grid grid-cols-2 gap-y-1">
                            <div class="text-gray-500">@{{ t('shipping') }}</div>
                            <div class="text-right">¥@{{ formatYen(modal.fulfillment.preview.shipping_amount) }}</div>
                            <div class="text-gray-500">@{{ t('cod_fee') }}</div>
                            <div class="text-right">¥@{{ formatYen(modal.fulfillment.preview.cod_fee) }}</div>
                            <div class="text-gray-500">@{{ t('cool_surcharge') }}</div>
                            <div class="text-right">¥@{{ formatYen(modal.fulfillment.preview.cool_surcharge) }}</div>
                            <div class="font-semibold pt-1 border-t mt-1">@{{ t('grand_total_new') }}</div>
                            <div class="text-right font-bold pt-1 border-t mt-1">¥@{{ formatYen(modal.fulfillment.preview.grand_total) }}</div>
                        </div>
                        <p v-if="modal.fulfillment.needs_cool" class="text-xs text-gray-500 mt-2">
                            * @{{ t('cool_applied_note') }}
                        </p>
                    </div>
                </div>
                <div class="ov-modal-foot">
                    <button class="ov-btn" @click="closeAll">@{{ t('cancel') }}</button>
                    <button class="ov-btn ov-btn-primary" :disabled="modal.fulfillment.busy || !modal.fulfillment.type" @click="submitFulfillment">
                        @{{ modal.fulfillment.busy ? t('saving') : t('apply') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- ─── Chat history modal (read-only) ──────────────────────── --}}
        <div v-if="modal.chat.open" class="ov-modal-bg" @click.self="closeAll">
            <div class="ov-modal ov-chat-modal">
                <div class="ov-modal-head">
                    <span>
                        @{{ t('chat_title') }} —
                        @{{ modal.chat.order?.customer?.name || '—' }}
                        <span class="ov-pill">SUB: @{{ modal.chat.order?.customer?.messenger_subscriber_id }}</span>
                    </span>
                    <span class="flex items-center gap-2">
                        <button class="ov-btn text-xs" @click="loadChatMessages" :disabled="modal.chat.busy">
                            ⟳ @{{ t('chat_refresh') }}
                        </button>
                        <a :href="livechatUrl(modal.chat.order?.customer?.messenger_subscriber_id)"
                           target="_blank" rel="noopener" class="ov-btn text-xs">
                            @{{ t('chat_open_in_full') }} ↗
                        </a>
                        <span class="ov-x" @click="closeAll">×</span>
                    </span>
                </div>
                <div class="ov-chat-body" ref="chatScroll">
                    <div v-if="modal.chat.busy" class="text-center text-sm text-gray-500 py-8">
                        @{{ t('chat_loading') }}
                    </div>
                    <div v-else-if="modal.chat.error" class="text-center text-sm text-red-500 py-8">
                        @{{ modal.chat.error }}
                    </div>
                    <div v-else-if="!modal.chat.messages.length" class="text-center text-sm text-gray-500 py-8">
                        @{{ t('chat_no_messages') }}
                    </div>
                    <template v-else>
                        <div v-for="(m, i) in modal.chat.messages" :key="i" class="ov-msg" :class="m.dir">
                            <div>
                                <div class="ov-msg-bubble" v-html="m.html"></div>
                                <div class="ov-msg-time">@{{ m.time }}</div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- ─── Payment confirmation modal ──────────────────────── --}}
        <div v-if="modal.payment.open" class="ov-modal-bg" @click.self="closeAll">
            <div class="ov-modal">
                <div class="ov-modal-head">
                    <span>@{{ t('payment_confirm') }}</span>
                    <span class="ov-x" @click="closeAll">×</span>
                </div>
                <div class="ov-modal-body">
                    <div class="mb-3">
                        <span class="ov-label">@{{ t('payment_state') }}</span>
                        <div class="flex gap-2 mt-1">
                            <button type="button"
                                class="ov-btn"
                                :class="modal.payment.confirmed ? 'ov-btn-success' : ''"
                                @click="modal.payment.confirmed = true">
                                ✓ @{{ t('paid') }}
                            </button>
                            <button type="button"
                                class="ov-btn"
                                :class="!modal.payment.confirmed ? 'ov-btn-danger' : ''"
                                @click="modal.payment.confirmed = false">
                                @{{ t('unpaid') }}
                            </button>
                        </div>
                        <p v-if="modal.payment.order?.payment_confirmed_at" class="text-xs text-gray-500 mt-2">
                            @{{ t('confirmed_at') }}: @{{ formatDate(modal.payment.order?.payment_confirmed_at) }}
                        </p>
                    </div>

                    <label class="ov-label mt-3">@{{ t('payment_note') }}</label>
                    <textarea
                        v-model="modal.payment.note"
                        class="ov-textarea"
                        rows="3"
                        :placeholder="t('payment_note_placeholder')"
                    ></textarea>

                    {{--
                        TODO(messenger-subscriber-sync): the link only renders
                        when the customer has messenger_subscriber_id populated.
                        That column is currently empty for all customers in this
                        DB. A separate Chatpion webhook → Bagisto sync feature
                        is required to populate it.
                    --}}
                    <div v-if="modal.payment.order?.customer?.messenger_subscriber_id && chatpionBaseUrl" class="mt-3">
                        <a :href="chatpionUrl(modal.payment.order?.customer?.messenger_subscriber_id)"
                           target="_blank" rel="noopener"
                           class="ov-btn ov-btn-primary inline-block">
                            @{{ t('chatpion_open') }}
                        </a>
                    </div>
                    <div v-else-if="modal.payment.order?.customer?.messenger_subscriber_id && !chatpionBaseUrl"
                         class="mt-3 text-xs text-gray-400">
                        @{{ t('no_chatpion_link') }}
                    </div>
                </div>
                <div class="ov-modal-foot">
                    <button class="ov-btn" @click="closeAll">@{{ t('cancel') }}</button>
                    <button class="ov-btn ov-btn-primary" :disabled="modal.payment.busy" @click="submitPayment">
                        @{{ modal.payment.busy ? t('saving') : t('save') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- ─── Sagawa modal ──────────────────────── --}}
        <div v-if="modal.sagawa.open" class="ov-modal-bg" @click.self="closeAll">
            <div class="ov-modal">
                <div class="ov-modal-head">
                    <span>@{{ t('sagawa_create_shipment') }}</span>
                    <span class="ov-x" @click="closeAll">×</span>
                </div>
                <div class="ov-modal-body" v-if="!modal.sagawa.success">
                    <p class="text-sm mb-3">@{{ t('sagawa_verify_address') }}</p>
                    <div class="text-sm bg-gray-50 dark:bg-gray-800 rounded p-3 mb-3 whitespace-pre-line">
                        @{{ formatAddress(modal.sagawa.address) }}
                    </div>
                    <button class="ov-btn mb-3" @click="openAddressModal(modal.sagawa.order)">
                        @{{ t('fix_address') }}
                    </button>
                    <label class="ov-label">@{{ t('tracking_number') }} (@{{ t('optional') }})</label>
                    <input v-model="modal.sagawa.tracking" class="ov-input" />
                </div>
                <div class="ov-modal-body" v-else>
                    <p class="text-green-600 font-semibold mb-2">✓ @{{ t('shipment_created') }}</p>
                    <p class="text-sm">@{{ t('next_step_assign_tracking') }}</p>
                </div>
                <div class="ov-modal-foot">
                    <button class="ov-btn" @click="closeAll">
                        @{{ modal.sagawa.success ? t('close') : t('cancel') }}
                    </button>
                    <button v-if="!modal.sagawa.success" class="ov-btn ov-btn-primary" :disabled="modal.sagawa.busy" @click="submitSagawa">
                        @{{ modal.sagawa.busy ? t('saving') : t('create') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- ─── Tracking modal ──────────────────────── --}}
        <div v-if="modal.tracking.open" class="ov-modal-bg" @click.self="closeAll">
            <div class="ov-modal">
                <div class="ov-modal-head">
                    <span>@{{ modal.tracking.update ? t('edit_tracking') : t('assign_tracking') }}</span>
                    <span class="ov-x" @click="closeAll">×</span>
                </div>
                <div class="ov-modal-body">
                    <label class="ov-label">@{{ t('tracking_number') }}</label>
                    <input v-model="modal.tracking.value" class="ov-input" />
                </div>
                <div class="ov-modal-foot">
                    <button class="ov-btn" @click="closeAll">@{{ t('cancel') }}</button>
                    <button class="ov-btn ov-btn-primary" :disabled="modal.tracking.busy" @click="submitTracking">
                        @{{ modal.tracking.busy ? t('saving') : t('save') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</script>

<script type="module">
    /* CSRF interceptor: install once on the shared axios. The component
       gets `this.$axios` via Bagisto's plugin; that instance is the same
       window axios used throughout admin. */
    (function () {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        if (csrf && window.axios && !window.axios.__ovCsrfWired) {
            window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf;
            window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
            window.axios.__ovCsrfWired = true;
        }
    })();

    // Lightweight click-outside directive for the multi-select panels.
    // Re-registering is harmless if Bagisto already exposes one — Vue 3
    // overwrites by name. Skips if a directive with this name already
    // exists so we don't trample a host implementation.
    if (!app._context.directives['click-outside']) {
        app.directive('click-outside', {
            mounted(el, binding) {
                el.__ovClickOutside = (e) => {
                    if (!el.contains(e.target)) {
                        if (typeof binding.value === 'function') binding.value(e);
                    }
                };
                document.addEventListener('mousedown', el.__ovClickOutside);
            },
            unmounted(el) {
                document.removeEventListener('mousedown', el.__ovClickOutside);
                delete el.__ovClickOutside;
            },
        });
    }

    app.component('v-orders-view', {
        template: '#tpl-orders-view',

        props: {
            channels:        { type: Array,  default: () => [] },
            statusLabels:    { type: Object, default: () => ({}) },
            chatpionBaseUrl: { type: String, default: '' },
            adminId:         { type: Number, default: 0 },
        },

        data() {
            return {
                records: [],
                meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 },
                summary: [],
                isLoading: false,
                searchTimer: null,

                filters: {
                    channel_ids: [],
                    statuses:    [],
                    search: '',
                    created_from: '',
                    created_to: '',
                    amount_min: null,
                    amount_max: null,
                },

                // Open/close state for the two multi-select dropdowns. Each
                // entry tracks whether its panel is currently visible.
                ms: {
                    channel: { open: false },
                    status:  { open: false },
                },

                sort: { by: 'id', dir: 'desc' },

                STATUS_KEYS: [
                    'pending', 'pending_payment', 'processing', 'shipped', 'completed',
                    'canceled', 'closed', 'fraud',
                ],

                // Per-row status dropdown. 'shipped' is virtual — flipping
                // a row to it doesn't change orders.status; instead we toggle
                // fujiba_label_printed=1 (the filter's source of truth). The
                // change handler dispatches to the right endpoint.
                ROW_STATUS_KEYS: [
                    'pending', 'pending_payment', 'processing', 'shipped', 'completed',
                    'canceled', 'closed', 'fraud',
                ],

                FULFILLMENT_OPTIONS: [
                    { value: 'delivery',      label: '宅配' },
                    { value: 'pickup',        label: '店舗受取' },
                    { value: 'cod',           label: '代引き' },
                    { value: 'bank_transfer', label: '銀行振込' },
                ],

                STATUS_MESSAGES: {
                    processing: 'ご注文を確認いたしました。発送準備を進めております。',
                    completed:  'ご注文の商品を発送いたしました。',
                    canceled:   '誠に恐れ入りますが、ご注文をキャンセルさせていただきました。',
                    pending_payment: 'お支払いの確認ができておりません。ご確認をお願いいたします。',
                },

                modal: {
                    status:      { open: false, busy: false, order: null, from: '', to: '', message: '', callSagawa: false },
                    address:     { open: false, busy: false, order: null, form: {} },
                    note:        { open: false, busy: false, order: null, text: '', previous: '' },
                    sagawa:      { open: false, busy: false, order: null, address: null, tracking: '', success: false },
                    tracking:    { open: false, busy: false, order: null, value: '', update: false },
                    fulfillment: { open: false, busy: false, order: null, type: '', preview: null, needs_cool: false },
                    payment:     { open: false, busy: false, order: null, confirmed: false, note: '' },
                    chat:        { open: false, busy: false, order: null, messages: [], error: '' },
                    editItems:   { open: false, busy: false, loading: false, order: null,
                                   items: [], shippingOverride: null, shippingMode: 'auto',
                                   note: '', search: '', searchResults: [], searchBusy: false,
                                   searchPage: 1, searchTotal: 0, searchHasMore: false,
                                   originalGrand: 0, errors: [] },
                },

                I18N: {
                    all_channels: 'すべてのチャンネル', all_statuses: 'すべてのステータス',
                    search_placeholder: '注文ID / 名前 / 電話 / 住所 / 郵便番号 / 追跡番号...', results: '件',
                    no_orders: '注文が見つかりません',
                    date_range: '日付範囲', date: '日付',
                    amount_min: '最小', amount_max: '最大',
                    customer: '顧客', chat: 'チャット', open_chat: '会話を開く',
                    open_in_livechat: 'ライブチャットで開く',
                    order: '注文', amount: '金額', payment: '支払方法',
                    status: 'ステータス', actions: '操作',
                    profit: '利益', sub: '小計', cost: '原価', fees: '手数料', discount: '割引', cool: 'クール料',
                    cost_unset: '仕入価格未設定',
                    cost_unset_for: '原価未設定の商品: ',
                    items: '点', cashondelivery: '代引', moneytransfer: '振込', cash: '現金', kpay: 'KPay',
                    view: '詳細', edit_address: '住所編集', note: 'メモ',
                    confirm: '確定', sagawa_print: 'Sagawa', assign_tracking: '追跡番号',
                    edit_tracking: '追跡番号変更',
                    update_status: 'ステータス更新',
                    customer_message: '顧客への通知メッセージ',
                    customer_message_hint: '空欄の場合、メールは送信されません',
                    call_sagawa_label: 'Sagawa 配送ラベルを発行する',
                    call_sagawa_label_hint: 'チェックを外すと、ステータスのみ更新し Sagawa は呼び出しません',
                    summary_filter_hint: 'クリックでこのチャンネル（保留 + 処理中）に絞り込み',
                    clear: 'クリア',
                    all_channels_short: 'すべてのチャンネル',
                    all_statuses_short: 'すべてのステータス',
                    selected_n: ':n 件選択',
                    save: '保存', saving: '保存中...', cancel: 'キャンセル', close: '閉じる', create: '作成',
                    name: 'お名前', phone: '電話', address1: '住所1', address2: '住所2',
                    city: '市区町村', state: '都道府県', postcode: '郵便番号', country: '国コード',
                    admin_note: '管理メモ', previous_note: '前回',
                    sagawa_create_shipment: 'Sagawa 出荷作成',
                    sagawa_verify_address: '出荷先住所をご確認ください：',
                    fix_address: '住所を修正',
                    tracking_number: '追跡番号', optional: '任意',
                    shipment_created: '出荷を作成しました',
                    next_step_assign_tracking: '次に追跡番号を割り当ててください。',
                    swap_payment_hint: '支払方法を変更（全ステータス可）',
                    change_to_suffix: 'に変更',
                    prev: '前', next: '次',
                    change_fulfillment: '配送方法変更',
                    fee_preview: '料金プレビュー',
                    shipping: '配送料',
                    cod_fee: '代引手数料',
                    cool_surcharge: 'クール料金',
                    grand_total_new: '新合計',
                    cool_applied_note: '商品にクール便指定があるため、クール料金が適用されます。',
                    sagawa_cod_warn_title: 'Sagawa 代引手数料が発生します',
                    sagawa_cod_warn_subtotal: '小計',
                    sagawa_cod_warn_tiers: '※9,999円以下: ¥330 / 10,000円以上: ¥440',
                    apply: '適用',
                    current: '現在',
                    confirm_pickup: '受取確認',
                    fulfillment_delivery: '宅配',
                    fulfillment_pickup: '店舗受取',
                    fulfillment_cod: '代引き',
                    fulfillment_bank_transfer: '銀行振込',
                    payment_confirm: '入金確認',
                    payment_state: '入金状態',
                    paid: '確認済',
                    unpaid: '未確認',
                    confirmed_at: '確認日時',
                    payment_note: '入金メモ',
                    payment_note_placeholder: '例: 振込確認 ¥28,500 / Chatpion 2026-04-27',
                    chatpion_open: 'Chatpionで確認 →',
                    no_chatpion_link: 'Chatpionリンク未設定（管理者が config/fujiba.php に base_url を設定してください）',
                    chat_open: '💬 チャット',
                    chat_title: 'チャット履歴',
                    chat_refresh: '更新',
                    chat_no_messages: 'まだメッセージがありません',
                    chat_not_linked: 'このお客様はMessengerと連携していません',
                    chat_loading: '読み込み中...',
                    chat_error: 'チャットの取得に失敗しました',
                    chat_open_in_full: '全画面で開く',
                    edit_items: '商品編集',
                    add_item: '商品追加',
                    qty: '数量',
                    unit_price: '単価',
                    line_total: '小計',
                    remove: '削除',
                    search_product: '商品検索 (SKU / 名前)',
                    in_stock: '在庫',
                    add: '追加',
                    shipping_auto: '自動再計算',
                    shipping_manual: '手動入力',
                    new_grand_total: '新合計',
                    grand_total_change: '差額',
                    payment_warning: '支払額が変わります。お客様に連絡してください。',
                    items_required: '商品が1つ以上必要です',
                },
            };
        },

        computed: {
            /* Edit-items modal preview totals. Pure functions of the in-modal
               state so any qty change reflows instantly without a server round-
               trip. The server still runs the authoritative recalc on submit. */
            previewSubTotal() {
                return (this.modal.editItems.items || [])
                    .reduce((sum, it) => sum + (it.price * (Number(it.qty) || 0)), 0);
            },
            previewShipping() {
                const m = this.modal.editItems;
                if (m.shippingMode === 'manual' && typeof m.shippingOverride === 'number') {
                    return m.shippingOverride;
                }
                /* Auto: keep the order's existing shipping until server recompute. */
                return m.order ? Number(m.order.shipping_amount || 0) : 0;
            },
            previewCodFee() {
                /* COD fee is only charged when the payment method is cash-
                   on-delivery, and the server re-resolves it via the
                   Sagawa tier table on every save. Mirror the same tiers
                   here so the preview matches what the server will write.
                   Tiers: ≤9999 → ¥330, ≥10000 → ¥440. */
                const m = this.modal.editItems;
                if (!m.order || m.order.payment_method !== 'cashondelivery') return 0;
                return this.previewSubTotal >= 10000 ? 440 : 330;
            },
            previewCoolSurcharge() {
                /* Cool surcharge is per-order, not per-item; if the order
                   has any cool-required item it gets applied. The modal
                   doesn't know per-item cool flags, so we keep the order's
                   original cool_surcharge unless the cart is empty. */
                const m = this.modal.editItems;
                if (!m.order || !m.items?.length) return 0;
                return Number(m.order.cool_surcharge || 0);
            },
            previewGrandTotal() {
                /* Tax recompute is server-side (per-item rate); for the modal
                   preview we approximate with the order's existing tax/discount
                   ratios so the user sees a sensible number. COD fee + cool
                   surcharge are added on top — they're stored separately on
                   the order, not folded into shipping. */
                const m = this.modal.editItems;
                const origSub = m.order ? Number(m.order.sub_total || 0) : 0;
                const origTax = m.order ? Number(m.order.tax_amount || 0) : 0;
                const origDisc= m.order ? Number(m.order.discount_amount || 0) : 0;

                const taxScaled = origSub > 0 ? origTax * (this.previewSubTotal / origSub) : 0;
                const discScaled= origSub > 0 ? origDisc * (this.previewSubTotal / origSub) : 0;
                return Math.round(this.previewSubTotal + taxScaled + this.previewShipping
                    + this.previewCodFee + this.previewCoolSurcharge - discScaled);
            },
            /* Defensive: never let null/undefined entries reach the template. */
            safeRecords() {
                return (this.records || []).filter(r => r && typeof r === 'object');
            },
            pageList() {
                /* Show up to 7 pages around the current page. */
                const cur = this.meta.current_page, last = this.meta.last_page;
                const span = 3;
                const start = Math.max(1, cur - span);
                const end   = Math.min(last, cur + span);
                const out = [];
                for (let i = start; i <= end; i++) out.push(i);
                return out;
            },
        },

        mounted() {
            /* Filter precedence on mount:
               1. URL params (deep-linked share/click) — highest priority
               2. Saved per-admin state in localStorage — survives reload
               3. Defaults — first-ever visit */
            const url = new URL(window.location.href);
            const pick = (clean, ...legacy) => {
                const v = url.searchParams.get(clean);
                if (v) return v;
                for (const k of legacy) {
                    const x = url.searchParams.get(k);
                    if (x) return x;
                }
                return '';
            };
            const collect = (clean, ...legacy) => {
                const out = url.searchParams.getAll(clean).filter(Boolean);
                if (out.length) return out;
                for (const k of legacy) {
                    const v = url.searchParams.get(k);
                    if (v) return [v];
                }
                return [];
            };

            const hasUrlFilters = url.searchParams.has('channel_id')
                || url.searchParams.has('status')
                || url.searchParams.has('search')
                || url.searchParams.has('created_from')
                || url.searchParams.has('created_to')
                || url.searchParams.has('amount_min')
                || url.searchParams.has('amount_max');

            if (hasUrlFilters) {
                this.filters.channel_ids = collect('channel_id', 'filters[channel_id][0]', 'filters[channel_id]')
                    .map(v => parseInt(v, 10)).filter(v => v > 0);
                this.filters.statuses    = collect('status',     'filters[status][0]',     'filters[status]');
                this.filters.search       = pick('search');
                this.filters.created_from = pick('created_from');
                this.filters.created_to   = pick('created_to');
                const aMin = pick('amount_min');
                const aMax = pick('amount_max');
                this.filters.amount_min = aMin === '' ? null : Number(aMin);
                this.filters.amount_max = aMax === '' ? null : Number(aMax);
            } else if (!this.restoreFilterState()) {
                // First-ever visit (no URL params, no saved state) — start
                // on the operator's working set: kimono store + open
                // statuses (pending, payment-pending, processing).
                this.applyDefaultFilters();
            }

            this.loadPage(1);
            this.loadSummary();

            // Persist on any filter / sort / per_page change. Deep watch
            // because filters is an object with arrays.
            this.$watch(() => JSON.stringify({
                f: this.filters,
                s: this.sort,
                pp: this.meta.per_page,
            }), () => this.persistFilterState());
        },

        methods: {
            t(key) { return this.I18N[key] || key; },

            statusLabel(v) {
                return this.statusLabels[v] || v;
            },

            missingCostTitle(rec) {
                const list = (rec && rec.missing_cost_products) || [];
                if (!list.length) return this.t('cost_unset');
                const names = list.map(p => p.name || p.sku || ('#' + p.product_id));
                return this.t('cost_unset_for') + names.join(' / ');
            },

            paymentLabel(method) {
                if (!method) return '—';
                // Lowercase codes (legacy rows) and human strings (production rows)
                const key = String(method).toLowerCase().replace(/\s+/g, '');
                const map = {
                    cashondelivery:   '代引',
                    moneytransfer:    '振込',
                    manual:           '振込', // production "Manual" = bank transfer
                    cash:             '現金',
                    pos_cash:         'POS現金',
                    poscash:          'POS現金',
                    kpay:             'KPay',
                };
                return map[key] || method;
            },

            // Editable in every status — staff sometimes need to re-classify
            // after the fact. Allow any of the four admin methods as both
            // source and target; rows on other methods (e.g. POS, paypal)
            // are left read-only to avoid clobbering external state.
            canSwapPayment(rec) {
                return rec.payment_method === 'cashondelivery'
                    || rec.payment_method === 'moneytransfer'
                    || rec.payment_method === 'cash'
                    || rec.payment_method === 'kpay';
            },

            paymentChipClass(method) {
                if (!method) return 'ov-pay-chip-other';
                const key = String(method).toLowerCase().replace(/\s+/g, '');
                if (key === 'cashondelivery') return 'ov-pay-chip-cod';
                if (key === 'moneytransfer' || key === 'manual') return 'ov-pay-chip-transfer';
                if (key === 'cash')                              return 'ov-pay-chip-cash';
                if (key === 'kpay')                              return 'ov-pay-chip-kpay';
                return 'ov-pay-chip-other';
            },

            // Per-admin filter persistence — survives page reload.
            // Keyed by admin id so two admins on the same browser don't
            // clobber each other. Bumped to v2 if the shape changes.
            filterStorageKey() {
                return 'fujiba.orders.filters.v2.' + (this.adminId || 0);
            },

            persistFilterState() {
                try {
                    const payload = {
                        filters: this.filters,
                        sort:    this.sort,
                        per_page: this.meta.per_page,
                    };
                    window.localStorage.setItem(this.filterStorageKey(), JSON.stringify(payload));
                } catch (e) { /* quota / private mode — silently ignore */ }
            },

            restoreFilterState() {
                try {
                    const raw = window.localStorage.getItem(this.filterStorageKey());
                    if (!raw) return false;
                    const saved = JSON.parse(raw);
                    if (!saved || typeof saved !== 'object') return false;
                    if (saved.filters) {
                        this.filters.channel_ids  = Array.isArray(saved.filters.channel_ids) ? saved.filters.channel_ids : [];
                        this.filters.statuses     = Array.isArray(saved.filters.statuses)    ? saved.filters.statuses    : [];
                        this.filters.search       = saved.filters.search       || '';
                        this.filters.created_from = saved.filters.created_from || '';
                        this.filters.created_to   = saved.filters.created_to   || '';
                        this.filters.amount_min   = saved.filters.amount_min ?? null;
                        this.filters.amount_max   = saved.filters.amount_max ?? null;
                    }
                    if (saved.sort && saved.sort.by) {
                        this.sort.by  = saved.sort.by;
                        this.sort.dir = saved.sort.dir === 'asc' ? 'asc' : 'desc';
                    }
                    if (saved.per_page) this.meta.per_page = Number(saved.per_page) || 10;
                    return true;
                } catch (e) { return false; }
            },

            // First-time defaults: kimono store + the three open statuses
            // (pending, pending_payment, processing). Resolves the kimono
            // channel id by code so it works across tenants. Falls back to
            // empty arrays if the channel isn't found.
            applyDefaultFilters() {
                const kimono = (this.channels || []).find(c => {
                    const code = (c.code || '').toLowerCase();
                    const name = (c.name || '').toLowerCase();
                    return code === 'kimono' || name === 'kimono';
                });
                this.filters.channel_ids = kimono ? [kimono.id] : [];
                this.filters.statuses    = ['pending', 'pending_payment', 'processing'];
            },

            initial(name) {
                return (name || '?').charAt(0).toUpperCase();
            },

            avColor(name) {
                const palette = ['ov-av-rose','ov-av-violet','ov-av-blue','ov-av-teal','ov-av-amber','ov-av-green'];
                if (!name) return palette[0];
                return palette[name.charCodeAt(0) % palette.length];
            },

            formatYen(n) {
                return Number(n || 0).toLocaleString('ja-JP');
            },

            formatDate(iso) {
                if (!iso) return '';
                const d = new Date(iso);
                if (isNaN(d.getTime())) return iso;
                return d.toLocaleString('ja-JP', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' });
            },

            formatAddress(a) {
                if (!a) return '';
                return [
                    a.name,
                    a.phone,
                    a.postcode + ' ' + a.country,
                    a.state + ' ' + a.city,
                    a.address1,
                    a.address2,
                ].filter(Boolean).join('\n');
            },

            viewUrl(id) {
                return '{{ url(config('app.admin_url')) }}/sales/orders/receipt/' + id;
            },

            livechatUrl(subscriberId) {
                return '{{ url(config('app.admin_url')) }}/fujiba-livechat?subscriber_id=' + encodeURIComponent(subscriberId);
            },

            // ─── load ────────────────────────────────
            onSearchInput() {
                clearTimeout(this.searchTimer);
                this.searchTimer = setTimeout(() => this.loadPage(1), 350);
            },

            loadPage(page) {
                this.isLoading = true;
                const params = {
                    page,
                    per_page: this.meta.per_page,
                    sort_by:  this.sort.by,
                    sort_dir: this.sort.dir,
                };
                if (this.filters.channel_ids.length) params.channel_id = this.filters.channel_ids;
                if (this.filters.statuses.length)    params.status     = this.filters.statuses;
                if (this.filters.search)             params.search     = this.filters.search;
                if (this.filters.created_from)       params.created_from = this.filters.created_from;
                if (this.filters.created_to)         params.created_to   = this.filters.created_to;
                if (this.filters.amount_min !== null && this.filters.amount_min !== '')
                    params.amount_min = this.filters.amount_min;
                if (this.filters.amount_max !== null && this.filters.amount_max !== '')
                    params.amount_max = this.filters.amount_max;

                this.$axios.get('{{ url(config('app.admin_url')) }}/api/orders', {
                    params,
                    // Axios default `indexes: true` emits status[0]=pending&status[1]=processing
                    // which Laravel parses into an array. `indexes: null` would emit the repeat
                    // form (status=a&status=b) which Laravel collapses to the last value — wrong.
                })
                    .then(r => {
                        this.records = r.data.data || [];
                        this.meta    = r.data.meta || this.meta;
                        this.fetchAvatars();
                        this.loadSummary();
                    })
                    .catch(e => this.flashError(e, 'load'))
                    .finally(() => { this.isLoading = false; });
            },

            // Open-orders summary (pending + processing per channel) — fed
            // by /api/orders/summary, refreshed on every page load so the
            // pills stay in sync after status flips, cancellations, etc.
            loadSummary() {
                this.$axios.get('{{ url(config('app.admin_url')) }}/api/orders/summary')
                    .then(r => { this.summary = (r.data && r.data.data) || []; })
                    .catch(() => { /* non-fatal — pills hide when array is empty */ });
            },

            // ─── multi-select helpers ────────────────
            toggleMs(name) {
                // Open the requested panel and close the other one — only
                // ever one open at a time keeps the UI uncluttered.
                for (const k of Object.keys(this.ms)) {
                    this.ms[k].open = (k === name) ? !this.ms[k].open : false;
                }
            },
            closeMs(name) { if (this.ms[name]) this.ms[name].open = false; },
            clearMs(name) {
                if (name === 'channel') this.filters.channel_ids = [];
                if (name === 'status')  this.filters.statuses    = [];
                this.loadPage(1);
            },
            msLabel(name) {
                if (name === 'channel') {
                    const ids = this.filters.channel_ids;
                    if (!ids.length) return this.t('all_channels_short');
                    if (ids.length === 1) {
                        const ch = (this.channels || []).find(c => c.id === ids[0]);
                        return ch ? ch.name : ('#' + ids[0]);
                    }
                    return this.t('selected_n').replace(':n', ids.length);
                }
                if (name === 'status') {
                    const ks = this.filters.statuses;
                    if (!ks.length) return this.t('all_statuses_short');
                    if (ks.length === 1) return this.statusLabels[ks[0]] || ks[0];
                    return this.t('selected_n').replace(':n', ks.length);
                }
                return '';
            },

            summaryClass(s) {
                const code = (s.channel_code || '').toLowerCase();
                const name = (s.name         || '').toLowerCase();
                if (code.includes('mmfood') || name.includes('food'))   return 'is-food';
                if (code === 'default'      || name.includes('kimono')) return 'is-kimono';
                return '';
            },

            summaryLabel(s) {
                // Prefer translated channel name; fall back to channel code.
                return s.name || s.channel_code || ('Channel #' + s.channel_id);
            },

            applySummaryFilter(s) {
                // Pin the channel and select pending+processing — that's the
                // exact set the pill represents.
                this.filters.channel_ids = [parseInt(s.channel_id, 10)];
                this.filters.statuses    = ['pending', 'processing'];
                this.loadPage(1);
            },

            // Avatars come from Chatpion (external HTTP) — fetched after the
            // table renders so the initial paint isn't blocked. Records that
            // already have a local avatar_url (from customer.image) are
            // skipped; the rest get patched in place when the response lands.
            fetchAvatars() {
                const ids = (this.records || [])
                    .filter(r => r && r.id && !(r.customer && r.customer.avatar_url))
                    .map(r => r.id);
                if (!ids.length) return;
                this.$axios.get('{{ url(config('app.admin_url')) }}/api/orders/avatars', {
                    params: { ids },
                    paramsSerializer: { indexes: null }, // ids[]=1&ids[]=2
                })
                    .then(r => {
                        const map = (r.data && r.data.data) || {};
                        for (const rec of this.records) {
                            const url = map[rec.id];
                            if (url && rec.customer) rec.customer.avatar_url = url;
                        }
                    })
                    .catch(() => { /* avatar fallback to initial — not fatal */ });
            },

            toggleSort(column) {
                if (this.sort.by === column) {
                    this.sort.dir = this.sort.dir === 'asc' ? 'desc' : 'asc';
                } else {
                    this.sort.by = column;
                    this.sort.dir = 'desc';
                }
                this.loadPage(1);
            },

            sortArrow(column) {
                if (this.sort.by !== column) return '';
                return this.sort.dir === 'asc' ? '▲' : '▼';
            },

            // ─── status ──────────────────────────────

            // Effective row status for the dropdown's :value binding.
            // 'shipped' is derived from fujiba_label_printed, not stored as a
            // real orders.status value, so reflect that in the UI.
            rowStatusValue(rec) {
                if (rec && rec.label_printed) return 'shipped';
                return rec ? rec.status : '';
            },

            // Row-status dropdown change. Dispatches:
            //   shipped  → POST /label-printed (no status change)
            //   anything → existing status modal flow
            onRowStatusChange(rec, evt) {
                const next = evt.target.value;
                // Reset the visual selection — both branches re-render after
                // the network round-trip; this prevents flicker if the user
                // cancels the modal.
                evt.target.value = this.rowStatusValue(rec);

                if (!next || next === this.rowStatusValue(rec)) return;

                if (next === 'shipped') {
                    this.markShipped(rec);
                    return;
                }
                this.openStatusModal(rec, next);
            },

            // Flip label_printed=1 directly. Used by the per-row status
            // dropdown when the admin picks "shipped" — a row-level shortcut
            // for "Sagawa label issued" without going through the full
            // shipment-creation modal.
            markShipped(rec) {
                if (rec.label_printed) return; // already shipped — no-op
                this.$axios.post('{{ url(config('app.admin_url')) }}/api/orders/' + rec.id + '/label-printed')
                    .then(() => {
                        rec.label_printed = true;
                        this.flash('success', this.statusLabels['shipped'] || 'Shipped');
                        this.loadSummary();
                    })
                    .catch(e => this.flashError(e, 'shipped'));
            },

            openStatusModal(rec, newStatus) {
                if (!newStatus || newStatus === rec.status) return;
                // Default the Sagawa toggle ON when transitioning into
                // processing on a delivery order with no label yet — that's
                // the natural moment to issue the shipping label. Off in
                // every other case so admins don't accidentally call Sagawa
                // when flipping to canceled / completed / etc.
                const sagawaEligible =
                    newStatus === 'processing'
                    && !rec.label_printed
                    && rec.fulfillment_type !== 'pickup';
                this.modal.status = {
                    open: true, busy: false,
                    order: rec, from: rec.status, to: newStatus,
                    message: this.STATUS_MESSAGES[newStatus] || '',
                    callSagawa: sagawaEligible,
                };
            },

            // Whether to render the Sagawa-label checkbox in the status modal.
            canPromptSagawa() {
                const m = this.modal.status;
                if (!m.order) return false;
                if (m.order.label_printed) return false;
                if (m.order.fulfillment_type === 'pickup') return false;
                return m.to === 'processing';
            },

            confirmOrder(rec) {
                this.openStatusModal(rec, 'processing');
            },

            submitStatus() {
                const m = this.modal.status;
                m.busy = true;
                this.$axios.post('{{ url(config('app.admin_url')) }}/api/orders/' + m.order.id + '/status', {
                    status: m.to,
                    customer_message: m.message || null,
                })
                    .then(() => {
                        // Optional Sagawa label print, fired after the status flip
                        // so the order is in 'processing' before the shipment is
                        // created (Bagisto's ShipmentController rejects shipments
                        // on non-shipping statuses).
                        if (m.callSagawa && this.canPromptSagawa()) {
                            this.flash('success', 'ステータスを更新しました');
                            this.closeAll();
                            // Hand off to the existing Sagawa modal flow so the
                            // admin can verify the address and enter the
                            // tracking number — same UX as today's 🖨 button.
                            const fresh = (this.records || []).find(r => r.id === m.order.id) || m.order;
                            fresh.status = m.to; // optimistic — refetch will reconcile
                            this.openSagawaModal(fresh);
                            this.loadPage(this.meta.current_page);
                            return;
                        }
                        this.flash('success', 'ステータスを更新しました');
                        this.closeAll();
                        this.loadPage(this.meta.current_page);
                    })
                    .catch(e => { m.busy = false; this.flashError(e, 'status'); });
            },

            // ─── payment swap ────────────────────────
            // 3-way picker: cashondelivery / moneytransfer / cash.
            // Invoked from the row <select> @change. Reverts the <select>
            // back to the original value on failure so the UI doesn't lie.
            changePaymentMethod(rec, event) {
                const next = event?.target?.value;
                if (!next || next === rec.payment_method) return;
                this.$axios.post('{{ url(config('app.admin_url')) }}/api/orders/' + rec.id + '/payment-method', { method: next })
                    .then(() => {
                        this.flash('success', '支払方法を変更しました');
                        this.loadPage(this.meta.current_page);
                    })
                    .catch(e => {
                        if (event?.target) event.target.value = rec.payment_method;
                        this.flashError(e, 'payment');
                    });
            },

            // Legacy binary toggle — kept as a thin redirect so any external
            // shortcut binding still works.
            swapPayment(rec) {
                const next = rec.payment_method === 'cashondelivery' ? 'moneytransfer' : 'cashondelivery';
                this.$axios.post('{{ url(config('app.admin_url')) }}/api/orders/' + rec.id + '/payment-method', { method: next })
                    .then(() => { this.flash('success', '支払方法を変更しました'); this.loadPage(this.meta.current_page); })
                    .catch(e => this.flashError(e, 'payment'));
            },

            // ─── address ─────────────────────────────
            openAddressModal(rec) {
                const a = rec.shipping_address || {};
                this.modal.address = {
                    open: true, busy: false, order: rec,
                    form: {
                        name: a.name || '',  phone: a.phone || '',
                        address1: a.address1 || '', address2: a.address2 || '',
                        city: a.city || '', state: a.state || '',
                        postcode: a.postcode || '', country: a.country || 'JP',
                    },
                };
            },

            submitAddress() {
                const m = this.modal.address;
                m.busy = true;
                this.$axios.post('{{ url(config('app.admin_url')) }}/api/orders/' + m.order.id + '/address', m.form)
                    .then(() => {
                        this.flash('success', '住所を更新しました');
                        this.closeAll();
                        this.loadPage(this.meta.current_page);
                    })
                    .catch(e => { m.busy = false; this.flashError(e, 'address'); });
            },

            // ─── note ────────────────────────────────
            openNoteModal(rec) {
                this.modal.note = {
                    open: true, busy: false, order: rec,
                    text: '',
                    previous: rec.admin_note || '',
                };
            },

            submitNote() {
                const m = this.modal.note;
                if (!m.text.trim()) { this.closeAll(); return; }
                m.busy = true;
                this.$axios.post('{{ url(config('app.admin_url')) }}/api/orders/' + m.order.id + '/note', {
                    customer_note: m.text,
                })
                    .then(() => {
                        this.flash('success', 'メモを保存しました');
                        this.closeAll();
                        this.loadPage(this.meta.current_page);
                    })
                    .catch(e => { m.busy = false; this.flashError(e, 'note'); });
            },

            // ─── sagawa ──────────────────────────────
            openSagawaModal(rec) {
                this.modal.sagawa = {
                    open: true, busy: false, order: rec,
                    address: rec.shipping_address,
                    tracking: '', success: false,
                };
            },

            submitSagawa() {
                const m = this.modal.sagawa;
                m.busy = true;
                /* Use Bagisto's existing /api/v1/admin/sales/shipments/{order_id} endpoint.
                   Body shape comes from ShipmentController::store: items keyed by
                   order_item_id with qty per inventory source. */
                const items = {};
                (m.order.items || []).forEach(it => { items[it.id] = { 0: it.qty_to_ship || it.qty_ordered || 1 }; });

                this.$axios.post('/api/v1/admin/sales/shipments/' + m.order.id, {
                    shipment: {
                        carrier_title: 'Sagawa',
                        track_number: m.tracking || '',
                        items,
                    },
                })
                    .then(() => {
                        // Flip label_printed flag — fire-and-forget; we patch the
                        // local row optimistically below for instant UI feedback.
                        this.$axios.post('{{ url(config('app.admin_url')) }}/api/orders/' + m.order.id + '/label-printed').catch(() => {});

                        // Reactive row update so the Sagawa button hides and
                        // the Tracking button appears without waiting for refetch.
                        m.order.label_printed = true;
                        if (m.tracking) m.order.tracking_number = m.tracking;

                        m.success = true;
                        m.busy = false;
                        this.loadPage(this.meta.current_page);
                    })
                    .catch(e => { m.busy = false; this.flashError(e, 'sagawa'); });
            },

            // ─── tracking ────────────────────────────
            openTrackingModal(rec) {
                this.modal.tracking = {
                    open: true, busy: false, order: rec,
                    value: rec.tracking_number || '',
                    update: !!rec.tracking_number,
                };
            },

            submitTracking() {
                const m = this.modal.tracking;
                m.busy = true;

                /* If a shipment already exists for this order, just patch its
                   track_number — avoids duplicate shipment rows. Otherwise
                   create a new shipment via Bagisto's existing endpoint. */
                if (m.update) {
                    this.$axios.patch('{{ url(config('app.admin_url')) }}/api/orders/' + m.order.id + '/tracking', {
                        tracking_number: m.value,
                    })
                        .then(() => {
                            this.flash('success', '追跡番号を更新しました');
                            this.closeAll();
                            this.loadPage(this.meta.current_page);
                        })
                        .catch(e => { m.busy = false; this.flashError(e, 'tracking'); });
                    return;
                }

                const items = {};
                (m.order.items || []).forEach(it => { items[it.id] = { 0: it.qty_to_ship || it.qty_ordered || 1 }; });

                this.$axios.post('/api/v1/admin/sales/shipments/' + m.order.id, {
                    shipment: {
                        carrier_title: 'Sagawa',
                        track_number: m.value,
                        items,
                    },
                })
                    .then(() => {
                        this.flash('success', '追跡番号を保存しました');
                        this.closeAll();
                        this.loadPage(this.meta.current_page);
                    })
                    .catch(e => { m.busy = false; this.flashError(e, 'tracking'); });
            },

            // ─── fulfillment ─────────────────────────
            fulfillmentLabel(type) {
                const map = {
                    delivery:      this.t('fulfillment_delivery'),
                    pickup:        this.t('fulfillment_pickup'),
                    cod:           this.t('fulfillment_cod'),
                    bank_transfer: this.t('fulfillment_bank_transfer'),
                };
                return map[type] || type || this.t('fulfillment_delivery');
            },

            canSagawa(rec) {
                return ['processing'].includes(rec.status)
                    && !rec.label_printed
                    && rec.fulfillment_type !== 'pickup';
            },

            openFulfillmentModal(rec) {
                this.modal.fulfillment = {
                    open: true, busy: false, order: rec,
                    type: rec.fulfillment_type || 'delivery',
                    preview: null, needs_cool: false,
                };
                this.loadFulfillmentPreview();
            },

            loadFulfillmentPreview() {
                const m = this.modal.fulfillment;
                if (!m.type || !m.order) return;
                this.$axios.post('{{ url(config('app.admin_url')) }}/api/orders/' + m.order.id + '/fulfillment/preview', {
                    type: m.type,
                })
                    .then(r => { m.preview = r.data.preview; m.needs_cool = !!r.data.needs_cool; })
                    .catch(e => this.flashError(e, 'fulfillment-preview'));
            },

            submitFulfillment() {
                const m = this.modal.fulfillment;
                m.busy = true;
                this.$axios.post('{{ url(config('app.admin_url')) }}/api/orders/' + m.order.id + '/fulfillment', {
                    type: m.type,
                })
                    .then(r => {
                        // Reactive row update so totals + button visibility refresh instantly.
                        const updated = r.data.data;
                        const idx = this.records.findIndex(x => x.id === m.order.id);
                        if (idx >= 0 && updated) {
                            this.records[idx].fulfillment_type = m.type;
                            this.records[idx].grand_total      = updated.grand_total;
                            this.records[idx].shipping_amount  = updated.shipping_amount;
                            this.records[idx].cod_fee          = updated.cod_fee;
                            this.records[idx].cool_surcharge   = updated.cool_surcharge;
                        }
                        this.flash('success', this.t('change_fulfillment') + ': ' + this.fulfillmentLabel(m.type));
                        this.closeAll();
                    })
                    .catch(e => { m.busy = false; this.flashError(e, 'fulfillment'); });
            },

            // ─── chat (read-only history modal) ─────
            openChatModal(rec) {
                if (!rec.customer?.messenger_subscriber_id) {
                    this.flash('warning', this.t('chat_not_linked'));
                    return;
                }
                this.modal.chat = {
                    open: true, busy: true, order: rec,
                    messages: [], error: '',
                };
                this.loadChatMessages();
            },

            loadChatMessages() {
                const m = this.modal.chat;
                if (!m.order) return;
                m.busy = true;
                m.error = '';
                const params = {
                    subscriber_id: m.order.customer.messenger_subscriber_id,
                    channel_id:    m.order.channel_id,
                };
                this.$axios.get('{{ url(config('app.admin_url')) }}/fujiba-livechat/conversation', { params })
                    .then(r => {
                        if (r.data?.status === 'error') {
                            m.error = r.data.message || this.t('chat_error');
                            m.messages = [];
                            return;
                        }
                        const raw = r.data?.data || [];
                        m.messages = raw
                            .map(this.parseChatMessage)
                            .filter(x => x !== null);
                        // Auto-scroll to bottom on next paint.
                        this.$nextTick(() => {
                            const el = this.$refs.chatScroll;
                            if (el) el.scrollTop = el.scrollHeight;
                        });
                    })
                    .catch(e => {
                        m.error = e?.response?.data?.message || this.t('chat_error');
                        m.messages = [];
                    })
                    .finally(() => { m.busy = false; });
            },

            /*
             * Port of LC.renderMessages from /admin/fujiba-livechat — Chatpion
             * stores message bodies in several shapes (raw FB webhook JSON,
             * postback, referral, plain text), so we have to disambiguate.
             * Returns null for events we want to skip (delivery / read / etc).
             */
            parseChatMessage(m) {
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
                            html = `<span class="ov-msg-meta">▸ ${this.escHtml(postback.title || '[postback]')}</span>`;
                        } else if (referral && !mg) {
                            const src = referral.source || '';
                            const ref = referral.ref ? ' · ' + this.escHtml(referral.ref) : '';
                            html = `<span class="ov-msg-meta">⤷ Opened thread from ${this.escHtml(src)}${ref}</span>`;
                        } else {
                            html = this.renderFbMessage(mg) || `<span class="ov-msg-meta">[empty]</span>`;
                        }
                    } catch (_) {
                        html = `<span class="ov-msg-meta">[unparseable]</span>`;
                    }
                } else if (raw) {
                    html = this.escHtml(raw);
                }

                if (m.media_url) {
                    const url = this.escHtml(m.media_url);
                    const isImg = /\.(jpg|jpeg|png|gif|webp)$/i.test(m.media_url)
                        || ['image', 'photo'].includes((m.message_type || '').toLowerCase());
                    if (isImg) {
                        const img = `<a href="${url}" target="_blank" rel="noopener"><img src="${url}" class="ov-msg-img"></a>`;
                        html = html ? img + '<br>' + html : img;
                    } else {
                        html += ` <a href="${url}" target="_blank" rel="noopener" class="ov-msg-file">[file]</a>`;
                    }
                }

                return {
                    dir,
                    html,
                    time: m.created_at || m.conversation_time || '',
                };
            },

            /* Inline FB message-payload renderer — text, attachments,
             * quick replies. Mirrors LC.renderFbMessage. */
            renderFbMessage(mg) {
                if (!mg) return '';
                let out = '';
                if (mg.text) out += this.escHtml(mg.text);
                if (mg.attachment) {
                    const att = mg.attachment;
                    if (att.type === 'image' && att.payload?.url) {
                        out += `<a href="${this.escHtml(att.payload.url)}" target="_blank" rel="noopener"><img src="${this.escHtml(att.payload.url)}" class="ov-msg-img"></a>`;
                    } else if (att.payload?.url) {
                        out += ` <a href="${this.escHtml(att.payload.url)}" target="_blank" rel="noopener" class="ov-msg-file">[${this.escHtml(att.type || 'file')}]</a>`;
                    }
                }
                if (Array.isArray(mg.attachments)) {
                    mg.attachments.forEach(att => {
                        if (att.type === 'image' && att.payload?.url) {
                            out += `<a href="${this.escHtml(att.payload.url)}" target="_blank" rel="noopener"><img src="${this.escHtml(att.payload.url)}" class="ov-msg-img"></a>`;
                        } else if (att.payload?.url) {
                            out += ` <a href="${this.escHtml(att.payload.url)}" target="_blank" rel="noopener" class="ov-msg-file">[${this.escHtml(att.type || 'file')}]</a>`;
                        }
                    });
                }
                return out;
            },

            escHtml(s) {
                return String(s ?? '').replace(/[&<>"']/g, c => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
                }[c]));
            },

            // ─── payment confirm ─────────────────────
            // Production stores method as human strings ("Manual", "Cash on
            // Delivery") on most rows, with a small number of legacy rows
            // using the lowercase codes ("cashondelivery", "moneytransfer").
            // Treat anything that isn't COD as bank-transfer-style (admin
            // confirms payment manually). COD doesn't need confirmation —
            // money is collected at delivery.
            needsPaymentConfirm(rec) {
                const m = (rec.payment_method || '').toLowerCase().replace(/\s+/g, '');
                if (!m) return false;
                if (m === 'cashondelivery' || m === 'cod') return false;
                return true;
            },

            openPaymentModal(rec) {
                this.modal.payment = {
                    open: true, busy: false, order: rec,
                    confirmed: !!rec.payment_confirmed_at,
                    note: rec.payment_note || '',
                };
            },

            chatpionUrl(subscriberId) {
                if (!this.chatpionBaseUrl || !subscriberId) return '#';
                return this.chatpionBaseUrl.replace(/\/+$/, '') + '/subscriber/' + encodeURIComponent(subscriberId);
            },

            submitPayment() {
                const m = this.modal.payment;
                m.busy = true;
                this.$axios.post('{{ url(config('app.admin_url')) }}/api/orders/' + m.order.id + '/payment-confirm', {
                    confirmed: m.confirmed,
                    note: m.note || null,
                })
                    .then(r => {
                        const idx = this.records.findIndex(x => x.id === m.order.id);
                        if (idx >= 0) {
                            this.records[idx].payment_confirmed_at = r.data.data.payment_confirmed_at;
                            this.records[idx].payment_note         = r.data.data.payment_note;
                        }
                        this.flash('success', m.confirmed ? this.t('paid') : this.t('unpaid'));
                        this.closeAll();
                    })
                    .catch(e => { m.busy = false; this.flashError(e, 'payment-confirm'); });
            },

            formatShortDate(iso) {
                if (!iso) return '';
                const d = new Date(iso);
                if (isNaN(d.getTime())) return iso;
                return d.toLocaleDateString('ja-JP', { month: '2-digit', day: '2-digit' });
            },

            confirmPickup(rec) {
                // Pickup orders skip the Sagawa label step. "受取確認" = mark
                // the order as picked up by flipping the label_printed flag.
                this.$axios.post('{{ url(config('app.admin_url')) }}/api/orders/' + rec.id + '/label-printed')
                    .then(() => {
                        rec.label_printed = true;
                        this.flash('success', this.t('confirm_pickup') + ' ✓');
                    })
                    .catch(e => this.flashError(e, 'confirm-pickup'));
            },

            // ─── edit items ──────────────────────────
            openEditItemsModal(rec) {
                this.modal.editItems = {
                    open: true, busy: false, loading: true, order: rec,
                    items: [], shippingOverride: null, shippingMode: 'auto',
                    note: '', search: '', searchResults: [], searchBusy: false,
                    searchPage: 1, searchTotal: 0, searchHasMore: false,
                    originalGrand: Number(rec.grand_total || 0), errors: [],
                };
                this.$axios.get('{{ url(config('app.admin_url')) }}/api/orders/' + rec.id + '/edit-items')
                    .then(r => {
                        this.modal.editItems.loading = false;
                        this.modal.editItems.order = r.data.order;
                        this.modal.editItems.originalGrand = Number(r.data.order.grand_total || 0);
                        this.modal.editItems.items = (r.data.items || []).map(it => ({
                            order_item_id: it.id,
                            product_id: it.product_id,
                            sku: it.sku, name: it.name,
                            image: it.image || null,
                            price: Number(it.price || 0),
                            qty: Number(it.qty || 1),
                        }));
                        // Pre-populate the picker with recent products so the
                        // admin can browse without having to type first.
                        this.searchEditProducts();
                    })
                    .catch(e => {
                        this.modal.editItems.loading = false;
                        this.flashError(e, 'edit-items');
                    });
            },
            recalcPreview() {
                /* Computed properties redraw from items[].qty automatically.
                   Sanitization happens in commitEditQty on blur/change so we
                   don't snap an in-progress empty value back to 1 mid-typing. */
            },
            stepEditQty(idx, delta) {
                const it = this.modal.editItems.items[idx];
                if (!it) return;
                const next = Math.max(1, (Number(it.qty) || 1) + delta);
                it.qty = next;
            },
            onEditQtyInput(idx, ev) {
                /* Allow intermediate empty/partial values while typing — store
                   the raw string so the input doesn't fight the user. The
                   computed preview uses Number(it.qty) || 0 to render safely. */
                const it = this.modal.editItems.items[idx];
                if (!it) return;
                it.qty = ev.target.value;
            },
            commitEditQty(idx, ev) {
                const it = this.modal.editItems.items[idx];
                if (!it) return;
                let v = parseInt(ev.target.value, 10);
                if (!Number.isFinite(v) || v < 1) v = 1;
                it.qty = v;
                ev.target.value = String(v);
            },
            removeEditItem(idx) {
                this.modal.editItems.items.splice(idx, 1);
            },
            /**
             * Search/browse products in the order's channel. Resets to page 1
             * whenever the query string changes; pagination uses the same
             * method via loadMore=true so we can append results on scroll.
             */
            searchEditProducts(loadMore = false) {
                const m = this.modal.editItems;
                const q = (m.search || '').trim();

                if (!loadMore) {
                    m.searchPage = 1;
                    m.searchResults = [];
                    m.searchHasMore = false;
                }

                // Block further scroll-fired loads immediately — otherwise rapid
                // scrolls fire multiple page fetches before the first one returns
                // and produce duplicate cards.
                m.searchBusy = true;

                // Track which request is the latest; stale responses (a previous
                // query whose result arrives after the user typed again) are
                // discarded so they don't overwrite the new results.
                m.searchSeq = (m.searchSeq || 0) + 1;
                const seq = m.searchSeq;

                if (m.searchTimer) clearTimeout(m.searchTimer);
                m.searchTimer = setTimeout(() => {
                    this.$axios.get('{{ url(config('app.admin_url')) }}/api/orders/' + m.order.id + '/edit-items/search', {
                        params: { q, page: m.searchPage, per_page: 30 },
                    })
                        .then(r => {
                            if (seq !== m.searchSeq) return; // stale
                            const existing = new Set(m.items.map(i => i.product_id));
                            const seen     = new Set(m.searchResults.map(p => p.id));
                            const newRows  = (r.data.data || []).filter(p => !existing.has(p.id) && !seen.has(p.id));
                            if (loadMore) {
                                m.searchResults = m.searchResults.concat(newRows);
                            } else {
                                m.searchResults = newRows;
                            }
                            m.searchTotal   = Number(r.data.total || 0);
                            m.searchHasMore = !!r.data.has_more;
                            m.searchBusy    = false;
                        })
                        .catch(() => {
                            if (seq === m.searchSeq) m.searchBusy = false;
                        });
                }, loadMore ? 0 : 250);
            },
            /**
             * Infinite scroll: when the user scrolls within ~120px of the
             * bottom of the picker grid, fetch the next page.
             */
            onEditGridScroll(ev) {
                const m = this.modal.editItems;
                if (m.searchBusy || !m.searchHasMore) return;
                const el = ev.target;
                if (el.scrollTop + el.clientHeight >= el.scrollHeight - 120) {
                    m.searchPage += 1;
                    this.searchEditProducts(true);
                }
            },
            addEditItem(product) {
                this.modal.editItems.items.push({
                    order_item_id: null,
                    product_id: product.id,
                    sku: product.sku, name: product.name,
                    image: product.image || null,
                    price: Number(product.price || 0),
                    qty: 1,
                });
                // Drop the just-added card from the visible grid without
                // re-firing the request — keeps the user's scroll position.
                this.modal.editItems.searchResults =
                    this.modal.editItems.searchResults.filter(p => p.id !== product.id);
            },
            submitEditItems() {
                const m = this.modal.editItems;
                if (!m.items.length) return;
                m.busy = true;
                m.errors = [];

                const payload = {
                    items: m.items.map(it => ({
                        product_id: it.product_id,
                        qty: Math.max(1, parseInt(it.qty, 10) || 1),
                        order_item_id: it.order_item_id,
                    })),
                    shipping_amount: m.shippingMode === 'manual' ? Number(m.shippingOverride || 0) : null,
                    note: m.note,
                };

                this.$axios.post('{{ url(config('app.admin_url')) }}/api/orders/' + m.order.id + '/edit-items', payload)
                    .then(() => {
                        this.flash('success', '商品を更新しました');
                        this.closeAll();
                        this.loadPage(this.meta.current_page);
                    })
                    .catch(e => {
                        m.busy = false;
                        m.errors = e?.response?.data?.errors || [e?.response?.data?.message || 'Save failed'];
                    });
            },

            // ─── helpers ─────────────────────────────
            closeAll() {
                Object.keys(this.modal).forEach(k => { this.modal[k].open = false; });
            },

            flash(type, msg) {
                if (this.$emitter) this.$emitter.emit('add-flash', { type, message: msg });
            },

            flashError(err, ctx) {
                const msg = err?.response?.data?.message || ('Error: ' + ctx);
                this.flash('error', msg);
            },
        },
    });
</script>
@endpush
