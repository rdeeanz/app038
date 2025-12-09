# Laravel Modular Structure

This document describes the modular architecture of the Laravel application with five main modules: ERPIntegration, Sales, Inventory, Auth, and Monitoring.

## Module Structure

Each module follows a consistent structure with the following components:

```
app/Modules/
├── [ModuleName]/
│   ├── Controllers/          # HTTP Controllers (thin controllers)
│   ├── Services/             # Business logic layer
│   ├── Repositories/         # Data access layer (with interfaces)
│   ├── Jobs/                 # Queue jobs for async processing
│   ├── Requests/             # Form requests for validation
│   ├── Models/               # Eloquent models (if needed)
│   └── routes/               # Module-specific routes
│       └── api.php
```

## Architecture Principles

### 1. Layered Architecture
- **Controllers**: Handle HTTP requests/responses only
- **Services**: Contain business logic
- **Repositories**: Handle data access with interfaces for dependency injection
- **Jobs**: Asynchronous processing

### 2. Dependency Injection
- All services use interfaces for repositories
- Services are injected into controllers
- Follows SOLID principles

### 3. Clean Code Practices
- Thin controllers
- Single responsibility principle
- Clear naming conventions
- Proper error handling

## Modules Overview

### 1. ERPIntegration Module

**Purpose**: Handle integration with external ERP systems

**Components**:
- `ERPIntegrationController`: API endpoints for ERP operations
- `ERPIntegrationService`: Business logic for ERP sync
- `ERPIntegrationRepository`: Data access and API communication
- `SyncERPDataJob`: Async job for syncing data
- `SyncRequest`: Validation for sync operations

**Routes**:
- `GET /api/erp-integration` - List integrations
- `POST /api/erp-integration/sync` - Initiate sync
- `GET /api/erp-integration/sync/{syncId}/status` - Get sync status
- `POST /api/erp-integration/test-connection` - Test ERP connection

**Permissions**:
- `erp-integration.view`
- `erp-integration.sync`
- `erp-integration.manage`

### 2. Sales Module

**Purpose**: Manage sales orders and transactions

**Components**:
- `SalesController`: CRUD operations for orders
- `SalesService`: Business logic for order processing
- `SalesRepository`: Data access for orders
- `ProcessOrderJob`: Async job for order processing
- `CreateOrderRequest` / `UpdateOrderRequest`: Validation

**Routes**:
- `GET /api/sales/orders` - List orders
- `POST /api/sales/orders` - Create order
- `GET /api/sales/orders/{id}` - Get order
- `PUT /api/sales/orders/{id}` - Update order
- `DELETE /api/sales/orders/{id}` - Delete order
- `GET /api/sales/statistics` - Get sales statistics

**Permissions**:
- `sales.view`
- `sales.create`
- `sales.update`
- `sales.manage`

### 3. Inventory Module

**Purpose**: Manage product inventory and stock levels

**Components**:
- `InventoryController`: Product and stock management
- `InventoryService`: Business logic for inventory operations
- `InventoryRepository`: Data access for products
- `UpdateInventoryJob`: Async job for inventory updates
- `CreateProductRequest` / `UpdateStockRequest`: Validation

**Routes**:
- `GET /api/inventory/products` - List products
- `POST /api/inventory/products` - Create product
- `GET /api/inventory/products/{id}` - Get product
- `PATCH /api/inventory/products/{id}/stock` - Update stock
- `GET /api/inventory/low-stock` - Get low stock alerts
- `GET /api/inventory/statistics` - Get inventory statistics

**Permissions**:
- `inventory.view`
- `inventory.create`
- `inventory.update`
- `inventory.manage`

### 4. Auth Module

**Purpose**: Handle authentication and user registration

**Components**:
- `AuthController`: Authentication endpoints
- `AuthService`: Business logic for auth operations
- `AuthRepository`: Data access for users
- `SendWelcomeEmailJob`: Async job for welcome emails
- `LoginRequest` / `RegisterRequest`: Validation

**Routes**:
- `POST /api/auth/register` - Register new user
- `POST /api/auth/login` - Login user
- `POST /api/auth/logout` - Logout user
- `GET /api/auth/me` - Get authenticated user
- `POST /api/auth/refresh` - Refresh token

**Note**: This module works alongside Laravel Breeze for web authentication.

### 5. Monitoring Module

**Purpose**: System health monitoring and metrics collection

**Components**:
- `MonitoringController`: Monitoring endpoints
- `MonitoringService`: Business logic for monitoring
- `MonitoringRepository`: Data access for logs
- `CollectMetricsJob`: Async job for metrics collection

**Routes**:
- `GET /api/monitoring/health` - Get system health
- `GET /api/monitoring/metrics` - Get system metrics
- `GET /api/monitoring/logs` - Get application logs
- `GET /api/monitoring/queue-status` - Get queue status
- `GET /api/monitoring/database-status` - Get database status

**Permissions**:
- `monitoring.view`

## Service Provider

The `ModuleServiceProvider` handles:
- Service binding (interfaces to implementations)
- Route registration for all modules
- Module initialization

## Usage Examples

### Using a Service in a Controller

```php
use App\Modules\Sales\Services\SalesService;

class SalesController extends Controller
{
    public function __construct(
        protected SalesService $salesService
    ) {}
}
```

### Dispatching a Job

```php
use App\Modules\Sales\Jobs\ProcessOrderJob;

ProcessOrderJob::dispatch($orderId)
    ->onQueue('sales');
```

### Using Repository Interface

```php
use App\Modules\Sales\Repositories\SalesRepositoryInterface;

class SalesService
{
    public function __construct(
        protected SalesRepositoryInterface $repository
    ) {}
}
```

## Adding a New Module

1. Create module directory structure:
```bash
mkdir -p app/Modules/NewModule/{Controllers,Services,Repositories,Jobs,Requests,routes}
```

2. Create base files:
   - Controller
   - Service
   - Repository Interface
   - Repository Implementation
   - Routes file

3. Register in `ModuleServiceProvider`:
   - Add service binding
   - Register routes

4. Add permissions to seeder

## Testing

Each module should have:
- Unit tests for Services
- Feature tests for Controllers
- Integration tests for Repositories

Example:
```php
// tests/Unit/Modules/Sales/SalesServiceTest.php
// tests/Feature/Modules/Sales/SalesControllerTest.php
```

## Best Practices

1. **Keep Controllers Thin**: Move business logic to Services
2. **Use Interfaces**: Always use repository interfaces
3. **Validate Input**: Use FormRequest classes
4. **Handle Errors**: Proper exception handling
5. **Log Actions**: Log important operations
6. **Use Transactions**: For database operations
7. **Queue Heavy Tasks**: Use Jobs for async processing
8. **Follow Naming**: Consistent naming conventions

## Permissions

Each module defines its own permissions. Add them to the `RolePermissionSeeder`:

```php
$permissions = [
    'erp-integration.view',
    'erp-integration.sync',
    'erp-integration.manage',
    'sales.view',
    'sales.create',
    'sales.update',
    'sales.manage',
    // ... etc
];
```

## Configuration

Module-specific configuration can be added to `config/` directory:
- `config/erp_integration.php` - ERP Integration settings

## Queue Configuration

Each module uses specific queues:
- `erp-sync` - ERP Integration jobs
- `sales` - Sales processing jobs
- `inventory` - Inventory update jobs
- `auth` - Authentication jobs
- `monitoring` - Metrics collection jobs

Configure in `config/queue.php` if needed.

