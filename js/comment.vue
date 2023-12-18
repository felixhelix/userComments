import { ref } from 'vue'

export default {
    props: ['userComments','user'],
    methods: {
      createFormId(userCommentId) {
        return "comment_" + userCommentId;
      },
      createButtonId(userCommentId) {
        return "button_" + userCommentId;
      },  
      moveForm(userCommentid) {
        console.log(userCommentid);
        // remove form from current parent node
        formNode = document.getElementById("userCommentForm");
        currentParentNode = formNode.parentNode;
        currentParentNode.removeChild(formNode);
        // Add the button to the old form
        if (this.buttonNode) {
          currentParentNode.appendChild(this.buttonNode);
        };
        // get the new parent node
        newParentNode = document.getElementById(this.createFormId(userCommentid));
        newParentNode.appendChild(formNode);
        // set the foreignCommentId to the commentId
        formNode.querySelector('#foreignCommentId').value = userCommentid;
        // remove the button in the new container
        this.buttonNode = document.getElementById(this.createButtonId(userCommentid));
        newParentNode.removeChild(this.buttonNode);
      },    
    },
    template: `
    <ul class="userComments" v-if="userComments && userComments.length">
    <li v-for="userComment in userComments" :key="userComment.id">
        {{ userComment.commentText }}
        <span class="commentMeta">{{ userComment.userName }} {{ userComment.commentDate }}</span>
        <div :v-if="user" :id=createFormId(userComment.id) :data-commentID=userComment.id>
            <button :id=createButtonId(userComment.id) @click="moveForm(userComment.id)">reply</button>
        </div>
    </li>
  </ul>`
  };