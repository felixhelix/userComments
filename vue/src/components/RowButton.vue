<template>
    <div>
      {{ itemid }}
      <pkpbutton @click="openExampleDialog()">edit</pkpbutton>
    </div>
</template> 

<script>
export default {
  props: ['itemid'],
  mixins: [pkp.vueMixins.dialog], 
  data() {
    return {};
  },
  methods: {
    openExampleDialog() {
      this.openDialog({
        name: "example",
        title: "Submit Article",
        message: "Are you sure you want to submit this article?",
        actions: [
          {
            label: "Yes",
            isPrimary: true,
            callback: () => {
              // user has confirmed
            },
          },
          {
            label: "No",
            isWarnable: true,
            callback: () => {
              // user has cancelled. close the modal
            },
          },
        ],
      });
    },
  },
};
</script>
