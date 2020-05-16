import {Given, Then} from "cypress-cucumber-preprocessor/steps";

Given('Open error page', () => {
   cy.visit(Cypress.env('HOST').concat('/error'));
});

Then('See text', () => {
   cy.contains('No Message').should('be.visible');
});
