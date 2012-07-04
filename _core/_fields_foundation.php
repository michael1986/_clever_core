<?php
/**
 * @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
 * @package CleverCore2
 */
class _fields_foundation extends _data_source {
    /**
     * Associative array, which describes DB table's fields
     *
     * @var mixed
     */
    protected $_fields = array();

    /**
     * Common fields' prefix, could be generated automatically
     */
    protected $_prefix_fields = false;

    /***********************************************************************************************
     * Internal properties
     ***********************************************************************************************/

    /**
     * Сокращенный массив с именами полей и их индексами в общем массиве (name => index),
     * используется для упрощения и ускорения расчетов
     *
     * @var array()
     */
    protected $__fields_names = array();

    /**
     * Name of 'sort' field
     *
     * @var mixed
     */
    protected $__sort_field_name = false;

    /**
     * @ignore
     *
     * Constructor
     */
    public function __construct($data = array()) {
        parent::__construct($data);

        $this->_prepare();
    }

    /**
     * Prepare datasource to work
     *
     */
    protected function _prepare() {
        foreach ($this->_fields as $index => &$field) {

            $field = _adjust_field_description($index, $field);

            $this->__fields_names[$field['name']] = $index;
        }
        unset($field);

        if (!$this->_prefix_fields) {
            $this->_prefix_fields = $this->__find_prefix_fields();
        }
    }

    /**
     * "unpack" language array
     *
     * @param mixed $lang_name
     */
    public function _apply_language($lang) {
        foreach ($this->_fields as &$f) {
            if (isset($f['title']) && isset($lang[$f['title']])) {
                $f['title'] = $lang[$f['title']];
            }
            if (isset($f['error_message'])) {
                if (is_array($f['error_message'])) {
                    foreach ($f['error_message'] as &$m) {
                        if (isset($lang[$m])) {
                            $m = $lang[$m];
                        }
                    }
                    unset($m);
                }
                else if (isset($lang[$f['error_message']])) {
                    $f['error_message'] = $lang[$f['error_message']];
                }
            }
            if (isset($f['labels']) && is_array($f['labels'])) {
                foreach ($f['labels'] as &$l) {
                    if (isset($lang[$l])) {
                        $l = $lang[$l];
                    }
                }
                unset($l);
            }
        }
        unset($f);
    }

    /**
     * Get common prefix fields
     *
     * @return string
     */
    public function _get_prefix_fields() {
        return $this->_prefix_fields;
    }

    /**
     * Get names of all fields
     *
     * @return array
     */
    public function _get_fields_names() {
        return array_keys($this->__fields_names);
    }

    public function _get_fields($values = false, $include_list = false, $exclude_list = false) {
        $ret = array();
        if (is_array($include_list) && sizeof($include_list) > 0) {
            $fields_names = array_keys($this->__fields_names);
            foreach($include_list as $index => $field) {
                if (is_string($field)) {
                    if (in_array($field, $fields_names)) {
                        $ret[$field] = $this->_fields[$this->__fields_names[$field]];
                    }
                }
                else {
                    $field = _adjust_field_description($index, $field);
                    if (!$field['name']) {
                        $ret[] = $field;
                    }
                    else {
                        $ret[$field['name']] = $field;
                    }
                }
            }
        }
        else {
            if (!is_array($exclude_list)) {
                $exclude_list = array();
            }
            foreach($this->_fields as $field) {
                if (
                    (!isset($field['primary_key']) || !$field['primary_key']) &&
                    (!isset($field['created']) || !$field['created']) &&
                    (!isset($field['modified']) || !$field['modified']) &&
                    (!isset($field['sort']) || !$field['sort']) &&
                    (!isset($field['input_disabled']) || !$field['input_disabled']) &&
                    (!in_array($field['name'], $exclude_list))
                ) {
                    if (!$field['name']) {
                        $ret[] = $field;
                    }
                    else {
                        $ret[$field['name']] = $field;
                    }
                }
            }
        }
        if ($values) {
            if (is_numeric($values)) { // values as ID
                $values = $this->_row($values);
            }
            $ret = _set_fields_values($ret, $values);
        }
        return $ret;
    }

    /**
     * возвращает описание отдельного поля по имени или описание отдельного ключа этого поля
     *
     * @param string $name имя поля
     * @param string $key ключ поля
     * @return array описание поля или значение его ключа
     */
    public function _get_field($name, $key = false) {
        if (isset($this->__fields_names[$name])) {
            if ($key) {
                if (isset($this->_fields[$this->__fields_names[$name]][$key])) {
                    return $this->_fields[$this->__fields_names[$name]][$key];
                }
                else {
                    return false;
                }
            }
            else {
                return $this->_fields[$this->__fields_names[$name]];
            }
        }
        else {
            return false;
        }
    }

    /**
     * Встроенные метод валидация пользовательского ввода; при перегрузке важно помнить, что нужно
     * вызывать parent::_validate_input(...) если вы хотите просто дополнить существующий
     * функционал, а не переделать его полностью
     *
     * @param mixed $id ID строки данных, которая редктировалась; если это новые данные, то false
     * @param mixed $values ассоциативный массив с данными
     */
    public function _validate_input($id, $values) {
        $ems = array();
        $fields_map = array();
        $__id_counter = 0;
        $fields_plain = _fields::__line_up_fields($this->_fields, $fields_map, $__id_counter);
        foreach ($fields_plain as $field) {
            if (
                isset($values[$field['name']])
            ) {
                if (isset($field['validate_input'])) {
                    if (!is_array($field['validate_input'])) {
                        $field['validate_input'] = array($field['validate_input']);
                    }
                    for ($i = 0; $i < sizeof($field['validate_input']); $i++) {
                        if (method_exists($this, $field['validate_input'][$i])) {
                            $is_valid = call_user_func_array(array($this, $field['validate_input'][$i]), array($values[$field['name']], $field, $id, $values));
                        }
                        else if (function_exists($field['validate_input'][$i])) {
                            $is_valid = $field['validate_input'][$i]($values[$field['name']]);
                        }
                        else {
                            _cc::debug_message(
                                _DEBUG_CC,
                                'CC error. Validation method or function not exists <b>' . $field['validate_input'][$i] . '</b>',
                                'error'
                            );
                            $is_valid = false;
                        }
                        if (!$is_valid) {
                            $ems_set = false;
                            if (isset($field['error_message'])) {
                                if (is_array($field['error_message'])) {
                                    if (isset($field['error_message'][$i])) {
                                        $ems_set = true;
                                        $ems[$field['name']] = $field['error_message'][$i];
                                    }
                                }
                                else {
                                    $ems_set = true;
                                    $ems[$field['name']] = $field['error_message'];
                                }
                            }
                            if (!$ems_set) {
                                $ems[$field['name']] = 'Please enter correct "' . $field['title'] . '"';
                            }
                        }
                    }
                }
            }
        }
        return $ems;
    }

    /**
     * Находит и возвращает общий префикс всех полей таблицы.
     *
     * @param mixed $fields
     */
    protected function __find_prefix_fields($fields = false, $key = 'name') {
        if (!$fields) {
            $fields = $this->_fields;
        }
        $prefix = false;
        foreach ($fields as $field) {
            $name = $field[$key];
            if ($prefix === false) {
                $prefix = $name;
                continue;
            }
            $prefix_tmp = '';
            for ($i = 0; $i < strlen($prefix); $i++) {
                if ($i >= strlen($name)) {
                    break;
                }
                if ($prefix[$i] != $name[$i]) {
                    break;
                }
                else {
                    $prefix_tmp .= $prefix[$i];
                }
            }
            $prefix = $prefix_tmp;
        }
        return $prefix;
    }

    public function __line_up_fields($fields, &$fields_modules_map, &$__id_counter) {
        $ret = array();
        foreach ($fields as $key => &$field) {
            $field = _adjust_field_description($key, $field);

            // Создать внутренний идентификатор поля
            if (is_numeric($key)) {
                if (!empty($field['name'])) {
                    if (is_array($field['name'])) {
                        $__id = $field['name'][0];
                    } else {
                        $__id = $field['name'];
                    }
                }
                else {
                    $__id = $__id_counter;
                    $__id_counter++;
                }
            }
            else {
                $__id = $key;
            }
            $field['__id'] = $__id;

            if (!isset($field['title'])) {
                $field['title'] = $field['name'];
            }

            if (isset($field['fields'])) {
                $pass_fields = $field['fields'];
                unset($field['fields']);
            }
            else {
                $pass_fields = false;
            }
            $ret[$__id] = $field;

            if ($pass_fields) {
                $pass_fields_modules_map = array();
                $ret = $ret + _fields::__line_up_fields($pass_fields, $pass_fields_modules_map, $__id_counter);
                $fields_modules_map[$__id] = $pass_fields_modules_map;
            }
            else {
                $fields_modules_map[$__id] = array();
            }
        }
        return $ret;
    }

}