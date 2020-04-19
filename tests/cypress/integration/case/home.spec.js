describe('Home', function () {
    it('Show page', () => {
        cy.visit(Cypress.env('host'));
        cy.contains('Hello World');
    })
});
