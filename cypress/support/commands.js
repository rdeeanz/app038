// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************

/**
 * Custom command to login via API and set session
 */
Cypress.Commands.add('login', (email = 'test@example.com', password = 'password') => {
  cy.request({
    method: 'POST',
    url: `${Cypress.env('apiUrl')}/login`,
    body: {
      email,
      password,
    },
  }).then((response) => {
    expect(response.status).to.eq(200);
    // Store token or session cookie
    window.localStorage.setItem('auth_token', response.body.token);
  });
});

/**
 * Custom command to visit authenticated page
 */
Cypress.Commands.add('visitAuthenticated', (url) => {
  cy.login().then(() => {
    cy.visit(url);
  });
});

/**
 * Custom command to wait for Inertia page load
 */
Cypress.Commands.add('waitForInertia', () => {
  cy.window().its('__inertia').should('exist');
  cy.wait(500); // Wait for Inertia to finish loading
});

/**
 * Custom command to check flash message
 */
Cypress.Commands.add('shouldSeeFlash', (message) => {
  cy.get('[data-cy="flash-message"]').should('contain', message);
});

/**
 * Custom command to fill Inertia form
 */
Cypress.Commands.add('fillInertiaForm', (selector, data) => {
  Object.keys(data).forEach((key) => {
    cy.get(`${selector} [name="${key}"]`).clear().type(data[key]);
  });
});

