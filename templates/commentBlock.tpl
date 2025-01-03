<section>
	<h2 class="font-semibold text-sky-500">Comments</h2>
    <div class="text-sm" 
    id="commentsApp" 
    data-baseUrl="{$baseURL}" 
    data-apiUrl="{$apiURL}" 
    data-user="{$userId}" 
    data-apiKey="{$apiKey}" 
    data-submissionId="{$submissionId}" 
    data-publicationId="{$publication->getData('id')}"
    data-publicationVersion="{$publication->getData('version')}" 
    data-csrfToken="{$csrfToken}">
        <user-comments-block :user-comments="userComments"></user-comments-block>
        <div>
        <h4 class="hidden">Submit a comment</h4>
        {if $user}
        <span class="hidden">You are logged in as {$user->getFullName()}</span>
        <form-container :userCommentId=null></form-container>
        {else}
        <span>{translate key='plugins.generic.userComments.loggedOut' loginPageUrl="login"}</span>
        {/if}
        </div>
    </div>
</section>

<template id="userCommentsBlock">
<ul data-title="userComments" class="userComments" v-if="userComments && userComments.length">
  <li class="userComment" v-for="userComment in userComments" :key="userComment.id">
      <div class="bg-gray-100 p-2 rounded-lg my-1" :id="userComment.id">
        <template v-if="userComment.visible != '0'">
          {{ userComment.commentText }}
        </template>          
        <template v-else>
          <i>This comment has been unpublished due to violation of our code of conduct.</i>
        </template>          
        <span class="block text-gray-400">{{ userComment.commentDate }}</span>
        <div class="flex justify-between w-full text-gray-400">
          <div class="flex font-semibold">
            {{ userComment.userName }}
            <a class="pl-1 font-normal" :href="userComment.userOrcid">{{ userComment.userOrcid }}</a>
          </div>
          <div class="flex">
            <button name="flagComment" v-if="$root.user && userComment.flagged != true" @click="flagComment(userComment.id)">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6" role="img" aria-label="[title]">
                <title>flag this comment</title>
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5" />
              </svg>
            </button>
          </div>
          <div class="flex" v-if="userComment.flagged == true" data-isFlagged="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6" role="img" aria-label="[title]">
              <title>comment has been flagged {{ userComment.flaggedDate}}</title>
              <path fill-rule="evenodd" d="M3 2.25a.75.75 0 0 1 .75.75v.54l1.838-.46a9.75 9.75 0 0 1 6.725.738l.108.054A8.25 8.25 0 0 0 18 4.524l3.11-.732a.75.75 0 0 1 .917.81 47.784 47.784 0 0 0 .005 10.337.75.75 0 0 1-.574.812l-3.114.733a9.75 9.75 0 0 1-6.594-.77l-.108-.054a8.25 8.25 0 0 0-5.69-.625l-2.202.55V21a.75.75 0 0 1-1.5 0V3A.75.75 0 0 1 3 2.25Z" clip-rule="evenodd" />
            </svg>
          </div>
        </div>
        <form-container v-if="userComment.visible != '0'" :userCommentId=userComment.id></form-container>
      </div>     
    <div class="pl-3" v-if="userComment.children && userComment.children.length">
      <user-comments-block :user-comments="userComment.children"></user-comments-block>
    </div>
  </li>
</ul>
</template>

<template id="userCommentsForm">
<form @submit.prevent="$root.postData($parent, $event)" :data-userCommentId="userCommentId">
      <label>Your comment:
        <textarea type="text" name="commentText" required  class="block rounded border w-full my-2"></textarea>
      </label>
      <button type="submit" class="rounded-lg border-2 p-1 mr-2 bg-sky-500 text-white border-sky-200 hover:border-sky-700">Submit</button>
      <button @click="$parent.toggleComment()" class="rounded border p-1 hover:border-black">close</button>
</form>
</template>