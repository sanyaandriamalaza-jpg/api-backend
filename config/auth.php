<?php

return [
    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'api' => [
            'driver' => 'sanctum',
            'provider' => 'multi_users', // Provider personnalisé
        ],

        // Guards spécifiques pour chaque type d'utilisateur
        'admin' => [
            'driver' => 'sanctum',
            'provider' => 'admin_users',
        ],

        'basic' => [
            'driver' => 'sanctum',
            'provider' => 'basic_users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],

        'admin_users' => [
            'driver' => 'eloquent',
            'model' => App\Models\AdminUser::class,
        ],

        'basic_users' => [
            'driver' => 'eloquent',
            'model' => App\Models\BasicUser::class,
        ],

        'multi_users' => [
            'driver' => 'multi_user', // Provider personnalisé
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_resets',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,
];