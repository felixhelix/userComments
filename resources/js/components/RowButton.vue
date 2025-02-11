<template>
    <div>
      {{ item.id }}
      <pkpbutton @click="openExampleDialog()">edit</pkpbutton>
    </div>
</template> 

<script>
export default {
  props: ['item','apiurl','csrftoken','locale'],
  mixins: [pkp.vueMixins.dialog], 
  data() {
    return {};
  },
  methods: {
    openExampleDialog() {
      // fetch the flagged comment from the API
      fetch(this.apiurl + 'getComment/' +  this.item.id)
        .then(response => response.json())
        .then(data => {
          console.log(data);
          this.openDialog({
            name: "flaggedComment",
            title: "Flagged Comment #" + this.item.id,
            message: "The flagged comment reads: '" + data.commentText[this.locale] + "'<br>The reason given is: '" + data.flagText[this.locale] + "'",
            actions: [
              {
                label: this.item.visible ? "Disable Flagged Comment" : "Re-enable Comment",
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
                      userCommentId: this.item.id,
                      visible: this.item.visible ? false : true,
                      flagged: this.item.visible ? true : false, // if the item is re-enabled, the comment is unflagged also
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
