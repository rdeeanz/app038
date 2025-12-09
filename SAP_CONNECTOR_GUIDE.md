# SAP Connector Abstraction Guide

This guide explains how to use the SAP connector abstraction layer with support for OData, RFC/BAPI, and IDoc protocols.

## Overview

The SAP connector abstraction provides a unified interface for integrating with SAP systems using three different protocols:

1. **OData** - RESTful API for SAP services
2. **RFC/BAPI** - Remote Function Calls and Business APIs
3. **IDoc** - Intermediate Documents for data exchange

## Architecture

```
SapConnectorInterface (Interface)
├── ODataSapConnector (OData implementation)
├── RfcBapiSapConnector (RFC/BAPI implementation)
└── IdocSapConnector (IDoc implementation)
```

## Configuration

### Environment Variables

Add to your `.env` file:

```env
# Default connector type
SAP_CONNECTOR=odata

# OData Configuration
SAP_ODATA_BASE_URL=https://sap-server.example.com:443/sap/opu/odata/sap
SAP_ODATA_USERNAME=your_username
SAP_ODATA_PASSWORD=your_password
SAP_ODATA_TIMEOUT=30

# RFC/BAPI Configuration
SAP_RFC_HOST=sap-server.example.com
SAP_RFC_SYSTEM_NUMBER=00
SAP_RFC_CLIENT=100
SAP_RFC_USERNAME=your_username
SAP_RFC_PASSWORD=your_password
SAP_RFC_LANGUAGE=EN

# IDoc Configuration
SAP_IDOC_HOST=sap-server.example.com
SAP_IDOC_SYSTEM_NUMBER=00
SAP_IDOC_CLIENT=100
SAP_IDOC_USERNAME=your_username
SAP_IDOC_PASSWORD=your_password
SAP_IDOC_PORT=3300
SAP_IDOC_FILE_PATH=/path/to/idoc/files
```

## Usage Examples

### Using OData Connector

```php
use App\Modules\ERPIntegration\Services\SapConnectorFactory;

$connector = SapConnectorFactory::create('odata');

// Query entities
$orders = $connector->odataQuery('SalesOrders', [
    'CustomerId' => 'CUST001',
], [
    'select' => ['OrderId', 'OrderDate', 'NetValue'],
    'orderby' => 'OrderDate desc',
    'top' => 10,
]);

// Create entity
$newOrder = $connector->odataCreate('SalesOrders', [
    'CustomerId' => 'CUST001',
    'OrderDate' => '20240115',
    'NetValue' => 1250.50,
]);

// Update entity
$connector->odataUpdate('SalesOrders', '12345', [
    'NetValue' => 1500.00,
]);

// Delete entity
$connector->odataDelete('SalesOrders', '12345');
```

### Using RFC/BAPI Connector

```php
use App\Modules\ERPIntegration\Services\SapConnectorFactory;

$connector = SapConnectorFactory::create('rfc');

// Call RFC function
$result = $connector->rfcCall('RFC_FUNCTION_NAME', [
    'PARAM1' => 'value1',
    'PARAM2' => 'value2',
]);

// Call BAPI
$result = $connector->bapiCall('BAPI_SALESORDER_CREATEFROMDAT2', [
    'ORDER_HEADER_IN' => [
        'DOC_TYPE' => 'OR',
        'SALES_ORG' => '1000',
        'DISTR_CHAN' => '10',
        'DIVISION' => '00',
    ],
    'ORDER_ITEMS_IN' => [
        [
            'ITM_NUMBER' => '10',
            'MATERIAL' => 'PROD001',
            'TARGET_QTY' => '2',
        ],
    ],
]);

// Commit transaction
if ($result['success']) {
    $connector->bapiCommit();
} else {
    $connector->bapiRollback();
}
```

### Using IDoc Connector

```php
use App\Modules\ERPIntegration\Services\SapConnectorFactory;

$connector = SapConnectorFactory::create('idoc');

// Send IDoc
$result = $connector->idocSend('ORDERS05', [
    'E1EDK01' => [
        'CURCY' => 'USD',
        'HWAER' => 'USD',
    ],
    'E1EDKA1' => [
        'PARVW' => 'AG',
        'PARTN' => 'CUST001',
    ],
    'E1EDP01' => [
        [
            'POSEX' => '1',
            'MATNR' => 'PROD001',
            'MENGE' => '2',
            'MEINS' => 'PC',
        ],
    ],
]);

// Get IDoc status
$status = $connector->idocStatus($result['idoc_number']);

// Receive IDoc
$idocData = $connector->idocReceive($idocNumber);
```

## YAML Mapping Service

The YAML mapping service transforms data between application format and SAP format using configuration files.

### Mapping File Structure

```yaml
# config/mappings/order-to-sap.yaml
target: SalesOrder

fields:
  SalesDocument:
    source: order_number
    required: true
    transform:
      type: string
      params:
        max_length: 10

  DocumentDate:
    source: order_date
    transform:
      type: date
      params:
        from: 'Y-m-d'
        to: 'Ymd'

  NetValue:
    source: total_amount
    transform:
      type: number
      params:
        decimals: 2
```

### Using YAML Mapping

```php
use App\Modules\ERPIntegration\Services\YamlMappingService;

$mappingService = app(YamlMappingService::class);

$orderData = [
    'order_number' => 'ORD-001',
    'order_date' => '2024-01-15',
    'total_amount' => 1250.50,
];

$sapData = $mappingService->transform($orderData, 'order-to-sap.yaml');
```

## SyncOrderJob Example

The `SyncOrderJob` demonstrates how to sync orders to SAP using YAML mapping:

```php
use App\Modules\ERPIntegration\Jobs\SyncOrderJob;

// Sync via OData
SyncOrderJob::dispatch('order-123', 'odata', 'order-to-sap.yaml')
    ->onQueue('sap-sync');

// Sync via BAPI
SyncOrderJob::dispatch('order-123', 'bapi', 'order-to-sap-bapi.yaml')
    ->onQueue('sap-sync');

// Sync via IDoc
SyncOrderJob::dispatch('order-123', 'idoc', 'order-to-sap-idoc.yaml')
    ->onQueue('sap-sync');
```

## YAML Mapping Transformations

### Supported Transform Types

1. **date** - Date format conversion
   ```yaml
   transform:
     type: date
     params:
       from: 'Y-m-d'
       to: 'Ymd'
   ```

2. **number** - Number formatting
   ```yaml
   transform:
     type: number
     params:
       decimals: 2
       multiplier: 1
   ```

3. **string** - String manipulation
   ```yaml
   transform:
     type: string
     params:
       max_length: 10
       pad:
         length: 10
         string: '0'
         type: left
   ```

4. **lookup** - Value mapping
   ```yaml
   transform:
     type: lookup
     params:
       mapping:
         'pending': 'P'
         'completed': 'C'
       default: 'P'
   ```

5. **concat** - Concatenate fields
   ```yaml
   transform:
     type: concat
     params:
       fields: ['field1', 'field2']
       separator: '-'
   ```

6. **nested** - Create nested structures
   ```yaml
   transform:
     type: nested
     params:
       mapping:
         SubField1:
           source: field1
         SubField2:
           source: field2
   ```

### Simple Transforms

- `uppercase` - Convert to uppercase
- `lowercase` - Convert to lowercase
- `trim` - Trim whitespace
- `int` - Convert to integer
- `float` - Convert to float
- `bool` - Convert to boolean

## Testing Connections

### Test Single Connector

```php
use App\Modules\ERPIntegration\Services\SapConnectorFactory;

$connector = SapConnectorFactory::create('odata');
$connected = $connector->testConnection();
```

### Test All Connectors

```php
use App\Modules\ERPIntegration\Services\SapConnectorFactory;

$results = SapConnectorFactory::testAllConnections();
// Returns: ['odata' => [...], 'rfc' => [...], 'idoc' => [...]]
```

## Dependency Injection

The connector can be injected using the interface:

```php
use App\Modules\ERPIntegration\Connectors\SapConnectorInterface;

class OrderService
{
    public function __construct(
        protected SapConnectorInterface $sapConnector
    ) {}
}
```

## Error Handling

All connector methods throw exceptions on failure:

```php
try {
    $result = $connector->odataCreate('SalesOrders', $data);
} catch (\Exception $e) {
    Log::error('SAP operation failed', [
        'error' => $e->getMessage(),
    ]);
    
    // Handle error
}
```

## Best Practices

1. **Use YAML Mapping**: Always use YAML mapping files for data transformation
2. **Handle Errors**: Wrap connector calls in try-catch blocks
3. **Log Operations**: Log all SAP operations for debugging
4. **Use Jobs**: Use queue jobs for async SAP operations
5. **Test Connections**: Test connections before production use
6. **Validate Data**: Validate data before sending to SAP
7. **Use Transactions**: Use BAPI commit/rollback for transactions

## Mapping Files

Three example mapping files are provided:

1. `config/mappings/order-to-sap.yaml` - OData mapping
2. `config/mappings/order-to-sap-bapi.yaml` - BAPI mapping
3. `config/mappings/order-to-sap-idoc.yaml` - IDoc mapping

## Next Steps

1. Configure SAP connection credentials
2. Create custom mapping files for your data structures
3. Implement error handling and retry logic
4. Set up monitoring and alerts
5. Create unit tests for connectors
6. Document your specific SAP integration requirements

