{?$columns && $rows}
    {@mode_grid_title_columns_start.tpl}
    {*$columns:$c}
        {@mode_grid_title_column_start.tpl}
            {$c[title]}
            {?$c[order]}
                {?$c[link_asc]}
                    <a href="{$c[link_asc]}" class="dgItemColContentTitleOrder">[&nbsp;&darr;&nbsp;]</a>
                {!}
                    &nbsp;&darr;&nbsp;
                {/?}
                {?$c[link_desc]}
                    <a href="{$c[link_desc]}" class="dgItemColContentTitleOrder">[&nbsp;&uarr;&nbsp;]</a>
                {!}
                    &nbsp;&uarr;&nbsp;
                {/?}
            {/?}
            {?$c[checkbox]}
                <input type="checkbox" id="{$prefix_data_grid}DataGridCheckAll">
            {/?}
        {@mode_grid_title_column_end.tpl}
    {/*}
    {@mode_grid_title_columns_end.tpl}
{/?}
