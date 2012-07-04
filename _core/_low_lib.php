<?php
/**
 * @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
 * @package CleverCore2
 *
 * Number of useful function.
 *
 * All function are enclosed with statement
 * <code>
 *   if (!function_exists('...')) {
 *       function ...() {
 *           // ...
 *       }
 *   }
 * </code>
 * this will allow to replace it in [PROJECT ROOT]/_core/_low_lib.php file
 */

if (!function_exists('_append_params')) {
    /**
    * добавляет к ссылке GET параметры
    * TODO: обработка значений FALSE
    * 
    * @param mixed $link
    * @param mixed $params
    * @param mixed $params_separator
    */
    function _append_params($link, $params, $params_separator = false) {
        if (!$params_separator) {
            $params_separator = '&';
        }

        $url = $link;
        $params_str = '';
        foreach ($params as $key => $value) {
            $params_str = __append_single_param($params_str, $key, $value, $params_separator);
        }
        if ($params_str) {
            // prepare $url
            if (strpos($url, '?') !== false) {
                if ($url[strlen($url)-1] != '?') {
                    $url .= $params_separator;
                }
            } else{
                $url .= '?';
            }
            $url .= $params_str;
        }
        return $url;
    }

    function __append_single_param($params_str, $key, $value, $params_separator = false) {
        if (is_array($value)) {
            foreach ($value as $value_key => $value_single) {
                $params_str = __append_single_param($params_str, $key . '[' . (is_int($value_key) ? '' : $value_key) . ']', $value_single, $params_separator);
            }
        }
        else {
            if ($params_str) {
                $params_str .= $params_separator;
            }
            $params_str .= urlencode($key) . '=' . urlencode($value);
        }
        return $params_str;
    }
}

if (!function_exists('_htmlspecialchars')) {
    /**
    * Рекурсивный htmlspecialchars - если передан массив пройдется по всем его элементам
    * 
    * @param mixed $var
    * @return mixed результат замены специальных HTML символов
    */
    function _htmlspecialchars($var) {
        if (is_array($var)) {
            foreach ($var as &$element) {
                $element = _htmlspecialchars($element);
            }
            return $var;
        } else {
            return htmlspecialchars($var);
        }
    }
}

if (!function_exists('_redirect')) {
    /**
    * Redirect to location
    */
    function _redirect($link) {
        $errors = false;
        // if there was any errors - no redirection to perform, we should output errors to developer
        if (_DEBUG_LEVEL) {
            foreach ($GLOBALS['__debug'] as $d) {
                if ($d['type'] == 'error') {
                    $errors = true;
                    break;
                }
            }
        }

        if (!$errors) {
            if (_is_ajax_request()) {
                // trick for FireFox
                $link = _append_params($link, array('__is_ajax_request' => 'on'));
            }
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Last-Modified: " . gmdate( "D, d M Y H:i:s") . " GMT");
            header("Cache-Control: no-cache, must-revalidate");
            header("Pragma: no-cache");
            header("HTTP/1.0 301 Moved Permanently");
            header('Location: ' . $link);

            // check for errors once more:
            // header() functions above may couse warnings in case output already started
            if (_DEBUG_LEVEL) {
                foreach ($GLOBALS['__debug'] as $d) {
                    if ($d['type'] == 'error') {
                        $errors = true;
                        break;
                    }
                }
            }
        }

        if ($errors) {
            _cc::debug_message(_DEBUG_CC, 'Message. Was going to redirect to: ' . $link, 'error');
            _exit();
        }
        else {
            exit;
        }
    }
}

if (!function_exists('_ufix_path')) {
    /**
    * Убирает из конеца пути слеш (/) если он присутсвует
    * @param string $path
    * @return string Путь без слеша (/) в конце
    */
    function _ufix_path($path) {
        $path = str_replace('\\', '/', $path);
        if ($path) {
            if ($path[strlen($path) - 1] == '/') {
                $path = substr($path, 0, strlen($path) - 1);
            }
        }
        return $path;
    }
}

if (!function_exists('_get_microtime')) {
    /**
    * Функция из мануала по PHP
    * 
    * @return string Текущее время в секундах начиная с 1970 года + милисекунды
    */
    function _get_microtime() {
        list($usec, $sec) = explode(' ', microtime());
        return ((float) $usec + (float) $sec);
    }
}

if (!function_exists('_start_timer')) {
    function _start_timer($name) {
        $GLOBALS['__timers'][$name] = _get_microtime();
    }
}

if (!function_exists('_get_timer')) {
    function _get_timer($name) {
        if (isset($GLOBALS['__timers'][$name])) {
            return round((_get_microtime() - $GLOBALS['__timers'][$name]) * 10000) / 10000;
        }
        else {
            return 'Timer "' . $name . '" is not started';
        }
    }
}

if (!function_exists('_get_url')) {
    /**
    * @param string $protocol http, https или false; если false, то будет использован протокол текущего запроса
    * @return string Полный URL без имени файла и параметров
    */
    function _get_url($protocol = false) {
        if (!$protocol) {
            $protocol = _is_ssl_request() ? 'https' : 'http';
        }
        $uri = explode('?', _env('REQUEST_URI'));
        return _fix_path($protocol . '://' . _env('SERVER_NAME') . $uri[0]);
    }
}

if (!function_exists('_get_baseurl')) {
    /**
    * @param string $protocol http, https или false; если false, то будет использован протокол текущего запроса
    * @return string Полный URL без имени файла и параметров
    */
    function _get_baseurl($protocol = false) {
        if (PHP_SAPI === 'cgi' && _env('SCRIPT_URL')) {
            $SCRIPT_NAME = 'SCRIPT_URL';
        } else {
            $SCRIPT_NAME = 'SCRIPT_NAME';
        }
        if (!$protocol) {
            $protocol = _is_ssl_request() ? 'https' : 'http';
        }
        return _fix_path($protocol . '://' . _env('SERVER_NAME') . dirname(_env($SCRIPT_NAME)));
    }
}

if (!function_exists('_get_basepath')) {
    /**
    * @return string URL проекта без имени файла, пути к нему и параметров
    */
    function _get_basepath() {
        if (PHP_SAPI === 'cgi' && _env('SCRIPT_URL')) {
            $SCRIPT_NAME = 'SCRIPT_URL';
        } else {
            $SCRIPT_NAME = 'SCRIPT_NAME';
        }
        return _fix_path(dirname(_env($SCRIPT_NAME)));
    }
}

if (!function_exists('_is_ssl_request')) {
    /**
    * Возвращает true если вызов был ssl
    * @return boolean
    */
    function _is_ssl_request() {
        if (_env('HTTPS') || strpos(_env('SCRIPT_URI'), 'https://') === 0) {
            return true;
        } else {
            return false;
        }
    }
}
if (!function_exists('_is_ajax_request')) {
    /**
    * Возвращает true если вызов был совершен с помощью jquery.ajax
    * @return boolean
    */
    function _is_ajax_request() {
        return _env('HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest'
            // trick for FireFox
            || _read_get_param('__is_ajax_request');
    }
}
if (!function_exists('_overwhelm_response')) {
    function _overwhelm_response($response, $content_type = false, $suppress_debug = false) {
        _ob_end_clean_all();
        if ($content_type) {
            switch ($content_type) {
                case 'json':
                    $content_type = 'application/json';
                    break;
            }
            header('Content-type: ' . $content_type);
        }
        echo $response;
        _exit($suppress_debug);
    }
}

function _ob_start() {
    $GLOBALS['__ob_counter']++;
    ob_start();
}

function _ob_get_clean() {
    $GLOBALS['__ob_counter']--;
    return ob_get_clean();
}

function _ob_get_contents() {
    return ob_get_contents();
}

function _ob_get_flush() {
    $GLOBALS['__ob_counter']--;
    return ob_get_flush();
}

function _ob_end_clean() {
    $GLOBALS['__ob_counter']--;
    return ob_end_clean();
}

function _ob_end_clean_all() {
    while ($GLOBALS['__ob_counter']) {
        _ob_end_clean();
    }
}

if (!function_exists('_env')) {
    /**
    * Ищет переменную окружения среди возможных источников
    * @param string $key Имя переменной
    * @return string Значение переменной или false если ничего не было найдено
    */
    function _env($key) {
        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        } elseif (isset($_ENV[$key])) {
            return $_ENV[$key];
        } elseif (getenv($key) !== false) {
            return getenv($key);
        } else {
            return false;
        }
    }
}

if (!function_exists('_unique_filename')) {
    /**
    * Обеспечивает уникальность имени файла для заданной папки
    *
    * Проверяет, существует ли файл з заданым именем в указанной папке, и если существует,
    * то подбирает новое имя, которое не встречается в указанной папке
    * Кроме того, функция убирает из имени нежелательные символы - все кроме латинских символов,
    * точек, символов подчеркивания, тире и цифр
    *
    * @param string $path Путь, по которому проверять файл
    * @param string $name Имя файла
    * @return string Если файла не было то возвращает имя без изменений, если такой файл уже существует то новое имя
    */
    function _unique_filename($path, $name) {
        $name = preg_replace('#[^a-zA-Z\.0-9_\-]#','_', $name);
        if (!file_exists($path . $name)) {
            return $name;
        } else {
            if (!preg_match('#^(.*)\.(.+)$#', $name, $file)) {
                $file = array($name, $name);
            }

            $file_name = $file[1];
            $file_extension = isset($file[2]) ? $file[2] : '';
            $i = 0;
            while (file_exists($path . $file_name . $i . '.' . $file_extension)) {
                $i++;
            }
            return $file_name . $i . ($file_extension ? '.' . $file_extension : '');
        }
    }
}

if (!function_exists('_mkdir')) {
    /**
    * Создает цепочку категорий
    * 
    * @param mixed $path
    * @param mixed $r
    */
    function _mkdir($path, $r = 0777) {
        if (!file_exists($path)) {
            $check_exists = true;
        }
        else {
            $check_exists = false;
        }
        $path = str_replace('\\', '/', $path);
        $dirs = explode('/',$path);
        $c_path = '';
        for ($i = 0; $i < sizeof($dirs); $i++) {
            $c_path .= $dirs[$i] . '/';
            if (strstr(_LOCALE_PATH, $c_path) === false) {
                if ($check_exists && !file_exists($c_path)) {
                    @mkdir($c_path, $r);
                }
            }
        }
    }
}

if (!function_exists('_rmdir')) {
    /**
    * Рекурсивное удаление всё её содержимое, включая папки и папку $path
    * 
    * @param mixed $path
    */
    function _rmdir($path, $level = 0) {
        if (is_dir($path)) {
            $dh = opendir($path);
            while (($file = readdir($dh)) !== false) {
                if ($file != '.' && $file != '..') {
                    if (is_dir($path . $file)) {
                        _rmdir(_fix_path($path . $file), $level + 1);
                        @rmdir($path . $file);
                    }
                    else {
                        @unlink($path . $file);
                    }
                }
            }
            closedir($dh);
            if (!$level) {
                @rmdir($path);
            }
        }
        else{
            return false;
        }
    }
}

if (!function_exists('_copy')) {
    /**
    * Копирует файл, при этом создавая категорию если ее не существует
    * 
    * @param mixed $source
    * @param mixed $destination
    */
    function _copy($source, $destination) {
        _mkdir(dirname($destination));
        return copy($source, $destination);
    }
}

if (!function_exists('_ve')) {
    function _ve($var, $deep = 20) {
        _v($var, $deep, 0, 1);
        _exit();
    }
}

if (!function_exists('_v')) {
    function _v($var, $deep = 20, $current = 0, $trace_index = 0) {
        $trace_data = debug_backtrace();

        $tab_single = ':&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $spacer = '<br><br>';
        $tab = '';
        for ($i = 0; $i < $current; $i++) {
            $tab .= $tab_single;
        }
        if ($current) {
            $s = '';
        }
        else {
            $s = '<div class="debugVarContainer"><b>File: ' . str_replace(str_replace('\\', '/', _LOCALE_PATH), '', str_replace('\\', '/', $trace_data[$trace_index]['file'])) . '<br>Line: ' . $trace_data[$trace_index]['line'] . '</b><br><br>';
        }
        if ($deep >= $current) {
            if (is_object($var)) {
                $s .= 'object (' . $spacer;
                foreach ($var as $key => $val) {
                    $s .= $tab . $tab_single . '[ ' . $key . ' ] =&gt; ' . _v($val, $deep, $current + 1);
                }
                $s .= $tab . ')';
            }
            else if (is_array($var)) {
                $s .= 'array (' . $spacer;
                foreach ($var as $key => $val) {
                    $s .= $tab . $tab_single . '[ ' . $key . ' ] =&gt; ' . _v($val, $deep, $current + 1);
                }
                $s .= $tab . ')';
            }
            else {
                $s .= '<b>';
                if (is_string($var)) {
                    $s .= "'" . _htmlspecialchars($var) . "'";
                }
                else if (is_bool($var)) {
                    $s .= ($var ? 'TRUE' : 'FALSE');
                }
                else {
                    $s .= $var;
                }
                $s .= '</b>';
            }
        }
        else {
            $s .= '<i>...too deeply...</i>';
        }
        $s .= $spacer;
        if ($current) {
            return $s;
        }
        else {
            $s .= '</div>';
            echo $s;
        }
    }
}

//if (!function_exists('_evde')) {
    /**
    * вывод дампа переменной на экран и прекращение работы
    * расшифровка аббревиатуры EchoVarDumpExit
    * 
    * @param mixed $var
    */
    /*
    function _evde($var) {
        _evd($var);
        _exit();
    }
    */
//}

if (!function_exists('_exit')) {
    function _exit($suppress_debug = false) {
        if (!$suppress_debug) {
            if (_DEBUG_LEVEL & _DEBUG_MEMORY) {
                _cc::debug_message(_DEBUG_MEMORY, 'Current memory usage is <b>' . _adjust_number(memory_get_usage(true)) . '</b>, peak memory usage is <b>' . _adjust_number(memory_get_peak_usage(true)) . '</b>');
            }
            if (_DEBUG_LEVEL & _DEBUG_SPEED) {
                _cc::debug_message(_DEBUG_SPEED, 'Page generated in <b>' . _get_timer('_root') . '</b> seconds. Please note, some kind of debugging may increase execution time dramatically.');
            }
            if (_DEBUG_LEVEL) {
                _cc::debug_show();
            }
        }
        exit;
    }
}

// if (!function_exists('_evd')) {
    /**
    * вывод дампа переменной на экран
    * расшифровка аббревиатуры EchoVarDump
    * 
    * @param mixed $var
    */
    /*
    function _evd($var) {
        echo _vd($var);
    }
    */
// }

// if (!function_exists('_vd')) {
    /**
    * вывод дампа переменной в строку
    * расшифровка аббревиатуры VarDump
    * 
    * @param mixed $var
    */
    /*
    function _vd($var) {
        $s = '<pre>';
        _ob_start();
        print_r($var);
        $s .= _ob_get_clean();
        $s .= '</pre>';
        return $s;
    }
    */
// }

if (!function_exists('_simplest_filter')) {
    /**
    * простейший фильтр и форматирование данных перед выводом в браузер (HTML)
    * экранирует специальные HTML-символы и заменяет символ перехода на новую строку на <br>
    * 
    * @param string $in
    * @return string
    */
    function _simplest_filter($in) {
        return trim(nl2br(htmlspecialchars($in)));
    }
}

if (!function_exists('_adjust_links')) {
    /**
    * Находит в тексте линки и заменяет их на HTML эквивалент
    * 
    * @param string $in
    * @return string
    */
    function _adjust_links($in) {

        preg_match_all('#(\s|^)(https?://)([a-zA-Z0-9\.\/\-\_\?\&\%\=]*)(\s|<|$)#is', $in, $found);
        for ($i = 0; $i < sizeof($found[0]); $i++) {
            $in = str_replace($found[0][$i], $found[1][$i] . '<a href="' . $found[2][$i] . $found[3][$i] . '" target="_blank">' . $found[3][$i] . '</a>' . $found[4][$i], $in);
        }

        preg_match_all('#(\s|^)(www\.[a-zA-Z0-9\.\/\-\_\?\&\%\=]*)(\s|<|$)#is', $in, $found);
        for ($i = 0; $i < sizeof($found[0]); $i++) {
            $in = str_replace($found[0][$i], $found[1][$i] . '<a href="http://' . $found[2][$i] . '" target="_blank">' . $found[2][$i] . '</a>' . $found[3][$i], $in);
        }

        return $in;
    }
}

if (!function_exists('_validate_email')) {
    /**
    * Проверяет е-мейл на корректность
    * 
    * @param mixed $email
    * @return boolean
    */
    function _validate_email($email) {
        if (preg_match("/^[a-zA-Z0-9\._\-]+@([a-zA-Z0-9_\-]+\.)+[a-zA-Z]{2,4}$/",$email)) {
            return true;
        } else {
            return false;
        }
    }
}

if (!function_exists('_validate_url')) {
    /**
    * Проверяет URL на корректность
    * 
    * @param mixed $email
    * @return boolean
    */
    function _validate_url($in) {
        if (preg_match('#^https?://[a-zA-Z0-9_\-]+(\.[a-zA-Z0-9_\-]+)+#',$in)) {
            return 1;
        }
        else {
            return 0;
        }
    }
}

if (!function_exists('_validate_password')) {
    /**
    * Проверяет пароль на корректность (не меньше 6 символов, без пробелов и кирилицы)
    * 
    * @param mixed $password
    * @return boolean
    */
    function _validate_password($password) {
        if (preg_match('#^[a-zA-Z0-9\-_\-\!\@\#\$\%\^\&\*\(\)\+\=\\\;\'\"\|\/\.\,\~\`\<\>\?\{\}\[\]\:]{6,}$#', $password)) {
            return true;
        }
        else {
            return false;
        }
    }
}

if (!function_exists('_validate_empty')) {
    /**
    * Проверяет данные на существование
    * 
    * @param mixed $data
    * @return boolean
    */
    function _validate_empty($data) {
        if (trim($data)) {
            return true;
        } else {
            return false;
        }
    }
}

if (!function_exists('_adjust_field_description')) {
    function _adjust_field_description($key, $field) {
        // поле в любом случае должно содержать ключ 'name'
        if (!is_array($field)) {
            if ($field) {
                $field = array(
                    'name' => $field
                );
            }
            else {
                $field = array(
                    'name' => $key
                );
            }
        }
        if (!isset($field['name'])) {
            if (is_numeric($key)) {
                // _cc::fatal_error(_DEBUG_CC, 'Field\'s name is undefined');
                $field['name'] = false;
            }
            else {
                $field['name'] = $key;
            }
        }
// restored in modFormBasic
//        if (!isset($field['title'])) {
//            $field['title'] = $field['name'];
//        }
        /*
        if (!isset($field['type'])) {
            $field['type'] = 'text';
        }
        */
        return $field;
    }
}
if (!function_exists('_organize_fields')) {
    /**
    * Вернет только те поля из первого массива, на которые есть ссылки (имена) во втором массиве 
    * при этом второй массив может содержать описание произвольных дополнительных полей
    * 
    * @param array $source исходных массив полей
    * @param array $destination массив, по ключам и элементам которого будет пересобран исходный массив
    * @param array $exclude массив, по значениям которого будет проигнорированы элементы из массива $destination
    * @param array $values
    * @param boolean $intersection если false, то поля, описанные в $destination но отсутсвующие в $source будут в результате, а если true - то будут убраны из результата
    * @return array пересобраный массив
    */
    function _organize_fields($source, $destination, $exclude = false, $values = false, $intersection = false) {
        if (!$exclude) {
            $exclude = array();
        }
        if (!$source) {
            $source = array();
        }
        $fields_names = array();
        foreach ($source as $key => &$field) {
            $field = _adjust_field_description($key, $field);

            // Mikhail`s fixes are in this place
            //$fields_names[$field['name']] = $key;
            if (is_string($key)) {
                $fields_names[$key] = $key;
            }
            else {
                $fields_names[$field['name']] = $key;
            }
        }
        unset($field);

        $ret = array();

        foreach ($destination as $key => &$field) {
            if (is_string($field)) {
                if (!in_array($field, $exclude)) {
                    if (in_array($field, $fields_names)) {
                        $ret[$field] = $source[$fields_names[$field]];
                    }
                    else if (!$intersection) {
                        $ret[$field] = _adjust_field_description($key, $field);
                    }
                }
            }
            else {
                $field = _adjust_field_description($key, $field);
                if (!in_array($field['name'], $exclude)) {
                    if (in_array($field['name'], $fields_names)) {
                        $ret[$field['name']] = array_merge(
                            $source[$field['name']],
                            $field
                        );
                    }
                    else if (!$intersection) {
                        $ret[$field['name']] = $field;
                    }
                }
            }
        }
        unset($field);

        if ($values) {
            $ret = _set_fields_values($ret, $values);
        }
        return $ret;
    }
}

if (!function_exists('_set_fields_values')) {
    /**
    * перенесет в поля значения из ассоциативного массива со значениями
    * 
    * @param mixed $fields - массив полей
    * @param mixed $values - ассоциативный массив значений
    */
    function _set_fields_values($fields, $values) {
        if ($values) {
            foreach ($fields as $key => &$field) {
                $field = _adjust_field_description($key, $field);
                // if (isset($field['name'])) {
                    /*
                    if (is_array($field['name'])) {
                        $field['value'] = array();
                        foreach ($field['name'] as $key2 => $name_single) {
                            if (preg_match('#^(\w+[\w\d]*)(\[.*)$#', $name_single, $found)) {
                                $tmp_name = $found[1];
                                $arr_key = str_replace('[', "['", str_replace(']', "']", $found[2]));
                                eval('if (isset($values[\'' . $tmp_name . '\']' . $arr_key . ')) { $field[\'value\'][$key2]  = $values[\'' . $tmp_name . '\']' . $arr_key . ';}');
                            }
                            else if (isset($values[$name_single])) {
                                $field['value'][$key2] = $values[$name_single];
                            }
                        }
                    }
                    else {
                    */
                        if (preg_match('#^(\w+[\w\d]*)(\[.*)$#', $field['name'], $found)) {
                            $tmp_name = $found[1];
                            $arr_key = str_replace('[', "['", str_replace(']', "']", $found[2]));
                            eval('if (isset($values[\'' . $tmp_name . '\']' . $arr_key . ')) { $field[\'value\']  = $values[\'' . $tmp_name . '\']' . $arr_key . ';}');
                        }
                        else if (isset($values[$field['name']])) {
                            $field['value'] = $values[$field['name']];
                        }
                    /*
                    }
                    */
                // }
            }
        }
        return $fields;
    }
}

if (!function_exists('_unique_string')) {
    function _unique_string($length = 32, $stable=array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','1','2','3','4','5','6','7','8','9','0')) {
        $sid = '';
        for ($i = 0; $i < $length; $i++) {
            $sid .= $stable[rand(0, sizeof($stable) - 1)];
        }
        return $sid;
    }
}

if (!function_exists('_get_image_width')) {
    /**
    * Находит возвращает ширину картинки
    * 
    * @param string $src
    * @return int
    */
    function _get_image_width($src) {
        $is = @getimagesize($src);
        if ($is) {
            return $is[0];
        }
        else {
            return false;
        }
    }
}

if (!function_exists('_get_image_height')) {
    /**
    * Находит возвращает ширину картинки
    * 
    * @param string $src
    * @return int
    */
    function _get_image_height($src) {
        $is = @getimagesize($src);
        if ($is) {
            return $is[1];
        }
        else {
            return false;
        }
    }
}

if (!function_exists('_resample_image')) {
    /**
    * Изменяет размер картинки, сохраняя пропорции
    * 
    * @param string $source имя исходной картинки и путь к ней
    * @param int $w желаемая ширина
    * @param int $h желаемая высота
    * @param string $destination имя результирующей картинки и путь к ней
    * @param array $bg массив (R, G, B) описывающий фон результирующей картинки (если пропорции исходной картинки отличаются от пропорций результирующей
    * @return boolean true если успешно, false если не успешно
    */
    function _resample_image($source, $w, $h, $destination = false, $bg = false) {
        if (!is_array($bg)) {
            $bg = array(255, 255, 255);
        }
        $is = @GetImageSize($source);
//_ve($is);
        // if not image or not JPG just copy
        if (
            ($is && $is[0] == $w && $is[1] == $h) ||
            !$is ||
            ($is[2] != 2 && $is[2] != 1)
        ) {
            if ($destination) {
                @copy($source, $destination);
                $ret = true;
            } 
            else {
                $ret = file_get_contents($source);
            }

            return $ret;
        }
        if ($is[2] == 2) { // JPG
            $output_function = 'ImageJPEG';
            $sip = @ImageCreateFromJPEG($source);
        } 
        else if ($is[2] == 1) { // GIF
            $output_function = 'ImageGIF';
            $sip = @ImageCreateFromGIF($source);
        }
        else if ($is[2] == 3) { // PNG
            $output_function = 'ImagePNG';
            $sip = @ImageCreateFromPNG($source);
        }
        if ($is && $sip) {
            $k = $w/$h;
            $sk = $is[0] / $is[1];
            if ($k < $sk) {
                $sw = $w;
                $sh = $sw / $sk;
            } 
            else if ($k > $sk) {
                $sh = $h;
                $sw = $sh * $sk;
            } 
            else {
                $sw = $w;
                $sh = $h;
            }
            $dip = ImageCreateTrueColor($w, $h);
            list($r, $g, $b) = $bg;
            $bgcolor = ImageColorAllocate($dip, $bg[0], $bg[1], $bg[2]);
            ImageFill($dip, 0, 0, $bgcolor);

            if (ImageCopyResampled($dip, $sip, ($w - $sw) / 2, ($h - $sh) / 2, 0, 0, $sw, $sh, $is[0], $is[1])) {
                ImageDestroy($sip);
                if ($destination) {
                    $output_function($dip, $destination, 80);
                    $ret = true;
                } 
                else {
                    _ob_start();
                    $output_function($dip, false, 80);
                    $ret = ob_get_contents();
                    _ob_end_clean();
                }
                ImageDestroy($dip);
            }
            return $ret;
        } else {
            return false;
        }
    }
}

if (!function_exists('_resample_image_by_max')) {
    /**
    * Изменяет размер картинки, при этом делая ее не больше чем указанная ширина или высота. 
    * Не имеет смысл указывать одновременно и высоту и ширину, что бы вписать картинку в желаемый прямоугольник 
    * нужно использовать функцию _resample_image_fit.
    * 
    * @param string $source имя исходной картинки и путь к ней
    * @param int $w максимальная ширина результирующей картинки; при этом высота может быть какая угодно
    * @param int $h максимальная высота результирующей картинки; при этом ширина может быть какая угодно
    * @param string $destination имя результирующей картинки и путь к ней
    * @return boolean true если успешно, false если не успешно
    */
    function _resample_image_by_max($source, $w = false, $h = false, $destination = false) {
        $is = @GetImageSize($source);
        // if not image or not JPG just copy
        if (
            ($is && $is[0] == $w && $is[1] == $h)||
            !$is ||
            ($is[2] != 2 && $is[2] != 1)
        ) {
            if ($destination) {
                @copy($source, $destination);
                $ret = true;
            } 
            else {
                $ret = file_get_contents($source);
            }
            return $ret;
        }
        if ($is[2] == 2) { // JPG
            $output_function = 'ImageJPEG';
            $sip = @ImageCreateFromJPEG($source);
        } 
        else if ($is[2] == 1) { // GIF
            $output_function = 'ImageGIF';
            $sip = @ImageCreateFromGIF($source);
        }
        else if ($is[2] == 3) { // PNG
            $output_function = 'ImagePNG';
            $sip = @ImageCreateFromPNG($source);
        }
        if ($is && $sip) {
            $sk = $is[0] / $is[1];
            if ($w) {
                $sw = $w;
                $sh = $sw / $sk;
            }
            if ($h) {
                $sh = $h;
                $sw = $sh * $sk;
            }
            $dip = ImageCreateTrueColor($sw, $sh);
            if (ImageCopyResampled($dip, $sip, 0, 0, 0, 0, $sw, $sh, $is[0], $is[1])) {
                ImageDestroy($sip);
                if ($destination) {
                    $output_function($dip, $destination, 80);
                    $ret = true;
                } 
                else {
                    _ob_start();
                    $output_function($dip, false, 80);
                    $ret = ob_get_contents();
                    _ob_end_clean();
                }
                ImageDestroy($dip);
            } 
            else {
                $ret = false;
            }
            return $ret;
        } else {
            return false;
        }
    }
}

if (!function_exists('_resample_image_fit')) {
    /**
    * Изменяет размер картинки, при этом вписывая ее в прямоугольник заданного размера
    * 
    * @param string $source имя исходной картинки и путь к ней
    * @param int $w максимальная ширина результирующей картинки
    * @param int $h максимальная высота результирующей картинки
    * @param string $destination имя результирующей картинки и путь к ней
    * @return boolean true если успешно, false если не успешно
    */
    function _resample_image_fit($source, $w, $h, $fname = false) {
        $is = @GetImageSize($source);

        // if not image or not JPG just copy
        if (
            ($is && ($is[0] <= $w || !$w) && ($is[1] <= $h || !$h)) ||
            !$is ||
            ($is[2] != 2 && $is[2] != 1)
        ) {
            if ($fname) {
                @copy($source, $fname);
                $ret = true;
            } 
            else {
                $ret = file_get_contents($source);
            }
            return $ret;
        }
        if (!$w) {
            $w = $is[0];
        }
        if (!$h) {
            $h = $is[1];
        }
        if ($is[2] == 2) { // JPG
            $output_function = 'ImageJPEG';
            $sip = @ImageCreateFromJPEG($source);
        } 
        else if ($is[2] == 1) { // GIF
            $output_function = 'ImageGIF';
            $sip = @ImageCreateFromGIF($source);
        }
        else if ($is[2] == 3) { // PNG
            $output_function = 'ImagePNG';
            $sip = @ImageCreateFromPNG($source);
        }
        if ($is && $sip) {
            $k = $w / $h;
            $sk = $is[0] / $is[1];
            if ($sk > $k) { // use width
                if ($w < $is[0]) {
                    $sw = $w;
                } 
                else {
                    $sw = $is[0];
                }
                $sh = $sw / $sk;
            } 
            else { // use height
                if ($h < $is[1]) {
                    $sh = $h;
                } 
                else {
                    $sh = $is[1];
                }
                $sw = $sh * $sk;
            }
            $dip = ImageCreateTrueColor($sw, $sh);
            if (ImageCopyResampled($dip, $sip, 0, 0, 0, 0, $sw, $sh, $is[0], $is[1])) {
                ImageDestroy($sip);
                if ($fname) {
                    $output_function($dip, $fname, 80);
                    $ret = true;
                } 
                else {
                    _ob_start();
                    $output_function($dip, false, 80);
                    $ret = ob_get_contents();
                    _ob_end_clean();
                }
                ImageDestroy($dip);
            } 
            else {
                $ret = false;
            }
            return $ret;
        } else {
            return false;
        }
    }
}

if (!function_exists('_pairs_to_array')) {
    /**
    * Превращает массив массивов, в простой ассоциативный массив, например
    * 
    * @param mixed $array входящий массив
    * @param mixed $key_key имя ключа в каждой строке входящего массива, под которым находится имя ключа для результирующего массива; если не указан будет использоваться первый найденый элемент
    * @param mixed $key_value имя ключа в каждой строке входящего массива, под которым находится значение для результирующего массива; если не указан будет использоваться второй найденый элемент
    */
    function _pairs_to_array($array, $key_key = false, $key_value = false, $append_same_key = false) {
        $ret = array();
        $converted = array();
        foreach ($array as $line) {
            $cnt = 0;
            foreach ($line as $key => $value) {
                if ($cnt == 0) {
                    if ($key_key === false) {
                        $use_key_key = $key;
                    }
                    else {
                        $use_key_key = $key_key;
                    }
                    $cnt++;
                }
                else if ($cnt == 1) {
                    if ($key_value === false) {
                        $use_key_value = $key;
                    }
                    else {
                        $use_key_value = $key_value;
                    }
                    break;
                }
            }
            if ($append_same_key && isset($ret[$line[$use_key_key]])) {
                if (!in_array($line[$use_key_key], $converted)) {
                    $converted[] = $line[$use_key_key];
                    $ret[$line[$use_key_key]] = array($ret[$line[$use_key_key]]);
                }
                $ret[$line[$use_key_key]][] = $line[$use_key_value];
            }
            else {
                $ret[$line[$use_key_key]] = $line[$use_key_value];
            }
        }
        return $ret;
    }
}

if (!function_exists('_array_values')) {
    /**
    * Возвращает линейный массив, полученый из массива ассоциативных массивов, значения берутся из ключа, переданного вторым параметром
    * 
    * @param mixed $array
    * @param mixed $key
    * @return array
    */
    function _array_values($array, $key = false) {
        if ($key) {
            $ret = array();
            foreach ($array as $a) {
                if (isset($a[$key])) {
                    $ret[] = $a[$key];
                }
            }
            return $ret;
        }
        else {
            return array_values($array);
        }
    }
}

if (!function_exists('_leading_zero')) {
    function _leading_zero($str,$num,$ch='0') {
        $ret=$str;
        while (strlen($ret)<$num) {
            $ret=$ch.$ret;
        }
        return $ret;
    }
}

if (!function_exists('_file_exists')) {
    /**
    * Тоже самое что встроенная функция file_exists, только кеширует результат в пределах одной загрузки страницы
    *
    * @param mixed $path
    * @return mixed
    */
    function _file_exists($path) {
        static $paths = array();
        if (!isset($paths[$path])) {
            $paths[$path] = file_exists($path);
        }
        return $paths[$path];
    }
}

/**
* Функции для работы с параметрами на низком уровне
*/
if (!function_exists('_read_param')) {
    function _read_param($name, $default_value = false) {
        $post_value = _read_post_param($name);
        $get_value = _read_get_param($name);
        // $cookie_value = _read_cookie_param($name);
        $file_value = _read_file_param($name);
        $router_value = _read_router_param($name);
        if ($post_value !== false) {
            return $post_value;
        }
        else if ($get_value !== false) {
            return $get_value;
        }
        /* since 2011-07-11
        else if ($cookie_value !== false) {
            return $cookie_value;
        }
        */
        else if ($file_value !== false) {
            return $file_value;
        }
        else if ($router_value !== false) {
            return $router_value;
        }
        else {
            return $default_value;
        }
    }
}

if (!function_exists('_read_post_param')) {
    function _read_post_param($name) {
        if (isset($_POST[$name])) {
            $value = $_POST[$name];
            return _param_fix_slashes($value);
        } elseif (isset($HTTP_POST_VARS[$name])) {
            $value = $HTTP_POST_VARS[$name];
            return _param_fix_slashes($value);
        } else {
            return false;
        }
    }
}

if (!function_exists('_read_get_param')) {
    function _read_get_param($name) {
        if (isset($_GET[$name])) {
            $value = $_GET[$name];
            return _param_fix_slashes($value);
        } elseif (isset($HTTP_GET_VARS[$name])) {
            $value = $HTTP_GET_VARS[$name];
            return _param_fix_slashes($value);
        } else {
            return false;
        }
    }
}

if (!function_exists('_read_file_param')) {
    function _read_file_param($name) {
        if (isset($_FILES[$name])) {
            $value = $_FILES[$name];
            return $value;
        } elseif (isset($HTTP_POST_FILES[$name])) {
            $value = $HTTP_POST_FILES[$name];
            return $value;
        } else {
            return false;
        }
    }
}

if (!function_exists('_read_router_param')) {
    function _read_router_param($name) {
        if (isset($GLOBALS['__ROUTER_VALUES']) && isset($GLOBALS['__ROUTER_VALUES'][$name])) {
            return $GLOBALS['__ROUTER_VALUES'][$name];
        }
        else {
            return false;
        }
    }
}

if (!function_exists('_write_router_param')) {
    function _write_router_param($name, $value) {
        if (!isset($GLOBALS['__ROUTER_VALUES'])) {
            $GLOBALS['__ROUTER_VALUES'] = array();
        }
        $GLOBALS['__ROUTER_VALUES'][$name] = $value;
    }
}

if (!function_exists('_read_cookie_param')) {
    function _read_cookie_param($name) {
        if (isset($_COOKIE[$name])) {
            $value = $_COOKIE[$name];
            return _param_fix_slashes($value);
        } elseif (isset($HTTP_COOKIE_VARS[$name])) {
            $value = $HTTP_COOKIE_VARS[$name];
            return _param_fix_slashes($value);
        } else {
            return false;
        }
    }
}

if (!function_exists('_write_cookie_param')) {
    /**
    * Записать куку.
    * Чтобы записать куку на определенное время, третьим параметром передайте время в секундах
    * Чтобы записать куку на время, пока открыто текущее окно браузера, третьим параметром передайте 0
    * Чтобы записать куку надолго, третьим параметром передайте очень большое число; например
    *   - 2592000 сохранит куку на месяц
    *   - 31536000 сохранит куку на год
    *
    * @param mixed $name
    * @param mixed $value
    * @param mixed $exp
    * @param mixed $path
    * @param mixed $domain
    */
    function _write_cookie_param($name, $value, $exp = 3600, $path = null, $domain = null) {
        if (!$exp) {
            setcookie($name, $value, 0, $path, $domain);
        }
        elseif ($exp > 0) {
            setcookie($name, $value, time() + $exp, $path, $domain);
        }
        else {
            setcookie($name, $value, time() - 2592000, $path, $domain);
        }
        return true;
    }
}

if (!function_exists('_param_fix_slashes')) {
    function _param_fix_slashes($value) {
        if (get_magic_quotes_gpc()) {
            if (is_array($value)) {
                return array_map('_param_fix_slashes',$value);
            } else {
                return stripslashes($value);
            }
        } else {
            return $value;
        }
    }
}

if (!function_exists('_array_implode_math')) {
    /**
    * merge array elements with operator, for example
    *   echo call _array_implode_math('+', array(1, 2, 3, 100));
    * will return
    *   106
    *
    * @param mixed $array
    * @return mixed
    */
    function _array_implode_math($operator, $array) {
        $ret = 0;
        if (is_array($array)) {
            foreach ($array as $val) {
                eval('$ret = $ret ' . $operator . ' $val;');
            }
        }
        return $ret;
    }
}

if (!function_exists('_get_bits')) {
    /**
    * explode number on bits, for example
    *   print_r(_get_bits(53));
    * will return
    *   array(1, 4, 16, 32)
    *
    * @param mixed $number
    */
    function _get_bits($number) {
        $bits=array();
        $proceeded = 0;
        $current_bit = 1;
        $number = abs($number);
        do {
            if ($number & $current_bit) {
                $bits[] = $current_bit;
                $proceeded += $current_bit;
            }
            $current_bit *= 2;
        } while ($number > $proceeded);
        return $bits;
    }
}

if (!function_exists('__adjust_params')) {
    function __adjust_params($params) {
        $ret = array();
        if ($params && is_array($params)) {
            foreach ($params as $key => $val) {
                if (strlen($key) > 2 && substr($key, strlen($key) - 2) == '[]') {
                    $key = substr($key, 0, strlen($key) - 2);
                    if (!is_array($val)) {
                        $val = array($val);
                    }
                }
                $ret[$key] = $val;
            }
        }
        return $ret;
    }
}

if (!function_exists('_adjust_number')) {
    function _adjust_number($number, $separator = ',') {
        $number = (string) $number;
        $ret = '';
        $strlen = strlen($number);
        while ($strlen - 3 > 0) {
            $strlen -= 3;
            $ret = substr($number, $strlen) . ($ret ? $separator . $ret : '');
            $number = substr($number, 0, $strlen);
        }
        $ret = $number . ($ret ? $separator . $ret : '');
        return $ret;
    }
}

if (!function_exists('_generate_id')) {
    /**
     * Генерирует красивый ID вида YYMMDDNNNN, где
     *  YY - текущий год
     *  MM - текущий месяц
     *  DD - текущий день
     *  NNNN - порядковый номер заказа в этот день
     *
     * @param $max_id - последний существующий ID
     * @param int $digits - количество разрядов в части NNNN
     * @return string
     */
    function _generate_id($max_id, $digits = 4) {
        $current_date = date('ymd');
        $id_mask = '';
        for ($i = 0; $i < $digits; $i++) {
            $id_mask .= '\d';
        }
        if (preg_match('#(\d\d\d\d\d\d)(' . $id_mask . ')#', $max_id, $match)) {
            $max_date = $match[1];
            $max_number = $match[2];
            if ($max_date != $current_date) {
                $max_number = 0;
            }
        }
        else {
            $max_number = 0;
        }
        return $current_date . _leading_zero($max_number + 1, $digits);
    }
}

if (!function_exists('date_parse_from_format')) {
    /**
     * taken as is from http://drupal.org/node/1184470
     */
    function date_parse_from_format($format, $date) {
        $i=0;
        $pos=0;
        $output=array();
        while ($i< strlen($format)) {
            $pat = substr($format, $i, 1);
            $i++;
            switch ($pat) {
                case 'd': //    Day of the month, 2 digits with leading zeros    01 to 31
                    $output['day'] = substr($date, $pos, 2);
                    $pos+=2;
                    break;
                case 'D': // A textual representation of a day: three letters    Mon through Sun
                    //TODO
                    break;
                case 'j': //    Day of the month without leading zeros    1 to 31
                    $output['day'] = substr($date, $pos, 2);
                    if (!is_numeric($output['day']) || ($output['day']>31)) {
                        $output['day'] = substr($date, $pos, 1);
                        $pos--;
                    }
                    $pos+=2;
                    break;
                case 'm': //    Numeric representation of a month: with leading zeros    01 through 12
                    $output['month'] = (int)substr($date, $pos, 2);
                    $pos+=2;
                    break;
                case 'n': //    Numeric representation of a month: without leading zeros    1 through 12
                    $output['month'] = substr($date, $pos, 2);
                    if (!is_numeric($output['month']) || ($output['month']>12)) {
                        $output['month'] = substr($date, $pos, 1);
                        $pos--;
                    }
                    $pos+=2;
                    break;
                case 'Y': //    A full numeric representation of a year: 4 digits    Examples: 1999 or 2003
                    $output['year'] = (int)substr($date, $pos, 4);
                    $pos+=4;
                    break;
                case 'y': //    A two digit representation of a year    Examples: 99 or 03
                    $output['year'] = (int)substr($date, $pos, 2);
                    $pos+=2;
                    break;
                case 'g': //    12-hour format of an hour without leading zeros    1 through 12
                    $output['hour'] = substr($date, $pos, 2);
                    if (!is_numeric($output['day']) || ($output['hour']>12)) {
                        $output['hour'] = substr($date, $pos, 1);
                        $pos--;
                    }
                    $pos+=2;
                    break;
                case 'G': //    24-hour format of an hour without leading zeros    0 through 23
                    $output['hour'] = substr($date, $pos, 2);
                    if (!is_numeric($output['day']) || ($output['hour']>23)) {
                        $output['hour'] = substr($date, $pos, 1);
                        $pos--;
                    }
                    $pos+=2;
                    break;
                case 'h': //    12-hour format of an hour with leading zeros    01 through 12
                    $output['hour'] = (int)substr($date, $pos, 2);
                    $pos+=2;
                    break;
                case 'H': //    24-hour format of an hour with leading zeros    00 through 23
                    $output['hour'] = (int)substr($date, $pos, 2);
                    $pos+=2;
                    break;
                case 'i': //    Minutes with leading zeros    00 to 59
                    $output['minute'] = (int)substr($date, $pos, 2);
                    $pos+=2;
                    break;
                case 's': //    Seconds: with leading zeros    00 through 59
                    $output['second'] = (int)substr($date, $pos, 2);
                    $pos+=2;
                    break;
                case 'l': // (lowercase 'L')    A full textual representation of the day of the week    Sunday through Saturday
                case 'N': //    ISO-8601 numeric representation of the day of the week (added in PHP 5.1.0)    1 (for Monday) through 7 (for Sunday)
                case 'S': //    English ordinal suffix for the day of the month: 2 characters    st: nd: rd or th. Works well with j
                case 'w': //    Numeric representation of the day of the week    0 (for Sunday) through 6 (for Saturday)
                case 'z': //    The day of the year (starting from 0)    0 through 365
                case 'W': //    ISO-8601 week number of year: weeks starting on Monday (added in PHP 4.1.0)    Example: 42 (the 42nd week in the year)
                case 'F': //    A full textual representation of a month: such as January or March    January through December
                case 'u': //    Microseconds (added in PHP 5.2.2)    Example: 654321
                case 't': //    Number of days in the given month    28 through 31
                case 'L': //    Whether it's a leap year    1 if it is a leap year: 0 otherwise.
                case 'o': //    ISO-8601 year number. This has the same value as Y: except that if the ISO week number (W) belongs to the previous or next year: that year is used instead. (added in PHP 5.1.0)    Examples: 1999 or 2003
                case 'e': //    Timezone identifier (added in PHP 5.1.0)    Examples: UTC: GMT: Atlantic/Azores
                case 'I': // (capital i)    Whether or not the date is in daylight saving time    1 if Daylight Saving Time: 0 otherwise.
                case 'O': //    Difference to Greenwich time (GMT) in hours    Example: +0200
                case 'P': //    Difference to Greenwich time (GMT) with colon between hours and minutes (added in PHP 5.1.3)    Example: +02:00
                case 'T': //    Timezone abbreviation    Examples: EST: MDT ...
                case 'Z': //    Timezone offset in seconds. The offset for timezones west of UTC is always negative: and for those east of UTC is always positive.    -43200 through 50400
                case 'a': //    Lowercase Ante meridiem and Post meridiem    am or pm
                case 'A': //    Uppercase Ante meridiem and Post meridiem    AM or PM
                case 'B': //    Swatch Internet time    000 through 999
                case 'M': //    A short textual representation of a month: three letters    Jan through Dec
                default:
                    $pos++;
            }
        }
        return  $output;
    }
}

if (!function_exists('_unix_eol')) {
    /**
    * Замещает в передаваемой строке все возможные комбинации спецсимволов переноса строк в классический *NIX формат (\n)
    * 
    * @param string $str
    * @return string
    */
    function _unix_eol($str) {
        return str_replace("\r", "\n", str_replace("\n\r", "\n", str_replace("\r\n", "\n", $str)));
    }
}
