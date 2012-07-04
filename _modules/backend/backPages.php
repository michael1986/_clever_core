<?php
_cc::load_module('backend/backBase');

class backPages extends backBase {
    protected $templates_dir_form = 'modFormPages';
    protected $cookie_param_cookie_mode = 'mode';

    public function __construct($data = array()) {
        parent::__construct($data);
        $this->lang = $this->_load_language();
        $this->_title = $this->lang['TITLE'];
    }

    public function _run() {
        $this->grid = $this->_module('backend/modBackDataGridTree', array(
            'title_add' => $this->lang['TITLE_ADD'],
            'title_edit' => $this->lang['TITLE_EDIT'],
            'data_source' => $this->back_pages,
            'prefix_params' => 'bp_',
            'controls_settings' => array(
                'popup' => true
            ),
            'controls' => array(
                'add', 
                'edit', 
                'move_up', 
                'move_down', 
                'cut', 'cut_cancel', 'paste_before', 'paste_after', 'paste_under',
                'delete' 
            ),
            'plugins' => array(
                'columns' => array(
                    'description' => array(
                        array(
                            'title' => $this->lang['COL_TITLE_PAGE_TITLE'],
                            'data_key' => 'page_title'
                        ),
                        array(
                            'title' => $this->lang['COL_TITLE_BUFFER']
                        ),
                        array(
                            'title' => $this->lang['COL_TITLE_CONTROLS']
                        ),
                        array(
                            'title' => '',
                            'checkbox' => true
                        ),
                    )
                )
            )
        ));

        $this->_append_breadcrumbs($this->grid->_get_breadcrumbs());

        return $this->_tpl(array(
            'breadcrumbs' => $this->_get_breadcrumbs(),
            'grid' => $this->grid->_run()
        ));
    }

    public function rows() {
        return $this->back_pages->get_pages_univariate();
    }
}



