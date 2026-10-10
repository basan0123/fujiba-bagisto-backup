<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="投稿予定">
    <meta name="theme-color" content="#141b33">
    <title>Fujiba Posts — 投稿予定</title>
    <style>
        :root { --ai:#141b33; --gold:#d9b866; --bg:#f2f3f7; --card:#fff; --ink:#141b33; --muted:#646a82; --line:#e1e3ec; --ok:#2f6b4f; --bad:#b0392b; }
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        html, body { margin: 0; background: var(--bg); color: var(--ink); font: 15px/1.5 -apple-system, "Hiragino Sans", "Noto Sans JP", system-ui, sans-serif; }
        header { position: sticky; top: 0; z-index: 5; background: var(--ai); color: #fff; padding: calc(env(safe-area-inset-top, 0px) + 10px) 16px 0; }
        .bar { display: flex; align-items: center; gap: 10px; }
        .bar h1 { font-size: 18px; margin: 0; flex: 1; }
        .switch { display: flex; align-items: center; gap: 8px; font-size: 13px; background: rgba(255,255,255,.1); border-radius: 999px; padding: 4px 6px 4px 12px; border: 0; color: #fff; font: inherit; }
        .knob { width: 40px; height: 24px; border-radius: 999px; background: #646a82; position: relative; transition: background .2s; }
        .knob::after { content: ""; position: absolute; top: 3px; left: 3px; width: 18px; height: 18px; border-radius: 50%; background: #fff; transition: left .2s; }
        .switch.on .knob { background: var(--ok); } .switch.on .knob::after { left: 19px; }
        .days { display: flex; gap: 6px; overflow-x: auto; padding: 12px 0 12px; scrollbar-width: none; }
        .days::-webkit-scrollbar { display: none; }
        .day { flex: 0 0 auto; border: 1px solid rgba(255,255,255,.25); background: transparent; color: #fff; border-radius: 10px; padding: 6px 10px; text-align: center; font: inherit; min-width: 58px; }
        .day b { display: block; font-size: 17px; line-height: 1.1; }
        .day span { font-size: 11px; opacity: .8; }
        .day.active { background: var(--gold); border-color: var(--gold); color: var(--ai); }
        main { padding: 12px 12px calc(env(safe-area-inset-bottom, 0px) + 24px); max-width: 640px; margin: 0 auto; }
        .post { display: flex; gap: 12px; background: var(--card); border-radius: 12px; padding: 10px; margin-bottom: 10px; border: 1px solid var(--line); width: 100%; text-align: left; font: inherit; color: inherit; }
        .post img { width: 84px; height: 84px; border-radius: 8px; object-fit: cover; flex: 0 0 84px; background: #dde; }
        .post .t { font-size: 20px; font-weight: 700; font-variant-numeric: tabular-nums; }
        .post .p { font-size: 13px; color: var(--muted); margin-top: 2px; word-break: break-word; }
        .post .c { font-size: 12px; color: var(--muted); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-top: 4px; }
        .pill { display: inline-block; font-size: 11px; padding: 1px 8px; border-radius: 999px; margin-left: 6px; vertical-align: middle; font-weight: 600; }
        .s-scheduled { background: #e8ecfb; color: #3047a8; } .s-published { background: #e5f3ec; color: var(--ok); }
        .s-failed { background: #f8e3e0; color: var(--bad); } .s-processing { background: #fff3d6; color: #8a5a00; } .s-draft { background: #eee; color: #555; }
        .empty { text-align: center; color: var(--muted); padding: 40px 0; }
        .sheet-bg { position: fixed; inset: 0; background: rgba(10,14,30,.55); z-index: 10; }
        .sheet { position: fixed; left: 0; right: 0; bottom: 0; z-index: 11; background: var(--card); border-radius: 16px 16px 0 0; max-height: 92vh; overflow-y: auto; padding: 8px 16px calc(env(safe-area-inset-bottom, 0px) + 16px); max-width: 640px; margin: 0 auto; }
        .grab { width: 40px; height: 4px; border-radius: 4px; background: #ccd; margin: 4px auto 10px; }
        .sheet .cover { width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 10px; background: #dde; }
        .thumbs { display: flex; gap: 6px; margin: 8px 0; overflow-x: auto; }
        .thumbs img { width: 64px; height: 64px; border-radius: 6px; object-fit: cover; flex: 0 0 64px; }
        label.f { display: block; font-size: 12px; color: var(--muted); margin: 10px 0 4px; }
        textarea, input[type=datetime-local] { width: 100%; border: 1px solid var(--line); border-radius: 8px; padding: 10px; font: inherit; font-size: 15px; background: #fff; color: var(--ink); }
        textarea { min-height: 220px; resize: vertical; }
        .acts { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 12px; }
        .btn { border: 0; border-radius: 10px; padding: 13px 10px; font: inherit; font-weight: 600; font-size: 15px; background: #eef0f6; color: var(--ink); }
        .btn.primary { background: var(--ai); color: #fff; grid-column: 1 / -1; }
        .btn.go { background: var(--gold); color: var(--ai); }
        .btn.danger { background: #f8e3e0; color: var(--bad); }
        .btn:disabled { opacity: .5; }
        .err { background: #f8e3e0; color: var(--bad); border-radius: 8px; padding: 8px 10px; font-size: 13px; margin-top: 8px; }
        .toast { position: fixed; left: 50%; bottom: calc(env(safe-area-inset-bottom, 0px) + 24px); transform: translateX(-50%); background: var(--ai); color: #fff; padding: 10px 16px; border-radius: 999px; font-size: 14px; z-index: 20; box-shadow: 0 6px 20px rgba(0,0,0,.25); }
        a.fb { color: #3047a8; font-size: 13px; }
    </style>
</head>
<body>
<header>
    <div class="bar">
        <h1>投稿予定 Posts</h1>
        <button type="button" class="switch {{ $enabled ? 'on' : '' }}" id="auto" aria-pressed="{{ $enabled ? 'true' : 'false' }}"><span id="autoText">{{ $enabled ? '自動 ON' : '自動 OFF' }}</span><span class="knob"></span></button>
    </div>
    <nav class="days" id="days"></nav>
</header>

<main id="list"><div class="empty">Loading…</div></main>

<div class="sheet-bg" id="bg" hidden></div>
<section class="sheet" id="sheet" hidden aria-modal="true" role="dialog"></section>
<div class="toast" id="toast" hidden></div>

<script>
(function () {
    const BASE = @json(url(config('app.admin_url').'/autoposter/m'));
    const CSRF = document.querySelector('meta[name=csrf-token]').content;
    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
    const WD = ['日', '月', '火', '水', '木', '金', '土'];
    const STATUS = { scheduled: '予定', published: '投稿済', failed: '失敗', processing: '投稿中', draft: '下書き' };

    let posts = [], day = null, open = null;

    function toast(msg) { const t = $('toast'); t.textContent = msg; t.hidden = false; clearTimeout(t._h); t._h = setTimeout(() => (t.hidden = true), 2200); }

    async function api(path, body) {
        const res = await fetch(BASE + path, {
            method: body ? 'POST' : 'GET',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': CSRF, ...(body ? { 'Content-Type': 'application/json' } : {}) },
            body: body ? JSON.stringify(body) : undefined,
            credentials: 'same-origin',
        });
        const json = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(json.message || ('Error ' + res.status));
        return json;
    }

    function today() { return new Date(Date.now() + 9 * 3600e3).toISOString().slice(0, 10); }

    function drawDays() {
        const byDay = {};
        posts.forEach((p) => { byDay[p.day] = (byDay[p.day] || 0) + (p.status === 'cancelled' ? 0 : 1); });
        const keys = Object.keys(byDay).sort();
        if (!day || !byDay[day]) day = keys.includes(today()) ? today() : keys[0];
        $('days').innerHTML = keys.map((k) => {
            return `<button type="button" class="day ${k === day ? 'active' : ''}" data-day="${k}"><b>${k.slice(5).replace('-', '/')}</b><span>${WD[new Date(k + 'T12:00:00Z').getUTCDay()]} · ${byDay[k]}件</span></button>`;
        }).join('');
        const act = $('days').querySelector('.active');
        if (act) act.scrollIntoView({ inline: 'center', block: 'nearest' });
    }

    function drawList() {
        const list = posts.filter((p) => p.day === day);
        $('list').innerHTML = list.length ? list.map((p) => `
            <button type="button" class="post" data-id="${p.id}">
                <img src="${esc(p.cover || (p.products[0] && p.products[0].image) || '')}" alt="" loading="lazy">
                <div style="min-width:0;flex:1">
                    <div><span class="t">${esc(p.time)}</span><span class="pill s-${p.status}">${STATUS[p.status] || p.status}</span></div>
                    <div class="p">${p.products.map((x) => esc(x.name)).join(' · ')}</div>
                    <div class="c">${esc(p.caption.split('\n').filter(Boolean)[0] || '')}</div>
                </div>
            </button>`).join('') : '<div class="empty">この日の投稿はありません</div>';
    }

    function sheet(p) {
        open = p;
        const locked = p.status === 'published' || p.status === 'processing';
        $('sheet').innerHTML = `
            <div class="grab"></div>
            ${p.cover ? `<img class="cover" src="${esc(p.cover)}" alt="Cover">` : ''}
            <div class="thumbs">${p.products.map((x) => `<img src="${esc(x.image)}" alt="${esc(x.name)}" loading="lazy">`).join('')}</div>
            <div class="p" style="font-size:13px;color:var(--muted)">${p.products.map((x) => esc(x.name)).join(' · ')}</div>
            ${p.permalink ? `<p><a class="fb" href="${esc(p.permalink)}" target="_blank" rel="noopener">Facebookで見る ↗</a></p>` : ''}
            ${p.error ? `<div class="err">${esc(p.error)}</div>` : ''}
            <label class="f" for="when">投稿日時 Time</label>
            <input type="datetime-local" id="when" value="${esc(p.local)}" ${locked ? 'disabled' : ''}>
            <label class="f" for="cap">本文 Caption</label>
            <textarea id="cap" ${locked ? 'disabled' : ''}>${esc(p.caption)}</textarea>
            ${locked ? '<div class="acts"><button type="button" class="btn primary" data-act="close">閉じる Close</button></div>' : `
            <div class="acts">
                <button type="button" class="btn primary" data-act="save">保存 Save</button>
                <button type="button" class="btn" data-act="recaption">別の文章 New text</button>
                <button type="button" class="btn" data-act="shuffle">商品を変更 New products</button>
                <button type="button" class="btn go" data-act="publish">今すぐ投稿 Post now</button>
                <button type="button" class="btn danger" data-act="skip">スキップ Skip</button>
            </div>`}`;
        $('sheet').hidden = false; $('bg').hidden = false;
        $('sheet').scrollTop = 0;
    }

    function close() { $('sheet').hidden = true; $('bg').hidden = true; open = null; }

    function replace(row) {
        const i = posts.findIndex((p) => p.id === row.id);
        if (i >= 0) posts[i] = row;
        drawDays(); drawList();
        if (open && open.id === row.id) sheet(row);
    }

    async function act(name, btn) {
        const p = open;
        if (!p) return;
        if (name === 'close') return close();
        if (name === 'skip' && !btn.dataset.sure) { btn.dataset.sure = '1'; btn.textContent = 'もう一度押してスキップ'; return; }
        if (name === 'publish' && !btn.dataset.sure) { btn.dataset.sure = '1'; btn.textContent = 'もう一度押して投稿'; return; }

        document.querySelectorAll('#sheet .btn').forEach((b) => (b.disabled = true));
        btn.textContent = '…';
        try {
            if (name === 'save') { replace((await api(`/posts/${p.id}`, { caption: $('cap').value, scheduled_at: $('when').value })).data); toast('保存しました Saved'); }
            if (name === 'recaption') { replace((await api(`/posts/${p.id}/recaption`, {})).data); toast('文章を変更しました'); }
            if (name === 'shuffle') { replace((await api(`/posts/${p.id}/shuffle`, {})).data); toast('商品を変更しました'); }
            if (name === 'publish') { replace((await api(`/posts/${p.id}/publish`, {})).data); toast('投稿しました Posted'); }
            if (name === 'skip') { await api(`/posts/${p.id}/skip`, {}); posts = posts.filter((x) => x.id !== p.id); close(); drawDays(); drawList(); toast('スキップしました'); }
        } catch (e) {
            toast(e.message);
            if (open) sheet(open);
        }
    }

    async function load() {
        try { posts = (await api('/posts')).data; drawDays(); drawList(); }
        catch (e) { $('list').innerHTML = `<div class="empty">読み込みできません: ${esc(e.message)}</div>`; }
    }

    document.addEventListener('click', (e) => {
        const d = e.target.closest('.day');
        if (d) { day = d.dataset.day; drawDays(); drawList(); window.scrollTo(0, 0); return; }
        const card = e.target.closest('.post');
        if (card) { sheet(posts.find((p) => p.id === +card.dataset.id)); return; }
        const b = e.target.closest('#sheet .btn');
        if (b) { act(b.dataset.act, b); return; }
        if (e.target === $('bg')) close();
    });

    $('auto').addEventListener('click', async () => {
        const btn = $('auto'), next = !btn.classList.contains('on');
        try {
            const r = await api('/toggle', { enabled: next });
            btn.classList.toggle('on', r.enabled); btn.setAttribute('aria-pressed', r.enabled);
            $('autoText').textContent = r.enabled ? '自動 ON' : '自動 OFF';
            toast(r.enabled ? '自動投稿 ON' : '自動投稿 OFF(予定は保持)');
        } catch (e) { toast(e.message); }
    });

    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
    load();
})();
</script>
</body>
</html>
