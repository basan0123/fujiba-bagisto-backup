<?php

namespace App\Saas\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Public marketing pages for snssale.com (central domains only).
 *
 * Served under /saas while the apex still redirects to the super-admin login;
 * moving it to "/" is a separate, owner-approved cutover.
 */
class LandingController extends Controller
{
    public function __invoke(Request $request)
    {
        $locale = $this->resolveLocale($request);
        $copy = require app_path('Saas/copy.php');

        return response()->view('saas.landing', [
            'locale' => $locale,
            't'      => array_replace_recursive($copy['en'], $copy[$locale] ?? []),
            'plans'  => config('saas.plans'),
            'saas'   => config('saas'),
        ])->header('Content-Language', $locale);
    }

    protected function resolveLocale(Request $request): string
    {
        $supported = config('saas.locales', ['ja', 'en']);
        $requested = (string) $request->query('lang', '');

        if (in_array($requested, $supported, true)) {
            return $requested;
        }

        return $request->getPreferredLanguage($supported) ?: config('saas.default_locale', 'ja');
    }
}
