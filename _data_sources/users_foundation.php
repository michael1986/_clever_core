<?php
/**
* @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
* @version 1.0
* @package Users
*/

/**
* 
*/
class users_foundation extends _db_table {

    /**
    * Wether or not login is email
    */
    protected $login_email = false;

    protected $_fields = array(
        'user_id' => array(
            'primary_key' => true,
            'auto_increment' => true,
        ),
        'user_login' => array(
            'title' => 'LOGIN_EMAIL',
            'validate_input' => '_validate_email',
            'error_message' => 'ERR_LOGIN_EMAIL'
        ),
        'user_password' => array(
            'type' => 'password',
            'title' => 'PASSWORD',
            'validate_input' => '_validate_password',
            'error_message' => 'ERR_PASSWORD'
        ),
        'user_level' => array(
            'input_disabled' => true
        ),
        'user_active' => array(
            'input_disabled' => true,
            'active' => true
        ),
        'user_retired' => array(
            'input_disabled' => true
        ),
        'user_created' => array(
            'created' => true
        )
    );
    protected $_table = 'users';
    protected $_prefix_fields = 'user_';

    protected $levels = array();
    protected $lang_name = false;
    protected $lang = array();

    /**
    * подготовим внутренние свойства, которые будем использовать в работе
    */
    public function __construct($data = array()) {
        parent::__construct($data);

        if ($this->login_email) {
            $this->_fields['user_login']['title'] = 'LOGIN_EMAIL';
            $this->_fields['user_login']['validate_input'] = '_validate_email';
            $this->_fields['user_login']['error_message'] = 'ERR_LOGIN_EMAIL';
        }
        else {
            $this->_fields['user_login']['title'] = 'LOGIN';
            $this->_fields['user_login']['validate_input'] = '_validate_empty';
            $this->_fields['user_login']['error_message'] = 'ERR_LOGIN';
        }

        $this->levels = $this->user_access_levels->_rows();
        foreach ($this->levels as &$level) {
            $level['al_level'] = (int)$level['al_level'];
        }
        unset($level);
        if ($this->lang_name) {
            $this->lang = $this->_load_language($this->lang_name);
            $this->_apply_language($this->lang);
        }
    }

    /**
    * Вернет все данные о пользователе с указанным условием, включая данные, полученые из связанных 
    * датасорцев
    */
    public function get_data($where, $mode = false) {
        $this->_save_snapshot('get_data');
        $user_level = (int)$this->_reset()->_row($where, 'user_level');
        $this->_restore_snapshot('get_data');
        foreach ($this->levels as $level) {
            // Было исправлено, чтобы присоеденялись все таблицы, а не только те к которым принадлежит пользователь
            //$temp = $user_level & $level['al_level'];
            //if (($temp == $level['al_level']) && ($level['al_table'])){
            if ($level['al_table']){
                $this->_join($level['al_table'], 'left');
            }
        }
        return $this->_arow($where, $mode);
    }
    
    /**
    * если добавляем нового пользователя, то добавляем данные и в связанных датасорцах по user_levels
    */
    public function _insert($values = array()) {
        if (!isset($values['user_level'])) {
            $values['user_level'] = 0;
        }
        else {
            $values['user_level'] = (int)$values['user_level'];
        }
        $existing_level = 0;
        foreach ($this->levels as $l){
            if ($values['user_level'] & $l['al_level']){
                $existing_level |= $l['al_level'];
            }
        }
        $values['user_level'] = $existing_level;
        if (!$values['user_level']) {
            _cc::fatal_error(_DEBUG_CC, 'Unable to insert user without correct user_level');
        }

        if (isset($values['user_id']) && $this->_row($values['user_id'])) {
            // если insert вызывается из users_data, то для users он может означать update поля user_level и, возможно, других
            $update = $values;
            $update['user_level'] = (int)$values['user_level'] | (int)$this->_row($values['user_id'], 'user_level');
            $this->_update($values['user_id'], $update);
        }
        else {
            $values['user_id'] = parent::_insert($values);
        }

        // вставка данных в связанные датасорцы
        if (isset($values['user_level'])) {
            foreach ($this->levels as $l){
                if (((int)$values['user_level'] & $l['al_level']) && $l['al_table']){
                    $this->{$l['al_table']}->prevent_recursion()->_delete($values['user_id']);
                    $values[$this->{$l['al_table']}->_get_primary_key()] = $values['user_id'];
                    $this->{$l['al_table']}->prevent_recursion()->_insert($values);
                }
            }
        }
        return $values['user_id'];
    }
    
    /**
    * если обновляется user_level, надо удалить или добавить соответсвующие данные в связаных 
    * датасорцах
    */
    public function _update($where = false, $values = array(), $update_all_possible = false) {
        $users = $this->_rows($where);
        foreach ($users as $u) {
            if (isset($values['user_level'])) {
                $user_level = (int)$values['user_level'];
            }
            else {
                $user_level = (int)$u['user_level'];
            }
            foreach ($this->levels as $l){
                if ($l['al_table']) {
                    if (
                        ($user_level & $l['al_level']) && 
                        !($u['user_level'] & $l['al_level'])
                    ){
                        // вставляем
                        $insert = $values;
                        $insert[$this->{$l['al_table']}->_get_primary_key()] = $u['user_id'];
                        $this->{$l['al_table']}->prevent_recursion()->_insert($insert);
                    }
                    else if (
                        !($user_level & $l['al_level']) && 
                        ($u['user_level'] & $l['al_level'])
                    ) {
                        // удаляем
                        $this->{$l['al_table']}->prevent_recursion()->_delete($u['user_id']);
                    }
                    else {
                        // обновляем (2011-06-16)
                        $this->{$l['al_table']}->prevent_recursion()->_update($u['user_id'], $values);
                    }
                }
            }
        }

        return parent::_update($where, $values, $update_all_possible);
    }

    /**
    * удаляем из основной таблицы и из связаных датасорцев
    */
    public function _delete($where = false, $allow_truncate = false) {
        $users = $this->_rows($where);
        foreach ($users as $u) {
            foreach ($this->levels as $l){
                if (((int)$u['user_level'] & $l['al_level']) && $l['al_table']){
                    $this->{$l['al_table']}->prevent_recursion()->_delete($u['user_id']);
                }
            }
            parent::_delete($u['user_id'], $allow_truncate);
        }
    }

}


