<?php
abstract class modBootstrapFoundation extends _module {
    public function __construct($data = array()) {
        parent::__construct($data);

        $this->current_admin = $this->_single('backend/libCurrentAdmin');
        $this->_stick_var('current_admin', $this->current_admin);
        $this->_stick_params($this->current_admin->get_session_params());

        if (isset($GLOBALS['__cc_globals']['__data_sources'])) {
            foreach ($GLOBALS['__cc_globals']['__data_sources'] as $ds) {
                $ds->_stick_var('current_admin', $this->current_admin);
            }
        }
    }

    public function _run() {
        return _cc::manager(_CURRENT_ROUT_RULE_NAME)->_run();
    }
}

