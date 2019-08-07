<?php

require_once('include/database.php');
require_once 'include/user.php';
require_once('include/rc_log.php');
require_once 'include/emergency_contact.php';
require_once 'include/destinations.php';
require_once('include/ledger.php');
require_once('include/name.php');
require_once('include/date_time.php');

function add_rider($rider, $user_id)
{
    $safe_emergency_contact = (is_null($rider['EmergencyContactID'])) ? 'NULL' : 
                              mysql_real_escape_string($rider['EmergencyContactID']);
    $safe_emergency_relation = (is_null($rider['EmergencyContactRelationship'])) ? 'NULL' : 
                               mysql_real_escape_string($rider['EmergencyContactRelationship']);
    $safe_user_id = mysql_real_escape_string($user_id);

	$sql = "INSERT INTO `rider` (`UserID`, `RiderStatus`, `EmergencyContactID`, `EmergencyContactRelationship`,`ADAQualified`, `QualificationReason`, 
		`DateOfBirth`, `RiderWaiverReceived`, default_num_in_car)
	 VALUES ($safe_user_id, '" . mysql_real_escape_string($rider['RiderStatus']) . "', $safe_emergency_contact, '$safe_emergency_relation', '" . mysql_real_escape_string($rider['ADAQualified']) 
	 . "', '" . mysql_real_escape_string($rider['QualificationReason']) . "', '" . mysql_real_escape_string($rider['DateOfBirth']) 
	 . "', '" . mysql_real_escape_string($rider['RiderWaiverReceived'])."', $rider[default_num_in_car]);";
	$result = mysql_query($sql) or die(mysql_error());
	
	if ($result){
        $added = $user_id;
		
		// check franchinse for annual fee
		$franchise_check  = mysql_query("select * from franchise f, user_role ur where ur.UserID=".$user_id." and ur.FranchiseID=f.FranchiseID");
		if (mysql_num_rows($franchise_check)>0) {
		  $franchise_row = mysql_fetch_assoc($franchise_check);
		  if ($franchise_row['AnnualFee']==0) {
		    mysql_query("update rider set AnnualFeePaymentDate=now() where UserID=".$user_id);
		  }
		}
	} else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Could not add rider for user $user_id", $sql);
        $added = FALSE;
	}
	
	return $added;
}
/**
 * Returns the Rider info of the identified user as a hash.  Keys to the hash:
 * RiderStatus,FranchiseID, EmergencyContactID, EmergencyContactRelationship, QualificationReason.
 * @param user_id ID of user to get info for
 * @return hash containing infomation fields or FALSE on error.
 * @deprecated Use functions in rider.php instead
 */
function get_user_rider_info( $user_id ){
    $safe_user_id = mysql_real_escape_string($user_id);
	$sql = "SELECT * FROM rider NATURAL JOIN users
            WHERE UserID = $user_id";
	$result = mysql_query($sql);
	
    if ($result) {
        $rider_info = mysql_fetch_array($result);
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Could not get rider info for user $user_id", $sql);
        $rider_info = FALSE;
    }

    return $rider_info;
}
function get_rider_info( $rider_user_id ){
    rc_log(PEAR_LOG_NOTICE, __FUNCTION__ . ' called', 'rc_rider_driver.log');
    return get_user_rider_info( $rider_user_id );
}

function get_user_rider_id( $user_id ){
    rc_log(PEAR_LOG_NOTICE, __FUNCTION__ . ' called', 'rc_rider_driver.log');
    return $user_id;
}

function get_rider_user_id( $user_id ){
    rc_log(PEAR_LOG_NOTICE, __FUNCTION__ . ' called', 'rc_rider_driver.log');
    return $user_id;
}

function get_user_rider_preferences( $user_id ){
    $safe_user_id = mysql_real_escape_string($user_id);
    $sql = "SELECT * FROM rider_preferences WHERE UserID = $safe_user_id";
	$result = mysql_query($sql);
	
	if($result){
        $return = mysql_fetch_array($result);
	} else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), "Could not get rider preferences for $user_id", $sql);
		$return = FALSE;
	}
	return $return;
}
function get_rider_preferences( $rider_user_id ){
    rc_log(PEAR_LOG_NOTICE, __FUNCTION__ . ' called', 'rc_rider_driver.log');
    return get_user_rider_preferences($rider_user_id);
}

function get_rider_emergency_contact( $rider_user_id ){
    $safe_rider_uid = mysql_real_escape_string($rider_user_id);
	$sql = "SELECT `EmergencyContactID`, `EmergencyContactName`, `Address`, `Phone`,`Email`, `EmergencyContactRelationship` 
            FROM `rider` NATURAL JOIN `emergency_contact` WHERE `UserID` = $safe_rider_uid LIMIT 1;";
	$result = mysql_query($sql);
	
	if($result){
		if(mysql_num_rows($result) < 1)
			return FALSE;
		else
			return mysql_fetch_array($result);
	} else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), "Could not get emergency contact information for rider UID $rider_user_id", $sql);
		return FALSE;
	}
}

function get_rider_survey( $user_id ){
	$safe_user_id = mysql_real_escape_string($user_id);
	$sql = "SELECT * FROM `rider_survey` WHERE `UserID` = $safe_user_id LIMIT 1;";
	$result = mysql_query($sql);
	
	if($result){
		if(mysql_num_rows($result) < 1)
			return FALSE;
		return mysql_fetch_array($result);
	} else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), "Could not get rider survey for rider $user_id", $sql);
		return FALSE;
	}
	
}

/**
 * Retrieves the last date the rider has paid their annual fee.
 * @param rider_user_id User ID of rider to get information for
 * @return date as a hash with keys (Year, Month, Day), or FALSE on error or NULL date
 */
function get_rider_annual_fee_payment_date($rider_user_id) {
    $safe_rider_user_id = mysql_real_escape_string($rider_user_id);

    $sql = "SELECT YEAR(AnnualFeePaymentDate) AS Year,
                   MONTH(AnnualFeePaymentDate) AS Month,
                   DAY(AnnualFeePaymentDate) AS Day
            FROM rider WHERE UserID = $safe_rider_user_id";

    $result = mysql_query($sql);
    if ($result) {
        $row = mysql_fetch_array($result);
        if ( $row['Year'] === $row['Month'] && $row['Month'] === $row['Day'] &&
             $row['Day'] === NULL) {
            return FALSE;
        } else {
            return $row;
        }
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 'Error getting annual fee date', $sql);
        return FALSE;
    }
}

function rider_annual_fee_is_current($rider_user_id) {
    $safe_rider_user_id = mysql_real_escape_string($rider_user_id);

    $sql = "SELECT DATE_ADD(AnnualFeePaymentDate, INTERVAL 1 YEAR) >= NOW() AS UP_TO_DATE
            FROM rider WHERE UserID = $safe_rider_user_id";

    $result = mysql_query($sql);
    if ($result) {
        $row = mysql_fetch_array($result);
        return (bool)$row['UP_TO_DATE'];
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 'Error checking whether annual fee is up to date', $sql);
        return FALSE;
    }
}


/**
 * Sets the last date the rider has paid their annual fee.
 * @param rider_user_id User ID of rider to set information for
 * @param date Date to set annual fee date, in YYYY-MM-DD format.  NULL means the current date.
 * @return TRUE on success, FALSE on error
 */
function set_rider_annual_fee_payment_date($rider_user_id, $date = NULL) {
    $safe_rider_user_id = mysql_real_escape_string($rider_user_id);

    $extant_date_array = get_rider_annual_fee_payment_date($rider_user_id);
    if (!is_null($extant_date_array) && is_array($extant_date_array)) {
        $extant_date = "{$extant_date_array['Year']}-{$extant_date_array['Month']}-{$extant_date_array['Day']}";
    }
    $safe_date = mysql_real_escape_string(calculate_new_annual_fee_payment_date($date, $extant_date));

    $sql = "UPDATE rider SET AnnualFeePaymentDate = '$safe_date'
            WHERE UserID = $safe_rider_user_id";

    $result = mysql_query($sql);
    if ($result) {
        rc_log(PEAR_LOG_INFO, "Set the annual fee date for rider $rider_user_id to $safe_date");
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 'Error setting annual fee date', $sql);
    }

    return $result;
}


/**
 * Gets a list of riders for the admin to select from
 */
function get_admin_work_as_rider_list( $franchise_id, $sort_order = 'L') {
    $rider_list = array(); 
    if ($franchise_id == 'ALLFRANCHISES') {
        $where_clause = "WHERE rider.RiderStatus = 'Active'";
    } else {
        $safe_franchise_id = mysql_real_escape_string($franchise_id);
        $where_clause = "WHERE rider.RiderStatus = 'Active' AND rider.UserID IN ( SELECT UserID FROM user_role WHERE FranchiseID = $safe_franchise_id)";
    }

    switch ($sort_order) {
        case 'F':
            $sort_clause = 'ORDER BY FirstName, MiddleInitial, LastName';
            break;
        case 'L':
        default:
            $sort_clause = 'ORDER BY LastName, FirstName, MiddleInitial';
    }


    $sql = "SELECT FirstName, MiddleInitial, LastName, UserID, UserName, Status, ApplicationStatus
            FROM rider NATURAL JOIN users NATURAL JOIN person_name
            $where_clause
            $sort_clause";

    $result = mysql_query($sql);

    if ($result) {
        while ($row = mysql_fetch_array($result)) {
             $rider_list[] = $row;
        }
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error getting rider work list for $franchise", $sql);
    }

    return $rider_list;
}

/*
*		get_rider_next_ride
*		Based on $rider_id, find the next ride, based on now (or by optional supplied time)
*		Returns an object of LinkID and Departure Time.
*/

function get_rider_next_ride( $rider_id, $now = 0, $CTI = 0 ) {
	if($now == 0) $now = time();
	
	/* this query is meaningless for Care Facility Users */
	$sql = "select * from care_facility_user where UserID = $rider_id";
	$r = mysql_query($sql);
	if(mysql_num_rows($r) > 0) return array();
	
	$cti_rs = array();
	if($CTI > 0) {
		$sql = "select max(LinkID) as `LinkID` from link where CustomTransitionID = $CTI and RiderUserID = $rider_id";
		#echo "<BR>$sql<BR>";
		$r = mysql_query($sql);
		$cti_rs = mysql_fetch_array($r);
	}
	
	$sql = "select LinkID, min(subtime(DesiredArrivalTime,concat('0 0:',EstimatedMinutes+PrePadding+PostPadding,':0.000000'))) as `Departure Time`
					from link where RiderUserID = $rider_id
					and subtime(DesiredArrivalTime,concat('0 0:',EstimatedMinutes+PrePadding+PostPadding,':0.000000')) >= '".date('c',$now)."'";
	if($CTI > 0) $sql .= " and LinkID > $cti_rs[LinkID]";
	#echo "<BR>".$sql."<BR>";
	$r = mysql_query($sql);
	
	if(mysql_num_rows($r) > 0) {
		$rs = mysql_fetch_array($r);
		$link_info = $rs;
		if($CTI > 0) $link_info['CTI_LinkID'] = $cti_rs['LinkID'];
	} else $link_info = array();
	
	return $link_info;
}

/*
*		clone_rider
*		Based on $currentid, create a new rider with all the same information, but where the admin has
*		specified a new username, email address, and qualifications
*/
function clone_rider( $currentid, $username, $email, $qualifications ) {
	global $db_connection_link;
	
	$sql = "insert into email (EmailAddress,IsVerified) values ('$email','Yes')";
	#echo $sql."\n";
	$r = mysql_query($sql);
	$emailid = mysql_insert_id($db_connection_link);
	
	$sql = "insert into person_name (Title, Firstname, MiddleInitial, LastName, Suffix)
		select Title, Firstname, MiddleInitial, case when LOCATE(' (',LastName) then SUBSTRING(LastName,1,LOCATE(' (',LastName)) else LastName end, Suffix
		from users, person_name where users.PersonNameID = person_name.PersonNameID and UserId = $currentid";
	#echo $sql."\n";
	$r = mysql_query($sql);
	if(!$r) echo mysql_error();
	$personnameid = mysql_insert_id($db_connection_link);	
	
	$sql = "insert into users (UserName, `Password`, `Salt`, `Status`, EmailID, `PersonNameID`, `DefaultFranchiseID`, `HasFelony`, `FelonyDescription`, `ApplicationStatus`, 
		`BackgroundCheck`, `ApplicationDate`, `ApprovalDate`, `Texting`, `OldID`)
	SELECT '$username', `Password`, `Salt`, `Status`, $emailid, $personnameid, `DefaultFranchiseID`, `HasFelony`, `FelonyDescription`, `ApplicationStatus`, 
		`BackgroundCheck`, `ApplicationDate`, `ApprovalDate`, `Texting`, `OldID` FROM `users` WHERE UserID = $currentid";
	#echo $sql."\n";
	$r = mysql_query($sql);
	if(!$r) echo mysql_error();
	$newuserid = mysql_insert_id($db_connection_link);	
	
	$sql = "insert into user_role (UserID, Role, FranchiseID) 
		select $newuserid, 'Rider', FranchiseID from user_role where UserID = $currentid limit 0,1";
	$r = mysql_query($sql);
	if(!$r) echo mysql_error();
	
	$r = mysql_query("select AddressID from user_address where UserID = $currentid");
	if(!$r) echo mysql_error();
	while($rs = mysql_fetch_assoc($r)) {
		$sql = "insert into address (`Address1`, `Address2`, `City`, `State`, `ZIP5`, `ZIP4`, `Latitude`, `Longitude`, `IsVerified`, `VerifySource`)
		SELECT `Address1`, `Address2`, `City`, `State`, `ZIP5`, `ZIP4`, `Latitude`, `Longitude`, `IsVerified`, `VerifySource` FROM `address` where AddressID = $rs[AddressID]";
		#echo $sql."\n";
		$ret = mysql_query($sql);
		if(!$ret) echo mysql_error();
		
		$newaddressid = mysql_insert_id($db_connection_link);
		$sql = "insert into user_address (UserID, AddressID, AddressType) select $newuserid, $newaddressid, AddressType from user_address where AddressID = $rs[AddressID]";
		# echo $sql."\n";
		$ret = mysql_query($sql);
		if(!$ret) echo mysql_error();
	}
	$r = mysql_query("select PhoneID from user_phone where UserID = $currentid");
	if(!$r) echo mysql_error();
	
	while($rs = mysql_fetch_assoc($r)) {
		$sql = "insert into phone (`PhoneType`, `PhoneNumber`, `canSMS`, `ProviderID`) select `PhoneType`, `PhoneNumber`, `canSMS`, `ProviderID` from phone where PhoneID = $rs[PhoneID]";
		#echo $sql."\n";
		$ret = mysql_query($sql);
		if(!$ret) echo mysql_error();
		
		$newphoneid = mysql_insert_id($db_connection_link);
		$sql = "insert into user_phone (UserID, PhoneID, IsPrimary) select $newuserid, $newphoneid, IsPrimary from user_phone where PhoneID = $rs[PhoneID]";
		#echo $sql."\n";
		$ret = mysql_query($sql);
		if(!$ret) echo mysql_error();
	}
	
	$sql = "insert into `rider` (UserID, `RiderStatus`, `EmergencyContactID`, `EmergencyContactRelationship`, 
		`EmergencyContactEmailedDate`, `EmergencyContactConfirmDate`, QualificationReason, 
		`ADAQualified`, `AnnualFeePaymentDate`, `DateOfBirth`, 
		`WelcomePackageSentDate`, `FirstRideDate`, `FirstRideFollowupDate`, 
		`RiderWaiverReceived`, `RiderPictureWaiver`)
	SELECT $newuserid, `RiderStatus`, `EmergencyContactID`, `EmergencyContactRelationship`, 
		`EmergencyContactEmailedDate`, `EmergencyContactConfirmDate`, '".mysql_real_escape_string($qualifications)."', 
		`ADAQualified`, `AnnualFeePaymentDate`, `DateOfBirth`, 
		`WelcomePackageSentDate`, `FirstRideDate`, `FirstRideFollowupDate`, 
		`RiderWaiverReceived`, `RiderPictureWaiver` FROM `rider` WHERE UserID = $currentid";
	#echo $sql."\n";
	$r = mysql_query($sql);
	if(!$r) echo mysql_error();
		
	$sql = "insert into rider_preferences (UserID, `HighVehicleOK`, `MediumVehicleOK`, `LowVehicleOK`, `FelonDriverOK`, `DriverStays`, 
			`HasWalker`, `HasWheelchair`, `HasCane`, `NeedsPackageHelp`, `NeedsHelpToCar`, `EnterDriverSide`, 
			`EnterPassengerSide`, `HasCaretaker`, `CaretakerID`, `CaretakerBirthday`, `CaretakerBackgroundCheck`, `SensitiveToSmells`, 
			`SmokerOrPerfumeUser`, `HasMemoryLoss`, `OtherNotes`)
		select $newuserid, `HighVehicleOK`, `MediumVehicleOK`, `LowVehicleOK`, `FelonDriverOK`, `DriverStays`, 
			`HasWalker`, `HasWheelchair`, `HasCane`, `NeedsPackageHelp`, `NeedsHelpToCar`, `EnterDriverSide`, 
			`EnterPassengerSide`, `HasCaretaker`, `CaretakerID`, `CaretakerBirthday`, `CaretakerBackgroundCheck`, `SensitiveToSmells`, 
			`SmokerOrPerfumeUser`, `HasMemoryLoss`, `OtherNotes` FROM `rider_preferences` where UserID = $currentid";
	# echo $sql."\n";
	$r = mysql_query($sql);
	if(!$r) echo mysql_error();
	
	$sql = "insert into rider_survey (UserID, `MaritalStatus`, `LivingSituation`, `Housing`, `DriveOwnCar`, `CityBus`, `Taxi`, `Walk`, `FamilyOrFriend`, 
		`OtherTransport`, `RotaryMember`, `KiwanisMember`, `LionsMember`, `ElksMember`, `EaglesMember`, `AAAMember`, `AARPMember`, 
		`FratSororityMember`, `KofCMember`, `MasonsMember`, `OtherMembership`, `OthersDriveAlways`, `OthersDriveAtNight`, `OthersDriveHighTraffic`, 
		`OthersDriveUnfamiliar`, `OthersDriveHighway`, `OthersDriveBadWeather`)
	SELECT $newuserid, `MaritalStatus`, `LivingSituation`, `Housing`, `DriveOwnCar`, `CityBus`, `Taxi`, `Walk`, `FamilyOrFriend`, 
		`OtherTransport`, `RotaryMember`, `KiwanisMember`, `LionsMember`, `ElksMember`, `EaglesMember`, `AAAMember`, `AARPMember`, 
		`FratSororityMember`, `KofCMember`, `MasonsMember`, `OtherMembership`, `OthersDriveAlways`, `OthersDriveAtNight`, `OthersDriveHighTraffic`, 
		`OthersDriveUnfamiliar`, `OthersDriveHighway`, `OthersDriveBadWeather` FROM `rider_survey` WHERE UserID = $currentid";
	# echo $sql."\n";
	$r = mysql_query($sql);
	if(!$r) echo mysql_error();
	
	$homeid = get_rider_default_home_destination( $currentid );
	$sql = "select AddressID, PhoneID from destination where DestinationID = $homeid";
	$rs = mysql_fetch_assoc(mysql_query($sql));
	$addressid = $rs["AddressID"];
	$phoneid = $rs["PhoneID"];
	$sql = "insert into address (Address1, Address2, City, State, ZIP5, ZIP4, Latitude, Longitude, IsVerified, VerifySource)
		select Address1, Address2, City, State, ZIP5, ZIP4, Latitude, Longitude, IsVerified, VerifySource from address where AddressID = $addressid";
	# echo $sql."\n";
	$r = mysql_query($sql);
	if(!$r) echo mysql_error();
	
	$newaddressid = mysql_insert_id($db_connection_link);
	$sql = "insert into phone (PhoneType, PhoneNumber, canSMS, ProviderID, Ext)
		select PhoneType, PhoneNumber, canSMS, ProviderID, Ext from phone where PhoneID = $phoneid";
	# echo $sql."\n";
	$r = mysql_query($sql);
	if(!$r) echo mysql_error();
	
	$newphoneid = mysql_insert_id($db_connection_link);
	$sql = "insert into destination (DestinationGroupID, Name, AddressID, IsPublic, IsPublicApproved, FranchiseID, PhoneID, DestinationDetail)
		select DestinationGroupID, Name, $newaddressid, IsPublic, IsPublicApproved, FranchiseID, $newphoneid, DestinationDetail
		from destination where DestinationID = $homeid";
	# echo $sql."\n";
	$r = mysql_query($sql);
	if(!$r) echo mysql_error();
	
	$newhomeid = mysql_insert_id($db_connection_link);
	
	$sql = "insert into rider_destination (UserID, DestinationID)
		select $newuserid, DestinationID from rider_destination where UserID = $currentid and not DestinationID = $homeid";
	$r = mysql_query($sql);
	if(!$r) echo mysql_error();

	$sql = "insert into rider_destination (UserID, DestinationID) values ($newuserid, $newhomeid)";
	$r = mysql_query($sql);
	if(!$r) echo mysql_error();
		
	return $newuserid;
}

function delete_rider( $rider_user_id ){
	$safe_rider_user_id = mysql_real_escape_string( $rider_user_id );
	
    $prefs = get_rider_preferences( $rider_user_id );
    $sql = "DELETE FROM `rider_preferences` WHERE `UserID` = $safe_rider_user_id";
    $result = mysql_query($sql);

    if(!$result){
        rc_log_db_error(PEAR_LOG_ERR, mysql_error,
                        "Could not delete rider preferences $rider_user_id", $sql);
        return FALSE;
    }
    delete_name($prefs['CaretakerID']);
    $sql = "DELETE FROM `rider_survey` WHERE `UserID` = $safe_rider_user_id";
    $result = mysql_query($sql);

    if(!$result){
        rc_log_db_error(PEAR_LOG_ERR, mysql_error,
                        "Could not delete rider survey $rider_user_id", $sql);
        return FALSE;
    }
    $sql = "DELETE FROM `link` WHERE `RiderUserID` = $safe_rider_user_id";
    $result = mysql_query($sql);

    if(!$result){
        rc_log_db_error(PEAR_LOG_ERR, mysql_error,
                        "Could not delete rider links $rider_user_id", $sql);
        return FALSE;
    }
    $sql = "DELETE FROM `link_history` WHERE `RiderUserID` = $safe_rider_user_id";
    $result = mysql_query($sql);

    if(!$result){
        rc_log_db_error(PEAR_LOG_ERR, mysql_error,
                        "Could not delete rider history links $rider_user_id", $sql);
        return FALSE;
    }//delete destinations
    $destinations = get_rider_destinations( $rider_user_id );
    
    $sql = "DELETE FROM `rider_destination` WHERE `UserID` = $safe_rider_user_id";
    $result = mysql_query($sql);

    if(!$result){
        rc_log_db_error(PEAR_LOG_ERR, mysql_error,
                        "Could not delete rider destinations for rider $rider_user_id", $sql);
        return FALSE;
    }
    
    for($i = count($destinations) - 1; $i >= 0; $i--){
        if($destinations[$i]['IsPublic'] == 'No'){
            delete_destination($destinations[$i]['DestinationID']);
        }
    }

    $emergency_contact = get_rider_emergency_contact($rider_user_id);
    $sql = "DELETE FROM `rider` WHERE `UserID` = $safe_rider_user_id";
    $result = mysql_query($sql);

    if(!$result){
        rc_log_db_error(PEAR_LOG_ERR, mysql_error,
                        "Could not delete rider $rider_user_id", $sql);
        return FALSE;
    }
    delete_emergency_contact($emergency_contact['EmergencyContactID']);

    return TRUE;
}

/**
 * Gets a rider's name and address from their user ID.  Stores to a cache to 
 * reduce number of DB hits.  If the rider info may have changed, the cache 
 * may be cleared.
 * @param user_id ID of rider
 * @param clear_cache TRUE to clear the entire rider cache, FALSE otherwise.
 * @return FALSE on failure, or associative array with keys (UserID, UserID, 
 *                              Title, FirstName, MiddleInitial, LastName, Suffix, 
 *                              Address1, Address2, City, State, ZIP5, ZIP4)
 */
function get_rider_person_info($user_id, $clear_cache = FALSE) {
    // TODO:  Move this to a general user func
    static $cache = array();  // Key is rider ID, value is return array.

    if (!is_array($cache) || $clear_cache === TRUE) {
        $cache = array();
    }

    if (isset($cache[$user_id])) {
        return $cache[$user_id];
    }

    $safe_user_id = mysql_real_escape_string($user_id);

    $sql = "SELECT UserID, Title, FirstName, MiddleInitial, NickName,
                   LastName, Suffix, Address1, Address2, City, State, ZIP5, ZIP4
            FROM users NATURAL JOIN person_name 
                 NATURAL JOIN user_address NATURAL JOIN address
            WHERE UserID = $user_id";
    // There could be multiple addresses (Physical/Mailing/Billing/Additional)
    // For now, just choose the first one.  TODO:  Which to select in the future?

    $result = mysql_query($sql);

    if ($result) {
        $row = mysql_fetch_array($result);

        $cache[$user_id] = $row;
        return $row;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error getting person info for $user_id", $sql);
        return FALSE;
    }

    return FALSE;
}

/**
 * Gets a rider's preferences by User ID.  Stores to a cache to 
 * reduce number of DB hits.  If the rider info may have changed, the cache 
 * may be cleared.
 * @param rider_user_id ID of rider
 * @param clear_cache TRUE to clear the entire rider cache, FALSE otherwise.
 * @return FALSE on failure, or associative array with keys (UserID, 
                                HighVehicleOK, MediumVehicleOK, LowVehicleOK,
                                FelonDriverOK, DriverStays, HasWalker, HasWheelchair, HasCane,
                                NeedsPackageHelp, NeedsHelpToCar, EnterDriverSide,
                                EnterPassengerSide, HasCaretaker, CaretakerID,
                                OtherNotes)
 */
function get_rider_prefs($rider_user_id, $clear_cache = FALSE) {
    static $cache = array();  // Key is rider ID, value is return array.

    if (!is_array($cache) || $clear_cache === TRUE) {
        $cache = array();
    }

    if (isset($cache[$rider_user_id])) {
        return $cache[$rider_user_id];
    }

    $safe_rider_user_id = mysql_real_escape_string($rider_user_id);

    $sql = "SELECT * FROM rider_preferences
            WHERE UserID = $rider_user_id";

    $result = mysql_query($sql);

    if ($result) {
        $row = mysql_fetch_array($result);

        $cache[$rider_user_id] = $row;
        return $row;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error getting rider preferences for $rider_user_id", $sql);
        return FALSE;
    }

    return FALSE;
}

function rider_preferences_to_display_string($preferences_array) {
    //return (var_export($preferences_array, TRUE));

    // Vehicle:  {High/Medium/Low}VehicleOK
    //

    if (!is_array($preferences_array) || count($preferences_array) == 0) {
        return 'Preferences not set';
    }

    static $string_refs = array(
	// build conditional statement for HasMemoryLoss => if (ML1 then ML1 elseif ML2 then ML2)
		//if($preferences_array['HasMemoryLoss'] <> 'No'){$preferences_array['HasMemoryLoss']}
		//if('ML2'){'HasMemoryLoss' => array('ML2' => 'ML2')}else{if(ML1'){'HasMemoryLoss' => array('ML1' => 'ML1')}}
				'HasMemoryLoss' => array('ML1' => 'ML1', 'ML2' => 'ML2'),
        'HasCaretaker' => array('Yes' => 'CG'),
        'NeedsPackageHelp' => array('Yes' => 'PH'),
        'NeedsHelpToCar' => array('Yes' => 'CH'),
        'SensitiveToSmells' => array('Yes' => 'SS'),
        'SmokerOrPerfumeUser' => array('Yes' => 'PU'),
				'EnterDriverSide' => array('Yes' => 'DS'),
        'EnterPassengerSide' => array('Yes' => 'PS'),
        'DriverStays' => array('Yes' => '' /*'Prefers Driver to Stay' Removed at Martin's request Jun 1 2010*/),
    // Build conditional statement for HasWalker and HasWheelchair
        'HasWalker' => array('W1' => 'W1', 'W2' => 'W2'),  //ADD CONDITIONAL STATEMENT
        'HasCane' => array('Yes' => 'C'),
        'HasWheelchair' => array('WC1' => 'WC1', 'WC2' => 'WC2'),  //ADD CONDITIONAL STATEMENT
        'EnterBoth' => array('OVERRIDE' => ''), /*'Both Sides OK' UNNECESSARY*/
        'FelonDriverOK' => array('No' => 'NFD'),
    );

    $prefs_strings = array(); 

    if ($preferences_array['EnterDriverSide'] == 'Yes' &&
        $preferences_array['EnterPassengerSide'] == 'Yes') {
        // No reason to break both out individually
        unset($preferences_array['EnterDriverSide']);
        unset($preferences_array['EnterPassengerSide']);
        $preferences_array['EnterBoth'] = 'OVERRIDE';
    }
    
    if($preferences_array['HighVehicleOK'] == 'Yes') $string_refs['HighVehicleOK'] = array('YES' =>'HV');
    if($preferences_array['MediumVehicleOK'] == 'Yes') $string_refs['MediumVehicleOK'] = array('YES' => 'MV');
    if($preferences_array['LowVehicleOK'] == 'Yes') $string_refs['LowVehicleOK'] = array('YES' => 'LV');
    
    if($preferences_array['HighVehicleOK'] == 'Yes' && 
    	$preferences_array['MediumVehicleOK'] == 'Yes' && 
    	$preferences_array['LowVehicleOK'] == 'Yes') {
    		unset($string_refs['HighVehicleOK']);
    		unset($string_refs['MediumVehicleOK']);
    		unset($string_refs['LowVehicleOK']);
    	}
    	

    foreach ($preferences_array as $type => $pref) {
        $strings = $string_refs[$type];
        if(is_array($strings)) { 
        	foreach($strings as $k => $v) if ($strings[$k] != '' && $k == $pref)
	            $prefs_strings[] = $v;
	      } else if($strings[$pref] != '') $prefs_strings[] = $strings[$pref];
	            
    }
    return implode(', ', $prefs_strings); 

    //return "PREFERENCES TODO";
}

/*
	function rider_is_valid_for_ab_review
	
	Review various criteria to determine if rider deserves a popup reminding them
	that they're getting close to their threshold.

*/

function rider_is_valid_for_ab_review( $franchise_id, $user_id ) {
	// If assigned to a care facility, no need to review
	$sql = "select * from care_facility_user where UserID = $user_id and DisconnectDate is null";
	$r = mysql_query($sql);
	if(mysql_num_rows($r) > 0) return false;
	
	$sql = "select PaymentReminderTiming from franchise where FranchiseID = $franchise_id";
	$rs = mysql_fetch_array(mysql_query($sql));
	$PaymentReminderTiming = $rs["PaymentReminderTiming"];
	
	// recent requests for funds, within previous 4 hours
	$sql = "select * from users where UserId = $user_id and RequestChange between now() - interval $PaymentReminderTiming hour and now()";
	$r = mysql_query($sql);
	if(mysql_num_rows($r) > 0) return false;
	
	// recent permission to pull funds
	$sql = "select * from ach_to_process where userid = $user_id and dts between now() - interval $PaymentReminderTiming hour and now()";
	$r = mysql_query($sql);
	if(mysql_num_rows($r) > 0) return false;
	
	return true;
}

function get_rider_preference_key(){
    $html = "
<br>
<style>
#rpk { border: 1px solid black; border-collapse: collapse; }
#rpk td { padding: 2px; border: 1px solid black; }	
</style>
<table width=\"100%\" cellpadding=0 cellspacing=0 id=rpk>
<tr>
	<td>C1</td><td>Simple Cane</td>
	<td>HL1</td><td>Some Hearing Loss</td>
	<td>ML1</td><td>Slight Mem. Loss</td>
	<td>PH</td><td>Package Help</td>
	<td>SP</td><td>Small Pet</td>
	<td>W1</td><td>Flat Walker</td>
</tr>
<tr>
	<td>C2</td><td>Quad Cane</td>
	<td>HL2</td><td>Asst Devise</td>
	<td>ML2</td><td>Severe Mem. Loss</td>
	<td>PS</td><td>Pass. Side only</td>
	<td>SS</td><td>Smell Sensitivity</td>
	<td>W2</td><td>W w/ Handles</td>
</tr>
<tr>
	<td>CG</td><td>Caregiver*</td>
	<td>HL3</td><td>Little/ No Hearing</td>
	<td>MV</td><td>Medium Vehicle</td>
	<td>PU</td><td>Perfume User</td>
	<td>VL1</td><td>not 20/20</td>
	<td>WC1</td><td>Transfer Chair</td>
</tr>
<tr>
	<td>CH</td><td>Need Help to Car</td>
	<td>HV</td><td>High Vehicle</td>
	<td>NFD</td><td>No Felon Driver</td>
	<td>SA</td><td>Service Animal</td>
	<td>VL2</td><td>Blind</td>
	<td>WC2</td><td>Wheelchair (Stays)</td>
</tr>
<tr>
	<td>DS</td><td>Driver Side only</td>
	<td>LV</td><td>Low Vehicle</td>
</tr>
<table>";
    return $html;
}
function get_rider_person_info_string($rider_user_id, $link_to_rider = FALSE) {
    static $cache = array();  // Key is rider ID, value is return array.
    if (!is_array($cache)) {
        $cache = array();
    }

    if (isset($cache[$rider_user_id])) {
        return $cache[$rider_user_id];
    }

    $rider = get_rider_person_info($rider_user_id);
    if ($rider === FALSE) {
        $rider_string = "Unknown User ID {$rider_user_id}";
    } else {
        $rider_name = get_displayable_person_name_string($rider); 

        if ($link_to_rider) {
            $rider_string = "<a id=\"$rider_user_id\" class=\"User_Redirect\"href=\"account.php\">$rider_name</a><br />";
        } else {
            $rider_string = "$rider_name<br />";
        }

        if ($phones = get_user_phone_numbers($rider['UserID'])) {
            $rider_string .= '<span>';
            foreach ($phones as $phone) {
            	$rider_string .= ($phone['IsPrimary'] == 'Yes' ? '<b>' : ' ') 
            			. "{$phone['PhoneNumber']} ({$phone['PhoneType'][0]})" 
            			. ($phone['IsPrimary'] == 'Yes' ? '*</b>' : ' ') . "<br />";    
            	$rider_string .= $phone['phonedescription'] == '' ? '' : "<span class=manifest_phonedescription>$phone[phonedescription]</span><br>";
            } 

            $rider_string .= '</span>';
        }

        /*$rider_string .= '<span style="font-size: smaller;">' . 
                      "{$rider['Address1']}<br />" . 
                      (($rider['Address2']) ? $rider['Address2'] : '') . '<br />' .
                      "{$rider['City']}, {$rider['State']}  {$rider['ZIP5']}" . 
                      '</span>';*/
    }

    $cache[$rider_user_id] = $rider_string;
    return $rider_string;
}

function set_rider_recharge_thresholds( $rider_user_id, $recharge_threshold, $recharge_amount, $add_four_percent ) {
    $safe_RA = mysql_real_escape_string( $recharge_amount );
    $safe_TA = mysql_real_escape_string( $recharge_threshold );
    $safe_uid = mysql_real_escape_string( $rider_user_id );

    $sql = "UPDATE `users` SET `RechargeThreshold` = '$safe_TA',
                               `RechargeAmount` = '$safe_RA'
            WHERE `UserID` =$safe_uid";
    $result = mysql_query($sql);
    if(!$result){
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                "Could not update rider $rider_user_id recharge Threshold $safe_RA & recharge amount $safe_TA", $sql);
    }
}
function create_rider_default_home( $rider_user_id ){
	$safe_rider_user_id = mysql_real_escape_string($rider_user_id);
	$sql = "SELECT COUNT(*) FROM `destination` LEFT JOIN `rider_destination` ON destination.DestinationID = rider_destination.DestinationID WHERE rider_destination.UserID = $safe_rider_user_id AND destination.Name = 'Default Home'";
	$result = mysql_query($sql) or die(mysql_error());

	if($result){
		$rows = mysql_fetch_array($result);
		if($rows[0] < 1){
			$sql = "SELECT Address1, Address2, City, State, ZIP5 FROM (user_address LEFT JOIN address ON user_address.AddressID = address.AddressID) WHERE AddressType = 'Physical' AND UserID = $safe_rider_user_id LIMIT 1;";
			$result = mysql_query($sql) or die(mysql_error());
			if($result && mysql_num_rows($result) == 1){
				$address = mysql_fetch_array($result);
				$rider = get_user_rider_info($rider_user_id);
				$sql2 = "SELECT phone.PhoneNumber FROM (phone LEFT JOIN user_phone ON user_phone.PhoneID = phone.PhoneID) WHERE user_phone.UserID = $safe_rider_user_id LIMIT 1;";
				$result2 = mysql_query($sql2) or die(mysql_error());
				$phone = (mysql_num_rows($result2) >= 1) ? mysql_fetch_array($result2) : array('PhoneNumber' => NULL);
				
				if($facility = get_first_user_care_facility($rider_user_id))
					$facility = get_care_facility($facility);
				
				if($home = create_new_destination('Default Home', $address, get_current_user_franchise(), FALSE, NULL, NULL, $phone['PhoneNumber'], $facility['CareFacilityName'])){
					add_destination_for_rider($rider_user_id, $home);
				}
				
			}
		} return true;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                "Could not find default home for $rider_user_id", $sql);
	}
}

function set_riders_recharge_contact_preference($rider_user_id, $preference){
	$safe_rider_user_id = mysql_real_escape_string($rider_user_id);
	$safe_preference = $preference ? 'YES' : 'RechargeAutomatically';
	$sql = "UPDATE `users` SET `ContactBeforeRecharge` = '$safe_preference' WHERE `UserID` =$safe_rider_user_id LIMIT 1 ;";
	$result = mysql_query($sql);
	
	if($result){
		return true;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                "Could not set recharge contact preference for rider $rider_user_id", $sql);
	}
}

function check_rider_threshold_limits($rider_user_id){
	$rider = get_user_rider_info($rider_user_id);
	
	$balance = calculate_user_ledger_balance($rider_user_id);
	$ride_costs = calculate_riders_incomplete_ride_costs( $rider_user_id );
	
	$total_balance = $balance - $ride_costs;

	if( $total_balance < $rider['RechargeThreshold'] ){
		notify_admin_if_threshold_hit($rider_user_id);
	}
}

function notify_admin_if_threshold_hit($rider_user_id){
	$user = get_user_account($rider_user_id);
	$name = get_name($user['PersonNameID']);
	$message = "Dear Admin,\n\n UserID: {$user['UserID']}; {$name['FirstName']} {$name['LastName']} has reached their threshold limit. \n\nYours truly,\n The Riders Club Server";
	
	if(site_url() == 'http://www.myridersclub.com/')
		mail('admin@myridersclub.com','Riders Club of America - Rider Reached Threshold Limit',$message, DEFAULT_EMAIL_FROM);
}

function get_rider_report_numbers($franchise, $start_date = '', $end_date = ''){
	
	$safe_franchise = mysql_real_escape_string($franchise);
	$start = ($start_date != '') ? " AND UNIX_TIMESTAMP(`DesiredArrivalTime`) >= UNIX_TIMESTAMP('" . mysql_real_escape_string($start_date) . "')" : NULL;
	$end = ($end_date != '') ? " AND UNIX_TIMESTAMP(`DesiredArrivalTime`) <= UNIX_TIMESTAMP('" . mysql_real_escape_string($end_date) . "')" : NULL;
	$franchise_W = ' AND FranchiseID = ' . mysql_real_escape_string($franchise);
	$sql = "SELECT 
				(SELECT Count(*) FROM `link_history` WHERE `LinkStatus` = 'COMPLETE'$franchise_W $start$end) AS \"Completed\",
				(SELECT Count(*) FROM `link_history` WHERE `LinkStatus` = 'CANCELEDLATE$franchise_W '$start$end) AS \"LateCanceled\",
				(SELECT Count(*) FROM `link` Where 1$franchise_W $start$end) AS \"FutureLinks\",
				(SELECT AVG(QuotedCents) FROM link_history WHERE `LinkStatus` = 'COMPLETE'$franchise_W$start$end) AS \"AveragePrice\",
				(SELECT SUM(QuotedCents) FROM link_history WHERE `LinkStatus` = 'COMPLETE'$franchise_W$start$end) AS \"RideRevenue\",
				(SELECT COUNT(*) FROM (SELECT count(LinkID) from link_history WHERE$franchise_W `LinkStatus` = 'COMPLETE'$start$end GROUP BY DriverUserID) t1) AS \"Drivers\",
				(SELECT COUNT( DISTINCT RiderUserID ) FROM `link_history` WHERE `LinkStatus` = 'COMPLETE'$franchise_W$start$end) AS \"PayingRiders\",
				(SELECT COUNT(*) FROM users LEFT JOIN user_role ON users.UserID = user_role.UserID WHERE users.UserID IN (SELECT UserID FROM user_role WHERE FranchiseID = $safe_franchise) AND Role = 'Rider' AND Status = 'ACTIVE' AND UNIX_TIMESTAMP( ApplicationDate ) >= UNIX_TIMESTAMP( '$start_date' ) AND UNIX_TIMESTAMP( ApplicationDate ) <= UNIX_TIMESTAMP( '$end_date' )) AS \"NewRiders\",
				(SELECT COUNT(*) FROM users LEFT JOIN user_role ON users.UserID = user_role.UserID WHERE users.UserID IN (SELECT UserID FROM user_role WHERE FranchiseID = $safe_franchise) AND Role = 'Rider' AND Status = 'ACTIVE' AND UNIX_TIMESTAMP( ApplicationDate ) >= UNIX_TIMESTAMP( '$start_date' ) AND UNIX_TIMESTAMP( ApplicationDate ) <= UNIX_TIMESTAMP( '$end_date' )) AS \"NewDrivers\",
				(SELECT SUM(Distance) FROM link_history WHERE$franchise_W LinkStatus = 'COMPLETE'$start$end) AS \"RiderMiles\",
				(SELECT SUM(Distance) FROM deadhead_history WHERE$franchise_W LinkStatus = 'COMPLETE'$start$end) AS \"DriverMiles\"";
	$results = mysql_query( $sql );
	if($results){
		return mysql_fetch_array( $results );
	}
	
	
}
function set_rider_recharge_method($rider_user_id, $method, $ACHAccount = FALSE){
	$safe_rider_user_id = mysql_real_escape_string($rider_user_id);
	$safe_method = mysql_real_escape_string($method);
	$safe_account = $ACHAccount !== FALSE ? "'" . mysql_real_escape_string($ACHAccount) . "'" : 'NULL';
	
	$sql = "UPDATE `users` SET `RechargePaymentType` = '$safe_method', `AccountForACH` = $safe_account  WHERE `UserID` =$safe_rider_user_id LIMIT 1 ;";
	$result = mysql_query($sql);

	
	if($result){
		return TRUE;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                "Could not set recharge method for rider $rider_user_id, $method", $sql);
		return FALSE;
	}
}

function get_new_riders($franchise, $start_date = '', $end_date = ''){
    $start = ($start_date != '') ? " AND UNIX_TIMESTAMP(`ApplicationDate`) >= UNIX_TIMESTAMP('" . mysql_real_escape_string($start_date) . "')" : NULL;
    $end = ($end_date != '') ? " AND UNIX_TIMESTAMP(`ApplicationDate`) <= UNIX_TIMESTAMP('" . mysql_real_escape_string($end_date) . "')" : NULL;
    $sql = "SELECT * FROM users LEFT JOIN user_role ON users.UserID = user_role.UserID 
            WHERE Role = 'Rider' AND Status = 'ACTIVE' $start$end";
    $result = mysql_query($sql);
    
    if($result){
        $riders;
        while($row = mysql_fetch_array($result))
            $riders[] = $row;
        return count($riders) > 0 ? $riders : FALSE;
        
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                "Could not get new riders for $start_date - $end_date", $sql);
        return FALSE;
    }
}
function get_paying_riders($franchise, $start_date = '', $end_date = ''){
    $start = ($start_date != '') ? " AND UNIX_TIMESTAMP(`DesiredArrivalTime`) >= UNIX_TIMESTAMP('" . mysql_real_escape_string($start_date) . "')" : NULL;
    $end = ($end_date != '') ? " AND UNIX_TIMESTAMP(`DesiredArrivalTime`) <= UNIX_TIMESTAMP('" . mysql_real_escape_string($end_date) . "')" : NULL;
    $sql = "SELECT * FROM link_history LEFT JOIN users ON link_history.RiderUserID = users.UserID WHERE `LinkStatus` = 'COMPLETE'$start$end GROUP BY link_history.RiderUserID";
    $result = mysql_query($sql) or die(mysql_error());
    if($result){
        $riders;
        while($row = mysql_fetch_array($result))
            $riders[] = $row;
        return count($riders) > 0 ? $riders : FALSE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                "Could not get working riders for $start_date - $end_date", $sql);
        return FALSE;
    }
}

function get_number_of_paying_riders($franchise, $start_date = '', $end_date = ''){
	$safe_franchise = mysql_real_escape_string($franchise);
    $start = ($start_date != '') ? " AND UNIX_TIMESTAMP(`DesiredArrivalTime`) >= UNIX_TIMESTAMP('" . mysql_real_escape_string($start_date) . "')" : NULL;
    $end = ($end_date != '') ? " AND UNIX_TIMESTAMP(`DesiredArrivalTime`) <= UNIX_TIMESTAMP('" . mysql_real_escape_string($end_date) . "')" : NULL;
	$franchise_W = " AND FranchiseID = $safe_franchise";
	$sql = "SELECT ";
	$sql .= "( SELECT COUNT(*) FROM (SELECT * FROM `link` WHERE 1$franchise_W$start$end GROUP BY `RiderUserID`) t1 ) AS \"SCHEDULED\",";
	$sql .= "(SELECT COUNT(*) FROM (SELECT * FROM `link_history` WHERE LinkStatus = 'COMPLETE'$franchise_W$start$end GROUP BY `RiderUserID`) t1) AS \"COMPLETE\",";
	$sql .= "(SELECT COUNT(*) FROM (SELECT * FROM `link_history` WHERE LinkStatus = 'DRIVERNOSHOW'$franchise_W$start$end GROUP BY `RiderUserID`) t1) AS \"DRIVERNOSHOW\",";
	$sql .= "(SELECT COUNT(*) FROM (SELECT * FROM `link_history` WHERE LinkStatus = 'CANCELEDLATE'$franchise_W$start$end GROUP BY `RiderUserID`) t1) AS \"CANCELEDLATE\",";
	$sql .= "(SELECT COUNT(*) FROM (SELECT * FROM `link_history` WHERE LinkStatus = 'CANCELEDEARLY'$franchise_W$start$end GROUP BY `RiderUserID`) t1) AS \"CANCELEDEARLY\",";
	$sql .= "(SELECT COUNT(*) FROM (SELECT * FROM `link_history` WHERE LinkStatus = 'NOTSCHEDULED'$franchise_W$start$end GROUP BY `RiderUserID`) t1) AS \"NOTSCHEDULED\",";
	$sql .= "(SELECT COUNT(*) FROM (SELECT * FROM (SELECT RiderUserID, FranchiseID, DesiredArrivalTime FROM `link` UNION SELECT RiderUserID, FranchiseID, DesiredArrivalTime FROM `link_history`) t3  WHERE 1$franchise_W$start$end GROUP BY `RiderUserID`)  t1 )  AS \"Total\"";
	$result = mysql_query($sql);
	if($result){
		return mysql_fetch_array($result);
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                "Could not get riding riders for $start_date - $end_date", $sql);
        return FALSE;
	}
}

function get_number_of_new_riders($franchise, $start_date = '', $end_date = ''){
	$start = ($start_date != '') ? " AND UNIX_TIMESTAMP(`DesiredArrivalTime`) >= UNIX_TIMESTAMP('" . mysql_real_escape_string($start_date) . "')" : NULL;
    $end = ($end_date != '') ? " AND UNIX_TIMESTAMP(`DesiredArrivalTime`) <= UNIX_TIMESTAMP('" . mysql_real_escape_string($end_date) . "')" : NULL;
	$safe_franchise = mysql_real_escape_string($franchise);
	$first_ride = ",( SELECT DesiredArrivalTime FROM ( SELECT LinkID,DesiredArrivalTime,RiderUserID FROM link UNION SELECT LinkID,DesiredArrivalTime,RiderUserID FROM link_history) t9 WHERE RiderUserID = t1.RiderUserID ORDER BY DesiredArrivalTime LIMIT 1) FirstRide";
	$first_ride_having = ($start_date != '' && $end_date != '') ? " HAVING UNIX_TIMESTAMP(FirstRide) BETWEEN UNIX_TIMESTAMP('" . mysql_real_escape_string($start_date) . "')  AND UNIX_TIMESTAMP('" . mysql_real_escape_string($end_date) . "')" : NULL;
	$franchise_W = " AND FranchiseID = $safe_franchise";
	
	
	$sql = "SELECT ";
	$sql .= "(SELECT COUNT(*) FROM (SELECT *$first_ride FROM `link` t1 NATURAL JOIN rider WHERE RiderUserID = UserID$franchise_W$start$end GROUP BY RiderUserID$first_ride_having) t3 ) AS 'SCHEDULED',";
	$sql .= "(SELECT COUNT(*) FROM (SELECT *$first_ride FROM `link_history` t1 NATURAL JOIN rider WHERE RiderUserID = UserID AND LinkStatus = 'COMPLETE'$franchise_W$start$end GROUP BY RiderUserID$first_ride_having) t3 ) AS 'COMPLETE',";
	$sql .= "(SELECT COUNT(*) FROM (SELECT COUNT(*)$first_ride FROM `link_history` t1 NATURAL JOIN rider WHERE LinkStatus = 'DRIVERNOSHOW'$franchise_W$start$end GROUP BY RiderUserID$first_ride_having) t3 ) AS 'DRIVERNOSHOW',";
	$sql .= "(SELECT COUNT(*) FROM (SELECT *$first_ride FROM `link_history` t1 NATURAL JOIN rider WHERE RiderUserID = UserID AND LinkStatus = 'CANCELEDLATE$franchise_W'$start$end GROUP BY RiderUserID$first_ride_having) t3 ) AS 'CANCELEDLATE',";
	$sql .= "(SELECT COUNT(*) FROM (SELECT *$first_ride FROM `link_history` t1 NATURAL JOIN rider WHERE RiderUserID = UserID AND LinkStatus = 'CANCELEDEARLY'$franchise_W$start$end GROUP BY RiderUserID$first_ride_having) t3 ) AS 'CANCELEDEARLY',";
	$sql .= "(SELECT COUNT(*) FROM (SELECT *$first_ride FROM `link_history` t1 NATURAL JOIN rider WHERE RiderUserID = UserID AND LinkStatus = 'NOTSCHEDULED'$franchise_W$start$end GROUP BY RiderUserID$first_ride_having) t3 ) AS \"NOTSCHEDULED\",";
	$sql .= "(SELECT COUNT(*) FROM (SELECT *$first_ride FROM (SELECT RiderUserID, DesiredArrivalTime, FranchiseID FROM `link_history` UNION SELECT RiderUserID, DesiredArrivalTime, FranchiseID FROM `link`) t1 NATURAL JOIN rider WHERE RiderUserID = UserID$franchise_W$start$end GROUP BY RiderUserID$first_ride_having) t3) AS 'Total'";
	$result = mysql_query($sql);
	
	if($result){
		return mysql_fetch_array($result);
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                "Could not get new riding riders for $start_date - $end_date", $sql);
        return FALSE;
	}
}

function get_time_until_fee_due($user_id){
	$rider_info = get_user_rider_info($user_id);
	$seconds = (strtotime($rider_info['AnnualFeePaymentDate']) + ( 365 * 24 * 60 * 60)) - time();
	return $seconds;
}

function get_days_until_fee_due($user_id){
	$time_left = get_time_until_fee_due($user_id);
	if(!$time_left)
		return false;
	$days = round($time_left / (24 * 60  * 60));
	return $days;
}


function remove_rider_caretaker($user_id) {
    $safe_user_id = mysql_real_escape_string($user_id);

    // TODO:  Consider deleting the entry from person_name keyed by CaretakerID

	$sql = "UPDATE rider_preferences SET HasCaretaker = 'NO', CaretakerID = NULL WHERE UserID = $safe_user_id";

	$result = mysql_query($sql);
	
	if ($result) {
		return TRUE;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), "Could not remove caretaker for rider $user_id", $sql);
		return FALSE;
	}
}

function get_batch_riders_first_ride($riders = array()){
	if(!is_array($riders))
		return array();
		
	$sql = "SELECT UserID, (SELECT DesiredArrivalTime FROM link_history WHERE RiderUserID = users.UserID AND (CustomTransitionType = 'RIDER' || CustomTransitionType IS NULL) ORDER BY DesiredArrivalTime LIMIT 1) AS FirstRide FROM users WHERE UserID IN ( " . implode(", ", $riders) . " )";
	$result = mysql_query($sql);
	
	if($result){
		$rides = array();
		while($row = mysql_fetch_array($result))
			$rides[$row['UserID']] = $row['FirstRide'];
		return $rides;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), "could not get batch riders for first ride", $sql);
		return FALSE;
	}
}


function get_batch_riders_last_ride($riders = array()){
	if(!is_array($riders))
		return array();
		
	//$sql = "SELECT UserID, (SELECT DesiredArrivalTime FROM link_history " .
	//	   "WHERE RiderUserID = users.UserID AND (CustomTransitionType = 'RIDER' || CustomTransitionType IS NULL) " .
	//	   "AND LinkStatus IN ('CANCLEDLATE', 'COMPLETE')" .
	//	   "ORDER BY DesiredArrivalTime DESC LIMIT 1) AS FirstRide FROM users WHERE UserID IN ( " . implode(", ", $riders) . " )";
	$sql = "SELECT RiderUserId as UserID,  MAX(DesiredArrivalTime) AS FirstRide FROM 
            (SELECT RiderUserID, DesiredArrivalTime FROM link_history WHERE (CustomTransitionType = 'RIDER' || CustomTransitionType IS NULL) AND LinkStatus IN ('CANCLEDLATE', 'COMPLETE') AND RiderUserID IN (" . implode(", ", $riders) . " ) 
             UNION
             SELECT RiderUserID, DesiredArrivalTime FROM link WHERE DesiredArrivalTime < '" . date('Y-m-d H:i') . "' AND RiderUserID IN (" . implode(", ", $riders) . " ) ) as links
             GROUP BY RiderUserID";

	$result = mysql_query($sql);
	
	if($result){
		$rides = array();
		while($row = mysql_fetch_array($result))
			$rides[$row['UserID']] = $row['FirstRide'];
		return $rides;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), "could not get batch riders for first ride", $sql);
		return FALSE;
	}
}


function createRiderPrefs($user_id, $post_vars) {
         $caretaker_id = NULL;
			if($post_vars['HasCaretaker'] == 'Yes')
			{
				$query = "INSERT INTO `person_name` (`PersonNameID` ,`Title` ,`FirstName` ,`MiddleInitial` ,`LastName` ,`Suffix`)
				VALUES (NULL , '" . mysql_real_escape_string($post_vars['Title']) . "', '" . mysql_real_escape_string($post_vars['FirstName']) . "', '" . mysql_real_escape_string($post_vars['MiddleInitial']) . "', '" . mysql_real_escape_string($post_vars['LastName']) . "', '" . mysql_real_escape_string($post_vars['Suffix']) . "');"; 
				mysql_query($query) or die(mysql_error());
				$caretaker_id = mysql_insert_id();
				$caretaker_mysql = "'" . mysql_real_escape_string($caretaker_id) . "'";
			}
			else
				$caretaker_mysql = "NULL";
				
            $care_taker_birthdate = mysql_real_escape_string($post_vars['CaretakerBirthYear'] . "-" . $post_vars['CaretakerBirthMonth'] . "-" . $post_vars['CaretakerBirthDay']);
			$query = "INSERT INTO `rider_preferences` (`UserID`, `HasMemoryLoss`, `HasCaretaker`, 
				`NeedsPackageHelp`, `NeedsHelpToCar`, `SensitiveToSmells`, `SmokerOrPerfumeUser`, 
				`EnterDriverSide`, `EnterPassengerSide`, `HasWalker`, `HasCane`, `HasWheelchair`, 
				`FelonDriverOK`, `HighVehicleOK`, `MediumVehicleOK`, `LowVehicleOK`, `DriverStays`, 
				VisionLevel, HearingLevel,  HasServiceAnimal, HasSmallPetInCarrier,
				`CaretakerID`, `OtherNotes`, `CaretakerBirthday`, `CaretakerBackgroundCheck`)
			  VALUES ('" . mysql_real_escape_string($user_id) . "', 
				'" . mysql_real_escape_string(@$post_vars['HasMemoryLoss']) . "', 
				'" . mysql_real_escape_string(@$post_vars['HasCaretaker']) . "', 
				'" . mysql_real_escape_string(@$post_vars['NeedsPackageHelp']) . "', 
				'" . mysql_real_escape_string(@$post_vars['NeedsHelpToCar']) . "', 
				'" . mysql_real_escape_string(@$post_vars['SensitiveToSmells']) . "', 
				'" . mysql_real_escape_string(@$post_vars['SmokerOrPerfumeUser']) . "', 
				'" . mysql_real_escape_string(@$post_vars['EnterDriverSide']) . "', 
				'" . mysql_real_escape_string(@$post_vars['EnterPassengerSide']) . "', 
				'" . mysql_real_escape_string(@$post_vars['HasWalker']) . "', 
				'" . mysql_real_escape_string(@$post_vars['HasCane']) . "', 
				'" . mysql_real_escape_string(@$post_vars['HasWheelchair']) . "', 
				'" . mysql_real_escape_string(@$post_vars['FelonDriverOK']) . "',
				'" . mysql_real_escape_string(@$post_vars['HighVehicleOK']) . "', 
				'" . mysql_real_escape_string(@$post_vars['MediumVehicleOK']) . "', 
				'" . mysql_real_escape_string(@$post_vars['LowVehicleOK']) . "', 
				'" . mysql_real_escape_string(@$post_vars['DriverStays']) . "',
				'$post_vars[VisionLevel]',
				'$post_vars[HearingLevel]',
				'$post_vars[HasServiceAnimal]',
				'$post_vars[HasSmallPetInCarrier]', 
				" . $caretaker_mysql . ", 
				'" . mysql_real_escape_string($post_vars['OtherNotes']) . "', 
				'$care_taker_birthdate',
				'" . mysql_real_escape_string($post_vars['CaretakerBackgroundCheck']) . "');";
			mysql_query($query) or die(mysql_error());

							
}
?>
