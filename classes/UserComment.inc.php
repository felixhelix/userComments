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

class UserComment extends DataObject {

	//
	// Get/set methods
	//

	/**
	 * Get context ID.
	 * @return int
	 */
	function getContextId(){
		return $this->getData('contextId');
	}

	/**
	 * Set context ID.
	 * @param $contextId int
	 */
	function setContextId($contextId) {
		return $this->setData('contextId', $contextId);
	}

	/**
	 * Get submission ID.
	 * @return int
	 */
	function getSubmissionId(){
		return $this->getData('submissionId');
	}

	/**
	 * Set submission ID.
	 * @param $submissionId int
	 */
	function setSubmissionId($submissionId) {
		return $this->setData('submissionId', $submissionId);
	}

	/**
	 * Get Publication Id.
	 * @return int
	 */
	function getPublicationId(){
		return $this->getData('publication_id');
	}

	/**
	 * Set Publication Id.
	 * @return int
	 * @param $publicationId int	 * 
	 */
	function setPublicationId($publicationId){
		return $this->setData('publication_id', $publicationId);
	}

	/**
	 * Get Publication version.
	 * @return int
	 */
	function getPublicationVersion(){
		return $this->getData('publication_version');
	}

	/**
	 * Set Publication version.
	 * @return int
	 * @param $publicationVersion int	 * 
	 */
	function setPublicationVersion($publicationVersion){
		return $this->setData('publication_version', $publicationVersion);
	}

	/**
	 * Get foreign comment ID.
	 * @return int
	 */
	function getForeignCommentId(){
		return $this->getData('foreignCommentId');
	}

	/**
	 * Set foreign comment ID.
	 * @param $foreignCommentId int
	 */
	function setForeignCommentId($foreignCommentId) {
		return $this->setData('foreignCommentId', $foreignCommentId);
	}

	/**
	 * Get user ID.
	 * @return int
	 */
	function getUserId(){
		return $this->getData('userId');
	}

	/**
	 * Set user ID.
	 * @param $userId int
	 */
	function setUserId($userId) {
		return $this->setData('userId', $userId);
	}	

	/**
	 * Get objectId.
	 * @return string
	 */
	function getId() {
		return $this->getData('objectId');
	}

	/**
	 * Set objectId.
	 * @param $objectId string
	 */ 
	function setId($objectId) {
		return $this->setData('objectId', $objectId);
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
	function getCommentText() {
		return $this->getData('commentText');
	}

	/**
	 * Set commentText.
	 * @param $commentText string
	 */
	function setCommentText($commentText) {
		return $this->setData('commentText', $commentText);
	}

	/**
	 * Get flagged.
	 * @return date
	 */
	function getDateFlagged() {
		return $this->getData('dateFlagged');
	}
	

	/**
	 * Set dateFlagged.
	 * @param $flagged date
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
	 * @param $flaggedBy id
	 */
	function getFlaggedBy() {
		return $this->getData('flaggedBy', $flaggedBy);
	}

	/**
	 * Get visible.
	 * @return boolean
	 */
	function getVisible() {
		return $this->getData('visible');
	}

	/**
	 * Set visible.
	 * @param $visible boolean
	 */
	function setVisible($visible) {
		return $this->setData('visible', $visible);
	}	

}

?>
