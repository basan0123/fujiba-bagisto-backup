<?php

// tests/Unit/POS/PosUserObserverTest.php

use App\Models\User as CentralUser;
use App\Models\UserChannelMap;
use App\Observers\PosUserObserver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Pos\Models\User as PosUser;

function makePosUser(string $email): PosUser
{
    DB::table('pos_users')->insertOrIgnore([
        'outlet_id'  => null,
        'email'      => $email,
        'username'   => $email,
        'firstname'  => 'Test',
        'lastname'   => 'User',
        'password'   => bcrypt('password'),
        'image'      => '',
        'status'     => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return PosUser::where('email', $email)->firstOrFail();
}

beforeEach(function () {
    seedChannel(1);
});

it('inserts user_channel_map row when a POS user is created', function () {
    $tenantId = 'obs-tenant-' . uniqid();
    seedTenant($tenantId);
    app()->instance('tenant', (object) ['id' => $tenantId]);

    $posUser = makePosUser('obs-create@example.com');

    (new PosUserObserver)->created($posUser);

    expect(UserChannelMap::count())->toBe(1);
    expect(UserChannelMap::first()->tenant_id)->toBe($tenantId);
});

it('does not create a duplicate mapping when observer fires twice (idempotent)', function () {
    $tenantId = 'obs-idem-' . uniqid();
    seedTenant($tenantId);
    app()->instance('tenant', (object) ['id' => $tenantId]);

    $posUser   = makePosUser('obs-idem@example.com');
    $observer  = new PosUserObserver;

    $observer->created($posUser);
    $observer->created($posUser);

    expect(UserChannelMap::count())->toBe(1);
});

it('logs error and does not throw when DB write fails', function () {
    Log::spy();

    // Bind tenant with an ID that has no record → tenant_id FK will fail
    app()->instance('tenant', (object) ['id' => 'ghost-tenant-' . uniqid()]);

    $posUser = makePosUser('obs-fail@example.com');

    expect(fn () => (new PosUserObserver)->created($posUser))
        ->not->toThrow(\Throwable::class);

    Log::shouldHaveReceived('error')->once();
});

it('uses mysql connection for UserChannelMap writes', function () {
    expect((new UserChannelMap)->getConnectionName())->toBe('mysql');
    expect((new CentralUser)->getConnectionName())->toBe('mysql');
});
