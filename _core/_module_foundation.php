<?php
/**
 * @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
 * @package CleverCore2
 */

/**
 * Base class for all CleverCore2 modules.
 */

class _module_foundation extends _core {
    protected $__last_tpl = false;
    protected $__sticked_params = array();
    protected $__type = '_module';
    protected $_templates_dir = array();
    protected $_title = false;
    protected $_routers = array();

    public function __construct($data = array()) {
        parent::__construct($data);

        if ($this->_templates_dir === false) {
            $this->_templates_dir = array();
        }
        else if (!is_array($this->_templates_dir)) {
            $this->_templates_dir = array($this->_templates_dir);
        }

        if (!$this->_title) {
            $this->_title = get_class($this);
        }

        $parent = $this->_get_holder();
        
        while ($parent && $parent->_get_type() != '_module') {
            $parent = $parent->_get_holder();
        }
        
        if ($parent) {
            $this->_stick_params($parent->_get_sticked_params());
        }
    }

    /**
    * Возвращает ситабельный заголовок данного модуля
    */
    public function _get_title() {
        return $this->_title;
    }

    /**
    * Возвращает алиас-имя данного объекта
    *
    * @return string алиас-имя данного объекта
    */
    public function _get_object_alias() {
        if (sizeof($this->_templates_dir)) {
            return $this->_templates_dir[0];
        }
        else {
            return parent::_get_object_alias();
        }
    }

    public function _get_routers() {
        return $this->_routers;
    }

    /**
    * Главная функция модуля по-умолчанию
    *
    * Возвращает объект tpl_engine, используется шаблон default.tpl, в него не передаются ничего
    * По сути бесполезная заглушка, должна быть реализована в каждом наследнике (кроме тех случаев, 
    * когда ее вызов не предполагается, например при создании библиотеки)
    */
    public function _run() {
        return $this->_tpl();
        //return $this->_create_tpl('default.tpl');
    }

    /**
    * Поиск, подключение и создание объекта модуля
    * 
    * Альтернатива прямому вызову _cc :: create_module(...);
    * 
    * @param string $module_name Название модуля
    * @param array $data  Ассоциативный массив параметров, которые будут переданы модулю при создании
    * @return object $module Объект модуля
    */
    public function _module($module_name, $data = array()) {
        return _cc::create_module($module_name, $data, $this);
    }

    /**
    * Поиск, подключение, создание и запуск объекта модуля
    * 
    * Альтернатива прямому вызову _cc :: execute_module(...);
    * 
    * @param string $module_name Название модуля
    * @param array $data Ассоциативный массив параметров, которые будут переданы модулю при создании
    * @return string $html Результат работы модуля
    */
    public function _execute_module($module_name, $data = array()) {
        return _cc::execute_module($module_name, $data, $this);
    }

    /**
    * Создает и инициализирует переменными объект tpl
    *
    * Возвращает результат работы (объект tpl), а так же записывает его
    * во внутреннюю переменную, которая при необходимости доступна позже 
    * с помощью функции _get_last_tpl
    * 
    * @param string $tpl_name Имя шаблона
    * @param array $tpl_vars Переменные, которые будут переданы в шаблон
    * 
    * @return tpl_engine Объект tpl_engine
    */
    public function _tpl($tpl_name = false, $tpl_vars = false, $search_above_path = false) {
        if ($tpl_name === false || is_array($tpl_name)) {
            $tpl_vars = $tpl_name;
            $tpl_name = 'default.tpl';
        }
        $this->__last_tpl = _cc::create_tpl_from_file($tpl_name, $tpl_vars, $this, $search_above_path);
        return $this->__last_tpl;
    }

    /**
    * Для обратной совместимости
    * 
    * @param string $tpl_name
    * @param array $tpl_vars
    */
    public function _create_tpl($tpl_name, $tpl_vars = array()) {
        return $this->_tpl($tpl_name, $tpl_vars);
    }

    /**
    * Возвращает объект шаблона, который был создан модулем
    */
    public function _get_last_tpl() {
        return $this->__last_tpl;
    }

    /**
    * Функции для работы с хлебными крошками 
    */
    protected $__breadcrumbs = array();

    /**
    * Add link to breadcrumbs
    *
    * @param string $title
    * @param string $link
    * @param array $siblings
    * @return module this
    */
    public function _add_breadcrumb($title, $link = false, $siblings = array()) {
        $this->_append_breadcrumbs(array($this->_create_breadcrumb($title, $link, $siblings)));
        return $this;
    }

    /**
    * Generate breadcrumb element 
    * 
    * @param mixed $title
    * @param string $link
    * @param mixed $siblings
    * @return array
    */
    protected function _create_breadcrumb($title, $link = false, $siblings = array()) {
        if ($title === false) {
            return false;
        }
        else {
            if ($link === false) {
                $link = $this->_link();
            }
            else if (is_array($link)) {
                $link = $this->_link($link);
            }
            return array(
                'title' => $title,
                'link' => $link,
                'siblings' => $siblings
            );
        }
    }

    /**
    * Добавить несколько ссылок в конец хлебных крошек
    * 
    * @param array $breadcrumbs
    * @return module this
    */
    public function _append_breadcrumbs($breadcrumbs) {
        for ($i=0; $i < sizeof($breadcrumbs); $i++) {
            if ($breadcrumbs[$i] === false) {
                array_pop($this->__breadcrumbs);
            }
            else {
                $this->__breadcrumbs[] = $breadcrumbs[$i];
            }
        }
        return $this;
    }

    /**
    * Добавить несколько ссылок в начало хлебных крошек
    * 
    * @param array $breadcrumbs
    * @return module this
    */
    public function _prepend_breadcrumbs($breadcrumbs) {
        for ($i = sizeof($breadcrumbs) - 1; $i >= 0; $i--) {
            if ($breadcrumbs[$i] === false) {
                array_shift($this->__breadcrumbs);
            }
            else {
                array_unshift($this->__breadcrumbs, $breadcrumbs[$i]);
            }
        }
        return $this;
    }

    /**
    * Получить все хлебные крошки
    * если в списке крошек есть ссылка, для которой не задан title, то эта ссылка подменит предыдущую
    * 
    * @return array breadcrumbs
    */
    public function _get_breadcrumbs() {
        $pure_breadcrumbs = array();
        for ($i = 0; $i < sizeof($this->__breadcrumbs); $i++) {
            if (
                !$this->__breadcrumbs[$i]['title'] &&
                sizeof($pure_breadcrumbs)
           ) {
                $pure_breadcrumbs[sizeof($pure_breadcrumbs) - 1]['link'] = $this->__breadcrumbs[$i]['link'];
            } else {
                $pure_breadcrumbs[] = $this->__breadcrumbs[$i];
            }
        }
        return $pure_breadcrumbs;
    }

    /**
    * Читает параметр и сразу приклеивает его к себе
    *
    * @param string param_name
    * @return mixed
    */
    public function _read_sticky_param($param_name, $default_value = false, $disable_trim = false) {
        $param_value = _read_param($param_name, $default_value, $disable_trim);
        $this->_stick_params(array(
            $param_name => $param_value
       ));
        return $param_value;
    }

    protected function __merge_params($params1, $params2) {
        /*
        --------------------------------------------------------------------------------------------
        Ниже алгоритм, который умеет склеивать параметры, значения которых являются массивами,
        например, 
        $params1 = array('param' => array('a' => 'b'));
        $params2 = array('param' => array('c' => 'd'));
        то в результате работы этого алгоритма получится
        array(
            'param' => array(
                'a' => 'b',
                'c' => 'd'
            )
        );
        так зачем-то было сделано в какой-то момент развития движка.
        Позже возникла проблема, когда приходилось склеивать параметры с простыми одномерными 
        массивами:
        $params1 = array('param' => array('a', 'b'));
        $params2 = array('param' => array('a', 'd'));
        т.к. программист получал
        array(
            'param' => array(
                'a', 'b', 'a', 'd'
            )
        );
        , а ожидал 
        array(
            'param' => array(
                'a', 'd'
            )
        );
        Алгоритм был доработан так, что бы определять тип массивов, и если хоть один из них является
        простым одномерным, то происходит полное замещение, как ожидал программист.
        При этом изучение репазитория с изменениями показазало, что возможность "интеллектуально" 
        склеивать массивы была добавлена автором скорее всего не под какие-то конкретные нужды, а 
        просто во время очередного творческого порыва, и вероятно является ошибочной.
        По этой причине "интеллектуальная" возможность была убрана, на ее место вернулось простое
        полное замещение вторым массивом первого, независимо от их типов. Сам же алгоритм и эти 
        комментарии оставлен на случай ошибочных выводов при изучении репазитория.
        --------------------------------------------------------------------------------------------
        
        foreach ($params2 as $key => $val) {
            if ($val === false) {
                if (isset($params1[$key])) {
                    unset($params1[$key]);
                }
            }
            else {
                if (
                    is_array($val) || (
                        isset($params1[$key]) && 
                        is_array($params1[$key])
                    )
                ) {
                    if (!is_array($val)) {
                        $val = array($val);
                    }
                    if (!isset($params1[$key])) {
                        $params1[$key] = array();
                    }
                    else if (!is_array($params1[$key])) {
                        $params1[$key] = array($params1[$key]);
                    }
                    if (_is_assoc($params1[$key]) || _is_assoc($val)) {
                        $params1[$key] = array_merge($params1[$key], $val);
                    }
                    else {
                        $params1[$key] = $val;
                    }
                }
                else {
                    $params1[$key] = $val;
                }
            }
        }
        if (!function_exists('_is_assoc')) {
            function _is_assoc($array) {
                if (is_array($array)) {
                    $keys = array_keys($array);
                    foreach ($keys as $k) {
                        if (!is_numeric($k)) {
                            return true;
                        }
                    }
                }
                return false;
            }
        }
        */
        foreach ($params2 as $key => $val) {
            if ($val === false) {
                if (isset($params1[$key])) {
                    unset($params1[$key]);
                }
            }
            else {
                $params1[$key] = $val;
            }
        }
        return $params1;
    }

    /**
    * stick parameters to module
    * will replace existing parameters
    * will remove parameters which are set to false
    *
    * @param array $params
    * @return object this
    */
    public function _stick_params($params = array()) {
        $this->__sticked_params = $this->__merge_params(
            $this->__sticked_params, 
            __adjust_params($params)
        );
        return $this;
    }

    public function _stick_param($name, $value) {
        return $this->_stick_params(array($name => $value));
    }

    /**
    * Возвращает все параметры, которые были приклеены к модулю
    * если передать false, то для каждого параметра вместо значения будет возвращаться false
    * (удобно использовать для отклеивания параметров от объекта или ссылки)
    * 
    * @return array
    */
    public function _get_sticked_params($values = true) {
        if ($values) {
            return $this->__sticked_params;
        }
        else {
            $ret = array();
            foreach ($this->__sticked_params as $p => $v) {
                $ret[$p] = false;
            }
            return $ret;
        }
    }

    /**
    * Генерировать ссылку. Ссылка будет сгенерирована относительно текущего менеджера и будет 
    * содержать все параметры, которые были "приклеены" соответсвующими методами, а также все 
    * параметры, которые были переданы в первом параметре $params. Если указан второй параметр 
    * $rout_rule, то все приклееные ранее параметры будут проигнорированы, а ссылка будет 
    * генерироваться относительно менеджера, на который ссылкается второй параметр.
    * 
    * При генерировании ссылки будут учтены все правила, перечисленные в настройке _url_converters.
    * 
    * @param array $params
    * @param mixed $rout_rule
    * @return string ссылка
    */
    public function _link($params = array(), $rout_rule = false, $split_url_params = false) {
        return $this->_internal_link($params, $rout_rule, '&', $split_url_params, 'link');
    }

    public function _ssl_link($params = array(), $rout_rule = false, $split_url_params = false) {
        return $this->_internal_link($params, $rout_rule, '&', $split_url_params, 'ssl_link');
    }

    /**
    * Depricated. Для обратной совместимости
    */
    public function _get_link($params = array(), $rout_rule = false) {
        return $this->_link($params, $rout_rule);
    }

    /**
    * Генерировать ссылку, совместимую с HTML4. Отличие от _link состоит в том, что параметры
    * будут разделяться &amp; а не просто & . Этот метод рекомендуется использовать, для генерирования
    * ссылок, которые будут вставляться в HTML документ, для всех остальных случаев (редирект,
    * ссылка для письма в plain-text формате, ссылка для использования в JS и т.п.) следует использовать
     * метод _link
    * 
    * @param mixed $params
    * @param mixed $rout_rule
    * @return string
    */
    public function _hlink($params = array(), $rout_rule = false, $split_url_params = false) {
        return $this->_internal_link($params, $rout_rule, '&amp;', $split_url_params, 'link');
    }

    public function _ssl_hlink($params = array(), $rout_rule = false, $split_url_params = false) {
        return $this->_internal_link($params, $rout_rule, '&amp;', $split_url_params, 'ssl_link');
    }

    /**
    * Depricated. Для обратной совместимости
    */
    public function _html_link($params = array(), $rout_rule = false) {
        return $this->_hlink($params, $rout_rule);
    }

    /**
    * Depricated. Для обратной совместимости
    */
    public function _get_html_link($params = array(), $rout_rule = false) {
        return $this->_hlink($params, $rout_rule);
    }

    protected function _internal_link($params = array(), $rout_rule = false, $params_separator = false, $split_url_params = false, $method = 'link') {
        $params = __adjust_params($params);
        if ($rout_rule) {
            return _cc::$method($params, $rout_rule, $params_separator, $split_url_params);
        }
        else {
            return _cc::$method($this->__merge_params($this->__sticked_params, $params), false, $params_separator, $split_url_params);
        }
    }

    public function _get_templates_dir() {
        return $this->_templates_dir;
    }

    public function _redirect($link = false) {
        if (is_array($link)) {
            $link = $this->_link($link);
        }
        else if (!$link) {
            $link = $this->_link();
        }
        _redirect($link);
    }

    protected function __get_localization_suffix() {
        return __MODULES_DIR;
    }

}


