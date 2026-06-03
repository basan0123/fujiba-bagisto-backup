<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Webkul\Admin\Http\Controllers\Controller;

/**
 * Standalone controller for /admin/catalog/products/pos-view.
 * Default ProductController is untouched.
 */
class ProductPosController extends Controller
{
    public function index()
    {
        // The whole POS UI (and its JS) is inline in this Blade. iPads were
        // caching an old copy and showing stale behaviour (e.g. a partial
        // product list), so force revalidation on every load.
        return response()
            ->view('admin::catalog.products.pos-view')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
