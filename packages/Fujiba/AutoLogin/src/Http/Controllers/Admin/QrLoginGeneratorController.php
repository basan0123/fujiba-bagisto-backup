<?php

namespace Fujiba\AutoLogin\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginToken;
use Illuminate\Http\Request;
use Webkul\Customer\Models\Customer;

/**
 * Admin-side one-page UI for generating a one-time customer-login URL.
 *
 * Reuses the existing LoginToken + BladeAutologinController flow (the
 * messenger.exchange route). No new token table, no new exchange endpoint.
 * The admin pastes the resulting URL into any QR generator of their
 * choosing — physical hardware integration is out of scope.
 */
class QrLoginGeneratorController extends Controller
{
    public function index(Request $request)
    {
        $url = null;
        $customer = null;

        if ($customerId = $request->query('customer_id')) {
            $customer = Customer::find((int) $customerId);

            if ($customer) {
                [, $rawToken] = LoginToken::issue(
                    (int) $customer->id,
                    (int) $request->query('ttl_minutes', 10),
                    $request->ip(),
                    $request->userAgent()
                );

                $url = route('messenger.exchange', [
                    'token'  => $rawToken,
                    'target' => $request->query('target') ?: null,
                ]);
            }
        }

        return view('autologin::admin.qr-generator', [
            'customer' => $customer,
            'url'      => $url,
            'request'  => $request,
        ]);
    }
}
