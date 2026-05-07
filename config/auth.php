<?php

return [

    'defaults' => [
        'guard' => 'customer',
        'passwords' => 'customers',
    ],

    'guards' => [

        'superadmin' => [
            'driver' => 'session',
            'provider' => 'superadmins',
        ],

        'customer' => [
            'driver' => 'session',
            'provider' => 'customers',
        ],

        'admin' => [
            'driver' => 'session',
            'provider' => 'admins',
        ],

        'pos' => [
            'driver' => 'jwt',
            'provider' => 'pos_agents',
        ],

        'pos_user' => [
            'driver' => 'jwt',
            'provider' => 'pos_agents',
        ],

    ],

    'providers' => [

        'superadmins' => [
            'driver' => 'eloquent',
            'model' => App\Models\SuperAdmin::class,
        ],

        'customers' => [
            'driver' => 'eloquent',
            'model' => Webkul\Customer\Models\Customer::class,
        ],

        'admins' => [
            'driver' => 'eloquent',
            'model' => Webkul\User\Models\Admin::class,
        ],

        'pos_agents' => [
            'driver' => 'eloquent',
            'model'  => Webkul\Pos\Models\User::class,
        ],
    ],

    'passwords' => [

        'customers' => [
            'provider' => 'customers',
            'table' => 'customer_password_resets',
            'expire' => 60,
            'throttle' => 60,
        ],

        'admins' => [
            'provider' => 'admins',
            'table' => 'admin_password_resets',
            'expire' => 60,
            'throttle' => 60,
        ],

    ],

];