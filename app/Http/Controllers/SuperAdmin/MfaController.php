<?php
namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SuperAdmin;
use App\Services\SuperAdmin\AuditWriter;
use App\Services\SuperAdmin\TotpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MfaController extends Controller
{
    public function __construct(
        private TotpService $totp,
        private AuditWriter $audit,
    ) {}

    /**
     * Step shown right after password login when MFA is not yet enrolled.
     * The pending super-admin id is in the session but the guard is NOT
     * fully authenticated yet (see AuthController).
     */
    public function showEnrollment(Request $request)
    {
        $sa = $this->pendingAdmin($request);
        if (! $sa) {
            return redirect()->route('superadmin.login');
        }

        if (! $request->session()->has('superadmin.mfa_enroll_secret')) {
            $request->session()->put(
                'superadmin.mfa_enroll_secret',
                $this->totp->generateSecret(),
            );
        }

        $secret = $request->session()->get('superadmin.mfa_enroll_secret');
        $uri = $this->totp->provisioningUri($secret, $sa->email, 'snssale Super-Admin');
        $qr  = $this->totp->qrCodeUrl($uri);

        return view('superadmin.auth.mfa_enroll', compact('secret', 'qr', 'uri'));
    }

    public function confirmEnrollment(Request $request)
    {
        $sa = $this->pendingAdmin($request);
        if (! $sa) {
            return redirect()->route('superadmin.login');
        }

        $request->validate(['code' => 'required|string']);
        $secret = $request->session()->get('superadmin.mfa_enroll_secret');
        if (! $secret || ! $this->totp->verify($secret, $request->input('code'))) {
            return back()->withErrors(['code' => 'Invalid code. Try again.']);
        }

        $sa->update([
            'mfa_secret'     => $secret,
            'mfa_enabled_at' => now(),
        ]);

        $this->audit->log($sa, 'mfa.enrolled', null, null, ['mfa_enabled_at' => $sa->mfa_enabled_at]);

        $this->finalizeLogin($request, $sa);

        return redirect()->route('superadmin.dashboard')
            ->with('success', 'MFA enabled. You are now logged in.');
    }

    public function showChallenge(Request $request)
    {
        $sa = $this->pendingAdmin($request);
        if (! $sa) {
            return redirect()->route('superadmin.login');
        }
        return view('superadmin.auth.mfa_challenge');
    }

    public function verifyChallenge(Request $request)
    {
        $sa = $this->pendingAdmin($request);
        if (! $sa) {
            return redirect()->route('superadmin.login');
        }

        $request->validate(['code' => 'required|string']);

        if (! $this->totp->verify($sa->mfa_secret, $request->input('code'))) {
            $this->audit->log($sa, 'mfa.failed', null, null, null);
            return back()->withErrors(['code' => 'Invalid code.']);
        }

        $this->finalizeLogin($request, $sa);

        return redirect()->intended(route('superadmin.dashboard'));
    }

    private function pendingAdmin(Request $request): ?SuperAdmin
    {
        $id = $request->session()->get('superadmin.pending_id');
        return $id ? SuperAdmin::find($id) : null;
    }

    private function finalizeLogin(Request $request, SuperAdmin $sa): void
    {
        Auth::guard('superadmin')->login($sa);
        $sa->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);
        $request->session()->forget(['superadmin.pending_id', 'superadmin.mfa_enroll_secret']);
        $request->session()->put('superadmin.session_started_at', now()->timestamp);
        $request->session()->regenerate();

        $this->audit->log($sa, 'login.success', null, null, ['ip' => $request->ip()]);
    }
}
