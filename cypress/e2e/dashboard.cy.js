describe('Dashboard E2E Tests', () => {
  beforeEach(() => {
    cy.login('admin@example.com', 'password');
    cy.visit('/dashboard');
    cy.waitForInertia();
  });

  it('should display dashboard page', () => {
    cy.contains('Dashboard').should('be.visible');
    cy.get('h1').should('contain', 'Dashboard');
  });

  it('should display statistics cards', () => {
    cy.contains('Total Orders').should('be.visible');
    cy.contains('Total Revenue').should('be.visible');
    cy.contains('Active Integrations').should('be.visible');
    cy.contains('Low Stock Alerts').should('be.visible');
  });

  it('should display recent orders table', () => {
    cy.contains('Recent Orders').should('be.visible');
    cy.get('table').should('exist');
  });

  it('should navigate to integration monitor', () => {
    cy.contains('ERP Integration').click();
    cy.url().should('include', '/integration-monitor');
    cy.waitForInertia();
    cy.contains('Integration Monitor').should('be.visible');
  });

  it('should refresh dashboard data', () => {
    cy.intercept('GET', '/api/dashboard/data', { fixture: 'dashboard-data.json' }).as('getDashboardData');
    
    // Trigger refresh (if there's a refresh button)
    cy.get('[data-cy="refresh-dashboard"]').click();
    
    cy.wait('@getDashboardData');
    cy.contains('Total Orders').should('be.visible');
  });
});

