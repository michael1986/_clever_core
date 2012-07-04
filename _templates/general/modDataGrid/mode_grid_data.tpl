{?$rows}
    <div style="display: table-cell">
        <table cellpadding="0" cellspacing="0" border="0">
        {@mode_grid_title_columns.tpl}
        {*$rows:$grid_row}
            {*$grid_row[cols]:$item}
                {@mode_grid_item.tpl}
            {/*}
        {/*}
        </table>
        <div style="position: relative">
            <div id="{$prefix_data_grid}DataGridControlsRows" style="display: none; position: absolute; right: 0">
                {@mode_grid_controls_rows.tpl}
            </div>
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