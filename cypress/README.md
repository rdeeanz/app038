# Cypress E2E Testing

End-to-end testing configuration using Cypress for the Laravel + Svelte application.

## Setup

### Install Dependencies

```bash
npm install
```

### Open Cypress

```bash
npm run test:e2e:open
```

## Test Structure

```
cypress/
├── e2e/              # End-to-end tests
│   ├── dashboard.cy.js
│   ├── integration-monitor.cy.js
│   └── mapping-editor.cy.js
├── component/        # Component tests
│   └── StatusBadge.cy.js
├── fixtures/         # Test data
│   └── dashboard-data.json
└── support/          # Custom commands
    ├── commands.js
    ├── e2e.js
    └── component.js
```

## Custom Commands

### `cy.login(email, password)`

Login via API and set session:

```javascript
cy.login('admin@example.com', 'password');
```

### `cy.visitAuthenticated(url)`

Visit a page after authentication:

```javascript
cy.visitAuthenticated('/dashboard');
```

### `cy.waitForInertia()`

Wait for Inertia.js to finish loading:

```javascript
cy.visit('/dashboard');
cy.waitForInertia();
```

### `cy.shouldSeeFlash(message)`

Check for flash message:

```javascript
cy.shouldSeeFlash('Success message');
```

### `cy.fillInertiaForm(selector, data)`

Fill Inertia form fields:

```javascript
cy.fillInertiaForm('form', {
  name: 'John Doe',
  email: 'john@example.com',
});
```

## Running Tests

### Development

```bash
# Open Cypress UI
npm run test:e2e:open

# Run in headless mode
npm run test:e2e

# Run specific test
npx cypress run --spec "cypress/e2e/dashboard.cy.js"
```

### CI/CD

Tests run automatically in GitHub Actions on:
- Push to main/develop
- Pull requests

## Best Practices

1. **Use data-cy attributes**: For stable selectors
2. **Wait for Inertia**: Use `cy.waitForInertia()` after navigation
3. **Mock API calls**: Use `cy.intercept()` for API mocking
4. **Clean up**: Reset state between tests
5. **Use fixtures**: Store test data in `fixtures/`

## Configuration

See `cypress.config.js` for:
- Base URL
- Viewport settings
- Timeouts
- Custom commands

