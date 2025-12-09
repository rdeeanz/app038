# Testing Guide

This directory contains comprehensive test suites for the Laravel application.

## Test Structure

```
tests/
├── Unit/              # Unit tests (fast, isolated)
│   ├── Services/      # Service layer tests
│   └── Modules/       # Module-specific unit tests
├── Integration/       # Integration tests (database, external services)
│   └── Modules/       # Module integration tests
├── Feature/           # Feature tests (HTTP, API, Inertia)
│   ├── Api/          # API endpoint tests
│   └── Inertia/      # Inertia page tests
├── Contract/         # Contract tests (Pact)
│   └── Pact/         # Pact contract tests
└── TestCase.php      # Base test case
```

## Running Tests

### All Tests
```bash
php artisan test
```

### Specific Test Suite
```bash
# Unit tests
php artisan test --testsuite=Unit

# Integration tests
php artisan test --testsuite=Integration

# Feature tests
php artisan test --testsuite=Feature

# Contract tests
php artisan test --testsuite=Contract
```

### Specific Test File
```bash
php artisan test tests/Unit/Services/ConnectionServiceTest.php
```

### With Coverage
```bash
php artisan test --coverage
php artisan test --coverage --min=80
```

## Test Types

### 1. Unit Tests

Fast, isolated tests for individual components.

**Location**: `tests/Unit/`

**Example**:
```php
public function test_can_create_order(): void
{
    $orderData = ['customer_id' => 1, 'total' => 100.00];
    $result = $this->service->createOrder($orderData);
    $this->assertNotNull($result);
}
```

### 2. Integration Tests

Tests that verify components work together (database, services, repositories).

**Location**: `tests/Integration/`

**Example**:
```php
public function test_can_create_and_retrieve_order(): void
{
    $order = $this->service->createOrder($orderData);
    $retrieved = $this->service->getOrder($order['id']);
    $this->assertEquals($order['id'], $retrieved['id']);
}
```

### 3. Feature Tests

HTTP and API endpoint tests, including Inertia page rendering.

**Location**: `tests/Feature/`

**Example**:
```php
public function test_can_access_dashboard(): void
{
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    
    $response = $this->getJson('/api/dashboard/data');
    $response->assertStatus(200);
}
```

### 4. Contract Tests (Pact)

Contract tests to verify API contracts between consumer and provider.

**Location**: `tests/Contract/Pact/`

**Setup**:
```bash
composer require pact-foundation/pact-php
```

**Example**:
```php
public function test_erp_sync_contract(): void
{
    $this->builder->verify();
    // Pact file generated in tests/pacts/
}
```

### 5. E2E Tests (Cypress)

End-to-end tests for the full application flow.

**Location**: `cypress/e2e/`

**Running**:
```bash
# Headless mode
npm run test:e2e

# Interactive mode
npm run test:e2e:open
```

## Test Configuration

### PHPUnit Configuration

See `phpunit.xml` for:
- Test suites configuration
- Coverage settings
- Environment variables

### Cypress Configuration

See `cypress.config.js` for:
- Base URL
- Viewport settings
- Timeouts
- Custom commands

## Best Practices

1. **Use Factories**: Create test data with factories
2. **Use RefreshDatabase**: For database tests
3. **Mock External Services**: Use Mockery for external dependencies
4. **Test Edge Cases**: Include boundary conditions
5. **Keep Tests Fast**: Unit tests should be < 1s
6. **One Assertion Per Test**: When possible
7. **Descriptive Names**: Use `test_can_do_something` format
8. **Arrange-Act-Assert**: Structure tests clearly

## GitHub Actions

Tests run automatically on:
- Push to `main` or `develop`
- Pull requests
- Manual workflow dispatch

See `.github/workflows/tests.yml` for configuration.

## Coverage

Target coverage:
- Unit tests: 80%+
- Integration tests: 70%+
- Feature tests: 60%+

View coverage:
```bash
php artisan test --coverage
```

## Troubleshooting

### Database Issues
```bash
# Reset test database
php artisan migrate:fresh --env=testing
php artisan db:seed --class=RolePermissionSeeder --env=testing
```

### Cypress Issues
```bash
# Clear Cypress cache
rm -rf node_modules/.cache/cypress

# Run with debug
DEBUG=cypress:* npm run test:e2e
```

### Pact Issues
```bash
# Start Pact Broker locally
docker run -d -p 9292:9292 pactfoundation/pact-broker

# Verify contracts
php artisan test --testsuite=Contract
```

