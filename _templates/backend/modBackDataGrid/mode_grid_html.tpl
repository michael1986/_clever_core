{?$plugin_overwhelm}
    {$plugin_overwhelm}
{!}

    <div style="display: table-cell">

        {?$title}
            <div class="grid01Title">{$title}</div>
        {/?}
        {$categories}
        <?php if (isset($filters) && $filters && ($rows || $filters->_vars['form_sent'] ||  $filters_apply)) { ?>
            {$filters}
        <?php } ?>
        {?$rows}
            {@mode_grid_pagination.tpl}
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                {@mode_grid_title_columns.tpl}
                {*$rows:$grid_row}
                    {*$grid_row[cols]:$item}
                        {@mode_grid_item.tpl}
                    {/*}
                {/*}
            </table>
            {@mode_grid_pagination.tpl}
            <div style="position: relative">
                <div id="{$prefix_data_grid}DataGridControlsRows" style="display: none; position: absolute; right: 0">
                    {@mode_grid_controls_rows.tpl}
                </div>
            </div>
            <script type="text/javascript" src="{$_coreurl}js/datagrid.design.js"></script>
            <script type="text/javascript">
                new DataGridDesign('{$prefix_data_grid}');
            </script>
        {!}
            <div class="grid01Empty">{$lang[NOTHING_FOUND]}</div>
        {/?}
        {?$controls_other}
            <div class="grid01ControlsOtherContainer">
                {$controls_other}
            </div>
        {/?}

    </div>
{/?}
