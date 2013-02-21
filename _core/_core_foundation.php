<?php
/**
 * @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
 * @package CleverCore2
 */

/**
 * Base class for the most CleverCore2 objects
 */
abstract class _core_foundation {

    /**#@+
     * @ignore
     */

    /** @var array All parrents' names keeper */
    protected $__hierarchy = false;
    /** @var string All parents' names imploded with dot (.) */
    protected $__hierarchy_path = false;
    /** @var array All holders' names keeper */
    protected $__upline = false;
    /** @var string All holders' names imploded with dot (.) */
    protected $__upline_path = false;

    /** @var _core Link to dirrect holder */
    protected $__holder = false;
    /** @var array Associated array of all "sticked" parameters (these to be inherited by all objectes, created by this object*/
    protected $__sticked_vars = array();
    /** @var array Contains languages cache */
    protected $__languages_cache = array();
    /** @var string type of current object (_module, _data_source etc.) */
    protected $__type = false;

    /**#@-*/

    /**
    * Данное свойство имеет двойное назначение.
    * 1. при реализации объектов в него можно записывать 
    * имя, под которым хранятся настройки БД в глобальной конфигурации, если оно отличается от 
    * имени по-умолчанию (_db)
    * 2. в конструкторе переменная превращается ссылку на объект БД, имеющий доступ к указанной БД,
    * т.е. в процессе работы можно выполнять прямые запросы к БД использую это свойство (хотя это не
    * рекомендуется).
    * 
    * @var string|object
    */
    protected $_db = false;

    /**
     * Keeps upline chain correct. Initialize object with passed properties. Created DB connection if necessary.
     *
     * It is very important to call parent constructor each time you redeclrating before any manipulations:
     *      function __construct( $data = array() ) {
     *          parent :: __construct( $data );
     *
     *          // here some stuff possible
     *      }
     *
     * @param array $data List of properties to be initialized
     * @return object $this
     */
    public function __construct($data = array()) {
        if ((!isset($data['__holder']) || !$data['__holder']) && isset($GLOBALS['__bootstrap'])) {
            $data['__holder'] = $GLOBALS['__bootstrap'];
        }
        if (isset($data['__holder']) && $data['__holder']) {
            $this->_stick_vars($data['__holder']->_get_sticked_vars());
        }
        if ($data) {
            foreach ($data as $key => $value) {
                $this->$key = $value;
            }
        }
        if ($this->_db !== false) {
            $this->_db = _cc::create_db_engine($this->_db);
        }
        return $this;
    }

    /**
     * Links inexisting propertywith corresponding _data_source
     * @param mixed $name
     */
    public function __get($name) {
        return $this->$name = $this->_get_data_source($name);
    }

    /**
     * Создает и инициализирует переменными объект data
     *
     * @param string|array $data_name Имя класса, описывающего данные или же ассоциативный массив, описывающий данные
     * @param array $initial_data Данные, которыми инициализировать созданый объект
     * @param string $data_source_class Имя базового класса, который будет использован при создании data_source на лету
     * @return db_table (data_source) Объект data_source
     */
    public function _create_data_source($data, $initial_data = array(), $data_source_class = false) {
        return _cc::create_data_source($data, $initial_data, $data_source_class);
    }

    /**
    * будет произведена проверка, не создавался ли датасорц с тавим именем с помощью этого 
    * метода ранее, если создавался, то будет возвращена ссылка на уже созданный объект, 
    * если нет, то будет создан новый экземпляр и возвращена ссылка на него
    * 
    * @param mixed $name
    */
    public function _get_data_source($name) {
        if (!isset($GLOBALS['__cc_globals']['__data_sources'])) {
            $GLOBALS['__cc_globals']['__data_sources'] = array();
        }
        if (!isset($GLOBALS['__cc_globals']['__data_sources'][$name])) {
            $GLOBALS['__cc_globals']['__data_sources'][$name] = $this->_create_data_source($name);
        }
        return $GLOBALS['__cc_globals']['__data_sources'][$name];
    }

    public function _get_type() {
        return $this->__type;
    }

    /**
    * загружает языковык данные, учитывая иерархию родетелей объекта
    * 
    * @param mixed $lang_name
    */
    public function _load_language($lang_name = false) {
        if (!$lang_name) {
            $lang_name = _cc::get_config('_project', '_language');
        }
        if ($lang_name) {
            if (!isset($this->__languages_cache[$lang_name])) {
                $suffix = $this->__get_localization_suffix();

                $hierarchy = $this->_get_hierarchy();
                $lang_gather = array();
                for ($i = sizeof($hierarchy) - 1; $i >= 0; $i--) {
                    if (file_exists(__ENGINE_LOCALIZATION_PATH . $lang_name . '/' . $suffix . $hierarchy[$i] . '.php')) {
                        $lang = array();
                        include(__ENGINE_LOCALIZATION_PATH . $lang_name . '/' . $suffix . $hierarchy[$i] . '.php');
                        $lang_gather = array_merge($lang_gather, $lang);
                    }
                }
                for ($i = sizeof($hierarchy) - 1; $i >= 0; $i--) {
                    if (file_exists(__LOCALE_LOCALIZATION_PATH . $lang_name . '/' . $suffix . $hierarchy[$i] . '.php')) {
                        $lang = array();
                        include(__LOCALE_LOCALIZATION_PATH . $lang_name . '/' . $suffix . $hierarchy[$i] . '.php');
                        $lang_gather = array_merge($lang_gather, $lang);
                    }
                }
                $this->__languages_cache[$lang_name] = $lang_gather;
            }
            return $this->__languages_cache[$lang_name];
        }
        else {
            return array();
        }
    }

    protected function __get_localization_suffix() {
        return '';
    }

    /**
    * Возвращает иерархию данного объекта (имена классов), включая сам класс в обратном порядке (т.е. первый элемент - текущий класс, а последний - _core)
    * из иерархии удаляются классы _*_foundation
    *
    * @return array Имена всех классов иерархии
    */
    public function _get_hierarchy() {
        if ($this->__hierarchy == false) {
            $this->__hierarchy = array();
            $parent = get_class($this);
            do {
                if (!preg_match('#^_\w+_foundation$#', $parent)) {
                    $this->__hierarchy[] = $parent;
                }
                $parent = get_parent_class($parent);
            } while ($parent);
            $this->__hierarchy_path = implode('.', $this->__hierarchy);
        }
        return $this->__hierarchy;
    }

    /**
    * Возвращает иерархию данного объекта (имена классов), включая сам класс в обратном порядке, преобразованную в строку
    *
    * @return string путь, например _core_foundation/_core/_module_foundation/_module/backend/
    */
    public function _get_hierarchy_path() {
        if ( $this->__hierarchy == false ) {
            $this->_get_hierarchy();
        }
        return $this->__hierarchy_path;
    }

    /**
    * Возвращает список upline алиасов объектов, включая текущий объект; 
    *
    * @return array Имена всех классов из upline
    */
    public function _get_upline() {
        if ($this->__upline == false) {
            $this->__upline = array();
            $parent = $this;
            do {
                /* включает только модули
                while ($parent && $parent->_get_type() != '_module') {
                    $parent = $parent->_get_holder();
                }
                */
                array_unshift($this->__upline, $parent->_get_object_alias());
                $parent = $parent->_get_holder();
            } while ($parent);
            $this->__upline_path = implode('.', $this->__upline);
        }
        return $this->__upline;
    }

    /**
    * Возвращает алиас-имя данного объекта
    *
    * @return string алиас-имя данного объекта
    */
    public function _get_object_alias() {
        return get_class($this);
    }

    /**
    * Возвращает upline алиасов объектов, включая текущий объект, преобразованную в строку
    *
    * @return string путь, например backend/fronend/shop/data_editor/categories
    */
    public function _get_upline_path() {
        if ( $this->__upline == false ) {
            $this->_get_upline();
        }
        return $this->__upline_path;
    }

    /**
    * Заглушка
    * 
    * @return string Пустая строка
    */
    public function _get_result() {
        return '';
    }

    /**
    * Устанавливает объект, который будет считаться создателем текущего
    *
    * Вызывается единожды автоматически при создании каждого нового модуля
    * Вызывать вручную крайне нерекомендуется
    * 
    * @param object $holder
    */
    public function _set_holder( $holder ) {
        $this->__holder = $holder;
        return $this;
    }

    /**
    * Возвращает ссылку на объект, в котором был создан текущий ($this) объект
    * 
    * @return object
    */
    public function _get_holder() {
        return $this->__holder;
    }

    /**
    * Приклеивает к объекту переменную
    *
    * Все приклеенные переменные автоматически приклеиваются ко всем объектам, создаваемым из данного объекта
    * 
    * @param string $name Имя переменной
    * @param mixed $value Значение переменной
    * 
    * @return object this
    */
    public function _stick_var($name, $value) {
        $this->__sticked_vars[$name] = $value;
        $this->$name = $value;
        return $this;
    }

    /**
    * Приклеивает к объекту несколько переменных
    *
    * @param array $vars Ассоциативный массив пар имя => значение
    * 
    * @return object this
    */
    public function _stick_vars($vars = array()) {
        foreach ($vars as $key => $val) {
            $this->_stick_var($key, $val);
        }
        return $this;
    }

    /**
    * Возвращает все переменные, которые были приклеены к объекту
    * 
    * @return array
    */
    public function _get_sticked_vars() {
        return $this->__sticked_vars;
    }

    /**
    * Применяет функцию объекта к items
    * 
    * @param string $callback Функция
    * @param array $items Массив элементов
    * @param mixed $extra_param1 Дополнительный параметр 1
    * ...
    * @param mixed $extra_paramNN Дополнительный параметр NN
    * 
    * @return array Обработанный массив
    */
    public function _array_map() {
        $args = func_get_args();
        $callback = array_shift($args);
        $items = array_shift($args);
        foreach ($items as &$item) {
            $item = call_user_func_array(array($this, $callback), array_merge(array($item), $args));
        }
        return $items;
    }

    /**
    * Автоматический перевод класса в строку
    */
    public function __toString() {
        return $this->_get_result();
    }


    /**
    * Заглушка (для обратной совместимости)
    */
    public function _create_module($module_name, $data = array()) {
        return $this->_module($module_name, $data);
    }

    /**
    * Заглушка
    */
    public function _module($module_name, $data = array()) {
        _cc::fatal_error(_DEBUG_CC, 'This type of objects is not supposed to create modules');
    }

    public function _single($module_name) {
        // 2012-03-07 
        /*
        if (!isset($GLOBALS['__cc_globals']['__modules'])) {
            $GLOBALS['__cc_globals']['__modules'] = array();
        }
        if (!isset($GLOBALS['__cc_globals']['__modules'][$module_name])) {
            $GLOBALS['__cc_globals']['__modules'][$module_name] = $this->_module($module_name, $data);
        }
        return $GLOBALS['__cc_globals']['__modules'][$module_name];
        */
        return _cc::single_module($module_name);
    }

}

