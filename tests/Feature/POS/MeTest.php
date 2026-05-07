<?php

// tests/Feature/POS/MeTest.php

use App\Models\UserChannelMap;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Stancl\Tenancy\Tenancy;

beforeEach(function () {
    mockTenancy();
    Cache::flush();
});

it('returns 200 with user fields on valid token', function () {
    $ctx = loginAsPosUser();

    $this->withToken($ctx['token'])
         ->getJson('/api/v1/pos/me')
         ->assertOk()
         ->assertJsonStructure(['id', 'email', 'tenant_id', 'channel_id']);
});

it('returns 401 when no token is provided', function () {
    $this->getJson('/api/v1/pos/me')->assertUnauthorized();
});

it('returns 401 for an expired token', function () {
    $ctx = loginAsPosUser();

    JWTAuth::factory()->setTTL(1);
    $expiredToken = JWTAuth::claims([
        'tenant_id'           => $ctx['tenantId'],
        'channel_id'          => 1,
        'inventory_source_id' => null,
    ])->fromUser($ctx['posUser']);
    JWTAuth::factory()->setTTL((int) config('jwt.ttl', 60));

    $this->travelTo(now()->addMinutes(5));

    $this->withToken($expiredToken)
         ->getJson('/api/v1/pos/me')
         ->assertUnauthorized();

    $this->travelBack();
});

it('returns 403 access_revoked when mapping is_active flipped after login', function () {
    $ctx = loginAsPosUser();

    UserChannelMap::where('user_id', $ctx['central']->id)->update(['is_active' => false]);

    $this->withToken($ctx['token'])
         ->getJson('/api/v1/pos/me')
         ->assertForbidden()
         ->assertJson(['error' => 'access_revoked']);
});

it('returns 401 tenant_not_found when tenant deleted after login', function () {
    $ctx = loginAsPosUser();

    DB::statement('SET FOREIGN_KEY_CHECKS=0');
    DB::table('tenants')->where('id', $ctx['tenantId'])->delete();
    DB::statement('SET FOREIGN_KEY_CHECKS=1');

    $this->withToken($ctx['token'])
         ->getJson('/api/v1/pos/me')
         ->assertUnauthorized()
         ->assertJson(['error' => 'tenant_not_found']);
});

it('calls tenancy end exactly once after a me attempt', function () {
    $ctx = loginAsPosUser();

    $spy = Mockery::mock(Tenancy::class);
    $spy->shouldReceive('initialize')->once()->andReturnNull();
    $spy->shouldReceive('end')->once()->andReturnNull();
    app()->instance(Tenancy::class, $spy);

    $this->withToken($ctx['token'])
         ->getJson('/api/v1/pos/me')
         ->assertOk();
});
