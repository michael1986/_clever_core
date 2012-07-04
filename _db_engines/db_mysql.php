<?php
/**
* @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
* @version 1.0
* @package CleverCore2
*/

/**
* Работа с MySQL
*/
class db_mysql extends _db_engine {
    public function _connect($hostname, $username, $password) {
        return @mysql_connect($hostname, $username, $password, true);
    }
    public function _select_db($basename) {
        return @mysql_select_db($basename);
    }
    public function _low_query($sql) {
        // return @mysql_query($this->__prepare_sql($sql), $this->__db_resource);
        return @mysql_query($sql, $this->__db_resource);
    }
    public function _insert_id() {
        return @mysql_insert_id();
    }
    public function _escape($param) {
        return @mysql_real_escape_string($param);
    }
    public function _error() {
        return @mysql_error();
    }
    public function _fetch_assoc($req) {
        return @mysql_fetch_assoc($req);
    }
    public function _fetch_num($req) {
        return @mysql_fetch_row($req);
    }
    public function _fetch_both($req, $k) {
        return @mysql_fetch_array($req, MYSQL_BOTH);
    }
    public function _num_fields($req) {
        return @mysql_num_fields($req);
    }
    public function _fetch_field($req) {
        return @mysql_fetch_field($req);
    }
    public function _sql_limit($limit1 = false, $limit2 = false) {
        if ($limit1) {
            if ($limit2) {
                return ' limit ' . $limit1 . ', ' . $limit2;
            }
            else {
                return ' limit ' . $limit1;
            }
        }
        else if($limit2) {
            return ' limit 0, ' . $limit2;
        }
    }
}


