<?php

/**
 * @file plugins/generic/comments/FlaggedCommentsHandler.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CommentsPlugin
 * @ingroup plugins_generic_comments
 * @brief List flagged comments
 */

import('classes.handler.Handler');
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Query\Builder;

class flaggedCommentsHandler extends Handler {

     public $_isBackendPage = true;

     public function __construct() {
		parent::__construct();

		$this->addRoleAssignment(
			[ROLE_ID_MANAGER],
			['index','edit']
		);		
	}
    
	/**
	 * @copydoc PKPHandler::authorize()
	 */    
    public function authorize($request, &$args, $roleAssignments)
    {
        import('lib.pkp.classes.security.authorization.PolicySet');
        $rolePolicy = new PolicySet(COMBINING_PERMIT_OVERRIDES);

        import('lib.pkp.classes.security.authorization.RoleBasedHandlerOperationPolicy');
        foreach ($roleAssignments as $role => $operations) {
            $rolePolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, $role, $operations));
        }
        $this->addPolicy($rolePolicy);

        return parent::authorize($request, $args, $roleAssignments);
    }    

    /**
	 * @copydoc GenericPlugin::index()
	 */
	public function index($args, $request) 
    {
		$this->setupTemplate($request);
		$plugin = PluginRegistry::getPlugin('generic', 'UserCommentsPlugin');		
		$templateMgr = TemplateManager::getManager();

		switch (array_pop($args)) {
			case 'edit':
				$this->edit($args, $request);
				break;
			default:
				$templateMgr->assign([
					'pageTitle' => __('plugins.generic.userComments.listFlaggedComments'),
				]);
				$flaggedComments = $this->getFlaggedComments();		
				$templateMgr->assign([
					'items' => $flaggedComments,
				]);
				return $templateMgr->display($plugin->getTemplateResource('listFlaggedComments.tpl'));				
				break;
		}
	}  

	public function getFlaggedComments()
	{
		$request = PKPApplication::get()->getRequest();
		$context = $request->getContext();

		$userCommentDao = DAORegistry::getDAO('UserCommentDAO');
        $userDao = DAORegistry::getDAO('UserDAO'); 	
        $queryResults = $userCommentDao->getFlagged($context->getId());

        $userComments = [];

        while ($userComment = $queryResults->next()) {  
            $user = $userDao->getById($userComment->getUserId());
            $userComments[] = [
            'id' => $userComment->getId(),
            'submissionId' => $userComment->getSubmissionId(),
			'publicationId' => $userComment->getPublicationId(),			
			'publicationVersion' => $userComment->getPublicationVersion(),						
            'foreignCommentId' => $userComment->getForeignCommentId(),
            'userName' => $user->getFullName(),
			'userEmail' => $user->getEmail(),
            'commentDate' =>$userComment->getDateCreated(),
            'commentText' => $userComment->getCommentText(),
            'flaggedDate' => $userComment->getDateFlagged(),
            'visible' => $userComment->getVisible(),
			'commentUrl' => $request->getRouter()->url($request, null, 'FlaggedComments', 'edit', $userComment->getId()),
            ];
        };

		return $userComments;
	}

	public function getComment($commentId)
	{
		$request = PKPApplication::get()->getRequest();
		$context = $request->getContext();

		$userCommentDao = DAORegistry::getDAO('UserCommentDAO');
        $userComment = $userCommentDao->getById($commentId);

		return $userComment;
	}

	public function edit($args, $request) {
		$this->setupTemplate($request);
		$context = $request->getContext();
		$plugin = PluginRegistry::getPlugin('generic', 'UserCommentsPlugin');
		$templateMgr = TemplateManager::getManager();

		// Get the userComment entity
		$commentId = array_pop($args);
		$userComment = $this->getComment($commentId); //returns a userComment object

		// Get the author of the comment and some data about the preprint
		$userDao = DAORegistry::getDAO('UserDAO'); 	
		$user = $userDao->getById($userComment->getUserId());

		// Get the user who flagged the comment
		$flaggedByUser = $userDao->getById($userComment->getFlaggedBy());

		// The list of flagged comments URL
		$commentsListUrl = $request->getRouter()->url($request, null, 'FlaggedComments');		

		// Create an instance of the comment form
		$plugin->import('UserCommentForm');
		$props = array(
			'$commentsListUrl' => $commentsListUrl,
			'$commentId' => $commentId,
            '$submissionId' => $userComment->getSubmissionId(),
			'$publicationId' => $userComment->getPublicationId(),
            '$foreignCommentId' => $userComment->getForeignCommentId(),
            '$userName' => $user->getFullName(),
			'$userEmail' => $user->getEmail(),
            '$commentDate' =>$userComment->getDateCreated(),
            '$commentText' => $userComment->getCommentText(),
            '$flaggedDate' => $userComment->getDateFlagged(),
			'$flagged' => $userComment->getFlagged(),
            '$visible' => $userComment->getVisible()
		);

		// The URL where the form will be submitted		
		$dispatcher = $request->getDispatcher();
		$apiUrl = $dispatcher->url($request, ROUTE_API, $context->getPath(), 'userComments/edit');

		$form = new UserCommentForm($apiUrl, $request, $props); // the parameters for the __construct function are variable
		//  Compile all of the required props and pass them to the template’s component state
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign([
			'plugin' => $plugin,
			'pageTitle' => __('plugins.generic.userComments.editFlaggedComments'),
			'commentListUrl' => $commentsListUrl,
			'commentText' => $userComment->getCommentText(),	
			'commentId' => $commentId,
			'publicationId' => $userComment->getPublicationId(),			
            'submissionId' => $userComment->getSubmissionId(),
			'submissionUrl' => $request->getRouter()->url($request, null, 'preprint', 'view', $userComment->getSubmissionId()),
            'foreignCommentId' => $userComment->getForeignCommentId(),
            'userName' => $user->getFullName(),
			'userEmail' => $user->getEmail(),
            'commentDate' =>$userComment->getDateCreated(),
            'commentText' => $userComment->getCommentText(),
            'flaggedDate' => $userComment->getDateFlagged(),
			'flagged' => $userComment->getFlagged(),
			'flaggedByUser' => $flaggedByUser->getFullName(),			
		]);
		$templateMgr->setState([
			'components' => [
				FORM_USER_COMMENT => $form->getConfig(),
			],
		]);
		return $templateMgr->display($plugin->getTemplateResource('userCommentForm.tpl'));				

	}	
		
}  