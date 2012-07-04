<?php
/**
* @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
* @version 1.0
* @package Lite
*/

class manLite extends _module {
    function _run() {
        $module = $this->_read_sticky_param('lite_module');
        $allowed_modules = _cc::get_config('lite', 'modules');
        $allowed_modules[] = 'general/liteCss';

        if ($module && is_array($allowed_modules) && in_array($module, $allowed_modules)) {
            $method = $this->_read_sticky_param('lite_method');
            $module_object = $this->_module($module);
            if (!$method) {
                $method = '_run';
            }
            return call_user_func_array(array($module_object, $method), array());
        }
        else {
            _cc::fatal_error(_DEBUG_CC, 'Access to <b>' . $module . '</b> is denied');
        }

    }
}


