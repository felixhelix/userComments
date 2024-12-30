<?php

/**
 * @file classes/log/CommentEventLogEntry.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CommentEventLogEntry
 * @ingroup log
 * @see CommentEventLogDAO
 *
 * @brief Describes an entry in the event log.
 */

import('lib.pkp.classes.log.EventLogEntry');


define('ASSOC_TYPE_COMMENT',			0x0000209);

// Comment events
define('COMMENT_POSTED',		0x80000001);
define('COMMENT_FLAGGED',		0x80000002);
define('COMMENT_UNFLAGGED',		0x80000003);
define('COMMENT_HIDDEN', 		0x80000004);
define('COMMENT_VISIBLE', 	0x80000005);

class CommentEventLogEntry extends EventLogEntry {

}


