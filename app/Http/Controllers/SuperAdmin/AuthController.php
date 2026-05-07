<?php
namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SuperAdmin;
use App\Services\SuperAdmin\AuditWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(private AuditWriter $audit) {}

    public function showLogin()
    {
        if (Auth::guard('superadmin')->check()) {
            return redirect()->route('superadmin.dashboard');
        }
        return view('superadmin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $sa = SuperAdmin::where('email', $credentials['email'])->first();

        if (! $sa || ! Hash::check($credentials['password'], $sa->password)) {
            $this->audit->log(null, 'login.failed', null, null, [
                'email' => $credentials['email'],
                'ip'    => $request->ip(),
            ]);
            return back()->withErrors(['email' => 'Invalid credentials.']);
        }

        if (! $this->ipAllowed($sa, $request->ip())) {
            $this->audit->log($sa, 'login.ip_blocked', null, null, ['ip' => $request->ip()]);
            return back()->withErrors(['email' => 'Login from this IP is not permitted.']);
        }

        // Stash pending admin id and require MFA enrollment or challenge before
        // promoting them to a real authenticated session.
        $request->session()->put('superadmin.pending_id', $sa->id);

        if (! $sa->hasMfaEnabled()) {
            return redirect()->route('superadmin.mfa.enroll');
        }

        return redirect()->route('superadmin.mfa.challenge');
    }

    public function logout(Request $request)
    {
        $sa = Auth::guard('superadmin')->user();
        if ($sa) {
            $this->audit->log($sa, 'logout', null, null, null);
        }

        Auth::guard('superadmin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('superadmin.login');
    }

    private function ipAllowed(SuperAdmin $sa, ?string $ip): bool
    {
        $list = $sa->ip_allowlist;
        if (empty($list) || ! is_array($list)) {
            return true;
        }
        return in_array($ip, $list, true);
    }
}
