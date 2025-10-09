/// <reference types="cypress" />

describe('API testing', () => {
    beforeEach(() => {
        // cy.visit('/index.php/preprints/preprint/view/1')
        cy.login('admin','admin','publicknowledge');
    })

    it("get comments for a publication", () => {
        cy.request("GET", "/index.php/publicknowledge/api/v1/submissions/usercomments/getbypublication/1").then((response) => {
        expect(response.status).to.eq(200)
        // expect(response.body.results).length.to.be.greaterThan(1)
            cy.log(JSON.stringify(response.body))
        });
    });

    // it("post a comment", () => {
    //     cy.request("POST", "/index.php/publicknowledge/api/v1/userComments/add", {
    //       submissionId: 2,
    //       publicationId: 1,
    //       foreignCommentId: null,
    //       commentText: "comment submitted via API directly"
    //     }).should((response) => {
    //       expect(response.status).to.eq(201);
    //     });
    //  });    

});