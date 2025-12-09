describe('Mapping Editor E2E Tests', () => {
  beforeEach(() => {
    cy.login('admin@example.com', 'password');
    cy.visit('/mapping-editor');
    cy.waitForInertia();
  });

  it('should display mapping editor page', () => {
    cy.contains('Mapping Editor').should('be.visible');
  });

  it('should display mapping files list', () => {
    cy.get('[data-cy="mapping-files"]').should('exist');
  });

  it('should load mapping file when clicked', () => {
    cy.intercept('GET', '/api/mappings/order-to-sap.yaml', {
      statusCode: 200,
      body: {
        content: 'target: order-to-sap\n\nfields:\n  order_number:\n    source: order_number',
      },
    }).as('loadMapping');

    cy.get('[data-cy="mapping-file"]').contains('order-to-sap.yaml').click();
    cy.wait('@loadMapping');

    cy.get('textarea').should('contain.value', 'target: order-to-sap');
  });

  it('should save mapping file', () => {
    cy.intercept('PUT', '/api/mappings/order-to-sap.yaml', {
      statusCode: 200,
      body: {
        message: 'Mapping file updated successfully',
      },
    }).as('saveMapping');

    cy.get('[data-cy="save-mapping"]').click();
    cy.wait('@saveMapping');

    cy.shouldSeeFlash('Mapping file saved successfully');
  });

  it('should test mapping transformation', () => {
    cy.intercept('POST', '/api/mappings/test', {
      statusCode: 200,
      body: {
        success: true,
        result: {
          order_number: 'ORD-001',
          order_date: '2024-01-15',
        },
      },
    }).as('testMapping');

    cy.get('[data-cy="test-mapping"]').click();
    cy.wait('@testMapping');

    // Check if result is displayed
    cy.contains('Transformation Result').should('be.visible');
  });
});

