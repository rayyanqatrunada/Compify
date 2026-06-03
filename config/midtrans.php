<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Midtrans Environment
    |--------------------------------------------------------------------------
    | false = Sandbox
    | true  = Production
    */
    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),

    /*
    |--------------------------------------------------------------------------
    | Midtrans Access Keys
    |--------------------------------------------------------------------------
    | Server key hanya dipakai di backend.
    | Client key nanti dipakai kalau suatu saat mau pakai Snap popup frontend.
    */
    'server_key' => env('MIDTRANS_SERVER_KEY'),
    'client_key' => env('MIDTRANS_CLIENT_KEY'),
    'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
];