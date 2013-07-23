<?php
/**
* @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
* @author Michail Talashov <Michail_pro1986@mail.ru>
* @version 1.0
* @package modForm
*/

_cc::load_module('general/modFormBasic');

/**
* Работа объекта построена на вызове колбеков
*/
class modForm extends modFormBasic {

    /**
    * @var _db_table
    */
    protected $data_source  = false;

    /**
    * Массив кнопок, которые нужно убрать у формы (удобно в многошаговой форме, что бы скрыть, 
    * например кнопку back или cancel, создаваемые по-умолчанию
    * 
    * @var array
    */
    protected $submits_disabled = array();
    
    /**
    * Тип хранения данных 
    * Принимает три значения 'internal','session','params'. Они определяют где хранить 
    * данные формы полученные с предыдущих шагов:
    * 'internal' - внутреннее хранение данных, для этого типа работает свойство timeout;
    * 'session' - в сессии;
    * 'params' - в url, через метод get/post
    * 
    * @var string
    */
    protected $repository_type = 'internal';
    protected $timeout = 86400;
    protected $internal_directory = 'form_internal_values/';
    
    /**
    * @var mixed
    * 
    *   - форма может перегружать всю страницу                          ajax            => false
    *       - при первом запуске возвращает HTML                            first_load  => 'tpl'
    *       - при последующих запусках возвращает HTML                      second_load => 'tpl'
    *       - редиректы между шагами настоящие                              redirect    => 'http'
    *       - редирект в конце (ok/cancel)                                  redirect_ok => 'http'
    *   - форма может перегружать только себя
    *       - если за замещение формы отвечает держатель                ajax            => 'partial'
    *           - при первом запуске возвращает HTML                        first_load  => 'tpl'
    *           - при последующих запусках возвращает HTML                  second_load => 'tpl'
    *           - редиректы между шагами настоящие                          redirect    => 'http'
    *           - редирект в конце (ok/cancel)                              redirect_ok => 'json'
    *       - если за замещение формы отвечает она сама                 ajax            => 'full'
    *           - при первом запуске возвращает HTML                        first_load  => 'tpl'
    *           - при последующих запусках возвращает JSON, тип 'html'      second_load => 'json'
    *           - редиректы между шагами настоящие                          redirect    => 'http'
    *           - редирект в конце (ok/cancel)                              redirect_ok => 'json'
    * 
    */
    protected $ajax = false;

    /**
     * @var bool если флаг ajax был установлен в true, то форма может возвращать результат своей работы:
     *  либо с помощью функции _overwhelm_response прямо в браузер, что более ожидаемо интуитивно ('suppress_overwhelm' => false, по-умолчанию)
     *  либо обратно держателю с помощью 'return', что может быть удобно в редких случая ('suppress_overwhelm' => false)
     *
     * второй вариант, например, удобно использовать при создании формы внутри попап-окна, создаваемого датагридом:
     * в таком случае датагрид сможет самостоятельно отследить пустой результат работы колбеков формы complete и proceed_cancel чтобы
     * закрыть попап и обновить свое содержимое. Разумеется, этот флаг даже в этом случае необязательно устанавливать в true,
     * но тогда прийдется самостоятельно беспокоиться о закрытии попапа и обновлении содержимого датагрида
     */
    protected $suppress_overwhelm = false;
    /**
    * @var string название JS обработчика для AJAX
    * 
    * @var mixed
    */
    protected $ajax_handler = false;

    /**
    * Максимальное количество шагов в форме
    * 
    * @var mixed
    */
    protected $max_step = false;
    
   
    /**
    * Переменные для работы с полями
    * внутренние переменные, нельзя инициализировать
    */
    protected $fields_modules_all = array();

    /**
    * Контейнеры для содержания в себе значений для хранилища и значений для передачи в колбеки
    * внутренние переменные, нельзя инициализировать
    * 
    * @var array
    */
    protected $external_values = array();
    protected $internal_values = array();
    protected $external_values_converted = false;
    
    /**
    * Указывает на то, что форма загружена не впервые
    * внутренние переменные, нельзя инициализировать
    * 
    * @var boolean
    */
    protected $walking = false;
    protected $param_walking = 'walking';
    
    /**
    * Текущий шаг формы
    * внутренние переменные, нельзя инициализировать
    * 
    * @var int
    */
    protected $step = false;
    protected $param_step = 'step';
    
    /**
    * История нажатых кнопок, которая передаётся во все колбеки 
    * внутренние переменные, нельзя инициализировать
    * 
    * @var mixed
    */
    protected $submits_history = array() ;
    protected $param_submits_history = 'submits_history';
    
    /*
    имена сабмитов формы, которые будут вызывать конфликты:
    input, add_input, edit_input (из-за колбеков вида validate_*)
    имена экшинов датагрида, которые будут вызывать конфликты при одновременном использовании с формой:
    ok, cancel, finish, back, next (из-за колбеков вида proceed_*)
    */

    /**
    * Общий префикс колбеков, используемых для проверки пользовательского ввода.
    * 
    * Полное имя колбека состоит из:
    * $prefix_callbacks . $callback_validate . '_' . $action_name
    * Например, если общий колбек $prefix_callbacks = 'foo_prefix_', $callback_validate = 'validate', а имя action на кнопке = 'ok', то полное имя колбека будет
    * foo_prefix_validate_ok
    * 
    * @var string
    */
    protected $callback_validate = 'validate';
    /**
    * Общий префикс колбеков, используемых для обработки событий формы.
    * 
    * Полное имя колбека состоит из:
    * $prefix_callbacks . $callback_proceed . '_' . $action_name
    * Например, если общий колбек $prefix_callbacks = 'foo_prefix_', $callback_proceed = 'proceed', а имя action на кнопке = 'ok', то полное имя колбека будет
    * foo_prefix_proceed_ok
    * 
    * @var string
    */
    protected $callback_proceed = 'proceed';
    protected $callback_proceed_ok = 'proceed_ok';
    protected $callback_proceed_cancel = 'proceed_cancel';
    protected $callback_proceed_finish = 'proceed_finish';
    protected $callback_proceed_back   = 'proceed_back';
    protected $callback_proceed_next   = 'proceed_next';
    protected $callback_list_submits   = 'list_submits';

    protected $callback_list_add_fields    = 'list_add_fields';
    protected $callback_list_edit_fields    = 'list_edit_fields';
    protected $callback_list_fields    = 'list_fields';

    protected $callback_prepare = 'prepare';

    protected $callback_validate_add_input = 'validate_add_input';
    protected $callback_validate_edit_input = 'validate_edit_input';
    protected $callback_validate_input = 'validate_input';

    /**
    * Колбек вызывается, когда форма заканчивает свою работу
    * 
    * @var mixed
    */
    protected $callback_complete  = 'complete';

    /**
    * Прочие внутренние переменные
    * 
    * @var mixed
    */
    
    protected $table_form_internal_repository = 'form_internal_repository';
    protected $internal_key = false;
    protected $param_internal_key = 'internal_key';
    protected $table_internal_obj = false;

    /**
    * если false, то форма работает в режиме добавления, если {значение} - в режиме редактирования
    * 
    * @var mixed
    */
    protected $edit_id = false;
    /**
    * на эту ссылка форма будет делать редирект после добавления данных (кнопка 'ok'), если нет 
    * соответствующего колбека
    * 
    * @var mixed
    */
    protected $link_ok = false;
    /**
    * на эту ссылка форма будет делать редирект после отмены (кнопка 'cancel'), если нет 
    * соответствующего колбека
    * 
    * @var mixed
    */
    protected $link_cancel = false;

    /**
     * @var bool если установить в true форма не будет читать параметры, отвечающие за ее поведение
     */
    protected $restart = false;

    /**
    * конструктор
    * TODO: создать метод инициализации, перебивающий родителя, вынести в него необходимое из 
    * конструктора
    * 
    * @param mixed $data
    * @return modForm
    */
    public function __construct($data = array()) {
        parent::__construct($data);

        if ($this->data_source && !is_object($this->data_source)) {
            $this->data_source = $this->_create_data_source($this->data_source);
        }

        // Добавляем префикс ко всем параметрам, которые будем использовать
        $this->param_step = $this->prefix_params . $this->param_step;
        $this->param_walking = $this->prefix_params . $this->param_walking;
        $this->param_submits_history = $this->prefix_params . $this->param_submits_history;
        $this->param_internal_key = $this->prefix_params . $this->param_internal_key;

        // Добавляем префикс к колбекам
        $this->callback_list_fields         = $this->prefix_callbacks . $this->callback_list_fields;
        $this->callback_validate_input      = $this->prefix_callbacks . $this->callback_validate_input;

        $this->callback_validate            = $this->prefix_callbacks . $this->callback_validate;
        $this->callback_list_submits        = $this->prefix_callbacks . $this->callback_list_submits;
        $this->callback_proceed             = $this->prefix_callbacks . $this->callback_proceed;
        if (!is_callable($this->callback_proceed_ok)) {
            $this->callback_proceed_ok      = $this->prefix_callbacks . $this->callback_proceed_ok;
        }
        if (!is_callable($this->callback_proceed_cancel)) {
            $this->callback_proceed_cancel      = $this->prefix_callbacks . $this->callback_proceed_cancel;
        }
        $this->callback_proceed_finish      = $this->prefix_callbacks . $this->callback_proceed_finish;
        $this->callback_proceed_back        = $this->prefix_callbacks . $this->callback_proceed_back;
        $this->callback_proceed_next        = $this->prefix_callbacks . $this->callback_proceed_next;

        $this->callback_prepare             = $this->prefix_callbacks . $this->callback_prepare;

        if (!is_callable($this->callback_complete)) {
            $this->callback_complete            = $this->prefix_callbacks . $this->callback_complete;
        }

        // depricated
        $this->callback_list_add_fields     = $this->prefix_callbacks . $this->callback_list_add_fields;
        $this->callback_list_edit_fields    = $this->prefix_callbacks . $this->callback_list_edit_fields;
        $this->callback_validate_add_input  = $this->prefix_callbacks . $this->callback_validate_add_input;
        $this->callback_validate_edit_input = $this->prefix_callbacks . $this->callback_validate_edit_input;
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

            if ($this->repository_type == 'internal') {
                if (!is_dir(_cc::get_tmp_path() . $this->internal_directory)) {
                    _mkdir(_cc::get_tmp_path() . $this->internal_directory);
                }
                if ($this->restart) {
                    $this->internal_key = false;
                }
                else {
                    $this->internal_key = _read_param($this->param_internal_key);
                }
                if (!$this->internal_key){
                    do {
                        $this->internal_key = time() . '[!]' . _unique_string(10);
                    } while (file_exists(_cc::get_tmp_path() . $this->internal_directory . $this->internal_key . '.tmp'));
                }
                $this->_stick_param($this->param_internal_key, $this->internal_key);
            }

            // Получаем необходимые данные для правильной работы формы из GET/POST
            if ($this->restart) {
                $this->step = false;
                $this->_stick_param($this->param_step, $this->step);
            }
            else {
                $this->step = $this->_read_sticky_param($this->param_step);
            }
            if ($this->restart) {
                $submits_history = false;
            }
            else {
                $submits_history = _read_param($this->param_submits_history);
            }
            if ($submits_history) {
                $this->submits_history = unserialize($submits_history);
            }
            else {
                $this->submits_history = array();
            }
            if ($this->restart) {
                $this->form_sent = false;
            }
            else {
                $this->form_sent = _read_param($this->param_form_sent);
            }

            if (!$this->step) {
                $this->step = 0;
            }
            /*
             * 2012-05-11 - из-за этой проверки невозможно послать человека на заведомо больший шаг когда он жмет finish
             * это заставило внутри proceed_finish передавать в walk дополнительный параметр form_sent, из-за которого
             * форма прочитывала параметры последнего шага (_read_param), вместо того чтобы брать дефолтное значение
             * из поля
            else if ($this->max_step !== false && $this->max_step < $this->step) {
                $this->step = $this->max_step;
            }
            */

            if ($this->restart) {
                $this->walking = false;
                $this->_stick_param($this->param_walking, $this->walking);
            }
            else {
                $this->walking = $this->_read_sticky_param($this->param_walking);
            }
            if ($this->walking) {
                $values_all = $this->do_repository($this->repository_type, 'restore');
            } else {
                $values_all = array();
                $this->_stick_param($this->param_walking, 'on');
                $this->do_repository($this->repository_type, 'reset');
            }

            // Валидация предыдущих шагов и сохранение всех значений в общие массивы
            $errors = array();
            $submits_history = array();
            $this->external_values = array();
            for ($i = 0, $sh_pos = 0; $i < $this->step; $i++) {
                if (isset($this->submits_history[$i]) && $this->submits_history[$i] != 'finish') {
                    $submits_history[] = $this->submits_history[$i];
                }
                else {
                    // если нажата finish или был сделан walk > 1
                    $submits_history[] = 'ok';
                }
                // 2012-05-29 - логика перемещена в метод modFormBasic->create_fields, т.к. разделение
                // этой логики вроде не имеет смысла
                //$this->reset_fields_modules();

                $this->create_fields(
                    $this->do_list_fields(
                        $this->edit_id,
                        $this->get_values(true),
                        $i,
                        $this,
                        $submits_history
                    )
                );
                $this->set_internal_values($values_all);

                $this->append_external_values($this->get_external_values());
                $this->fields_modules_all = array_merge($this->fields_modules_all, $this->fields_modules);

                $errors = $this->do_validate(
                    $this->edit_id,
                    $this->get_values(true),
                    $i,
                    $this,
                    $submits_history
                );
                if (sizeof($errors)) {
                    $this->errors = $errors;
                    $this->form_sent = false;
                    $this->set_step($i);
                    break;
                }
            }

            // Сбрасывание переменных, сохранение новой истории кнопок, согласно пройденным шагам
            // 2012-05-29 - логика перемещена в метод modFormBasic->create_fields, т.к. разделение
            // этой логики вроде не имеет смысла
            // $this->reset_fields_modules();
            $this->submits_history = $submits_history;

            // заглушка, в которой модуль может подготовить форму к текущему шагу
            $this->do_prepare(
                $this->edit_id,
                $this->get_values(),
                $this->step,
                $this,
                $this->submits_history
            );

            // Создание полей и сабмитов для текущего шага
            $is_multipart = $this->create_fields(
                $this->do_list_fields(
                    $this->edit_id,
                    $this->get_values(),
                    $this->step,
                    $this,
                    $this->submits_history
                )
            );
            if ($this->enctype === false) {
                if ($is_multipart) {
                    $this->enctype = 'multipart';
                }
                else {
                    $this->enctype = 'urlencoded';
                }
            }
            $this->fields_modules_all = array_merge($this->fields_modules_all, $this->fields_modules);

            if (!$this->submits) {
                $this->submits = $this->do_list_submits();
            }
            if (!is_array($this->submits_disabled)) {
                $this->submits_disabled = array($this->submits_disabled);
            }
            if (sizeof($this->submits_disabled)) {
                $all_submits = $this->submits;
                $this->submits = array();
                foreach ($all_submits as $submit) {
                    if (!in_array($submit, $this->submits_disabled)) {
                        $this->submits[] = $submit;
                    }
                }
            }

            // Проверка на кнопку "ok" есть она или нет, если нет, то засовываем в начало массива кнопок
            $this->check_ok_submit();

            // Cоздание объектов кнопок
            $this->prepare_submits();

            // Была отправлена форма
            if ($this->form_sent) {
                $this->submits_history[] = $this->fill_in_submit();
                // прочитать значения полей
                $this->get_user_input();

                // Добавить значения к общим массивам
                $this->append_internal_values($this->get_internal_values());
                $this->append_external_values($this->get_external_values());

                // Записываем в хранилище - перенесено в функцию walk()
                //$this->do_repository('file','save');

                // Проверить пользовательский ввод
                $this->errors = $this->do_validate(
                    $this->edit_id,
                    $this->get_values(true),
                    $this->step,
                    $this,
                    $this->submits_history
                );

                // Пользователь допустил ошибку(и), надо снова отобразить форму и сообщение(я) об
                // ошибках, либо вывести JSON сообщения об ошибках если включена AJAX валидация
                if (sizeof($this->errors)) {
                    $this->submit = false;
                    array_pop($this->submits_history);
                }
            }
            else {
                $this->set_internal_values($values_all);
            }
            $this->_stick_param($this->param_submits_history, serialize($this->submits_history));

        }
    }

    /**
    * главный метод
    */
    public function _run() {
        $this->initialize();

        // форма закончилась
        if (!sizeof($this->fields_modules)) {
            return $this->create_response($this->do_complete(
                $this->edit_id,
                $this->get_values(),
                $this->step,
                $this,
                $this->submits_history
            ));
        }
        // обработать нажатие сабмита
        else if ($this->submit) {
            // форма замещает себя сама
            return $this->create_response($this->do_proceed(
                $this->edit_id,
                $this->get_values(),
                $this->step,
                $this,
                $this->submits_history
            ));
        }
        // отрисовать шаг формы
        else {
             // не первый запуск и форма аяксовая
            if ($this->ajax && $this->walking) {
                _overwhelm_response(
                    $this->_tpl('form_html.tpl', $this->do_adjust_tpl_data($this->get_tpl_data()))->_get_result()
                );
            }
            // или первый запуск
            // или AJAX выключен
            else {
                return $this->_tpl('form.tpl', $this->do_adjust_tpl_data($this->get_tpl_data()));
            }
        }
    }

    /**
    * возвращает переменные для шаблоны
    */
    protected function get_tpl_data() {
        $tpl_data = parent::get_tpl_data();

        $tpl_data['ajax'] = $this->ajax;
        $tpl_data['ajax_handler'] = $this->ajax_handler;
        $tpl_data['current_step'] = $this->step;
        $tpl_data['max_step'] = $this->max_step;
        $tpl_data['prefix_form'] = $this->prefix_params;
        $tpl_data['walking'] = $this->walking;

        return $tpl_data;
    }

    protected function do_prepare($id, $values, $step, $form, $submits) {
        if (method_exists($this->data_source, $this->callback_prepare)) {
            return call_user_func_array(
                array($this->data_source, $this->callback_prepare), 
                array($id, $values, $step, $form, $submits)
            );
        }

        else if (method_exists($this->data_source, $this->callback_prepare)) {
            return call_user_func_array(
                array($this->data_source, $this->callback_prepare), 
                array($id, $values, $step, $form, $submits)
            );
        }
        else {
            return $this->prepare($id, $values, $step, $form, $submits);
        }
    }

    public function prepare($id, $values, $step, $form, $submits) {
        return $step;
    }

    /**
    * находит колбек что бы получить список полей для указанного шага; 
    * если полей нет, будет вызван встроенный метод finalize
    */
    protected function do_list_fields($id, $values, $step, $form, $submits) {
        // depricated
        if (method_exists($this->_get_holder(), $this->callback_list_edit_fields)) {
            $ret = call_user_func_array(
                array($this->_get_holder(), $this->callback_list_edit_fields), 
                array($id, $values, $step, $form, $submits)
            );
        }

        else if (method_exists($this->_get_holder(), $this->callback_list_fields)) {
            $ret = call_user_func_array(
                array($this->_get_holder(), $this->callback_list_fields), 
                array($id, $values, $step, $form, $submits)
            );
        }

        else if ($this->fields) {
            if ($step == 0) {
                $ret = $this->fields;
            }
            else {
                $ret = array();
            }
        }

        // depricated
        else if (method_exists($this->data_source, $this->callback_list_edit_fields)) {
            $ret = call_user_func_array(
                array($this->data_source, $this->callback_list_edit_fields), 
                array($id, $values, $step, $form, $submits)
            );
        }

        else if (method_exists($this->data_source, $this->callback_list_fields)) {
            $ret = call_user_func_array(
                array($this->data_source, $this->callback_list_fields), 
                array($id, $values, $step, $form, $submits)
            );
        }

        else {
            if ($step == 0) {
                $ret = $this->list_fields($id, $values, $step, $form, $submits);
            }
            else {
                $ret = array();
            }
        }
        return $ret;

    }

    public function list_fields($id, $values, $step, $form, $submits) {
        if ($this->data_source) {
            return $this->data_source->_get_fields($id);
        }
        else {
            _cc::fatal_error(_DEBUG_CC, 'Form error. There are no fields received for step #' . $step . ' inside modForm');
        }
    }

    protected function do_complete($id, $values, $step, $form, $submits) {
        // 2012-02-24 - 2012-04-27: before this date this code was commented out:
        // $values = $this->finalize($values);
        // and replaced with
        // $values = $this->convert_values_with_keys($this->finalize($this->external_values));
        // and this is correct way, since $this->finalize expects NOT CONVERTED value,
        // so:
        $values = $this->convert_values_with_keys($this->finalize($this->external_values));
        // eof

        if (is_callable($this->callback_complete)) {
            $res = call_user_func_array(
                $this->callback_complete, 
                array($id, $values, $step, $form, $submits)
            );
        }
        else {
            if (method_exists($this->_get_holder(), $this->callback_complete)) {
                $res = call_user_func_array(
                    array($this->_get_holder(), $this->callback_complete), 
                    array($id, $values, $step, $form, $submits)
                );
            }
            else if (method_exists($this->data_source, $this->callback_complete)) {
                $res = call_user_func_array(
                    array($this->data_source, $this->callback_complete), 
                    array($id, $values, $step, $form, $submits)
                );
            }
            else {
                $res = $this->complete($id, $values);
            }
        }

        // если при инициализации формы передать ключ link_ok, 
        //     то форма будет редиректить всю страницу на этот линк
        //     иначе она будет выводить результат работы метода complete
        if ($this->link_ok !== false) {
            $this->redirect($this->link_ok);
        }
        else {
            /*
             * 2012-03-02: пользы от этого минимум, а существовавшую логику ломает
            if (!$res) {
                _cc::fatal_error(_DEBUG_CC, 'Form error. Unable to redirect after "ok" submit inside modForm - you should define "link_ok" key during form initialization', 'error');
            }
            */
            return $res;
        }
    }

    public function complete($id, $values) {
        if ($this->data_source) {
            if ($id) {
                $this->data_source->_update($id, $values);
            }
            else {
                $this->data_source->_insert($values);
            }
        }
        /*
         * 2012-03-02: пользы от этого минимум, а существовавшую логику ломает
        else {
            _cc::fatal_error(_DEBUG_CC, 'Form error. Unable to complete inside modForm - you should define "data_source" key during form initialization OR create "complete" callback', 'error');
        }
        */
    }

    /**
    * находит колбек для обработки нажатой кнопки
    */
    protected function do_proceed($id, $values, $step, $form, $submits) {
        $submit = end($submits);
        if (is_callable($this->{'callback_proceed_' . $submit})) {
            return call_user_func_array(
                $this->{'callback_proceed_' . $submit},
                array(
                    $id,
                    $values,
                    $step,
                    $form,
                    $submits
                )
            );
        }
        else {
            $callback = $this->callback_proceed . '_' . $submit;
            if (method_exists($this->_get_holder(), $callback)) {
                return call_user_func_array(array($this->_get_holder(), $callback), array(
                    $id,
                    $values,
                    $step,
                    $form,
                    $submits
                ));
            }
            else if (method_exists($this->data_source, $callback)) {
                return call_user_func_array(array($this->data_source, $callback), array(
                    $id,
                    $values,
                    $step,
                    $form,
                    $submits
                ));
            }
            else if (method_exists($this, 'proceed_' . $submit)) {
                return call_user_func_array(array($this, 'proceed_' . $submit), array(
                    $id,
                    $values,
                    $step,
                    $form,
                    $submits
                ));
            }
            else {
                _cc::fatal_error(_DEBUG_CC, 'Form error. Callback not found: <b>' . $callback . '</b>');
            }
        }
    }

    /**
    * есть ли кнопка 'ok' в массиве кнопок для текущего шага
    */
    protected function check_ok_submit() {
        if (!in_array('ok', $this->submits, true) && !in_array('ok', array_keys($this->submits), true)) {
            array_unshift($this->submits, 'ok');
            _cc::debug_message(_DEBUG_CC, 'CC Warning.\'ok\' submit not found, added to the begin of submits list inside modForm', 'error');
        }
    }
    
    /**
    * Конвертирует массив вида 
    *   array('a[d]' => 'b', 'a[e]' => 'c') 
    * в массив 
    *   array('a' => array('d' => 'b', 'e' => 'c'))
    * 
    * @param mixed $values
    */
    protected function convert_values_with_keys($values) {
        $ret = array();
        foreach ($values as $name => $value) {
            if (preg_match('#^(\w+[\w\d]*)(\[.*)$#', $name, $found)) {
                $tmp_name = $found[1];
                $arr_key = str_replace('[', "['", str_replace(']', "']", $found[2]));
                eval('$ret[\'' . $tmp_name . '\']' . $arr_key . ' = $value;');
            }
            else {
                $ret[$name] = $value;
            }
        }
        return $ret;
    }

    /**
    * находит колбек для валидации указанного шага
    */
    protected function do_validate($id, $values, $step, $form, $submits_history) {
        $submit = end($submits_history);
        if ($submit == 'finish') {
            $submit = 'ok';
        }
        $callback = $this->callback_validate . '_' . $submit;
        if (method_exists($this->_get_holder(), $callback)) {
            return call_user_func_array(array($this->_get_holder(), $callback), array($id, $values, $step, $form, $submits_history));
        }
        else if (method_exists($this->_get_holder(), $this->callback_validate_edit_input)) {
            return call_user_func_array(array($this->_get_holder(), $this->callback_validate_edit_input), array($id, $values, $step, $form, $submits_history));
        }
        else if (method_exists($this->_get_holder(), $this->callback_validate_input)) {
            return call_user_func_array(array($this->_get_holder(), $this->callback_validate_input), array($id, $values, $step, $form, $submits_history));
        }
        else if ($this->data_source && method_exists($this->data_source, $callback)) {
            return call_user_func_array(array($this->data_source, $callback), array($id, $values, $step, $form, $submits_history));
        }
        else if ($this->data_source && method_exists($this->data_source, $this->callback_validate_edit_input)) {
            return call_user_func_array(array($this->data_source, $this->callback_validate_edit_input), array($id, $values, $step, $form, $submits_history));
        }
        else if ($this->data_source && method_exists($this->data_source, $this->callback_validate_input)) {
            return call_user_func_array(array($this->data_source, $this->callback_validate_input), array($id, $values, $step, $form, $submits_history));
        }
        else if ($submit == 'ok' && $this->data_source) {
            return $this->data_source->_validate_input($id, $values);
        }
        else if ($submit == 'ok') {
            $data_source = _cc::create_data_from_array(array('_fields' => $this->fields_plain), array(), '_fields');
            return $data_source->_validate_input($id, $values);
        }
    }

    /**
    * добавляет значения в массив $this->external_values
    * 
    * @param array $values
    */
    protected function append_external_values($values) {
        if (is_array($this->external_values)) {
            $this->external_values = array_merge($this->external_values, $values);
        } else {
            $this->external_values = $values;
        }
        return $this;
    }

    /**
    * добавляет значения в массив $this->internal_values
    * 
    * @param array $values
    */
    protected function append_internal_values($values) {
        if (is_array($this->internal_values)) {
            $this->internal_values = array_merge($this->internal_values, $values);
        } else {
            $this->internal_values = $values;
        }
        return $this;
    }

    /**
    * Запись значений в хранилище, принимает два параметра , действие (восстановить, записать) и тип 
    * хранилища в которое записываются данные. В случае действия восстановления, возвращаются все 
    * значения из хранилища данных.
    * 
    * @param string $type
    * @param string $action
    * @return array $values
    */
    protected function do_repository($type, $action) {
        $values = array();
        if ($action == 'reset') {
            /*if ($type == 'file') {
                unlink('_tmp/big_file.tmp');
            }*/            
            if ($type == 'internal') {
                $handle = opendir(_cc::get_tmp_path() . $this->internal_directory);
                while ($fn = readdir($handle)){
                    if ( $fn!='.' && $fn!='..'){
                        $time = explode('[!]', $fn);
                        if ((time()-$time[0]) > $this->timeout) {
                            unlink(_cc::get_tmp_path() . $this->internal_directory . $fn);
                        }
                    }
                }
            }
            if ($type == 'params') {
                $this->_stick_param('form_values', false);
            }
        }
        else if ($action == 'save') {
            if ($type == 'params') {
                $values = unserialize(_read_param('form_values'));
                if (is_array($values)) {
                    $this->internal_values = array_merge($values, $this->internal_values);
                }
                $this->_stick_param('form_values', serialize($this->internal_values));
            }
            if ($type == 'internal') {
                $fn = _cc::get_tmp_path() . $this->internal_directory . $this->internal_key . '.tmp';
                if (file_exists($fn) && filesize($fn) > 0) {
                    $content_old = unserialize(file_get_contents($fn));
                }
                else {
                    $content_old = false;
                }
                $fp = fopen($fn, 'w');
                if (is_array($content_old)){
                    $content = serialize(array_merge($content_old, $this->internal_values));
                } else {
                    $content = serialize($this->internal_values);
                }
                fwrite($fp, $content);
                fclose ($fp);
            }
        }
        else if ($action == 'restore') {
            if ($type == 'params') {
                $this->_read_sticky_param('form_values');
                $values = _read_param('form_values');
                if ($values){
                    $values = unserialize($values);
                }
            }
            if ($type == 'internal') {
                $fn = _cc::get_tmp_path() . $this->internal_directory . $this->internal_key . '.tmp';
                if (file_exists($fn) && filesize($fn) > 0) {
                    $values = unserialize(file_get_contents($fn));
                }
            }
            return $values;
        }
    }

    /**
    * Сбрасывает значения полей
    */
    // 2012-05-29 - логика перемещена в метод modFormBasic->create_fields, т.к. разделение
    // этой логики вроде не имеет смысла
    /*
    protected function reset_fields_modules() {
        $this->fields_modules = array();
        $this->fields_plain = array();
    }
    */

    /**
    * находит колбек для получения списка кнопок
    */
    protected function do_list_submits() {
        if (method_exists($this->_get_holder(), $this->callback_list_submits)) {
            return call_user_func_array(array($this->_get_holder(), $this->callback_list_submits), array(
                $this->edit_id,
                $this->get_values(),
                $this->step,
                $this,
                $this->submits_history 
            ));
        } 
        else if ($this->data_source && method_exists($this->data_source, $this->callback_list_submits )) {
            return call_user_func_array(array($this->data_source, $this->callback_list_submits), array(
                $this->edit_id,
                $this->get_values(),
                $this->step,
                $this,
                $this->submits_history 
            ));
        } 
        else {
            return $this->list_submits(
                $this->edit_id,
                $this->get_values(),
                $this->step,
                $this,
                $this->submits_history
            );
        }
    }

    /**
    * Создание списка кнопок по-умолчанию, форма попадает в него если в пользовательском
    * модуле нет колбека по созданию списка кнопок и если не проинициализирована 
    * внутренняя переменная $this->submits
    */
    public function list_submits($id, $values, $step, $form, $submits) {
        $res = array();
        if ($this->step > 0) {
            $res[] = 'back';
        }
        if ($this->max_step !== false) {
            $res[] = 'ok';
            if ($this->max_step != $this->step) {
                $res[] = 'finish';
            }
        }
        else {
            $res[] = 'ok';
        }
        $res[] = 'cancel';
        return $res;
    }
    
    /**
    * Реализация обработки кнопки "finish" по-умолчанию. Имитирует поведение как-будто на всех 
    * следующих шагах пользователь нажимал "ok", значения в поля подставляются по-умолчанию или 
    * если есть в хранилище то из хранилища
    */
    public function proceed_finish() {
        $this->submit = 'ok';
        $this->submits_history[sizeof($this->submits_history) - 1] = 'ok';
        if ($this->max_step) {
            // 2012-05-11 (see comment marked with same date above)
            // $this->walk($this->max_step - $this->step, 'ok');
            $this->walk($this->max_step - $this->step + 1);
        }
        else {
            // шагнем на заведомо большое число шагов, чем может быть
            // Не самое красивое решение, но можно утешиться тем, что в это место мы врядли попадем, 
            // т.к. кнопка finish рисуется автоматически только если программист указал max_step
            // если же программист принудительно пропишет в кнопку finish в списке кнопок, то на 
            // его совести будет и более красивая обработка
            $this->walk(10000);
        }
    }

    public function proceed_ok($id, $values, $step, $form, $submits) {
        if ($this->max_step !== false && $this->step == $this->max_step) {
            return $this->do_complete($id, $values, $step, $form, $submits);
        }
        else {
            $this->walk(1);
        }
    }

    public function proceed_cancel() {
        if ($this->link_cancel !== false) {
            $this->redirect($this->link_cancel);
        }
    }

    public function proceed_back() {
        $this->walk(-1);
    }

    /**
    * Метод, который обязательно должен вызываться для перехода на следующие/предыдущие шаги формы.
    * Принимает параметр насколько шагов шагнуть по форме, в случае если значение отрицательное, форма
    * шагает назад, на всех пропущенных шагах, будет иммитировано нажатие кнопки "ok".
    * 
    * @param int $step
    */
    public function walk($step, $submit = false) {
        $this->initialize();

        $this->do_repository($this->repository_type, 'save');

        $params = array(
            $this->param_step => $this->step + $step,
        );
        /*
         * 2012-05-11 (see comment marked with same date above)
        if ($submit) {
            $params[$this->prefix_params . $submit];
            $params[$this->param_form_sent] = 'on';
            // параметр был приклеен в самом начале, зачем он здесь?
            // $params[$this->param_walking] = 'on';
        }
        */
        $this->_redirect($params);
    }

    /**
    * метод, который обязательно должен вызываться один раз при завершении работы формы
    * критично для работы сложных полей, таких как uploadify и т.п.
    */
    public function finalize($values) {
        foreach ($this->fields_modules_all as $__id => $field_module) {
            // $values[$__id] = $field_module->finalize($values[$__id]);
            $name = $field_module->get_external_name();
            if ($name) {
                $values[$name] = $field_module->finalize($values[$name]);
            }
        }
        return $values;
    }

    public function redirect($link = array()) {
        $this->initialize();

        if ($this->ajax) {
            if (_is_ssl_request()) {
                $link_method = '_ssl_link';
            }
            else {
                $link_method = '_link';
            }
            _overwhelm_response(
                json_encode($array = array(
                    'type' => 'form_link',
                    // 'content' => is_array($link) ? $this->_get_holder()->_get_link($link) : $link
                    'content' => is_array($link) ? $this->$link_method($link) : $link
                )),
                'application/json'
            );
        }
        else {
            $this->_redirect($link);
        }
    }

    /**
    * вернет ассоциативный массив параметров, втавив которые в ссылку можно эмулировать поведение формы
    * 
    * @param mixed $params значения формы, которые нужно эмулировать
    * @param string $submit имя сабмита, нажатие на который надо эмулировать
    */
    public function imitate_submit_params($params, $submit = 'ok') {
        $this->initialize();

        $ret = array();
        // TODO: реализовать с использованием internal/external values, сейчас будет работать 
        // только при условии что (internal values == external value)
        foreach ($params as $key => $value) {
            $ret[$this->prefix_params . $key] = $value;
        }
        $ret[$this->prefix_params . $submit] = 'on';
        $ret[$this->param_form_sent] = 'on';
        $ret[$this->param_walking] = 'on';
        return $ret;
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
            $ret[$this->param_internal_key] = $this->internal_key;
            $ret[$this->param_step] = $this->step;
            $ret[$this->param_walking] = 'on';
            $ret[$this->param_submits_history] = serialize($this->submits_history);
            // такой тип репазитория никогда не использовался, сделан криво и вероятно его следует вообще удалить
            // $ret['form_values'] = $this->_read_sticky_param('form_values');
        }
        else {
            $ret[$this->param_internal_key] = false;
            $ret[$this->param_step] = false;
            $ret[$this->param_walking] = false;
            $ret[$this->param_submits_history] = false;
            // такой тип репазитория никогда не использовался, сделан криво и вероятно его следует вообще удалить
            // $ret['form_values'] = false;
        }
        return $ret;
    }

    /**
    * Задать значения полей
    * 
    * @param mixed $values_all
    */
    protected function set_internal_values($values_all = array()) {
        if ($this->fields_plain) {
            $id_counter = 0;
            // foreach ($this->fields_plain as $i => &$field) {
            foreach ($this->fields_plain as $__id => &$field) {
                // Создать внутренний идентификатор поля
                // $__id = $this->create_internal_field_id($i, isset($field['name']) ? $field['name'] : null, $id_counter);

                $field_name = $this->fields_modules[$__id]->get_external_name();
                if ($field_name) {
                    $value_present = false;
                    if (is_array($field_name)) {
                        $internal_value = array();
                        for ($j = 0; $j < sizeof($field_name); $j++) {
                            if (isset($values_all[$field_name[$j]])) {
                                $internal_value[$j] = $values_all[$field_name[$j]];
                                $value_present = true;
                            } else {
                                $internal_value[$j] = false;
                            }
                        }
                    }
                    else {
                        if (isset($values_all[$field_name])) {
                            $value_present = true;
                            $internal_value = $values_all[$field_name];
                        }
                    }
                    if ($value_present) {
                        $this->fields_modules[$__id]->set_internal_value($internal_value);
                    } else {
                        if (isset($field['value'])) {
                            $external_value = $field['value'];
                            unset($field['value']);
                        } else {
                            $external_value = false;
                        }
                        $this->fields_modules[$__id]->set_external_value($external_value);
                        /* 2010.08.13 moved to modFormBasic
                        */
                    }
                }
                $id_counter++;
            }

            $this->append_internal_values($this->get_internal_values());
        }
        return $this;
    }

    public function get_edit_id() {
        $this->initialize();

        return $this->edit_id;
    }

    /**
    * Получить внешние значения из полей текущего шага
    * Возвращает массив полученных значений
    * 
    * @return array $values
    */
    public function get_values($reset = false) {
        $this->initialize();

        if ($reset || !$this->external_values_converted) {
            $this->external_values_converted = $this->convert_values_with_keys($this->external_values);
        }
        return $this->external_values_converted;
    }

    public function get_step() {
        $this->initialize();

        return $this->step;
    }

    protected function set_step($step) {
        $this->step = $step;
        $this->_stick_param($this->param_step, $this->step);
    }

    protected function create_response($response) {
//        if ($this->ajax && !$this->ajax_handler) {
        if ($this->ajax && !$this->suppress_overwhelm) {
            _overwhelm_response($response);
        }
        else {
            return $response;
        }
    }
}

