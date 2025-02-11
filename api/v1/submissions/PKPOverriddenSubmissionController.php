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

use APP\facades\Repo;
use APP\plugins\generic\userComments\classes\UserCommentDAO;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use PKP\API\v1\submissions\PKPSubmissionController;
use PKP\security\Role;
use PKP\db\DAORegistry;

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
            Route::get('usercomments/getbypublication/{publicationId}', $this->getByPublication(...))
            ->name('submission.usercomments.getMany')
            ->whereNumber('publicationId');
            Route::post('usercomments/add', $this->addComment(...))
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

    public function getByPublication(Request $illuminateRequest): JsonResponse
    {
		$userCommentDao = DAORegistry::getDAO('UserCommentDAO');
        // $userDao = DAORegistry::getDAO('UserDAO'); 	
        $queryResults = $userCommentDao->getByPublicationId($illuminateRequest->route('publicationId'));

        // $userComments = ['none yet :/'];

        while ($userComment = $queryResults->next()) {  
            // $user = $userDao->getById($userComment->getUserId());
            $user = Repo::user()->get((int) $userComment->getUserId());        
            $userComments[] = [
            'id' => $userComment->getId(),
            'publicationId' => $userComment->getPublicationId(),
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

        return response()->json(
            $userComments, Response::HTTP_OK);
    }      

    public function addComment(Request $illuminateRequest): JsonResponse
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

        // Get the DAO for user comments
        $UserCommentDao = DAORegistry::getDAO('UserCommentDAO');
            
        // Create the data object
        $newUserComment = $UserCommentDao->newDataObject(); 
        $newUserComment->setContextId($context.getId());
        $newUserComment->setSubmissionId($submissionId);        
        $newUserComment->setPublicationId($publicationId);
        $newUserComment->setUserId($currentUser->getId());
        $newUserComment->setForeignCommentId($foreignCommentId);        
        $newUserComment->setCommentText($commentText);

        // Insert the data object
        $commentId = $UserCommentDao->insertObject($newUserComment);

        // Log the event in the event log related to the submission
		$msg = 'comment.event.posted';
        // import('plugins.generic.userComments.classes.log.CommentLog');
        // import('plugins.generic.userComments.classes.log.CommentEventLogEntry'); // We need this for the ASSOC_TYPE and EVENT_TYPE constants
        $logDetails = array(
            'publicationId' => $publicationId,
            'commentId' => $commentId,
            'foreignCommentId' => $foreignCommentId,
            'userId' => $currentUser->getId(),            
        );
        // $request, $submission, $eventType, $messageKey, $params = array()
        // CommentLog::logEvent($request, $commentId, COMMENT_POSTED, $msg, $logDetails);

        // $userComment = $illuminateRequest->input();
        return response()->json(
            ['id' => 1,
            'comment' => $commentText,
        ], Response::HTTP_OK);
    }
    
    public function flagComment(Request $illuminateRequest): JsonResponse
    {
        $request = $this->getRequest();
        $currentUser = $request->getUser();
        // $locale = Locale::getLocale();

        $requestParams = $illuminateRequest->input();

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

        // Get the DAO for user comments
        $UserCommentDao = new UserCommentDAO();
        DAORegistry::registerDAO('UserCommentDAO', $UserCommentDao);

        // Get the data object
        $userComment = $UserCommentDao->getById($userCommentId);
            
        // Update the data object
        $userComment->setFlagged(true);
        $userComment->setDateFlagged(Now());
        $userComment->setFlaggedBy($currentUser->getId());
        $UserCommentDao->updateObject($userComment);        

        // Log the event
		// Flagging is logged in the event log and is related to the submission
		$msg = 'comment.event.flagged';
        // import('plugins.generic.userComments.classes.log.CommentLog');
        // import('plugins.generic.userComments.classes.log.CommentEventLogEntry'); // We need this for the ASSOC_TYPE and EVENT_TYPE constants
        $logDetails = array(
            'publicationId' => $publicationId,
            'commentId' => $userCommentId,
            'userId' => $currentUser->getId(),            
        );
        // $request, $submission, $eventType, $messageKey, $params = array()
        // CommentLog::logEvent($request, $userCommentId, COMMENT_FLAGGED, $msg, $logDetails);

        $commentText = 'comment has been flagged';

        return response()->json(
            ['id' => 1,
            'comment' => $commentText,
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