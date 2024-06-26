<?php

import('lib.pkp.classes.handler.APIHandler');

class UserCommentsHandler extends APIHandler
{
    public function __construct()
    {
        $this->_handlerPath = 'userComments';
        $rolesComment = [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_AUTHOR, ROLE_ID_READER];
        $rolesEdit = [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR];
        $this->_endpoints = array(
            'POST' => array(
                array(
                    'pattern' => $this->getEndpointPattern() . '/submitComment',
                    'handler' => array($this, 'submitComment'),
                    'roles' => $rolesComment
                ),
                array(
                    'pattern' => $this->getEndpointPattern() . '/flagComment',
                    'handler' => array($this, 'flagComment'),
                    'roles' => $rolesComment
                ),      
                array(
                    'pattern' => $this->getEndpointPattern() . '/edit',
                    'handler' => array($this, 'setVisibility'),
                    'roles' => $rolesEdit
                ),                               
            ),
            'GET' => array(
                array(
                    'pattern' => $this->getEndpointPattern() . '/getComment/{commentId}',
                    'handler' => array($this, 'getComment'),
                    'roles' => $rolesComment
                ),
                array(
                    'pattern' => $this->getEndpointPattern() . '/getCommentsByPublication/{publicationId}',
                    'handler' => array($this, 'getCommentsByPublication'),
                    'roles' => $rolesComment
                ),                                
            ),            
        );
        parent::__construct();
    }

	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.ContextAccessPolicy');
		$this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}

    public function getComment($slimRequest, $response, $args)
    {
        $params = $slimRequest->getQueryParams(); // ['searchPhrase' => 'barnes']

        error_log("get comment");

        return $response->withJson(
            ['id' => 1,
            'comment' => "get comment",
        ], 200);
    }

    public function getCommentsByPublication($slimRequest, $response, $args)
    {
        $params = $slimRequest->getQueryParams(); // ['searchPhrase' => 'barnes']
        $request = $this->getRequest();
        $publicationId = (int) $args['publicationId'];
        // $request = $this->getRequest();
        // $baseURL = $request->getBaseURL();

		$userCommentDao = DAORegistry::getDAO('UserCommentDAO');
        $userDao = DAORegistry::getDAO('UserDAO'); 	
        $queryResults = $userCommentDao->getByPublicationId($publicationId);

        $userComments = [];

        while ($userComment = $queryResults->next()) {  
            $user = $userDao->getById($userComment->getUserId());
            $userComments[] = [
            'id' => $userComment->getId(),
            'publicationId' => $userComment->getPublicationId(),
            'publicationVersion' => $userComment->getPublicationVersion(),
            'submissionId' => $userComment->getSubmissionId(),
            'foreignCommentId' => $userComment->getForeignCommentId(),
            'userName' => $user->getFullName(),
            'userOrcid' => $user->getData('orcid'),
            'commentDate' =>$userComment->getDateCreated(),
            'commentText' => $userComment->getCommentText(),
            'flagged' => $userComment->getFlagged(),            
            'flaggedDate' => $userComment->getDateFlagged(),
            'visible' => $userComment->getVisible(),
            ];
        };

        return $response->withJson(
            $userComments, 200);
    }      

    public function submitComment($slimRequest, $response, $args)
    {
        $request = APIHandler::getRequest();
        $requestParams = $slimRequest->getParsedBody();
        $currentUser = $request->getUser();
        $locale = AppLocale::getLocale();

        // This probably is not neccessary
        // get submission values
        // $sanitizedValues = [];
        // $submissionValues = array(
        //     'commentText' => 'string',
        //     'publicationId' => 'integer',
        //     'foreignCommentId' => 'integer');

        // foreach ($submissionValues as $submissionValue => $type) {
        //     if (gettype($requestParams[$submissionValue]) == $type) {
        //         $sanitizedValues [] = $requestParams[$submissionValue];
        //     }
        //     else {
        //         return $response->withJson(
        //             ['error' => 'wrong type',
        //         ], 400);
        //     }
        // }

        $publicationId = $requestParams['publicationId'];
        $foreignCommentId = $requestParams['foreignCommentId'];     
        $submissionId = $requestParams['submissionId'];  
        $publicationVersion = null;
        $commentText = $requestParams['commentText'];

        // Creata a DAO for user comments
        import('plugins.generic.userComments.classes.UserCommentDAO');
        $UserCommentDao = new UserCommentDAO();
        DAORegistry::registerDAO('UserCommentDAO', $UserCommentDao);
            
        // Create the data object
        $UserComment = $UserCommentDao->newDataObject(); 
        $UserComment->setContextId(1);
        $UserComment->setUserId($currentUser->getId());
        $UserComment->setPublicationId($publicationId);
        $UserComment->setForeignCommentId($foreignCommentId);        
        $UserComment->setSubmissionId($submissionId);
        $UserComment->setPublicationVersion($publicationVersion);
        $UserComment->setCommentText($commentText);

        // Insert the data object
        $commentId = $UserCommentDao->insertObject($UserComment);

        // Log the event in the event log related to the submission
		$msg = 'comment.event.posted';
        import('plugins.generic.userComments.classes.log.CommentLog');
        import('plugins.generic.userComments.classes.log.CommentEventLogEntry'); // We need this for the ASSOC_TYPE and EVENT_TYPE constants
        $logDetails = array(
            'publicationId' => $publicationId,
            'commentId' => $commentId,
            'foreignCommentId' => $foreignCommentId,
            'userId' => $currentUser->getId(),            
        );
        // $request, $submission, $eventType, $messageKey, $params = array()
        CommentLog::logEvent($request, $commentId, COMMENT_POSTED, $msg, $logDetails);

        return $response->withJson(
            ['id' => 1,
            'comment' => $userComment,
        ], 200);
    }

    public function flagComment($slimRequest, $response, $args)
    {
        $request = APIHandler::getRequest();
        $requestParams = $slimRequest->getParsedBody();
        $currentUser = $request->getUser();
        $locale = AppLocale::getLocale();

        $userCommentId = $requestParams['userCommentId'];
        $publicationId = $requestParams['publicationId'];
        // Validate input
        if ( gettype($userCommentId) != 'integer') {
            return $response->withJson(
                ['error' => 'wrong type',
            ], 400);            
        }
        if ( gettype($publicationId) != 'integer') {
            return $response->withJson(
                ['error' => 'wrong type',
            ], 400);            
        }        

        // Create a DAO for user comments
        import('plugins.generic.userComments.classes.UserCommentDAO');
        $UserCommentDao = new UserCommentDAO();
        DAORegistry::registerDAO('UserCommentDAO', $UserCommentDao);

        // Get the data object
        $userComment = $UserCommentDao->getById($userCommentId);
            
        // Update the data object
        // $commentId = $UserCommentDao->updateFlag($userCommentId);
        $userComment->setFlagged(true);
        $userComment->setDateFlagged(Now());
        $userComment->setFlaggedBy($currentUser->getId());
        $UserCommentDao->updateObject($userComment);        

        // Log the event
		// Flagging is logged in the event log and is related to the submission
		$msg = 'comment.event.flagged';
        import('plugins.generic.userComments.classes.log.CommentLog');
        import('plugins.generic.userComments.classes.log.CommentEventLogEntry'); // We need this for the ASSOC_TYPE and EVENT_TYPE constants
        $logDetails = array(
            'publicationId' => $publicationId,
            'commentId' => $userCommentId,
            'userId' => $currentUser->getId(),            
        );
        // $request, $submission, $eventType, $messageKey, $params = array()
        CommentLog::logEvent($request, $userCommentId, COMMENT_FLAGGED, $msg, $logDetails);

        return $response->withJson(
            ['id' => $commentId,
            'comment' => 'comment was flagged',
        ], 200);
    }

    public function setVisibility($slimRequest, $response, $args)
    {
        // User comments may not be deleted
        // This changes the visibility of the comment
        // and/or the flagging
        $request = APIHandler::getRequest();
        $requestParams = $slimRequest->getParsedBody();
        $userCommentId = $requestParams['userCommentId'];
        $publicationId = $requestParams['publicationId'];
        $visible = $requestParams['visible'];
        $flagged = $requestParams['flagged'];
        $messageKey = '';
        // error_log("setVisibility: " . $visible . " on " . $userCommentId);
        $currentUser = $request->getUser();
        $locale = AppLocale::getLocale();

        // Create a DAO for user comments
        import('plugins.generic.userComments.classes.UserCommentDAO');
        $UserCommentDao = new UserCommentDAO();
        DAORegistry::registerDAO('UserCommentDAO', $UserCommentDao);

        // Get the data object
        $userComment = $UserCommentDao->getById($userCommentId);    

        // Import the classes for logging
        import('plugins.generic.userComments.classes.log.CommentLog');
        import('plugins.generic.userComments.classes.log.CommentEventLogEntry'); // We need this for the ASSOC_TYPE and EVENT_TYPE constants

        // Update the data object
        // Only possible value for flagged should be false, since once the flag is removed, 
        // the comment is removed from the list of flagged comments as well
        $userComment->setFlagged($flagged == 1 ? true : false);
        if ($flagged != 1) {
            // if the comment is un-flagged, it has to be visible
            $userComment->setVisible(true);
            // In this cas the logged message relates to this event
            $messageKey = COMMENT_UNFLAGGED;
            $msg = 'comment.event.unflag';
        } else {
            $userComment->setVisible($visible == 'true' ? true : false);
            $messageKey = $visible == 'true' ? COMMENT_VISIBLE : COMMENT_HIDDEN;
            $msg = $visible == 'true' ? 'comment.event.setvisible' : 'comment.event.hide';
        }
        
        $UserCommentDao->updateObject($userComment);               

        // Log the event
        // Some log details are redundant, but since I'm unsure about the validity of ASSOC_TYPE I will maintain these for now 
        $logDetails = array(
            'publicationId' => $publicationId,
            'commentId' => $userCommentId,
            'userId' => $currentUser->getId(),
        );
        // $request, $commentId, $eventType, $messageKey, $params = array()
        CommentLog::logEvent($request, $userCommentId, $messageKey, $msg, $logDetails);

        return $response->withJson(
            ['id' => $userCommentId,
            'comment' => 'comment visibilty was changed',
        ], 200);        

    }

}
