<?php
_cc::load_module('backend/modBackDataGrid');

class modBackDataGridTree extends modBackDataGrid {

    protected $callback_proceed_paste_under = 'proceed_paste_under';
    protected $callback_paste_under = 'paste_under';

    public function __construct($data = array()) {
        $this->controls_default['paste_under'] = array(
            'modes'     => array('grid'),
            'applicable'    => array('row'),
            'title' => 'CTRL_PASTE_UNDER'
        );

        parent::__construct($data);

        $this->callback_proceed_paste_under = $this->prefix_callbacks . $this->callback_proceed_paste_under;
        $this->callback_paste_under = $this->prefix_callbacks . $this->callback_paste_under;

    }

    public function proceed_action($id, $action) {
        $this->initialize();

        if ($action == 'paste_under') {
            return $this->do_proceed_paste_under($id);
        }

        else {
            return parent::proceed_action($id, $action);
        }
    }

    /**
    * обработка кнопки "Paste under", вызывается или колбек (если найден) или встроенный метод
    */
    public function do_proceed_paste_under($id) {
        if (method_exists($this->_get_holder(), $this->callback_proceed_paste_under)) {
            return call_user_func_array(array($this->_get_holder(), $this->callback_proceed_paste_under), array($id));
        }
        else {
            return $this->proceed_paste_under($id);
        }
    }

    /**
    * встроенный метод для обработки кнопки "Paste under"
    */
    public function proceed_paste_under($id) {
        return $this->do_paste_under($id);
    }

    /**
    * Вставить вырезанные/скопированные row/rows перед указанной строкой - вызывается или колбек (если найден), или встроенный метод
    * 
    * @param mixed $id
    */
    public function do_paste_under($id) {
        if (method_exists($this->_get_holder(), $this->callback_paste_under)) {
            return call_user_func_array(array($this->_get_holder(), $this->callback_paste_under), array($id));
        }
        else if (method_exists($this->data_source, $this->callback_paste_under)) {
            return call_user_func_array(array($this->data_source, $this->callback_paste_under), array($id));
        }
        else {
            return $this->paste_under($id);
        }
    }

    /**
    * втроенный метод вставки данных перед указанной строкой
    * 
    * @param mixed $under_id
    */
    public function paste_under($under_id) {
        $this->initialize();

        $paste = $this->get_clipboard();

        if ($paste['id']) {
            if ($paste['type'] == 'cut') {
                $this->data_source->_where($this->where)->_place_under($paste['id'], $under_id);
            }
            $this->reset_clipboard();
        }
        return false;
    }

    public function check_access($id, $control) {
        $this->initialize();

        if ($control == 'move_up') {
            $parent_id = $this->data_source->parent_id($id);
            if ($id == $this->get_first_row_id($parent_id)) {
                return false;
            }
            else {
                return true;
            }
        }
        else if ($control == 'move_down') {
            $parent_id = $this->data_source->parent_id($id);
            if ($id == $this->get_last_row_id($parent_id)) {
                return false;
            }
            else {
                return true;
            }
        }
        else if (
            $control == 'paste_before' ||
            $control == 'paste_after' ||
            $control == 'paste_under'
        ) {
            $clipboard = $this->get_clipboard();
            if ($clipboard['id'] !== false) {
                if (is_array($clipboard['id'])) {
                    if (in_array($id, $clipboard['id'])) {
                        return false;
                    }
                    else {
                        foreach ($clipboard['id'] as $parent_id) {
                            if ($this->data_source->is_nested($parent_id, $id)) {
                                return false;
                            }
                        }
                        return true;
                    }
                }
                else if ($id == $clipboard['id'] || $this->data_source->is_nested($clipboard['id'], $id)) {
                    return false;
                }
                else {
                    return true;
                }
            }
            else {
                return false;
            }
        }
        else {
            return parent::check_access($id, $control);
        }
    }

    protected $first_row_id = array();
    protected function get_first_row_id() {
        $args = func_get_args();
        $parent_id = $args[0];
        if (!isset($this->first_row_id[$parent_id])) {
            $rows = $this->data_source->rows_univariate();
            $all_ids = _array_values($rows, $this->data_source->_get_primary_key());
            $all_parent_ids = _array_values($rows, $this->data_source->get_field_parent_id());
            $this->first_row_id[$parent_id] = $all_ids[array_search($parent_id, $all_parent_ids)];
        }
        return $this->first_row_id[$parent_id];
    }

    protected $last_row_id = array();
    protected function get_last_row_id() {
        $args = func_get_args();
        $parent_id = $args[0];
        if (!isset($this->last_row_id[$parent_id])) {
            $rows = $this->data_source->rows_univariate();
            $all_ids = _array_values($rows, $this->data_source->_get_primary_key());
            $all_parent_ids = _array_values($rows, $this->data_source->get_field_parent_id());
            $this->last_row_id[$parent_id] = $all_ids[sizeof($all_parent_ids) - array_search($parent_id, array_reverse($all_parent_ids)) - 1];
        }
        return $this->last_row_id[$parent_id];
    }

}


