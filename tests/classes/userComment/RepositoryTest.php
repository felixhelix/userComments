<?php

namespace APP\plugins\generic\userComments\tests\classes\userComment;

use PKP\core\Core;

use APP\plugins\generic\userComments\classes\userComment\userComment;
use APP\plugins\generic\userComments\classes\userComment\Repository;
use APP\plugins\generic\userComments\tests\UserCommentsTestCase;

class RepositoryTest extends UserCommentsTestCase
{
    public function testGetNewUserCommentsObject(): void
    {
        $repository = app(Repository::class);
        $userComment = $repository->newDataObject();
        self::assertInstanceOf(UserComment::class, $userComment);

        $params = [
            'contextId' => $this->contextId,
            'userId' => 1,
            'submissionId' => (int) $this->submissionId,
            'publicationId' => (int) $this->publicationId,
            'foreignCommentId' => 1,
            'dateCreated' => Core::getCurrentDate(),
            'flagged' => true,
            'dateFlagged' => Core::getCurrentDate(),
            'visible' => true,
            'commentText' => "Test comment text",
            'flaggedBy' => 1,
            'flagNote' => "Test flag note"
        ];

        $userComment = $repository->newDataObject($params);
        self::assertEquals($params, $userComment->_data);
    }

    public function testCrud(): void
    {
        $params = [
            'contextId' => (int) $this->contextId,
            'userId' => 1,
            'submissionId' => (int) $this->submissionId,
            'publicationId' => (int) $this->publicationId,
            'foreignCommentId' => 1,
            'dateCreated' => Core::getCurrentDate(),
            'flagged' => false,
            'dateFlagged' => null,
            'visible' => true,
            'commentText' => "Test comment text"          
        ];

        $repository = app(Repository::class);
        $userComment = $repository->newDataObject($params);

        $insertedUserCommentId = $repository->add($userComment);
        $params['commentId'] = $insertedUserCommentId;

        $fetchedUserComment = $repository->get($insertedUserCommentId, $this->contextId);

        self::assertEquals($params, $fetchedUserComment->_data);

        $params['flagged'] = true;
        $params['dateFlagged'] = Core::getCurrentDate();
        $params['visible'] = true;
        $params['flaggedBy'] = 1;
        $params['flagNote'] = "Test flag note";

        $repository->update($userComment, $params);

        $fetchedUserComment = $repository->get($userComment->getId(), $this->contextId);
        self::assertEquals($params, $fetchedUserComment->_data);

        $repository->delete($userComment);
        self::assertFalse($repository->exists($userComment->getId()));
    }

    public function testCollectorFilterByPublicationId(): void
    {
        $params = [
            'contextId' => (int) $this->contextId,
            'userId' => 1,
            'submissionId' => 1,
            'publicationId' => (int) $this->publicationId,
            'foreignCommentId' => 1,
            'dateCreated' => Core::getCurrentDate(),
            'flagged' => false,
            'dateFlagged' => null,
            'visible' => true,
            'commentText' => "Test comment text"          
        ];

        $repository = app(Repository::class);
        $userComment = $repository->newDataObject($params);

        $insertedUserCommentId = $repository->add($userComment);

        $userComments = $repository->getCollector()
            ->filterByPublicationId($this->publicationId)
            ->getMany();

        self::assertEquals(
            [$userComment->getId() => $userComment],
            $userComments->all()
        );
    }

}
