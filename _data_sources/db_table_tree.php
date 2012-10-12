<?php
/**
* @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
* @version 1.0
* @package CleverCore2
*/

/**
*
*/
class db_table_tree extends _db_table {
    protected $field_parent_id = false;
    /**
     * @var string имя поля, которое в режиме дерева хранит child элементы (в режиме univariate поле unset)
     */
    protected $field_nested = 'nested';
    /**
     * @var string имя поля, которое хроанит количесвто child
     */
    protected $field_nested_count = 'nested_count';
    /**
     * @var string имя поля, которое хранит глубину элемента в дереве (начинается с 0)
     */
    protected $field_deep = 'deep';

    /**
    * Конструктор, заполнит $this->field_parent_id
    * 
    * @param mixed $data
    * @return db_table_tree
    */
    public function __construct($data = array()) {
        parent::__construct($data);

        if (!$this->field_parent_id) {
            foreach ($this->_fields as $field) {
                if (isset($field['foreign_table']) && $field['foreign_table'] == $this->_get_table()) {
                    $this->field_parent_id = $field['name'];
                    break;
                }
            }
        }
    }

    /**
    * Вернет имя поля, которое ссылается на родительский нод
    * 
    */
    public function get_field_parent_id() {
        return $this->field_parent_id;
    }

    protected $parent_ids = array();
    public function parent_id($id) {
        if (!isset($this->parent_ids[$id])) {
            $rows = $this->rows_univariate();
            $all_ids = _array_values($rows, $this->_get_primary_key());
            $all_parent_ids = _array_values($rows, $this->get_field_parent_id());
            $this->parent_ids[$id] = $all_parent_ids[array_search($id, $all_ids)];
        }
        return $this->parent_ids[$id];
    }

    /**
    * TODO: переделать с использованием rows_univariate
    * 
    * выбирает цепочку родителей для указанной категории, в возвращаемом массиве самая верхняя 
    * категория будет первой, а та, для которой делается выборка последней; каждый элемент - полный
    * ассоциативный массив, описывающий категорию
    * 
    * @param mixed $category_id
    * @param string $cols - список столбцов, которые надо возвращать
    * @return array
    */
    public function get_parents($category_id, $cols = false) {
        $ret = array();

        if ($cols && !strpos($cols, $this->field_parent_id)) {
            $cols .= ', ' . $this->field_parent_id;
            $remove_parent_id = true;
        }
        else {
            $remove_parent_id = false;
        }
        $this->_save_snapshot('get_parents');

        // build parent categories queue
        while ($category_id && $category_row = $this->_restore_snapshot('get_parents')->_row($category_id, $cols)) {
            $category_id = $category_row[$this->field_parent_id];
            if ($remove_parent_id) {
                unset($category_row[$this->field_parent_id]);
            }
            if (sizeof($category_row) == 1) {
                array_unshift($ret, reset($category_row));
            }
            else {
                array_unshift($ret, $category_row);
            }
        }
        // added 2011-08-18, because cumulative _joins breaks the login
        $this->_reset();

        return $ret;
    }

    /**
    * Ошибочное написание, оставлено для обратной совместимости
    */
    public function get_childs($category_id) {
        return $this->get_children($category_id);
    }

    /**
    * Возвращает массив непосредственных потомков для указанной категории
    * 
    * @param mixed $category_id
    * @return array
    */
    public function get_children($category_id) {
        return $this->_rows(array($this->field_parent_id => $category_id));
    }

    protected $tree = false;
    public function rows_tree() {
        if (!$this->tree) {
            $rows = $this->
                _order($this->get_field_parent_id())->
                _order($this->_get_default_order())->
                _arows();
            $this->tree = $this->build_tree($rows, 0, 0);
        }
        return $this->tree;
    }

    protected function build_tree($rows, $parent_id, $deep) {
        $started = false;
        $ret = array();
        foreach ($rows as $r) {
            if ($r[$this->get_field_parent_id()] == $parent_id) {
                $r[$this->field_deep] = $deep;

                $ret[$r[$this->_get_primary_key()]] = $r;
                $ret[$r[$this->_get_primary_key()]][$this->field_nested] = $this->build_tree($rows, $r[$this->_get_primary_key()], $deep + 1);
                $ret[$r[$this->_get_primary_key()]][$this->field_nested_count] = sizeof($ret[$r[$this->_get_primary_key()]][$this->field_nested]);

                $started = true;
            }
            else if ($started) {
                break;
            }
        }
        return $ret;
    }

    protected $univariate = false;
    public function rows_univariate() {
        if (!$this->univariate) {
            $this->univariate = $this->rows_univariate_recur($this->rows_tree());
        }
        return $this->univariate;
    }

    protected function rows_univariate_recur($rows) {
        $ret = array();
        foreach ($rows as $r) {
            $index = sizeof($ret);
            $ret[] = $r;

            if (isset($r[$this->field_nested])) {
                $ret = array_merge($ret, $this->rows_univariate_recur($r[$this->field_nested]));
                unset($ret[$index][$this->field_nested]);
            }
        }
        return $ret;
    }

    public function is_nested($parent_id, $test_id) {
        $rows = $this->rows_univariate();
        $all_ids = _array_values($rows, $this->_get_primary_key());
        $all_deeps = _array_values($rows, 'deep');
        $start_index = array_search($parent_id, $all_ids);
        $start_deep = $all_deeps[$start_index];
        $nested = false;
        for ($i = $start_index + 1; $i < sizeof($all_ids); $i++) {
            if ($all_deeps[$i] <= $start_deep) {
                break;
            }
            if ($all_ids[$i] == $test_id) {
                $nested = true;
                break;
            }
        }
        return $nested;
    }

    /**
    * Рекурсивно удалит все подчиненные элементы
    * 
    * @param mixed $where
    */
    public function _delete($where = false, $allow_truncate = false) {
        $this->_save_snapshot('delete_recursive');
        $ids = $this->_ids($where);
        $this->_restore_snapshot('delete_recursive');

        foreach ($ids as $id) {
            $child_ids = $this->_reset()->_ids(array($this->get_field_parent_id() => $id));
            if (sizeof($child_ids)) {
                $this->_restore_snapshot('delete_recursive')->_delete($child_ids, $allow_truncate);
            }
            $this->_restore_snapshot('delete_recursive');
            parent::_delete($id, $allow_truncate);
        }
    }

    /**
    * Добавлена процедура заполнения корректного поля сорт если известен парент ID
    * 
    * @param mixed $values
    * @return mixed
    */
    public function _insert($values = array()) {
        if ($this->_get_sort_field() && !isset($values[$this->_get_sort_field()]) && isset($values[$this->get_field_parent_id()])) {
            $values[$this->_get_sort_field()] = $this->_row(array(
                $this->get_field_parent_id() => $values[$this->get_field_parent_id()]
            ), 'max(' . $this->_get_sort_field() . ')') + 1;
        }
        return parent::_insert($values);
    }

    /**
    * Добавлена процедура апдейта поля сорт если изменился парент ID
    * 
    * @param mixed $where
    * @param mixed $values
    * @return object
    */
    public function _update($where = false, $values = array(), $update_all_possible = false) {
        $ids = $this->_cols($where, $this->_get_primary_key());
        foreach ($ids as $id) {
            if (
                isset($values[$this->get_field_parent_id()]) && 
                $this->_row($id, $this->get_field_parent_id()) != $values[$this->get_field_parent_id()] &&
                $this->_get_sort_field() && 
                !isset($values[$this->_get_sort_field()])
            ) {
                $values[$this->_get_sort_field()] = $this->_row(array(
                    $this->get_field_parent_id() => $values[$this->get_field_parent_id()]
                ), 'max(' . $this->_get_sort_field() . ')') + 1;
            }
            parent::_update($id, $values);
        }
        return $this;
    }

    /**
    * Переместит строку с учетом parent_id
    * 
    * @param mixed $id
    * @return object
    */
    public function _move_up($id) {
        $this->_where(array($this->get_field_parent_id() => $this->_row($id, $this->get_field_parent_id())));
        return parent::_move_up($id);
    }

    /**
    * Переместит строку с учетом parent_id
    * 
    * @param mixed $id
    * @return object
    */
    public function _move_down($id) {
        $this->_where(array($this->get_field_parent_id() => $this->_row($id, $this->get_field_parent_id())));
        return parent::_move_down($id);
    }

    /**
    * Переместит строку с учетом parent_id
    * 
    * @param mixed $place_id
    * @param mixed $before_id
    * @return object
    */
    public function _place_before($place_id, $before_id) {
        $this->_where(array($this->get_field_parent_id() => $this->_row($before_id, $this->get_field_parent_id())));
        return parent::_place_before($place_id, $before_id);
    }

    /**
    * Переместит строку с учетом parent_id
    * 
    * @param mixed $place_id
    * @param mixed $before_id
    * @return object
    */
    public function _place_after($place_id, $after_id) {
        $this->_where(array($this->get_field_parent_id() => $this->_row($after_id, $this->get_field_parent_id())));
        return parent::_place_after($place_id, $after_id);
    }

    /**
    * Переместит указанную строку под нод
    * 
    * @param mixed $place_id
    * @param mixed $under_id
    */
    public function _place_under($place_id, $under_id) {
        if (is_array($place_id)) {
            // рекурсивно вызвать этот же метод для каждого элемента
            foreach ($place_id as $place_id_single) {
                $this->_place_under($place_id_single, $under_id);
            }
        }
        else {
            if ($this->_get_sort_field()) {
                $this->_where(array($this->get_field_parent_id() => $under_id));
                $update = $this->_get_strict_where();
                $update[$this->_get_sort_field()] = $this->_row(false, 'max(' . $this->_get_sort_field() . ')') + 1;
                $update[$this->get_field_parent_id()] = $under_id;
                $this->_low_update($place_id, $update);
            }
            else {
                _cc::debug_message(_DEBUG_CC, 'Sorting field should be defined to use movement commands', 'error');
            }
        }
   }
}


