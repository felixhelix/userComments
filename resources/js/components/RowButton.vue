<template>
    <div>
      {{ itemid }}
      <pkpbutton @click="openExampleDialog()">edit</pkpbutton>
    </div>
</template> 

<script>
export default {
  props: ['itemid','apiurl','csrftoken','locale'],
  mixins: [pkp.vueMixins.dialog], 
  data() {
    return {};
  },
  methods: {
    openExampleDialog() {
      // fetch the flagged comment from the API
      fetch(this.apiurl + 'getComment/' +  this.itemid)
        .then(response => response.json())
        .then(data => {
          console.log(data);
          this.openDialog({
            name: "flaggedComment",
            title: "Flagged Comment #" + this.itemid,
            message: "The flagged comment reads: '" + data.commentText[this.locale] + "'<br>The reason given is: '" + data.flagText[this.locale] + "'",
            actions: [
              {
                label: "Disable Flagged Comment",
                isPrimary: true,
                callback: () => {
                  // an editor has decided to disable the comment
                  fetch(this.apiurl + 'update', { 
                    method: 'POST', 
                    headers: {
                      'Content-Type': 'application/json',
                      'X-Csrf-Token': this.csrftoken,          
                    },                    
                    body: JSON.stringify({
                      userCommentId: this.itemid,
                      visible: false,
                      flagged: true,
                    }),
                  })
                  .then(response => response.json())
                  .then(data => {
                    console.log(data);
                  });
                  this.$modal.hide('flaggedComment');
                },
              },
              {
                label: "Remove Flag",
                isWarnable: true,
                callback: () => {
                  // user has cancelled. close the modal
                  this.$modal.hide('flaggedComment');
                },
              },              
              {
                label: "Cancel",
                isWarnable: false,
                callback: () => {
                  // user has cancelled. close the modal
                  this.$modal.hide('flaggedComment');
                },
              },
            ],
          });          
        })
        .catch(error => {
          console.error('Error fetching data:', error);
        });
    },
  },
};
</script>
