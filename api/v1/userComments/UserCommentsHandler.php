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
use Illuminate\Support\Facades\Mail;
use PKP\mail\Mailable;
use PKP\stageAssignment\StageAssignmentDAO; // used to sent email to
use PKP\user\User;

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
                    'pattern' => $this->getEndpointPattern() . '/update',
                    'handler' => [$this, 'update'],
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

        parent::__construct();
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
        $commentId = (int) $args['commentId'];
        $queryResult = Repo::userComment()
            ->get($commentId);

        if (empty($queryResult)) {
            return $response->withStatus(404)->withJsonError('api.404.resourceNotFound');
        }    
        else { 
            $userComment = Repo::userComment()
                ->getSchemaMap()
                ->map($queryResult);
            return $response->withJson(
                $userComment, 200);                    
        }
        
    }

    public function getCommentsByPublication($slimRequest, $response, $args)
    {
        $params = $slimRequest->getQueryParams();
        $request = $this->getRequest();
        $publicationId = (int) $args['publicationId'];

        $queryResults = Repo::userComment()
            ->getCollector()
            ->filterByPublicationId($publicationId)
            ->getMany();
            // ->remember();

        if (empty($queryResults)) {
            $userComments = [];
        }
        else { 
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

        $submissionId = $requestParams['submissionId'];          
        $publicationId = $requestParams['publicationId'];
        $foreignCommentId = $requestParams['foreignCommentId'];     
        $commentText = $requestParams['commentText'];
            
        // Create the data object
        $userComment = Repo::userComment()->newDataObject();
        $userComment->setDateCreated(Core::getCurrentDate());
        $userComment->setContextId($context->getId());
        $userComment->setUserId($currentUser->getId());
        $userComment->setPublicationId($publicationId);
        $userComment->setForeignCommentId($foreignCommentId);        
        $userComment->setSubmissionId($submissionId);
        $userComment->setCommentText($commentText);

        // Insert the data object
        $commentId = Repo::userComment()->add($userComment);
        // Get the comment entity
        // $userComment = $UserCommentDao->getById($commentId);

        // Log the event in the event log related to the submission
		$msg = 'comment posted: ' . $commentId; // either a locale key or literal string
        // $data = json_decode('{"commentId":"' . $commentId . '", "userCommentText":"' . $commentText . '"}');
        $eventLog = Repo::eventLog()->newDataObject([
            'assocType' => PKPApplication::ASSOC_TYPE_PUBLICATION,
            'assocId' => $submissionId,
            'eventType' => EventLogEntry::SUBMISSION_LOG_NOTE_POSTED,
            'userId' => Validation::loggedInAs() ?? $request->getUser()->getId(),
            'message' => $msg,            
            'isTranslated' => false,
            'dateLogged' => Core::getCurrentDate(),
            'username' => $currentUser->getData('userName'),
            // 'data' => $data, // this should accept an object, but throws an error ?
        ]);
        Repo::eventLog()->add($eventLog);        

        // Return the data, so that the comment list can be updated
        return $response->withJson([
            'id' => $commentId,
            'comment' => $userCommentText,
            'userName' => $currentUser->getFullName(),
            'userOrcid' => $currentUser->getData('orcid'),
            'userAffiliation' => $currentUser->getLocalizedAffiliation(),
            'commentDate' => $userComment->getDateCreated(),
        ], 200);
    }

    public function flagComment($slimRequest, $response, $args)
    {
        $request = APIHandler::getRequest();
        $dispatcher = $request->getDispatcher();
        $context = $request->getContext();  
        $requestParams = $slimRequest->getParsedBody();
        $currentUser = $request->getUser();

        $commentId = $requestParams['commentid'];
        $publicationId = $requestParams['publicationId'];
        $flagNote = $requestParams['flagNote'];
        // Validate input
        if ( gettype($commentId) != 'integer') {
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
            'flaggedBy' => $currentUser->getId(),
            'flagNote' => $flagNote,
        ];

        // update the entity
        $userComment = Repo::userComment()->get($commentId, $context->getId());
        Repo::userComment()->update($userComment, $params);

        // Log the event
		// Flagging is logged in the event log and is related to the submission
		$msg = 'comment flagged: ' . $commentId; // either a locale key or literal string
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

        // Send email
        // get manager/editor contact email
        $recipientIds = [];
        $submission = Repo::submission()->get((int) Repo::publication()->get((int) $publicationId)->getData('submissionId'));
        $stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO'); /** @var StageAssignmentDAO $stageAssignmentDao */
        $editorStageAssignments = $stageAssignmentDao->getEditorsAssignedToStage($submission->getId(), $submission->getStageId());
        foreach ($editorStageAssignments as $editorStageAssignment) {
            $recipientIds[] = $editorStageAssignment->getUserId();
        }
        $recipients = Repo::user()->getCollector()->filterByUserIds($recipientIds)->getMany();

        $editUrl = $dispatcher->url($request, PKPApplication::ROUTE_PAGE, $context->getPath(), 'management', 'settings','website#flaggedUserComments');
        $subject = "Comment #$commentId has been flagged";
        $body = "Comment #$commentId has been flagged.\nThe flagnote is: '$flagNote'.\n<a href='$editUrl'>Log in</a> to edit the comment.";

        $mailable = new Mailable();
        $mailable
            ->from($context->getData('contactEmail'), $context->getData('contactName'))
            ->to($recipients->map(fn(User $recipient) => ['email' => $recipient->getEmail(), 'name' => $recipient->getFullName()])->toArray())
            ->cc($context->getData('contactEmail'), $context->getData('contactName'))
            ->subject($subject)
            ->body($body);
        
        Mail::send($mailable);        

        // Return updated entry
        return $response->withJson(
            ['id' => $commentId,
            'flagged' => true,
        ], 200);
    }

    public function update($slimRequest, $response, $args)
    {
        $request = APIHandler::getRequest();
        $context = $request->getContext();  
        $requestParams = $slimRequest->getParsedBody();
        $currentUser = $request->getUser();

        // set the data      
        $commentId = $requestParams['commentId'];        
        $params = [
            'flagged' => $requestParams['flagged'],
            'visible' => $requestParams['visible'],
        ];

        // update the entity
        $userComment = Repo::userComment()->get($commentId, $context->getId());
        Repo::userComment()->update($userComment, $params);        

        // Log the event
        // There are only two options: 
        // if the entry is not visible, it must have been hidden, 
        // if it is visible (again) it must have been unflagged.
		$msg = 'comment' . $visible?' hidden: ':' unflagged: ' . $commentId; // either a locale key or literal string
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
            ['commentId' => $userComment->getId(),
        ], 200);

    }
    
    function getFlaggedComments($slimRequest, $response, $args) {

        $queryResults = Repo::userComment()
            ->getCollector()
            ->filterByFlag(true)
            ->getMany();
            // ->remember();

        if ($queryResults->isEmpty()) {
            $userComments = [];
        }
        else { 
            $userComments = Repo::userComment()
            ->getSchemaMap()
            ->mapMany($queryResults->values());
        };

        return $response->withJson(
            $userComments, 200);     

    }

}
