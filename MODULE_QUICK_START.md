# Module Quick Start Guide

This guide provides quick examples for using each module in the application.

## ERP Integration Module

### Sync Data with ERP System

```php
use App\Modules\ERPIntegration\Services\ERPIntegrationService;

$erpService = app(ERPIntegrationService::class);
$result = $erpService->syncData([
    'type' => 'products',
    'endpoint' => '/api/products',
    'params' => ['limit' => 100],
]);
```

### Check Sync Status

```php
$status = $erpService->getSyncStatus($syncId);
```

### Test Connection

```php
$result = $erpService->testConnection();
```

## Sales Module

### Create an Order

```php
use App\Modules\Sales\Services\SalesService;

$salesService = app(SalesService::class);
$order = $salesService->createOrder([
    'customer_id' => '123',
    'items' => [
        ['product_id' => '456', 'quantity' => 2, 'price' => 29.99],
    ],
    'total' => 59.98,
    'status' => 'pending',
]);
```

### Get Sales Statistics

```php
$stats = $salesService->getStatistics([
    'date_from' => '2024-01-01',
    'date_to' => '2024-12-31',
]);
```

## Inventory Module

### Create a Product

```php
use App\Modules\Inventory\Services\InventoryService;

$inventoryService = app(InventoryService::class);
$product = $inventoryService->createProduct([
    'name' => 'Product Name',
    'sku' => 'SKU-001',
    'price' => 99.99,
    'stock' => 100,
    'min_stock' => 10,
]);
```

### Update Stock

```php
$product = $inventoryService->updateStock($productId, [
    'quantity' => 50,
    'reason' => 'purchase',
]);
```

### Get Low Stock Alerts

```php
$alerts = $inventoryService->getLowStockAlerts();
```

## Auth Module

### Register a User

```php
use App\Modules\Auth\Services\AuthService;

$authService = app(AuthService::class);
$result = $authService->register([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => 'password123',
    'role' => 'user',
]);
```

### Login

```php
$result = $authService->login([
    'email' => 'john@example.com',
    'password' => 'password123',
]);
```

## Monitoring Module

### Get System Health

```php
use App\Modules\Monitoring\Services\MonitoringService;

$monitoringService = app(MonitoringService::class);
$health = $monitoringService->getHealthStatus();
```

### Get Metrics

```php
$metrics = $monitoringService->getMetrics();
```

### Get Queue Status

```php
$queueStatus = $monitoringService->getQueueStatus();
```

## API Endpoints

### ERP Integration
- `GET /api/erp-integration` - List integrations
- `POST /api/erp-integration/sync` - Sync data
- `GET /api/erp-integration/sync/{syncId}/status` - Get sync status
- `POST /api/erp-integration/test-connection` - Test connection

### Sales
- `GET /api/sales/orders` - List orders
- `POST /api/sales/orders` - Create order
- `GET /api/sales/orders/{id}` - Get order
- `PUT /api/sales/orders/{id}` - Update order
- `DELETE /api/sales/orders/{id}` - Delete order
- `GET /api/sales/statistics` - Get statistics

### Inventory
- `GET /api/inventory/products` - List products
- `POST /api/inventory/products` - Create product
- `GET /api/inventory/products/{id}` - Get product
- `PATCH /api/inventory/products/{id}/stock` - Update stock
- `GET /api/inventory/low-stock` - Get low stock alerts
- `GET /api/inventory/statistics` - Get statistics

### Auth
- `POST /api/auth/register` - Register
- `POST /api/auth/login` - Login
- `POST /api/auth/logout` - Logout
- `GET /api/auth/me` - Get authenticated user
- `POST /api/auth/refresh` - Refresh token

### Monitoring
- `GET /api/monitoring/health` - System health
- `GET /api/monitoring/metrics` - System metrics
- `GET /api/monitoring/logs` - Application logs
- `GET /api/monitoring/queue-status` - Queue status
- `GET /api/monitoring/database-status` - Database status

## Testing Modules

### Unit Test Example

```php
use App\Modules\Sales\Services\SalesService;
use App\Modules\Sales\Repositories\SalesRepositoryInterface;
use Mockery;

test('can create order', function () {
    $repository = Mockery::mock(SalesRepositoryInterface::class);
    $repository->shouldReceive('create')
        ->once()
        ->andReturn(['id' => '123', 'total' => 100]);
    
    $service = new SalesService($repository);
    $order = $service->createOrder(['total' => 100]);
    
    expect($order)->toHaveKey('id');
});
```

### Feature Test Example

```php
use App\Models\User;

test('can list orders', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('sales.view');
    
    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/sales/orders');
    
    $response->assertStatus(200)
        ->assertJsonStructure(['data']);
});
```

## Queue Jobs

### Dispatch Jobs

```php
// ERP Sync
use App\Modules\ERPIntegration\Jobs\SyncERPDataJob;
SyncERPDataJob::dispatch($syncId, $data)->onQueue('erp-sync');

// Process Order
use App\Modules\Sales\Jobs\ProcessOrderJob;
ProcessOrderJob::dispatch($orderId)->onQueue('sales');

// Update Inventory
use App\Modules\Inventory\Jobs\UpdateInventoryJob;
UpdateInventoryJob::dispatch($productId, $data)->onQueue('inventory');

// Send Welcome Email
use App\Modules\Auth\Jobs\SendWelcomeEmailJob;
SendWelcomeEmailJob::dispatch($user)->onQueue('auth');

// Collect Metrics
use App\Modules\Monitoring\Jobs\CollectMetricsJob;
CollectMetricsJob::dispatch()->onQueue('monitoring');
```

## Permissions

All modules use Spatie Laravel Permission. Ensure users have appropriate permissions:

```php
$user->givePermissionTo('sales.create');
$user->givePermissionTo('inventory.view');
$user->assignRole('admin');
```

## Configuration

### ERP Integration

Add to `.env`:
```env
ERP_BASE_URL=https://api.erp.example.com
ERP_API_KEY=your_api_key
ERP_TIMEOUT=30
ERP_RETRY_ATTEMPTS=3
```

## Next Steps

1. Create database migrations for each module
2. Add Eloquent models if needed
3. Implement unit and feature tests
4. Add API documentation (Swagger/OpenAPI)
5. Set up monitoring and alerts

