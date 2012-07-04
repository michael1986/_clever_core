<?php
/**
 * @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
 * @package CleverCore2
 */
_cc::load_module('general/fields/fieldBase');

class fieldSelect extends fieldBase {
    protected $tpl_name = 'select.tpl';
    protected $values = array();
    protected $labels = array();
    protected $multiple = false;
    
    protected $zero_option = false;
    protected $zero_value = '';

    protected function get_tpl_data() {
        $tpl_data = parent::get_tpl_data();
        if ($this->multiple) {
            $tpl_data['name'] .= '[]';
        }

        $tpl_data['options'] = array();
        if (sizeof($this->values) != sizeof($this->labels)) {
            if (!sizeof($this->labels)) {
                $this->labels = $this->values;
            }
            else if (!sizeof($this->values)) {
                $this->values = $this->labels;
            }
            else {
                _cc::fatal_error(
                    _DEBUG_CC, 
                    'CC Error. Count of labels and values should be the same for field <b>' . $this->external_name . '</b>', 
                    'error'
                );
            }
        }
        
        if ($this->zero_option) {
            /*~~~ Mikhail`s fixes ~~~*/
            if (sizeof($this->values) && sizeof($this->labels)) {
                array_unshift($this->values, $this->zero_value);
                array_unshift($this->labels, $this->zero_option);
            }
            else {
                $this->values[] = '';
                $this->labels[] = $this->zero_option;
            }
            /*~~~*/
        }
        for ($i = 0; $i < sizeof($this->labels); $i++) {
            $selected = false;
            if (
                $this->internal_value !== false && (
                    (is_array($this->internal_value) && $this->multiple && in_array($this->values[$i], $this->internal_value)) ||
                    $this->internal_value == $this->values[$i]
                )
            ) {
                $selected = true;
            }
            $tpl_data['options'][] = array(
                'name'      => $this->internal_name,
                'value'     => $this->values[$i],
                'label'     => $this->labels[$i],
                'selected'  => $selected
            );
        }
        $tpl_data['multiple'] = $this->multiple;
        return $tpl_data;
    }

    protected function read_param_with_keys($name) {
        $value = parent::read_param_with_keys($name);
        if ($this->multiple && $value === false) {
            $value = array();
        }
        return $value;
    }
}


