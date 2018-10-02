<?php

function create_options_from_array($options, $selected_key = NULL) {
    $option_text = '';
    // Key is option name, value is text to display
    if (count($options) > 0) {
        foreach ($options as $raw_opt_value => $raw_opt_display) {
            $opt_value = htmlspecialchars($raw_opt_value);
            $opt_display = htmlspecialchars($raw_opt_display);

            $selected = (($opt_value == $selected_key) ? ' selected="selected"' : '');

            $option_text .= "<option value=\"{$opt_value}\"{$selected}>{$opt_display}</option>";
        }
    }

    return $option_text;
}

function check_required_fields($required_fields) {
    $all_filled = TRUE;
    $missing_fields = array();

    if (count($required_fields) > 0) {
        foreach ($required_fields as $field_name) {
            if ($_POST[$field_name] == '') {
                $all_filled = FALSE;
                $missing_fields[] = $field_name;
            }
        }
    }

    return array($all_filled, $missing_fields);
}

    
?>
