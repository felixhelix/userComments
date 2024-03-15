<?php

import('lib.pkp.classes.handler.APIHandler');

class UserCommentsHandler extends APIHandler
{
    public function __construct()
    {
        $this->_handlerPath = 'userComments';
        $roles = [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_AUTHOR];
        $this->_endpoints = array(
            'POST' => array(
                array(
                    'pattern' => $this->getEndpointPattern() . '/submitComment',
                    'handler' => array($this, 'submitComment'),
                    'roles' => $roles
                ),
                array(
                    'pattern' => $this->getEndpointPattern() . '/flagComment',
                    'handler' => array($this, 'flagComment'),
                    'roles' => $roles
                ),      
                array(
                    'pattern' => $this->getEndpointPattern() . '/edit',
                    'handler' => array($this, 'setVisibility'),
                    'roles' => $roles
                ),                               
            ),
            'GET' => array(
                array(
                    'pattern' => $this->getEndpointPattern() . '/getComment/{commentId}',
                    'handler' => array($this, 'getComment'),
                    'roles' => $roles
                ),
                array(
                    'pattern' => $this->getEndpointPattern() . '/getCommentsByPublication/{publicationId}',
                    'handler' => array($this, 'getCommentsByPublication'),
                    'roles' => $roles
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
        // array_pop(explode('/', $slimRequest->getRequestPath()))
        // var_dump($slimRequest);
        // error_log("get comments by submission: " . json_encode(explode('/', $slimRequest->getRequestPath())));
        // error_log("get comments by submission: " . json_encode($args));
        $publicationId = (int) $args['publicationId'];

		$userCommentDao = DAORegistry::getDAO('UserCommentDAO');
        $userDao = DAORegistry::getDAO('UserDAO'); 	
        $queryResults = $userCommentDao->getByPublicationId($publicationId);
		// $userComments = $queryResults->toArray();

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
            'commentDate' =>$userComment->getDateCreated(),
            'commentText' => $userComment->getCommentText(),
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
        $submissionId = null;
        $publicationVersion = null;
        $commentText = $requestParams['commentText'];

        // Creata a DAO for user comments
        import('plugins.generic.comments.classes.UserCommentDAO');
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
        // Validate input
        if ( gettype($userCommentId) != 'integer') {
            return $response->withJson(
                ['error' => 'wrong type',
            ], 400);            
        }

        // Create a DAO for user comments
        import('plugins.generic.comments.classes.UserCommentDAO');
        $UserCommentDao = new UserCommentDAO();
        DAORegistry::registerDAO('UserCommentDAO', $UserCommentDao);

        // Get the data object
        $userComment = $UserCommentDao->getById($userCommentId);
            
        // Update the data object
        // $commentId = $UserCommentDao->updateFlag($userCommentId);
        $userComment->setDateFlagged(Now());
        $userComment->setFlaggedBy($currentUser->getId());
        error_log("get commentId: " . $userComment->getId());
        $UserCommentDao->updateObject($userComment);        

        return $response->withJson(
            ['id' => $commentId,
            'comment' => 'comment was flagged',
        ], 200);
    }

    public function setVisibility($slimRequest, $response, $args)
    {
        // User comments may not be deleted
        // This changes the visibility of the comment
        $request = APIHandler::getRequest();
        $requestParams = $slimRequest->getParsedBody();
        $userCommentId = $requestParams['userCommentId'];
        $visible = $requestParams['visible'];
        $flagged = $requestParams['flagged'];
        // error_log("setVisibility: " . $visible . " on " . $userCommentId);
        $currentUser = $request->getUser();
        $locale = AppLocale::getLocale();

        // Create a DAO for user comments
        import('plugins.generic.comments.classes.UserCommentDAO');
        $UserCommentDao = new UserCommentDAO();
        DAORegistry::registerDAO('UserCommentDAO', $UserCommentDao);

        // Get the data object
        $userComment = $UserCommentDao->getById($userCommentId);    
            
        // Update the data object
        // Only possible value for flagged should be false, since once the flag is removed, 
        // the comment is removed from the list of flagged comments as well
        $userComment->setFlagged($flagged == 'true' ? true : false);
        if ($flagged == 'false') {
            // if the comment is un-flagged, it has to be visible
            $userComment->setVisible(true);
        } else {
            $userComment->setVisible($visible == 'true' ? true : false);
        }
        

        $UserCommentDao->updateObject($userComment);               

        return $response->withJson(
            ['id' => $userCommentId,
            'comment' => 'comment visibilty was changed',
        ], 200);        

    }

}
