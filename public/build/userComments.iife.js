(function(){"use strict";function o(e,t,i,c,d,h,f,g){var n=typeof e=="function"?e.options:e;return t&&(n.render=t,n.staticRenderFns=i,n._compiled=!0),{exports:e,options:n}}const a={props:["item","apiurl","csrftoken","i18n"],mixins:[pkp.vueMixins.dialog],data(){return{hideComment:{label:this.i18n.hide_flagged_comment,isPrimary:!0,callback:()=>{this.updateComment(!0,!1)}},removeFlag:{label:this.i18n.remove_flag,isWarnable:!0,callback:()=>{this.updateComment(!1,!0)}},cancel:{label:this.i18n.cancel,isWarnable:!1,callback:()=>{this.$modal.hide("flaggedComment")}}}},methods:{openExampleDialog(){fetch(this.apiurl+"getComment/"+this.item.id).then(e=>e.json()).then(e=>{e.flagged?this.openDialog({name:"flaggedComment",title:"Flagged Comment #"+this.item.id,message:this.i18n.flag_info_comment+" '"+e.commentText+"'<br>"+this.i18n.flag_info_note+" '"+e.flagNote+"'"+(e.visible?"":'<div class="pkpButton--isWarnable">'+this.i18n.flag_info_hidden+"</div>"),actions:e.visible?[this.hideComment,this.removeFlag,this.cancel]:[this.removeFlag,this.cancel]}):window.alert(this.i18n.alert_not_flagged)}).catch(e=>{console.error("Error fetching data:",e)})},updateComment(e,t){fetch(this.apiurl+"update",{method:"POST",headers:{"Content-Type":"application/json","X-Csrf-Token":this.csrftoken},body:JSON.stringify({userCommentId:this.item.id,flagged:e,visible:t})}),this.$modal.hide("flaggedComment")}}};var s=function(){var t=this,i=t._self._c;return i("div",[t._v(" "+t._s(t.item.id)+" "),i("pkpbutton",{on:{click:function(c){return t.openExampleDialog()}}},[t._v("edit")])],1)},l=[],m=o(a,s,l);const r=m.exports;pkp.Vue.component("RowButton",r)})();
