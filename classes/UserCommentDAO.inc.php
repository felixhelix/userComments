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
	function getById(int $objectId) {
		$params = [(int) $objectId];

		$result = $this->retrieve(
			'SELECT * FROM user_comments WHERE object_id = ?'
			. ($submissionId?' AND submission_id = ?':''),
			$params
		);

		$row = $result->current();
		return $row ? $this->_fromRow((array) $row) : null;
	}

	/**
	 * Get UserComments objects by submission ID
	 * @param $submissionId int Submission ID
	 * @param $publicationVersion int Publication version number
	 * @param $contextId int (optional) context ID
	 */
	function getBySubmissionId($submissionId, $publicationVersion = 1, $contextId = null) {
		$params = [(int) $submissionId, (int) $version];
		if ($contextId) $params[] = (int) $contextId;

		$result = $this->retrieve(
			'SELECT * FROM user_comments WHERE submission_id = ? AND publication_version = ?'
			. ($contextId?' AND context_id = ?':''),
			$params
		);

		return new DAOResultFactory($result, $this, '_fromRow');
	}

	/**
	 * Get UserComments objects by publication ID
	 * @param $publicationId int Publication ID
	 * @param $contextId int (optional) context ID
	 */
	function getByPublicationId($publicationId, $contextId = null) {
		$params = [(int) $publicationId];
		if ($contextId) $params[] = (int) $contextId;

		$result = $this->retrieve(
			'SELECT * FROM user_comments WHERE publication_id = ?'
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
			'INSERT INTO user_comments (submission_id, publication_id, publication_version, context_id, user_id, foreign_comment_id, date_created) VALUES (?, ?, ?, ?, ?, ?, NOW())',
			array(
				$userComment->getSubmissionId()  == "NULL" ? null : $userComment->getSubmissionId(),
				$userComment->getPublicationId(),
				$userComment->getPublicationVersion()  == "NULL" ? null : $userComment->getPublicationVersion(),
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
		$this->update(
			'UPDATE user_comments 
			SET visible = ?, 
			date_flagged = ? 
			WHERE object_id = ?',
			array(
				(bool) $userComment->getVisible(),
				$userComment->getDateFlagged(),
				(int) $userComment->getId()
			)
		);
		$this->updateLocaleFields($userComment);
	}

	/**
	 * Update the database with a userComment object
	 * @param $objectId objectId
	 */
	function updateFlag($objectId) {
		$this->update(
			'UPDATE	user_comments
			SET	date_flagged = NOW()
			WHERE object_id = ?',
		array(
			$objectId
			)	
		);
		return $objectId;
	}	

	/**
	 * Get a object for UserComments by submission ID
	 * @param $contextId int (optional) context ID
	 */
	function getFlagged($contextId = null) {
		if ($contextId) $params[] = (int) $contextId;

		$result = $this->retrieve(
			'SELECT * FROM user_comments WHERE date_flagged IS NOT NULL'
			. ($contextId?' AND context_id = ?':''),
			$params
		);

		return new DAOResultFactory($result, $this, '_fromRow');
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
		$userComment->setPublicationId($row['publication_id']);		
		$userComment->setPublicationVersion($row['publication_version']);		
		$userComment->setForeignCommentId($row['foreign_comment_id']);
		$userComment->setDateCreated($row['date_created']);
		$userComment->setDateFlagged($row['date_flagged']);
		$userComment->setVisible($row['visible']);
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
		return array('commentText','flaggedBy');
	}

	/**
	 * Update the settings for this object
	 * @param $userComment object
	 */
	function updateLocaleFields($userComment) {
		$this->updateDataObjectSettings('user_comment_settings', $userComment, array('object_id' => (int) $userComment->getId()));
	}

	/**
	 * A helper function to compile the key/value set for the primary table
	 *
	 * @param DataObject
	 * @return array
	 */
	private function _getPrimaryDbProps($object) {
		$schema = Services::get('schema')->get($this->schemaName);
		$sanitizedProps = Services::get('schema')->sanitize($this->schemaName, $object->_data);

		$primaryDbProps = [];
		foreach ($this->primaryTableColumns as $propName => $columnName) {
			if ($propName !== 'id' && array_key_exists($propName, $sanitizedProps)) {
				// If the value is null and the prop is nullable, leave it null
				if (is_null($sanitizedProps[$propName])
						&& isset($schema->properties->{$propName}->validation)
						&& in_array('nullable', $schema->properties->{$propName}->validation)) {
					$primaryDbProps[$columnName] = null;

				// Convert empty string values for DATETIME columns into null values
				// because an empty string can not be saved to a DATETIME column
				} elseif (array_key_exists($columnName, $sanitizedProps)
						&& $sanitizedProps[$columnName] === ''
						&& isset($schema->properties->{$propName}->validation)
						&& (
							in_array('date_format:Y-m-d H:i:s', $schema->properties->{$propName}->validation)
							|| in_array('date_format:Y-m-d', $schema->properties->{$propName}->validation)
						)
				) {
					$primaryDbProps[$columnName] = null;
				} else {
					$primaryDbProps[$columnName] = $this->convertToDB($sanitizedProps[$propName], $schema->properties->{$propName}->type);
				}
			}
		}

		return $primaryDbProps;
	}	

}

?>
