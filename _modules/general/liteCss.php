<?php
class liteCss extends _module {
    protected $param_name = 'name';

    public function _run() {
        $name = _read_param($this->param_name);
        _overwhelm_response(
            $this->_tpl($name),
            'text/css',
            true
        );
    }
}

