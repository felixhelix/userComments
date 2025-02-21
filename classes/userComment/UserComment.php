<?php

/**
 * @file plugins/generic/comments/classes/UserComment.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class UserComment
 * @ingroup plugins_generic_comments
 *
 * Data object representing a userComment.
 */

namespace APP\plugins\generic\userComments\classes\userComment;


class UserComment extends \PKP\core\DataObject {

	//
	// Get/set methods
	//

	/**
	 * Get context ID.
	 * @return int
	 */
	function getContextId(): int {
		return $this->getData('contextId');
	}

	/**
	 * Set context ID.
	 * @param $contextId int
	 */
	function setContextId(int $contextId) {
		return $this->setData('contextId', $contextId);
	}


	/**
	 * Get submission ID.
	 * @return int
	 */
	function getSubmissionId(): int {
		return $this->getData('submissionId');
	}

	/**
	 * Set submission ID.
	 * @param $submissionId int
	 */
	function setSubmissionId(int $submissionId) {
		return $this->setData('submissionId', $submissionId);
	}

	/**
	 * Get Publication Id.
	 * @return int
	 */
	function getPublicationId(): int {
		return $this->getData('publicationId');
	}

	/**
	 * Set Publication Id.
	 * @return int
	 * @param $publicationId int	 * 
	 */
	function setPublicationId(int $publicationId){
		return $this->setData('publicationId', $publicationId);
	}

	/**
	 * Get foreign comment ID.
	 * @return int
	 */
	function getForeignCommentId(): int {
		return $this->getData('foreignCommentId');
	}

	/**
	 * Set foreign comment ID.
	 * @param $foreignCommentId int
	 */
	function setForeignCommentId(int $foreignCommentId) {
		return $this->setData('foreignCommentId', $foreignCommentId);
	}

	/**
	 * Get user ID.
	 * @return int
	 */
	function getUserId(): int {
		return $this->getData('userId');
	}

	/**
	 * Set user ID.
	 * @param $userId int
	 */
	function setUserId(int $userId) {
		return $this->setData('userId', $userId);
	}	

	/**
	 * Get objectId.
	 * @return string
	 */
	function getId(): int {
		return $this->getData('commentId');
	}

	/**
	 * Set objectId.
	 * @param $objectId string
	 */ 
	function setId($objectId) {
		return $this->setData('commentId', $objectId);
	}

	/**
	 * Get dateCreated.
	 * @return string
	 */
	function getDateCreated() {
		return $this->getData('dateCreated');
	}

	/**
	 * Set dateCreated.
	 * @param $dateCreated string
	 */
	function setDateCreated($dateCreated) {
		return $this->setData('dateCreated', $dateCreated);
	}	

	/**
	 * Get commentText.
	 * @return string
	 */
	function getCommentText(): string {
		return $this->getData('commentText');
	}

	/**
	 * Set commentText.
	 * @param $commentText string
	 */
	function setCommentText(string $commentText) {
		return $this->setData('commentText', $commentText);
	}
	
	/**
	 * Get flagged.
	 * @return boolean
	 */
	function getFlagged(): bool {
		return $this->getData('flagged');
	}

	/**
	 * Set flagged.
	 * @param $flagged boolean
	 */
	function setFlagged(bool $flagged) {
		return $this->setData('flagged', $flagged);
	}

	/**
	 * Get date flagged.
	 * @return date
	 */
	function getDateFlagged() {
		return $this->getData('dateFlagged');
	}

	/**
	 * Set date flagged.
	 * @param $dateFlagged datetime
	 */
	function setDateFlagged($dateFlagged) {
		return $this->setData('dateFlagged', $dateFlagged);
	}		

	/**
	 * Set flaggedBy.
	 * @param $flaggedBy id
	 */
	function setFlaggedBy(int $flaggedBy) {
		return $this->setData('flaggedBy', $flaggedBy);
	}		

	/**
	 * Get flaggedBy.
	 */
	function getFlaggedBy(): int {
		return $this->getData('flaggedBy');
	}

	/**
	 * Set flagNote.
	 * @param $flagNote string
	 */
	function setFlagNote(string $flagNote) {
		return $this->setData('flagNote', $flagNote);
	}		

	/**
	 * Get flagNote.
	 */
	function getFlagNote(): string {
		return $this->getData('flagNote');
	}

	/**
	 * Get visible.
	 * @return boolean
	 */
	function getVisible(): bool {
		return $this->getData('visible');
	}

	/**
	 * Set visible.
	 * @param $visible boolean
	 */
	function setVisible(bool $visible) {
		return $this->setData('visible', $visible);
	}	

}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\generic\userComments\classes\userComment\UserComment', '\UserComment');
}

