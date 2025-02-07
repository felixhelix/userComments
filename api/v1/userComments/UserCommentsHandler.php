<?php

namespace APP\plugins\generic\userComments\api\v1\userComments;

use Slim\Http\Request as SlimRequest;
use PKP\core\Core;
use PKP\core\PKPApplication;
use PKP\core\APIResponse;
use PKP\core\PKPBaseController;
use PKP\handler\APIHandler;
use PKP\db\DAORegistry;
use PKP\security\authorization\ContextAccessPolicy;
use PKP\security\Role;
use PKP\security\Validation;
use PKP\facades\Locale;
use PKP\log\event\EventLogEntry;
use APP\core\Application;
use APP\core\Services;
use APP\plugins\generic\userComments\classes\userComment;
use APP\plugins\generic\userComments\classes\facades\Repo;
// use APP\facades\Repo;

// Comment events
define('COMMENT_POSTED',		0x80000001);
define('COMMENT_FLAGGED',		0x80000002);
define('COMMENT_UNFLAGGED',		0x80000003);
define('COMMENT_HIDDEN', 		0x80000004);
define('COMMENT_VISIBLE', 	0x80000005);

class UserCommentsHandler extends APIHandler
{
    /**
     * Constructor
     */    
    public function __construct()
    {
        $this->_handlerPath = 'userComments';
        $rolesComment = [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SUB_EDITOR, Role::ROLE_ID_AUTHOR, Role::ROLE_ID_READER];
        $rolesEdit = [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SUB_EDITOR];
        $this->_endpoints = [
            'POST' => [
                [
                    'pattern' => $this->getEndpointPattern() . '/add',
                    'handler' => [$this, 'submitComment'],
                    'roles' => $rolesComment
                ],
                [
                    'pattern' => $this->getEndpointPattern() . '/flag',
                    'handler' => [$this, 'flagComment'],
                    'roles' => $rolesComment
                ],      
                [
                    'pattern' => $this->getEndpointPattern() . '/edit',
                    'handler' => [$this, 'setVisibility'],
                    'roles' => $rolesEdit
                ],                               
            ],
            'GET' => [
                [
                    'pattern' => $this->getEndpointPattern() . '/getComment/{commentId}',
                    'handler' => [$this, 'getComment'],
                    'roles' => $rolesComment
                ],
                [
                    'pattern' => $this->getEndpointPattern() . '/getFlaggedComments',
                    'handler' => [$this, 'getFlaggedComments'],
                    'roles' => $rolesComment
                ],                
                [
                    'pattern' => $this->getEndpointPattern() . '/getbypublication/{publicationId}',
                    'handler' => [$this, 'getCommentsByPublication'],   
                    'roles' => $rolesComment                    
                ],                                
            ],            
        ];

        // error_log("UserCommentsHandler called: " . $this->getEndpointPattern()); // . '/getCommentsByPublication/{publicationId}');
        parent::__construct();
        // parent::__construct($controller);
    }

    //
    // Overridden template methods
    //
    /**
     * @copydoc PKPHandler::authorize()
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        if ($request->getContext()) {
            $this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
        } else {
            $this->addPolicy(new PKPSiteAccessPolicy($request, null, $roleAssignments));
        }
        return parent::authorize($request, $args, $roleAssignments);
    }

    public function getComment($slimRequest, $response, $args)
    {
        //$request = APIHandler::getRequest();
        //$context = $request->getContext();        
        $commentId = (int) $args['commentId'];
        $queryResult = Repo::userComment()
            ->get($commentId);

        // $userComment = ['An error has occured :/'];

        // if ($queryResult->isEmpty()) {
        //     $userComment = ['There is no comment with this id.'];
        // }    
        // else { 
        //     $userComment = ['There is a comment with this id :)'];
        //     // Repo::userComment()
        //     //     ->getSchemaMap()
        //     //     ->map($queryResult->values());
        // };

        $userComment = Repo::userComment()
            ->getSchemaMap()
            ->map($queryResult);

        return $response->withJson(
            $userComment, 200);                

        // return $response->withJson(
        //     ['id' => $commentId,
        //     'comment' => $userComment,
        // ], 200);
    }

    public function getCommentsByPublication($slimRequest, $response, $args)
    {
        $params = $slimRequest->getQueryParams(); // ['searchPhrase' => 'barnes']
        $request = $this->getRequest();
        $publicationId = (int) $args['publicationId'];
        // $request = $this->getRequest();
        // $baseURL = $request->getBaseURL();

		// $userCommentDao = DAORegistry::getDAO('UserCommentDAO');
        // $queryResults = $userCommentDao->getByPublicationId($publicationId);

        $queryResults = Repo::userComment()
            ->getCollector()
            ->filterByPublicationIds([$publicationId])
            ->getMany();
            // ->remember();

        if ($queryResults->isEmpty()) {
            $userComments = ['none yet :/'];
        }
        else { 
            // foreach ($queryResults as $userComment) {
            //     $user = Repo::user()->get((int) $userComment->getUserId());   
            //     $userComments[] = [
            //     'id' => $userComment->getId(),
            //     'publicationId' => $userComment->getPublicationId(),                
            //     'publicationVersion' => $userComment->getPublicationVersion(),
            //     'submissionId' => $userComment->getSubmissionId(),
            //     'foreignCommentId' => $userComment->getForeignCommentId(),
            //     'userName' => $user->getFullName(),
            //     'userOrcid' => $user->getData('orcid'),
            //     'userAffiliation' => $user->getLocalizedAffiliation(),
            //     'commentDate' =>$userComment->getDateCreated(),
            //     'commentText' => $userComment->getCommentText(),
            //     'flagged' => $userComment->getFlagged(),            
            //     'flaggedDate' => $userComment->getDateFlagged(),
            //     'visible' => $userComment->getVisible(),
            //     ];
            // }
            $userComments = Repo::userComment()
            ->getSchemaMap()
            ->mapMany($queryResults->values());
        };

        return $response->withJson(
            $userComments, 200);
    }      

    /**
     * @throws \Exception
     */
    public function submitComment(SlimRequest $slimRequest, APIResponse $response, array $args): \Slim\Http\Response
    {
        $request = APIHandler::getRequest();
        $context = $request->getContext();        
        $requestParams = $slimRequest->getParsedBody();
        $currentUser = $request->getUser();
        $locale = Locale::getLocale();

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
            
        // Create the data object
        $userComment = Repo::userComment()->newDataObject();
        $userComment->setDateCreated(Core::getCurrentDate());
        $userComment->setContextId($context->getId());
        $userComment->setUserId($currentUser->getId());
        $userComment->setPublicationId($publicationId);
        $userComment->setForeignCommentId($foreignCommentId);        
        $userComment->setSubmissionId($submissionId);
        $userComment->setPublicationVersion($publicationVersion);
        $userComment->setCommentText($commentText);

        // Insert the data object
        $userCommentId = Repo::userComment()->add($userComment);
        // Get the comment entitty
        // $userComment = $UserCommentDao->getById($commentId);

        // Log the event in the event log related to the submission
		$msg = 'comment.event.posted';
        // import('plugins.generic.userComments.classes.log.CommentLog');
        // import('plugins.generic.userComments.classes.log.CommentEventLogEntry'); // We need this for the ASSOC_TYPE and EVENT_TYPE constants
        // $logDetails = array(
        //     'publicationId' => $publicationId,
        //     'commentId' => $commentId,
        //     'foreignCommentId' => $foreignCommentId,
        //     'userId' => $currentUser->getId(),       
        //     'dateCreated' => $userComment->getDateCreated()     
        // );
        // $request, $submission, $eventType, $messageKey, $params = array()
        // CommentLog::logEvent($request, $commentId, COMMENT_POSTED, $msg, $logDetails);

        $eventLog = Repo::eventLog()->newDataObject([
            'assocType' => PKPApplication::ASSOC_TYPE_PUBLICATION,
            'assocId' => $submissionId,
            'eventType' => EventLogEntry::SUBMISSION_LOG_NOTE_POSTED,
            'userId' => Validation::loggedInAs() ?? $request->getUser()->getId(),
            'userCommentId' => $userCommentId,
            'message' => $msg,
            'isTranslated' => false,
            'dateLogged' => Core::getCurrentDate()
        ]);
        Repo::eventLog()->add($eventLog);        

        return $response->withJson([
            'id' => $userCommentId,
            'comment' => $commentText,
            'userName' => $currentUser->getFullName(),
            'userOrcid' => $currentUser->getData('orcid'),
            'userAffiliation' => $currentUser->getLocalizedAffiliation(),
            'commentDate' => $userComment->getDateCreated(),
        ], 200);
    }

    public function flagComment($slimRequest, $response, $args)
    {
        $request = APIHandler::getRequest();
        $context = $request->getContext();  
        $requestParams = $slimRequest->getParsedBody();
        $currentUser = $request->getUser();
        $locale = Locale::getLocale();

        $userCommentId = $requestParams['userCommentId'];
        $publicationId = $requestParams['publicationId'];
        $flagText = $requestParams['flagText'];
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
            
        // set the data      
        $params = [
            'flagged' => true,
            'dateFlagged' => Core::getCurrentDate(),
            'flagText' => $flagText,
            'flaggedBy' => $currentUser->getId(),
        ];

        // update the entity
        $userComment = Repo::userComment()->get($userCommentId, $context->getId());
        Repo::userComment()->update($userComment, $params);

        // Log the event
		// Flagging is logged in the event log and is related to the submission
		$msg = 'comment.event.flagged';
        // import('plugins.generic.userComments.classes.log.CommentLog');
        // import('plugins.generic.userComments.classes.log.CommentEventLogEntry'); // We need this for the ASSOC_TYPE and EVENT_TYPE constants
        // $logDetails = array(
        //     'publicationId' => $publicationId,
        //     'commentId' => $userCommentId,
        //     'userId' => $currentUser->getId(),            
        // );
        // $request, $submission, $eventType, $messageKey, $params = array()
        // CommentLog::logEvent($request, $userCommentId, COMMENT_FLAGGED, $msg, $logDetails);

        $eventLog = Repo::eventLog()->newDataObject([
            'assocType' => PKPApplication::ASSOC_TYPE_PUBLICATION,
            'assocId' => $publicationId,
            'eventType' => EventLogEntry::SUBMISSION_LOG_NOTE_POSTED,
            'userId' => Validation::loggedInAs() ?? $request->getUser()->getId(),
            'userCommentId' => $userComment->getId(),
            'message' => $msg,
            'isTranslated' => false,
            'dateLogged' => Core::getCurrentDate()
        ]);
        Repo::eventLog()->add($eventLog);             

        return $response->withJson(
            ['id' => $userCommentId,
            'flagged' => true,
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
        $locale = Locale::getLocale();

        // Create a DAO for user comments
        $UserCommentDao = new UserCommentDAO();
        DAORegistry::registerDAO('UserCommentDAO', $UserCommentDao);

        // Get the data object
        $userComment = $UserCommentDao->getById($userCommentId);    

        // Import the classes for logging
        // import('plugins.generic.userComments.classes.log.CommentLog');
        // import('plugins.generic.userComments.classes.log.CommentEventLogEntry'); // We need this for the ASSOC_TYPE and EVENT_TYPE constants

        // Update the data object
        // Only possible value for flagged should be false, since once the flag is removed, 
        // the comment is removed from the list of flagged comments as well
        $userComment->setFlagged($flagged == "true" ? true : false);
        if ($flagged != "true") {
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
        // $logDetails = array(
        //     'publicationId' => $publicationId,
        //     'commentId' => $userCommentId,
        //     'userId' => $currentUser->getId(),
        // );
        // $request, $commentId, $eventType, $messageKey, $params = array()
        // CommentLog::logEvent($request, $userCommentId, $messageKey, $msg, $logDetails);

        $eventLog = Repo::eventLog()->newDataObject([
            'assocType' => PKPApplication::ASSOC_TYPE_PUBLICATION,
            'assocId' => $publicationId,
            'eventType' => EventLogEntry::SUBMISSION_LOG_NOTE_POSTED,
            'userId' => Validation::loggedInAs() ?? $request->getUser()->getId(),
            'message' => $msg,
            'isTranslated' => false,
            'dateLogged' => Core::getCurrentDate()
        ]);
        Repo::eventLog()->add($eventLog); 
        
        return $response->withJson(
            ['id' => $userCommentId,
            'comment' => 'comment visibilty was changed',
        ], 200);        

    }

    function getFlaggedComments($slimRequest, $response, $args) {

        $queryResults = Repo::userComment()
            ->getCollector()
            ->filterByFlag(true)
            ->getMany();
            // ->remember();

        if ($queryResults->isEmpty()) {
            $userComments = ['none yet :/'];
        }
        else { 
            $userComments = Repo::userComment()
            ->getSchemaMap()
            ->mapMany($queryResults->values());
        };

        return $response->withJson(
            $userComments, 200);   
        
        // return $response->withJson(
        //     [
        //         'items'=> [
        //             [ 'itemTitle' => 'title 1' ],
        //             [ 'itemTitle' => 'title 2' ],
        //         ]
        //     ],
        // 200);        

    }

}
