<?php

/**
 * @file plugins/generic/authorReplies/ListReviewedSubmissionHandler.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorRepliesPlugin
 * @ingroup plugins_generic_authorReplies
 * @brief Handle reader-facing router requests
 */

import('classes.handler.Handler');
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Query\Builder;

class ListFlaggedCommentsHandler extends Handler {

     public $_isBackendPage = true;

     public function __construct() {
		parent::__construct();

		$this->addRoleAssignment(
			[ROLE_ID_MANAGER],
			['index']
		);		
		
		// Add additional styles and scripts
		// Does not work in backend?
		// $request = Application::get()->getRequest();
		// $plugin = PluginRegistry::getPlugin('generic', 'CommentsPlugin');		
		// $cssUrl = $request->getBaseUrl() . '/' . $plugin->getPluginPath() . '/css/comments.css';
		// $templateMgr = TemplateManager::getManager($request);
		// $templateMgr->addStyleSheet('comments', $cssUrl);
  
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
		$plugin = PluginRegistry::getPlugin('generic', 'CommentsPlugin');		
		
		$templateMgr = TemplateManager::getManager();
		$templateMgr->assign([
			'pageTitle' => __('plugins.generic.comments.listFlaggedComments'),
		]);
	
		// HookRegistry::call('ListFlaggedCommentsHandler::display', [$templateMgr, $plugin->getTemplateResource('listFlaggedComments.tpl'), Null]);

		$flaggedComments = $this->getFlaggedComments();

		$templateMgr->assign([
			'items' => $flaggedComments,
        ]);

		return $templateMgr->display($plugin->getTemplateResource('listFlaggedComments.tpl'));
	}  

	public function getFlaggedComments()
	{
		$request = PKPApplication::get()->getRequest();
		$context = $request->getContext();

		$userCommentDao = DAORegistry::getDAO('UserCommentDAO');
        $userDao = DAORegistry::getDAO('UserDAO'); 	
        $queryResults = $userCommentDao->getFlagged($context->getId());
		// $userComments = $queryResults->toArray();

        $userComments = [];

        while ($userComment = $queryResults->next()) {  
            $user = $userDao->getById($userComment->getUserId());
            $userComments[] = [
            'id' => $userComment->getId(),
            'submissionId' => $userComment->getSubmissionId(),
            'foreignCommentId' => $userComment->getForeignCommentId(),
            'userName' => $user->getFullName(),
			'userEmail' => $user->getEmail(),
            'commentDate' =>$userComment->getDateCreated(),
            'commentText' => $userComment->getCommentText(),
            'flaggedDate' => $userComment->getDateFlagged(),
            'visible' => $userComment->getVisible(),
            ];
        };

		return $userComments;
		// var_dump("getFlaggedComments: " . json_encode($userComments));
	}
}  