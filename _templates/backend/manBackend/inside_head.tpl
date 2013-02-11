<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html id="BackendHtml">
<head>
    <link href="<?php echo $this->_hlink(array('lite_module' => 'general/liteCss', 'name' => 'backend.css'), 'lite') ?>" rel="stylesheet" type="text/css">
    <link href="<?php echo $this->_hlink(array('lite_module' => 'general/liteCss', 'name' => $lang['CSS']), 'lite') ?>" rel="stylesheet" type="text/css">
    {?$css}
        <link href="{$_baseurl}{$css}" rel="stylesheet" type="text/css">
    {/?}
    <title>{$page_title}</title>
    {@jquery_js.tpl}
    {@jquery_cookie_js.tpl}
    {@popupmodal_js.tpl}
    <script type="text/javascript" language="javascript" src="{$_coreurl}js/backend.js"></script>
    <script type="text/javascript">
        if (parent.frames.length) {
            parent.location.href = window.location.href;
        }

        var {$backend_instance};
        $(function () {
            {$backend_instance} = new Backend({
                linkAddToFavorites: '{$link_add_to_favorites}',
                backendInstance: '{$backend_instance}'
            });
        });
    </script>
</head>
