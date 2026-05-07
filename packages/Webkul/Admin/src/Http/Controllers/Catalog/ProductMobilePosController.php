<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Webkul\Admin\Http\Controllers\Controller;

/**
 * Standalone controller for /admin/catalog/products/mobile-view.
 * Default ProductController is untouched.
 */
class ProductMobilePosController extends Controller
{
    public function index()
    {
        return view('admin::catalog.products.mobile-view');
    }
}
