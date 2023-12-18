const App = Vue.createApp({
  data() {
    return {
    user: null,
    userComments: [],
    userCommentsTree: [],
    dataFetched: false,
    commentText: '',
    replyText: '',
    buttonId: 'button_null',
    }
  },
  components: {
    // userCommentsBlock
  },
  beforeCreate() {
    // import NodeTree from "./NodeTree";
  },
  mounted() {    
    // Fetch data from the API when the component is mounted
    this.fetchData();
    this.originalParentNode = this.$el.parentNode;
  },
  methods: {
    moveForm(userCommentid) {
      console.log("commentId: " + userCommentid);
      // remove form from current parent node
      formNode = document.getElementById("userCommentForm");
      currentParentNode = formNode.parentNode;
      currentParentNode.removeChild(formNode);
      // get the new parent node
      newParentNode = document.getElementById(this.createFormId(userCommentid));
      newParentNode.appendChild(formNode);
      // set the foreignCommentId to the commentId
      formNode.querySelector('#foreignCommentId').value = userCommentid;
      // remove the button in the target container
      // buttonNode = document.getElementById(this.createButtonId(userCommentid));
      // newParentNode.removeChild(this.buttonNode);      
      // // Add the button to the old form
      // if (this.buttonNode) {
      //   currentParentNode.appendChild(this.buttonNode);
      // };      
      this.buttonId = this.createButtonId(userCommentid)
    },
    createFormId(userCommentId) {
      return "comment_" + userCommentId;
    },
    createButtonId(userCommentId) {
      return "button_" + userCommentId;
    },    
    fetchFormData() {
      // The token is embedded in the smarty template
      this.csrfToken = document.getElementById('csfrToken').value;
      // this.submissionId = document.getElementById('submissionId').value;
      this.foreignCommentId = document.getElementById('foreignCommentId').value;      
    },
    fetchData() {
      this.location = window.location.href;
      this.submissionId = this.location.split("/")[this.location.split("/").length -1];
      // An API key is needed for unauthenticated GET requests
      this.apiKey = document.getElementById('commentsApp').dataset.apikey;
      // Make a GET request to the API
      fetch('http://localhost/ops3/index.php/socios/api/v1/userComments/getCommentsBySubmission/' + this.submissionId + "?" + new URLSearchParams({
        'apiToken': this.apiKey
      }))
        .then(response => response.json())
        .then(data => {
          // Set the fetched data to the component state
          this.userComments = data;
          this.userCommentsTree = this.buildTree(data).children;
          this.dataFetched = true;
          // Clear the input fields
          this.commentText = '';
        })
        .catch(error => {
          console.error('Error fetching data:', error);
        });
    },
    postData() {
      // Fetch CSRF token and submission data
      this.fetchFormData();
      // Make a POST request to the API
      fetch('http://localhost/ops3/index.php/socios/api/v1/userComments/submitComment', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Csrf-Token': this.csrfToken,          
        },
        body: JSON.stringify({
          commentText: this.commentText,
          submissionId: this.submissionId,
          foreignCommentId: this.foreignCommentId,
          completed: false
        }),
      })
        .then(response => response.json())
        .then(data => {
          // Handle the response if needed
          console.log('Data posted successfully:', data);

          // Fetch data again to update the displayed list
          this.fetchData();
          // Re-position the comment field, return a success message, put the focus on the new comment or reply
          this.moveForm(null); 
        })
        .catch(error => {
          console.error('Error posting data:', error);
        });
    },
    buildTree(nodes) {
      tree = {
        id: null,
        children: []
      };
      for (const item of nodes) 
        {
            if (item.foreignCommentId == null) {
                // we only need the top level nodes
                // console.log("root: " + item.id);    
                tree.children.push(this.returnChildnodes(item, nodes));
            }
        }
        return tree;
    },
    returnChildnodes(item, nodes, childnodes = []) 
    {
        // console.log(item.id);    
        var childnodes_ = nodes.filter((node) => node.foreignCommentId == item.id );
        if (childnodes_.length == 0) { return item }
        else {
            childnodes = [];        
            for (const childnode of childnodes_) {
                childnodes.push(this.returnChildnodes(childnode, nodes, childnodes));
                // console.log(JSON.stringify(childnodes));   
            }
        };
        return {id: item.id, foreignCommentId: item.foreignCommentId, userName: item.userName, commentDate: item.commentDate, commentText: item.commentText, children: childnodes};
    }
  }
});

App.component('userCommentsBlock', {
  props: ['userComments','user', 'buttonId'],
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
      // get the new parent node
      newParentNode = document.getElementById(this.createFormId(userCommentid));
      newParentNode.appendChild(formNode);
      // set the foreignCommentId to the commentId
      formNode.querySelector('#foreignCommentId').value = userCommentid;
      // remove the button in the target container
      this.buttonNode = document.getElementById(this.createButtonId(userCommentid));
      newParentNode.removeChild(this.buttonNode);      
      // Add the button to the old form
      if (this.buttonNode) {
        currentParentNode.appendChild(this.buttonNode);
      };
    },    
  },
  template: `
  <ul class="userComments" v-if="userComments && userComments.length">
  <li v-for="userComment in userComments" :key="userComment.id">
      <div class="userComment">
        {{ userComment.commentText }}
        <span class="commentMeta">{{ userComment.userName }} {{ userComment.commentDate }}</span>
        <div :v-if="user" :id=createFormId(userComment.id) :data-commentID=userComment.id>
            <button :id=createButtonId(userComment.id) @click="moveForm(userComment.id)">reply</button>
        </div>
      </div>
      <div style="margin-left: 1rem" v-if="userComment.children && userComment.children.length">
        <user-comments-block :user-comments="userComment.children" :user="user" :buttonId="null"></user-comments-block>
      </div>      
  </li>
</ul>`
});

App.mount('#commentsApp')
