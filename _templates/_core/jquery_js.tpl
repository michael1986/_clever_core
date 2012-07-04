<?php
if (!defined('JQUERY_JS') && !_is_ajax_request()) { 
    define('JQUERY_JS', true);
    ?>
    <script type="text/javascript" src="{$_coreurl}js/jquery.js"></script>
    <?php
}
?>