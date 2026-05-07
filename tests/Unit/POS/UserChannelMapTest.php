<?php

// tests/Unit/POS/UserChannelMapTest.php

use App\Models\User as CentralUser;
use App\Models\UserChannelMap;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

function makeUser(): CentralUser
{
    return CentralUser::create([
        'name'     => fake()->name(),
        'email'    => fake()->unique()->safeEmail(),
        'password' => Hash::make('password'),
    ]);
}

function makeMap(CentralUser $user, array $state = []): UserChannelMap
{
    seedTenant($state['tenant_id'] ?? 'tenant-' . uniqid());

    return UserChannelMap::create(array_merge([
        'user_id'    => $user->id,
        'channel_id' => 1,
        'tenant_id'  => $state['tenant_id'] ?? ('tenant-' . uniqid()),
        'is_active'  => true,
    ], $state));
}

it('scopeActive excludes rows where is_active is false', function () {
    $user = makeUser();
    makeMap($user, ['is_active' => false, 'tenant_id' => 'ten-' . uniqid()]);

    expect(UserChannelMap::active()->count())->toBe(0);
});

it('scopeActive excludes soft-deleted rows', function () {
    $user = makeUser();
    $map  = makeMap($user, ['tenant_id' => 'ten-' . uniqid()]);
    $map->delete();

    expect(UserChannelMap::active()->count())->toBe(0);
});

it('scopeActive includes rows that are active and not deleted', function () {
    $user = makeUser();
    makeMap($user, ['tenant_id' => 'ten-' . uniqid()]);

    expect(UserChannelMap::active()->count())->toBe(1);
});

it('throws QueryException on duplicate user_id + channel_id', function () {
    $user     = makeUser();
    $tenantId = 'ten-' . uniqid();
    seedTenant($tenantId);

    UserChannelMap::create([
        'user_id'    => $user->id,
        'channel_id' => 1,
        'tenant_id'  => $tenantId,
        'is_active'  => true,
    ]);

    expect(fn () => UserChannelMap::create([
        'user_id'    => $user->id,
        'channel_id' => 1,
        'tenant_id'  => $tenantId,
        'is_active'  => true,
    ]))->toThrow(QueryException::class);
});
