<?php
/**
 * @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
 * @package CleverCore2
 */
_cc::load_module('general/fields/fieldBase');

class fieldRadioSingle extends fieldBase {
    protected $tpl_name = 'radio_single.tpl';
    protected $significance = null;

    public function get_tpl_data() {
        $tpl_data = parent::get_tpl_data();
        $tpl_data['significance'] = $this->significance;
        return $tpl_data;
    }

    public function set_internal_value($value) {
        if ($this->significance == $value) {
            $this->internal_value = $value;
        }
        else {
            $this->internal_value = null;
        }

        $this->external_value = $this->internal_to_external($this->internal_value);
    }

    public function set_external_value($value) {
        if ($this->significance == $value) {
            $this->external_value = $value;
        }
        else {
            $this->external_value = null;
        }

        $this->internal_value = $this->external_to_internal($this->external_value);
    }
}

