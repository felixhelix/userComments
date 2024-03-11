<section class="my-4 text-sm">
	<h2 class="font-semibold">Comments</h2>
    <div id="commentsApp" 
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
        You have to be logged in to post a comment.
        {/if}
        </div>
    </div>
</section>