<section class="my-8">
	<h2 class="font-semibold text-sky-500">Comments</h2>
    <div class="text-sm" 
    id="commentsApp" 
    data-baseUrl="{$baseURL}" 
    data-apiUrl="{$apiURL}" 
    data-user="{$userId}" 
    data-apiKey="{$apiKey}" 
    data-submissionId="{$submissionId}" 
    data-publicationId="{$publication->getData('id')}"
    data-publicationVersion="{$publication->getData('version')}" 
    data-csrfToken="{$csrfToken}">
        <user-comments-block :user-comments="userComments"></user-comments-block>
        <div>
        {if $user}
        <form-container :userCommentId=null></form-container>
        {else}
        <span>{translate key='plugins.generic.userComments.loggedOut' loginPageUrl="{$loginPageUrl}?source={$source|escape:'url'}"}</span>
        {/if}
        </div>
    </div>
</section>