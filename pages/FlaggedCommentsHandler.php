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

namespace APP\plugins\generic\userComments\pages;

use APP\plugins\generic\userComments\UserCommentForm;
use APP\facades\Repo;
use APP\handler\Handler;
use APP\template\TemplateManager;

use APP\plugins\generic\userComments\UserCommentsPlugin;

use PKP\core\PKPApplication;
use PKP\db\DAORegistry;
use PKP\plugins\PluginRegistry;
use PKP\security\Role;
use PKP\security\authorization\PolicySet;
use PKP\security\authorization\RoleBasedHandlerOperationPolicy;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Query\Builder;

class flaggedCommentsHandler extends Handler {

     public $_isBackendPage = true;	 
	 public UserCommentsPlugin $plugin;  

     public function __construct(UserCommentsPlugin $plugin) {

		parent::__construct();

		$this->plugin = $plugin;

		$this->addRoleAssignment(
			[Role::ROLE_ID_MANAGER],
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
        $rolePolicy = new PolicySet(COMBINING_PERMIT_OVERRIDES);
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
		$templateMgr = TemplateManager::getManager($request);

		switch (array_pop($args)) {
			case 'edit':
				$this->edit($args, $request);
				break;
			default:
				$flaggedComments = $this->getFlaggedComments();					
				$templateMgr->assign([
					'pageTitle' => __('plugins.generic.userComments.listFlaggedComments'),
					'items' => $flaggedComments,
				]);
				$templateMgr->setState([
					'items' => array(1,2,3,4,5),
					'isPublished' => true,
				]);				
				return $templateMgr->display($this->plugin->getTemplateResource('listFlaggedCommentsUI.tpl'));				
				break;
		}
	}  

	/**
	 * 	Return an array of all flagged comments
	 */
	public function getFlaggedComments()
	{
		$request = PKPApplication::get()->getRequest();
		$context = $request->getContext();

		$userCommentDao = DAORegistry::getDAO('UserCommentDAO');
        $queryResults = $userCommentDao->getFlagged($context->getId());

		$userComments = [];

        while ($userComment = $queryResults->next()) {  
            $user = Repo::user()->get($userComment->getUserId());
            $userComments[] = [
            'id' => $userComment->getId(),
            'submissionId' => $userComment->getSubmissionId(),
			'publicationId' => $userComment->getPublicationId(),			
            'foreignCommentId' => $userComment->getForeignCommentId(),
            'userName' => $user->getFullName(),
			'userEmail' => $user->getEmail(),
            'commentDate' =>$userComment->getDateCreated(),
            'commentText' => $userComment->getCommentText(),
            'flaggedDate' => $userComment->getDateFlagged(),
            'visible' => $userComment->getVisible(),
			'commentUrl' => $request->getRouter()->url($request, null, 'flaggedComments', 'edit', array($userComment->getId())),
            ];
        };

		return $userComments;
	}

	/**
	 * return a single comment entity by id
	 */
	public function getComment($commentId)
	{
		$request = PKPApplication::get()->getRequest();
		$context = $request->getContext();

		$userCommentDao = DAORegistry::getDAO('UserCommentDAO');
        $userComment = $userCommentDao->getById($commentId);

		return $userComment;
	}

	/**
	 * Display a form to edit flagged comments
	 */
	public function edit($args, $request) {
		$this->setupTemplate($request);
		$context = $request->getContext();
		$plugin = PluginRegistry::getPlugin('generic', 'UserCommentsPlugin');
		$templateMgr = TemplateManager::getManager($request);

		// Get the userComment entity
		$commentId = array_pop($args);
		$userComment = $this->getComment($commentId); //returns a userComment object

		// Get the author of the comment and some data about the preprint
		$user = Repo::user()->get($userComment->getUserId());

		// Get the user who flagged the comment
		$flaggedByUser = Repo::user()->get($userComment->getFlaggedBy());

		// The list of flagged comments URL
		$commentsListUrl = $request->getRouter()->url($request, null, 'management','settings','website', null, 'flaggedUserComments');		

		// Assign the values to be displayed on the edit page
		$templateMgr->assign([
			'plugin' => $plugin,
			'pageTitle' => __('plugins.generic.userComments.editFlaggedComments'),
			'commentListUrl' => $commentsListUrl,
			'commentText' => $userComment->getCommentText(),	
			'commentId' => $commentId,
			'publicationId' => $userComment->getPublicationId(),			
            'submissionId' => $userComment->getSubmissionId(),
			'submissionUrl' => $request->getRouter()->url($request, null, 'preprint', 'view', array($userComment->getSubmissionId())),
            'foreignCommentId' => $userComment->getForeignCommentId(),
            'userName' => $user->getFullName(),
			'userEmail' => $user->getEmail(),
            'commentDate' =>$userComment->getDateCreated(),
            'commentText' => $userComment->getCommentText(),
            'flaggedDate' => $userComment->getDateFlagged(),
			'flagged' => $userComment->getFlagged(),
			'flaggedByUser' => $flaggedByUser->getFullName(),	
			'flagNote' => $userComment->getFlagNote(),					
		]);

		// Create an instance of the comment form
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
			'$flagNote' => $userComment->getFlagNote(),				
            '$visible' => $userComment->getVisible()
		);
		// The URL where the form will be submitted		
		$dispatcher = $request->getDispatcher();
		// new for 3.5
		// $apiUrl = $dispatcher->url($request, ROUTE_API, $context->getPath(), 'submissions/usercomments/edit');
		$apiUrl = $dispatcher->url($request, ROUTE_API, $context->getPath(), 'userComments/edit');
		$form = new UserCommentForm($apiUrl, $request, $props); // the parameters for the __construct function are variable
		//  Compile all of the required props and pass them to the template’s component state
		$templateMgr->setState([
			'components' => [
				FORM_USER_COMMENT => $form->getConfig(),
			],
		]);

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