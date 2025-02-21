<?php

namespace APP\plugins\generic\userComments\tests\classes\userComment;

use PKP\core\Core;

use APP\plugins\generic\userComments\classes\userComment\UserComment;
use APP\plugins\generic\userComments\classes\userComment\DAO;
use APP\plugins\generic\userComments\tests\UserCommentsTestCase;

class DAOTest extends UserCommentsTestCase
{
    public function testCreateNewDataObject(): void
    {
        $userCommentsDAO = app(DAO::class);
        $userComment = $userCommentsDAO->newDataObject();
        self::assertInstanceOf(UserComment::class, $userComment);
    }

    public function testCrud(): void
    {
        $contextId = (int) $this->contextId;
        $userId = 1;
        $submissionId = (int) $this->submissionId;
        $publicationId = (int) $this->publicationId;
        $foreignCommentId = 1;
        $dateCreated = Core::getCurrentDate();
        $flagged = true;
        $dateFlagged = Core::getCurrentDate();
        $visible = true;
        $commentText = "Test comment text";
        $flaggedBy = 1;
        $flagNote = "Test flag note";

        $userCommentDAO = app(DAO::class);
        $userComment = $userCommentDAO->newDataObject();        

        $userComment->setContextId($contextId);
        $userComment->setUserId($userId);        
        $userComment->setSubmissionId($submissionId);        
        $userComment->setPublicationId($publicationId);
        $userComment->setForeignCommentId($foreignCommentId);
        $userComment->setDateCreated($dateCreated);
        $userComment->setFlagged($flagged);
        $userComment->setDateFlagged($dateFlagged);
        $userComment->setVisible($visible);        
        $userComment->setCommentText($commentText);        
        $userComment->setFlaggedBy($flaggedBy);
        $userComment->setFlagNote($flagNote);

        $insertedUserCommentId = $userCommentDAO->insert($userComment);
        $fetchedUserComment = $userCommentDAO->get($insertedUserCommentId, $contextId);

        self::assertEquals([
            'commentId' => $insertedUserCommentId,
            'contextId' => $contextId,
            'userId' => $userId,
            'submissionId' => $submissionId,
            'publicationId' => $publicationId,
            'foreignCommentId' => $foreignCommentId,
            'dateCreated' => $dateCreated,
            'flagged' => $flagged,
            'dateFlagged' => $dateFlagged,
            'visible' => $visible,
            'commentText' => $commentText,
            'flaggedBy' => $flaggedBy,
            'flagNote' => $flagNote,
        ], $fetchedUserComment->_data);

    }

}
