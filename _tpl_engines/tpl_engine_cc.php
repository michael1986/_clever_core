<?php
/**
* @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
* @version 1.0
* @package CleverCore2
*/

/**
* TODO: {$value[$key]}
*
* допустимы следующие конструкции:
* 
*   {$var}
*   переменная, заменяется на 
*       <?php echo $var ?>
*   или 
*       ' . $var . '
*   в зависимости от контекста
*   Если переменная - массив, то к его элементам можно обращаться с помощью квадратных скобок без ковычек:
*       {$var[elem]}
*       {$var[elem][subelem]}
*       {$var[elem][subelem][15]}
*   Если переменная объект tpl_engine, то к его переменным можно обращаться с помощью точки:
*       {$var.subvar}
*       {$var.subvar.onmore}
*   Если переменная имеет структуру (массивы и объекты tpl_engine) то можно комбинировать:
*       {$var.subvar[elem].elem_var}
* 
*   TODO: {=$expression}
*   переменная или сложное PHP выражение, заменяется на
*       <?php echo $expression ?>
*   или 
*       ' . $var . '
*   в зависимости от контекста.
*   $expression может быть любым PHP выражением:
*       $a + $b
*       abs($c) - sizeof($d)
*       ($e % $f) - 2
*   и так далее
*   
*   {?$condition}...{/?}
*   {?$condition}...{!}...{/?}
*   условие, заменяется на
*       <?php if ($condition) { ?>...<?php } ?>
*       <?php if ($condition) { ?>...<?php } else { ?>...<?php } ?>
*   $condition может быть любым PHP условием: 
*       $var === 'string'
*       $var1 == 1 && $var2 == 2
*       ($a || $b) && $c
*   и так далее.
* 
*   {*$loop:$var}...{/*}
*   {*$loop:$key,var}...{/*}
*   TODO: {*$loop}{>}...{<}...{/*}
* 
*   {@tpl_name}
*   {@tpl_name:$var1, $var2...}
*   {@tpl_name:a=b, c=d...}
*   {@tpl_name:$var1, $var2..., a=b, c=d...}
* 
* TODO: {literal}...{/literal} - не парсить
* TODO: {:$form.fields[user_name].class = 'myClass'}
* TODO: систеные переменные в цикле, например {$_.last} {$_.iteration}
*/
class tpl_engine_cc extends _tpl_engine {

//    protected $__loop_variables_stack = array();

    protected function __parse_tpl($tpl, $context = 'html', $first_iteration = false) {
        $out = '';
        $pos = 0;
        while ($pos < strlen($tpl)) {
            $current_char = $tpl[$pos];
            if ($current_char == '{') {
                // $close_char_pos = 
                list($old_string, $new_string) = $this->__analise_open_brace($tpl, $pos, $context);
                $out .= $new_string;
                $pos += strlen($old_string);
            } else {
                $out .= $current_char;
                $pos++;
            }
        }

        if ($first_iteration) {
            $open_pos1 = strrpos($out, '<?php');
            $open_pos2 = strrpos($out, '<?');
            $open_pos3 = strrpos($out, '<?=');

            if ($open_pos1 !== false || $open_pos2 !== false || $open_pos3 !== false) {
                $open_pos = max($open_pos1, $open_pos2, $open_pos3);
                if (!preg_match('#\s\?\>#s', substr($out, $open_pos))) {
                    $out .= ' ?>';
                }
            }
        }
        return $out;
    }

    /**
    * функция проверяет, является ли конструкция, находящаяся в строке $tpl начиная с позиции $start_pos
    * частью шаблона. Если является - то вернется массив, содержащий найденую подстроку, 
    * соответвующую найденой конструкции (элемент [0]), а также эквивалентную PHP конструкцию (элемент [1])
    * 
    * @param mixed $tpl
    * @param mixed $start_pos указывает на начало конструкции, т.е. на символ '{'
    * @param mixed $context контекст, в котором находится конструкция: 'php', 'html', 'string'
    * @return array($cc_construction, $php_construction)
    */
    protected function __analise_open_brace($tpl, $start_pos, $context = 'html') {
        $pos = $start_pos + 1;
        if (isset($tpl[$pos])) {
            $old_string = '{';
            $new_string = '{';
            if ($tpl[$pos] == '@') { // inclusion
                list($is_tpl_name, $tpl_name, $array_params, $key_params, $new_pos) = $this->__parse_inclusion($tpl, $pos, $context);
                if ($context == 'find') {
                    $old_string = $new_string = substr($tpl, $start_pos, $new_pos - $start_pos);
                }
                else {
                    if ($is_tpl_name) {
                        $new_string = '';
                        if ($context == 'html') {
                            $new_string .= '<?php echo ';
                        }
                        else if ($context == 'string') { // context is string
                            $new_string .= '\' . ';
                        }
                        if ($tpl_name[0] == '*') {
                            $tpl_name = substr($tpl_name, 1);
                            $search_above_path = true;
                        }
                        else {
                            $search_above_path = false;
                        }
                        $new_string .= '$this->_tpl(\'' . $tpl_name . '\'';
                        $close_arr_merge = false;
                        $close_arr = false;
                        if (sizeof($array_params) || sizeof($key_params)) {
                            $new_string .= ', ';
                            if (sizeof($array_params)) {
                                if (sizeof($array_params) > 1 || sizeof($key_params)) {
                                    $new_string .= 'array_merge(';
                                    $close_arr_merge = true;
                                }
                                $first = true;
                                foreach ($array_params as $param) {
                                    if ($first) {
                                        $first = false;
                                    }
                                    else {
                                        $new_string .= ', ';
                                    }

                                    // если объект TPL - то берем его переменные, иначе массив как есть
                                    if ($param == '$this') {
                                        $new_string .= '$this->_vars';
                                    }
                                    else {
                                        $new_string .= 'is_object(' . $param . ') ? ' . $param . '->_vars : ' . $param;
                                    }
                                }
                            }
                            if (sizeof($key_params)) {
                                if (sizeof($array_params)) {
                                    $new_string .= ', ';
                                }
                                $new_string .= 'array(';
                                $close_arr = true;
                                $first = true;
                                foreach ($key_params as $key => $value) {
                                    if ($first) {
                                        $first = false;
                                    }
                                    else {
                                        $new_string .= ', ';
                                    }
                                    
                                    if (preg_match('#^{\$\w[^\s]*}$#', $value)) {
                                        // это переменная
                                        $new_string .= '\'' . $key . '\' => ' . $this->__format_variable(substr($value, 2, strlen($value) - 3));
                                    }
                                    else {
                                        // это строка
                                        $new_string .= '\'' . $key . '\' => \'' . $this->__parse_tpl($value, 'string') . '\'';
                                    }
                                }
                            }
                        }
                        if ($close_arr_merge) {
                            $new_string .= ')';
                        }
                        if ($close_arr) {
                            $new_string .= ')';
                        }

                        if ($search_above_path) {
                            $new_string .= ', \'' . $this->__source_path . '\'';
                        }

                        if ($context == 'html') {
                            $new_string .= ') ?>';
                        }
                        else if ($context == 'string') { // context is string
                            $new_string .= ') . \'';
                        }
                        else if ($context == 'value') {
                            eval('$new_string = ' . $new_string . ';');
                        }
                    }
                    else {
                        // в $tpl_name содержится шаблон "как есть" для вставки прямо в 
                        // вызваший шаблон
                        $new_string = $tpl_name;
                    }
                    $old_string = substr($tpl, $start_pos, $new_pos - $start_pos);
                }
            }
            else if ($tpl[$pos] == ' ' || $tpl[$pos] == '$') { // variable ?
                if ($tpl[$pos] == ' ') {
                    while (isset($tpl[$pos]) && $tpl[$pos] == ' ') {
                        $pos++;
                    }
                }
                if (isset($tpl[$pos]) && $tpl[$pos] == '$') { // variable
                    list($variable, $new_pos) = $this->__parse_variable($tpl, $pos, $context);
                    if ($context == 'find') {
                        $old_string = $new_string = substr($tpl, $start_pos, $new_pos - $start_pos);
                    }
                    else {
                        if ($context == 'html') {
                            $new_string = '<?php echo ' . $variable . ' ?>';
                        }
                        else if ($context == 'string') {
                            $new_string = '\' . ' . $variable . ' . \'';
                        }
                        $old_string = substr($tpl, $start_pos, $new_pos - $start_pos);
                    }
                }
            }
            else if ($tpl[$pos] == '?') { // condition
                list($cond_condition, $cond_tpl_true, $cond_tpl_false, $new_pos) = $this->__parse_condition($tpl, $pos, $context);
                if ($context == 'find') {
                    $old_string = $new_string = substr($tpl, $start_pos, $new_pos - $start_pos);
                }
                else {
                    if ($context == 'html') {
                        $new_string = '<?php if (' . $cond_condition . ') { ?>';
                        $new_string .= $cond_tpl_true;
                        if ($cond_tpl_false !== '') {
                            $new_string .= '<?php } else { ?>';
                            $new_string .= $cond_tpl_false;
                        }
                        $new_string .= '<?php } ?>';
                        $old_string = substr($tpl, $start_pos, $new_pos - $start_pos);
                    }
                    else if ($context == 'string') { // context is string
                        $new_string = '\' . (' . $cond_condition . ' ? ';
                        $new_string .= '\'' . $cond_tpl_true . '\'';
                        $new_string .= ' : ';
                        if ($cond_tpl_false) {
                            $new_string .= '\'' . $cond_tpl_false . '\'';
                        }
                        else {
                            $new_string .= '\'\'';
                        }
                        $new_string .= ') . \'';
                        $old_string = substr($tpl, $start_pos, $new_pos - $start_pos);
                    }
                    else {
                        // TODO: context == 'string' (for inclusions)
                        // TODO: content == 'value' (for all)
                        $new_string = $old_string = substr($tpl, $start_pos, $new_pos - $start_pos);
                    }
                }
            }
            else if ($tpl[$pos] == '*') { // loop
                list($loop_array, $loop_key, $loop_value, $loop_tpl, $new_pos) = $this->__parse_loop($tpl, $pos, $context);
                if ($context == 'find') {
                    $old_string = $new_string = substr($tpl, $start_pos, $new_pos - $start_pos);
                } else {
                    if ($context == 'html') {
                        $loop_value_alias = substr($loop_value, 1);
                        $new_string = '<?php $_[\'' . $loop_value_alias . '\'] = array(\'queue\' => 0, \'total\' => sizeof(' . $loop_array . ')); foreach ((' . $loop_array . '?' . $loop_array . ' : array()) as ' . ($loop_key? $loop_key . ' => ':'') . $loop_value . ') { ';
                        $new_string .= 
                            'if ($_[\'' . $loop_value_alias . '\'][\'queue\'] % 2) { $_[\'' . $loop_value_alias . '\'][\'even\'] = true; $_[\'' . $loop_value_alias . '\'][\'odd\'] = false; } ' .
                            'else {$_[\'' . $loop_value_alias . '\'][\'even\'] = false; $_[\'' . $loop_value_alias . '\'][\'odd\'] = true; } ' . 
                            'if ($_[\'' . $loop_value_alias . '\'][\'queue\'] == 0) {$_[\'' . $loop_value_alias . '\'][\'first\'] = true;} ' . 
                            'else {$_[\'' . $loop_value_alias . '\'][\'first\'] = false;} ' . 
                            'if ($_[\'' . $loop_value_alias . '\'][\'queue\'] == $_[\'' . $loop_value_alias . '\'][\'total\'] - 1) {$_[\'' . $loop_value_alias . '\'][\'last\'] = true;} ' . 
                            'else {$_[\'' . $loop_value_alias . '\'][\'last\'] = false;} ' . 
                            '?>';
                        $new_string .= $loop_tpl;
                        $new_string .= '<?php $_[\'' . $loop_value_alias . '\'][\'queue\']++; } ?>';
                        $old_string = substr($tpl, $start_pos, $new_pos - $start_pos);
                    } else {
                        // TODO: context == 'string' (for inclusions)
                        $new_string = $old_string = substr($tpl, $start_pos, $new_pos - $start_pos);
                    }
                }
            }
            else if ($tpl[$pos] == '/') { // closing tag
                $old_string = $this->__parse_close_tag($tpl, $pos, $context);
                $new_string = false;
            }
            // TODO: {+subtemplate}, {=var:value}, {#eval:expression}
        }
        return array($old_string, $new_string);
    }

    /**
    * функция парсит шаблонную конструкцию, отвечающую за переменные
    * {$...}; вернет сгенерированное имя переменной и новую позицию (
    * 
    * @param mixed $tpl
    * @param mixed $start_pos указывает на первый символ, следующий за символом '$'
    * @return array($variable, $new_pos) 
    * [0] сгенерированное имя переменной, например '$variable' или '$this->_vars['variable']'
    * [1] указатель на позицию в шаблоне, следующую сразу за закрывающей скобкой '{'
    */
    protected function __parse_variable($tpl, $start_pos, $context) {
        $pos = $start_pos + 1;
        $variable = '';
        while ($pos < strlen($tpl)) {
            $current_char = $tpl[$pos];
            if ($current_char == '}') {
                break;
            } else {
                $append_str = $current_char;
                $increase_pos = 1;
            }
            $variable .= $append_str;
            $pos += $increase_pos;
        }

        if ($variable[0] == '$') {
            $variable = substr($variable, 1);
        }

        if ($context == 'find') {
            return array($variable, $pos + 1);
        } else {
            $var = $this->__format_variable($variable);
            return array($var, $pos + 1);
        }
    }

    protected function __format_variable($variable, $get = true) {
        $variable = str_replace(']', '', $variable);
        $res = '$';
        $first_occurance = true;
        for ($i = 0; $i < strlen($variable); $i++) {
            if ($variable[$i] == '.' || $variable[$i] == '[') {
                if ($first_occurance) {
                    $first_occurance = false;
                }
                else {
                    $res .= '\']';
                }
                if ($variable[$i] == '.') {
                    $res .= '->_vars';
                }
                $res .= '[\'';
            }
            else {
                $res .= $variable[$i];
            }
        }
        if (!$first_occurance) {
            $res .= '\']';
        }
        if ($get) {
            $res = '(isset(' . $res . ') ? ' . $res . ' : false)';
        }
        return $res;
    }

    /**
    * функция парсит шаблонную конструкцию, отвечающую за подключение другого шаблона {@...};
    * вернет имя шаблона и массив параметров
    */
    protected function __parse_inclusion($tpl, $start_pos, $context) {
        $pos = $start_pos + 1;
        $file_name = '';
        while (isset($tpl[$pos]) && $tpl[$pos] != ':' && $tpl[$pos] != '}') {
            $file_name .= $tpl[$pos];
            $pos++;
        }
        if ($tpl[$pos] == '}') {
            // этот шаблон надо вставить "как есть"
            // return array(trim($file_name), array(), array(), $pos + 1);
            if ($file_name[0] == '*') {
                $file_name = substr($file_name, 1);
                $search_above_path = $this->__source_path;
            }
            else {
                $search_above_path = false;
            }
            return array(false, $this->_tpl($file_name, false, $search_above_path)->_get_result(false), array(), array(), $pos + 1);
        }

        $pos++;

        // начинаются параметры
        $array_params = array();
        $key_params = array();

        $key = '';
        $value = '';
        $mode = 'key';

        $opened_braces_rec = 0;

        while ($pos < strlen($tpl)) {
            $current_char = $tpl[$pos];
            if ($current_char == '=') {
                if ($mode == 'value') {
                    _cc::fatal_error(__DEBUG_TPL, 'TPL Error. \',\' expecting instead of \'=\' inside inclusion parameters inside <b>' . $this->__name . '</b> template for <b>' . get_class($this->_get_holder()) . '</b> module');
                }
                $mode = 'value';
                $append_str = '';
                $increase_pos = 1;
            }
            else if ($current_char == ',') {
                if ($this->__parse_include_pair($key, $value, $mode, $array_params, $key_params, $context)) {
                    $mode = 'key';
                    $append_str = '';
                    $increase_pos = 1;
                }
                else {
                    // !!! обработано в $this->__parse_include_pair, т.е. сюда не попадаем никогда
                    return array();
                }
            }
            else if ($current_char == '"' || $current_char == '\'') {
                /*
                 * TODO: (нужно ли?) доработать обработку ковычек внутри значения параметра, что бы можно было записать так:
                 * {@form_js.tpl:~, errors_container_id = backendEmsContain'er{$id}, error_form_class = backendFormError, error_field_class = backendFieldError}
                 * вместо
                 * {@form_js.tpl:~, errors_container_id = 'backendEmsContain\'er{$id}', error_form_class = backendFormError, error_field_class = backendFieldError}
                 */
                $string_ended = strpos($tpl, $current_char, $pos + 1);
                while ($string_ended && $this->__find_backslash($tpl, $string_ended)) {
                    $string_ended = strpos($tpl, $current_char, $string_ended + 1);
                }
                $append_str = substr($tpl, $pos, $string_ended - $pos + 1);
                $increase_pos = strlen($append_str);
            }
            else if ($current_char == '{') {
                if ($mode == 'key') {
                    list($old_string, $new_string) = $this->__analise_open_brace($tpl, $pos, 'find');
                    $append_str = $old_string;
                }
                else {
                  list($old_string, $new_string) = $this->__analise_open_brace($tpl, $pos, 'find');
                    $append_str = $old_string;
                }
                $increase_pos = strlen($old_string);
            }
            else if ($current_char == '}') {
                break;
            }
            else {
                $append_str = $current_char;
                $increase_pos = 1;
            }
            if ($mode == 'key') {
                $key .= $append_str;
            }
            else {
                $value .= $append_str;
            }
            $pos += $increase_pos;
        }
        if ($this->__parse_include_pair($key, $value, $mode, $array_params, $key_params, $context)) {
            return array(true, trim($file_name), $array_params, $key_params, $pos + 1);
        }
        else {
            // !!! обработано в $this->__parse_include_pair, т.е. сюда не попадаем никогда
            return array();
        }
    }

    protected function __parse_include_pair(&$key, &$value, &$mode, &$array_params, &$key_params, $context) {
        $key = trim($key);
        // $value = trim($value, '\'"');
        $value = preg_replace('#^\s*([\'"]?)(.*?)\\1\s*$#s', '\\2', $value);

        if ($mode == 'key') {
            if ($key == '~') {
                $array_params[] = '$this';
            }
            else if ($key) {
                $array_params[] = $this->__format_variable(substr($key, 1), false);
            }
            $key = '';
        }
        else if (preg_match('#[_\w]+[\w\d_]*#', $key)) {
            $key_params[$key] = $value;
            $key = '';
            $value = '';
        }
        else {
            _cc::fatal_error(_DEBUG_TPL, 'TPL Error. Key inside inclusion parameters contains wrong chars inside <b>' . $this->__name . '</b> template for <b>' . get_class($this->_get_holder()) . '</b> module', 'error');
        }

        return true;
    }

    protected function __parse_loop($tpl, $start_pos, $context) {
        $pos = $start_pos + 1;
        $mode = 'array';

        $loop_array = '';
        $loop_key = '';
        $loop_value = '';
        $loop_tpl = '';

        // parse loop start
        while ($pos < strlen($tpl)) {
            $current_char = $tpl[$pos];
            if ($current_char == ',') {
                if ($mode == 'key') {
                    $mode = 'value';
                    $append_str = '';
                    $increase_pos = 1;
                }
                else if ($mode == 'tpl') {
                    $append_str = $current_char;
                    $increase_pos = 1;
                }
                else {
                    _cc::fatal_error(_DEBUG_TPL, 'TPL Error. Unexpected \',\' char during loop parsing inside <b>' . $this->__name . '</b> template for <b>' . get_class($this->_get_holder()) . '</b> module', 'error');
                }
            }
            else if ($current_char == '{') {
                if ($mode == 'tpl') {
                    list($old_string, $new_string) = $this->__analise_open_brace($tpl, $pos, 'find');
                    if ($old_string == '{/*}') {
                        break;
                    }
                    else {
                        $append_str = $old_string;
                        $increase_pos = strlen($old_string);
                    }
                }
                else {
                    list($old_string, $new_string) = $this->__analise_open_brace($tpl, $pos, 'find');
                    $append_str = $old_string;
                    $increase_pos = strlen($old_string);
                }
            }
            else if ($current_char == ':') {
                if ($mode == 'array') {
                    $mode = 'key';
                    $append_str = '';
                    $increase_pos = 1;
                }
                else if ($mode == 'tpl') {
                    $append_str = $current_char;
                    $increase_pos = 1;
                }
                else {
                    _cc::fatal_error(_DEBUG_TPL, 'TPL Error. Unexpected \':\' char during loop parsing inside <b>' . $this->__name . '</b> template for <b>' . get_class($this->_get_holder()) . '</b> module', 'error');
                }
            }
            else if ($current_char == '}') {
                if ($mode == 'key' || $mode == 'value') {
                    $mode = 'tpl';
                    $append_str = '';
                    $increase_pos = 1;
                }
                else if ($mode == 'tpl') {
                    $append_str = $current_char;
                    $increase_pos = 1;
                }
                else {
                    _cc::fatal_error(_DEBUG_TPL, 'TPL Error. Unexpected \'}\' char during loop parsing inside <b>' . $this->__name . '</b> template for <b>' . get_class($this->_get_holder()) . '</b> module', 'error');
                }
            }
            else {
                $append_str = $current_char;
                $increase_pos = 1;
            }
            if ($mode == 'array') {
                $loop_array .= $append_str;
            }
            else if ($mode == 'key') {
                $loop_key .= $append_str;
            }
            else if ($mode == 'value') {
                $loop_value .= $append_str;
            }
            else if ($mode == 'tpl') {
                $loop_tpl .= $append_str;
            }
            $pos += $increase_pos;
        }
        if (!$loop_value) {
            $loop_value = $loop_key;
            $loop_key = '';
        }

        if ($context != 'find') {
//            list($var, $referred_to) = $this->__format_variable(substr($loop_array, 1)); // substr will trim '$'
            $var = $this->__format_variable(substr($loop_array, 1)); // substr will trim '$'
/*
            eval('$loop_array_keys = array_keys(' . $referred_to . ');');
            if (sizeof($loop_array_keys)) {
                array_push($this->__loop_variables_stack, array(
                    'var' => $loop_value,
                    'referred_to' => $referred_to . '[' . $loop_array_keys[0] . ']'
                ));
            } else {
                array_push($this->__loop_variables_stack, array(
                    'var' => $loop_value,
                    'referred_to' => false
                ));
            }
*/
            $loop_tpl = $this->__parse_tpl($loop_tpl, 'html');
//            array_pop($this->__loop_variables_stack);

        } else {
            $var = $loop_array;
        }

        return array(trim($var), trim($loop_key), trim($loop_value), $loop_tpl, $pos + 4); // 4 is strlen of '{/*}'
    }

    protected function __parse_condition($tpl, $start_pos, $context) {
        $pos = $start_pos + 1;
        $mode = 'cond';

        $cond_condition = '';
        $cond_tpl_true = '';
        $cond_tpl_false = '';

        // parse loop start
        while ($pos < strlen($tpl)) {
            $current_char = $tpl[$pos];
            if ($current_char == '{') {
                if ($mode == 'cond') {
//                    list($old_string, $new_string) = $this->__analise_open_brace($tpl, $pos, 'value');
                    list($old_string, $new_string) = $this->__analise_open_brace($tpl, $pos, 'find');
//                    $append_str = $new_string;
//                    $increase_pos = strlen($old_string);
                    $append_str = $old_string;
                    $increase_pos = strlen($old_string);
                }
                else {
//                    list($old_string, $new_string) = $this->__analise_open_brace($tpl, $pos, $context);
                    list($old_string, $new_string) = $this->__analise_open_brace($tpl, $pos, 'find');
                    if ($old_string == '{/?}') {
                        break;
                    }
                    else if (substr($tpl, $pos, 3) == '{!}') {
                        if ($mode == 'true') {
                            $mode = 'false';
                            $append_str = '';
                            $increase_pos = 3;
                        } else {
                            _cc::fatal_error(_DEBUG_TPL, 'TPL Error. Unexpected \'{!}\' statement during condition parsing inside <b>' . $this->__name . '</b> template for <b>' . get_class($this->_get_holder()) . '</b> module', 'error');
                        }
                    }
                    else {
//                        $append_str = $new_string;
//                        $increase_pos = strlen($old_string);
                        $append_str = $old_string;
                        $increase_pos = strlen($old_string);
                    }
                }
            }
            else if ($current_char == '}') {
                if ($mode == 'cond') {
                    $mode = 'true';
                    $append_str = '';
                    $increase_pos = 1;
                }
                else {
                    $append_str = $current_char;
                    $increase_pos = 1;
                }
            }
            else {
                $append_str = $current_char;
                $increase_pos = 1;
            }
            if ($mode == 'cond') {
                $cond_condition .= $append_str;
            }
            else if ($mode == 'true') {
                $cond_tpl_true .= $append_str;
            }
            else if ($mode == 'false') {
                $cond_tpl_false .= $append_str;
            }
            $pos += $increase_pos;
        }

        if ($context != 'find') {
            $cond_tpl_true = $this->__parse_tpl($cond_tpl_true, $context);
            $cond_tpl_false = $this->__parse_tpl($cond_tpl_false, $context);

            preg_match_all('#\$(\w[\w\d_.\[\]]*[\w\d_]*)#', $cond_condition, $variables);

            // longest variables should go first
            array_multisort($variables[0], SORT_DESC, $variables[1]);

            for ($i = 0; $i < sizeof($variables[0]); $i++) {
//                list($var, ) = $this->__format_variable($variables[1][$i]);
                $var = $this->__format_variable($variables[1][$i]);
                // replace '$' temporarily to avoid rewriting parts of variables in case
                // of complext conditions, e.g. {?is_array($a) && $a[0]!==false}
                $var = str_replace('$', chr(1), $var);
                $cond_condition = str_replace($variables[0][$i], $var, $cond_condition);
            }
            $cond_condition = str_replace(chr(1), '$', $cond_condition);
        }

        return array($cond_condition, $cond_tpl_true, $cond_tpl_false, $pos + 4); // 4 = strlen('{/*}')
    }

    protected function __parse_close_tag($tpl, $start_pos, $context) {
        $end_pos = strpos($tpl, '}', $start_pos);
        return substr($tpl, $start_pos - 1, $end_pos - $start_pos + 2);
    }

    protected function __find_backslash($dump, $stren) {
        $numb = 0;
        $i = $stren - 1;
        while ($i > 0 && $dump[$i] == "\\") {
            $numb++;
            $i--;
        }
        if ($numb%2) {
            return 1;
        } else {
            return 0;
        }
    }
}


