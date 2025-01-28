<tab id="flaggedUserComments" label="Flagged Comments">

<div class="listPanel__header">
    <div class="pkpHeader -isOneLine">
        <span class="pkpHeader__title"><h2>Flagged Comments</h2></span>
    </div>
</div>

<!-- v-bind="components.{$smarty.const.FLAGGED_COMMENTS_LIST}"
@set="set"
:items="items"
-->

<list-panel
    v-bind="components.{$smarty.const.FLAGGED_COMMENTS_LIST}"
    @set="set"
></list-panel>

</tab>