<?php
/**
 * @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
 * @package modDataGrid
 */

/**
* Модуль используется, когда нужно выводить список однотипных данных - список новостей, продуктов, 
* чего угодно, и совершать с отдельными строками данных, либо же группой строк какие-либо действия: редактировать,
* удалять, добавлять и т.п. - практически что угодно.
* 
* Модуль позволяет производить следующие действия над данными автоматически:
*   - просмотр:
*       - автоматическая выборка данных из указанного data_source с заданными параметрами
*       - API на основе колбеков, для выборки произвольных данных в создающем модуле (_holder)
*       - размещение данных в несколько столбцов (слева на право или сверху вниз)
*       - размещение данных на нескольких страницах (пагинация)
*       - фильтрация данных на лету по заданным пользователем параметрам (с помощью стандартного плагина)
*       - сортировка данных на лету по заданным пользователем столбцам (с помощью стандартного плагина)
*       - детальная информация о выбранной строке
*   - манипуляции:
*       - добавление
*       - редактирование одиночное (TODO: групповое)
*       - удаление (одиночное или групповое)
*       - API на основе колбеков, для создания пользователькой функциональности
*   - JS и AJAX
*       - TODO: работа модуля может происходить как через браузер, т.к. и с помощью AJAX
*       - TODO: любое действие может происходить как в контейнере списка данных, так и в произвольном
*           контейнере (например, в модальном JS окне или в специальной выделенной на странице области)
* 
* Внутри JS можно обращаться к главному контейнеру, содержащему данный data_grig используя ID '#{$data_grid_prefix}DataGrid' (в случае использования стандартных шаблонов)
*/
class modDataGrid extends _module {

    protected $language = false;
    /** Заголовок списка данных */
    protected $title = false;
    /** Заголовок формы добавления */
    protected $title_add = false;
    /** Заголовок формы редактирования */
    protected $title_edit = false;
    /** @var _db_table Основной датасорц */
    protected $data_source  = false;

    protected $prefix_params = '';
    protected $prefix_callbacks = '';
    protected $prefix_cookies = '';

    protected $param_mode_default = 'mode';
    protected $param_mode = false;
    protected $param_action_default = 'act';
    protected $param_action = false;
    protected $param_page_default = 'dg_page';
    protected $param_page = false;
    protected $param_id_default = 'id';
    protected $param_id = false;

    protected $use_cookie_page = false;
    /**
    * Нельзя использовать просто _is_ajax_request(), потому что грид может быть частью большого
    * шаблона, запрашиваемого через ajax
    * 
    * @var mixed
    */
    public $param_is_ajax_request_default = 'is_ajax';
    public $param_is_ajax_request = false;

    protected $cookie_param_copycut_id = 'copycut_id';
    protected $cookie_param_copycut_type = 'copycut_type';

    protected $form_add_templates_dir = 'general/modFormAdd';
    protected $form_edit_templates_dir = 'general/modFormEdit';
    protected $form_popup_templates_dir = 'general/modFormPopup';
    protected $module_form = 'general/modForm';

    protected $callbacks = array(
        /**
        * Имя колбека, запускаемого когда нужно проверить имеет ли текущий пользователь доступ к 
        * конкретному контролю - action или mode; для mode вызывается только в режиме details
        * Колбек ищется сначала в модуле, потом в датасорце
        */
        'check_access',
        /**
        * Имя колбека, запускаемого когда пользователь нажал какую-то кнопку
        * Колбек ищется только модуле
        */
        'proceed_action',
        /**
        * Имя колбека, возвращает количество найденых данных
        * Колбек ищется сначала в модуле, потом в датасорце
        */
        'count',
        /**
        * Имя колбека, возвращает найденые данные (в промежутке, заданном пагинацией)
        * Колбек ищется сначала в модуле, потом в датасорце
        */
        'rows',
        /**
        * Имя колбека, через него прогоняется массив данных для шаблона сетки перед выводом
        * Колбек ищется только модуле
        */
        'adjust_grid_tpl_data',
        /**
        * Имя колбека, через него прогоняется массив данных для шаблона детальной информации перед выводом
        * Колбек ищется только модуле
        */
        'adjust_details_tpl_data',
        'adjust_add_tpl_data',
        'adjust_edit_tpl_data',
        /**
        * Имя колбека, возвращает одну строку данных (по ID)
        * Колбек ищется сначала в модуле, потом в датасорце
        */
        'row',
        /**
        * Имя колбека, запускаемого перед выводом данных пользователю
        * Колбек ищется сначала в модуле, потом в датасорце
        */
        'adjust_output',
        /**
        * Имя колбека, запускаемого когда нужно добавить хлебную крошку
        * Колбек ищется только модуле
        */
        'create_breadcrumbs',
        /**
        * имя колбека, обрабатывающего кнопку "Add"
        * Колбек ищется только модуле
        */
        'proceed_add',
        /**
        * Имя колбека, возвращающего список полей для добавления/редактирования (имеет более низкий приоритет чем предыдущие колбеки)
        * Колбек ищется сначала в модуле, потом в датасорце
        */
        'list_fields',
        /**
        * Имя колбека, проверяющего данные перед добавлением/обновлением в БД, имеет более низкий приоритет чем предыдущие
        * Колбек ищется сначала в модуле, потом в датасорце
        */
        'validate_input',
        /**
        * Имя колбека, сохраняющего данные в БД
        * Колбек ищется сначала в модуле, потом в датасорце
        */
        'insert',
        /**
        * Имя колбека, обрабатывающего кнопку "Edit"
        * Колбек ищется только модуле
        */
        'proceed_edit',
        /**
        * Имя колбека, обновляющего данные в БД
        * Колбек ищется сначала в модуле, потом в датасорце
        */
        'update',
        /**
        * Имя колбека, обрабатывающего кнопку "Delete"
        * Колбек ищется только модуле
        */
        'proceed_delete',
        /**
        * Имя колбека, удаляющего данные из БД
        * Колбек ищется сначала в модуле, потом в датасорце
        */
        'delete',
        /**
        * Имя колбека, обрабатывающего кнопку "Move up"
        * Колбек ищется только модуле
        */
        'proceed_move_up',
        /**
        * Имя колбека, перемещающего данные вверх
        * Колбек ищется сначала в модуле, потом в датасорце
        */
        'move_up',
        /**
        * Имя колбека, обрабатывающего кнопку "Move down"
        * Колбек ищется только модуле
        */
        'proceed_move_down',
        /**
        * Имя колбека, перемещающего данные вниз
        * Колбек ищется сначала в модуле, потом в датасорце
        */
        'move_down',
        /**
        * Имя колбека, обрабатывающего кнопку "Activate"
        * Колбек ищется только модуле
        */
        'proceed_activate',
        /**
        * Имя колбека, активирующего строку данных
        * Колбек ищется сначала в модуле, потом в датасорце
        */
        'activate',
        /**
        * Имя колбека, обрабатывающего кнопку "Deactivate"
        * Колбек ищется только модуле
        */
        'proceed_deactivate',
        /**
        * Имя колбека, деактивирующего строку данных
        * Колбек ищется сначала в модуле, потом в датасорце
        */
        'deactivate',
        /**
        * Имя колбека, обрабатывающего кнопку "Cut"
        * Колбек ищется только модуле
        */
        'proceed_cut',
        /**
        * Имя колбека, сохраняющего список ID для последующей вырезки
        * Колбек ищется сначала в модуле, потом в датасорце
        */
        'cut',
        /**
        * 
        */
        'proceed_cut_cancel',
        /**
        * 
        */
        'cut_cancel',
        /**
        * 
        */
        'proceed_reset_clipboard',
        /**
        * 
        */
        'reset_clipboard',
        /**
        * Имя колбека, обрабатывающего кнопку "Paste"
        * Колбек ищется только модуле
        */
        'proceed_paste',
        /**
        * Имя колбека, копирующего или переносящего строки с сохраненными ранее ID
        * Колбек ищется сначала в модуле, потом в датасорце
        */
        'paste',
        /**
        * Имя колбека, обрабатывающего кнопку "Paste before"
        * Колбек ищется только модуле
        */
        'proceed_paste_before',
        /**
        * Имя колбека, копирующего или переносящего строки с сохраненными ранее ID, ставит из перед выбранной строкой
        * Колбек ищется сначала в модуле, потом в датасорце
        */
        'paste_before',
        /**
        * Имя колбека, обрабатывающего кнопку "Paste after"
        * Колбек ищется только модуле
        */
        'proceed_paste_after',
        /**
        * Имя колбека, копирующего или переносящего строки с сохраненными ранее ID, ставит из после выбранной строки
        * Колбек ищется сначала в модуле, потом в датасорце
        */
        'paste_after',
        /**
        * 
        */
        'complete'
    );

    /**
    * initialization sample: 'pagination_settings' => array('ajax' => true)
    * 
    * @var mixed
    */
    protected $pagination_settings = array();
/*
    protected $modes = array(
        'grid' => array(
            'popup' => false
        ),
        'details' => array(
            'popup' => true
        )
    );
*/
    protected $default_mode = 'grid';
    protected $mode = false;
    protected $action = false;
    protected $id = false;
    protected $page = false;
    protected $per_page = 0;
    protected $per_row = 1;
    protected $count = false;
    protected $data_rows = false;
    protected $data_row = false;

    protected $order = array();
    protected $group = false;
    /** 
    * Хранит в себе гибкие условия, совместимые с _db_table, эти условия используются только для 
    * фильтрации списка
    * 
    * @var array 
    */
    protected $filter_where = array();
    protected $filter_having = array();
    /** 
    * Хранит в себе только строгие условия (например, 'a' => 'b'), помимо фильтрации списка, эти 
    * условия используются для перемщения данных и т.п. - т.е. как бы разбивают данные на 
    * автономные группы
    * 
    * @var array 
    */
    protected $columns = false;
    protected $where = array();
    protected $having = array();
    protected $join = array();
    protected $fields = array();

    /**
    * кеширует выборку из БД, используется в row, что бы к нему можно было обращаться из разных 
    * колбеков, не делая повторный запрос
    */
    protected $cache = array();
    /**
    * Хранить кешированные exclude_fields
    * 
    * @var array()
    */
    protected $exclude_fields = false;
    /**
    * сколько эдементов пагинации отображать до появления фрагмента skip
    * (в шаблонах по-умолчанию это '...'),
    * например:
    * если это свойство установить в 2, нужно вывести 100 страниц и мы находимся на 34 странице,
    * то пагинация будет выглядеть следующим образом
    * 
    * 1 2 ... 32 33 34 35 36 ... 99 100
    */
    protected $pagination_display_cnt = 3;

    protected $orientation = 'horizontal';
    protected $orientations = array('horizontal', 'h', 'vertical', 'v');

    protected $plugins = array();
    protected $plugins_modules = array();
    protected $plugins_path = 'general/dg_plugins/';

    /**
    * initialization sample: 'controls_settings' => array('popup' => true)
    * 
    * @var mixed
    */
    protected $controls_settings = array();
    /**
    * Массив, описывающий контроли по-умолчанию
    * Каждый ключ - название контроля, каждое значение - ассоциативный массив, описывающий контроль
    * При описании контроля можно использовать следующие ключи:
    *   - modes: массив, в котором перечислены все режимы, в которых присутсвует данный контроль;
    *   может принимать занчения всех доступных режимов (для modDataGrid это 'grid' и 'details')
    *   - applicable: массив, в котором перечислено, в каких случаях (types) может применяться данный контроль;
    *   может принимать следуюшие значения
    *       - row: для одиночной строки (например, редактирование)
    *       - rows: для нескольких выбраных строк (например, удаление)
    *       - other: все другие случаи (напимер, добавление)
    *   - is_mode: (не рекомендуется использовать в кастомных контролях) задает, как будет действовать 
    *   данный контроль - переключать ли modDataGrid в другой режим или же просто вызывать действие 
    *   в текущем режиме
    * 
    * Для каждой строки данных и для общего шаблона data_list.tpl будет создана переменная $controls (объект tpl_engine),
    * содержащая ассоциативный массив объектов tpl_engine, по одному на каждый контроль; 
    *   - что бы отобразить панель контролей по-умолчанию достаточно в шаблоне вызвать <?php echo $controls ?> или {$controls}
    *   - что бы отобразать отдельный контроль нужно вызвать <?php echo $controls->_vars['add'] ?> или {$controls.add}
    *   - что бы получить отдельную ссылку нужно вызвать <?php echo $controls->_vars['add']->_vars['link'] ?> или {$controls.add.link}
    * 
    * Имена шаблонов, отвечающие за внешний вид панелей по-умолчанию, строятся по следующей схеме:
    * controls.tpl (раньше было controls_[mode]_[apply_to].tpl, теперь в единый шаблон поступают дополнительные
    *                       переменные apply_to и mode)
    * 
    * Имена шаблонов, отвечающие за внешний вид отдельных контролей, строятся по следующей схеме:
    * control_[name].tpl (раньше было control_[name]_[mode]_[apply_to].tpl, теперь в единый шаблон поступают дополнительные
    *                       переменные apply_to и mode)
    * например: control_add.tpl, control_delete.tpl
    * 
    * @var array
    */
    protected $controls_default = array(
        'activate' => array(
            'modes' => array('grid', 'details'),
            'applicable' => array('row', 'rows'),
            'title' => 'CTRL_ACTIVATE'
        ),
        'deactivate' => array(
            'modes' => array('grid', 'details'),
            'applicable' => array('row', 'rows'),
            'title' => 'CTRL_DEACTIVATE'
        ),
        'details' => array(
            'is_mode' => true,
            'modes' => array('grid'),
            'applicable' => array('row'),
            'title' => 'CTRL_DETAILS'
        ),
        'edit' => array(
            'modes' => array('grid', 'details'),
            'applicable' => array('row'),
            'title' => 'CTRL_EDIT'
        ),
        'delete' => array(
            'modes' => array('grid', 'details'),
            'applicable' => array('row', 'rows'),
            'title' => 'CTRL_DELETE'
        ),
        'add' => array(
            'modes' => array('grid'),
            'applicable' => array('other'),
            'title' => 'CTRL_ADD'
        ),
        'cut' => array(
            'modes' => array('grid'),
            'applicable' => array('row', 'rows'),
            'title' => 'CTRL_CUT'
        ),
        'cut_cancel' => array(
            'modes' => array('grid'),
            'applicable' => array('row', 'rows'),
            'title' => 'CTRL_CUT_CANCEL'
        ),
        'reset_clipboard' => array(
            'modes' => array('grid'),
            'applicable' => array('other'),
            'title' => 'CTRL_RESET_CLIPBOARD'
        ),
        'copy' => array(
            'modes' => array('grid'),
            'applicable' => array('row', 'rows'),
            'title' => 'CTRL_COPY'
        ),
        'paste' => array(
            'modes' => array('grid'),
            'applicable' => array('other'),
            'title' => 'CTRL_PASTE'
        ),
        'paste_before' => array(
            'modes' => array('grid'),
            'applicable' => array('row'),
            'title' => 'CTRL_PASTE_BEFORE'
        ),
        'paste_after' => array(
            'modes' => array('grid'),
            'applicable' => array('row'),
            'title' => 'CTRL_PASTE_AFTER'
        ),
        'move_up' => array(
            'modes' => array('grid'),
            'applicable' => array('row'),
            'title' => 'CTRL_MOVE_UP'
        ),
        'move_down' => array(
            'modes' => array('grid'),
            'applicable' => array('row'),
            'title' => 'CTRL_MOVE_DOWN'
        ),
        // TODO: этот линк по идее ведет обратно к списку (в противовес details)
        'grid' => array(
            'is_mode' => true,
            'modes' => array('details'),
            'applicable' => array('row'),
            'title' => 'CTRL_GRID'
        )
    );

    /**
    * Контроли, определенные пользователем
    */
    protected $controls = array();

    protected $max_step = 0;
    protected $max_step_add = false;
    protected $max_step_edit = false;

    protected $lang_name = false;
    protected $lang = array();
    /**
    * @ignore
    * 
    * @var boolean была ли уже выполнена инициализация?
    */
    protected $initialized = false;
    /**
    * @ignore
    * 
    * @var boolean были ли уже созданы хлебные крошки?
    */
    protected $breadcrumbs_generated = false;

    /**
     * @var boolean подключать ли JS? по-умолчанию подключается всегда ТОЛЬКО первый раз на странице, однако иногда
     * бывает нужно явно запретить подключение JS даже первый раз - в том случае если модуль загружается на страницу
     * с помощью AJAX и на этой странице JS уже был подключен
     */
    protected $include_js = true;

    /**
    * @ignore
    * 
    * Инициализация, заполняет все временные внутренние переменные, вызывается автоматически
    * 
    * Делает доступными все данные для гетров (getters): id, action, mode и т.д.
    */
    protected function initialize() {
        if (!$this->initialized) {
            $this->initialized = true;

            $this->lang = $this->_load_language($this->lang_name);

            if (!is_object($this->data_source)) {
                if (!$this->data_source) {
                    _cc::fatal_error(_DEBUG_CC, 'CC Error. It is necessary to initialize modDataGrid with correct data_source name or object');
                } else {
                    $this->data_source = $this->_create_data_source($this->data_source);
                }
            }

            // добавляем префикс ко всем параметрам, которые будем использовать
            if (!$this->param_mode) {
                $this->param_mode = $this->prefix_params . $this->param_mode_default;
            }
            if (!$this->param_action) {
                $this->param_action = $this->prefix_params . $this->param_action_default;
            }
            if (!$this->param_id) {
                $this->param_id = $this->prefix_params . $this->param_id_default;
            }
            if (!$this->param_page) {
                $this->param_page = $this->prefix_params . $this->param_page_default;
            }
            if (!$this->param_is_ajax_request) {
                $this->param_is_ajax_request = $this->prefix_params . $this->param_is_ajax_request_default;
            }

            $this->cookie_param_copycut_id = $this->prefix_cookies . $this->cookie_param_copycut_id;
            $this->cookie_param_copycut_type = $this->prefix_cookies . $this->cookie_param_copycut_type;

            // добавляем префикс ко всем колбекам, которые будем использовать
            foreach ($this->callbacks as $callback) {
                $parameter = 'callback_' . $callback;
                if (!isset($this->{$parameter})) {
                    $this->{$parameter} = $this->prefix_callbacks . $callback;
                }
            }

            if (!$this->order) {
                $this->order = $this->data_source->_get_default_order();
            }
            else if (!is_array($this->order)) {
                $this->order = array($this->order);
            }

            /*
            * Создать объекты расширений
            * Они создаются умышленно ДО того как modDataGrid приклеит к себе 
            * параметры, для того, что бы расширения сами приклеивали параметры modDataGrid 
            * при необходимости, например:
            * 
            *   class modDGPluginTest extends modDGPluginBase {
            *       public function __construct($data = array()) {
            *           parent::__construct($data);
            *           ...
            *           $this->_stick_params($this->_get_holder()->get_grid_params());
            *           ...
            *       }
            *       ...
            *   }
            */
            if (!$this->plugins) {
                $this->plugins = array();
            }
            foreach ($this->plugins as $key => $value) {
                if (is_numeric($key)) {
                    $this->plugins_modules[$value] = $this->_module(
                        $this->plugins_path . 'modDGPlugin' . implode('', array_map('ucfirst', explode('_', $value))),
                        array(
                            'prefix_callbacks' => $this->prefix_callbacks,
                            'prefix_params' => $this->prefix_params
                        )
                    );
                }
                else {
                    $this->plugins_modules[$key] = $this->_module(
                        $this->plugins_path . 'modDGPlugin' . implode('', array_map('ucfirst', explode('_', $key))), 
                        array_merge($value, array(
                            'prefix_callbacks' => $this->prefix_callbacks,
                            'prefix_params' => $this->prefix_params
                        ))
                    );
                }
            }

            // заполнение controls
            if (!$this->controls) {
                $this->controls = array();
            }
            if (!is_array($this->controls)) {
                $this->controls = array();
                _cc::debug_message(_DEBUG_CC, 'CC Warning. "controls" property should be an array for the modDataGrid', 'error');
            }
            $controls_useable = array();
            foreach ($this->controls as $control => $description) {
                if (is_numeric($control)) {
                    $control = $description;
                    $description = array();
                }
                if (isset($this->controls_default[$control])) {
                    $controls_useable[$control] = array_merge($this->controls_default[$control], $this->controls_settings, $description);
                }
                else {
                    $controls_useable[$control] = array_merge($this->controls_settings, $description);
                }
            }
            $this->controls = array();
            $action_names = array(); // we will use it later in this method
            $mode_names = array($this->default_mode); // we will use it later in this method
            foreach ($controls_useable as $key => $description) {
                $description['action'] = $key;
                if (!isset($description['ajax'])) {
                    $description['ajax'] = false;
                }
                if (!isset($description['popup'])) {
                    $description['popup'] = false;
                }
                if (!isset($description['is_mode'])) {
                    $description['is_mode'] = false;
                }
                if (!isset($description['modes'])) {
                    $description['modes'] = array('grid', 'details');
                }
                if (!isset($description['applicable'])) {
                    $description['applicable'] = array('row');
                }
                $this->controls[$key] = $description;
                if ($description['is_mode']) {
                    if ($key != $this->default_mode) {
                        $mode_names[] = $key;
                    }
                }
                else {
                    $action_names[] = $key;
                }
            }

            // set templates_dir for forms
            /*
            if (!$this->form_add_templates_dir && $this->form_templates_dir) {
                $this->form_add_templates_dir = $this->form_templates_dir;
            }
            if (!$this->form_edit_templates_dir && $this->form_templates_dir) {
                $this->form_edit_templates_dir = $this->form_templates_dir;
            }
            */
            /*
            if (isset($this->controls['add'])) {
                if (!$this->form_add_templates_dir) {
                    if ($this->form_templates_dir) {
                        $this->form_add_templates_dir = $this->form_templates_dir;
                    }
                    else {
                        if ($this->controls['add']['popup']) {
                            $this->form_add_templates_dir = $this->default_form_popup_templates_dir;
                        }
                        else {
                            $this->form_add_templates_dir = $this->default_form_templates_dir;
                        }
                    }
                }
            }
            if (isset($this->controls['edit'])) {
                if (!$this->form_edit_templates_dir) {
                    if ($this->form_templates_dir) {
                        $this->form_edit_templates_dir = $this->form_templates_dir;
                    }
                    else {
                        if ($this->controls['edit']['popup']) {
                            $this->form_edit_templates_dir = $this->default_form_templates_dir;
                        }
                        else {
                            $this->form_edit_templates_dir = $this->default_form_popup_templates_dir;
                        }
                    }
                }
            }
            */

            if ($this->join && !is_array($this->join)) {
                $this->join = array($this->join);
            }

            // adjust join, where, order
            foreach ($this->plugins_modules as $module) {
                $this->join = $module->adjust_join($this->join);
                $this->where = $module->adjust_where($this->where);
                $this->having = $module->adjust_having($this->having);
                $this->filter_where = $module->adjust_filter_where($this->filter_where);
                $this->filter_having = $module->adjust_filter_having($this->filter_having);
                $this->order = $module->adjust_order($this->order);
                $this->group = $module->adjust_group($this->group);
            }

            // - проверка параметров -----------------------------------------------------------------------------------

            $bad_mode = false;
            $bad_action = false;
            $bad_id = false;

            // проверяем $this->mode (инициализированный или переданный через GET/POST параметры)
            if ($this->mode === false) {
                $this->mode = _read_param($this->param_mode);
            }
            if ($this->mode && !in_array($this->mode, $mode_names)) {
                // BAD parameter passed (mode)
                $keep_test_mode = $this->mode;
                $this->mode = false;
                $bad_mode = true;
            }
            if (!$this->mode) {
                $this->mode = $this->default_mode;
            }

            // проверяем $this->action (инициализированный или переданный через GET/POST параметры)
            if ($this->action === false) {
                $this->action = _read_param($this->param_action);
            }
            if ($this->action && !in_array($this->action, $action_names)) {
                // BAD parameter passed (action)

                $keep_test_action = $this->action;
                $bad_action = true;

                $this->action = false;
            }

            if ($this->id === false) {
                $this->id = _read_param($this->param_id);
                if ($this->id !== false) {
                    $this->id = array_map('trim', explode(',', $this->id));
                }
            }

            // проверить ID на существование в разрешенном множестве строк
            /*
             * перенесено туда же где check_access
             */
            /*
            if ($this->id) {
                if (!is_array($this->id)) {
                    $this->id = array($this->id);
                }
                $tmp_id = $this->id;
                $this->id = array();
                foreach ($tmp_id as $id) {
                    if ($this->do_row($id)) {
                        $this->id[] = $id;
                    }
                }
                if (!sizeof($this->id)) {
                    // BAD parameter passed (id)
                    $bad_id = true;
                }
            }
            */

            // проверить $this->mode (details) и $this->id на соответвуие друг другу
            if ($this->mode == 'details') {
                if (!$this->id) {
                    // BAD parameter passed (mode)
                    $keep_test_mode = $this->mode;
                    $this->mode = 'grid';
                    $bad_mode = true;
                }
            }

            // проверить $this->action и $this->id на соответвуие друг другу
            if ($this->action) {
                if (
                    in_array('other', $this->controls[$this->action]['applicable']) && 
                    !in_array('row', $this->controls[$this->action]['applicable']) &&
                    !in_array('rows', $this->controls[$this->action]['applicable']) &&
                    $this->id
                ) {
                    // BAD parameter passed (id)
                    $keep_test_action = $this->action;
                    $this->action = false;
                    // $this->id = false;
                    // $bad_id = true;
                    $bad_action = true;
                }
                else if (
                    !in_array('other', $this->controls[$this->action]['applicable']) && (
                        in_array('row', $this->controls[$this->action]['applicable']) ||
                        in_array('rows', $this->controls[$this->action]['applicable'])
                    ) &&
                    !$this->id
                ) {
                    // BAD parameter passed (action)
                    $keep_test_action = $this->action;
                    $this->action = false;
                    $bad_action = true;
                }
            }
            // проверить $this->mode (grid) и $this->id на соответвие друг другу (только если нет action)
            else if ($this->mode == 'grid' && $this->id) {
                // эта ветка исполняется только в случае если кто-то фальсифицирует параметры
                // или происходит конфлик параметров (отсутсвует префикс)
                // поэтому ниже канитель с флагами

                // BAD parameter passed (id)
                $keep_test_mode = $this->mode;
                $this->id = false;
                // $bad_id = true;
                $bad_mode = true;
            }

            // проверить режим через check_access
            if ($this->mode == 'details') {
                // $tmp_id = $this->id;
                // $this->id = array();
                // foreach ($tmp_id as $id) {
                $mode_id = array();
                foreach ($this->id as $id) {
                    if ($this->do_row($id) && $this->do_check_access($id, $this->mode)) {
                        // $this->id[] = $id;
                        $mode_id[] = $id;
                    }
                }
                // if (!sizeof($this->id)) {
                if (!sizeof($mode_id)) {
                    // BAD parameter passed (id)
                    $keep_test_mode = $this->mode;
                    $this->mode = 'grid';
                    // $bad_id = true;
                    $bad_mode = true;
                }
            }

            // проверить $this->action через check_access
            if ($this->action) {
                if ($this->id) {
                    // $tmp_id = $this->id;
                    // $this->id = array();
                    // foreach ($tmp_id as $id) {
                    $action_id = array();
                    foreach ($this->id as $id) {
                        if ($this->do_row($id) && $this->do_check_access($id, $this->action)) {
                            // $this->id[] = $id;
                            $action_id[] = $id;
                        }
                    }
                    // if (!sizeof($this->id)) {
                    if (!sizeof($action_id)) {
                        // BAD parameter passed (id)... YES, id, not action!
                        $keep_test_action = $this->action;
                        $this->action = false;
                        // $bad_id = true;
                        $bad_action = true;
                    }
                }
                else {
                    if (!$this->do_check_access(false, $this->action)) {
                        // BAD parameter passed (action)
                        $keep_test_action = $this->action;
                        $this->action = false;
                        $bad_action = true;
                    }
                }
            }

            // если были переданы плохие параметры
            if ($bad_mode || $bad_action) {

                $params = array(
                    $this->param_is_ajax_request =>_read_param($this->param_is_ajax_request)
                );

                $popups_to_close_level = 0;
                $this->id = array();

                if ($bad_action) {
                    $params = array_merge($params, $this->get_action_params(false));
                    if (isset($this->controls[$keep_test_action]) && $this->controls[$keep_test_action]['popup']) {
                        $popups_to_close_level++;
                    }
                }
                else {
                    if ($this->action && $this->controls[$this->action]['popup']) {
                        $popups_to_close_level++;
                    }
                    if (isset($action_id)) {
                        $this->id = array_merge($this->id, $action_id);
                    }
                }
                if ($bad_mode) {
                    $params = array_merge($params, $this->get_details_params(false));
                    if (isset($this->controls[$keep_test_mode]) && $this->controls[$keep_test_mode]['popup']) {
                        $popups_to_close_level++;
                    }
                }
                else {
                    if ($this->mode == 'details' && $this->controls[$this->mode]['popup']) {
                        $popups_to_close_level++;
                    }
                    if (isset($mode_id)) {
                        $this->id = array_merge($this->id, $mode_id);
                    }
                }
                $this->id = $this->simplify_id($this->id);

                if ($popups_to_close_level) {
                    $this->ajax_popup_close_content_reload($popups_to_close_level);
                }
                else {
                    $this->_redirect($params);
                }
            }
            else {
                $this->id = array();
                if ($this->mode == 'details' && isset($mode_id)) {
                    $this->id = $mode_id;
                }
                if ($this->action && isset($action_id)) {
                    $this->id = array_merge($this->id, $action_id);
                }
                $this->id = $this->simplify_id($this->id);
            }
            // - EOF проверка параметров -------------------------------------------------------------------------------

            // если мы запустились не в дефолтоном режиме, а для этого режима установлен ajax или popup
            // то контроль ведущий в дефолтный режим должен быть ajax
            if (
                $this->mode != $this->default_mode &&
                ($this->controls[$this->mode]['ajax'] || $this->controls[$this->mode]['popup']) &&
                // 2012-03-02
                isset($this->controls[$this->default_mode])
            ) {
                $this->controls[$this->default_mode]['popup'] = false;
                $this->controls[$this->default_mode]['ajax'] = true;
            }

            // если текущий режим popup, то все действия в нем тоже должны быть popup
            /*
            if (isset($this->controls[$this->mode]) && $this->controls[$this->mode]['popup']) {
                foreach ($this->controls as &$ctrl) {
                    if (!$ctrl['is_mode']) {
                        $ctrl['popup'] = true;
                        $ctrl['ajax'] = false;
                    }
                }
                unset($ctrl);
            }
            */

            // current page
            if ($this->page === false) {
                if ($this->per_page == 0) {
                    $this->page = 0;
                } else {
                    $this->page = _read_param($this->param_page);
                    if ($this->use_cookie_page) {
                        if ($this->get_mode() == 'grid' && !$this->get_action()) {
                            _write_cookie_param($this->param_page, $this->page, 0, _get_basepath());
                        }
                        else {
                            $this->page = _read_cookie_param($this->param_page);
                        }
                    }
                    $count = $this->get_count();
                    if (!is_numeric($this->page) || $this->page < 0 || $count == 0) {
                        $this->page = 0;
                    } else if ($this->per_page * $this->page >= $count) {
                        $this->page = ceil($count / $this->per_page) - 1;
                    }
                }
            }

            // загрузить языковой файла
            if (!$this->language) {
                $this->language = _cc::get_config('_project', '_language');
            }

            // 2012-04-03
            $this->_stick_params($this->get_grid_params());
            if ($this->mode == 'details') {
                $this->_stick_params($this->get_details_params());
            }
            if ($this->action) {
                $this->_stick_params($this->get_action_params());
            }
            // EOF 2012-04-03

        }

    }

    public function _get_breadcrumbs() {
        $this->initialize();

        if (!$this->breadcrumbs_generated) {
            /* BREADCRUMBS and PARAMS */
            // $this->_stick_params($this->get_grid_params());
            $this->do_create_breadcrumbs(
                'mode',
                array(
                    'mode' => 'grid',
                    'params' => array_merge($this->get_grid_params(), $this->get_details_params(false), $this->get_action_params(false))
                )
            );
            if ($this->get_mode() == 'details') {
                // $this->_stick_params($this->get_details_params());
                $this->do_create_breadcrumbs(
                    'mode',
                    array(
                        'mode' => 'details',
                        'id' => $this->get_id(),
                        'params' => array_merge($this->get_details_params(), $this->get_action_params(false))
                    )
                );
            }

            if ($this->get_action()) {
                // $this->_stick_params($this->get_action_params());
                $this->do_create_breadcrumbs(
                    'action',
                    array(
                        'action' => $this->get_action(),
                        'id' => $this->get_id(),
                        'params' => $this->get_action_params()
                    )
                );
            }
            $this->breadcrumbs_generated = true;
        }
        return parent::_get_breadcrumbs();
    }

    /*****************************************************************
    * УПРАВЛЯЮЩИЕ МЕТОДЫ
    *****************************************************************/
    
    /**
    * Запускает modDataGrid в текущем режиме
    */
    public function _run() {
        $this->initialize();
        return call_user_func_array(array($this, 'proceed_mode_' . $this->get_mode()), array());
    }

    /**
    * Выполняет пользовательский колбек proceed_[action], если существует, иначе внутренний метод proceed_[action]
    * Ожидает получить данные (tpl_engine или string), которые должны заменить сетку или FALSE/NULL, 
    * если действие окончено, и надо обновить сетку
    * 
    * @param mixed $id ID строки (по primary_key из data_source), над которой производится действие
    * @param string $action Имя экшена, указанное в контроле
    * 
    * @return mixed Результат работы экшена, который нужно вывести вместо сетки
    */
    public function do_proceed_action($id, $action) {
        if (method_exists($this->_get_holder(), $this->callback_proceed_action)) {
            $res = call_user_func_array(array($this->_get_holder(), $this->callback_proceed_action), array($id, $action, $this));
        }
        else if (method_exists($this->_get_holder(), $this->prefix_callbacks . 'proceed_' . $action)) {
            $res = call_user_func_array(array(
                $this->_get_holder(), 
                $this->prefix_callbacks . 'proceed_' . $action
            ), array($this->get_id(), $this));
        }
        else {
            $res = $this->proceed_action($id, $action);
        }
        if ($res === false || $res === null) {
            if ($this->controls[$action]['popup']) {
                $this->ajax_popup_close_content_reload($this->ajax_get_response_level());
            }
            else if ($this->controls[$action]['ajax']) {
                $this->ajax_content_reload();
            }
            else {
                // 2012-04-03
                /*
                if ($this->get_mode() == 'details') {
                    $this->_redirect($this->get_details_params());
                }
                else {
                    $this->_redirect($this->get_grid_params());
                }
                */
                $this->_redirect($this->get_action_params(false));
            }
        }
        else {
            if ($this->controls[$action]['ajax'] || $this->controls[$action]['popup']) {
                if ($this->controls[$action]['popup']) {
                    _overwhelm_response(json_encode(array(
                        'type' => 'grid_popup',
                        'level' => $this->ajax_get_response_level(),
                        'content' => is_object($res) ? $res->__toString() : $res
                    )), 'application/json');
                }
                else {
                    if ($this->controls[$this->get_mode()]['popup']) {
                        _overwhelm_response(json_encode(array(
                            'type' => 'grid_popup',
                            'level' => 1,
                            'content' => is_object($res) ? $res->__toString() : $res
                        )), 'application/json');
                    }
                    else {
                        _overwhelm_response(json_encode(array(
                            'type' => 'grid_html',
                            'content' => is_object($res) ? $res->__toString() : $res
                        )), 'application/json');
                    }
                }
            }
            else {
                return $res;
            }
        }
    }

    /**
    * Если ID == false - значит пользователь производит действие не над существующими данными (например, add)
    * Если ID == int - значит пользователь производит действие над одиночной строкой данных
    * Если ID == array - значит пользователь производит действие над группой данных
    */
    public function proceed_action($id, $action) {
        $this->initialize();

        $callback_name = 'callback_proceed_' . $action;
        $method_name = 'proceed_' . $action;
        if (method_exists($this->_get_holder(), $callback_name)) {
            return call_user_func_array(array($this->_get_holder(), $callback_name), array($id));
        }
        else if (method_exists($this, $method_name)) {
            return $this->$method_name($id);
        }
        else {
            _cc::fatal_error(_DEBUG_CC, 'CC Error. Action <b>' . $action . '</b> is not handled (neigher <b>' . $this->callback_proceed_action . '</b> nor <b>' . $this->prefix_callbacks . 'proceed_' . $action . '</b> callback exists).');
        }
    }

    /*****************************************************************
    * ВСЯКИЙ НУЖНЫЙ ХЛАМ
    *****************************************************************/

    protected function simplify_id($id) {
        if (is_array($id)) {
            $id = array_unique($id);
            if (sizeof($id) == 0) {
                $id = false;
            }
            else if (sizeof($id) == 1) {
                $id = $id[0];
            }
        }
        return $id;
    }

    protected function join_joins() {
        foreach ($this->join as $ds => $join) {
            if (is_numeric($ds)) { // join method is default
                if (is_array($join)) {
                    call_user_func_array(array($this->data_source, '_join'), $join);
                }
                else {
                    $this->data_source->_join($join);
                }
            }
            else {
                $this->data_source->_join($ds, $join);
            }
        }
    }

    /**
    * Добавляет контроли к элементу списка или ко всей странице
    * 
    * @param array элемент списка
    * @param mixed row, rows, other или массив с любой комбинацией этих значений
    * @return array элемент списка
    */
    protected function adjust_controls($in, $apply_to = 'row') {
        if (!is_array($apply_to)) {
            $apply_to = array($apply_to);
        }
        $controls_sets = array();
        foreach ($this->controls as $key => $control) {
            if (in_array($this->mode, $control['modes']) && sizeof(array_values(array_intersect($apply_to, $control['applicable'])))) {

                foreach ($apply_to as $app_t) {
                    if ($app_t != 'rows' || $this->get_count()) {
                        if (isset($in[$this->data_source->_get_primary_key()])) {
                            $id = $in[$this->data_source->_get_primary_key()];
                        }
                        else {
                            $id = false;
                        }
                        if (in_array($app_t, $control['applicable']) && $this->do_check_access($id, $key)) {
                            if ($key == 'grid') {
                                $link = $this->grid_hlink();
                            }
                            else if ($key == 'details') {
                                $link = $this->details_hlink($in[$this->data_source->_get_primary_key()]);
                            }
                            else if ($app_t == 'row') {
                                $link = $this->action_hlink($in[$this->data_source->_get_primary_key()], $key, $this->get_mode() != $this->default_mode ? $this->get_mode() : false);
                            }
                            else if ($app_t == 'rows') {
                                $link = $this->action_hlink('{DATA_GRID_ID}', $key, $this->get_mode() != $this->default_mode ? $this->get_mode() : false);
                            }
                            else if ($app_t == 'other') {
                                $link = $this->action_hlink(false, $key, $this->get_mode() != $this->default_mode ? $this->get_mode() : false);
                            }

                            // при нереальной ситуации, когда для одного контроля будут указаны 
                            // "rows" и "other" произойдет конфликт
                            $in['link_' . $key] = $link;

                            if (!isset($controls_sets[$app_t])) {
                                $controls_sets[$app_t] = array();
                            }

                            // нужно ли сложное имя контролям? вроде бы нет
                            // $tpl = 'control_' . $key . '_' . $this->mode . '_' . $app_t . '.tpl';
                            if (isset($control['tpl_name'])) {
                                $tpl = $control['tpl_name'];
                            }
                            else {
                                $tpl = 'control_' . $key . '.tpl';
                            }
                            $tpl_data = $control;
                            $tpl_data['prefix_data_grid'] = $this->prefix_params;
                            // $tpl_data['perform_ajax_param'] = $this->perform_ajax_param;
                            // вместо сложного имени передаем в шаблон mode и apply_to - по ним контроль сможет менять свой внешний вид и поведение при необходимости
                            $tpl_data['link'] = $link;
                            $tpl_data['mode'] = $this->mode;
                            $tpl_data['apply_to'] = $app_t;

                            if (isset($tpl_data['title']) && isset($this->lang[$tpl_data['title']])) {
                                $tpl_data['title'] = $this->lang[$tpl_data['title']];
                            }
                            $tpl_data['lang'] = $this->lang;
                            $tpl_data['js_instance'] = $this->get_js_instance();

                            $controls_sets[$app_t][$key] = $this->_tpl($tpl, $tpl_data);
                        }
                    }
                }
            }
        }
        foreach ($apply_to as $app_t) {
            // нужно ли сложное имя пачкам контролей? вроде бы нет
            // $tpl = 'controls_' . $this->mode . '_' . $app_t . '.tpl';
            $tpl = 'controls.tpl';
            if (isset($controls_sets[$app_t]) && sizeof($controls_sets[$app_t])) {
                $in['controls_' . $app_t] = $this->_tpl($tpl, array(
                    'mode' => $this->get_mode(),
                    'apply_to' => $app_t,
                    'controls' => $controls_sets[$app_t],
                    // 'lang' => $this->lang
                ));
            } else {
                $in['controls_' . $app_t] = false;
            }
        }
        return $in;
    }

    /**
    * Проверка контроля на валидность
    */
    protected function control_is_valid($key,$control) {
        if (isset($control['modes']) && isset($control['applicable']) && is_array($control['modes']) && is_array($control['applicable'])) {
            return true;
        } else {
            _cc::debug_message(_DEBUG_CC, 'CC Warning. "modes" and "applicable" keys should be defined as array for control <b>' . $key . '</b> for the DataList_mod', 'error');
            return false;
        }
    }

    /**
    * находит поля в data_source, которые не надо выводить при 
    * добавлении/редактировании (значение для них установлено в where)
    */
    protected function get_exclude_fields() {
        if ($this->exclude_fields === false) {
            $test_keys = $this->data_source->_get_fields_names();
            $this->exclude_fields = array_values(array_unique(array_merge(
                $this->get_exclude_fields_sub($this->where, $test_keys), 
                $this->get_exclude_fields_sub($this->having, $test_keys)
            )));
        }
        return $this->exclude_fields;
    }

    protected function get_exclude_fields_sub($w, $test_keys) {
        $ret = array();
        foreach ($w as $key => $v) {
            if (in_array($key, $test_keys, true)) {
                $ret[] = $key;
            }
        }
        return $ret;
    }

    public function do_check_access($id, $control) {
        if (method_exists($this->_get_holder(), $this->callback_check_access)) {
            return call_user_func_array(
                array($this->_get_holder(), $this->callback_check_access), 
                array($id, $control, $this)
            );
        }
        else if (method_exists($this->data_source, $this->callback_check_access)) {
            return call_user_func_array(
                array($this->data_source, $this->callback_check_access), 
                array($id, $control, $this)
            );
        }
        else {
            return $this->check_access($id, $control);
        }
    }

    public function check_access($id, $control) {
        $this->initialize();
        if ($control == 'activate') {
            if ($id && $this->data_source->_is_active($id)) {
                return false;
            }
            else {
                return true;
            }
        }
        else if ($control == 'deactivate') {
            if (!$id || $this->data_source->_is_active($id)) {
                return true;
            }
            else {
                return false;
            }
        }
        else if ($control == 'move_up') {
            if ($id == $this->get_first_row_id()) {
                return false;
            }
            else {
                return true;
            }
        }
        else if ($control == 'move_down') {
            if ($id == $this->get_last_row_id()) {
                return false;
            }
            else {
                return true;
            }
        }
        else if (
            $control == 'paste'
        ) {
            $clipboard = $this->get_clipboard();
            if ($clipboard['id'] !== false) {
                return true;
            }
            else {
                return false;
            }
        }
        else if (
            $control == 'paste_before' ||
            $control == 'paste_after'
        ) {
            $clipboard = $this->get_clipboard();
            if (
                $clipboard['id'] !== false && (
                    (is_array($clipboard['id']) && !in_array($id, $clipboard['id'])) ||
                    (!is_array($clipboard['id']) && $id != $clipboard['id'])
                )
            ) {
                return true;
            }
            else {
                return false;
            }
        }
        else if (
            $control == 'cut'
        ) {
            $clipboard = $this->get_clipboard();
            if (
                $clipboard['id'] === false || (
                    $clipboard['id'] !== false && (
                        (is_array($clipboard['id']) && !in_array($id, $clipboard['id'])) ||
                        (!is_array($clipboard['id']) && $id != $clipboard['id'])
                    )
                )
            ) {
                return true;
            }
            else {
                return false;
            }
        }
        else if (
            $control == 'cut_cancel'
        ) {
            $clipboard = $this->get_clipboard();
            if ($id) {
                if (
                    is_array($clipboard['id']) && in_array($id, $clipboard['id']) ||
                    !is_array($clipboard['id']) && $id == $clipboard['id']
                ) {
                    return true;
                }
                else {
                    return false;
                }
            }
            else if ($clipboard['id']) {
                return true;
            }
            else {
                return false;
            }
        }
        else if (
            $control == 'reset_clipboard'
        ) {
            $clipboard = $this->get_clipboard();
            if ($clipboard['id']) {
                return true;
            }
            else {
                return false;
            }
        }
        else {
            return true;
        }
    }


    protected $first_row_id = false;
    protected function get_first_row_id() {
        if (!$this->first_row_id) {
            /*
            $this->data_source->_reset()->_order($this->data_source->_get_sort_field());
            if ($this->group) {
                $this->data_source->_group($this->group);
            }
            $this->first_row_id = $this->data_source->_having($this->having)->_row($this->where, $this->data_source->_get_primary_key());
            */
            $this->join_joins();
            $this->data_source->_order($this->order);
            if ($this->group) {
                $this->data_source->_group($this->group);
            }
            $this->first_row_id = $this->data_source->_where($this->where)->_having($this->having)->_first_row(false, $this->data_source->_get_primary_key());
        }
        return $this->first_row_id;
    }

    protected $last_row_id = false;
    protected function get_last_row_id() {
        if (!$this->last_row_id) {
            /*
            $this->data_source->_reset()->_order($this->data_source->_get_sort_field() . ' desc');
            if ($this->group) {
                $this->data_source->_group($this->group);
            }
            $this->last_row_id = $this->data_source->_having($this->having)->_row($this->where, $this->data_source->_get_primary_key());
            */
            $this->join_joins();
            $this->data_source->_order($this->order);
            if ($this->group) {
                $this->data_source->_group($this->group);
            }
            $this->last_row_id = $this->data_source->_where($this->where)->_having($this->having)->_last_row(false, $this->data_source->_get_primary_key());
        }
        return $this->last_row_id;
    }

    protected function merge_where() {
        $where_many = func_get_args();
        $ret = array();
        foreach ($where_many as $where) {
            if ($where) {
                if (is_array($where)) {
                    $ret = array_merge($ret, $where);
                }
                else {
                    $ret[] = $where;
                }
            }
        }
        return $ret;
    }

    protected function merge_cols() {
        $cols_many = func_get_args();
        $ret = array();
        foreach ($cols_many as $cols) {
            if ($cols) {
                if (is_array($cols)) {
                    $ret = array_merge($ret, $cols);
                }
                else {
                    $ret[] = $cols;
                }
            }
        }
        return $ret;
    }

    /**
    * Получить ссылку на объект плагина
    * 
    * @param mixed $str
    */
    public function get_plugin_object($str) {
        $this->initialize();
        if (isset($this->plugins_modules[$str])) {
            return $this->plugins_modules[$str];
        } else {
            return false;
        }
    }

    protected function get_tpl_data() {
        return array(
            'action'                => $this->get_action(),
            'mode'                  => $this->get_mode(),
            'js_instance'           => $this->get_js_instance(),
            'link_ajax_load_grid'   => $this->_link(array_merge(
                $this->get_grid_params(), 
                $this->get_action_params(false), 
                $this->get_details_params(false), 
                array($this->param_is_ajax_request => 'on')
            )),
            'include_js'            => $this->include_js,
            'prefix_data_grid'      => $this->prefix_params,
            'lang'                  => $this->lang
        );
    }

    /***********************************************************************************************
    * ДОБАВЛЕНИЕ/РЕДАКТИРОВАНИЕ (общее)
    ***********************************************************************************************/

    /**
    * список полей, вызывается или колбек (если найден) 
    * или встроенный метод
    */
    public function do_list_fields($id, $values, $step, $form, $submits) {
        if (method_exists($this->_get_holder(), $this->callback_list_fields)) {
            return call_user_func_array(
                array($this->_get_holder(), $this->callback_list_fields), 
                array($id, $values, $step, $form, $submits)
            );
        }
        else if (is_array($this->fields) && sizeof($this->fields)) {
            if ($step == 0) {
                return _organize_fields(false, $this->fields, $this->get_exclude_fields(), $id ? $this->row($id) : false);
            }
            else {
                return array();
            }
        }
        else if (method_exists($this->data_source, $this->callback_list_fields)) {
            return call_user_func_array(
                array($this->data_source, $this->callback_list_fields), 
                array($id, $values, $step, $form, $submits)
            );
        }
        else {
            if ($step == 0) {
                return $this->list_fields($id, $values);
            }
            else {
                return array();
            }
        }
    }

    /**
    * встроенный метод, возвращающий список полей
    */
    public function list_fields($id, $values) {
        $this->initialize();

        return $this->data_source->_get_fields($id, false, $this->get_exclude_fields());
    }

    /**
    * проверка данных, вызывается или колбек (если найден) или встроенный метод
    */
    public function do_validate_input($id, $values, $step, $form, $submits) {
        if (method_exists($this->_get_holder(), $this->callback_validate_input)) {
            return call_user_func_array(array($this->_get_holder(), $this->callback_validate_input), array($id, $values, $step, $form, $submits));
        }
        else if (method_exists($this->data_source, $this->callback_validate_input)) {
            return call_user_func_array(array($this->data_source, $this->callback_validate_input), array($id, $values, $step, $form, $submits));
        }
        else {
            return $this->validate_input($id, $values);
        }
    }

    /**
    * встроенный метод для проверки данных
    */
    public function validate_input($id, $values) {
        $this->initialize();
        return $this->data_source->_validate_input($id, $values);
    }

    /**
    * обработка завершения работы формы, вызывается или колбек (если найден) 
    * или встроенный метод
    */
    protected function do_complete($id, $values, $step, $form, $submits) {
        if (method_exists($this->_get_holder(), $this->callback_complete)) {
            // return 
            return call_user_func_array(
                array($this->_get_holder(), $this->callback_complete), 
                array($id, $values, $step, $form, $submits)
            );
        }
        else {
            // return 
            return $this->complete($id, $values, $step, $form, $submits);
        }
    }

    /**
    * встроенный метод для обработки завершения работы формы
    */
    public function complete($id, $values, $step, $form, $submits) {
        if ($id) {
            $this->do_update($id, $values);
            $ctrl = 'edit';
        }
        else {
            
            $this->do_insert($values);
            $ctrl = 'add';
        }

        if ($this->controls[$ctrl]['popup']) {
            $this->ajax_popup_close_content_reload($this->ajax_get_response_level());
        }
        else if ($this->controls[$ctrl]['ajax']) {
            $this->ajax_content_reload();
        }
        else {
            return false;
        }
    }

    /***********************************************************************************************
    * ДОБАВЛЕНИЕ
    ***********************************************************************************************/

    /**
    * встроенный метод для обработки кнопки "Add"
    */
    public function proceed_add() {

        $templates_dir = array();
        if ($this->form_add_templates_dir) {
            $templates_dir[] = $this->form_add_templates_dir;
        }
        if ($this->controls['add']['popup']) {
            $templates_dir[] = $this->form_popup_templates_dir;
        }

        if ($this->title_add) {
            $title = $this->title_add;
        }
        else {
            $title = $this->lang['TITLE_ADD'];
        }

        $this->form = $this->_module($this->module_form, array(
            '_templates_dir'        => $templates_dir,
            'prefix_params'         => $this->prefix_params . 'f_',
            'prefix_callbacks'      => 'add_form_',
            'title'                 => $title,
            'ajax'                  => $this->controls['add']['ajax'] || $this->controls['add']['popup'], // ajax/popup mod
            'suppress_overwhelm'    => true,
            'ajax_handler'          => $this->get_ajax_handler(),
            'max_step'              => $this->max_step_add ? $this->max_step_add : $this->max_step
        ));
        $this->form->_stick_params($this->get_action_params());

        $ret = $this->form->_run();
        if ($ret) {
            return $this->_tpl('action_add.tpl', array_merge($this->get_tpl_data(), array(
                'form'      => $ret,
            )));
        }
        else {
            return $ret;
        }
    }

    /**
    * Колбек list_fields, вызывается формой при добавлении
    */
    public function add_form_list_fields($id, $values, $step, $form, $submits) {
        $ret = $this->do_list_fields($id, $values, $step, $form, $submits);

        foreach ($this->plugins_modules as $plugin) {
            $ret = $plugin->adjust_fields($ret, $id, $values, $step, $form, $submits);
        }

        return $ret;
    }

    /**
    * колбек вализации нажатия OK, вызываемый формой при добавлении
    */
    public function add_form_validate_ok($id, $values, $step, $form, $submits) {
        return $this->do_validate_input($id, $values, $step, $form, $submits);
    }

    /**
    * колбек complete, вызываемый формой при добавлении
    */
    public function add_form_complete($id, $values, $step, $form, $submits) {
        return $this->do_complete($id, $values, $step, $form, $submits);
    }

    /**
    * 
    */
    public function add_form_adjust_tpl_data($data) {
        // $data['popup'] = $this->controls['add']['popup'];
        if (method_exists($this->_get_holder(), $this->callback_adjust_add_tpl_data)) {
            $data = call_user_func_array(array($this->_get_holder(), $this->callback_adjust_add_tpl_data), array($data));
        }
        else if (method_exists($this->data_source, $this->callback_adjust_add_tpl_data)) {
            $data = call_user_func_array(array($this->data_source, $this->callback_adjust_add_tpl_data), array($data));
        }
        return $data;
    }

    /**
    * добавление данных, вызывается или колбек (если найден) или встроенный метод
    */
    public function do_insert($values) {
        if (method_exists($this->_get_holder(), $this->callback_insert)) {
            return call_user_func_array(array($this->_get_holder(), $this->callback_insert), array($values));
        }
        else if (method_exists($this->data_source, $this->callback_insert)) {
            return call_user_func_array(array($this->data_source, $this->callback_insert), array($values));
        }
        else {
            return $this->insert($values);
        }
    }

    /**
    * встроенный метод для добавления данных
    */
    public function insert($values) {
        $this->initialize();

        $excluded_fields = $this->get_exclude_fields();
        foreach ($excluded_fields as $field) {
            if (!isset($values[$field])) {
                if (isset($this->where[$field])) {
                    $values[$field] = $this->where[$field];
                }
                else if (isset($this->having[$field])) {
                    $values[$field] = $this->having[$field];
                }
            }
        }

        foreach ($this->plugins_modules as $plugin) {
            $values = $plugin->adjust_before_insert($values);
        }
        $id = $this->data_source->/*_where($this->where)->*/_insert($values);
        foreach ($this->plugins_modules as $plugin) {
            $plugin->execute_after_insert($id, $values);
        }

        return $id;
    }

    /**
    * колбек CANCEL, вызываемый формой при добавлении
    */
    public function add_form_proceed_cancel($id, $values) {
        if ($this->controls['add']['popup']) {
            $this->ajax_popup_close($this->ajax_get_response_level());
        }
        else {
            return false;
        }
        // return false;
    }

    /*****************************************************************
    * РЕДАКТИРОВАНИЕ
    *****************************************************************/

    /**
    * встроенный метод для обработки кнопки "Edit"
    */
    protected function proceed_edit($id) {

        $templates_dir = array();
        if ($this->form_edit_templates_dir) {
            $templates_dir[] = $this->form_edit_templates_dir;
        }
        if ($this->controls['edit']['popup']) {
            $templates_dir[] = $this->form_popup_templates_dir;
        }
        if ($this->title_edit) {
            $title = sprintf($this->title_edit, $this->data_source->_get_row_title($id));
        }
        else {
            $title = sprintf($this->lang['TITLE_EDIT'], $this->data_source->_get_row_title($id));
        }

        $this->form = $this->_module($this->module_form, array(
            '_templates_dir'        => $templates_dir,
            'prefix_params'         => $this->prefix_params . 'f_',
            'prefix_callbacks'      => 'edit_form_',
            'title'                 => $title,
            'ajax'                  => $this->controls['edit']['ajax'] || $this->controls['edit']['popup'], // ajax/popup mod
            'ajax_handler'          => $this->get_ajax_handler(),
            'suppress_overwhelm'    => true,
            'max_step'              => $this->max_step_edit ? $this->max_step_edit : $this->max_step,
            'edit_id'               => $this->get_id()
        ));
        $this->form->_stick_params($this->get_action_params());

        $res = $this->form->_run();
        if ($res) {
            if ($this->controls['edit']['popup'] || $this->controls['edit']['ajax']) {
                return $this->_tpl('action_edit_html.tpl', array_merge($this->get_tpl_data(), array(
                    'form'      => $res
                )));
            }
            else {
                return $this->_tpl('action_edit.tpl', array_merge($this->get_tpl_data(), array(
                    'form'          => $res
                )));
            }
        }
        else {
            return $res;
        }
    }

    /**
    * Колбек list_fields, вызывается формой при редактировании
    */
    public function edit_form_list_fields($id, $values, $step, $form, $submits) {

        $ret = $this->do_list_fields($id, $values, $step, $form, $submits);

        foreach ($this->plugins_modules as $plugin) {
            $ret = $plugin->adjust_fields($ret, $id, $values, $step, $form, $submits);
        }

        return $ret;
    }

    /**
    * колбек вализации нажатия OK, вызываемый формой при редактировании
    */
    public function edit_form_validate_ok($id, $values, $step, $form, $submits) {
        return $this->do_validate_input($this->get_id(), $values, $step, $form, $submits);
    }

    /**
    * колбек complete, вызываемый формой при редактировании
    */
    public function edit_form_complete($id, $values, $step, $form, $submits) {
        return $this->do_complete($id, $values, $step, $form, $submits);
    }

    public function edit_form_adjust_tpl_data($data) {
        // $data['popup'] = $this->controls['edit']['popup'];
        if (method_exists($this->_get_holder(), $this->callback_adjust_edit_tpl_data)) {
            $data = call_user_func_array(array($this->_get_holder(), $this->callback_adjust_edit_tpl_data), array($data));
        }
        else if (method_exists($this->data_source, $this->callback_adjust_edit_tpl_data)) {
            $data = call_user_func_array(array($this->data_source, $this->callback_adjust_edit_tpl_data), array($data));
        }
        return $data;
    }
    /**
    * обновление данных, вызывается или колбек (если найден) или встроенный метод
    */
    public function do_update($where, $values) {
        // в этом нет необходимости, т.к. при инициализации датагрид проверяет, дотупна ли 
        // выбранная строка для апдейта или нет
        // $where = $this->merge_where($this->where, $where);

        if (method_exists($this->_get_holder(), $this->callback_update)) {
            return call_user_func_array(array($this->_get_holder(), $this->callback_update), array($where, $values));
        }
        else if (method_exists($this->data_source, $this->callback_update)) {
            return call_user_func_array(array($this->data_source, $this->callback_update), array($where, $values));
        }
        else {
            return $this->update($where, $values);
        }
    }

    /**
    * встроенный метод для обновления данных
    */
    public function update($where, $values) {
        $this->initialize();

        foreach ($this->plugins_modules as $plugin) {
            $values = $plugin->adjust_before_update($where, $values);
        }
        $this->data_source->_update($where, $values);

        foreach ($this->plugins_modules as $plugin) {
            $values = $plugin->execute_after_update($where, $values);
        }
    }

    /**
    * колбек CANCEL, вызываемый формой при редактировании
    */
    public function edit_form_proceed_cancel($id, $values) {
        if ($this->controls['edit']['popup']) {
            $this->ajax_popup_close($this->ajax_get_response_level());
        }
        else {
            return false;
        }
        // return false;
    }

    /*****************************************************************
    * УДАЛЕНИЕ
    *****************************************************************/

    /**
    * встроенный метод для обработки кнопки "Delete"
    */
    public function proceed_delete($id) {
        return $this->do_delete($id);
    }

    /**
    * удаление данных, вызывается или колбек (если найден), или встроенный метод
    */
    public function do_delete($where) {
        // в этом нет необходимости, т.к. при инициализации датагрид проверяет, дотупна ли 
        // выбранная строка для удаления или нет
        // $where = $this->merge_where($this->where, $where);

        if (method_exists($this->_get_holder(), $this->callback_delete)) {
            return call_user_func_array(array($this->_get_holder(), $this->callback_delete), array($where));
        }
        else if (method_exists($this->data_source, $this->callback_delete)) {
            return call_user_func_array(array($this->data_source, $this->callback_delete), array($where));
        }
        else {
            return $this->delete($where);
        }
    }

    /**
    * встроенный метод для удаления данных
    */
    public function delete($where) {
        $this->initialize();

        $delete = true;
        foreach ($this->plugins_modules as $plugin) {
            if (!$plugin->verify_before_delete($where)) {
                $delete = false;
            }
        }
        if ($delete) {
            $this->data_source->_delete($where);
            foreach ($this->plugins_modules as $plugin) {
                $plugin->execute_after_delete($where);
            }
        }

        return false;
    }

    /***********************************************************************************************
    * АКТИВАЦИЯ/ДЕАКТИВАЦИЯ
    ***********************************************************************************************/

    /**
    * встроенный метод для обработки кнопки "Activate"
    */
    public function proceed_activate($id) {
        return $this->do_activate($id);
    }

    /**
    * Активировать - вызывается или колбек (если найден), или встроенный метод
    * 
    * @param mixed $id
    */
    public function do_activate($id) {
        if (method_exists($this->_get_holder(), $this->callback_activate)) {
            return call_user_func_array(array($this->_get_holder(), $this->callback_activate), array($id));
        }
        else if (method_exists($this->data_source, $this->callback_activate)) {
            return call_user_func_array(array($this->data_source, $this->callback_activate), array($id));
        }
        else {
            return $this->activate($id);
        }
    }

    /**
    * встроенный метод для активации
    * 
    * @param mixed $id
    */
    public function activate($id) {
        $this->initialize();

        $this->data_source->_activate($id);

        return false;
    }

    /**
    * встроенный метод для обработки кнопки "Deactivate"
    */
    public function proceed_deactivate($id) {
        return $this->do_deactivate($id);
    }

    /**
    * Деактивировать - вызывается или колбек (если найден), или встроенный метод
    * 
    * @param mixed $id
    */
    public function do_deactivate($id) {
        if (method_exists($this->_get_holder(), $this->callback_deactivate)) {
            return call_user_func_array(array($this->_get_holder(), $this->callback_deactivate), array($id));
        }
        else if (method_exists($this->data_source, $this->callback_deactivate)) {
            return call_user_func_array(array($this->data_source, $this->callback_deactivate), array($id));
        }
        else {
            return $this->deactivate($id);
        }
    }

    /**
    * встроенный метод для деактивации
    * 
    * @param mixed $id
    */
    public function deactivate($id) {
        $this->initialize();

        $this->data_source->_deactivate($id);

        return false;
    }

    /***********************************************************************************************
    * ПЕРЕМЕЩЕНИЕ
    ***********************************************************************************************/

    /**
    * встроенный метод для обработки кнопки "Move up"
    */
    public function proceed_move_up($id) {
        return $this->do_move_up($id);
    }

    /**
    * Переместить строку вверх - вызывается или колбек (если найден), или встроенный метод
    * 
    * @param mixed $id
    */
    public function do_move_up($id) {
        if (method_exists($this->_get_holder(), $this->callback_move_up)) {
            return call_user_func_array(array($this->_get_holder(), $this->callback_move_up), array($id));
        }
        else if (method_exists($this->data_source, $this->callback_move_up)) {
            return call_user_func_array(array($this->data_source, $this->callback_move_up), array($id));
        }
        else {
            return $this->move_up($id);
        }
    }

    /**
    * встроенный метод для перемещения строки вверх
    * 
    * @param mixed $id
    */
    public function move_up($id) {
        $this->initialize();

        $this->data_source->_where($this->where);
        /* ?
        if ($this->group) {
            $this->data_source->_group($this->group);
        }
        */
        $this->data_source->_move_up($id);

        return false;
    }

    /**
    * встроенный метод для обработки кнопки "Move down"
    */
    public function proceed_move_down($id) {
        return $this->do_move_down($id);
    }

    /**
    * Переместить строку вниз - вызывается или колбек (если найден), или встроенный метод
    * 
    * @param mixed $id
    */
    public function do_move_down($id) {
        if (method_exists($this->_get_holder(), $this->callback_move_down)) {
            return call_user_func_array(array($this->_get_holder(), $this->callback_move_down), array($id));
        }
        else if (method_exists($this->data_source, $this->callback_move_down)) {
            return call_user_func_array(array($this->data_source, $this->callback_move_down), array($id));
        }
        else {
            return $this->move_down($id);
        }
    }

    /**
    * встроенный метод для перемещения строки вниз
    * 
    * @param mixed $id
    */
    public function move_down($id) {
        $this->initialize();

        $this->data_source->_where($this->where);
        /* ?
        if ($this->group) {
            $this->data_source->_group($this->group);
        }
        */
        $this->data_source->_move_down($id);

        return false;
    }

    /**
    * встроенный метод для обработки кнопки "Cut"
    */
    public function proceed_cut($id) {
        return $this->do_cut($id);
    }

    /**
    * Вырезать row/rows - вызывается или колбек (если найден), или встроенный метод
    * 
    * @param mixed $id
    */
    public function do_cut($id) {
        if (method_exists($this->_get_holder(), $this->callback_cut)) {
            return call_user_func_array(array($this->_get_holder(), $this->callback_cut), array($id));
        }
        else if (method_exists($this->data_source, $this->callback_cut)) {
            return call_user_func_array(array($this->data_source, $this->callback_cut), array($id));
        }
        else {
            return $this->cut($id);
        }
    }

    /**
    * встроенный метод для вырезания
    * 
    * @param mixed $id
    */
    public function cut($id) {
        $this->initialize();

        $clipboard = $this->get_clipboard();
        if ($clipboard['type'] == 'cut') {
            if (!is_array($id)) {
                $id = array($id);
            }
            if (!is_array($clipboard['id'])) {
                if ($clipboard['id']) {
                    $clipboard['id'] = array($clipboard['id']);
                }
                else {
                    $clipboard['id'] = array();
                }
            }

            $id = array_merge($clipboard['id'], $id);

            if (sizeof($id) == 1) {
                $id = $id[0];
            }
        }

        _write_cookie_param($this->cookie_param_copycut_id, serialize($id), 0, _get_basepath());
        _write_cookie_param($this->cookie_param_copycut_type, 'cut', 0, _get_basepath());

        return false;
    }

    /**
    * встроенный метод для обработки кнопки "Cancel Cut"
    */
    public function proceed_cut_cancel($id) {
        return $this->do_cut_cancel($id);
    }

    /**
    * Отменить вырезание row/rows - вызывается или колбек (если найден), или встроенный метод
    * 
    * @param mixed $id
    */
    public function do_cut_cancel($id) {
        if (method_exists($this->_get_holder(), $this->callback_cut_cancel)) {
            return call_user_func_array(array($this->_get_holder(), $this->callback_cut_cancel), array($id));
        }
        else if (method_exists($this->data_source, $this->callback_cut_cancel)) {
            return call_user_func_array(array($this->data_source, $this->callback_cut_cancel), array($id));
        }
        else {
            return $this->cut_cancel($id);
        }
    }

    /**
    * встроенный метод для вырезания
    * 
    * @param mixed $id
    */
    public function cut_cancel($id) {
        $this->initialize();

        $clipboard = $this->get_clipboard();
        if ($clipboard['type'] == 'cut') {
            if (!is_array($id)) {
                $id = array($id);
            }
            if (!is_array($clipboard['id'])) {
                $clipboard['id'] = array($clipboard['id']);
            }

            $new_id = array_diff($clipboard['id'], $id);

            if (sizeof($new_id) == 0) {
                $this->reset_clipboard();
            }
            else {
                if (sizeof($new_id) == 1) {
                    $new_id = $new_id[0];
                }
                _write_cookie_param($this->cookie_param_copycut_id, serialize($new_id), 0, _get_basepath());
                _write_cookie_param($this->cookie_param_copycut_type, 'cut', 0, _get_basepath());
            }
        }

        return false;
    }

    /**
    * встроенный метод для обработки кнопки "Buffer reset"
    */
    public function proceed_reset_clipboard() {
        return $this->do_reset_clipboard();
    }

    /**
    * очистить буфер - вызывается или колбек (если найден), или встроенный метод
    * 
    * @param mixed $id
    */
    public function do_reset_clipboard() {
        if (method_exists($this->_get_holder(), $this->callback_reset_clipboard)) {
            return call_user_func_array(array($this->_get_holder(), $this->callback_reset_clipboard), array());
        }
        else if (method_exists($this->data_source, $this->callback_reset_clipboard)) {
            return call_user_func_array(array($this->data_source, $this->callback_reset_clipboard), array());
        }
        else {
            return $this->reset_clipboard();
        }
    }

    /**
    * получить содержимое клипбоарда
    * 
    * @return array array('type' => $type, 'id' => $id)
    */
    public function get_clipboard() {
        $serialized_id = _read_cookie_param($this->cookie_param_copycut_id);
        $paste_type = _read_cookie_param($this->cookie_param_copycut_type);
        if ($serialized_id) {
            $paste_id = unserialize($serialized_id);
            if (!$paste_id) {
                $paste_id = false;
            }
        }
        else {
            $paste_id = false;
        }
        return array(
            'type' => $paste_type,
            'id' => $paste_id
        );
    }

    /**
    * очистить содержимое клипбоарда
    * 
    */
    public function reset_clipboard() {
        $this->initialize();

        _write_cookie_param($this->cookie_param_copycut_id, false, 0, _get_basepath());
        _write_cookie_param($this->cookie_param_copycut_type, false, 0, _get_basepath());

        return false;
    }

    /**
    * встроенный метод для обработки кнопки "Paste"
    */
    public function proceed_paste() {
        return $this->do_paste();
    }

    /**
    * Вставить вырезанные/скопированные row/rows перед указанной строкой - вызывается или колбек (если найден), или встроенный метод
    */
    public function do_paste() {
        if (method_exists($this->_get_holder(), $this->callback_paste)) {
            return call_user_func_array(array($this->_get_holder(), $this->callback_paste), array());
        }
        else if (method_exists($this->data_source, $this->callback_paste)) {
            return call_user_func_array(array($this->data_source, $this->callback_paste), array());
        }
        else {
            return $this->paste();
        }
    }

    /**
    * встроенный метод вставки данных
    */
    public function paste() {
        $this->initialize();

        $paste = $this->get_clipboard();

        if ($paste['id']) {
            if ($paste['type'] == 'cut') {
                $this->data_source->_where($this->where)->_place($paste['id']);
            }
            $this->reset_clipboard();
        }

        return false;
    }

    /**
    * встроенный метод для обработки кнопки "Paste before"
    */
    public function proceed_paste_before($id) {
        return $this->do_paste_before($id);
    }

    /**
    * Вставить вырезанные/скопированные row/rows перед указанной строкой - вызывается или колбек (если найден), или встроенный метод
    *
    * @param mixed $id
    */
    public function do_paste_before($id) {
        if (method_exists($this->_get_holder(), $this->callback_paste_before)) {
            return call_user_func_array(array($this->_get_holder(), $this->callback_paste_before), array($id));
        }
        else if (method_exists($this->data_source, $this->callback_paste_before)) {
            return call_user_func_array(array($this->data_source, $this->callback_paste_before), array($id));
        }
        else {
            return $this->paste_before($id);
        }
    }

    /**
    * втроенный метод вставки данных перед указанной строкой
    *
    * @param mixed $before_id
    */
    public function paste_before($before_id) {
        $this->initialize();

        $paste = $this->get_clipboard();

        if ($paste['id']) {
            if ($paste['type'] == 'cut') {
                $this->data_source->_where($this->where)->_place_before($paste['id'], $before_id);
            }
            $this->reset_clipboard();
        }

        return false;
    }

    /**
    * встроенный метод для обработки кнопки "Paste after"
    */
    public function proceed_paste_after($id) {
        return $this->do_paste_after($id);
    }

    /**
    * Вставить вырезанные/скопированные row/rows после указанной строкой - вызывается или колбек (если найден), или встроенный метод
    * 
    * @param mixed $id
    */
    public function do_paste_after($id) {
        if (method_exists($this->_get_holder(), $this->callback_paste_after)) {
            return call_user_func_array(array($this->_get_holder(), $this->callback_paste_after), array($id));
        }
        else if (method_exists($this->data_source, $this->callback_paste_after)) {
            return call_user_func_array(array($this->data_source, $this->callback_paste_after), array($id));
        }
        else {
            return $this->paste_after($id);
        }
    }

    /**
    * встроенный метод для вставки данных после указанной строки
    * 
    * @param mixed $after_id
    */
    public function paste_after($after_id) {
        $this->initialize();

        $paste = $this->get_clipboard();

        if ($paste['id']) {
            if ($paste['type'] == 'cut') {
                $this->data_source->_where($this->where)->_place_after($paste['id'], $after_id);
            }
            $this->reset_clipboard();
        }

        return false;
    }

    /*****************************************************************
    * ВЫВОД СЕТКИ С ДАННЫМИ 
    *****************************************************************/

    /**
    * $this->mode == grid
    */
    protected function proceed_mode_grid() {
        if ($this->get_action()) {
            return $this->do_proceed_action($this->id, $this->action);
        }
        else {
            $page = $this->get_page();

            $limit1 = $page * $this->per_page;
            $limit2 = $this->per_page;
            // $this->data_rows = $this->_array_map('do_adjust_output', $this->do_rows(false, false, $limit1, $limit2));
            $this->data_rows = $this->_array_map('do_adjust_output', $this->do_rows($limit1, $limit2));

            if (_DEBUG_LEVEL & _DEBUG_CC) {
                if (!in_array($this->orientation, $this->orientations)) {
                    _cc::debug_message(_DEBUG_CC, 'CC Warning. "orientation" property is incorrect for the DataList_mod', 'error');
                }
            }
            if ($this->orientation == 'vertical' || $this->orientation == 'v') {

                $num_rows_total = ceil(sizeof($this->data_rows) / $this->per_row);
                $num_rows = array();
                $rows_v = array();
                for ($i = 0; $i < $this->per_row; $i++) {
                    $num_rows[$i] = ceil((sizeof($this->data_rows) - $i) / $this->per_row);
                    for ($j = 0; $j < $num_rows[$i]; $j++) {
                        $new_index = $j * $this->per_row + $i;
                        $old_offset = 0;
                        for ($k = 0; $k < $i; $k++) {
                            $old_offset += $num_rows[$k];
                        }
                        $old_index = $j + $old_offset;
                        $rows_v[$new_index] = $this->data_rows[$old_index];
                    }
                }

                $this->data_rows = $rows_v;
            }

            $rows = array();
            if (sizeof($this->data_rows)) {
                $col_index = 0;
                $row_index = 0;
                // foreach ($this->data_rows as $row) {
                for ($i = 0; $i < sizeof($this->data_rows); $i++) {
                    if (!$col_index) {
                        $rows[] = array('cols' => array());
                    }
                    if (!$this->data_rows[$i]) {
                        $this->data_rows[$i] = array('is_empty' => true);
                    }
                    $this->data_rows[$i]['col'] = $col_index;
                    $this->data_rows[$i]['row'] = $row_index;
                    $rows[$row_index]['cols'][] = $this->internal_adjust_output($this->data_rows[$i]);
                    $col_index++;
                    if ($col_index >= $this->per_row) {
                        $col_index = 0;
                        $row_index++;
                    }
                }
                $last_line_show = sizeof($this->data_rows) % $this->per_row;
                if ($last_line_show) {
                    $empty_count = $this->per_row - $last_line_show;
                } else {
                    $empty_count = 0;
                }
                if ($empty_count) {
                    for ($i = 0; $i < $empty_count; $i++) {
                        $rows[$row_index]['cols'][] = array(
                            'is_empty'    => true,
                            'col'      => $col_index,
                            'row'      => $row_index
                        );
                        $col_index++;
                    }
                }
            }

            $tpl_data = $this->adjust_controls(array_merge($this->get_tpl_data(), array(
                'per_row'               => $this->per_row,
                'title'                 => $this->title,
                'rows'                  => $rows,
                'pagination'            => $this->per_page ? $this->create_pagination() : false
            )), array('rows', 'other'));

            $tpl_data = $this->do_adjust_grid_tpl_data($tpl_data);
            foreach ($this->plugins_modules as $module) {
                $tpl_data = $module->adjust_grid_tpl_data($tpl_data);
            }

            // ajax/popup mod
            if (_read_param($this->param_is_ajax_request)) {
                $tpl = $this->_tpl('mode_grid_html.tpl', $tpl_data);
            }
            else {
                $tpl = $this->_tpl('mode_grid.tpl', $tpl_data);
            }

            foreach ($this->plugins_modules as $module) {
                $tpl = $module->wrap_grid_tpl($tpl);
            }

            if (_read_param($this->param_is_ajax_request)) {
                if ($this->default_mode == 'grid' || !$this->controls['grid']['popup']) {
                    _overwhelm_response(json_encode(array(
                        'type' => 'grid_html',
                        'content' => $tpl->_get_result()
                    )), 'application/json');
                }
                else {
                    _overwhelm_response(json_encode(array(
                        'type' => 'grid_popup',
                        'level' => 1,
                        'content' => $tpl->_get_result()
                    )), 'application/json');
                }
            }
            else {
                return $tpl;
            }
        }
    }

    public function do_adjust_grid_tpl_data($tpl_data) {
        if (method_exists($this->_get_holder(), $this->callback_adjust_grid_tpl_data)) {
            return call_user_func_array(array($this->_get_holder(), $this->callback_adjust_grid_tpl_data), array($tpl_data));
        }
        else {
            return $this->adjust_grid_tpl_data($tpl_data);
        }
    }

    /**
    * втроенный метод для модификации данных, посылаемых в шаблон; ничего не делает, присутсвует только 
    * для того, что бы придерживаться общей струтуры системы колбеков и встроенных аналогов
    * 
    * @param mixed $tpl_data
    */
    public function adjust_grid_tpl_data($tpl_data) {
        $this->initialize();

        return $tpl_data;
    }

    /**
    * геттер для получения количесва найденых строк
    */
    public function get_count() {
        $this->initialize();

        if ($this->count === false) {
            $this->count = $this->do_count();
        }
        return $this->count;
    }

    /**
    * подсчет количества строк, вызывается или колбек (если найден) или встроенный метод
    */
    public function do_count() {
        if (method_exists($this->_get_holder(), $this->callback_count)) {
            // return call_user_func_array(array($this->_get_holder(), $this->callback_count), array(array_merge($this->where, $this->filter)));
            return call_user_func_array(array($this->_get_holder(), $this->callback_count), array($this->filter_where, $this->filter_having));
        }
        else if (method_exists($this->data_source, $this->callback_count)) {
            // return call_user_func_array(array($this->data_source, $this->callback_count), array(array_merge($this->where, $this->filter)));
            return call_user_func_array(array($this->data_source, $this->callback_count), array($this->filter_where, $this->filter_having));
        }
        else {
            // return $this->count(array_merge($this->where, $this->filter));
            return $this->count($this->filter_where, $this->filter_having);
        }
    }

    /**
    * встроенный метод подсчета количества строк 
    */
    public function count($filter_where = false, $filter_having = false) {
        if (!$this->per_page && $this->data_rows !== false) {
            // если нет разделения на страницы и мы уже выбрали все данные для отображения то не нужно делать лишний запрос
            return sizeof($this->data_rows);
        }
        else {
            $this->initialize();
            $this->data_source->_reset();
            $this->join_joins();

            // 2011-07-28 - fixed count for distinct columns
            return $this->data_source->
                    _where($this->where)->
                    _where($filter_where)->
                    _having($this->having)->
                    _having($filter_having)->
                    _group($this->group)->
                    _columns($this->columns)->
                    _count();
        }
        /*
        if ($this->group) {
            // columns перенесено ниже и применяется без условия, т.к. в случае если в columns есть distinct, то пагинация работает не правильно
            $this->data_source->_group($this->group)->_columns($this->columns);
            // отменено
            // $this->data_source->_group($this->group); // ->_columns($this->columns);
        }
        // отменено
        return $this->data_source->_where($this->where)->_where($filter_where)->_having($this->having)->_having($filter_having)->_count();
        // return $this->data_source->_where($this->where)->_where($filter_where)->_having($this->having)->_having($filter_having)->_count(false, $this->columns);
        */
        // EOF 2011-07-28 - fixed count for distinct columns
    }
    
    /**
    * получение списка данных для текущей страницы, вызывается или колбек (если найден) 
    * или встроенный метод
    */
    // public function do_rows($where = false, $cols = false, $limit1 = false, $limit2 = false) {
    public function do_rows($limit1 = false, $limit2 = false) {
        // $where = $this->merge_where(array_merge($this->where, $this->filter), $where);
        // $where = $this->merge_where($this->filter, $where);
        // $cols = $this->merge_cols($this->columns, $cols);

        if (method_exists($this->_get_holder(), $this->callback_rows)) {
            // return call_user_func_array(array($this->_get_holder(), $this->callback_rows), array($where, $cols, $limit1, $limit2));
            // return call_user_func_array(array($this->_get_holder(), $this->callback_rows), array($this->filter, $this->columns, $limit1, $limit2));
            return call_user_func_array(array($this->_get_holder(), $this->callback_rows), array($this->filter_where, $this->filter_having, $limit1, $limit2));
        } 
        else if (method_exists($this->data_source, $this->callback_rows)) {
            // return call_user_func_array(array($this->data_source, $this->callback_rows), array($where, $cols, $limit1, $limit2));
            // return call_user_func_array(array($this->data_source, $this->callback_rows), array($this->filter, $this->columns, $limit1, $limit2));
            return call_user_func_array(array($this->data_source, $this->callback_rows), array($this->filter_where, $this->filter_having, $limit1, $limit2));
        }
        else {
            // return $this->rows($where, $cols, $limit1, $limit2);
            // return $this->rows($this->filter, $this->columns, $limit1, $limit2);
            return $this->rows($this->filter_where, $this->filter_having, $limit1, $limit2);
        }
    }

    /**
    * встроенный метод для получения списка данных
    */
    // public function rows($filter = false, $cols = false, $limit1 = false, $limit2 = false) {
    public function rows($filter_where = false, $filter_having = false, $limit1 = false, $limit2 = false) {
        $this->initialize();

        $this->data_source->_reset()->_limit($limit1, $limit2);
        $this->join_joins();

        $this->data_source->_order($this->order);
        if ($this->group) {
            $this->data_source->_group($this->group);
        }
        return $this->data_source->_where($this->where)->_where($filter_where)->_having($this->having)->_having($filter_having)->_arows(false, $this->columns);
    }

    /**
    * построение пагинации
    */
    protected function create_pagination() {
        $count = $this->get_count();
        $page = $this->get_page();
        if ($this->per_page) {
            $total_pages = ceil($count/$this->per_page);
        }
        else {
            $total_pages = 1;
        }

        // previous link
        if ($page > 0) {
            $prev_link = $this->_hlink(array_merge(
                $this->get_sticky_params(), 
                array(
                    $this->param_page => $page > 1 ? ($page - 1) : false,
                    $this->param_is_ajax_request => isset($this->pagination_settings['ajax']) && $this->pagination_settings['ajax'] ? 'yes' : false
                )
            ));
        }
        else {
            $prev_link = false;
        }

        if ($page < $total_pages - 1) {
            $next_link = $this->_hlink(array_merge(
                $this->get_sticky_params(), 
                array(
                    $this->param_page => $page + 1,
                    $this->param_is_ajax_request => isset($this->pagination_settings['ajax']) && $this->pagination_settings['ajax'] ? 'yes' : false
                )
            ));
        }
        else {
            $next_link = false;
        }

        $skip = false;
        $pages = array();
        if ($total_pages > 1) {
            for ($i = 0; $i < $total_pages; $i++) {
                if (
                    $i < $this->pagination_display_cnt || (
                        $i >= ($page - $this->pagination_display_cnt) &&
                        $i <= ($page + $this->pagination_display_cnt)
                    ) ||
                    $i >= $total_pages - $this->pagination_display_cnt
                ) {
                    $pages[] = array(
                        'index'         => $i + 1,
                        'link'          => $this->_hlink(array_merge(
                            $this->get_sticky_params(), 
                            array(
                                $this->param_page => $i ? $i : false,
                                $this->param_is_ajax_request => isset($this->pagination_settings['ajax']) && $this->pagination_settings['ajax'] ? 'yes' : false
                            )
                        )),
                        'is_current'    => $page == $i ? true : false,
                        'skip'          => false
                    );
                    $skip = false;
                } elseif (!$skip) {
                    $pages[] = array(
                        'skip' => true
                    );
                    $skip = true;
                }
            }
        }

        return $this->_tpl('pagination.tpl', array_merge($this->get_tpl_data(), array(
            'ajax' => isset($this->pagination_settings['ajax']) && $this->pagination_settings['ajax'] ? true : false,
            'next_link' => $next_link,
            'previous_link' => $prev_link,
            'pages' => $pages
        )));
    }

    /**
    * форматирование данных перед выводом, вызывается или колбек (если найден) 
    * или встроенный метод
    */
    public function do_adjust_output($in) {
        if (method_exists($this->_get_holder(), $this->callback_adjust_output)) {
            return call_user_func_array(array($this->_get_holder(), $this->callback_adjust_output), array($in, $this->mode, $this));
        }
        else if (method_exists($this->data_source, $this->callback_adjust_output)) {
            return call_user_func_array(array($this->data_source, $this->callback_adjust_output), array($in, $this->mode, $this));
        }
        else {
            return $this->adjust_output($in, $this->mode);
        }
    }

    /**
    * встроенный метод форматирования данных перед выводом
    */
    public function adjust_output($in, $mode) {
        $this->initialize();

        foreach ($this->plugins_modules as $plugin) {
            $in = $plugin->adjust_output($in, $mode);
        }

        return $in;
    }

    /**
    * внутреннее форматирование, вызывается всегда
    */
    protected function internal_adjust_output($in) {
        if (isset($in[$this->data_source->_get_primary_key()])) {
            $in = $this->adjust_controls($in);
            $in['is_empty'] = false;
            $in['prefix_data_grid'] = $this->prefix_params;
            $in['id'] = $in[$this->data_source->_get_primary_key()];
        }
        else {
            // какая-то неправильная строка, возможно фейк из колбека rows
        }
        return $in;
    }

    /*****************************************************************
    * ВЫВОД ПОДРОБНОСТЕЙ ДЛЯ ВЫБРАННОЙ СТРОКИ ДАННЫХ
    *****************************************************************/

    /**
    * $this->mode == details
    */
    protected function proceed_mode_details() {
        $row = $this->do_row($this->get_id());
        // 2012-04-03 уже не нужно
        /*
        // TODO: переделать ??? подумать
        if (!$row) {
            $this->_redirect($this->get_details_params(false));
        }
        */

        $this->data_row = $this->do_adjust_output($row, $this->get_mode());

        if ($this->get_action()) {
            return $this->do_proceed_action($this->id, $this->action);
        }
        else {
            $tpl_data = $this->adjust_controls(
                array_merge($this->data_row, $this->get_tpl_data()),
                array('row')
            );

            if ($this->get_mode() == 'details') {
                // ошибка?
                // $tpl_data = $this->do_adjust_grid_tpl_data($tpl_data);
                $tpl_data = $this->do_adjust_details_tpl_data($tpl_data);
                // EOF ошибка
            }

            // if ($this->controls['details']['ajax'] || $this->controls['details']['popup']) {
            if (_read_param($this->param_is_ajax_request)) {
                $tpl = $this->_tpl('mode_details_html.tpl', $tpl_data);
            }
            else {
                $tpl = $this->_tpl('mode_details.tpl', $tpl_data);
            }

/*
            if (
                ($this->default_mode != 'details' && ($this->controls['details']['ajax'] || $this->controls['details']['popup'])) ||
                ($this->default_mode == 'details' && _read_param($this->param_is_ajax_request))
            ) {
*/
            if (_read_param($this->param_is_ajax_request)) {
                if ($this->default_mode == 'details' || !$this->controls['details']['popup']) {
                    _overwhelm_response(json_encode(array(
                        'type' => 'grid_html',
                        'content' => $tpl->_get_result()
                    )), 'application/json');
                }
                else {
                    _overwhelm_response(json_encode(array(
                        'type' => 'grid_popup',
                        'level' => 1,
                        'content' => $tpl->_get_result()
                    )), 'application/json');
                }
            }
            else {
                return $tpl;
            }
        }
    }

    /**
    * получение текущей строки данных, вызывается или колбек (если найден) или встроенный метод
    */
    public function do_row($where = false) {

        if (method_exists($this->_get_holder(), $this->callback_row)) {
            return call_user_func_array(array($this->_get_holder(), $this->callback_row), array($where));
        }
        else if (method_exists($this->data_source, $this->callback_row)) {
            return call_user_func_array(array($this->data_source, $this->callback_row), array($where));
        }
        else {
            return $this->row($where);
        }
    }

    /**
    * встроенный метод для получения текущей строки данных
    */
    // public function row($filter = false, $cols = false) {
    public function row($where = false) {
        $this->initialize();

        $cache_key = serialize($where);

        if (!isset($this->cache[$cache_key])) {
            $this->data_source->_reset();
            $this->join_joins();

            if ($this->group) {
                $this->data_source->_group($this->group);
            }
            $this->cache[$cache_key] = $this->data_source->_where($this->where)->_having($this->having)->_arow($where, $this->columns, 'details');
        }
        return $this->cache[$cache_key];
    }

    public function do_adjust_details_tpl_data($tpl_data) {
        if (method_exists($this->_get_holder(), $this->callback_adjust_details_tpl_data)) {
            return call_user_func_array(array($this->_get_holder(), $this->callback_adjust_details_tpl_data), array($tpl_data));
        }
        else {
            return $this->adjust_details_tpl_data($tpl_data);
        }
    }

    public function adjust_details_tpl_data($tpl_data) {
        $this->initialize();

        return $tpl_data;
    }


    public function get_page() {
        $this->initialize();

        return $this->page;
    }

    public function get_action() {
        $this->initialize();

        return $this->action;
    }

    public function get_mode() {
        $this->initialize();

        return $this->mode;
    }

    public function get_id() {
        $this->initialize();

        return $this->id;
    }

    public function get_id_as_param() {
        $this->initialize();

        $id = $this->get_id();

        if (is_array($id)) {
            $id = implode(',', $id);
        }

        return $id;
    }

    public function get_title() {
        $this->initialize();

        return $this->title;
    }

    public function get_data_source() {
        $this->initialize();

        return $this->data_source;
    }

    public function get_order() {
        return $this->order;
    }

    public function get_group() {
        return $this->group;
    }

    /**********************************************************************
    * функции для получения параметров, которые нужны modDataGrid, что бы оставаться в 
    * текущем состоянии. Используются, что бы прикреплять их к ссылкам, генерируемым во 
    * внешнем модуле
    ***********************************************************************/

    public function get_previous_link($id) {
        return $this->internal_get_previous_link('details_link', $id);
    }

    public function get_previous_hlink($id) {
        return $this->internal_get_previous_link('details_hlink', $id);
    }

    protected function internal_get_previous_link($method, $id) {
        $this->join_joins();
        $this->data_source->_order($this->order);
        if ($this->group) {
            $this->data_source->_group($this->group);
        }
        $prev_id = $this->data_source->_where($this->where)->_having($this->having)->_previous_row($id, $this->data_source->_get_primary_key());
        if ($prev_id) {
            return call_user_func_array(
                array($this, $method),
                array(
                    $prev_id
                )
            );
        }
        else {
            return false;
        }
    }

    public function get_next_link($id) {
        return $this->internal_get_next_link('details_link', $id);
    }

    public function get_next_hlink($id) {
        return $this->internal_get_next_link('details_hlink', $id);
    }

    protected function internal_get_next_link($method, $id) {
        $this->join_joins();
        $this->data_source->_order($this->order);
        if ($this->group) {
            $this->data_source->_group($this->group);
        }
        $next_id = $this->data_source->_where($this->where)->_having($this->having)->_next_row($id, $this->data_source->_get_primary_key());
        if ($next_id) {
            return call_user_func_array(
                array($this, $method),
                array(
                    $next_id
                )
            );
        }
        else {
            return false;
        }
    }

    public function grid_link($params = array()) {
        if (_is_ssl_request()) {
            $method = '_ssl_link';
        }
        else {
            $method = '_link';
        }
        return $this->internal_grid_link($params, $method);
    }

    public function grid_hlink($params = array()) {
        if (_is_ssl_request()) {
            $method = '_ssl_hlink';
        }
        else {
            $method = '_hlink';
        }
        return $this->internal_grid_link($params, $method);
    }

    protected function internal_grid_link($params = array(), $method = '_hlink') {
        $this->initialize();

        if ($this->default_mode == 'grid') {
            $mode = false;
            // $is_ajax = $this->controls['grid']['ajax'] || $this->controls['grid']['popup'] ? 'yes' : false;
        }
        else {
            $mode = 'grid';
            // $is_ajax = false;
        }
        $is_ajax = $this->controls['grid']['ajax'] || $this->controls['grid']['popup'] ? 'yes' : false;

        return call_user_func_array(
            array($this, $method), 
            array(
                array_merge(
                    $params,
                    array(
                        $this->param_page => $this->page ? $this->page : false,
                        $this->param_mode => $mode,
                        $this->param_action => false,
                        $this->param_id => false,
                        $this->param_is_ajax_request => $is_ajax
                    )
                )
            )
        );
    }

    public function details_link($id, $params = array()) {
        if (_is_ssl_request()) {
            $method = '_ssl_link';
        }
        else {
            $method = '_link';
        }
        return $this->internal_details_link($id, $params, $method);
    }

    public function details_hlink($id, $params = array()) {
        if (_is_ssl_request()) {
            $method = '_ssl_hlink';
        }
        else {
            $method = '_hlink';
        }
        return $this->internal_details_link($id, $params, $method);
    }

    protected function internal_details_link($id, $params = array(), $method = '_hlink') {
        $this->initialize();

        if ($this->default_mode == 'details') {
            $mode = false;
            // $is_ajax = $this->controls['details']['ajax'] || $this->controls['details']['popup'] ? 'yes' : false;
        }
        else {
            $mode = 'details';
            // $is_ajax = false;
        }
        $is_ajax = $this->controls['details']['ajax'] || $this->controls['details']['popup'] ? 'yes' : false;

        return call_user_func_array(
            array($this, $method), 
            array(
                array_merge(
                    $params,
                    array(
                        $this->param_page => $this->use_cookie_page || !$this->page ? false : $this->page,
                        $this->param_mode => $mode,
                        $this->param_action => false,
                        $this->param_id => $id,
                        $this->param_is_ajax_request => $is_ajax
                    )
                )
            )
        );
    }

    public function action_link($id, $act, $add_mode = false, $params = array()) {
        if (_is_ssl_request()) {
            $method = '_ssl_link';
        }
        else {
            $method = '_link';
        }
        return $this->internal_action_link($id, $act, $add_mode, $params, $method);
    }

    public function action_hlink($id, $act, $add_mode = false, $params = array()) {
        if (_is_ssl_request()) {
            $method = '_ssl_hlink';
        }
        else {
            $method = '_hlink';
        }
        return $this->internal_action_link($id, $act, $add_mode, $params, $method);
    }

    public function internal_action_link($id, $act, $add_mode = false, $params = array(), $method = '_hlink') {
        $this->initialize();

        $is_ajax = $this->controls[$act]['ajax'] || $this->controls[$act]['popup'] ? 'yes' : false;
        
        
        
        return call_user_func_array(
            array($this, $method), 
            array(
                array_merge(
                    $params,
                    array(
                        $this->param_page => $this->use_cookie_page || !$this->page ? false : $this->page,
                        $this->param_mode => $add_mode,
                        $this->param_action => $act,
                        $this->param_id => $id,
                        $this->param_is_ajax_request => $is_ajax
                    )
                )
            )
        );
    }

    /**
    * Получить параметры режима grid
    * 
    * @param boolean $values Если true (по-умолчанию) - вернет значения для всех параметров, иначе вернет false (удобно что бы убрать прикрепленные ранее параметры)
    * @return array
    */
    public function get_grid_params($values = true) {
        $this->initialize();

        $ret = array();
        if ($values) {
            $current_page = $this->get_page();
            if ($current_page != 0) {
                $ret[$this->param_page] = $current_page;
            }
        }
        else {
            $ret[$this->param_page] = false;
        }

        foreach ($this->plugins_modules as $plugin) {
            $ret = $plugin->adjust_grid_params($ret, $values);
        }
        return $ret;
    }

    /**
    * Получить параметры режима details + grid 
    * (параметры grid добавляются только если $values == true)
    * 
    * @param boolean $values Если true (по-умолчанию) - вернет значения для всех параметров, иначе вернет false (удобно что бы убрать прикрепленные ранее параметры)
    * @return array
    */
    public function get_details_params($values = true) {
        $this->initialize();

        if ($values) {
            if ($this->get_mode() == 'details') {
                $ret = array_merge($this->get_grid_params(), array(
                    $this->param_is_ajax_request => isset($this->controls['details']) && ($this->controls['details']['ajax'] || $this->controls['details']['popup']),
                    $this->param_mode => $this->get_mode(),
                    $this->param_id => $this->get_id(),
                    $this->param_page => $this->use_cookie_page || !$this->page ? false : $this->page,
                ));
            }
            else {
                $ret = array_merge($this->get_grid_params(), array(
                    $this->param_page => $this->use_cookie_page || !$this->page ? false : $this->page,
                ));
            }
        }
        else {
            $ret = array(
                $this->param_mode => false,
                $this->param_id => false
            );
            if (isset($this->controls['details']) && !$this->controls['details']['ajax'] && !$this->controls['details']['popup']) {
                $ret[$this->param_is_ajax_request] = false;
            }
        }

        foreach ($this->plugins_modules as $plugin) {
            $ret = $plugin->adjust_details_params($ret, $values);
        }
        return $ret;
    }

    /**
    * Получить параметры текущего action + параметры режимов details и grid
    * (параметры details и grid добавляются только если $values == true)
    * 
    * @param boolean $values Если true (по-умолчанию) - вернет значения для всех параметров, иначе вернет false (удобно что бы убрать прикрепленные ранее параметры)
    * @return array
    */
    public function get_action_params($values = true) {
        $this->initialize();

        if ($values) {
            $ret = array(
                $this->param_is_ajax_request => $this->get_action() && isset($this->controls[$this->get_action()]) && ($this->controls[$this->get_action()]['ajax'] || $this->controls[$this->get_action()]['popup']),
                $this->param_action => $this->get_action(),
                $this->param_id => $this->get_id_as_param()
            );
            $ret = array_merge($ret, $this->get_details_params());
        }
        else {
            $ret = array(
                $this->param_action => false
            );
            if ($this->get_mode() == 'grid') {
                $ret[$this->param_id] = false;
            }
            if (
                $this->get_action() &&
                ($this->controls[$this->get_action()]['ajax'] || $this->controls[$this->get_action()]['popup']) &&
                ($this->get_mode() != 'details' || (!$this->controls['details']['ajax'] && !$this->controls['details']['popup']))
            ) {
                $ret[$this->param_is_ajax_request] = false;
            }
        }

        foreach ($this->plugins_modules as $plugin) {
            $ret = $plugin->adjust_action_params($ret, $values);
        }
        return $ret;
    }

    public function get_sticky_params($values = true) {
        $this->initialize();

        return array_merge(
            $this->get_grid_params($values),
            $this->get_details_params($values),
            $this->get_action_params($values)
        );
    }

    /**
    * добавление хлебных крошек, вызывается колбек (если есть) или встроенный метод
    */
    public function do_create_breadcrumbs($type, $params = array()) {
        if (method_exists($this->_get_holder(), $this->callback_create_breadcrumbs)) {
            return call_user_func_array(array($this->_get_holder(), $this->callback_create_breadcrumbs), array(
                $type, $params, $this
            ));
        } else {
            return $this->create_breadcrumbs(
                $type, $params
            );
        }
    }

    /**
    * встроенный метод добавления хлебных крошек
    */
    public function create_breadcrumbs($type, $params = array()) {
        $this->initialize();

        if ($type == 'mode') {
            if ($params['mode'] == 'grid') {
                $breadcrumbs = array(
                    array(
                        'title' => isset($params['title']) ? $params['title'] : ($this->title ? $this->title : $this->_get_holder()->_get_title()),
                        'link' => $this->_hlink($params['params']),
                        'params' => $params['params']
                    )
                );
            }
            else if ($params['mode'] == 'details') {
                $breadcrumbs = array(
                    array(
                        'title' => isset($params['title']) ? $params['title'] : $this->data_source->_get_row_title($params['id']),
                        'link' => $this->_hlink($params['params']),
                        'params' => $params['params']
                    )
                );
            }
            else {
                $breadcrumbs = array();
            }
        }
        else if ($type == 'action') {
            if (isset($params['title'])) {
                $pattern = $params['title'];
            }
            else if (isset($this->controls[$params['action']]['title_breadcrumb']) && $this->controls[$params['action']]['title_breadcrumb']) {
                $pattern = $this->controls[$params['action']]['title_breadcrumb'];
            }
            else if (isset($this->{'title_' . $params['action']}) && $this->{'title_' . $params['action']}) {
                $pattern = $this->{'title_' . $params['action']};
            }
            else if (isset($this->lang['TITLE_' . strtoupper($params['action'])])) {
                $pattern = $this->lang['TITLE_' . strtoupper($params['action'])];
            }
            else if (isset($this->controls[$params['action']]['title']) && $this->controls[$params['action']]['title']) {
                $pattern = sprintf($this->lang['TITLE_CUSTOM_ACTION'], $this->controls[$params['action']]['title']);
            }
            else {
                $pattern = sprintf($this->lang['TITLE_CUSTOM_ACTION'], ucfirst($params['action']));
            }
            /*
            else {
                $pattern = $this->lang['TITLE_CUSTOM_ACTION'];
            }
            */
            if (isset($params['id']) && $params['id']) {
                $title = sprintf($pattern, $this->data_source->_get_row_title($params['id']));
            }
            else {
                $title = $pattern;
            }

            $breadcrumbs = array(
                array(
                    'title' => $title,
                    'link' => $this->_hlink($params['params']),
                    'params' => $params['params']
                )
            );
            /*
            if ($params['action'] == 'add') {
                $breadcrumbs = array(
                    array(
                        'title' => isset($params['title']) ? $params['title'] : ($this->title_add ? $this->title_add : $this->lang['TITLE_ADD']),
                        'link' => $this->_hlink($params['params']),
                        'params' => $params['params']
                    )
                );
            }
            else if ($params['action'] == 'edit') {
                if (isset($params['title'])) {
                    $title = $params['title'];
                }
                else if ($this->title_edit) {
                    $title = sprintf($this->title_edit, $this->data_source->_get_row_title($params['id']));
                }
                else {
                    $title = sprintf($this->lang['TITLE_EDIT'], $this->data_source->_get_row_title($params['id']));
                }
                $breadcrumbs = array(
                    array(
                        'title' => $title,
                        'link' => $this->_hlink($params['params']),
                        'params' => $params['params']
                    )
                );
            }
            else {
                // 2012-02-21
                // $breadcrumbs = array();
                if (isset($params['title'])) {
                    $title = $params['title'];
                }
                else if ($this->title_edit) {
                    $title = $this->title_edit;
                }
                else {
                    $title = sprintf($this->lang['TITLE_EDIT'], $this->data_source->_get_row_title($params['id']));
                }
                $breadcrumbs = array(
                    array(
                        'title' => isset($params['title']) ? $params['title'] : $this->controls[$params['action']]['title'] . ' for "' . $this->data_source->_get_row_title($params['id']) . '"',
                        'link' => $this->_hlink($params['params']),
                        'params' => $params['params']
                    )
                );
            }
            */
        }
        else {
            $breadcrumbs = array();
        }
        foreach ($this->plugins_modules as $plugin) {
            $breadcrumbs = $plugin->adjust_create_breadcrumbs($type, $params, $breadcrumbs);
        }
        $this->_append_breadcrumbs($breadcrumbs);
    }

    /**********************************************************************
    * функции для работы с ajax / popup
    ***********************************************************************/

    public function get_js_instance() {
        return $this->prefix_params . 'DGInstance';
    }

    public function get_ajax_handler() {
        return $this->get_js_instance() . '.handleAjax';
    }

    /**
    * отправляет команду закрыть попап
    */
    public function ajax_popup_close($level = 1) {
        _overwhelm_response(json_encode(array(
            'type' => 'grid_js',
            'content' => $this->get_js_instance() . '.popupModalHide(' . $level . ')'
        )), 'application/json');
    }

    /**
    * отправляет команду закрыть попап и обновить содержимое грида
    */
    public function ajax_popup_close_content_reload($level = 1) {
        // 2012-04-03
        /*
        if ($this->get_mode() == 'details') {
            $params = $this->get_details_params();
        }
        else {
            $params = $this->get_grid_params();
        }
        */
        // 2012-04-04
        if (_is_ssl_request()) {
            $method = '_ssl_link';
        }
        else {
            $method = '_link';
        }
        
        _overwhelm_response(json_encode(array(
            'type' => 'grid_js',
            // 'content' => $this->get_js_instance() . '.popupModalHide(' . $level . '); ' . $this->get_js_instance() . '.loadContent(\'' . $this->_link(array_merge($params, array($this->param_is_ajax_request => 'yes'))) . '\');'
            // 2012-04-04
            // 'content' => $this->get_js_instance() . '.popupModalHide(' . $level . '); ' . $this->get_js_instance() . '.loadContent(\'' . $this->_link(array($this->param_is_ajax_request => 'yes')) . '\');'
            'content' => $this->get_js_instance() . '.popupModalHide(' . $level . '); ' . $this->get_js_instance() . '.loadContent(\'' . $this->$method(array_merge($this->get_action_params(false), array($this->param_is_ajax_request => 'yes'))) . '\');'
        )), 'application/json');
    }

    /**
    * отправляет команду обновить содержимое грида
    */
    public function ajax_content_reload() {
        // 2012-04-03
        /*
        if ($this->get_mode() == 'details') {
            $params = $this->get_details_params();
        }
        else {
            $params = $this->get_grid_params();
        }
        */
        if (_is_ssl_request()) {
            $method = '_ssl_link';
        }
        else {
            $method = '_link';
        }
        _overwhelm_response(json_encode(array(
            'type' => 'grid_js',
            // 'content' => $this->get_js_instance() . '.loadContent(\'' . $this->_link(array_merge($params, array($this->param_is_ajax_request => 'yes'))) . '\');'
            // 2012-04-04
            // 'content' => $this->get_js_instance() . '.loadContent(\'' . $this->_link(array($this->param_is_ajax_request => 'yes')) . '\');'
            'content' => $this->get_js_instance() . '.loadContent(\'' . $this->$method(array_merge($this->get_action_params(false), array($this->param_is_ajax_request => 'yes'))) . '\');'
        )), 'application/json');
    }

    protected function ajax_get_response_level() {
        $level = 1;
        if ($this->default_mode == 'grid') {
            $test_ctrl = 'details';
        }
        else {
            $test_ctrl = 'grid';
        }
        if ($this->get_mode() != $this->default_mode && isset($this->controls[$test_ctrl]) && $this->controls[$test_ctrl]['popup']) {
            $level++;
        }
        return $level;
    }
}


