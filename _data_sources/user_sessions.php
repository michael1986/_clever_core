<?php
/**
* @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
* @version 1.0
* @package Users
*/

class user_sessions extends _db_table {
    protected $_fields = array(
        array('name' => 'sess_id', 'primary_key' => true),
        array('name' => 'sess_user_id', 'foreign_table' => 'users'),
        array('name' => 'sess_ip'),
        array('name' => 'sess_created', 'created' => true, 'sql_type' => 'int'),
        array('name' => 'sess_modified', 'modified' => true, 'sql_type' => 'int')
    );
    protected $_relations_get = array('user_session_data');
    protected $_relations_delete = array('user_session_data');
    
    /*
        автоматическое прилинковывание таблиц
            добавление (поля и сохранение)
                - 1 к 1 - будут добавлены поля из второй таблицы
                - 1 к NN - будут добавлены поля из второй таблицы в единичном экземпляре, при сохранении подразумевается что добавлена лишь первая запись
                - NN к 1 - описывать не нужно, будет использовано поле с меткой foreign_key/foreign_table
                - NN к NN - описывать не нужно, будет использовано поле с меткой foreign_key/foreign_table
            просмотр
                - 1 к 1 - будет добавлена связанная строка данных из соответсвующей таблицы
                - 1 к NN - будут добавлены все связанные строки (массивом) из соответсвующей таблицы
                - NN к 1 - будет добавлена связанная строка данных (в отдельный ключ?)
                - NN к NN - будут добавлены все связанные строки (массивом) из соответсвующей таблицы
            редактирование
                - 1 к 1 - будут добавлены поля из второй таблицы
                - 1 к NN - не будет ничего
                - NN к 1 - описывать не нужно, будет использовано поле с меткой foreign_key/foreign_table
                - NN к NN - описывать не нужно, будет использовано поле с меткой foreign_key/foreign_table
            удаление
                - 1 к 1 - будет удалена строка из связанной таблицы
                - 1 к NN - будут удалены все строки из связанной таблицы
                - NN к 1 - не будет ничего
                - NN к NN - не будет ничего
    */
/*
    protected $_relations = array(
        'remove' => array('user_session_data'),
        'remove' => array('user_session_data'),
    );
*/

//    protected $_plugins = array(
//        'belongs_to' => array(
//            'users'
//        ),
//        'has_many' => array(
//            'session_data'
//        )
//    );

    /**
    * ТАК НЕЛЬЗЯ, переделать что бы удаление связаных данных было более простым
    * 
    * @param mixed $where
    */
    public function _delete($where = false, $allow_truncate = false) {
//        $this->_join('user_session_data');
        $sess_ids = $this->_cols($where, 'sess_id');
        $usd_ds = $this->_create_data_source('user_session_data');
        foreach ($sess_ids as $sess_id) {
            $usd_ds->_delete(array('usd_sess_id' => $sess_id));
        }
        parent::_delete($where, $allow_truncate);
    }
}


