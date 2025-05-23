<?php

return [
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // URL layanan UserService
    // 'user_service' => [
    //     'url' => env('USER_SERVICE_URL', 'http://user-service.test'),
    // ],
    'user_service' => [
    'base_url' => env('USER_SERVICE_URL', 'http://localhost:8001'),
    ],  
    
    // URL layanan OrderService
    // 'order_service' => [
    //     'url' => env('ORDER_SERVICE_URL', 'http://order-service.test'),
    // ],
    'order_service' => [
        'url' => env('ORDER_SERVICE_URL', 'http://localhost:8003'),
    ],
];