<?php

// tests/POS/Helpers.php

use App\Models\User as CentralUser;
use App\Models\UserChannelMap;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Stancl\Tenancy\Tenancy;
use Webkul\Pos\Models\User as PosUser;

function mockTenancy(): Mockery\MockInterface
{
    $mock = Mockery::mock(Tenancy::class);
    $mock->shouldReceive('initialize')->zeroOrMoreTimes()->andReturnNull();
    $mock->shouldReceive('end')->zeroOrMoreTimes()->andReturnNull();
    $mock->initialized = false;
    $mock->tenant      = null;
    app()->instance(Tenancy::class, $mock);

    return $mock;
}

function seedTenant(string $id): void
{
    DB::table('tenants')->insertOrIgnore([
        'id'         => $id,
        'data'       => json_encode(['name' => 'Test Tenant']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function seedChannel(int $id = 1): void
{
    DB::statement('SET FOREIGN_KEY_CHECKS=0');
    DB::table('channels')->insertOrIgnore([
        'id'                => $id,
        'code'              => 'test',
        'default_locale_id' => 1,
        'base_currency_id'  => 1,
        'created_at'        => now(),
        'updated_at'        => now(),
    ]);
    DB::statement('SET FOREIGN_KEY_CHECKS=1');
}

function loginAsPosUser(array $overrides = []): array
{
    $email     = $overrides['email'] ?? fake()->unique()->safeEmail();
    $password  = $overrides['password'] ?? 'password123';
    $tenantId  = $overrides['tenant_id'] ?? ('tenant-' . uniqid());
    $isActive  = $overrides['is_active'] ?? true;
    $posStatus = $overrides['pos_status'] ?? 1;

    seedTenant($tenantId);

    $central = CentralUser::create([
        'name'     => 'POS User',
        'email'    => $email,
        'password' => Hash::make($password),
    ]);

    UserChannelMap::create([
        'user_id'    => $central->id,
        'channel_id' => 1,
        'tenant_id'  => $tenantId,
        'is_active'  => $isActive,
    ]);

    DB::table('pos_users')->insertOrIgnore([
        'outlet_id'  => null,
        'username'   => $email,
        'firstname'  => 'POS',
        'lastname'   => 'User',
        'email'      => $email,
        'password'   => Hash::make($password),
        'image'      => '',
        'status'     => $posStatus,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $posUser = PosUser::where('email', $email)->firstOrFail();

    $token = JWTAuth::claims([
        'tenant_id'           => $tenantId,
        'channel_id'          => 1,
        'inventory_source_id' => null,
    ])->fromUser($posUser);

    return compact('central', 'posUser', 'token', 'tenantId', 'email', 'password');
}
