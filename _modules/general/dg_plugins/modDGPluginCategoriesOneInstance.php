<?php
/**
* @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
* @version 1.0
* @package modDataGrid
*/

_cc::load_module('general/dg_plugins/modDGPluginBase');

/**
* Для работы плагина необходимо проинициализировать датагрид плагином
*             'plugins' => array(
*                 ...
*                 'categories_one_instance' => array(
*                     'data_source_categories' => ...
*                     'controls' => array('add', 'edit', 'move_up', 'move_down', 'delete')
*                 )
*                 ...
*             ),
* 'data_source_categories' - имя таблицы, в которой хранятся категории, при этом в основной 
*               таблице должно быть поле, ссылающееся на эту таблицу с категориями (foreign_table)
*/
class modDGPluginCategoriesOneInstance extends modDGPluginBase {
    /**
    * name of HTTP parameter, used to track category_id
    * 
    * @var mixed
    */
    protected $param_category_id = 'category_id';
    /**
    * current category ID
    * 
    * @var mixed
    */
    protected $category_id;
    /**
    * parent categories queue
    * 
    * @var array
    */
    protected $parent_queue = array();

    /**
    * categories data_source name
    */
    protected $data_source_categories = 'categories';
    /**
    * categories data_source object
    * 
    * @var _db_table
    */
    protected $categories = false;
    /**
    * items data_source object
    * 
    * @var _db_table
    */
    protected $items = false;
    /**
    * название поля из таблицы categories, которое хранит в себе id вышестоящего элемента
    * заполняется автоматически из data_source
    */
    protected $field_categories_parent_id = false;
    /**
    * название поля из таблицы items, которое хранит в себе id категории, к которой относится 
    * данный элемент
    * заполняется автоматически из data_source
    */
    protected $field_items_category_id = false;
    /**
    * data_grid for categories
    * 
    * @var modDataGrid
    */
    protected $dg = false;
    /**
    * префикс параметров для собственного data_grid
    */
    protected $own_prefix_params = 'cat_';
    protected $columns = false;
    protected $controls = false;
    protected $controls_settings = array();
    protected $title_add = false;
    protected $title_edit = false;
    // 2010.11.01 Art Добавил ключ per_row
    /**
    * количество в строке
    * 
    * @var int
    */
    protected $per_row = 1;
    protected $orientation = 'horizontal';

    public function __construct($data = array()) {
        parent::__construct($data);

        $this->param_category_id = $this->prefix_params . $this->param_category_id;
        $this->category_id = $this->_read_sticky_param($this->param_category_id);

        // приклеить категорию к держащему датагриду, что бы все последующие плагины содержали в 
        // своих линках этот параметр
        $this->_get_holder()->_stick_param($this->param_category_id, $this->category_id);

        $this->categories = $this->_create_data_source($this->data_source_categories);
        $this->items = $this->_get_holder()->get_data_source();

        $ci_fields = $this->items->_get_fields();
        foreach ($ci_fields as $ci_field) {
            if (isset($ci_field['name']) && isset($ci_field['foreign_table'])) {
                if ($ci_field['foreign_table'] == $this->categories->_get_table()) {
                    $this->field_items_category_id = $ci_field['name'];
                    break;
                }
            }
        }
        if (method_exists($this->categories, 'get_field_parent_id')) {
            $this->field_categories_parent_id = $this->categories->get_field_parent_id();
        }
        else {
            $ci_fields = $this->categories->_get_fields();
            foreach ($ci_fields as $ci_field) {
                if (isset($ci_field['name']) && isset($ci_field['foreign_table'])) {
                    if ($ci_field['foreign_table'] == $this->categories->_get_table()) {
                        $this->field_categories_parent_id = $ci_field['name'];
                        break;
                    }
                }
            }
        }

        if (!$this->field_items_category_id) {
            _cc::fatal_error(_DEBUG_CC, 'CC error. Unable to determine "category_id" field inside the <b>' . $this->items->_get_table() . '</b> data_source.');
        }
        if (!$this->field_categories_parent_id) {
            _cc::fatal_error(_DEBUG_CC, 'CC error. Unable to determine "parent_id" field inside the <b>' . $this->categories->_get_table() . ' data_source</b>');
        }

        // build parent categories queue
        $this->parent_queue = $this->categories->get_parents($this->category_id);//, 'Root');

        // Не возможно (потому что не имеет смысла) сохранять параметры главной сетки данных при 
        // выполнении каких-то действия кроме переходов внутри категорий, т.к. любые действия с 
        // категориями могут вести к изменению самой сетки
        // $this->_stick_params($this->_get_holder()->get_grid_params());

        if ($this->columns) {
            $plugins = array(
                'columns' => array(
                    'description' => $this->columns
                )
            );
        }
        else {
            $plugins = false;
        }
        $this->dg = $this->_module('modDataGrid', array(
            '_templates_dir' => 'categories',
            // 2010.11.01 Art Добавил недостающие ключи 
            'per_row' => $this->per_row,
            'orientation' => $this->orientation,
            'controls_settings' => $this->controls_settings,
            'title_add' => $this->title_add,
            'title_edit' => $this->title_edit,
            // ----
            'data_source' => $this->categories,
            'prefix_params' => $this->prefix_params . $this->own_prefix_params,
            'prefix_callbacks' => 'cat_',
            'where' => array($this->field_categories_parent_id => $this->category_id),
            'controls' => $this->controls ? $this->controls : false,
            'plugins' => $plugins
        ));
    }

    /***********************************************************************************************
    * колбеки, вызываемые внутренним modDataGrid
    ***********************************************************************************************/

    public function cat_adjust_output($in) {
        // $in = $this->categories->_adjust_output($in);
        $in['link'] = $this->_get_link(array($this->param_category_id => $in[$this->categories->_get_primary_key()]));
        return $in;
    }

    public function cat_create_breadcrumbs($type, $params) {
        if ($type != 'mode' || $params['mode'] != 'grid') {
            $this->dg->create_breadcrumbs($type, $params);
        }
    }

    /**
    * Удаление реализуется НЕ средствами data_source, т.к. есть вероятность, что удаление items так же 
    * реализовано НЕ средствами data_source (а средствами модуля, который вызывает modDataGrid, т.е. 
    * в нем переопределены колбеки удаления)
    */
    public function cat_delete($id) {
        // рекурсивно удалить все подкатегории
        $categories = $this->categories->_rows(array(
            $this->field_categories_parent_id => $id
        ), $this->categories->_get_primary_key());

        for ($i = 0; $i < sizeof($categories); $i++) {
            $this->cat_delete($categories[$i]);
        }

        // удалить все items
        $items = $this->items->_rows(array(
            $this->field_items_category_id => $id
        ), $this->items->_get_primary_key());

        // $this->category_id используется в $this->verify_before_delete, что бы удалить
        // строку из categories_items
        // мы на время подставляем удаляемую категорию
        $tmp_category_id = $this->category_id;
        $this->category_id = $id;
        for ($i = 0; $i < sizeof($items); $i++) {
            $this->_get_holder()->do_delete($items[$i]);
        }
        $this->category_id = $tmp_category_id;

        $this->dg->delete($id);
    }

    /**********************************************************************
    * колбеки, вызываемые modDataGrid, к которому относится этот плагин
    ***********************************************************************/

    public function adjust_where($in) {
        $in[$this->field_items_category_id] = $this->category_id;
        return $in;
    }

    public function adjust_grid_tpl_data($in) {
        if ($this->dg->get_action()) {
            $in['plugin_overwhelm'] = $this->dg->_run();
        }
        else {
            $in['categories'] = $this->dg->_run();
        }
        return $in;
    }

    public function adjust_create_breadcrumbs($type, $params, $in) {
        if ($type == 'mode' && $params['mode'] == 'grid') {
            $ret = array(
                array(
                    'title' => $in[0]['title'],
                    'link' => $this->_get_link(
                        array($this->param_category_id => false)
                    )
                )
            );
            for ($i = 0; $i < sizeof($this->parent_queue); $i++) {
                $ret[] = array(
                    'title' => $this->categories->_get_row_title($this->parent_queue[$i]),
                    'link' => $this->_get_link(
                        $i == sizeof($this->parent_queue) - 1 ?
                            array_merge($in[0]['params'], array($this->param_category_id => $this->parent_queue[$i][$this->categories->_get_primary_key()]))
                            :
                            array($this->param_category_id => $this->parent_queue[$i][$this->categories->_get_primary_key()])
                    )
                );
            }
            $ret = array_merge($ret, $this->dg->_get_breadcrumbs());

            return $ret;
        }
        else {
            return $in;
        }
    }

    public function adjust_grid_params($params, $values = true) {
        if ($values) {
            $params[$this->param_category_id] = $this->category_id;
        }
        else {
            $params[$this->param_category_id] = false;
        }
        return $params;
    }

}


