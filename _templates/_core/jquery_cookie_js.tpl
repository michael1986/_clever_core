<?php
if (!defined('JQUERY_COOKIE_JS')) { 
    define('JQUERY_COOKIE_JS', true);
    ?>
    {@jquery_js.tpl}
    <script type="text/javascript" src="{$_coreurl}js/jquery.cookie.js"></script>
    <?php
}
?>