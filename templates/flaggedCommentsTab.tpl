<tab id="flaggedUserComments" label="{translate key='plugins.generic.userComments.flaggedCommentsTitle'}">

    <list-panel 
        v-bind="components.{$smarty.const.FLAGGED_COMMENTS_LIST}"
        :items="items"
        :apiurl="apiurl"
        :preprinturl="preprinturl"
        :csrftoken="csrftoken"
        :i18n="i18n"
        title="{translate key='plugins.generic.userComments.flaggedCommentsTitle'}"
        description="{translate key='plugins.generic.userComments.listFlaggedComments'}"
    >

        <template v-slot:item-title="{ldelim}item{rdelim}">
            <pkp-badge 
                class="pkpBadge--isWarnable" 
                label="original comment is hidden" 
                :isWarnable="true" 
                v-if="item.visible == false">
                {translate key='plugins.generic.userComments.flaggedCommentHidden'}
            </pkp-badge>
            #{{ item.id }} {translate key='plugins.generic.userComments.commentBy'} <a :href="item.userOrcid">{{ item.userName }}</a> {translate key='plugins.generic.userComments.onPublication'} '{{ item.submissionTitle }}'
        </template>

        <template v-slot:item-subtitle="{ldelim}item{rdelim}">
            {translate key='plugins.generic.userComments.flaggedBy'} <a :href="item.flaggedBy._data.orcid">{{ item.flaggedBy._data.userName }}</a> {translate key='plugins.generic.userComments.flaggedAt'} {{ item.dateFlagged }}
            <div>
                <a :href="preprinturl+'/'+item.publicationId">{translate key='plugins.generic.userComments.publicationPage'}</a>
            </div>
        </template>

        <template v-slot:item-actions="{ldelim}item{rdelim}">
            <row-button :item="item" :apiurl="apiurl" :csrftoken="csrftoken" :i18n="i18n" />
        </template>

    </list-panel>

</tab>

