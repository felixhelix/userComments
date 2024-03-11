const App = Vue.createApp({
  data() {
    return {
    user: null,
    userComments: [],
    dataFetched: false,
    commentText: '',
    apiKey: '',
    submissionId: '',
    version: '',
    publicationId: '',
    csrfToken: '',
    foreignCommentId: null,
    commentAction: 'formButton' //'commentForm'
    }
  },
  mounted() {     
    // Read some props from the root element
    this.apiKey = this.$el.parentNode.dataset.apikey;
    this.csrfToken = this.$el.parentNode.dataset.csrftoken;   
    this.user = this.$el.parentNode.dataset.user;   
    // Fetch data from the API when the component is mounted
    this.fetchData();
  },
  methods: {
    moveForm2(userCommentId, event) {
      // replace the button with the form component
      commentAction = 'commentForm';
    },
    moveForm(userCommentId, event) {
      // remove form from current parent node
      formNode = document.getElementById("userCommentForm");
      oldParentNode = formNode.parentNode;
      oldParentNode.removeChild(formNode);
      // get the new parent node
      newParentNode = event.target.parentNode;
      newParentNode.appendChild(formNode);
      // set the foreignCommentId to the commentId
      // formNode.querySelector('#foreignCommentId').value = userCommentId;
      this.foreignCommentId = userCommentId
      // hide the button in the new container
      event.target.style.display = "none";
      // show the button in the old container
      button = oldParentNode.getElementsByTagName('button')[0];
      button.style.display = "block";
    },  
    fetchData() {
      this.location = window.location.href;
      // this.submissionId = this.location.split("/")[this.location.split("/").length -1];
      this.publicationId = document.getElementById('commentsApp').dataset.publicationid;
      // An API key is needed for unauthenticated GET requests
      this.apiKey = document.getElementById('commentsApp').dataset.apikey;
      // Make a GET request to the API
      fetch('http://localhost/ops3/index.php/socios/api/v1/userComments/getCommentsByPublication/' + this.publicationId + "?" + new URLSearchParams({
        'apiToken': this.apiKey
      }))
        .then(response => response.json())
        .then(data => {
          // Set the fetched data to the component state
          this.userComments = this.buildTree(data).children;
          this.dataFetched = true;
          // Clear the input fields
          this.commentText = '';
        })
        .catch(error => {
          console.error('Error fetching data:', error);
        });
    },
    postData(parentComponent, submitEvent) {
      // Make a POST request to the API
      fetch('http://localhost/ops3/index.php/socios/api/v1/userComments/submitComment', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Csrf-Token': this.csrfToken,          
        },
        body: JSON.stringify({
          commentText: submitEvent.target.commentText.value,
          publicationId: this.publicationId,
          foreignCommentId: submitEvent.target.dataset.usercommentid,
          completed: false
        }),
      })
        .then(response => response.json())
        .then(data => {
          // Handle the response if needed
          console.log('Data posted successfully:', data);

          // Fetch data again to update the displayed list
          this.fetchData();
          // close the comment field
          parentComponent.toggleComment();
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
    }, 
  }
});

App.component('userCommentsBlock', {
  props: ['userComments'],
  data() {
    return {
      commentAction: 'formButton'
    }
  },
  methods: {
    flagComment(userCommentId) {
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

          // Fetch data again to update the displayed list
          this.$root.fetchData();
        })
        .catch(error => {
          console.error('Error posting data:', error);
        });      
    }, 
    toggleComment() {
      this.commentAction = (this.commentAction == 'formButton' ? 'commentForm' : 'formButton');
    }
  },
  template: `
  <ul class="rounded-lg" v-if="userComments && userComments.length">
  <li v-for="userComment in userComments" :key="userComment.id">
    <template v-if="userComment.visible != '0'">
      <div class="bg-gray-100 p-2 rounded-lg my-1" :id="userComment.id">
        {{ userComment.commentText }}
        <span class="block font-semibold pt-1">{{ userComment.userName }} {{ userComment.commentDate }}</span>
        <button v-if="$root.user && userComment.flaggedDate == null" @click="flagComment(userComment.id)">flag</button>
        <div v-if="userComment.flaggedDate != null" style="background-color: red">{{ userComment.flaggedDate}}</div>
        <form-container :userCommentId=userComment.id></form-container>
      </div>     
    </template>
    <template v-else>
      <div class="bg-gray-100 p-1 rounded-lg" :id="userComment.id">
        <i>This comment has been unpublished due to violation of our code of conduct.</i>
      </div>
    </template>
    <div class="pl-3" v-if="userComment.children && userComment.children.length">
      <user-comments-block :user-comments="userComment.children"></user-comments-block>
    </div>
  </li>
</ul>`
});

App.component('formContainer', {
  props: ['userCommentId'],
  data() {
    return {
      commentAction:  (this.userCommentId == null ? 'commentForm' : 'formButton')
    }
  },  
  methods: {
    toggleComment() {
      this.commentAction = (this.commentAction == 'formButton' ? 'commentForm' : 'formButton');
    }
  },
  template: `
    <div v-if="$root.user" :data-commentID=userCommentId>
      <component :is="commentAction" :userCommentId=userCommentId></component>
    </div>`
  });
  
App.component('commentForm', {
  props: ['userCommentId'],
  template: `
    <form id="userCommentForm" @submit.prevent="$root.postData($parent, $event)" :data-userCommentId="userCommentId">
      <label for="commentText" class="hidden">Your comment:</label>
      <textarea type="text" id="commentText" v-model="$root.commentText" required  class="block rounded border-2 w-full my-2"></textarea>
      <input id="userCommentId" type="hidden" v-model="userCommentId">
      <button type="submit" class="rounded border-2 p-1 mr-2  border-green-400">Submit</button>
      <button @click="$parent.toggleComment()" class="rounded border-2 p-1">close</button>
    </form>`
});

App.component('formButton', {
  props: ['userCommentId'],
  template: `
    <button @click="$parent.toggleComment()" class="rounded border-2 p-1">comment</button>
    `
  });
  
App.mount('#commentsApp')
