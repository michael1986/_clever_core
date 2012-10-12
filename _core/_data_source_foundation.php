<?php
/**
 * @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
 * @package CleverCore2
 */

/**
 * Base class for data sources.
 */
abstract class _data_source_foundation extends _core {
    /**
    * Внутреннее хранилище данных, формат зависит от наследника
    */
    protected $__data = false;
    protected $__type = '_data_source';
    protected $__snapshots = array();
    protected $__anonymus_snapshot_index = 0;

    public function __construct($data = array()) {
        parent::__construct($data);
    }

    /**
    * Используется что бы сбросить объект к начальному состоянию
    * Начиная с 24.12.2009 не сбрасывает внуренний массив, содержащий 
    * данные, т.к. автоматически вызывается сразу после выборки данных методом __items
    *
    * Рекомендуется вызывать перед каждым использованием объекта что бы избежать накладок
    * Как правило, требуется расширение этой функции в потомке
    */
    public function _reset() {
        return $this;
    }

    /**
    * Сохраняет текущее состояние объекта в специальном хранилище
    * 
    * @param mixed $name имя ячейки в хранилище
    */
    public function _save_snapshot($name = false, $fields = array()) {
        if (!$name) {
            $name = '__anonymus_snapshot_' . $this->__anonymus_snapshot_index;
            $this->__anonymus_snapshot_index++;
        }
        $this->__snapshots[$name] = array();
        foreach ($fields as $field) {
            $this->__snapshots[$name][$field] = $this->$field;
        }
        return $this;
    }

    /**
    * Востанавливает состояние объекта из хранилища
    * 
    * @param mixed $name имя ячейки в хранилище
    */
    public function _restore_snapshot($name = false) {
        if (!$name) {
            $this->__anonymus_snapshot_index--;
            $name = '__anonymus_snapshot_' . $this->__anonymus_snapshot_index;
        }
        if (isset($this->__snapshots[$name])) {
            foreach ($this->__snapshots[$name] as $key => $value) {
                $this->$key = $value;
            }
        }
        return $this;
    }

    /**
    * форматирование данных перед выдачей их запросившему объекту
    * 
    * @param mixed $in
    * @param mixed $mode
    */
    public function _adjust_output($in, $mode = false) {
        return $in;
    }

    /**
    * Поиск, подключение и создание объекта модуля
    * 
    * (нет!) Держателем модуля становится не сам data_source, а модуль, который держит этот data_source
    * 
    * @param string $module_name Название модуля
    * @param array $data  Ассоциативный массив параметров, которые будут переданы модулю при создании
    * @return object $module Объект модуля
    */
    /*
    убрано 2012-03-07
    function _module($module_name, $data = array()) {
        return _cc::create_module($module_name, $data, $this);
    }
    */

    /**
    * Для обратной совместимости
    * 
    * @param string $module_name
    * @param array $data
    */
    /*
    убрано 2012-03-07
    function _create_module($module_name, $data = array()) {
        return _cc::create_module($module_name, $data, $this);
    }
    */

    /*
    public function _get_sticked_params($values = true) {
        return $this->_get_holder()->_get_sticked_params($values);
    }
    */

    /**
    * Поиск, подключение, создание и запуск объекта модуля
    * 
    * (нет!) Держателем модуля становится не сам data_source, а модуль, который держит этот data_source
    * 
    * @param string $module_name Название модуля
    * @param array $data Ассоциативный массив параметров, которые будут переданы модулю при создании
    * @return string $html Результат работы модуля
    */
    /*
    убрано 2012-03-07
    function _execute_module($module_name, $data = array()) {
        return _cc::execute_module($module_name, $data, $this);
        // return $this->_get_holder()->_execute_module($module_name, $data);
    }
    */

    /**
    * Создает и инициализирует переменными объект data
    * 
    * (нет!) Держателем объекта становится не сам data_source, а модуль, который держит этот data_source
    * 
    * @param string $data_name Имя класса, описывающего данные
    * @param array $initial_data Данные, которыми инициализировать созданый объект
    * @return data_source Объект data_source
    */
    /* 2012-04-25 перенесено в _core_foundation
    public function _create_data_source($data, $initial_data = array(), $data_class = false) {
        // return $this->_get_holder()->_create_data_source($data, $initial_data, $data_class);
        if (!$data_class) {
            $data_class = __DEFAULT_DATA_SOURCE_CLASS;
        }
        return _cc::create_data_from_class($data, $this, $initial_data, $data_class);
    }
    */

    /**
    * Вызывает соответсвующий метод модуля-держатедя и возвращает результат его работы
    * 
    * @param array $params
    * @param mixed $rout_rule
    * @return string ссылка
    */
    /*
    public function _link($params = array(), $rout_rule = false) {
        return $this->_get_holder()->_link($params, $rout_rule);
    }
    */

    /**
    * Depricated. Для обратной совместимости
    */
    /*
    public function _get_link($params = array(), $rout_rule = false) {
        return $this->_get_holder()->_link($params, $rout_rule);
    }
    */

    /**
    * Вызывает соответсвующий метод модуля-держатедя и возвращает результат его работы
    */
    /*
    function _html_link($params = array(), $url = false) {
        return $this->_get_holder()->_html_link($params, $url);
    }
    */

    /**
    * Depricated. Для обратной совместимости
    */
    /*
    function _get_html_link($params = array(), $url = false) {
        return $this->_get_holder()->_html_link($params, $url);
    }
    */

    /**
    * Метод вызывается автоматически, если ядро не смогло найти класс, описывающий запрошеный источник данных
    * 
    * @param mixed $class_name имя класса
    */
    public static function __autocreate($class_name) {
        if ($fp = @fopen(__LOCALE_DATA_SOURCES_PATH . $class_name . '.php', 'w')) {
            $code = "<?php\n" . _cc::create_tpl_from_string(file_get_contents(__ENGINE_DATA_SOURCE_TPLS_PATH, '_data_source_foundation.php.tpl'), array(
                'class_name' => $class_name
            ))->_get_result();
            fwrite($fp, $code);
            fclose($fp);
        } else {
            _cc::debug_message(_DEBUG_CC, 'CC error. Unable to auto create data_source <b>' . $class_name. '</b>', 'error');
        }
    }

    protected function __get_localization_suffix() {
        return __DATA_SOURCES_DIR;
    }

}


