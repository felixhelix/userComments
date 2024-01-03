{**
 * templates/reviewCommentForm.tpl
 *
 * Copyright (c) 2013-2023 Simon Fraser University
 * Copyright (c) 2003-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * Template for one-page submission form
 *}
{extends file="layouts/backend.tpl"}

{block name="page"}
	<pkp-form
		v-bind="components.{$smarty.const.FORM_USER_COMMENT}"
		@set="set"
	/>	
{/block}
