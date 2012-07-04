{@mode_grid_item_start.tpl}

    {@mode_grid_item_column_start.tpl}
        <div class="rowData" style="margin-left: <?php echo ($item['deep']) * 50; ?>px;"
            {?$item[page_module]} page_module="true"{/?} is_visible="true"
            page_id="{$item[page_id]}" 
            page_parent_id="{$item[page_parent_id]}">

            <table class="backendLayout">
            <tr>
                <td style="padding: 0 10px 0 0;">
                    <div class="rowIcon"></div>
                </td>
                {?$item[controls_row].controls[move_up]}<td>{$item[controls_row].controls[move_up]}</td>{/?}
                {?$item[controls_row].controls[move_down]}<td>{$item[controls_row].controls[move_down]}</td>{/?}
                <td style="padding: 0 0 0 10px;">
                    {$item[page_title]}{?$item[page_module]}<br><span style="color: #aaa">{$item[page_module]}</span>{/?}
                </td>
            </tr>
            </table>
        </div>
    {@mode_grid_item_column_end.tpl}

    {@mode_grid_item_column_start.tpl}
        <table cellpadding="2" cellspacing="2" border="0">
        <tr>
            {?$item[controls_row].controls[cut]}<td>{$item[controls_row].controls[cut]}</td>{/?}
            {?$item[controls_row].controls[cut_cancel]}<td>{$item[controls_row].controls[cut_cancel]}</td>{/?}
            {?$item[controls_row].controls[paste_under]}<td style="padding-left: 20px">{$item[controls_row].controls[paste_under]}</td>{/?}
            {?$item[controls_row].controls[paste_before]}<td>{$item[controls_row].controls[paste_before]}</td>{/?}
            {?$item[controls_row].controls[paste_after]}<td>{$item[controls_row].controls[paste_after]}</td>{/?}
        </tr>
        </table>
    {@mode_grid_item_column_end.tpl}

    {@mode_grid_item_column_start.tpl}
        <table cellpadding="2" cellspacing="2" border="0">
        <tr>
            {?$item[controls_row].controls[details]}<td>{$item[controls_row].controls[details]}</td>{/?}
            {?$item[controls_row].controls[activate]}<td>{$item[controls_row].controls[activate]}</td>{/?}
            {?$item[controls_row].controls[deactivate]}<td>{$item[controls_row].controls[deactivate]}</td>{/?}
            {?$item[controls_row].controls[edit]}<td>{$item[controls_row].controls[edit]}</td>{/?}
            {?$item[controls_row].controls[edit_page]}<td>{$item[controls_row].controls[edit_page]}</td>{/?}
            {?$item[controls_row].controls[view] && $item[controls_row].controls[deactivate]}<td>{$item[controls_row].controls[view]}</td>{/?}
            {?$item[controls_row].controls[delete]}<td>{$item[controls_row].controls[delete]}</td>{/?}
        </tr>
        </table>
    {@mode_grid_item_column_end.tpl}

    {@mode_grid_item_column_start.tpl}
        {@mode_grid_item_checkbox.tpl}
    {@mode_grid_item_column_end.tpl}

{@mode_grid_item_end.tpl}
