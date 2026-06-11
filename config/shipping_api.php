<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Shipping API Preparation
    |--------------------------------------------------------------------------
    | Tahap ini hanya menyiapkan konfigurasi. Perhitungan ongkir real-time
    | akan dipakai di tahap berikutnya.
    */
    'default_provider' => env('SHIPPING_API_PROVIDER', 'manual'),

    'enabled' => filter_var(env('SHIPPING_API_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    'fallback_manual' => filter_var(env('SHIPPING_API_FALLBACK_MANUAL', true), FILTER_VALIDATE_BOOLEAN),

    'cache_minutes' => (int) env('SHIPPING_API_CACHE_MINUTES', 30),

    'default_couriers' => array_filter(array_map(
        'trim',
        explode(',', env('SHIPPING_API_DEFAULT_COURIERS', 'jne,jnt,sicepat,anteraja,pos'))
    )),

    'providers' => [
        'rajaongkir' => [
            'api_key' => env('SHIPPING_RAJAONGKIR_API_KEY'),
            'base_url' => env('SHIPPING_RAJAONGKIR_BASE_URL', 'https://rajaongkir.komerce.id/api/v1/'),
        ],

        'biteship' => [
            'api_key' => env('SHIPPING_BITESHIP_API_KEY'),
            'base_url' => env('SHIPPING_BITESHIP_BASE_URL', 'https://api.biteship.com'),
        ],
    ],
];
