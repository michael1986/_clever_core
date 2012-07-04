<?php

/**
* @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
*/
class libCurrentUserBase extends _module {
    /**
    * Время в секундах, после которого пользователь будет автоматически разлогинен при неактивности
    *
    * @var mixed
    */
    protected $session_timeout = 7200; // 2 часа
    /**
    * GET/POST параметр сессии
    *
    * @var string
    */
    protected $param_sess_id = 'sess_id';
    /**
    * ID текущей сессии
    *
    * @var string
    */
    protected $sess_id = false;
    protected $sess_data = array();

    /**
    * Хранить сессию в куках? Если false, то сессия будет таскаться за всеми ссылками и формами
    *
    * @var boolean
    */
    protected $cookie_session = false;
    /**
    * cookie параметр сессии
    *
    * @var mixed
    */
    protected $cookie_param_sess_id = 'sess_id';

    /**
    * Включает в проекте функцию запоминания пользователя
    *
    * @var mixed
    */
    protected $remember_enabled = false;
    /**
    * Настройки cookie
    *
    * @var mixed
    */
    protected $cookie_path = false;
    /**
    * Настройки cookie
    *
    * @var mixed
    */
    protected $cookie_domain = false;
    /**
    * Настройки cookie
    *
    * @var mixed
    */
    protected $cookie_param_remember = 'p0';
    protected $cookie_param_usr = 'p1';
    protected $cookie_param_pwd = 'p2';
    protected $param_remember = 'remember';

    protected $max_group_name_length = 64;
    protected $max_var_name_length = 64;

    /**
     * кеш данныъ сессии, заполняется при первом обращении к данным сессии
     *
     * @var mixed
     */
    protected $cache_session_data = false;

    public function __construct($data = array()) {
        parent::__construct($data);

        $this->cookie_path = _get_basepath();
    }

    /**
    * возвращает время в секундах, после которого пользователь будет автоматически разлогинен при неактивности
    *
    */
    public function get_session_timeout() {
        return $this->session_timeout;
    }

    /**
    * Проверить, залогинен ли сейчас какой-то пользователь
    *
    * @return mixed false или ID текущего пользователя
    */
    public function is_signed() {
        if (isset($this->sess_data['sess_user_id'])) {
            return $this->sess_data['sess_user_id'];
        } else {
            return false;
        }
    }

    /**
    * Получить ID текущей сессии
    */
    public function get_session_id() {
        return $this->sess_id;
    }

    /**
    * Возвращает массив параметров, совместимый с вызовом $this->_stick_params
    * Если параметр $auto_create_session установлен в true то автоматически создаст
    * сессию если она еще не создана
    *
    * @param boolean $auto_create_session
    * @return array ассоциативный массив
    */
    public function get_session_params($auto_create_session = array()) {
        if ($auto_create_session) {
            $sess_id = $this->start_session($auto_create_session);
        } else {
            $sess_id = $this->sess_id;
        }
        if ($this->cookie_session) {
            return array();
        } else {
            return array(
                $this->param_sess_id => $sess_id
            );
        }
    }

    /**
    * Создает сессию, если она еще не создана
    *
    * @param mixed $values если целое число, то интерпретируется как ID пользователя, которого нужно ассоциировать с создаваемой сессией; если ассоциативный массив, то интерпретируется как условие для выборки ID пользователя из БД если сессия уже создана то указаный пользователь проассоциирован с ней
    * @return string ID сессии - текущей или созданой
    */
    // public function start_session($values = array()) {
    public function start_session($user_id = false) {
        if ($user_id !== false) {
            if ($this->sess_id) {
                if (!isset($this->sess_data['sess_user_id']) || $this->sess_data['sess_user_id'] != $user_id) {
                    $this->user_sessions->_update(array('sess_id' => $this->sess_id), array('sess_user_id' => $user_id));
                    $this->sess_data = $this->check_session($this->sess_id);
                }
            }
            else {
                srand((float)microtime() * 1000000);
                do {
                    $sess_id = _unique_string();
                } while ($this->user_sessions->_row(array('sess_id' => $sess_id)));
    
                $this->user_sessions->_insert(array(
                    'sess_id' => $sess_id,
                    'sess_user_id' => $user_id,
                    'sess_ip' => _env('HTTP_X_FORWARDED_FOR') ? _env('HTTP_X_FORWARDED_FOR') : _env('REMOTE_ADDR')
                ));
    
                if ($this->cookie_session) {
                    _write_cookie_param($this->cookie_param_sess_id, $sess_id, false, $this->cookie_path, $this->cookie_domain);
                }
    
                $this->sess_id = $sess_id;
                $this->sess_data = $this->check_session($sess_id);
            }
            return $this->sess_id;
        }
        else {
            return false;
        }
    }

    protected function validate_user_where() {
        return 0;
    }

    public function validate_user($values) {
        if ($values) {
            return $this->users->_where($this->validate_user_where())->_row($values, 'user_id');
        }
        else {
            return false;
        }
    }

    /**
    * проверить сессию и обновляет время модификации текущей сессии
    *
    * @param mixed $sess_id
    * @return boolean true если сессия корректная, false если некорректная
    */
    public function check_session($sess_id) {
        $sessions = $this->user_sessions->_join($this->users, 'left')->_where(array(
//            'or',
//            'user_sessions.sess_user_id' => $this->default_user_id,
//            array(
//                'users.user_active <>' => 0,
//                'users.user_retired' => 0
//            )
        ))->_rows();

        $now=time();
        $found = false;
        for ($i = 0; $i < sizeof($sessions); $i++) {
            if (
                $now - $sessions[$i]['sess_modified'] > $this->session_timeout ||
//                (
//                    $sessions[$i]['sess_user_id'] != $sessions[$i]['user_id'] &&
//                    $sessions[$i]['sess_user_id'] != $this->default_user_id
//                ) ||
                (
                    $sessions[$i]['sess_id'] == $sess_id &&
                    (_env('HTTP_X_FORWARDED_FOR') ? _env('HTTP_X_FORWARDED_FOR') : _env('REMOTE_ADDR')) != $sessions[$i]['sess_ip']
                )
            ) {
                $this->stop_session($sessions[$i]['sess_id']);
                continue;
            }
            if ($sessions[$i]['sess_id'] == $sess_id) {
//                if ($sessions[$i]['sess_user_id'] == $this->default_user_id) {
//                    $sessions[$i] = array_merge($sessions[$i], $this->get_default_user_data());
//                }
                $found = $sessions[$i];
                $this->user_sessions->_update(array('sess_id' => $sess_id), array());
            }
        }
        // !!!
        // $this->clear_sid_tables();
        // EOF !!!
        return $found;
    }

    /**
    * Прекратить сессию и удалить ее из БД
    *
    * @param mixed $sess_id
    */
    public function stop_session($sess_id = false) {
        if (!$sess_id) {
            $sess_id = $this->sess_id;
        }
        if ($sess_id) {
            $this->user_sessions->_delete(array('sess_id' => $sess_id));
            if ($sess_id == $this->sess_id) {
                $this->sess_id = false;
                $this->sess_data = array();
            }
        }

        return $this;
        // !!!
        // $this->clear_sid_tables();
        // EOF !!!
    }

    /**
    * сохранить переменную в сессии
    *
    * @param mixed $group имя группы переменных, например 'shop'
    * @param mixed $var имя переменной, например 'cart_items' либо ассоциативный массив устанавливаемых переменных
    * @param mixed $value значение переменной, может быть любого типа, принимаемого функцией PHP serialize
    */
    public function set_session_data($group, $var, $value = false) {
        if (is_array($var)) {
            if ($value !== false) {
                _cc::fatal_error(_DEBUG_CC, 'set_session_data parameters error');
            }
            else {
                $this->reset_session_data($group);
                foreach ($var as $real_var => $real_val) {
                    $this->set_session_data($group, $real_var, $real_val);
                }
            }
        }
        else {
            if (strlen($group) > $this->max_group_name_length) {
                _cc::fatal_error(_DEBUG_CC, 'Group name length for session data could not be greater then ' . $this->max_group_name_length);
            }
            if (strlen($var) > $this->max_var_name_length) {
                _cc::fatal_error(_DEBUG_CC, 'Var name length for session data could not be greater then ' . $this->max_var_name_length);
            }
            $sess_id = $this->start_session();
            // 2012-01-25: зачем создавать экземпляр?
            // $this->_create_data_source('user_session_data')->_delete(array(
            $this->user_session_data->_delete(array(
                'usd_sess_id' => $sess_id,
                'usd_group' => $group,
                'usd_var' => $var
            ))->_insert(array(
                'usd_sess_id' => $sess_id,
                'usd_group' => $group,
                'usd_var' => $var,
                'usd_value' => serialize($value)
            ));
        }

        // TODO: сделать зеркальное обновление кеша вместо очистки
        $this->cache_session_data = false;

        return $this;
    }

    /**
    * получить переменную из сессии
    *
    * @param mixed $group имя группы переменных, например 'shop'
    * @param mixed $var имя переменной, например 'cart_items'
    * @return mixed
    */
    public function get_session_data($group, $var = false) {
        // 2012-01-25: реализован кеш
        /*
        if ($var) {
            return unserialize($this->user_session_data->_row(array(
                'usd_sess_id' => $sess_id,
                'usd_group' => $group,
                'usd_var' => $var
            ), 'usd_value'));
        }
        else {
            $vars = $this->user_session_data->_rows(array(
                'usd_sess_id' => $sess_id,
                'usd_group' => $group
            ), 'usd_var, usd_value');
            $ret = array();
            foreach ($vars as $var) {
                $ret[$var['usd_var']] = unserialize($var['usd_value']);
            }
            return $ret;
        }
        */
        if ($this->cache_session_data === false) {
            $sess_id = $this->start_session();
            $unsorted = $this->user_session_data->_rows(array('usd_sess_id' => $sess_id));
            $this->cache_session_data = array();
            foreach ($unsorted as $data) {
                if (!isset($this->cache_session_data[$data['usd_group']])) {
                    $this->cache_session_data[$data['usd_group']] = array();
                }
                if (!isset($this->cache_session_data[$data['usd_group']][$data['usd_var']])) {
                    $this->cache_session_data[$data['usd_group']][$data['usd_var']] = $data['usd_value'];
                }
            }
        }
        if ($var) {
            if (isset($this->cache_session_data[$group][$var])) {
                return unserialize($this->user_session_data->adjust_before_usage($this->cache_session_data[$group][$var]));
            }
            else {
                return false;
            }
        }
        else {
            if (isset($this->cache_session_data[$group])) {
                $ret = array();
                foreach ($this->cache_session_data[$group] as $key => $value) {
                    $ret[$key] = unserialize($this->user_session_data->adjust_before_usage($value));
                }
                return $ret;
            }
            else {
                return false;
            }
        }
    }

    /**
    * удалить переменную из сессии и вернуть то, что было в этом контейнере
    *
    * @param mixed $group имя группы переменных, например 'shop'
    * @param mixed $var имя переменной, например 'cart_items'
    * @return
    */
    public function reset_session_data($group, $var = false) {
        $sess_id = $this->start_session();
        
        // Get result before deleting
        $result = $this->get_session_data($group, $var);
        
        // Delete session data 
        $where = array(
            'usd_sess_id' => $sess_id,
            'usd_group' => $group
        );
        if ($var) {
            $where['usd_var'] = $var;
        }
        $this->user_session_data->_delete($where);

        // TODO: сделать зеркальное обновление кеша вместо очистки
        $this->cache_session_data = false;

        return $result;
    }

    /**
    * Возвращает данные о текущем пользователе
    * если в параметре указан конкретный ключ данных - вернется его значение, иначе все
    * данные (ассоциативный массив)
    *
    * @param string $key ключ данных (опционально)
    * @return mixed
    */
    public function get_data($key = false) {
        if ($key) {
            if (isset($this->sess_data[$key])) {
                return $this->sess_data[$key];
            } else {
                return false;
            }
        } else {
            return $this->sess_data;
        }
    }

    public function get_users() {
        return $this->users;
    }

}
