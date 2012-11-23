<?php
/**
* @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
* @version 1.0
* @package modDataGrid
*/

_cc::load_module('general/dg_plugins/modDGPluginBase');

class modDGPluginColumns extends modDGPluginBase {
    protected $description = array();
    protected $grid_tpl_variable = 'columns';
    protected $default_width = false;
    protected $param_order = 'order';
    protected $param_order_direction = 'order_direction';
    protected $order = false;
    protected $order_fields = array();
    protected $order_direction = false;
    protected $dg_order_index = false;
    protected $dg_order_direction = false;

    public function __construct($data = array()) {
        parent::__construct($data);

        $this->param_order = $this->prefix_params . $this->param_order;
        $this->param_order_direction = $this->prefix_params . $this->param_order_direction;

        $this->order = _read_param($this->param_order);
        $this->order_direction = _read_param($this->param_order_direction);

        $order = $this->_get_holder()->get_order();
        $dg_order_str = reset($order);
        if ($dg_order_str) {
            $dg_order_data = explode(' ', $dg_order_str);
            $dg_order = $dg_order_data[0];
            if (isset($dg_order_data[1]) && $dg_order_data[1] == 'desc') {
                $this->dg_order_direction = 'desc';
            }
            else {
                $this->dg_order_direction = false;
            }
        }
        else {
            $dg_order = false;
            $this->dg_order_direction = false;
        }

        $i = 0;
        $order_is_valid = false;
        $this->dg_order_index = false;
        foreach ($this->description as &$d) {
            if (!isset($d['checkbox'])) {
                $d['checkbox'] = false;
            }
            if (!isset($d['controls'])) {
                $d['controls'] = false;
            }
            if (!isset($d['title'])) {
                $d['title'] = '';
            }
            if (!isset($d['width'])) {
                $d['width'] = $this->default_width;
            }
            if (!isset($d['data_key'])) {
                $d['data_key'] = false;
            }
            if (isset($d['order'])) {
                $this->order_fields[$i] = $d['order'];
                if ($this->order !== false && $this->order == $i) {
                    $order_is_valid = true;
                }
                if ($dg_order == $d['order']) {
                    $this->dg_order_index = $i;
                }
            }
            else {
                $d['order'] = false;
            }
            $i++;
        }
        if (!$order_is_valid) {
            $this->order = false;
            $this->order_direction = false;
        }
        if ($this->order === false && $this->dg_order_index !== false) {
            $this->order = $this->dg_order_index;
            $this->order_direction = $this->dg_order_direction;
        }
    }

    public function adjust_grid_tpl_data($data) {
        $data[$this->grid_tpl_variable] = array();
        $i = 0;
        foreach ($this->description as $d) {
            if ($d['order']) {
                if ($this->order !== false && $this->order == $i) {
                    if ($this->order_direction == 'desc') {
                        $d['link_asc'] = $this->get_order_link($i, false);
                        $d['link_desc'] = false;
                    }
                    else {
                        $d['link_asc'] = false;
                        $d['link_desc'] = $this->get_order_link($i, 'desc');
                    }
                }
                else {
                    $d['link_asc'] = $this->get_order_link($i, false);
                    $d['link_desc'] = $this->get_order_link($i, 'desc');
                }
            }
            else {
                $d['link_asc'] = false;
                $d['link_desc'] = false;
            }
            $data[$this->grid_tpl_variable][] = $d;
            $i++;
        }
        return $data;
    }

    protected function get_order_link($index, $direction) {
        if ($index === $this->dg_order_index && $direction == $this->dg_order_direction) {
            $index = false;
            $direction = false;
        }
        return $this->_get_link(array(
            $this->param_order => $index,
            $this->param_order_direction => $direction
        ));
    }

    public function adjust_grid_params($params, $values = true) {
        if ($values) {
            $params[$this->param_order] = $this->order;
            $params[$this->param_order_direction] = $this->order_direction;
        }
        else {
            $params[$this->param_order] = false;
            $params[$this->param_order_direction] = false;
        }
        return $params;
    }

    public function adjust_order($order) {
        if ($this->order !== false) {
            if (!is_array($order)) {
                if ($order) {
                    $order = array($order);
                }
                else {
                    $order = array();
                }
            }
            $order_str = $this->order_fields[$this->order];
            if ($this->order_direction == 'desc') {
                $order_str .= ' desc';
            }
            $ret = array($order_str);
            foreach ($order as $o) {
                $o_parts = explode(' ', $o);
                if ($o_parts[0] != $this->order_fields[$this->order]) {
                    $ret[] = $o;
                }
            }
            return $ret;
        }
        else {
            return $order;
        }
    }
}


