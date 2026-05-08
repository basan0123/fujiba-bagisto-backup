<?php

namespace Fujiba\CatalogAdmin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * When the admin opens a product edit page, redirect to a `?channel=` that
 * actually has a product_flat row for this product. Without this, MMFOOD-only
 * products opened on the default (KIMONO) channel render the wrong locale set
 * and an inline-edit save would write product_flat against the wrong channel.
 *
 * Picks the first channel from product_flat for the requested product.
 * No-op when the URL already has a valid ?channel= for that product.
 */
class RedirectProductEditToVisibleChannel
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->isMethod('GET')) {
            return $next($request);
        }

        // Match path directly: route() is null at the global-middleware stage
        // (router hasn't resolved yet), so we can't rely on the named route.
        $adminPrefix = trim((string) config('app.admin_url', 'admin'), '/');
        $pattern = '#^' . preg_quote($adminPrefix, '#') . '/catalog/products/edit/(\d+)/?$#';

        if (! preg_match($pattern, trim($request->path(), '/'), $m)) {
            return $next($request);
        }

        $productId = (int) $m[1];
        if ($productId <= 0) {
            return $next($request);
        }

        $requestedChannel = (string) $request->query('channel', '');

        $visibleChannels = DB::table('product_flat')
            ->where('product_id', $productId)
            ->distinct()
            ->pluck('channel')
            ->filter()
            ->values()
            ->all();

        if (empty($visibleChannels)) {
            return $next($request);
        }

        if ($requestedChannel !== '' && in_array($requestedChannel, $visibleChannels, true)) {
            return $next($request);
        }

        $target = $visibleChannels[0];

        $params = array_merge($request->query(), ['channel' => $target]);

        return redirect()->to($request->url() . '?' . http_build_query($params));
    }
}
