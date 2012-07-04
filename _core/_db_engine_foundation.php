<?php
/**
 * @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
 * @package CleverCore2
 */

/**
* Base class for data base engines.
*/
abstract class _db_engine_foundation extends _core {
    protected $__type = '_db_engine';
    protected $__sql = false;
    protected $__resource = false;
    protected $__db_resource = false;

    protected $_hostname = false;
    protected $_basename = false;
    protected $_username = false;
    protected $_password = false;
    protected $_prefix = false;

    protected $_number_of_queries = 0;

    protected $_db = false;

    public function __construct($data = array()) {
        parent::__construct($data);

        if ($this->_hostname === false || $this->_basename === false || $this->_username === false || $this->_password === false) {
            _cc::fatal_error(_DEBUG_SQL, 'SQL Error. Can\'t connect to the database - some settings are not set');
        } else {
            $this->__db_resource = $this->_connect($this->_hostname, $this->_username, $this->_password);
            if (!$this->__db_resource) {
                _cc::fatal_error(_DEBUG_SQL, 'SQL Error. Can\'t connect to the database - some settings are incorrect');
            }
            if (!$this->_select_db($this->_basename, $this->__db_resource)) {
                _cc::fatal_error(_DEBUG_SQL, 'SQL Error. Can\'t select database - some settings are incorrect');
            }
            $this->_query("set names 'utf8'");
        }
    }

    /**
    * Вернет 1 строку
    * 
    * если был запрошен только один столбец, строка будет значением, иначе ассоциативным массивом
    * 
    * @param mixed $sql
    */
    public function _row($sql, $adj_db_output_object = false, $adj_db_output_method = '_adjust_db_output') {
        $this->__set_query($sql);
        $this->__init_res(1);
        $ret = $this->_fetch_assoc($this->__resource);
        if (is_object($adj_db_output_object) && method_exists($adj_db_output_object, $adj_db_output_method)) {
            $ret = $adj_db_output_object->$adj_db_output_method($ret);
        }
        if (sizeof($ret) > 1) {
            return $ret;
        } elseif ($ret) {
            $ret = array_values($ret);
            return $ret[0];
        } else {
            return false;
        }
    }

    /**
    * Вернет массив строк
    * 
    * если был запрошен только один столбец, каждая строка будет значением, иначе ассоциативным массивом
    * 
    * @param mixed $sql
    * @param mixed $limit1
    * @param mixed $limit2
    */
    public function _rows($sql, $limit1 = false, $limit2 = false) {
        return $this->__low_rows($sql, $limit1, $limit2);
    }

    public function __low_rows($sql, $limit1 = false, $limit2 = false, $adj_db_output_object = false, $adj_db_output_methods = '_adjust_db_output', $adjust_mode = false) {
        $this->__set_query($sql);
        $this->__init_res($limit1, $limit2);
        $ret = array();

        $tas = array();
        while ($ta = $this->_fetch_assoc($this->__resource)) {
            $tas[] = $ta;
        }
        foreach ($tas as $ta) {
            if (is_object($adj_db_output_object)) {
                if (!is_array($adj_db_output_methods)) {
                    $adj_db_output_methods = array($adj_db_output_methods);
                }
                foreach ($adj_db_output_methods as $method) {
                    if (method_exists($adj_db_output_object, $method)) {
                        $ta = $adj_db_output_object->$method($ta, $adjust_mode);
                    }
                }
            }
            // if ($num_fields == 1) {
            if (sizeof($ta) == 1) {
                $ret[] = current($ta);
            } else {
                $ret[] = $ta;
            }
        }
        /* EOF remade */

        return $ret;
    }

    /**
    * Вернет массив значений, отформатированный по столбцам, т.е. первый элемент результата будет 
    * собержать все значения для первого запрошенного столбца БД, второй - для второго и т.д.
    * 
    * Если был запрошен 1 столбец - вернется одномерный массив со значениями из этого столбца
    * 
    * если был запрошен только один столбец, каждая строка будет значением, иначе ассоциативным массивом
    * 
    * @param mixed $sql
    * @param mixed $limit1
    * @param mixed $limit2
    * @return array
    */
    public function _cols($sql, $limit1 = false, $limit2 = false) {
        return $this->__low_cols($sql, $limit1, $limit2);
    }

    /**
    * @param mixed $sql
    * @param mixed $limit1
    * @param mixed $limit2
    * @param object $adj_db_output_object объект, в котором есть метод, который надо вызвать после получения данных из БД
    * @param object $adj_db_output_method имя метода (или массив имен), в который отправляются данные после получения их из БД
    * @return mixed
    */
    public function __low_cols($sql, $limit1 = false, $limit2 = false, $adj_db_output_object = false, $adj_db_output_methods = '_adjust_db_output', $adjust_mode = false) {
        $this->__set_query($sql);
        $this->__init_res($limit1, $limit2);

        $ret = array();
        $num_fields = $this->_num_fields($this->__resource);
        $num_rows = 0;
        $names_fields = array();

        $tas = array();
        while ($ta = $this->_fetch_assoc($this->__resource)) {
            $tas[] = $ta;
        }
        foreach ($tas as $ta) {
            if (is_object($adj_db_output_object)) {
                if (!is_array($adj_db_output_methods)) {
                    $adj_db_output_methods = array($adj_db_output_methods);
                }
                foreach ($adj_db_output_methods as $method) {
                    if (method_exists($adj_db_output_object, $method)) {
                        $ta = $adj_db_output_object->$method($ta, $adjust_mode);
                    }
                }
            }
            foreach ($ta as $field => $value) {
                if (!$num_rows) {
                    $names_fields[] = $field;
                }
                $ret[$field][] = $value;
            }
            $num_rows++;
        }

        if (!$num_rows) {
            for ($i = 0; $i < $num_fields; $i++) {
                $field_data = $this->_fetch_field($this->__resource);
                $names_fields[] = $field_data->name;
                $ret[$field_data->name] = array();
            }
        }
        if (sizeof($ret) == 1) {
            return $ret[$names_fields[0]];
        } else {
            return $ret;
        }
    }

    public function _query($sql, $limit1 = false, $limit2 = false) {
        $starttime = _get_microtime();
        $sql .= $this->_sql_limit($limit1, $limit2);
        $resource = $this->_low_query($sql);
        $endtime = _get_microtime();

        if ($resource) {
            if (_DEBUG_LEVEL & _DEBUG_SQL) {
                $this->_number_of_queries++;
                _cc::debug_message(_DEBUG_SQL, 
                    'Query #' . $this->_number_of_queries . ' inside' . 
                    $this->__get_dbg_queue_str(debug_backtrace()) . 
                    '<br><b>' . 
                    $sql . 
                    '</b>;<br> time spent <b>' . 
                    round(($endtime - $starttime) * 10000) / 10000 . 
                    '</b>'
                );
            }
            return $resource;
        }
        else {
            _cc::fatal_error(_DEBUG_SQL, 
                'SQL Error. Query inside' . 
                $this->__get_dbg_queue_str(debug_backtrace()) . 
                '<br><b>' . 
                $sql . 
                '</b>;<br> error: ' . 
                $this->_error()
            );
            return false;
        }
    }

    protected function __get_dbg_queue_str($dbg) {
        $obj = false;
        $dbg_queue = array();
        for ($i = 0; $i < sizeof($dbg); $i++) {
            if (isset($dbg[$i]['file'])) {
                $fl = basename($dbg[$i]['file']);
                if (
                    $fl[0] != '_' && // system file
                    // $dbg[$i]['function'] != '_create_module' &&
                    isset($dbg[$i]['object']) && 
                    ($dbg[$i]['object']->_get_type() == '_module' || $dbg[$i]['object']->_get_type() == '_data_source')
                    // get_class($dbg[$i]['object']) != $obj
                ) {
                    $obj = get_class($dbg[$i]['object']);
                    array_unshift($dbg_queue, array(
                        'object' => $obj, 
                        'file' => $fl,
                        'function' => $dbg[$i]['function'],
                        'line' => $dbg[$i]['line']
                    ));
                }
            }
        }
        $dbg_queue_str = '';
        foreach ($dbg_queue as $q) {
            $dbg_queue_str .= 
                '<br>' . 
                $q['file'] . 
                ', #' . 
                $q['line'] . 
                ': ' . 
                $q['object'] . 
                '&rarr;' . 
                $q['function'] . 
                '(...)';
        }
        return $dbg_queue_str;
    }

    /*****************************************************************
    * Getters
    *****************************************************************/

    public function _get_hostname() {
        return $this->_hostname;
    }

    public function _get_basename() {
        return $this->_basename;
    }

    public function _get_username() {
        return $this->_username;
    }

    public function _get_password() {
        return $this->_password;
    }

    public function _get_prefix() {
        return $this->_prefix;
    }

    /*****************************************************************
    * Internal methods
    *****************************************************************/

    protected function __set_query($sql) {
        $this->__sql = $sql;
        $this->__resource = false;
        return $this;
    }

    protected function __init_res($limit1 = false, $limit2 = false) {
        if (!$this->__resource || $limit1 || $limit2) {
            if (!$this->__sql) {
                return false;
            } else {
                $this->__resource = $this->_query($this->__sql, $limit1, $limit2);
            }
        }
        return true;
    }

    public function _sql_limit($limit1 = false, $limit2 = false) {
        return '';
    }

    /*****************************************************************
    * Require implementation
    *****************************************************************/

    abstract public function _connect($hostname, $username, $password);

    abstract public function _select_db($basename);

    abstract public function _low_query($sql);

    abstract public function _escape($param);

    abstract public function _insert_id();

    abstract public function _error();

    abstract public function _fetch_assoc($resource);

    abstract public function _fetch_num($resource);

    abstract public function _fetch_field($resource);

    abstract public function _fetch_both($resource, $k);

    abstract public function _num_fields($resource);

}


