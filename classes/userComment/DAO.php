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

namespace APP\plugins\generic\userComments\classes\userComment;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use PKP\core\EntityDAO;
use PKP\core\traits\EntityWithParent;

class DAO extends EntityDAO {

	use EntityWithParent;

    public $schema = 'userComment';
    public $table = 'user_comments';
    public $settingsTable = 'user_comment_settings';
    public $primaryKeyColumn = 'comment_id';
    public $primaryTableColumns = [
        'commentId' => 'comment_id',
		'contextId' => 'context_id',
		'userId' => 'user_id',
		'submissionId' => 'submission_id',
		'publicationId' => 'publication_id',
		'publicationVersion' => 'publication_version',
		'foreignCommentId' => 'foreign_comment_id',
		'dateCreated' => 'date_created',
		'flagged' => 'flagged',
		'dateFlagged' => 'date_flagged',		
		'visible' => 'visible',
    ];	

    public function getParentColumn(): string
    {
        return 'context_id';
    }	

	/**
	 * Generate a new userComment object.
	 * @return UserComment
	 */
	function newDataObject(): UserComment {
		return app(UserComment::class);
	}

    // public function getCount(Collector $query): int
    // {
    //     return $query
    //         ->getQueryBuilder()
    //         ->get('cq.' . $this->primaryKeyColumn)
    //         ->count();
    // }

    // public function getIds(Collector $query): Collection
    // {
    //     return $query
    //         ->getQueryBuilder()
    //         ->select('cq.' . $this->primaryKeyColumn)
    //         ->pluck('cq.' . $this->primaryKeyColumn);
    // }

    public function getMany(Collector $query): LazyCollection
    {
        $rows = $query
            ->getQueryBuilder()
            ->get();

        return LazyCollection::make(function () use ($rows) {
            foreach ($rows as $row) {
                yield $row->comment_id => $this->fromRow($row);
            }
        });
    }	

    public function fromRow(object $row): UserComment
    {
        return parent::fromRow($row);
    }	

    public function insert(UserComment $UserComment): int
    {
        return parent::_insert($UserComment);
    }

    public function update(UserComment $UserComment): void
    {
        parent::_update($UserComment);
    }

    public function delete(UserComment $UserComment): void
    {
        parent::_delete($UserComment);
    }
	



}
