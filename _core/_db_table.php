<?php
/**
 * @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
 * @package CleverCore2
 */

/**
 * Dummy class to be replaced in project if necessary.
 */
class _db_table extends _db_table_foundation {
    final public function _low_select($columns, $adjusted = false, $mode = false) {
        return parent::_low_select($columns, $adjusted, $mode);
    }
    final public function _low_insert($values = array()) {
        return parent::_low_insert($values);
    }
    final public function _low_update($where = false, $values = array(), $update_all_possible = false) {
        return parent::_low_update($where, $values, $update_all_possible);
    }
    final public function _low_delete($where = false, $allow_truncate = false) {
        return parent::_low_delete($where, $allow_truncate);
    }
}

