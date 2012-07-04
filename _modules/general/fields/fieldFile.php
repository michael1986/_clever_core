<?php
/**
 * @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
 * @package CleverCore2
 */
_cc::load_module('general/fields/fieldBase');

/**
* TODO: Сохранять оригинальное имя файла, что бы при перемещении/копировании после _unique_filename
* пользователю выводилось то что он ожидает, а не то что может оказаться на самом деле
*/
class fieldFile extends fieldBase {
    protected $tpl_name = 'file.tpl';
    /**
    * Здесь будут складываться загруженые файлы, по умолчанию во временной папке проекта
    * @var mixed
    */
    protected $binary_path = false;
    protected $tmp_path = false;
    protected $enctype = 'multipart';
    protected $allowed_extensions = array();

    public function __construct($data = array()) {
        parent::__construct($data);

        foreach ($this->allowed_extensions as &$ext) {
            $ext = strtolower($ext);
        }
        unset($ext);

        if (!$this->binary_path) {
            _cc::fatal_error(_DEBUG_CC, '\'binary_path\' should be defined for \'file\' fields. Field: <b>' . $this->external_name . '</b>');
        }
        if (!$this->tmp_path) {
            $this->tmp_path = $this->binary_path;
        }

        _mkdir($this->tmp_path);
        _mkdir($this->binary_path);

        $this->lang = $this->_load_language();
    }

    public function get_tpl_data() {
        $data = parent::get_tpl_data();
        $data['tmp_path'] = $this->tmp_path;
        $data['lang'] = $this->lang;
        $data['binary_path'] = $this->binary_path;
        return $data;
    }

    /**
    * устанавливаем внешнее и внутреннее представление значения в соответсвие с 
    * полученым при инициализации внешним значением поля
    */
    public function set_external_value($value) {
        if ($value) {
            $filename = basename($value);
            if ($value != $filename) {
                // file located outside our binary_path and we get full path
                $filename = _unique_filename($this->tmp_path, $filename);
                copy($value, $this->tmp_path . $filename);
            }
            else if ($this->tmp_path != $this->binary_path) {
                // make a copy to work with
                $filename = _unique_filename($this->tmp_path, $filename);
                copy($this->binary_path . $value, $this->tmp_path . $filename);
            }
            $this->external_value = $filename;
            $this->internal_value = array(
                'current' => $filename,
                'original' => $filename
            );
        }
        else {
            $this->external_value = false;
            $this->internal_value = array(
                'current' => false,
                'original' => false
            );
        }
    }

    public function set_internal_value($value) {
        if ($this->tmp_path != $this->binary_path) {
            $this->external_value = _unique_filename($this->binary_path, $value['current']);
        }
        else {
            $this->external_value = $value['current'];
        }
        $this->internal_value = $value;
    }

    /**
    * в случае указания имени файла как массив, он обрабатываются в ПХП не так как 
    * обычные переменные; поэтому нужно изменить способ чтения файла
    * 
    * @param mixed $name
    */
    protected function read_file_with_keys($name) {
        if (preg_match('#^(\w+[\w\d]*)(\[.*)$#', $name, $found)) {
            $tmp_name = $found[1];
            $arr_key = str_replace('[', "['", str_replace(']', "']", $found[2]));
            $tmp_value = _read_file_param($tmp_name);
            eval('$name = $tmp_value[\'name\']' . $arr_key . '[\'new\'];');
            eval('$type = $tmp_value[\'type\']' . $arr_key . '[\'new\'];');
            eval('$tmp_name = $tmp_value[\'tmp_name\']' . $arr_key . '[\'new\'];');
            eval('$error = $tmp_value[\'error\']' . $arr_key . '[\'new\'];');
            eval('$size = $tmp_value[\'size\']' . $arr_key . '[\'new\'];');
            return array(
                'name' => $name,
                'type' => $type,
                'tmp_name' => $tmp_name,
                'error' => $error,
                'size' => $size
            );
        }
        else {
            $file = _read_file_param($name);
            return array(
                'name' => $file['name']['new'],
                'type' => $file['type']['new'],
                'tmp_name' => $file['tmp_name']['new'],
                'error' => $file['error']['new'],
                'size' => $file['size']['new']
            );
        }
    }

    /**
    * получаем пользовательский ввод
    */
    public function get_user_input() {
        $value = $this->read_file_with_keys($this->internal_name);
        if (is_uploaded_file($value['tmp_name'])) {
            if (sizeof($this->allowed_extensions)) {
                preg_match('#\.([^\.]+)$#i', $value['name'], $match);
                if (in_array(strtolower($match[1]), $this->allowed_extensions)) {
                    $extention_ok = true;
                }
                else {
                    $extention_ok = false;
                }
            }
            else {
                $extention_ok = true;
            }
            if ($extention_ok) {
                // $new = _unique_filename($this->tmp_path, $value['name']);
                $new = $value;
            }
            else {
                $new = false;
            }
        }
        else {
            $new = false;
        }

        $file_data = $this->read_param_with_keys($this->internal_name);
        $current = isset($file_data['current']) ? $file_data['current'] : '';
        $original = isset($file_data['original']) ? $file_data['original'] : '';
        $remove = (isset($file_data['remove']) && $file_data['remove']) ? true : false;

        if ($current && ($remove || $new)) {
            if (file_exists($this->tmp_path . $current)) {
                unlink($this->tmp_path . $current);
            }
            $current = '';
        }
        if ($new) {
            $current = _unique_filename($this->tmp_path, $new['name']);
            move_uploaded_file($new['tmp_name'], $this->tmp_path . $current);
        }
        $this->set_internal_value(array(
            'current' => $current,
            'original' => $original
        ));
    }

    public function finalize($value) {
        if (
            ($this->internal_value['original'] && $this->internal_value['current'] != $this->internal_value['original']) ||
            ($this->internal_value['current'] && $this->tmp_path != $this->binary_path && $this->internal_value['current'] == $this->internal_value['original'])
        ) {
            if (file_exists($this->binary_path . $this->internal_value['original'])) {
                unlink($this->binary_path . $this->internal_value['original']);
            }
            
        }
        if ($this->internal_value['current'] && $this->tmp_path != $this->binary_path) {
            $destination_file = _unique_filename($this->binary_path, $this->internal_value['current']);
            rename($this->tmp_path . $this->internal_value['current'], $this->binary_path . $destination_file);
        }
        return $value;
    }
}


