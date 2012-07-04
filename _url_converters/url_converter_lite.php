<?php
/**
* @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
* @version 1.0
* @package Lite
*/

/**
*
*/
class url_converter_lite extends _url_converter {

    public function _compact(&$params) {
        $module = $this->_get_param($params, 'lite_module');
        $method = $this->_get_param($params, 'lite_method');

        $module = str_replace('/', '.', $module);

        $link = '';
        if ($module) {
            $link .= $module . '/';
            if ($method) {
                $link .= $method . '/';
            }
        }

        return $link;
    }

    public function _extract(&$link) {
        $params = array();

        $module = $this->_extract_param($link);

        $module = str_replace('.', '/', $module);

        if ($module) {
            $params['lite_module'] = $module;
            $method = $this->_extract_param($link);
            if ($method) {
                $params['lite_method'] = $method;
            }
        }

        return $params;
    }
}


