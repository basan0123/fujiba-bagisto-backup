{{-- Auto Poster design layer.

     Plain, scoped CSS (every rule lives under .ap) instead of Tailwind
     utilities: the admin Tailwind bundle is prebuilt, so new utility classes
     would be purged. Pushed once into the layout's <head> via the styles stack
     — a <style> tag inside the Vue-mounted #app would be stripped. --}}
@pushOnce('styles')
<style>
.ap {
    --ap-bg: #f5f7fb;
    --ap-surface: #ffffff;
    --ap-surface-2: #f8fafc;
    --ap-border: #e5e9f2;
    --ap-text: #0f172a;
    --ap-muted: #64748b;
    --ap-faint: #94a3b8;
    --ap-primary: #1877f2;
    --ap-primary-600: #1466d8;
    --ap-primary-soft: #e7f0fe;
    --ap-success: #16a34a;
    --ap-success-soft: #dcfce7;
    --ap-warn: #d97706;
    --ap-warn-soft: #fef3c7;
    --ap-danger: #dc2626;
    --ap-danger-soft: #fee2e2;
    --ap-violet: #7c3aed;
    --ap-violet-soft: #ede9fe;
    --ap-radius: 14px;
    --ap-shadow: 0 1px 2px rgba(15, 23, 42, .04), 0 4px 16px rgba(15, 23, 42, .05);
    --ap-shadow-lg: 0 10px 30px rgba(15, 23, 42, .12);
    --ap-ease: cubic-bezier(.2, .8, .2, 1);

    color: var(--ap-text);
    font-size: 14px;
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.dark .ap {
    --ap-bg: #0b1220;
    --ap-surface: #111a2e;
    --ap-surface-2: #0f1729;
    --ap-border: #1f2a44;
    --ap-text: #e5e9f2;
    --ap-muted: #94a3b8;
    --ap-faint: #64748b;
    --ap-primary-soft: rgba(24, 119, 242, .16);
    --ap-success-soft: rgba(22, 163, 74, .16);
    --ap-warn-soft: rgba(217, 119, 6, .16);
    --ap-danger-soft: rgba(220, 38, 38, .16);
    --ap-violet-soft: rgba(124, 58, 237, .18);
    --ap-shadow: 0 1px 2px rgba(0, 0, 0, .3);
    --ap-shadow-lg: 0 10px 30px rgba(0, 0, 0, .45);
}

/* ── Motion ─────────────────────────────────────────────────── */
@keyframes ap-rise { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
@keyframes ap-pop  { 0% { transform: scale(.6); opacity: 0; } 70% { transform: scale(1.12); } 100% { transform: scale(1); opacity: 1; } }
@keyframes ap-spin { to { transform: rotate(360deg); } }
@keyframes ap-pulse { 0%, 100% { box-shadow: 0 0 0 0 rgba(22, 163, 74, .45); } 50% { box-shadow: 0 0 0 6px rgba(22, 163, 74, 0); } }
@keyframes ap-shimmer { from { background-position: -200% 0; } to { background-position: 200% 0; } }

.ap-rise { animation: ap-rise .45s var(--ap-ease) both; }
.ap-rise:nth-child(2) { animation-delay: .04s; }
.ap-rise:nth-child(3) { animation-delay: .08s; }
.ap-rise:nth-child(4) { animation-delay: .12s; }
.ap-rise:nth-child(5) { animation-delay: .16s; }
.ap-rise:nth-child(6) { animation-delay: .2s; }

@media (prefers-reduced-motion: reduce) {
    .ap *, .ap *::before, .ap *::after { animation: none !important; transition: none !important; }
}

/* ── Header ─────────────────────────────────────────────────── */
.ap-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.ap-header-main { display: flex; align-items: center; gap: 12px; }
.ap-logo {
    width: 42px; height: 42px; border-radius: 12px; flex-shrink: 0;
    display: grid; place-items: center; color: #fff;
    background: linear-gradient(135deg, #1877f2, #5b9dff);
    box-shadow: 0 6px 16px rgba(24, 119, 242, .35);
}
.ap-logo svg { width: 22px; height: 22px; }
.ap-title { font-size: 22px; font-weight: 700; letter-spacing: -.01em; line-height: 1.2; margin: 0; }
.ap-subtitle { color: var(--ap-muted); font-size: 13px; margin-top: 2px; }

/* ── Tabs ───────────────────────────────────────────────────── */
.ap-navbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.ap-tabs {
    display: flex; gap: 4px; padding: 4px; overflow-x: auto; max-width: 100%;
    background: var(--ap-surface); border: 1px solid var(--ap-border);
    border-radius: 12px; box-shadow: var(--ap-shadow); scrollbar-width: none;
}
.ap-tabs::-webkit-scrollbar { display: none; }
.ap-tab {
    display: inline-flex; align-items: center; gap: 7px; white-space: nowrap;
    padding: 8px 14px; border-radius: 9px; font-weight: 500; font-size: 13.5px;
    color: var(--ap-muted); transition: background .2s, color .2s;
}
.ap-tab svg { width: 16px; height: 16px; opacity: .8; }
.ap-tab:hover { background: var(--ap-surface-2); color: var(--ap-text); }
.ap-tab.is-active { background: var(--ap-primary); color: #fff; box-shadow: 0 4px 12px rgba(24, 119, 242, .3); }
.ap-tab.is-active svg { opacity: 1; }

/* ── Cards ──────────────────────────────────────────────────── */
.ap-card {
    background: var(--ap-surface); border: 1px solid var(--ap-border);
    border-radius: var(--ap-radius); box-shadow: var(--ap-shadow); padding: 20px;
}
.ap-card-flush { padding: 0; overflow: hidden; }
.ap-card-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 14px; flex-wrap: wrap; }
.ap-card-title { font-size: 15px; font-weight: 650; display: flex; align-items: center; gap: 8px; margin: 0; }
.ap-card-title svg { width: 18px; height: 18px; color: var(--ap-primary); }
.ap-card-desc { color: var(--ap-muted); font-size: 12.5px; margin-top: -8px; margin-bottom: 14px; line-height: 1.55; }
.ap-grid { display: grid; gap: 16px; }
.ap-grid-2 { grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); }
.ap-grid-4 { grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); }

/* ── Stat tiles ─────────────────────────────────────────────── */
.ap-stat { display: flex; align-items: center; gap: 14px; padding: 16px 18px; }
.ap-stat-icon { width: 42px; height: 42px; border-radius: 12px; display: grid; place-items: center; flex-shrink: 0; }
.ap-stat-icon svg { width: 20px; height: 20px; }
.ap-stat-label { color: var(--ap-muted); font-size: 12.5px; font-weight: 500; }
.ap-stat-value { font-size: 24px; font-weight: 700; line-height: 1.15; letter-spacing: -.02em; }
.ap-tone-blue   { background: var(--ap-primary-soft); color: var(--ap-primary); }
.ap-tone-green  { background: var(--ap-success-soft); color: var(--ap-success); }
.ap-tone-amber  { background: var(--ap-warn-soft);    color: var(--ap-warn); }
.ap-tone-red    { background: var(--ap-danger-soft);  color: var(--ap-danger); }
.ap-tone-violet { background: var(--ap-violet-soft);  color: var(--ap-violet); }
.ap-tone-gray   { background: var(--ap-surface-2);    color: var(--ap-muted); }

/* ── Buttons ────────────────────────────────────────────────── */
.ap-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    height: 40px; padding: 0 18px; border-radius: 10px; border: 1px solid transparent;
    font-size: 14px; font-weight: 600; line-height: 1; white-space: nowrap; cursor: pointer;
    transition: transform .15s var(--ap-ease), box-shadow .2s, background .2s, border-color .2s, color .2s, opacity .2s;
    user-select: none; text-decoration: none;
}
.ap-btn svg { width: 17px; height: 17px; flex-shrink: 0; }
.ap-btn:hover { transform: translateY(-1px); }
.ap-btn:active { transform: translateY(0) scale(.98); }
.ap-btn:focus-visible { outline: none; box-shadow: 0 0 0 3px rgba(24, 119, 242, .35); }
.ap-btn[disabled], .ap-btn.is-loading { opacity: .65; cursor: not-allowed; transform: none; }
.ap-btn-sm { height: 32px; padding: 0 12px; font-size: 13px; border-radius: 8px; }
.ap-btn-sm svg { width: 15px; height: 15px; }
.ap-btn-lg { height: 46px; padding: 0 24px; font-size: 15px; border-radius: 12px; }
.ap-btn-block { width: 100%; }

.ap-btn-primary { background: var(--ap-primary); color: #fff; box-shadow: 0 4px 14px rgba(24, 119, 242, .3); }
.ap-btn-primary:hover { background: var(--ap-primary-600); box-shadow: 0 8px 20px rgba(24, 119, 242, .35); }
.ap-btn-success { background: var(--ap-success); color: #fff; box-shadow: 0 4px 14px rgba(22, 163, 74, .3); }
.ap-btn-success:hover { background: #15803d; }
.ap-btn-violet { background: var(--ap-violet); color: #fff; box-shadow: 0 4px 14px rgba(124, 58, 237, .28); }
.ap-btn-violet:hover { background: #6d28d9; }
.ap-btn-warn { background: var(--ap-warn); color: #fff; }
.ap-btn-secondary { background: var(--ap-surface); color: var(--ap-text); border-color: var(--ap-border); box-shadow: var(--ap-shadow); }
.ap-btn-secondary:hover { border-color: var(--ap-faint); }
.ap-btn-ghost { background: transparent; color: var(--ap-muted); }
.ap-btn-ghost:hover { background: var(--ap-surface-2); color: var(--ap-text); transform: none; }
.ap-btn-danger { background: var(--ap-surface); color: var(--ap-danger); border-color: var(--ap-danger-soft); }
.ap-btn-danger:hover { background: var(--ap-danger-soft); }

.ap-btn.is-loading::before {
    content: ""; width: 15px; height: 15px; border-radius: 50%;
    border: 2px solid currentColor; border-right-color: transparent;
    animation: ap-spin .7s linear infinite;
}
.ap-btn.is-loading svg { display: none; }

.ap-link { color: var(--ap-primary); font-weight: 500; }
.ap-link:hover { text-decoration: underline; }
.ap-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }

/* ── Badges ─────────────────────────────────────────────────── */
.ap-badge {
    display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px;
    border-radius: 999px; font-size: 12px; font-weight: 600; line-height: 1.5;
    text-transform: capitalize; white-space: nowrap; vertical-align: middle;
}
.ap-badge::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
.ap-badge.no-dot::before { display: none; }
.ap-badge-processing::before { animation: ap-pulse 1.4s infinite; }

/* ── Alerts / flash ─────────────────────────────────────────── */
.ap-alert {
    display: flex; gap: 12px; align-items: flex-start; padding: 14px 16px;
    border-radius: 12px; border: 1px solid transparent; font-size: 13.5px; line-height: 1.55;
    animation: ap-rise .35s var(--ap-ease) both;
}
.ap-alert svg { width: 19px; height: 19px; flex-shrink: 0; margin-top: 1px; }
.ap-alert-body { flex: 1; min-width: 0; }
.ap-alert-close { background: none; border: 0; cursor: pointer; color: inherit; opacity: .55; font-size: 18px; line-height: 1; padding: 0 2px; }
.ap-alert-close:hover { opacity: 1; }
.ap-alert.is-leaving { opacity: 0; transform: translateY(-6px); transition: all .3s; }
.ap-alert-success { background: var(--ap-success-soft); color: #166534; border-color: rgba(22, 163, 74, .25); }
.ap-alert-warn    { background: var(--ap-warn-soft);    color: #92400e; border-color: rgba(217, 119, 6, .25); }
.ap-alert-danger  { background: var(--ap-danger-soft);  color: #991b1b; border-color: rgba(220, 38, 38, .25); }
.ap-alert-info    { background: var(--ap-primary-soft); color: #1e40af; border-color: rgba(24, 119, 242, .22); }
.dark .ap .ap-alert-success { color: #86efac; }
.dark .ap .ap-alert-warn    { color: #fcd34d; }
.dark .ap .ap-alert-danger  { color: #fca5a5; }
.dark .ap .ap-alert-info    { color: #93c5fd; }
.ap-alert code, .ap-card code { font-size: 12px; padding: 1px 6px; border-radius: 6px; background: rgba(100, 116, 139, .14); }

/* ── Forms ──────────────────────────────────────────────────── */
.ap-field { display: flex; flex-direction: column; gap: 6px; font-size: 13px; }
.ap-label { font-weight: 600; font-size: 13px; color: var(--ap-text); }
.ap-hint { color: var(--ap-muted); font-size: 12px; line-height: 1.5; }
.ap input[type=text], .ap input[type=url], .ap input[type=number], .ap input[type=date],
.ap input[type=time], .ap input[type=datetime-local], .ap input[type=email], .ap input[type=search],
.ap select, .ap textarea {
    width: 100%; min-height: 40px; padding: 8px 12px; border-radius: 10px;
    border: 1px solid var(--ap-border); background: var(--ap-surface); color: var(--ap-text);
    font-size: 14px; transition: border-color .2s, box-shadow .2s; box-shadow: none;
}
.ap textarea { line-height: 1.6; resize: vertical; }
.ap input:focus, .ap select:focus, .ap textarea:focus {
    outline: none; border-color: var(--ap-primary); box-shadow: 0 0 0 3px rgba(24, 119, 242, .15);
}
.ap input:disabled, .ap select:disabled, .ap textarea:disabled { background: var(--ap-surface-2); color: var(--ap-muted); cursor: not-allowed; }
.ap input[type=checkbox] { width: 17px; height: 17px; border-radius: 5px; accent-color: var(--ap-primary); cursor: pointer; }
.ap-check {
    display: inline-flex; align-items: center; gap: 9px; padding: 9px 13px; cursor: pointer;
    border: 1px solid var(--ap-border); border-radius: 10px; background: var(--ap-surface);
    font-size: 13px; font-weight: 500; transition: border-color .2s, background .2s;
}
.ap-check:hover { border-color: var(--ap-faint); }
.ap-check:has(input:checked) { border-color: var(--ap-primary); background: var(--ap-primary-soft); }

/* ── Switch ─────────────────────────────────────────────────── */
.ap-switch {
    position: relative; display: inline-flex; align-items: center; gap: 12px;
    padding: 6px 16px 6px 6px; border-radius: 999px; border: 1px solid var(--ap-border);
    background: var(--ap-surface); cursor: pointer; font-weight: 600; font-size: 14px;
    transition: border-color .2s, background .2s; color: var(--ap-text);
}
.ap-switch-track { width: 46px; height: 26px; border-radius: 999px; background: #cbd5e1; position: relative; transition: background .25s; }
.ap-switch-track::after {
    content: ""; position: absolute; top: 3px; left: 3px; width: 20px; height: 20px; border-radius: 50%;
    background: #fff; box-shadow: 0 1px 3px rgba(0, 0, 0, .25); transition: transform .25s var(--ap-ease);
}
.ap-switch.is-on { border-color: rgba(22, 163, 74, .35); background: var(--ap-success-soft); }
.ap-switch.is-on .ap-switch-track { background: var(--ap-success); }
.ap-switch.is-on .ap-switch-track::after { transform: translateX(20px); }

/* ── Tables ─────────────────────────────────────────────────── */
.ap-table-wrap { overflow-x: auto; }
.ap-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
.ap-table th {
    text-align: left; padding: 12px 16px; font-size: 11.5px; font-weight: 600; text-transform: uppercase;
    letter-spacing: .04em; color: var(--ap-muted); background: var(--ap-surface-2); border-bottom: 1px solid var(--ap-border);
}
.ap-table td { padding: 14px 16px; border-bottom: 1px solid var(--ap-border); vertical-align: middle; }
.ap-table tbody tr { transition: background .15s; }
.ap-table tbody tr:hover { background: var(--ap-surface-2); }
.ap-table tbody tr:last-child td { border-bottom: 0; }
.ap-thumbs { display: flex; }
.ap-thumbs > * {
    width: 42px; height: 42px; border-radius: 10px; object-fit: cover; flex-shrink: 0;
    border: 2px solid var(--ap-surface); margin-left: -10px; background: var(--ap-surface-2);
    display: grid; place-items: center; font-size: 11.5px; font-weight: 600; color: var(--ap-muted);
    box-shadow: 0 1px 3px rgba(0, 0, 0, .08);
}
.ap-thumbs > *:first-child { margin-left: 0; }
.ap-clamp { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; color: var(--ap-muted); font-size: 13px; line-height: 1.5; max-width: 360px; }
.ap-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12px; }
.ap-muted { color: var(--ap-muted); }
.ap-small { font-size: 12px; }

/* ── Filter pills ───────────────────────────────────────────── */
.ap-pills { display: flex; gap: 8px; flex-wrap: wrap; }
.ap-pill {
    display: inline-flex; align-items: center; gap: 8px; padding: 7px 14px; border-radius: 999px;
    border: 1px solid var(--ap-border); background: var(--ap-surface); font-size: 13px; font-weight: 500;
    color: var(--ap-muted); text-transform: capitalize; transition: all .2s;
}
.ap-pill:hover { color: var(--ap-text); border-color: var(--ap-faint); }
.ap-pill.is-active { background: var(--ap-text); border-color: var(--ap-text); color: var(--ap-surface); }
.ap-pill-count { font-size: 11.5px; padding: 1px 7px; border-radius: 999px; background: var(--ap-surface-2); color: var(--ap-muted); }
.ap-pill.is-active .ap-pill-count { background: rgba(255, 255, 255, .18); color: inherit; }

/* ── Status strip (dashboard queue) ─────────────────────────── */
.ap-status-strip { display: grid; gap: 10px; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); }
.ap-status-cell {
    padding: 14px; border-radius: 12px; border: 1px solid var(--ap-border); background: var(--ap-surface-2);
    transition: transform .2s var(--ap-ease), box-shadow .2s, border-color .2s;
}
.ap-status-cell:hover { transform: translateY(-2px); box-shadow: var(--ap-shadow-lg); border-color: transparent; }
.ap-status-cell b { display: block; font-size: 22px; font-weight: 700; margin-top: 6px; color: var(--ap-text); }

/* ── Readiness hero ─────────────────────────────────────────── */
.ap-hero { position: relative; overflow: hidden; }
.ap-hero::after {
    content: ""; position: absolute; inset: 0 0 auto 0; height: 4px;
    background: var(--ap-hero-accent, var(--ap-border));
}
.ap-hero.is-ready    { --ap-hero-accent: linear-gradient(90deg, #16a34a, #4ade80); }
.ap-hero.is-blocked  { --ap-hero-accent: linear-gradient(90deg, #dc2626, #f97316); }
.ap-hero-row { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
.ap-hero-state { display: flex; align-items: center; gap: 14px; }
.ap-hero-icon { width: 48px; height: 48px; border-radius: 14px; display: grid; place-items: center; }
.ap-hero-icon svg { width: 24px; height: 24px; }
.ap-hero-title { font-size: 17px; font-weight: 700; }
.ap-checks { display: grid; gap: 10px; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); margin-top: 18px; }
.ap-check-item { display: flex; gap: 10px; padding: 12px; border-radius: 10px; background: var(--ap-surface-2); border: 1px solid var(--ap-border); }
.ap-check-dot { width: 22px; height: 22px; border-radius: 50%; flex-shrink: 0; display: grid; place-items: center; }
.ap-check-dot svg { width: 13px; height: 13px; }
.ap-check-item b { font-weight: 600; font-size: 13px; }
.ap-check-item p { margin: 2px 0 0; color: var(--ap-muted); font-size: 12px; line-height: 1.5; }
.ap-check-item .ap-fix { color: var(--ap-primary); }

/* ── Definition list ────────────────────────────────────────── */
.ap-dl { display: grid; grid-template-columns: minmax(96px, auto) 1fr; gap: 0; margin: 0; }
.ap-dl dt, .ap-dl dd { padding: 10px 0; border-bottom: 1px dashed var(--ap-border); margin: 0; font-size: 13.5px; }
.ap-dl dt { color: var(--ap-muted); padding-right: 16px; }
.ap-dl dd { word-break: break-word; font-weight: 500; }
.ap-dl dt:nth-last-of-type(1), .ap-dl dd:last-child { border-bottom: 0; }

/* ── Empty state ────────────────────────────────────────────── */
.ap-empty { text-align: center; padding: 48px 20px; color: var(--ap-muted); }
.ap-empty-icon { width: 56px; height: 56px; margin: 0 auto 14px; border-radius: 16px; display: grid; place-items: center; }
.ap-empty-icon svg { width: 26px; height: 26px; }
.ap-empty h3 { font-size: 16px; font-weight: 650; color: var(--ap-text); margin: 0 0 4px; }

/* ── Product picker ─────────────────────────────────────────── */
.ap-picker-bar {
    position: sticky; top: 62px; z-index: 5; display: flex; align-items: center; justify-content: space-between;
    gap: 10px; flex-wrap: wrap; padding: 10px 12px; margin: -4px -4px 14px; border-radius: 12px;
    background: var(--ap-surface); border: 1px solid var(--ap-border); box-shadow: var(--ap-shadow);
}
.ap-picker-grid { display: grid; gap: 14px; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); }
.ap-pcard {
    position: relative; border-radius: 12px; overflow: hidden; cursor: pointer; background: var(--ap-surface);
    border: 1px solid var(--ap-border); transition: transform .2s var(--ap-ease), box-shadow .2s, border-color .2s;
}
.ap-pcard:hover { transform: translateY(-3px); box-shadow: var(--ap-shadow-lg); }
.ap-pcard.is-selected { border-color: var(--ap-primary); box-shadow: 0 0 0 3px rgba(24, 119, 242, .25); }
.ap-pcard-img { aspect-ratio: 1; width: 100%; object-fit: cover; display: block; background: var(--ap-surface-2); transition: transform .35s var(--ap-ease); }
.ap-pcard:hover .ap-pcard-img { transform: scale(1.04); }
.ap-pcard-noimg { aspect-ratio: 1; display: grid; place-items: center; color: var(--ap-faint); background: var(--ap-surface-2); font-size: 12px; }
.ap-pcard-body { padding: 9px 11px 11px; }
.ap-pcard-name { font-size: 12.5px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ap-pcard-price { font-size: 13px; font-weight: 700; margin-top: 2px; }
.ap-pcard-num {
    position: absolute; top: 8px; left: 8px; z-index: 2; width: 26px; height: 26px; border-radius: 50%;
    background: var(--ap-primary); color: #fff; font-size: 12px; font-weight: 700;
    display: grid; place-items: center; box-shadow: 0 3px 10px rgba(24, 119, 242, .45); animation: ap-pop .3s var(--ap-ease);
}
.ap-pcard-check {
    position: absolute; top: 8px; right: 8px; z-index: 2; width: 24px; height: 24px; border-radius: 50%;
    background: rgba(255, 255, 255, .92); border: 1.5px solid rgba(15, 23, 42, .15); transition: all .2s;
}
.ap-pcard.is-selected .ap-pcard-check { background: var(--ap-primary); border-color: var(--ap-primary); }
.ap-pcard.is-selected .ap-pcard-check::after {
    content: ""; position: absolute; left: 7.5px; top: 4px; width: 6px; height: 11px;
    border: solid #fff; border-width: 0 2px 2px 0; transform: rotate(45deg);
}

/* ── Facebook post preview ──────────────────────────────────── */
.ap-fb {
    max-width: 500px; margin: 0 auto; width: 100%; background: #fff; color: #050505;
    border-radius: 12px; box-shadow: 0 1px 2px rgba(0, 0, 0, .1), 0 8px 28px rgba(0, 0, 0, .08);
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif; overflow: hidden;
}
.dark .ap .ap-fb { background: #242526; color: #e4e6eb; }
.ap-fb-head { display: flex; align-items: center; gap: 10px; padding: 12px 16px 0; }
.ap-fb-avatar {
    width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0; object-fit: cover;
    background: linear-gradient(135deg, #1877f2, #5b9dff); color: #fff; display: grid; place-items: center; font-weight: 700;
}
.ap-fb-name { font-weight: 600; font-size: 15px; line-height: 1.2; }
.ap-fb-meta { font-size: 13px; color: #65676b; display: flex; align-items: center; gap: 4px; }
.dark .ap .ap-fb-meta { color: #b0b3b8; }
.ap-fb-dots { margin-left: auto; color: #65676b; font-size: 20px; letter-spacing: 1px; }
.ap-fb-text { padding: 10px 16px 12px; font-size: 15px; line-height: 1.34; white-space: pre-wrap; word-break: break-word; }
.ap-fb-text.is-collapsed { max-height: 8.1em; overflow: hidden; position: relative; }
.ap-fb-more { background: none; border: 0; padding: 0 16px 10px; font-weight: 600; color: #65676b; cursor: pointer; font-size: 15px; }
.ap-fb-text a, .ap-fb-link { color: #0064d1; }
.dark .ap .ap-fb-text a { color: #4599ff; }
.ap-fb-empty { color: #8a8d91; font-style: italic; }
.ap-fb-carousel { display: flex; gap: 8px; overflow-x: auto; padding: 0 16px 12px; scroll-snap-type: x mandatory; scrollbar-width: thin; }
.ap-fb-card {
    flex: 0 0 72%; max-width: 300px; border-radius: 10px; overflow: hidden; border: 1px solid #dadde1;
    scroll-snap-align: start; background: #fff; animation: ap-rise .35s var(--ap-ease) both;
}
.dark .ap .ap-fb-card { border-color: #3e4042; background: #3a3b3c; }
.ap-fb-card img, .ap-fb-card .ap-fb-noimg { width: 100%; aspect-ratio: 1; object-fit: cover; display: block; background: #f0f2f5; }
.ap-fb-noimg { display: grid !important; place-items: center; color: #8a8d91; font-size: 13px; }
.ap-fb-card-body { display: flex; align-items: center; gap: 8px; padding: 10px 12px; background: #f0f2f5; }
.dark .ap .ap-fb-card-body { background: #3a3b3c; }
.ap-fb-card-title { font-weight: 600; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ap-fb-card-sub { font-size: 12.5px; color: #65676b; }
.dark .ap .ap-fb-card-sub { color: #b0b3b8; }
.ap-fb-cta { margin-left: auto; flex-shrink: 0; padding: 7px 12px; border-radius: 6px; background: #e4e6eb; font-weight: 600; font-size: 13.5px; color: #050505; }
.dark .ap .ap-fb-cta { background: #4e4f50; color: #e4e6eb; }
.ap-fb-single img, .ap-fb-single .ap-fb-noimg { width: 100%; aspect-ratio: 1.91 / 1; object-fit: cover; display: block; }
.ap-fb-single .ap-fb-card-body { padding: 10px 16px; }
.ap-fb-stats { display: flex; justify-content: space-between; padding: 8px 16px; font-size: 14px; color: #65676b; }
.ap-fb-react { display: inline-flex; align-items: center; gap: 6px; }
.ap-fb-react i { width: 18px; height: 18px; border-radius: 50%; display: inline-grid; place-items: center; font-style: normal; font-size: 10px; color: #fff; }
.ap-fb-bar { display: flex; border-top: 1px solid #ced0d4; margin: 0 16px; padding: 4px 0; }
.dark .ap .ap-fb-bar { border-color: #3e4042; }
.ap-fb-bar span { flex: 1; text-align: center; padding: 7px 0; font-weight: 600; font-size: 14.5px; color: #65676b; border-radius: 6px; display: inline-flex; justify-content: center; align-items: center; gap: 6px; }
.ap-fb-bar svg { width: 18px; height: 18px; }
.ap-fb-bar span:hover { background: #f2f2f2; }
.dark .ap .ap-fb-bar span:hover { background: #3a3b3c; }
.ap-device-toggle { display: inline-flex; padding: 3px; border-radius: 10px; background: var(--ap-surface-2); border: 1px solid var(--ap-border); }
.ap-device-toggle button { border: 0; background: none; padding: 6px 12px; border-radius: 7px; font-size: 12.5px; font-weight: 600; color: var(--ap-muted); cursor: pointer; transition: all .2s; }
.ap-device-toggle button.is-active { background: var(--ap-surface); color: var(--ap-text); box-shadow: var(--ap-shadow); }
.ap-preview-stage { padding: 22px 16px; border-radius: 12px; background: #f0f2f5; transition: all .3s var(--ap-ease); }
.dark .ap .ap-preview-stage { background: #18191a; }
.ap-preview-stage.is-mobile .ap-fb { max-width: 375px; }

/* ── Reorder strip ──────────────────────────────────────────── */
.ap-reorder { display: flex; gap: 10px; overflow-x: auto; padding: 2px 2px 8px; }
.ap-reorder-item {
    position: relative; flex: 0 0 104px; border-radius: 10px; overflow: hidden; background: var(--ap-surface);
    border: 1px solid var(--ap-border); transition: transform .2s var(--ap-ease), box-shadow .2s, opacity .2s;
}
.ap-reorder-item.is-draggable { cursor: grab; }
.ap-reorder-item.is-draggable:hover { transform: translateY(-2px); box-shadow: var(--ap-shadow-lg); }
.ap-reorder-item.is-dragging { opacity: .35; transform: scale(.96); }
.ap-reorder-item img, .ap-reorder-item .ap-pcard-noimg { width: 100%; aspect-ratio: 1; object-fit: cover; display: block; }
.ap-reorder-item .ap-pcard-num { width: 22px; height: 22px; font-size: 11px; top: 6px; left: 6px; animation: none; }
.ap-reorder-name { font-size: 11.5px; padding: 6px 8px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ap-reorder-x {
    position: absolute; top: 6px; right: 6px; width: 22px; height: 22px; border-radius: 50%; border: 0; cursor: pointer;
    background: rgba(15, 23, 42, .7); color: #fff; font-size: 13px; line-height: 1; display: grid; place-items: center;
    opacity: 0; transition: opacity .2s, background .2s;
}
.ap-reorder-item:hover .ap-reorder-x { opacity: 1; }
.ap-reorder-x:hover { background: var(--ap-danger); }
.ap-save-state { font-size: 12px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; }

/* ── Sticky action bar ──────────────────────────────────────── */
.ap-actionbar {
    position: sticky; bottom: 12px; z-index: 6; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
    padding: 12px 14px; border-radius: 14px; background: color-mix(in srgb, var(--ap-surface) 88%, transparent);
    backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
    border: 1px solid var(--ap-border); box-shadow: var(--ap-shadow-lg);
}
.ap-spacer { flex: 1; }
.ap-counter { font-size: 12px; color: var(--ap-muted); text-align: right; }
.ap-counter.is-over { color: var(--ap-danger); font-weight: 600; }

.ap-split { grid-template-columns: minmax(0, 1fr) minmax(320px, 420px); align-items: start; }
.ap-split > * { min-width: 0; }
.ap-split-edit { grid-template-columns: minmax(0, 1.05fr) minmax(0, 1fr); align-items: start; }
@media (max-width: 1000px) { .ap-split-edit { grid-template-columns: 1fr; } }
.ap-sticky { position: sticky; top: 76px; }
.ap-grid > * { min-width: 0; }
@media (max-width: 1100px) {
    .ap-split { grid-template-columns: 1fr; }
    .ap-sticky { position: static; }
}

@media (max-width: 640px) {
    .ap-card { padding: 16px; }
    .ap-title { font-size: 19px; }
    .ap-grid-2 { grid-template-columns: 1fr; }
    .ap-actionbar .ap-btn { flex: 1; }
    .ap-picker-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>
@endPushOnce

@pushOnce('scripts')
<script>
    /* Auto Poster UX helpers: button loading state on submit (guards against
       double-posting to Facebook) and auto-dismiss for success flashes. */
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (! form.closest || ! form.closest('.ap') || e.defaultPrevented) return;
        var btn = e.submitter && e.submitter.classList.contains('ap-btn') ? e.submitter : form.querySelector('button.ap-btn[type=submit], button.ap-btn:not([type])');
        if (btn) {
            setTimeout(function () { btn.classList.add('is-loading'); btn.setAttribute('aria-busy', 'true'); }, 0);
        }
    }, false);

    document.addEventListener('click', function (e) {
        var close = e.target.closest && e.target.closest('.ap-alert-close');
        if (! close) return;
        var box = close.closest('.ap-alert');
        box.classList.add('is-leaving');
        setTimeout(function () { box.remove(); }, 300);
    });

    window.addEventListener('load', function () {
        document.querySelectorAll('.ap-alert[data-autohide]').forEach(function (box) {
            setTimeout(function () { box.classList.add('is-leaving'); setTimeout(function () { box.remove(); }, 300); }, 6000);
        });
    });
</script>
@endPushOnce
