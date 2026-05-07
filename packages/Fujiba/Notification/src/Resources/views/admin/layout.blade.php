{{--
   Standalone admin layout for the Fujiba Notification package.

   Why this exists instead of <x-admin::layouts>:
   The Bagisto admin layout component injects the global mega-search Vue
   component in the header (packages/Webkul/Admin/src/Resources/views/components/layouts/header/index.blade.php),
   which crashes on initial mount with:
     "TypeError: Cannot read properties of undefined (reading 'first_name')"
   at the inline x-template line: @{{ customer.first_name + ' ' + customer.last_name }}.

   The crash happens consistently — but only on /admin/fujiba-notifications.
   Other admin pages (Sagawa, Reorder, dashboard, etc.) using the same
   <x-admin::layouts> mount the mega-search cleanly. We could not isolate
   the specific markup in our blade that triggers it — multiple swap
   attempts (Stage 3 of the POS fix and a later Sagawa-pattern rewrite)
   both reproduced the crash. Captured via /_boost/browser-logs.

   This standalone layout sidesteps the problem entirely by skipping the
   admin Vue shell. Consequence: no Bagisto sidebar/header on this page,
   but the page reliably renders. The standalone editor is reached from
   the "Notifications" entry registered in config('menu.admin') — that
   sidebar entry shows up on every other admin page.

   If you want to retry the admin-shell integration in the future:
   1. Switch the index.blade.php and recipients.blade.php files from
      @extends('fujiba_notification::admin.layout') back to
      <x-admin::layouts> with <x-slot:title>...</x-slot>.
   2. Open Chrome DevTools → Sources → break at app-DJP0iOXV.js:15:29852
      (the ls.ee frame in the crash stack).
   3. Inspect what 'customer' refers to and where searchedResults.customers.data
      gets a malformed entry.
   4. Either patch our blade to remove the trigger, or add a global
      v-mega-search defensive null-check.
--}}
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '通知テンプレート') — Fujiba Notifications</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Noto Sans JP', sans-serif; background: #f5f5f7; color: #1d1d1f; font-size: 14px; min-height: 100dvh; }
        a { color: inherit; text-decoration: none; }
        button { font-family: inherit; }

        .top-bar { background: #fff; border-bottom: 1px solid #e5e5e5; padding: 14px 0; position: sticky; top: 0; z-index: 50; }
        .top-bar .inner { max-width: 1280px; margin: 0 auto; padding: 0 24px; display: flex; justify-content: space-between; align-items: center; }
        .top-bar h1 { font-size: 18px; font-weight: 600; }
        .top-bar .home-link { font-size: 13px; color: #0071e3; }

        .layout { display: grid; grid-template-columns: 240px 1fr; max-width: 1280px; margin: 0 auto; gap: 24px; padding: 24px; }
        @media (max-width: 800px) { .layout { grid-template-columns: 1fr; padding: 12px; gap: 12px; } }

        .sidebar { background: #fff; border-radius: 12px; padding: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.06); height: fit-content; position: sticky; top: 80px; }
        .sidebar h3 { font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #6b7280; margin: 8px 12px 6px; font-weight: 600; }
        .sidebar a { display: block; padding: 9px 12px; border-radius: 8px; font-size: 14px; color: #1d1d1f; }
        .sidebar a.active { background: #0071e3; color: #fff; font-weight: 500; }
        .sidebar a:not(.active):hover { background: #f3f4f6; }

        .main { background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,.06); min-height: 400px; }
        @media (max-width: 800px) { .main { padding: 16px; } }

        .tabs { display: flex; gap: 6px; border-bottom: 1px solid #e5e5e5; margin-bottom: 18px; flex-wrap: wrap; }
        .tabs a { padding: 9px 16px; font-size: 13px; color: #6b7280; border-bottom: 2px solid transparent; margin-bottom: -1px; }
        .tabs a.active { color: #0071e3; border-bottom-color: #0071e3; font-weight: 500; }
        .tabs a.disabled { color: #c7c7cc; cursor: not-allowed; pointer-events: none; }
        .tabs .badge-soon { font-size: 9px; background: #f3f4f6; color: #86868b; padding: 1px 6px; border-radius: 4px; margin-left: 4px; vertical-align: middle; }

        .lang-bar { display: flex; gap: 6px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; }
        .lang-chip { padding: 6px 14px; border-radius: 20px; background: #f3f4f6; font-size: 12px; font-weight: 500; }
        .lang-chip.active { background: #0071e3; color: #fff; }
        .lang-chip.has-template::before { content: '●'; color: #059669; font-size: 8px; margin-right: 4px; vertical-align: middle; }
        .lang-chip.active.has-template::before { color: #fff; }

        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px; color: #374151; text-transform: uppercase; letter-spacing: .3px; }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;
            font-family: inherit; background: #fff;
        }
        .form-group textarea { font-family: ui-monospace, 'SF Mono', Menlo, monospace; line-height: 1.6; resize: vertical; }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            outline: none; border-color: #0071e3; box-shadow: 0 0 0 3px rgba(0,113,227,.15);
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        @media (max-width: 600px) { .form-row { grid-template-columns: 1fr; } }

        .toggle { display: inline-flex; align-items: center; gap: 8px; cursor: pointer; }
        .toggle input { display: none; }
        .toggle-track { display: inline-block; width: 36px; height: 20px; background: #d1d5db; border-radius: 999px; position: relative; transition: background .15s; flex-shrink: 0; }
        .toggle-track::after { content: ''; position: absolute; top: 2px; left: 2px; width: 16px; height: 16px; background: #fff; border-radius: 50%; transition: transform .15s; }
        .toggle input:checked + .toggle-track { background: #059669; }
        .toggle input:checked + .toggle-track::after { transform: translateX(16px); }

        .chips { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 6px; }
        .chip {
            padding: 4px 10px; background: #eff6ff; color: #1e40af; border: 1px solid #dbeafe;
            border-radius: 6px; font-size: 12px; font-family: ui-monospace, 'SF Mono', Menlo, monospace;
            cursor: pointer; user-select: none;
        }
        .chip:hover { background: #dbeafe; }

        .actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 24px; padding-top: 20px; border-top: 1px solid #e5e5e5; }
        .btn { display: inline-flex; align-items: center; padding: 9px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; border: none; cursor: pointer; transition: opacity .15s; }
        .btn:disabled { opacity: .5; cursor: not-allowed; }
        .btn-primary { background: #0071e3; color: #fff; }
        .btn-primary:hover { background: #005bb5; }
        .btn-danger  { background: transparent; color: #dc2626; border: 1.5px solid #dc2626; }
        .btn-danger:hover { background: #fef2f2; }
        .btn-ghost   { background: transparent; color: #6b7280; }

        .alert { padding: 10px 14px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error   { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        .empty { text-align: center; padding: 48px 16px; color: #6b7280; }
        .empty p { margin-bottom: 16px; }

        .meta-bar { display: flex; gap: 12px; align-items: center; margin-bottom: 12px; font-size: 12px; color: #6b7280; }
        .meta-bar .pill { padding: 3px 8px; background: #f3f4f6; border-radius: 4px; font-family: ui-monospace, 'SF Mono', Menlo, monospace; }
    </style>
    @stack('styles')
</head>
<body>

<div class="top-bar">
    <div class="inner">
        <h1>@yield('title', '通知テンプレート設定')</h1>
        <a href="{{ url(config('app.admin_url', 'admin') . '/dashboard') }}" class="home-link">← Bagisto Admin</a>
    </div>
</div>

@yield('content')

@stack('scripts')
</body>
</html>
