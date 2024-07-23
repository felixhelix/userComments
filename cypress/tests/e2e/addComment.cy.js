/// <reference types="cypress" />

// This test adds a comment and a sub-comment

describe('addUserComment', () => {
    beforeEach(() => {
        // cy.visit('/index.php/publicknowledge/preprint/view/1')
        cy.login('annauthor','annauthor','publicknowledge');
        cy.wait(500); // give some time to finish loading the backend page
        // Load a preprint page
        cy.visit('/index.php/publicknowledge/preprint/view/1');
    })

    it('writes a comment in the last textarea field and submit the comment', () => {
        var newComment = "This is a comment to a preprint";
        // Get the comment form block
        var commentApp = cy.get('[id=commentsApp]');
        // locate the textare field and type a comment         
        var commentField = commentApp.find('[name=commentText]');
        commentField.type(newComment);
        // select the parent form
        var parentForm = commentField.parent().parent().should('match', 'form'); 
        // select the submit button and submit the comment
        parentForm.get('button[type="submit"]').contains('Submit').click();
        // Check if the new comment is posted
        var commentApp = cy.get('[id=commentsApp]'); // since vue updates, the element no longer exists
        var commentDiv = commentApp.children('ul').children('li').last().children().first().should('match', 'div');
        commentDiv.should('contain', newComment);
    })

    it('writes a comment to a commment and submits it', () => {
        var newComment = "This is a comment to a comment";
        // Get the comment list
        var comments = cy.get('[data-title=userComments]').should('match', 'ul');
        // locate the last comment
        var commentDiv = comments.find('li').last().children().first().should('match', 'div');
        // locate the reply button and click it to attach a form field
        commentDiv.find('button').contains('reply').click();
        // now the form field should be loaded and we can access it
        // Get the comment list again
        var comments = cy.get('[data-title=userComments]').should('match', 'ul');
        // locate the last comment again
        var commentDiv = comments.find('li').last().children().first().should('match', 'div');        
        var commentField = commentDiv.find('[name=commentText]').should('match', 'textarea');
        commentField.type(newComment);
        // select the parent form
        var parentForm = commentField.parent().parent().should('match', 'form'); 
        // select the submit button and submit comment
        parentForm.get('button[type="submit"]').contains('Submit').click();
        // the vue app is re-loaded and we need to access it again
        // Get the comment list again
        var comments = cy.get('[data-title=userComments]').should('match', 'ul');
        // locate the last comment again
        var commentDiv = comments.find('li').last().children().first().should('match', 'div');  
        // Meta comments are in a list of their own      
        var metaComments = commentDiv.next().find('[data-title=userComments]').should('match', 'ul');
        // locate the last meta comment
        var metaCommentDiv = comments.find('li').last().children().first().should('match', 'div');  
        metaCommentDiv.should('contain', newComment);
    })    

})

