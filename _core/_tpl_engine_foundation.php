<?php
/**
 * @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
 * @package CleverCore2
 */

/**
 * Main class to manage templates.
 */
abstract class _tpl_engine_foundation extends _core {

    public $_vars = false;
    protected $__type = '_tpl_engine';
    protected $__name = false;
    protected $__content = false;
    protected $__path = false;
    protected $__source_path = false;
    protected $__paths_debug = array();
    protected $__search_above_path = false;

    public function __construct($data = array()) {
        parent::__construct($data);

        if (!$this->_vars) {
            $this->_vars = array();
        }
    }

    /**
    * Подменить имя файла шаблона
    * ЭКСПЕРИМЕНТ
    * 
    * @param mixed $name новое имя файла
    */
    public function _set_tpl_name($name) {
        if ($this->__name != $name) {
            $this->__name = $name;
            $this->__path = false;
        }
        return $this;
    }

    /**
    * Добавить произвольную переменную в существующий объект tpl_engine
    * 
    * @param mixed $name
    * @param mixed $value
    */
    public function _add_var($name, $value) {
        $this->_vars[$name] = $value;
        return $this;
    }

    protected function __generate_path($force = false) {
        if ($force || ($this->__path === false && $this->__name)) {
            if ($this->__search_above_path) {
                $local_mask = '#^' . preg_quote(__LOCALE_TEMPLATES_PATH) . '#';
                if (preg_match($local_mask, $this->__search_above_path)) {
                    $extra = 'locale.' . preg_replace($local_mask, '', $this->__search_above_path);
                }
                else {
                    $extra = 'engine.' . preg_replace('#^' . preg_quote(__ENGINE_TEMPLATES_PATH) . '#', '', $this->__search_above_path);
                }
                $this->__path = 
                    __TPL_CACHE_PATH . 
                    $this->_get_holder()->_get_upline_path() . 
                    '/' . 
                    $extra;
            }
            else {
                $this->__path = __TPL_CACHE_PATH . $this->_get_holder()->_get_upline_path() . '/';
            }

            if (
                $force || (
                    !_cc::is_release() &&
                    !file_exists($this->__path . $this->__name)
                )
            ) {
                _mkdir($this->__path);
                $search_tpl = true;
            }
            else if (_FORCE_TPL_SEARCH && !_cc::is_release()) {
                $search_tpl = true;
            }
            else if (_DEBUG_LEVEL & _DEBUG_TPL) {
                if (file_exists($this->__path . $this->__name . '.debug')) {
                    if ($debug_data = @file($this->__path . $this->__name . '.debug', FILE_IGNORE_NEW_LINES)) {
                        if (file_exists($debug_data[0] . $this->__name) && filemtime($debug_data[0] . $this->__name) == $debug_data[1]) {
                            $search_tpl = false;
                        }
                        else {
                            $search_tpl = true;
                        }
                        // this variable will be used for debug message, do not rename it
                        $this->__source_path = $debug_data[0];
                    }
                    else {
                        $search_tpl = true;
                    }
                }
                else {
                    $search_tpl = true;
                }
            }
            else {
                $search_tpl = false;
            }

            // если не релиз - все шаблоны ищутся всегда
            if ((!_cc::is_release() && _FORCE_TPL_SEARCH) || $search_tpl) {
                if (isset($GLOBALS['__template_paths_cache'][$this->__path . $this->__name]) && !$this->__search_above_path) {
                    $this->__source_path = $GLOBALS['__template_paths_cache'][$this->__path . $this->__name];
                    $ret = true;
                }
                else {
                    if ($this->__name[0] == '/') { // указан полный путь к шаблону
                        $full_path = dirname(substr($this->__name, 1)) . '/';
                        if ($full_path == './') {
                            $full_path = '';
                        }
                        $this->__name = basename($this->__name);

                        if (file_exists(__LOCALE_TEMPLATES_PATH . $full_path . $this->__name)) {
                            $this->__source_path = __LOCALE_TEMPLATES_PATH . $full_path;
                        }
                        else if (file_exists(__ENGINE_TEMPLATES_PATH . $full_path . $this->__name)) {
                            $this->__source_path = __ENGINE_TEMPLATES_PATH . $full_path;
                        }
                        else {
                            $this->__source_path = false;
                            $this->__paths_debug = array(
                                __LOCALE_TEMPLATES_PATH . $full_path,
                                __ENGINE_TEMPLATES_PATH . $full_path
                            );
                        }
                    }
                    else {
                        $research = array();
                        $parent = $this->_get_holder();
                        $first_iteration = true;
                        do {
                            while ($parent->_get_type() != '_module') {
                                $parent = $parent->_get_holder();
                            }
                            $hierarchy = $parent->_get_hierarchy();
                            if (!$first_iteration) {
                                // remove _core and _module from hierarchy
                                array_pop($hierarchy);
                                array_pop($hierarchy);
                            }
                            else {
                                // remove _module from hierarchy
                                array_splice($hierarchy, sizeof($hierarchy) - 2, 1);
                            }
                            if ($_templates_dir = $parent->_get_templates_dir()) {
                                // add templates_dir with highest priority
                                for ($i = sizeof($_templates_dir) - 1; $i >= 0; $i--) {
                                    $dirname = dirname($_templates_dir[$i]);
                                    $class = basename($_templates_dir[$i]);
                                    if ($dirname && !isset($GLOBALS['__classes'][$class])) {
                                        $GLOBALS['__classes'][$class] = _fix_path($dirname);
                                    }
                                    array_unshift($hierarchy, $class);
                                }
                            }
                            if (!$first_iteration) {
                                // add empty dir with lowest priority
                                $hierarchy[] = '';
                            }

                            array_unshift($research, $hierarchy);
                            $parent = $parent->_get_holder();
                            if ($first_iteration) {
                                $first_iteration = false;
                            }
                        } while ($parent && get_class($parent) != 'modBootstrap');

                        $this->__paths_debug = array();

                        $paths = $this->__generate_path_variants($research, __LOCALE_TEMPLATES_PATH);

                        $this->__source_path = false;
                        for ($i = 0; $i < sizeof($paths); $i++) {
                            if ($this->__search_above_path) {
                                if ($paths[$i] == $this->__search_above_path) {
                                    $this->__search_above_path = false;
                                }
                            }
                            else {
                                if (file_exists($paths[$i] . $this->__name)) {
                                    $this->__source_path = $paths[$i];
                                    break;
                                }
                            }
                        }
                        if (!$this->__source_path) {

                            $paths = $this->__generate_path_variants($research, __ENGINE_TEMPLATES_PATH);
                            for ($i = 0; $i < sizeof($paths); $i++) {
                                if ($this->__search_above_path) {
                                    if ($paths[$i] == $this->__search_above_path) {
                                        $this->__search_above_path = false;
                                    }
                                }
                                else {
                                    if (file_exists($paths[$i] . $this->__name)) {
                                        $this->__source_path = $paths[$i];
                                        break;
                                    }
                                }
                            }
                        }
                    }
                    if ($this->__source_path) {
                        $this->__copy($this->__source_path . $this->__name, $this->__path . $this->__name);
                        if ((_DEBUG_LEVEL & _DEBUG_TPL) && !_FORCE_TPL_SEARCH) {
                            $fp = fopen($this->__path . $this->__name . '.debug', 'w');
                            // source
                            fwrite($fp, $this->__source_path . "\n");
                            // modified
                            fwrite($fp, filemtime($this->__source_path . $this->__name) . "\n");
                            fclose($fp);
                        }
                        $ret = true;
                    }
                    else {
                        if (_DEBUG_LEVEL & _DEBUG_TPL) {
                            $message = 'CC Error. Template <b>' . $this->__name . '</b> for <b>' . get_class($this->_get_holder()) . '</b> not found.<br />';
                            $message .= '<b>Search paths:</b>';
                            for ($i = 0; $i < sizeof($this->__paths_debug); $i++) {
                                $message .= '<br />' . $this->__paths_debug[$i] . $this->__name;
                            }
                            _cc::debug_message(_DEBUG_TPL, $message, 'error');
                        }
                        $ret = false;
                    }

                }
            }
            else {
                $ret = true;
            }

            if ($ret) {
                if (_DEBUG_LEVEL & _DEBUG_TPL) {
                    _cc::debug_message(_DEBUG_TPL, 'Template <b>' . $this->__name . '</b> for <b>' . get_class($this->_get_holder()) . '</b> found at <b>' . $this->__source_path . '</b>');
                }
            }
            if ($this->__source_path) {
                $GLOBALS['__template_paths_cache'][$this->__path . $this->__name] = $this->__source_path;
            }
            return $ret;
        }
        else if ($this->__path) {
            return true;
        }
        else {
            return false;
        }
    }

    protected function __generate_path_variants($research, $path) {
        $variants = $this->__generate_path_variants_rec(sizeof($research) - 2, $research);
        $paths = array();
        for ($i = 0; $i < sizeof($research[sizeof($research) - 1]); $i++) {
            for ($j = 0; $j < sizeof($variants); $j++) {
                if ($variants[$j] && $research[sizeof($research) - 1][$i]) {
                    if ($this->__is_tpl_dir($path . $variants[$j] . '.' . $research[sizeof($research) - 1][$i])) {
                        $paths[] = $path . $variants[$j] . '.' . $research[sizeof($research) - 1][$i] . '/';
                    }
                    $this->__paths_debug[] = $path . $variants[$j] . '.' . $research[sizeof($research) - 1][$i] . '/';
                }
                else if ($variants[$j]) {
                    if ($this->__is_tpl_dir($path . $variants[$j])) {
                        $paths[] = $path . $variants[$j] . '/';
                    }
                    $this->__paths_debug[] = $path . $variants[$j] . '/';
                }
                else if ($research[sizeof($research) - 1][$i]) {
                    // add to search path list full path with module subdirectory
                    if(isset($GLOBALS['__classes'][$research[sizeof($research) - 1][$i]])) {
                        $full_path = $GLOBALS['__classes'][$research[sizeof($research) - 1][$i]] . $research[sizeof($research) - 1][$i];
                        if ($this->__is_tpl_dir($path . $full_path)) {
                            $paths[] = $path . $full_path . '/';
                        }
                        $this->__paths_debug[] = $path . $full_path . '/';
                    }

                    // 2012-07-19 - only _core added
                    if ($research[sizeof($research) - 1][$i] == '_core') {
                        if ($this->__is_tpl_dir($path . $research[sizeof($research) - 1][$i])) {
                            $paths[] = $path . $research[sizeof($research) - 1][$i] . '/';
                        }
                        $this->__paths_debug[] = $path . $research[sizeof($research) - 1][$i] . '/';
                    }
                    // EOF 2012-07-19
                }
            }
        }
        return $paths;
    }

    /**
    * вызывает is_dir и кеширует результат
    */
    protected function __is_tpl_dir($dir) {
        if (!isset($GLOBALS['__is_tpl_dir_cache'])) {
            $GLOBALS['__is_tpl_dir_cache'] = array();
            $this->__create_tpl_dir_cache_rec(__LOCALE_TEMPLATES_PATH);
            $this->__create_tpl_dir_cache_rec(__ENGINE_TEMPLATES_PATH);
        }
        return in_array($dir, $GLOBALS['__is_tpl_dir_cache']);
    }

    protected function __create_tpl_dir_cache_rec($dir) {
        $dr = opendir($dir);
        while ($fl = readdir($dr)) {
            if ($fl != '.' && $fl != '..' && is_dir($dir . $fl)) {
                $GLOBALS['__is_tpl_dir_cache'][] = $dir . $fl;
                $this->__create_tpl_dir_cache_rec($dir . $fl . '/');
            }
        }
        closedir($dr);
    }

    protected function __generate_path_variants_rec($i, $research) {
        if ($i == -1) {
            return false;
        }
        $res = array();
        for ($j = 0; $j < sizeof($research[$i]); $j++) {
            if ($recursion = $this->__generate_path_variants_rec($i - 1, $research)) {
                for ($k = 0; $k < sizeof($recursion); $k++) {
                    if ($research[$i][$j] && $recursion[$k]) {
                        if(isset($GLOBALS['__classes'][$recursion[$k]])) {
                            $res[] = $GLOBALS['__classes'][$recursion[$k]] . $recursion[$k] . '.' . $research[$i][$j];
                        }
                        else {
                            $res[] = $recursion[$k] . '.' . $research[$i][$j];
                        }
                    }
                    elseif ($research[$i][$j]) {
                        if (isset($GLOBALS['__classes'][$research[$i][$j]])) {
                            $res[] = $GLOBALS['__classes'][$research[$i][$j]] . $research[$i][$j];
                        }
                        else {
                            $res[] = $research[$i][$j];
                        }
                    }
                    elseif ($recursion[$k]) {
                        if (isset($GLOBALS['__classes'][$recursion[$k]])) {
                            $res[] = $GLOBALS['__classes'][$recursion[$k]] . $recursion[$k];
                        }
                        else {
                             $res[] = $recursion[$k];
                        }
                    }
                    else {
                        $res[] = '';
                    }
                }
            } else {
                if (!$research[$i][$j]) {
                    $res[] = '';
                }
                else if (isset($GLOBALS['__classes'][$research[$i][$j]])) {
                    $research[$i][$j] = $GLOBALS['__classes'][$research[$i][$j]] . $research[$i][$j];
                    $res[] = $research[$i][$j];
                }
            }
        }
        return $res;
    }

    protected function __copy($source, $dest) {
        _mkdir(dirname($dest));
        $file_content = $this->__parse_tpl(file_get_contents($source), 'html', true);

        // php 5.3.9 fix (it does not allow to include 4k sized files
        if (phpversion() == '5.3.9' || phpversion() == '5.3.10') {
            $file_size_div_4k = strlen($file_content) / 4096;
            if ($file_size_div_4k == floor($file_size_div_4k)) {
                $file_content .= ' ';
            }
        }
        // EOF fix

        file_put_contents($dest, $file_content);
        return true;
    }

    protected function __parse_tpl($tpl, $mode, $first_iteration = false) {
        return $tpl;
    }

    /**
    * Создает и инициализирует переменными объект tpl от имени модуля, который создал данный шаблон
    *
    * @param string $tpl_name Имя шаблона
    * @param array $tpl_vars Переменные, которые будут переданы в шаблон
    * 
    * @return tpl_engine Объект tpl_engine
    */
    public function _tpl($tpl_name, $tpl_vars = false, $search_above_path = false) {
        /*
        if ($tpl_name[0] == '*') {
            $tpl_name = substr($tpl_name, 1);
            $search_above_path = $this->__source_path;
        }
        */
        return $this->_get_holder()->_tpl($tpl_name, $tpl_vars, $search_above_path);
    }

    /**
    * Depricated. Для обратной совместимости
    * 
    * @param string $tpl_name
    * @param array $tpl_vars
    */
    public function _create_tpl($tpl_name, $tpl_vars = false, $search_above_path = false) {
        return $this->_tpl($tpl_name, $tpl_vars, $search_above_path);
    }

    /**
    * Поиск, подключение и создание объекта модуля
    * 
    * Держателем модуля становится не сам tpl_engine, а модуль, который создал этот tpl_engine
    * 
    * @param string $module_name Название модуля
    * @param array $data  Ассоциативный массив параметров, которые будут переданы модулю при создании
    * @return object $module Объект модуля
    */
    function _module($module_name, $data = array()) {
        return $this->_get_holder()->_module($module_name, $data);
    }

    /**
    * Создает и инициализирует переменными объект data_source
    * 
    * Держателем модуля становится не сам tpl_engine, а модуль, который создал этот tpl_engine
    * 
    * @param string $module_name Название модуля
    * @param array $data  Ассоциативный массив параметров, которые будут переданы модулю при создании
    * @return object $module Объект модуля
    */
    /* 2012-04-25 перенесено в _core_foundation
    function _create_data_source($data, $initial_data = array(), $data_class = false) {
        if (!$data_class) {
            $data_class = __DEFAULT_DATA_SOURCE_CLASS;
        }
        return $this->_get_holder()->_create_data_source($data, $initial_data, $data_class);
    }
    */

    /**
    * Вызывает соответсвующий метод модуля-держатедя и возвращает результат его работы
    */
    function _link($params = array(), $url = false) {
        return $this->_get_holder()->_link($params, $url);
    }

    /**
    * Depricated. Для обратной совместимости
    * 
    * @param mixed $params
    * @param mixed $url
    */
    function _get_link($params = array(), $url = false) {
        return $this->_get_holder()->_link($params, $url);
    }

    /**
    * Вызывает соответсвующий метод модуля-держатедя и возвращает результат его работы
    */
    function _hlink($params = array(), $url = false) {
        return $this->_get_holder()->_hlink($params, $url);
    }

    /**
    * Depricated. Для обратной совместимости
    */
    function _html_link($params = array(), $url = false) {
        return $this->_get_holder()->_html_link($params, $url);
    }

    /**
    * Depricated. Для обратной совместимости
    * 
    * @param mixed $params
    * @param mixed $url
    */
    function _get_html_link($params = array(), $url = false) {
        return $this->_get_holder()->_html_link($params, $url);
    }

    /**
    * метод работает в 2-ух режимах:
    *   1. по-умолчанию, если не переданы параметры он парсит шаблон и возвращает результат его работы
    *   2. если переданы параметры, то возвращает ассоциативный массив, каждый ключ которого соответсует 
    * параметру, а значения - значениям из переменных, установленных внутри шаблона. При этом если какой-то 
    * переменной не существует - она будет пропущена (поведение, аналогичное ф-ии PHP compact() ).
    * 
    * Например, если в коде встречается вызов
    *   $vars = $this->_create_tpl($tpl_name)->_get_result('a', 'b', 'c');
    * , а в шаблоне записано:
    *   <?php 
    *       $a = 'hello';
    *       $b = 'world';
    *   ?>
    * , то в переменной $vars будет следующий ассоциативный массив:
    *   array(
    *       'a' => 'hello',
    *       'b' => 'world'
    *   );
    */
    public function _get_result() {
        $this->_vars['_baseurl'] = _BASEURL;
        $this->_vars['_coreurl'] = _COREURL;
        extract($this->_vars);
        if ($this->__content) {
            $php = $this->__parse_tpl($this->__content, 'html', true);

            _ob_start();
            eval(' ?>' . $php . '<?php ');
            $result = _ob_get_clean();

            $args = func_get_args();
            if (sizeof($args)) {
                return call_user_func_array('compact', $args);
            }
            else {
                return $result;
            }

        }
        else if ($this->__name) {
            if ($this->__generate_path()) {
                $args = func_get_args();
                if (sizeof($args) == 1 && $args[0] === false) {
                    $evaluate = false;
                }
                else {
                    $evaluate = true;
                }

                if (_cc::is_release()) {
                    // TODO: более "красивый" кеш
                    if (!isset($GLOBALS['__tpl_cache'][$this->__path . $this->__name])) {
                        $GLOBALS['__tpl_cache'][$this->__path . $this->__name] = file_get_contents($this->__path . $this->__name);
                    }
                    if ($GLOBALS['__tpl_cache'][$this->__path . $this->__name] === false) {
                        // похоже скомпилированый шаблон по каким-то причинам потерялся
                        // форсировать поиск и компиляцию шаблона
                        if ($this->__generate_path(true)) {
                            $GLOBALS['__tpl_cache'][$this->__path . $this->__name] = file_get_contents($this->__path . $this->__name);
                        }
                    }
                    if ($evaluate) {
                        _ob_start();
                        eval(' ?>' . $GLOBALS['__tpl_cache'][$this->__path . $this->__name] . '<?php ');
                        $result = _ob_get_clean();
                    }
                    else {
                        $result = $GLOBALS['__tpl_cache'][$this->__path . $this->__name];
                    }
                }
                else {
                    if ($evaluate) {
                        _ob_start();
                        include($this->__path . $this->__name);
                        $result = _ob_get_clean();
                    }
                    else {
                        $result = file_get_contents($this->__path . $this->__name);
                    }
                }

                if (sizeof($args) > 1) {
                    return call_user_func_array('compact', $args);
                }
                else if (sizeof($args) == 1 && $args[0] !== false) {
                    return $$args[0];
                }
                else {
                    return $result;
                }
            }
            else {
                return '';
            }

        } else {
            return '';
        }
    }
}


