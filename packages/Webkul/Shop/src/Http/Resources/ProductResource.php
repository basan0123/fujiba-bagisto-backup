<?php

namespace Webkul\Shop\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Product\Helpers\Review;

class ProductResource extends JsonResource
{
    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @return void
     */
    public function __construct($resource)
    {
        $this->reviewHelper = app(Review::class);

        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $productTypeInstance = $this->getTypeInstance();

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'url_key' => $this->url_key,
            'base_image' => product_image()->getProductBaseImage($this),
            'images' => product_image()->getGalleryImages($this),
            'is_new' => (bool) $this->new,
            'is_featured' => (bool) $this->featured,
            'on_sale' => (bool) $productTypeInstance->haveDiscount(),
            'is_saleable' => (bool) $productTypeInstance->isSaleable(),
            // Remaining stock for the storefront's qty stepper to cap at.
            // null = unmanaged → no cap. cart_qty is what's already in
            // this customer's cart for this product (so the storefront can
            // disable the +/Add button at cap_qty − cart_qty).
            'available_qty' => $this->resolveAvailableQty(),
            'cart_qty'      => $this->resolveCartQty(),
            'is_wishlist' => (bool) auth()->guard()->user()?->wishlist_items
                ->where('channel_id', core()->getCurrentChannel()->id)
                ->where('product_id', $this->id)->count(),
            'min_price' => core()->formatPrice($productTypeInstance->getMinimalPrice()),
            'prices' => $productTypeInstance->getProductPrices(),
            'price_html' => $productTypeInstance->getPriceHtml(),
            'ratings' => [
                'average' => $this->reviewHelper->getAverageRating($this),
                'total' => $this->reviewHelper->getTotalRating($this),
            ],
            'reviews' => [
                'total' => $this->reviewHelper->getTotalReviews($this),
            ],
        ];
    }

    /**
     * Remaining stock for the storefront's qty stepper. null = unmanaged
     * (no cap). 0 = managed but out of stock.
     */
    private function resolveAvailableQty(): ?int
    {
        try {
            $channelId = (int) core()->getCurrentChannel()->id;
            // Prefer the current channel's index row. If host→channel
            // resolution fell back to default but the product has stock in
            // any other channel, surface the max so the listing matches
            // Bagisto's own haveSufficientQuantity() check (which sums all
            // inventory rows). Avoids OOS-disabled buttons on alias hosts.
            $indices = $this->inventory_indices;
            if ($indices && $indices->count()) {
                $byChannel = $indices->firstWhere('channel_id', $channelId);
                if ($byChannel && (int) $byChannel->qty > 0) {
                    return (int) $byChannel->qty;
                }
                $maxQty = (int) $indices->max('qty');
                if ($maxQty > 0) {
                    return $maxQty;
                }
                return 0;
            }
            if ($this->manage_stock) {
                return 0;
            }
        } catch (\Throwable) {}

        return null;
    }

    /**
     * How much of this product is already in the current cart. Used by
     * the storefront to compute remaining add-allowance: max(0, available_qty - cart_qty).
     */
    private function resolveCartQty(): int
    {
        try {
            $cart = \Webkul\Checkout\Facades\Cart::getCart();
            if (! $cart || ! $cart->items) return 0;
            $sum = 0;
            foreach ($cart->items as $item) {
                if ((int) $item->product_id === (int) $this->id) {
                    $sum += (int) $item->quantity;
                }
            }
            return $sum;
        } catch (\Throwable) {
            return 0;
        }
    }
}
