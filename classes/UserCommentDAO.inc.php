<?php

/**
 * @file plugins/generic/comments/classes/classes/UserCommentDAO.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class UserCommentDAO
 * @ingroup plugins_generic_comments
 *
 * Operations for retrieving and modifying userComment objects.
 */

import('lib.pkp.classes.db.DAO');
import('plugins.generic.comments.classes.UserComment');

class UserCommentDAO extends DAO {

	/**
	 * Get a object for UserComments by ID
	 * @param $objectId int UserComments ID
	 * @param $submissionId int (optional) Submission ID
	 */
	function getById($objectId, $submissionId = null) {
		$params = [(int) $objectId];
		if ($submissionId) $params[] = (int) $submissionId;

		$result = $this->retrieve(
			'SELECT * FROM user_comments WHERE object_id = ?'
			. ($submissionId?' AND submission_id = ?':''),
			$params
		);

		$row = $result->current();
		return $row ? $this->_fromRow((array) $row) : null;
	}

	/**
	 * Get a object for UserComments by submission ID
	 * @param $submissionId int Submission ID
	 * @param $contextId int (optional) context ID
	 */
	function getBySubmissionId($submissionId, $contextId = null) {
		$params = [(int) $submissionId];
		if ($contextId) $params[] = (int) $contextId;

		$result = $this->retrieve(
			'SELECT * FROM user_comments WHERE submission_id = ?'
			. ($contextId?' AND context_id = ?':''),
			$params
		);

		return new DAOResultFactory($result, $this, '_fromRow');
	}

	/**
	 * Get a object for UserComments by user ID
	 * @param $userId int User ID
	 * @param $contextId int (optional) context ID
	 */
	function getByUserId($userId, $contextId = null) {
		$params = [(int) $userId];

		$result = $this->retrieve(
			'SELECT * FROM user_comments WHERE user_id = ?'
			. ($contextId?' AND context_id = ?':''),
			$params
		);

		return new DAOResultFactory($result, $this, '_fromRow');
	}

	/**
	 * Insert a user comment.
	 * @param $userComment userComment
	 * @return int Inserted userComment ID
	 */
	function insertObject($userComment) {
		$this->update(
			'INSERT INTO user_comments (submission_id, context_id, user_id, foreign_comment_id, date_created) VALUES (?, ?, ?, ?, NOW())',
			array(
				$userComment->getSubmissionId(),
				(int) $userComment->getContextId(),
				$userComment->getUserId(),
				$userComment->getForeignCommentId() == "NULL" ? null : $userComment->getForeignCommentId(),
			)
		);
		
		$userComment->setId($this->getInsertId());
		$this->updateLocaleFields($userComment);
		return $userComment->getId();

	}

	/**
	 * Update the database with a userComment object
	 * @param $userComment userComment
	 */
	function updateObject($userComment) {
		// $this->update(
		// 	'UPDATE	userComments
		// 	SET	submission_id = ?,
		// 		context_id = ?,
		// 		user_id = ?,
		// 		date_created = ?
		// 	WHERE object_id = ?',
		// 	array(
		// 		$userComment->getSubmissionId(),
		// 		(int) $userComment->getContextId(),
		// 		$userComment->getUserId(),
		// 		$userComment->getDateCreated(),
		// 		(int) $userComment->getId()
		// 	)
		// );
		$this->updateLocaleFields($userComment);
	}

	/**
	 * Delete a userComment by ID.
	 * @param $userComment int
	 */
	function deleteById($objectId) {
		$this->update(
			'DELETE FROM user_comments WHERE object_id = ?',
			[(int) $objectId]
		);

		$this->update(
			'DELETE FROM user_comment_settings WHERE object_id = ?',
			[(int) $objectId]
		);
	}

	/**
	 * Delete a userComment object.
	 * @param $userComment userComment
	 */
	function deleteObject($userComment) {
		$this->deleteById($userComment->getId());
	}

	/**
	 * Generate a new funder object.
	 * @return userComment
	 */
	function newDataObject() {
		return new userComment();
	}

	/**
	 * Return a new funder object from a given row.
	 * @return userComment
	 */
	function _fromRow($row) {
		$userComment = $this->newDataObject();
		$userComment->setId($row['object_id']);
		$userComment->setContextId($row['context_id']);
		$userComment->setUserId($row['user_id']);
		$userComment->setSubmissionId($row['submission_id']);
		$userComment->setForeignCommentId($row['foreign_comment_id']);
		$userComment->setDateCreated($row['date_created']);
		$this->getDataObjectSettings('user_comment_settings', 'object_id', $row['object_id'], $userComment);

		return $userComment;
	}

	/**
	 * Get the insert ID for the last inserted userComment.
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('user_comments', 'object_id');
	}

	/**
	 * Get the additional field names.
	 * @return array
	 */
	function getAdditionalFieldNames() {
		return array('commentText');
	}

	/**
	 * Update the settings for this object
	 * @param $userComment object
	 */
	function updateLocaleFields($userComment) {
		$this->updateDataObjectSettings('user_comment_settings', $userComment, array('object_id' => (int) $userComment->getId()));
	}

}

?>
