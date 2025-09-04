<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Payment Provider
    |--------------------------------------------------------------------------
    |
    | This option controls the default payment provider that will be used
    | when no specific provider is requested.
    |
    */
    'default_provider' => env('PAYMENT_DEFAULT_PROVIDER', 'mock'),

    /*
    |--------------------------------------------------------------------------
    | Payment Providers Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for various payment providers.
    |
    */
    'providers' => [
        'mock' => [
            'enabled' => true,
            'success_rate' => env('MOCK_PAYMENT_SUCCESS_RATE', 90), // Percentage
        ],

        'stripe' => [
            'enabled' => env('STRIPE_ENABLED', false),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        ],

        'paypal' => [
            'enabled' => env('PAYPAL_ENABLED', false),
            'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Settings
    |--------------------------------------------------------------------------
    |
    | General payment configuration options.
    |
    */
    'currency' => env('PAYMENT_CURRENCY', 'USD'),
    'minimum_amount' => env('PAYMENT_MINIMUM_AMOUNT', 1.00),
    'maximum_amount' => env('PAYMENT_MAXIMUM_AMOUNT', 10000.00),

    /*
    |--------------------------------------------------------------------------
    | Refund Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for payment refunds.
    |
    */
    'refund' => [
        'enabled' => env('REFUND_ENABLED', true),
        'time_limit_days' => env('REFUND_TIME_LIMIT_DAYS', 30), // Days after donation
    ],
];
