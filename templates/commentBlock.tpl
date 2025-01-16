<section class="item">
	<h2>Comments</h2>
    <div id="commentsApp" 
    data-baseUrl="{$baseURL}" 
    data-apiUrl="{$apiURL}" 
    data-user="{$userId}" 
    data-apiKey="{$apiKey}" 
    data-submissionId="{$submissionId}" 
    data-publicationId="{$publication->getData('id')}"
    data-publicationVersion="{$publication->getData('version')}" 
    data-csrfToken="{$csrfToken}">
        <div>
        <h4 class="hidden">{translate key='plugins.generic.userComments.sectionheader'}</h4>
        {if $user}
        <form-container :usercommentid=null></form-container>
        {else}
        <span>{translate key='plugins.generic.userComments.loggedOut' loginPageUrl="login"}</span>
        {/if}
        </div>
        <user-comments-block :user-comments="userComments" :usercommentid=null :comments-ref="commentsRef"></user-comments-block>
    </div>
</section>

<template id="userCommentsBlock">
<ul data-title="userComments" 
    class="userComments" 
    :id="`commentList${ usercommentid }`"
    :ref="(el) => (commentsRef[`commentList${ usercommentid }`] = el)"
    v-if="userComments && userComments.length">
    <li v-for="userComment in userComments" 
        :key="userComment.id"
        :ref="`commentBlock${ usercommentid }`">
        <div class="userComment" 
            :id="`comment${ userComment.id }`" 
            :ref="(el) => (commentsRef[userComment.id] = el)"
            >
            <div class="commentBlock">
                <div>
                    <template v-if="userComment.visible != '0'">
                    {{ userComment.commentText }}
                    </template>          
                    <template v-else>
                    <i>{translate key='plugins.generic.userComments.hidden'}</i>
                    </template>   
                    <div class="secondary">{{ userComment.commentDate }}</div> 
                </div>
                <div v-if="$root.user && userComment.flagged != true">
                    <button name="flagComment" @click="userComment.showFlagForm = true">
                    <!--- button name="flagComment" @click="flagComment(userComment.id)" -->
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="flagComment" role="img" aria-label="[title]">
                        <title>{translate key='plugins.generic.userComments.flag'}</title>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5" />
                    </svg>
                    </button>
                </div>
                <div v-if="userComment.flagged == true" data-isFlagged="true">
                    {capture name=flagdate assign=flagdate} {{ userComment.flaggedDate }} {/capture} 
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="flagComment" role="img" aria-label="[title]">
                    <title>{translate key='plugins.generic.userComments.flagged' flagdate=$flagdate}</title>
                    <path fill-rule="evenodd" d="M3 2.25a.75.75 0 0 1 .75.75v.54l1.838-.46a9.75 9.75 0 0 1 6.725.738l.108.054A8.25 8.25 0 0 0 18 4.524l3.11-.732a.75.75 0 0 1 .917.81 47.784 47.784 0 0 0 .005 10.337.75.75 0 0 1-.574.812l-3.114.733a9.75 9.75 0 0 1-6.594-.77l-.108-.054a8.25 8.25 0 0 0-5.69-.625l-2.202.55V21a.75.75 0 0 1-1.5 0V3A.75.75 0 0 1 3 2.25Z" clip-rule="evenodd" />
                    </svg>
                </div> 
                <flag-modal :usercommentid=userComment.id :usercomment=userComment v-if="userComment.showFlagForm"></flag-modal>
            </div>                     
            <div class="authorBlock">
                <span class="authorName">{{ userComment.userName }}</span>
                <a class="authorOrcid" :href="userComment.userOrcid">{{ userComment.userOrcid }}</a>
                <span class="authorAffiliation">{{ userComment.userAffiliation }}</span>
            </div>
            <form-container v-if="userComment.visible != '0'" :usercommentid=userComment.id></form-container>    
        </div>
        <div class="replies" v-if="userComment.children && userComment.children.length">
            <user-comments-block 
                :user-comments="userComment.children" 
                :usercommentid=userComment.id
                :comments-ref="commentsRef"></user-comments-block>
        </div>
  </li>
</ul>
</template>

<template id="userCommentForm">
<form @submit.prevent="$root.postData($parent, $event)" 
    :data-usercommentid="usercommentid"
    class="cmp_form">
    <label class="sr-only" for="commentTextArea">{translate key='plugins.generic.userComments.label'}</label>
    <textarea 
        :usercommentid="usercommentid"    
        type="text"
        name="commentText" 
        id="commentTextArea" 
        class="commentTextArea" 
        placeholder="{translate key='plugins.generic.userComments.placeholder'}"
        required
        v-focus="usercommentid"></textarea>
    <div class="buttons">
        <button type="submit" class="submit">{translate key='plugins.generic.userComments.submit'}</button>
        <a v-if="this.usercommentid !== null" @click="$parent.toggleComment()" class="linkbutton">{translate key='plugins.generic.userComments.close'}</a>
    </div>
</form>
</template>

<template id="flagModal">
<form @submit.prevent="submitflag($parent, $event)" 
    :data-usercommentid="usercommentid"
    class="cmp_form">
    <div class="modal-backdrop">
        <div class="modal">
            <textarea 
                :usercommentid="usercommentid"
                type="text"
                name="flagtext" 
                id="flagTextArea" 
                class="commentTextArea" 
                placeholder="{translate key='plugins.generic.flagText.placeholder'}"
                required
                v-focus="usercommentid"></textarea>
            <div class="buttons">
                <button type="submit" class="submit" name="submitflag">{translate key='plugins.generic.userComments.flag'}</button>
                <a v-if="this.usercommentid !== null" @click="cancelflag" class="linkbutton">{translate key='plugins.generic.userComments.close'}</a>
            </div>
        </div>
    </div>                
</form>
</template>

<template id="userCommentsToggle">
<div class="buttons linkbuttons">
    <a @click="$parent.toggleComment()" class="linkbutton">{translate key='plugins.generic.userComments.reply'}</a>
</div>
</template>