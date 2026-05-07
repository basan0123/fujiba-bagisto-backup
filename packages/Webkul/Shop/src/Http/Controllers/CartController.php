<?php

namespace Webkul\Shop\Http\Controllers;

use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Services\StockReconciler;

class CartController extends Controller
{
    /**
     * Cart page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        if (! core()->getConfigData('sales.checkout.shopping_cart.cart_page')) {
            abort(404);
        }

        if ($cart = Cart::getCart()) {
            $report = app(StockReconciler::class)->reconcile($cart);

            if ($report['changed']) {
                session()->flash('warning', trans('shop::app.checkout.cart.stock-auto-adjusted'));
            }
        }

        return view('shop::checkout.cart.index');
    }
}
