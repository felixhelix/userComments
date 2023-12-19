<div class="item">
    <h3>Comments</h3>
    <div id="commentsApp" data-user="{$user->getId()}" data-apiKey="{$apiKey}" data-submissionId="{$submissionId}" data-csrfToken="{$csrfToken}">
        <user-comments-block :user-comments="userCommentsTree"></user-comments-block>
        <div>
        <h4>Submit a comment</h4>
        {if $user}
        You are logged in as {$user->getFullName()}
        <div id="comment_null" data-commentID="null">
            <button style="display:none" @click="moveForm('null')" :id=createButtonId('null')>comment</button>
            <form id="userCommentForm" @submit.prevent="postData">
                <input type="hidden" id="csfrToken" value="{$csrfToken}">
                <input type="hidden" id="foreignCommentId" value="NULL">
                <label for="commentText">Your comment:</label>
                <textarea type="text" id="commentText" v-model="commentText" required></textarea>
                <button type="submit">Submit</button>
            </form>
        </div>
        {else}
        You have to be logged in to post a comment.
        {/if}
        </div>
    </div>
</div>

<!--- e-mail@mieterverein-koeln.de --->