<?php

namespace App\Http\Controllers;

use App\Models\LoginToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Webkul\Customer\Models\Customer;

/**
 * Blade-side counterpart of POST /api/autologin.
 *
 * GET /customer/messenger/exchange?token=<one-time>&target=<path>
 *
 * Validates the one-time token, marks it used, logs the customer into the
 * standard `customer` session guard, and redirects to a Blade path. Used by
 * the storefront Messenger autologin routes that need a Blade landing page
 * rather than the PWA SPA.
 */
class BladeAutologinController extends Controller
{
    public function __invoke(Request $request)
    {
        $raw    = (string) $request->query('token', '');
        $target = (string) $request->query('target', '');

        if ($raw === '' || strlen($raw) !== 64) {
            return redirect()->route('shop.home.index')
                ->with('warning', 'Invalid login link.');
        }

        $record = LoginToken::where('token_hash', LoginToken::hash($raw))->first();

        if (! $record || ! $record->isUsable()) {
            return redirect()->route('shop.home.index')
                ->with('warning', 'Login link expired. Please tap the link again.');
        }

        $customer = Customer::find($record->user_id);
        if (! $customer) {
            return redirect()->route('shop.home.index')
                ->with('warning', 'Account not found.');
        }

        $record->markUsed();

        Auth::guard('customer')->login($customer, true);

        return redirect()->to($this->resolveTarget($target));
    }

    /**
     * Map the bot `target` shorthand to a Blade path.
     */
    protected function resolveTarget(string $target): string
    {
        return match ($target) {
            'orders'   => '/customer/account/orders',
            'profile'  => '/customer/account/profile',
            'wishlist' => '/customer/account/wishlist',
            'cart'     => '/checkout/cart',
            default    => '/',
        };
    }
}
