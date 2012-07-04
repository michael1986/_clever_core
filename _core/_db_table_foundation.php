<?php
/**
 * @package CleverCore2
 * @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
 */

/**
 * Base class for all data sources, which work with the DB tables
 */
abstract class _db_table_foundation extends _fields {

    /**
     * @var database alias where current table is located
     */
    protected $_db = '_db';

    /**
    * Table name without prefix; coincides with the data_source name by default
    * 
    * @var string
    */
    protected $_table = false;

    /**
    * Table alias to be used inside SQL queries
    * conisides with $this->_table by default
    *
    * @var string
    */
    protected $_alias = false;

    /**
    * Cleanup settings; associative array, which supports the following keys:
    *   - 'data_sources'
    *   - 'binary_fields'
    * 
    * @var array
    */
    protected $_cleanup_settings = array();

    /**
    * Flag, set it to TRUE to force this data_source to always call _cleanup method
    * 
    * @var boolean
    */
    protected $_force_cleanup = false;

    /***********************************************************************************************
    * Static settings (which don't resets with _reset() method
    ***********************************************************************************************/

    /**#@+
    * @ignore
    */

    /**
    * TODO ?
    */
    protected $_order_static = array();
    /**
    * TODO ?
    */
    protected $_join_static = array();
    /**
    * TODO ?
    */
    protected $_where_static = array();

    /**#@-*/

    /**#@+
    * @ignore
    */

    /***********************************************************************************************
    * Internal properties
    ***********************************************************************************************/

    /**
     * Primary key field name
     *
     * @var mixed
     */
    protected $__primary_key = false;

    /**
     * Name of 'active' field
     *
     * @var mixed
     */
    protected $__active_field_name = false;

    /**
     * Array of fields' names, used to order results
     *
     * @var mixed
     */
    protected $__order_array_static = array();

    /**
    * Сокращенный массив с именами двоичных полей и значением их binary_path (name => binary_path)
    * используется для упрощения и ускорения расчетов
    * 
    * заполняется автоматически
    * 
    * @var array()
    */
    protected $__binary_fields = array();

    /**
    * Массив, хранящий все ссылки данного датасорца на внешние таблицы
    * используется для упрощения и ускорения расчетов
    * 
    * заполняется автоматически
    * 
    * @var array()
    */
    protected $__foreign_fields = array();

    /**
    * @var mixed
    */
    protected $__columns_array = array();

    /**
    * хранит в себе все принудительно присоединенные таблицы (используя _join)
    * массив ассоциативных массивов, каждый элемент имеет:
    * - data_source - созданный объект data_source
    * - join - тип join (inner, left outer, right outer, left, right)
    */
    protected $__join_array = array();

    /**
    * Флаг, false указывает на необходимость пересобрать массив $this->__join_array
    * 
    * @var boolean
    */
    protected $__join_array_ready = false;

    /**
    * хранит в себе все неявно присоединенные таблицы (например, при упоминании в условии where)
    * массив ассоциативных массивов, каждый элемент имеет:
    * - data_source - созданный объект data_source ИЛИ массив, описывающий data_source ИЛИ имя класса, описывающего data_source
    * - join - тип join (inner, left outer, right outer, left, right)
    */
    protected $__join_weekness_array = array();

    protected $__where_array = array();
    protected $__order_array = array();
    protected $__having_array = array();
    protected $__group_array = array();
    protected $__limit1_number = false;
    protected $__limit2_number = false;

    /**
    * В каком виде возвращать следующий результат работы метода _select - как массив столбцов или строк
    * 
    * @var boolean
    */
    protected $__result_as_columns = false;
    /**#@-*/

    /**
    * Prepare datasource to work
    * 
    */
    protected function _prepare() {
        parent::_prepare();

        if (!$this->_table) {
            $this->_table = get_class($this);
        }

        if (!isset($this->_cleanup_settings['binary_fields'])) {
            $cleanup_settings_binary_fields = true;
        }
        else {
            $cleanup_settings_binary_fields = $this->_cleanup_settings['binary_fields'];
        }
        $this->_cleanup_settings['binary_fields'] = array();

        foreach ($this->_fields as $index => &$field) {
            // primary_key
            if (!isset($field['primary_key'])) {
                $field['primary_key'] = false;
            }
            if ($field['primary_key']) {
                if ($this->__primary_key) {
                    if (!is_array($this->__primary_key)) {
                        $this->__primary_key = array($this->__primary_key);
                    }
                    $this->__primary_key[] = $field['name'];
                }
                else {
                    $this->__primary_key = $field['name'];
                }
            }

            // adjust $this->_cleanup_settings['binary_fields']
            if (
                (
                    is_array($cleanup_settings_binary_fields) && 
                    in_array($field['name'], $cleanup_settings_binary_fields)
                ) ||
                (
                    !is_array($cleanup_settings_binary_fields) &&
                    $cleanup_settings_binary_fields && 
                    isset($field['binary_path']) && 
                    $field['binary_path']
                )
            ) {
                if (!isset($field['binary_path'])) {
                    $field['binary_path'] = '';
                }
                if (isset($field['binary_cleanup_mode'])) {
                    $binary_cleanup_mode = $field['binary_cleanup_mode'];
                }
                if (!isset($binary_cleanup_mode) || !in_array($binary_cleanup_mode, array('unlink_always', 'unlink_last', 'unlink_none'))) {
                    $binary_cleanup_mode = 'unlink_always';
                }
                if ($binary_cleanup_mode != 'unlink_none') {
                    $this->_cleanup_settings['binary_fields'][$field['name']] = array(
                        'path' => $field['binary_path'],
                        'cleanup_mode' => $binary_cleanup_mode
                    );
                }
            }

            // sort and order
            if (isset($field['order'])) {
                $do_sort = $field['order'];
            }
            else if (isset($field['sort'])) {
                $do_sort = $field['sort'];
            }
            else {
                $do_sort = false;
            }
            if (isset($field['binary_path']) && $field['binary_path']) {
                $this->__binary_fields[$field['name']] = $field['binary_path'];
            }
            if ($do_sort) {
                $this->__order_array_static[] = $field['name'] . (strtolower($do_sort) == 'desc' ? ' desc' : '');
            }
            if (isset($field['sort']) && $field['sort']) {
                if ($this->__sort_field_name) {
                    _cc::debug_message(_DEBUG_CC, '"Sort" field is ambiguous', 'error');
                }
                else {
                    $this->__sort_field_name = $field['name'];
                }
            }
            if (isset($field['active']) && $field['active']) {
                if ($this->__active_field_name) {
                    _cc::debug_message(_DEBUG_CC, '"Active" field is ambiguous', 'error');
                }
                else {
                    $this->__active_field_name = $field['name'];
                }
            }

            // foreign_table
            if (isset($field['foreign_table']) && $field['foreign_table']) {
                if (isset($this->__foreign_fields[$field['foreign_table']])) {
                    $this->__foreign_fields[$field['foreign_table']] = array($this->__foreign_fields[$field['foreign_table']]);
                    $this->__foreign_fields[$field['foreign_table']][] = $field['name'];
                }
                else {
                    $this->__foreign_fields[$field['foreign_table']] = $field['name'];
                }
            }
        }
        unset($field);

        if (!$this->__primary_key) {
            _cc::fatal_error(_DEBUG_CC, 'Primary key for <b>' . get_class($this) . '</b> datasource is not defined.');
        }

        // adjust $this->_cleanup_settings['data_sources']
        if (!isset($this->_cleanup_settings['data_sources'])) {
            $this->_cleanup_settings['data_sources'] = array();
        }
        foreach ($this->_cleanup_settings['data_sources'] as $key => &$ds) {
            if (is_numeric($key)) {
                $ds_name = $ds;
                $foreign_key = false;
            }
            else {
                $ds_name = $key;
                $foreign_key = $ds;
            }
            $ds_obj = $this->_create_data_source($ds_name);

            if (!$foreign_key) {
                $foreign_field1 = $ds_obj->_get_foreign_field($this->_table);
                $foreign_field2 = $this->_get_foreign_field($ds_name);
                if (
                    ($foreign_field1 && $foreign_field2) ||
                    is_array($foreign_field1) ||
                    is_array($foreign_field2)
                ) {
                    _cc::fatal_error(_DEBUG_CC, 'Unable to prepare autocleanup: relation between <b>' . $this->_table . '</b> and <b>' . $ds_name . '</b> is ambiguous');
                }
                else if ($foreign_field1) {
                    $foreign_key = $foreign_field1;
                    $this_flag = false;
                }
                else if ($foreign_field2) {
                    $foreign_key = $foreign_field2;
                    $this_flag = true;
                }
                else {
                    _cc::fatal_error(_DEBUG_CC, 'Unable to prepare autocleanup: relation between <b>' . $this->_table . '</b> and <b>' . $ds_name . '</b> is undefined');
                }
            }
            $ds = array(
                'data_source' => $ds_obj,
                'this_flag' => $this_flag,
                'foreign_key' => $foreign_key
            );
        }
        unset($ds);

        if (!sizeof($this->_cleanup_settings['binary_fields']) && !sizeof($this->_cleanup_settings['data_sources'])) {
            // to avoid extra SQL query without necessity
            $this->_cleanup_settings = false;
        }
    }

    /*****************************************************************
    * Getters and Setters
    *****************************************************************/
    public function _is_active($where) {
        if ($this->__active_field_name) {
            $active = $this->_cols($where, $this->__active_field_name);
            if (is_array($active)) {
                return in_array(0, $active) ? false : true;
            }
            else {
                return $active ? true : false;
            }
        }
        else {
            _cc::fatal_error(_DEBUG_CC, 'Unable to test "is active": "Active" field is not defined for "' . get_class($this) . '" data_source.');
        }
    }

    /**
    * Get name or array of names of fields, which points to the passed table name
    * 
    * @return mixed
    */
    public function _get_foreign_field($table) {
        if (isset($this->__foreign_fields[$table])) {
            return $this->__foreign_fields[$table];
        }
        else {
            return false;
        }
    }

    /**
    * Get array of "ORDER" SQL statements
    * 
    * @return array
    */
    public function _get_default_order() {
        return $this->__order_array_static;
    }

    /**
    * Get name of table (withuout prefix)
    * 
    * @return string
    */
    public function _get_table() {
        return $this->_table;
    }

    /**
    * Get table alias
    * 
    * @return string
    */
    public function _get_alias() {
        if (!$this->_alias) {
            $this->_alias = get_class($this); // $this->_table
        }
        return $this->_alias;
    }

    /**
    * Get name of primary key field
    * 
    * @return string
    */
    public function _get_primary_key() {
        return $this->__primary_key;
    }

    /**
    * Get name of 'sort' field
    * 
    * @return string
    */
    public function _get_sort_field() {
        return $this->__sort_field_name;
    }

    /**
    * Get fields
    * 
    * Работает в одном из трех режимов:
    *   - нет параметров кроме $values - вернет все поля, требующие пользовательского ввода, т.е. будут пропущены 
    *   поля primary_key, sort, created, modified
    *   - $include_list указан - вернет только те поля, на которые есть ссылки (имена) в этом массиве
    *   - $exclude_list указан - вернет только те поля, ссылки на которые отсутсвуют в этом массиве
    * 
    * Первый параметр ($values) может содержать одно из значений:
    *   - false - значит поля будут без предустановленных в них значений (или со значениями, 
    * которые вписаны в них прямо при описании свойства _fields)
    *   - ID существущей строки, из которой нужно выбрать значения и подставить их в соответвующие
    * поля
    *   - ассоциативный массив значений, которые нужно подставить в поля
    * 
    * @param mixed $values false, ID стоки, или массив со значениями
    * @param array $include_list список полей, которые нужно вернуть
    * @param array $exclude_list список полей, которые нужно исключить
    * @return array массив полей
    */
    public function _get_fields($values = false, $include_list = false, $exclude_list = false) {
        $ret = parent::_get_fields($values, $include_list, $exclude_list);
        return $ret;
    }

    public function _first_row($where = false, $columns = false) {
        return $this->__first($where, $columns, '_row');
    }

    public function _first_arow($where = false, $columns = false) {
        return $this->__first($where, $columns, '_arow');
    }

    protected function __first($where, $columns, $method) {
        $this->_reset_limit()->_limit(1);
        return call_user_func_array(array($this, $method), array($where, $columns));
    }

    public function _last_row($where = false, $columns = false) {
        return $this->__last($where, $columns, '_row');
    }

    public function _last_arow($where = false, $columns = false) {
        return $this->__last($where, $columns, '_arow');
    }

    protected function __last($where, $columns, $method) {
        $new_order = array();
        foreach ($this->__order_array as $o) {
            $o_parts = explode(' ', $o);
            if (isset($o_parts[1]) && strtolower(trim($o_parts[1])) == 'desc') {
                $direction = ' asc';
            }
            else {
                $direction = ' desc';
            }
            array_unshift($new_order, $o_parts[0] . $direction);
        }
        $this->_reset_order()->_order($new_order);
        $this->_reset_limit()->_limit(1);
        return call_user_func_array(array($this, $method), array($where, $columns));
    }

    public function _previous_row($current_id, $columns = false) {
        return $this->__prev($current_id, $columns, '_row');
    }

    public function _previous_arow($current_id, $columns = false) {
        return $this->__prev($current_id, $columns, '_arow');
    }

    protected function __prev($current_id, $columns, $method) {
        $order_where = $this->__get_prev_next_order_where($current_id, false);
        $new_order = array();
        foreach ($this->__order_array as $o) {
            $o_parts = explode(' ', $o);
            if (isset($o_parts[1]) && strtolower(trim($o_parts[1])) == 'desc') {
                $direction = ' asc';
            }
            else {
                $direction = ' desc';
            }
            array_unshift($new_order, $o_parts[0] . $direction);
        }
        $this->_reset_order();
        $this->_order($new_order);
        return call_user_func_array(array($this, $method), array($order_where, $columns));
    }

    public function _next_row($current_id, $columns = false) {
        return $this->__next($current_id, $columns, '_row');
    }

    public function _next_arow($current_id, $columns = false) {
        return $this->__next($current_id, $columns, '_arow');
    }

    protected function __next($current_id, $columns, $method) {
        $order_where = $this->__get_prev_next_order_where($current_id, true);
        return call_user_func_array(array($this, $method), array($order_where, $columns));
    }

    protected function __get_prev_next_order_where($current_id, $next = false) {
        $this->_save_snapshot('__get_prev_next_order_where');
        $data = $this->_row($current_id);
        $this->_restore_snapshot('__get_prev_next_order_where');
        $order_where = array(
            $this->_get_primary_key() . ' <>' => $current_id
        );
        foreach ($this->__order_array as $o) {
            $o_parts = explode(' ', $o);
            $table_field = $o_parts[0];
            if (isset($o_parts[1]) && strtolower(trim($o_parts[1])) == 'desc') {
                $direction = 'desc';
            }
            else {
                $direction = 'asc';
            }
            $table_field_parts = explode('.', $table_field);
            if (isset($table_field_parts[1])) {
                $table = $table_field_parts[0];
                $field = $table_field_parts[1];
            }
            else {
                $table = false;
                $field = $table_field_parts[0];
            }
            if ($direction == 'asc') {
                $order_where[$table_field . ($next ? ' >=' : ' <=')] = $data[$field];
            }
            else {
                $order_where[$table_field . ($next ? ' <=' : ' >=')] = $data[$field];
            }
        }
        return $order_where;
    }

    /**
    * Генерирует заголовок, описывающий переданную строку данных; заголовок берется из поля 
    * {$prefix_fields}title (если есть) или генерируется "Record ID: {$primary_key}"
    * 
    * @param array|int $row строка данных, полученная методом _adjusted_row
    * @return string сгенерированный заголовок поля
    */
    public function _get_row_title($row) {
        if (is_numeric($row)) { // ID is given
            $row = $this->_adjusted_row($row);
        }

        return 
            isset($row[$this->_prefix_fields . 'title'])?
                $row[$this->_prefix_fields . 'title']
                :
                'Record ID: ' . $row[$this->_get_primary_key()];
    }

    public function _activate($where) {
        if ($this->__active_field_name) {
            $this->_update($where, array(
                $this->__active_field_name => 1
            ));
        }
        else {
            _cc::fatal_error(_DEBUG_CC, 'Unable to activate: "Active" field is not defined for "' . get_class($this) . '" data_source.');
        }
    }

    public function _deactivate($where) {
        if ($this->__active_field_name) {
            $this->_update($where, array(
                $this->__active_field_name => 0
            ));
        }
        else {
            _cc::fatal_error(_DEBUG_CC, 'Unable to deactivate: "Active" field is not defined for "' . get_class($this) . '" data_source.');
        }
    }

    /*****************************************************************
    * Получение данных (SELECT)
    * 
    * TODO: парсить cols и добавлять псевдонимы в __join_weakness
    *****************************************************************/

    /**
    * Возвращает количество строк, попадающих под опциональное условие
    * 
    * В конце вызова автоматически вызывается метод $this->_reset()
    * 
    * @param mixed $where
    * @return int
    */
    // отменено
    public function _count($where = false, $cols = false) {
    // public function _count($where = false, $cols = false) {
        if ($cols !== false) {
            $this->_columns($cols);
        }
        $this->_where($where);
        if (
            sizeof($this->__group_array) ||
            // experemental 2011-07-28 - to support count with distinct columns
            trim($this->__generate_columns()) != '*'
        ) {
            // experemental 2011-09-16
            // $sql = $this->_generate_sql();
            $sql = $this->_reset_columns()->_columns($this->_get_primary_key())->_generate_sql();
            // EOF experemental 2011-09-16
            $q = $this->_db->_query('select count(*) from (' . $sql . ') as t');
            $res = $this->_db->_fetch_num($q);
        }
        else {
            // подправлено чтобы исправить проблему датагрида, см. там коммент
            // columns перенесено ниже и применяется без условия, т.к. в случае если в columns есть distinct, то пагинация работает не правильно
            $res = $this->__set_result_as_columns(false)->_select('count(*)', false, false);
            // отменено
            // $res = $this->__set_result_as_columns(false)->_select('count(' . $this->__generate_columns() . ')', false, false);
        }
        return $res[0];
    }

    /**
    * Возвращает все строки, попадающие под условие
    * 
    * В конце вызова автоматически вызывается метод $this->_reset()
    * 
    * @param mixed $where
    * @param string $cols
    * @return array массив, количество элементов в нем будет совпадать с количесвом найденых строк; каждый элемент - ассоциативный массив
    */
    public function _rows($where = false, $cols = false) {
        if ($cols !== false) {
            $this->_columns($cols);
        }
        $this->_where($where);
        return $this->__set_result_as_columns(false)->_select($this->__generate_columns(), false, false);
    }

    /**
    * Полностью аналогичный методу arows, оставлен для совместимости
    */
    public function _adjusted_rows($where = false, $cols = false, $mode = false) {
        return $this->_arows($where, $cols, $mode);
    }

    /**
    * Возвращает все строки, попадающие под условие; результат будет автоматически пропущен через 
    * встроенный метод _adjust_output
    * 
    * В конце вызова автоматически вызывается метод $this->_reset()
    * 
    * @param mixed $where
    * @param string $cols
    * @param mixed $mode режим, в котором должен работать _adjust_output
    * @return array массив, количество элементов в нем будет совпадать с количесвом найденых строк; каждый элемент - ассоциативный массив
    */
    public function _arows($where = false, $cols = false, $mode = false) {
        if ($cols !== false) {
            $this->_columns($cols);
        }
        $this->_where($where);
        return $this->__set_result_as_columns(false)->_select($this->__generate_columns(), true, $mode);
    }

    /**
    * Возвращает первую найденую строку, попадающую под условие
    * 
    * В конце вызова автоматически вызывается метод $this->_reset()
    * 
    * @param mixed $where
    * @param mixed $cols
    * @return array ассоциативный массив
    */
    public function _row($where = false, $cols = false) {
        if ($cols !== false) {
            $this->_columns($cols);
        }
        $this->_where($where);
        $res = $this->_limit(1)->__set_result_as_columns(false)->_select($this->__generate_columns(), false, false);
        if (isset($res[0])) {
            return $res[0];
        }
        else {
            return false;
        }
    }

    /**
    * Полностью аналогичный методу arow, оставлен для совместимости
    */
    public function _adjusted_row($where = false, $cols = false, $mode = false) {
        return $this->_arow($where, $cols, $mode);
    }

    /**
    * Возвращает первую найденую строку, попадающую под условие; результат будет автоматически пропущен через 
    * встроенный метод _adjust_output
    * 
    * В конце вызова автоматически вызывается метод $this->_reset()
    * 
    * @param mixed $where
    * @param mixed $cols
    * @return array Ассоциативный массив
    */
    public function _arow($where = false, $cols = false, $mode = false) {
        if ($cols !== false) {
            $this->_columns($cols);
        }
        $this->_where($where);
        $res = $this->_limit(1)->__set_result_as_columns(false)->_select($this->__generate_columns(), true, $mode);
        if (isset($res[0])) {
            return $res[0];
        }
        else {
            return false;
        }
    }

    /**
    * Возвращает все строки, попадающие под условие; результат форматировать в столбцы
    * 
    * В конце вызова автоматически вызывается метод $this->_reset()
    * 
    * @param mixed $where
    * @param mixed $cols
    * @return array Массив, количество элементов которого совпадает с количеством запрошеных столбцов; каждый элемент массива является массивом с количесвом элементов, совпадающим с количесвом найденых строк
    */
    public function _cols($where = false, $cols = false) {
        if ($cols !== false) {
            $this->_columns($cols);
        }
        $this->_where($where);
        return $this->__set_result_as_columns(true)->_select($this->__generate_columns(), false, false);
    }

    /**
    * Полностью аналогичный методу acols, оставлен для совместимости
    */
    public function _adjusted_cols($where = false, $cols = false, $mode = false) {
        return $this->_acols($where, $cols, $mode);
    }

    /**
    * Возвращает все строки, попадающие под условие; результат форматировать в столбцы; результат 
    * будет автоматически пропущен через встроенный метод _adjust_output
    * 
    * В конце вызова автоматически вызывается метод $this->_reset()
    * 
    * @param mixed $where
    * @param mixed $cols
    * @return array Массив, количество элементов которого совпадает с количеством запрошеных столбцов; каждый элемент массива является массивом с количесвом элементов, совпадающим с количесвом найденых строк
    */
    public function _acols($where = false, $cols = false, $mode = false) {
        if ($cols !== false) {
            $this->_columns($cols);
        }
        $this->_where($where);
        return $this->__set_result_as_columns(true)->_select($this->__generate_columns(), true, $mode);
    }

    /**
    * Возвращает массив ID для строк, попадающих под условие $where
    * 
    * @param mixed $where
    * @return string|object
    */
    public function _ids($where = false) {
        return $this->_rows($where, $this->_get_alias() . '.' . $this->_get_primary_key());
    }

    /**
    * Возвращает данные по предварительным настройкам, именно этот метод в конце концов вызывают 
    * все описаные выше, поэтому если необходимо изменить поведение всех методов, отвечающих за 
    * получение данных, перегружать нужно только этот
    * 
    * @param mixed $result_as_columns
    */
    public function _select($columns, $adjusted = false, $mode = false) {
        return $this->_low_select($columns, $adjusted, $mode);
    }

    /**
    * Генерирует SQL запрос и возвращает его в виде строки
    */
    public function _generate_sql($where = false, $cols = false) {
        if ($cols !== false) {
            $this->_columns($cols);
        }
        $this->_where($where);

        $this->__prepare_join_array();
        $select_sql = 'select ' . $this->__generate_columns();
        $from_sql = $this->__generate_from();
        $where_sql = $this->__generate_where();
        $having_sql = $this->__generate_having();
        $order_sql = $this->__generate_order();
        $group_sql = $this->__generate_group();
        $limit_sql = $this->_db->_sql_limit($this->__limit1_number, $this->__limit2_number);

        $this->_reset();

        return $select_sql . $from_sql . $where_sql . $group_sql . $having_sql . $order_sql . $limit_sql;
    }

    public function _low_select($columns, $adjusted, $mode) {
        $result_as_columns = $this->__get_result_as_columns();

        $this->__prepare_join_array();

        $select_sql = 'select ' . $columns;
        $from_sql = $this->__generate_from();
        $where_sql = $this->__generate_where();
        $having_sql = $this->__generate_having();
        $order_sql = $this->__generate_order();
        $group_sql = $this->__generate_group();

        $limit1 = $this->__limit1_number;
        $limit2 = $this->__limit2_number;
        if ($adjusted) {
            $adjust_method = array(
                '_adjust_db_output',
                '_adjust_output'
            );
        }
        else {
            $adjust_method = '_adjust_db_output';
        }
        if ($result_as_columns) {
            $this->__data = $this->_db->__low_cols($select_sql . $from_sql . $where_sql . $group_sql . $having_sql . $order_sql, $limit1, $limit2, $this, $adjust_method, $mode);
        } else {
            $this->__data = $this->_db->__low_rows($select_sql . $from_sql . $where_sql . $group_sql . $having_sql . $order_sql, $limit1, $limit2, $this, $adjust_method, $mode);
        }

        $this->_reset();

        return $this->__data;
    }

    /**
    * Вызывается непосредственно перед занесением данных в БД методами _insert и _update. Может 
    * быть удобно перегружать в связке с методом _adjust_db_output для того что бы работать в PHP с 
    * данными в одном формате, а в БД эти данные хранить в другом формате
    * 
    * @param array $in данные для модификации; важно помнить, что могли быть получены только выборочные данные, поэтому перед модификацией следует проверять данные на наличие с помощью функции isset
    * @return array модифицированные данные
    */
    public function _adjust_db_input($in) {
        return $in;
    }

    /**
    * Вызывается непосредственно после получения данных из БД методом _select. Может 
    * быть удобно перегружать в связке с методом _adjust_db_input для того что бы работать в PHP с 
    * данными в одном формате, а в БД эти данные хранить в другом формате
    * 
    * @param array $in данные для модификации; важно помнить, что могли быть получены только выборочные данные, поэтому перед модификацией следует проверять данные на наличие с помощью функции isset
    * @return array модифицированные данные
    */
    public function _adjust_db_output($in, $mode = false) {
        $adjusted = array(get_class($this));
        for ($i = 0; $i < sizeof($this->__join_array); $i++) {
            $test_class = get_class($this->__join_array[$i]['data_source']);
            if (!in_array($test_class, $adjusted)) {
                $in = $this->__join_array[$i]['data_source']->_adjust_db_output($in, $mode);
                $adjusted[] = $test_class;
            }
        }
        return $in;
    }

    /**
    * Форматировние данных перед выдачей их пользователю; при перегрузке важно помнить, что нужно
    * вызывать parent::_adjust_output(...) если вы хотите просто дополнить существующий 
    * функционал, а не переделать его полностью
    * 
    * @param mixed $in данные для модификации
    * @param string $mode режим, в котором должен работать метод; при перегрузке данного метода следует использовать этот параметр что бы ресурсоемкие операции выполнять только при необходимости
    * @return mixed модифицированные данные
    */
    public function _adjust_output($in, $mode = false) {
        foreach ($this->_fields as $field) {
            if (
                isset($field['name']) &&
                isset($in[$field['name']])
            ) {
                if (isset($field['adjust_output'])) {
                    if (!is_array($field['adjust_output'])) {
                        $field['adjust_output'] = array($field['adjust_output']);
                    }
                    for ($i = 0; $i < sizeof($field['adjust_output']); $i++) {
                        if (isset($field['adjust_output_class']) || method_exists($this, $field['adjust_output'][$i])) {
                            if (isset($field['adjust_output_class'])) {
                                if (is_array($field['adjust_output_class'])) {
                                    $class = $field['adjust_output_class'][$i];
                                }
                                else {
                                    $class = $field['adjust_output_class'];
                                }
                                if ($class) {
                                    $object = $this->_single($class);
                                }
                                else {
                                    $object = $this;
                                }
                            }
                            else {
                                $object = $this;
                            }
                            $in[$field['name']] = call_user_func_array(array($object, $field['adjust_output'][$i]), array($in[$field['name']], $mode, $field, $this));
                        }
                        else if (function_exists($field['adjust_output'][$i])) {
                            $in[$field['name']] = $field['adjust_output'][$i]($in[$field['name']], $mode, $field, $this);
                        }
                        else {
                            _cc::debug_message(_DEBUG_CC, 'Unable to adjust output using <b>' . $field['adjust_output'][$i] . '</b> function/method: such function/method not exists', 'error');
                        }
                    }
                }
                else if (isset($field['binary_path'])) {
                    if ($in[$field['name']]) {
                        $in[$field['name']] = $field['binary_path'] . $in[$field['name']];
                    }
                }
            }
        }
        $adjusted = array(get_class($this));
        for ($i = 0; $i < sizeof($this->__join_array); $i++) {
            $test_class = get_class($this->__join_array[$i]['data_source']);
            if (!in_array($test_class, $adjusted)) {
                $in = $this->__join_array[$i]['data_source']->_adjust_output($in, $mode);
                $adjusted[] = $test_class;
            }
        }
        return $in;
    }

    /**
    * Встроенные метод валидации поля на уникальность
    * 
    * @param mixed $value значение
    * @param mixed $field полный массив, описывающий данное поле
    * @param mixed $id ID строки, которая редактировалась; если это новые данные, то false
    * @param mixed $values ассоциативный массив со всем значениями, получеными от пользователя
    */
    protected function _validate_unique($value, $field, $id, $values) {
        $this->_save_snapshot('validate_unique');
        $where = array($field['name'] => $value);
        if ($id) {
            $where[$this->_get_primary_key() . ' <>'] = $id;
        }
        $row = $this->_reset()->_row($where);
        $this->_restore_snapshot('validate_unique');
        if ($row) {
            return false;
        }
        else {
            return true;
        }
    }

    /*****************************************************************
    * Добавление данных (INSERT)
    *****************************************************************/

    /**
    * Вставить данные. Вызывает _low_insert, может быть перегружен что бы выполнить какие-то 
    * дополнительные действия при вставке, например, передать по цепочке подчиненному data_source
    * команду _insert
    * 
    * @param array $values ассоциативный массив со значениями; будут использованы только те ключи, которые существуют в качестве имен полей в $this->_fields
    * @return mixed ID вставленой строки
    */
    /* ------------------ убрано ----------------------------------------------
    * Для каждого прикрепленного (join) data_source будет выполнен аналогичный 
    * запрос _insert, в который будет передан параметр $values "как есть"; будет предпринята попытка 
    * автоматически связать полученый результат по ключу foreign_table
    * 
    * В конце вызова автоматически вызывается метод $this->_reset()
    -------------------- /убрано ------------------------------------------- */
    public function _insert($values = array()) {
        return $this->_low_insert($values);
    }

    /**
    * Выполняет SQL запрос, не может быть перегружен и дополнен; если нужно выполнять дополнительные 
    * действия при вставке данных следует перегружать метод _insert
    * 
    * @param array $values ассоциативный массив со значениями; будут использованы только те ключи, которые существуют в качестве имен полей в $this->_fields
    * @return mixed ID вставленой строки
    */
    public function _low_insert($values = array()) {
        if (!in_array($this->_get_primary_key(), array_keys($values), true) && empty($this->_fields[$this->_get_primary_key()]['auto_increment'])) {
            $this->_save_snapshot('insert');
            $values[$this->_get_primary_key()] = $this->_reset()->_row(false, 'max(' . $this->_get_primary_key() . ')') + 1;
            $this->_restore_snapshot('insert');
        }

        /* (Для каждого прикрепленного data_source запрос _insert)
        for ($i = 0; $i < sizeof($this->__temporary_join_storage); $i++) {
            $foreign_key = $this->_prefix_fields . $this->__temporary_join_storage[$i]['data_source']->_get_primary_key();
            $foreign_value = $this->__temporary_join_storage[$i]['data_source']->_insert($values);
            if (
                isset($this->__fields_names[$foreign_key]) && 
                isset($this->_fields[$this->__fields_names[$foreign_key]]['foreign_table']) &&
                $this->_fields[$this->__fields_names[$foreign_key]]['foreign_table'] == $this->__temporary_join_storage[$i]['data_source']->_get_table()
            ) {
                $values[$foreign_key] = $foreign_value;
            }
        }
        EOF */

        $values = $this->_adjust_db_input($values);

        foreach ($this->_fields as &$field) {
            if (!isset($values[$field['name']])) { // только если не заданы явно
                // add 'create' and 'modify' fields
                if (
                    (isset($field['created']) && $field['created']) ||
                    (isset($field['modified']) && $field['modified'])
                ) {
                    if (!isset($field['sql_type'])) {
                        $field['sql_type'] = 'datetime';
                    }
                    if (strtolower($field['sql_type']) == 'datetime') {
                        $values[$field['name']] = date('Y-m-d H:i:s');
                    } elseif (strtolower($field['sql_type']) == 'int') {
                        $values[$field['name']] = time();
                    }
                }
                // add 'sort' field
                if (isset($field['sort']) && $field['sort'] && !isset($values[$field['name']])) {
                    $values[$field['name']] = $this->_row(array(), 'max(' . $field['name'] . ')') + 1;
                    $this->_restore_snapshot('insert');
                }
                // add 'value' field
                if (isset($field['value'])) {
                    $values[$field['name']] = $field['value'];
                }
            }
            else if (isset($this->__binary_fields[$field['name']])) { // binary
                $ret = $this->__adjust_binary_filename($values[$field['name']], $this->__binary_fields[$field['name']]);
                if ($ret !== false) {
                    $values[$field['name']] = $ret;
                }
                else {
                    unset($values[$field['name']]);
                }
            }
        }

        // generate 'insert' query
        $ins = '';
        $vals = '';
        $fields_names = array_keys($this->__fields_names);
        foreach ($values as $key => $val) {
            if ($key && in_array($key, $fields_names)) {
                if ($ins) {
                    $ins .= ',';
                    $vals .= ',';
                }
                $ins .= '`' . $key . '`';
                $vals .= '\'' . $this->_db->_escape($val) . '\'';
            }
        }
        $this->_db->_query('insert into ' . $this->_db->_get_prefix() . $this->_table . ' (' . $ins . ') values (' . $vals . ')');

        $this->_reset();

        if (isset($values[$this->_get_primary_key()])) {
            return $values[$this->_get_primary_key()];
        }
        else {
            return $this->_db->_insert_id();
        }
    }

    /*****************************************************************
    * Обновление данных (UPDATE)
    *****************************************************************/

    /**
    * Обновить все строки, попадающие под условие. Вызывает _low_update, может быть перегружен что бы выполнить какие-то 
    * дополнительные действия при вставке, например, передать по цепочке подчиненному data_source
    * команду _update
    * 
    * @param mixed $where условие
    * @param array $values ассоциативный массив со значениями; будут использованы только те ключи, которые существуют в качестве имен полей в $this->_fields
    * @return object $this
    */
    public function _update($where = false, $values = array()) {
        return $this->_low_update($where, $values);
    }

    /**
    * Выполняет SQL запрос, не может быть перегружен и дополнен; если нужно выполнять дополнительные 
    * действия при вставке данных следует перегружать метод _update
    * 
    * @param mixed $where условие
    * @param array $values ассоциативный массив со значениями; будут использованы только те ключи, которые существуют в качестве имен полей в $this->_fields
    * @return object $this
    */
    public function _low_update($where = false, $values = array()) {
        // update 'modify' fields
        // TODO: добавить обновление поля modified при изменении данных из подчиненных таблиц
//        for ($i = 0; $i < sizeof($this->_fields); $i++) {
        foreach ($this->_fields as &$field) {
            if (!isset($values[$field['name']])) { // только если не заданы явно
                if (
                    (isset($field['modified']) && $field['modified'])
                ) {
                    if (!isset($field['sql_type'])) {
                        $field['sql_type'] = 'datetime';
                    }
                    if (strtolower($field['sql_type']) == 'datetime') {
                        $values[$field['name']] = date('Y-m-d H:i:s');
                    } elseif (strtolower($field['sql_type']) == 'int') {
                        $values[$field['name']] = time();
                    }
                }
            }
            else if (isset($this->__binary_fields[$field['name']])) { // binary
                $ret = $this->__adjust_binary_filename($values[$field['name']], $this->__binary_fields[$field['name']]);
                if ($ret !== false) {
                    $values[$field['name']] = $ret;
                }
                else {
                    unset($values[$field['name']]);
                }
            }
        }

        $values = $this->_adjust_db_input($values);

        // generate 'update' query
        $vals = '';
        $fields_names = array_keys($this->__fields_names);
        foreach ($values as $key => $val) {
            if (is_numeric($key) && $val) {
                if ($vals) {
                    $vals .= ',';
                }
                $vals .= $val;
            }
            else if ($key && in_array($key, $fields_names)) {
                if ($vals) {
                    $vals .= ',';
                }
                $vals .= '`' . $key . '`' . '=\'' . $this->_db->_escape($val) . '\'';
            }
        }
        if ($vals) {
            $this->_where($where);

            $this->__prepare_join_array();

            $update_sql = 'update ' . $this->_db->_get_prefix() . $this->_table . ' as ' . $this->_get_alias();
            foreach ($this->__join_array as $join_line) {
                $update_sql .= $join_line['sql'];
            }

            $sql_where = $this->__generate_where();
            if ($sql_where) {
                $update_sql = $update_sql . ' set ' . $vals . $sql_where;

                $this->_db->_query($update_sql, $this->__limit1_number, $this->__limit2_number);
            }
            else {
                _cc::fatal_error(_DEBUG_CC, 'Empty WHERE statement when updating inside ' . get_class($this));
            }
        }

        $this->_reset();

        return $this;
    }

    /*****************************************************************
    * Удаление данных (DELETE)
    *****************************************************************/

    /**
    * Удалить все строки, попадающие под условие. Вызывает _low_delete, может быть перегружен что бы выполнить какие-то 
    * дополнительные действия, например, передать по цепочке подчиненному data_source
    * команду _delete
    * 
    * @param mixed $where условие
    * @return object $this
    */
    public function _delete($where = array(), $allow_truncate = false) {
        if ($this->_cleanup_settings || $this->_force_cleanup) {
            $this->_save_snapshot('delete');
            $data_lines = $this->_rows($where);
            $this->_restore_snapshot('delete');

            for ($i = 0; $i < sizeof($data_lines); $i++) {
                $this->_cleanup($data_lines[$i]);
            }
        }
        return $this->_low_delete($where, $allow_truncate);
    }

    /**
    * Вызывается для каждой удаляемой строки что бы удалить мусор, связанный с удаляемыми данными
    * 
    * @param mixed $data_line
    */
    protected function _cleanup($data_line) {
        // удалить файлы
        foreach ($this->_cleanup_settings['binary_fields'] as $field => $description) {
            if ($data_line[$field] && file_exists($description['path'] . $data_line[$field])) {
                if ($description['cleanup_mode'] == 'unlink_last') {
                    $this->_save_snapshot('cleanup');
                    if ($this->_row(array(
                        $field => $data_line[$field],
                        $this->_get_primary_key() . ' <>' => $data_line[$this->_get_primary_key()]
                    ))) {
                        $unlink = false;
                    }
                    else {
                        $unlink = true;
                    }
                    $this->_restore_snapshot('cleanup');
                }
                else {
                    $unlink = true;
                }
                if ($unlink) {
                    unlink($description['path'] . $data_line[$field]);
                }
            }
        }
        // удалить записи из датасорцев
        foreach ($this->_cleanup_settings['data_sources'] as $ds) {
            if ($ds['this_flag']) {
                $ds['data_source']->_delete(array(
                    $ds['data_source']->_get_primary_key() => $data_line[$ds['foreign_key']]
                ));
            }
            else {
                $ds['data_source']->_delete(array(
                    $ds['foreign_key'] => $data_line[$this->_get_primary_key()]
                ));
            }
        }
    }

    /**
    * Выполняет SQL запрос, не может быть перегружен и дополнен; если нужно выполнять дополнительные 
    * действия при удалении данных следует перегружать метод _delete
    * 
    * @param mixed $where условие
    * @return object $this
    */
    public function _low_delete($where = false, $allow_truncate = false) {
        $this->_where($where);
        $sql_where = $this->__generate_where(false);
        if ($allow_truncate || $sql_where) {
            $sql = 'delete from ' . $this->_db->_get_prefix() . $this->_table . $sql_where;
            $this->_db->_query($sql, $this->__limit1_number, $this->__limit2_number);
            $this->_reset();
            return $this;
        }
        else {
            _cc::fatal_error(_DEBUG_CC, 'Empty WHERE statement when deleting inside ' . get_class($this));
        }
    }

    /***********************************************************************************************
    * Перемещение данных (MOVE)
    * 
    * эти методы УЧИТЫВАЮТ текущие условия (установленные с помощью _where); 
    * однако следует быть осторожными с неточными условиями (т.е. всеми, кроме '=', а 
    * так же соединенными операторами OR) - они игнорируются, поэтому поведение методов при наличии 
    * таких условий может отличаться от желаемого или ожидаемого
    ***********************************************************************************************/

    /**
    * Переместить строку вверх
    * 
    * @param mixed $id ID строки
    * @return object $this
    */
    public function _move_up($id) {
        if ($this->_get_sort_field()) {
            $this->_save_snapshot('move_up');
            $this_sort = $this->_row($id, $this->_get_sort_field());
            $this->_restore_snapshot('move_up');
            $before_id = $this
                ->_order($this->_get_sort_field() . ' desc')
                ->_row(array(
                    $this->_get_sort_field() . ' <' => $this_sort
                ), $this->_get_primary_key())
            ;
            $this->_restore_snapshot('move_up');
            if ($before_id) {
                $this->_place_before($id, $before_id);
            }
        }
        else {
            _cc::debug_message(_DEBUG_CC, 'Sorting field should be defined to use movement commands', 'error');
        }
        return $this;
    }

    /**
    * Переместить строку вниз
    * 
    * @param mixed $id ID строки
    * @return object $this
    */
    public function _move_down($id) {
        if ($this->_get_sort_field()) {
            $this->_save_snapshot('move_down');
            $this_sort = $this->_row($id, $this->_get_sort_field());
            $this->_restore_snapshot('move_down');
            $after_id = $this
                ->_order($this->_get_sort_field())
                ->_row(array(
                    $this->_get_sort_field() . ' >' => $this_sort
                ), $this->_get_primary_key())
            ;
            $this->_restore_snapshot('move_down');
            if ($after_id) {
                $this->_place_after($id, $after_id);
            }
        }
        else {
            _cc::debug_message(_DEBUG_CC, 'Sorting field should be defined to use movement commands', 'error');
        }
        return $this;
    }

    /**
    * Переместить строку - поставить ее перед другой строкой
    * 
    * @param mixed $place_id ID строки которую надо переместить
    * @param mixed $before_id ID строки, перед которой надо вставить указаную строку
    * @return object $this
    */
    public function _place_before($place_id, $before_id) {
        if (is_array($place_id)) {
            // рекурсивно вызвать этот же метод для каждого элемента
            foreach ($place_id as $place_id_single) {
                $this->_place_before($place_id_single, $before_id);
            }
        }
        else {
            if ($this->_get_sort_field()) {
                $this->_save_snapshot('place_before');

                // условие обрабатывается вручную, т.к. в случае, если перемещаемая строка не 
                // входит в подпространство, выделенное с помощью where, то для нее нужно будет 
                // переустановить все значения, а переустановить мы сможем только точные условия
                $strict_where = $this->_get_strict_where();

                $this->_reset();
                if ($place_id == $this->_where($strict_where)->_row($place_id, $this->_get_primary_key())) {
                    // значит $place_id находится в том же подпространстве (набор условий where), что 
                    // и $before_id, значит можно использовать более быструю процедуру перемещения
                    $place_sort = $this->_row($place_id, $this->_get_sort_field());
                    $before_sort = $this->_row($before_id, $this->_get_sort_field());

                    if ($place_sort > $before_sort) {
                        $move_rows = $this->_order($this->_get_sort_field())->_where($strict_where)->_rows(array(
                            $this->_get_sort_field() . ' >=' => $before_sort,
                            $this->_get_sort_field() . ' <' => $place_sort,
                        ), $this->_get_primary_key() . ', ' . $this->_get_sort_field());
                        for ($i = 0; $i < sizeof($move_rows); $i++) {
                            // 2011-12-28
                            // были проблемы когда при перемещении записей одной таблицы нужно было
                            // синхронно перемещать записи другой таблицы
                            // $this->_low_update($move_rows[$i][$this->_get_primary_key()], array(
                            $this->_update($move_rows[$i][$this->_get_primary_key()], array(
                                $this->_get_sort_field() => $move_rows[$i][$this->_get_sort_field()] + 1
                            ));
                        }
                        // 2011-12-28
                        // $this->_low_update($place_id, array(
                        $this->_update($place_id, array(
                            $this->_get_sort_field() => $before_sort
                        ));
                    }

                    else if ($place_sort < $before_sort) {
                        $move_rows = $this->_order($this->_get_sort_field())->_where($strict_where)->_rows(array(
                            $this->_get_sort_field() . ' >' => $place_sort,
                            $this->_get_sort_field() . ' <' => $before_sort,
                        ), $this->_get_primary_key() . ', ' . $this->_get_sort_field());
                        if (sizeof($move_rows)) { // otherwise no sence in movement
                            for ($i = 0; $i < sizeof($move_rows); $i++) {
                                if ($i == sizeof($move_rows) - 1) {
                                    // save last sort value to use for place_id
                                    $place_sort_new = $move_rows[$i][$this->_get_sort_field()];
                                }
                                // 2011-12-28
                                // $this->_low_update($move_rows[$i][$this->_get_primary_key()], array(
                                $this->_update($move_rows[$i][$this->_get_primary_key()], array(
                                    $this->_get_sort_field() => $move_rows[$i][$this->_get_sort_field()] - 1
                                ));
                            }
                            // 2011-12-28
                            // $this->_low_update($place_id, array(
                            $this->_update($place_id, array(
                                $this->_get_sort_field() => $place_sort_new
                            ));
                        }
                    }
                }
                else {
                    // $place_id не попадает под условия, используемые для получения $before_id
                    // нужно полностью пройтись по всем записям и переустановить sort_field
                    $rows = $this->_order($this->_get_sort_field())->_where($strict_where)->_rows(
                        array(), 
                        $this->_get_primary_key() . ', ' . $this->_get_sort_field()
                    );
                    for ($i = 0, $sort = 1; $i < sizeof($rows); $i++) {
                        if ($rows[$i][$this->_get_primary_key()] == $before_id) {
                            $place_sort_new = $sort;
                            $sort++;
                        }
                        // 2011-12-28
                        // $this->_low_update($rows[$i][$this->_get_primary_key()], array(
                        $this->_update($rows[$i][$this->_get_primary_key()], array(
                            $this->_get_sort_field() => $sort
                        ));
                        $sort++;
                    }
                    // найти и переустановить для новой строки все поля из where, которые имеют оператор '='
                    $update = array_merge($strict_where, array(
                        $this->_get_sort_field() => $place_sort_new
                    ));
                    // 2011-12-28
                    // $this->_low_update($place_id, $update);
                    $this->_update($place_id, $update);
                }
                $this->_restore_snapshot('place_before');
            }
            else {
                _cc::debug_message(_DEBUG_CC, 'Sorting field should be defined to use movement commands', 'error');
            }
        }
        return $this;
    }

    /**
    * Переместить строку - поставить ее после другой строки
    * 
    * @param mixed $place_id ID строки которую надо переместить
    * @param mixed $before_id ID строки, после которой надо вставить указаную строку
    * @return object $this
    */
    public function _place_after($place_id, $after_id) {
        if (is_array($place_id)) {
            // рекурсивно вызвать этот же метод для каждого элемента
            for ($i = sizeof($place_id) - 1; $i >= 0 ; $i--) {
                // в обратном порядке что бы записи вставлялись в том порядке как при вырезании
                $this->_place_after($place_id[$i], $after_id);
            }
        }
        else {
            if ($this->_get_sort_field()) {
                $this->_save_snapshot('place_after');

                // условие обрабатывается вручную, т.к. в случае, если перемещаемая строка не 
                // входит в подпространство, выделенное с помощью where, то для нее нужно будет 
                // переустановить все значения, а переустановить мы сможем только точные условия
                $strict_where = $this->_get_strict_where();

                $this->_reset();
                if ($place_id == $this->_where($strict_where)->_row($place_id, $this->_get_primary_key())) {
                    // значит $place_id находится в том же подпространстве (набор условий where), что 
                    // и $after_id, значит можно использовать более быструю процедуру перемещения
                    $place_sort = $this->_row($place_id, $this->_get_sort_field());
                    $after_sort = $this->_row($after_id, $this->_get_sort_field());

                    if ($place_sort > $after_sort) {
                        $move_rows = $this->_order($this->_get_sort_field())->_where($strict_where)->_rows(array(
                            $this->_get_sort_field() . ' >' => $after_sort,
                            $this->_get_sort_field() . ' <=' => $place_sort,
                        ), $this->_get_primary_key() . ', ' . $this->_get_sort_field());
                        if (sizeof($move_rows)) { // otherwise no sence in movement
                            for ($i = 0; $i < sizeof($move_rows); $i++) {
                                if ($i == 0) {
                                    // save last sort value to use for place_id
                                    $place_sort_new = $move_rows[$i][$this->_get_sort_field()];
                                }
                                // 2011-12-28
                                // $this->_low_update($move_rows[$i][$this->_get_primary_key()], array(
                                $this->_update($move_rows[$i][$this->_get_primary_key()], array(
                                    $this->_get_sort_field() => $move_rows[$i][$this->_get_sort_field()] + 1
                                ));
                            }
                            // 2011-12-28
                            // $this->_low_update($place_id, array(
                            $this->_update($place_id, array(
                                $this->_get_sort_field() => $place_sort_new
                            ));
                        }
                    }

                    else if ($place_sort < $after_sort) {
                        $move_rows = $this->_order($this->_get_sort_field())->_where($strict_where)->_rows(array(
                            $this->_get_sort_field() . ' >' => $place_sort,
                            $this->_get_sort_field() . ' <=' => $after_sort,
                        ), $this->_get_primary_key() . ', ' . $this->_get_sort_field());
                        for ($i = 0; $i < sizeof($move_rows); $i++) {
                            // 2011-12-28
                            // $this->_low_update($move_rows[$i][$this->_get_primary_key()], array(
                            $this->_update($move_rows[$i][$this->_get_primary_key()], array(
                                $this->_get_sort_field() => $move_rows[$i][$this->_get_sort_field()] - 1
                            ));
                        }
                        // 2011-12-28
                        // $this->_low_update($place_id, array(
                        $this->_update($place_id, array(
                            $this->_get_sort_field() => $after_sort
                        ));
                    }
                }
                else {
                    // $place_id не попадает под условия, используемые для получения $after_id
                    // нужно полностью пройтись по всем записям и переустановить sort_field
                    $rows = $this->_order($this->_get_sort_field())->_where($strict_where)->_rows(
                        array(), 
                        $this->_get_primary_key() . ', ' . $this->_get_sort_field()
                    );
                    for ($i = 0, $sort = 1; $i < sizeof($rows); $i++) {
                        // 2011-12-28
                        // $this->_low_update($rows[$i][$this->_get_primary_key()], array(
                        $this->_update($rows[$i][$this->_get_primary_key()], array(
                            $this->_get_sort_field() => $sort
                        ));
                        $sort++;
                        if ($rows[$i][$this->_get_primary_key()] == $after_id) {
                            $place_sort_new = $sort;
                            $sort++;
                        }
                    }
                    // найти и переустановить для новой строки все поля из where, которые имеют оператор '='
                    $update = array_merge($strict_where, array(
                        $this->_get_sort_field() => $place_sort_new
                    ));
                    // 2011-12-28
                    // $this->_low_update($place_id, $update);
                    $this->_update($place_id, $update);
                }
                $this->_restore_snapshot('place_after');
            }
            else {
                _cc::debug_message(_DEBUG_CC, 'Sorting field should be defined to use movement commands', 'error');
            }
        }
        return $this;
    }

    /**
    * Переместить строку - поставить ее с учетом условий where
    *
    * @param mixed $place_id ID строки которую надо переместить
    * @return object $this
    */
    public function _place($place_id) {
        if (is_array($place_id)) {
            // рекурсивно вызвать этот же метод для каждого элемента
            // for ($i = sizeof($place_id) - 1; $i >= 0 ; $i--) {
                // ??? (получается все наоборот) в обратном порядке что бы записи вставлялись в том порядке как при вырезании
            for ($i = 0; $i < sizeof($place_id); $i++) {
                $this->_place($place_id[$i]);
            }
        }
        else {
            if ($this->_get_sort_field()) {
                $this->_save_snapshot('place');

                // условие обрабатывается вручную, т.к. в случае, если перемещаемая строка не
                // входит в подпространство, выделенное с помощью where, то для нее нужно будет
                // переустановить все значения, а переустановить мы сможем только точные условия
                $strict_where = $this->_get_strict_where();

                $this->_reset();
                $place_sort_new = $this->_row($strict_where, 'max(' . $this->_get_sort_field() . ')') + 1;
                // 2011-12-28
                // $this->_low_update($place_id, array_merge(
                $this->_update($place_id, array_merge(
                    $strict_where,
                    array(
                        $this->_get_sort_field() => $place_sort_new
                    )
                ));
                $this->_restore_snapshot('place');
            }
            else {
                _cc::debug_message(_DEBUG_CC, 'Sorting field should be defined to use movement commands', 'error');
            }
        }
        return $this;
    }

    protected function _get_strict_where() {
        $strict_where = array();

        // счет начинается с 1, т.к. нулевой элемент указывает на тип склеивания
        // TODO: обрабатывать рекурсивные условия
        for ($i = 1; $i < sizeof($this->__where_array); $i++) {
            if ($this->__where_array[$i]['operator'] == '=') {
                $strict_where[$this->__where_array[$i]['field']] = $this->__where_array[$i]['value'];
            }
        }

        return $strict_where;
    }

    /*****************************************************************
    * Используются для разных задач
    *****************************************************************/

    /**
    * Сбрасывает все накопленые настройки data_source, т.е. предыдущие вызовы методов
    *   - _columns
    *   - _join
    *   - _where
    *   - _group
    *   - _order
    *   - _having
    *   - _limit
    * 
    * @return data_source $this
    */
    public function _reset() {
        parent::_reset();

        $this->_reset_columns();
        $this->_reset_join();
        $this->_reset_where();
        $this->_reset_group();
        $this->_reset_order();
        $this->_reset_having();
        $this->_reset_limit();

        return $this;
    }

    public function _reset_columns() {
        $this->__columns_array = array();
        return $this;
    }

    public function _reset_join() {
        $this->__join_array = array();
        $this->__join_array_ready = false;
        $this->__join_weekness_array = array();
        return $this;
    }

    public function _reset_where() {
        $this->__where_array = array();
        return $this;
    }

    public function _reset_group() {
        $this->__group_array = array();
        return $this;
    }

    public function _reset_order() {
        $this->__order_array = array();
        return $this;
    }

    public function _reset_having() {
        $this->__having_array = array();
        return $this;
    }

    public function _reset_limit() {
        $this->__limit1_number = false;
        $this->__limit2_number = false;
        return $this;
    }

    /**
    * Сохраняет все накопленые настройки _db_table в специальном хранилище под заданным именем
    *   - _columns
    *   - _join
    *   - _where
    *   - _group
    *   - _order
    *   - _having
    *   - _limit
    * 
    * @param mixed $name имя
    */
    public function _save_snapshot($name = false, $fields = array()) {
        return parent::_save_snapshot($name, array(
            '__columns_array',
            '__join_array',
            '__join_array_ready',
            '__join_weekness_array',
            '__where_array',
            '__group_array',
            '__order_array',
            '__having_array',
            '__limit1_number',
            '__limit2_number'
        ));
    }

    /**
    * Задать список столбцов для выборки, может получить как строку, в которой перечислены 
    * поля, так и массив
    * 
    * @param mixed $cols
    */
    public function _columns($cols) {
        if (is_array($cols)) {
            $this->__columns_array = array_merge($this->__columns_array, $cols);
        }
        else if ($cols !== false) {
            $this->__columns_array[] = $cols;
        }
        return $this;
    }

    /**
    * @method _join
    * 
    * Задает ссылку на объект data_source, описывающий таблицу, которую нужно привязать к запросу.
    * 
    * Пример корректного вызова:
    * ... ->_join($this->table2)-> ...
    * ... ->_join($this->table2, 'left')-> ...
    * 
    * @param object $data_source
    * @param string $join_method
    * @param string $alias
    * @return object $this
    */

    /**
    * @method _join
    * 
    * Задает ссылку на объект data_source, описывающий таблицу, к которой нужно привязать data_source,
    * переданый вторым параметром.
    * 
    * Пример корректного вызова:
    * ... ->_join($this->table1, $this->table2)-> ...
    * ... ->_join($this->table1, $this->table2, 'left')-> ...
    * 
    * @param object $join_to
    * @param object $data_source
    * @param string $join_method
    * @param string $alias
    * @return object $this
    */

    /**
    * @method _join
    * 
    * Задает правило связывания ($join_rule), описывающего привязку произвольной таблицы к запросу
    * Примеры корректных $join_rule
    * - 'data_source'
    * - 'join_to data_source'
    * - 'join_to.join_to_key data_source'
    * - 'join_to data_source.data_source_key'
    * - 'join_to.join_to_key data_source.data_source_key'
    * - 'join_to.join_to_key data_source.data_source_key'
    * - 'join_to.join_to_key data_source.data_source_key'
    * 
    * @param string $join_rule
    * @param string $join_method
    * @param string $alias
    * @return object $this
    */

    public function _join($join_to, $data_source = false, $join_method = false, $alias = false, $condition = false) {
        $this->__join_array[] = $this->__create_join_line($join_to, $data_source, $join_method, $alias, $condition);
        $this->__join_array_ready = false;

        return $this;
    }

    protected function __create_join_line($join_to, $data_source = false, $join_method = false, $alias = false, $condition = false) {
        if (!is_object($data_source)) {
            $condition = $alias;
            $alias = $join_method;
            $join_method = $data_source;
            $data_source = $join_to;
            $join_to = $this;
        }
        if (!$join_method) {
            $join_method = 'inner';
        }
        $join_line = array(
            'join_method' => $join_method
        );

        $join_line['condition'] = $condition;
        if (is_object($data_source)) {
            $join_line['data_source'] = $data_source;
            $join_line['data_source_key'] = false;
            $join_line['join_to'] = $join_to;
            $join_line['join_to_key'] = false;
        }
        else { // data_source как строка
            $parsing_tables = explode(' ', $data_source, 2);
            if (sizeof($parsing_tables) == 2) { // в виде 'join_to data_source'
                $parsing_join = explode('.', $parsing_tables[0], 2);
                if (sizeof($parsing_join) == 2) {
                    $join_line['join_to'] = $parsing_join[0];
                    $join_line['join_to_key'] = $parsing_join[1];
                } else {
                    $join_line['join_to'] = $parsing_join[0];
                    $join_line['join_to_key'] = false;
                }
                $parsing_data_source = explode('.', $parsing_tables[1], 2);
                if (sizeof($parsing_data_source) == 2) {
                    $join_line['data_source'] = $parsing_data_source[0];
                    $join_line['data_source_key'] = $parsing_data_source[1];
                } else {
                    $join_line['data_source'] = $parsing_data_source[0];
                    $join_line['data_source_key'] = false;
                }
            } else { // в виде 'data_source'
                $parsing_data_source = explode('.', $parsing_tables[0], 2);
                if (sizeof($parsing_data_source) == 2) {
                    $join_line['data_source'] = $parsing_data_source[0];
                    $join_line['data_source_key'] = $parsing_data_source[1];
                    $join_line['join_to'] = $join_to;
                    $join_line['join_to_key'] = false;
                } else {
                    $join_line['data_source'] = $data_source;
                    $join_line['data_source_key'] = false;
                    $join_line['join_to'] = $join_to;
                    $join_line['join_to_key'] = false;
                }
            }

            // 2011-10-19
            // $join_line['data_source'] = $this->_create_data_source($join_line['data_source']);
            $join_line['data_source'] = $this->_get_data_source($join_line['data_source']);
        }
        /* 2011-10-19 - если $join_line['join_to'] строка, то позже он превращается в объект
        if (!is_object($join_line['join_to'])) {
            // 2011-10-19
            // $join_line['join_to'] = $this->_create_data_source($join_line['join_to']);
            foreach ($this->__join_array as $joined) {
                if ($joined['data_source_alias'] == $join_line['join_to']) {
                    $join_line['join_to'] = $joined['data_source'];
                    break;
                }
            }
            if (!is_object($join_line['join_to'])) {
                $join_line['join_to'] = $this->_get_data_source($join_line['join_to']);
            }
        }
        */

        if (!isset($join_line['data_source_alias'])) {
            if (!$alias) {
                $alias = $join_line['data_source']->_get_alias();
            }
            $join_line['data_source_alias'] = $alias;
        }
        return $join_line;
    }

    /**
    * Задает условие, которое будет учитываться во время следующего запроса _select, _update, _delete
    * 
    * @param mixed $where условие
    * @return object $this
    */
    public function _where($where) {
        /*
        if ((is_array($where) && sizeof($where)) || (!is_array($where) && $where !== false)) {
            $generated_where = $this->__generate_where_array($where);
            if (sizeof($this->__where_array)) {
                if ($generated_where[0] == $this->__where_array[0]) {
                    array_shift($generated_where);
                    $this->__where_array = array_merge($this->__where_array, $generated_where);
                }
                else {
                    $this->__where_array = array_merge($this->__where_array, array($generated_where));
                }
            }
            else {
                $this->__where_array = $generated_where;
            }
        }
        */
        $this->__where_array = $this->__merge_conditions($this->__where_array, $where);
        return $this;
    }

    /**
    * Задает HAVING условие, которое будет учитываться во время следующего запроса _select, _update, _delete
    * 
    * @param mixed $where условие
    * @return object $this
    */
    public function _having($where) {
        $this->__having_array = $this->__merge_conditions($this->__having_array, $where);
        return $this;
    }

    protected function __merge_conditions($where1, $where2) {
        if ((is_array($where2) && sizeof($where2)) || (!is_array($where2) && $where2 !== false)) {
            $generated_where = $this->__generate_where_array($where2);
            if (sizeof($where1)) {
                if ($generated_where[0] == $where1[0]) {
                    array_shift($generated_where);
                    $where1 = array_merge($where1, $generated_where);
                }
                else {
                    $where1 = array_merge($where1, array($generated_where));
                }
            }
            else {
                $where1 = $generated_where;
            }
        }
        return $where1;
    }

    /**
    * Задает поля для сортировки, которые будут учитываться во время следующего запроса _select
    * 
    * @param mixed $by имя поля или массив с именами; после имени может идти суфикс desc, например 'field_name desc'
    * @return object $this
    */
    public function _order($by = false) {
        // если $by == false то система попытается определеить столбец по которому сортировать - {prefix_columns}sort
        if (is_array($by)) {
            for ($i = 0; $i < sizeof($by); $i++) {
                $this->__order_array[] = $by[$i];
            }
        }
        else if ($by !== false) {
            $this->__order_array[] = $by;
        }
        else {
            $this->__order_array = array_merge($this->__order_array, $this->__order_array_static);
        }
        return $this;
    }

    /**
    * Ограничить выборку во время следующего запроса _select, _update, _delete
    * 
    * @param int $limit1
    * @param int $limit2
    * @param boolean $force - переустановить значения лимитов, даже если они были ранее установлены в другие значения
    * @return object $this
    */
    public function _limit($limit1 = 1, $limit2 = false, $force = false) {
        if ($force || (!$this->__limit1_number && !$this->__limit2_number)) {
            $this->__limit1_number = $limit1;
            $this->__limit2_number = $limit2;
        }

        return $this;
    }

    /**
    * TODO: оттестировать
    */
    public function _group($by) {
        if (is_array($by)) {
            for ($i = 0; $i < sizeof($by); $i++) {
                $this->__group_array[] = $by[$i];
            }
        }
        else if ($by !== false) {
            $this->__group_array[] = $by;
        }
        return $this;
    }

    /*****************************************************
    * Внутренние методы, используются для разных задач
    *****************************************************/

    /**#@+
    * @ignore
    */
    /**
    * В качестве параметра принимает ID строки или смешаный массив, описывающий сложное условие:
    *   array(statement_asis1, field => value, statement_asis2 ...)
    * по-умолчанию между field и value ставится ' = ', при этом field может через пробел содежать в себе другое условие, например:
    * array('user_age >' => 3)
    */
    protected function __generate_where_array($where) {
        $primary_key_values = array();
        if (!is_array($where)) {
            $where = array($where);
        }
        // какая логика используется при склеивании элементов данного массива?
        if (
            isset($where[0]) &&
            !is_array($where[0]) && (
                strtolower($where[0]) == 'or' || strtolower($where[0]) == 'and'
            )
        ) {
            $where_array = array(strtolower($where[0]));
            unset($where[0]);
        }
        else {
            $where_array = array('and');
        }
        // $i = 0;
        foreach ($where as $key => $val) {
            // какая логика используется при склеивании элементов данного массива?
            /*
            if ($i == 0) {
                if (
                    is_numeric($key) &&
                    !is_array($val) && (
                        strtolower($val) == 'or' || strtolower($val) == 'and'
                    )
                ) {
                    $where_array[] = strtolower($val);
                    $i++;
                    continue;
                }
                else {
                    $where_array[] = 'and';
                }
            }
            */

            if (is_numeric($key)) {
                if (is_array($val)) {
                    $where_array[] = $this->__generate_where_array($val);
                }
                else if (is_numeric($val)) { // || preg_match('#^[\w\d]+$#') - вариант, например, для sess_id; он не покрывает еще более сложные ключи, например, содержащие скобки или пробелы и т.п.
                    $primary_key_values[] = $val;
                }
                else if ($val) {
                    $where_array[] = array(
                        'alias' => false,
                        'expression' => $val,
                        'operator' => false,
                        'field' => false,
                        'value' => false
                    );
                }
            }
            else {
                $key_data = explode(' ', $key, 2);
                if (sizeof($key_data) > 1) {
                    $key = trim($key_data[0]);
                    $operator = trim($key_data[1]);
                }
                else {
                    $operator = '=';
                }
                if (is_array($val)) {
                    if ($operator == '=') {
                        $operator = 'in';
                    }
                    else if ($operator == '<>') {
                        $operator = 'not in';
                    }
                }

                $key_data = explode('.', $key, 2);
                if (sizeof($key_data) > 1) {
                    $table = trim($key_data[0]);
                    $key = trim($key_data[1]);
                } else {
                    if (preg_match('#^' . preg_quote($this->_get_prefix_fields(), '#') . '#', $key)) {
                        // old style (for back compatibility and flexibility)
                        $table = $this->_get_alias();
                    }
                    else {
                        // new style - unknown table - no table alias supposed
                        $table = false;
                    }
                    // $table = $this->_get_alias();
                    // $table = false;
                }
                if ($table && $table != $this->_get_alias()) {
                    // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
                    $this->__join_weakness($table);
                    // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
                }

                if ($operator == 'in' || $operator == 'not in') {
                    // TODO: автоматически не экранировать подзапрос - подумать, надо ли
                    // if (is_array($val)) {
                        $inline_val = ' (\'' . implode('\',\'', $this->_db->_array_map('_escape', $val)) . '\')';
                    // }
                    // else {
                        // подзапрос
                        // $inline_val = ' (' . $val . ')';
                    // }
                }
                else if ($operator == 'like' || $operator == '%like' || $operator == 'like%' || $operator == '%like%') {
                    $inline_val = '\'' . str_replace('like', $this->_db->_escape($val), $operator) . '\'';
                    $operator = 'like';
                }
                else {
                    $inline_val = '\'' . $this->_db->_escape($val) . '\'';
                }
                $where_array[] = array(
                    'alias' => $table,
                    'expression' => '`' . $key . '` ' . $operator . ' ' . $inline_val,
                    'operator' => $operator,
                    'field' => $key,
                    'value' => $val
                );
            }
            // $i++;
        }
        if (sizeof($primary_key_values) == 1) {
            $where_array[] = array(
                'alias' => $this->_get_alias(), // $this->_table,
                'expression' => '`' . $this->_get_primary_key() . '` = ' . '\'' . $this->_db->_escape($primary_key_values[0]) . '\'',
                'operator' => '=',
                'field' => $this->_get_primary_key(),
                'value' => $primary_key_values[0]
            );
        }
        else if (sizeof($primary_key_values) > 1) {
            $where_array[] = array(
                'alias' => $this->_get_alias(), // $this->_table,
                'expression' => '`' . $this->_get_primary_key() . '` in (\'' . implode('\',\'', $this->_db->_array_map('_escape', $primary_key_values)) . '\')',
                'operator' => 'in',
                'field' => $this->_get_primary_key(),
                'value' => $primary_key_values
            );
        }
        return $where_array;
    }

    protected function __join_weakness($table) {
        $this->__join_weekness_array[] = $table;
        $this->__join_array_ready = false;

        return $this;
    }

    protected function __prepare_join_array() {
        // обработать неявные join (в where и др.)
        $aliases = array($this->_get_alias());
        foreach ($this->__join_array as $join_line) {
            $aliases[] = $join_line['data_source_alias'];
        }
        foreach ($this->__join_weekness_array as $data_source_name) {
            if (!in_array($data_source_name, $aliases)) {
                $this->_join($data_source_name);
                $aliases[] = $this->__join_array[sizeof($this->__join_array) - 1]['data_source_alias'];
            }
        }
        if (!$this->__join_array_ready) {
            /*
            * нужно пройтись по всем прикрепленным таблицам и создать объект join_to
            * почему не сделали одновременно с объектом data_source - см. комментарии в $this->_join()
            */
            foreach ($this->__join_array as &$join_line) {
                if (isset($join_line['join_to'])) {
                    if (is_array($join_line['join_to'])) {
                        $join_line['join_to'] = $this->_create_data_source($join_line['join_to']);
                        $join_line['join_to_alias'] = $join_line['join_to']->_get_alias();
                        continue;
                    }
                    else if (is_object($join_line['join_to'])) {
                        $join_line['join_to_alias'] = $join_line['join_to']->_get_alias();
                        continue;
                    }
                    else {
                        if ($this->_get_alias() == $join_line['join_to']) {
                            $join_line['join_to'] = $this;
                            $join_line['join_to_alias'] = $this->_get_alias();
                            continue;
                        }
                        else {
                            foreach ($this->__join_array as $join_line_again) {
                                if ($join_line_again['data_source_alias'] == $join_line['join_to']) {
                                    $join_line['join_to'] = $join_line_again['data_source'];
                                    $join_line['join_to_alias'] = $join_line_again['data_source_alias'];
                                    continue 2;
                                }
                            }
                            // 2011-10-19 - просто строка с именем датасорца
                            $join_line['join_to'] = $this->_get_data_source($join_line['join_to']);
                            $join_line['join_to_alias'] = $join_line['join_to']->_get_alias();
                        }
                    }
                }
            }

            foreach ($this->__join_array as &$join_line) {
                if ($join_line['condition'] === false) { // поведение по-умолчанию
                    // заполнить join_to_key и data_source_key везде, где они не были указаны явно
                    if (!$join_line['join_to_key'] || !$join_line['data_source_key']) {
                        // join_to relations
                        $jt_relations = $join_line['join_to']->__find_relations($join_line['data_source']);
                        // data_source relations
                        $ds_relations = $join_line['data_source']->__find_relations($join_line['join_to']);

                        $relations = array();
                        foreach ($jt_relations as $jt_key => $ds_key) {
                            $relations[] = array('jt_key' => $jt_key, 'ds_key' => $ds_key);
                        }
                        foreach ($ds_relations as $ds_key => $jt_key) {
                            $found = false;
                            for ($i = 0; $i < sizeof($relations); $i++) {
                                if (
                                    $relations[$i]['jt_key'] == $jt_key &&
                                    $relations[$i]['ds_key'] == $ds_key
                                ) {
                                    $found = true;
                                    break;
                                }
                            }
                            if (!$found) {
                                $relations[] = array('jt_key' => $jt_key, 'ds_key' => $ds_key);
                            }
                        }
                        $clean_relations = array();
                        for ($i = 0; $i < sizeof($relations); $i++) {
                            if ($join_line['join_to_key'] && $relations[$i]['jt_key'] != $join_line['join_to_key']) {
                                continue;
                            }
                            if ($join_line['data_source_key'] && $relations[$i]['ds_key'] != $join_line['data_source_key']) {
                                continue;
                            }
                            $clean_relations[] = $relations[$i];
                        }

                        if (sizeof($clean_relations) > 1) {
                            if (_DEBUG_LEVEL & _DEBUG_CC) {
                                $debug_join_string = 
                                    $join_line['join_to']->_get_table() .
                                    ($join_line['join_to_key'] ? '.' . $join_line['join_to_key'] : '') . ' ' .
                                    $join_line['data_source']->_get_table() .
                                    ($join_line['data_source_key'] ? '.' . $join_line['data_source_key'] : '')
                                ;
                                _cc::fatal_error(_DEBUG_CC, 'CC Warning. Relation is ambiguous: <b>' . $debug_join_string . '</b>');
                            }
                        }
                        if (sizeof($clean_relations) == 0) {
                            if (_DEBUG_LEVEL & _DEBUG_CC) {
                                $debug_join_string = 
                                    $join_line['join_to']->_get_table() .
                                    ($join_line['join_to_key'] ? '.' . $join_line['join_to_key'] : '') . ' ' .
                                    $join_line['data_source']->_get_table() .
                                    ($join_line['data_source_key'] ? '.' . $join_line['data_source_key'] : '')
                                ;
                                _cc::fatal_error(_DEBUG_CC, 'CC Error. Relation is undefined: <b>' . $debug_join_string . '</b>');
                            }
                        }

                        if (!$join_line['join_to_key']) {
                            $join_line['join_to_key'] = $clean_relations[0]['jt_key'];
                        }
                        if (!$join_line['data_source_key']) {
                            $join_line['data_source_key'] = $clean_relations[0]['ds_key'];
                        }
                    }
                    $join_line['condition'] = 
                        $join_line['join_to_alias'] . '.' . $join_line['join_to_key'] . ' = ' . 
                        $join_line['data_source_alias'] . '.' . $join_line['data_source_key'];
                }
                $join_line['sql'] = 
                    ($join_line['condition'] ? ' ' . $join_line['join_method'] . ' join ' : ' inner join ') . 
                    $this->_db->_get_prefix() . $join_line['data_source']->_get_table() . ' as ' . $join_line['data_source_alias'] . 
                        ($join_line['condition'] ? ' on ' . $join_line['condition'] : '')
                ;
            }

            // пересортировать элементы $this->__join_array что бы таблицы с которыми идет склеивание 
            // шли ДО самого склеивания
            $unsorted_join_array = $this->__join_array;
            $this->__join_array = array();
            $ready_aliases = array($this->_get_alias());
            $deferred_joins = array();
            foreach ($unsorted_join_array as $line) {
                if (!in_array($line['join_to_alias'], $ready_aliases)) {
                    $deferred_joins[] = $line;
                    continue;
                }
                $this->__join_array[] = $line;
                $ready_aliases[] = $line['data_source_alias'];
                while (true) {
                    for ($i = 0; $i < sizeof($deferred_joins); $i++) {
                        if (is_array($deferred_joins[$i])) {
                            if (in_array($deferred_joins[$i]['join_to_alias'], $ready_aliases)) {
                                $this->__join_array[] = $deferred_joins[$i];
                                $ready_aliases[] = $deferred_joins[$i]['data_source_alias'];
                                $deferred_joins[$i] = false;
                                continue;
                            }
                        }
                    }
                    break;
                }
            }
            if (!_cc::is_release()) {
                $found = 0;
                $message = '';
                for ($i = 0; $i < sizeof($deferred_joins); $i++) {
                    if (is_array($deferred_joins[$i])) {
                        $message .= '<br>' . 
                            $deferred_joins[$i]['data_source_alias'] .
                            '.' . $deferred_joins[$i]['data_source_key'] .
                            ' ' . $deferred_joins[$i]['join_to'] .
                            '.' . $deferred_joins[$i]['join_to_key'] . ''
                        ;
                        $found++;
                    }
                }
                if ($found) {
                    $message = 'Was unable to join <b>' . $found . '</b> deferred joins.' .
                        '<br><b>List of deferred joins:</b>' .
                        $message
                    ;
                    $message .= '<br><b>List of ready aliases:</b>';
                    for ($i = 0; $i < sizeof($ready_aliases); $i++) {
                        $message .= '<br>' . $ready_aliases[$i] . '';
                    }
                    _cc::fatal_error(_DEBUG_CC, $message);
                }
            }

            // используется в _adjust_output, что бы вызвать _adjust_output связаных таблиц
            // 
            // раньше этот массив было необзодимо сохранять, т.к. adjust_output вызывался ПОСЛЕ того 
            // как отрабатывал _select, теперь же вызов совершается прямо из _low_select, поэтому 
            // необходимость в данном масиве пропала (ПРОТЕСТИРОВАТЬ)
            // 
            // $this->__temporary_join_storage = $this->__join_array;
            $this->__join_array_ready = true;
        }
    }

    /**
    * Генерирует часть COLUMNS SQL запроса
    */
    protected function __generate_columns() {
        $columns_sql = '';
        foreach ($this->__columns_array as $col_line) {
            if ($columns_sql) {
                $columns_sql .= ', ';
            }
            $columns_sql .= $col_line;
        }
        if (!$columns_sql) {
            $columns_sql = '*';
        }
        return $columns_sql;
    }

    /**
    * Генерирует часть FROM SQL запроса
    */
    protected function __generate_from() {
        $from_sql = ' from ' . $this->_db->_get_prefix() . $this->_table . ' as ' . $this->_get_alias();
        foreach ($this->__join_array as $join_line) {
            $from_sql .= $join_line['sql'];
        }
        return $from_sql;
    }

    /**
    * Генерирует часть WHERE SQL запроса
    */
    protected function __generate_where($use_alias = true, $where_array = false, $concat = false, $disable_where = false) {
        return $this->__generate_condition($this->__where_array, 'where', $use_alias, $where_array, $concat, $disable_where);
    }

    protected function __generate_having($use_alias = true, $concat = false, $disable_where = false) {
        return $this->__generate_condition($this->__having_array, 'having', $use_alias, $concat, $disable_where);
    }

    protected function __generate_condition($where_array, $word, $use_alias = true, $concat = false, $disable_where = false) {
        $where_sql = '';
        if (sizeof($where_array)) {
            $concat = ' ' . $where_array[0] . ' ';

            $i = 0;
            foreach ($where_array as $key => $where_line) {
                if ($i == 0) {
                    $i++;
                    continue;
                }
                if ($where_sql) {
                    $where_sql .= $concat;
                }
                else if (!$disable_where) {
                    $where_sql = ' ' . $word . ' ';
                }
                // $where_line[0] равен 'or' или 'and', в других случаях $where_line[0] не существует
                if (isset($where_line[0])) { 
                    $where_sql .= 
                        '(' . $this->__generate_condition($where_line, $word, $use_alias, false, true) . ')';
                }
                else {
                    if ($use_alias) {
                        $where_sql .= ($where_line['alias'] ? $where_line['alias'] . '.' : '') . $where_line['expression'];
                    } else {
                        $where_sql .= $where_line['expression'];
                    }
                }
                $i++;
            }
        }
        return $where_sql;
    }

    /**
    * Генерирует часть ORDER SQL запроса
    */
    protected function __generate_order() {
        $order_sql = '';
        if (!sizeof($this->__order_array)) {
            // add default order field
            $this->_order();
        }
        foreach ($this->__order_array as $order_line) {
            if ($order_sql) {
                $order_sql .= ', ';
            }
            $order_sql .= $order_line;
        }
        if ($order_sql) {
            $order_sql = ' order by ' . $order_sql;
        }
        return $order_sql;
    }

    /**
    * Генерирует часть GROUP SQL запроса
    */
    protected function __generate_group() {
        $group_sql = '';
        foreach ($this->__group_array as $group_line) {
            if ($group_sql) {
                $group_sql .= ', ';
            }
            $group_sql .= $group_line;
        }
        if ($group_sql) {
            $group_sql = ' group by ' . $group_sql;
        }
        return $group_sql;
    }

    protected function __get_primary_key_index() {
        return $this->__fields_names[$this->_get_primary_key()];
    }

    protected function __find_relations($foreign_data_source) {
        $foreign_table = $foreign_data_source->_get_table();
        $ret = array();
        foreach ($this->_fields as $field) {
            if (isset($field['foreign_table']) && $field['foreign_table'] == $foreign_table) {
                if (isset($field['foreign_key']) && is_string($field['foreign_key'])) {
                    $foreign_key = $field['foreign_key'];
                } else {
                    $foreign_key = $foreign_data_source->_get_primary_key();
                }
                $ret[$field['name']] = $foreign_key;
            }
        }
        return $ret;
    }

    /**
     * Модифицирует ссылку на двоичные данные;
     *   - если первым параметром указано имя файла без пути, будет проверено, существует
     *   ли такой файл в папке $binary_path, если не существует, вернется false;
     *   - если первым параметром указан путь к файлу, который лежит не в папке $binary_path - он будет
     *   оттуда скопирован в папку $binary_path и вернется имя файла без пути
     *   - если первым параметром указан путь к файлу, находящемуся в $binary_path - ничего не произойдет, и
     *   вернется имя файла без пути
     *
     * 2011-11-08 - убрана строгая проверка на существование файла
     * мешает, например, при единовременном обновлении базы отдельном переносе файлов - например, при импорте
     * большого количества данных
     */
    protected function __adjust_binary_filename($full_filename, $binary_path) {
        if ($full_filename) {
            if (
                $full_filename &&
                strpos($full_filename, ':') === false && // full path on win machine
                $full_filename[0] != '/' // full path *nix
            ) {
                $full_filename = $binary_path . $full_filename;
            }
            if (preg_match('#^' . preg_quote(_LOCALE_PATH) . '#', $full_filename)) {
                $full_filename = preg_replace('#^' . preg_quote(_LOCALE_PATH) . '#', '', $full_filename);
            }

            if (preg_match('#^' . preg_quote($binary_path) . '#', $full_filename)) {
                $ret = preg_replace('#^' . preg_quote($binary_path) . '#', '', $full_filename);
            }
            else {
                $filename = basename($full_filename);
                if ($filename != $full_filename) {
                    $new_filename = _unique_filename($binary_path, $filename);
                    if (file_exists($full_filename)) {
                        _copy($full_filename, $binary_path . $new_filename);
                    }
                }
                $ret = $new_filename;
            }
        }
        else {
            $ret = '';
        }
        return $ret;
        /* 2011-11-08
        if ($full_filename) {
            if (
                $full_filename &&
                strpos($full_filename, ':') === false && // full path on win machine
                $full_filename[0] != '/' // full path *nix
            ) {
                $full_filename = $binary_path . $full_filename;
            }
            if (file_exists($full_filename)) {
                if (preg_match('#^' . preg_quote(_LOCALE_PATH) . '#', $full_filename)) {
                    $full_filename = preg_replace('#^' . preg_quote(_LOCALE_PATH) . '#', '', $full_filename);
                }

                if (preg_match('#^' . preg_quote($binary_path) . '#', $full_filename)) {
                    $ret = preg_replace('#^' . preg_quote($binary_path) . '#', '', $full_filename);
                }
                else {
                    $filename = basename($full_filename);
                    if ($filename != $full_filename) {
                        $new_filename = _unique_filename($binary_path, $filename);
                        _copy($full_filename, $binary_path . $new_filename);
                    }
                    $ret = $new_filename;
                }
            }
            else {
                $ret = false;
            }

            return $ret;
        }
        else {
            return '';
        }
        */
    }

    protected function __set_result_as_columns($result_as_columns = false) {
        $this->__result_as_columns = $result_as_columns;
        return $this;
    }

    protected function __get_result_as_columns() {
        return $this->__result_as_columns;
    }

    /**
    * Автоматическое создание датасорца на базе указанной таблицы
    * 
    * @param mixed $class_name
    */
    public function __autocreate($class_name) {
        if (!file_exists(__LOCALE_DATA_SOURCES_PATH . $class_name . '.php')) {
            if (
                $fp = @fopen(__LOCALE_DATA_SOURCES_PATH . $class_name . '.php', 'w')
            ) {
                if ($_db = _cc::create_db_engine('_db')) {
                    if ($_db->_rows('show tables like \'' . $_db->_get_prefix() . $class_name . '\'') && $d_q = $_db->_query('describe ' . $_db->_get_prefix() . $class_name)) {
                        $fields = array();
                        while ($d_r = $_db->_fetch_assoc($d_q)) {
                            $fields[] = $d_r;
                        }
                        $prefix_fields = self::__find_prefix_fields($fields, 'Field');
                        foreach ($fields as &$d_r) {
                            if ($prefix_fields) {
                                $title = preg_replace('#^' . $prefix_fields . '#', '', $d_r['Field']);
                            }
                            else {
                                $title = $d_r['Field'];
                            }
                            $d_r['title'] = ucfirst(str_replace('_', ' ', $title));
                            if ($d_r['Field'] == $prefix_fields . 'sort') {
                                $d_r['sort'] = true;
                            }
                            if ($d_r['Field'] == $prefix_fields . 'created') {
                                $d_r['created'] = true;
                            }
                            if ($d_r['Field'] == $prefix_fields . 'modified') {
                                $d_r['modified'] = true;
                            }
                            if ($d_r['Field'] == $prefix_fields . 'active') {
                                $d_r['active'] = true;
                            }
                            if (preg_match('#_id$#', $d_r['Field']) && (!isset($d_r['Key']) || $d_r['Key'] != 'PRI')) {
                                $d_r['foreign_table'] = ' ';
                            }
                        }
                        $code = "<?php\n" . _cc::create_tpl_from_string(file_get_contents(__ENGINE_DATA_SOURCE_TPLS_PATH . '_db_table_foundation.php.tpl'), array(
                            'class_name' => $class_name,
                            'fields' => $fields,
                            'prefix_fields' => $prefix_fields
                        ))->_get_result();
                        fwrite($fp, $code);
                        fclose($fp);
                    }
                    else {
                        fclose($fp);
                        unlink(__LOCALE_DATA_SOURCES_PATH . $class_name . '.php');
                        _cc::fatal_error(_DEBUG_CC, 'CC error. Unable to auto create _db_table <b>' . $class_name. '</b>: Such table does not exists');
                    }
                }
                else {
                    _cc::fatal_error(_DEBUG_CC, 'CC error. Unable to auto create _db_table <b>' . $class_name. '</b>: DB object is not set inside the global configuration');
                }
            }
            else {
                _cc::fatal_error(_DEBUG_CC, 'CC error. Unable to auto create _db_table <b>' . $class_name. '</b>: error opening ' . __LOCALE_DATA_SOURCES_PATH . $class_name . '.php for writing');
            }
        }
    }

    /**#@-*/
}



