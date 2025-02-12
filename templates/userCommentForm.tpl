{**
 * templates/reviewCommentForm.tpl
 *
 * Copyright (c) 2013-2023 Simon Fraser University
 * Copyright (c) 2003-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * Template for editing flagged comments
 * Holds the form component
 *}
{extends file="layouts/backend.tpl"}

{block name="page"}
<div class="pkpFormGroup">
	<div class="pkpNotification pkpNotification--warning">Flagged {{$flaggedDate|date_format:$datetimeFormatLong}} by {{$flaggedByUser}}
	<span style="display: block">Reason given: {{$flagNote}}</span>
	</div>
	<div style="margin-top: 1rem;">Comment Text:</div>
	<div style="background-color: white; padding: 0.5rem; border-radius: 0.5rem;">
		{{$commentText}}
	</div>
	<div>Posted {{$commentDate|date_format:$datetimeFormatLong}} by {{$userName}} ({{$userEmail}})</div>
	<div><a href="{{url page="preprint" op="view" path=$submissionId|to_array:"version":$publicationId}}">View submission page</a></div>
	<div><a href="{{$commentListUrl}}">Back to list</a></div>
</div>
<pkp-form
	v-bind="components.{$smarty.const.FORM_USER_COMMENT}"
	@set="set"
/>	
{/block}
