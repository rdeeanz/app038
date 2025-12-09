<?php

return [
    'base_url' => env('ERP_BASE_URL', 'https://api.erp.example.com'),
    'api_key' => env('ERP_API_KEY', null),
    'timeout' => env('ERP_TIMEOUT', 30),
    'retry_attempts' => env('ERP_RETRY_ATTEMPTS', 3),
];

