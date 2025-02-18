/// <reference types="cypress" />

// This test flags a comment

describe('flag a comment', () => {
    beforeEach(() => {
        // cy.visit('/index.php/preprints/preprint/view/1')
        cy.login('admin','admin','publicknowledge');
        // Load a preprint page
        cy.visit('/index.php/publicknowledge/preprint/view/1');
    })

    it('flag the last comment', () => {
        // Get the comment list
        cy.get('[data-test=userCommentsBlock]').should('match', 'ul').as('comments');
        // locate the last comment
        cy.get('@comments').find('li').last().children().first().should('match', 'div').as('lastComment');
        // locate the flag button and click it to flag the comment
        cy.get('@lastComment').find('button[name=flagComment]').click();
        // now a modal window will pop up and we have to insert a flag note
        cy.get('[data-test=flagModal]').as('flagModal');
        cy.get('@flagModal').find('textarea').type("This comment is offensive.");
        cy.get('@flagModal').find('button[type=submit]').click();
        // Now check if the comment has been flagged
        cy.get('@lastComment').find('div[data-isFlagged=true]');
        });    

})

