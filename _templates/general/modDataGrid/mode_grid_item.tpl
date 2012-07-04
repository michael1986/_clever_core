{?$columns}
    {@mode_grid_item_start.tpl}
    {*$columns:$column}
        {@mode_grid_item_column_start.tpl}
            {?$column[checkbox]}
                {@mode_grid_item_checkbox.tpl}
            {/?}
            {?$column[tpl_name]}
                <?php echo $this->_tpl($column['tpl_name'], array_merge($this->_vars, array('item' => $item))) ?>
            {!}
                {?$column[data_key]}
                    <?php echo $item[$column['data_key']]; ?>
                {/?}
            {/?}
            {?$column[controls]}
                {$item[controls_row]}
            {/?}
        {@mode_grid_item_column_end.tpl}
    {/*}
    {@mode_grid_item_end.tpl}
{!}
{@mode_grid_item_start.tpl}
    {@mode_grid_item_column_start.tpl}
        Use <b>'columns'</b> plugin during modDataGrid initialization OR create <b>'mode_grid_item.tpl'</b> template to visualize your data</p>
    {@mode_grid_item_column_end.tpl}
    {@mode_grid_item_column_start.tpl}
        {$item[controls_row]}
    {@mode_grid_item_column_end.tpl}
{@mode_grid_item_end.tpl}
{/?}
