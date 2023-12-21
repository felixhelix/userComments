<div class="item">
    <h3>Comments</h3>
    <div id="commentsApp" data-user="{$user->getId()}" data-apiKey="{$apiKey}" data-submissionId="{$submissionId}" data-csrfToken="{$csrfToken}">
        <user-comments-block :user-comments="userComments"></user-comments-block>
        <div>
        <h4>Submit a comment</h4>
        {if $user}
        You are logged in as {$user->getFullName()}
        <form-container :userCommentId=null></form-container>
        {else}
        You have to be logged in to post a comment.
        {/if}
        </div>
    </div>
</div>
<!--- e-mail@mieterverein-koeln.de --->