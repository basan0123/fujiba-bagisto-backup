<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ChatpionRedirectController extends Controller
{
    // ChatPion ecommerce_store.id → Bagisto tenant host.
    // Only listed stores are honored; unknown ids fall through to shop home.
    protected const STORE_HOST_MAP = [
        9  => 'mmfood.fujiba.co.jp', // Fujiba Myanmar Food
        10 => 'kimono.fujiba.co.jp', // Fujiba Kimono
    ];

    public function __invoke(Request $request)
    {
        $storeId      = (int) $request->query('store_id', 0);
        $subscriberId = (string) $request->query('subscriber_id', '');

        if (! isset(self::STORE_HOST_MAP[$storeId])) {
            return redirect()->route('shop.home.index');
        }

        $host = self::STORE_HOST_MAP[$storeId];

        if ($subscriberId === '' || ! preg_match('/^[A-Za-z0-9_-]+$/', $subscriberId)) {
            return redirect()->away("https://{$host}/");
        }

        $target = "https://{$host}/messenger/autologin?subscriber_id=" . rawurlencode($subscriberId);

        return redirect()->away($target);
    }
}
