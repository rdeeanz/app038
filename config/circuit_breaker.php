<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Circuit Breaker Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for circuit breaker middleware to prevent cascading failures
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Failure Threshold
    |--------------------------------------------------------------------------
    |
    | Number of consecutive failures before opening the circuit
    |
    */
    'failure_threshold' => env('CIRCUIT_BREAKER_FAILURE_THRESHOLD', 5),

    /*
    |--------------------------------------------------------------------------
    | Success Threshold
    |--------------------------------------------------------------------------
    |
    | Number of consecutive successes in half-open state to close the circuit
    |
    */
    'success_threshold' => env('CIRCUIT_BREAKER_SUCCESS_THRESHOLD', 2),

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | Time in seconds to wait before attempting to reset from open to half-open
    |
    */
    'timeout' => env('CIRCUIT_BREAKER_TIMEOUT', 60),

    /*
    |--------------------------------------------------------------------------
    | Cache Prefix
    |--------------------------------------------------------------------------
    |
    | Prefix for cache keys used by circuit breaker
    |
    */
    'cache_prefix' => env('CIRCUIT_BREAKER_CACHE_PREFIX', 'circuit_breaker'),

    /*
    |--------------------------------------------------------------------------
    | Service-Specific Configuration
    |--------------------------------------------------------------------------
    |
    | Override default settings for specific services
    |
    */
    'services' => [
        'erp-integration' => [
            'failure_threshold' => 3,
            'timeout' => 120,
        ],
        'database' => [
            'failure_threshold' => 5,
            'timeout' => 30,
        ],
        'external-api' => [
            'failure_threshold' => 5,
            'timeout' => 60,
        ],
    ],
];

