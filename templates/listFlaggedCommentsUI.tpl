<tab id="flaggedUserComments" label="Flagged Comments">

<div class="listPanel__header">
    <div class="pkpHeader -isOneLine">
        <span class="pkpHeader__title"><h2>Flagged Comments</h2></span>
    </div>
</div>

<list-panel 
    v-bind="components.{$smarty.const.FLAGGED_COMMENTS_LIST}"
    :items="items"
    :apiurl="apiurl"
    :csrftoken="csrftoken"
    :locale="locale"
>

<!-- list-panel :items="items" -->
    <template v-slot:item-actions="{ldelim}item{rdelim}">
        <!-- pkp-button @click="$modal.show('userCommentForm', item)">Edit</pkp-button -->
        <row-button class="button" :item="item" :apiurl="apiurl" :csrftoken="csrftoken" :locale="locale">Edit</rowbutton>
    </template>
</list-panel>

</tab>

