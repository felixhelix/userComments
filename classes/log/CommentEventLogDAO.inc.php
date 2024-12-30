<?php

/**
 * @file classes/log/CommentEventLogDAO.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CommentEventLogDAO
 * @ingroup log
 * @see EventLogDAO
 *
 * @brief Extension to EventLogDAO for submission file specific log entries.
 */

import('lib.pkp.classes.log.EventLogDAO');
import('plugins.generic.userComments.classes.log.CommentEventLogEntry');

class CommentEventLogDAO extends EventLogDAO {

	/**
	 * Instantiate a submission file event log entry.
	 * @return CommentEventLogEntry
	 */
	function newDataObject() {
		$returner = new CommentEventLogEntry();
		$returner->setAssocType(ASSOC_TYPE_COMMENT);
		return $returner;
	}
}


