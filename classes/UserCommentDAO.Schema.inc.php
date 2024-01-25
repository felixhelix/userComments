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

 import('plugins.generic.comments.classes.UserComment');
import('lib.pkp.classes.db.SchemaDAO');

use Illuminate\Database\Capsule\Manager as Capsule;

class UserCommentDAO extends SchemaDAO {
	/** @var string One of the SCHEMA_... constants */
	var $schemaName = SCHEMA_USERCOMMENT;

	/** @var string The name of the primary table for this object */
	var $tableName = 'user_comments';

	/** @var string The name of the settings table for this object */
	var $settingsTableName = 'user_comment_settings';

	/** @var string The column name for the object id in primary and settings tables */
	var $primaryKeyColumn = 'object_id';

	/** @var array Maps schema properties for the primary table to their column names */
	var $primaryTableColumns = [
		'id' => 'object_id',
		'submissionId' => 'submission_id',
		'contextId' => 'context_id',
		'userId' => 'user_id',
		'foreignCommentId' => 'foreign_comment_id',
		'dateCreated' => 'date_created',
		'dateFlagged' => 'date_flagged',
		'visible' => 'visible',
		'publicationVersion' => 'publication_version',
		'publicationId' => 'publication_id',
	];

	/**
	 * Get a new data object.
	 * @return DataObject
	 */
	function newDataObject() {
		return new UserComment();
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

}


