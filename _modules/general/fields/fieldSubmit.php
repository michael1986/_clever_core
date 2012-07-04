<?php
/**
 * @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
 * @package CleverCore2
 */
_cc::load_module('general/fields/fieldBase');

class fieldSubmit extends fieldBase {
    protected $tpl_name = 'submit.tpl';
    protected $type = 'submit';
    protected $value_filter = false;
/*
    public function __construct($data = array()) {
        parent::__construct($data);
        if (!$this->internal_value) {
            // заменили на external name что бы на кнопках по умолчанию отображался экшин без префикса
            $this->internal_value = $this->external_name;
        }
    }
    */
    public function is_pushed() {
        // TODO: ???
        // $value = $this->_read_sticky_param($this->name);
        // return $value;
        return _read_param($this->internal_name);
    }
}


