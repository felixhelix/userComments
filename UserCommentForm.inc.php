<?php

/**
 * @file UserCommentForm.inc.php
 *
 * Copyright (c) 2013-2023 Simon Fraser University
 * Copyright (c) 2003-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class UserCommentForm
 * @ingroup plugins_generic_comments
 *
 * @brief form to edit flagged comments
 */


use PKP\components\forms\FormComponent;
use PKP\components\forms\FieldText;
use PKP\components\forms\FieldHTML;
use PKP\components\forms\FieldOptions;

define('FORM_USER_COMMENT', 'userComment');

class UserCommentForm extends FormComponent
{
	/** @copydoc FormComponent::$id */
	public $id = FORM_USER_COMMENT;

	/** @copydoc FormComponent::$method */
	public $method = 'POST';

	/**
	 * Constructor
	 *
	 * @param $action string URL to submit the form to
	 * @param $request
	 */	
	public function __construct($action, $request, $props)
	{
		$this->id = FORM_USER_COMMENT;
		$this->action = $action;	

		$reviewListUrl = $request->getRouter()->url($request, null, 'FlaggedComments');

		$commentInfo = '';

		if ($props['$visible'] != true) {
			$commentInfo .= '<notification type="warning" class="pkpNotification pkpNotification--warning">This comment is hidden</notification>';
		}

		$commentInfo .= '
		<div class="listPanel__itemSummary">
			<div class="listPanel__itemIdentity" style="
			background-color: lightgrey;
			margin-top: 1rem;
			padding: 0.5rem;
			border-radius: 0.5rem;" >
				<div>
				Published: $commentDate <br> 
				Flagged: $flaggedDate
				</div>
				<div>
				Id: $commentId
				Submission-Id: $submissionId
				Foreign-Comment-Id: $foreignCommentId
				</div>
				<div>
				User: $userName ($userEmail)
				<div style="background-color: white; 
				padding: 0.5rem;
				border-radius: 0.5rem;" >
					$commentText
				</div>
				<div>
					<a href="http://localhost/ops3/index.php/socios/preprint/view/$submissionId">View in context</a>
				</div>
				<div>
					<a href="$commentsListUrl">Back to List</a>
				</div>				
			</div>
		</div>';	

		$this->addPage([
			'id' => 'default',
			'submitButton' => [
				'label' => 'Update Comment',
			]]);	

		$this->addGroup([
			'id' => 'default',
			'pageId' => 'default',
		]);			

		// $this->addField(new FieldHTML('commentInfo', [
		// 	'groupId' => 'default',
		// 	'description' => strtr($commentInfo, $props),
		// 	'size' => 'large'
		// ]));	

		// $this->addField(new FieldText('visible', [
		// 	'groupId' => 'default',
		// 	'isRequired' => false,
		// 	'inputType' => 'hidden',		
		// 	'value' => $props['$visible']
		// ]));

		$this->addField(new FieldText('userCommentId', [
			'groupId' => 'default',
			'isRequired' => false,
			'inputType' => 'hidden',
			'value' => $props['$commentId']
		]));	
		
		
		$this->addField(new FieldOptions('visible', [
			'groupId' => 'default',
			'options' => [
				['value' => true, 'label' => 'visible'],
			],
			'description' => 'If visibility is turned off, a replacement text is shown.',
			'label' => 'Set visibility',
			'value' => [$props['$visible']]
		]));	

		// $this->addField(new FieldOptions('toggleFlag', [
		// 	'groupId' => 'default',
		// 	'options' => [
		// 		['value' => true, 'label' => 'flagged'],
		// 	],
		// 	'label' => 'Toggle flag',
		// 	'value' => $props['$flaggedDate'] ? true : false
		// ]));			

	}
}

