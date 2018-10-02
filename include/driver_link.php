<?php

require_once('include/database.php');
require_once('include/rc_log.php');


/**
 * Finds possible drivers for a given link.  Drivers must match user preferences 
 * (e.g. felony).  Driver must have an associated vehicle that accommodates the 
 * rider (height, walker, etc).  Drivers and riders must be owned by the same 
 * franchise.
 * The following preferences are not deal-breakers.  Mismatches are left in:
 *   StayWithRider/DriverStays; WillHelpWithPackage/NeedsPackageHelp.
 * Driver availability is currently NOT taken into account.
 * @param $link_id Link (ride) to match
 * @return array of driver IDs
 */
function find_possible_drivers_for_link( $link_id ) {
    $driver_ids = array();
    // TODO:  At the moment, I only assume this works.  I haven't tested it.
    // TODO:  Drivers that aren't available need to be excluded.
    $safe_link_id = mysql_real_escape_string($link_id);

    // User DISTINCT because there may be multiple vehicles that qualify.
    $sql = "SELECT DISTINCT driver.UserID 
            FROM driver, driver_settings, link, rider_preferences, 
                 users AS rider_user, users AS driver_user, vehicle_driver,
                 vehicle
            WHERE link.LinkID = $safe_link_id AND
                  rider_preferences.UserID = link.RiderUserID AND
                  rider_user.UserID = link.RiderUserID AND 
                  driver_settings.UserID = driver.UserID AND
                  driver.FranchiseID = link.FranchiseID AND
                  driver_user.UserID = driver.UserID AND
                  ( driver_settings.FelonRiderOK = 'Yes' OR
                    rider_user.HasFelony = 'No') AND
                  ( rider_preferences.FelonDriverOK = 'Yes' OR
                    driver_user.HasFelony = 'No') AND
                  ( driver_settings.WillHelpToCar = 'Yes' OR
                    rider_preferences.NeedsHelpToCar = 'No' ) AND
                  vehicle_driver.UserID = driver.UserID AND
                  vehicle_driver.VehicleID = vehicle.VehicleID AND
                  ( ( vehicle.VehicleHeight = 'UNKNOWN' OR
                        (   vehicle.VehicleHeight = 'HIGH' AND
                            rider_preferences.HighVehicleOk = 'Yes') OR
                        (   vehicle.VehicleHeight = 'MEDIUM' AND
                            rider_preferences.MediumVehicleOk = 'Yes') OR
                        (   vehicle.VehicleHeight = 'LOW' AND
                            rider_preferences.LowVehicleOk = 'Yes')) AND
                    ( vehicle.CanHandleWalker = 'Yes' OR
                      rider_preferences.HasWalker = 'No') AND
                    ( vehicle.CanHandleCane = 'Yes' OR
                      rider_preferences.HasCane = 'No') AND
                    (   ( vehicle.HasDriverSideRearDoor = 'Yes' AND
                          rider_preferences.EnterDriverSide = 'Yes' ) OR
                        ( vehicle.HasPassengerSideRearDoor = 'Yes' AND
                          rider_preferences.EnterPassengerSide = 'Yes') )
                  )";

    $result = mysql_query($sql);
    if ($result) {
        $row = mysql_fetch_array($result);

        $driver_ids[] = $row['UserID'];
    } else {
        rc_log_db_error(PEAR_LOG_WARNING, mysql_error(),
                        "Error finding drivers for $link_id", $sql);
    }

    return $driver_ids;
}

/**
 * Sets the driver for a link.
 * @param $link_id Link to set driver for
 * @param $driver_user_id Driver to assign
 * @return TRUE if set, FALSE otherwise
 */
function set_driver_for_link( $link_id, $driver_user_id ) {
    $success = FALSE;
    // TODO:  pre-check?  Lock row and pre-check?
    // Traffic will probably be low enough that race conditions are unlikely.
    $safe_link_id = mysql_real_escape_string($link_id);
    $safe_driver_user_id = mysql_real_escape_string($driver_user_id);

    $sql = "UPDATE link SET AssignedDriverUserID = $safe_driver_user_id
            WHERE LinkID = $link_id";

    $result = mysql_query($sql);
    
    if ($result) {
        $success = TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_WARNING, mysql_error(),
                        "Could not set driver for $link_id to $driver_user_id", $sql);
    }

    return $success;
}

/**
 * Removes the driver for a link.
 * @param $link_id Link to clear driver for
 * @return TRUE if successful, FALSE otherwise.
 */
function remove_driver_for_link( $link_id ) {
    $success = FALSE;
    $safe_link_id = mysql_real_escape_string($link_id);

    $sql = "UPDATE link SET AssignedDriverUserID = NULL 
            WHERE LinkID = $link_id";

    $result = mysql_query($sql);
    
    if ($result) {
        $success = TRUE;
        // TODO:  Sensible log?
    } else {
        rc_log_db_error(PEAR_LOG_WARNING, mysql_error(),
                        "Could not clear driver for $link_id", $sql);
    }

    return $success;
}


/**
 * Gets a list of links that need a driver.
 * @param $franchise_id Franchise to search
 * @return array of hashes (keys from link table), FALSE on error
 */
function get_links_needing_driver( $franchise_id ) {
    $links = array();
    $safe_franchise_id = mysql_real_escape_string($franchise_id);

    $sql = "SELECT LinkID, RiderUserID, DesiredArrivalTime, EstimatedMinutes, Distance,
                   FromDestinationID, ToDestinationID, NumberOfRiders, LinkStatus,
                   F_Dest.Name AS F_Name, F_Dest.IsPublic AS F_Public,
                   F_Address.AddressID AS F_AddressID, F_Address.Address1 AS F_Address1, 
                   F_Address.Address2 AS F_Address2, F_Address.City AS F_City, 
                   F_Address.State AS F_State, F_Address.ZIP5 AS F_ZIP5, 
                   F_Address.ZIP4 AS F_ZIP4,
                   F_Phone.PhoneNumber AS F_PhoneNumber,
                   T_Dest.Name AS T_Name, T_Dest.IsPublic AS T_IsPublic,
                   T_Address.AddressID AS T_AddressID, T_Address.Address1 AS T_Address1, 
                   T_Address.Address2 AS T_Address2,
                   T_Address.City AS T_City, T_Address.State AS T_State, 
                   T_Address.ZIP5 AS T_ZIP5, T_Address.ZIP4 AS T_ZIP4,
                   T_Phone.PhoneNumber AS T_PhoneNumber
            FROM (link, destination AS F_Dest, destination AS T_Dest, 
                 address AS F_Address, address AS T_Address)
                 LEFT JOIN phone AS F_Phone ON F_Dest.PhoneID = F_Phone.PhoneID
                 LEFT JOIN phone AS T_Phone ON T_Dest.PhoneID = T_Phone.PhoneID
            WHERE link.AssignedDriverUserID IS NULL AND
                  link.FranchiseID = $safe_franchise_id AND
                  link.FromDestinationID = F_Dest.DestinationID AND
                  F_Dest.AddressID = F_Address.AddressID AND
                  link.ToDestinationID = T_Dest.DestinationID AND
                  T_Dest.AddressID = T_Address.AddressID
            ORDER BY DesiredArrivalTime ASC";

    $result = mysql_query($sql);
    if ($result) {
        while ($row = mysql_fetch_array($result)) {
            $links[] = $row;
        }

    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get driverless links for $franchise_id", $sql);
    }

    return $links;
}

function driver_link_franchise_where_clause($franchise_id, $table_name = 'link') {
    if ($franchise_id == 'ALLFRANCHISES') {
        $franchise_where_clause = '';
    } else {
        $safe_franchise_id = mysql_real_escape_string($franchise_id);
        $franchise_where_clause = "$table_name.FranchiseID = {$safe_franchise_id} AND";
    }

    return $franchise_where_clause;
}

function driver_link_date_where_clause($date) {
    if ( $date == 'ALLDATES' ) {
        $date_where_clause = ''; 
    } else {
        $safe_date = mysql_real_escape_string($date);
        #$date_where_clause = "DATE(DesiredArrivalTime) = '$safe_date' AND";
        /* Always include links for rides up to 5AM the following day */
        $date_where_clause = "DesiredArrivalTime between '$safe_date 00:00:00'
        	and '".date('Y-m-d H:i:s',strtotime('tomorrow 5am',strtotime($safe_date)))."' AND";
    }

    return $date_where_clause;
}

/**
 * Gets a list of links on a given date.
 * @param $franchise_id Franchise to search - 'ALLFRANCHISES' to search all franchises
 * @param $date Date to search on - as MYSQL string, or 'ALLDATES' for all dates
 * @return array of hashes (keys from link table), FALSE on error
 */
function get_schedule_driver_links_on_date( $franchise_id, $date ) {
    $links = array();
   
    $franchise_where_clause = driver_link_franchise_where_clause($franchise_id);
    $date_where_clause = driver_link_date_where_clause($date);

    // TODO:  INCLUDE links that already have drivers assigned?
    // If so, keep the AssignedDriverUserID bit commented
    $sql = "SELECT LinkID, RiderUserID, IF(DesiredArrivalTime IS NOT NULL, FROM_UNIXTIME(UNIX_TIMESTAMP(DesiredArrivalTime) - ((EstimatedMinutes + PrePadding + PostPadding) * 60)),DesiredDepartureTime) as DesiredDepartureTime, DesiredArrivalTime, link.FranchiseID, EstimatedMinutes, Distance, AssignedDriverUserID, PrePadding, PostPadding, PercentagePadding,
                   FromDestinationID, ToDestinationID, NumberOfRiders, LinkStatus, LinkNote, LinkFlexFlag, DepartureTimeConfimed, ArrivalTimeConfirmed, DriverConfirmed, CustomTransitionID,
                   F_Dest.Name AS F_Name, F_Dest.IsPublic AS F_Public,
                   F_Dest.DestinationDetail AS F_DestinationDetail,
                   F_Address.AddressID AS F_AddressID, F_Address.Address1 AS F_Address1, 
                   F_Address.Address2 AS F_Address2, F_Address.City AS F_City, 
                   F_Address.State AS F_State, F_Address.ZIP5 AS F_ZIP5, 
                   F_Address.ZIP4 AS F_ZIP4,
                   F_Phone.PhoneNumber AS F_PhoneNumber,
                   T_Dest.Name AS T_Name, T_Dest.IsPublic AS T_IsPublic,
                   T_Dest.DestinationDetail AS T_DestinationDetail,
                   T_Address.AddressID AS T_AddressID, T_Address.Address1 AS T_Address1, 
                   T_Address.Address2 AS T_Address2,
                   T_Address.City AS T_City, T_Address.State AS T_State, 
                   T_Address.ZIP5 AS T_ZIP5, T_Address.ZIP4 AS T_ZIP4,
                   T_Phone.PhoneNumber AS T_PhoneNumber,
                   Last_Changed_By, Last_Changed_Date, IndexPath
            FROM (link, destination AS F_Dest, destination AS T_Dest, 
                 address AS F_Address, address AS T_Address)
                 LEFT JOIN phone AS F_Phone ON F_Dest.PhoneID = F_Phone.PhoneID
                 LEFT JOIN phone AS T_Phone ON T_Dest.PhoneID = T_Phone.PhoneID
            WHERE -- link.AssignedDriverUserID IS NULL AND
                  $franchise_where_clause
                  $date_where_clause
                  (link.CustomTransitionType = 'DRIVER' OR 
                  link.CustomTransitionType IS NULL ) AND
                  link.FromDestinationID = F_Dest.DestinationID AND
                  F_Dest.AddressID = F_Address.AddressID AND
                  link.ToDestinationID = T_Dest.DestinationID AND
                  T_Dest.AddressID = T_Address.AddressID
            ORDER BY DATE_ADD(DesiredArrivalTime, INTERVAL -EstimatedMinutes MINUTE) ASC,
                     DesiredArrivalTime ASC, LinkID ASC";

    $result = mysql_query($sql);
    if ($result) {
        while ($row = mysql_fetch_array($result)) {
            $links[] = $row;
        }

    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get driverless links for $franchise_id", $sql);
    }

    return $links;
    


}
// TODO:  Will want to set vehicle for links if necessary.  Defer for now.


function get_next_link_id_needing_driver_on_date( $franchise_id, $date, $previous_link_id,
                                                  $no_driver_only = FALSE ) {
    $next = FALSE;

    $franchise_where_clause[0] = driver_link_franchise_where_clause($franchise_id, 'A');
	$franchise_where_clause[1] = driver_link_franchise_where_clause($franchise_id, 'B');

    $safe_date = mysql_real_escape_string($date);
    $date_where_clause = "DATE(B.DesiredArrivalTime) = '$safe_date' AND DATE(A.DesiredArrivalTime) = '$safe_date' AND ";

    $safe_prev_link = mysql_real_escape_string($previous_link_id);
    
    $driver_id_clause = ($no_driver_only) ? ' A.AssignedDriverUserID IS NULL AND ' : ' ';

    // TODO:  INCLUDE links that already have drivers assigned?
    // If so, keep the AssignedDriverUserID bit commented
    $sql = "SELECT A.LinkID
            FROM link AS A, link AS B
            WHERE $driver_id_clause
                  {$franchise_where_clause[0]}
				  {$franchise_where_clause[1]}
                  $date_where_clause
                  (DATE_ADD(A.DesiredArrivalTime, INTERVAL -A.EstimatedMinutes MINUTE) > 
                       DATE_ADD(B.DesiredArrivalTime, INTERVAL -B.EstimatedMinutes MINUTE)  
                   OR (
                   (DATE_ADD(A.DesiredArrivalTime, INTERVAL -A.EstimatedMinutes MINUTE) = 
                           DATE_ADD(B.DesiredArrivalTime, INTERVAL -B.EstimatedMinutes MINUTE) AND
                        ((A.DesiredArrivalTime > B.DesiredArrivalTime) OR
                         (A.DesiredArrivalTime = B.DesiredArrivalTime AND
                          A.LinkID > B.LinkID))))) AND
                  B.LinkID = $safe_prev_link
            ORDER BY DATE_ADD(A.DesiredArrivalTime, INTERVAL -A.EstimatedMinutes MINUTE) ASC,
                     A.DesiredArrivalTime ASC";

    $result = mysql_query($sql);
    if ($result) {
        if ($row = mysql_fetch_array($result)) {
            $next = $row['LinkID'];
        }

    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get next link for $franchise_id, $date, $previous_link_id", $sql);
    }

    return $next;
}

function get_previous_link_id_needing_driver_on_date( $franchise_id, $date, $next_link_id,
                                                      $no_driver_only = FALSE) {
    $prev = FALSE;

    $franchise_where_clause[0] = driver_link_franchise_where_clause($franchise_id, 'A');
	$franchise_where_clause[1] = driver_link_franchise_where_clause($franchise_id, 'B');

    $safe_date = mysql_real_escape_string($date);
    $date_where_clause = "DATE(B.DesiredArrivalTime) = '$safe_date' AND DATE(A.DesiredArrivalTime) = '$safe_date' AND ";

    $safe_next_link = mysql_real_escape_string($next_link_id);

    $driver_id_clause = ($no_driver_only) ? ' A.AssignedDriverUserID IS NULL AND ' : ' ';

    // TODO:  INCLUDE links that already have drivers assigned?
    // If so, keep the AssignedDriverUserID bit commented
    $sql = "SELECT A.LinkID
            FROM link AS A, link AS B
            WHERE $driver_id_clause
                  {$franchise_where_clause[0]}
				  {$franchise_where_clause[1]}
                  $date_where_clause
                  (DATE_ADD(A.DesiredArrivalTime, INTERVAL -A.EstimatedMinutes MINUTE) < 
                       DATE_ADD(B.DesiredArrivalTime, INTERVAL -B.EstimatedMinutes MINUTE)  
                   OR (
                   (DATE_ADD(A.DesiredArrivalTime, INTERVAL -A.EstimatedMinutes MINUTE) = 
                           DATE_ADD(B.DesiredArrivalTime, INTERVAL -B.EstimatedMinutes MINUTE) AND
                        ((A.DesiredArrivalTime < B.DesiredArrivalTime) OR
                         (A.DesiredArrivalTime = B.DesiredArrivalTime AND
                          A.LinkID < B.LinkID))))) AND
                  B.LinkID = $safe_next_link
            ORDER BY DATE_ADD(A.DesiredArrivalTime, INTERVAL -A.EstimatedMinutes MINUTE) ASC,
                     A.DesiredArrivalTime ASC";

    $result = mysql_query($sql);
    if ($result) {
        $link_id = -99;
        while ($row = mysql_fetch_array($result)) {
            $link_id = $row['LinkID'];
            if ($link_id == $next_link_id) {
                return $prev;
            }
            $prev = $link_id;
        }

    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get previous link for $franchise_id, $date, $next_link_id", $sql);
    }

    return $prev;
}

/**
 * Gets the drivers scheduled to drive on a selected date and the basic link
 * information (arrival time, transit time, destination IDs).
 * @param $franchise_id ID of franchise or 'ALLFRANCHISES'
 * @param $date requested arrival date for link
 * @return associative array indexed by driver ID
 */
function get_drivers_scheduled_on_date($franchise_id, $date) {
    $scheduled_drivers = array();

    $franchise_where_clause = driver_link_franchise_where_clause($franchise_id);
    $date_where_clause = driver_link_date_where_clause($date);

    $sql = "SELECT LinkID, AssignedDriverUserID
            FROM link
            WHERE $franchise_where_clause
                  $date_where_clause
                  AssignedDriverUserID IS NOT NULL 
			GROUP BY AssignedDriverUserID 
            ORDER BY DesiredArrivalTime ASC";

    $result = mysql_query($sql);
    if ($result) {
        while ($row = mysql_fetch_array($result)) {
            $scheduled_drivers[$row['AssignedDriverUserID']][] = $row;
        }

    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get drivers scheduled for $franchise_id / $date", $sql);
    }

    return $scheduled_drivers;
}

/**
 * Gets the drivers NOT scheduled to drive on a selected date.
 * @param $franchise_id ID of franchise or 'ALLFRANCHISES'
 * @param $date requested arrival date for link
 * @return array of driver IDs 
 */
function get_drivers_not_scheduled_on_date($franchise_id, $date) {
    $unscheduled_drivers = array();

    $franchise_where_clause = driver_link_franchise_where_clause($franchise_id, 'user_role');
    $date_where_clause = driver_link_date_where_clause($date);

    $sql = "SELECT driver.UserID
            FROM driver LEFT JOIN user_role ON driver.UserID = user_role.UserID
            WHERE $franchise_where_clause
				  DriverStatus = 'Active' AND
                  driver.UserID NOT IN (
                          SELECT DISTINCT AssignedDriverUserID
                          FROM link
                          WHERE $franchise_where_clause
                                $date_where_clause
                                AssignedDriverUserID IS NOT NULL ) GROUP BY driver.UserID
            ORDER BY UserID ASC";

    $result = mysql_query($sql);
    if ($result) {
        while ($row = mysql_fetch_array($result)) {
            $unscheduled_drivers[] = $row['UserID'];
        }

    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get drivers not scheduled for $franchise_id / $date", $sql);
    }

    return $unscheduled_drivers;
}


function get_driver_links_by_date($driver_user_id, $link_date) {
    $safe_driver_user_id = mysql_real_escape_string($driver_user_id);
    $safe_date = mysql_real_escape_string($link_date);

    $sql = "SELECT * FROM link
            WHERE DATE(DesiredArrivalTime) = DATE('$safe_date') AND
                  AssignedDriverUserID = $safe_driver_user_id
            ORDER BY DesiredArrivalTime ASC";

    $result = mysql_query($sql);
    if ($result) {
        $links = array();
        while ($row = mysql_fetch_array($result)) {
            $links[] = $row;
        }
        return $links;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get driver links on $link_date", $sql);
        return FALSE;
    }
}

function get_completed_driver_links_by_week($driver_user_id, $link_date) {
    $safe_driver_user_id = mysql_real_escape_string($driver_user_id);
    $safe_date = mysql_real_escape_string($link_date);

    $sql = "SELECT * FROM link_history
            WHERE YEARWEEK(DesiredArrivalTime) = YEARWEEK('$safe_date') AND
                  DriverUserID = $safe_driver_user_id
            ORDER BY DesiredArrivalTime ASC";

    $result = mysql_query($sql);
    if ($result) {
        $links = array();
        while ($row = mysql_fetch_array($result)) {
            $links[] = $row;
        }
        return $links;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get driver links on week of $link_date", $sql);
        return FALSE;
    }
}


/**
 * Gets a list of past (history) links on a given date.
 * @param $franchise_id Franchise to search - 'ALLFRANCHISES' to search all franchises
 * @param $date Date to search on - as MYSQL string.  'ALLDATES' is not allowed.
 * @return array of hashes (keys from link table), FALSE on error
 */
function get_history_links_on_date( $franchise_id, $date ) {
    $links = array();
   
    $franchise_where_clause = driver_link_franchise_where_clause($franchise_id, 'link_history');
    $date_where_clause = driver_link_date_where_clause($date);
    $safe_date = mysql_real_escape_string($date);

    $sql = "SELECT LinkID, RiderUserID, PrePadding, PostPadding, PercentagePadding, DesiredArrivalTime,IF(DesiredArrivalTime IS NOT NULL, FROM_UNIXTIME(UNIX_TIMESTAMP(DesiredArrivalTime) - ((EstimatedMinutes + PrePadding + PostPadding) * 60)),DesiredDepartureTime) as DesiredDepartureTime, link_history.FranchiseID, EstimatedMinutes, Distance, DriverUserID,
                   LinkStatus, NumberOfRiders, ReportedArrivalTime, LinkNote, LinkFlexFlag, QuotedCents,
                   FromDestinationID, ToDestinationID, NumberOfRiders, 
                   F_Dest.Name AS F_Name, F_Dest.IsPublic AS F_Public,
                   F_Dest.DestinationDetail AS F_DestinationDetail,
                   F_Address.AddressID AS F_AddressID, F_Address.Address1 AS F_Address1, 
                   F_Address.Address2 AS F_Address2, F_Address.City AS F_City, 
                   F_Address.State AS F_State, F_Address.ZIP5 AS F_ZIP5, 
                   F_Address.ZIP4 AS F_ZIP4,
                   F_Phone.PhoneNumber AS F_PhoneNumber,
                   T_Dest.Name AS T_Name, T_Dest.IsPublic AS T_IsPublic,
                   T_Dest.DestinationDetail AS T_DestinationDetail,
                   T_Address.AddressID AS T_AddressID, T_Address.Address1 AS T_Address1, 
                   T_Address.Address2 AS T_Address2,
                   T_Address.City AS T_City, T_Address.State AS T_State, 
                   T_Address.ZIP5 AS T_ZIP5, T_Address.ZIP4 AS T_ZIP4,
                   T_Phone.PhoneNumber AS T_PhoneNumber
            FROM (link_history, destination AS F_Dest, destination AS T_Dest, 
                 address AS F_Address, address AS T_Address)
                 LEFT JOIN phone AS F_Phone ON F_Dest.PhoneID = F_Phone.PhoneID
                 LEFT JOIN phone AS T_Phone ON T_Dest.PhoneID = T_Phone.PhoneID
            WHERE 
                  $franchise_where_clause
                  DATE(DesiredArrivalTime) = '$safe_date' AND
                  (link_history.CustomTransitionType != 'RIDER' OR 
                  link_history.CustomTransitionType IS NULL) AND
                  link_history.FromDestinationID = F_Dest.DestinationID AND
                  F_Dest.AddressID = F_Address.AddressID AND
                  link_history.ToDestinationID = T_Dest.DestinationID AND
                  T_Dest.AddressID = T_Address.AddressID
            ORDER BY DesiredArrivalTime ASC";

    $result = mysql_query($sql) or die(mysql_error());
    if ($result) {
        while ($row = mysql_fetch_array($result)) {
            $links[] = $row;
        }

    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get driverless links for $franchise_id", $sql);
    }

    return $links;
    


}

function get_rider_history_links_on_date( $franchise_id, $date ) {
    $links = array();
   
    $franchise_where_clause = driver_link_franchise_where_clause($franchise_id, 'link_history');
    $date_where_clause = driver_link_date_where_clause($date);
    $safe_date = mysql_real_escape_string($date);

    $sql = "SELECT LinkID, RiderUserID, PrePadding, PostPadding, PercentagePadding, DesiredArrivalTime,IF(DesiredArrivalTime IS NOT NULL, FROM_UNIXTIME(UNIX_TIMESTAMP(DesiredArrivalTime) - ((EstimatedMinutes + PrePadding + PostPadding) * 60)),DesiredDepartureTime) as DesiredDepartureTime, link_history.FranchiseID, EstimatedMinutes, Distance, DriverUserID,
                   LinkStatus, NumberOfRiders, ReportedArrivalTime, LinkNote, LinkFlexFlag, QuotedCents,
                   FromDestinationID, ToDestinationID, NumberOfRiders, 
                   F_Dest.Name AS F_Name, F_Dest.IsPublic AS F_Public,
                   F_Dest.DestinationDetail AS F_DestinationDetail,
                   F_Address.AddressID AS F_AddressID, F_Address.Address1 AS F_Address1, 
                   F_Address.Address2 AS F_Address2, F_Address.City AS F_City, 
                   F_Address.State AS F_State, F_Address.ZIP5 AS F_ZIP5, 
                   F_Address.ZIP4 AS F_ZIP4,
                   F_Phone.PhoneNumber AS F_PhoneNumber,
                   T_Dest.Name AS T_Name, T_Dest.IsPublic AS T_IsPublic,
                   T_Dest.DestinationDetail AS T_DestinationDetail,
                   T_Address.AddressID AS T_AddressID, T_Address.Address1 AS T_Address1, 
                   T_Address.Address2 AS T_Address2,
                   T_Address.City AS T_City, T_Address.State AS T_State, 
                   T_Address.ZIP5 AS T_ZIP5, T_Address.ZIP4 AS T_ZIP4,
                   T_Phone.PhoneNumber AS T_PhoneNumber
            FROM (link_history, destination AS F_Dest, destination AS T_Dest, 
                 address AS F_Address, address AS T_Address)
                 LEFT JOIN phone AS F_Phone ON F_Dest.PhoneID = F_Phone.PhoneID
                 LEFT JOIN phone AS T_Phone ON T_Dest.PhoneID = T_Phone.PhoneID
            WHERE 
                  $franchise_where_clause
                  DATE(DesiredArrivalTime) = '$safe_date' AND
                  link_history.CustomTransitionType = 'RIDER' AND
                  link_history.FromDestinationID = F_Dest.DestinationID AND
                  F_Dest.AddressID = F_Address.AddressID AND
                  link_history.ToDestinationID = T_Dest.DestinationID AND
                  T_Dest.AddressID = T_Address.AddressID
            ORDER BY DesiredArrivalTime ASC";

    $result = mysql_query($sql) or die(mysql_error());
    if ($result) {
        while ($row = mysql_fetch_array($result)) {
            $links[] = $row;
        }

    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get driverless links for $franchise_id", $sql);
    }

    return $links;
    


}

function getDriverOptions($franchise) {
    $franchise_where_clause = driver_link_franchise_where_clause($franchise, 'user_role');

    $sql = "SELECT driver.UserID, person_name.FirstName, person_name.LastName
            FROM driver, user_role, users, person_name
            WHERE user_role.FranchiseID = $franchise AND
				  driver.DriverStatus = 'Active' and 
				  driver.UserID = user_role.UserID and
                  driver.UserID = users.UserID and 
				  person_name.PersonNameID = users.PersonNameID
			GROUP BY UserID
            ORDER BY person_name.FirstName ASC, person_name.LastName ASC";
			
	
	$html = '';
	$results = mysql_query($sql) or die(mysql_error());
	while ($row = mysql_fetch_assoc($results)) {
	  $html .= '<option value="'.$row['UserID'].'">'.$row['FirstName'].' '.$row['LastName'] .' - '.$row['UserID'] .'</option>';
	}
	return $html;
}

?>
