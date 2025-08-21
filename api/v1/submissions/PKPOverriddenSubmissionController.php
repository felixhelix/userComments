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

use PKP\core\Core;
use PKP\core\PKPApplication;
// use PKP\API\v1\submissions\PKPSubmissionController;
// use PKP\security\Role;
use PKP\security\Validation;
use APP\plugins\generic\userComments\classes\userComment;
use APP\plugins\generic\userComments\classes\facades\Repo;
use PKP\log\event\EventLogEntry;
use PKP\mail\Mailable;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
// use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;

class PKPOverriddenSubmissionController // extends PKPSubmissionController
{
    /**
     * @copydoc \PKP\core\PKPBaseController::getGroupRoutes()
     */
    // public function getGroupRoutes(): void
    // {
    //     parent::getGroupRoutes();

    //     Route::middleware([
    //         self::roleAuthorizer([
    //             Role::ROLE_ID_READER,
    //             Role::ROLE_ID_REVIEWER,
    //             Role::ROLE_ID_AUTHOR,
    //             Role::ROLE_ID_MANAGER,                
    //         ]),
    //     ])->group(function () {
    //         Route::get('usercomments/getbypublication/{publicationId}', $this->getCommentsByPublication(...));
    //         Route::get('usercomments/getComment/{commentId}', $this->getById(...));
    //         Route::post('usercomments/add', $this->submit(...));
    //         Route::post('usercomments/flag', $this->flag(...));
    //         Route::post('usercomments/update', $this->update(...));
    //     });                

    // }
    
    public function getById(Request $illuminateRequest): JsonResponse
    {
        $commentId = (int) $illuminateRequest->route('commentId');        
        $queryResult = Repo::userComment()
            ->get($commentId);

        if (empty($queryResult)) {
            return response()->json([
                'error' => __('api.404.resourceNotFound'),
            ], Response::HTTP_NOT_FOUND);
        }    
        else { 
            $userComment = Repo::userComment()
                ->getSchemaMap()
                ->map($queryResult);
            return response()->json(
                $userComment, Response::HTTP_OK);                    
        }
        
    }    

    static function getCommentsByPublication(Request $illuminateRequest): JsonResponse
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

    static function submit(Request $illuminateRequest): JsonResponse
    {
        $request = PKPApplication::get()->getRequest();
        // $request = $this->getRequest();
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
            'userId' => Validation::loggedInAs() ?? $currentUser->getId(),
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
            'comment' => $commentText,
            'userName' => $currentUser->getFullName(),
            'userOrcid' => $currentUser->getData('orcid'),
            'userAffiliation' => $currentUser->getLocalizedAffiliation(),
            'commentDate' => $userComment->getDateCreated(),
        ], Response::HTTP_OK);
    }
    
    static function flag(Request $illuminateRequest): JsonResponse
    {
        $request = PKPApplication::get()->getRequest();
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
            'userId' => Validation::loggedInAs() ?? $currentUser->getId(),
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

    public function update(Request $illuminateRequest): JsonResponse
    {
        $request = $this->getRequest();
        $context = $request->getContext();  
        $requestParams = $illuminateRequest->input();
        $currentUser = $request->getUser();

        // set the data      
        $commentId = $requestParams['commentId'];     
        $visible = $requestParams['visible'];        

        // update the entity
        $params = [
            'flagged' => $requestParams['flagged'],
            'visible' => $visible,
        ];        
        $userComment = Repo::userComment()->get($commentId, $context->getId());
        Repo::userComment()->update($userComment, $params);        

        // Log the event
        // There are only two options: 
        // if the entry is not visible, it must have been hidden, 
        // if it is visible (again) it must have been unflagged.
		$msg = 'comment' . $visible?' hidden: ':' unflagged: ' . $commentId; // either a locale key or literal string
        $eventLog = Repo::eventLog()->newDataObject([
            'assocType' => PKPApplication::ASSOC_TYPE_PUBLICATION,
            'assocId' => $userComment->getData('publicationId'),
            'eventType' => EventLogEntry::SUBMISSION_LOG_NOTE_POSTED,
            'userId' => Validation::loggedInAs() ?? $request->getUser()->getId(),
            'message' => $msg,
            'isTranslated' => false,
            'dateLogged' => Core::getCurrentDate()
        ]);
        Repo::eventLog()->add($eventLog);     

        return response()->json(
            ['commentId' => $userComment->getId(),
        ], Response::HTTP_OK); 
    }

}