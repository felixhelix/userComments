<section class="my-4">
	<h2 class="font-semibold text-sky-500">Comments</h2>
    <div class="text-sm" 
    id="commentsApp" 
    data-baseUrl="{$baseURL}" 
    data-user="{$userId}" 
    data-apiKey="{$apiKey}" 
    data-submissionId="{$submissionId}" 
    data-publicationId="{$publication->getData('id')}"
    data-publicationVersion="{$publication->getData('version')}" 
    data-csrfToken="{$csrfToken}">
        <user-comments-block :user-comments="userComments"></user-comments-block>
        <div class="userCommentForm">
        <h4 class="hidden">Submit a comment</h4>
        {if $user}
        <span class="hidden">You are logged in as {$user->getFullName()}</span>
        <form-container :userCommentId=null></form-container>
        {else}
        <span>{translate key='plugins.generic.comments.loggedOut' loginPageUrl="login"}</span>
        {/if}
        </div>
    </div>
</section>