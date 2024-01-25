<?php

/**
 * @file plugins/generic/comments/CommentsPlugin.inc.php
 *
 * Copyright (c) 2013-2023 Simon Fraser University
 * Copyright (c) 2003-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class CommentsPlugin
 * @ingroup plugins_generic_comments
 *
 * @brief Comments plugin
 */


import('lib.pkp.classes.plugins.GenericPlugin');


class commentsPlugin extends GenericPlugin {

	/**
	 * @copydoc Plugin::register()
	 */
	public function register($category, $path, $mainContextId = NULL) {

		$success = parent::register($category, $path, $mainContextId);
		
		if ($success && $this->getEnabled($mainContextId)) {	

			// Creata a DAO for user comments
			import('plugins.generic.comments.classes.UserCommentDAO');
			$this->UserCommentDao = new UserCommentDAO();
			DAORegistry::registerDAO('UserCommentDAO', $this->UserCommentDao);

			// Add additional styles and scripts
			$request = Application::get()->getRequest();
      		$jsUrl = $request->getBaseUrl() . '/' . $this->getPluginPath() . '/js/comments.js';
			$cssUrl = $request->getBaseUrl() . '/' . $this->getPluginPath() . '/css/comments.css';
      		$templateMgr = TemplateManager::getManager($request);
			$templateMgr->addJavaScript('vue', 'https://unpkg.com/vue@3/dist/vue.global.js');
			$templateMgr->addJavaScript('comments', $jsUrl);
			$templateMgr->addStyleSheet('comments', $cssUrl);
			
			// Use a hook to insert a template on the details page
			// HookRegistry::register('Templates::Preprint::Main', [$this, 'addCommentBlock']);	
			HookRegistry::register('Templates::Preprint::Main', [$this, 'addCommentBlock']);	

			// Add the API handler
			HookRegistry::register('Dispatcher::dispatch', array($this, 'setupUserCommentsHandler'));	

			// Use a hook to add a menu item in the backend
			HookRegistry::register('TemplateManager::display', array($this, 'addMenuItem'));
			// HookRegistry::register('ListReviewedSubmissionHandler::display', array($this, 'addMenuItem'));

			// Add the custom pages
			HookRegistry::register('LoadHandler', array($this, 'setPageHandler'));

			// Add link to flagged comments list on admin page
			HookRegistry::register('Templates::Admin::Index::AdminFunctions', array($this, 'addAdminItem'));

			// Install database tables
			// $migration = $this->getInstallMigration();
			// $migration->up();
		}

		return $success;
	}

	/**
	 * @copydoc Plugin::getInstallMigration()
	 */
	function getInstallMigration() {
		$this->import('CommentsSchemaMigration');
		return new CommentsSchemaMigration();
	}


	/**
	 * @copydoc Plugin::getName()
	 */
	public function getName() {
		return 'CommentsPlugin';
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	public function getDisplayName() {
		return __('plugins.generic.comments.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	public function getDescription() {
		return __('plugins.generic.comments.description');
	}

    public function addCommentBlock(string $hookName, array $args): bool {
		$request = Application::get()->getRequest();
		$user = $request->getUser();
        $smarty = & $args[1];
		$publication = $smarty->getTemplateVars('currentPublication');
        $output = & $args[2];	
		// Insert the comment template
		$smarty->assign([
			'csrfToken' => $request->getSession()->getCSRFToken(),
			'apiKey' => $this->getSetting($request->getContext()->getId(), 'apiKey'),
			'submissionId' => $publication->getData('submissionId'), 
			'version' =>  $publication->getData('version'),
			'foreignCommentId' => 1,
			'user' => $user,
		]);
        $output .= $smarty->fetch($this->getTemplateResource('commentBlock.tpl'));
        return false;
    }	

    public function setupUserCommentsHandler($hookName, $request)
    {
        $router = $request->getRouter();
        if (!($router instanceof \APIRouter)) {
            return;
        }

        if (str_contains($request->getRequestPath(), 'api/v1/userComments')) {
            $this->import('api.v1.userComments.UserCommentsHandler');
            $handler = new UserCommentsHandler();
        }

        if (!isset($handler)) {
            return;
        }
		
        $router->setHandler($handler);	
        $handler->getApp()->run();	
        exit;
    }	

	public function getActions($request, $actionArgs) {

		// Get the existing actions
		$actions = parent::getActions($request, $actionArgs);
		if (!$this->getEnabled()) {
			return $actions;
		}
	
		// Create a LinkAction that will call the plugin's
		// `manage` method with the `settings` verb.
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		$linkAction = new LinkAction(
			'settings',
			new AjaxModal(
				$router->url(
					$request,
					null,
					null,
					'manage',
					null,
					array(
						'verb' => 'settings',
						'plugin' => $this->getName(),
						'category' => 'generic'
					)
				),
				$this->getDisplayName()
			),
			__('manager.plugins.settings'),
			null
		);
	
		// Add the LinkAction to the existing actions.
		// Make it the first action to be consistent with
		// other plugins.
		array_unshift($actions, $linkAction);

		return $actions;
	}
	
	public function manage($args, $request) {
		switch ($request->getUserVar('verb')) {
			case 'settings':

				// Load the custom form
				$this->import('CommentsPluginSettingsForm');
				$form = new CommentsPluginSettingsForm($this);

				// Fetch the form the first time it loads, before
				// the user has tried to save it
				if (!$request->getUserVar('save')) {
				$form->initData();
						return new JSONMessage(true, $form->fetch($request));
				}

				// Validate and execute the form
				$form->readInputData();
				if ($form->validate()) {
				$form->execute();
				return new JSONMessage(true);
				}
			}
		return parent::manage($args, $request);
	}

	public function addMenuItem($hookName, $params)
    {
		$request = PKPApplication::get()->getRequest();
		$context = $request->getContext();
		$user = $request->getUser();
		$router = $request->getRouter();
        $templateMgr = $params[0];
        $template = $params[1];
		$managerGroupId = 3; // This should not be hard coded

        if (!($template == 'dashboard/index.tpl')) {
            return false;
        }

		// user has to be in moderator group
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		error_log($userGroupDao->userInGroup($user->getId(), $managerGroupId));
		if(!$userGroupDao->userInGroup($user->getId(), $managerGroupId)) {
			return false;
		}

		$menu = & $templateMgr->getState('menu');
		$menu['flaggedComments'] = [
			'name' => 'Flagged Comments',
			'url' => $router->url($request, 'socios', 'FlaggedComments'),
			'isCurrent' => $router->getRequestedPage($request) === 'FlaggedComments',
		];
		$templateMgr->setState([
			'menu' => $menu,
		]);		

		return False;
	}	

	public function addAdminItem($hookName, $params) {
		// alternatively show the link on the admin page
		error_log("addAdminItem");
		return "<li>Flagged Comments</li>";
	}	

	public function setPageHandler($hookName, $params) {
		$page = $params[0];	
		switch ($page) {
			case 'FlaggedComments':
				$this->import('FlaggedCommentsHandler');
				define('HANDLER_CLASS', 'flaggedCommentsHandler');
				return true;			
		}
		return false;
	}	

}

