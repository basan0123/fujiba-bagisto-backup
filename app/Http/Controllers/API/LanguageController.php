<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LanguageController extends Controller
{
    public function switch(Request $request)
    {
        $request->validate(['locale' => 'required|string|max:10']);
        $locale = $request->locale;

        // Save to logged-in admin DB
        try {
            if (Auth::guard('admin')->check()) {
                Auth::guard('admin')->user()->update(['locale' => $locale]);
            }
        } catch (\Throwable $e) {}

        // Save to logged-in customer DB
        try {
            if (Auth::guard('customer')->check()) {
                Auth::guard('customer')->user()->update(['locale' => $locale]);
            }
        } catch (\Throwable $e) {}

        // Save to session
        session(['user_locale' => $locale]);

        return redirect()->back();
    }

    public function current(Request $request)
    {
        $locale = session('user_locale') ?? config('app.locale', 'en');
        return response()->json(['locale' => $locale]);
    }
}
