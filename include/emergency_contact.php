<?php

include_once 'include/database.php';

function get_emergency_contact( $emergency_id ){
	if($emergency_id == NULL)
		return FALSE;
		
	$safe_emergency_id = mysql_real_escape_string($emergency_id);
	$sql = "SELECT * 
            FROM `emergency_contact` WHERE `EmergencyContactID` = $safe_emergency_id LIMIT 1";
	$result = mysql_query($sql);
	
	if($result){
		$return = mysql_fetch_array($result, MYSQL_ASSOC);
	} else {
		echo "Could not get emergency contact info for id $emergency_id: " . mysql_error();
		$return = FALSE;
	}
	return $return;
}

function delete_emergency_contact( $emergency_id ){
	$emergency_contact = get_emergency_contact( $emergency_id );
	$safe_emergency_id = mysql_real_escape_string( $emergency_id );
	$sql = "DELETE FROM `emergency_contact` WHERE `EmergencyContactID` = '$safe_emergency_id' LIMIT 1;";
		$result = mysql_query($sql);
	
		if($result){
			delete_name($emergency_contact['EmergencyContactName']);
			delete_address($emergency_contact['Address']);
			delete_phone_number($emergency_contact['Phone']);
			delete_email_address($emergency_contact['Email']);
		} else {
			rc_log_db_error(PEAR_LOG_ERR, mysql_error,
	                        "Could not delete driver emergency contact $driver_id", $sql);
	        return FALSE;
		}
}
function notify_emergency_contact( $user_id, $emergency_id ){
		$contact = get_emergency_contact( $emergency_id );
		$contact_name = get_name($contact['EmergencyContactName']);
		$contact_phone = get_phone_number($contact['Phone']);
		if(!$contact_phone){
			$secondary_phone = get_emergency_contact_secondary_phones($emergency_id);
			$contact_phone = $secondary_phone[0];
		}
		$email_address = get_email_address($contact['Email']);

		$user = get_user_account($user_id);
		$user_name = get_name($user['PersonNameID']);

		$link = site_url() . "/verify_email_address.php?id={$email_address['EmailID']}&hash=" . sha1('Verify' . $email_address['EmailID'] . $email_address['EmailAddress'] . $email_address['IsVerified']);
		$to = $email_address;
		$subject = "Riders Club of America requests confirmation of Emergency Contact Status";

        $body = <<<BODY
Dear {$contact_name['FirstName']} {$contact_name['LastName']},

Riders Club of America is a ride-share program serving the Cedar Rapids Area.  For a greatly reduced rate, qualified riders can schedule transport within the Cedar Rapids Metropolitan area.

You have been identified as the emergency contact for {$user_name['FirstName']} {$user_name['LastName']}.  If there is need to contact you, we will both send you an email and try to phone you at {$contact_phone['PhoneNumber']}.

Please verify this email address by clicking on the link below.

{$link}

If you have any questions, please call us at 319.365.1511.  Our office is open 9:00 a.m. to 3:00 p.m., Monday through Friday.

Thank you for your support.

Martin Wissenberg
Executive Director
Riders Club of America
BODY;

		if ($to['EmailAddress'] != '') {
			mail($to['EmailAddress'],$subject,$body, DEFAULT_EMAIL_FROM);
        }
}

function get_emergency_contact_secondary_phones($contact_id){
    $safe_contact_id = mysql_real_escape_string($contact_id);
    $sql = "SELECT * FROM emergency_contact_phone NATURAL JOIN phone WHERE EmergencyContactID = $safe_contact_id AND emergency_contact_phone.PhoneID = phone.PhoneID";
    $result = mysql_query($sql) or die($sql);
    
    if($result){
        $numbers = array();
        while($row = mysql_fetch_array($result))
            $numbers[] = $row;
        return $numbers;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error,
	                        "Could not get secondary phone numbers for contact $contact_id", $sql);
	        return FALSE;
    }
}
function link_phone_number_to_emergency_contact($contact_id, $phone_id){
    $safe_contact_id = mysql_real_escape_string($contact_id);
    $safe_phone_id = mysql_real_escape_string($phone_id);
    $sql = "INSERT INTO `emergency_contact_phone` ( `EmergencyContactID` , `PhoneID` )
                                           VALUES ( '$safe_contact_id', '$safe_phone_id' );";
    $result = mysql_query($sql);
    
    if($result){
        return true;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error,
	                        "Could not link secondary phone numbers for contact $contact_id", $sql);
        return FALSE;
    }
}

function remove_phone_number_for_emergency_contact($contact_id, $phone_id){
    $safe_contact_id = mysql_real_escape_string($contact_id);
    $safe_phone_id = mysql_real_escape_string($phone_id);
    $sql = "DELETE FROM `emergency_contact_phone` WHERE `EmergencyContactID` = $safe_contact_id AND `PhoneID` = $safe_phone_id";
    $result = mysql_query($sql);
    
    if($result){
        return true;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error,
	                        "Could not remove secondary phone number $phone_id for contact $contact_id", $sql);
        return FALSE;
    }
}



function createEmergencyContact($user_id, $emergency_contact, $verfiy_address=true, $driver_or_rider='rider') {

				$contact_name = add_person_name($emergency_contact['Title'],$emergency_contact['FirstName'],$emergency_contact['MiddleInitial'],$emergency_contact['LastName'],$emergency_contact['Suffix']);
				
				$address = array('Address1' => $emergency_contact['Address1'],
								 'Address2' => $emergency_contact['Address2'],
								 'City' => $emergency_contact['City'],
								 'State' => $emergency_contact['State'],
								 'ZIP5' => $emergency_contact['Zip5'],
								 'ZIP4' => $emergency_contact['Zip4']);
								
								 
				$contact_address = add_address($address);
				
				$contact_phone = add_phone_number($emergency_contact['PhoneNumber'][0],$emergency_contact['PhoneType'][0],'N',0,@$emergency_contact['Ext'][0],'FIRST',@$emergency_contact['PhoneDescription'][0]);
				
				$contact_email = ($emergency_contact['Email'] != '') ? add_email_address($emergency_contact['Email']) : 'NULL';
				
				$query = "INSERT INTO `emergency_contact` (`EmergencyContactID`, `EmergencyContactName`, `Address`, `Phone`, `Email`) VALUES (NULL, '$contact_name', '$contact_address', '$contact_phone', $contact_email);";

				if (!mysql_query($query)) {
                    rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                                    "Could not create emergency contact", $query);
                    return false;
                }

				$contact_id = mysql_insert_id();
				
				$keys = array_keys($emergency_contact['PhoneNumber']);
				for($i = 0; $i < count($keys); $i++){
				    if($keys[$i] < 0 && $emergency_contact['PhoneNumber'][ $keys[$i] ] != ''){
				        $phone_id = add_phone_number($emergency_contact['PhoneNumber'][ $keys[$i] ],$emergency_contact['PhoneType'][ $keys[$i] ],'N',0,$emergency_contact['Ext'][ $keys[$i] ]);
				        link_phone_number_to_emergency_contact($contact_id, $phone_id);
				    }
				}
				
				$safe_user_id = mysql_real_escape_string($user_id);
				$safe_relationship = mysql_real_escape_string($emergency_contact['EmergencyContactRelationship']);
				$query ="UPDATE `".$driver_or_rider."` SET `EmergencyContactID` = $contact_id, 
                                            `EmergencyContactRelationship` = '$safe_relationship' 
                         WHERE `UserID` = $safe_user_id";

				if (!mysql_query($query)) {
                    rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                                    "Could not connect emergency contact $contact_id to rider $user_id", $sql);
                    return false;
                }
				
				//notify_emergency_contact($user_id, $contact_id);
				return true;
}
?>
