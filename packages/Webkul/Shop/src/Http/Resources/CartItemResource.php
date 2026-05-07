<?php

namespace Webkul\Shop\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CartItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'type' => $this->type,
            'name' => $this->name,
            'price' => $this->price,
            'formatted_price' => core()->formatPrice($this->price),
            'price_incl_tax' => $this->price_incl_tax,
            'formatted_price_incl_tax' => core()->formatPrice($this->price_incl_tax),
            'total' => $this->total,
            'formatted_total' => core()->formatPrice($this->total),
            'total_incl_tax' => $this->total_incl_tax,
            'formatted_total_incl_tax' => core()->formatPrice($this->total_incl_tax),
            'discount_amount' => $this->discount_amount,
            'formatted_discount_amount' => core()->formatPrice($this->discount_amount),
            'base_image' => $this->getTypeInstance()->getBaseImage($this),
            'product_url_key' => $this->product->url_key,
            'options' => $this->formatAdditionalAttributes(),
            'can_change_qty' => $this->product ? $this->product->getTypeInstance()->showQuantityBox() : false,
            'available_qty' => $this->getAvailableQty(),
            'temperature_type' => $this->getTemperatureType(),
        ];
    }

    /**
     * Get available inventory quantity for this item's product.
     */
    protected function getAvailableQty(): ?int
    {
        $product = $this->product;

        if (! $product) {
            return null;
        }

        $inventories = $product->inventories;

        if ($inventories->isEmpty()) {
            return null;
        }

        return (int) $inventories->sum('qty');
    }

    protected function getTemperatureType(): string
    {
        $product = $this->product;
        if (! $product) return 'normal';

        try {
            $val = $product->Cool;
            if (! $val) return 'normal';

            static $optionMap = null;
            if ($optionMap === null) {
                $optionMap = [];
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
                    if (str_contains($label, 'cool')) $optionMap[$row->id] = 'cool';
                    elseif (str_contains($label, 'freez') || str_contains($label, 'frozen')) $optionMap[$row->id] = 'frozen';
                    else $optionMap[$row->id] = 'normal';
                }
            }

            $type = 'normal';
            foreach (array_map('intval', explode(',', (string) $val)) as $id) {
                $t = $optionMap[$id] ?? 'normal';
                if ($t === 'frozen') return 'frozen';
                if ($t === 'cool') $type = 'cool';
            }
            return $type;
        } catch (\Exception $e) {
            return 'normal';
        }
    }

    /**
     * Format the additional attributes.
     */
    public function formatAdditionalAttributes(): array
    {
        $attributes = $this->resource->additional['attributes'] ?? [];

        if (! empty($attributes)) {
            return collect($attributes)
                ->map(function ($attribute) {
                    if (
                        isset($attribute['attribute_type'])
                        && $attribute['attribute_type'] == 'file'
                    ) {
                        $attribute['file_name'] = File::basename($attribute['option_label']);

                        $attribute['file_url'] = Storage::url($attribute['option_label']);
                    }

                    return $attribute;
                })
                ->values()
                ->toArray();
        }

        return [];
    }
}
