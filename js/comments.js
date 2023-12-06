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
    postDataInput: ''
  },
  mounted() {
    // Fetch CSRF token when the component is mounted
    this.fetchFormData();    
    // Fetch data from the API when the component is mounted
    this.fetchData();
  },
  methods: {
    fetchFormData() {
      // The token is embedded in the form template
      this.csrfToken = document.getElementById('csfrtoken').value;
      this.submissionId = document.getElementById('submissionId').value;
      this.foreignCommentId = document.getElementById('foreignCommentId').value;      
    },
    fetchData() {
      // Make a GET request to the API
      fetch('http://localhost/ops3/index.php/socios/api/v1/userComments/getCommentsBySubmission/' + this.submissionId , {
        headers: {
          'X-Csrf-Token': this.csrfToken,
        },
      })
        .then(response => response.json())
        .then(data => {
          // Set the fetched data to the component state
          this.userComments = data;
          this.dataFetched = true;
          // Clear the input field
          this.commentText = '';
        })
        .catch(error => {
          console.error('Error fetching data:', error);
        });
    },
    postData() {
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
        })
        .catch(error => {
          console.error('Error posting data:', error);
        });
    }
  }
});
  