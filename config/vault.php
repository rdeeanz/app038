<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Vault Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for HashiCorp Vault integration
    |
    */

    'address' => env('VAULT_ADDR', 'http://localhost:8200'),
    'token' => env('VAULT_TOKEN', ''),
    
    /*
    |--------------------------------------------------------------------------
    | Authentication Method
    |--------------------------------------------------------------------------
    |
    | Supported: token, kubernetes, approle
    |
    */
    'auth_method' => env('VAULT_AUTH_METHOD', 'token'),

    /*
    |--------------------------------------------------------------------------
    | AppRole Authentication
    |--------------------------------------------------------------------------
    |
    | Used when auth_method is 'approle'
    |
    */
    'approle' => [
        'role_id' => env('VAULT_ROLE_ID', ''),
        'secret_id' => env('VAULT_SECRET_ID', ''),
        'mount_path' => env('VAULT_APPROLE_MOUNT', 'approle'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Kubernetes Authentication
    |--------------------------------------------------------------------------
    |
    | Used when auth_method is 'kubernetes'
    |
    */
    'kubernetes' => [
        'role' => env('VAULT_K8S_ROLE', 'laravel-app'),
        'mount_path' => env('VAULT_K8S_MOUNT', 'kubernetes'),
        'service_account_token_path' => env('VAULT_K8S_TOKEN_PATH', '/var/run/secrets/kubernetes.io/serviceaccount/token'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Secrets Engine Configuration
    |--------------------------------------------------------------------------
    |
    */
    'secrets' => [
        'kv_path' => env('VAULT_KV_PATH', 'secret/data/app038'),
        'kv_version' => env('VAULT_KV_VERSION', '2'),
        'database_path' => env('VAULT_DB_PATH', 'database/creds/app038-readwrite'),
        'transit_path' => env('VAULT_TRANSIT_PATH', 'transit'),
        'transit_key' => env('VAULT_TRANSIT_KEY', 'app038-key'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Cache secrets to reduce Vault API calls
    |
    */
    'cache' => [
        'enabled' => env('VAULT_CACHE_ENABLED', true),
        'ttl' => env('VAULT_CACHE_TTL', 3600), // 1 hour
        'store' => env('VAULT_CACHE_STORE', 'redis'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Connection Timeout
    |--------------------------------------------------------------------------
    |
    */
    'timeout' => env('VAULT_TIMEOUT', 5),

    /*
    |--------------------------------------------------------------------------
    | TLS Configuration
    |--------------------------------------------------------------------------
    |
    */
    'tls' => [
        'verify' => env('VAULT_TLS_VERIFY', true),
        'cert_path' => env('VAULT_TLS_CERT_PATH', ''),
        'key_path' => env('VAULT_TLS_KEY_PATH', ''),
        'ca_path' => env('VAULT_TLS_CA_PATH', ''),
    ],
];

