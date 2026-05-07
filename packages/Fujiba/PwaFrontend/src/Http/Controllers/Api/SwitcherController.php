<?php

namespace Fujiba\PwaFrontend\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\Core\Repositories\CurrencyRepository;
use Webkul\Core\Repositories\LocaleRepository;

/**
 * PWA-side currency / locale switcher.
 *
 * Bagisto's Shop middlewares (Webkul\Shop\Http\Middleware\Currency / Locale)
 * read `session('currency')` and `session('locale')` on every request. We
 * just need to write those keys; Bagisto picks them up on the next page load.
 */
class SwitcherController extends Controller
{
    public function __construct(
        protected CurrencyRepository $currencies,
        protected LocaleRepository $locales,
    ) {}

    public function currency(Request $request): JsonResponse
    {
        $code = (string) $request->query('currency', '');
        if ($code === '') {
            return response()->json(['ok' => false, 'message' => 'Missing currency code'], 422);
        }

        $allowed = collect(core()->getCurrentChannel()->currencies)->pluck('code')->all();
        if (! in_array($code, $allowed, true)) {
            return response()->json(['ok' => false, 'message' => 'Currency not enabled for this channel'], 422);
        }

        session()->put('currency', $code);
        session()->save();

        return response()->json(['ok' => true, 'currency' => $code]);
    }

    public function locale(Request $request): JsonResponse
    {
        $code = (string) $request->query('locale', '');
        if ($code === '') {
            return response()->json(['ok' => false, 'message' => 'Missing locale code'], 422);
        }

        $allowed = collect(core()->getCurrentChannel()->locales)->pluck('code')->all();
        if (! in_array($code, $allowed, true)) {
            return response()->json(['ok' => false, 'message' => 'Locale not enabled for this channel'], 422);
        }

        session()->put('locale', $code);
        app()->setLocale($code);
        session()->save();

        return response()->json(['ok' => true, 'locale' => $code]);
    }
}
