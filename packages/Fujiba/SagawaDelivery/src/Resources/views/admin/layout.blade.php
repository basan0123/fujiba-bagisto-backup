<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '佐川管理') — Sagawa Admin</title>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Noto Sans JP', sans-serif; background: #f5f5f7; color: #1d1d1f; font-size: 14px; min-height: 100dvh; display: flex; flex-direction: column; }
        .container { max-width: 960px; margin: 0 auto; padding: 0 16px; width: 100%; }
        .top-bar { background: #fff; border-bottom: 1px solid #e5e5e5; padding: 12px 0; position: sticky; top: 0; z-index: 50; }
        .top-bar h1 { font-size: 18px; font-weight: 600; }
        .bottom-nav { position: fixed; bottom: 0; left: 0; right: 0; background: #fff; border-top: 1px solid #e5e5e5; display: flex; z-index: 50; padding-bottom: env(safe-area-inset-bottom, 0); }
        .bottom-nav a { flex: 1; text-align: center; padding: 10px 4px 8px; font-size: 11px; color: #86868b; text-decoration: none; display: flex; flex-direction: column; align-items: center; gap: 2px; }
        .bottom-nav a.active { color: #0071e3; }
        .bottom-nav a svg { width: 22px; height: 22px; }
        .main { flex: 1; padding: 16px 0 80px; }
        .card { background: #fff; border-radius: 12px; padding: 16px; margin-bottom: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .badge { display: inline-block; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 6px; text-transform: uppercase; letter-spacing: .3px; }
        .badge-pending_verification { background: #fef3c7; color: #92400e; }
        .badge-address_verified    { background: #dbeafe; color: #1e40af; }
        .badge-address_rejected    { background: #f3f4f6; color: #6b7280; }
        .badge-waybill_pending     { background: #dbeafe; color: #1e40af; animation: pulse 2s infinite; }
        .badge-waybill_issued      { background: #d1fae5; color: #065f46; }
        .badge-waybill_failed      { background: #fee2e2; color: #991b1b; }
        .badge-reissue_requested   { background: #ede9fe; color: #5b21b6; }
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.5} }
        .meta { color: #86868b; font-size: 12px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; border: none; cursor: pointer; transition: opacity .15s; }
        .btn:disabled { opacity: .4; cursor: not-allowed; }
        .btn-primary { background: #0071e3; color: #fff; }
        .btn-success { background: #059669; color: #fff; }
        .btn-danger  { background: transparent; color: #dc2626; border: 1.5px solid #dc2626; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; font-size: 12px; font-weight: 500; margin-bottom: 4px; color: #6b7280; }
        .form-group input, .form-group textarea { width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #0071e3; box-shadow: 0 0 0 3px rgba(0,113,227,.15); }
        .addr-compare { display: grid; gap: 12px; }
        @media (min-width: 768px) { .addr-compare { grid-template-columns: 1fr 1fr; } }
        .addr-col { padding: 12px; border-radius: 8px; background: #f9fafb; }
        .addr-col h4 { font-size: 12px; color: #6b7280; margin-bottom: 8px; }
        .result-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
        .result-dot.green  { background: #059669; }
        .result-dot.yellow { background: #d97706; }
        .result-dot.red    { background: #dc2626; }
        .tracking-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px; display: flex; align-items: center; gap: 8px; }
        .tracking-box code { font-size: 16px; font-weight: 600; letter-spacing: 1px; }
        .countdown { font-size: 12px; color: #6b7280; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 8px 12px; border-bottom: 1px solid #e5e5e5; font-size: 13px; }
        th { font-weight: 600; color: #6b7280; font-size: 11px; text-transform: uppercase; }
        .filter-bar { display: flex; gap: 8px; overflow-x: auto; padding: 8px 0 16px; }
        .filter-chip { padding: 6px 14px; border-radius: 20px; background: #e5e5e5; color: #1d1d1f; font-size: 12px; white-space: nowrap; cursor: pointer; text-decoration: none; }
        .filter-chip.active { background: #0071e3; color: #fff; }
    </style>
    @stack('styles')
</head>
<body>

<div class="top-bar">
    <div class="container">
        <h1>@yield('title', '佐川配送管理')</h1>
    </div>
</div>

<div class="main">
    <div class="container">
        @yield('content')
    </div>
</div>

<nav class="bottom-nav">
    <a href="{{ route('admin.sagawa.queue.index') }}" class="{{ request()->routeIs('admin.sagawa.queue.*') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
        Queue
    </a>
    <a href="{{ route('admin.sagawa.usage') }}" class="{{ request()->routeIs('admin.sagawa.usage') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
        Usage
    </a>
    <a href="#">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
        Settings
    </a>
</nav>

@stack('scripts')
</body>
</html>
