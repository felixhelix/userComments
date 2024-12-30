<!-- templates/settings.tpl -->
<script>
	$(function() {ldelim}
		$('#commentsSettings').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form
  class="pkp_form"
  id="commentsSettings"
  method="POST"
  action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}"
>
    <!-- Always add the csrf token to secure your form -->
	{csrf}

    {fbvFormArea}
		{fbvFormSection}
			{fbvElement
                type="text"
                id="apiKey"
                value=$apiKey
                label="plugins.generic.userComments.apiKey"
            }
		{/fbvFormSection}
    {/fbvFormArea}
    {fbvFormButtons submitText="common.save"}
</form>

