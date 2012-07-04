<?php
/**
 * @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
 * @package CleverCore2
 */
_cc::load_module('general/fields/fieldSelect');

/**
* понятие value для этого поля не такое как в HTML, а такое как в остальных полях CC2
* а в шаблоне в атрибуте value нужно использовать передаваемое свойство significance
*/
class fieldRadio extends fieldSelect {
    protected $tpl_name = 'radio.tpl';

    public function get_tpl_data() {
        $tpl_data = parent::get_tpl_data();
        for ($i = 0; $i < sizeof($tpl_data['options']); $i++) {
            $tpl_data['options'][$i]['id'] = $this->id . $i;
        }
        return $tpl_data;
    }
}


