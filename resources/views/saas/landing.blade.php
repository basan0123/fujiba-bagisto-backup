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
/* animated POS demo */
.sub{color:var(--muted);margin:10px 0 0}
.posdemo{display:flex;gap:34px;align-items:center;justify-content:center}
.stage{flex:none;width:560px;max-width:100%;padding-bottom:170px}
.tab{position:relative;aspect-ratio:16/10.5;background:#fff;border:12px solid #2d2f4a;border-radius:26px;box-shadow:0 30px 70px rgba(45,47,74,.25);overflow:hidden;display:grid;grid-template-columns:1.35fr 1fr;font-size:12px}
.tgrid{display:grid;grid-template-columns:1fr 1fr;gap:8px;padding:10px;background:var(--soft)}
.tile{position:relative;border-radius:12px;overflow:hidden;min-height:0}
.tile img{width:100%;height:100%;object-fit:cover;display:block}
.tile span{position:absolute;left:6px;bottom:6px;right:6px;background:rgba(255,255,255,.92);border-radius:6px;padding:2px 6px;font-weight:700;font-size:11px;color:#2d2f4a}
.bc{position:absolute;top:6px;left:6px;background:#fff;border-radius:4px;padding:0 5px;font-size:10px;letter-spacing:-1px;color:#2d2f4a}
.beam{position:absolute;left:0;right:0;top:50%;height:2px;background:#ff2d55;box-shadow:0 0 12px 4px rgba(255,45,85,.6);opacity:0}
.tcart{display:flex;flex-direction:column;padding:12px;gap:6px;color:#2d2f4a}
.ct{font-size:14px}
.scanning{color:#13704F;font-weight:800;opacity:0}
.row{display:flex;justify-content:space-between;gap:6px;background:var(--soft);border-radius:8px;padding:6px 8px;opacity:0;transform:translateX(20px);transition:all .4s}
.row small{display:block;color:var(--muted)}
.tot{margin-top:auto;display:flex;justify-content:space-between;font-size:15px;font-weight:800;border-top:1px dashed var(--line);padding-top:6px;opacity:.25;transition:opacity .4s}
.paybar{background:var(--g-pink);color:#fff;text-align:center;font-weight:800;border-radius:10px;padding:8px;transition:transform .2s}
.modal,.hub{position:absolute;inset:0;display:grid;place-items:center;align-content:center;gap:12px;background:rgba(255,255,255,.96);opacity:0;pointer-events:none;transition:opacity .45s}
.methods{display:flex;gap:8px}.methods span{border:2px solid var(--line);border-radius:10px;padding:8px 14px;font-weight:800;color:#2d2f4a;transition:all .3s}
.ok{font-size:20px;font-weight:800;color:#13704F;opacity:0;transform:scale(.8);transition:all .4s .9s}
.printer{position:relative;width:200px;height:14px;margin:-2px auto 0;background:#2d2f4a;border-radius:0 0 10px 10px}
.receipt{position:absolute;top:10px;left:14px;right:14px;background:#fff;box-shadow:0 10px 20px rgba(45,47,74,.15);padding:0 10px;font:11px/1.5 ui-monospace,monospace;color:#2d2f4a;max-height:0;overflow:hidden;transition:max-height 1.4s ease-out,padding 1.4s}
.receipt div{display:flex;justify-content:space-between;gap:6px}.receipt .rt{border-top:1px dashed #999;font-weight:800}
.receipt p{text-align:center;margin:4px 0}.rbc{display:block;height:18px;background:repeating-linear-gradient(90deg,#2d2f4a 0 2px,transparent 2px 4px,#2d2f4a 4px 5px,transparent 5px 8px)}
.hub{grid-template-columns:repeat(5,minmax(0,1fr));place-items:stretch;align-content:center;padding:14px;gap:22px 8px}
.core,.hubcap{justify-self:center}
.core{grid-column:1/-1;justify-self:center;background:var(--g-pink);color:#fff;border-radius:18px;padding:10px 22px;text-align:center;box-shadow:0 0 0 0 rgba(222,80,136,.5)}
.core small{display:block;opacity:.85}.core b{font-size:18px}.core s{opacity:.7}
.chn{position:relative;text-align:center;background:#fff;border:2px solid var(--line);border-radius:12px;padding:8px 4px;opacity:0;transform:translateY(10px);transition:all .4s}
.chn:before{content:"";position:absolute;left:50%;bottom:100%;width:3px;height:18px;margin-left:-1px;background:linear-gradient(#dc4985,#a866d1);background-size:100% 200%}
.chn b{display:block;font-size:12px;color:#2d2f4a}.chn small{font-weight:800;font-size:10.5px}
.chn.instant{border-color:#2cc590}.chn.instant small{color:#13704F}
.chn.auto{border-color:#6ca3f3}.chn.auto small{color:#2a4fc2}
.hubcap{grid-column:1/-1;justify-self:center;font-weight:800;color:#2d2f4a}
/* step states */
.posdemo.at0 .beam{opacity:1;animation:scan 1.2s ease-in-out infinite}.posdemo.at0 .scanning{opacity:1}
.posdemo.s1 .r0{opacity:1;transform:none}.posdemo.s1 .r1{opacity:1;transform:none;transition-delay:.7s}.posdemo.s1 .tot{opacity:1}
.posdemo.at1 .t1,.posdemo.at1 .t2{box-shadow:0 0 0 3px var(--pink)}
.posdemo.at2 .modal{opacity:1}.posdemo.at2 .methods span:last-child{border-color:#ff0033;background:#ff0033;color:#fff;transition-delay:.5s}.posdemo.at2 .ok{opacity:1;transform:none}
.posdemo.at2 .paybar{transform:scale(.94)}
.posdemo.s3 .receipt{max-height:220px;padding:8px 10px}
.posdemo.at4 .hub{opacity:1}.posdemo.at4 .chn{opacity:1;transform:none}
.posdemo.at4 .core{animation:pulse 1.6s infinite}.posdemo.at4 .chn:before{animation:liquid 1.2s linear infinite}
@keyframes pulse{70%{box-shadow:0 0 0 16px rgba(222,80,136,0)}}
.posdemo.at4 .c1{transition-delay:.15s}.posdemo.at4 .c2{transition-delay:.3s}.posdemo.at4 .c3{transition-delay:.45s}.posdemo.at4 .c4{transition-delay:.6s}
.posdemo .rail{min-width:220px}
@media(max-width:1000px){.posdemo{flex-direction:column;gap:0}.posdemo .rail{min-width:0}
  .tab{grid-template-columns:1.1fr 1fr;font-size:11px}.hub{grid-template-columns:repeat(5,minmax(0,1fr));gap:10px 4px;padding:8px}.chn:before{display:none}
  .chn{padding:5px 2px}.chn b{font-size:9.5px}.chn small{font-size:8.5px}.core{padding:6px 14px}.core b{font-size:14px}.hubcap{font-size:11px;text-align:center}}
@media(prefers-reduced-motion:reduce){.posdemo *{animation:none!important;transition:none!important}}
/* feature cards: 3 columns with animated art */
.feats{grid-template-columns:repeat(3,minmax(0,1fr))}
@media(max-width:1000px){.feats{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:640px){.feats{grid-template-columns:1fr}}
.feats .feat{padding:0;overflow:hidden}
.art{position:relative;height:170px;overflow:hidden;display:grid;place-items:center;color:#fff}
.art:after{content:"";position:absolute;inset:0;background:radial-gradient(circle at 20% 0,rgba(255,255,255,.35),transparent 55%);pointer-events:none}
.fbody{padding:22px 24px 26px}
.ficon{display:inline-grid;place-items:center;width:38px;height:38px;border-radius:11px;color:#fff;margin-bottom:10px}.ficon svg{width:20px;height:20px}
/* 0 store window */
.win{width:72%;background:#fff;border-radius:10px;box-shadow:0 10px 25px rgba(0,0,0,.18);overflow:hidden}
.url{font:700 10px/1 ui-monospace,monospace;color:#5c607e;background:#f1f1f6;padding:6px 8px;white-space:nowrap;overflow:hidden;width:0;animation:typeurl 4s steps(20) infinite}
@keyframes typeurl{0%{width:0}45%,100%{width:100%}}
.prods{display:grid;grid-template-columns:repeat(3,1fr);gap:6px;padding:8px}
.prods i{aspect-ratio:3/4;border-radius:6px;background:#f0e6f2 center/cover;opacity:0;animation:popin 4s infinite}
.prods i:nth-child(2){background:#e2e8fb;animation-delay:.3s}.prods i:nth-child(3){background:#dff6ec;animation-delay:.6s}
@keyframes popin{0%,30%{opacity:0;transform:translateY(8px)}45%,92%{opacity:1;transform:none}100%{opacity:0}}
/* 1 pos scanner */
.mini-tab{position:relative;width:62%;height:96px;background:#fff;border-radius:12px;border:6px solid #2d2f4a;display:grid;place-items:center}
.bars{font-size:22px;letter-spacing:-2px;color:#2d2f4a}
.beam2{position:absolute;left:8%;right:8%;height:2px;background:#ff2d55;box-shadow:0 0 12px 4px rgba(255,45,85,.6);animation:sweep 1.6s ease-in-out infinite}
@keyframes sweep{0%,100%{top:18%}50%{top:78%}}
.beep{position:absolute;right:12%;top:16%;background:#fff;color:#2d2f4a;font-weight:800;font-size:13px;padding:3px 10px;border-radius:99px;animation:popin 3.2s infinite}
/* 2 payment */
.card3{width:132px;height:84px;border-radius:12px;background:linear-gradient(135deg,#2d2f4a,#5a5f8f);padding:12px;display:flex;flex-direction:column;justify-content:space-between;box-shadow:0 10px 25px rgba(0,0,0,.25);animation:tilt 4s ease-in-out infinite}
.card3 .chip{width:24px;height:18px;border-radius:4px;background:linear-gradient(135deg,#f6d365,#fda085)}.card3 span{font:700 11px ui-monospace,monospace}
@keyframes tilt{0%,100%{transform:rotate(-8deg) translateX(-30px)}50%{transform:rotate(-2deg) translateX(-18px)}}
.paypay{position:absolute;right:16%;bottom:26%;background:#ff0033;font-weight:800;font-size:13px;padding:6px 12px;border-radius:8px;box-shadow:0 8px 18px rgba(0,0,0,.2)}
.tick{position:absolute;right:13%;top:18%;width:36px;height:36px;border-radius:50%;background:#2cc590;display:grid;place-items:center;font-weight:800;animation:popin 3s infinite}
/* 3 delivery */
.road{position:absolute;left:8%;right:8%;bottom:46px;height:4px;border-radius:4px;background:repeating-linear-gradient(90deg,rgba(255,255,255,.9) 0 14px,transparent 14px 24px)}
.truck{position:absolute;bottom:4px;left:0;animation:drive 4s linear infinite}.truck svg{width:44px;height:44px}
@keyframes drive{from{left:-10%}to{left:85%}}
.pin{position:absolute;right:0;bottom:8px;width:16px;height:16px;border-radius:50% 50% 50% 0;background:#fff;transform:rotate(-45deg)}
.track{position:absolute;top:26px;background:#fff;color:#2d2f4a;font:800 11px ui-monospace,monospace;padding:4px 10px;border-radius:99px}
/* 4 social */
.nets{display:flex;gap:12px}
.n{width:46px;height:46px;border-radius:14px;display:grid;place-items:center;font-weight:800;font-size:16px;box-shadow:0 8px 18px rgba(0,0,0,.2);animation:bob 2.4s ease-in-out infinite}
.n.f{background:#1877f2}.n.ig{background:linear-gradient(135deg,#f58529,#dd2a7b,#8134af);animation-delay:.3s}.n.tt{background:#111;animation-delay:.6s}
@keyframes bob{50%{transform:translateY(-8px)}}
.heart{position:absolute;bottom:20px;font-style:normal;font-size:18px;color:#fff;opacity:0;animation:float 3s ease-out infinite}
.h1{left:30%}.h2{left:50%;animation-delay:1s}.h3{left:68%;animation-delay:2s}
@keyframes float{0%{opacity:0;transform:translateY(0)}20%{opacity:1}100%{opacity:0;transform:translateY(-110px) scale(1.4)}}
/* 5 ai */
.doc{width:56%;background:#fff;border-radius:10px;padding:14px;box-shadow:0 10px 25px rgba(0,0,0,.18)}
.doc i{display:block;height:8px;border-radius:4px;margin:7px 0;background:linear-gradient(90deg,#a866d1,#f9a4d7);width:0;animation:write 4s infinite}
.doc i:nth-child(2){animation-delay:.5s}.doc i:nth-child(3){animation-delay:1s}.doc i:nth-child(4){animation-delay:1.5s}
@keyframes write{0%{width:0}30%,90%{width:var(--w,90%)}100%{width:0}}
.doc i:nth-child(4){--w:55%}
.spark{position:absolute;font-size:22px;animation:twinkle 1.8s ease-in-out infinite}.s1{left:18%;top:22%}.s2{right:18%;bottom:24%;animation-delay:.9s;font-size:16px}
@keyframes twinkle{50%{transform:scale(1.5) rotate(20deg);opacity:.5}}
@media(prefers-reduced-motion:reduce){.art *{animation:none!important;opacity:1!important;width:auto}}
/* animated logo */
.brand{display:flex;align-items:center;gap:9px}
.mark{display:block;width:38px;height:38px;border-radius:12px;box-shadow:0 6px 16px rgba(220,73,133,.35);animation:markin .8s cubic-bezier(.3,1.6,.5,1) both}
.mark svg{display:block}
.bag{transform-origin:20px 13px;animation:swing 3.2s ease-in-out 1s infinite}
.spk{transform-origin:31px 9px;animation:twinkle2 2.4s ease-in-out infinite}
.brand:hover .bag{animation-duration:.8s}
.word b{background:linear-gradient(90deg,#dc4985,#a866d1,#4354e2,#f18da2,#dc4985);background-size:300% 100%;-webkit-background-clip:text;background-clip:text;color:transparent;animation:flowtxt 6s linear infinite}
@keyframes markin{from{transform:scale(.3) rotate(-25deg);opacity:0}}
@keyframes swing{0%,60%,100%{transform:rotate(0)}70%{transform:rotate(-9deg)}80%{transform:rotate(7deg)}90%{transform:rotate(-3deg)}}
@keyframes twinkle2{0%,100%{transform:scale(.6);opacity:.5}50%{transform:scale(1.15) rotate(45deg);opacity:1}}
@keyframes flowtxt{to{background-position:300% 0}}
.langs a{white-space:nowrap}
@media(max-width:420px){.top .wrap{gap:8px}.brand{gap:6px;font-size:19px}.mark,.mark svg{width:30px;height:30px}.mark{border-radius:9px}.langs{padding:3px}.langs a{padding:4px 7px;font-size:12px}}
@media(prefers-reduced-motion:reduce){.mark,.bag,.spk,.word b{animation:none!important}}
footer{padding:36px 0;color:var(--muted);font-size:14px;text-align:center}
</style>
</head>
<body>
<header class="top"><div class="wrap">
  <a class="brand" href="?lang={{ $locale }}" aria-label="snssale">
    <span class="mark" aria-hidden="true">
      <svg viewBox="0 0 40 40" width="38" height="38">
        <defs><linearGradient id="lg" x1="0" y1="0" x2="1" y2="1">
          <stop offset="0" stop-color="#dc4985"><animate attributeName="stop-color" values="#dc4985;#a866d1;#4354e2;#dc4985" dur="6s" repeatCount="indefinite"/></stop>
          <stop offset="1" stop-color="#f18da2"><animate attributeName="stop-color" values="#f18da2;#f9a4d7;#6ca3f3;#f18da2" dur="6s" repeatCount="indefinite"/></stop>
        </linearGradient></defs>
        <rect width="40" height="40" rx="12" fill="url(#lg)"/>
        <g class="bag" fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
          <path d="M11 15h18l-1.6 15a2 2 0 0 1-2 1.8H14.6a2 2 0 0 1-2-1.8z"/>
          <path d="M15.5 15v-2.5a4.5 4.5 0 0 1 9 0V15"/>
        </g>
        <path class="spk" d="M31 5l1.2 3 3 1.2-3 1.2L31 13.4l-1.2-3-3-1.2 3-1.2z" fill="#fff"/>
      </svg>
    </span>
    <span class="word">sns<b>sale</b></span>
  </a>
  <nav class="menu">
    <a href="#pos">{{ $t['nav']['pos'] }}</a><a href="#features">{{ $t['nav']['features'] }}</a><a href="#demo">{{ $t['nav']['flow'] }}</a><a href="#how">{{ $t['nav']['how'] }}</a>
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

<section id="pos" class="soft"><div class="wrap">
  @php $pd = $t['pos_demo']; $sum = array_sum(array_column($pd['items'], 2)); @endphp
  <div class="title"><h2>{{ $pd['h'] }}</h2><p class="sub">{{ $pd['sub'] }}</p></div>
  <div class="posdemo" id="posdemo">
    <div class="stage" aria-hidden="true">
      <div class="tab">
        <div class="tgrid">
          <div class="tile t1"><img src="/saas/demo-fabric.webp" alt="" width="480" height="480"><span>{{ $pd['items'][0][0] }}</span><b class="bc">▮▯▮▮▯▮▯▮▮</b><i class="beam"></i></div>
          <div class="tile t2 purple"><span>{{ $pd['items'][1][0] }}</span></div>
          <div class="tile blue"></div><div class="tile green"></div>
        </div>
        <div class="tcart">
          <b class="ct">{{ $pd['cart'] }}</b>
          <div class="scanning k0">{{ $pd['scanning'] }}</div>
          @foreach ($pd['items'] as $k => [$name, $qty, $price])
            <div class="row r{{ $k }}"><span>{{ $name }}<small>{{ $qty }}</small></span><b>¥{{ number_format($price) }}</b></div>
          @endforeach
          <div class="tot"><span>{{ $pd['total'] }}</span><b>¥{{ number_format($sum) }}</b></div>
          <div class="paybar">{{ $pd['pay'] }}</div>
        </div>
        <div class="modal">
          <div class="methods">@foreach ($pd['methods'] as $m)<span>{{ $m }}</span>@endforeach</div>
          <div class="ok">✓ {{ $pd['paid'] }} ¥{{ number_format($sum) }}</div>
        </div>
        <div class="hub">
          <div class="core"><small>{{ $pd['stock'] }}</small><b><s>12 m</s> → 10 m</b></div>
          @foreach ([['Web', 'instant'], ['POS', 'instant'], ['Facebook', 'auto'], ['Instagram', 'auto'], ['TikTok', 'auto']] as $k => [$ch, $mode])
            <div class="chn c{{ $k }} {{ $mode }}"><b>{{ $ch }}</b><small>{{ $pd[$mode] }}</small></div>
          @endforeach
          <div class="hubcap">{{ $pd['synced'] }}</div>
        </div>
      </div>
      <div class="printer"><div class="receipt">
        <b>snssale</b>
        @foreach ($pd['items'] as [$name, $qty, $price])<div><span>{{ $name }} {{ $qty }}</span><span>¥{{ number_format($price) }}</span></div>@endforeach
        <div class="rt"><span>{{ $pd['total'] }}</span><span>¥{{ number_format($sum) }}</span></div>
        <p>{{ $pd['thanks'] }}</p><i class="rbc"></i>
      </div></div>
    </div>
    <ol class="rail">
      @foreach ($pd['steps'] as $i => $step)
        <li><button type="button" data-go="{{ $i }}" @if ($i === 0) aria-current="step" @endif><span>{{ $i + 1 }}</span>{{ $step }}</button></li>
      @endforeach
    </ol>
  </div>
</div></section>

<section id="features"><div class="wrap">
  <div class="title"><h2>{{ $t['features_h'] }}</h2></div>
  <div class="grid feats">
    @foreach ($t['features'] as $i => [$h, $p])
      <div class="feat">
        <div class="art art{{ $i }} {{ $tiles[$i % 6] }}" aria-hidden="true">
          @switch($i)
            @case(0)
              <div class="win"><div class="url">yourname.snssale.com</div><div class="prods"><i style="background-image:url(/saas/demo-fabric.webp)"></i><i></i><i></i></div></div>
              @break
            @case(1)
              <div class="mini-tab"><b class="bars">▮▯▮▮▯▮▯▮▮▯▮</b><i class="beam2"></i></div><div class="beep">¥3,190</div>
              @break
            @case(2)
              <div class="card3"><i class="chip"></i><span>•••• 4242</span></div><div class="paypay">PayPay</div><div class="tick">✓</div>
              @break
            @case(3)
              <div class="road"><div class="truck">{!! $icon('truck') !!}</div><i class="pin"></i></div><div class="track">SGW 1234 5678</div>
              @break
            @case(4)
              <div class="nets"><span class="n f">f</span><span class="n ig">ig</span><span class="n tt">tt</span></div><i class="heart h1">♥</i><i class="heart h2">♥</i><i class="heart h3">♥</i>
              @break
            @default
              <div class="doc"><i></i><i></i><i></i><i></i></div><b class="spark s1">✦</b><b class="spark s2">✦</b>
          @endswitch
        </div>
        <div class="fbody"><span class="ficon {{ $tiles[$i % 6] }}">{!! $icon($featureIcons[$i % 6]) !!}</span><h3>{{ $h }}</h3><p>{{ $p }}</p></div>
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
<script>
(function () {
  var root = document.getElementById('posdemo'); if (!root) return;
  var btn = root.querySelectorAll('[data-go]'), n = 0, timer, still = matchMedia('(prefers-reduced-motion: reduce)').matches;
  var hold = [2600, 2600, 3200, 3000, 4600];
  function show(k) {
    n = k; var c = 'posdemo at' + k; for (var i = 0; i <= k; i++) c += ' s' + i; root.className = c;
    btn.forEach(function (b, i) { i === k ? b.setAttribute('aria-current', 'step') : b.removeAttribute('aria-current'); });
    clearTimeout(timer); if (!still) timer = setTimeout(function () { show((n + 1) % btn.length); }, hold[k]);
  }
  btn.forEach(function (b) { b.addEventListener('click', function () { show(+b.getAttribute('data-go')); }); });
  show(still ? 4 : 0);
})();
</script>
</body>
</html>
