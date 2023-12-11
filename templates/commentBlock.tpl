<div class="item">
    <h3>Comments</h3>
    <div id="commentsApp" data-apiKey="{$apiKey}" data-submissionId="{$submissionId}">
        <ul class="userComments" v-if="userComments && userComments.length">
            <li v-for="userComment in userComments" :key="userComment.id">
                {{ userComment.commentText }}
                <span class="commentMeta">{{ userComment.userName }} {{ userComment.commentDate }}</span>
                {if $user}
                <div>
                <form @submit.prevent="postReply">
                    <input type="hidden" id="csfrToken" value="{$csrfToken}">
                    <input type="hidden" id="foreignCommentId" value="{$id}">
                    <label for="replyText">Reply to comment:</label>
                    <textarea type="text" id="replyText" v-model="replyText" required></textarea>
                    <button type="submit">Submit</button>
                </form>
                </div>
                {/if}
            </li>
        </ul>
        <div>
        <h4>Submit a comment</h4>
        {if $user}
        You are logged in as {$user->getFullName()}
        <form @submit.prevent="postData">
            <input type="hidden" id="csfrToken" value="{$csrfToken}">
            <input type="hidden" id="foreignCommentId" value="null">
            <label for="commentText">Your comment:</label>
            <textarea type="text" id="commentText" v-model="commentText" required></textarea>
            <button type="submit">Submit</button>
        </form>
        {else}
        You have to be logged in to post a comment.
        {/if}
        </div>
    </div>
</div>
