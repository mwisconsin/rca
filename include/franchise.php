<?php 

require_once('include/database.php');
require_once('include/rc_log.php');
require_once('include/user.php');

    /**
 * Returns the Franchise Name of the identified Franchise.
 * @param franchise_id ID of the franchise to get the name for
 * @return name of franchise of franchise_id. false of error
 */
function get_franchise_name( $franchise_id ){
	$query = "SELECT * FROM `franchise` WHERE `FranchiseID` = '" . mysql_real_escape_string($franchise_id) . "' LIMIT 1;";
	$result = mysql_query($query) or die(mysql_error());
	if(mysql_num_rows($result) != 1)
		return FALSE;
	$result = mysql_fetch_array($result);
	return $result['FranchiseName'];
}

/**
 * Returns the franchise that serves a given ZIP code.
 * Assumption:  There can only be one.  
 * @param zip 5-digit ZIP code
 * @return Franchise ID (integer) or FALSE (zero is also invalid, so === may not be necessary)
 */
function get_franchise_by_zip( $zip ) {
    $safe_zip = mysql_real_escape_string($zip);

    $sql = "SELECT FranchiseID FROM franchise_service_area WHERE ZIP5 = $safe_zip";

    $result = mysql_query($sql);

    if ($result) {
        // TODO:  Check for multiple possible values?
        $row = mysql_fetch_array($result, MYSQL_ASSOC);

        return $row['FranchiseID'];
    } else {
        rc_log_db_error(PEAR_LOG_WARNING, mysql_error(),
                        "Error looking up franchise for ZIP $zip", $sql);
    }

    return FALSE;
}

function get_franchise_service_zips($franchise_id) {
    $safe_franchise_id = mysql_real_escape_string($franchise_id);

    $sql = "SELECT ZIP5 FROM franchise_service_area WHERE FranchiseID = $safe_franchise_id";

    $result = mysql_query($sql);

    if ($result) {
        $zips = array();
        while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
            $zips[$row['ZIP5']] = $row['ZIP5'];
        }

        return $zips;
    } else {
        rc_log_db_error(PEAR_LOG_WARNING, mysql_error(),
                        "Error looking up service ZIPs for $franchise_service_area", $sql);
    }
    return FALSE;
}

function add_franchise_service_area($franchise, $zip){
	if($zip == NULL || $zip == '')
		return FALSE;
	$safe_franchise = mysql_real_escape_string($franchise);
	$safe_zip = mysql_real_escape_string($zip);
	
	$sql = "INSERT INTO `franchise_service_area` (`FranchiseID` ,`ZIP5`)
											VALUES ('$safe_franchise', '$safe_zip');";
	$result = mysql_query($sql);
	
	if($result){
		return TRUE;
	} else {
		rc_log_db_error(PEAR_LOG_WARNING, mysql_error(),
                        "Error adding service ZIPs for $franchise", $sql);
		return FALSE;
	}
}

function remove_franchise_service_zip($franchise, $zip){
	$safe_franchise = mysql_real_escape_string($franchise);
	$safe_zip = mysql_real_escape_string($zip);
	
	$sql = "DELETE FROM `franchise_service_area` WHERE `FranchiseID` = $safe_franchise AND `ZIP5` = '$safe_zip'";	
	$result = mysql_query($sql);
	
	if($result){
		return TRUE;
	} else {
		rc_log_db_error(PEAR_LOG_WARNING, mysql_error(),
                        "Error deleting service ZIPs for $franchise", $sql);
		return FALSE;
	}
}

function is_zip_out_of_area( $franchise_id, $zip ) {
    $sql = "SELECT ZIP5 FROM franchise_service_area WHERE FranchiseID = $franchise_id and ZIP5 = $zip";
    $result = mysql_query($sql);	
    return mysql_num_rows($result) == 0;
}


/**
 * Returns an array of all franchises.  Key is franchise ID, value is name.
 */
function get_franchise_name_id_list() {
    $sql = "SELECT FranchiseID, FranchiseName FROM franchise";

    $result = mysql_query($sql);
    if ($result) {
        $franchises = array();
        while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
            $franchises[$row['FranchiseID']] = $row['FranchiseName'];
			//echo $row['FranchiseName'];
        }
        return $franchises;
    }

    rc_log_db_error(PEAR_LOG_ERR, mysql_error(), "Error retrieving franchise list", $sql);
    return FALSE;
}


function get_user_franchise( $user_id ) {
   	echo "DEPRECATED FUNCTION ---- get_user_franchise() function was called";
	die();
    return FALSE;

}

function get_current_user_franchise($should_redirect = true){
	if(isset($_SESSION['UserFranchiseID']))
		return $_SESSION['UserFranchiseID'];
	
	if($should_redirect){
		$_SESSION['RedirectURL'] = $_SERVER['PHP_SELF'];
		header("Location: select_club.php");
	}
	
	return false;
	die();
}
function get_user_franchises($user_id){
	//if($_SESSION['UserFranchises'][$user_id])
		//return $_SESSION['UserFranchises'][$user_id];
	
	$safe_uid = mysql_real_escape_string($user_id);
	if(!user_has_role($user_id, 1, "FullAdmin")){
    	$sql = "SELECT franchise.FranchiseID, FranchiseName FROM `user_role` LEFT JOIN franchise ON `user_role`.FranchiseID = franchise.FranchiseID WHERE user_role.UserID = '$user_id' GROUP BY franchise.FranchiseID";
	} else {
		$sql = "SELECT FranchiseID, FranchiseName FROM franchise";
	}
    $result =  mysql_query($sql);
    
    if($result){
		
    	$franchises = array();
    	
    	while($row = mysql_fetch_array($result)){
    		$franchises[] = $row;
       	}
		$_SESSION['UserFranchises'][$user_id] = $franchises;
       	return $franchises;
    } else {
    	rc_log_db_error(PEAR_LOG_ERR, mysql_error(), "Error retrieving users ($user_id) franchise list", $sql);
    	return FALSE;
    }
    
}

function user_has_franchise($user_id, $franchise_id){
	$franchises = get_user_franchises($user_id);
	foreach($franchises as $franchise){
		if($franchise['FranchiseID'] == $franchise_id)
			return TRUE;
	}
	return false;
}

function add_franchise_role($user_id, $franchise, $role){
	set_role_for_user($user_id, $franchise, $role);
}


function save_user_default_franchise($user_id, $franchise){
	$safe_franchise = mysql_real_escape_string($franchise);
	$safe_user = mysql_real_escape_string($user_id);
	
	$sql = "UPDATE users SET DefaultFranchiseID = $safe_franchise WHERE UserID = $safe_user LIMIT 1;";
	$result = mysql_query($sql);
	
	if($result){
		
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), "Error setting users ($user_id) default franchise", $sql);
    	return FALSE;
	}
}

function get_user_default_franchise($user_id){
	
	$safe_user_id = mysql_real_escape_string($user_id);
	
	
	$sql = "SELECT DefaultFranchiseID FROM users WHERE UserID = $safe_user_id AND DefaultFranchiseID IS NOT NULL";
	$result = mysql_query($sql);
	
	if($result){
		if(mysql_num_rows($result) < 1)
			return false;
		$results = mysql_fetch_array($result);
		return $results['DefaultFranchiseID'];
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), "Error setting users ($user_id) default franchise", $sql);
    	return FALSE;
	}
}


function updateFranchise($franchise_id, $franchise_name, $office_hours, $office_time_zone, $processing_fee, $main_phone_number, $emergency_phone_number, $club_id,
                         $address1, $address2, $city, $state, $zip5, $zip4, $primarycontact) {
  $sql = "UPDATE franchise set FranchiseName='".addslashes($franchise_name).
  	"', OfficeHours='".addslashes($office_hours)."', OfficeTimeZone='$office_time_zone', ProcessingFee='"
  	.$processing_fee."' where FranchiseID='".(int)$franchise_id."'";
  mysql_query($sql) or die(mysql_error());
  
  $phone_check = mysql_query("select MainPhoneID from franchise where FranchiseID='".(int)$franchise_id."'");
  $phone_row = mysql_fetch_assoc($phone_check);
  if ($phone_row['MainPhoneID']) {
    // update entry
	mysql_query("update phone set PhoneNumber='".$main_phone_number."' where PhoneID='".$phone_row['MainPhoneID']."'");
  } else {
    // create entry
	mysql_query("insert into phone (PhoneType, PhoneNumber) values ('WORK', '".$main_phone_number."')");
	$phone_id = mysql_insert_id();
	mysql_query("update franchise set MainPhoneID='".$phone_id."' where FranchiseID='".(int)$franchise_id."'");
  }
  
  $phone_check2 = mysql_query("select EmergencyPhoneID from franchise where FranchiseID='".(int)$franchise_id."'");
  $phone_row2 = mysql_fetch_assoc($phone_check2);
  if ($phone_row2['EmergencyPhoneID']) {
    // update entry
	mysql_query("update phone set PhoneNumber='".$emergency_phone_number."' where PhoneID='".$phone_row2['EmergencyPhoneID']."'");
  } else {
    // create entry
	mysql_query("insert into phone (PhoneType, PhoneNumber) values ('WORK', '".$emergency_phone_number."')");
	$phone_id = mysql_insert_id();
	mysql_query("update franchise set EmergencyPhoneID='".$phone_id."' where FranchiseID='".(int)$franchise_id."'");
  }
  
  if (current_user_has_role(1, 'FullAdmin')) {
    mysql_query("update franchise set ClubID='".$club_id."' where FranchiseID='".(int)$franchise_id."'");
  }

  
  $address_check = mysql_query("select AddressID from franchise where FranchiseID='".(int)$franchise_id."'");
  $address_row = mysql_fetch_assoc($address_check);
  if ($address_row['AddressID']) {
    // update entry
	mysql_query("update address set Address1='".$address1."', Address2='".$address2."', City='".$city."', State='".$state."', ZIP5='".$zip5."', ZIP4='".$zip4."' where AddressID='".$address_row['AddressID']."'");
  } else {
    // create entry
	mysql_query("insert into address (Address1, Address2, City, State, ZIP5, ZIP4) values ('".$address1."', '".$address2."', '".$city."', '".$state."', '".$zip5."', '".$zip4."')");
	$address_id = mysql_insert_id();
	mysql_query("update franchise set AddressID='".$address_id."' where FranchiseID='".(int)$franchise_id."'");
  }
  
	mysql_query("update franchise set UserID = $primarycontact where FranchiseID = $franchise_id");
}
function updateFranchiseLogo($franchiseid, $logo_file_name) {
    mysql_query("update franchise set LogoSRC='".$logo_file_name."' where FranchiseID='".(int)$franchiseid."'");
}

function updateFranchiseEmails($franchiseid, $post_vars) {
  $email_check = mysql_query("select * from franchise_email_settings where FranchiseID='".(int)$franchiseid."' and EmailType='cc_processing'");
  if (mysql_num_rows($email_check)>0) {
	  $email_row = mysql_fetch_assoc($email_check);
		// update entry
		mysql_query("update email set EmailAddress='".$_POST['cc_processing1']."' where EmailID='".$email_row['EmailID1']."'");
		mysql_query("update email set EmailAddress='".$_POST['cc_processing2']."' where EmailID='".$email_row['EmailID2']."'");
  } else {
      // create entry
	  mysql_query("insert into email (EmailAddress, IsVerified) values ('".$_POST['cc_processing1']."', 'No')");
	  $email_id1 = mysql_insert_id();
	  mysql_query("insert into email (EmailAddress, IsVerified) values ('".$_POST['cc_processing2']."', 'No')");
	  $email_id2 = mysql_insert_id();
	  mysql_query("insert into franchise_email_settings (EmailID1, EmailID2, EmailType, FranchiseID) values ('".$email_id1."', '".$email_id2."', 'cc_processing', '".(int)$franchiseid."')");
  }
  $email_check = mysql_query("select * from franchise_email_settings where FranchiseID='".(int)$franchiseid."' and EmailType='cc_thursday'");
  if (mysql_num_rows($email_check)>0) {
	  $email_row = mysql_fetch_assoc($email_check);
		// update entry
		mysql_query("update email set EmailAddress='".$_POST['cc_thursday1']."' where EmailID='".$email_row['EmailID1']."'");
		mysql_query("update email set EmailAddress='".$_POST['cc_thursday2']."' where EmailID='".$email_row['EmailID2']."'");
  } else {
      // create entry
	  mysql_query("insert into email (EmailAddress, IsVerified) values ('".$_POST['cc_thursday1']."', 'No')");
	  $email_id1 = mysql_insert_id();
	  mysql_query("insert into email (EmailAddress, IsVerified) values ('".$_POST['cc_thursday2']."', 'No')");
	  $email_id2 = mysql_insert_id();
	  mysql_query("insert into franchise_email_settings (EmailID1, EmailID2, EmailType, FranchiseID) values ('".$email_id1."', '".$email_id2."', 'cc_thursday', '".(int)$franchiseid."')");
  }
  
  $email_check = mysql_query("select * from franchise_email_settings where FranchiseID='".(int)$franchiseid."' and EmailType='billing_contact'");
  if (mysql_num_rows($email_check)>0) {
	  $email_row = mysql_fetch_assoc($email_check);
		// update entry
		mysql_query("update email set EmailAddress='".$_POST['billing_contact1']."' where EmailID='".$email_row['EmailID1']."'");
		mysql_query("update email set EmailAddress='".$_POST['billing_contact2']."' where EmailID='".$email_row['EmailID2']."'");
  } else {
      // create entry
	  mysql_query("insert into email (EmailAddress, IsVerified) values ('".$_POST['billing_contact1']."', 'No')");
	  $email_id1 = mysql_insert_id();
	  mysql_query("insert into email (EmailAddress, IsVerified) values ('".$_POST['billing_contact2']."', 'No')");
	  $email_id2 = mysql_insert_id();
	  mysql_query("insert into franchise_email_settings (EmailID1, EmailID2, EmailType, FranchiseID) values ('".$email_id1."', '".$email_id2."', 'billing_contact', '".(int)$franchiseid."')");
  }
  
  $email_check = mysql_query("select * from franchise_email_settings where FranchiseID='".(int)$franchiseid."' and EmailType='ra_threshold'");
  if (mysql_num_rows($email_check)>0) {
	  $email_row = mysql_fetch_assoc($email_check);
		// update entry
		mysql_query("update email set EmailAddress='".$_POST['ra_threshold1']."' where EmailID='".$email_row['EmailID1']."'");
		mysql_query("update email set EmailAddress='".$_POST['ra_threshold2']."' where EmailID='".$email_row['EmailID2']."'");
  } else {
      // create entry
	  mysql_query("insert into email (EmailAddress, IsVerified) values ('".$_POST['ra_threshold1']."', 'No')");
	  $email_id1 = mysql_insert_id();
	  mysql_query("insert into email (EmailAddress, IsVerified) values ('".$_POST['ra_threshold2']."', 'No')");
	  $email_id2 = mysql_insert_id();
	  mysql_query("insert into franchise_email_settings (EmailID1, EmailID2, EmailType, FranchiseID) values ('".$email_id1."', '".$email_id2."', 'ra_threshold', '".(int)$franchiseid."')");
  }
  
  $email_check = mysql_query("select * from franchise_email_settings where FranchiseID='".(int)$franchiseid."' and EmailType='ra_annual_fee'");
  if (mysql_num_rows($email_check)>0) {
	  $email_row = mysql_fetch_assoc($email_check);
		// update entry
		mysql_query("update email set EmailAddress='".$_POST['ra_annual_fee1']."' where EmailID='".$email_row['EmailID1']."'");
		mysql_query("update email set EmailAddress='".$_POST['ra_annual_fee2']."' where EmailID='".$email_row['EmailID2']."'");
  } else {
      // create entry
	  mysql_query("insert into email (EmailAddress, IsVerified) values ('".$_POST['ra_annual_fee1']."', 'No')");
	  $email_id1 = mysql_insert_id();
	  mysql_query("insert into email (EmailAddress, IsVerified) values ('".$_POST['ra_annual_fee2']."', 'No')");
	  $email_id2 = mysql_insert_id();
	  mysql_query("insert into franchise_email_settings (EmailID1, EmailID2, EmailType, FranchiseID) values ('".$email_id1."', '".$email_id2."', 'ra_annual_fee', '".(int)$franchiseid."')");
  }
  
  $email_check = mysql_query("select * from franchise_email_settings where FranchiseID='".(int)$franchiseid."' and EmailType='de_availability_change'");
  if (mysql_num_rows($email_check)>0) {
	  $email_row = mysql_fetch_assoc($email_check);
		// update entry
		mysql_query("update email set EmailAddress='".$_POST['de_availability_change1']."' where EmailID='".$email_row['EmailID1']."'");
		mysql_query("update email set EmailAddress='".$_POST['de_availability_change2']."' where EmailID='".$email_row['EmailID2']."'");
  } else {
      // create entry
	  mysql_query("insert into email (EmailAddress, IsVerified) values ('".$_POST['de_availability_change1']."', 'No')");
	  $email_id1 = mysql_insert_id();
	  mysql_query("insert into email (EmailAddress, IsVerified) values ('".$_POST['de_availability_change2']."', 'No')");
	  $email_id2 = mysql_insert_id();
	  mysql_query("insert into franchise_email_settings (EmailID1, EmailID2, EmailType, FranchiseID) values ('".$email_id1."', '".$email_id2."', 'de_availability_change', '".(int)$franchiseid."')");
  }
  
  $email_check = mysql_query("select * from franchise_email_settings where FranchiseID='".(int)$franchiseid."' and EmailType='de_month_end_allocation'");
  if (mysql_num_rows($email_check)>0) {
	  $email_row = mysql_fetch_assoc($email_check);
		// update entry
		mysql_query("update email set EmailAddress='".$_POST['de_month_end_allocation1']."' where EmailID='".$email_row['EmailID1']."'");
		mysql_query("update email set EmailAddress='".$_POST['de_month_end_allocation2']."' where EmailID='".$email_row['EmailID2']."'");
  } else {
      // create entry
	  mysql_query("insert into email (EmailAddress, IsVerified) values ('".$_POST['de_month_end_allocation1']."', 'No')");
	  $email_id1 = mysql_insert_id();
	  mysql_query("insert into email (EmailAddress, IsVerified) values ('".$_POST['de_month_end_allocation2']."', 'No')");
	  $email_id2 = mysql_insert_id();
	  mysql_query("insert into franchise_email_settings (EmailID1, EmailID2, EmailType, FranchiseID) values ('".$email_id1."', '".$email_id2."', 'de_month_end_allocation', '".(int)$franchiseid."')");
  }
  
  $email_check = mysql_query("select * from franchise_email_settings where FranchiseID='".(int)$franchiseid."' and EmailType='de_insurance_1_yr'");
  if (mysql_num_rows($email_check)>0) {
	  $email_row = mysql_fetch_assoc($email_check);
		// update entry
		mysql_query("update email set EmailAddress='".$_POST['de_insurance_1_yr1']."' where EmailID='".$email_row['EmailID1']."'");
		mysql_query("update email set EmailAddress='".$_POST['de_insurance_1_yr2']."' where EmailID='".$email_row['EmailID2']."'");
  } else {
      // create entry
	  mysql_query("insert into email (EmailAddress, IsVerified) values ('".$_POST['de_insurance_1_yr1']."', 'No')");
	  $email_id1 = mysql_insert_id();
	  mysql_query("insert into email (EmailAddress, IsVerified) values ('".$_POST['de_insurance_1_yr2']."', 'No')");
	  $email_id2 = mysql_insert_id();
	  mysql_query("insert into franchise_email_settings (EmailID1, EmailID2, EmailType, FranchiseID) values ('".$email_id1."', '".$email_id2."', 'de_insurance_1_yr', '".(int)$franchiseid."')");
  }
  
  $email_check = mysql_query("select * from franchise_email_settings where FranchiseID='".(int)$franchiseid."' and EmailType='de_driver_license_exp'");
  if (mysql_num_rows($email_check)>0) {
	  $email_row = mysql_fetch_assoc($email_check);
		// update entry
		mysql_query("update email set EmailAddress='".$_POST['de_driver_license_exp1']."' where EmailID='".$email_row['EmailID1']."'");
		mysql_query("update email set EmailAddress='".$_POST['de_driver_license_exp2']."' where EmailID='".$email_row['EmailID2']."'");
  } else {
      // create entry
	  mysql_query("insert into email (EmailAddress, IsVerified) values ('".$_POST['de_driver_license_exp1']."', 'No')");
	  $email_id1 = mysql_insert_id();
	  mysql_query("insert into email (EmailAddress, IsVerified) values ('".$_POST['de_driver_license_exp2']."', 'No')");
	  $email_id2 = mysql_insert_id();
	  mysql_query("insert into franchise_email_settings (EmailID1, EmailID2, EmailType, FranchiseID) values ('".$email_id1."', '".$email_id2."', 'de_driver_license_exp', '".(int)$franchiseid."')");
  }
  
  $email_check = mysql_query("select * from franchise_email_settings where FranchiseID='".(int)$franchiseid."' and EmailType='de_vacation'");
  if (mysql_num_rows($email_check)>0) {
	  $email_row = mysql_fetch_assoc($email_check);
		// update entry
		mysql_query("update email set EmailAddress='".$_POST['de_vacation1']."' where EmailID='".$email_row['EmailID1']."'");
		mysql_query("update email set EmailAddress='".$_POST['de_vacation2']."' where EmailID='".$email_row['EmailID2']."'");
		mysql_query("update franchise_email_settings set vacation_end = $_POST[vacation_end], vacation_duration = $_POST[vacation_duration] where FranchiseID='".(int)$franchiseid."' and EmailType='de_vacation'");
  } else {
      // create entry
	  mysql_query("insert into email (EmailAddress, IsVerified) values ('".$_POST['de_vacation1']."', 'No')");
	  $email_id1 = mysql_insert_id();
	  mysql_query("insert into email (EmailAddress, IsVerified) values ('".$_POST['de_vacation2']."', 'No')");
	  $email_id2 = mysql_insert_id();
	  //print_r($_POST);
	  //echo "insert into franchise_email_settings (EmailID1, EmailID2, EmailType, FranchiseID, vacation_end, vacation_duration) values ('".$email_id1."', '".$email_id2."', 'de_vacation', '".(int)$franchiseid."', $_POST[vacation_end], $_POST[vacation_duration])";
	  mysql_query("insert into franchise_email_settings (EmailID1, EmailID2, EmailType, FranchiseID, vacation_end, vacation_duration) values ('".$email_id1."', '".$email_id2."', 'de_vacation', '".(int)$franchiseid."', $_POST[vacation_end], $_POST[vacation_duration])");
  }
  
  $email_check = mysql_query("select * from franchise_email_settings where FranchiseID='".(int)$franchiseid."' and EmailType='de_ride_taken'");
  if (mysql_num_rows($email_check)>0) {
	  $email_row = mysql_fetch_assoc($email_check);
		// update entry
		mysql_query("update email set EmailAddress='".$_POST['de_ride_taken1']."' where EmailID='".$email_row['EmailID1']."'");
		mysql_query("update email set EmailAddress='".$_POST['de_ride_taken2']."' where EmailID='".$email_row['EmailID2']."'");
  } else {
      // create entry
	  mysql_query("insert into email (EmailAddress, IsVerified) values ('".$_POST['de_ride_taken1']."', 'No')");
	  $email_id1 = mysql_insert_id();
	  mysql_query("insert into email (EmailAddress, IsVerified) values ('".$_POST['de_ride_taken2']."', 'No')");
	  $email_id2 = mysql_insert_id();
	  #echo "insert into franchise_email_settings (EmailID1, EmailID2, EmailType, FranchiseID) values ('".$email_id1."', '".$email_id2."', 'de_ride_taken', '".(int)$franchiseid."')";
	  mysql_query("insert into franchise_email_settings (EmailID1, EmailID2, EmailType, FranchiseID) values ('".$email_id1."', '".$email_id2."', 'de_ride_taken', '".(int)$franchiseid."')");
  }
  
  $email_check = mysql_query("select * from franchise_email_settings where FranchiseID='".(int)$franchiseid."' and EmailType='de_fix_map'");
  if (mysql_num_rows($email_check)>0) {
	  $email_row = mysql_fetch_assoc($email_check);
		// update entry
		mysql_query("update email set EmailAddress='".$_POST['de_fix_map1']."' where EmailID='".$email_row['EmailID1']."'");
		mysql_query("update email set EmailAddress='".$_POST['de_fix_map2']."' where EmailID='".$email_row['EmailID2']."'");
  } else {
      // create entry
	  mysql_query("insert into email (EmailAddress, IsVerified) values ('".$_POST['de_fix_map1']."', 'No')");
	  $email_id1 = mysql_insert_id();
	  mysql_query("insert into email (EmailAddress, IsVerified) values ('".$_POST['de_fix_map2']."', 'No')");
	  $email_id2 = mysql_insert_id();
	  #echo "insert into franchise_email_settings (EmailID1, EmailID2, EmailType, FranchiseID) values ('".$email_id1."', '".$email_id2."', 'de_fix_map', '".(int)$franchiseid."')";
	  mysql_query("insert into franchise_email_settings (EmailID1, EmailID2, EmailType, FranchiseID) values ('".$email_id1."', '".$email_id2."', 'de_fix_map', '".(int)$franchiseid."')");
  }
  //exit();
}

function savePriceVariables($franchise, $annual_fee = 0, $min_threshold = 0, $payment_reminder_timing = 4) {
  
  mysql_query("update franchise set AnnualFee='".($annual_fee*100)."', MinThreshold='".($min_threshold*100)."', PaymentReminderTiming=$payment_reminder_timing where FranchiseID='".(int)$franchise."'");
}

function getFranchiseMinThreshold($franchise) {
  $result = mysql_query("Select MinThreshold from franchise where FranchiseID='".(int)$franchise."'");
  if (mysql_num_rows($result)) {
    $row = mysql_fetch_assoc($result);
	return $row['MinThreshold'];
  } else {
    return 1000;
  }
}

function getAnnualFeeAmount($franchise) {
  $fee = 6000;
  $result = mysql_query("select AnnualFee from franchise where FranchiseID='".(int)$franchise."'");
  if (mysql_num_rows($result)>0) {
    $row = mysql_fetch_assoc($result);
	$fee = $row['AnnualFee'];
  }
  return $fee;
}

function get_franchise_email_addresses($franchise, $setting_type) {
  $result = mysql_query("select e1.EmailAddress as EmailAddress1, e2.EmailAddress as EmailAddress2 from franchise_email_settings fes, email e1, email e2 where e1.EmailID=fes.EmailID1 and e2.EmailID=fes.EmailID2 and fes.FranchiseID='".(int)$franchise."' and fes.EmailType='".$setting_type."'");
  
  if (mysql_num_rows($result)>0) {
    $row = mysql_fetch_assoc($result);
	return array($row['EmailAddress1'], $row['EmailAddress2']);
  } else {
    return array();
  }
}

function getFranchiseLogo($franchise) {
  $result = mysql_query("select LogoSRC from franchise where FranchiseID='".(int)$franchise."'");
  if (mysql_num_rows($result)>0) {
    $row = mysql_fetch_assoc($result);
	if ($row['LogoSRC']) {
      return 'images/logos/'.$row['LogoSRC'];
	} else {
	  return 'images/logo3.png';
	}
  } else {
    return 'images/logo3.png';
  }
}

function getFranchiseDefaultEmail($franchise) {
  $result = mysql_query("select Email from franchise where FranchiseID='".(int)$franchise."'");
  if (mysql_num_rows($result)>0) {
    $row = mysql_fetch_assoc($result);
	if ($row['Email']) {
      return $row['Email'];
	} else {
	  return DEFAULT_ADMIN_EMAIL;
	}
  } else {
    return DEFAULT_ADMIN_EMAIL;
  }
}

function getFranchisePrimaryContactEmail($franchise) {
  $result = mysql_query("select e.EmailAddress from franchise f left join email e on f.PrimaryContactEmailID=e.EmailID where f.FranchiseID=".$franchise);
  if (mysql_num_rows($result)>0) {
    $row = mysql_fetch_assoc($result);
	return ($row['EmailAddress']);
  } else {
    return DEFAULT_ADMIN_EMAIL;
  }
}

function getFranchiseMainPhoneNumber($franchise) {
  $result = mysql_query("select p.PhoneNumber from franchise f left join phone p on f.MainPhoneID=p.PhoneID where f.FranchiseID=".$franchise);
  if (mysql_num_rows($result)>0) {
    $row = mysql_fetch_assoc($result);
	return formatFranchisePhoneNumber($row['PhoneNumber']);
  } else {
    return '( 319 ) 365 - 1511';
  }
}


function formatFranchisePhoneNumber($input) {
  $input = preg_replace('/\.|\)|\(|\ /', '', $input);
  if (strlen($input)>1) {
	  $input = '('.substr($input, 0, 3) . ') ' . substr($input, 3, 3) . '-' . substr($input, 6,4);
  } else {
      $input = ' <font color="red">Club Has Not Provided a Main Phone Number</font>';
  }
  return $input;
}


function isAnnualFeeRequired($franchise_id) {
  $franchise_check = mysql_query("select AnnualFee from franchise where FranchiseID=".$franchise_id);
  if (mysql_num_rows($franchise_check)>0) {
    $franchise_row = mysql_fetch_assoc($franchise_check);
	if ($franchise_row['AnnualFee']>0) {
	  return true;
	} else {
	  return false;
	}
  } else {
    return false;
  }
}



?>
