<?php
/**
 * @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
 * @package CleverCore2
 */
/**
* TODO: Недоработаны поля с несколькими именами
*
* ИНФОРМАЦИЯ НИЖЕ УСТАРЕЛА!!!
* 
* В процессе своей работы, форма вызывает следующие методы:
*   __construct - когда нужно создать данное поле
*   _run - когда нужно вывести поле в браузер
*   get_user_input - когда нужно что бы поле прочитало свое значение из GET/POST/FILES, на основании прочитаного установило internal_value (для гарантированной корректной работы должно полностью совпасть с прочитаным значением) и external_value
*   set_internal_value - когда поле нужно инициализировать сохраненным ранее внутренним представлением значения, здесь же поле должно сразу преобразовать полученое внутреннее представление значения во внешнее
*   get_internal_value - что бы получить внутреннее представление значения поля
*   set_external_value - когда поле нужно инициализировать сохраненным ранее внешним представлением значения, здесь же поле должно сразу преобразовать полученое внешнее представление значения во внутреннее; вызывается только 1 раз
*   get_external_value - что бы получить внешнее представление значения поля
*   form_complete - когда форма успешно завершает свою работу (т.е. нажата какая-нибудь финишная кнопка); вызывается только 1 раз
*   form_canceled - НЕ РЕАЛИЗУЕМО, т.к. невозможно отследить закрытие окна браузера пользователем; нужно отслеживать по времени - спустя какое-то время если не была нажата финишная кнопка, считать что нажата кнопка отмены и чистить устаревшие данные
*   get_external_name - когда форме нужно получить оригинальное 
*   set_errors - форма передает массив ошибок, произошедших в данном поле
*
* Что бы создать новое поле в 99 случаях из 100 достаточно перебить следующие методы:
*   set_external_value
*   set_internal_value
*   form_complete
*   get_tpl_data (вызываемый в _run) или сам _run
* а так же создать шаблон, заданый в свойстве $this->tpl_name
*/
abstract class fieldBase extends _module {
    /**
    * @var string префикс имени поля, добавляется к имени (к $this->external_name)
    */
    protected $prefix = '';
    /**
    * @var boolean является ли поле обязательным, устанавливается при инициализации
    */
    protected $mandatory = false;
    /**
    * @var mixed имя поля как оно было инициализировано, т.е. не содержащее префикс
    */
    protected $external_name = false;
    /**
    * @var mixed внутреннее имя поля (содержащее префикс)
    */
    protected $internal_name = false;
    /**
    * @var mixed хранилище для внутреннего представления значения поля
    */
    protected $internal_value;
    protected $value_filter = '_htmlspecialchars';
    /**
    * @var mixed хранилище для внешнего представления значения поля
    */
    protected $external_value;
    /**
    * @var string тип поля, устанавливается при инициализации; автоматически преобразуется в имя модуля, обрабатывающего данный тип поля, например text => fields/FieldText_mod
    */
    protected $type = false;
    /**
    * @var string подпись к полю, устанавливается при инициализации
    */
    protected $title = false;
    /**
    * @var string ID поля (в DOM HTML), если не указано то используется $this->external_name
    */
    protected $id = false;
    /**
    * тип формы, необходимый форме:
    *   urlencoded:     application/x-www-form-urlencoded
    *   multipart:      multipart/form-data
    * используется формой что бы автоматически определить, когда нужно использовать multipart
    * 
    * @var string
    */
    protected $enctype = 'urlencoded';
    /**
    * если установить в true - к имени поля автоматически будут добавлены квадратные скобки
    * если установить в какое-нибудь значение - это будет будет добавлено к имени в квадратных скобках
    * 
    * Первый вариант не имеет поддержки в полной мере в базовом классе, поэтому не следует 
    * использовать его в полях, в которых не реализована поддержка этой функциональности. Пример 
    * поля, в котором данная возможность поддерживается - checkbox (TODO). Это ограничение обусловлено 
    * тем, что в обычных полях невозможно сопоставить значения из массива с конкретными экземпляром 
    * поля. В случае с checkbox это возможжно, т.к. чекбоксы имеют четко фиксированные значения, 
    * которые и можно искать в массиве значений.
    * 
    * @var mixed
    */
    
    /**
    * @var array ошибки, которые произошли в данном поле (устанавливается в процессе работы формы)
    */
    protected $errors = array();
    /**
    * @var string имя шаблона по-умолчанию
    */
    protected $tpl_name = 'field.tpl';
    protected $tpl_data_init = false;

    /**
    * При объявлении полей их можно объединять в группы
    * 
    * @var string
    */
    protected $group = false;

    /**
    * Имя JS объекта формы, держащей данное поле
    * 
    * @var mixed
    */
    protected $js_form_instance = false;

    /**
    * К имени добавляется префикс, генерируется id поля (по имени)
    */
    public function __construct($data = array()) {
        $this->tpl_data_init = $data;
        if (isset($data['name'])) {
            $data['internal_name'] = $data['name'];
            unset($data['name']);
        }
        parent::__construct($data);
        if (is_array($this->internal_name)) {
            $this->external_name = array();
            if (!$this->id || !is_array($this->id)) {
                $this->id = array();
            }
            for ($i = 0; $i < sizeof($this->internal_name); $i++) {
                $this->external_name[$i] = $this->internal_name[$i];
                $this->internal_name[$i] = $this->prefix . $this->internal_name[$i];
                if (!isset($this->id[$i])) {
                    // $this->id[$i] = $this->external_name[$i];
                    $this->id[$i] = $this->prefix . $this->external_name[$i];
                }
            }
        } else {
            $this->external_name = $this->internal_name;
            $this->internal_name = $this->prefix . $this->internal_name;
            if (!$this->id) {
                // $this->id = strtr($this->external_name, array('[' => '_', ']' => ''));
                $this->id = $this->prefix . strtr($this->external_name, array('[' => '_', ']' => ''));
            }
        }
    }

    /**
    * @return mixed сгенерированный объект шаблона поля
    */
    public function _run() {
        return $this->_tpl($this->tpl_name, $this->get_tpl_data());
    }

    public function get_group() {
        return $this->group;
    }

    /**
    * @return array переменные для шаблоны
    */
    protected function get_tpl_data() {
        if (is_array($this->internal_name)) {
            $errors = array();
            $values = array();
            for ($i = 0; $i < sizeof($this->internal_name); $i++) {
                if ($this->value_filter) {
                    $filter_func = $this->value_filter;
                    $values[$i] = $filter_func($this->internal_value[$i]);
                }
                else {
                    $values[$i] = $this->internal_value[$i];
                }
                if (isset($this->errors[$this->external_name[$i]])) {
                    $errors[$i] = $this->errors[$this->external_name[$i]];
                } else {
                    $errors[$i] = false;
                }
            }
        }
        else {
            if ($this->value_filter) {
                $filter_func = $this->value_filter;
                $values = $filter_func($this->internal_value);
            }
            else {
                $values = $this->internal_value;
            }
            if (isset($this->errors[$this->external_name])) {
                $errors = $this->errors[$this->external_name];
            } else {
                $errors = false;
            }
        }

        $tpl_data = array_merge($this->tpl_data_init, array(
            'original_name' => $this->external_name,
            'name' => $this->internal_name,
            'value' => $values,
            'mandatory' => $this->mandatory,
            'title' => $this->title,
            'id' => $this->id,
            'js_form_instance' => $this->js_form_instance
        ));
        $tpl_data['error'] = $errors;
        return $tpl_data;
    }

    protected function read_param_with_keys($name) {
        if (preg_match('#^(\w+[\w\d]*)(\[.*)$#', $name, $found)) {
            $tmp_name = $found[1];
            $arr_key = str_replace('[', "['", str_replace(']', "']", $found[2]));
            $tmp_value = _read_param($tmp_name);
            eval('$value = isset($tmp_value' . $arr_key . ') ? $tmp_value' . $arr_key . ' : false;');
            return $value;
        }
        else {
            return _read_param($name);
        }
    }

    /**
    * читает пользовательский ввод (GET/POST/FILES) во внутненнее представление значения поле, 
    * сохраняет его в свойстве $this->internal_value
    *
    * @return array ассоциативный массив name=>value (или name[i]=>value[i] если name - массив)
    */
    public function get_user_input() {
        $ret = array();
        if (is_array($this->internal_name)) {
            $value = array();
            for ($i = 0; $i < sizeof($this->internal_name); $i++) {
                $value[$i] = $this->read_param_with_keys($this->internal_name[$i]);
                $ret[$this->external_name[$i]] = $value[$i];
            }
        } else {
            $ret[$this->external_name] = $value = $this->read_param_with_keys($this->internal_name);
        }
        $this->set_internal_value($value);
        return $ret;
    }

    /**
    * перевод внутреннего значения во внешнее
    */
    protected function internal_to_external($internal_value) {
        return $internal_value;
    }

    /**
    * перевод внешнего значения во внутреннее
    */
    protected function external_to_internal($external_value) {
        return $external_value;
    }

    /**
    * устанавливает внешнее представление значения формы, заполняет в соответствии с ним
    * внутреннее представление
    */
    public function set_external_value($value) {
        //if ($this->is_array_element && $this->is_array_element !== true && is_array($value)) {
//            $this->external_value = $value[$this->is_array_element];
//        }
//        else {
            $this->external_value = $value;
//        }
        $this->internal_value = $this->external_to_internal($value);
    }

    /**
    * возвращает внутреннее представление значение
    */
    public function get_external_value() {
        return $this->external_value;
    }

    /**
    * устанавливает внутреннее представление значения формы, заполняет в соответствии с ним
    * внешнее представление
    */
    public function set_internal_value($value) {
        //if ($this->is_array_element && $this->is_array_element !== true && is_array($value)) {
//            $this->internal_value = $value[$this->is_array_element];
//        }
//        else {
            $this->internal_value = $value;
//        }
        $this->external_value = $this->internal_to_external($value);
    }

    /**
    * возвращает внутреннее представление значение
    */
    public function get_internal_value() {
        return $this->internal_value;
    }

    /**
    * getter for enctype
    */
    public function get_enctype() {
        return $this->enctype;
    }

    /**
    * вызывается формой после того как пользователь заполнил последний шаг - т.е. форма отработала успешно
    */
    public function finalize($value) {
        return $value;
    }

    /**
    * @return mixed имя поля как оно было инициализировано
    */
    public function get_external_name() {
        return $this->external_name;
    }

    /**
    * @return mixed имя поля как оно используется
    */
    public function get_internal_name() {
        return $this->internal_name;
    }

    /**
    * установить ошибки, которые произошли в данном поле, вызывается формой
    */
    public function set_errors($errors) {
        $this->errors = $errors;
    }
}


