<?php
/**
* @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
* @version 1.0
* @package Users
*/

class users_data extends _db_table {
    /** @var int уровень доступа, которому соответсвует данная таблица */
    protected $level = false;
    protected $lang_name = false;
    /** @var users */
    protected $users = false;
    /** @var boolean флаг, позволяющий избежать зацикливания при insert/update/delete*/
    protected $prevent_recursion_flag = false;

    /**
    * подготовим внутренние свойства, которые будем использовать в работе
    */
    public function __construct($data = array()) {
        parent::__construct($data);

        if (!$this->level && !$this->_table) {
            _cc::fatal_error(_DEBUG_CC, '$this->level or $this->_table should be set for child of user_data (<b>' . get_class($this) . '</b> class)');
        }
        if (!$this->level) {
            $this->level = $this->user_access_levels->_row(array('al_table' => $this->_table), 'al_level');
        }
        else if (!$this->_table) {
            $this->_table = $this->user_access_levels->_row(array('al_level' => $this->level), 'al_table');
        }
        $this->level = (int)$this->level;

        if (!$this->lang_name) {
            $this->lang_name = _cc::get_config('_project', '_language');
        }
        $this->users = $this->_create_data_source('users', array(
            'lang_name' => $this->lang_name
        ));
        $this->lang = $this->_load_language($this->lang_name);
        $this->_apply_language($this->lang);
    }

    /**
    * Возвращает свои поля и поля users
    */
    public function _get_fields($vals = array(), $incl = false, $excl = false) {
        return array_merge(
            $this->users->_get_fields($vals, $incl, $excl),
            parent::_get_fields($vals, $incl, $excl)
        );
    }

    /**
    * Добавляется новый пользователь в текущий датасорц
    * Если среди ключей $values нет user_id, то считается, что пользователь регистрируется первый 
    * раз и для него создается запись в датасорце user
    */
    public function _insert($values) {
        if (isset($values['user_id'])) {
            $values[$this->_get_primary_key()] = $values['user_id'];
        }
        else if (isset($values[$this->_get_primary_key()])) {
            $values['user_id'] = $values[$this->_get_primary_key()];
        }
        if (
            $this->prevent_recursion_flag 
            // на всякий случай
            && isset($values['user_id'])
        ) {
            $this->prevent_recursion(false);
            return parent::_insert($values);
        }
        else {
            $values['user_level'] = $this->level;
            return $this->users->_insert($values);
        }
    }

    /**
    * Обновление данных о пользователе в данном датасорце и в users
    */
    public function _update($where, $values) {
        if ($this->prevent_recursion_flag) {
            $this->prevent_recursion(false);
            return parent::_update($where, $values);
        }
        else {
            $ids = $this->_ids($where);
            $this->users->_update($ids, $values);
        }
        return $this;
    }

    /**
    * Удаление пользователя из текущего датасорца
    * Если новый user_level = 0, то и из датасорца user соответствующая запись тоже удаляется
    */
    public function _delete($where = false, $allow_truncate = false) {
        if ($this->prevent_recursion_flag) {
            $this->prevent_recursion(false);
            return parent::_delete($where, $allow_truncate);
        }
        else {
            $ids = $this->_ids($where);

            $users = $this->users->_rows($ids, 'user_id, user_level');
            foreach ($users as $u) {
                $new_level = (int)$u['user_level'] ^ $this->level; 
                if ($new_level) {
                    $this->users->_update($u['user_id'], array('user_level' => $new_level));
                }
                else {
                    $this->users->_delete($u['user_id']); 
                }
            }
        }
        return $this;
    }

    /**
    * К любым выбираемым данным джоинятся данные из user
    */
    public function _select($columns, $adjusted = false, $mode = false) {
        $this->_join($this->users);
        return $this->_low_select($columns, $adjusted, $mode);
    }

    /**
    * Объединяется внутренняя валидация и валидация из users
    */
    public function _validate_input($id, $values) {
        return array_merge(
            $this->users->_validate_input($id, $values),
            parent::_validate_input($id, $values)
        );
    }

    /**
    * управление флагом, предотвращающим рекурсивное зацикливание при вызове insert/update/delete из users
    * метод не должен вызываться ниоткуда, кроме users_foundation, users_data и их наследников
    * 
    * @param mixed $flag
    */
    public function prevent_recursion($flag = true) {
        $this->prevent_recursion_flag = $flag;
        return $this;
    }

    public function _is_active($where) {
        return $this->users->_is_active($where);
    }

    public function _activate($where) {
        return $this->users->_activate($where);
    }

    public function _deactivate($where) {
        return $this->users->_deactivate($where);
    }

}

