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
		
		// Add additional styles and scripts
		// Does not work in backend?
		// $request = Application::get()->getRequest();
		// $plugin = PluginRegistry::getPlugin('generic', 'CommentsPlugin');		
		// $cssUrl = $request->getBaseUrl() . '/' . $plugin->getPluginPath() . '/css/comments.css';
		// $jsUrl = $request->getBaseUrl() . '/' . $plugin->getPluginPath() . '/js/comments.js';
		// $templateMgr = TemplateManager::getManager($request);
		// $templateMgr->addJavaScript('vue', 'https://unpkg.com/vue@3/dist/vue.global.js');
		// $templateMgr->addJavaScript('comments', $jsUrl);
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

		switch (array_pop($args)) {
			case 'edit':
				$this->edit($args, $request);
				break;
			default:
				$templateMgr->assign([
					'pageTitle' => __('plugins.generic.comments.listFlaggedComments'),
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
		// $userComments = $queryResults->toArray();

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
		// var_dump("getFlaggedComments: " . json_encode($userComments));
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
		$plugin = PluginRegistry::getPlugin('generic', 'CommentsPlugin');
		$templateMgr = TemplateManager::getManager();

		// Get the author of the comment and some data about the preprint
		$commentId = array_pop($args);
		$userComment = $this->getComment($commentId); //returns a userComment object
		$userDao = DAORegistry::getDAO('UserDAO'); 	
		$user = $userDao->getById($userComment->getUserId());

		// The list of flagged comments URL
		$commentsListUrl = $request->getRouter()->url($request, null, 'FlaggedComments');			

		// Create an instance of the comment form
		$plugin->import('UserCommentForm');
		$props = array(
			'$commentsListUrl' => $commentsListUrl,
			'$commentId' => $commentId,
            '$submissionId' => $userComment->getSubmissionId(),
            '$foreignCommentId' => $userComment->getForeignCommentId(),
            '$userName' => $user->getFullName(),
			'$userEmail' => $user->getEmail(),
            '$commentDate' =>$userComment->getDateCreated(),
            '$commentText' => $userComment->getCommentText(),
            '$flaggedDate' => $userComment->getDateFlagged(),
            '$visible' => $userComment->getVisible()
		);

		// The URL where the form will be submitted		
		$dispatcher = $request->getDispatcher();
		$apiUrl = $dispatcher->url($request, ROUTE_API, $context->getPath(), 'userComments/update');

		$form = new UserCommentForm($apiUrl, $request, $props); // the parameters for the __construct function are variable
		//  Compile all of the required props and pass them to the template’s component state
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign([
			'pageTitle' => __('plugins.generic.comments.editFlaggedComments'),
			'commentList_url' => $commentsListUrl]);
		$templateMgr->setState([
			'components' => [
				FORM_USER_COMMENT => $form->getConfig(),
			],
		]);
		error_log("Template: " . $plugin->getTemplateResource('userCommentForm.tpl'));
		return $templateMgr->display($plugin->getTemplateResource('userCommentForm.tpl'));				

	}	



	/**
	 * Extend the {url ...} for smarty to support this plugin.
	 */
	function smartyPluginUrl($params, $smarty) {
		$plugin = PluginRegistry::getPlugin('generic', 'AuthorRepliesPlugin');		
		$path = array('plugin', $plugin->getName());
		if (is_array($params['path'])) {
			$params['path'] = array_merge($path, $params['path']);
		} elseif (!empty($params['path'])) {
			$params['path'] = array_merge($path, array($params['path']));
		} else {
			$params['path'] = $path;
		}

		if (!empty($params['id'])) {
			$params['path'] = array_merge($params['path'], array($params['id']));
			unset($params['id']);
		}
		return $smarty->smartyUrl($params, $smarty);
	}		
}  