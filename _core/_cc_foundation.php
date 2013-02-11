<?php
/**
 * @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
 * @package CleverCore2
 */

/**
 * Low-level static class:
 *
 * - loads and creates modules;
 * - loads and creates templates;
 * - loads and creates data sources;
 * - loads and creates url converters;
 * - creates links/hlinks.
 */
abstract class _cc_foundation {
    /**
    * Поиск и подключение модуля
    *
    * @param string $module_path Путь и название модуля, например, fields/field_text
    * @return boolean
    */
    public static function load_module($module_path) {
        $dirname = dirname($module_path);
        if ($dirname == '.') {
            $dirname = '';
        }
        else {
            $dirname .= '/';
        }
        $GLOBALS['__classes'][basename($module_path)] = $dirname;

        if (file_exists(__LOCALE_MODULES_PATH . $module_path . '.php')) {
            require_once(__LOCALE_MODULES_PATH . $module_path . '.php');
            return true;
        }
        else if (file_exists(__ENGINE_MODULES_PATH . $module_path . '.php')) {
            require_once(__ENGINE_MODULES_PATH . $module_path . '.php');
            return true;
        }
        return false;
    }

    /**
    * Поиск и подключение конвертера URLов
    *
    * @param string $url_converter название конвертера, например, url_converter_frontend
    * @return boolean
    */
    public static function load_url_converter($url_converter) {
        if (file_exists(__LOCALE_URL_CONVERTERS_PATH . $url_converter . '.php')) {
            require_once(__LOCALE_URL_CONVERTERS_PATH . $url_converter . '.php');
            return true;
        }
        else if (file_exists(__ENGINE_URL_CONVERTERS_PATH . $url_converter . '.php')) {
            require_once(__ENGINE_URL_CONVERTERS_PATH . $url_converter . '.php');
            return true;
        }
        return false;
    }

    /**
    * Поиск, подключение и создание объекта модуля
    *
    * @param string $module Путь и название модуля, например, fields/field_text
    * @param array $data  Ассоциативный массив параметров, которые будут переданы модулю при создании
    * @param object $holder Объект модуля, который запрашивает создание нового модуля
    * @return object Объект модуля
    */
    public static function create_module($module_path, $data = array(), $holder = false) {
        if (self::load_module($module_path)) {
            $module_name = basename($module_path);
            $data['__holder'] = $holder;
            if (class_exists($module_name)) {
                $url_converters = self::get_config('_url_converters');
                if (is_array($url_converters)) {
                    foreach ($url_converters as $rule) {
                        if (isset($rule['_extract_hint'])) {
                            $hierarchy = array();
                            $parent = $module_name;
                            do {
                                $hierarchy[] = $parent;
                                $parent = get_parent_class($parent);
                            } while ($parent);
                            if (is_array($rule['_extract_hint'])) {
                                $extract_hint = $rule['_extract_hint'];
                            }
                            else if (is_string($rule['_extract_hint'])) {
                                $extract_hint = array($rule['_extract_hint']);
                            }
                            else {
                                self::fatal_error(_DEBUG_CC, 'CC Error. _extract_hint rule could be a string or an array only.');
                            }
                            if (
                                array_intersect($hierarchy, $extract_hint)
                                // (is_array($rule['_extract_hint']) && in_array($module_name, $rule['_extract_hint'])) ||
                                // (is_string($rule['_extract_hint']) && $rule['_extract_hint'] == $module_name)
                            ) {
                                if ($url_converter_obj = self::create_url_converter($rule)) {
                                    $params = $url_converter_obj->_extract($GLOBALS['__params'], isset($rule['_settings']) ? $rule['_settings'] : false);
                                    if (is_array($params)) {
                                        foreach ($params as $name => $value) {
                                            _write_router_param($name, $value);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                self::debug_message(_DEBUG_CC, 'Creating module <b>' . $module_name . '</b>');
                return new $module_name($data);
            }
            else {
                self::fatal_error(_DEBUG_CC, 'CC Error. File <b>' . $module_name . '.php</b> found, but unable to find class <b>' . $module_name . '</b>');
            }
        }
        else {
            self::fatal_error(_DEBUG_CC, 'CC Error. Unable to find module <b>' . $module_path . '</b> (file <b>' . $module_path . '.php</b> not exists)');
        }
    }

    /**
    * Преобразует GET параметры в фрагмент ссылки
    *
    * @param array $params
    * @return string
    */
    public static function params_to_link(&$params) {
        $routers = self::get_config('_url_converters');
        $link = '';
        $removed_params = array();

        if (is_array($routers)) {
            $updated_params = array();
            foreach ($routers as $rule) {
                if (isset($rule['_compact_hint'])) {
                    $param = $rule['_compact_hint'];
                    if (is_array($param)) {
                        if (sizeof($param)) {
                            $passed = true;
                            foreach ($param as $key => $val) {
                                if (is_numeric($key)) {
                                    if (!isset($params[$val])) {
                                        $passed = false;
                                        break;
                                    }
                                }
                                else {
                                    if (!isset($params[$key]) || $params[$key] != $val) {
                                        $passed = false;
                                        break;
                                    }
                                }
                            }
                            /* (изменен алгоритм поиска параметров с ИЛИ на И, т.к.
                                эффект ИЛИ можно достичь указывая несколько правил подряд)

                            $passed = false;
                            foreach ($param as $key => $val) {
                                if (is_numeric($key)) {
                                    if (isset($params[$val])) {
                                        $passed = true;
                                        break;
                                    }
                                }
                                else {
                                    if (isset($params[$key]) && $params[$key] == $val) {
                                        $passed = true;
                                        break;
                                    }
                                }
                            }
                            */
                        }
                        else {
                            $passed = false;
                        }
                    }
                    else {
                        $passed = isset($params[$param]);
                    }
                    if ($passed) {
                        if ($router_obj = self::create_url_converter($rule)) {
                            $tmp_params = $params;
                            // добавлен второй параметр - оригинальные параметры, что бы можно
                            // было влиять на выполнение следующих правил
                            // $link .= $router_obj->_compact($tmp_params, $params);
                            $link .= $router_obj->_compact($tmp_params, isset($rule['_settings']) ? $rule['_settings'] : false);
                            $removed_params = array_merge($removed_params, array_diff(array_keys($params), array_keys($tmp_params)));
                            // сохранить изменения, которые программист внес в параметры
                            foreach ($tmp_params as $key => $val) {
                                if (!isset($params[$key]) || $tmp_params[$key] != $params[$key]) {
                                    $updated_params[$key] = $tmp_params[$key];
                                }
                            }
                        }
                        if (isset($rule['_final']) && $rule['_final']) {
                            break;
                        }
                    }
                }
            }
        }

        foreach ($removed_params as $remove_key) {
            unset($params[$remove_key]);
        }
        foreach ($params as $key => $val) {
            if (isset($updated_params[$key])) {
                $params[$key] = $updated_params[$key];
            }
        }
        return $link;
    }

    /**
    * Создает объект роутера и записывает во внутреннее хранилище
    *
    * @param array $rule описание правила из главного конфига
    * @return _router
    */
    public static function create_url_converter($rule, $holder = false) {
        $converter = $rule['_url_converter'];
        if (!isset($GLOBALS['__cc_globals']['__url_converters'])) {
            $GLOBALS['__cc_globals']['__url_converters'] = array();
        }
        if (!isset($GLOBALS['__cc_globals']['__url_converters'][$converter])) {
            if (self::load_url_converter($converter)) {
                $GLOBALS['__cc_globals']['__url_converters'][$converter] = new $converter(array(
                    '__holder' => $holder
                ));
            }
            else {
                $GLOBALS['__cc_globals']['__url_converters'][$converter] = false;
                self::fatal_error(_DEBUG_CC, 'CC Error. Unable to load url converter <b>' . $converter . '</b>', 'error');
            }
        }
        return $GLOBALS['__cc_globals']['__url_converters'][$converter];
    }

    /**
    * Поиск, подключение, создание и запуск объекта модуля
    *
    * @param string $module_name Название модуля
    * @param array $data Ассоциативный массив параметров, которые будут переданы модулю при создании
    * @param object $holder Объект модуля, который запрашивает создание нового модуля
    * @return mixed Результат работы модуля - как правило объект tpl или string
    */
    public static function execute_module($module_name, $data = array(), $holder = false) {
        return _cc
           ::create_module($module_name, $data, $holder)
           ->_run()
        ;
    }

    /**
    * Создает единственный экземпляр модуля
    */
    public static function single_module($module_name) {
        if (!isset($GLOBALS['__cc_globals']['__modules'])) {
            $GLOBALS['__cc_globals']['__modules'] = array();
        }
        if (!isset($GLOBALS['__cc_globals']['__modules'][$module_name])) {
            $GLOBALS['__cc_globals']['__modules'][$module_name] = self::create_module($module_name);
        }
        return $GLOBALS['__cc_globals']['__modules'][$module_name];
    }

    /**
    * Создает и инициализирует объект tpl
    * @param string $tpl_name Имя шаблона
    * @param array $tpl_vars Масив переменных шаблона
    * @param object $module Модуль, который запросил создание шаблона
    * @return object Объект tpl
    */
    public static function create_tpl_from_file($tpl_name, $tpl_vars = false, $holder = false, $search_above_path = false) {
        $tpl_engine_name = _TPL_ENGINE;
        $tpl = new $tpl_engine_name(array(
            '__holder' => $holder,
            '__name' => $tpl_name,
            '__search_above_path' => $search_above_path,
            '_vars' => $tpl_vars
        ));
        return $tpl;
    }

    /**
    * Создает и инициализирует объект tpl
    * @param string $tpl_content Содержимое шаблона
    * @param array $tpl_vars Масив переменных шаблона
    * @param object $module Модуль, который запросил создание шаблона
    * @return object Объект tpl
    */
    public static function create_tpl_from_string($tpl_content, $tpl_vars = array(), $holder = false) {
        $tpl_engine_name = _TPL_ENGINE;
        $tpl = new $tpl_engine_name(array(
            '__holder' => $holder,
            '__content' => $tpl_content,
            '_vars' => $tpl_vars
       ));
        return $tpl;
    }

    /**
    * может принимать не только ссылку на конфиг но и непосредственно конфигурацию - в последнем
    * случае кеширование объекта БД не произойдет
    */
    public static function create_db_engine($config_key) {
        $sql_engine_name = _DB_ENGINE;
        if (is_array($config_key)) {
            return new $sql_engine_name($config_key);
        }
        else {
            // storage for various database connections
            if (!isset($GLOBALS['__cc_globals']['__db'])) {
                $GLOBALS['__cc_globals']['__db'] = array();
            }
            if (!isset($GLOBALS['__cc_globals']['__db'][$config_key])) {
                $GLOBALS['__cc_globals']['__db'][$config_key] = new $sql_engine_name(self::get_config($config_key));
            }
            return $GLOBALS['__cc_globals']['__db'][$config_key];
        }
    }

    /**
    * Поиск и подключение данных
    *
    * @param string $data_name Название класса
    * @return void
    */
    public static function load_data_source($data_name, $autocreate = true) {
        if (file_exists(__LOCALE_DATA_SOURCES_PATH . $data_name . '.php')) {
            require_once(__LOCALE_DATA_SOURCES_PATH . $data_name . '.php');
            if (class_exists($data_name)) {
                return true;
            }
            else {
                self::fatal_error(_DEBUG_CC, 'File <b>' . __LOCALE_DATA_SOURCES_PATH . $data_name . '.php' . '</b> exists, but class <b>' . $data_name . '</b> not found');
            }
        }
        else if (file_exists(__ENGINE_DATA_SOURCES_PATH . $data_name . '.php')) {
            require_once(__ENGINE_DATA_SOURCES_PATH . $data_name . '.php');
            if (class_exists($data_name)) {
                return true;
            }
            else {
                self::fatal_error(_DEBUG_CC, 'File <b>' . __ENGINE_DATA_SOURCES_PATH . $data_name . '.php' . '</b> exists, but class <b>' . $data_name . '</b> not found');
            }
        }
        else if ($autocreate && __DEFAULT_DATA_SOURCE_CLASS) {
            eval(__DEFAULT_DATA_SOURCE_CLASS . '::__autocreate($data_name);');
            return self::load_data_source($data_name, false);
        }
        return false;
    }

    /**
    * Создает на лету объект data_source
    * @param array $description Массив, описывающий создаваемый объект data_source
    * @param array $initial_data Данные, которыми инициализировать созданый объект
    * @param string $data_source_class Имя класса который нужно создать
    * @return object Объект data
    */
    public static function create_data_from_array($description, $initial_data, $data_source_class) {
        $initial_data = array_merge($description, $initial_data);
        $data = new $data_source_class($initial_data);
        return $data;
    }

    /**
    * Создает объект data из класса
    * @param array $name Массив, описывающий создаваемый объект data
    * @param object $holder Модуль, который запросил создание объекта
    * @param array $initial_data Данные, которыми инициализировать созданый объект
    * @return object Объект data
    */
    public static function create_data_from_class($name, $initial_data, $data_source_class) {
        if (self::load_data_source($name)) {
            $data = new $name($initial_data);
            return $data;
        }
        else {
            return self::create_data_from_array(array(
                '_table' => $name
            ), $initial_data, $data_source_class);
        }
    }

    /**
     * Получить имя класса роутера (менеджера)
     * @static
     * @param $rout_rule
     * @return mixed
     */
    public static function router($rout_rule) {
        return self::get_config('_routers', $rout_rule);
    }

    /**
     * Получить синглтон роутера (менеджера)
     * @static
     * @param $rout_rule
     * @return mixed
     */
    public static function manager($rout_rule) {
        return self::single_module(self::router($rout_rule));
    }

    /**
    * для внутреннего использования (?)
    * Возвращает путь для заданного rout_rule, по умолчанию возвращает путь для текущего rout_rule
    * @param string $rout_rule Название rout_rule (например, admin, lite или frontend)
    * @return string path
    */
    protected static function get_rout_rule_path($rout_rule = false) {
        if (!$rout_rule) {
            return __ROUT_URL;
        } else {
            $man_fn = self::router($rout_rule);

            if (
                self::is_release() ||
                file_exists(__LOCALE_MODULES_PATH . $man_fn . '.php') ||
                file_exists(__ENGINE_MODULES_PATH . $man_fn . '.php')
            ) {
                $routers = self::get_config('_routers');
                if ($rout_rule == '_default' || reset($routers) == $man_fn) {
                    return '';
                }
                else {
                    return _fix_path($rout_rule);
                }
            } else {
                self::fatal_error(_DEBUG_CC, 'CC Error. Incorrect rout rule <b>' . $rout_rule . '</b> (file <b>' . $man_fn . '.php</b> not exists)');
            }
        }
    }

    /**
    * Возвращает базовый URL для заданного rout_rule, по умолчанию возвращает URL для текущего rout_rule
    * @param string $rout_rule Название rout rule (например, admin, lite или frontend)
    * @return string URL
    */
    public static function get_baseurl($rout_rule = false) {
        return _BASEURL . self::get_rout_rule_path($rout_rule);
    }

    /**
    * Возвращает базовый HTTP URL для заданного rout_rule, по умолчанию возвращает URL для текущего rout_rule
    * @param string $rout_rule Название rout rule (например, admin, lite или frontend)
    * @return string URL
    */
    public static function get_http_baseurl($rout_rule = false) {
        return _get_baseurl('http') . self::get_rout_rule_path($rout_rule);
    }

    /**
    * Возвращает базовый HTTPS URL для заданного rout_rule, по умолчанию возвращает URL для текущего rout_rule
    * @param string $rout_rule Название rout rule (например, admin, lite или frontend)
    * @return string URL
    */
    public static function get_https_baseurl($rout_rule = false) {
        return _get_baseurl('https') . self::get_rout_rule_path($rout_rule);
    }

    /**
    * Прерывает исполнение скрипта и выводит в дебаг окно соответсвующее сообщение
    * @param string $message Сообщение об ошибке
    */
    public static function fatal_error($debug_level, $message = '') {
        if ($message && _DEBUG_LEVEL & $debug_level) {
            self::debug_message($debug_level, $message, 'error');
        }
        self::debug_show();
        die();
    }

    /**
    * Выводит в отладочное окно или файл соответсвующее сообщение
    *
    * @param int $debug_level уровень отладки, при котором данное сообщение будет выводиться
    * @param string $message Сообщение
    * @param string $type Тип сообщения, например, 'message' (по-умолчанию), 'error'
    *
    * альтернативные параметры:
    *
    * @param string $message Сообщение
    */
    public static function debug_message($debug_level, $message = '', $type = '') {
        if (!is_numeric($debug_level)) { // first param is message
            $message = $debug_level;
            $type = $message;
            $show_message = true;
        }
        else {
            $show_message = _DEBUG_LEVEL & $debug_level;
        }
        if ($message && $show_message) {
            if ($type == 'error') {
                $line = array(
                    'type' => $type,
                    'message' => $message,
                    'backtrace' => debug_backtrace()
                );
            }
            else {
                $line = array(
                    'type' => $type,
                    'message' => $message
                );
            }
            $GLOBALS['__debug'][] = $line;
        }
    }

    /**
    * Возвращает название системы и ее версию
    *
    */
    public static function get_core_name() {
        return $GLOBALS['_config']['__name'] . ' ' . $GLOBALS['_config']['__version'];
    }

    /**
    * Используется внутри, выводит дебаг окно на экран
    */
    public static function debug_show() {
        if (_DEBUG_LEVEL) {
            _ob_start();
            // design and behaviour
            echo '<link href="' . _COREURL . 'css/debug.css" rel="stylesheet" type="text/css" />';
            echo '<script src="' . _COREURL . 'js/debug.js"></script>';

            $params = $_GET;
            $params['__reset_cache'] = 'on';

            // turn on debug window button
            echo '<a href="' . _append_params(_get_url(), $params) . '" id="DebugWindowContainerSmall" title="Reset templates cache">X</a><a href="javascript:void(0)" onclick="showDebugWindow()" id="DebugShowBtn" title="Maximize Debug Window">&uarr;</a>';

            // container start
            echo '<div id="DebugWindowContainer">';

            // header
            echo '<div id="DebugWindowHeader">';
            echo '<a href="' . _append_params(_get_url(), $params) . '" id="DebugResetCacheBtn" title="Reset templates cache">X</a>';
            echo '<a href="javascript:void(0)" onclick="hideDebugWindow()" id="DebugHideBtn" title="Minimize Debug Window">&darr;</a>';
            echo self::get_core_name() . ': Debug Window';
            echo '</div>';

            echo '<div id="DebugWindowMessages">';

            $show_window = false;
            for ($i = 0; $i < sizeof($GLOBALS['__debug']); $i++) {
                if ($GLOBALS['__debug'][$i]['type'] == 'error') {
                    $show_window = true;

                    $dbt = $GLOBALS['__debug'][$i]['backtrace'];
                    array_shift($dbt);
                    array_shift($dbt);
                    $dbt_str = '';
                    foreach ($dbt as $dbt_line) {
                        if (isset($dbt_line['file']) && isset($dbt_line['line'])) {
                            $dbt_str .= '<br>File: ' . $dbt_line['file'] . '; line: ' . $dbt_line['line'];
                        }
                        else {
                            $dbt_str .= '<br>File: undefined; line: undefined (call_user_func_array used?)';
                        }
                    }
                    echo '<p class="debugError">' . ($GLOBALS['__debug'][$i]['message'] . $dbt_str) . '</p>';
                }
                else {
                    echo '<p class="debugMessage">' . ($GLOBALS['__debug'][$i]['message']) . '</p>';
                }
            }
            echo '</div>';

            // container end
            echo '</div>';
            if ($show_window) {
                echo '<script>showDebugWindow()</script>';
            }
            else {
                echo '<script>hideDebugWindow()</script>';
            }

            $debug_html = _ob_get_clean();
            if (defined('_DEBUG_OUTPUT_FILE')) {
                $i = 0;
                do {
                    $fn = _DEBUG_OUTPUT_FILE . '-' . time() . '-' . _leading_zero($i, 3) . '.html';
                    $i++;
                } while (file_exists($fn));
                file_put_contents($fn, $debug_html);
            }
            else if (!_is_ajax_request()) {
                echo $debug_html;
            }
        }
    }

    /**
    * Возвращает глобальную конфигурацию по одному или двум ключам
    * @param string $key1 Первый ключ
    * @param string $key2 Второй ключ
    * @return mixed Значение конфигурации
    */
    public static function get_config($key1, $key2 = false) {
        if ($key1 && $key2) {
            if (isset($GLOBALS['_config'][$key1]) && isset($GLOBALS['_config'][$key1][$key2])) {
                return $GLOBALS['_config'][$key1][$key2];
            }
            else {
                return false;
            }
        }
        else if ($key1) {
            if (isset($GLOBALS['_config'][$key1])) {
                return $GLOBALS['_config'][$key1];
            }
            else {
                return false;
            }
        }
        else {
            return false;
        }
    }

    /**
    * Добавляет в глобальную конфигурацию значение по 2-ум ключам
    * @param string $key1 Первый ключ
    * @param string $key2 Второй ключ
    * @param mixed $value Значение конфигурации
    */
    public static function set_config() {
        $args = func_get_args();
        if (sizeof($args) == 3) {
            $GLOBALS['_config'][$args[0]][$args[1]] = $args[2];
        }
        else if (sizeof($args) == 2) {
            $GLOBALS['_config'][$args[0]] = $args[1];
        }
        else {
            self::fatal_error(_DEBUG_CC, '_cc::set_config() expects 2 or 3 parameters.');
        }
    }

    /**
    * Путь к временной папке
    */
    public static function get_tmp_path() {
        return __TMP_PATH;
    }

    /**
    * Имя временной папки
    */
    public static function get_tmp_dir() {
        return __TMP_DIR;
    }

    /**
    * возвращает true если проект запущен в режиме релиза
    * следует использовать в своих модулях что бы в режиме релиза не выполнять необязательные
    * ресурсоемкие операции, которые имеет смысл выполнять только на этапе разработки (например,
    * различные проверки)
    */
    public static function is_release() {
        return self::get_config('_project', '_release');
    }

    public static function link($params, $rout_rule = false, $params_separator = false, $split_url_params = false) {
        return self::internal_link($params, $rout_rule, $params_separator, $split_url_params, 'get_http_baseurl');
    }

    public static function ssl_link($params, $rout_rule = false, $params_separator = false, $split_url_params = false) {
        return self::internal_link($params, $rout_rule, $params_separator, $split_url_params, 'get_https_baseurl');
    }

    protected static function internal_link($params, $rout_rule = false, $params_separator = false, $split_url_params = false, $baseurl_method = false) {
        if (!$params_separator) {
            $params_separator = '&';
        }

        $url = self::$baseurl_method($rout_rule);

        foreach ($params as $key => $value) {
            if ($value === false) {
                unset($params[$key]);
            }
        }
        $url .= self::params_to_link($params);

        if ($split_url_params) {
            return array($url, $params);
        }
        else {
            $url = _append_params($url, $params, $params_separator);
            return $url;
        }
    }
}



