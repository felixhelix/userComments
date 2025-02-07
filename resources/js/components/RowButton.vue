<template>
    <div>
      {{ itemid }}
      <pkpbutton @click="openExampleDialog()">edit</pkpbutton>
    </div>
</template> 

<script>
export default {
  props: ['itemid','apiurl'],
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
            message: "The flagged comment reads: '" + data.commentText + "'<br>The reason given is: '" + data.flagText + "'",
            actions: [
              {
                label: "Disable Flagged Comment",
                isPrimary: true,
                callback: () => {
                  // user has confirmed
                },
              },
              {
                label: "Cancel",
                isWarnable: true,
                callback: () => {
                  // user has cancelled. close the modal
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
