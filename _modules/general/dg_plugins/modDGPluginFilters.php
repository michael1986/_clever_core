<?php
/**
* @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
* @version 1.0
* @package modDataGrid
*/

_cc::load_module('general/dg_plugins/modDGPluginBase');

class modDGPluginFilters extends modDGPluginBase {
    protected $fields = array();
    protected $form = false;
    protected $module_form = 'general/modFormBasic';
    protected $apply = false;
    protected $lang = false;

    public function __construct($data = array()) {
        parent::__construct($data);
        $this->lang = $this->_load_language();

        if (sizeof($this->fields)) {
            $submits = array(
                'ok' => array(
                    'value' => $this->lang['BTN_SEARCH']
                ),
                'reset' => array(
                    'value' => $this->lang['BTN_RESET']
                )
            );
            $this->form = $this->_module($this->module_form, array(
                'prefix_params' => $this->prefix_params . 'fltr_',
                'method' => 'get',
                'fields' => $this->fields,
                'submits' => $submits,
            ));
            foreach ($this->fields as $key => &$field) {
                $field = _adjust_field_description($key, $field);
            }
            $submit = $this->form->get_submit();
            if ($submit == 'ok') {
                // приклеить "липкие" параметры формы к держащему датагриду, что бы все последующие плагины 
                // содержали в своих линках этот параметр
                $this->_get_holder()->_stick_params($this->get_form_sticky_params());
            }
            else if ($submit == 'reset') {
                $this->_redirect();
            }
        }
    }

    /**
    * Получить ссылку на объект формы
    * 
    */
    public function get_form_object() {
        return $this->form;
    }

    public function adjust_grid_tpl_data($data) {
        if ($this->form) {
            $data['filters'] = $this->form->_run();
            $data['filters_apply'] = $this->apply;
        }
        return $data;
    }

    public function adjust_grid_params($params, $values = true) {
        if ($this->form) {
            $submit = $this->form->get_submit();
            if ($submit == 'ok') {
                return array_merge(
                    $params,
                    $this->get_form_sticky_params($values)
                );
            }
            else {
                return array_merge(
                    $params,
                    $this->get_form_sticky_params(false)
                );
            }
        }
        else {
            return $params;
        }
    }
    public function adjust_filter_where($where) {
        if ($this->form) {
            $values = $this->form->get_values();
            $submit = $this->form->get_submit();
            if ($submit == 'ok' || $this->apply) {
                foreach ($this->fields as $field) {
                    if (
                        (!isset($field['ignore']) || !$field['ignore']) && 
                        isset($values[$field['name']])
                    ) {
                        if (
                            is_array($values[$field['name']]) &&
                            isset($values[$field['name']]['do']) &&
                            isset($values[$field['name']]['values'])
                        ) {
                            $do = $values[$field['name']]['do'];
                            if ($do) {
                                $value = $values[$field['name']]['values'];
                            }
                        }
                        else {
                            $value = $values[$field['name']];
                            if ($value) {
                                if (is_array($value)) {
                                    $do = false;
                                    foreach ($value as $key => $v) {
                                        if ($v) {
                                            $do = true;
                                            break;
                                        }
                                    }
                                }
                                else {
                                    $do = true;
                                }
                            }
                            else {
                                $do = false;
                            }
                        }
                        if ($do) {
                            if (isset($field['field'])) {
                                $field_name = $field['field'];
                            }
                            else {
                                $field_name = $field['name'];
                            }
                            if (isset($field['operator'])) {
                                $operator = $field['operator'];
                            }
                            else {
                                $operator = false;
                            }
                            if (is_array($field_name)) {
                                if (isset($field['logic'])) {
                                    $logic = $field['logic'];
                                }
                                else {
                                    $logic = 'and';
                                }
                                $where_tmp = array($logic);
                                foreach ($field_name as $form_name => $db_name) {
                                    if (is_numeric($form_name)) {
                                        /*
                                         * remade 2011-11-17
                                         * потому что не работало описание
                                         *             array(
                                         *                  'name' => 'name',
                                         *                  'field' => array(
                                         *                      'fname',
                                         *                       'lname'
                                         *                  ),
                                         *                  'operator' => 'like%',
                                         *                  'logic' => 'or',
                                         *                  'title' => 'Name'
                                         *              )
                                         * т.к. $value в данном случае не массив
                                        if (isset($value[$form_name]) && $value[$form_name]) {
                                            if (isset($operator[$form_name]) && $operator) {
                                                $where_tmp[$db_name . ' ' . $operator] = $value[$form_name];
                                            }
                                            else {
                                                $where_tmp[$db_name] = $value[$form_name];
                                            }
                                        }
                                        */
                                        if (is_array($operator) && isset($operator[$form_name])) {
                                            $where_tmp[$db_name . ' ' . $operator[$form_name]] = $value;
                                        }
                                        else if ($operator) {
                                            $where_tmp[$db_name . ' ' . $operator] = $value;
                                        }
                                        else {
                                            $where_tmp[$db_name] = $value;
                                        }
                                    }
                                    else {
                                        if (isset($value[$form_name]) && $value[$form_name]) {
                                            if (is_array($operator)) {
                                                if (isset($operator[$form_name]) && $operator[$form_name]) {
                                                    $where_tmp[$db_name . ' ' . $operator[$form_name]] = $value[$form_name];
                                                }
                                                else {
                                                    $where_tmp[$db_name] = $value[$form_name];
                                                }
                                            }
                                            else {
                                                if ($operator) {
                                                    $where_tmp[$db_name . ' ' . $operator] = $value[$form_name];
                                                }
                                                else {
                                                    $where_tmp[$db_name] = $value[$form_name];
                                                }
                                            }
                                        }
                                        else {
                                            _cc::debug_message(_DEBUG_CC, $form_name . ' field should be present in filters form');
                                        }
                                    }
                                }
                                $where[] = $where_tmp;
                            }
                            else {
                                if ($operator) {
                                    $where[$field_name . ' ' . $field['operator']] = $value;
                                }
                                else {
                                    $where[$field_name] = $value;
                                }
                            }
                        }
                    }
                }
                if (method_exists($this->_get_holder()->_get_holder(), $this->prefix_callbacks . 'adjust_filter_where')) {
                    $where = call_user_func_array(array($this->_get_holder()->_get_holder(), $this->prefix_callbacks . 'adjust_filter_where'), array($where, $values));
                }
            }
        }
        return $where;
    }

    public function get_form_sticky_params($values = true) {
        if ($this->form) {
            $params = $this->form->get_sticky_params($values);
            return $params;
            /* atavism? 2010.09.09
            $ret = array();
            foreach ($params as $p => $v) {
                if ($values) {
                    $ret[$p . '[do]'] = 'on';
                    $ret[$p . '[value]'] = $v;
                }
                else {
                    $ret[$p . '[do]'] = false;
                    $ret[$p . '[value]'] = false;
                }
            }
            return $ret;
            */
        }
        else {
            return $values;
        }
    }
    
    public function adjust_filter_having($where) {
        if ($this->form) {
            $values = $this->form->get_values();
            if (method_exists($this->_get_holder()->_get_holder(), $this->prefix_callbacks . 'adjust_filter_having')) {
                $where = call_user_func_array(array($this->_get_holder()->_get_holder(), $this->prefix_callbacks . 'adjust_filter_having'), array($where, $values));
            }
        }
        return $where;
    }

    public function adjust_order($order) {
        if ($this->form) {
            $values = $this->form->get_values();
            if (method_exists($this->_get_holder()->_get_holder(), $this->prefix_callbacks . 'adjust_order')) {
                $order = call_user_func_array(array($this->_get_holder()->_get_holder(), $this->prefix_callbacks . 'adjust_order'), array($order, $values));
            }
        }
        return $order;
    }
}


