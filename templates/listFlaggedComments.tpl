{extends file="layouts/backend.tpl"}

{block name="page"}

<div class="listPanel__header">
    <div class="pkpHeader -isOneLine">
        <span class="pkpHeader__title"><h2>{$pageTitle}</h2></span>
    </div>
</div>

<div class="listPanel__body">
    <div class="listPanel__items">
        <ul class="listPanel__itemsList">
            {if $items|@count}
            {foreach $items as $item}
            <li class="listPanel__item">
                <div class="listPanel__itemSummary">
                    <div class="listPanel__itemIdentity"style="background-color: lightgrey;
                    padding: 0.5rem;
                    border-radius: 0.5rem;" >
                        <div>
                        Published: {$item.commentDate} <br> 
                        Flagged: {$item.flaggedDate}
                        </div>
                        <div>
                        Id: {$item.id}  
                        Submission-Id: {$item.submissionId} 
                        Foreign-Comment-Id: {$item.foreignCommentId}
                        </div>
                        <div>
                        User: {$item.userName} ({$item.userEmail})
                        <div style="background-color: white; 
                        padding: 0.5rem;
                        border-radius: 0.5rem;" >
                            {$item.commentText}
                        </div>
                        <div>
                            <a href="http://localhost/ops3/index.php/socios/preprint/view/{$item.submissionId}">View in context</a>
                        </div>
                        <div class="listPanel_itemActions">
                            <pkp-button element="a" href="">
                                Hide Comment
                            </pkp-button>
                        </div>
                    </div>
                </div>
            </li>
            {/foreach}
            {else}
            <div>
                There are no flagged comments.
            </div>
            {/if}
        </ul>
    </div>
</div>


{/block}