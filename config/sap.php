<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default SAP Connector
    |--------------------------------------------------------------------------
    |
    | This option controls the default SAP connector driver that will be used
    | by the application. Supported: "odata", "rfc", "idoc"
    |
    */

    'default' => env('SAP_CONNECTOR', 'odata'),

    /*
    |--------------------------------------------------------------------------
    | OData Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for SAP OData connector
    |
    */

    'odata' => [
        'base_url' => env('SAP_ODATA_BASE_URL'),
        'username' => env('SAP_ODATA_USERNAME'),
        'password' => env('SAP_ODATA_PASSWORD'),
        'timeout' => env('SAP_ODATA_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | RFC/BAPI Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for SAP RFC/BAPI connector
    |
    */

    'rfc' => [
        'host' => env('SAP_RFC_HOST'),
        'system_number' => env('SAP_RFC_SYSTEM_NUMBER', '00'),
        'client' => env('SAP_RFC_CLIENT', '100'),
        'username' => env('SAP_RFC_USERNAME'),
        'password' => env('SAP_RFC_PASSWORD'),
        'language' => env('SAP_RFC_LANGUAGE', 'EN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | IDoc Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for SAP IDoc connector
    |
    */

    'idoc' => [
        'host' => env('SAP_IDOC_HOST'),
        'system_number' => env('SAP_IDOC_SYSTEM_NUMBER', '00'),
        'client' => env('SAP_IDOC_CLIENT', '100'),
        'username' => env('SAP_IDOC_USERNAME'),
        'password' => env('SAP_IDOC_PASSWORD'),
        'port' => env('SAP_IDOC_PORT', '3300'),
        'file_path' => env('SAP_IDOC_FILE_PATH', storage_path('app/sap/idoc')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Mapping Configuration
    |--------------------------------------------------------------------------
    |
    | Default mapping files directory
    |
    */

    'mappings' => [
        'path' => base_path('config/mappings'),
        'default' => env('SAP_DEFAULT_MAPPING', 'order-to-sap.yaml'),
    ],
];

