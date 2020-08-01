import {Given, Then} from "cypress-cucumber-preprocessor/steps";
import dayjs from "dayjs";

Given('Open time page', () => {
    cy.visit(Cypress.env('HOST').concat('/time'));
});

Then('See text', () => {
    let today = dayjs().format('YYYY-MM-DD HH:mm');

    cy.get('td').should('be.include.text', today);
});
