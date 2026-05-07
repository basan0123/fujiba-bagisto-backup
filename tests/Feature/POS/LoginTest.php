<?php

// tests/Feature/POS/LoginTest.php

use App\Models\User as CentralUser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Stancl\Tenancy\Tenancy;

use function Pest\Laravel\postJson;

beforeEach(function () {
    mockTenancy();
    Cache::flush();
});

it('returns 200 with token and required keys on valid credentials', function () {
    $ctx = loginAsPosUser();

    postJson('/api/v1/pos/login', ['email' => $ctx['email'], 'password' => $ctx['password']])
        ->assertOk()
        ->assertJsonStructure(['token', 'tenant_id', 'channel_id', 'pos_url']);
});

it('returns 401 on wrong password', function () {
    $ctx = loginAsPosUser();

    postJson('/api/v1/pos/login', ['email' => $ctx['email'], 'password' => 'wrong'])
        ->assertUnauthorized();
});

it('returns 403 access_revoked when email has no channel mapping', function () {
    CentralUser::create([
        'name'     => 'No Map',
        'email'    => 'nomap@example.com',
        'password' => Hash::make('password123'),
    ]);

    postJson('/api/v1/pos/login', ['email' => 'nomap@example.com', 'password' => 'password123'])
        ->assertForbidden()
        ->assertJson(['error' => 'access_revoked']);
});

it('returns 403 access_revoked when is_active is false', function () {
    $ctx = loginAsPosUser(['is_active' => false]);

    postJson('/api/v1/pos/login', ['email' => $ctx['email'], 'password' => $ctx['password']])
        ->assertForbidden()
        ->assertJson(['error' => 'access_revoked']);
});

it('returns 403 pos_user_inactive when pos user status is disabled', function () {
    $ctx = loginAsPosUser(['pos_status' => 0]);

    postJson('/api/v1/pos/login', ['email' => $ctx['email'], 'password' => $ctx['password']])
        ->assertForbidden()
        ->assertJson(['error' => 'pos_user_inactive']);
});

it('returns 422 with errors.email when email is missing', function () {
    postJson('/api/v1/pos/login', ['password' => 'password123'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('returns 422 with errors.password when password is missing', function () {
    postJson('/api/v1/pos/login', ['email' => 'test@example.com'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('returns 429 on the 6th request from the same email and IP', function () {
    $ctx = loginAsPosUser(['email' => 'throttle-test@example.com']);

    foreach (range(1, 5) as $_) {
        postJson('/api/v1/pos/login', [
            'email'    => 'throttle-test@example.com',
            'password' => $ctx['password'],
        ])->assertOk();
    }

    postJson('/api/v1/pos/login', [
        'email'    => 'throttle-test@example.com',
        'password' => $ctx['password'],
    ])->assertStatus(429);
});

it('returns 401 tenant_not_found when tenant record does not exist', function () {
    $ctx = loginAsPosUser();

    DB::statement('SET FOREIGN_KEY_CHECKS=0');
    DB::table('tenants')->where('id', $ctx['tenantId'])->delete();
    DB::statement('SET FOREIGN_KEY_CHECKS=1');

    postJson('/api/v1/pos/login', ['email' => $ctx['email'], 'password' => $ctx['password']])
        ->assertUnauthorized()
        ->assertJson(['error' => 'tenant_not_found']);
});

it('calls tenancy end exactly once after a successful login attempt', function () {
    $ctx = loginAsPosUser();

    $spy = Mockery::mock(Tenancy::class);
    $spy->shouldReceive('initialize')->once()->andReturnNull();
    $spy->shouldReceive('end')->once()->andReturnNull();
    app()->instance(Tenancy::class, $spy);

    postJson('/api/v1/pos/login', ['email' => $ctx['email'], 'password' => $ctx['password']])
        ->assertOk();
});
