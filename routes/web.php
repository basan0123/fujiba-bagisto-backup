<?php

// ── SUPERADMIN PANEL ──────────────────────────────────────────
use App\Http\Controllers\SuperAdmin\AuthController;
use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\TenantController;
use App\Http\Controllers\SuperAdmin\PlanController;
use App\Http\Controllers\SuperAdmin\MfaController;
use App\Http\Controllers\SuperAdmin\AuditController;
use App\Http\Controllers\SuperAdmin\CloneTenantController;
use App\Http\Controllers\SuperAdmin\SslController;
use App\Http\Controllers\SuperAdmin\ImpersonationController;
use App\Http\Controllers\SuperAdmin\AnnouncementController;

Route::prefix('superadmin')->name('superadmin.')->group(function () {
    // Guest routes
    Route::middleware('guest:superadmin')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    });

    // MFA gate routes — guard is NOT auth:superadmin yet; they rely on a
    // session-stashed pending_id set by AuthController::login().
    Route::get('/mfa/enroll',          [MfaController::class, 'showEnrollment'])->name('mfa.enroll');
    Route::post('/mfa/enroll',         [MfaController::class, 'confirmEnrollment'])->name('mfa.enroll.confirm');
    Route::get('/mfa/challenge',       [MfaController::class, 'showChallenge'])->name('mfa.challenge');
    Route::post('/mfa/challenge',      [MfaController::class, 'verifyChallenge'])->name('mfa.challenge.verify');

    // Authenticated routes
    Route::middleware(['auth:superadmin', 'superadmin.guard'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Tenants
        Route::get('/tenants', [TenantController::class, 'index'])->name('tenants.index');
        Route::get('/tenants/create', [TenantController::class, 'create'])->middleware('superadmin.perm:can_create_tenant')->name('tenants.create');
        Route::post('/tenants', [TenantController::class, 'store'])->middleware('superadmin.perm:can_create_tenant')->name('tenants.store');
        Route::get('/tenants/{tenant}', [TenantController::class, 'show'])->name('tenants.show');
        Route::patch('/tenants/{tenant}/toggle', [TenantController::class, 'toggleStatus'])->name('tenants.toggle');
        Route::patch('/tenants/{tenant}/suspend', [TenantController::class, 'suspend'])->name('tenants.suspend');
        Route::patch('/tenants/{tenant}/unsuspend', [TenantController::class, 'unsuspend'])->name('tenants.unsuspend');
        Route::delete('/tenants/{tenant}', [TenantController::class, 'destroy'])->middleware('superadmin.perm:can_delete_tenant')->name('tenants.destroy');

        // Clone Tenant
        Route::get('/tenants/clone/new', [CloneTenantController::class, 'create'])->middleware('superadmin.perm:can_create_tenant')->name('tenants.clone.create');
        Route::post('/tenants/clone',     [CloneTenantController::class, 'store'])->middleware('superadmin.perm:can_create_tenant')->name('tenants.clone.store');
        Route::get('/tenants/clone/source-channels/{tenant}', [CloneTenantController::class, 'sourceChannels'])->name('tenants.clone.source_channels');
        Route::get('/tenants/clone/status/{job}', [CloneTenantController::class, 'status'])->name('tenants.clone.status');
        Route::get('/tenants/clone/watch/{job}', [CloneTenantController::class, 'watch'])->name('tenants.clone.watch');

        // SSL controls
        Route::post('/tenants/{tenant}/ssl/generate', [SslController::class, 'generate'])->name('tenants.ssl.generate');
        Route::post('/tenants/{tenant}/ssl/renew',    [SslController::class, 'renew'])->name('tenants.ssl.renew');
        Route::post('/tenants/{tenant}/domain/update', [SslController::class, 'updateDomain'])->name('tenants.domain.update');

        // Impersonation
        Route::post('/tenants/{tenant}/impersonate',
            [ImpersonationController::class, 'start']
        )->middleware('superadmin.perm:can_impersonate')->name('tenants.impersonate');
        Route::post('/impersonate/stop', [ImpersonationController::class, 'stop'])->name('impersonate.stop');

        // Plans
        Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');
        Route::post('/plans', [PlanController::class, 'store'])->name('plans.store');
        Route::delete('/plans/{id}', [PlanController::class, 'destroy'])->name('plans.destroy');

        // Announcements
        Route::get('/announcements',          [AnnouncementController::class, 'index'])->name('announcements.index');
        Route::post('/announcements',         [AnnouncementController::class, 'store'])->name('announcements.store');
        Route::delete('/announcements/{id}',  [AnnouncementController::class, 'destroy'])->name('announcements.destroy');

        // Audit log viewer
        Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');
    });
});

// Tenant-aware public storage. Streams from Storage::disk('public'), whose
// root is repointed per-request by SetTenantStorageDisk middleware.
Route::get('/storage/{path}', App\Http\Controllers\TenantStorageController::class)
    ->where('path', '.*')
    ->name('storage.public');

// ── IMPERSONATION (tenant-side accept + stop) ─────────────────────
// Accept is signed; the route NAME must match what
// App\Http\Controllers\SuperAdmin\ImpersonationController::start() generates.
Route::middleware(['web', 'signed'])
    ->get(config('app.admin_url') . '/impersonate/accept', App\Http\Controllers\Admin\ImpersonateAcceptController::class)
    ->name('admin.impersonate.accept');

Route::middleware(['web'])
    ->get('/admin/impersonation/stop', App\Http\Controllers\Admin\ImpersonateStopController::class)
    ->name('admin.impersonate.stop');

// Language switching
Route::post('/language/switch', [App\Http\Controllers\API\LanguageController::class, 'switch'])->name('language.switch');
Route::get('/language/current', [App\Http\Controllers\API\LanguageController::class, 'current'])->name('language.current');

// ── MESSENGER LOGIN ──────────────────────────────────────────
Route::get('/messenger/login',             [App\Http\Controllers\MessengerLoginController::class, 'login'])->name('messenger.login');
Route::get('/messenger/autologin',         [App\Http\Controllers\MessengerLoginController::class, 'login'])->name('messenger.autologin');
Route::get('/customer/messenger/exchange', App\Http\Controllers\BladeAutologinController::class)->name('messenger.exchange');

// ── CHATPION → BAGISTO REDIRECT (store_id 9, 10 only) ────────
Route::get('/chatpion/redirect', App\Http\Controllers\ChatpionRedirectController::class)->name('chatpion.redirect');

// ── ADMIN: STORE PICKUP ───────────────────────────────────────
Route::middleware(['web', 'admin', 'auth:admin'])
    ->prefix(config('app.admin_url') . '/sales/orders')
    ->name('admin.sales.orders.')
    ->group(function () {
        Route::post('{id}/mark-pickup', [\App\Http\Controllers\Admin\StorePickupController::class, 'markAsPickup'])
            ->name('mark_pickup');
    });
