<?php
	require_once('include/database.php');
	
    function get_name( $name_id ){
        if (!$name_id) {
            return FALSE;
        }
    	$safe_name_id = mysql_real_escape_string($name_id);
		
		$sql = "SELECT * FROM `person_name` WHERE `PersonNameID` = {$safe_name_id} LIMIT 1;";
		$result = mysql_query($sql);
		
		if($result){
			if(mysql_num_rows($result) < 1)
				return FALSE;
			return mysql_fetch_array($result);
		} else {
            rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                            "Failed to get name for PersonNameID $name_id", $sql);
			return FALSE;
		}
    }
	/**
	 * Adds a name to the database.
	 *
	 * @param title string - person's form of address (Mr, Mrs, Miss, Dr, etc)
	 * @param first string - person's first name
	 * @param middle character - person's middle initial
	 * @param last string - person's last name
	 * @param suffix string - Jr, III, Esq, etc
	 * @return ID of name, or FALSE on error
	 */
	function add_person_name( $title, $first, $middle, $last, $suffix, $nickname="" ) {
	
	    $safe_title = mysql_real_escape_string( $title );
	    $safe_first = mysql_real_escape_string( $first );
	    $safe_middle = mysql_real_escape_string( $middle );
	    $safe_last = mysql_real_escape_string( $last );
	    $safe_suffix  = mysql_real_escape_string( $suffix  );
	    $safe_nickname  = mysql_real_escape_string( $nickname  );
	
	    $sql = "INSERT INTO person_name (Title, FirstName, MiddleInitial, LastName, Suffix, NickName) 
	                    VALUES ('$safe_title', '$safe_first', '$safe_middle', '$safe_last', '$safe_suffix', '$safe_nickname')";
	
	    $result = mysql_query($sql);
	
	    if ($result) {
	        return mysql_insert_id();
	    } else {
	        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
	                        "Error adding person name ($title/$first/$middle/$last/$suffix/$nickname)", $sql);
	    }
	
	    return FALSE;
	}
	function update_person_name( $name_id, $title, $first, $middle, $last, $suffix, $nickname="" ){
		$safe_name_id = mysql_real_escape_string( $name_id );
		$safe_title = mysql_real_escape_string( $title );
	    $safe_first = mysql_real_escape_string( $first );
	    $safe_middle = mysql_real_escape_string( $middle );
	    $safe_last = mysql_real_escape_string( $last );
	    $safe_suffix  = mysql_real_escape_string( $suffix  );
	    $safe_nickname = mysql_real_escape_string( $nickname );
	
	    $sql = "UPDATE `person_name` SET `Title` = '$safe_title',
										 `FirstName` = '$safe_first',
										 `MiddleInitial` = '$safe_middle',
										 `LastName` = '$safe_last',
										 `Suffix` = '$safe_suffix',
										 `NickName` = '$safe_nickname'
				WHERE `PersonNameID` =$safe_name_id LIMIT 1;";
	
	    $result = mysql_query($sql);
	
	    if ($result)
	        return $name_id;
	    return FALSE;	    
	}
	
	function delete_name( $name_id ){
		$safe_name_id = mysql_real_escape_string($name_id);
		$sql = "DELETE FROM `person_name` WHERE `PersonNameID`= '$safe_name_id' LIMIT 1;";
		$result = mysql_query($sql);
		
		if($result){
			return true;
		} else {
			rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
	                        "Error removing person name $name_id", $sql);
			return FALSE;
		}
	}

function print_get_name_form_part($person_name = NULL, $form_name_prefix = '', $show_submit = TRUE, $style = NULL) {
echo <<<HTML
<table style="{$style}">
    <tr>
        <td class="alignright">Title</td>
        <td><input type="text" name="{$form_name_prefix}Title" value="{$person_name['Title']}" maxlength="10" style="width:50px;" /></td>
    </tr>
    <tr>
        <td class="alignright">First Name</td>
        <td><input type="text" name="{$form_name_prefix}FirstName" value="{$person_name['FirstName']}" maxlength="30" style="width:200px;" /></td>
    </tr>
    <tr>
        <td class="alignright">Middle Initial</td>
        <td><input type="text" name="{$form_name_prefix}MiddleInitial" value="{$person_name['MiddleInitial']}" maxlength="1" style="width:50px;" /></td>
    </tr>
    <tr>
        <td class="alignright">Last Name</td>
        <td><input type="text" name="{$form_name_prefix}LastName" value="{$person_name['LastName']}" maxlength="30" style="width:200px;" /></td>
    </tr>
    <tr>
        <td class="alignright">Suffix</td>
        <td><input type="text" name="{$form_name_prefix}Suffix" value="{$person_name['Suffix']}" maxlength="10" style="width:50px;" /></td>
    </tr>
    <tr>
        <td class="alignright">Nickname</td>
        <td><input type="text" name="{$form_name_prefix}Nickname" value="{$person_name['NickName']}" maxlength="10" style="width:200px;" /></td>
    </tr>
HTML;

    if ($show_submit) {
echo <<<SUBMIT
    <tr>
        <td class="alignright" colspan="2"><input type="submit" name="{$form_name_prefix}Save" value="Save" /></td>
    </tr>
SUBMIT;
    }
echo '</table>';
}

function get_name_fields_from_post($form_name_prefix = '', $required_fields = array('FirstName', 'LastName')) {
    $name_fields = array('Title', 'FirstName', 'MiddleInitial', 'LastName', 'Suffix');
    $missing_fields = array(); 
    if (count($required_fields)) {
        foreach ($required_fields as $field_name) {
            if (!$_POST[$form_name_prefix . $field_name]) {
                $missing_fields[] = $field_name;
            }
        }
    }

    $name = array();
    foreach ($name_fields as $field_name) {
        $name[$field_name] = $_POST[$form_name_prefix . $field_name];
    }

    return array('Name' => $name, 
                 'RequiredFieldsPresent' => (boolean)(count($missing_fields) == 0),
                 'MissingFieldList' => $missing_fields);
}


function get_displayable_person_name_string($person_name, $prefix = "") {
		$name = "";
    if (isset($person_name[$prefix . 'Title']) && $person_name[$prefix . 'Title']) != '') {
        $name = $person_name[$prefix . 'Title'] . ' ';
    } 

    $name .= $person_name[$prefix . 'FirstName'] . ' ';
    
    if(isset($person_name[$prefix . 'NickName']) && $person_name[$prefix . 'NickName'] != '')
    	$name .= '(<B>'.$person_name[$prefix . 'NickName'].'</B>) ';
    
    if (isset($person_name[$prefix . 'MiddleInitial']) && $person_name[$prefix . 'MiddleInitial'] != ''
    			&& !strstr($name,'(')) {
        $name .= $person_name[$prefix . 'MiddleInitial'] . ' ';
    }

    $name .= $person_name[$prefix . 'LastName'];

    if (isset($person_name[$prefix . 'Suffix']) && $person_name[$prefix . 'Suffix'] != '') {
        $name .= ' ' . $person_name[$prefix . 'Suffix'];
    }
    return $name;
}

?>
