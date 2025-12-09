# Testing Guide

Comprehensive testing configuration for Laravel application with Unit, Integration, Contract (Pact), and E2E (Cypress) tests.

## Test Structure

```
tests/
├── Unit/                    # Unit tests (fast, isolated)
│   ├── Services/           # Service layer unit tests
│   └── Modules/            # Module-specific unit tests
├── Integration/            # Integration tests (database, services)
│   ├── DatabaseTest.php   # Database connectivity tests
│   └── Modules/           # Module integration tests
├── Feature/                # Feature tests (HTTP, API, Inertia)
│   ├── Api/               # API endpoint tests
│   └── Inertia/           # Inertia page rendering tests
├── Contract/              # Contract tests (Pact)
│   └── Pact/              # Pact consumer/provider tests
└── TestCase.php           # Base test case

cypress/
├── e2e/                   # End-to-end tests
│   ├── dashboard.cy.js
│   ├── integration-monitor.cy.js
│   └── mapping-editor.cy.js
├── component/             # Component tests
│   └── StatusBadge.cy.js
├── fixtures/              # Test fixtures
│   └── dashboard-data.json
└── support/               # Custom commands and config
    ├── commands.js
    ├── e2e.js
    └── component.js
```

## Running Tests

### PHP Tests

```bash
# All tests
php artisan test

# Specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Integration
php artisan test --testsuite=Feature
php artisan test --testsuite=Contract

# Specific test file
php artisan test tests/Unit/Services/ConnectionServiceTest.php

# With coverage
php artisan test --coverage
php artisan test --coverage --min=80

# Filter by test name
php artisan test --filter test_can_create_order
```

### Cypress Tests

```bash
# Run E2E tests (headless)
npm run test:e2e

# Open Cypress UI
npm run test:e2e:open

# Run component tests
npm run test:component

# Run specific test file
npx cypress run --spec "cypress/e2e/dashboard.cy.js"
```

## Test Types

### 1. Unit Tests

**Purpose**: Test individual components in isolation

**Location**: `tests/Unit/`

**Characteristics**:
- Fast execution (< 1 second per test)
- No database access
- Mock external dependencies
- Test business logic

**Example**:
```php
public function test_can_create_order(): void
{
    $this->repositoryMock
        ->shouldReceive('create')
        ->once()
        ->andReturn(['id' => 1]);
    
    $result = $this->service->createOrder($orderData);
    $this->assertNotNull($result);
}
```

### 2. Integration Tests

**Purpose**: Test components working together

**Location**: `tests/Integration/`

**Characteristics**:
- Use real database
- Test service + repository interactions
- Verify data persistence
- Test queue jobs

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

**Purpose**: Test HTTP endpoints and API responses

**Location**: `tests/Feature/`

**Characteristics**:
- Test full HTTP request/response cycle
- Test authentication and authorization
- Test Inertia page rendering
- Test API contracts

**Example**:
```php
public function test_can_access_dashboard(): void
{
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    
    $response = $this->getJson('/api/dashboard/data');
    $response->assertStatus(200)
        ->assertJsonStructure(['stats']);
}
```

### 4. Contract Tests (Pact)

**Purpose**: Verify API contracts between consumer and provider

**Location**: `tests/Contract/Pact/`

**Setup**:
```bash
composer require pact-foundation/pact-php
```

**Consumer Test**:
```php
public function test_erp_sync_contract(): void
{
    $this->builder
        ->given('ERP system is available')
        ->uponReceiving('a request to sync products')
        ->with($request)
        ->willRespondWith($response)
        ->verify();
}
```

**Provider Test**:
```php
public function test_verify_contracts(): void
{
    $this->verifier->verifyFiles([
        'tests/pacts/app038-erpintegrationapi.json',
    ]);
}
```

### 5. E2E Tests (Cypress)

**Purpose**: Test complete user workflows

**Location**: `cypress/e2e/`

**Example**:
```javascript
describe('Dashboard E2E Tests', () => {
  beforeEach(() => {
    cy.login('admin@example.com', 'password');
    cy.visit('/dashboard');
    cy.waitForInertia();
  });

  it('should display dashboard page', () => {
    cy.contains('Dashboard').should('be.visible');
  });
});
```

## GitHub Actions Integration

### Automated Test Execution

Tests run automatically on:
- Push to `main` or `develop`
- Pull requests
- Manual workflow dispatch

### Test Jobs

1. **unit-tests** - PHP unit tests with matrix (PHP 8.2, 8.3)
2. **integration-tests** - Integration tests with services
3. **feature-tests** - Feature/API tests
4. **contract-tests** - Pact contract verification
5. **e2e-tests** - Cypress E2E tests
6. **test-summary** - Test results summary

### Coverage Reports

Coverage is uploaded to Codecov:
- Unit tests: `coverage-unit.xml`
- Integration tests: `coverage-integration.xml`
- Feature tests: `coverage-feature.xml`

## Configuration

### PHPUnit (`phpunit.xml`)

- Test suites: Unit, Integration, Feature, Contract
- Coverage settings
- Environment variables
- Source code inclusion/exclusion

### Cypress (`cypress.config.js`)

- Base URL configuration
- Viewport settings
- Timeouts
- Custom commands
- Component testing setup

## Best Practices

### 1. Test Organization

- **Unit tests**: One test class per service/class
- **Integration tests**: One test class per feature
- **Feature tests**: One test class per endpoint/page
- **E2E tests**: One test file per page/feature

### 2. Test Naming

Use descriptive names:
```php
test_can_create_order()
test_returns_error_when_order_data_invalid()
test_requires_authentication_to_access_dashboard()
```

### 3. Test Data

- Use factories for test data
- Use `RefreshDatabase` for database tests
- Clean up after tests

### 4. Assertions

- One assertion per test when possible
- Use specific assertions
- Test both success and failure cases

### 5. Mocking

- Mock external services
- Mock database for unit tests
- Use real services for integration tests

## Test Coverage Goals

- **Unit tests**: 80%+ coverage
- **Integration tests**: 70%+ coverage
- **Feature tests**: 60%+ coverage
- **E2E tests**: Critical user paths

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

# Check Cypress installation
npx cypress verify
```

### Pact Issues

```bash
# Start Pact Broker locally
docker run -d -p 9292:9292 \
  -e PACT_BROKER_DATABASE_URL=sqlite:///pact_broker.sqlite \
  pactfoundation/pact-broker

# Verify contracts
php artisan test --testsuite=Contract
```

### Service Container Issues

If services (PostgreSQL, Redis) are not available:

```bash
# Check service health
docker ps
docker logs <container-name>

# Restart services
docker-compose restart postgres redis
```

## Continuous Integration

### GitHub Actions Workflow

The `.github/workflows/tests.yml` workflow:
1. Runs unit tests with matrix strategy
2. Runs integration tests with services
3. Runs feature tests
4. Runs contract tests (Pact)
5. Runs E2E tests (Cypress)
6. Generates test summary

### Parallel Execution

Tests run in parallel:
- Unit tests: Multiple PHP versions
- Integration/Feature: After unit tests pass
- E2E tests: After integration tests pass

### Artifacts

- Test coverage reports
- Cypress screenshots (on failure)
- Cypress videos (always)
- Pact contract files

## Next Steps

1. **Add more tests**: Expand test coverage
2. **Set up Pact Broker**: For contract management
3. **Configure Cypress Cloud**: For test recording
4. **Add performance tests**: Load testing
5. **Add accessibility tests**: a11y testing with Cypress

