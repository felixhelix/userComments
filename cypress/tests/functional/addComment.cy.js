/// <reference types="cypress" />

// This test adds a comment and a sub-comment

describe('addUserComment', () => {
    beforeEach(() => {
        // cy.visit('/index.php/preprints/preprint/view/1')
        cy.login('admin','admin','publicknowledge');
        // Load a preprint page
        cy.visit('/index.php/publicknowledge/preprint/view/1');
    })

    it('writes a comment in the last textarea field and submit the comment', () => {
        var newComment = "This is a comment to a preprint";
        // Get the comment form block
        cy.get('[data-test=commentsApp]').as('commentsApp');
        // locate the textare field and type a comment         
        cy.get('@commentsApp').find('[name=commentText]').type(newComment);
        // select the submit button and submit the comment
        cy.get('@commentsApp').find('button[type="submit"]').contains('Submit').click();
        // Check if the new comment is posted
        cy.get('@commentsApp').children('ul').children('li').last().children().first().should('match', 'div').should('contain', newComment);
    })

    it('writes a comment to a commment and submits it', () => {
        var newComment = "This is a comment to a comment";
        // Get the comment list
        cy.get('[data-test=userCommentsBlock]').should('match', 'ul').as('comments');
        // locate the last comment
        cy.get('@comments').find('li').last().children().first().should('match', 'div').as('lastComment');
        // locate the reply button and click it to attach the form field
        cy.get('@lastComment').find('a').contains('Reply', { timeout: 10000, matchCase: false}).click();
        // now the form field should be loaded and we can access it
        cy.get('@lastComment').find('[name=commentText]').should('match', 'textarea').as('commentText');
        cy.get('@commentText').type(newComment);
        // select the submit button and submit comment
        cy.get('@commentText').next().find('[type=submit]').click();
        // Meta comments are in a list of their own      
        cy.get('@lastComment').next().find('[data-test=userCommentsBlock]').should('match', 'ul').as('metaComments');
        // locate the last meta comment
        cy.get('@metaComments').find('li').last().children().first().should('match', 'div').should('contain', newComment);
    })    

})

