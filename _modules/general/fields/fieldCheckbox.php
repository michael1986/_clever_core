<?php
/**
 * @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
 * @package CleverCore2
 */
_cc::load_module('general/fields/fieldBase');

/**
* понятие value для этого поля не такое как в HTML, а такое как в остальных полях CC2
* а в шаблоне в атрибуте value нужно использовать передаваемое свойство significance
*/
class fieldCheckbox extends fieldBase {
    protected $tpl_name = 'checkbox.tpl';
    protected $significance = 'on';
    // protected $significance_false = false;

    public function get_tpl_data() {
        $tpl_data = parent::get_tpl_data();
        $tpl_data['significance'] = $this->significance;
        return $tpl_data;
    }
}


