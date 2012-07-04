<?php
/**
* @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
* @version 1.0
* @package modDataGrid
*/

/**
* Базовый класс для всех расширений modDataGrid
* 
* Реализует пустые заглушки для всех вызовов modDataGrid,
* что бы при реализации расширения объявлять только необходимые 
* методы
*/
class modDGPluginBase extends _module {
    /**
    * Получаем из создающего modDataGrid
    */
    protected $prefix_params = false;
    /**
    * Получаем из создающего modDataGrid
    */
    protected $prefix_callbacks = false;

    /**
    * возвращает параметры, которые modDataGrid должен дописать к своим 
    * grid параметрам (см. соответсвующий метод в modDataGrid
    */
    public function adjust_grid_params($params, $values = true) {
        return $params;
    }

    /**
    * возвращает параметры, которые modDataGrid должен дописать к своим  
    * details параметрам (см. соответсвующий метод в modDataGrid
    */
    public function adjust_details_params($params, $values = true) {
        return $params;
    }

    /**
    * возвращает параметры, которые modDataGrid должен дописать к своим  
    * action параметрам (см. соответсвующий метод в modDataGrid
    */
    public function adjust_action_params($params, $values = true) {
        return $params;
    }

    /**
    * дополняет переменные шаблона modDataGrid в режиме grid
    */
    public function adjust_grid_tpl_data($data) {
        return $data;
    }

    /**
    * форматирование данных перед выводом
    */
    public function adjust_output($in, $mode) {
        return $in;
    }

    /**
    * дополняет массив where
    */
    public function adjust_where($in) {
        return $in;
    }

    /**
    * дополняет массив having
    */
    public function adjust_having($in) {
        return $in;
    }

    /**
    * дополняет массив filter
    */
    public function adjust_filter_where($in) {
        return $in;
    }

    public function adjust_filter_having($in) {
        return $in;
    }

    /**
    * дополняет массив join
    */
    public function adjust_join($in) {
        return $in;
    }

    /**
    * дополняет массив order
    */
    public function adjust_order($in) {
        return $in;
    }

    /**
    * дополняет массив group
    */
    public function adjust_group($in) {
        return $in;
    }

    /**
    * дополняет хлебные крошки, сгенерированные modDataGrid
    */
    public function adjust_create_breadcrumbs($type, $params, $in) {
        return $in;
    }

    /**
    * оборачивает шаблон modDataGrid в режиме grid
    */
    public function wrap_grid_tpl($tpl) {
        return $tpl;
    }

    /**
    * дополняет значения, введеные пользователем при добавлении
    * 
    * @param array $values
    * @return array
    */
    public function adjust_before_insert($values) {
        return $values;
    }

    /**
    * Вызывается сразу после добавления данных в БД, передается созданый ID и значения, 
    * введеные пользователем 
    * 
    * @param mixed $id
    * @param mixed $values
    */
    public function execute_after_insert($id, $values) {
    }

    /**
    * дополняет список полей при редактировании
    * 
    * @param array $fields чистый список полей
    * @param mixed $id ID строки, которая редактируется
    * @param mixed $values значения, полученые с предыдущих шагов при многошаговом добавлении
    * @return array дополненный список полей
    */
    public function adjust_fields($fields, $id, $values, $step, $form, $submits) {
        return $fields;
    }

    /**
    * дополняет значения, введеные пользователем при редактировании
    * 
    * @param array $values
    * @return array
    */
    public function adjust_before_update($id, $values) {
        return $values;
    }

    /**
    * Вызывается сразу после обновления данных в БД, передается ID и значения, 
    * введеные пользователем 
    * 
    * @param mixed $id
    * @param mixed $values
    */
    public function execute_after_update($id, $values) {
    }

    /**
    * Вызывается перед удалением данных из БД; должен вернуть ID для удаления или false, если 
    * удаление нужно отменить
    * 
    * @param mixed $id
    */
    public function verify_before_delete($id) {
        return $id;
    }

    /**
    * Вызывается после удаления данных из БД
    * 
    * @param mixed $id
    */
    public function execute_after_delete($id) {
    }


}
