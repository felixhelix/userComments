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
                    'pattern' => $this->getEndpointPattern() . '/update',
                    'handler' => array($this, 'toggleComment'),
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
                    'pattern' => $this->getEndpointPattern() . '/getCommentsBySubmission/{submissionId}',
                    'handler' => array($this, 'getCommentsBySubmission'),
                    'roles' => $roles
                ),  
                array(
                    'pattern' => $this->getEndpointPattern() . '/getComments',
                    'handler' => array($this, 'getComments'),
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

    public function getCommentsBySubmission($slimRequest, $response, $args)
    {
        $params = $slimRequest->getQueryParams(); // ['searchPhrase' => 'barnes']
        // array_pop(explode('/', $slimRequest->getRequestPath()))
        // var_dump($slimRequest);
        // error_log("get comments by submission: " . json_encode(explode('/', $slimRequest->getRequestPath())));
        // error_log("get comments by submission: " . json_encode($args));
        $submissionId = (int) $args['submissionId'];

		$userCommentDao = DAORegistry::getDAO('UserCommentDAO');
        $userDao = DAORegistry::getDAO('UserDAO'); 	
        $queryResults = $userCommentDao->getBySubmissionId($submissionId);
		// $userComments = $queryResults->toArray();

        $userComments = [];

        while ($userComment = $queryResults->next()) {  
            $user = $userDao->getById($userComment->getUserId());
            $userComments[] = [
            'id' => $userComment->getId(),
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
        $commentText = $requestParams['commentText'];
        $submissionId = $requestParams['submissionId'];
        $foreignCommentId = $requestParams['foreignCommentId'];
        $currentUser = $request->getUser();
        $locale = AppLocale::getLocale();

        error_log("submitted comment: " . $reviewComment);
        error_log("locale: " . $locale);

        // Creata a DAO for user comments
        import('plugins.generic.comments.classes.UserCommentDAO');
        $UserCommentDao = new UserCommentDAO();
        DAORegistry::registerDAO('UserCommentDAO', $UserCommentDao);
            
        // Create the data object
        $UserComment = $UserCommentDao->newDataObject(); 
        $UserComment->setSubmissionId($submissionId);
        $UserComment->setForeignCommentId($foreignCommentId);        
        $UserComment->setContextId(1);
        $UserComment->setUserId($currentUser->getId());

        // add the author comment
        // $UserComment->setData('authorReply', $userComment, $locale); // This inserts a serialized (JSON) string in the setting_value field
        $UserComment->setCommentText($commentText);

        error_log(json_encode($UserComment));

        // Insert the data object
        $commentId = $UserCommentDao->insertObject($UserComment);
        error_log("comment id: " . $CommentId);

        return $response->withJson(
            ['id' => 1,
            'comment' => $userComment,
        ], 200);
    }

    public function flagComment($slimRequest, $response, $args)
    {
        $request = APIHandler::getRequest();
        $requestParams = $slimRequest->getParsedBody();
        $userCommentId = $requestParams['userCommentId'];
        $currentUser = $request->getUser();
        $locale = AppLocale::getLocale();

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

    public function toggleComment($slimRequest, $response, $args)
    {
        // User comments may not be deleted
        // This changes the visibility of the comment
        $request = APIHandler::getRequest();
        $requestParams = $slimRequest->getParsedBody();
        $userCommentId = $requestParams['userCommentId'];
        $visible = $requestParams['visible'];
        $currentUser = $request->getUser();
        $locale = AppLocale::getLocale();

        // Create a DAO for user comments
        import('plugins.generic.comments.classes.UserCommentDAO');
        $UserCommentDao = new UserCommentDAO();
        DAORegistry::registerDAO('UserCommentDAO', $UserCommentDao);

        // Get the data object
        $userComment = $UserCommentDao->getById($userCommentId);    
            
        // Update the data object
        $userComment->setVisible(!$visible);
        $UserCommentDao->updateObject($userComment);               

        return $response->withJson(
            ['id' => $userCommentId,
            'comment' => 'comment visibilty was changed',
        ], 200);        

    }

}
