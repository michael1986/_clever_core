<?php
if (!isset($apply_to)) {
    $apply_to = false;
}
if (!isset($action)) {
    $action = false;
}
// design
if (isset($class) && $class) {
    $simple = true;
}
else {
    $simple = false;

    if (!isset($size)) {
        $size = 'normal';
    }
    if (!isset($icon)) {
        if (_file_exists(_LOCALE_PATH . 'images/icon_' . $size . '_' . $action . '.png')) {
            $icon = $_baseurl . 'images/icon_' . $size . '_' . $action . '.png';
        }
        else if (_file_exists(_ENGINE_PATH . 'images/icon_' . $size . '_' . $action . '.png')) {
            $icon = $_coreurl . 'images/icon_' . $size . '_' . $action . '.png';
        }
        else {
            $icon = false;
        }
    }

    $icon_class = 'controlIcon' . ucfirst($size);
    $title_class = 'controlTitle' . ucfirst($size);
    if (!isset($show_title)) {
        if ($apply_to == 'row' && $icon) {
            $show_title = false;
        }
        else {
            $show_title = true;
        }
    }
}

// behaviour
if (!isset($error_message) && $apply_to == 'rows') {
    $error_message = $lang['NOTHING_SELECTED'];
}
if (!isset($popup)) {
    $popup = false;
}
if (!isset($ajax)) {
    $ajax = false;
}
if (!isset($prefix_data_grid)) {
    $prefix_data_grid = '';
}
$class_behaviour = $prefix_data_grid . 'DGCtrl' . ucfirst($apply_to);
if ($popup) {
    $class_behaviour .= ' ' . $prefix_data_grid . 'DGCtrlPopup';
}
else if ($ajax) {
    $class_behaviour .= ' ' . $prefix_data_grid . 'DGCtrlAjax';
}
?>
{?$simple}
<a href="{$link}"{?$id} id="{$id}"{/?}{?$confirm_message} confirm="{$confirm_message}"{/?}{?$error_message} error="{$error_message}"{/?}{?$target} target="{$target}"{/?} title="{$title}" class="{$class} {$class_behaviour}"{$custom_attributes}>{$title}</a>
{!}
<a href="{$link}"{?$id} id="{$id}"{/?}{?$confirm_message} confirm="{$confirm_message}"{/?}{?$error_message} error="{$error_message}"{/?}{?$target} target="{$target}"{/?} title="{$title}" class="controlContainer {$class_behaviour}"{$custom_attributes}>
    <span class="backendCtrlTable">
    <span class="backendCtrlTr">
        {?$icon}
            <span class="backendCtrlTd">
                <span class="{$icon_class} backendCtrlIcon" style="background-image: url('{$icon}')"></span>
            </span>
        {/?}
        {?$show_title}
            <span class="{$title_class} backendCtrlTd">
                {$title}
            </span>
        {/?}
    </span>
    </span>
</a>
{/?}