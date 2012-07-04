<?php
_cc::load_module('general/libCurrentUserBase');

/**
* @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
*/
class libCurrentAdmin extends libCurrentUserBase {
    protected $param_sess_id = 'admin_sess_id';
    protected $cookie_param_sess_id = 'admin_sess_id';


    public function __construct($data = array()) {
        parent::__construct($data);

        if ($remember_enabled = _cc::get_config('current_admin', 'remember_enabled')) {
            $this->remember_enabled = $remember_enabled;
        }
        $this->cookie_session = _cc::get_config('current_admin', 'cookie_session');

        if ($this->cookie_session) {
            $sess_id = _read_cookie_param($this->cookie_param_sess_id);
        } else {
            $sess_id = _read_param($this->param_sess_id);
        }

        if (
            $sess_id
            &&
            $session_data = $this->check_session($sess_id)
        ) {
            $this->sess_id = $sess_id;
            $this->sess_data = $session_data;
        }
    }

    protected function validate_user_where() {
        return array(
            'user_active <>' => 0,
            'user_retired' => 0,
            'user_level & ' => _LEVEL_ADMIN
        );
    }

    public function start_session($values = array()) {
        if ($values && $user_id = $this->validate_user($values)) {
            return parent::start_session($user_id);
        }
        else {
            return false;
        }
    }

}


