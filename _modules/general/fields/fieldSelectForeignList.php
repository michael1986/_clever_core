<?php
/**
 * @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
 * @package CleverCore2
 */
_cc::load_module('general/fields/fieldSelect');

class fieldSelectForeignList extends fieldSelect {
    protected $foreign_table = false;

    protected $values = false;
    protected $labels = false;

    protected $sort_labels = false;

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
        if ($this->sort_labels) {
            $ds->_order($this->sort_labels);
        }
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
        $rows = $ds->_adjusted_rows();
        $field_values = $this->values;
        $field_labels = $this->labels;
        $this->values = array();
        $this->labels = array();
        for ($i = 0; $i < sizeof($rows); $i++) {
            $this->values[] = $rows[$i][$field_values];
            $this->labels[] = $rows[$i][$field_labels];
        }
    }
}


