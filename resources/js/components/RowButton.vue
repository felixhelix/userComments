<template>
    <div>
      <pkp-button :isPrimary="true" @click="openExampleDialog()">{{ __('common.edit') }}</pkp-button>
    </div>
</template> 

<script>
export default {
  props: ['item','apiurl','csrftoken','i18n'],
  mixins: [pkp.vueMixins.dialog], 
  data() {
    return {
      hideComment: {
        label: this.i18n.hide_flagged_comment,
        isPrimary: true,
        callback: () => {
          // the editor has decided to hide the flagged comment
          this.updateComment(true, false);
        }
      },
      removeFlag: {
        label:  this.i18n.remove_flag,
        isWarnable: true,
        callback: () => {
          // editor has decided to remove the flag
          this.updateComment(false, true);
        },
      },  
      cancel: {
        label: this.i18n.cancel,
        isWarnable: false,
        callback: () => {
          // user has cancelled. close the modal
          this.$modal.hide('flaggedComment');
        },
      },                 
    }
  },
  methods: {
    openExampleDialog() {
      // fetch the flagged comment from the API
      fetch(this.apiurl + 'getComment/' +  this.item.id)
        .then(response => response.json())
        .then(data => {
          if (data.flagged) {
            this.openDialog({
              name: "flaggedComment",
              title: "Flagged Comment #" + this.item.id,
              // message: `The flagged comment reads: '${data.commentText}'<br>The reason given is: '${data.flagNote}'`, // 
              // message: eval('`'+this.i18n.flag_info+'`'),
              message: this.i18n.flag_info_comment + ' \'' +  data.commentText + '\'<br>' + this.i18n.flag_info_note + ' \'' + data.flagNote + '\'' + (data.visible?'':'<div class="pkpButton--isWarnable">'+this.i18n.flag_info_hidden+'</div>'),
              actions: data.visible ? [
                this.hideComment,
                this.removeFlag,
                this.cancel,
              ] : [ 
                this.removeFlag,
                this.cancel, 
              ]
            });  
          } else {
            // the list does so far not update together with the item
            window.alert(this.i18n.alert_not_flagged);
          }       
        })
        .catch(error => {
          console.error('Error fetching data:', error);
        });
    },
    updateComment(flagged, visible) {
      // editor has decided to remove the flag
      fetch(this.apiurl + 'update', { 
        method: 'POST', 
        headers: {
          'Content-Type': 'application/json',
          'X-Csrf-Token': this.csrftoken,          
        },                    
        body: JSON.stringify({
          userCommentId: this.item.id,
          flagged: flagged,
          visible: visible,
        }),
      })
      // .then(response => response.json())
      // .then(data => {
      //   console.log(data);
      // });
      this.$modal.hide('flaggedComment');
    },
  },
};
</script>
