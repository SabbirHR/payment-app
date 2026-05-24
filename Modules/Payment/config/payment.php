<?php

return [
    'default'      => env('PAYMENT_DEFAULT_GATEWAY', 'sslcommerz'),
    'table_prefix' => env('PAYMENT_TABLE_PREFIX', ''),

    'gateways' => [
        'sslcommerz' => [
            'store_id'   => env('PAYMENT_SSL_COMMERZ_STORE_ID'),
            'store_password' => env('PAYMENT_SSL_COMMERZ_STORE_PASSWORD'),
            'sandbox'    => env('PAYMENT_SSL_COMMERZ_SANDBOX', true),
        ],
        'stripe' => [
            'key'    => env('PAYMENT_STRIPE_KEY'),
            'secret' => env('PAYMENT_STRIPE_SECRET'),
        ],
        'paypal' => [
            'client_id' => env('PAYMENT_PAYPAL_CLIENT_ID'),
            'secret'    => env('PAYMENT_PAYPAL_SECRET'),
        ],
        'bikash' => [
            'app_key'    => env('PAYMENT_BIKASH_APP_KEY'),
            'app_secret' => env('PAYMENT_BIKASH_APP_SECRET'),
            'username'   => env('PAYMENT_BIKASH_USERNAME'),
            'password'   => env('PAYMENT_BIKASH_PASSWORD'),
            'sandbox'    => env('PAYMENT_BIKASH_SANDBOX', true),
        ],
        'nagad' => [
            'merchant_id'      => env('PAYMENT_NAGAD_MERCHANT_ID'),
            'merchant_number'  => env('PAYMENT_NAGAD_MERCHANT_NUMBER'),
            'public_key'       => env('PAYMENT_NAGAD_PUBLIC_KEY'),
            'private_key'      => env('PAYMENT_NAGAD_PRIVATE_KEY'),
            'sandbox'          => env('PAYMENT_NAGAD_SANDBOX', true),
        ],
        'aamarpay' => [
            'store_id'      => env('PAYMENT_AAMARPAY_STORE_ID'),
            'signature_key' => env('PAYMENT_AAMARPAY_SIGNATURE_KEY'),
            'sandbox'       => env('PAYMENT_AAMARPAY_SANDBOX', true),
        ],
        'shurjopay' => [
            'username' => env('PAYMENT_SHURJOPAY_USERNAME'),
            'password' => env('PAYMENT_SHURJOPAY_PASSWORD'),
            'prefix'   => env('PAYMENT_SHURJOPAY_PREFIX', 'sp'),
            'sandbox'  => env('PAYMENT_SHURJOPAY_SANDBOX', true),
        ],
    ],
];
