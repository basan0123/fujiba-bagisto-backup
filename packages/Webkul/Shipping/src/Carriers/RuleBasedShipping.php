<?php

namespace Webkul\Shipping\Carriers;

use Webkul\Checkout\Models\CartShippingRate;
use Webkul\Shipping\Carriers\AbstractShipping;
use Webkul\Shipping\DTOs\CartDTO;
use Webkul\Shipping\DTOs\CartItemDTO;
use Webkul\Shipping\Models\ProductShippingExclusion;
use Webkul\Shipping\Services\ShippingRuleEngine;

class RuleBasedShipping extends AbstractShipping
{
    protected $code = 'rulebased';

    public function calculate()
    {
        if (! $this->isAvailable()) {
            return false;
        }

        $cart   = cart()->getCart();
        $engine = app(ShippingRuleEngine::class);
        $dto    = $this->buildCartDTO($cart);
        $result = $engine->calculate($dto);

        $rate                    = new CartShippingRate();
        $rate->carrier           = $this->code;
        $rate->carrier_title     = $this->getConfigData('title') ?: '送料';
        $rate->method            = $this->code . '_' . $this->code;
        $rate->method_title      = $this->getConfigData('title') ?: '送料';
        $rate->method_description = json_encode([
            'display'       => $this->buildDescription($result->breakdown),
            'breakdown'     => $result->breakdown,
            'cool_fee'      => $result->coolFee,
            'frozen_fee'    => $result->frozenFee,
            'base_shipping' => $result->baseShipping,
        ]);
        $rate->price             = $result->total;
        $rate->base_price        = $result->total;

        return $rate;
    }

    private function buildDescription(array $breakdown): string
    {
        if (empty($breakdown)) return '';
        return implode(' + ', array_map(
            fn($b) => $b['label'] . ' ¥' . number_format((int) $b['amount']),
            array_filter($breakdown, fn($b) => $b['amount'] > 0)
        )) ?: '送料無料';
    }

    private function buildCartDTO($cart): CartDTO
    {
        $channelId      = core()->getCurrentChannel()->id;
        $prefectureCode = $this->getPrefectureCode($cart);

        $exclusions = ProductShippingExclusion::globalOrChannel($channelId)
            ->active()->get();

        $subtotal          = 0;
        $subtotalExcluding = 0;
        $totalWeight       = 0;
        $items             = [];

        foreach ($cart->items as $item) {
            $product    = $item->product;
            $categoryId = optional($product->categories->first())->id ?? 0;
            $rowTotal   = (float) $item->total;
            $weight     = (float) ($product->weight ?? 0) * $item->quantity;

            $tempType = 'normal';
            try {
                $coolAttrVal = $product->Cool;
                if ($coolAttrVal) {
                    $ids = array_map('intval', explode(',', (string) $coolAttrVal));
                    foreach ($ids as $id) {
                        $type = $this->mapCoolOptionToType($id);
                        if ($type === 'frozen') { $tempType = 'frozen'; break; }
                        if ($type === 'cool') { $tempType = 'cool'; }
                    }
                }
            } catch (\Exception $e) {}

            $excludeFromFree = $exclusions->contains(
                fn($e) => (($e->product_id  === $item->product_id) ||
                           ($e->category_id === $categoryId)) &&
                          in_array($e->exclude_type, ['free_threshold', 'all'])
            );

            $excludeFromCool = $exclusions->contains(
                fn($e) => (($e->product_id  === $item->product_id) ||
                           ($e->category_id === $categoryId)) &&
                          in_array($e->exclude_type, ['cool_fee', 'all'])
            );

            $subtotal    += $rowTotal;
            $totalWeight += $weight;

            if (! $excludeFromFree) $subtotalExcluding += $rowTotal;

            $items[] = new CartItemDTO(
                productId:               $item->product_id,
                categoryId:              $categoryId,
                temperatureType:         $tempType,
                quantity:                (int) $item->quantity,
                price:                   (float) $item->price,
                rowTotal:                $rowTotal,
                weight:                  $weight,
                excludeFromFreeShipping: $excludeFromFree,
                excludeFromCoolFee:      $excludeFromCool,
            );
        }

        return new CartDTO(
            cartId:                      $cart->id,
            channelId:                   $channelId,
            prefectureCode:              $prefectureCode,
            subtotal:                    $subtotal,
            subtotalExcludingExclusions: $subtotalExcluding,
            totalWeight:                 round($totalWeight, 3),
            items:                       $items,
            currencyCode:                core()->getCurrentCurrencyCode(),
        );
    }

    private function mapCoolOptionToType($optionId): string
    {
        static $map = null;

        if ($map === null) {
            $map = [];
            $rows = \DB::table('attribute_options')
                ->join('attribute_option_translations', 'attribute_options.id', '=', 'attribute_option_translations.attribute_option_id')
                ->where('attribute_options.attribute_id', function ($q) {
                    $q->select('id')->from('attributes')->where('code', 'Cool')->limit(1);
                })
                ->where('attribute_option_translations.locale', 'en')
                ->select('attribute_options.id', 'attribute_option_translations.label')
                ->get();

            foreach ($rows as $row) {
                $label = strtolower(trim($row->label));
                if (str_contains($label, 'cool')) {
                    $map[$row->id] = 'cool';
                } elseif (str_contains($label, 'freez') || str_contains($label, 'frozen')) {
                    $map[$row->id] = 'frozen';
                } else {
                    $map[$row->id] = 'normal';
                }
            }
        }

        return $map[(int) $optionId] ?? 'normal';
    }

    private function getPrefectureCode($cart): string
    {
        $address = $cart->shipping_address ?? $cart->billing_address;
        if (! $address) return '13';

        $map = [
            '北海道'=>'01','青森県'=>'02','岩手県'=>'03','宮城県'=>'04','秋田県'=>'05',
            '山形県'=>'06','福島県'=>'07','茨城県'=>'08','栃木県'=>'09','群馬県'=>'10',
            '埼玉県'=>'11','千葉県'=>'12','東京都'=>'13','神奈川県'=>'14','新潟県'=>'15',
            '富山県'=>'16','石川県'=>'17','福井県'=>'18','山梨県'=>'19','長野県'=>'20',
            '岐阜県'=>'21','静岡県'=>'22','愛知県'=>'23','三重県'=>'24','滋賀県'=>'25',
            '京都府'=>'26','大阪府'=>'27','兵庫県'=>'28','奈良県'=>'29','和歌山県'=>'30',
            '鳥取県'=>'31','島根県'=>'32','岡山県'=>'33','広島県'=>'34','山口県'=>'35',
            '徳島県'=>'36','香川県'=>'37','愛媛県'=>'38','高知県'=>'39','福岡県'=>'40',
            '佐賀県'=>'41','長崎県'=>'42','熊本県'=>'43','大分県'=>'44','宮崎県'=>'45',
            '鹿児島県'=>'46','沖縄県'=>'47',
        ];

        $state = $address->state ?? $address->city ?? '';
        return $map[$state] ?? '13';
    }
}
