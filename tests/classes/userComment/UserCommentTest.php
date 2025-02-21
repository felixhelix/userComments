<?php

namespace APP\plugins\generic\customQuestions\tests\classes\userComment;

use APP\plugins\generic\userComments\classes\userComment\UserComment;
use PKP\tests\PKPTestCase;

class UserCommentTest extends PKPTestCase
{
    public function testGettersAndSetters(): void
    {
        $contextId = 1;
        $submissionId = 1;
        $publicationId = 1;
        $foreignCommentId = 1;
        $userId = 1;
        $dateCreated = now();
        $commentText = "Test comment text";
        $flagged = true;
        $dateFlagged = now();
        $flaggedBy = 1;
        $flagNote = "Test flag note";
        $visible = true;

        $userComment = new UserComment();
        $userComment->setContextId($contextId);
        $userComment->setPublicationId($publicationId);
        $userComment->setSubmissionId($submissionId);
        $userComment->setForeignCommentId($foreignCommentId);
        $userComment->setUserId($userId);
        $userComment->setDateCreated($dateCreated);
        $userComment->setCommentText($commentText);
        $userComment->setFlagged($flagged);
        $userComment->setDateFlagged($dateFlagged);
        $userComment->setFlaggedBy($flaggedBy);
        $userComment->setFlagNote($flagNote);
        $userComment->setVisible($visible);

        self::assertEquals($contextId, $userComment->getContextId());
        self::assertEquals($submissionId, $userComment->getSubmissionId());
        self::assertEquals($publicationId, $userComment->getPublicationId());
        self::assertEquals($foreignCommentId, $userComment->getForeignCommentId());
        self::assertEquals($userId, $userComment->getUserId());
        self::assertEquals($dateCreated, $userComment->getDateCreated());
        self::assertEquals($commentText, $userComment->getCommentText());
        self::assertEquals($flagged, $userComment->getFlagged());
        self::assertEquals($dateFlagged, $userComment->getDateFlagged());
        self::assertEquals($flaggedBy, $userComment->getFlaggedBy());
        self::assertEquals($flagNote, $userComment->getFlagNote());
        self::assertEquals($visible, $userComment->getVisible());
    }

}