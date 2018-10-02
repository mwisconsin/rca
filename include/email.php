<?php

require_once('include/database.php');

/**
 * Adds a email address to the database without linking to a user.
 * @param email_address the email address as a string
 * @return ID of email address within the system or FALSE on error
 */
function add_email_address($email_address) {
    $safe_email = mysql_real_escape_string($email_address);

    $sql = "INSERT INTO email (EmailAddress) VALUES ('$safe_email')";

    $result = mysql_query($sql);
    if (!$result) {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error inserting email address $email_address", $sql);
        return FALSE;
    }     

    return mysql_insert_id();
}

function get_email_address( $email_id ){
	$safe_email_id = mysql_real_escape_string($email_id);
	
	if($email_id == NULL)
		return FALSE;
	
	$sql = "SELECT * FROM `email` WHERE `EmailID` = {$safe_email_id} LIMIT 1;";
	$result = mysql_query($sql);
	
	if($result){
		if(mysql_num_rows($result) < 1)
			return FALSE;
		return mysql_fetch_array($result);
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Failed to select email for EmailID $email_id ", $sql);
		return FALSE;
	}
}
function update_email_address( $email_id , $email_address ){
	$safe_email_id = mysql_real_escape_string($email_id);
	$safe_email_address = mysql_real_escape_string($email_address);
	$sql = "UPDATE `email` SET `EmailAddress` = '$safe_email_address', `IsVerified`= 'No' WHERE `EmailID` =$safe_email_id LIMIT 1;";
	$result = mysql_query($sql);
	
	if($result){
		return TRUE;
	} else {
		echo "Failed to update email for EmailID $email_id :" . mysql_error();
		return FALSE;
	}
}
function delete_email_address( $email_id ){
	$safe_email_id = mysql_real_escape_string($email_id);
	
	$sql = "DELETE FROM `email` WHERE `EmailID` = '$safe_email_id' LIMIT 1;";
	$result = mysql_query($sql);
	
	if(!$result){
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not delete email $email_id", $sql);
		return FALSE;
	}
	return TRUE;
}
?>