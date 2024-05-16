<?php

/**
 * @file plugins.generic.comments.classes.log.CommentLog.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CommentLog
 * @ingroup log
 *
 * @brief Static class for adding / accessing comment log entries.
 */


class CommentLog {
	/**
	 * Add a new event log entry with the specified parameters
	 * @param $request object
	 * @param $submission object
	 * @param $eventType int
	 * @param $messageKey string
	 * @param $params array optional
	 * @return object SubmissionLogEntry iff the event was logged
	 */
	static function logEvent($request, $commentId, $eventType, $messageKey, $params = array()) {
		// Create a new entry object
		import('plugins.generic.comments.classes.log.CommentEventLogDAO');
		$CommentEventLogDao = new CommentEventLogDAO();
		DAORegistry::registerDAO('CommentEventLogDAO', $CommentEventLogDAO);
		$entry = $CommentEventLogDao->newDataObject();

		// Set implicit parts of the log entry
		$entry->setDateLogged(Core::getCurrentDate());

		if (Validation::isLoggedInAs()) {
			// If user is logged in as another user log with real userid
			$sessionManager = SessionManager::getManager();
			$session = $sessionManager->getUserSession();
			$userId = $session->getSessionVar('signedInAs');
			if ($userId) $entry->setUserId($userId);
		} else {
			$user = $request->getUser();
			if ($user) $entry->setUserId($user->getId());
		}

		$entry->setAssocType(ASSOC_TYPE_COMMENT);

		// Set explicit parts of the log entry
		$entry->setAssocId($commentId);
		$entry->setEventType($eventType);
		$entry->setMessage($messageKey);
		$entry->setParams($params);
		$entry->setIsTranslated(0); // Legacy for other apps. All messages use locale keys.

		// Insert the resulting object
		$CommentEventLogDao->insertObject($entry);
		return $entry;
	}
}


