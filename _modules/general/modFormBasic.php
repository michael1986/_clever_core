<?php
/**
 * @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
 * @author Michail Talashov <Michail_pro1986@mail.ru>
 * @package CleverCore2
 */

/**
 * Basic forms builder (single step, no ajax)
 */
class modFormBasic extends _module {

    /**
    * ID формы, если не инициализирован генерируется автоматически - в настоящий момент используется имя модуля-держателя, но расчитывать на это не рекомендуется
    * 
    * @var string
    */
    protected $id = false;
    
    /**
    * Заголовок формы, будет передан в шаблон
    * 
    * @var string
    */
    protected $title = false;

    /**
    * Префикс всех колбеков
    * 
    * @var string
    */
    protected $prefix_callbacks = '';

    protected $callback_adjust_tpl_data = 'adjust_tpl_data';

    /**
    * Префикс для всех управляющих параметров формы (номер шага, дополнительные флаги и т.п.)
    * Используется что бы избежать конфликтов, если на одной странице расположено несколько форм
    * 
    * @var string
    */
    protected $prefix_params = '';

    /**
    * Имя параметра, который указывает на то что модуль загружен после отправки формы
    * 
    * @var string
    */
    protected $param_form_sent = 'form_sent';
    /**
    * Указывает на то что модуль загружен после отправки формы
    * 
    * @var string
    */
    protected $form_sent = false;

    /**
    * Массив полей, которые будут у формы по-умолчанию
    * 
    * @var array
    */
    protected $fields = array();
    protected $fields_plain = array();
    
    /**
    * Массив кнопок, которые будут у формы по-умолчанию
    * 
    * @var array
    */
    protected $submits = array();
    protected $submits_descriptions_default = array(
        'ok' => array(
            'name' => 'ok',
            'value' =>'SUBMIT_OK'
        ),
        'cancel' => array(
            'name' => 'cancel',
            'value' =>'SUBMIT_CANCEL'
        ),
        'next' => array(
            'name' => 'next',
            'value' =>'SUBMIT_NEXT'
        ),
        'back' => array(
            'name' => 'back',
            'value' =>'SUBMIT_BACK'
        ),
        'finish' => array(
            'name' => 'finish',
            'value' =>'SUBMIT_FINISH'
        ),
        'reset' => array(
            'name' => 'reset',
            'value' =>'SUBMIT_RESET'
        )
    );
    protected $submits_descriptions = array();

    /**
    * Переменные для работы с полями
    * внутренние переменные, нельзя инициализировать
    */
    protected $fields_path = 'general/fields/';
    protected $fields_modules = false;
    protected $fields_modules_map = array();
    protected $submits_modules = false;

    /**
    * Массив - элементы которого ошибки выводимые на экран в текущем шаге
    * нельзя инициализировать
    * 
    * @var array
    */
    protected $errors = array();
    /**
    * Массив - элементы которого - информационные сообщения выводимые на экран в текущем шаге
    * нужно инициализировать
    * 
    * @var array
    */
    protected $info = array();

    /**
    * HTTP метод (get или post)
    * 
    * @var string
    */
    protected $method = 'post';
    protected $enctype = false;

    /**
    * была ли уже выполнена инициализация?
    * @var boolean
    */
    protected $initialized = false;
    /**
    * Текущая нажатая кнопка
    * внутренняя переменная, нельзя инициализировать
    * 
    * @var mixed
    */
    protected $submit = false;
    protected $lang_name = false;
    protected $lang = array();

    public function __construct($data = array()) {
        parent::__construct($data);

        $this->lang = $this->_load_language($this->lang_name);

        if ($this->submits && !is_array($this->submits)) {
            $this->submits = array($this->submits);
        }

        $this->callback_adjust_tpl_data = $this->prefix_callbacks . $this->callback_adjust_tpl_data;

        $submits_inwork = array();
        foreach ($this->submits_descriptions_default as $s => $d) {
            if (isset($this->submits_descriptions[$s])) {
                $d = array_merge($d, $this->submits_descriptions[$s]);
                unset($this->submits_descriptions[$s]);
            }
            $submits_inwork[$s] = $d;
            if (isset($this->lang[$submits_inwork[$s]['value']])) {
                $submits_inwork[$s]['value'] = $this->lang[$submits_inwork[$s]['value']];
            }
        }
        foreach ($this->submits_descriptions as $s => $d) {
            $submits_inwork[$s] = $d;
            $submits_inwork[$s]['value'] = isset($this->lang[$submits_inwork[$s]['value']]) ? $this->lang[$submits_inwork[$s]['value']] : $submits_inwork[$s]['value'];
        }
        $this->submits_descriptions = $submits_inwork;

        // Добавляем префикс ко всем параметрам, которые будем использовать
        $this->param_form_sent = $this->prefix_params . $this->param_form_sent;

        // 2012-05-29
        // $this->fields_plain = $this->fields;

        // Получаем ID формы, если его нет
        if (!$this->id) {
            // $this->id = get_class($this->_get_holder());
            $this->id = $this->prefix_params . get_class($this->_get_holder());
        }
    }

    /**
    * Инициализация, заполняет все временные внутренние переменные, вызывается автоматически при необходимости
    * 
    * @param array $data
    * @return modDataGrid
    */
    protected function initialize() {
        if (!$this->initialized) {
            $this->initialized = true;
            $this->create_fields($this->fields);
            $this->set_external_values();
            if (!$this->submits) {
                $this->submits = array('ok');
            }
            $this->prepare_submits();
            $this->form_sent = _read_param($this->param_form_sent);
            if ($this->form_sent) {
                $this->get_user_input();
                $this->fill_in_submit();
            }
        }
    }

    public function _run() {
        $this->initialize();
        return $this->_tpl('form.tpl', $this->do_adjust_tpl_data($this->get_tpl_data()));
    }

    protected function do_adjust_tpl_data($data) {
        if (method_exists($this->_get_holder(), $this->callback_adjust_tpl_data)) {
            return call_user_func_array(
                array($this->_get_holder(), $this->callback_adjust_tpl_data), 
                array($data)
            );
        }
        else {
            return $data;
        }
    }

    /**
    * Получить "липкие" параметры, использование которых в других формах или ссылках сохранит 
    * текущее состояние данной формы после отправки такой формы или перехода по такой ссылке
    * 
    * Если параметр $values == true, то вернет "липкие" параметры со значениями, если false - вернет
    * для каждого параметра false, что позволит исключить их из ссылки, что бы вернуть форму к 
    * исходному состоянию
    * 
    * @param boolean $values
    */
    public function get_sticky_params($values = true) {
        $this->initialize();

        $ret = array();
        if ($values) {
            $ret[$this->param_form_sent] = $this->form_sent;
            $ret = array_merge($ret, $this->get_internal_values(true));
        }
        else {
            $ret[$this->param_form_sent] = false;
            $fv = $this->get_internal_values(true);
            foreach ($fv as $name => $value) {
                $ret[$name] = false;
            }
        }
        return $ret;
    }

    /**
    * Получить внешние значения из полей текущего шага
    * Возвращает массив полученных значений
    * 
    * @return array $values
    */
    public function get_values() {
        $this->initialize();

        return $this->get_external_values();
    }

    /**
    * возвращает имя сабмита, который был нажат или false если форма не была отправлена
    */
    public function get_submit() {
        $this->initialize();

        return $this->submit;
    }

    /**
    * установить сообщения об ошибках
    */
    public function set_errors($ems) {
        $this->errors = $ems;
    }

    /**
    * Получить внешние значения из полей текущего шага
    * Возвращает массив полученных значений
    * 
    * @return array $values
    */
    protected function get_external_values() {
        $values = array();
        foreach ($this->fields_modules as $__id => $field_module) {
            $field_name = $field_module->get_external_name();
            if ($field_name) {
                $field_value = $field_module->get_external_value();
                if (!is_null($field_value)) { // may be null, for example inside radio_single
                    if (is_array($field_name)) {
                        $field_values = array();
                        for ($i = 0; $i < sizeof($field_name); $i++) {
                            $field_values[$field_name[$i]] = $field_value[$i];
                        }
                    }
                    else {
                        $field_values[$field_name] = $field_value;
                    }
                    $values = array_merge($values, $field_values);
                }
            }
        }
        return $values;
    }


    /**
    * Получить внутренние значения полей текущего шага.
    * Возвращает массив полученных значений
    * 
    * @return array $values
    */
    protected function get_internal_values($use_internal_names = false) {
        $values = array();
        foreach ($this->fields_modules as $__id => $field_module) {
            if ($use_internal_names) {
                $field_name = $field_module->get_internal_name();
            }
            else {
                $field_name = $field_module->get_external_name();
            }
            if ($field_name) {
                $field_value = $field_module->get_internal_value();
                if (!is_null($field_value)) { // may be null, for example inside radio_single
                    if (is_array($field_name)) {
                        $field_values = array();
                        for ($i = 0; $i < sizeof($field_name); $i++) {
                            $field_values[$field_name[$i]] = $field_value[$i];
                        }
                    }
                    else {
                        $field_values[$field_name] = $field_value;
                    }
                    $values = array_merge($values, $field_values);
                }
            }
        }
        return $values;
    }

    /**
    * Получить пользовательский ввод текущего шага
    */
    protected function get_user_input() {
        foreach ($this->fields_modules as $__id => $field_module) {
            $field_module->get_user_input();
        }
    }

    /**
    * заполняет внутреннее свойство $this->submit, если пользователь нажал кнопку - запишется ее 
    * имя, иначе - 'ok'. Метод вызывается только после проверки параметра form_sent, таким образом 
    * если пользователь нажал "ENTER" на форме, вместо того что бы нажать на какой-нибудь сабмит 
    * под ней, система будет интерпретировать это как нажатие на кнопку "OK"
    */
    protected function fill_in_submit() {
        // Кнопка была нажата?
        foreach ($this->submits_modules as $name => $submit) {
            if ($submit->is_pushed()) {
                $this->submit = $name;
                break;
            }
        }
        if (!$this->submit) {
            $this->submit = 'ok'; 
        }
        return $this->submit;
    }

    /**
    * Возвращает параметры для передачи в главный шаблон
    */
    protected function get_tpl_data() {
        // display the form
        $tpl_data = array();
        $tpl_data['id'] = $this->id;

        list($tpl_data['action'], $hidden_params) = $this->_hlink(false, false, true);
        $tpl_data['title'] = $this->title;

        $tpl_data['fields'] = array();
        foreach ($this->fields_modules as $__id => $field) {
            // set fields error
            $field_name = $field->get_external_name();
            if ($field_name) {
                if (!is_array($field_name)) {
                    $field_name = array($field_name);
                }
                $field_errors = array();
                foreach ($this->errors as $key => $error) {
                    if (in_array($key, $field_name)) {
                        $field_errors[$key] = $error;
                    }
                }
                $field->set_errors($field_errors);
            }

            /*
            2012-06-08 logic moved to $this->reorganize_fields_recursive
            if ($group = $field->get_group()) {
                $tpl_data['fields'][$group][$__id] = $field->_run();
            }
            else {
                $tpl_data['fields'][$__id] = $field->_run();
            }
            */
            $tpl_data['fields'][$__id] = $field;// $field->_run();
            // EndOf logic moved
        }
        $tpl_data['fields'] = $this->reorganize_fields_recursive($tpl_data['fields'], $this->fields_modules_map);

        $tpl_data['hidden_fields'] = array();
        foreach ($hidden_params as $param_name => $param_value) {
            $field_hidden_mod = $this->_module($this->fields_path . 'fieldHidden', array(
                'name'  => $param_name,
                'id' => $this->prefix_params . $param_name
            ));
            $field_hidden_mod->set_external_value($param_value);

            $tpl_data['hidden_fields'][] = $field_hidden_mod->_run();
        }

        // переменная сигнализирует о том что форма отправлена
        $field_hidden_mod = $this->_module($this->fields_path . 'fieldHidden', array(
            'name'  => $this->param_form_sent,
            'id' => $this->param_form_sent
        ));

        $field_hidden_mod->set_external_value('on');
        $tpl_data['hidden_fields'][] = $field_hidden_mod->_run();

        $tpl_data['submits'] = array();
        foreach ($this->submits_modules as $name => $button) {
            $tpl_data['submits'][$name] = $button->_run();
        }
        $tpl_data['errors'] = $this->errors;
        $tpl_data['info'] = $this->info;
        $tpl_data['enctype'] = $this->enctype;
        $tpl_data['method'] = $this->method;
        $tpl_data['form_sent'] = $this->form_sent;
        $tpl_data['lang'] = $this->lang;
        $tpl_data['js_instance'] = $this->get_js_instance();

        return $tpl_data;
    }

    public function reorganize_fields_recursive($fields_modules, $fields_modules_map) {
        $ret = array();
        foreach ($fields_modules_map as $__id => $fields_modules_submap) {
            /*
            2012-06-08 logic moved from $this->get_tpl_data
            $ret[$__id] = $fields_modules[$__id];
            if ($fields_modules_submap) {
                $ret[$__id]->_vars['fields'] = $this->reorganize_fields_recursive($fields_modules, $fields_modules_submap);
            }
            */

            $field_tpl = $fields_modules[$__id]->_run();
            if ($fields_modules_submap) {
                $field_tpl->_vars['fields'] = $this->reorganize_fields_recursive($fields_modules, $fields_modules_submap);
            }
            if ($group = $fields_modules[$__id]->get_group()) {
                $ret[$group][$__id] = $field_tpl;
            }
            else {
                $ret[$__id] = $field_tpl;
            }

            // EndOf logic moved
        }
        return $ret;
    }

    /**
    * Превращает ассоциативный массив $this->fields, описывающий поля в массив модулей $this->fields_modules
    * 
    * @return boolean есть ли поля, требующие enctype="multipart/form-data" в форме (поля аплоада)
    */
    protected function create_fields($fields) {
        $is_multipart = false;

        // 2012-05-29 - логика перемещена из метода modForm->reset_fields_modules, т.к. разделение
        // этой логики вроде не имеет смысла
        $this->fields_modules = array();
        $this->fields_plain = array();

        if ($fields) {
            $this->fields_modules_map = array();
            $this->fields_plain = _fields::__line_up_fields($fields, $this->fields_modules_map, $__id_counter = 0);
            foreach ($this->fields_plain as $__id => &$field) {
                $field['prefix'] = $this->prefix_params;
                $field['js_form_instance'] = $this->get_js_instance();
                if (isset($field['module'])) {
                    $field_module_name = $field['module'];
                }
                else {
                    if (isset($field['type']) && $field['type']) {
                        $type = $field['type'];
                    }
                    else {
                        // По-умолчанию тип поля 'text'
                        $type = 'text';
                    }
                    $field_module_name = $this->fields_path . 'field' . implode('', array_map('ucfirst', explode('_', $type)));
                }
                $this->fields_modules[$__id] = $this->_module($field_module_name, $field);

                // проверка multipart
                if ($this->fields_modules[$__id]->get_enctype() == 'multipart') {
                    $is_multipart = true;
                }
            }
            unset($field);
        }

        return $is_multipart;
    }

    /**
    * Установить в поля значения, которыми они были проинициализированы
    */
    protected function set_external_values() {
        foreach ($this->fields_plain as $i => &$field) {
            if (isset($field['value'])) {
                $external_value = $field['value'];
                unset($field['value']);
                $this->fields_modules[$field['__id']]->set_external_value($external_value);
            }
            else {
                $this->fields_modules[$field['__id']]->set_external_value(false);
            }
        }
    }

    /**
    * Превращает ассоциативный массив $this->submits, описывающий поля в массив модулей $this->submits_modules
    */
    protected function prepare_submits() {
        if ($this->submits_modules === false) {
            $submit_module_name = $this->fields_path . 'fieldSubmit';
            foreach ($this->submits as $submit => $description) {
                if (is_numeric($submit)) {
                    $submit = $description;
                    if (isset($this->submits_descriptions[$submit])) {
                        $description = $this->submits_descriptions[$submit];
                    }
                    else {
                        $description = array(
                            'name' => $submit,
                            'value' => $submit
                        );
                    }
                }
                if (!isset($description['name'])) {
                    $description['name'] = $submit;
                }
                if (!isset($description['prefix'])) {
                    $description['prefix'] = $this->prefix_params;
                }
                $description['js_form_instance'] = $this->get_js_instance();

                $external_value = $description['value'];
                unset($description['value']);

                $this->submits_modules[$description['name']] = $this->_module($submit_module_name, $description);
                $this->submits_modules[$description['name']]->set_external_value($external_value);
            }
        }
        return $this;
    }

    public function get_js_instance() {
        return $this->prefix_params . 'FInstance';
    }

    /*
    protected function create_internal_field_id($key, $name, $id_counter) {
        if (is_numeric($key)) {
            if ($name) {
                if (is_array($name)) {
                    $__id = $name[0];
                } else {
                    $__id = $name;
                }
            }
            else {
                $__id = $id_counter;
            }
        }
        else {
            $__id = $key;
        }
        return $__id;
    }
    */
}


