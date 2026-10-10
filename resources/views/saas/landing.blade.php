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
.mock{position:relative;background:#fff;border-radius:24px;box-shadow:var(--shadow);padding:18px}
.mock .bar{display:flex;gap:6px;margin-bottom:12px}.mock .bar i{width:10px;height:10px;border-radius:50%;background:var(--line)}
.mock .row{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
.mock .p{border-radius:14px;aspect-ratio:3/4;background:var(--g-pink);opacity:.9}
.mock .p:nth-child(2){background:var(--g-purple)}.mock .p:nth-child(3){background:var(--g-blue)}
.mock .line{height:10px;border-radius:6px;background:var(--soft);margin-top:12px}.mock .line.s{width:60%}
.chip{position:absolute;background:#fff;border-radius:14px;box-shadow:var(--shadow);padding:10px 14px;font-weight:800;font-size:14px}
.chip.a{left:-22px;bottom:30px}.chip.b{right:-14px;top:-16px;color:var(--pink)}
@media(max-width:820px){.hero .wrap{grid-template-columns:1fr}.chip.a{left:6px}.chip.b{right:6px}}

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
/* liquid automation flow */
.flow{position:relative;max-width:980px;margin:0 auto;padding:10px 0}
.pipe{position:absolute;top:0;bottom:0;left:50%;width:14px;margin-left:-7px;border-radius:14px;overflow:hidden;
  background:linear-gradient(180deg,#dc4985,#a866d1,#4354e2,#2cc590,#dc4985);background-size:100% 400%;animation:liquid 8s linear infinite;
  box-shadow:0 0 0 6px rgba(222,80,136,.08),0 0 30px rgba(168,102,209,.35)}
.pipe i{position:absolute;left:3px;width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,.85);animation:bubble 6s linear infinite}
.pipe i:nth-child(2){animation-delay:-1.5s;width:6px;height:6px;left:4px}.pipe i:nth-child(3){animation-delay:-3s}
.pipe i:nth-child(4){animation-delay:-4.5s;width:5px;height:5px;left:5px}
@keyframes liquid{to{background-position:0 400%}}
@keyframes bubble{from{top:-10px;opacity:0}10%{opacity:1}90%{opacity:1}to{top:100%;opacity:0}}
.node{position:relative;width:calc(50% - 46px);margin:0 0 22px;background:#fff;border-radius:20px;padding:20px 22px 20px 84px;box-shadow:var(--shadow)}
.node:nth-child(odd){margin-left:auto}
.node:before{content:"";position:absolute;top:34px;width:46px;height:4px;border-radius:4px;background:var(--g-pink);opacity:.5}
.node:nth-child(odd):before{left:-46px}.node:nth-child(even):before{right:-46px}
.node .icon{position:absolute;left:18px;top:18px;width:50px;height:50px;border-radius:15px;margin:0}
.node .icon svg{width:24px;height:24px}
.node h3{margin:0 0 2px;font-size:17px;color:#2d2f4a}.node p{margin:0;font-size:14.5px;color:var(--muted)}
.tag{display:inline-block;font-size:11.5px;font-weight:800;letter-spacing:.03em;padding:2px 10px;border-radius:99px;margin-bottom:6px;background:rgba(44,197,144,.12);color:#13704F}
.node.you{border:2px solid var(--pink);box-shadow:0 14px 40px rgba(220,73,133,.25)}
.node.you .tag{background:var(--g-pink);color:#fff}
@media(min-width:821px){.node+.node{margin-top:-64px}}
@media(max-width:820px){.pipe{left:24px}.node,.node:nth-child(odd){width:auto;margin-left:52px}
  .node:before,.node:nth-child(odd):before,.node:nth-child(even):before{left:-28px;right:auto;width:28px}}
@media(prefers-reduced-motion:reduce){.pipe,.pipe i{animation:none}.pipe i{display:none}}
footer{padding:36px 0;color:var(--muted);font-size:14px;text-align:center}
</style>
</head>
<body>
<header class="top"><div class="wrap">
  <a class="brand" href="?lang={{ $locale }}">sns<b>sale</b></a>
  <nav class="menu">
    <a href="#features">{{ $t['nav']['features'] }}</a><a href="#flow">{{ $t['nav']['flow'] }}</a><a href="#how">{{ $t['nav']['how'] }}</a>
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
  <div class="mock" aria-hidden="true">
    <div class="bar"><i></i><i></i><i></i></div>
    <div class="row"><div class="p"></div><div class="p"></div><div class="p"></div></div>
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

<section id="flow"><div class="wrap">
  <div class="title"><h2>{{ $t['flow_h'] }}</h2><p style="color:var(--muted);margin:10px 0 0">{{ $t['flow_sub'] }}</p></div>
  <div class="flow">
    <div class="pipe" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
    @foreach ($t['flow'] as $i => [$ic, $h, $p, $who])
      <div class="node {{ $who }}">
        <div class="icon {{ $who === 'you' ? 'pink' : $tiles[$i % 6] }}">{!! $icon($ic) !!}</div>
        <span class="tag">{{ $t[$who] }}</span>
        <h3>{{ $h }}</h3><p>{{ $p }}</p>
      </div>
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
</body>
</html>
