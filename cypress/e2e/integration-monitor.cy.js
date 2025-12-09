describe('Integration Monitor E2E Tests', () => {
  beforeEach(() => {
    cy.login('admin@example.com', 'password');
    cy.visit('/integration-monitor');
    cy.waitForInertia();
  });

  it('should display integration monitor page', () => {
    cy.contains('Integration Monitor').should('be.visible');
  });

  it('should display integration cards', () => {
    cy.get('[data-cy="integration-card"]').should('exist');
  });

  it('should test connection when button is clicked', () => {
    cy.intercept('POST', '/api/erp-integration/test-connection', {
      statusCode: 200,
      body: {
        connected: true,
        message: 'Connection successful',
      },
    }).as('testConnection');

    cy.get('[data-cy="test-connection"]').first().click();
    cy.wait('@testConnection');

    cy.contains('Connected').should('be.visible');
  });

  it('should sync data when sync button is clicked', () => {
    cy.intercept('POST', '/api/erp-integration/sync', {
      statusCode: 202,
      body: {
        message: 'Sync initiated successfully',
        data: {
          sync_id: 'sync-123',
        },
      },
    }).as('syncData');

    cy.get('[data-cy="sync-products"]').first().click();
    cy.wait('@syncData');

    cy.shouldSeeFlash('Sync initiated successfully');
  });

  it('should display sync history', () => {
    cy.contains('Sync History').should('be.visible');
    cy.get('table').should('exist');
  });
});

