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
            ];
        };
        
        error_log("getCommentsBySubmission: " . json_encode($userComments));

        return $response->withJson(
            $userComments, 200);
    }    

    public function getComments($slimRequest, $response, $args)
    {
        $params = $slimRequest->getQueryParams(); // ['searchPhrase' => 'barnes']

        error_log("get comments");



        return $response->withJson([
            ['id' => 1,
            'submissionId' => 1,
            'foreignCommentId' => Null,
            'commentText' => "first API comment",
            ],
            ['id' => 2,
            'submissionId' => 1,
            'foreignCommentId' => 1,
            'commentText' => "second API comment",
            ],
            ['id' => 3,
            'submissionId' => 2,
            'foreignCommentId' => Null,
            'commentText' => "third API comment",
            ],
        ], 200);
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

}
