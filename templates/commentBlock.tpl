<div class="item">
    <h3>Comments</h3>
    <div id="commentsApp">
        <ul v-if="userComments && userComments.length">
            <li v-for="userComment in userComments" :key="userComment.id">
            {{ userComment.commentText }}
            </li>
        </ul>
        <div>
        <h4>Post Data:</h4>
        <form @submit.prevent="postData">
            <input type="hidden" id="csfrtoken" value="{$csrfToken}">
            <input type="hidden" id="submissionId" value="{$submissionId}">
            <input type="hidden" id="foreignCommentId" value="{$foreignCommentId}">
            <label for="commentText">Your comment:</label>
            <textarea type="text" id="commentText" v-model="commentText" required></textarea>
            <button type="submit">Submit</button>
        </form>
        </div>
    </div>
</div>
