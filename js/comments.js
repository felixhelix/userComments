const App = Vue.createApp({
  data() {
    return {
    baseURL: '',
    user: null,
    userComments: [],
    dataFetched: false,
    apiKey: '',
    apiURL: '',
    submissionId: '',
    version: '',
    publicationId: '',
    csrfToken: '',
    foreignCommentId: null,
    commentAction: 'formButton'
    }
  },
  mounted() {     
    // Read some props from the root element
    this.apiKey = this.$el.parentNode.dataset.apikey;
    this.apiURL = this.$el.parentNode.dataset.apiurl;
    this.csrfToken = this.$el.parentNode.dataset.csrftoken;   
    this.user = this.$el.parentNode.dataset.user;   
    this.publicationId = this.$el.parentNode.dataset.publicationid;  
    this.submissionId = this.$el.parentNode.dataset.submissionid;  
    // Fetch data from the API when the component is mounted
    this.fetchData();
  },
  methods: {
    fetchData() {
      this.baseURL = document.getElementById('commentsApp').dataset.baseurl;
      this.apiURL = document.getElementById('commentsApp').dataset.apiurl;
      this.publicationId = document.getElementById('commentsApp').dataset.publicationid;
      // An API key is needed for unauthenticated GET requests
      this.apiKey = document.getElementById('commentsApp').dataset.apikey;
      // Make a GET request to the API
      // fetch(this.baseURL + '/index.php/socios/api/v1/userComments/getCommentsByPublication/' + this.publicationId + "?" + new URLSearchParams({
      //  'apiToken': this.apiKey
      // }))
      fetch(this.apiURL + 'getCommentsByPublication/' +  this.publicationId + "?" + new URLSearchParams({'apiToken': this.apiKey}))
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
      // get the form value
      commentTextField =  submitEvent.target[name = 'commentText'];
      // Make a POST request to the API
      fetch(this.$root.apiURL + 'submitComment', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Csrf-Token': this.csrfToken,          
        },
        body: JSON.stringify({
          commentText: commentTextField.value,
          publicationId: this.publicationId,
          submissionId: this.submissionId,
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
        var childnodes_ = nodes.filter((node) => node.foreignCommentId == item.id );
        if (childnodes_.length == 0) { return item }
        else {
            childnodes = [];        
            for (const childnode of childnodes_) {
                childnodes.push(this.returnChildnodes(childnode, nodes, childnodes));
            }
        };
        return {id: item.id, 
          foreignCommentId: item.foreignCommentId, 
          userName: item.userName, 
          userOrcid: item.userOrcid,
          commentDate: item.commentDate, 
          commentText: item.commentText, 
          flaggedDate: item.flaggedDate, 
          flagged: item.flagged, 
          visible: item.visible, 
          children: childnodes};
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
      if (confirm("Do you want to flag this post?") == true) {
        // Make a POST request to the API
        fetch(this.$root.apiURL + 'flagComment', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Csrf-Token': this.$root.csrfToken,          
          },
          body: JSON.stringify({
            userCommentId: userCommentId,
            publicationId: Number(this.$root.publicationId),
            completed: false
          }),
        })
          .then(response => response.json())
          .then(data => {
            // Handle the response if needed
            console.log('Data posted successfully:', data);
            // Fetch data again to update the displayed list
            this.$root.fetchData();
          })
          .catch(error => {
            console.error('Error posting data:', error);
          });      
      }
    }, 
    toggleComment() {
      this.commentAction = (this.commentAction == 'formButton' ? 'commentForm' : 'formButton');
    },
    confirmFlagging() {

    }
  },
  template: `
  <ul data-title="userComments" class="rounded-lg" v-if="userComments && userComments.length">
  <li class="list-none" v-for="userComment in userComments" :key="userComment.id">
      <div class="bg-gray-100 p-2 rounded-lg my-1" :id="userComment.id">
        <template v-if="userComment.visible != '0'">
          {{ userComment.commentText }}
        </template>          
        <template v-else>
          <i>This comment has been unpublished due to violation of our code of conduct.</i>
        </template>          
        <span class="block text-gray-400">{{ userComment.commentDate }}</span>
        <div class="flex justify-between w-full text-gray-400">
          <div class="flex font-semibold">
            {{ userComment.userName }}
            <a class="pl-1 font-normal" :href="userComment.userOrcid">{{ userComment.userOrcid }}</a>
          </div>
          <div class="flex">
            <button name="flagComment" v-if="$root.user && userComment.flagged != true" @click="flagComment(userComment.id)">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6" role="img" aria-label="[title]">
                <title>flag this comment</title>
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5" />
              </svg>
            </button>
          </div>
          <div class="flex" v-if="userComment.flagged == true" data-isFlagged="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6" role="img" aria-label="[title]">
              <title>comment has been flagged {{ userComment.flaggedDate}}</title>
              <path fill-rule="evenodd" d="M3 2.25a.75.75 0 0 1 .75.75v.54l1.838-.46a9.75 9.75 0 0 1 6.725.738l.108.054A8.25 8.25 0 0 0 18 4.524l3.11-.732a.75.75 0 0 1 .917.81 47.784 47.784 0 0 0 .005 10.337.75.75 0 0 1-.574.812l-3.114.733a9.75 9.75 0 0 1-6.594-.77l-.108-.054a8.25 8.25 0 0 0-5.69-.625l-2.202.55V21a.75.75 0 0 1-1.5 0V3A.75.75 0 0 1 3 2.25Z" clip-rule="evenodd" />
            </svg>
          </div>
        </div>
        <form-container v-if="userComment.visible != '0'" :userCommentId=userComment.id></form-container>
      </div>     
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
      // If this is the root element, display the input form (commentForm), else display a toggle button (formButton)
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
  // display the input form
  props: ['userCommentId'],
  data() {
    return {
      userCommentFieldId:  ("userComment_" + this.userCommentId) // use v-bind:id="userCommentFieldId"
    }
  }, 
  template: `
    <form @submit.prevent="$root.postData($parent, $event)" :data-userCommentId="userCommentId">
      <label>Your comment:
        <textarea type="text" name="commentText" required  class="block rounded border w-full my-2"></textarea>
      </label>
      <button type="submit" class="rounded-lg border-2 p-1 mr-2 bg-sky-500 text-white border-sky-200 hover:border-sky-700">Submit</button>
      <button @click="$parent.toggleComment()" class="rounded border p-1 hover:border-black">close</button>
    </form>`
});

App.component('formButton', {
  // display a toggle button
  props: ['userCommentId'], 
  data() {
    return {
      buttonText:  (this.userCommentId != null ? "reply" : "comment") // use v-bind:id="userCommentFieldId"
    }
  }, 
  template: `
    <button @click="$parent.toggleComment()" class="rounded border p-1" v-text=buttonText />
    `
  });

App.mount('#commentsApp')
