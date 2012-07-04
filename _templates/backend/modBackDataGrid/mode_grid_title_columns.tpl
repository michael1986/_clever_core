{?$columns && $rows}
    {@mode_grid_title_columns_start.tpl}
    {*$columns:$c}
        {@mode_grid_title_column_start.tpl}

            {?$c[order]}
                    {?$c[link_asc]}
                        <a href="{$c[link_asc]}" class="dgItemColContentTitleOrder">{$c[title]}</a>{?!$c[link_desc]}&nbsp;&uarr;&nbsp;{/?}
                    {/?}
                    {?!$c[link_asc] && $c[link_desc]}
                        <a href="{$c[link_desc]}" class="dgItemColContentTitleOrder">{$c[title]}</a>&nbsp;&darr;&nbsp;
                    {/?}
                {!}
                {$c[title]}
            {/?}
            {?$c[checkbox]}
                <input type="checkbox" id="{$prefix_data_grid}DataGridCheckAll">
            {/?}
        {@mode_grid_title_column_end.tpl}
    {/*}
    {@mode_grid_title_columns_end.tpl}
{/?}
