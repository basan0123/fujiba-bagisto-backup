{{-- Fujiba: kimono home FAQ — questions are the top Messenger topics (price/stock/delivery/payment/photos). Also emitted as FAQPage JSON-LD. --}}
@php
    $faqs = [
        'ja' => [
            ['ロンジー・ルンギ用の生地ですか？', 'はい。ミャンマーのロンジー・タメイン、ネパール・ブータンのルンギを仕立てるお客様向けの着物生地です。必要な長さはMessengerでご相談ください。'],
            ['着物生地の値段はいくらですか？', 'カット済みの着物生地は¥1,595・¥1,900・¥2,000・¥2,500の価格帯があります。11個選ぶと1個無料（実質10個分）です。'],
            ['在庫はありますか？', '商品ページに表示されている商品はすべて在庫ありです。売り切れた柄は自動で非表示になります。'],
            ['いつ届きますか？送料は？', 'ご入金確認後24時間以内に発送し、日本国内なら通常1〜2日でお届けします。'],
            ['支払い方法は？', 'PayPay・クレジットカードでお支払いいただけます。'],
            ['柄や色の実物写真を見たいです', '各商品ページに写真を掲載しています。追加の写真やサイズの相談はMessengerでお気軽にどうぞ。'],
            ['卸売・まとめ買いはできますか？', 'はい、卸売に対応しています。会員登録後、Messengerでご相談ください。'],
        ],
        'en' => [
            ['Is this fabric for longyi and lungi?', 'Yes — our kimono fabric is made for sewing Myanmar longyi & htamein and Nepali / Bhutanese lungi (लुंगी). Tell us on Messenger the length you need and we will suggest the right cut.'],
            ['How much is the kimono fabric?', 'Pre-cut kimono fabric comes in ¥1,595, ¥1,900, ¥2,000 and ¥2,500 price bands. Buy 11 and get 1 free.'],
            ['Is it in stock?', 'Everything shown on the site is in stock. Sold-out patterns are hidden automatically.'],
            ['When will it arrive?', 'We ship within 24 hours of payment; delivery inside Japan usually takes 1–2 days.'],
            ['How can I pay?', 'You can pay by PayPay or credit card.'],
            ['Can I see more photos of a pattern?', 'Every product page has photos. For extra photos or size questions, message us on Messenger.'],
            ['Do you sell wholesale?', 'Yes. Register an account and contact us on Messenger for wholesale.'],
        ],
        'my' => [
            ['ကီမိုနိုလုံချည်၊ ထဘီ ချုပ်လို့ရလား?', 'ရပါတယ်။ ကီမိုနိုလုံချည်၊ ကီမိုနိုထဘီ ချုပ်ဖို့ ပိတ်စများပါ။ လိုချင်တဲ့ အရှည်ကို Messenger ကနေ မေးနိုင်ပါတယ်။'],
            ['ကီမိုနိုပိတ်စ ဈေးနှုန်း ဘယ်လောက်လဲ?', 'ဖြတ်ပြီးသား ပိတ်စများ ¥1,595၊ ¥1,900၊ ¥2,000၊ ¥2,500 ဈေးအဆင့်များ ရှိပါတယ်။ ၁၁ ခုယူရင် ၁ ခု အလကားပါ။'],
            ['ပစ္စည်း ရှိသေးလား?', 'ဆိုက်ပေါ်မှာ ပြထားတာ အားလုံး စတော့ရှိပါတယ်။ ကုန်သွားတဲ့ ဒီဇိုင်းတွေ အလိုအလျောက် ဖျောက်ထားပါတယ်။'],
            ['ဘယ်တော့ ရောက်မလဲ?', 'ငွေချေပြီး ၂၄ နာရီအတွင်း ပို့ပေးပြီး ဂျပန်နိုင်ငံအတွင်း ၁–၂ ရက်အတွင်း ရောက်ပါတယ်။'],
            ['ဘယ်လို ငွေချေရမလဲ?', 'PayPay နဲ့ ခရက်ဒစ်ကတ် နဲ့ ချေနိုင်ပါတယ်။'],
            ['ဓာတ်ပုံ ထပ်ကြည့်ချင်ပါတယ်', 'ပစ္စည်းတိုင်းမှာ ဓာတ်ပုံ ပါပါတယ်။ ထပ်ကြည့်ချင်ရင် Messenger ကနေ မေးနိုင်ပါတယ်။'],
            ['လက်ကား ရလား?', 'ရပါတယ်။ အကောင့်ဖွင့်ပြီး Messenger ကနေ ဆက်သွယ်ပါ။'],
        ],
    ];
    $items = $faqs[app()->getLocale()] ?? $faqs['en'];
    $heading = ['ja' => 'よくあるご質問', 'my' => 'မေးလေ့ရှိသော မေးခွန်းများ'][app()->getLocale()] ?? 'Frequently asked questions';
    $schema = [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => array_map(fn ($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]], $items),
    ];
@endphp

<section class="container mt-14 max-lg:px-8 max-sm:px-4" aria-labelledby="kimono-faq">
    <h2 id="kimono-faq" class="mb-5 font-dmserif text-3xl max-sm:text-xl">{{ $heading }}</h2>
    @foreach ($items as [$q, $a])
        <details class="border-b border-zinc-200 py-3">
            <summary class="cursor-pointer font-medium">{{ $q }}</summary>
            <p class="mt-2 text-zinc-600">{{ $a }}</p>
        </details>
    @endforeach
</section>

@push('scripts')
<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush
