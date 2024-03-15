<?php

/**
 * @file plugins/generic/authorReplies/ReviewCommentFormHandler.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class commentsPlugin
 * @ingroup plugins_generic_comments
 * @brief Handle reader-facing router requests
 */

 import('classes.handler.Handler');
 

 class UserCommentFormHandler extends Handler {

     public $_isBackendPage = true;

     public function __construct() {
		parent::__construct();

		$this->addRoleAssignment(
			[ROLE_ID_MANAGER],
			['edit']
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

	public function edit($args, $request) {
		$this->setupTemplate($request);
		$context = $request->getContext();
		$plugin = PluginRegistry::getPlugin('generic', 'CommentsPlugin');
		$templateMgr = TemplateManager::getManager();
		$templateMgr->registerPlugin('function', 'plugin_url', array($this, 'smartyPluginUrl'));

		// Get the author of the comment and some data about the preprint
		$commentId = array_pop($args);

		// The list of flagged comments URL
		$commentsListUrl = $request->getRouter()->url($request, null, 'flaggedComments');			

		// Create an instance of the comment form
		$plugin->import('UserCommentForm');
		$props = array(
			'commentId' => $commentId,
			'userCommentStr' => $userCommentStr,
		);

		// The URL where the form will be submitted		
		$dispatcher = $request->getDispatcher();
		$apiUrl = $dispatcher->url($request, ROUTE_API, $context->getPath(), 'FlaggedComments/update');

		$form = new UserCommentForm($apiUrl, $request, $props); // the parameters for the __construct function are variable
		//  Compile all of the required props and pass them to the template’s component state
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign([
			'commentList_url' => $reviewListUrl]);
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