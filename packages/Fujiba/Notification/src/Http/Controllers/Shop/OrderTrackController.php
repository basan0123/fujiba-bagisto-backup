<?php

namespace Fujiba\Notification\Http\Controllers\Shop;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Webkul\Customer\Models\Customer;
use Webkul\Sales\Models\Order;

/**
 * Signed auto-login + redirect for order tracking links delivered via
 * email or messenger templates ({{action_url}}). The signed URL gates
 * access; we only log the customer in if their PSID/email-linked order
 * matches the requested order id.
 *
 * Guest orders (no customer_id) skip the login step and redirect to the
 * shop homepage — they cannot use /customer/account/* without an account.
 */
class OrderTrackController extends Controller
{
    public function __invoke(Request $request, int $orderId)
    {
        // Middleware 'signed' has already verified the signature + expiry.
        $order = Order::find($orderId);

        if (! $order) {
            return redirect('/');
        }

        // Guest order: skip login, send to homepage. Building a "guest order
        // tracking" page is its own product surface; not in scope here.
        if (! $order->customer_id) {
            return redirect('/');
        }

        $customer = Customer::find($order->customer_id);

        if (! $customer) {
            return redirect('/');
        }

        Auth::guard('customer')->loginUsingId($customer->id);

        return redirect()->route('shop.customers.account.orders.view', ['id' => $order->id]);
    }
}
