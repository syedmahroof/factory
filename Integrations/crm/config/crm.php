<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Inbound CRM API
    |--------------------------------------------------------------------------
    | This portal is the service provider: the third-party CRM calls us.
    | Nothing here dials out to the CRM.
    */

    'routes' => [
        'prefix' => env('CRM_ROUTE_PREFIX', 'api/crm'),
        'name' => 'CRM::',
        'middleware' => ['api', 'crm.auth', 'throttle:crm'],
    ],

    'company_id' => (int) env('CRM_COMPANY_ID', 4),

    'rate_limit' => (int) env('CRM_RATE_LIMIT', 120),

    'logging' => (bool) env('CRM_LOG_REQUESTS', false),
];
