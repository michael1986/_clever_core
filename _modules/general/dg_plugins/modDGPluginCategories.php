<?php
/**
 * @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
 * @package modDataGrid
 */

_cc::load_module('general/dg_plugins/modDGPluginBase');

/**
* Плагин к modDataGrid, позволяет структурировать данные в древовидной структуре категорий
* не вмешиваясь в структуру таблицы с данными
* 
* Для корректной работы требуется 2 дополнительные таблицы:
*   - таблица категорий (по-умолчанию categories); в классе data_source к ней parent_id должен 
*       обязательно быть помечен как foreign_key, ссылающийся на эту же таблицу, т.е. указан 
*       ключ 'foreign_table' => '[categories_table]'; а так же он должен быть унаследован 
*       не от _db_table, а от db_table_tree
*   - таблица связей категорий и данных (по-умолчанию categories_items);  в классе data_source к 
*       ней обязательно должны быть помечены поля ci_item_id и ci_cat_id (имена вероятно будут 
*       другими) как foreign_key, ссылающиеся на таблицы items и categories (имена вероятно будут 
*       другими), т.е. указаны ключи 
*           'foreign_table' => '[categories_table]'
*           'foreign_table' => '[items_table]'
*/
class modDGPluginCategories extends modDGPluginBase {
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
    * items to categories data_source name
    */
    protected $data_source_categories_items = 'categories_items';
    /**
    * items to categories data_source object
    * 
    * @var _db_table
    */
    protected $categories_items = false;
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
    protected $field_categories_parent_id = false;//'cat_parent_id';
    /**
    * название поля из таблицы categories_items, которое хранит в себе id категори
    * заполняется автоматически из data_source
    */
    protected $field_categories_items_cat_id = false;//'ci_cat_id';
    /**
    * название поля из таблицы categories_items, которое хранит в себе id item
    * заполняется автоматически из data_source
    */
    protected $field_categories_items_item_id = false;//'ci_item_id';
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

    public function __construct($data = array()) {
        parent::__construct($data);

        $this->param_category_id = $this->prefix_params . $this->param_category_id;
        $this->category_id = $this->_read_sticky_param($this->param_category_id);

        // приклеить категорию к держащему датагриду, что бы все последующие плагины содержали в 
        // своих линках этот параметр
        $this->_get_holder()->_stick_param($this->param_category_id, $this->category_id);

        $this->categories = $this->_create_data_source($this->data_source_categories);
        $this->categories_items = $this->_create_data_source($this->data_source_categories_items);

        $this->items = $this->_get_holder()->get_data_source();

        $ci_fields = $this->categories_items->_get_fields();
        foreach ($ci_fields as $ci_field) {
            if (isset($ci_field['name']) && isset($ci_field['foreign_table'])) {
                if ($ci_field['foreign_table'] == $this->categories->_get_table()) {
                    $this->field_categories_items_cat_id = $ci_field['name'];
                }
                else if ($ci_field['foreign_table'] == $this->items->_get_table()) {
                    $this->field_categories_items_item_id = $ci_field['name'];
                }
            }
        }
        if (method_exists($this->categories, 'get_field_parent_id')) {
            $this->field_categories_parent_id = $this->categories->get_field_parent_id();
        }

        if (!$this->field_categories_items_cat_id) {
            _cc::fatal_error(_DEBUG_CC, 'CC error. Unable to determine "cat_id" field inside the <b>' . $this->data_source_categories_items . '</b> data_source.');
        }
        if (!$this->field_categories_items_item_id) {
            _cc::fatal_error(_DEBUG_CC, 'CC error. Unable to determine "item_id" field inside the <b>' . $this->data_source_categories_items . '</b> data_source.');
        }
        if (!$this->field_categories_parent_id) {
            _cc::fatal_error(_DEBUG_CC, 'CC error. Unable to determine "parent_id" field inside the <b>' . $this->data_source_categories . ' data_source</b>');
        }

        // build parent categories queue
        $this->parent_queue = $this->categories->get_parents($this->category_id);//, 'Root');

        // Не возможно (потому что не имеет смысла) сохранять параметры главной сетки данных при 
        // выполнении каких-то действия кроме переходов внутри категорий, т.к. любые действия с 
        // категориями могут вести к изменению самой сетки
        // $this->_stick_params($this->_get_holder()->get_grid_params());

        $this->dg = $this->_module('modDataGrid', array(
            '_templates_dir' => 'categories',
            'data_source' => $this->categories,
            'prefix_params' => $this->prefix_params . $this->own_prefix_params,
            'prefix_callbacks' => 'cat_',
            'where' => array($this->field_categories_parent_id => $this->category_id),
            'controls' => array('add', 'edit', 'delete')
        ));
    }

    /**********************************************************************
    * колбеки, вызываемые внутренним modDataGrid
    ***********************************************************************/

    public function cat_adjust_output($in) {
        $in = $this->categories->_adjust_output($in);
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
        $items = $this->categories_items->_join($this->items)->_rows(array(
            $this->field_categories_items_cat_id => $id
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

    public function adjust_join($in) {
        $in[] = $this->data_source_categories_items;
        return $in;
    }

    public function adjust_where($in) {
        $in[$this->data_source_categories_items . '.' . $this->field_categories_items_cat_id] = $this->category_id;
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

    public function execute_after_insert($id, $values) {
        $this->categories_items->_insert(array(
            $this->field_categories_items_item_id => $id,
            $this->field_categories_items_cat_id => $this->category_id
        ));
    }

    public function verify_before_delete($id) {
        $this->categories_items->_delete(array(
            $this->field_categories_items_item_id => $id,
            $this->field_categories_items_cat_id => $this->category_id
        ));
        if ($this->categories_items->_row(array($this->field_categories_items_item_id => $id))) {
            return false;
        }
        else {
            return $id;
        }
    }

}


