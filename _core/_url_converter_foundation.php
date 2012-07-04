<?php
/**
 * @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
 * @package CleverCore2
 */

/**
 * Base class for url converters.
 */
abstract class _url_converter_foundation extends _core {
    protected $__type = '_url_converter';

    public function _compact(&$params) {
        return '';
    }

    public function _extract(&$link) {
        return array();
    }

    /**********************************************************************
    * вспомогательные методы
    ***********************************************************************/

    /**
    * подставляет указаный параметр к линку "как есть" и удаляет его из списка параметров
    * 
    * @param array $params
    * @param string $param
    * @param string $separator
    */
    protected function _get_param(&$params, $param, $separator = '') {
        if (isset($params[$param])) {
            $link = $params[$param] . $separator;
            unset($params[$param]);
        }
        else {
            $link = '';
        }
        return $link;
    }

    /**
    * Извлекает из начала линка и возвращает подстроку, ограниченую параметром $separator
    * 
    * @param string $link
    * @param string $separator
    * @return string
    */
    protected function _extract_param(&$link, $separator = '/') {
        $pos = strpos($link, $separator);
        if ($pos == false) {
            $ret = $link;
            $link = '';
        }
        else {
            $ret = substr($link, 0, $pos);
            $link = substr($link, $pos + 1);
        }
        return $ret;
    }

    /**
    * совершает действие, обратное _extract_param; используется если после извлечения и 
    * проверки параметра, он оказался чужим
    * 
    * @param mixed $link
    * @param mixed $param
    * @param mixed $separator
    */
    protected function _undo_extract_param(&$link, $param, $separator = '/') {
        if ($link) {
            $link = $param . $separator . $link;
        }
        else {
            $link = $param;
        }
    }

    /**
    * Создает и инициализирует переменными объект data
    * 
    * @param string $data_name Имя класса, описывающего данные
    * @param array $initial_data Данные, которыми инициализировать созданый объект
    * @param string $data_source_class Имя базового класса, который будет использован при создании data_source на лету
    * @return db_table (data_source) Объект data_source
    */
    /* 2012-04-25 перенесено в _core_foundation
    public function _create_data_source($data, $initial_data = array(), $data_source_class = false) {
        if (!$data_source_class) {
            $data_source_class = __DEFAULT_DATA_SOURCE_CLASS;
        }
        return _cc::create_data_from_class($data, $this, $initial_data, $data_source_class);
    }
    */

}


