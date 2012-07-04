<?php
/**
 * @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
 * @package CleverCore2
 */
_cc::load_module('general/fields/fieldSelect');

class fieldCheckboxGroup extends fieldSelect {
    protected $tpl_name = 'checkbox_group.tpl';
    protected $multiple = true;

    protected function get_tpl_data() {
        $tpl_data = parent::get_tpl_data();
        for ($i = 0; $i < sizeof($tpl_data['options']); $i++) {
            $tpl_data['options'][$i]['id'] = $this->id . $i;
        }
        return $tpl_data;
    }

}


