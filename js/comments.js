// var app = new Vue({
//     el: '#commentsApp',
//     data: {
//       "userComments": [
//         {"id": "1",
//          "commentText": "first comment"
//         },
//         {"id": "2",
//           "commentText": "second comment"
//         },
//         {"id": "3",
//           "commentText": "third comment"
//         },        
//       ]
//     }
//   });

new Vue({
  el: '#commentsApp',
  data: {
    userComments: [],
    dataFetched: false,
    commentText: '',
    replyText: ''
  },
  mounted() {    
    // Fetch data from the API when the component is mounted
    this.fetchData();
  },
  methods: {
    fetchFormData() {
      // The token is embedded in the form template
      this.csrfToken = document.getElementById('csfrToken').value;
      // An API key is needed for unauthenticated access
      this.apiKey = document.getElementById('commentsApp').dataset.apiKey;
      // this.submissionId = document.getElementById('submissionId').value;
      this.foreignCommentId = document.getElementById('foreignCommentId').value;      
    },
    fetchData() {
      this.location = window.location.href;
      this.submissionId = this.location.split("/")[this.location.split("/").length -1];
      // Make a GET request to the API
      fetch('http://localhost/ops3/index.php/socios/api/v1/userComments/getCommentsBySubmission/' + this.submissionId + "?" + new URLSearchParams({
        'apiToken': "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.IjFmMzEzNTRmMGFkODQyM2Y5ZTAyYTNkYTBkYjNhNTI3Y2EyOWY0N2Qi.5-ql-FFy5Pr3UAiE3bfnW7G1gOqbXT1u7gEB3mHl2Q4"
      }))
        .then(response => response.json())
        .then(data => {
          // Set the fetched data to the component state
          this.userComments = data;
          this.dataFetched = true;
          // Clear the input fields
          this.commentText = '';
          this.replyText = '';
        })
        .catch(error => {
          console.error('Error fetching data:', error);
        });
    },
    postReply() {

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
          foreignCommentId: null,
          completed: false
        }),
      })
        .then(response => response.json())
        .then(data => {
          // Handle the response if needed
          console.log('Data posted successfully:', data);

          // Fetch data again to update the displayed list
          this.fetchData();
        })
        .catch(error => {
          console.error('Error posting data:', error);
        });
    }
  }
});
  