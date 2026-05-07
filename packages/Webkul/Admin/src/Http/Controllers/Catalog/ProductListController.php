<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Webkul\Admin\Http\Controllers\Controller;

/**
 * Standalone controller for /admin/catalog/products/list-view.
 * Default ProductController is untouched.
 */
class ProductListController extends Controller
{
    public function index()
    {
        return view('admin::catalog.products.list-view');
    }
}
