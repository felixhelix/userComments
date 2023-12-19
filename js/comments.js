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
    apiKey: '',
    csrfToken: ''
    }
  },
  mounted() {     
    // Read some props from the root element
    this.originalParentNode = this.$el.parentNode;
    this.apiKey = this.originalParentNode.dataset.apikey;
    this.csrfToken = this.originalParentNode.dataset.csrftoken;   
    this.user = this.originalParentNode.dataset.user;   
    // Fetch data from the API when the component is mounted
    this.fetchData();
  },
  methods: {
    moveForm(userCommentid) {
      // remove form from current parent node
      formNode = document.getElementById("userCommentForm");
      oldParentNode = formNode.parentNode;
      oldParentNode.removeChild(formNode);
      // get the new parent node
      newParentNode = document.getElementById(this.createFormId(userCommentid));
      newParentNode.appendChild(formNode);
      // set the foreignCommentId to the commentId
      formNode.querySelector('#foreignCommentId').value = userCommentid;
      // hide the button in the new container
      button = document.getElementById(this.createCommentId(userCommentid));
      button.style.display = "none";
      // show the button in the old container
      button = oldParentNode.getElementsByTagName('button')[0];
      button.style.display = "block";
    },   
    createFormId(userCommentId) {
      return "comment_" + userCommentId;
    },
    createCommentId(userCommentId) {
      return "comment_" + userCommentId;
    },      
    createButtonId(userCommentId) {
      return "button_" + userCommentId;
    },    
    fetchFormData() {
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
        return {id: item.id, foreignCommentId: item.foreignCommentId, userName: item.userName, commentDate: item.commentDate, commentText: item.commentText, flaggedDate: item.flaggedDate, visible: item.visible, children: childnodes};
    }
  }
});

App.component('userCommentsBlock', {
  props: ['userComments'],
  methods: {
    createFormId(userCommentId) {
      return "commentForm_" + userCommentId;
    },
    createCommentId(userCommentId) {
      return "comment_" + userCommentId;
    },  
    createFlagId(userCommentId) {
      return "flag_" + userCommentId;
    },  
    flagComment(userCommentId) {
      alert("flag comment " + userCommentId);
      // Make a POST request to the API
      fetch('http://localhost/ops3/index.php/socios/api/v1/userComments/flagComment', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Csrf-Token': this.$root.csrfToken,          
        },
        body: JSON.stringify({
          userCommentId: userCommentId,
          completed: false
        }),
      })
        .then(response => response.json())
        .then(data => {
          // Handle the response if needed
          console.log('Data posted successfully:', data);

          // Change look of button to reflect flagging
          // ...
        })
        .catch(error => {
          console.error('Error posting data:', error);
        });      
    },
    moveFormRoot(userCommentid) { 
      return this.$root.moveForm(userCommentid);
    },
    moveForm(userCommentid) {
      // remove form from current parent node
      formNode = document.getElementById("userCommentForm");
      oldParentNode = formNode.parentNode;
      oldParentNode.removeChild(formNode);
      // get the new parent node
      newParentNode = document.getElementById(this.createFormId(userCommentid));
      newParentNode.appendChild(formNode);
      // set the foreignCommentId to the commentId
      formNode.querySelector('#foreignCommentId').value = userCommentid;
      // hide the button in the new container
      button = document.getElementById(this.createCommentId(userCommentid));
      button.style.display = "none";
      // show the button in the old container
      button = oldParentNode.getElementsByTagName('button')[0];
      button.style.display = "block";
    },    
  },
  template: `
  <ul class="userComments" v-if="userComments && userComments.length">
  <li v-for="userComment in userComments" :key="userComment.id">
      <div class="userComment">
        {{ userComment.commentText }}
        <span class="commentMeta">{{ userComment.userName }} {{ userComment.commentDate }}</span>
        <button v-if="$root.user && userComment.flaggedDate == null" :id=createFlagId(userComment.id) @click="flagComment(userComment.id)">flag</button>
        <div v-if="userComment.flaggedDate != null" style="background-color: red">{{ userComment.flaggedDate}}</div>
        <div v-if="$root.user" :id=createFormId(userComment.id) :data-commentID=userComment.id>
            <button :id=createCommentId(userComment.id) @click="moveForm(userComment.id)">reply</button>
        </div>
      </div>
      <div style="margin-left: 1rem" v-if="userComment.children && userComment.children.length">
        <user-comments-block :user-comments="userComment.children"></user-comments-block>
      </div>      
  </li>
</ul>`
});

App.mount('#commentsApp')
