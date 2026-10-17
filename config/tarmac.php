<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trial
    |--------------------------------------------------------------------------
    |
    | Every new account gets a free trial of the given plan.
    |
    */

    'trial_days' => (int) env('TARMAC_TRIAL_DAYS', 14),

    'trial_plan' => 'pro',

    /*
    |--------------------------------------------------------------------------
    | Plans (public pricing, in euros)
    |--------------------------------------------------------------------------
    */

    'plans' => [
        'free' => ['monthly' => 0, 'yearly' => null],
        'pro' => ['monthly' => 4.99, 'yearly' => 39],
        'tuner' => ['monthly' => 9.99, 'yearly' => 79],
        'garage' => ['monthly' => 29, 'yearly' => null],
    ],

    /*
    |--------------------------------------------------------------------------
    | RevenueCat
    |--------------------------------------------------------------------------
    |
    | The webhook is authenticated with "Authorization: Bearer <secret>".
    | Store product identifiers are mapped to a plan and a billing period.
    |
    */

    'revenuecat' => [
        'webhook_secret' => env('REVENUECAT_WEBHOOK_SECRET'),

        'products' => [
            'tarmac_pro_monthly' => ['plan' => 'pro', 'period' => 'monthly'],
            'tarmac_pro_yearly' => ['plan' => 'pro', 'period' => 'yearly'],
            'tarmac_tuner_monthly' => ['plan' => 'tuner', 'period' => 'monthly'],
            'tarmac_tuner_yearly' => ['plan' => 'tuner', 'period' => 'yearly'],
            'tarmac_garage_monthly' => ['plan' => 'garage', 'period' => 'monthly'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Stripe (web checkout)
    |--------------------------------------------------------------------------
    |
    | Web checkout stays disabled until both keys are configured.
    |
    */

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Anthropic (DTC explanations)
    |--------------------------------------------------------------------------
    |
    | When a trouble code is missing from the knowledge base and a key is set,
    | Claude is asked for an explanation that is then cached in the database.
    |
    */

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-5-5'),
        'url' => env('ANTHROPIC_URL', 'https://api.anthropic.com/v1/messages'),
        'effort' => env('ANTHROPIC_EFFORT', 'medium'),
        'fallbacks' => (bool) env('ANTHROPIC_FALLBACKS', true),
        'timeout' => (int) env('ANTHROPIC_TIMEOUT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync limits
    |--------------------------------------------------------------------------
    */

    'sync' => [
        'max_records_per_type' => 500,
    ],

];
