<?php
if ($include_js && !defined('DATAGRID_JS')) {
    define('DATAGRID_JS', true);
    ?>
    {@popupmodal_js.tpl}
    {@jquery_cookie_js.tpl}
    <script type="text/javascript" src="{$_coreurl}js/datagrid.js"></script>
    <?php
}
?>
{@popupmodal_loading.tpl}
{@popupmodal_alert.tpl:id={$prefix_data_grid}DataGridAlert,ok={$lang[DIALOG_OK]}}
{@popupmodal_confirm.tpl:id={$prefix_data_grid}DataGridConfirm,yes={$lang[DIALOG_YES]},no={$lang[DIALOG_NO]}}
{@popupmodal_content.tpl}
<script type="text/javascript">
var {$js_instance};

$(function () {
    {$js_instance} = new DataGrid({
        prefixDataGrid: '{$prefix_data_grid}',
        linkLoadGrid: '{$link_ajax_load_grid}'
    });
});
</script>
