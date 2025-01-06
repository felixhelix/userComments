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
      fetch(this.apiURL + 'getbypublication/' +  this.publicationId + "?" + new URLSearchParams({'apiToken': this.apiKey}))
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
          foreignCommentId: submitEvent.target.dataset.usercommentid ? submitEvent.target.dataset.usercommentid : null,
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
            if (item.foreignCommentId === null) {
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
    flagComment(usercommentid) {
      if (confirm("Do you want to flag this post?") == true) {
        // Make a POST request to the API
        fetch(this.$root.apiURL + 'flag', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Csrf-Token': this.$root.csrfToken,          
          },
          body: JSON.stringify({
            usercommentid: usercommentid,
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
  template: '#userCommentsBlock'
});

App.component('formContainer', {
  props: ['usercommentid'],
  data() {
    return {
      // If this is the root element, display the input form (commentForm), else display a toggle button (formButton)
      commentAction:  (this.usercommentid === null ? 'commentForm' : 'formButton')
    }
  },  
  methods: {
    toggleComment() {
      this.commentAction = (this.commentAction == 'formButton' ? 'commentForm' : 'formButton');
    }
  },
  template: `
    <div v-if="$root.user" :data-commentID=usercommentid>
      <component :is="commentAction" :usercommentid></component>
    </div>`
  });
  
App.component('commentForm', {
  // display the input form
  props: ['usercommentid'],
  data() {
    return {
      userCommentFieldId:  ("userComment_" + this.usercommentid) // use v-bind:id="userCommentFieldId"
    }
  }, 
  template: '#userCommentsForm'
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
