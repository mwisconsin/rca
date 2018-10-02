<?php

require_once('include/database.php');

/**
 * Adds a phone number to the database linked to the selected user.
 * @param phone_number the phone number as a string
 * @param type type of phone number ('HOME', 'MOBILE', 'WORK', 'FAX', 'OTHER')
 * @param user_id User to associate with this phone number
 * @return ID of phone number within the system or FALSE on error
 */
function add_phone_number_for_user($phone_number, $type, $user_id, $canSMS = 'N', $ProviderID = 0, $Ext = "", $sms_preference = "FIRST", $phonedescription = "") {
    $safe_user_id = mysql_real_escape_string($user_id);
    $ProviderID = $ProviderID == '' ? 0 : $ProviderID;

    $phone_id = add_phone_number($phone_number, $type, $canSMS, $ProviderID, $Ext, $sms_preference, $phonedescription);

    $sql = "INSERT INTO user_phone (UserID, PhoneID) VALUES ($safe_user_id, $phone_id)";
    $result = mysql_query($sql);
    if (!$result) {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error associating phone number $phone_number to user $user_id", $sql);
        return FALSE;
    }     

    return $phone_id;
}

function user_owns_phone_number($phone_id, $user_id){
    $safe_phone_id = mysql_real_escape_string($phone_id);
    $safe_user_id = mysql_real_escape_string($user_id);

    $sql = "SELECT * FROM user_phone WHERE PhoneID = $safe_phone_id AND UserID = $safe_user_id LIMIT 1;";
    $result = mysql_query($sql) or die($sql);
    
    if($result){
        if(mysql_num_rows($result) > 0)
            return true;
        return false;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error checking phone number owner $phone_id to user $user_id", $sql);
        return FALSE;

    }
}

function edit_phone_number_for_user($phone_id, $phone_number, $type, $user_id, $canSMS = 'N', $ProviderID = 0, $Ext = "", $sms_preference = "FIRST", $phonedescription = ""){
    if(!user_owns_phone_number($phone_id, $user_id))
        return false;
    $ProviderID = $ProviderID == '' ? 0 : $ProviderID;
    $safe_user_id = mysql_real_escape_string($user_id);
    $phone_id = update_phone_number($phone_id, $phone_number, $type, $canSMS, $ProviderID, $Ext, $sms_preference, $phonedescription);

    return $phone_id;
}

/**
 * Adds a phone number to the database without linking to a user.
 * @param phone_number the phone number as a string
 * @param type type of phone number ('HOME', 'MOBILE', 'WORK', 'FAX', 'OTHER')
 * @return ID of phone number within the system or FALSE on error
 */
function add_phone_number($phone_number, $type, $canSMS = 'N', $ProviderID = 0, $Ext = "", $sms_preference = "FIRST", $phonedescription = "") {

    $safe_number = mysql_real_escape_string( format_phone_number( $phone_number ) );
    $phonedescription = mysql_real_escape_string( $phonedescription );
    $safe_type = mysql_real_escape_string($type);
    $ProviderID = $ProviderID == '' ? 0 : $ProviderID;

    $sql = "INSERT INTO phone (PhoneType, PhoneNumber, canSMS, ProviderID, Ext, sms_preferences, phonedescription) VALUES ('$safe_type', '$safe_number', '$canSMS', $ProviderID, '$Ext', '$sms_preference', '$phonedescription')";

    $result = mysql_query($sql);
    if (!$result) {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error inserting phone number $phone_number/$phone_type", $sql);
        return FALSE;
    }     

    return mysql_insert_id();
}

function get_phone_number( $phone_id ){
	if($phone_id == NULL)
		return;
	$safe_phone_id = mysql_real_escape_string($phone_id);
	
	$sql = "SELECT * FROM `phone` WHERE `PhoneID` = $safe_phone_id LIMIT 1;";
	$result = mysql_query($sql);
	
	if($result){
		if(mysql_num_rows($result) < 1)
			return FALSE;
		return mysql_fetch_array($result);
	} else {
		echo "Could not get phone number for PhoneID $phone_id :" . mysql_error();
		return FALSE;
	}
}

function update_phone_number( $phone_id, $phone_number, $type, $canSMS = 'N', $ProviderID = 0, $Ext = "", $sms_preference = 'FIRST', $phonedescription = ""){
	$safe_phone_id = mysql_real_escape_string($phone_id);
	$safe_phone_number = mysql_real_escape_string( format_phone_number($phone_number) );
	$safe_type = mysql_real_escape_string($type);
	$phonedescription = mysql_real_escape_string( $phonedescription );
	$ProviderID = $ProviderID == '' ? 0 : $ProviderID;
	
	$sql = "UPDATE `phone` SET `PhoneType` = '$safe_type', `PhoneNumber` = '$safe_phone_number',
		canSMS = '$canSMS', ProviderID = $ProviderID, `Ext` = '$Ext', sms_preferences = '$sms_preference', phonedescription = '$phonedescription'
		WHERE `PhoneID` =$safe_phone_id";
	//echo "$sql<BR><BR>";
	$result = mysql_query($sql);
	
	if($result){
		return $phone_id;
	} else {
		echo "Could not get phone number for PhoneID $phone_id :" . mysql_error();
		return FALSE;
	}
}
function delete_phone_number( $phone_id ){
	$safe_phone_id = mysql_real_escape_string($phone_id);
	
	$sql = "DELETE FROM `phone` WHERE `PhoneID` = '$safe_phone_id' LIMIT 1;";
	$result = mysql_query($sql);
	
	if(!$result){
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not delete phone number $phone_id", $sql);
		return FALSE;
	}
	return TRUE;
}

function delete_all_user_phone_number( $user_id ) {
	$safe_user_id = mysql_real_escape_string( $user_id );
	
	$sql = "DELETE user_phone, phone FROM user_phone NATURAL JOIN phone
            WHERE UserID = $safe_user_id";
	$result = mysql_query($sql);
	
	if (!$result) {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not delete phone numbers for user $user_id", $sql);
		return FALSE;
	}
}

function format_phone_number( $phone_number ){
	$phone_number = preg_replace("/[^0-9]/", "", $phone_number);
	if(strlen($phone_number) == 7){
		return substr($phone_number, 0, 3) . "-" . substr($phone_number, 3, 6);
	} else if(strlen($phone_number) == 10){
		return substr($phone_number, 0, 3) . "-" . substr($phone_number, 3, 3) . "-". substr($phone_number, 6, 4);
	} else if(strlen($phone_number) == 11){
		return substr($phone_number, 0, 1) . "-" . substr($phone_number, 1, 3) . "-" . substr($phone_number, 4, 3) . "-". substr($phone_number, 7, 4);
	} else {
		return $phone_number;
	}
}

function get_phone_number_for_user($user_id){
	$safe_user_id = mysql_real_escape_string($user_id);	
	
	$sql = "SELECT UserID, PhoneNumber, Ext FROM user_phone NATURAL JOIN phone WHERE UserID = $safe_user_id  ORDER BY IsPrimary DESC, FIELD( PhoneType, 'MOBILE','HOME','WORK','OTHER') LIMIT 1;";
	$result  = mysql_query($sql);
	if($result){
		if(mysql_num_rows($result) > 0)
			return mysql_fetch_array($result);
		else
			return FALSE;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get phone number for user $user_id", $sql);
		return FALSE;
	}
}



function get_phone_number_for_users($user_ids){
	if(!is_array($user_ids))
		return array();
	$sql = "SELECT UserID, PhoneNumber, Ext FROM user_phone NATURAL JOIN phone WHERE UserID IN ( " . implode(", ", $user_ids) . " ) GROUP BY UserID ORDER BY IsPrimary DESC, FIELD( PhoneType, 'MOBILE','HOME','WORK','OTHER')";
	$result  = mysql_query($sql);
	if($result){
		$phones = array();
		while($row = mysql_fetch_array($result))
			$phones[$row['UserID']] = $row['PhoneNumber'].($row['Ext'] != '' ? '<BR>x'.$row['Ext'] : '');
		return $phones;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get phone number for user $user_id", $sql);
		return FALSE;
	}
}

function get_HTML_phone_number_input($phone){
	$html = "<table id=\"phone{$phone['PhoneID']}\"><tr><td>Phone Number Type:</td><td><select name=\"PhoneType[{$phone['PhoneID']}]\">";
				$html .= "<option " . ($phone['PhoneType'] == "HOME" ? 'SELECTED' : '') . "value=\"HOME\">Home</option>";
				$html .= "<option value=\"MOBILE\"" . ($phone['PhoneType'] == "MOBILE" ? 'SELECTED' : '') . ">Mobile</option>";
				$html .= "<option value=\"WORK\"" . ($phone['PhoneType'] == "WORK" ? 'SELECTED' : '') . ">Work</option>";
				$html .= "<option value=\"UNKNOWN\"" . ($phone['PhoneType'] == "UNKNOWN" ? 'SELECTED' : '') . ">Unknown</option>";
				$html .= "<option value=\"OTHER\"" . ($phone['PhoneType'] == "OTHER" ? 'SELECTED': '') . ">Other</option>";
 $html .= "</select></td></tr><tr valign=top><td>Phone Number</td><td><input style=\"width:120px\" type=\"text\" name=\"PhoneNumber[{$phone['PhoneID']}]\" value=\"{$phone['PhoneNumber']}\" maxlength=\"20\" style=\"vertical-align: bottom;\"/> x<input type=\"text\" name=\"Ext[{$phone['PhoneID']}]\" value=\"{$phone['Ext']}\" maxlength=\"5\" style=\"vertical-align: bottom; width:33px;\"/><br>Name: <input style=\"width:120px\" type=\"text\" name=\"PhoneDescription[{$phone['PhoneID']}]\" value=\"{$phone['phonedescription']}\"/></td></tr></table>";
return trim($html);
}

function get_HTML_add_phone_Number_button($new_row = FALSE, $extendedFieldName = FALSE){
	$new_input = get_HTML_phone_number_input(array('PhoneID' => '{phonenumberid}'));
    if($new_row) $add_line = "$('addPhoneNumber').fireEvent('click');";
	$html = "
	<button type=\"button\" id=\"addPhoneNumber\">Add Another Phone Number</button><br>
	<script type=\"text/javascript\">
		var id = -1;
		$('addPhoneNumber').addEvent('click',function(){
			var new_html = '$new_input';
			".($extendedFieldName ? "
				new_html = new_html.replace('PhoneType[','data[Emergency][PhoneType][');
				new_html = new_html.replace('PhoneDescription[','data[Emergency][PhoneDescription][');
				new_html = new_html.replace('PhoneNumber[','data[Emergency][PhoneNumber][');
				new_html = new_html.replace('Ext[','data[Emergency][Ext][');
				" : "")."
			new_html = new_html.replace(/\{phonenumberid\}/g,id);

			
			new Element('div', {
				html: new_html
			}).inject(this,'before');
			id -= 1;
			$add_line
		});
	</script>
	";
return $html;
}
?>