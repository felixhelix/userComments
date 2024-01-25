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

<div style="background-color: white; padding: 0.5rem; border-radius: 0.5rem;">
	{{$commentText}}
</div>

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#editCommentsForm').pkpHandler('$.pkp.controllers.form.FormHandler');
	{rdelim});
</script>
<form id="editCommentsForm" class="pkp_form" action={{$apiUrl}} method="post">
{csrf}
{fbvFormArea}
	{if !empty($actionNames)}
		{fbvFormSection}
			{foreach from=$actionNames key=action item=actionName}
				{fbvElement type="submit" label="$actionName" id="$action" name="$action" value="1" class="$action" translate=false inline=true}
			{/foreach}
		{/fbvFormSection}
	{/if}
{/fbvFormArea}
</form>

{/block}
