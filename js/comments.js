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
    publicationId: '',
    csrfToken: '',
    foreignCommentId: null,
    commentAction: 'formButton',
    commentsRef: Vue.ref({})
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
    fetchData(callback = null) {
      this.baseURL = document.getElementById('commentsApp').dataset.baseurl;
      this.apiURL = document.getElementById('commentsApp').dataset.apiurl;
      this.publicationId = document.getElementById('commentsApp').dataset.publicationid;
      // An API key is needed for unauthenticated GET requests
      this.apiKey = document.getElementById('commentsApp').dataset.apikey;
      // Make a GET request to the API
      // fetch(this.baseURL + '/index.php/socios/api/v1/userComments/getCommentsByPublication/' + this.publicationId + "?" + new URLSearchParams({
      //  'apiToken': this.apiKey
      // }))
      fetch(this.apiURL + 'getbypublication/' +  this.publicationId + "?" + new URLSearchParams({'apiToken': this.apiKey}))
        .then(response => response.json())
        .then(data => {
          // Set the fetched data to the component state:
          // Since the list is bound, vue updates the display as well
          this.userComments = this.buildTree(data).children;
          this.dataFetched = true;
          // Clear the input fields
          this.commentText = '';
        })
        .catch(error => {
          console.error('Error fetching data:', error);
        });
        if (callback) {
          Vue.nextTick(callback());
        }
    },
    postData(parentComponent, submitEvent) {
      // get the form value
      commentTextField =  submitEvent.target[name = 'commentText'];
      foreignCommentId = submitEvent.target.dataset.usercommentid ? Number(submitEvent.target.dataset.usercommentid) : null
      // Make a POST request to the API
      fetch(this.$root.apiURL + 'add', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Csrf-Token': this.csrfToken,          
        },
        body: JSON.stringify({
          commentText: commentTextField.value,
          publicationId: this.publicationId,
          submissionId: this.submissionId,
          foreignCommentId: foreignCommentId,
          completed: false
        }),
      })
        .then(response => response.json())
        .then(data => {
          // Handle the response if needed
          // Add new comment to list
          newComment = {"id":data.id,
            "foreignCommentId":foreignCommentId,
            "userName":data.userName,
            "userOrcid":data.userOrcid,
            "userAffiliation":data.userAffiliation,
            "commentDate":data.commentDate,
            "commentText":commentTextField.value,
            "flaggedDate":null,
            "flagged":0,
            "showFlagForm": false,
            "visible":1,
            "children":Array()};
          if (foreignCommentId == null) {
            this.userComments.push(newComment);
          } else {
            parentComment = this.searchTree(this.userComments, foreignCommentId)
            parentComment.children.push(newComment);
          }
          // close the comment field if this is a reply
          if (foreignCommentId !== null) {
            parentComponent.toggleComment();
          } else {
            // just clear the textfield
            commentTextField.value = "";
          }
          // Vue.nextTick(this.highlightNewElement.bind(null, data.id));
        })
        .catch(error => {
          console.error('Error posting data:', error);
        });
    },
    searchTree(elements, id) {
      found = null;
      elements.forEach((element) => {   
        if (found != null) { return found }
        if (Number(element.id) == Number(id)) { 
          found = element;
        } 
        else {
          found = this.searchTree(element.children, id)
        }
      });
      return found; 
    },
    buildTree(nodes) {
      tree = {
        id: null,
        children: Array()
      };
      for (const item of nodes) 
        {
            if (item.foreignCommentId === null) {
                // we only need the top level nodes
                tree.children.push(this.returnChildnodes(item, nodes));
            }
        }
        return tree;
    },
    returnChildnodes(item, nodes, childnodes = Array()) 
    {   
        // item is a comment node
        // nodes is a flat array of all comments
        // childnodes are nodes for which foreignCommentId == item.id 
        childnodes_ = nodes.filter((node) => node.foreignCommentId == item.id );
        for (childnode of childnodes_) {
          childnodes.push(this.returnChildnodes(childnode, nodes));
        }
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
  // This displays the comment text, meta data, flag or flagging button,
  // and is a container for either the form or the toggle button and replies
  props: ['userComments','usercommentid','commentsRef'],
  data() {
    return {
      commentAction: 'formButton',
      editComment: false,
    }
  },
  methods: {
    toggleComment() {
      this.commentAction = (this.commentAction == 'formButton' ? 'userCommentForm' : 'formButton');
    },
  },
  template: '#userCommentsBlock'
});

App.component('flagModal', {
  props: ['usercommentid', 'usercomment'],  
  methods: {
    cancelflag() {
      this.usercomment.showFlagForm = false;
    }, 
    submitflag(parentComponent, submitEvent) {
      // get the form value
      flagTextField =  submitEvent.target[name = 'flagnote'];
      userCommentId = submitEvent.target.dataset.usercommentid ? Number(submitEvent.target.dataset.usercommentid) : null      
      // Make a POST request to the API
      fetch(this.$root.apiURL + 'flag', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Csrf-Token': this.$root.csrfToken,          
        },
        body: JSON.stringify({
          userCommentId: userCommentId,
          publicationId: Number(this.$root.publicationId),
          flagNote: flagTextField.value,
          completed: false
        }),
      })
        .then(this.cancelflag())
        .then(response => response.json())
        .then(data => {
          // Handle the response if needed
          console.log('Comment flagged successfully:', data);
          // Find the comment and apply the flag
          flaggedComment = this.$root.searchTree(this.$root.userComments, data.id);
          flaggedComment.flagged = true;
          flaggedComment.flaggedDate = data.date;
        })
        .catch(error => {
          console.error('Error posting data:', error);
        });      
    },
  },        
  template: '#flagModal'
});

App.component('formContainer', {
  // this is a placeholder for either the form for or the toggle button
  // only visible if the parent comment is not disabled
  props: ['usercommentid'],
  data() {
    return {
      // If this is the root element, display the input form (commentForm), else display a toggle button (formButton)
      commentAction:  (this.usercommentid === null ? 'userCommentForm' : 'formButton')
    }
  },  
  methods: {
    toggleComment() {
      this.commentAction = (this.commentAction == 'formButton' ? 'userCommentForm' : 'formButton');
    }
  },
  template: `
    <div v-if="$root.user" :data-commentID=usercommentid>
      <component :is="commentAction" :usercommentid></component>
    </div>`
  });
  
App.component('userCommentForm', {
  // display the input form
  props: ['usercommentid'],
  data() {
    return {
      userCommentFieldId:  ("userComment_" + this.usercommentid) // use v-bind:id="userCommentFieldId"
    }
  }, 
  template: '#userCommentForm'
});

App.component('formButton', {
  // display a toggle button
  props: {
    usercommentid: {
      type: [Number, null],
      default: null,
    }
  },
  // data() {
  //   return {
  //     buttonText:  (this.usercommentid === null ? "comment" : "reply") // use v-bind:id="userCommentFieldId"
  //   }
  // }, 
  // template: `
  //   <button @click="$parent.toggleComment()" class="rounded border p-1" v-text=buttonText />
  //   `
  template: '#userCommentsToggle'
  });

App.mount('#commentsApp')

// Register a global custom directive called `v-focus`
App.directive('focus', (el, binding) => {
  // When the bound element is mounted into the DOM...
  // Focus the element
  if (binding.value != null) {
    el.focus();
  };
})