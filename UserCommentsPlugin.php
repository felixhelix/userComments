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

use Illuminate\Http\Request as IlluminateRequest;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;

use PKP\facades\Locale;
use PKP\security\Role;
use PKP\core\JSONMessage;
use PKP\core\PKPBaseController;
use PKP\core\PKPApplication;
use PKP\db\DAORegistry;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\handler\APIHandler;
use PKP\components\listPanels\ListPanel;

use APP\core\Application;
use APP\template\TemplateManager;
use APP\plugins\generic\userComments\classes\facades\Repo;

use APP\plugins\generic\userComments\pages\FlaggedCommentsHandler;
use APP\plugins\generic\userComments\UserCommentForm;
use APP\plugins\generic\userComments\CommentsSchemaMigration;
use APP\plugins\generic\userComments\classes\UserCommentDAO;
use APP\plugins\generic\userComments\classes\Settings\Actions;
use APP\plugins\generic\userComments\classes\Settings\Manage;
use APP\plugins\generic\userComments\api\v1\userComments\UserCommentsHandler;
use APP\plugins\generic\userComments\api\v1\submissions\PKPOverriddenSubmissionController;

define('FLAGGED_COMMENTS_LIST', 'commentslist');

class UserCommentsPlugin extends GenericPlugin {

	/**
	 * @copydoc Plugin::register()
	 */
	public function register($category, $path, $mainContextId = NULL) {

		$success = parent::register($category, $path, $mainContextId);
		
		if ($success && $this->getEnabled($mainContextId)) {	

            Hook::add('Schema::get::userComment', [$this, 'addUserCommentsSchema']);

			// Use a hook to insert a template on the details page
			Hook::add('Templates::Preprint::Main', [$this, 'addCommentBlock'], Hook::SEQUENCE_LAST);	

			// Add the API handler
			Hook::add('Dispatcher::dispatch', array($this, 'setupUserCommentsHandler'), Hook::SEQUENCE_LAST);	

			// add/inject new routes/endpoints to an existing collection/list of api end points
			// $this->addRoute(); // this is for 4.5 already

            // This allows themes to override the plugin templates
            $this->_registerTemplateResource();

            // Frontend            
            // Add the custom js and style sheet
            $request = Application::get()->getRequest();
            $templateMgr = TemplateManager::getManager($request);         
            $jsUrl = $request->getBaseUrl() . '/' . $this->getPluginPath() . '/js/comments.js';
            $cssUrl = $request->getBaseUrl() . '/' . $this->getPluginPath() . '/css/comments.css';
            $templateMgr->addJavaScript('vue', 'https://unpkg.com/vue@3/dist/vue.global.js');             
            $templateMgr->addJavaScript('comments', $jsUrl); 

            // Backend
			// Use a hook to add a menu item in the backend
            Hook::add('TemplateManager::display', array($this, 'addWebsiteSettingsTabData'));
			Hook::add('Template::Settings::website', array($this, 'addWebsiteSettingsTab'), Hook::SEQUENCE_LAST);    
            // Add the custom components        
            $jsListUrl = $request->getBaseUrl() . '/' . $this->getPluginPath() . '/public/build/userComments.iife.js';
            $templateMgr->addJavaScript(
                'userComments', 
                $jsListUrl,
                [
                    'inline' => false,
                    'contexts' => ['backend'],
                    'priority' => TemplateManager::STYLE_SEQUENCE_LAST
                ]
            );            
            $templateMgr->addStyleSheet('comments', $cssUrl);                
			
		}

		return $success;
	}


    public function getInstallMigration(): Migration
    {
        return new UserCommentsSchemaMigration();
    }

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

	/**
	 * @copydoc Plugin::addCommentBlock()
     * Adds a comment block to the publication details page
	 */    
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
			//'apiURL' => $request->getDispatcher()->url($request, ROUTE_API, $context->getData('urlPath'), 'submissions/usercomments/'), // this is for 4.5 already
            'apiURL' => $request->getDispatcher()->url($request, ROUTE_API, $context->getData('urlPath'), 'userComments/'),
			'csrfToken' => $request->getSession()->getCSRFToken(),
			'apiKey' => $this->getSetting($context->getId(), 'apiKey'),
			'submissionId' => $publication->getData('submissionId'), 
			'publicationId' =>  $publication->getId(),
			'foreignCommentId' => 1,
			'user' => $user,
			'userId' => $user ? $user->getId() : null,
		]);
        $output .= $smarty->fetch($this->getTemplateResource('commentBlock.tpl'));
        return false;
    }	

	/**
     * Add/override new api endpoints to existing list of api endpoints
     * this is for 4.5 already
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
		$request = $params[0];
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

    public function addWebsiteSettingsTabData($hookName, $params)
    {
        // We need to assign the template data with a hook before we add the template itself
        $templateMgr = $params[0];
        $template = $params[1];

        if ($template !== 'management/website.tpl') {
            return false;
        }

        $request = Application::get()->getRequest();
        $context = $request->getContext();
        $dispatcher = $request->getDispatcher();
        // listPanel does not support this :/
        $apiURL = $request->getDispatcher()->url($request, ROUTE_API, $context->getData('urlPath'), 'userComments/getFlaggedComments');
        $csrfToken = $request->getSession()->getCSRFToken();
        
        $queryResults = Repo::userComment()
            ->getCollector()
            ->filterByFlag(true)
            ->getMany();
            // ->remember();

        if ($queryResults->isEmpty()) {
            $userComments = [];
        }
        else { 
            $userComments = Repo::userComment()
            ->getListPanelMap()
            ->mapMany($queryResults->values());
        };


        // $flaggedCommentsList = new ListPanel(
        //     FLAGGED_COMMENTS_LIST,
        //     __('plugins.generic.userComments.listFlaggedComments'),
        //     [
        //         'apiURL' => $apiURL,
        //         'lazyLoad' => true,
        //     ]
        // );

		// The URL where the form will be submitted		
		$dispatcher = $request->getDispatcher();
		// new for 3.5
		// $apiUrl = $dispatcher->url($request, ROUTE_API, $context->getPath(), 'submissions/usercomments/edit');
		$apiUrl = $dispatcher->url($request, ROUTE_API, $context->getPath(), 'userComments/');

        // get the translation strings
        $i18n['hide_flagged_comment'] = __('plugins.generic.userComments.hideFlaggedComment');
        $i18n['remove_flag'] = __('plugins.generic.userComments.removeFlag');
        $i18n['cancel'] = __('plugins.generic.userComments.cancel');
        $i18n['alert_not_flagged'] = __('plugins.generic.userComments.alertNotFlagged');
        $i18n['flag_info'] = __('plugins.generic.userComments.flagInfo');
        $i18n['flag_info_comment'] = __('plugins.generic.userComments.flagInfoComment');
        $i18n['flag_info_note'] = __('plugins.generic.userComments.flagInfoNote');
        $i18n['flag_info_hidden'] = __('plugins.generic.userComments.flagInfoHidden');

        // we don't want to override existing states, so we assign them first and then add the ListPanel        
        $lists = $templateMgr->getState('components');
        // $listConfig = $flaggedCommentsList->getConfig();
        // $lists[$flaggedCommentsList->id] = $listConfig;

        $templateMgr->setState([
            'components' => $lists,
            'items' => $userComments,
            'apiurl' => $apiUrl,
            'preprinturl' => $dispatcher->url($request, PKPApplication::ROUTE_PAGE, $context->getPath(), 'preprint', 'view'),
            'csrftoken' => $csrfToken,
            'i18n' => $i18n
        ]);
        
        return false;
    }

	public function addWebsiteSettingsTab($hookName, $params) {
        $request = Application::get()->getRequest();
        $templateMgr = TemplateManager::getManager($request);        
      
        return $templateMgr->display($this->getTemplateResource('flaggedCommentsTab.tpl'));				
	}	

    public function addUserCommentsSchema(string $hookName, array $params): bool
    {
        $schema = &$params[0];
        $schema = $this->getJsonSchema('userComment');
        return true;
    }    

    private function getJsonSchema(string $schemaName): ?\stdClass
    {
        $schemaFile = sprintf(
            '%s/plugins/generic/userComments/schemas/%s.json',
            BASE_SYS_DIR,
            $schemaName
        );
        if (file_exists($schemaFile)) {
            $schema = json_decode(file_get_contents($schemaFile));
            if (!$schema) {
                throw new \Exception(
                    'Schema failed to decode. This usually means it is invalid JSON. Requested: '
                    . $schemaFile
                    . '. Last JSON error: '
                    . json_last_error()
                );
            }
        }
        return $schema;
    }

}

