<?php
if (!function_exists('output_hidden_fields_recur')) {
    function output_hidden_fields_recur($name, $id, $value) {
        if (is_array($value)) {
            foreach ($value as $key => $value_single) {
                if (is_int($key)) {
                    output_hidden_fields_recur($name . '[]', $id . '[]', $value[$key]);
                }
                else {
                    output_hidden_fields_recur($name . '[' . $key . ']', $id . '[' . $key . ']', $value[$key]);
                }
            }
        }
        else {
            echo "<div><input type=\"hidden\" name=\"$name\" id=\"$id\" value=\"$value\"></div>\n";
        }
    }
}
        
output_hidden_fields_recur($name, $id, $value);

