<?php
/**
* @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
* @version 1.0
* @package Backend
*/

class manBackend extends _module {
    protected $act_param = 'back_act';
    protected $param_module = 'back_module';
    protected $param_add = 'back_add';
    protected $module = false;
    protected $default_module = false;

    protected $pages = false;
    protected $main_menu = false;

    protected $backend_instance = 'backendInstance';

    public function _run() {
        $lang_name = _cc::get_config('_project', '_language');
        $this->lang = $this->_load_language($lang_name);

        if (_read_param($this->act_param) == 'signout') {
            $this->current_admin->stop_session();
            $this->_redirect();
        }
        $this->_stick_params($this->current_admin->get_session_params());

        if ($this->current_admin->is_signed()) {
            return $this->get_content_inside();
        } else {
            return $this->get_content_outside();
        }
    }

    /********************************************
    * Outside
    ********************************************/
    protected function get_content_outside() {
        $title = $this->lang['SIGNIN_TITLE'];

        $form = $this->_module('general/modForm', array(
            '_templates_dir' => 'signin_form',
            'title' => $title,
            'prefix_callbacks' => 'signin_',
            'prefix_params' => 'si2_',
            'fields' => array(
                array(
                    'name' => 'user_login',
                    'title' => $this->lang['ENTER_LOGIN'],
                    'mandatory' => true,
                    'validate_input' => '_validate_email'
                ),
                array(
                    'name' => 'user_password',
                    'title' => $this->lang['ENTER_PASSWORD'],
                    'type' => 'password',
                    'obligatory' => true,
                    'validate_input' => '_validate_password'
                )
            ),
            'submits' => array('ok')
        ));
        return $this->_tpl('outside.tpl', $this->adjust_outside_tpl_data(array(
            'page_title' => $title,
            'form' => $form->_run(),
            'lang' => $this->lang
        )));
    }

    public function signin_validate_ok($id, $values) {
        $ems = array();
        if (!$values['user_login']) {
            $ems['user_login'] = $this->lang['ERROR_LOGIN'];
        } 
        else if (!$this->current_admin->validate_user($values)) {
            $ems['user_login'] = $this->lang['ERROR_PAIR'];
        }
        return $ems;
    }

    public function signin_proceed_ok($id, $values, $step, $form) {
        $this->current_admin->start_session($values);
        $form->redirect($this->_link($this->current_admin->get_session_params()));
    }

    protected function adjust_outside_tpl_data($data) {
        return $data;
    }

    /********************************************
    * Inside
    ********************************************/
    protected function get_content_inside() {
        $this->pages = $this->back_pages->get_pages();
        foreach ($this->pages as $p) {
            $default_page_data = $this->back_pages->find_page_with_content_inside_page_data($p, $this->current_admin->get_data('user_level'));
            if ($default_page_data) {
                $this->default_module = $default_page_data['page_id'];
                break;
            }
        }

        $this->module = _read_param($this->param_module);
        if ($this->module) {
            $module_page_data = $this->back_pages->find_page_with_content_inside_page_data(
                $this->back_pages->get_page_data_from_id($this->module),
                $this->current_admin->get_data('user_level')
            );
            $this->module = $module_page_data['page_id'];
        }
        else {
            $module_page_data = false;
        }
        if (!$module_page_data) {
            $module_page_data = $default_page_data;
            $this->module = $default_page_data['page_id'];
        }
        $this->_stick_param($this->param_module, $this->module);

        if ($module_page_data) {
            $content = $this->_module($module_page_data['page_module'])->/*_append_breadcrumbs($this->_get_breadcrumbs())->*/_run();
        }
        else {
            $this->_redirect($this->_link(array(
                $this->act_param => 'signout'
            )));
        }

        $this->build_main_menu();

        $page_title = (!empty($content->_vars['page_title']) ? $content->_vars['page_title'] : $module_page_data['page_title']);

        if (/* FAV disabled */ false && $this->_read_sticky_param($this->param_add)) {
            /*
            $form = $this->_module('backend/modBackForm', array(
                '_templates_dir' => 'modFormAddToFav',
                'prefix_params' => 'add_f_',
                'fields' => array(
                    'f_title' => array(
                        'title' => $this->lang['ADD_TO_FAVORITES_FIELD_TITLE'],
                        'value' => $page_title
                    ),
                    'f_link' => array(
                        'tpl_name' => 'window_location.tpl'
                    )
                ),
                'title' => $this->lang['ADD_TO_FAVORITES_FORM_TITLE'],
                'ajax' => true,
                'ajax_handler' => $this->backend_instance . '.handleAjax',
                'callback_complete' => function ($id, $values) {
                    _cc::create_data_from_class('back_favorites', false, false)->_insert(array(
                        'f_title' => $values['f_title'],
                        'f_link' => $this->current_admin->remove_session_params($values['f_link'])
                    ));
                },
                'callback_proceed_cancel' => function () {
                }
            ));
            _overwhelm_response($form->_run());
            */
        }
        else {
            return $this->_tpl('inside.tpl', $this->adjust_inside_tpl_data(array(
                'main_menu'     => $this->main_menu,
                'page_title'    => $page_title,
                'content'       => $content,
                'link_add_to_favorites' => $this->_link(array(
                    $this->param_add => 'on'
                )),
                // FAV disabled
                // 'favorites' => $this->back_favorites->_arows(),
                'signout_link'  => $this->_hlink(array(
                    $this->act_param => 'signout'
                )),
                'backend_instance' => $this->backend_instance,
                'lang' => $this->lang
            )));
        }
    }

    protected function adjust_inside_tpl_data($data) {
        return $data;
    }

    protected function build_main_menu() {
        $this->build_main_menu_recur($this->pages, $this->main_menu);
    }

    protected function build_main_menu_recur($pages, &$submenu) {
        $submenu = array();

        $return_is_parent = false;
        foreach ($pages as $p) {
            $submenu_item = array();

            if ($page_data = $this->back_pages->find_page_with_content_inside_page_data($p, $this->current_admin->get_data('user_level'))) {

                $submenu_item['link'] = $this->_hlink(array(
                    $this->param_module => $page_data['page_id']
                ));

                if (isset($p['pages']) && is_array($p['pages'])) {
                    $is_parent = $this->build_main_menu_recur($p['pages'], $submenu_item['pages']);
                }
                else {
                    $is_parent = false;
                }

                if ($p['page_id'] == $this->module) {
                    $submenu_item['item_off'] = false;
                    $submenu_item['item_on'] = true;
                    $submenu_item['item_parent'] = false;

                    $return_is_parent = true;
                }
                else if ($is_parent) {
                    $submenu_item['item_off'] = false;
                    $submenu_item['item_on'] = false;
                    $submenu_item['item_parent'] = true;

                    $return_is_parent = true;
                }
                else {
                    $submenu_item['item_off'] = true;
                    $submenu_item['item_on'] = false;
                    $submenu_item['item_parent'] = false;
                }

                if ($submenu_item['item_on'] || $submenu_item['item_parent']) {
                    $this->_prepend_breadcrumbs(array($this->_create_breadcrumb(
                        $p['page_title'],
                        $this->_hlink(array(
                            $this->param_module => $page_data['page_id']
                        ))
                    )));
                }

                unset($p['pages']);
                $submenu[] = array_merge($submenu_item, $p);
            }

        }

        return $return_is_parent;
    }

}



