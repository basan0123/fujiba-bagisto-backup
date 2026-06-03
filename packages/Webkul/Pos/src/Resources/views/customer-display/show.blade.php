<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="apple-mobile-web-app-capable" content="yes">
<title>合計金額 / Total</title>
<style>
  html,body{margin:0;height:100%;width:100%;background:#000;color:#fff;font-family:-apple-system,BlinkMacSystemFont,"Hiragino Sans","Yu Gothic",sans-serif;-webkit-text-size-adjust:100%;overflow:hidden}
  .wrap{height:100vh;width:100vw;display:flex;flex-direction:column;justify-content:center;align-items:center;padding:env(safe-area-inset-top,24px) 24px env(safe-area-inset-bottom,24px);text-align:center;box-sizing:border-box}
  .label{font-size:clamp(18px,2.5vmin,40px);color:#8E8E93;letter-spacing:.05em}
  .total{font-size:clamp(64px,18vmin,260px);line-height:1;font-weight:700;margin:clamp(16px,3vmin,48px) 0;font-variant-numeric:tabular-nums}
  .sub{font-size:clamp(18px,2.6vmin,42px);color:#aeaeb2}
  /* Itemized line list — shown above the total when the cart has lines.
     Scrolls internally if the cart is long so the total stays visible. */
  .lines{width:min(900px,92vw);max-height:42vh;overflow-y:auto;margin:0 0 clamp(8px,2vmin,24px);
    -webkit-overflow-scrolling:touch}
  .lines.empty{display:none}
  .line{display:flex;align-items:baseline;justify-content:space-between;gap:16px;
    padding:clamp(6px,1.2vmin,14px) 0;border-bottom:1px solid #1c1c1e;font-variant-numeric:tabular-nums}
  .line:last-child{border-bottom:0}
  .line .nm{flex:1;min-width:0;text-align:left;font-size:clamp(16px,2.2vmin,34px);color:#fff;
    white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .line .qty{flex:none;color:#8E8E93;font-size:clamp(14px,1.8vmin,28px)}
  .line .amt{flex:none;color:#fff;font-size:clamp(16px,2.2vmin,34px);min-width:4em;text-align:right}
  body.paid .lines{display:none}
  .status{position:fixed;top:12px;right:12px;font-size:13px;color:#8E8E93;font-variant-numeric:tabular-nums}
  .status.ok{color:#34C759}.status.warn{color:#FF9F0A}.status.err{color:#FF453A}
  .ended{font-size:clamp(22px,3vmin,48px);color:#FF453A;margin-top:clamp(16px,3vmin,40px);display:none}
  .waiting{color:#8E8E93;font-size:clamp(18px,2.4vmin,40px)}
  .thanks{font-size:clamp(28px,5vmin,96px);color:#34C759;margin-top:clamp(16px,3vmin,40px);display:none;font-weight:700}
  body.paid .total{color:#34C759}
  body.paid .label{color:#34C759}
  /* Landscape kiosk: cap text scaling so very wide screens stay readable */
  @media (orientation:landscape) and (min-width:1280px){
    .total{font-size:clamp(96px,14vmin,220px)}
  }
</style>
</head>
<body>
<div class="wrap">
  <div class="lines empty" id="lines"></div>
  <div class="label" id="label">合計 / TOTAL</div>
  <div class="total" id="total">¥0</div>
  <div class="sub" id="sub"><span class="waiting">スキャンをお待ちしています / Waiting for scan</span></div>
  <div class="thanks" id="thanks">ありがとうございました / Thank you!</div>
  <div class="ended" id="ended">セッション終了 / Session ended — please ask staff.</div>
</div>
<div class="status" id="status">接続中…</div>
<script>
(function(){
  var token = {!! json_encode($token) !!};
  var fmt = function(n, cur){
    n = Math.round(Number(n)||0);
    if (cur === 'JPY' || !cur) return '¥' + n.toLocaleString('ja-JP');
    return new Intl.NumberFormat(undefined, {style:'currency', currency:cur}).format(n);
  };
  var $ = function(id){ return document.getElementById(id); };

  // Render the itemized line list (display-only). Builds DOM via textContent
  // so product names can never inject markup. Hides the box when there are no
  // lines (legacy publishers that send totals only).
  var renderLines = function(lines, cur){
    var box = $('lines');
    box.textContent = '';
    if (!lines || !lines.length) { box.className = 'lines empty'; return; }
    box.className = 'lines';
    for (var i = 0; i < lines.length; i++) {
      var l = lines[i] || {};
      var row = document.createElement('div'); row.className = 'line';
      var nm = document.createElement('span'); nm.className = 'nm';
      nm.textContent = String(l.name == null ? '' : l.name);
      var qty = document.createElement('span'); qty.className = 'qty';
      qty.textContent = '×' + (l.qty | 0);
      var amt = document.createElement('span'); amt.className = 'amt';
      amt.textContent = fmt((Number(l.price) || 0) * (l.qty | 0), cur);
      row.appendChild(nm); row.appendChild(qty); row.appendChild(amt);
      box.appendChild(row);
    }
  };

  var lastMsg = Date.now();
  var ended = false;

  setInterval(function(){
    if (ended) return;
    fetch('/pos/display/'+encodeURIComponent(token)+'/ping', {method:'POST', keepalive:true})
      .catch(function(){});
  }, 30000);

  var es = new EventSource('/pos/display/'+encodeURIComponent(token)+'/stream');

  function bump(){ lastMsg = Date.now(); $('status').textContent='接続中'; $('status').className='status ok'; }

  es.addEventListener('message', bump);
  es.addEventListener('ping', bump);
  var paidResetTimer = null;

  es.addEventListener('cart', function(e){
    bump();
    try {
      var d = JSON.parse(e.data);

      if (d.state === 'paid') {
        // Show paid total + thank-you message; hold for ~8s then reset.
        document.body.classList.add('paid');
        $('label').textContent = 'お支払い完了 / PAID';
        $('total').textContent = fmt(d.total, d.currency);
        $('sub').style.display = 'none';
        $('thanks').style.display = 'block';

        if (paidResetTimer) clearTimeout(paidResetTimer);
        paidResetTimer = setTimeout(function(){
          document.body.classList.remove('paid');
          $('label').textContent = '合計 / TOTAL';
          $('total').textContent = fmt(0, d.currency);
          $('sub').style.display = '';
          $('sub').innerHTML = '<span class="waiting">スキャンをお待ちしています / Waiting for scan</span>';
          $('thanks').style.display = 'none';
          renderLines([], d.currency);
        }, 8000);
        return;
      }

      // state === 'cart' (or unspecified — legacy)
      if (paidResetTimer) {
        // A new cart heartbeat arrived during the paid hold; ignore it
        // (server should have suppressed but defensive).
        return;
      }
      document.body.classList.remove('paid');
      $('label').textContent = '合計 / TOTAL';
      $('total').textContent = fmt(d.total, d.currency);
      $('sub').style.display = '';
      $('thanks').style.display = 'none';
      $('sub').textContent = '小計 ' + fmt(d.subtotal, d.currency)
        + ' ・ 税 ' + fmt(d.tax, d.currency)
        + ' ・ ' + (d.items|0) + '点';
      renderLines(d.lines, d.currency);
    } catch(_) {}
  });
  es.addEventListener('revoked', function(){
    ended = true;
    $('ended').style.display = 'block';
    $('status').textContent = '終了';
    $('status').className = 'status err';
    es.close();
  });
  es.addEventListener('expired', function(){
    ended = true;
    $('ended').style.display = 'block';
    $('status').textContent = '期限切れ';
    $('status').className = 'status err';
    es.close();
  });
  es.addEventListener('rotate', function(){
    es.close();
    setTimeout(function(){ location.reload(); }, 500);
  });

  es.onerror = function(){
    if (ended) return;
    if (es.readyState === EventSource.CLOSED) {
      $('status').textContent = '切断';
      $('status').className = 'status err';
    } else {
      $('status').textContent = '再接続中…';
      $('status').className = 'status warn';
    }
  };

  setInterval(function(){
    if (ended) return;
    if (Date.now() - lastMsg > 10000) {
      $('status').textContent = '切断 — Disconnected, please ask staff';
      $('status').className = 'status err';
    }
  }, 2000);

  document.addEventListener('visibilitychange', function(){
    if (document.visibilityState === 'visible' && !ended && es.readyState === EventSource.CLOSED) {
      location.reload();
    }
  });
})();
</script>
</body>
</html>
