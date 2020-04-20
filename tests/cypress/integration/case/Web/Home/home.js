import {Given, Then} from "cypress-cucumber-preprocessor/steps";

Given('Open web site', () => {
    cy.visit(Cypress.env('host'));
});

Then('See text', () => {
    cy.contains('Hello World').should('be.visible');
});
