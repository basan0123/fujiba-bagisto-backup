<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>{{ __('accounting::app.kiosk.title') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <style>
        :root {
            --bg: #0f172a;
            --panel: #1e293b;
            --text: #f1f5f9;
            --accent: #3b82f6;
            --ok: #10b981;
            --err: #ef4444;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; height: 100%; background: var(--bg); color: var(--text); font-family: system-ui, -apple-system, "Hiragino Sans", "Hiragino Kaku Gothic ProN", "Yu Gothic", sans-serif; }
        .root { height: 100vh; display: grid; place-items: center; padding: 32px; }
        .panel { background: var(--panel); border-radius: 24px; padding: 48px; max-width: 640px; width: 100%; text-align: center; box-shadow: 0 24px 48px rgba(0,0,0,0.4); }
        h1 { margin: 0 0 24px; font-size: 32px; }
        .clock { font-family: monospace; font-size: 48px; color: #94a3b8; margin: 16px 0; }
        input { width: 100%; padding: 24px; font-size: 24px; border: 2px solid #334155; background: #0f172a; color: var(--text); border-radius: 16px; text-align: center; font-family: monospace; }
        input:focus { outline: none; border-color: var(--accent); }
        .result { margin-top: 24px; padding: 24px; border-radius: 16px; font-size: 20px; min-height: 96px; display: grid; place-items: center; }
        .result.ok { background: rgba(16,185,129,0.15); color: #6ee7b7; }
        .result.err { background: rgba(239,68,68,0.15); color: #fca5a5; }
        .result .name { font-size: 28px; font-weight: 700; margin-bottom: 8px; }
        .hint { font-size: 14px; color: #64748b; margin-top: 16px; }
    </style>
</head>
<body>
<div class="root">
    <div class="panel">
        <h1>{{ __('accounting::app.kiosk.title') }}</h1>
        <div id="clock" class="clock">--:--</div>
        <input id="qr" type="text" autofocus autocomplete="off" placeholder="{{ __('accounting::app.kiosk.placeholder') }}"/>
        <div id="result" class="result"></div>
        <p class="hint">QRコードをスキャンするか、トークンを入力して Enter</p>
    </div>
</div>
<script>
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const qrEl = document.getElementById('qr');
    const resultEl = document.getElementById('result');
    const clockEl = document.getElementById('clock');

    function tickClock() {
        const d = new Date();
        clockEl.textContent = d.toLocaleTimeString('ja-JP', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }
    setInterval(tickClock, 1000); tickClock();

    qrEl.addEventListener('keydown', async (e) => {
        if (e.key !== 'Enter') return;
        const token = qrEl.value.trim();
        if (! token) return;

        resultEl.className = 'result';
        resultEl.textContent = '...';

        try {
            const r = await fetch('{{ route('accounting.kiosk.punch') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ token }),
                credentials: 'same-origin',
            });
            const body = await r.json();
            if (body.ok) {
                resultEl.className = 'result ok';
                resultEl.innerHTML = `<div><div class="name">${body.staff_name ?? ''}</div><div>${body.message} (${body.time ?? ''})</div></div>`;
            } else {
                resultEl.className = 'result err';
                resultEl.textContent = body.message ?? 'error';
            }
        } catch (err) {
            resultEl.className = 'result err';
            resultEl.textContent = err.message;
        }

        qrEl.value = '';
        setTimeout(() => qrEl.focus(), 50);
        setTimeout(() => { resultEl.className = 'result'; resultEl.textContent = ''; }, 3500);
    });

    // Keep focus pinned on the input — kiosk staff don't have keyboards normally
    document.addEventListener('click', () => qrEl.focus());
</script>
</body>
</html>
