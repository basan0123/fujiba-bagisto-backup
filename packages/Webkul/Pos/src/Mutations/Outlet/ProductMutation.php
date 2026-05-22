<?php

namespace Webkul\Pos\Mutations\Outlet;

use App\Http\Controllers\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Core\Rules\Slug;
use Webkul\GraphQLAPI\Validators\CustomException;
use Webkul\Pos\Helpers\BarcodeNormalizer;
use Webkul\Pos\Helpers\ImageUploader;
use Webkul\Pos\Helpers\RoleGate;
use Webkul\Pos\Repositories\OutletProductRepository;
use Webkul\Pos\Repositories\ProductBarcodeRepository;
use Webkul\Pos\Repositories\ProductRequestRepository;
use Webkul\Product\Repositories\ProductImageRepository;
use Webkul\Product\Repositories\ProductRepository;

class ProductMutation extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected OutletProductRepository $outletProductRepository,
        protected ProductBarcodeRepository $productBarcodeRepository,
        protected ProductRequestRepository $productRequestRepository,
        protected ProductRepository $productRepository,
        protected ProductImageRepository $productImageRepository,
    ) {}

    /**
     * Request product qty.
     */
    public function requestProductQty($root, array $args, GraphQLContext $context): array
    {
        $validator = Validator::make($args, [
            'products'                      => 'required|array',
            'products.*'                    => 'required|array',
            'products.*.product_id'         => 'required|integer',
            'products.*.requested_quantity' => 'required|integer',
        ]);

        if ($validator->fails()) {
            throw new CustomException($validator->errors()->first());
        }

        foreach ($args['products'] as $requestedProduct) {
            $product = $this->productRepository->find($requestedProduct['product_id']);

            if (! $product) {
                continue;
            }

            $this->productRequestRepository->updateOrCreate([
                'user_id'        => $context->user->id,
                'product_id'     => $product->id,
                'request_status' => 0,
            ], [
                'user_id'            => $context->user->id,
                'product_id'         => $product->id,
                'requested_quantity' => $requestedProduct['requested_quantity'],
                'comment'            => $requestedProduct['comment'] ?? null,
                'send_status'        => 1,
            ]);
        }

        return [
            'success'  => true,
            'message'  => trans('pos::app.outlet.products.request-success'),
        ];
    }

    /**
     * Bulk update product name and/or price for outlet products.
     */
    public function bulkUpdateProducts($root, array $args, GraphQLContext $context): array
    {
        $validator = Validator::make($args, [
            'products'             => 'required|array|min:1',
            'products.*.id'        => 'required|integer',
            'products.*.name'      => 'sometimes|string|max:255',
            'products.*.price'     => 'sometimes|numeric|min:0',
            'products.*.quantity'  => 'sometimes|integer|min:0',
            'products.*.weight'    => 'sometimes|numeric|min:0',
            'products.*.status'    => 'sometimes|integer|in:0,1',
            'products.*.posStatus' => 'sometimes|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            throw new CustomException($validator->errors()->first());
        }

        $outlet            = $context->user()->outlet;
        $inventorySourceId = $outlet->inventory_source_id;
        $channel           = core()->getDefaultChannel();
        $locale            = core()->getRequestedLocaleCode();
        $updated           = 0;

        foreach ($args['products'] as $item) {
            $productId = (int) $item['id'];

            // Verify this product belongs to the agent's outlet
            $outletProduct = $this->outletProductRepository->findOneWhere([
                'outlet_id'  => $outlet->id,
                'product_id' => $productId,
            ]);

            if (! $outletProduct) {
                continue;
            }

            $data         = [];
            $changed      = false;
            $hasQuantity  = array_key_exists('quantity', $item) && $item['quantity'] !== null;

            if (isset($item['name'])) {
                $data['name']    = $item['name'];
                $data['channel'] = $channel->code;
                $data['locale']  = $locale;
            }

            if (isset($item['price'])) {
                $data['price']   = $item['price'];
                $data['channel'] = $data['channel'] ?? $channel->code;
                $data['locale']  = $data['locale']  ?? $locale;
            }

            if (isset($item['weight'])) {
                $data['weight']  = $item['weight'];
                $data['channel'] = $data['channel'] ?? $channel->code;
                $data['locale']  = $data['locale']  ?? $locale;
            }

            // Website visibility flips products.status (the global Bagisto
            // catalog flag, denormalized into product_flat). Cashier sets
            // 1 = visible on storefront, 0 = hidden everywhere except POS.
            if (array_key_exists('status', $item) && $item['status'] !== null) {
                $data['status']  = (int) $item['status'];
                $data['channel'] = $data['channel'] ?? $channel->code;
                $data['locale']  = $data['locale']  ?? $locale;
            }

            if (! empty($data)) {
                Event::dispatch('catalog.product.update.before', $productId);

                $this->productRepository->update($data, $productId);

                Event::dispatch('catalog.product.update.after', $this->productRepository->find($productId));

                $changed = true;
            }

            if ($hasQuantity && $inventorySourceId) {
                $this->updateInventoryQty($productId, (int) $inventorySourceId, (int) $item['quantity']);
                $changed = true;
            }

            // POS visibility flips pos_outlet_product.status for *this* outlet
            // only. Cashiers can hide a product from the POS grid without
            // affecting the storefront, and vice versa via 'status' above.
            if (array_key_exists('posStatus', $item) && $item['posStatus'] !== null) {
                $outletProduct->update(['status' => (int) $item['posStatus']]);
                $changed = true;
            }

            if ($changed) {
                $updated++;
            }
        }

        return [
            'success' => true,
            'updated' => $updated,
            'message' => trans('pos::app.outlet.products.update-success'),
        ];
    }

    /**
     * Upsert product_inventories qty for a given source.
     */
    private function updateInventoryQty(int $productId, int $inventorySourceId, int $qty): void
    {
        $existing = DB::table('product_inventories')
            ->where('product_id', $productId)
            ->where('inventory_source_id', $inventorySourceId)
            ->first();

        if ($existing) {
            DB::table('product_inventories')
                ->where('id', $existing->id)
                ->update(['qty' => $qty]);
        } else {
            DB::table('product_inventories')->insert([
                'product_id'          => $productId,
                'inventory_source_id' => $inventorySourceId,
                'qty'                 => $qty,
                'vendor_id'           => 0,
            ]);
        }

        // Refresh the channel-level index so the storefront / catalog shows
        // the new stock without waiting for a full reindex job.
        $this->refreshInventoryIndices($productId);
    }

    /**
     * Recompute product_inventory_indices.qty per channel for a product.
     */
    private function refreshInventoryIndices(int $productId): void
    {
        $sourceIds = DB::table('channel_inventory_sources')
            ->select('channel_id', 'inventory_source_id')
            ->get()
            ->groupBy('channel_id');

        foreach ($sourceIds as $channelId => $rows) {
            $sourceList = $rows->pluck('inventory_source_id')->all();

            $totalQty = (int) DB::table('product_inventories')
                ->where('product_id', $productId)
                ->whereIn('inventory_source_id', $sourceList)
                ->sum('qty');

            $existing = DB::table('product_inventory_indices')
                ->where('product_id', $productId)
                ->where('channel_id', $channelId)
                ->first();

            if ($existing) {
                DB::table('product_inventory_indices')
                    ->where('id', $existing->id)
                    ->update([
                        'qty'        => $totalQty,
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('product_inventory_indices')->insert([
                    'product_id' => $productId,
                    'channel_id' => $channelId,
                    'qty'        => $totalQty,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Create a new product.
     *
     * Optionally accepts:
     *   - barcode: links the scanned code to the new product via pos_product_barcode.
     *     If a product already has this barcode (or matches by gtin/sku), we return
     *     that product instead of creating a duplicate (auto-assigning to the outlet
     *     if needed).
     *   - imageBase64 + imageName: stores a single product image.
     */
    public function createProduct($root, array $args, GraphQLContext $context): array
    {
        RoleGate::requireManager($context->user());

        $barcode = isset($args['barcode']) ? trim((string) $args['barcode']) : '';
        if ($barcode !== '') {
            $barcode = BarcodeNormalizer::normalize($barcode) ?: $barcode;
            $args['barcode'] = $barcode;
        }
        $imageBase64 = $args['imageBase64'] ?? null;
        $imageName   = $args['imageName'] ?? null;

        // Detect-or-create: if a barcode is supplied and an existing product
        // already owns it, return that product (and ensure outlet assignment).
        if ($barcode !== '') {
            $existing = $this->findProductByBarcode($barcode);

            if ($existing) {
                $this->ensureOutletAssignment($existing->id, $context->user()->outlet);

                return [
                    'success' => true,
                    'message' => trans('pos::app.outlet.products.barcode-linked'),
                    'product' => $this->productRepository->find($existing->id),
                ];
            }
        }

        $validator = Validator::make($args, [
            'name'     => 'required',
            'sku'      => ['nullable', 'unique:products,sku', new Slug],
            'price'    => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'weight'   => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors'  => json_encode($validator->errors()->get('*')),
            ];
        }

        $args['weight'] = $args['weight'] ?? 0;

        if (empty($args['sku'])) {
            do {
                $sku = Str::upper(uniqid());

                $skuValidator = Validator::make(['sku' => $sku], [
                    'sku' => ['unique:products,sku', new Slug],
                ]);
            } while ($skuValidator->fails());

            $args['sku'] = $sku;
        }

        $args = array_merge($args, [
            'type'                => 'simple',
            'attribute_family_id' => $this->attributeFamilyRepository->first()->id,
        ]);

        Event::dispatch('catalog.product.create.before');

        $product = $this->productRepository->create(Arr::only($args, [
            'sku',
            'name',
            'type',
            'attribute_family_id',
        ]));

        Event::dispatch('catalog.product.create.after', $product);

        $outlet = $context->user()->outlet;

        $this->outletProductRepository->create([
            'outlet_id'  => $outlet->id,
            'product_id' => $product->id,
            'status'     => 1,
        ]);

        // Resolve the agent's OWN channel — a quick-created product must be
        // visible in the outlet the cashier is logged into. Falling back to
        // core()->getDefaultChannel() put every mmfood quick-create on the
        // kimono channel (default), so it never appeared in the mmfood POS.
        // Prefer pos_outlets.channel_id (added 2026-05-18, guard for older
        // clones), then the source→channel map, then the default channel.
        $outletChannelId = property_exists($outlet, 'channel_id') && ! empty($outlet->channel_id)
            ? (int) $outlet->channel_id
            : (int) (DB::table('channel_inventory_sources')
                ->where('inventory_source_id', $outlet->inventory_source_id)
                ->value('channel_id') ?? 0);

        $channel = $outletChannelId
            ? (core()->getAllChannels()->firstWhere('id', $outletChannelId) ?? core()->getDefaultChannel())
            : core()->getDefaultChannel();

        // Optional category from the POS quick-create form. Only attach when
        // the category actually exists (and isn't the root) to avoid FK errors.
        $categoryId = isset($args['categoryId']) ? (int) $args['categoryId'] : 0;
        $categoryIds = [];
        if ($categoryId > 0 && DB::table('categories')->where('id', $categoryId)->exists()) {
            $categoryIds = [$categoryId];
        }

        $data = array_merge($args, [
            'channel'           => $channel->code,
            'locale'            => core()->getRequestedLocaleCode(),
            'sku'               => $product->sku,
            'name'              => $args['name'],
            'url_key'           => Str::kebab($args['name']),
            'short_description' => "<p>{$args['name']}</p>",
            'description'       => "<p>{$args['name']}</p>",
            'price'             => $args['price'],
            'weight'            => $args['weight'],
            'status'            => 1,
            'manage_stock'      => 1,
            'inventories'       => [$outlet->inventory_source_id => $args['quantity']],
            'channels'          => [$channel->id],
            'categories'        => $categoryIds,
        ]);

        Event::dispatch('catalog.product.update.before', $product->id);

        $product = $this->productRepository->update($data, $product->id);

        Event::dispatch('catalog.product.update.after', $product);

        // Optional image — base64-encoded payload from the POS quick-create form.
        if ($imageBase64) {
            $this->storeBase64Image($product, $imageBase64, $imageName);
        }

        // Optional barcode — link the scanned code to the freshly created product.
        if ($barcode !== '') {
            try {
                $this->productBarcodeRepository->generateBarcode(
                    $product->id,
                    null,
                    $barcode,
                    'gtin',
                );
            } catch (\Throwable $e) {
                // Barcode link is non-fatal — the product still exists.
            }
        }

        $product = $this->productRepository->find($product->id);

        $product->quantity = $args['quantity'];

        return [
            'success' => true,
            'message' => trans('pos::app.outlet.products.create-success'),
            'product' => $product,
        ];
    }

    /**
     * Look up a product whose barcode/gtin/scan_value or sku matches the
     * scanned code. Returns null if nothing matches.
     */
    private function findProductByBarcode(string $barcode)
    {
        $candidates = array_values(array_unique(array_filter([
            $barcode,
            BarcodeNormalizer::normalize($barcode),
        ], fn ($v) => $v !== '')));

        $hit = DB::table('pos_product_barcode')
            ->where(function ($w) use ($candidates) {
                foreach ($candidates as $c) {
                    $w->orWhere('scan_value', $c)
                        ->orWhere('gtin', $c)
                        ->orWhere('barcode', $c);
                }
            })
            ->first();

        if ($hit) {
            return DB::table('products')->where('id', $hit->product_id)->first();
        }

        foreach ($candidates as $c) {
            if ($p = DB::table('products')->where('sku', $c)->first()) {
                return $p;
            }
        }

        return null;
    }

    /**
     * Make sure the given product is in pos_outlet_product for the agent's outlet.
     */
    private function ensureOutletAssignment(int $productId, $outlet): void
    {
        if (! $outlet) {
            return;
        }

        $exists = $this->outletProductRepository->findOneWhere([
            'outlet_id'  => $outlet->id,
            'product_id' => $productId,
        ]);

        if (! $exists) {
            $this->outletProductRepository->create([
                'outlet_id'  => $outlet->id,
                'product_id' => $productId,
                'status'     => 1,
            ]);
        }
    }

    /**
     * Decode a data-URI / raw base64 string and store it as the product's
     * primary image. Mirrors ProductMediaRepository::upload but without the
     * UploadedFile/HTTP plumbing so it works through GraphQL.
     *
     * Always resizes the longest edge to 800px and re-encodes to webp so
     * uploads from a phone camera don't bloat storage / network.
     */
    private function storeBase64Image($product, string $payload, ?string $originalName = null): ?string
    {
        // Strip "data:image/png;base64," prefix if present.
        if (preg_match('/^data:[^;]+;base64,(.+)$/', $payload, $m)) {
            $payload = $m[1];
        }

        $binary = base64_decode($payload, true);

        if ($binary === false || $binary === '') {
            return null;
        }

        try {
            $manager = new ImageManager;
            $image   = $manager->make($binary);

            // Resize longest edge to 800px, preserve aspect, never upscale.
            $image->resize(800, 800, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            $encoded = $image->encode('webp', 82);

            $directory = 'product/'.$product->id;
            $path      = $directory.'/'.Str::random(40).'.webp';

            Storage::put($path, (string) $encoded);

            // Position right after any existing image — caller decides whether
            // to delete the prior ones.
            $position = (int) ($product->images()->max('position') ?? 0) + 1;

            $this->productImageRepository->create([
                'type'       => 'images',
                'path'       => $path,
                'product_id' => $product->id,
                'position'   => $position,
            ]);

            return $path;
        } catch (\Throwable $e) {
            // Image is optional — never block product creation on a bad upload.
            return null;
        }
    }

    /**
     * Replace the product's primary image with a new base64 upload.
     *
     * Used by ProductEdit "Add image" / "Change image" — wipes existing
     * images first so the new one becomes position-1 / the displayed thumb.
     */
    public function setProductImage($root, array $args, GraphQLContext $context): array
    {
        $validator = Validator::make($args, [
            'productId'   => 'required|integer|exists:products,id',
            'imageBase64' => 'required|string',
        ]);

        if ($validator->fails()) {
            throw new CustomException($validator->errors()->first());
        }

        $productId = (int) $args['productId'];

        $outlet = $context->user()->outlet;

        $outletProduct = $this->outletProductRepository->findOneWhere([
            'outlet_id'  => $outlet->id,
            'product_id' => $productId,
        ]);

        if (! $outletProduct) {
            throw new CustomException(trans('pos::app.outlet.products.not-in-outlet'));
        }

        $product = $this->productRepository->find($productId);

        // Wipe existing images (storage + DB) so the new upload is the only one.
        foreach ($product->images as $img) {
            try {
                Storage::delete($img->path);
            } catch (\Throwable $e) {}

            $this->productImageRepository->delete($img->id);
        }

        $path = $this->storeBase64Image(
            $product,
            (string) $args['imageBase64'],
            $args['imageName'] ?? null,
        );

        if (! $path) {
            return [
                'success' => false,
                'message' => trans('pos::app.outlet.products.image-upload-failed'),
            ];
        }

        // Reload so images relation reflects the new row.
        $product = $this->productRepository->find($productId);

        return [
            'success' => true,
            'message' => trans('pos::app.outlet.products.image-updated'),
            'product' => $product,
        ];
    }

    /**
     * Delete every image for a product.
     */
    public function deleteProductImage($root, array $args, GraphQLContext $context): array
    {
        $validator = Validator::make($args, [
            'productId' => 'required|integer|exists:products,id',
        ]);

        if ($validator->fails()) {
            throw new CustomException($validator->errors()->first());
        }

        $productId = (int) $args['productId'];

        $outlet = $context->user()->outlet;

        $outletProduct = $this->outletProductRepository->findOneWhere([
            'outlet_id'  => $outlet->id,
            'product_id' => $productId,
        ]);

        if (! $outletProduct) {
            throw new CustomException(trans('pos::app.outlet.products.not-in-outlet'));
        }

        $product = $this->productRepository->find($productId);

        foreach ($product->images as $img) {
            try {
                Storage::delete($img->path);
            } catch (\Throwable $e) {}

            $this->productImageRepository->delete($img->id);
        }

        $product = $this->productRepository->find($productId);

        return [
            'success' => true,
            'message' => trans('pos::app.outlet.products.image-removed'),
            'product' => $product,
        ];
    }

    /**
     * Link a scanned barcode/GTIN to an existing product.
     *
     * Used when the cashier scans an unknown barcode and picks the matching
     * product from a list — we persist the value so future scans resolve
     * automatically. The persistence layer also recomputes scan_value and
     * regenerates the printable barcode image.
     */
    public function linkBarcode($root, array $args, GraphQLContext $context): array
    {
        $validator = Validator::make($args, [
            'productId' => 'required|integer|exists:products,id',
            'barcode'   => 'required|string|min:3|max:64',
        ]);

        if ($validator->fails()) {
            throw new CustomException($validator->errors()->first());
        }

        $productId = (int) $args['productId'];
        $barcode   = BarcodeNormalizer::normalize((string) $args['barcode']) ?: trim((string) $args['barcode']);

        $outlet = $context->user()->outlet;

        $outletProduct = $this->outletProductRepository->findOneWhere([
            'outlet_id'  => $outlet->id,
            'product_id' => $productId,
        ]);

        if (! $outletProduct) {
            throw new CustomException(trans('pos::app.outlet.products.not-in-outlet'));
        }

        $this->productBarcodeRepository->generateBarcode(
            $productId,
            null,
            $barcode,
            'gtin',
        );

        $product = $this->productRepository->find($productId);

        return [
            'success'   => true,
            'message'   => trans('pos::app.outlet.products.barcode-linked'),
            'productId' => $productId,
            'gtin'      => $barcode,
            'scanValue' => $barcode,
        ];
    }

    /**
     * Merchant-management: update a product. Manager+owner only.
     *
     * Accepts the same shape as createProduct, plus optional taxCategoryId
     * + status + description + imageBase64. Only fields actually present in
     * the input are touched; everything else is preserved.
     */
    public function updateProduct($root, array $args, GraphQLContext $context): array
    {
        RoleGate::requireManager($context->user());

        $validator = Validator::make($args, [
            'id'             => 'required|integer',
            'name'           => 'sometimes|required|string|max:190',
            'price'          => 'sometimes|required|numeric|min:0',
            'cost'           => 'sometimes|nullable|numeric|min:0',
            'quantity'       => 'sometimes|required|integer|min:0',
            'weight'         => 'sometimes|nullable|numeric|min:0',
            'taxCategoryId'  => 'sometimes|nullable|integer',
            'description'    => 'sometimes|nullable|string|max:5000',
            'shortDescription' => 'sometimes|nullable|string|max:1000',
            'status'         => 'sometimes|boolean',
            'imageBase64'    => 'sometimes|nullable|string',
            'imageName'      => 'sometimes|nullable|string|max:190',
        ]);

        if ($validator->fails()) {
            throw new CustomException($validator->errors()->first());
        }

        $outlet = $context->user()->outlet;

        // Authorize: product must belong to this outlet.
        $belongs = DB::table('pos_outlet_product')
            ->where('outlet_id', $outlet->id)
            ->where('product_id', $args['id'])
            ->exists();
        if (! $belongs) {
            throw new CustomException(trans('pos::app.outlet.product.not-found'));
        }

        $product = $this->productRepository->find($args['id']);
        if (! $product) {
            throw new CustomException(trans('pos::app.outlet.product.not-found'));
        }

        $defaultChannel = core()->getDefaultChannel();

        $data = [
            'channel'  => $defaultChannel->code,
            'locale'   => core()->getRequestedLocaleCode(),
            'sku'      => $product->sku,
            'channels' => [$defaultChannel->id],
        ];

        // Only fields the caller sent are forwarded. `cost` is a normal
        // product attribute (code=cost), so it rides along in the update data.
        foreach (['name', 'price', 'cost', 'weight', 'description', 'shortDescription', 'status'] as $k) {
            if (array_key_exists($k, $args)) {
                $mapped = match ($k) {
                    'shortDescription' => 'short_description',
                    default => $k,
                };
                $data[$mapped] = $args[$k];
            }
        }

        if (array_key_exists('taxCategoryId', $args)) {
            $data['tax_category_id'] = $args['taxCategoryId'];
        }

        if (array_key_exists('quantity', $args)) {
            $data['manage_stock'] = 1;
            $data['inventories'] = [$outlet->inventory_source_id => (int) $args['quantity']];
        }

        Event::dispatch('catalog.product.update.before', $product->id);
        $product = $this->productRepository->update($data, $product->id);
        Event::dispatch('catalog.product.update.after', $product);

        // Optional image replacement.
        if (! empty($args['imageBase64'])) {
            $this->storeBase64Image($product, $args['imageBase64'], $args['imageName'] ?? null);
        }

        $product = $this->productRepository->find($product->id);

        return [
            'success' => true,
            'message' => trans('pos::app.outlet.product.updated'),
            'product' => $product,
        ];
    }

    /**
     * Merchant-management: delete a product. Manager+owner only.
     *
     * Soft-delete strategy: if the product has any historical order_items
     * we flip status=0 + remove the pos_outlet_product link so the catalog
     * stops showing it, but the catalog row stays for invoicing/reports.
     * If there are no orders, we hard-delete.
     */
    public function deleteProduct($root, array $args, GraphQLContext $context): array
    {
        RoleGate::requireManager($context->user());

        $validator = Validator::make($args, [
            'id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            throw new CustomException($validator->errors()->first());
        }

        $outlet = $context->user()->outlet;

        $belongs = DB::table('pos_outlet_product')
            ->where('outlet_id', $outlet->id)
            ->where('product_id', $args['id'])
            ->exists();
        if (! $belongs) {
            throw new CustomException(trans('pos::app.outlet.product.not-found'));
        }

        $hasOrders = DB::table('order_items')
            ->where('product_id', $args['id'])
            ->exists();

        if ($hasOrders) {
            // Soft path: flip product status off + drop outlet assignment.
            DB::table('products')
                ->where('id', $args['id'])
                ->update(['updated_at' => now()]);

            DB::table('product_flat')
                ->where('product_id', $args['id'])
                ->update(['status' => 0, 'visible_individually' => 0, 'updated_at' => now()]);

            DB::table('pos_outlet_product')
                ->where('outlet_id', $outlet->id)
                ->where('product_id', $args['id'])
                ->delete();
        } else {
            // Hard delete.
            $this->productRepository->delete($args['id']);
        }

        return [
            'success' => true,
            'message' => trans('pos::app.outlet.product.deleted'),
            'softDeleted' => $hasOrders,
        ];
    }
}
