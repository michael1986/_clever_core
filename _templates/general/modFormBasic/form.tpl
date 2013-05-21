<?php if (!defined('FORM_JS')) { define('FORM_JS', true); ?>
{@jquery_js.tpl}
<script type="text/javascript" src="{$_coreurl}js/form.js"></script>
<?php } ?>
<script type="text/javascript">
var {$js_instance};
$(function () {
    {$js_instance} = new Form({
        ajax: '{$ajax}',
        ajaxHandler: '{$ajax_handler}',
        prefixForm: '{$prefix_form}',
        formId: '{$id}'
    });
});
</script>
<div id="{$prefix_form}Form" style="display: inline-block; width: 100%">
    {@form_html.tpl}
</div>
