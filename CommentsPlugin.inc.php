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
      		$url = $request->getBaseUrl() . '/' . $this->getPluginPath() . '/js/comments.js';
      		$templateMgr = TemplateManager::getManager($request);
			$templateMgr->addJavaScript('vue', 'https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js');
			$templateMgr->addJavaScript('comments', $url);

			
			// Use a hook to insert a template on the details page
			HookRegistry::register('Templates::Preprint::Details', [$this, 'addCommentBlock']);	

			// Add the API handler
			HookRegistry::register('Dispatcher::dispatch', array($this, 'setupUserCommentsHandler'));	

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
		// Insert the comment template
		$request = Application::get()->getRequest();
        $smarty = & $args[1];
        $output = & $args[2];
		$smarty->assign([
			'csrfToken' => $request->getSession()->getCSRFToken(),
			'submissionId' => array_pop(explode('/', $request->getRequestPath())),
			'foreignCommentId' => 1,
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

}

