@php
    $fmtLimit = fn ($v) => $v === null ? $t['unlimited'] : number_format($v);
    $langs = array_intersect_key(['ja' => '日本語', 'en' => 'English', 'my' => 'မြန်မာ'], array_flip($saas['locales']));
    $tiles = ['pink', 'purple', 'blue', 'green', 'purple', 'pink'];
    $featureIcons = ['store', 'receipt', 'card', 'truck', 'megaphone', 'sparkles'];
    // Stroke icons (24px grid), drawn inline so nothing depends on emoji fonts.
    $paths = [
        'store'     => '<path d="M3 9l1.5-5h15L21 9"/><path d="M3 9h18v2a3 3 0 0 1-6 0 3 3 0 0 1-6 0 3 3 0 0 1-6 0z"/><path d="M5 13v7h14v-7"/><path d="M10 20v-4h4v4"/>',
        'receipt'   => '<path d="M6 2h12v20l-3-2-3 2-3-2-3 2z"/><path d="M9 7h6M9 11h6M9 15h4"/>',
        'card'      => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/>',
        'truck'     => '<path d="M1 6h13v10H1z"/><path d="M14 9h4l4 4v3h-8z"/><circle cx="5.5" cy="18" r="2"/><circle cx="17.5" cy="18" r="2"/>',
        'megaphone' => '<path d="M3 10v4h3l7 4V6L6 10z"/><path d="M17 8a5 5 0 0 1 0 8M20 5a9 9 0 0 1 0 14"/>',
        'sparkles'  => '<path d="M12 3l1.8 4.7L18.5 9.5l-4.7 1.8L12 16l-1.8-4.7L5.5 9.5l4.7-1.8z"/><path d="M19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8z"/>',
        'chat'      => '<path d="M21 12a8 8 0 0 1-11.6 7.1L3 21l1.9-6.4A8 8 0 1 1 21 12z"/><path d="M8 12h.01M12 12h.01M16 12h.01"/>',
        'user'      => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'cart'      => '<circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.7 12.4a1 1 0 0 0 1 .8h9.6a1 1 0 0 0 1-.8L21 7H6"/>',
        'box'       => '<path d="M21 8l-9-5-9 5 9 5z"/><path d="M3 8v8l9 5 9-5V8M12 13v8"/>',
        'bell'      => '<path d="M18 16V11a6 6 0 0 0-12 0v5l-2 2h16z"/><path d="M10 21h4"/>',
        'chart'     => '<path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 6-6"/>',
    ];
    $icon = fn ($k) => '<svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($paths[$k] ?? '').'</svg>';
    $noindex = ! config('saas.public', false) || in_array($locale, $saas['unreviewed_locales'] ?? [], true);
@endphp
<!doctype html>
<html lang="{{ $locale }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $t['title'] }}</title>
<meta name="description" content="{{ $t['lead'] }}">
<meta property="og:title" content="{{ $t['title'] }}">
<meta property="og:description" content="{{ $t['lead'] }}">
@if ($noindex)<meta name="robots" content="noindex">@endif
@foreach ($saas['locales'] as $alt)
<link rel="alternate" hreflang="{{ $alt }}" href="{{ url('/saas?lang='.$alt) }}">
@endforeach
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
@php $fontUrl = 'https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;800'.($locale === 'my' ? '&family=Noto+Sans+Myanmar:wght@400;700' : '').'&display=swap'; @endphp
<link rel="stylesheet" href="{{ $fontUrl }}" media="print" onload="this.media='all'">
<noscript><link rel="stylesheet" href="{{ $fontUrl }}"></noscript>
<style>
:root{
  --text:#505478;--muted:#686C8C;--bg:#fff;--soft:#F9F9F9;--line:#ececf3;
  --pink-a:#dc4985;--pink-b:#f18da2;--pink:#C2386F;
  --g-pink:linear-gradient(135deg,#dc4985,#f18da2);--g-purple:linear-gradient(135deg,#a866d1,#f9a4d7);
  --g-blue:linear-gradient(135deg,#4354e2,#6ca3f3);--g-green:linear-gradient(135deg,#2cc590,#75eb92);
  --shadow:0 10px 35px rgba(80,84,120,.10);
}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font:16px/1.75 "Nunito","Noto Sans Myanmar","Hiragino Sans","Yu Gothic","Noto Sans JP",sans-serif}
html[lang=my] body{line-height:2.05}
a{color:inherit;text-decoration:none}img{max-width:100%}
.wrap{max-width:1140px;margin:0 auto;padding:0 16px}

/* header */
.top{position:sticky;top:0;z-index:10;background:rgba(255,255,255,.92);backdrop-filter:blur(8px);border-bottom:1px solid var(--line)}
.top .wrap{display:flex;align-items:center;gap:18px;height:68px}
.brand{font-weight:800;font-size:24px;color:var(--text)}.brand b{background:var(--g-pink);-webkit-background-clip:text;background-clip:text;color:transparent}
.menu{display:flex;gap:22px;margin-left:auto;font-weight:700;font-size:15px}.menu a:hover{color:var(--pink)}
.langs{display:flex;gap:4px;background:var(--soft);border-radius:99px;padding:4px}
.langs a{font-size:13px;font-weight:700;padding:4px 10px;border-radius:99px;color:#5c607e}
.langs a[aria-current]{background:var(--g-pink);color:#fff}
@media(max-width:820px){.menu{display:none}.langs{margin-left:auto}}

/* buttons */
.btn{display:inline-block;padding:13px 30px;border-radius:99px;font-weight:800;transition:transform .15s,box-shadow .15s}
.btn:hover{transform:translateY(-2px)}
.btn-main{background:var(--g-pink);color:#fff;box-shadow:0 10px 25px rgba(220,73,133,.35)}
.btn-ghost{border:2px solid var(--pink);color:var(--pink)}
.btn-white{background:#fff;color:var(--pink)}

/* hero */
.hero{position:relative;overflow:hidden;padding:72px 0 90px;background:radial-gradient(ellipse at 90% 10%,rgba(241,141,162,.22),transparent 55%),radial-gradient(ellipse at 0% 100%,rgba(168,102,209,.14),transparent 50%)}
.hero .wrap{display:grid;grid-template-columns:1.1fr .9fr;gap:40px;align-items:center}
.eyebrow{display:inline-block;font-weight:800;font-size:14px;color:var(--pink);background:rgba(222,80,136,.1);padding:5px 14px;border-radius:99px;margin-bottom:16px}
.hero h1{font-size:clamp(30px,5vw,50px);line-height:1.25;margin:0 0 18px;color:#2d2f4a}
.hero p{font-size:18px;color:var(--muted);margin:0 0 30px;max-width:560px}
.hero .actions{display:flex;flex-wrap:wrap;gap:12px}
.note{margin-top:16px;font-size:13px;color:var(--muted)}
@media(max-width:820px){.hero .wrap{grid-template-columns:1fr}}
/* sections */
section{padding:80px 0}
.soft{background:var(--soft)}
.title{text-align:center;max-width:640px;margin:0 auto 50px}
.title h2{font-size:clamp(26px,4vw,38px);margin:0;color:#2d2f4a}
.title:after{content:"";display:block;width:60px;height:4px;border-radius:4px;background:var(--g-pink);margin:16px auto 0}
.grid{display:grid;gap:26px;grid-template-columns:repeat(auto-fit,minmax(250px,1fr))}
.feat{background:#fff;border-radius:20px;padding:30px;box-shadow:var(--shadow);transition:transform .2s}
.feat:hover{transform:translateY(-6px)}
.icon{width:62px;height:62px;border-radius:18px;display:grid;place-items:center;color:#fff;margin-bottom:18px}
.pink{background:var(--g-pink)}.purple{background:var(--g-purple)}.blue{background:var(--g-blue)}.green{background:var(--g-green)}
.feat h3{margin:0 0 6px;font-size:20px;color:#2d2f4a}.feat p{margin:0;color:var(--muted)}

.steps{counter-reset:s;display:grid;gap:22px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr))}
.step{position:relative;background:#fff;border-radius:20px;padding:28px;box-shadow:var(--shadow)}
.step:before{counter-increment:s;content:counter(s);display:grid;place-items:center;width:44px;height:44px;border-radius:50%;background:var(--g-pink);color:#fff;font-weight:800;margin-bottom:14px}
.step h3{margin:0 0 4px;font-size:18px;color:#2d2f4a}.step p{margin:0;color:var(--muted);font-size:15px}

.plan{position:relative;background:#fff;border-radius:22px;padding:34px 28px;box-shadow:var(--shadow);text-align:center;border:2px solid transparent}
.plan.featured{border-color:var(--pink);transform:scale(1.03)}
.badge{position:absolute;top:-14px;left:50%;transform:translateX(-50%);background:var(--g-pink);color:#fff;font-size:13px;font-weight:800;padding:4px 16px;border-radius:99px;white-space:nowrap}
.plan h3{margin:0;font-size:20px;color:var(--muted)}
.price.contact{font-size:26px;padding:10px 0 4px}.price{font-size:42px;font-weight:800;color:#2d2f4a;margin:6px 0 0}.per{font-size:14px;color:var(--muted)}
.plan ul{list-style:none;padding:0;margin:22px 0 26px;text-align:left;font-size:15px}
.plan li{display:flex;justify-content:space-between;gap:10px;padding:9px 0;border-top:1px dashed var(--line)}
.plan li b{color:#2d2f4a;font-variant-numeric:tabular-nums}
.plan .btn{width:100%}
.center{text-align:center}

.faq{max-width:800px;margin:0 auto}
details{background:#fff;border-radius:16px;box-shadow:var(--shadow);padding:18px 22px;margin-bottom:14px}
summary{cursor:pointer;font-weight:800;color:#2d2f4a;list-style:none;display:flex;justify-content:space-between;gap:12px}
summary:after{content:"+";color:var(--pink);font-size:22px;line-height:1}details[open] summary:after{content:"−"}
details p{margin:10px 0 0;color:var(--muted)}

.band{background:var(--g-pink);color:#fff;border-radius:28px;padding:56px 32px;text-align:center;box-shadow:0 20px 45px rgba(220,73,133,.3)}
.band h2{font-size:clamp(24px,4vw,36px);margin:0 0 8px}.band p{margin:0 0 26px;opacity:.92}
/* animated product-to-order demo */
.demo{display:flex;gap:22px;align-items:center;justify-content:center}
.phone{position:relative;flex:none;width:270px;max-width:100%;height:540px;border-radius:40px;background:#fff;border:10px solid #2d2f4a;box-shadow:0 30px 70px rgba(45,47,74,.28),0 0 0 8px rgba(222,80,136,.08);overflow:hidden}
.notch{position:absolute;top:0;left:50%;width:90px;height:20px;margin-left:-45px;background:#2d2f4a;border-radius:0 0 14px 14px;z-index:3}
.hero .phone p{font-size:13px;max-width:none;margin:8px 0;color:#2d2f4a}
.scr{position:absolute;inset:0;padding:30px 14px 14px;opacity:0;transform:translateY(14px) scale(.98);transition:opacity .5s,transform .5s;font-size:13px;line-height:1.55;overflow:hidden}
.scr.on{opacity:1;transform:none}
.scr img{display:block;width:100%;height:100%;object-fit:cover}
.ph{position:relative;height:260px;border-radius:18px;overflow:hidden}.ph.sq{height:200px}
.scanline{position:absolute;left:0;right:0;height:3px;background:#2cc590;box-shadow:0 0 18px 6px rgba(44,197,144,.55);top:0}
.on .scanline{animation:scan 1.8s ease-in-out 2}
@keyframes scan{50%{top:100%}}
.scan{margin:10px 0 6px;font-weight:800;color:#13704F}
.tags{display:flex;flex-wrap:wrap;gap:6px}
.tags span,.bub,.typing,.hand,.ticks li{opacity:0;transform:translateY(8px)}
.on .tags span,.on .bub,.on .hand,.on .ticks li{animation:pop .45s var(--d) forwards}
.tags span{background:var(--soft);border:1px solid var(--line);padding:3px 10px;border-radius:99px;font-weight:700;font-size:12px}
.tags span:last-child{background:var(--g-pink);color:#fff;border:0}
@keyframes pop{to{opacity:1;transform:none}}
.sparkle{position:absolute;top:10px;right:10px;background:var(--g-purple);color:#fff;font-size:11px;padding:3px 9px;border-radius:99px}
.type{min-height:88px;margin:10px 0 4px;color:#2d2f4a}.type:after{content:"";display:inline-block;width:2px;height:14px;background:var(--pink);margin-left:2px;vertical-align:-2px;animation:blink 1s steps(1) infinite}
@keyframes blink{50%{opacity:0}}
.hash{margin:0;color:#3b5bdb;font-weight:700;font-size:12px}
.fb{border:1px solid var(--line);border-radius:14px;overflow:hidden}
.fbh{display:flex;gap:8px;align-items:center;padding:8px}.fbh small{display:block;color:var(--muted);font-size:11px}
.av{flex:0 0 30px;width:30px;height:30px;border-radius:50%;background:var(--g-pink)}
.net{display:inline-grid;place-items:center;width:15px;height:15px;border-radius:4px;background:#1877f2;color:#fff;font-size:9px;font-weight:800}
.net.ig{background:linear-gradient(135deg,#f58529,#dd2a7b,#8134af)}.net.tt{background:#111}
.fbc{margin:0;padding:0 8px 8px;font-size:12px}.fb img{height:190px}
.fbb{display:flex;justify-content:flex-end;padding:8px;background:var(--soft)}.fbb span{background:#1877f2;color:#fff;font-weight:800;font-size:12px;padding:4px 12px;border-radius:6px}
.likes{padding:6px 8px;color:#e0245e;font-size:12px}
.ch{display:flex;gap:8px;align-items:center;padding-bottom:8px;border-bottom:1px solid var(--line);margin-bottom:10px}
.bot{font-size:10px;font-weight:800;background:#e7f8f1;color:#13704F;padding:1px 6px;border-radius:6px}
.bub{max-width:84%;padding:8px 11px;border-radius:16px;margin-bottom:8px;font-size:12.5px}
.bub.c{background:#0084ff;color:#fff;margin-left:auto;border-bottom-right-radius:4px}
.bub.b{background:#f0f0f5;color:#2d2f4a;border-bottom-left-radius:4px}
.lk{display:flex;gap:8px;align-items:center;background:#fff;border-radius:10px;margin-top:6px;padding:5px;font-size:11.5px}.lk img{width:40px;height:40px;border-radius:7px}
.typing{display:flex;gap:4px;width:52px;padding:10px;background:#f0f0f5;border-radius:16px;margin-bottom:8px}
.on .typing{animation:pop .3s var(--d) forwards,gone .2s 1.7s forwards}
@keyframes gone{to{opacity:0;height:0;padding:0;margin:0}}
.typing i{width:6px;height:6px;border-radius:50%;background:#9a9db5;animation:blink 1s infinite}.typing i:nth-child(2){animation-delay:.2s}.typing i:nth-child(3){animation-delay:.4s}
.hand{font-size:11.5px;font-weight:700;color:var(--pink);background:rgba(222,80,136,.08);border-radius:10px;padding:6px 9px;margin-top:6px}
.scr h4{margin:10px 0 2px;font-size:15px;color:#2d2f4a}.pr{font-size:20px;font-weight:800;color:#2d2f4a}.pr small{font-size:12px;color:var(--muted);font-weight:600}
.paybtn{position:relative;height:44px;margin-top:12px;border-radius:12px;overflow:hidden;font-weight:800;color:#fff;text-align:center;line-height:44px}
.paybtn span{position:absolute;inset:0}.paybtn .a{background:#ff0033}.paybtn .b{background:#2cc590;opacity:0}
.on .paybtn .a{animation:press .25s 1.3s}.on .paybtn .b{animation:pop .35s 1.6s forwards}
@keyframes press{50%{transform:scale(.94)}}
.ord{background:var(--g-pink);color:#fff;font-weight:800;border-radius:14px;padding:12px;margin-bottom:12px}
.ticks{list-style:none;padding:0;margin:0}.ticks li{display:flex;gap:8px;align-items:flex-start;padding:9px 0;border-bottom:1px dashed var(--line);font-weight:700;color:#2d2f4a}
.ticks i{flex:0 0 20px;height:20px;border-radius:50%;background:#2cc590;color:#fff;font-style:normal;font-size:12px;display:grid;place-items:center}
.rail{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:6px;position:relative}
.rail:before{content:"";position:absolute;left:15px;top:14px;bottom:14px;width:4px;border-radius:4px;background:linear-gradient(180deg,#dc4985,#a866d1,#4354e2,#2cc590);background-size:100% 300%;animation:liquid 6s linear infinite;opacity:.35}
@keyframes liquid{to{background-position:0 300%}}
.rail button{position:relative;display:flex;gap:10px;align-items:center;width:100%;border:0;background:none;padding:6px 8px 6px 0;font:inherit;font-size:13.5px;font-weight:700;color:#5c607e;text-align:left;cursor:pointer;border-radius:12px}
.rail button span{flex:0 0 34px;height:34px;border-radius:50%;display:grid;place-items:center;background:#fff;border:2px solid var(--line);font-weight:800;transition:all .3s}
.rail button[aria-current] {color:#2d2f4a}
.rail button[aria-current] span{background:var(--g-pink);border-color:transparent;color:#fff;box-shadow:0 0 0 6px rgba(222,80,136,.15)}
@media(max-width:1000px){.demo{flex-direction:column}.rail{flex-direction:row;overflow-x:auto;max-width:100%;padding-bottom:4px}.rail:before{display:none}
  .rail li{flex:0 0 auto}.rail button{font-size:0;padding:0}.rail button span{font-size:14px}.rail button[aria-current]{font-size:13px;padding-right:10px}}
@media(prefers-reduced-motion:reduce){.scr,.scr *{animation:none!important;transition:none!important;opacity:1!important;transform:none!important}.typing{display:none}}
footer{padding:36px 0;color:var(--muted);font-size:14px;text-align:center}
</style>
</head>
<body>
<header class="top"><div class="wrap">
  <a class="brand" href="?lang={{ $locale }}">sns<b>sale</b></a>
  <nav class="menu">
    <a href="#features">{{ $t['nav']['features'] }}</a><a href="#demo">{{ $t['nav']['flow'] }}</a><a href="#how">{{ $t['nav']['how'] }}</a>
    <a href="#pricing">{{ $t['nav']['pricing'] }}</a><a href="#faq">{{ $t['nav']['faq'] }}</a>
  </nav>
  <nav class="langs" aria-label="Language">
    @foreach ($langs as $code => $label)
      <a href="?lang={{ $code }}" @if ($code === $locale) aria-current="true" @endif>{{ $label }}</a>
    @endforeach
  </nav>
</div></header>

<main>
<div class="hero"><div class="wrap">
  <div>
    <span class="eyebrow">snssale.com</span>
    <h1>{{ $t['tagline'] }}</h1>
    <p>{{ $t['lead'] }}</p>
    <div class="actions">
      <a class="btn btn-main" href="#pricing">{{ $t['cta'] }}</a>
      <a class="btn btn-ghost" href="https://kimono.fujiba.co.jp" rel="noopener">{{ $t['demo'] }}</a>
    </div>
    <div class="note">{{ $t['preview'] }}</div>
  </div>
  @php $d = $t['demo_flow']; @endphp
  <div class="demo" id="demo">
    <div class="phone" aria-hidden="true">
      <div class="notch"></div>
      {{-- 1 learn --}}
      <div class="scr on" data-s="0">
        <div class="ph"><img src="/saas/demo-fabric.webp" alt="" width="480" height="480"><i class="scanline"></i></div>
        <div class="scan">{{ $d['scan'] }}</div>
        <div class="tags">@foreach ($d['tags'] as $k => $tag)<span style="--d:{{ .5 + $k * .45 }}s">{{ $tag }}</span>@endforeach</div>
      </div>
      {{-- 2 create --}}
      <div class="scr" data-s="1">
        <div class="ph sq"><img src="/saas/demo-fabric.webp" alt="" width="480" height="480"><b class="sparkle">✦ AI</b></div>
        <p class="type" data-text="{{ $d['caption'] }}"></p>
        <p class="hash">{{ $d['hashtags'] }}</p>
      </div>
      {{-- 3 publish --}}
      <div class="scr" data-s="2">
        <div class="fb">
          <div class="fbh"><i class="av"></i><div><b>{{ $d['shop'] }}</b><small>{{ $d['sponsored'] }} · <span class="net">f</span> <span class="net ig">ig</span> <span class="net tt">tt</span></small></div></div>
          <p class="fbc">{{ \Illuminate\Support\Str::limit($d['caption'], 70) }}</p>
          <img src="/saas/demo-fabric.webp" alt="" width="480" height="480">
          <div class="fbb"><span>{{ $d['shop_now'] }}</span></div>
          <div class="likes">♥ <b class="count" data-to="248">0</b> {{ $d['likes'] }}</div>
        </div>
      </div>
      {{-- 4 chat --}}
      <div class="scr chat" data-s="3">
        <div class="ch"><i class="av"></i><b>{{ $d['shop'] }}</b><span class="bot">BOT</span></div>
        <div class="bub c" style="--d:.3s">{{ $d['c1'] }}</div>
        <div class="typing" style="--d:1s"><i></i><i></i><i></i></div>
        <div class="bub b" style="--d:1.8s">{{ $d['b1'] }}
          <div class="lk"><img src="/saas/demo-fabric.webp" alt="" width="480" height="480"><span>{{ $d['product'] }}<br><b>¥1,595 / m</b></span></div></div>
        <div class="bub c" style="--d:2.8s">{{ $d['c2'] }}</div>
        <div class="hand" style="--d:3.4s">{{ $d['handover'] }}</div>
      </div>
      {{-- 5 buy --}}
      <div class="scr" data-s="4">
        <div class="ph sq"><img src="/saas/demo-fabric.webp" alt="" width="480" height="480"></div>
        <h4>{{ $d['product'] }}</h4>
        <div class="pr">¥3,190 <small>{{ $d['qty'] }}</small></div>
        <div class="paybtn"><span class="a">{{ $d['pay'] }}</span><span class="b">✓ {{ $d['paid'] }}</span></div>
      </div>
      {{-- 6 done --}}
      <div class="scr" data-s="5">
        <div class="ord">{{ $d['order'] }}</div>
        <ul class="ticks">@foreach ($d['done'] as $k => $line)<li style="--d:{{ .3 + $k * .55 }}s"><i>✓</i>{{ $line }}</li>@endforeach</ul>
        <div class="hand" style="--d:2.8s">{{ $d['handover'] }}</div>
      </div>
    </div>
    <ol class="rail">
      @foreach ($d['steps'] as $i => $step)
        <li><button type="button" data-go="{{ $i }}" @if ($i === 0) aria-current="step" @endif><span>{{ $i + 1 }}</span>{{ $step }}</button></li>
      @endforeach
    </ol>
  </div>
</div></div>
    <div class="line"></div><div class="line s"></div>
    <div class="chip a">POS ✓</div><div class="chip b">PayPay</div>
  </div>
</div></div>

<section id="features" class="soft"><div class="wrap">
  <div class="title"><h2>{{ $t['features_h'] }}</h2></div>
  <div class="grid">
    @foreach ($t['features'] as $i => [$h, $p])
      <div class="feat"><div class="icon {{ $tiles[$i % 6] }}">{!! $icon($featureIcons[$i % 6]) !!}</div><h3>{{ $h }}</h3><p>{{ $p }}</p></div>
    @endforeach
  </div>
</div></section>

<section id="how" class="soft"><div class="wrap">
  <div class="title"><h2>{{ $t['steps_h'] }}</h2></div>
  <div class="steps">
    @foreach ($t['steps'] as [$h, $p])
      <div class="step"><h3>{{ $h }}</h3><p>{{ $p }}</p></div>
    @endforeach
  </div>
</div></section>

<section id="pricing"><div class="wrap">
  <div class="title"><h2>{{ $t['pricing_h'] }}</h2></div>
  <div class="grid">
    @foreach ($plans as $code => $plan)
      <div class="plan @if (! empty($plan['featured'])) featured @endif" data-plan="{{ $code }}">
        @if (! empty($plan['featured']))<span class="badge">{{ $t['popular'] }}</span>@endif
        <h3>{{ $t['plan_names'][$code] ?? ucfirst($code) }}</h3>
        @if ($plan['monthly_price'] === null)
          <div class="price contact">{{ $t['contact'] }}</div><div class="per">&nbsp;</div>
        @else
          <div class="price">¥{{ number_format($plan['monthly_price']) }}</div>
          <div class="per">{{ $t['per_month'] }}</div>
        @endif
        <ul>
          @foreach ($plan['limits'] as $key => $value)
            <li><span>{{ $t['limits'][$key] ?? $key }}</span><b>{{ $fmtLimit($value) }}</b></li>
          @endforeach
        </ul>
        <a class="btn {{ ! empty($plan['featured']) ? 'btn-main' : 'btn-ghost' }}" href="#pricing">{{ $t['cta'] }}</a>
      </div>
    @endforeach
  </div>
  <p class="note center">{{ str_replace(':days', $saas['trial_days'], $t['trial']) }}</p>
</div></section>

<section id="faq" class="soft"><div class="wrap">
  <div class="title"><h2>{{ $t['faq_h'] }}</h2></div>
  <div class="faq">
    @foreach ($t['faq'] as [$q, $a])
      <details><summary>{{ $q }}</summary><p>{{ $a }}</p></details>
    @endforeach
  </div>
</div></section>

<div class="wrap"><div class="band">
  <h2>{{ $t['band_h'] }}</h2><p>{{ $t['band_p'] }}</p>
  <a class="btn btn-white" href="#pricing">{{ $t['cta'] }}</a>
</div></div>

</main>

<footer>© {{ date('Y') }} snssale · {{ $t['footer'] }}</footer>
<script>
(function () {
  var root = document.getElementById('demo'); if (!root) return;
  var scr = root.querySelectorAll('.scr'), btn = root.querySelectorAll('[data-go]');
  var cur = 0, timer, still = matchMedia('(prefers-reduced-motion: reduce)').matches;
  var hold = [4200, 5200, 4200, 5600, 3800, 4600];
  function type(el) {
    var full = el.getAttribute('data-text'), i = 0; el.textContent = '';
    (function tick() { if (!scr[1].classList.contains('on')) return; el.textContent = full.slice(0, ++i); if (i < full.length) setTimeout(tick, 22); })();
  }
  function count(el) {
    var to = +el.getAttribute('data-to'), n = 0;
    (function tick() { n = Math.min(to, n + 9); el.textContent = n; if (n < to && scr[2].classList.contains('on')) setTimeout(tick, 40); })();
  }
  function show(n) {
    cur = n;
    scr.forEach(function (s, i) { s.classList.remove('on'); void s.offsetWidth; if (i === n) s.classList.add('on'); });
    btn.forEach(function (b, i) { i === n ? b.setAttribute('aria-current', 'step') : b.removeAttribute('aria-current'); });
    if (n === 1) { var t = scr[1].querySelector('.type'); still ? t.textContent = t.getAttribute('data-text') : type(t); }
    if (n === 2) { var c = scr[2].querySelector('.count'); still ? c.textContent = c.getAttribute('data-to') : count(c); }
    clearTimeout(timer); if (!still) timer = setTimeout(function () { show((cur + 1) % scr.length); }, hold[n]);
  }
  btn.forEach(function (b) { b.addEventListener('click', function () { show(+b.getAttribute('data-go')); }); });
  show(0);
})();
</script>
</body>
</html>
