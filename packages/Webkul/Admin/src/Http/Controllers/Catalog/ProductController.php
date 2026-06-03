<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Admin\DataGrids\Catalog\ProductDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\InventoryRequest;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Admin\Http\Requests\MassUpdateRequest;
use Webkul\Admin\Http\Requests\ProductForm;
use Webkul\Admin\Http\Resources\AttributeResource;
use Webkul\Admin\Http\Resources\ProductResource;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Core\Rules\Slug;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Product\Helpers\Product;
use Webkul\Product\Helpers\ProductType;
use Webkul\Product\Helpers\StockAuditRecorder;
use Webkul\Pos\Repositories\ProductBarcodeRepository;
use Webkul\Product\Repositories\ProductAttributeValueRepository;
use Webkul\Product\Repositories\ProductDownloadableLinkRepository;
use Webkul\Product\Repositories\ProductDownloadableSampleRepository;
use Webkul\Product\Repositories\ProductInventoryRepository;
use Webkul\Product\Repositories\ProductRepository;

class ProductController extends Controller
{
    /**
     * Using const variable for status.
     */
    const ACTIVE_STATUS = 1;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected ProductAttributeValueRepository $productAttributeValueRepository,
        protected ProductDownloadableLinkRepository $productDownloadableLinkRepository,
        protected ProductDownloadableSampleRepository $productDownloadableSampleRepository,
        protected ProductInventoryRepository $productInventoryRepository,
        protected ProductRepository $productRepository,
        protected CustomerRepository $customerRepository,
        protected ProductBarcodeRepository $productBarcodeRepository,
    ) {}

    /**
     * Return ALL products as JSON for custom list/POS views.
     * 
     * ╔════════════════════════════════════════════════════════════════════════╗
     * ║ BAGISTO PRODUCT DATA ARCHITECTURE EXPLAINED                             ║
     * ╠════════════════════════════════════════════════════════════════════════╣
     * ║ PRODUCT_FLAT Table:                                                    ║
     * ║  ├─ Denormalized, locale+channel specific                              ║
     * ║  ├─ Fast for list views (name, sku, price, special_price, status)      ║
     * ║  ├─ Updated via sync/observers when product_attribute_values change    ║
     * ║  └─ Fields: product_id, locale, channel, name, sku, price, etc.        ║
     * ║                                                                         ║
     * ║ PRODUCTS Table:                                                        ║
     * ║  ├─ Base product with minimal info (id, sku, type, status, parent_id)  ║
     * ║  ├─ Relationship hub for inventories, attributes, images, categories   ║
     * ║  └─ Used to join other tables (never for display fields)               ║
     * ║                                                                         ║
     * ║ PRODUCT_ATTRIBUTE_VALUES Table:                                        ║
     * ║  ├─ Stores all custom attributes (cost, brand, color, dimensions)      ║
     * ║  ├─ Identified by attribute_id (look up in attributes table)           ║
     * ║  ├─ Fields: product_id, attribute_id, text_value, float_value, etc.    ║
     * ║  ├─ "cost" attribute stores purchase cost (from supplier/import)       ║
     * ║  └─ CRITICAL: Cost NOT in product_flat; must join explicitly           ║
     * ║                                                                         ║
     * ║ WHY COST NEEDS SEPARATE JOIN:                                          ║
     * ║  • Product_flat only contains display fields (name, price)             ║
     * ║  • Cost is stored as attribute value for flexibility                   ║
     * ║  • Dynamic join via attributes.code = 'cost' ensures correct attr ID   ║
     * ║  • Locale-independent (cost same across all locales)                   ║
     * ╚════════════════════════════════════════════════════════════════════════╝
     */
    public function allProducts(): JsonResponse
    {
        $locale  = app()->getLocale();
        // 'all' means no channel filter (show every channel); otherwise filter by code
        $channelParam = request('channel', '');
        $search       = request('q', '');
        $limit        = min((int) request('limit', 1000), 2000);
        $offset       = max((int) request('offset', 0), 0);
        // Multi-value filters: categories[]=1&categories[]=2  brands[]=Foo
        $categoryIds  = array_filter((array) request('categories', []));
        $brands       = array_filter((array) request('brands', []));

        $query = DB::table('product_flat')
            ->leftJoin('products', 'product_flat.product_id', '=', 'products.id')
            
            // ── JOIN CATEGORIES (multi-value, locale-aware) ──
            ->leftJoin('product_categories', 'products.id', '=', 'product_categories.product_id')
            ->leftJoin('category_translations', function($q) use ($locale) {
                $q->on('product_categories.category_id', '=', 'category_translations.category_id')
                  ->where('category_translations.locale', $locale);
            })
            
            // ── JOIN BRAND ATTRIBUTE (from product_attribute_values) ──
            // Brand is a `select` attribute → the selected option id lives in integer_value
            ->leftJoin('product_attribute_values as brand_attr', function($q) {
                $q->on('products.id', '=', 'brand_attr.product_id')
                  ->where('brand_attr.attribute_id', function($sub) {
                      $sub->select('id')->from('attributes')->where('code', 'brand')->limit(1);
                  });
            })
            ->leftJoin('attribute_option_translations as brand_trans', function($q) use ($locale) {
                $q->on('brand_trans.attribute_option_id', '=', 'brand_attr.integer_value')
                  ->where('brand_trans.locale', $locale);
            })
            
            // ── JOIN COLOR ATTRIBUTE (from product_attribute_values) ──
            ->leftJoin('product_attribute_values as color_attr', function($q) {
                $q->on('products.id', '=', 'color_attr.product_id')
                  ->where('color_attr.attribute_id', function($sub) {
                      $sub->select('id')->from('attributes')->where('code', 'color')->limit(1);
                  });
            })
            
            // ── JOIN COST ATTRIBUTE (from product_attribute_values) ──
            // Cost is NOT in product_flat; must fetch from product_attribute_values
            // We dynamically find the attribute_id for code='cost' to ensure portability
            ->leftJoin('product_attribute_values as cost_attr', function($q) {
                $q->on('products.id', '=', 'cost_attr.product_id')
                  ->where('cost_attr.attribute_id', function($sub) {
                      $sub->select('id')->from('attributes')->where('code', 'cost')->limit(1);
                  });
            })

            // ── JOIN MASTER PRICE ATTRIBUTE (source of truth) ──
            // product_flat.price can drift out of sync with the master
            // attribute_values.float_value when inline writes skip the observer.
            // Reading master here means the UI always shows the price an admin
            // actually set (seen in prod: flat=1395 while master=1595).
            ->leftJoin('product_attribute_values as price_attr', function($q) {
                $q->on('products.id', '=', 'price_attr.product_id')
                  ->where('price_attr.attribute_id', function($sub) {
                      $sub->select('id')->from('attributes')->where('code', 'price')->limit(1);
                  });
            })

            // ── JOIN ORIGINAL PRODUCT NAME (text) ──
            ->leftJoin('product_attribute_values as opn_attr', function($q) {
                $q->on('products.id', '=', 'opn_attr.product_id')
                  ->where('opn_attr.attribute_id', function($sub) {
                      $sub->select('id')->from('attributes')->where('code', 'original_product_name')->limit(1);
                  });
            })

            // ── JOIN SOURCE NAME (multiselect) ──
            // Stored as comma-separated option ids in text_value per Bagisto
            // convention for multiselect attributes.
            ->leftJoin('product_attribute_values as source_attr', function($q) {
                $q->on('products.id', '=', 'source_attr.product_id')
                  ->where('source_attr.attribute_id', function($sub) {
                      $sub->select('id')->from('attributes')->where('code', 'source_name')->limit(1);
                  });
            })
            
            // ── JOIN IMAGES ──
            ->leftJoin('product_images', function ($j) {
                $j->on('product_images.product_id', '=', 'products.id')
                  ->whereRaw('product_images.id = (SELECT MIN(pi2.id) FROM product_images pi2 WHERE pi2.product_id = products.id)');
            })
            
            // ── JOIN INVENTORY (pre-aggregated) ──
            // Subquery aggregate so other LEFT JOINs (cost, brand, etc.) cannot
            // duplicate inventory rows and inflate the SUM. Each product gets
            // exactly one inv.qty regardless of how many EAV rows match.
            ->leftJoin(
                DB::raw('(SELECT product_id, SUM(qty) AS qty FROM product_inventories GROUP BY product_id) AS inv'),
                'product_flat.product_id', '=', 'inv.product_id'
            )

            // Reserved qty = sum across channels of product_ordered_inventories.
            // Same subquery pattern so multi-channel products don't get inflated
            // by other LEFT JOINs.
            ->leftJoin(
                DB::raw('(SELECT product_id, SUM(qty) AS qty FROM product_ordered_inventories GROUP BY product_id) AS resv'),
                'product_flat.product_id', '=', 'resv.product_id'
            )
            
            // ── JOIN CUSTOM POS META (buy_price, currency, exchange_rate, weight_kg, sold_count) ──
            ->leftJoin('pos_product_meta', 'pos_product_meta.product_id', '=', 'products.id')
            
            // ── SELECT COLUMNS ──
            ->select([
                // From product_flat (denormalized for fast access)
                'product_flat.product_id as id',
                'product_flat.name',
                'product_flat.sku',
                'product_flat.price',
                'product_flat.special_price',
                'product_flat.status',
                'product_flat.weight',
                'product_flat.type',
                'product_flat.channel',
                
                // From images
                'product_images.path as image',
                
                // From pos_product_meta (custom fields for POS/inventory)
                'pos_product_meta.buy_price',
                'pos_product_meta.currency',
                'pos_product_meta.exchange_rate',
                'pos_product_meta.weight_kg',
                'pos_product_meta.sold_count',
                
                // From product_attribute_values
                // Cost attribute from Bagisto's attribute system
                'cost_attr.float_value as cost',
                'cost_attr.text_value as cost_text',

                // Master price (source of truth) — overrides product_flat.price
                // in the row map below when present.
                'price_attr.float_value as master_price',
                'price_attr.text_value as master_price_text',

                // Custom attributes: original_product_name (text), source_name (multiselect)
                'opn_attr.text_value as original_product_name',
                'source_attr.text_value as source_name_csv',
                
                // Aggregate categories and brands (GROUP_CONCAT for multi-value)
                DB::raw('GROUP_CONCAT(DISTINCT category_translations.name SEPARATOR ", ") as categories'),
                DB::raw('GROUP_CONCAT(DISTINCT product_categories.category_id SEPARATOR ",") as category_ids'),
                DB::raw('brand_attr.integer_value as brand_id'),
                DB::raw('COALESCE(brand_trans.label, brand_attr.text_value) as brand'),
                DB::raw('color_attr.integer_value as color')
            ])
            
            // ── AGGREGATES ──
            ->addSelect(DB::raw('inv.qty as stock'))
            ->addSelect(DB::raw('COALESCE(resv.qty, 0) as reserved'))
            
            // ── FILTERS ──
            ->whereNull('products.parent_id')
            ->where('product_flat.locale', $locale)
            ->groupBy('product_flat.product_id');

        // Channel filter — 'all' or empty = no filter, otherwise exact match
        if ($channelParam && $channelParam !== 'all') {
            $query->where('product_flat.channel', $channelParam);
        }

        // Search filter (name and SKU)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('product_flat.name', 'like', "%{$search}%")
                  ->orWhere('product_flat.sku', 'like', "%{$search}%");
            });
        }

        // Multi-category filter — product must belong to ANY of the selected categories
        if (! empty($categoryIds)) {
            $query->whereExists(function ($sub) use ($categoryIds) {
                $sub->select(DB::raw(1))
                    ->from('product_categories as pc_filter')
                    ->whereColumn('pc_filter.product_id', 'products.id')
                    ->whereIn('pc_filter.category_id', $categoryIds);
            });
        }

        // Multi-brand filter
        if (! empty($brands)) {
            $query->whereIn('brand_attr.text_value', $brands);
        }

        // Clone query for accurate total count
        $total = DB::table(DB::raw("({$query->toSql()}) as sub"))
                    ->mergeBindings($query)
                    ->count();

        $records = $query->orderBy('product_flat.product_id', 'desc')
                         ->skip($offset)
                         ->take($limit)
                         ->get()
                         ->map(function ($p) {
                             // Generate image URL
                             $p->image_url = $p->image
                                 ? Storage::url($p->image)
                                 : null;

                             // ✅ PARSE CATEGORY FORMAT (multi-category support)
                             $p->category_names = $p->categories
                                 ? array_map('trim', explode(',', $p->categories))
                                 : [];
                             $p->category_ids = $p->category_ids
                                 ? array_map('trim', explode(',', $p->category_ids))
                                 : [];
                             // Single value for backward compatibility
                             $p->category_name = $p->category_names[0] ?? '';
                             $p->category_id   = $p->category_ids[0] ?? '';

                             // ✅ ENSURE COST IS NUMERIC (from product_attribute_values.float_value)
                             // The cost attribute is stored in product_attribute_values table
                             // Use float_value if available, fallback to text_value parsed as float
                             $p->cost = $p->cost !== null ? floatval($p->cost) : (
                                 $p->cost_text !== null ? floatval($p->cost_text) : 0
                             );

                             // ✅ PRICE: prefer master attribute_values over stale product_flat.
                             // Falls back to product_flat.price when no master row exists.
                             $masterPrice = $p->master_price !== null
                                 ? floatval($p->master_price)
                                 : ($p->master_price_text !== null ? floatval($p->master_price_text) : null);
                             if ($masterPrice !== null) {
                                 $p->price = $masterPrice;
                             }
                             unset($p->master_price, $p->master_price_text);

                             // `jpy_cost` is the canonical final cost for this row (Bagisto `cost` attr
                             // in JPY). Fallback to pos_product_meta.buy_price so legacy rows still show.
                             $p->jpy_cost = $p->cost > 0
                                 ? $p->cost
                                 : (float) ($p->buy_price ?? 0);

                             // Multiselect source_name: stored as comma-separated option ids in
                             // text_value. Parse into a clean array for the client.
                             $p->source_ids = !empty($p->source_name_csv)
                                 ? array_values(array_filter(array_map('trim', explode(',', $p->source_name_csv))))
                                 : [];
                             unset($p->source_name_csv);

                             return $p;
                         });

        // Build option metadata for dropdowns (categories + brand options)
        $categoryOptions = DB::table('categories')
            ->leftJoin('category_translations', function ($q) use ($locale) {
                $q->on('categories.id', '=', 'category_translations.category_id')
                  ->where('category_translations.locale', $locale);
            })
            ->where('categories.parent_id', '>', 0)
            ->select('categories.id', 'category_translations.name')
            ->orderBy('category_translations.name')
            ->get();

        $brandAttrId = DB::table('attributes')->where('code', 'brand')->value('id');
        $brandOptions = $brandAttrId
            ? DB::table('attribute_options')
                ->leftJoin('attribute_option_translations', function ($q) use ($locale) {
                    $q->on('attribute_options.id', '=', 'attribute_option_translations.attribute_option_id')
                      ->where('attribute_option_translations.locale', $locale);
                })
                ->where('attribute_options.attribute_id', $brandAttrId)
                ->select('attribute_options.id', 'attribute_options.admin_name', 'attribute_option_translations.label')
                ->get()
                ->map(fn($o) => ['id' => $o->id, 'label' => $o->label ?: $o->admin_name])
            : collect();

        $colorAttrId = DB::table('attributes')->where('code', 'color')->value('id');
        $colorOptions = $colorAttrId
            ? DB::table('attribute_options')
                ->leftJoin('attribute_option_translations', function ($q) use ($locale) {
                    $q->on('attribute_options.id', '=', 'attribute_option_translations.attribute_option_id')
                      ->where('attribute_option_translations.locale', $locale);
                })
                ->where('attribute_options.attribute_id', $colorAttrId)
                ->select('attribute_options.id', 'attribute_options.admin_name', 'attribute_option_translations.label')
                ->get()
                ->map(fn($o) => ['id' => $o->id, 'label' => $o->label ?: $o->admin_name])
            : collect();

        $sourceAttrId = DB::table('attributes')->where('code', 'source_name')->value('id');
        $sourceOptions = $sourceAttrId
            ? DB::table('attribute_options')
                ->leftJoin('attribute_option_translations', function ($q) use ($locale) {
                    $q->on('attribute_options.id', '=', 'attribute_option_translations.attribute_option_id')
                      ->where('attribute_option_translations.locale', $locale);
                })
                ->where('attribute_options.attribute_id', $sourceAttrId)
                ->select('attribute_options.id', 'attribute_options.admin_name', 'attribute_option_translations.label')
                ->orderBy('attribute_options.admin_name')
                ->get()
                ->map(fn($o) => ['id' => $o->id, 'label' => $o->label ?: $o->admin_name])
            : collect();

        return new JsonResponse([
            'data'    => $records,
            'total'   => $total,
            'offset'  => $offset,
            'limit'   => $limit,
            'options' => [
                'categories' => $categoryOptions,
                'brands'     => $brandOptions,
                'colors'     => $colorOptions,
                'sources'    => $sourceOptions,
            ],
        ]);
    }

    /**
     * Per-product reservation detail — which open orders reserved which quantity.
     *
     * Bagisto decrements product_ordered_inventories.qty as items get shipped
     * (see OrderItemRepository::updateProductOrderedInventories), so the live
     * reservation per order_item is qty_ordered − qty_shipped, filtered to
     * orders that aren't fully cancelled/closed.
     */
    public function reservations(int $id): JsonResponse
    {
        $hasLabelPrinted = Schema::hasColumn('orders', 'fujiba_label_printed');

        $rows = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->leftJoin('channels as c', 'c.id', '=', 'o.channel_id')
            ->where('oi.product_id', $id)
            ->whereNull('oi.parent_id')
            ->whereRaw('(oi.qty_ordered - COALESCE(oi.qty_shipped,0) - COALESCE(oi.qty_canceled,0)) > 0')
            ->whereIn('o.status', ['pending', 'pending_payment', 'processing', 'fraud'])
            ->when($hasLabelPrinted, fn ($q) => $q->where(function ($qq) {
                $qq->whereNull('o.fujiba_label_printed')->orWhere('o.fujiba_label_printed', 0);
            }))
            ->select([
                'o.id as order_id',
                'o.increment_id',
                'o.status',
                'o.created_at',
                'o.customer_first_name',
                'o.customer_last_name',
                'o.customer_email',
                'o.channel_name',
                'c.code as channel_code',
                'oi.qty_ordered',
                'oi.qty_shipped',
                'oi.qty_canceled',
                'oi.qty_invoiced',
                DB::raw('(oi.qty_ordered - COALESCE(oi.qty_shipped,0) - COALESCE(oi.qty_canceled,0)) as qty_reserved'),
            ])
            ->orderByDesc('o.created_at')
            ->limit(500)
            ->get()
            ->map(function ($r) {
                $r->customer_name = trim(($r->customer_first_name ?? '') . ' ' . ($r->customer_last_name ?? ''));
                unset($r->customer_first_name, $r->customer_last_name);
                return $r;
            });

        $total = (int) DB::table('product_ordered_inventories')
            ->where('product_id', $id)
            ->sum('qty');

        return new JsonResponse([
            'product_id'    => $id,
            'reserved_total'=> $total,
            'reserved_open' => (int) $rows->sum('qty_reserved'),
            'data'          => $rows,
        ]);
    }

    /**
     * Inline update — price, stock, status, name, buy_price, currency, rate, weight_kg.
     */
    public function updateInline(): JsonResponse
    {
        $id = (int) request('id');

        if (! $id) {
            return new JsonResponse(['message' => 'Missing product id'], 422);
        }

        $product = $this->productRepository->find($id);
        if (! $product) {
            return new JsonResponse(['message' => 'Product not found'], 404);
        }

        $locale  = app()->getLocale();
        // Use channel from request (sent by the JS client) so inline edits on
        // "all channels" view or mmfood view update the correct product_flat row.
        $channel = request('channel') ?: core()->getCurrentChannelCode();

        // ── master attribute writes (source of truth) ────────────────────
        // Direct product_flat updates get clobbered by the ProductObserver
        // re-syncing from the master record. Always upsert the master
        // attribute_values too; mirror to product_flat so the immediate
        // re-read returns the updated value.
        $flatFields = [];
        foreach (['name', 'price', 'special_price', 'status', 'weight'] as $f) {
            if (request()->has($f)) {
                $v = request($f);
                $flatFields[$f] = $v;
                $this->upsertAttributeValue($id, $f, $f === 'status' ? (string) (int) $v : $v);
            }
        }

        if (! empty($flatFields)) {
            DB::table('product_flat')
                ->where('product_id', $id)
                ->where('locale', $locale)
                ->where('channel', $channel)
                ->update($flatFields);
        }

        // ── per-locale name writes (translation editor) ──────────────────
        // Accepts `names` => { en: "...", ja: "...", my: "..." } and writes
        // each non-empty value to the master attribute_values + product_flat
        // for that locale. Only the `name` attribute is locale-aware here.
        if (request()->has('names') && is_array(request('names'))) {
            $nameAttr = DB::table('attributes')->where('code', 'name')->first();
            $allowed  = array_keys(\Webkul\Core\Models\Locale::query()->pluck('id', 'code')->toArray());
            foreach ((array) request('names') as $loc => $val) {
                if (! in_array($loc, $allowed, true)) {
                    continue;
                }
                $val = is_string($val) ? trim($val) : $val;
                if ($nameAttr) {
                    DB::table('product_attribute_values')
                        ->where('product_id', $id)
                        ->where('attribute_id', $nameAttr->id)
                        ->where('locale', $loc)
                        ->delete();
                    if ($val !== '' && $val !== null) {
                        DB::table('product_attribute_values')->insert([
                            'product_id'   => $id,
                            'attribute_id' => $nameAttr->id,
                            'locale'       => $loc,
                            'channel'      => null,
                            'text_value'   => (string) $val,
                        ]);
                    }
                }
                DB::table('product_flat')
                    ->where('product_id', $id)
                    ->where('locale', $loc)
                    ->where('channel', $channel)
                    ->update(['name' => $val === '' ? null : $val]);
            }
        }

        // ── product_inventories stock ─────────────────────────────────────
        if (request()->has('stock')) {
            $sourceId = DB::table('inventory_sources')->where('status', 1)->orderBy('id')->value('id')
                     ?? DB::table('inventory_sources')->orderBy('id')->value('id')
                     ?? 1;
            $qty = max(0, (int) request('stock'));
            $before = StockAuditRecorder::currentQty((int) $id, (int) $sourceId);
            DB::table('product_inventories')->updateOrInsert(
                ['product_id' => $id, 'inventory_source_id' => $sourceId],
                ['qty' => $qty]
            );
            StockAuditRecorder::record((int) $id, (int) $sourceId, $before, $qty, 'admin.product.inline_update');
        }

        // ── product_categories (multi-category assignment) ────────────────
        if (request()->has('categories')) {
            // Filter out the empty-string sentinel used when clearing all categories
            $catIds = array_filter(array_map('intval', (array) request('categories')));
            // Replace all category assignments for this product
            DB::table('product_categories')->where('product_id', $id)->delete();
            foreach (array_unique($catIds) as $catId) {
                DB::table('product_categories')->insert([
                    'product_id'  => $id,
                    'category_id' => $catId,
                ]);
            }
        }

        // ── pos_product_meta ──────────────────────────────────────────────
        $metaFields = [];
        foreach (['buy_price', 'currency', 'exchange_rate', 'weight_kg', 'sold_count', 'cost_notes'] as $f) {
            if (request()->has($f)) {
                $metaFields[$f] = request($f);
            }
        }

        if (! empty($metaFields)) {
            DB::table('pos_product_meta')->updateOrInsert(
                ['product_id' => $id],
                array_merge($metaFields, ['updated_at' => now()])
            );
        }

        // ── product_attribute_values (brand, color, cost) ───────────────────
        // Brand is a `select` attribute → selected option id goes in integer_value.
        if (request()->has('brand')) {
            $this->upsertAttributeValue($id, 'brand', request('brand'));
        }

        // Cost is a `price` attribute → numeric JPY value in float_value. This is
        // what the list view reads as the canonical `jpy_cost`.
        if (request()->has('jpy_cost') || request()->has('cost')) {
            $this->upsertAttributeValue($id, 'cost', request('jpy_cost', request('cost')));
        }

        if (request()->has('original_product_name')) {
            $this->upsertAttributeValue($id, 'original_product_name', request('original_product_name'));
        }

        // Multiselect: client sends `source_ids` as an array of option ids.
        // Empty array (or `['']` sentinel) clears the value.
        if (request()->has('source_ids') || request()->has('source_name')) {
            $this->upsertAttributeValue(
                $id,
                'source_name',
                request('source_ids', request('source_name'))
            );
        }

        // Accept either `color` (single) or `colors[]` (multi from the multi-select UI).
        // The Bagisto `color` attribute is of type=select, so we persist the first
        // chosen option id. When the attribute is later promoted to multiselect,
        // the upsert helper will write the full list automatically.
        if (request()->has('colors')) {
            $colors = array_values(array_filter((array) request('colors'), fn($v) => $v !== '' && $v !== null));
            $this->upsertAttributeValue($id, 'color', $colors[0] ?? null);
        } elseif (request()->has('color')) {
            $this->upsertAttributeValue($id, 'color', request('color'));
        }

        // Fire standard events so observers/search re-index
        Event::dispatch('catalog.product.update.before', $id);
        Event::dispatch('catalog.product.update.after', $product);

        return new JsonResponse(['message' => 'Updated', 'id' => $id]);
    }

    /**
     * Upsert a single product_attribute_values row for the given attribute code.
     * Picks the correct value column (integer_value for select, float_value for
     * price/decimal, text_value otherwise) based on attribute.type.
     */
    protected function upsertAttributeValue(int $productId, string $code, $value): void
    {
        $attr = DB::table('attributes')->where('code', $code)->first();
        if (! $attr) {
            return;
        }

        // Resolve the scope of THIS write FIRST so the delete can be scoped
        // to the same channel/locale. The previous unscoped delete wiped
        // every channel's row for value_per_channel attributes (e.g. status):
        // toggling a product on mmfood admin would silently destroy kimono's
        // status row, leaving the other channel mis-configured.
        $channel = $attr->value_per_channel
            ? (request('channel') ?: core()->getCurrentChannelCode())
            : null;
        $locale  = $attr->value_per_locale
            ? app()->getLocale()
            : null;

        DB::table('product_attribute_values')
            ->where('product_id', $productId)
            ->where('attribute_id', $attr->id)
            ->when($attr->value_per_channel, fn ($q) => $q->where('channel', $channel))
            ->when($attr->value_per_locale,  fn ($q) => $q->where('locale',  $locale))
            ->delete();

        // Empty value → treat as "clear" (delete-only path, already done).
        if ($value === null || $value === '') {
            return;
        }

        $row = [
            'product_id'   => $productId,
            'attribute_id' => $attr->id,
            'text_value'   => null,
            'integer_value'=> null,
            'float_value'  => null,
            'boolean_value'=> null,
            'datetime_value'=> null,
        ];

        if ($attr->value_per_locale)  { $row['locale']  = $locale; }
        if ($attr->value_per_channel) { $row['channel'] = $channel; }

        switch ($attr->type) {
            case 'select':
                $row['integer_value'] = (int) $value;
                break;
            case 'multiselect':
            case 'checkbox':
                // Bagisto convention: comma-separated option ids in text_value.
                $ids = is_array($value)
                    ? array_values(array_filter(array_map('intval', $value)))
                    : array_values(array_filter(array_map('intval', explode(',', (string) $value))));
                if (empty($ids)) {
                    return; // nothing to write — already deleted above.
                }
                $row['text_value'] = implode(',', $ids);
                break;
            case 'price':
            case 'decimal':
                $row['float_value'] = (float) $value;
                break;
            case 'boolean':
                $row['boolean_value'] = $value ? 1 : 0;
                break;
            case 'date':
            case 'datetime':
                $row['datetime_value'] = $value;
                break;
            default:
                $row['text_value'] = (string) $value;
        }

        DB::table('product_attribute_values')->insert($row);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(ProductDataGrid::class)->process();
        }

        $families = $this->attributeFamilyRepository->all();

        return view('admin::catalog.products.index', compact('families'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $families = $this->attributeFamilyRepository->all();

        $configurableFamily = null;

        if ($familyId = request()->get('family')) {
            $configurableFamily = $this->attributeFamilyRepository->find($familyId);
        }

        return view('admin::catalog.products.create', compact('families', 'configurableFamily'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store()
    {
        $this->validate(request(), [
            'type' => 'required',
            'attribute_family_id' => 'required',
            'sku' => ['required', 'unique:products,sku', new Slug],
            'super_attributes' => 'array|min:1',
            'super_attributes.*' => 'array|min:1',
        ]);

        if (
            ProductType::hasVariants(request()->input('type'))
            && ! request()->has('super_attributes')
        ) {
            $configurableFamily = $this->attributeFamilyRepository
                ->find(request()->input('attribute_family_id'));

            return new JsonResponse([
                'data' => [
                    'attributes' => AttributeResource::collection($configurableFamily->configurable_attributes),
                ],
            ]);
        }

        Event::dispatch('catalog.product.create.before');

        $product = $this->productRepository->create(request()->only([
            'type',
            'attribute_family_id',
            'sku',
            'super_attributes',
            'family',
        ]));

        Event::dispatch('catalog.product.create.after', $product);

        session()->flash('success', trans('admin::app.catalog.products.create-success'));

        return new JsonResponse([
            'data' => [
                'redirect_url' => route('admin.catalog.products.edit', $product->id),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
    {
        $product = $this->productRepository->findOrFail($id);

        return view('admin::catalog.products.edit', compact('product'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(ProductForm $request, int $id)
    {
        Event::dispatch('catalog.product.update.before', $id);

        $product = $this->productRepository->update($request->all(), $id);

        Event::dispatch('catalog.product.update.after', $product);

        $scanType = $request->input('scan_type') ?: null;
        $gtin     = trim((string) $request->input('gtin', ''));

        // Regenerate barcode whenever scan_type or gtin is submitted.
        // For 'product_id' and 'sku' types the gtin is irrelevant — scan_value
        // is auto-computed by the repository from products.id / products.sku.
        if ($scanType !== null || $gtin !== '') {
            $this->productBarcodeRepository->generateBarcode(
                $id,
                null,
                $gtin !== '' ? $gtin : null,
                $scanType,
            );
        }

        session()->flash('success', trans('admin::app.catalog.products.update-success'));

        return redirect()->route('admin.catalog.products.index');
    }

    /**
     * Update inventories.
     *
     * @return \Illuminate\Http\Response
     */
    public function updateInventories(InventoryRequest $inventoryRequest, int $id)
    {
        $product = $this->productRepository->findOrFail($id);

        Event::dispatch('catalog.product.update.before', $id);

        $this->productInventoryRepository->saveInventories(request()->all(), $product);

        Event::dispatch('catalog.product.update.after', $product);

        return response()->json([
            'message' => trans('admin::app.catalog.products.saved-inventory-message'),
            'updatedTotal' => $this->productInventoryRepository->where('product_id', $product->id)->sum('qty'),
        ]);
    }

    /**
     * Uploads downloadable file.
     *
     * @return \Illuminate\Http\Response
     */
    public function uploadLink(int $id)
    {
        return response()->json(
            $this->productDownloadableLinkRepository->upload(request()->all(), $id)
        );
    }

    /**
     * Copy a given Product.
     *
     * @return \Illuminate\Http\Response
     */
    public function copy(int $id)
    {
        try {
            Event::dispatch('catalog.product.create.before');

            $product = $this->productRepository->copy($id);

            Event::dispatch('catalog.product.create.after', $product);
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->to(route('admin.catalog.products.index'));
        }

        return response()->json([
            'message' => trans('admin::app.catalog.products.product-copied'),
        ]);
    }

    /**
     * Uploads downloadable sample file.
     *
     * @return \Illuminate\Http\Response
     */
    public function uploadSample(int $id)
    {
        return response()->json(
            $this->productDownloadableSampleRepository->upload(request()->all(), $id)
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            Event::dispatch('catalog.product.delete.before', $id);

            $this->productRepository->delete($id);

            Event::dispatch('catalog.product.delete.after', $id);

            return new JsonResponse([
                'message' => trans('admin::app.catalog.products.delete-success'),
            ]);
        } catch (\Exception $e) {
            report($e);
        }

        return new JsonResponse([
            'message' => trans('admin::app.catalog.products.delete-failed'),
        ], 500);
    }

    /**
     * Mass delete the products.
     */
    public function massDestroy(MassDestroyRequest $massDestroyRequest): JsonResponse
    {
        $productIds = $massDestroyRequest->input('indices');

        try {
            foreach ($productIds as $productId) {
                $product = $this->productRepository->find($productId);

                if (isset($product)) {
                    Event::dispatch('catalog.product.delete.before', $productId);

                    $this->productRepository->delete($productId);

                    Event::dispatch('catalog.product.delete.after', $productId);
                }
            }

            return new JsonResponse([
                'message' => trans('admin::app.catalog.products.index.datagrid.mass-delete-success'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mass update the products.
     */
    public function massUpdate(MassUpdateRequest $massUpdateRequest): JsonResponse
    {
        $productIds = $massUpdateRequest->input('indices');

        foreach ($productIds as $productId) {
            Event::dispatch('catalog.product.update.before', $productId);

            $product = $this->productRepository->update([
                'status' => $massUpdateRequest->input('value'),
            ], $productId, ['status']);

            Event::dispatch('catalog.product.update.after', $product);
        }

        return new JsonResponse([
            'message' => trans('admin::app.catalog.products.index.datagrid.mass-update-success'),
        ], 200);
    }

    /**
     * To be manually invoked when data is seeded into products.
     *
     * @return \Illuminate\Http\Response
     */
    public function sync()
    {
        Event::dispatch('products.datagrid.sync', true);

        return redirect()->route('admin.catalog.products.index');
    }

    /**
     * Result of search product.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function search()
    {
        $query = trim(request('query'));

        if (empty($query)) {
            return response()->json([
                'data' => [],
            ]);
        }

        $searchEngine = 'database';

        if (
            core()->getConfigData('catalog.products.search.engine') == 'elastic'
            && core()->getConfigData('catalog.products.search.admin_mode') == 'elastic'
        ) {
            $searchEngine = 'elastic';

            $indexNames = core()->getAllChannels()->map(function ($channel) {
                return Product::formatElasticSearchIndexName($channel->code, app()->getLocale());
            })->toArray();
        }

        $channelId = $this->customerRepository->find(request('customer_id'))->channel_id ?? null;

        $params = [
            'index' => $indexNames ?? null,
            'name' => request('query'),
            'sort' => 'created_at',
            'order' => 'desc',
            'channel_id' => $channelId,
        ];

        if (request()->has('type')) {
            $params['type'] = request('type');
        }

        if (request()->has('exclude_customizable_products')) {
            $params['exclude_customizable_products'] = request('exclude_customizable_products');
        }

        $products = $this->productRepository
            ->setSearchEngine($searchEngine)
            ->getAll($params);

        return ProductResource::collection($products);
    }

    /**
     * Bulk inline update. Accepts either:
     *   • `updates`: [{id, ...fields}, ...]  (new spreadsheet-editor shape)
     *   • `products`: [{id, ...fields}, ...] (legacy shape, kept for compat)
     *
     * Each row is piped through the same write-path as updateInline() so every
     * supported field (flat, inventory, meta, category, brand, color, cost) works.
     */
    public function bulkUpdate(): \Illuminate\Http\JsonResponse
    {
        $updates = request()->input('updates', request()->input('products', []));

        if (empty($updates)) {
            return response()->json(['message' => 'No data provided'], 422);
        }

        $locale  = app()->getLocale();
        $channel = request('channel') ?: core()->getCurrentChannelCode();
        $ok      = 0;
        $fail    = 0;
        $errors  = [];

        foreach ($updates as $item) {
            $id = (int) ($item['id'] ?? 0);
            if (! $id) { $fail++; continue; }

            try {
                $this->applyInlineUpdate($id, $item, $locale, $channel);
                Event::dispatch('catalog.product.update.before', $id);
                Event::dispatch('catalog.product.update.after', $this->productRepository->find($id));
                $ok++;
            } catch (\Throwable $e) {
                $fail++;
                $errors[$id] = $e->getMessage();
            }
        }

        return response()->json([
            'success' => $fail === 0,
            'ok'      => $ok,
            'fail'    => $fail,
            'errors'  => $errors,
            'message' => "{$ok} 成功" . ($fail ? " / {$fail} 失敗" : ''),
        ]);
    }

    /**
     * Shared write path used by updateInline() and bulkUpdate(). Given one
     * row of changes, applies each field to the right table/attribute.
     */
    protected function applyInlineUpdate(int $id, array $item, string $locale, string $channel): void
    {
        // Master attribute writes — these are the source of truth. Bagisto's
        // ProductObserver mirrors them into product_flat after each save, so
        // writing directly to product_flat is pointless: the observer will
        // overwrite the row from the master record on the next event dispatch.
        // Status/price/special_price/name/weight are all standard Bagisto
        // attributes (codes match the column names) — upsert via the helper.
        foreach (['name', 'price', 'special_price', 'status', 'weight'] as $f) {
            if (! array_key_exists($f, $item)) continue;
            $v = $item[$f];
            if ($f === 'status') {
                $v = (string) (int) $v;       // boolean attr: '1' / '0'
            }
            $this->upsertAttributeValue($id, $f, $v);
        }

        // Mirror the same values into product_flat so the list view's
        // immediate re-read sees them without waiting for the observer.
        $flatFields = array_intersect_key($item, array_flip(['name','price','special_price','status','weight']));
        if (! empty($flatFields)) {
            DB::table('product_flat')
                ->where('product_id', $id)
                ->where('locale', $locale)
                ->where('channel', $channel)
                ->update($flatFields);
        }

        if (array_key_exists('stock', $item)) {
            // Need a real row to update — products without an inventory row
            // would otherwise silently drop the write.
            $sourceId = DB::table('inventory_sources')->where('status', 1)->orderBy('id')->value('id')
                     ?? DB::table('inventory_sources')->orderBy('id')->value('id')
                     ?? 1;
            $qty = max(0, (int) $item['stock']);
            $before = StockAuditRecorder::currentQty((int) $id, (int) $sourceId);
            DB::table('product_inventories')->updateOrInsert(
                ['product_id' => $id, 'inventory_source_id' => $sourceId],
                ['qty' => $qty]
            );
            StockAuditRecorder::record((int) $id, (int) $sourceId, $before, $qty, 'admin.product.bulk_update');
        }

        if (array_key_exists('categories', $item)) {
            $catIds = array_filter(array_map('intval', (array) $item['categories']));
            DB::table('product_categories')->where('product_id', $id)->delete();
            foreach (array_unique($catIds) as $catId) {
                DB::table('product_categories')->insert([
                    'product_id'  => $id,
                    'category_id' => $catId,
                ]);
            }
        }

        $metaFields = array_intersect_key($item, array_flip(['buy_price','currency','exchange_rate','weight_kg','sold_count','cost_notes']));
        if (! empty($metaFields)) {
            DB::table('pos_product_meta')->updateOrInsert(
                ['product_id' => $id],
                array_merge($metaFields, ['updated_at' => now()])
            );
        }

        if (array_key_exists('brand', $item)) {
            $this->upsertAttributeValue($id, 'brand', $item['brand']);
        }
        if (array_key_exists('jpy_cost', $item) || array_key_exists('cost', $item)) {
            $this->upsertAttributeValue($id, 'cost', $item['jpy_cost'] ?? $item['cost']);
        }
        if (array_key_exists('original_product_name', $item)) {
            $this->upsertAttributeValue($id, 'original_product_name', $item['original_product_name']);
        }
        if (array_key_exists('source_ids', $item)) {
            $this->upsertAttributeValue($id, 'source_name', $item['source_ids']);
        } elseif (array_key_exists('source_name', $item)) {
            $this->upsertAttributeValue($id, 'source_name', $item['source_name']);
        }
        if (array_key_exists('colors', $item)) {
            $colors = array_values(array_filter((array) $item['colors'], fn($v) => $v !== '' && $v !== null));
            $this->upsertAttributeValue($id, 'color', $colors[0] ?? null);
        } elseif (array_key_exists('color', $item)) {
            $this->upsertAttributeValue($id, 'color', $item['color']);
        }
    }
    /**
     * Download image or file.
     *
     * @param  int  $productId
     * @param  int  $attributeId
     * @return \Illuminate\Http\Response
     */
    public function download($productId, $attributeId)
    {
        $productAttribute = $this->productAttributeValueRepository->findOneWhere([
            'product_id' => $productId,
            'attribute_id' => $attributeId,
        ]);

        return Storage::download($productAttribute['text_value']);
    }
    

}
