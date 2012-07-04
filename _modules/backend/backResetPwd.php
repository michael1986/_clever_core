<?php
/**
 * @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
 * @package CleverCore2
 */

_cc::load_module('backend/backBase');

/**
* Reset password module
*/
class backResetPwd extends backBase {
    protected $_title = false;

    public function _run() {
        $lang_name = _cc::get_config('_project', '_language');
        $this->lang = $this->_load_language($lang_name);

        $this->_title = $this->lang['MODULE_TITLE'];
        $this->_add_breadcrumb($this->_title);

        $success = _read_param('success');

        if ($success) {
            $infos = array($this->lang['SUCCESS_MESSAGE']);
        }
        else {
            $infos = array();
        }

        $this->form = $this->_create_module('backend/modBackForm', array(
            'title' => $this->_title,
            'info' => $infos,
            'fields' => array(
                array(
                    'type' => 'fieldset',
                    'title' => $this->lang['ENTER_CURRENT_PWD'],
                    'fields' => array(
                        array('name' => 'current_pwd', 'type' => 'password', 'title' => $this->lang['ENTER_CURRENT_PWD']),
                    )
                ),
                array(
                    'type' => 'fieldset',
                    'title' => $this->lang['ENTER_NEW_PWD'],
                    'fields' => array(
                        array('name' => 'new_pwd', 'type' => 'password', 'title' => $this->lang['ENTER_NEW_PWD']),
                        array('name' => 'confirm_pwd', 'type' => 'password', 'title' => $this->lang['CONFIRM_NEW_PWD']),
                    )
                )
            ),
            'submits' => array('ok')
        ));
        return $this->form->_run();
    }

    public function validate_ok($id, $values) {
        $ems = array();

        if ($this->current_admin->get_data('user_password') != $values['current_pwd']) {
            $ems['current_pwd'] = $this->lang['ERROR_CURRENT_PWD'];
        }
        if (!$values['new_pwd']) {
            $ems['new_pwd'] = $this->lang['ERROR_NEW_PWD'];
        }
        if ($values['new_pwd'] != $values['confirm_pwd']) {
            $ems['confirm_pwd'] = $this->lang['ERROR_CONFIRM_PWD'];
        }

        return $ems;
    }

    public function complete($id, $values) {
        $this->current_admin->get_users()->_update($this->current_admin->get_data('user_id'), array('user_password' => $values['new_pwd']));
        $this->_redirect(array(
            'success' => 'on'
        ));
    }
}


