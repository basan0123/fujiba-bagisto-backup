<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class SetUserLanguage
{
    protected $validLocales = ['en', 'ja', 'my', 'ar', 'bn', 'ca', 'de', 'es', 'fa', 'fr', 'he', 'hi_IN', 'id', 'it', 'nl', 'pl', 'pt_BR', 'ru', 'sin', 'tr', 'uk', 'zh_CN'];

    public function handle(Request $request, Closure $next)
    {
        $locale = null;

        // 1. Logged-in admin DB locale (highest priority)
        try {
            if (Auth::guard('admin')->check()) {
                $locale = Auth::guard('admin')->user()->locale ?? null;
            }
        } catch (\Throwable $e) {}

        // 2. Logged-in customer DB locale
        try {
            if (!$locale && Auth::guard('customer')->check()) {
                $locale = Auth::guard('customer')->user()->locale ?? null;
            }
        } catch (\Throwable $e) {}

        // 3. Session (plain value)
        if (!$locale) {
            $locale = session('user_locale');
        }

        // 4. Cookie - Laravel encrypts cookies, request()->cookie() auto-decrypts
        if (!$locale) {
            try {
                $locale = $request->cookie('user_locale');
            } catch (\Throwable $e) {}
        }

        // Validate - must be a simple locale string
        if (!$locale || !is_string($locale) || !in_array($locale, $this->validLocales)) {
            $locale = config('app.locale', 'en');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
