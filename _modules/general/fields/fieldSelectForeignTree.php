<?php
/**
 * @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
 * @package CleverCore2
 */
_cc::load_module('general/fields/fieldSelect');

class fieldSelectForeignTree extends fieldSelect {
    protected $foreign_table = false;

    protected $where = false;
    protected $join = false;

    function __construct($data = array()) {
        parent::__construct($data);
        $ds = $this->_get_data_source($this->foreign_table);
        if (!$this->values) {
            $this->values = $ds->_get_primary_key();
        }
        if (!$this->labels) {
            $fp = $ds->_get_prefix_fields();
            if (!$labels = $ds->_get_field($fp . 'title', 'name')) {
                $this->labels = $this->values;
            }
            else {
                $this->labels = $labels;
            }
        }
        if (is_string($this->labels) && is_string($this->values)) {
            if ($this->where) {
                $ds->_where($this->where);
            }
            if ($this->join) {
                if (is_array($this->join)) {
                    foreach ($this->join as $join) {
                        if (is_array($join)) {
                            call_user_func_array(array($ds, '_join'), $join);
                        }
                        else {
                            $ds->_join($join);
                        }
                    }
                }
            }
            $rows = $ds->rows_univariate();
            $this->labels = _array_values($rows, $this->labels);
            $this->values = _array_values($rows, $this->values);
            $deeps = _array_values($rows, 'deep');
            if ($this->zero_option) {
                $this->zero_value = 0;
                /* Добавятся в родителе
                array_unshift($deeps, 0);
                array_unshift($this->values, 0);
                array_unshift($this->labels, $this->zero_option);
                */
            }
            for ($i = 0; $i < sizeof($deeps); $i++) {
                if ($deeps[$i]) {
                    // $this->labels[$i] = $this->labels[$i];
                    for ($j = 0; $j < $deeps[$i]; $j++) {
                        $this->labels[$i] = '.... ' . $this->labels[$i];
                    }
                }
            }
        }
        else {
            _cc::debug_message(_DEBUG_CC, 'CC Warning. \'labels\' and \'values\' should be references to the foreign table\'s columns, field: <b>' . $this->external_name . '</b>', 'error');
            list($this->values, $this->labels) = array_values($ds->_cols());
        }
    }
}


