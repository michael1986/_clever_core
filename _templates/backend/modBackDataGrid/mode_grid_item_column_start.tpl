<?php
$__tmp_name = '__' . $prefix_data_grid . 'column_index';

if (isset($column) && is_array($column)) {
    if (isset($column['width'])) {
        $width = $column['width'];
    }
    else {
        $width = false;
    }
    if (isset($column['text_align'])) {
        $text_align = $column['text_align'];
    }
    else {
        $text_align = false;
    }
}
else if (isset($columns) && isset($columns[$GLOBALS[$__tmp_name]])) {
    $width = (isset($columns[$GLOBALS[$__tmp_name]]['width'])) ? $columns[$GLOBALS[$__tmp_name]]['width'] : false;
    $text_align = (isset($columns[$GLOBALS[$__tmp_name]]['text_align'])) ? $columns[$GLOBALS[$__tmp_name]]['text_align'] : false;
}
?>

<td{?$colspan} colspan="{$colspan}"{/?} class="grid01ItemColumn {$prefix_data_grid}DataGridTdContent">
    <div{?$width||$text_align} style="{?$width}width: {$width}px;"{/?}{?$text_align}text-align: {$text_align};{/?}"{/?}>