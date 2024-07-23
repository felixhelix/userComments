/// <reference types="cypress" />

// This test flags a comment

describe('flag a comment', () => {
    beforeEach(() => {
        // cy.visit('/index.php/publicknowledge/preprint/view/1')
        cy.login('annauthor','annauthor','publicknowledge');
        cy.wait(500); // give some time to finish loading the backend page
        // Load a preprint page
        cy.visit('/index.php/publicknowledge/preprint/view/1');
    });

    it('flag the last comment', () => {
        // Get the comment list
        var comments = cy.get('[data-title=userComments]').should('match', 'ul');
        // locate the last comment
        var commentDiv = comments.find('li').last().children().first().should('match', 'div');
        // locate the flag button and click it to flag the comment
        commentDiv.find('button[name=flagComment]').click();
        // now a assert window will pop up and we have to click again
        // this seems to be done per default
        // Now check if the comment has been flagged
        // Get the comment list again as it has been reloaded
        var comments = cy.get('[data-title=userComments]').should('match', 'ul');
        // locate the last comment again
        var commentDiv = comments.find('li').last().children().first().should('match', 'div');
        // locate the flag 
        commentDiv.find('div[data-isFlagged=true]');
        });    

})

