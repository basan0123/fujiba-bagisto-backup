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
        return view('admin::catalog.products.pos-view');
    }
}
