<?php
use Stancl\Tenancy\Database\Models\Tenant;
use Stancl\Tenancy\Database\Models\Domain;
return [
    'tenant_model' => \App\Models\Tenant::class,
    'domain_model' => Domain::class,
    'central_domains' => [
        '162.43.75.135.nip.io',
        'localhost',
        '127.0.0.1',
        'snssale.com',
        'www.snssale.com',
    ],
    'bootstrappers' => [
        Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
    ],
    'database' => [
        'based_on_tenant_id' => false,
        'central_connection' => 'mysql',
        'template_tenant_connection' => 'mysql',
        'prefix' => 'tenant_',
        'suffix' => '',
        'managers' => [
            'mysql' => Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
        ],
    ],
    'cache' => [
        'tag_base' => 'tenant',
    ],
    'filesystem' => [
        'suffix_base' => 'tenant',
        'disks' => ['local', 'public'],
        'root_override' => [
            'local' => '%storage_path%/app/',
            'public' => '%storage_path%/app/public/',
        ],
    ],
    'features' => [],
    'migration_parameters' => [
        '--force' => true,
        '--path' => [
            '/var/www/bagisto/database/migrations',
            '/var/www/bagisto/database/migrations/tenant',
            '/var/www/bagisto/packages/Webkul/Pos/src/Database/Migrations',
            'packages/Webkul/Shipping/src/Database/Migrations',
            'packages/Webkul/Waste/src/Database/Migrations',
            'packages/Fujiba/Reorder/src/Database/Migrations',
            'packages/Fujiba/InventoryControl/src/Database/Migrations',
            'packages/Fujiba/Notification/database/migrations/tenant',
            'packages/Fujiba/OrdersAdmin/src/Database/Migrations',
            'packages/Fujiba/AutoLogin/database/migrations',
            'packages/Fujiba/TikTokShop/database/migrations/tenant',
            'packages/Fujiba/FacebookCatalog/database/migrations/tenant',
            'packages/Fujiba/Accounting/src/Database/Migrations',
            'packages/Fujiba/SagawaDelivery/src/Database/Migrations',
        ],
        '--realpath' => true,
    ],
    'seeder_parameters' => [
        '--class' => 'DatabaseSeeder',
        '--force' => true,
    ],
];
