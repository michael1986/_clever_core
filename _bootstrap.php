<?php
/**
* @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
* @version 1.0
* @package CleverCore2
*/

// define main constants
define('_ENGINE_PATH', _fix_path(dirname(__FILE__)));
define('_LOCALE_PATH', _fix_path(getcwd()));

if (!defined('_LEVEL_ADMIN')) {
    define('_LEVEL_ADMIN', 5); // 1: developer + 4: admin
}

// require system configuration
if (!defined('_TPL_ENGINE')) {
    define('_TPL_ENGINE', 'tpl_engine_cc');
}
if (!defined('_FORCE_TPL_SEARCH')) {
    define('_FORCE_TPL_SEARCH', false);
}
if (!defined('_CREATE_DATA_SOURCES')) {
    define('_CREATE_DATA_SOURCES', false);
}
if (!defined('_DB_ENGINE')) {
    define('_DB_ENGINE', 'db_mysql');
}
if (!defined('__DEFAULT_DATA_SOURCE_CLASS')) {
    define('__DEFAULT_DATA_SOURCE_CLASS', '_db_table');
}

// define dir names
define('__CORE_DIR', '_core/');
define('__DATA_SOURCES_DIR', '_data_sources/');
define('__CONFIGS_DIR', '_configs/');
define('__DATA_SOURCE_TPLS_DIR', __DATA_SOURCES_DIR . '_code_tpls/');
define('__MODULES_DIR', '_modules/');
define('__URL_CONVERTERS_DIR', '_url_converters/');
define('__TEMPLATES_DIR', '_templates/');
define('__LOCALIZATION_DIR', '_localization/');
define('__TPL_ENGINES_DIR', '_tpl_engines/');
define('__DB_ENGINES_DIR', '_db_engines/');
define('__TMP_DIR', '_tmp/');
define('__TPL_CACHE_DIR', 'tpl_cache/');

// define paths
define('__ENGINE_MODULES_PATH', _ENGINE_PATH . __MODULES_DIR);
define('__ENGINE_URL_CONVERTERS_PATH', _ENGINE_PATH . __URL_CONVERTERS_DIR);
define('__ENGINE_DATA_SOURCES_PATH', _ENGINE_PATH . __DATA_SOURCES_DIR);
define('__ENGINE_DATA_SOURCE_TPLS_PATH', _ENGINE_PATH . __DATA_SOURCE_TPLS_DIR);
define('__ENGINE_TEMPLATES_PATH', _ENGINE_PATH . __TEMPLATES_DIR);
define('__ENGINE_LOCALIZATION_PATH', _ENGINE_PATH . __LOCALIZATION_DIR);
define('__ENGINE_TPL_ENGINES_PATH', _ENGINE_PATH . __TPL_ENGINES_DIR);
define('__ENGINE_DB_ENGINES_PATH', _ENGINE_PATH . __DB_ENGINES_DIR);

define('__LOCALE_MODULES_PATH', _LOCALE_PATH . __MODULES_DIR);
define('__LOCALE_URL_CONVERTERS_PATH', _LOCALE_PATH . __URL_CONVERTERS_DIR);
define('__LOCALE_DATA_SOURCES_PATH', _LOCALE_PATH . __DATA_SOURCES_DIR);
define('__LOCALE_CONFIGS_PATH', _LOCALE_PATH . __CONFIGS_DIR);
define('__LOCALE_TEMPLATES_PATH', _LOCALE_PATH . __TEMPLATES_DIR);
define('__LOCALE_LOCALIZATION_PATH', _LOCALE_PATH . __LOCALIZATION_DIR);
define('__LOCALE_DB_ENGINES_PATH', _LOCALE_PATH . __DB_ENGINES_DIR);
define('__TMP_PATH', _LOCALE_PATH . __TMP_DIR);
define('__TPL_CACHE_PATH', __TMP_PATH . __TPL_CACHE_DIR);

if (!isset($_config)) {
    $_config = array();
}
require_once('_version.php');
if (file_exists(__LOCALE_CONFIGS_PATH . '_.php')) {
    require_once(__LOCALE_CONFIGS_PATH . '_.php');
}
require_once(__LOCALE_CONFIGS_PATH . '_db.php');
require_once(__LOCALE_CONFIGS_PATH . '_routers.php');
require_once(__LOCALE_CONFIGS_PATH . '_project.php');
require_once(__LOCALE_CONFIGS_PATH . '_url_converters.php');

// load low level functions
if (file_exists(_LOCALE_PATH . __CORE_DIR . '_low_lib.php')) {
    require_once(_LOCALE_PATH . __CORE_DIR . '_low_lib.php');
}
require_once(_ENGINE_PATH . __CORE_DIR . '_low_lib.php');

// load CleverCore class
require_once(_ENGINE_PATH . __CORE_DIR . '_cc_foundation.php');
if (file_exists(_LOCALE_PATH . __CORE_DIR . '_cc.php')) {
    require_once(_LOCALE_PATH . __CORE_DIR . '_cc.php');
}
else {
    require_once(_ENGINE_PATH . __CORE_DIR . '_cc.php');
}

// load base _core_foundation class
require_once(_ENGINE_PATH . __CORE_DIR . '_core_foundation.php');
if (file_exists(_LOCALE_PATH . __CORE_DIR . '_core.php')) {
    require_once(_LOCALE_PATH . __CORE_DIR . '_core.php');
}
else {
    require_once(_ENGINE_PATH . __CORE_DIR . '_core.php');
}

// load base _module_foundation class
require_once(_ENGINE_PATH . __CORE_DIR . '_module_foundation.php');
if (file_exists(_LOCALE_PATH . __CORE_DIR . '_module.php')) {
    require_once(_LOCALE_PATH . __CORE_DIR . '_module.php');
}
else {
    require_once(_ENGINE_PATH . __CORE_DIR . '_module.php');
}

// load base _url_converter class
require_once(_ENGINE_PATH . __CORE_DIR . '_url_converter_foundation.php');
if (file_exists(_LOCALE_PATH . __CORE_DIR . '_url_converter.php')) {
    require_once(_LOCALE_PATH . __CORE_DIR . '_url_converter.php');
}
else {
    require_once(_ENGINE_PATH . __CORE_DIR . '_url_converter.php');
}

// load base _data_source_foundation class
require_once(_ENGINE_PATH . __CORE_DIR . '_data_source_foundation.php');
if (file_exists(_LOCALE_PATH . __CORE_DIR . '_data_source.php')) {
    require_once(_LOCALE_PATH . __CORE_DIR . '_data_source.php');
}
else {
    require_once(_ENGINE_PATH . __CORE_DIR . '_data_source.php');
}

// load base _fields class
require_once(_ENGINE_PATH . __CORE_DIR . '_fields_foundation.php');
if (file_exists(_LOCALE_PATH . __CORE_DIR . '_fields.php')) {
    require_once(_LOCALE_PATH . __CORE_DIR . '_fields.php');
}
else {
    require_once(_ENGINE_PATH . __CORE_DIR . '_fields.php');
}

// load base _data_source_sql_foundation class
require_once(_ENGINE_PATH . __CORE_DIR . '_db_table_foundation.php');
if (file_exists(_LOCALE_PATH . __CORE_DIR . '_db_table.php')) {
    require_once(_LOCALE_PATH . __CORE_DIR . '_db_table.php');
}
else {
    require_once(_ENGINE_PATH . __CORE_DIR . '_db_table.php');
}

// load base _tpl_engine_foundation class
require_once(_ENGINE_PATH . __CORE_DIR . '_tpl_engine_foundation.php');
if (file_exists(_LOCALE_PATH . __CORE_DIR . '_tpl_engine.php')) {
    require_once(_LOCALE_PATH . __CORE_DIR . '_tpl_engine.php');
}
else {
    require_once(_ENGINE_PATH . __CORE_DIR . '_tpl_engine.php');
}
require_once(__ENGINE_TPL_ENGINES_PATH . _TPL_ENGINE . '.php');

// load base _sql_engine_foundation class
require_once(_ENGINE_PATH . __CORE_DIR . '_db_engine_foundation.php');
if (file_exists(_LOCALE_PATH . __CORE_DIR . '_db_engine.php')) {
    require_once(_LOCALE_PATH . __CORE_DIR . '_db_engine.php');
}
else {
    require_once(_ENGINE_PATH . __CORE_DIR . '_db_engine.php');
}
if (file_exists(__LOCALE_DB_ENGINES_PATH . _DB_ENGINE . '.php')) {
    require_once(__LOCALE_DB_ENGINES_PATH . _DB_ENGINE . '.php');
}
else {
    require_once(__ENGINE_DB_ENGINES_PATH . _DB_ENGINE . '.php');
}

define('_BASEURL', _get_baseurl());
if (!defined('_COREURL')) {
    define('_COREURL', _BASEURL . preg_replace('#^' . preg_quote(_LOCALE_PATH, '#') . '#', '', _ENGINE_PATH));
}

/************************* PREPARE ROUTER ********************************/

// find default router
$__routers = _cc::get_config('_routers');
if (!sizeof($__routers)) {
    _cc::fatal_error('CC Error. Unable to find default router.');
}
$__roters_names = array_keys($__routers);
$__rout_rule_default = reset($__roters_names);

// find current router and __ROUT_RULE
$__ccr = _read_param('__ccr');
unset($_GET['__ccr']);

$__rout_rule = false;
$__params_array = explode('/', $__ccr, 2);
if (in_array($__params_array[0], array_keys($__routers))) {
    $__rout_rule = $__params_array[0];
    if (isset($__params_array[1])) {
        $__params = $__params_array[1];
    }
    else {
        $__params = '';
    }
}
else {
    $__params = $__ccr;
}
if ($__rout_rule) {
    define('_CURRENT_ROUT_RULE_NAME', $__rout_rule);
}
else {
    define('_CURRENT_ROUT_RULE_NAME', $__rout_rule_default);
}

if (!$__rout_rule || $__rout_rule == $__rout_rule_default) {
    define('__ROUT_URL', '');
}
else {
    define('__ROUT_URL', $__rout_rule . '/');
}

// cleanup
unset($__rout_rule);
unset($__routers);
unset($__roters_names);
unset($__rout_rule_default);
unset($__params_array);
unset($__ccr);

/************************* EOF PREPARE ROUTER ****************************/

require_once(_ENGINE_PATH . '_debug.php');
if (!defined('_DEBUG_LEVEL')) {
    if (_cc::is_release()) {
        define('_DEBUG_LEVEL', 0);
    }
    else {
        define('_DEBUG_LEVEL', _DEBUG_ALL);
    }
}

if (_read_param('__reset_cache')) {
    if (!_cc::is_release()) {
        _rmdir(__TPL_CACHE_PATH);
    }
    $params = $_GET;
    unset($params['__reset_cache']);
    header('Location: ' . _append_params(_get_url(), $params));
    exit;
}

if (_DEBUG_LEVEL) {
    // store all debug messages
    $__debug = array();

    if (_DEBUG_LEVEL & _DEBUG_PHP) {
        error_reporting(E_ALL);
        set_error_handler('__cc_error_handler', E_ALL);
    }
    else {
        error_reporting(0);
    }
}
else {
    error_reporting(0);
}

_start_timer('_root');

if (isset($_config['_project']['_timezone']) && function_exists('date_default_timezone_set')) {
    date_default_timezone_set($_config['_project']['_timezone']);
}

$__template_paths_cache = array();
$__ob_counter = 0;
header('Content-Type: text/html; charset=utf-8');
$__bootstrap = _cc::single_module('modBootstrap');
echo $__bootstrap->_run();
_exit();

function __cc_error_handler($errno, $errstr, $errfile, $errline) {
    if ($errno & ini_get('error_reporting')) {
        switch ($errno) {
            case E_PARSE:
                _cc::fatal_error(_DEBUG_PHP, 'PHP parse error: <b>' . $errstr . '</b> in file <b>' . $errfile . '</b>, line number <b>' . $errline . '</b>', 'error');
                break;
            case E_ERROR:
                _cc::fatal_error(_DEBUG_PHP, 'PHP fatal error: <b>' . $errstr . '</b> in file <b>' . $errfile . '</b>, line number <b>' . $errline . '</b>', 'error');
                break;
            case E_WARNING:
                _cc::debug_message(_DEBUG_PHP, 'PHP warning: <b>' . $errstr . '</b> in file <b>' . $errfile . '</b>, line number <b>' . $errline . '</b>', 'error');
                break;
            case E_NOTICE:
                _cc::debug_message(_DEBUG_PHP, 'PHP notice: <b>' . $errstr . '</b> in file <b>' . $errfile . '</b>, line number <b>' . $errline . '</b>', 'error');
                break;
            default:
                _cc::debug_message(_DEBUG_PHP, 'PHP error: <b>' . $errstr . '</b> in file <b>' . $errfile . '</b>, line number <b>' . $errline . '</b>', 'error');
                break;
        }
        return true;
    }
    else {
        return false;
    }
}

/**
* Добавляет в конец пути слеш (/) если он отсутсвует
* @param string $path
* @return string Путь, всегда оканчивающийся слешем (/)
*/
function _fix_path($path) {
    $path = str_replace('\\', '/', $path);
    if ($path) {
        if ($path[strlen($path) - 1] != '/') {
            $path .= '/';
        }
    }
    return $path;
}

