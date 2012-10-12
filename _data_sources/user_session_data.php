<?php
/**
* @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
* @version 1.0
* @package Users
*/

class user_session_data extends _db_table {
    protected $_fields = array(
        'usd_id' => array(
            'primary_key' => true,
            'auto_increment' => true,
        ),
        'usd_sess_id' => array(
            'foreign_table' => 'user_sessions'
        ),
        'usd_group',
        'usd_var',
        'usd_value'
    );
    protected $binary_path = '_tmp/session/';
    protected $max_value_length_to_insert = 102400; // 100kb // 4294967296; // 4mb

    public function __construct($data = array()) {
        parent::__construct($data);
        _mkdir($this->binary_path);
    }

    public function _adjust_db_input($in) {
        if (isset($in['usd_value'])) {
            if (strlen($in['usd_value']) >= $this->max_value_length_to_insert) {
                $fn = _unique_filename($this->binary_path, time());
                file_put_contents($this->binary_path . $fn, $in['usd_value']);
                $in['usd_value'] = 'F' . $fn; // file
            }
            else {
                $in['usd_value'] = 'D' . $in['usd_value']; // data
            }
        }
        return $in;
    }

    /*
     * 2012-01-25 - изменена логика поведения для обслуживания кеша
    public function _adjust_db_output($in, $mode = false) {
        if (isset($in['usd_value']) && $mode != 'clean') {
            if ($in['usd_value'][0] == 'F') {
                $in['usd_value'] = file_get_contents($this->binary_path . substr($in['usd_value'], 1));
            }
            else {
                $in['usd_value'] = substr($in['usd_value'], 1);
            }
        }
        return $in;
    }
    */
    public function adjust_before_usage($value) {
        if ($value[0] == 'F') {
            return file_get_contents($this->binary_path . substr($value, 1));
        }
        else {
            return substr($value, 1);
        }
    }

    public function _delete($where = false, $allow_truncate = false) {
        $datas = $this->_arows($where, false, 'clean');
        foreach ($datas as $data) {
            if ($data['usd_value'][0] == 'F') {
                unlink($this->binary_path . substr($data['usd_value'], 1));
            }
        }
        return parent::_delete($where, $allow_truncate);
    }
}