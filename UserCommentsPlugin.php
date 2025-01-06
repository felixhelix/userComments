<?php

/**
 * @file plugins/generic/userComments/UserCommentsPlugin.inc.php
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

namespace APP\plugins\generic\userComments;

use Illuminate\Database\Migrations\Migration;

use APP\core\Application;
use PKP\core\JSONMessage;
use PKP\core\PKPBaseController;
use APP\template\TemplateManager;
use APP\facades\Repo;
use PKP\db\DAORegistry;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;

use APP\plugins\generic\userComments\pages\FlaggedCommentsHandler;

use PKP\handler\APIHandler;
use APP\plugins\generic\userComments\api\v1\submissions\PKPOverriddenSubmissionController;
use Illuminate\Http\Request as IlluminateRequest;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use PKP\security\Role;

use APP\plugins\generic\userComments\CommentsSchemaMigration;
use APP\plugins\generic\userComments\classes\UserCommentDAO;
use APP\plugins\generic\userComments\classes\Settings\Actions;
use APP\plugins\generic\userComments\classes\Settings\Manage;
use APP\plugins\generic\userComments\api\v1\userComments\UserCommentsHandler;


class UserCommentsPlugin extends GenericPlugin {

	/**
	 * @copydoc Plugin::register()
	 */
	public function register($category, $path, $mainContextId = NULL) {

		$success = parent::register($category, $path, $mainContextId);
		
		if ($success && $this->getEnabled($mainContextId)) {	

			// Creata a DAO for user comments
			$UserCommentDao = new UserCommentDAO();
			DAORegistry::registerDAO('UserCommentDAO', $UserCommentDao);

			// Use a hook to insert a template on the details page
			Hook::add('Templates::Preprint::Main', [$this, 'addCommentBlock'], Hook::SEQUENCE_LAST);	

			// Add the API handler
			Hook::add('Dispatcher::dispatch', array($this, 'setupUserCommentsHandler'), Hook::SEQUENCE_LAST);	

			// add/inject new routes/endpoints to an existing collection/list of api end points
			// $this->addRoute(); // this is for 4.5 already

			// Use a hook to add a menu item in the backend
			Hook::add('TemplateManager::display', array($this, 'addMenuItem'), Hook::SEQUENCE_LAST);

			Hook::add('Template::Settings::website', array($this, 'addWebsiteSettingsTab'), Hook::SEQUENCE_LAST);

            Hook::add('LoadHandler', $this->setPageHandler(...));

            // This allows themes to override the plugins templates
            $this->_registerTemplateResource();

            // Add the custom style sheet and js
            $request = Application::get()->getRequest();
            $templateMgr = TemplateManager::getManager($request);
            $jsUrl = $request->getBaseUrl() . '/' . $this->getPluginPath() . '/js/comments.js';
            $cssUrl = $request->getBaseUrl() . '/' . $this->getPluginPath() . '/css/comments.css';
            $templateMgr->addJavaScript('vue', 'https://unpkg.com/vue@3/dist/vue.global.js');
            $templateMgr->addJavaScript('comments', $jsUrl);            
            $templateMgr->addStyleSheet('comments', $cssUrl);                
			
		}

		return $success;
	}


    public function getInstallMigration(): Migration
    {
        return new UserCommentsSchemaMigration();
    }

    // /**
    //  * @copydoc Plugin::updateSchema()
    //  */
    // public function updateSchema($hookName, $args)
    // {
    //     $installer = $args[0];
    //     $result = &$args[1];
    //     $migration = new UserCommentsSchemaMigration($installer, $this);
    //     try {
    //         $migration->up();
    //     } catch (Exception $e) {
    //         $installer->setError(Installer::INSTALLER_ERROR_DB, __('installer.installMigrationError', ['class' => get_class($migration), 'message' => $e->getMessage()]));
    //         $result = false;
    //     }
    // }	

	/**
	 * @copydoc Plugin::getName()
	 */
	public function getName() {
		return 'UserCommentsPlugin';
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	public function getDisplayName() {
		return __('plugins.generic.userComments.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	public function getDescription() {
		return __('plugins.generic.userComments.description');
	}

    public function addCommentBlock(string $hookName, array $args): bool {
		$request = Application::get()->getRequest();
		$context = $request->getContext();

		$user = $request->getUser();
        $smarty = & $args[1];
		$publication = $smarty->getTemplateVars('currentPublication');
        $output = & $args[2];	
		
		// Insert the comment template
		$smarty->assign([
			'baseURL' => $request->getBaseURL(),
			// 'apiURL' => $request->getDispatcher()->url($request, ROUTE_API, $context->getData('urlPath'), 'submissions/usercomments/'),
            'apiURL' => $request->getDispatcher()->url($request, ROUTE_API, $context->getData('urlPath'), 'userComments/'),
			'csrfToken' => $request->getSession()->getCSRFToken(),
			'apiKey' => $this->getSetting($context->getId(), 'apiKey'),
			'submissionId' => $publication->getData('submissionId'), 
			'version' =>  $publication->getData('version'),
			'foreignCommentId' => 1,
			'user' => $user,
			'userId' => $user ? $user->getId() : null,
		]);
        $output .= $smarty->fetch($this->getTemplateResource('commentBlock.tpl'));
        return false;
    }	

	/**
     * Add/override new api endpoints to existing list of api endpoints
     */
    public function addRoute(): void
    {
        Hook::add('APIHandler::endpoints::submissions', function(string $hookName, PKPBaseController &$apiController, APIHandler $apiHandler): bool {
            
            // This allow to add a route on fly without defining a api controller
            // Through this allow quick add/modify routes, it's better to use
            // controller based appraoch which is more structured and understandable
            $apiHandler->addRoute(
                'GET',
                'usercomments/onfly',
                function (IlluminateRequest $request): JsonResponse {
                    return response()->json([
                        'message' => 'userComments added successfully on fly',
                    ], Response::HTTP_OK);
                },
                'test.onfly',
                [
                    Role::ROLE_ID_SITE_ADMIN,
                    Role::ROLE_ID_MANAGER,
                    Role::ROLE_ID_SUB_EDITOR,
                ]
            );
            
            // This allow to update the api controller directly with an overrided controller 
            // that extends a core controller where one or more routes can be added or 
            // multiple existing routes can be modified
            $apiController = new PKPOverriddenSubmissionController();
            
            return false;
        });
    }

    public function setupUserCommentsHandler($hookName, $params)
    {
		$request = $params[0]; // APP\\core\\Request
        $router = $request->getRouter();

        if (!($router instanceof \APIRouter)) {
            return;
        }

        if (str_contains($request->getRequestPath(), 'api/v1/userComments')) {
            $handler = new UserCommentsHandler();
        }

        if (!isset($handler)) {
            return;
        }
		
        $router->setHandler($handler);	
        $handler->getApp()->run();	
        exit;
    }	
    /**
     * Add a settings action to the plugin's entry in the plugins list.
     *
     * @param Request $request
     * @param array $actionArgs
     */
    public function getActions($request, $actionArgs): array
    {
        $actions = new Actions($this);
        return $actions->execute($request, $actionArgs, parent::getActions($request, $actionArgs));
    }

    /**
     * Load a form when the `settings` button is clicked and
     * save the form when the user saves it.
     *
     * @param array $args
     * @param Request $request
     */
    public function manage($args, $request): JSONMessage
    {
        $manage = new Manage($this);
        return $manage->execute($args, $request);
    }

	public function addMenuItem($hookName, $params)
    {
		$request = Application::get()->getRequest();
		$context = $request->getContext();
		$user = $request->getUser();
		$router = $request->getRouter();
        $templateMgr = $params[0];
        $template = $params[1];
		$managerGroupId = 3; // This should not be hard coded

        if (!($template == 'management/website.tpl')) {
            return false;
        }

		// user has to be in moderator group	
		if(!Repo::userGroup()->userInGroup($user->getId(), $managerGroupId)) {
			return false;
		}

		$menu = $templateMgr->getState('menu');
		$menu['flaggedComments'] = [
			'name' => 'Flagged Comments',
			'url' => $router->url($request, $context->getData('urlPath'), 'flaggedComments'),
			'isCurrent' => $router->getRequestedPage($request) === 'flaggedComments',
		];
		$templateMgr->setState([
			'menu' => $menu,
		]);		

		return False;
	}	

	public function addWebsiteSettingsTab($hookName, $params) {
		// alternatively show the link on the admin page
		echo('<tab id="flaggedUserComments" label="Flagged Comments">Flagged Comments</tab>');
		return false;
	}	

    public function setPageHandler(string $hookName, array $params): bool
    {
        $page = & $params[0];
        $handler = & $params[3];
        if ($this->getEnabled() && $page === 'flaggedComments') {
            $handler = new FlaggedCommentsHandler($this);
            return true;
        }
        return false;
    }	

	// public function setPageHandler(string $hookName, array $params) {
	// 	$page = $params[0];	
	// 	switch ($page) {
	// 		case 'FlaggedComments':
	// 			$this->import('FlaggedCommentsHandler');
	// 			define('HANDLER_CLASS', 'flaggedCommentsHandler');
	// 			return true;			
	// 	}
	// 	return false;
	// }	

}

