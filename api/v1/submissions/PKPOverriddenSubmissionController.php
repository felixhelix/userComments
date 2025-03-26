<?php

/**
 * @file plugins/generic/userComments/api/v1/submissions/PKPOverriddenSubmissionController.php
 *
 * Copyright (c) 2023 Simon Fraser University
 * Copyright (c) 2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PKPOverriddenSubmissionController
 *
 * @ingroup plugins_generic_userComments_api_v1_submissions
 *
 * @brief Override existing submission api to add user comments
 *
 */

namespace APP\plugins\generic\userComments\api\v1\submissions;

// use APP\facades\Repo;
use APP\plugins\generic\userComments\classes\UserCommentDAO;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use PKP\API\v1\submissions\PKPSubmissionController;
use PKP\security\Role;
use PKP\db\DAORegistry;

use PKP\core\Core;
use PKP\core\PKPApplication;
use APP\plugins\generic\userComments\classes\userComment;
use APP\plugins\generic\userComments\classes\facades\Repo;
use PKP\security\Validation;
use PKP\log\event\EventLogEntry;
use Illuminate\Support\Facades\Mail;
use PKP\mail\Mailable;


class PKPOverriddenSubmissionController extends PKPSubmissionController
{
    /**
     * @copydoc \PKP\core\PKPBaseController::getGroupRoutes()
     */
    public function getGroupRoutes(): void
    {
        parent::getGroupRoutes();

        Route::middleware([
            self::roleAuthorizer([
                Role::ROLE_ID_READER,
                Role::ROLE_ID_REVIEWER,
                Role::ROLE_ID_AUTHOR,
                Role::ROLE_ID_MANAGER,                
            ]),
        ])->group(function () {
            Route::get('usercomments/getbypublication/{publicationId}', $this->getCommentsByPublication(...))
            ->name('submission.usercomments.getMany')
            ->whereNumber('publicationId');
            Route::post('usercomments/add', $this->submitComment(...))
            ->name('submission.usercomments.add'); 
            Route::post('usercomments/flag', $this->flagComment(...))
            ->name('submission.usercomments.flag');  
            Route::post('usercomments/edit', $this->editComment(...))
            ->name('submission.usercomments.edit');                                 
        });                

    }
    
    /**
     * A simple test api endpoint which will be added to the list of [users] api endpoint as
     * http://BASE_URL/index.php/CONTEXT_PATH/api/v1/submissions/usercomments
     */
    public function addNewRoute(Request $illuminateRequest): JsonResponse
    {
        $publication = Repo::publication()->get((int) $illuminateRequest->route('publicationId'));        
        return response()->json([
            'message' => $illuminateRequest->route('publicationId')
        ], Response::HTTP_OK);
    }

    public function getCommentsByPublication(Request $illuminateRequest): JsonResponse
    {
        $publicationId = (int) $illuminateRequest->route('publicationId');

        $queryResults = Repo::userComment()
            ->getCollector()
            ->filterByPublicationId($publicationId)
            ->getMany();

        if (empty($queryResults)) {
            $userComments = [];
        }
        else { 
            $userComments = Repo::userComment()
            ->getSchemaMap()
            ->mapMany($queryResults->values());
        };

        return response()->json(
            $userComments, Response::HTTP_OK);
    }      

    public function submitComment(Request $illuminateRequest): JsonResponse
    {
        $request = $this->getRequest();
        $context = $request->getContext();        
        $currentUser = $request->getUser();
        // $locale = Locale::getLocale();

        // $params = $this->convertStringsToSchema(PKPSchemaService::SCHEMA_DECISION, $illuminateRequest->input());

        $requestParams = $illuminateRequest->input();
        $publicationId = $requestParams['publicationId'];
        $foreignCommentId = array_key_exists('foreignCommentId', $requestParams) ? $requestParams['foreignCommentId'] : null;     
        $submissionId = $requestParams['submissionId'];  
        $commentText = $requestParams['commentText'];

        // Create the data object
        $userComment = Repo::userComment()->newDataObject();
        $userComment->setDateCreated(Core::getCurrentDate());
        $userComment->setContextId($context->getId());
        $userComment->setUserId($currentUser->getId());
        $userComment->setPublicationId($publicationId);
        $userComment->setSubmissionId($submissionId);
        $userComment->setCommentText($commentText);
        if($foreignCommentId){ $userComment->setForeignCommentId($foreignCommentId); };                

        // Insert the data object
        $commentId = Repo::userComment()->add($userComment);

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
        return response()->json([
            'id' => $commentId,
            'comment' => $userCommentText,
            'userName' => $currentUser->getFullName(),
            'userOrcid' => $currentUser->getData('orcid'),
            'userAffiliation' => $currentUser->getLocalizedAffiliation(),
            'commentDate' => $userComment->getDateCreated(),
        ], Response::HTTP_OK);
    }
    
    public function flagComment(Request $illuminateRequest): JsonResponse
    {
        $request = $this->getRequest();
        $dispatcher = $request->getDispatcher();        
        $context = $request->getContext();    
        $site = $request->getSite();

        $currentUser = $request->getUser();

        $requestParams = $illuminateRequest->input();

        $commentId = $requestParams['commentId'];
        $publicationId = $requestParams['publicationId'];
        $flagNote = $requestParams['flagNote'];        

        // Validate input
        if ( gettype($commentId) != 'integer') {
            return response()->json(
                ['error' => 'wrong type',
            ], Response::HTTP_BAD_REQUEST);            
        }
        if ( gettype($publicationId) != 'integer') {
            return response()->json(
                ['error' => 'wrong type',
            ], Response::HTTP_BAD_REQUEST);            
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
        $editUrl = $dispatcher->url($request, PKPApplication::ROUTE_PAGE, $context->getPath(), 'management', 'settings',['website#flaggedUserComments']);
        $subject = "Comment #$commentId has been flagged";
        $body = "Comment #$commentId has been flagged.\nThe flagnote is: '$flagNote'.\n<a href='$editUrl'>Log in</a> to edit the comment.";

        $mailable = new Mailable();
        $mailable
            ->from($site->getLocalizedContactEmail(), $site->getLocalizedContactName())
            ->to(array(['email' => $context->getContactEmail(), 'name' => $context->getContactName()]))
            ->cc($context->getData('contactEmail'), $context->getData('contactName'))
            ->subject($subject)
            ->body($body);

        Mail::send($mailable);        

        // Return updated entry
        return response()->json(
            ['id' => $commentId,
            'flagged' => true,
        ], Response::HTTP_OK);        
    }

    public function editComment(Request $illuminateRequest): JsonResponse
    {
        // User comments may not be deleted
        // This changes the visibility of the comment
        // and/or the flagging
        $request = $this->getRequest();
        $requestParams = $illuminateRequest->input();
        $userCommentId = $requestParams['userCommentId'];
        $publicationId = $requestParams['publicationId'];
        $visible = $requestParams['visible'];
        $flagged = $requestParams['flagged'];
        $messageKey = '';
        // error_log("setVisibility: " . $visible . " on " . $userCommentId);
        $currentUser = $request->getUser();
        // $locale = Locale::getLocale();

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
        $userComment->setFlagged($flagged == 'true' ? true : false);
        if ($flagged != 'true') {
            // if the comment is un-flagged, it has to be visible
            $userComment->setVisible(true);
            // In this cas the logged message relates to this event
            // $messageKey = COMMENT_UNFLAGGED;
            $msg = 'comment.event.unflag';
        } else {
            $userComment->setVisible($visible == 'true' ? true : false);
            // $messageKey = $visible == 'true' ? COMMENT_VISIBLE : COMMENT_HIDDEN;
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
        // CommentLog::logEvent($request, $userCommentId, $messageKey, $msg, $logDetails);

        $commentText = 'comment visibilty and/or flagging has been changed';
        return response()->json(
            ['id' => $userCommentId,
            'comment' => $msg,
        ], Response::HTTP_OK);        

    }


}