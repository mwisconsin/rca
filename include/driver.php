<?php 

include_once 'include/database.php';
require_once('include/rc_log.php');

/**
 * Returns the Driver ID of the identified user.
 * @param user_id ID of user to get Driver ID for
 * @return Driver ID of $user_id or FALSE on error.
 */
function get_user_driver_id( $user_id ){
    rc_log(PEAR_LOG_NOTICE, __FUNCTION__ . ' called', 'rc_rider_driver.log');
    return $user_id;
}

function get_driver_user_id( $user_id ){
    rc_log(PEAR_LOG_NOTICE, __FUNCTION__ . ' called', 'rc_rider_driver.log');
    return $user_id;
}
 /**
 * Returns the Driver Status and Franchise ID of the identified user as a hash.  Keys to the hash:
 * DriverStatus, FranchiseID.
 * @param user_id ID of user to get info for
 * @return hash containing name fields or FALSE on error.
 *///TODO: rewrite function to use driverID
function get_user_driver_info( $user_id ){
    $safe_user_id = mysql_real_escape_string($user_id);

    $sql = "SELECT * FROM driver NATURAL JOIN users
            WHERE UserID = $safe_user_id";
    
	$result = mysql_query($sql);
    if ($result) {
        $result = mysql_fetch_array($result);
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error getting driver info for user $user_id", $sql);
        $result = FALSE;
    }

	return $result;
}

function get_driver_info( $user_id ){
    $safe_user_id = mysql_real_escape_string($user_id);

    $sql = "SELECT * FROM driver WHERE UserID = $safe_user_id";
    
	$result = mysql_query($sql);
    if ($result) {
        $result = mysql_fetch_array($result);
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error getting driver info for driver $user_id", $sql);
        $result = FALSE;
    }

	return $result;
}
/**
 * Returns the Driver Settings of the identified user as a hash.  Keys to the hash:
 * FelonRiderOK, StayWithRider, WillHelpWithPackage, WillHelpToCar, OtherNotes.
 * @param user_id ID of user to get Settings for
 * @return hash containing Driver Setting fields or FALSE on error.
 *///TODO: rewrite function to use driverID
function get_user_driver_settings( $user_id ){
    $safe_user_id = mysql_real_escape_string($user_id);
	$query = "SELECT * FROM `driver_settings` WHERE `UserID` = $safe_user_id";

    $result = mysql_query($query);
    if ($result) {
        return  mysql_fetch_array($result);
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error getting driver settings for driver $user_id", $sql);
        return FALSE; 
    }
}

/**
 * Returns the Driver Settings of the identified user as a hash.  Keys to the hash:
 * FelonRiderOK, StayWithRider, WillHelpWithPackage, WillHelpToCar, OtherNotes.
 * @param user_id ID of user to get Settings for
 * @return hash containing Driver Setting fields or FALSE on error.
 */
function get_driver_settings_by_driver_id( $user_id) {
    $safe_user_id = mysql_real_escape_string($user_id);
    $sql = "SELECT * from driver_settings WHERE UserID = $safe_user_id";

    $result = mysql_query($sql);

    if ($result) {
        $row = mysql_fetch_array($result, MYSQL_ASSOC);
        return $row;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error getting driver info for $user_id", $sql);
        return FALSE;
    }
}

/**
 * Returns the name of the user associated with the given driver ID.  First/Last order.
 * @param $user_id ID of driver
 * @return string containing user's (person) name or FALSE on error
 */
function get_driver_name($user_id) {
    $safe_user_id = mysql_real_escape_string($user_id);
    $sql = "SELECT Title, FirstName, MiddleInitial, LastName, Suffix
            FROM driver NATURAL JOIN users NATURAL JOIN person_name
            WHERE driver.UserID = $safe_user_id";

    $result = mysql_query($sql);
    if ($result) {
        if (mysql_num_rows($result) != 1) {
            rc_log(PEAR_LOG_ERR, "Multiple names returned for driver $user_id");
            return FALSE;
        }

        $row = mysql_fetch_array($result, MYSQL_ASSOC);
        return "{$row['Title']} {$row['FirstName']} {$row['MiddleInitial']}" .
                (($row['MiddleInitial'] == '') ? '' : '. ') . "{$row['LastName']} {$row['Suffix']}";
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error,
                        "Could not get name for driver $user_id", $sql);
        return FALSE;
    }
}

function get_driver_vehicles( $user_id ){
	$sql = "SELECT * FROM `vehicle_driver` WHERE `vehicle_driver`.`UserID` = '" . mysql_real_escape_string($user_id) . "';";
	$result = mysql_query($sql);
	
	if($result){
		if(mysql_num_rows($result) < 1)
			return FALSE;
		return $result;
	} else {
		echo "Could not get vehicles for driver $user_id: " . mysql_error();
		return FALSE;
	}
}
function get_driver_emergency_contact( $user_id ){
	$safe_user_id = mysql_real_escape_string($user_id);
	$sql = "SELECT `EmergencyContactID`, `EmergencyContactName`, `Address`, `Phone`,`Email`, `EmergencyContactRelationship` FROM `driver` NATURAL JOIN `emergency_contact` WHERE `UserID` = $safe_user_id LIMIT 1;";
	$result = mysql_query($sql);
	
	if($result){
		if(mysql_num_rows($result) < 1)
			return FALSE;
		return mysql_fetch_array($result);
	} else {
		echo "Could not get emergency contact information for UserID $user_id:" . mysql_error();
		return FALSE;
	}
}

function get_vehicle( $vehicle_id ){
	$sql = "SELECT * FROM `vehicle` WHERE `vehicle`.`VehicleID` = '" . mysql_real_escape_string($vehicle_id) . "' LIMIT 1;";
	$result = mysql_query($sql);
	
	if($result){
		if(mysql_num_rows($result) != 1)
			return FALSE;
		return mysql_fetch_array($result, MYSQL_ASSOC);
	} else {
		echo "Could not get vehicle $vehicle_id: " . mysql_error();
		return FALSE;
	}
}

function delete_driver( $user_id ){
	$safe_user_id = mysql_real_escape_string( $user_id );
	
    //Deleting Drivers Availability
    $sql = "DELETE FROM `driver_availability` WHERE `UserID` = $safe_user_id";
    $result = mysql_query($sql);

    if(!$result){
        rc_log_db_error(PEAR_LOG_ERR, mysql_error,
                        "Could not delete driver availability $user_id", $sql);
        return FALSE;
    }//Deleting Drivers Availability History
    $sql = "DELETE FROM `driver_availability_history` WHERE `UserID` = $safe_user_id";
    $result = mysql_query($sql);

    if(!$result){
        rc_log_db_error(PEAR_LOG_ERR, mysql_error,
                        "Could not delete driver availability history $user_id", $sql);
        return FALSE;
    }// Deleting Drivers Settings
    $sql = "DELETE FROM `driver_settings` WHERE `UserID` = $safe_user_id";
    $result = mysql_query($sql);

    if(!$result){
        rc_log_db_error(PEAR_LOG_ERR, mysql_error,
                        "Could not delete driver settings $user_id", $sql);
        return FALSE;
    }//Deleting Drivers Vacation Hours
    $sql = "DELETE FROM `driver_vacation` WHERE `UserID` = $safe_user_id";
    $result = mysql_query($sql);

    if(!$result){
        rc_log_db_error(PEAR_LOG_ERR, mysql_error,
                        "Could not delete driver vacation $user_id", $sql);
        return FALSE;
    }
    //delete vehciles
    delete_all_driver_vehcile($user_id);
    //Deleting Emergency Contact
    $emergency_contact = get_driver_emergency_contact($user_id);
    //Deleting Driver
    $sql = "DELETE FROM `driver` WHERE `UserID` = $safe_user_id";
    $result = mysql_query($sql);

    if(!$result){
        rc_log_db_error(PEAR_LOG_ERR, mysql_error,
                        "Could not delete driver $user_id", $sql);
        return FALSE;
    }
    delete_emergency_contact( $emergency_contact['EmergencyContactID'] );
		
    return TRUE;
}
function delete_driver_vehicle( $vehicle_id ){
	$safe_vehicle_id = mysql_real_escape_string( $vehicle_id );
	$sql = "DELETE FROM `vehicle_driver` WHERE `VehicleID` = '$safe_vehicle_id'";
	$result = mysql_query($sql);
	
	if(!$result){
		rc_log_db_error(PEAR_LOG_ERR, mysql_error,
	                    "Could not delete vehicle driver for vehicle $vehicle_id", $sql);
	    return FALSE;
	}
	$sql = "DELETE FROM `vehicle` WHERE `VehicleID` = '$safe_vehicle_id'";
	$result = mysql_query($sql);
	
	if(!$result){
		rc_log_db_error(PEAR_LOG_ERR, mysql_error,
	                    "Could not delete vehicle $vehicle_id", $sql);
	    return FALSE;
	}
	return TRUE;
}
function delete_all_driver_vehcile( $user_id ){
	$vehciles = get_driver_vehicles( $user_id );
	$safe_user_id = mysql_real_escape_string( $user_id );
	
	$sql = "DELETE FROM `vehicle_driver` WHERE `UserID` = '$safe_vehicle_id'";
	$result = mysql_query($sql);
	
	if(!$result){
		rc_log_db_error(PEAR_LOG_ERR, mysql_error,
	                    "Could not delete vehicle $vehicle_id", $sql);
	    return FALSE;
	}
	if($vehciles != FALSE){
		while($row = mysql_fetch_array($vehciles)){
			delete_driver_vehicle( $row['VehicleID'] );
		}
	}
}


/**
 * Gets a driver's name and address from their driver ID.  Stores to a cache to 
 * reduce number of DB hits.  If the driver info may have changed, the cache 
 * may be cleared.
 * @param user_id of driver
 * @param clear_cache TRUE to clear the entire driver cache, FALSE otherwise.
 * @return FALSE on failure, or associative array with keys (UserID, UserID, 
 *                              Title, FirstName, MiddleInitial, LastName, Suffix, 
 *                              Address1, Address2, City, State, ZIP5, ZIP4)
 */
function get_driver_person_info( $user_id, $clear_cache = FALSE ) {
    static $cache = array();  // Key is driver ID, value is return array.

    if (!is_array($cache) || $clear_cache === TRUE) {
        $cache = array();
    }

    if (isset($cache[$user_id])) {
        return $cache[$user_id];
    }

    $safe_user_id = mysql_real_escape_string($user_id);

    $sql = "SELECT UserID, Title, FirstName, MiddleInitial, NickName, 
                   LastName, Suffix, Address1, Address2, City, State, ZIP5, ZIP4,
                   VehicleHeight, VehicleColor, VehicleDescription
            FROM users NATURAL JOIN person_name NATURAL JOIN vehicle_driver NATURAL JOIN vehicle
                 NATURAL JOIN user_address NATURAL JOIN address
            WHERE UserID = $user_id";
    // There could be multiple addresses (Physical/Mailing/Billing/Additional)
    // For now, just choose the first one.  TODO:  Which to select in the future?

    $result = mysql_query($sql);

    if ($result) {
        $row = mysql_fetch_array($result, MYSQL_ASSOC);

        $cache[$user_id] = $row;
        return $row;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error getting driver info for $user_id", $sql);
        return FALSE;
    }

    rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                    "No results returned for $user_id", $sql);
    return FALSE;
}


/**
 * Gets a list of drivers for the admin to select from
 */
function get_admin_work_as_driver_list( $franchise_id, $sort_order='L' ) {
    $driver_list = array(); 
    if ($franchise_id == 'ALLFRANCHISES') {
        $where_clause = "WHERE driver.DriverStatus = 'Active' and users.UserID=driver.UserID and person_name.PersonNameID=users.PersonNameID AND driver.UserID = user_role.UserID ";
    } else {
        $safe_franchise_id = mysql_real_escape_string($franchise_id);
       $where_clause = "WHERE driver.DriverStatus = 'Active' AND  user_role.FranchiseID = $safe_franchise_id AND driver.UserID = user_role.UserID and users.UserID=driver.UserID and person_name.PersonNameID=users.PersonNameID";
    }

    switch ($sort_order) {
        case 'F':
            $sort_clause = 'ORDER BY FirstName, MiddleInitial, LastName';
            break;
        case 'L':
        default:
            $sort_clause = 'ORDER BY LastName, FirstName, MiddleInitial';
    }

    $sql = "SELECT DISTINCT FirstName, MiddleInitial, LastName, driver.UserID, UserName, Status, ApplicationStatus 
            FROM driver, user_role, users, person_name
            $where_clause
            $sort_clause";
//echo $sql;
    $result = mysql_query($sql);

    if ($result) {
        while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
             $driver_list[] = $row;
        }
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error getting driver work list for $franchise", $sql);
    }

    return $driver_list;
}

function get_driver_insurance_info($user_id){
	$safe_user_id = mysql_real_escape_string($user_id);
	$sql = "SELECT * FROM driver_insurance NATURAL JOIN person_name, address, phone WHERE UserID = $safe_user_id AND person_name.PersonNameID = driver_insurance.AgentNameID AND AddressID = AgentAddressID AND PhoneID = AgentPhoneID LIMIT 1;";
	$result = mysql_query($sql);
	
	if($result){
		if(mysql_num_rows($result) < 1)
			return FALSE;
		return mysql_fetch_array($result);
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error getting driver insurance info for driver $user_id", $sql);
		return FALSE;
	}
}

function set_driver_insurance_info($user_id, $company_name, $policy_number, $per_person_liability, 
								   $per_accident_liability, $property_damage_liability, $combined_single_limit, $policy_expiration_date, $certificate_of_insurance, $copy_of_insurance_card, $verified_date = NULL){
	$safe_user_id = mysql_real_escape_string($user_id);
	$safe_company_name = mysql_real_escape_string($company_name);
	$safe_policy_number = mysql_real_escape_string($policy_number);
	$safe_per_person_liability = mysql_real_escape_string($per_person_liability);
	$safe_per_accident_liability = mysql_real_escape_string($per_accident_liability);
	$safe_property_damage_liability = mysql_real_escape_string($property_damage_liability);
	$safe_combined_single_limit = mysql_real_escape_string($combined_single_limit);
	$safe_policy_expiration_date = mysql_real_escape_string($policy_expiration_date);
	$safe_certificate_of_insurance = mysql_real_escape_string($certificate_of_insurance);
	$safe_copy_of_insurance_card = mysql_real_escape_string($copy_of_insurance_card);
	$safe_verified_date = $verified_date !== NULL ? ", `InsuranceVerified` = '" . mysql_real_escape_string($verified_date) . "'" : NULL;
	$sql = "UPDATE `driver_insurance` SET `CompanyName` = '$safe_company_name',
										  `PolicyNumber` = '$safe_policy_number',
										  `PerPersonLiability` = '$safe_per_person_liability',
										  `PerAccidentLiability` = '$safe_per_accident_liability',
										  `PropertyDamageLiability` = '$safe_property_damage_liability',
										  `CombinedSingleLimit` = '$safe_combined_single_limit',
									      `PolicyExpirationDate` = '$safe_policy_expiration_date',
										  `CertificateOfInsuranceOnFile` = '$safe_certificate_of_insurance',
										  `CopyOfInsuranceCardOnFile` = '$safe_copy_of_insurance_card'
										  $safe_verified_date 
									   WHERE `UserID` = $safe_user_id LIMIT 1 ;";
	$result = mysql_query($sql);
	
	if($result){
		return TRUE;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error setting driver insurance info for driver $user_id", $sql);
		return FALSE;
	}
}

function create_driver_insurance_info($user_id, $company_name, $policy_number, $per_person_liability, 
								      $per_accident_liability, $property_damage_liability, $combined_single_limit, $policy_expiration_date, $agent_name, $agent_phone, $agent_address,$certificate_of_insurance, $copy_of_insurance_card, $verified_date = NULL){
	$safe_user_id = mysql_real_escape_string($user_id);
	$safe_company_name = mysql_real_escape_string($company_name);
	$safe_policy_number = mysql_real_escape_string($policy_number);
	$safe_per_person_liability = mysql_real_escape_string($per_person_liability);
	$safe_per_accident_liability = mysql_real_escape_string($per_accident_liability);
	$safe_property_damage_liability = mysql_real_escape_string($property_damage_liability);
	$safe_combined_single_limit = mysql_real_escape_string($combined_single_limit);
	$safe_policy_expiration_date = mysql_real_escape_string($policy_expiration_date);
	$safe_agent_name = mysql_real_escape_string($agent_name);
	$safe_agent_phone = mysql_real_escape_string($agent_phone);
	$safe_agent_address = mysql_real_escape_string($agent_address);
	$safe_certificate_of_insurance = mysql_real_escape_string($certificate_of_insurance);
	$safe_copy_of_insurance_card = mysql_real_escape_string($copy_of_insurance_card);
	$safe_verified_date = $verified_date !== NULL ? "'" . mysql_real_escape_string($verified_date) . "'" : "NULL";
	
	$sql = "INSERT INTO `driver_insurance` (`UserID`, `CompanyName`, `PolicyNumber`, `PerPersonLiability`, `PerAccidentLiability`,
											`PropertyDamageLiability`, `CombinedSingleLimit`, `PolicyExpirationDate`, `AgentNameID`, 
											`AgentPhoneID`, `AgentAddressID`, `CertificateOfInsuranceOnFile`, `CopyOfInsuranceCardOnFile`,
											`InsuranceVerified`) 
									VALUES ('$safe_user_id', '$safe_company_name', '$safe_policy_number', '$safe_per_person_liability', 
											'$safe_per_accident_liability', '$safe_property_damage_liability', '$safe_combined_single_limit', '$safe_policy_expiration_date', $safe_agent_name, $safe_agent_phone, $safe_agent_address, '$safe_certificate_of_insurance', '$safe_copy_of_insurance_card', $safe_verified_date);";
	$result = mysql_query($sql);
	
	if($result){
		return mysql_insert_id();
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error setting driver insurance info for driver $user_id", $sql);
		return FALSE;
	}
}
//SELECT LinkID,`DesiredArrivalTime`, FROM_UNIXTIME( UNIX_TIMESTAMP(`DesiredArrivalTime`) - (`EstimatedMinutes` + 10) * 60) AS "DesiredDepartureTime" FROM `link`

function find_available_best_fitting_drivers($link_id = array()){
	for($i =0; $i < count($link_id); $i++){
		$links_sql_string .= "LinkID = {$links_id[$i]}";
		if($i != count($ride_ids) - 1)
			$links_sql_string .= ' OR ';
	}
	$sql = "SELECT *
FROM driver, driver_availability
WHERE UNIX_TIMESTAMP( driver_availability.StartTime ) <= UNIX_TIMESTAMP( 
	DATE_FORMAT((

SELECT FROM_UNIXTIME( UNIX_TIMESTAMP( `DesiredArrivalTime` ) - ( `EstimatedMinutes` +10 ) *60 )/* driver is available at pickup */
FROM link
WHERE LinkID =2
OR LinkID =3
ORDER BY `DesiredArrivalTime`
LIMIT 1 ), '%H:%i')
)
AND UNIX_TIMESTAMP( driver_availability.EndTime ) >= UNIX_TIMESTAMP( DATE_FORMAT((SELECT `DesiredArrivalTime` /* driver is available till drop off */
FROM link
WHERE LinkID =2
OR LinkID =3
ORDER BY `DesiredArrivalTime` DESC
LIMIT 1 ), '%H:%i')
)
AND UNIX_TIMESTAMP( driver_availability.TimeValid ) <= UNIX_TIMESTAMP( )/* driver available time is up to date */
AND UNIX_TIMESTAMP( driver_availability.TimeInvalid ) >= UNIX_TIMESTAMP( ) /* driver available time is up to date */
AND driver_availability.DayOfWeek = DATE_FORMAT( ( /* day driver is available */

SELECT DesiredArrivalTime
FROM link
WHERE LinkID =2
OR LinkID =3
LIMIT 1
), '%W' )";
}

function get_drivers_total_driven_miles_to_date( $user_id, $date = NULL ){
	if($date === NULL)
		$date = date("Y-n-j");
    $safe_user_id = mysql_real_escape_string($user_id);
	$safe_date = mysql_real_escape_string($user_id);
	$date = "AND DesiredArrivalTime < '$date'";
    $sql = "SELECT SUM(Distance) FROM (( SELECT  Distance FROM link_history WHERE DriverUserID =$safe_user_id AND (LinkStatus = 'COMPLETE' OR LinkStatus = 'CANCELEDLATE') $date) UNION ( SELECT deadhead_history.Distance FROM deadhead_history LEFT JOIN link_history ON deadhead_history.NextLinkID = link_history.LinkID WHERE 1 $date )) t1";
    $result = mysql_query($sql) or die(mysql_error());
    
    if($result){
        $miles = mysql_fetch_array($result);
        return $miles[0];
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error getting driver total miles for driver $user_id", $sql);
        return FALSE;
    }
}

function get_driver_total_driven_miles_for_month_to_date($user_id, $date = NULL){
	if($date === NULL)
		$date = date("Y-n-j G:i:s");
    $safe_user_id = mysql_real_escape_string($user_id);
	$safe_date = mysql_real_escape_string($user_id);
	$date2 = date("Y-n-1");
	$date = "AND DesiredArrivalTime < '$date' AND DesiredArrivalTime >= '$date2' ";
    $sql = "SELECT SUM(Distance) FROM (( SELECT  Distance FROM link_history WHERE DriverUserID =$safe_user_id AND (LinkStatus = 'COMPLETE' OR LinkStatus = 'CANCELEDLATE') $date) UNION ( SELECT deadhead_history.Distance FROM deadhead_history LEFT JOIN link_history ON deadhead_history.NextLinkID = link_history.LinkID WHERE 1 $date )) t1";
    $result = mysql_query($sql) or die(mysql_error());
    
    if($result){
        $miles = mysql_fetch_array($result);
        return $miles[0];
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error getting driver total miles for driver $user_id", $sql);
        return FALSE;
    }
}

function get_new_drivers($start_date = '', $end_date = ''){
    $start = ($start_date != '') ? " AND UNIX_TIMESTAMP(`ApplicationDate`) >= UNIX_TIMESTAMP('" . mysql_real_escape_string($start_date) . "')" : NULL;
    $end = ($end_date != '') ? " AND UNIX_TIMESTAMP(`ApplicationDate`) <= UNIX_TIMESTAMP('" . mysql_real_escape_string($end_date) . "')" : NULL;
    $sql = "SELECT * FROM users LEFT JOIN user_role ON users.UserID = user_role.UserID 
            WHERE Role = 'Driver' AND Status = 'ACTIVE' $start$end";
    $result = mysql_query($sql);
    
    if($result){
        $drivers;
        while($row = mysql_fetch_array($result))
            $drivers[] = $row;
        return count($drivers) > 0 ? $drivers : FALSE;
        
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                "Could not get new drivers for $start_date - $end_date", $sql);
        return FALSE;
    }
}
function get_working_drivers($start_date = '', $end_date = ''){
    $start = ($start_date != '') ? " AND UNIX_TIMESTAMP(`DesiredArrivalTime`) >= UNIX_TIMESTAMP('" . mysql_real_escape_string($start_date) . "')" : NULL;
    $end = ($end_date != '') ? " AND UNIX_TIMESTAMP(`DesiredArrivalTime`) <= UNIX_TIMESTAMP('" . mysql_real_escape_string($end_date) . "')" : NULL;
    $sql = "SELECT * FROM (link_history LEFT JOIN users ON link_history.DriverUserID = users.UserID) WHERE `LinkStatus` = 'COMPLETE'$start$end GROUP BY link_history.DriverUserID";
    $result = mysql_query($sql) or die(mysql_error());
    if($result){
        $drivers;
        while($row = mysql_fetch_array($result))
            $drivers[] = $row;
        return count($drivers) > 0 ? $drivers : FALSE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                "Could not get working drivers for $start_date - $end_date", $sql);
        return FALSE;
    }
}

function set_driver_max_hours($user_id, $hour_input, $time_selection, $day_input, $day_selection){
    $safe_user_id = mysql_real_escape_string($user_id);
    $safe_hour_input = mysql_real_escape_string($hour_input);
    $safe_time_selection = mysql_real_escape_string($time_selection);
    $safe_day_input = mysql_real_escape_string($day_input);
    $safe_day_selection = mysql_real_escape_string($day_selection);
    
    $sql = "UPDATE `driver_settings` SET `HoursPerTime` = '$safe_hour_input', `HoursTimeUnit` = '$safe_time_selection', `DaysPerTime` = '$day_input', `DaysTimeUnit` = '$safe_day_selection', AvailabilityLastUpdate = NOW() WHERE `UserID` = $safe_user_id LIMIT 1;";
    $result = mysql_query($sql);
    
    if($result){
        return true;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not set drivers max hours for driver: $user_id", $sql);
        return false;
    }
}

function get_number_of_working_drivers($franchise, $start_date = '', $end_date = ''){
	$safe_franchise = mysql_real_escape_string($franchise);
    $start = ($start_date != '') ? " AND UNIX_TIMESTAMP(`DesiredArrivalTime`) >= UNIX_TIMESTAMP('" . mysql_real_escape_string($start_date) . "')" : NULL;
    $end = ($end_date != '') ? " AND UNIX_TIMESTAMP(`DesiredArrivalTime`) <= UNIX_TIMESTAMP('" . mysql_real_escape_string($end_date) . "')" : NULL;
	$franchise_W = " AND FranchiseID = $safe_franchise";
	
	
	$sql = "SELECT ";
	$sql .= "( SELECT COUNT(*) FROM (SELECT * FROM `link` WHERE AssignedDriverUserID IS NOT NULL$franchise_W$start$end GROUP BY `AssignedDriverUserID`) t1 ) AS \"SCHEDULED\",";
	$sql .= "(SELECT COUNT(*) FROM (SELECT * FROM `link_history` WHERE DriverUserID IS NOT NULL AND LinkStatus = 'COMPLETE'$franchise_W$start$end GROUP BY `DriverUserID`) t1) AS \"COMPLETE\",";
	$sql .= "(SELECT COUNT(*) FROM (SELECT * FROM `link_history` WHERE DriverUserID IS NOT NULL AND LinkStatus = 'DRIVERNOSHOW'$franchise_W$start$end GROUP BY `DriverUserID`) t1) AS \"DRIVERNOSHOW\",";
	$sql .= "(SELECT COUNT(*) FROM (SELECT * FROM `link_history` WHERE DriverUserID IS NOT NULL AND LinkStatus = 'CANCELEDLATE'$franchise_W$start$end GROUP BY `DriverUserID`) t1) AS \"CANCELEDLATE\",";
	$sql .= "(SELECT COUNT(*) FROM (SELECT * FROM `link_history` WHERE DriverUserID IS NOT NULL AND LinkStatus = 'CANCELEDEARLY'$franchise_W$start$end GROUP BY `DriverUserID`) t1) AS \"CANCELEDEARLY\",";
	$sql .= "(SELECT COUNT(*) FROM (SELECT * FROM `link_history` WHERE DriverUserID IS NOT NULL AND LinkStatus = 'NOTSCHEDULED'$franchise_W$start$end GROUP BY `DriverUserID`) t1) AS \"NOTSCHEDULED\",";
	$sql .= "(SELECT COUNT(*) FROM (SELECT * FROM (SELECT AssignedDriverUserID DriverUserID, DesiredArrivalTime, FranchiseID FROM link UNION SELECT DriverUserID, DesiredArrivalTime, FranchiseID FROM link_history) t2 WHERE DriverUserID IS NOT NULL$franchise_W$start$end GROUP BY `DriverUserID`) t1) AS \"Total\"";
	
	$result = mysql_query($sql);
	
	if($result){
		return mysql_fetch_array($result);
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                "Could not get riding riders for $start_date - $end_date", $sql);
        return FALSE;
	}
}
function  get_number_of_new_drivers($franchise, $start_date = '', $end_date = ''){
	$safe_franchise = mysql_real_escape_string($franchise);
	$start = ($start_date != '') ? " AND UNIX_TIMESTAMP(`DesiredArrivalTime`) >= UNIX_TIMESTAMP('" . mysql_real_escape_string($start_date) . "')" : NULL;
    $end = ($end_date != '') ? " AND UNIX_TIMESTAMP(`DesiredArrivalTime`) <= UNIX_TIMESTAMP('" . mysql_real_escape_string($end_date) . "')" : NULL;
	$having_operator = " HAVING UNIX_TIMESTAMP(FirstDrive) >= UNIX_TIMESTAMP('$start_date') AND UNIX_TIMESTAMP(FirstDrive) <= UNIX_TIMESTAMP('$end_date')";
	
	$franchise_W = " AND FranchiseID = $safe_franchise";
	
	$sql = "SELECT ";
	$sql .= "(SELECT COUNT(*) FROM (SELECT *,(SELECT DesiredArrivalTime FROM (SELECT DesiredArrivalTime, AssignedDriverUserID DriverUserID FROM link UNION SELECT DesiredArrivalTime, DriverUserID FROM link_history ORDER BY DesiredArrivalTime) t4 WHERE DriverUserID = t2.AssignedDriverUserID LIMIT 1) FirstDrive FROM `link` t2 WHERE AssignedDriverUserID IS NOT NULL$franchise_W$start$end GROUP BY `AssignedDriverUserID`$having_operator ) t1) AS \"SCHEDULED\",";
	$sql .= "(SELECT COUNT(*) FROM (SELECT *,(SELECT DesiredArrivalTime FROM (SELECT DesiredArrivalTime, AssignedDriverUserID DriverUserID FROM link UNION SELECT DesiredArrivalTime, DriverUserID FROM link_history ORDER BY DesiredArrivalTime) t4 WHERE DriverUserID IS NOT NULL AND DriverUserID = t2.DriverUserID LIMIT 1) FirstDrive FROM `link_history` t2 WHERE LinkStatus = 'COMPLETE'$franchise_W$start$end GROUP BY `DriverUserID` $having_operator  ) t1) AS \"COMPLETE\",";
	$sql .= "(SELECT COUNT(*) FROM (SELECT *,(SELECT DesiredArrivalTime FROM (SELECT DesiredArrivalTime, AssignedDriverUserID DriverUserID FROM link UNION SELECT DesiredArrivalTime, DriverUserID FROM link_history ORDER BY DesiredArrivalTime) t4 WHERE DriverUserID = t2.DriverUserID LIMIT 1) FirstDrive FROM `link_history` t2 WHERE DriverUserID IS NOT NULL AND LinkStatus = 'DRIVERNOSHOW'$franchise_W$start$end GROUP BY `DriverUserID` $having_operator  ) t1) AS \"DRIVERNOSHOW\",";
	$sql .= "(SELECT COUNT(*) FROM (SELECT *,(SELECT DesiredArrivalTime FROM (SELECT DesiredArrivalTime, AssignedDriverUserID DriverUserID FROM link UNION SELECT DesiredArrivalTime, DriverUserID FROM link_history ORDER BY DesiredArrivalTime) t4 WHERE DriverUserID = t2.DriverUserID LIMIT 1) FirstDrive FROM `link_history` t2 WHERE DriverUserID IS NOT NULL AND LinkStatus = 'CANCELEDLATE'$franchise_W$start$end GROUP BY `DriverUserID` $having_operator  ) t1) AS \"CANCELEDLATE\",";
	$sql .= "(SELECT COUNT(*) FROM (SELECT *,(SELECT DesiredArrivalTime FROM (SELECT DesiredArrivalTime, AssignedDriverUserID DriverUserID FROM link UNION SELECT DesiredArrivalTime, DriverUserID FROM link_history ORDER BY DesiredArrivalTime) t4 WHERE DriverUserID = t2.DriverUserID LIMIT 1) FirstDrive FROM `link_history` t2 WHERE DriverUserID IS NOT NULL AND LinkStatus = 'CANCELEDEARLY'$franchise_W$start$end GROUP BY `DriverUserID` $having_operator  ) t1) AS \"CANCELEDEARLY\",";
	$sql .= "(SELECT COUNT(*) FROM (SELECT *,(SELECT DesiredArrivalTime FROM (SELECT DesiredArrivalTime, AssignedDriverUserID DriverUserID FROM link UNION SELECT DesiredArrivalTime, DriverUserID FROM link_history ORDER BY DesiredArrivalTime) t4 WHERE DriverUserID = t2.DriverUserID LIMIT 1) FirstDrive FROM `link_history` t2 WHERE DriverUserID IS NOT NULL AND LinkStatus = 'NOTSCHEDULED'$franchise_W$start$end GROUP BY `DriverUserID` $having_operator  ) t1) AS \"NOTSCHEDULED\",";
	$sql .= "(SELECT COUNT(*) FROM (SELECT *,(SELECT DesiredArrivalTime FROM (SELECT DesiredArrivalTime, AssignedDriverUserID FROM link UNION SELECT DesiredArrivalTime, DriverUserID FROM link_history ORDER BY DesiredArrivalTime) t4 WHERE DriverUserID = t2.DriverUserID LIMIT 1) FirstDrive FROM ( SELECT FranchiseID, AssignedDriverUserID DriverUserID, DesiredArrivalTime FROM link UNION SELECT FranchiseID, DriverUserID, DesiredArrivalTime FROM link_history) t2 WHERE DriverUserID IS NOT NULL$franchise_W$start$end GROUP BY `DriverUserID` $having_operator  ) t1) AS \"Total\"";
	
	$result = mysql_query($sql) or die(mysql_error());
	
	if($result){
		return mysql_fetch_array($result);
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                "Could not get riding riders for $start_date - $end_date", $sql);
        return FALSE;
	}
}

function get_user_driver_next_drives($user_id){
	unset($_SESSION['DriverNextDrives']);
	if(isset($_SESSION['DriverNextDrives']))
		return $_SESSION['DriverNextDrives'];
	$safe_user_id = mysql_real_escape_string($user_id);
	$sql = "SELECT * FROM link WHERE AssignedDriverUserID =$safe_user_id AND DesiredArrivalTime > NOW() and (CustomTransitionType = 'DRIVER' or CustomTransitionType is null) ORDER BY DesiredArrivalTime LIMIT 1 ;";
	//echo $sql."<BR>";
	$next = mysql_fetch_array(mysql_query($sql));
	$sql = "SELECT Count(*) FROM link WHERE AssignedDriverUserID =$safe_user_id  and (CustomTransitionType = 'DRIVER' or CustomTransitionType is null) "
		."and date_format(date('{$next[DesiredArrivalTime]}'),'%d-%m-%Y') = date_format(date(DesiredArrivalTime),'%d-%m-%Y')";
	//echo $sql."<BR>";
	$scheduled = mysql_fetch_array(mysql_query($sql));
	$_SESSION['DriverNextDrives'] = array('NumberOfDrives' => $scheduled[0] . " Destinations", 'NextDrive' => $next['LinkID']);
	return $_SESSION['DriverNextDrives'];
}

function get_user_driver_attened_miles_YTD($user_id, $date = NULL, $to_date = true){
	if($date === NULL)
		$date = date("Y-n-j");
    $safe_user_id = mysql_real_escape_string($user_id);
	$safe_date = mysql_real_escape_string($date);
	$date2 = date("Y-01-01");
	if($to_date)
		$strdate = " AND EffectiveDate >= '$date2 ' AND EffectiveDate <= '$safe_date 23:59:59'";
	else
		$strdate = " AND EffectiveDate > '$safe_date'";
    $sql = "SELECT SUM(Distance) AS Distance, SUM(ledger.Cents) AS Cents FROM (`completed_link_ledger_xref` LEFT JOIN ledger ON completed_link_ledger_xref.`LedgerEntryID` = ledger.`LedgerEntryID`) LEFT JOIN link_history ON completed_link_ledger_xref.LinkID = link_history.LinkID WHERE `EntityRole` = 'DRIVER' AND (LinkStatus = 'COMPLETE' OR linkStatus = 'CANCELEDLATE') AND Cents > 0 AND (CustomTransitionType != 'RIDER' OR CustomTransitionType IS NULL)   AND DriverUserID = $safe_user_id AND ledger.EntityID = $safe_user_id $strdate";
    $result = mysql_query($sql) or die(2 . mysql_error());
    
    if($result){
        return mysql_fetch_array($result);
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error getting driver YTD attended miles for driver $user_id", $sql);
        return FALSE;
    }
}

function get_user_driver_unattened_miles_YTD($user_id, $date = NULL, $to_date = true){
	if($date === NULL)
		$date = date("Y-n-j");
    $safe_user_id = mysql_real_escape_string($user_id);
	$safe_date = mysql_real_escape_string($date);
	$date2 = date("Y-01-01");
	if($to_date)
		$strdate = " AND EffectiveDate >= '$date2 ' AND EffectiveDate <= '$safe_date 23:59:59'";
	else
		$strdate = " AND EffectiveDate > '$safe_date'";
    $sql = "SELECT SUM( deadhead_history.Distance) AS Distance, SUM(ledger.Cents) AS Cents FROM (deadhead_history LEFT JOIN link_history ON deadhead_history.NextLinkID = link_history.LinkID) LEFT JOIN ledger ON deadhead_history.LedgerEntryID = ledger.LedgerEntryID WHERE deadhead_history.UserID =  $safe_user_id $strdate ";
    $result = mysql_query($sql);
    
    if($result){
    	$row = mysql_fetch_array($result);
        return $row;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error getting driver total miles for driver $user_id", $sql);
        return FALSE;
    }
}

function get_drivers_for_payout_reports($franchise, $month = NULL, $year = NULL){
	$safe_month = ($month !== NULL) ? mysql_real_escape_string($month) : 1;
	$safe_franchise = mysql_real_escape_string($franchise);
	if($year !== NULL && $month !== NULL)
		$safe_year = "AND DesiredArrivalTime >= '" . mysql_real_escape_string($year) . "-" . $safe_month . "-1' AND DesiredArrivalTime < '" . mysql_real_escape_string($year) . "-" . ($safe_month + 1) . "-1'";
	else if($year !== NULL)
		$safe_year = "AND DesiredArrivalTime >= '" . mysql_real_escape_string($year) . "-1-1' AND DesiredArrivalTime < '" . mysql_real_escape_string($year + 1) . "-1-1'";
	$sql = "SELECT UserID, FirstName, MiddleInitial, LastName, (SELECT Count(*) FROM ((SELECT DriverUserID AS UserID, link_history.LinkID as LinkID, Distance, DesiredArrivalTime FROM (`completed_link_ledger_xref` LEFT JOIN ledger ON completed_link_ledger_xref.`LedgerEntryID` = ledger.`LedgerEntryID`) LEFT JOIN link_history ON completed_link_ledger_xref.LinkID = link_history.LinkID WHERE `EntityRole` = 'DRIVER' AND (LinkStatus = 'COMPLETE' OR linkStatus = 'CANCELEDLATE') AND Cents > 0 AND (CustomTransitionType != 'RIDER' OR CustomTransitionType IS NULL)) UNION (SELECT UserID, DeadheadLinkID as LinkID, deadhead_history.Distance, DesiredArrivalTime FROM deadhead_history LEFT JOIN link_history ON deadhead_history.NextLinkID = link_history.LinkID AND (LinkStatus = 'COMPLETE' OR linkStatus = 'CANCELEDLATE')) )t2 WHERE UserID = t1.UserID $safe_year) rides FROM driver t1 NATURAL JOIN users NATURAL JOIN  person_name WHERE users.UserID IN(SELECT UserID FROM user_role WHERE FranchiseID = $safe_franchise) HAVING rides > 0 ORDER BY rides DESC";
	$result = mysql_query($sql);
		
	if($result){
		$drivers = array();
		while($row = mysql_fetch_array($result))
			$drivers[] = $row;
		return $drivers;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error getting drivers for payout reports", $sql);
        return FALSE;
	}
}

function get_driver_with_rider_data($franchise, $driver_id, $month = NULL, $year = NULL){
	$safe_franchise = mysql_real_escape_string($franchise);
	$safe_driver = mysql_real_escape_string($driver_id);
	$safe_month = ($month !== NULL) ? mysql_real_escape_string($month) : 1;
	if($year !== NULL && $month !== NULL)
		$safe_year = "AND EffectiveDate >= '" . mysql_real_escape_string($year) . "-" . $safe_month . "-1' AND EffectiveDate < '" . mysql_real_escape_string($year) . "-" . ($safe_month + 1) . "-1'";
	else if($year !== NULL)
		$safe_year = "AND EffectiveDate >= '" . mysql_real_escape_string($year) . "-1-1' AND EffectiveDate < '" . mysql_real_escape_string($year + 1) . "-1-1'";

	$sql = "SELECT SUM(Cents) sumCents, SUM(Distance) sumDistance, COUNT(*) numRides, SUM(EstimatedMinutes) sumMinutes FROM (`completed_link_ledger_xref` LEFT JOIN ledger ON completed_link_ledger_xref.`LedgerEntryID` = ledger.`LedgerEntryID`) LEFT JOIN link_history ON completed_link_ledger_xref.LinkID = link_history.LinkID WHERE link_history.FranchiseID = $safe_franchise AND `EntityRole` = 'DRIVER' AND (LinkStatus = 'COMPLETE' OR linkStatus = 'CANCELEDLATE') AND Cents > 0 AND (CustomTransitionType != 'RIDER' OR CustomTransitionType IS NULL)   AND DriverUserID = $safe_driver AND ledger.EntityID = $safe_driver $safe_year LIMIT 1";
	$result = mysql_query($sql);
	if($result){
		return mysql_fetch_array($result);
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error getting driver data for payout reports for driver $driver_id", $sql);
        return FALSE;
	}
}

function get_driver_without_rider_data($franchise, $driver_id, $month = NULL , $year = NULL){
	$safe_franchise = mysql_real_escape_string($franchise);
	$safe_driver = mysql_real_escape_string($driver_id);
	$safe_month = ($month !== NULL) ? mysql_real_escape_string($month) : 1;
	if($year !== NULL && $month !== NULL)
		$safe_year = "AND EffectiveDate >= '" . mysql_real_escape_string($year) . "-" . $safe_month . "-1' AND EffectiveDate < '" . mysql_real_escape_string($year) . "-" . ($safe_month + 1) . "-1'";
	else if($year !== NULL)
		$safe_year = "AND EffectiveDate >= '" . mysql_real_escape_string($year) . "-1-1' AND EffectiveDate < '" . mysql_real_escape_string($year + 1) . "-1-1'";

	$sql = "SELECT COUNT(*) numRides, 
			SUM(deadhead_history.QuotedCents) sumCents, 
			SUM(deadhead_history.Distance) sumDistance, 
			SUM(deadhead_history.EstimatedMinutes) sumMinutes 
		FROM (
			deadhead_history 
			LEFT JOIN link_history 
				ON deadhead_history.NextLinkID = link_history.LinkID
		) 
		LEFT JOIN ledger 
			ON deadhead_history.LedgerEntryID = ledger.LedgerEntryID 
		WHERE link_history.FranchiseID = $safe_franchise 
			AND deadhead_history.UserID = $safe_driver $safe_year LIMIT 1";
	$result = mysql_query($sql);
	if($result){
		return mysql_fetch_array($result);
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error getting driver data for payout reports for driver $driver_id", $sql);
        return FALSE;
	}
}


function driver_oncall_status($user_id) {
  $sql = "Select OnCall from driver_settings where UserID='".(int)$user_id."'";
  $result = mysql_query($sql);
  if (mysql_num_rows($result)>0) {
    $row = mysql_fetch_assoc($result);
	return ($row['OnCall']=='Yes') ? 'On Call' : 'No';
  } else {
    return 'No';
  }
}

function driver_toggle_oncall($user_id) {
  $sql = "Select OnCall from driver_settings where UserID='".(int)$user_id."'";
  $result = mysql_query($sql);
  if (mysql_num_rows($result)>0) {
    $row = mysql_fetch_assoc($result);
	if ($row['OnCall']=='Yes') {
	  mysql_query("update driver_settings set OnCall='No' where UserID='".(int)$user_id."'");
	} else {
	  mysql_query("update driver_settings set OnCall='Yes' where UserID='".(int)$user_id."'");
	}
  }
}

?>