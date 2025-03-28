<template>
	<PkpButton :isPrimary="true" @click="openEditDialog">{{ t('common.edit') }}</PkpButton>
</template>

<script setup>
	const { useModal } = pkp.modules.useModal;
	const { openDialog } = useModal();
  const { useLocalize } = pkp.modules.useLocalize;
  const {t} = useLocalize();

  const props = defineProps({
    item: {type: Object, required: true},
    apiurl: {type: String, required: true},
    csrftoken: {type: String, required: true},
    i18n: {type: String, required: true},
  });  

  const actions = {
      hideComment: {
        label: props.i18n.hide_flagged_comment,
        isPrimary: true,
        callback: (close) => {
          // the editor has decided to hide the flagged comment
          updateComment(true, false);
          close();
        }
      },
      removeFlag: {
        label:  props.i18n.remove_flag,
        isWarnable: true,
        callback: (close) => {
          // editor has decided to remove the flag
          updateComment(false, true);
          close();
        },
      },  
      cancel: {
        label: props.i18n.cancel,
        isWarnable: false,
        callback: (close) => {
          // user has cancelled. close the modal
          close();
        },
      },                 
    }

	function openEditDialog() {
    // fetch the flagged comment from the API
    fetch(props.apiurl + 'getComment/' +  props.item.id)
    .then(response => response.json())
    .then(data => {
      if (data.flagged) {
        openDialog({
          name: "flaggedComment",
          title: "Flagged Comment #" + props.item.id,
          message: props.i18n.flag_info_comment + ' \'' +  data.commentText + '\'<br>' + props.i18n.flag_info_note + ' \'' + data.flagNote + '\'' + (data.visible?'':'<div class="pkpButton--isWarnable">'+props.i18n.flag_info_hidden+'</div>'),
          actions: data.visible ? [
                actions.hideComment,
                actions.removeFlag,
                actions.cancel,
              ] : [ 
              actions.removeFlag,
              actions.cancel, 
              ],
          close: () => {
            // dialog has been closed
          },
          modalStyle: 'primary',
        });
      };
    });
	};

  function updateComment(flagged, visible) {
    // editor has decided to remove the flag
    fetch(props.apiurl + 'update', { 
      method: 'POST', 
      headers: {
        'Content-Type': 'application/json',
        'X-Csrf-Token': props.csrftoken,          
      },                    
      body: JSON.stringify({
        commentId: props.item.id,
        flagged: flagged,
        visible: visible,
      }),
    })
  };
</script>