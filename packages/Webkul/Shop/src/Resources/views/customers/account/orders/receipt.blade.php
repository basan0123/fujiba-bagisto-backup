@php
    $sortedItems = $order->items->sortByDesc(fn ($i) => (int) $i->qty_ordered)->values();
    $totalQty = (int) $order->items->sum('qty_ordered');
    $isCod = optional($order->payment)->method === 'cashondelivery';
    $displayInclTax = core()->getConfigData('sales.taxes.sales.display_prices') === 'including_tax';

    $codFee = (float) ($order->cod_fee ?? 0);
    $shippingAmount = (float) ($order->shipping_amount_incl_tax ?? $order->shipping_amount ?? 0);
    $discount = (float) ($order->discount_amount ?? 0);
    $tax = (float) ($order->tax_amount ?? 0);
    $subTotal = $displayInclTax
        ? (float) ($order->sub_total_incl_tax ?? $order->sub_total ?? 0)
        : (float) ($order->sub_total ?? 0);
    $grandTotal = (float) ($order->grand_total ?? 0);
    $currency = $order->order_currency_code;
    $paymentTitle = core()->getConfigData('sales.payment_methods.' . optional($order->payment)->method . '.title')
        ?: optional($order->payment)->method;
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>レシート — Order #{{ $order->increment_id }}</title>
    <style>
        *,*::before,*::after{box-sizing:border-box}
        body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Hiragino Sans","Noto Sans JP",sans-serif;margin:0;background:#f4f4f5;color:#111827;-webkit-font-smoothing:antialiased}
        .wrap{max-width:760px;margin:0 auto;padding:16px}
        .card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;margin-bottom:14px}
        .head{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:16px 20px;border-bottom:1px solid #e5e7eb;background:#fafafa}
        .head h1{font-size:18px;margin:0;font-weight:700}
        .head .meta{font-size:12px;color:#6b7280}
        .actions{display:flex;gap:8px}
        .btn{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:8px;font-size:13px;font-weight:600;border:1px solid #d4d4d8;background:#fff;color:#111;cursor:pointer;text-decoration:none}
        .btn.primary{background:#111827;color:#fff;border-color:#111827}
        .summary{display:flex;justify-content:space-between;align-items:center;padding:10px 20px;font-size:13px;color:#374151;background:#f9fafb;border-bottom:1px solid #e5e7eb}
        .summary b{color:#111}
        .row{display:flex;align-items:flex-start;gap:14px;padding:14px 20px;border-bottom:1px solid #f1f1f4}
        .row:last-child{border-bottom:0}
        .img{flex:0 0 72px;width:72px;height:72px;border-radius:8px;border:1px solid #e5e7eb;background:#fafafa;object-fit:cover}
        .img.placeholder{display:flex;align-items:center;justify-content:center;color:#9ca3af;font-size:11px;font-weight:600}
        .info{flex:1;min-width:0}
        .name{font-size:15px;font-weight:600;line-height:1.35;color:#111;word-break:break-word}
        .sku{font-size:11px;color:#6b7280;margin-top:4px;letter-spacing:.02em}
        .price{font-size:13px;color:#111;margin-top:4px;font-weight:600}
        .qcol{flex:0 0 auto;text-align:right;min-width:96px}
        .qty{font-size:22px;font-weight:700;color:#111}
        .qty.big{color:#dc2626;font-size:30px}
        .qty small{display:block;font-size:11px;color:#6b7280;font-weight:500;margin-top:2px}
        .subtotal{font-size:13px;font-weight:700;color:#111;margin-top:6px;border-top:1px dashed #e5e7eb;padding-top:6px}
        .icon{font-size:13px;margin-right:2px}
        .empty{padding:32px 20px;text-align:center;color:#6b7280;font-size:14px}

        .totals{padding:14px 20px}
        .totals .line{display:flex;justify-content:space-between;align-items:center;padding:6px 0;font-size:14px;color:#374151}
        .totals .line.muted{color:#6b7280;font-size:13px}
        .totals .line .neg{color:#16a34a;font-weight:600}
        .totals .grand{margin-top:8px;padding-top:10px;border-top:2px solid #111;font-size:18px;font-weight:800;color:#111}
        .totals .grand .amt{color:#111}

        .meta-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;padding:14px 20px}
        .meta-grid .block h3{font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin:0 0 6px}
        .meta-grid .block p{margin:0;font-size:13px;line-height:1.55;color:#111}

        .cod-banner{display:flex;align-items:center;gap:10px;padding:14px 20px;background:#fff7ed;border:2px solid #ea580c;border-radius:10px;margin-bottom:14px;font-weight:700;color:#9a3412}
        .cod-banner .badge{background:#ea580c;color:#fff;padding:4px 10px;border-radius:999px;font-size:12px;letter-spacing:.05em}
        .cod-banner .amt{margin-left:auto;font-size:16px;color:#9a3412}
        .cod-card{border:2px solid #ea580c !important;background:#fff7ed !important}
        .cod-card .head{background:#ffedd5}

        @media (max-width:480px){
            .wrap{padding:8px}
            .head{padding:12px 14px;flex-wrap:wrap}
            .head h1{font-size:16px}
            .row{padding:12px 14px;gap:10px}
            .img{flex:0 0 60px;width:60px;height:60px}
            .name{font-size:14px}
            .qty{font-size:18px}
            .qty.big{font-size:24px}
            .qcol{min-width:80px}
            .meta-grid{grid-template-columns:1fr}
        }
        @media print{
            body{background:#fff}
            .wrap{padding:0;max-width:none}
            .card{border:0;border-radius:0;margin-bottom:0;border-top:1px solid #ccc}
            .actions,.no-print{display:none !important}
            .row{break-inside:avoid;page-break-inside:avoid}
            .head,.summary{background:#fff}
            .cod-banner{border-radius:0;background:#fff !important;border:2px solid #000 !important;color:#000 !important}
            .cod-banner .badge{background:#000 !important}
            .cod-card{background:#fff !important;border:2px solid #000 !important}
        }
    </style>
</head>
<body>
    <div class="wrap">
        @if ($isCod)
            <div class="cod-banner">
                <span class="badge">COD</span>
                <span>代金引換 — お届け時に現金でお支払い</span>
                <span class="amt">{{ core()->formatPrice($grandTotal, $currency) }}</span>
            </div>
        @endif

        <div class="card">
            <div class="head">
                <div>
                    <h1>レシート — Order #{{ $order->increment_id }}</h1>
                    <div class="meta">{{ core()->formatDate($order->created_at, 'd M Y H:i') }}</div>
                </div>
                <div class="actions no-print">
                    <a href="{{ route('shop.customers.account.orders.view', $order->id) }}" class="btn">← 戻る</a>
                    <button type="button" class="btn primary" onclick="window.print()">🖨 印刷</button>
                </div>
            </div>

            <div class="summary">
                <span>商品 <b>{{ $order->items->count() }}</b> 種類</span>
                <span>合計 <b>{{ $totalQty }}</b> 点</span>
            </div>

            @forelse ($sortedItems as $item)
                @php
                    $itemImage = $item->product ? product_image()->getProductImage($item) : null;
                    $itemImageUrl = $itemImage['small_image_url'] ?? ($itemImage['medium_image_url'] ?? null);
                    $qty = (int) $item->qty_ordered;
                    $unit = $displayInclTax ? (float) $item->price_incl_tax : (float) $item->price;
                    $line = $displayInclTax ? (float) $item->total_incl_tax : (float) $item->total;

                    $tempType = 'normal';
                    if ($item->product) {
                        try {
                            $coolVal = $item->product->Cool;
                            if ($coolVal) {
                                $coolOptMap = cache()->remember('cool_option_map', 300, function () {
                                    $map = [];
                                    $rows = \DB::table('attribute_options')
                                        ->join('attribute_option_translations', 'attribute_options.id', '=', 'attribute_option_translations.attribute_option_id')
                                        ->where('attribute_options.attribute_id', function ($q) { $q->select('id')->from('attributes')->where('code', 'Cool')->limit(1); })
                                        ->where('attribute_option_translations.locale', 'en')
                                        ->select('attribute_options.id', 'attribute_option_translations.label')->get();
                                    foreach ($rows as $r) {
                                        $l = strtolower(trim($r->label));
                                        if (str_contains($l, 'cool')) $map[$r->id] = 'cool';
                                        elseif (str_contains($l, 'freez') || str_contains($l, 'frozen')) $map[$r->id] = 'frozen';
                                        else $map[$r->id] = 'normal';
                                    }
                                    return $map;
                                });
                                foreach (array_map('intval', explode(',', (string) $coolVal)) as $cid) {
                                    $ct = $coolOptMap[$cid] ?? 'normal';
                                    if ($ct === 'frozen') { $tempType = 'frozen'; break; }
                                    if ($ct === 'cool') $tempType = 'cool';
                                }
                            }
                        } catch (\Exception $e) {}
                    }
                @endphp
                <div class="row">
                    @if ($itemImageUrl)
                        <img class="img" src="{{ $itemImageUrl }}" alt="" loading="lazy">
                    @else
                        <div class="img placeholder">no image</div>
                    @endif

                    <div class="info">
                        <div class="name">
                            @if($tempType === 'cool')<span class="icon" title="クール便">❄️</span>@endif
                            @if($tempType === 'frozen')<span class="icon" title="冷凍便">⚠️❄️</span>@endif
                            {{ $item->name }}
                        </div>
                        <div class="sku">SKU: {{ $item->product ? $item->getTypeInstance()->getOrderedItem($item)->sku : $item->sku }}</div>
                        <div class="price">{{ core()->formatPrice($unit, $currency) }}</div>
                    </div>

                    <div class="qcol">
                        <div class="qty {{ $qty > 1 ? 'big' : '' }}">×{{ $qty }}<small>個</small></div>
                        <div class="subtotal">{{ core()->formatPrice($line, $currency) }}</div>
                    </div>
                </div>
            @empty
                <div class="empty">商品がありません</div>
            @endforelse
        </div>

        <div class="card {{ $isCod ? 'cod-card' : '' }}">
            <div class="head">
                <h1>お支払い詳細 / Totals</h1>
            </div>
            <div class="totals">
                <div class="line">
                    <span>小計 / Subtotal</span>
                    <span>{{ core()->formatPrice($subTotal, $currency) }}</span>
                </div>

                @if ($shippingAmount > 0)
                    <div class="line">
                        <span>配送料 / Shipping</span>
                        <span>{{ core()->formatPrice($shippingAmount, $currency) }}</span>
                    </div>
                @endif

                @if ($codFee > 0)
                    <div class="line">
                        <span>代引手数料 / COD Fee</span>
                        <span>{{ core()->formatPrice($codFee, $currency) }}</span>
                    </div>
                @endif

                @if ($tax > 0)
                    <div class="line muted">
                        <span>消費税 / Tax</span>
                        <span>{{ core()->formatPrice($tax, $currency) }}</span>
                    </div>
                @endif

                @if ($discount > 0)
                    <div class="line">
                        <span>割引 / Discount @if ($order->coupon_code) <span class="muted">({{ $order->coupon_code }})</span> @endif</span>
                        <span class="neg">-{{ core()->formatPrice($discount, $currency) }}</span>
                    </div>
                @endif

                <div class="line grand">
                    <span>合計 / Grand Total</span>
                    <span class="amt">{{ core()->formatPrice($grandTotal, $currency) }}</span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="head">
                <h1>配送・お支払い / Shipping & Payment</h1>
            </div>
            <div class="meta-grid">
                @if ($order->shipping_address)
                    <div class="block">
                        <h3>配送先 / Shipping Address</h3>
                        <p>
                            @if ($order->shipping_address->company_name)
                                <b>{{ $order->shipping_address->company_name }}</b><br>
                            @endif
                            {{ $order->shipping_address->name }}<br>
                            〒{{ $order->shipping_address->postcode }}<br>
                            {{ $order->shipping_address->state }} {{ $order->shipping_address->city }}<br>
                            {{ $order->shipping_address->address }}<br>
                            TEL: {{ $order->shipping_address->phone }}
                        </p>
                    </div>
                @endif

                <div class="block">
                    <h3>配送方法 / Shipping Method</h3>
                    <p>{{ $order->shipping_title ?: '—' }}</p>

                    <h3 style="margin-top:12px">お支払い / Payment Method</h3>
                    <p>
                        {{ $paymentTitle }}
                        @if ($isCod)
                            <br><span style="color:#ea580c;font-weight:700">⚠ 代金引換 (COD)</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
