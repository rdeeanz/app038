import StatusBadge from '../../resources/js/components/StatusBadge.svelte';

describe('StatusBadge Component', () => {
  it('renders with default status', () => {
    cy.mount(StatusBadge, {
      props: {
        status: 'pending',
      },
    });

    cy.contains('pending').should('be.visible');
  });

  it('applies correct color class for success status', () => {
    cy.mount(StatusBadge, {
      props: {
        status: 'completed',
      },
    });

    cy.get('span').should('have.class', 'bg-green-100');
  });

  it('applies correct color class for error status', () => {
    cy.mount(StatusBadge, {
      props: {
        status: 'failed',
      },
    });

    cy.get('span').should('have.class', 'bg-red-100');
  });
});

