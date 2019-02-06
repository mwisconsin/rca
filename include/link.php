<?php

require_once('include/database.php');
require_once('include/rc_log.php');
require_once('include/date_time.php');
require_once('include/large_facility.php');
require_once('include/weather.php');
require_once 'include/time_delay.php';
require_once 'include/scheduling_lockout.php';
require_once 'include/name.php';


function get_link($link_id) {
    if (is_null($link_id)) {
        return FALSE;
    }
    $safe_link_id = mysql_real_escape_string($link_id);

    $sql = "SELECT LinkID, RiderUserID, DesiredDepartureTime, DesiredArrivalTime, Distance, EstimatedMinutes, 
                   QuotedCents, AssignedDriverUserID, VehicleID, link.FranchiseID, NumberOfRiders, PrePadding, PostPadding,  PercentagePadding, DepartureTimeConfimed, ArrivalTimeConfirmed,
                   LinkStatus, FromDestinationID AS F_DestinationID, ToDestinationID AS T_DestinationID,
                   F_Dest.Name AS F_Name, F_Dest.IsPublic AS F_Public,
                   F_Dest.DestinationDetail AS F_DestinationDetail,
                   F_Address.AddressID AS F_AddressID, F_Address.Address1 AS F_Address1, 
                   F_Address.Address2 AS F_Address2, F_Address.City AS F_City, 
                   F_Address.State AS F_State, F_Address.ZIP5 AS F_ZIP5, 
                   F_Address.ZIP4 AS F_ZIP4,
                   F_Address.VerifySource AS F_VerifySource,
                   F_Address.Latitude as F_Latitude,
                   F_Address.Longitude as F_Longitude,
                   T_Dest.Name AS T_Name, T_Dest.IsPublic AS T_IsPublic,
                   T_Dest.DestinationDetail AS T_DestinationDetail,
                   T_Address.AddressID AS T_AddressID, T_Address.Address1 AS T_Address1, 
                   T_Address.Address2 AS T_Address2,
                   T_Address.City AS T_City, T_Address.State AS T_State, 
                   T_Address.ZIP5 AS T_ZIP5, T_Address.ZIP4 AS T_ZIP4,
                   T_Address.VerifySource as T_VerifySource,
                   T_Address.Latitude as T_Latitude,
                   T_Address.Longitude as T_Longitude
            FROM link, destination AS F_Dest, destination AS T_Dest, 
                 address AS F_Address, address AS T_Address
            WHERE link.LinkID = $safe_link_id AND
                  link.FromDestinationID = F_Dest.DestinationID AND
                  F_Dest.AddressID = F_Address.AddressID AND
                  link.ToDestinationID = T_Dest.DestinationID AND
                  T_Dest.AddressID = T_Address.AddressID
            ORDER BY DesiredArrivalTime ASC";

    $result = mysql_query($sql) or die(mysql_error());

    if ($result) {
        $row = mysql_fetch_array($result);
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get link for ID $link_id", $sql);
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), var_export(debug_backtrace(), TRUE), '');
        $row = FALSE;
    }

    return $row;
}

function get_history_link($link_id) {
    if (is_null($link_id)) {
        return FALSE;
    }
    $safe_link_id = mysql_real_escape_string($link_id);

    $sql = "SELECT LinkID, RiderUserID, DesiredDepartureTime, DesiredArrivalTime, Distance, EstimatedMinutes, 
                   QuotedCents, DriverUserID, VehicleID, link_history.FranchiseID, NumberOfRiders, PrePadding, PostPadding,  PercentagePadding,
                   LinkStatus, FromDestinationID AS F_DestinationID, ToDestinationID AS T_DestinationID,
                   F_Dest.Name AS F_Name, F_Dest.IsPublic AS F_Public,
                   F_Dest.DestinationDetail AS F_DestinationDetail,
                   F_Address.AddressID AS F_AddressID, F_Address.Address1 AS F_Address1, 
                   F_Address.Address2 AS F_Address2, F_Address.City AS F_City, 
                   F_Address.State AS F_State, F_Address.ZIP5 AS F_ZIP5, 
                   F_Address.ZIP4 AS F_ZIP4,
                   F_Address.VerifySource AS F_VerifySource,
                   F_Address.Latitude as F_Latitude,
                   F_Address.Longitude as F_Longitude,
                   T_Dest.Name AS T_Name, T_Dest.IsPublic AS T_IsPublic,
                   T_Dest.DestinationDetail AS T_DestinationDetail,
                   T_Address.AddressID AS T_AddressID, T_Address.Address1 AS T_Address1, 
                   T_Address.Address2 AS T_Address2,
                   T_Address.City AS T_City, T_Address.State AS T_State, 
                   T_Address.ZIP5 AS T_ZIP5, T_Address.ZIP4 AS T_ZIP4,
                   T_Address.VerifySource as T_VerifySource,
                   T_Address.Latitude as T_Latitude,
                   T_Address.Longitude as T_Longitude
            FROM link_history, destination AS F_Dest, destination AS T_Dest, 
                 address AS F_Address, address AS T_Address
            WHERE link_history.LinkID = $safe_link_id AND
                  link_history.FromDestinationID = F_Dest.DestinationID AND
                  F_Dest.AddressID = F_Address.AddressID AND
                  link_history.ToDestinationID = T_Dest.DestinationID AND
                  T_Dest.AddressID = T_Address.AddressID
            ORDER BY DesiredArrivalTime ASC";

    $result = mysql_query($sql) or die(mysql_error());
    if ($result) {
        $row = mysql_fetch_array($result);
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get link for ID $link_id", $sql);
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), var_export(debug_backtrace(), TRUE), '');
        $row = FALSE;
    }
    return $row;
}

function add_rider_link_request( $rider_user_id, $from_destination_id, $to_destination_id,
                                 $arrival_time, $distance, $estimated_minutes, $quoted_cents,
                                 $number_of_riders = 1, $departure_time_confirmed = FALSE, $arrival_time_confirmed = FALSE, $link_note = NULL, $link_flex_flag = 0,
								 $driver_confirmed = FALSE, $prePadding = 5, $postPadding = 5, $departure_time = FALSE,
								 $last_changed_by = 0, $last_changed_date = '', $created_by = 0, $created_date = '') {
    if (!is_numeric($number_of_riders))
    {
        $number_of_riders = 1;
    }
	if ($distance == 0.0 && $from_destination_id != $to_destination_id)
	{
		$distance = 0.1;
	}
    $safe_rider_user_id = mysql_real_escape_string($rider_user_id);
    $safe_from_destination_id = mysql_real_escape_string($from_destination_id);
    $safe_to_destination_id = mysql_real_escape_string($to_destination_id);
    $safe_distance = mysql_real_escape_string($distance);
    $safe_minutes = mysql_real_escape_string($estimated_minutes);
    $safe_quoted_cents = mysql_real_escape_string($quoted_cents);
    $safe_num_riders = mysql_real_escape_string($number_of_riders);
	$safe_dtc = $departure_time_confirmed ? 'Y' : 'N';
	$safe_atc = $arrival_time_confirmed ? 'Y' : 'N';
	$safe_note = $link_note === NULL ? 'NULL' : "'" . mysql_real_escape_string($link_note) . "'";
	$safe_DC = $driver_confirmed ? 'Yes' : 'No';
	$safe_prePadding = $prePadding == NULL ? 5 :  mysql_real_escape_string($prePadding);
	$safe_postPadding = $postPadding == NULL ? 5 : mysql_real_escape_string($postPadding);
	$safe_franchise_id = mysql_real_escape_string(get_current_user_franchise());

    $safe_arrival_time = date('Y-m-d H:i:0', $arrival_time);
	$safe_departure_time = ($departure_time !== FALSE) ? "'" . date('Y-m-d H:i:0', $departure_time) . "'" : 'NULL';
    $sql = "INSERT INTO link (RiderUserID, FromDestinationID, ToDestinationID,
                              DesiredArrivalTime, Distance, EstimatedMinutes, QuotedCents, FranchiseID,
                              NumberOfRiders, DepartureTimeConfimed, ArrivalTimeConfirmed, LinkStatus, LinkNote, LinkFlexFlag, DriverConfirmed, PrePadding, PostPadding, DesiredDepartureTime, Last_Changed_By, Last_Changed_Date, Created_By, Created_Date) 
            VALUES ($safe_rider_user_id, $safe_from_destination_id, $safe_to_destination_id,
                    '$safe_arrival_time', $safe_distance, $safe_minutes, $safe_quoted_cents,
                        $safe_franchise_id,
                    $safe_num_riders, '$safe_dtc', '$safe_atc', 'UNKNOWN', $safe_note, $link_flex_flag, '$safe_DC', 
					'$safe_prePadding', '$safe_postPadding', $safe_departure_time, $last_changed_by, '$last_changed_date', $created_by, '$created_date')";

    $result = mysql_query($sql) or die($sql);

    if ($result) {
       $return = mysql_insert_id(); 
       $actor_user = get_current_user_id();
       $log_string = "User $actor_user created new link $return for $rider_user_id from " .
                     "$from_destination_id to $to_destination_id arriving at " .
                     "$safe_arrival_time for $number_of_riders riders.  Distance is " .
                     "$distance, quoted at $quoted_cents cents for $estimated_minutes minutes.";
        rc_log(PEAR_LOG_NOTICE, $log_string, 'rc_link_log.log');
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not add link_request $rider_user_id, $from_destination_id, $to_destination_id",
                        $sql);
    }

    return $return;
}

function get_rider_active_links( $rider_user_id ) {
    $links = array();
    $safe_rider_user_id = mysql_real_escape_string($rider_user_id);

    $sql = "SELECT LinkID, RiderUserID, DesiredDepartureTime, DesiredArrivalTime, Distance, EstimatedMinutes, QuotedCents,
					 PrePadding, PostPadding, PercentagePadding, link.FranchiseID,
                   AssignedDriverUserID, VehicleID, NumberOfRiders, LinkStatus, DepartureTimeConfimed, ArrivalTimeConfirmed,
                   FromDestinationID AS F_DestinationID, ToDestinationID AS T_DestinationID,
                   F_Dest.Name AS F_Name, F_Dest.IsPublic AS F_Public,
                   F_Dest.DestinationDetail AS F_DestinationDetail,
                   F_Address.AddressID AS F_AddressID, F_Address.Address1 AS F_Address1, 
                   F_Address.Address2 AS F_Address2, F_Address.City AS F_City, 
                   F_Address.State AS F_State, F_Address.ZIP5 AS F_ZIP5, 
                   F_Address.ZIP4 AS F_ZIP4,
                   F_Address.VerifySource AS F_VerifySource,
                   F_Address.Latitude as F_Latitude,
                   F_Address.Longitude as F_Longitude,
                   F_Phone.PhoneNumber AS F_PhoneNumber, F_Phone.Ext AS F_Ext,
                   T_Dest.Name AS T_Name, T_Dest.IsPublic AS T_IsPublic,
                   T_Dest.DestinationDetail AS T_DestinationDetail,
                   T_Address.AddressID AS T_AddressID, T_Address.Address1 AS T_Address1, 
                   T_Address.Address2 AS T_Address2,
                   T_Address.City AS T_City, T_Address.State AS T_State, 
                   T_Address.ZIP5 AS T_ZIP5, T_Address.ZIP4 AS T_ZIP4,
                   T_Address.VerifySource as T_VerifySource,
                   T_Address.Latitude as T_Latitude,
                   T_Address.Longitude as T_Longitude,
                   T_Phone.PhoneNumber AS T_PhoneNumber, T_Phone.Ext AS T_Ext,
                   RecurRide, RRFrequency, RRNumber, RRStatus, RRStart, RREnd, RROngoing, LinkNote, LinkFlexFlag, Last_Changed_By, Last_Changed_Date, Created_By, Created_Date, CustomTransitionID
            FROM (link, destination AS F_Dest, destination AS T_Dest, 
                 address AS F_Address, address AS T_Address)
                 LEFT JOIN phone AS F_Phone ON F_Dest.PhoneID = F_Phone.PhoneID
                 LEFT JOIN phone AS T_Phone ON T_Dest.PhoneID = T_Phone.PhoneID
                 WHERE link.RiderUserID = $safe_rider_user_id AND
            	  (link.CustomTransitionType != 'DRIVER' OR 
            	  link.CustomTransitionType IS NULL) AND
                  link.FromDestinationID = F_Dest.DestinationID AND
                  F_Dest.AddressID = F_Address.AddressID AND
                  link.ToDestinationID = T_Dest.DestinationID AND
                  T_Dest.AddressID = T_Address.AddressID
            ORDER BY DesiredArrivalTime ASC";

    $result = mysql_query($sql) or die(mysql_error());
    if ($result) {
        while ($row = mysql_fetch_array($result)) {
            $links[] = $row;
        }

    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get links for rider $rider_user_id", $sql);
    }

    return $links;
}

function get_facility_active_links( $facility_id ) {
    $links = array();

    $sql = "SELECT LinkID, RiderUserID, DesiredDepartureTime, DesiredArrivalTime, Distance, EstimatedMinutes, QuotedCents,
					 PrePadding, PostPadding, PercentagePadding, link.FranchiseID,
                   AssignedDriverUserID, VehicleID, NumberOfRiders, LinkStatus, DepartureTimeConfimed, ArrivalTimeConfirmed,
                   FromDestinationID AS F_DestinationID, ToDestinationID AS T_DestinationID,
                   F_Dest.Name AS F_Name, F_Dest.IsPublic AS F_Public,
                   F_Dest.DestinationDetail AS F_DestinationDetail,
                   F_Address.AddressID AS F_AddressID, F_Address.Address1 AS F_Address1, 
                   F_Address.Address2 AS F_Address2, F_Address.City AS F_City, 
                   F_Address.State AS F_State, F_Address.ZIP5 AS F_ZIP5, 
                   F_Address.ZIP4 AS F_ZIP4,
                   F_Address.VerifySource AS F_VerifySource,
                   F_Address.Latitude as F_Latitude,
                   F_Address.Longitude as F_Longitude,
                   F_Phone.PhoneNumber AS F_PhoneNumber,
                   T_Dest.Name AS T_Name, T_Dest.IsPublic AS T_IsPublic,
                   T_Dest.DestinationDetail AS T_DestinationDetail,
                   T_Address.AddressID AS T_AddressID, T_Address.Address1 AS T_Address1, 
                   T_Address.Address2 AS T_Address2,
                   T_Address.City AS T_City, T_Address.State AS T_State, 
                   T_Address.ZIP5 AS T_ZIP5, T_Address.ZIP4 AS T_ZIP4,
                   T_Address.VerifySource as T_VerifySource,
                   T_Address.Latitude as T_Latitude,
                   T_Address.Longitude as T_Longitude,
                   T_Phone.PhoneNumber AS T_PhoneNumber, T_Phone.Ext AS T_Ext,
                   RecurRide, RRFrequency, RRNumber, RRStatus, RRStart, RREnd, RROngoing,
                   person_name.FirstName, person_name.LastName
            FROM (link, destination AS F_Dest, destination AS T_Dest, 
                 address AS F_Address, address AS T_Address, care_facility_user as cfu, users as rider, person_name)
                 LEFT JOIN phone AS F_Phone ON F_Dest.PhoneID = F_Phone.PhoneID
                 LEFT JOIN phone AS T_Phone ON T_Dest.PhoneID = T_Phone.PhoneID
                 WHERE link.RiderUserID = cfu.UserID and cfu.CareFacilityID = $facility_id AND
                 RiderUserID = rider.UserID and rider.PersonNameID = person_name.PersonNameID AND
            	  (link.CustomTransitionType != 'DRIVER' OR 
            	  link.CustomTransitionType IS NULL) AND
                  link.FromDestinationID = F_Dest.DestinationID AND
                  F_Dest.AddressID = F_Address.AddressID AND
                  link.ToDestinationID = T_Dest.DestinationID AND
                  T_Dest.AddressID = T_Address.AddressID
                  AND ( (not cfu.DisconnectDate is null AND cfu.DisconnectDate > DesiredArrivalTime )
                  			OR cfu.DisconnectDate is null )
            ORDER BY DesiredArrivalTime ASC";

    $result = mysql_query($sql) or die(mysql_error());
    if ($result) {
        while ($row = mysql_fetch_array($result)) {
            $links[] = $row;
        }

    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get links for rider $rider_user_id", $sql);
    }

    return $links;
}

function get_driver_active_links( $driver_user_id, $date = 'ALLDATES' ) {
		#echo "get_driver_active_links $driver_user_id, $date<br>";
    $links = array();
    $safe_driver_user_id = mysql_real_escape_string($driver_user_id);
		//echo "DATE: $date";
		
    if ( $date == 'ALLDATES' ) {
        $date_where_clause = ''; 
    } elseif ($date == 'FUTURE') {
    	#$date_where_clause = "DATE(DesiredArrivalTime) > '" .  date('Y-m-d H:i')  . "' AND";
    	$date_where_clause = "DesiredArrivalTime > '" .  date('Y-m-d H:i')  . "' AND";
    } else {
        $safe_date = mysql_real_escape_string($date);
        $date_where_clause = "DATE(DesiredArrivalTime) = '$safe_date' AND";
    }

    $sql = "SELECT LinkID, RiderUserID, DesiredDepartureTime, DesiredArrivalTime, link.FranchiseID, Distance, EstimatedMinutes, QuotedCents, 
	PrePadding, PostPadding, PercentagePadding, AssignedDriverUserID, VehicleID, ReportedArrivalTime, LinkStatus, LinkNote, LinkFlexFlag,
	NumberOfRiders, CustomTransitionType, CustomTransitionID, DriverConfirmed,
                   FromDestinationID AS F_DestinationID, ToDestinationID AS T_DestinationID,
                   F_Dest.Name AS F_Name, F_Dest.IsPublic AS F_Public,
                   F_Dest.DestinationDetail AS F_DestinationDetail,
                   F_Address.AddressID AS F_AddressID, F_Address.Address1 AS F_Address1, 
                   F_Address.Address2 AS F_Address2, F_Address.City AS F_City, 
                   F_Address.State AS F_State, F_Address.ZIP5 AS F_ZIP5, 
                   F_Address.ZIP4 AS F_ZIP4,
                   F_Address.VerifySource as F_VerifySource,
                   F_Address.Latitude as F_Latitude,
                   F_Address.Longitude as F_Longitude,
                   T_Dest.Name AS T_Name, T_Dest.IsPublic AS T_IsPublic,
                   T_Dest.DestinationDetail AS T_DestinationDetail,
                   T_Address.AddressID AS T_AddressID, T_Address.Address1 AS T_Address1, 
                   T_Address.Address2 AS T_Address2,
                   T_Address.City AS T_City, T_Address.State AS T_State, 
                   T_Address.ZIP5 AS T_ZIP5, T_Address.ZIP4 AS T_ZIP4,
                   T_Address.VerifySource as T_VerifySource,
                   T_Address.Latitude as T_Latitude,
                   T_Address.Longitude as T_Longitude, IndexPath, IndexPathUrgent
            FROM link, destination AS F_Dest, destination AS T_Dest, 
                 address AS F_Address, address AS T_Address
            WHERE link.AssignedDriverUserID = $safe_driver_user_id AND
                  $date_where_clause
                  (link.CustomTransitionType != 'RIDER' || link.CustomTransitionType IS NULL) AND
                  link.FromDestinationID = F_Dest.DestinationID AND
                  F_Dest.AddressID = F_Address.AddressID AND
                  link.ToDestinationID = T_Dest.DestinationID AND
                  T_Dest.AddressID = T_Address.AddressID
            ORDER BY DATE_ADD(DesiredArrivalTime, INTERVAL -EstimatedMinutes MINUTE) ASC,
                     DesiredArrivalTime ASC, LinkID ASC";
    //echo $sql."<BR><BR>";
    $result = mysql_query($sql);
    if ($result) {
        while ($row = mysql_fetch_array($result)) {
            $links[] = $row;
        }

    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get links for driver $driver_user_id ($date)", $sql);
    }

    return $links;
}

function get_all_driver_history_and_active_links( $driver_user_id, $date = 'ALLDATES' ) {
    $links = array();
    $safe_driver_user_id = mysql_real_escape_string($driver_user_id);

    if ( $date == 'ALLDATES' ) {
        $date_where_clause = ''; 
    } else {
        $safe_date = mysql_real_escape_string($date);
        $date_where_clause = "DATE(DesiredArrivalTime) = '$safe_date' AND";
    }

    $sql = "SELECT LinkID, RiderUserID, DesiredDepartureTime, link_history.FranchiseID, DesiredArrivalTime, Distance, EstimatedMinutes, QuotedCents, PrePadding, PostPadding, 
	PercentagePadding, DriverUserID, VehicleID, ReportedArrivalTime, LinkStatus, NumberOfRiders, LinkNote, LinkFlexFlag, 
	CustomTransitionType, CustomTransitionID,
                   FromDestinationID AS F_DestinationID, ToDestinationID AS T_DestinationID,
                   F_Dest.Name AS F_Name, F_Dest.IsPublic AS F_Public,
                   F_Dest.DestinationDetail AS F_DestinationDetail,
                   F_Address.AddressID AS F_AddressID, F_Address.Address1 AS F_Address1, 
                   F_Address.Address2 AS F_Address2, F_Address.City AS F_City, 
                   F_Address.State AS F_State, F_Address.ZIP5 AS F_ZIP5, 
                   F_Address.ZIP4 AS F_ZIP4,
                   F_Address.VerifySource as F_VerifySource,
                   F_Address.Latitude as F_Latitude,
                   F_Address.Longitude as F_Longitude,
                   T_Dest.Name AS T_Name, T_Dest.IsPublic AS T_IsPublic,
                   T_Dest.DestinationDetail AS T_DestinationDetail,
                   T_Address.AddressID AS T_AddressID, T_Address.Address1 AS T_Address1, 
                   T_Address.Address2 AS T_Address2,
                   T_Address.City AS T_City, T_Address.State AS T_State, 
                   T_Address.ZIP5 AS T_ZIP5, T_Address.ZIP4 AS T_ZIP4,
                   T_Address.VerifySource as T_VerifySource,
                   T_Address.Latitude as T_Latitude,
                   T_Address.Longitude as T_Longitude,
                   'HISTORY' AS IsHistory, IndexPath, IndexPathUrgent
            FROM link_history, destination AS F_Dest, destination AS T_Dest, 
                 address AS F_Address, address AS T_Address
            WHERE link_history.DriverUserID = $safe_driver_user_id AND
                  $date_where_clause
                  (link_history.CustomTransitionType != 'RIDER' OR 
                  link_history.CustomTransitionType IS NULL) AND
                  link_history.FromDestinationID = F_Dest.DestinationID AND
                  F_Dest.AddressID = F_Address.AddressID AND
                  link_history.ToDestinationID = T_Dest.DestinationID AND
                  T_Dest.AddressID = T_Address.AddressID
            ORDER BY DATE_ADD(DesiredArrivalTime, INTERVAL -EstimatedMinutes MINUTE) ASC,
                     DesiredArrivalTime ASC, LinkID ASC";
    //echo $sql."<BR><BR>";
    $result = mysql_query($sql);
    if ($result) {
        $links = get_driver_active_links($driver_user_id, $date);
        while ($row = mysql_fetch_array($result)) {
            $links[] = $row;
        }

    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get links for driver $driver_user_id ($date)", $sql);
    }

    return $links;
}


function get_user_link_table_headings($include_delete = FALSE, $show_status = FALSE, $show_recur = FALSE, $show_rider = FALSE, $show_date_range_filter = FALSE) {
    $ret = '<th>Link ID</th>' . ($show_rider ? '<th>Rider</th>' : '') .'
    				<th>SMS</th>
    				<th>Rid- ers</th>
    				<th>Depart</th>
    				<th width="120px">At</th>
    				<th>DR</th>';
    if($show_date_range_filter) {
    	$ret .= "<th>FromDate</th><th>ToDate</th>";
    }
    $ret .= '<th>Arrive</th>
    				<th>At</th>
    				<th>Est`d Time</th>
    				<th>Dist</th>
    				<th>Price</th>
    				';

    if ($include_delete) {
        $ret .= '<th>Delete</th>';
    }

    if ($show_status) {
        $ret .= '<th width=154>Status</th>';
    }
    
    if ($show_recur) {
    	$ret .= "<th>Recur<br>Ride</th>
    		<th>RR Freq</th>
    		<th>RR #</th>
    		<th>RR Status</th>
    		<th>Start</th>
    		<th>End</th>";
    }

    return $ret;
}

function get_link_as_user_link_table_row( $link_row, $include_edit = FALSE, $show_status = FALSE, $link_to_ledger = FALSE, $show_recur = FALSE, $show_rider = FALSE, $show_date_range_filter = FALSE ) {

    $arrival = get_link_arrival_time($link_row);
  
    $departure = get_link_departure_time($link_row);
    $display_departure = date('g:i A \o\n l, F j, Y', $departure['time_t']);
    $arrival_departure = date('g:i A \o\n l, F j, Y', $arrival['time_t']);
    $href = 'user_ledger.php?StartMonth=' . date('n&\S\t\a\r\t\D\a\y=j&\S\t\a\r\t\Y\e\a\r=Y', $departure['time_t']) .
                       '&EndMonth=' . date('n&\E\n\d\D\a\y=j&\E\n\d\Y\e\a\r=Y', $departure['time_t']);
	$link_row['EstimatedMinutes'] = get_link_estimated_minutes($link_row);
    $ret = '';
  	$sql = "select FirstName from users 
  		natural join rider_destination
  		natural join person_name where DestinationID = $link_row[F_DestinationID]";
  	$rs = mysql_fetch_array(mysql_query($sql));
  	
  	$sql = "select * from todays_links where LinkID = $link_row[LinkID]";
  	$tlr = mysql_query($sql);
  	$sms_checkbox = "";
  	if(mysql_num_rows($tlr) > 0) {
  		$tlrs = mysql_fetch_array($tlr);
  		$sms_checkbox = "<input onClick=\"updateTRFT("
  			.$link_row["LinkID"].",this);\" type=checkbox name=TextRiderForThisLink_".$link_row["LinkID"]." ".($tlrs["TextRiderForThisLink"]==1?" checked": "").">";
  	}
    $ret .= "<td><a href=\"/admin_schedule_link.php?LinkID={$link_row['LinkID']}\">{$link_row['LinkID']}</td>" .
    				"<td>$sms_checkbox</td>" .
    				"<td>{$link_row['NumberOfRiders']}</td>".
    				($show_rider ? "<td>" . $link_row["FirstName"] .' '. $link_row["LastName"] . "</td>" : "").
            '<td nowrap="nowrap" style="padding-left: 0.5em;">' .
            get_link_destination_table_cell_contents('F_', $link_row, FALSE, $rs["FirstName"]) .
            "</td><td>" . (($link_to_ledger === TRUE) ? "<a href=\"{$href}\">{$display_departure}</a>" : $display_departure) . ($link_row['DepartureTimeConfimed'] == 'Y' ? '<br><u>(Conf_d)</u>' : '') .
            '</td>';

    $ret .= '<td>' .  (($link_row['AssignedDriverUserID'] || $link_row['DriverUserID']) ? 
                        '<center><img src="images/green_check.gif" /><br>' . get_driver_user_id($link_row['AssignedDriverUserID']) . get_driver_user_id($link_row["DriverUserID"]) . '</center>' : '&nbsp;') .
            '</td>';
    if($show_date_range_filter) {
    	$ret .= "<th>".date('m/d/Y',$departure['time_t'])."</th><th>".date('m/d/Y',$arrival['time_t'])."</th>";
    }		
  	$sql = "select FirstName from users 
  		natural join rider_destination
  		natural join person_name where DestinationID = $link_row[T_DestinationID]";
  	$rs = mysql_fetch_array(mysql_query($sql));
    $ret .= '<td nowrap="nowrap">' . get_link_destination_table_cell_contents('T_', $link_row, FALSE, $rs["FirstName"]) .
            "</td><td>{$arrival_departure}".($link_row['ArrivalTimeConfirmed'] == 'Y' ? '<br><u>(Conf_d)</u>' : '')."</td>";
            
    $ret .= "<td>{$link_row['EstimatedMinutes']} min.</td>" .
            "<td>{$link_row['Distance']} mi.</td><td>$";
    
    $ret .= sprintf("%d.%02.2d", $link_row['QuotedCents']/100, $link_row['QuotedCents'] % 100) . '</td>'; 
			

    if ($include_edit) {
        $ret .= '<td><a href="plan_ride.php?edit=' . $link_row['LinkID'] . '"><input type="button" value="Edit"/></a><br/><input type="submit" name="DeleteLink[' . $link_row['LinkID'] . 
                ']" value="Delete Link" /></td>';
    }

		echo "
		<script>
			function set_link_status( linkid, stat ) {
				$.get('/xhr/set_link_status.php', { linkid: linkid, stat : stat }, function(data) {
					window.location.href = '/myrides.php';
				});
			}
			function updateTRFT( linkid, cb ) {
				jQuery.get('/xhr/updateTRFT.php?linkid='+linkid+'&to='+(cb.checked?1:0),function(data) {
					d = jQuery('<div>Ride Text Preferences Updated</div>').dialog({
						modal: true,
						buttons: [
							{
								text: 'Ok',
								click: function() { d.dialog('close'); }
							}
						],
						close: function() { d.remove(); }	
					});
				});			
			}
		</script>
		";
    if ($show_status) {
    		$ret .= "<td class=link_status_cell>";
        if (current_user_has_role(1, 'FullAdmin') || current_user_has_role($link_row['FranchiseID'], 'Franchisee')) {
            $ret .= '<a href="admin_driver_links.php?Year=' . 
                    date('Y', $departure['time_t']) . '&Month=' . 
                    date('m', $departure['time_t']) . '&Day=' . 
                    date('j', $departure['time_t']) . "\">{$link_row['LinkStatus']}</a>";
//
//            if($link_row['LinkStatus'] == 'CANCELEDEARLY' && strtotime("-30 day") < $departure['time_t']) {
//            	$ret .= "<BR><a href=\"#\" onClick=\"set_link_status({$link_row[LinkID]},'UNKNOWN'); return false;\">UNCANCEL</a>";
//            }
						$ret .= '<span class=status-text>';
		        if($link_row['Last_Changed_By'] != 0) {
		        	$u = get_user_account($link_row['Last_Changed_By']);
		        	$ret .= '<br><b>Last Changed By</b>: '.$u["FirstName"]." ".$u["LastName"];
		        	$ret .= " on ".date('m/d/Y h:i A',strtotime($link_row["Last_Changed_Date"]))."<BR>";
		        }
		        if($link_row['Created_By'] != 0) {
		        	$u = get_user_account($link_row['Created_By']);
		        	$ret .= '<br><b>Created By</b>: '.$u["FirstName"]." ".$u["LastName"];
		        	$ret .= " on ".date('m/d/Y h:i A',strtotime($link_row["Created_Date"]));
		        }
		        $ret .= "</span>";
        } else {
            $ret .= "{$link_row['LinkStatus']}";
        }
        $ret .= "</td>";
    }
  
    if ($show_recur) {
    	$ret .= "<td><input type=checkbox name=RecurRide ".($link_row["RecurRide"] == "Y" ? "checked" : "")."></td>";
    	$ret .= "<td><select size=1 name=RRFrequency>";
    	$rrfreq = array("","Weekly","Last","Date","Week");
    	for($i = 0; $i < count($rrfreq); $i++) $ret .= "<option value=\"{$rrfreq[$i]}\" ".($link_row["RecurRide"] == "Y" && $link_row["RRFrequency"] == $rrfreq[$i] ? "selected" : "").">{$rrfreq[$i]}</option>";
    	$ret .= "</select></td>";
    	$ret .= "<td><input type=text size=2 name=RRNumber value=\"".($link_row["RecurRide"] == "Y" ? $link_row["RRNumber"] : "")."\"></td>";
    	$rrstatus = array("Active","Hold","Delete");
    	$ret .= "<td><select size=1 name=RRStatus>";
    	for($i = 0; $i < count($rrstatus); $i++) $ret .= "<option value={$rrstatus[$i]} ".($link_row["RecurRide"] == "Y" && $link_row["RRStatus"] == $rrstatus[$i] ? "selected" : "").">{$rrstatus[$i]}</option>";
    	$ret .= "</select></td>";
    	$ret .= "<td valign=top><input type=text size=8 name=RRStart class=jq_datepicker value=\"".($link_row["RecurRide"] == "Y" && $link_row["RRStart"] != '' ? date('m/d/Y',strtotime($link_row["RRStart"])) : '')."\"></td>";
    	$ret .= "<td valign=top><input type=text size=8 name=RREnd class=jq_datepicker value=\"".($link_row["RecurRide"] == "Y" && $link_row["RREnd"] != '' ? date('m/d/Y',strtotime($link_row["RREnd"])) : '')."\">
    							<br><input type=checkbox name=RROngoing ".($link_row["RecurRide"] == "Y" && $link_row["RROngoing"] == "Y" ? "checked" : "")."> Ongoing
    							<br><br><input type=button class=updateRR value='Update Rec. Ride'></td>";
    }

    return $ret;
}

function get_admin_link_table_headings($include_delete = TRUE) {
    $prefs_spacer = str_repeat('&nbsp;', 13);
    //$ret = '<th>Ride ID</th><th>Rider ID</th><th>&nbsp;</th><th>Arrival Time</th><th>&nbsp;</th><th>Distance</th><th>Minutes</th><th>Price</th><th>From</th><th>To</th><th>Delete</th>';
$ret = <<<HEADERS
<th>Ride ID</th>
<th>Date</th>
<th>Depart Time</th>
<th>&nbsp;</th>
<th>Arrival Time</th>
<th>&nbsp;</th>
<th>From</th>
<th>To</th>
<th>Rider</th>
<th>{$prefs_spacer}Preferences{$prefs_spacer}</th>
<th>Driver</th>
<th>Status</th>
HEADERS;
    if ($include_delete) { $ret .= '<th>Delete</th>'; }

    return $ret;
}

function get_link_as_admin_link_table_row( $link_row, $include_delete = TRUE ) {

    $arrival_times = get_link_arrival_time($link_row);
    $departure_times = get_link_departure_time($link_row);
    $travel_date = get_link_travel_date($link_row);

		// I hate you, inconsistency.
		if(!isset($link_row["T_DestinationID"]) && isset($link_row["ToDestinationID"])) 
			$link_row["T_DestinationID"] = $link_row["ToDestinationID"];
		if(!isset($link_row["F_DestinationID"]) && isset($link_row["FromDestinationID"]))
			$link_row["F_DestinationID"] = $link_row["FromDestinationID"];
		
  	$sql = "select FirstName from users 
  		natural join rider_destination
  		natural join person_name where DestinationID = $link_row[F_DestinationID]";
  	$rs = mysql_fetch_array(mysql_query($sql));		
    $from_address = get_link_destination_table_cell_contents('F_', $link_row, $rs["FirstName"]);
  	$sql = "select FirstName from users 
  		natural join rider_destination
  		natural join person_name where DestinationID = $link_row[T_DestinationID]";
  	$rs = mysql_fetch_array(mysql_query($sql));	
    $to_address = get_link_destination_table_cell_contents('T_', $link_row, $rs["FirstName"]);

    if ($lf_rider_info = get_large_facility_rider_info_for_link($link_row['LinkID'])) {
        $rider_cell = get_lf_rider_person_info_string($lf_rider_info);
    } else {
        $rider_cell = get_rider_person_info_string($link_row['RiderUserID'], TRUE);
    }

    $prefs_string = rider_preferences_to_display_string(get_rider_prefs($link_row['RiderUserID']));
    $adi = @$link_row['AssignedDriverUserID'];
    if(is_null($adi)) $adi = @$link_row['DriverUserID'];
    if(is_null($adi)) {
        $driver_cell = 'None Assigned';
    } else {
        $driver = get_driver_person_info($adi);
        if ($driver === FALSE) {
            $driver_cell = "Unknown Driver ID {$adi}";
        } else {
            $driver_name =  "{$driver['Title']} {$driver['FirstName']} {$driver['MiddleInitial']}. " .
                           "{$driver['LastName']} {$driver['Suffix']}"; 
            $driver_cell = "<a id=\"{$driver['UserID']}\" class=\"User_Redirect\" href=\"account.php\">$driver_name</a> <br />" .
                           "<a href=\"manifest.php?id={$driver['UserID']}&date={$travel_date}\">(manifest)</a>"; 

            if ($driver_mobiles = get_user_phone_numbers_type($driver['UserID'], 'MOBILE')) {
                $driver_cell .= '<br /><span style="font-size: smaller;">'; 
                foreach ($driver_mobiles as $mobile) {
                    $driver_cell .= "{$mobile['PhoneNumber']} ({$mobile['PhoneType'][0]})<br />";
                }

                $driver_cell .= '</span>';
            }
        }
    }
    
    $sql = "SHOW COLUMNS FROM `link` LIKE 'LinkStatus'";
		$result = mysql_query($sql);
		$row = mysql_fetch_array($result);
		$type = $row['Type'];
		preg_match('/enum\((.*)\)$/', $type, $matches);
		$options = str_getcsv($matches[1], ',', "'");
    $linkStatusList = "<select id=Admin_linkStatusList linkid=".$link_row['LinkID'].">";
    for($i = 0; $i < count($options); $i++) 
    	$linkStatusList .= "<option value=".$options[$i].($options[$i]==$link_row["LinkStatus"]?" selected":"").">".$options[$i]."</option>";
    $linkStatusList .= "</select>";
    
    $ret = <<<ROW
<td><a href="admin_schedule_link.php?LinkID={$link_row['LinkID']}">{$link_row['LinkID']}</a></td>
<td>$travel_date</td>
<td nowrap="nowrap">{$departure_times['string']}</td>
<td nowrap="nowrap">{$arrival_times['string']}</td>
<td nowrap="nowrap">{$rider_cell}</td>
<td nowrap="nowrap">$from_address</td>
<td nowrap="nowrap">$to_address</td>

<td>{$prefs_string}</td>
<td nowrap="nowrap">{$driver_cell}</td>
<td>{$linkStatusList}</td>
ROW;

    if ($include_delete) {
        $ret .= '<td><input type="submit" name="DeleteLink[' . $link_row['LinkID'] . 
                ']" value="Delete" /></td>';
    }

    return $ret;
}

function get_link_destination_table_cell_contents($dest_prefix, $link_row, $link = TRUE, $rider_name = '') {

		$cell = "";
    $address2 = (($link_row[$dest_prefix . 'Address2']) ? "{$link_row[$dest_prefix . 'Address2']}<br />" : '');
    
    if ($link) {
        $cell = '<a href="#" onclick="window.open(\'mapquest_map_location.php?id=' . $link_row[$dest_prefix . 'AddressID'] . '\',\'Window1\', \'menubar=no,width=700,height=400,toolbar=no\'); return false;">';
    } 

		// I hate you, inconsistency.
		if(!isset($link_row["T_DestinationID"]) && isset($link_row["ToDestinationID"])) 
			$link_row["T_DestinationID"] = $link_row["ToDestinationID"];
		if(!isset($link_row["F_DestinationID"]) && isset($link_row["FromDestinationID"]))
			$link_row["F_DestinationID"] = $link_row["FromDestinationID"];
		
		if(isset($link_row[$dest_prefix."DestinationID"])) {
	  	$sql = "select FirstName from users 
	  		natural join rider_destination
	  		natural join person_name where DestinationID = ".$link_row[$dest_prefix."DestinationID"];
	  	$rs = mysql_fetch_array(mysql_query($sql));		
	  	$rider_name = $rs["FirstName"];
		}
		
    $cell .= "{$link_row[$dest_prefix . 'Name']}" 
    	. ($link_row[$dest_prefix . 'Name'] == 'Default Home' ? " - $rider_name" : "")
    	. (($link) ? '</a>' : '') . '<br />';
    $cell .= ((is_null($link_row[$dest_prefix . 'DestinationDetail'])) ? 
                '' : "{$link_row[$dest_prefix . 'DestinationDetail']}<br />");
    $cell .= "{$link_row[$dest_prefix . 'Address1']}<br />" . $address2;
    $cell .= "{$link_row[$dest_prefix . 'City']}, {$link_row[$dest_prefix . 'State']}  {$link_row[$dest_prefix . 'ZIP5']}";
    
    if (isset($link_row[$dest_prefix . 'PhoneNumber'])) {
        $cell .= "<br />{$link_row[$dest_prefix . 'PhoneNumber']}";
        $cell .= $link_row[$dest_prefix. 'Ext'] != '' ? ' x'.$link_row[$dest_prefix. 'Ext'] : '';
    }

    return $cell;
}

function get_link_departure_time($orig_link_row, $load_pad = NULL, $drive_pad = NULL) {
	
	$link_row = $orig_link_row;
	//print_r($link_row);
	if(isset($link_row['CustomTransitionID']) && $link_row['CustomTransitionID'] != '' && $link_row['CustomTransitionID'] != null) {
		$sql = "select * from link where CustomTransitionID = $link_row[CustomTransitionID] and CustomTransitionType = 'DRIVER' and FromDestinationID = $link_row[F_DestinationID]";
		//echo $sql."<BR>";
		$r = mysql_query($sql);
		if(mysql_num_rows($r) > 0) {
			$rs = mysql_fetch_array($r);
			//print_r($rs);
			$sql = "SELECT LinkID, RiderUserID, DesiredDepartureTime, DesiredArrivalTime, Distance, EstimatedMinutes, QuotedCents,
					 PrePadding, PostPadding, PercentagePadding, link.FranchiseID,
                   AssignedDriverUserID, VehicleID, NumberOfRiders, LinkStatus, DepartureTimeConfimed, ArrivalTimeConfirmed,
                   FromDestinationID AS F_DestinationID, ToDestinationID AS T_DestinationID,
                   F_Dest.Name AS F_Name, F_Dest.IsPublic AS F_Public,
                   F_Dest.DestinationDetail AS F_DestinationDetail,
                   F_Address.AddressID AS F_AddressID, F_Address.Address1 AS F_Address1, 
                   F_Address.Address2 AS F_Address2, F_Address.City AS F_City, 
                   F_Address.State AS F_State, F_Address.ZIP5 AS F_ZIP5, 
                   F_Address.ZIP4 AS F_ZIP4,
                   F_Address.VerifySource AS F_VerifySource,
                   F_Address.Latitude as F_Latitude,
                   F_Address.Longitude as F_Longitude,
                   F_Phone.PhoneNumber AS F_PhoneNumber,
                   T_Dest.Name AS T_Name, T_Dest.IsPublic AS T_IsPublic,
                   T_Dest.DestinationDetail AS T_DestinationDetail,
                   T_Address.AddressID AS T_AddressID, T_Address.Address1 AS T_Address1, 
                   T_Address.Address2 AS T_Address2,
                   T_Address.City AS T_City, T_Address.State AS T_State, 
                   T_Address.ZIP5 AS T_ZIP5, T_Address.ZIP4 AS T_ZIP4,
                   T_Address.VerifySource as T_VerifySource,
                   T_Address.Latitude as T_Latitude,
                   T_Address.Longitude as T_Longitude,
                   T_Phone.PhoneNumber AS T_PhoneNumber, T_Phone.Ext AS T_Ext,
                   RecurRide, RRFrequency, RRNumber, RRStatus, RRStart, RREnd, RROngoing, LinkNote, LinkFlexFlag, Last_Changed_By, Last_Changed_Date, Created_By, Created_Date, CustomTransitionID
            FROM (link, destination AS F_Dest, destination AS T_Dest, 
                 address AS F_Address, address AS T_Address)
                 LEFT JOIN phone AS F_Phone ON F_Dest.PhoneID = F_Phone.PhoneID
                 LEFT JOIN phone AS T_Phone ON T_Dest.PhoneID = T_Phone.PhoneID
                 WHERE link.FromDestinationID = F_Dest.DestinationID AND
                  F_Dest.AddressID = F_Address.AddressID AND
                  link.ToDestinationID = T_Dest.DestinationID AND
                  T_Dest.AddressID = T_Address.AddressID
                  and link.LinkID = $rs[LinkID]";
       $rr = mysql_query($sql);
       $link_row = mysql_fetch_array($rr);
		}
	}
	
	$padding_percentage = 0;
	if(isset($link_row['PercentagePadding']))
		$padding_percentage = $link_row['PercentagePadding'];
		
	$estimated_minutes = get_link_estimated_minutes($link_row);

  if(@$link_row['DesiredDepartureTime'] != "") {
		$departure_time_t = strtotime($link_row['DesiredDepartureTime']);
	} else {
		$arrival_time_t = strtotime($link_row['DesiredArrivalTime']);
		
		if($load_pad === NULL)
			$load_pad = $link_row['PrePadding'];
		if($drive_pad === NULL)
			$drive_pad = $link_row['PostPadding'];

    	$departure_time_t = $arrival_time_t - (( ($padding_percentage * ($load_pad + $drive_pad)) + $estimated_minutes) * 60);
	}
	
    $departure_time = date('g:i A', $departure_time_t);
    $mysql_date_time = date('Y-m-d H:i:s', $departure_time_t);

    return array( 'string' => $departure_time,
                  'time_t' => $departure_time_t,
                  'mysql'  => $mysql_date_time);
}

function get_link_estimated_minutes($link_row){
	
	$weather_delay = get_weather_time_delay($link_row['FranchiseID'], $link_row['DesiredArrivalTime']);
	$daily_delay = get_daily_delay($link_row['FranchiseID'], $link_row['DesiredArrivalTime']);
	return ($link_row['EstimatedMinutes'] * $daily_delay * $weather_delay);
}

function get_link_arrival_time($orig_link_row, $selected_col = 'DesiredArrivalTime') {
	
	$link_row = $orig_link_row;

	if(isset($link_row['CustomTransitionID']) && $link_row['CustomTransitionID'] != '' && $link_row['CustomTransitionID'] != null) {
		$sql = "select * from link where CustomTransitionID = $link_row[CustomTransitionID] and CustomTransitionType = 'DRIVER' and ToDestinationID = $link_row[T_DestinationID]";
		#echo $sql."<BR>";
		$r = mysql_query($sql);
		if(mysql_num_rows($r) > 0) {
			$rs = mysql_fetch_array($r);
			#print_r($rs);
			$sql = "SELECT LinkID, RiderUserID, DesiredDepartureTime, DesiredArrivalTime, Distance, EstimatedMinutes, QuotedCents,
					 PrePadding, PostPadding, PercentagePadding, link.FranchiseID, ReportedArrivalTime,
                   AssignedDriverUserID, VehicleID, NumberOfRiders, LinkStatus, DepartureTimeConfimed, ArrivalTimeConfirmed,
                   FromDestinationID AS F_DestinationID, ToDestinationID AS T_DestinationID,
                   F_Dest.Name AS F_Name, F_Dest.IsPublic AS F_Public,
                   F_Dest.DestinationDetail AS F_DestinationDetail,
                   F_Address.AddressID AS F_AddressID, F_Address.Address1 AS F_Address1, 
                   F_Address.Address2 AS F_Address2, F_Address.City AS F_City, 
                   F_Address.State AS F_State, F_Address.ZIP5 AS F_ZIP5, 
                   F_Address.ZIP4 AS F_ZIP4,
                   F_Address.VerifySource AS F_VerifySource,
                   F_Address.Latitude as F_Latitude,
                   F_Address.Longitude as F_Longitude,
                   F_Phone.PhoneNumber AS F_PhoneNumber,
                   T_Dest.Name AS T_Name, T_Dest.IsPublic AS T_IsPublic,
                   T_Dest.DestinationDetail AS T_DestinationDetail,
                   T_Address.AddressID AS T_AddressID, T_Address.Address1 AS T_Address1, 
                   T_Address.Address2 AS T_Address2,
                   T_Address.City AS T_City, T_Address.State AS T_State, 
                   T_Address.ZIP5 AS T_ZIP5, T_Address.ZIP4 AS T_ZIP4,
                   T_Address.VerifySource as T_VerifySource,
                   T_Address.Latitude as T_Latitude,
                   T_Address.Longitude as T_Longitude,
                   T_Phone.PhoneNumber AS T_PhoneNumber, T_Phone.Ext AS T_Ext,
                   RecurRide, RRFrequency, RRNumber, RRStatus, RRStart, RREnd, RROngoing, LinkNote, LinkFlexFlag, Last_Changed_By, Last_Changed_Date, Created_By, Created_Date, CustomTransitionID
            FROM (link, destination AS F_Dest, destination AS T_Dest, 
                 address AS F_Address, address AS T_Address)
                 LEFT JOIN phone AS F_Phone ON F_Dest.PhoneID = F_Phone.PhoneID
                 LEFT JOIN phone AS T_Phone ON T_Dest.PhoneID = T_Phone.PhoneID
                 WHERE link.FromDestinationID = F_Dest.DestinationID AND
                  F_Dest.AddressID = F_Address.AddressID AND
                  link.ToDestinationID = T_Dest.DestinationID AND
                  T_Dest.AddressID = T_Address.AddressID
                  and link.LinkID = $rs[LinkID]";
       $rr = mysql_query($sql);
       $link_row = mysql_fetch_array($rr);
		}
	}
		
	if(isset($link_row['PercentagePadding']))
		$padding_percentage = $link_row['PercentagePadding'];
		
	$estimated_minutes = get_link_estimated_minutes($link_row);

  if(isset($link_row['DesiredDepartureTime']) && $link_row['DesiredDepartureTime'] != '' && $selected_col == "DesiredArrivalTime"){
		$arrival_time_t = strtotime($link_row['DesiredDepartureTime']) + (( ($padding_percentage * ($link_row['PrePadding'] + $link_row['PostPadding'])) + $estimated_minutes ) * 60);
	} else {
		$arrival_time_t = $link_row[$selected_col] != '' ? strtotime($link_row[$selected_col]) : 0;
	}
	
    $arrival_time = date('g:i A', $arrival_time_t);
    $mysql_date_time = date('Y-m-d H:i:s', $arrival_time_t);

    return array( 'string' => $arrival_time,
                  'time_t' => $arrival_time_t,
                  'mysql'  => $mysql_date_time);
}

function get_link_travel_date($link_row) {
    $arrival_times = get_link_arrival_time($link_row);
    $travel_date = date('Y-m-d', $arrival_times['time_t']);

    return $travel_date;
}

/**
 * Returns the next user schedulable link time as an associative array.  
 * If the current time is Day N, a ride may be scheduled for Day N+2.
 * (Removed Day N+1 scheduling; check SVN to get it back)
 * @return time as associative array (keys Year Month Day Hour)
 */
function get_next_user_schedulable_link_time( $userid = -1 ) {
//    $orig_tz = date_default_timezone_get();
//    // date_default_timezone_set('America/Chicago');
//
//    $time_struct = localtime(time(), TRUE);  // TODO:  TZ aware - more general solution
//
//    // If it is before noon, can schedule tomorrow.  If it is NOT before noon, 
//    // can schedule the day after tomorrow.
//    $days_padding = (date('G') < 12) ? 1 : 2;
//
//    $next_timestamp = mktime( 0, 0, 0, $time_struct['tm_mon'] + 1, 
//                              $time_struct['tm_mday'] + $days_padding, 
//                              $time_struct['tm_year'] + 1900);

		$csd = check_scheduable_date(date('Y-n-j H:m',time()),date('Y-n-j H:m',time()+1),$userid);
		//print_r($csd);
		
		
		$next_timestamp = strtotime($csd['NextDate']);

    $next_time = array( 'Year' => date('Y', $next_timestamp),
                        'Month' => date('n', $next_timestamp),
                        'Day' => date('j', $next_timestamp),
                        'Date' => date('m/d/Y', $next_timestamp),
    		    		'Hour' => date('H', $next_timestamp) ,
                        'time_t' => $next_timestamp );
   
//    date_default_timezone_set($orig_tz);
    return($next_time);
}

function delete_link($link_id) {
    $safe_link_id = mysql_real_escape_string($link_id);

    $sql = "DELETE FROM link WHERE LinkID = $safe_link_id";
    
    $result = mysql_query($sql);
    if ($result) {
        $actor_user = get_current_user_id();
        rc_log(PEAR_LOG_NOTICE, "User $actor_user deleted link $link_id.", 'rc_link_log.log');
        return TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not delete link $link_id", $sql);
        return FALSE;
    }

}

function get_links_before_date($franchise, $tz_offset_hours ,$as_of_date ) {
	// TODO:  Make franchise-safe?
	// TODO:  Consider creating the date as a string here.
	$safe_franchise = mysql_real_escape_string($franchise);
	$safe_tz_offset = mysql_real_escape_string($tz_offset_hours) . ':00';

	$sql_safe_date = date('Y-m-d',$as_of_date) . ' 23:59';
	if ( date('Y-m-d',strtotime("-".$tz_offset_hours." hours")) <=
			date('Y-m-d',$as_of_date))
	{
		$sql_safe_date = date('Y-m-d hh:MM', strtotime("-".$tz_offset_hours." hours"));
	}
	 
	$links = array();

	$sql = "SELECT *
	FROM link
	WHERE FranchiseID = $safe_franchise AND
	'$sql_safe_date' > DesiredArrivalTime
	ORDER BY LinkID ASC";

	$result = mysql_query($sql);
	if ($result) {
	while ($row = mysql_fetch_array($result)) {
	$links[] = $row;
	}
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
		"Could not get past links", $sql);
		}

		return $links;
		}



function get_past_links($franchise, $tz_offset_hours = 0 ,$if_yesterday = false ) {
    // TODO:  Make franchise-safe?
    // TODO:  Consider creating the date as a string here.
	$safe_franchise = mysql_real_escape_string($franchise);
    $safe_tz_offset = mysql_real_escape_string($tz_offset_hours) . ':00';
    
    $yesterday = date('YY-m-d',strtotime("-1 day",strtotime("-".$tz_offset_hours." hours")));
    
    $links = array();
    if ($if_yesterday)
    {
    $sql = "SELECT *
            FROM link
            WHERE FranchiseID = $safe_franchise AND 
                  ( date(CONVERT_TZ(NOW(), CONCAT(TIMESTAMPDIFF(HOUR, UTC_TIMESTAMP(), NOW()), ':00'), '$safe_tz_offset')) > DesiredArrivalTime)
            ORDER BY LinkID ASC";
    }
    else
    {
    $sql = "SELECT *
            FROM link
            WHERE FranchiseID = $safe_franchise AND DesiredArrivalTime < CONVERT_TZ(NOW(), CONCAT(TIMESTAMPDIFF(HOUR, UTC_TIMESTAMP(), NOW()), ':00'), '$safe_tz_offset')
            ORDER BY LinkID ASC";
    }
    $result = mysql_query($sql);
    if ($result) {
        while ($row = mysql_fetch_array($result)) {
            $links[] = $row;
        }
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get past links", $sql);
    }

    return $links;
}

function get_past_link($link_id) {
    $safe_link_id = mysql_real_escape_string($link_id);

    $sql = "SELECT * FROM link_history
            WHERE LinkID = $safe_link_id";
    
    $result = mysql_query($sql);
    if ($result) {
        $row = mysql_fetch_array($result);
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get history link for ID $link_id", $sql);
        $row = FALSE;
    }

    return $row;
}

function move_link_to_history($link_id) {
    $safe_link_id = mysql_real_escape_string($link_id);

    // If the LinkStatus is set to something besides UNKNOWN, use that.
    // If the LinkStatus is set to UNKNOWN, infer based on AssignedDriverUserID.
    $sql = "INSERT INTO link_history
                (LinkID, RiderUserID, FranchiseID, FromDestinationID, ToDestinationID, DesiredDepartureTime,
                 DesiredArrivalTime, Distance, EstimatedMinutes, QuotedCents, DriverUserID, PrePadding, PostPadding, PercentagePadding,
                 VehicleID, NumberOfRiders, ReportedArrivalTime, LinkStatus, CustomTransitionID, CustomTransitionType, Last_Changed_By, Last_Changed_Date, LinkNote, LinkFlexFlag, Created_By, Created_Date)
            SELECT LinkID, RiderUserID, FranchiseID, FromDestinationID, ToDestinationID, DesiredDepartureTime,
                   DesiredArrivalTime, Distance, EstimatedMinutes, QuotedCents, AssignedDriverUserID, PrePadding, PostPadding, PercentagePadding,
                   VehicleID, NumberOfRiders, ReportedArrivalTime,
                   CASE WHEN (LinkStatus != 'UNKNOWN') THEN LinkStatus
                        WHEN (NOT ISNULL(AssignedDriverUserID)) THEN 'COMPLETE'
                        ELSE 'NOTSCHEDULED' END, CustomTransitionID, CustomTransitionType
                   , Case WHEN Last_Changed_By = 0 THEN $_SESSION[UserID] else Last_Changed_By END
                   , Case WHEN Last_Changed_Date = '0000-00-00 00:00:00' THEN CURRENT_TIMESTAMP ELSE Last_Changed_Date END
                   , LinkNote, LinkFlexFlag, Created_By, Created_Date
            FROM link
            WHERE LinkID = $safe_link_id";

		$insert_recur_link_sql = '';
		$rs = mysql_fetch_array(mysql_query("select * from link where LinkID = $safe_link_id"));

		if($rs["RecurRide"] == "Y") {
			$targetArrivalTime = time();
			$targetDepartTime = time();
			if($rs["RRFrequency"] == "Weekly") {
				$targetDepartureTime = strtotime("+".(7*$rs["RRNumber"])." days",strtotime($rs["DesiredDepartureTime"]));
				$targetArrivalTime = strtotime("+".(7*$rs["RRNumber"])." days",strtotime($rs["DesiredArrivalTime"]));
			}
			if($rs["RRFrequency"] == "Last") {
				if(date('l',$rs["DesiredArrivalTime"]) != date('l',strtotime(date('Y-m-t',utility_add_one_month(strtotime($rs["DesiredArrivalTime"])))))) {
					$targetDepartureTime = strtotime("last ".date('l',$rs["DesiredDepartureTime"]),utility_add_one_month(strtotime(date('Y-m-t',strtotime($rs["DesiredDepartureTime"])))));
					$targetArrivalTime = strtotime("last ".date('l',$rs["DesiredArrivalTime"]),utility_add_one_month(strtotime(date('Y-m-t',strtotime($rs["DesiredArrivalTime"])))));		
				} else {
					$targetDepartureTime = strtotime(date('Y-m-t',utility_add_one_month(strtotime($rs["DesiredDepartureTime"]))));
					$targetArrivalTime = strtotime(date('Y-m-t',utility_add_one_month(strtotime($rs["DesiredArrivalTime"]))));
				}
			}
			if($rs["RRFrequency"] == "Date") {
				$targetDepartureTime = strtotime(
					$rs["RRNumber"] > date('t',utility_add_one_month(strtotime($rs["DesiredDepartureTime"]))) ?
						date('Y-m-t',utility_add_one_month(strtotime($rs["DesiredDepartureTime"]))) :
						date('Y-m-',utility_add_one_month(strtotime($rs["DesiredDepartureTime"]))).$rs["RRNumber"]
						);
				$targetArrivalTime = strtotime(
					$rs["RRNumber"] > date('t',utility_add_one_month(strtotime($rs["DesiredArrivalTime"]))) ?
						date('Y-m-t',utility_add_one_month(strtotime($rs["DesiredArrivalTime"]))) :
						date('Y-m-',utility_add_one_month(strtotime($rs["DesiredArrivalTime"]))).$rs["RRNumber"]
						);
			}
			if($rs["RRFrequency"] == "Week") {
				$nums = array( "1" => "first", "2" => "second", "3" => "third", "4" => "fourth", "5" => "fifth");
				$targetDepartureTime = strtotime($nums[$rs["RRNumber"]]." ".date('l',$rs["DesiredDepartureTime"])." of ".date('F Y',utility_add_one_month(strtotime($rs["DesiredDepartureTime"]))));
				$targetArrivalTime = strtotime($nums[$rs["RRNumber"]]." ".date('l',$rs["DesiredArrivalTime"])." of ".date('F Y',utility_add_one_month(strtotime($rs["DesiredArrivalTime"]))));
			}
			
			$link_price = get_context_link_price($rs, $rs["Distance"], $rs["FranchiseID"], 
             $rs['FromDestinationID'], $rs['ToDestinationID'],
             date('Y-m-d',$targetArrivalTime));
             
			$update_sql = "INSERT INTO `link` (`RiderUserID`, `FromDestinationID`, `ToDestinationID`, `DesiredDepartureTime`, `DesiredArrivalTime`, `ReportedArrivalTime`, `Distance`, `EstimatedMinutes`, `PrePadding`, `PostPadding`, `PercentagePadding`, `QuotedCents`, `FranchiseID`, `NumberOfRiders`, `LinkStatus`, `ArrivalTimeConfirmed`, `DepartureTimeConfimed`, `LinkNote`, `LinkFlexFlag`, `CustomTransitionID`, `CustomTransitionType`, `DriverConfirmed`, `Last_Changed_By`, `RecurRide`, `RRFrequency`, `RRNumber`, `RRStatus`, `RRStart`, `RREnd`, `RROngoing`, Created_By, Created_Date)
				SELECT `RiderUserID`, `FromDestinationID`, `ToDestinationID`, '".date("Y-m-d H:i:s",$targetDepartureTime)."', '".date("Y-m-d H:i:s",$targetArrivalTime)."', `ReportedArrivalTime`, `Distance`, `EstimatedMinutes`, `PrePadding`, `PostPadding`, `PercentagePadding`,"
				.$link_price["RiderShare"]
				.", `FranchiseID`, `NumberOfRiders`, 'UNKNOWN', 'N', 'N', NULL, 0, NULL, NULL, 'No', 0, `RecurRide`, `RRFrequency`, `RRNumber`, `RRStatus`, `RRStart`, `RREnd`, `RROngoing`, Created_By, Created_Date from link where LinkID =  $safe_link_id";

			mysql_query($update_sql) or die(mysql_error());
		}

    
    $delete_sql = "DELETE FROM link WHERE LinkID = $safe_link_id";
    $result = mysql_query($sql) or die(mysql_error());
    if ($result) {
        $delete_result = mysql_query($delete_sql);

        if (!$delete_result) {
            rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                            "Could not remove link $link_id after adding to history", $sql);
            $ret = FALSE;
        } else {
            $ret = TRUE;
            $actor_user = get_current_user_id();
            rc_log(PEAR_LOG_NOTICE, "User $actor_user moved link $link_id to history.", 'rc_link_log.log');
        }
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not move $link_id to link_history", $sql);
        $ret = FALSE;
    }

    return $ret;
}

function get_rider_links_on_same_day($link_id) {
    $safe_link_id = mysql_real_escape_string($link_id);
    $sql = "SELECT search_link.*
            FROM link AS search_link, link AS reference_link
            WHERE reference_link.LinkID = $link_id AND
                  reference_link.RiderUserID = search_link.RiderUserID AND
                  DATE(search_link.DesiredArrivalTime) = DATE(reference_link.DesiredArrivalTime)";

    $result = mysql_query($sql);
    if ($result) {
        $links = array();
        while ($row = mysql_fetch_array($result)) {
            $links[] = $row;
        }
        return $links;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get rider links on same day as $link_id", $sql);
        return FALSE;
    }
}

function set_completed_link_reported_arrival_time($link_id, $time_str) {
    $safe_link_id = mysql_real_escape_string($link_id);
    $safe_time = mysql_real_escape_string($time_str);

    $sql = "UPDATE link_history SET ReportedArrivalTime = '$safe_time'
            WHERE LinkID=$safe_link_id";

    $result = mysql_query($sql);
    if ($result) {
        $ret = TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not set reported arrival time for $link_id to $time_str", $sql);
        $ret = FALSE;
    }

    return $ret;
}

function set_active_link_reported_arrival_time($link_id, $time_str) {
    $safe_link_id = mysql_real_escape_string($link_id);
    $safe_time = mysql_real_escape_string($time_str);
	
    $sql = "UPDATE link SET ReportedArrivalTime = '$safe_time'
            WHERE LinkID=$safe_link_id";
	
    $result = mysql_query($sql);
    if ($result) {
        $ret = TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not set reported arrival time for active $link_id to $time_str", $sql);
        $ret = FALSE;
    }

    return $ret;
}



function set_completed_link_driver_id($link_id, $driver_user_id) {
    rc_log(PEAR_LOG_NOTICE, __FUNCTION__ . ' called', 'rc_rider_driver.log');
    return set_completed_link_driver_user_id($link_id, $driver_user_id); 
}

function set_completed_link_driver_user_id($link_id, $driver_user_id) {
    $safe_link_id = mysql_real_escape_string($link_id);
    $safe_driver_user_id = (is_null($driver_user_id) ? 'NULL' :
                      "'" . mysql_real_escape_string($driver_user_id) . "'");

    $sql = "UPDATE link_history SET DriverUserID = $safe_driver_user_id
            WHERE LinkID=$safe_link_id";

    $result = mysql_query($sql);
    if ($result) {
        $ret = TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not set driver ID for completed link $link_id to $driver_user_id", $sql);
        $ret = FALSE;
    }

    return $ret;
}

function set_link_driver_user_id($link_id, $driver_user_id) {
    $safe_link_id = mysql_real_escape_string($link_id);
    $safe_driver_user_id = (is_null($driver_user_id) ? 'NULL' :
                      "'" . mysql_real_escape_string($driver_user_id) . "'");

    $sql = "UPDATE link SET AssignedDriverUserID = $safe_driver_user_id
            WHERE LinkID=$safe_link_id";

    $result = mysql_query($sql);
    if ($result) {
        $ret = TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not set driver ID for link $link_id to $driver_user_id", $sql);
        $ret = FALSE;
    }

    return $ret;
}


function set_completed_link_status($link_id, $new_status) {
    $safe_link_id = mysql_real_escape_string($link_id);
    $safe_status = mysql_real_escape_string($new_status);

    $sql = "UPDATE link_history t1 SET LinkStatus = '$safe_status', Last_Changed_By = $_SESSION[UserID], Last_Changed_Date = CURRENT_TIMESTAMP
            WHERE LinkID=$safe_link_id";

    $result = mysql_query($sql);
    if ($result) {
        $ret = TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not set status for completed link $link_id to $new_status", $sql);
        $ret = FALSE;
    }

    return $ret;
}

function set_active_link_status($link_id, $new_status) {
	if($new_status == NULL || $new_status == '')
		return;
    $safe_link_id = mysql_real_escape_string($link_id);
    $safe_status = mysql_real_escape_string($new_status);

    $sql = "UPDATE link SET LinkStatus = '$safe_status', Last_Changed_By = $_SESSION[UserID], Last_Changed_Date = CURRENT_TIMESTAMP
            WHERE LinkID=$safe_link_id";
    $result = mysql_query($sql);
    if ($result) {
        $ret = TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not set status for active link $link_id to $new_status", $sql);
        $ret = FALSE;
    }

    return $ret;
}

/**
 * Gets a list of past (history) links for a rider.
 * @param $rider_user_id ID of rider
 * @param $limit_count maximum number of rows to return, or FALSE for all 
 * @return array of hashes (keys from link_history table), FALSE on error
 */
function get_rider_past_links( $rider_user_id, $limit_count = FALSE, $start_count = false, $destination_list = array(), $status_list = array() ) {
    $links = array();
   
    $safe_rider_user_id = mysql_real_escape_string($rider_user_id);
    $limit_clause = ($limit_count === FALSE && $start_count === FALSE) ? '' : 'LIMIT ' . mysql_real_escape_string($limit_count) . ", " . mysql_real_escape_string($start_count);

    $sql = "SELECT LinkID, RiderUserID, DesiredDepartureTime, DesiredArrivalTime, EstimatedMinutes, link_history.FranchiseID, Distance, DriverUserID,
                   LinkStatus, NumberOfRiders, ReportedArrivalTime, QuotedCents, PrePadding, PostPadding, PercentagePadding,
                   FromDestinationID AS F_DestinationID, ToDestinationID AS T_DestinationID,
                   F_Dest.Name AS F_Name, F_Dest.IsPublic AS F_Public,
                   F_Dest.DestinationDetail AS F_DestinationDetail,
                   F_Address.AddressID AS F_AddressID, F_Address.Address1 AS F_Address1, 
                   F_Address.Address2 AS F_Address2, F_Address.City AS F_City, 
                   F_Address.State AS F_State, F_Address.ZIP5 AS F_ZIP5, 
                   F_Address.ZIP4 AS F_ZIP4,
                   F_Phone.PhoneNumber AS F_PhoneNumber, F_Phone.Ext as F_Ext,
                   T_Dest.Name AS T_Name, T_Dest.IsPublic AS T_IsPublic,
                   T_Dest.DestinationDetail AS T_DestinationDetail,
                   T_Address.AddressID AS T_AddressID, T_Address.Address1 AS T_Address1, 
                   T_Address.Address2 AS T_Address2,
                   T_Address.City AS T_City, T_Address.State AS T_State, 
                   T_Address.ZIP5 AS T_ZIP5, T_Address.ZIP4 AS T_ZIP4,
                   T_Phone.PhoneNumber AS T_PhoneNumber, T_Phone.Ext as T_Ext,
                   link_history.Last_Changed_By, link_history.Last_Changed_Date, LinkNote, LinkFlexFlag, Created_By, Created_Date
            FROM (link_history, destination AS F_Dest, destination AS T_Dest, 
                 address AS F_Address, address AS T_Address)
                 LEFT JOIN phone AS F_Phone ON F_Dest.PhoneID = F_Phone.PhoneID
                 LEFT JOIN phone AS T_Phone ON T_Dest.PhoneID = T_Phone.PhoneID
                 WHERE 
                  RiderUserID = $safe_rider_user_id AND
                  (link_history.CustomTransitionType != 'DRIVER' OR 
                  link_history.CustomTransitionType IS NULL) AND
                  link_history.FromDestinationID = F_Dest.DestinationID AND
                  F_Dest.AddressID = F_Address.AddressID AND
                  link_history.ToDestinationID = T_Dest.DestinationID AND
                  T_Dest.AddressID = T_Address.AddressID"
                  .( count($destination_list) > 0 ? " and (F_Dest.DestinationGroupID in (".join(',',$destination_list).") or T_Dest.DestinationGroupID in (".join(',',$destination_list).")) " : "")
                  .( count($status_list) > 0 ? " and link_history.LinkStatus in ('".join("','",$status_list)."') " : "" )
                  ."
            ORDER BY DATE(DesiredArrivalTime) DESC,
                     DesiredArrivalTime ASC  $limit_clause";
		#echo $sql;
    $result = mysql_query($sql) or die(mysql_error());
    if ($result) {
        while ($row = mysql_fetch_array($result)) {
            $links[] = $row;
        }
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get past links for rider $rider_user_id", $sql);
    }

    return $links;
}

function get_facility_past_links( $facility_id, $limit_count = FALSE, $start_count = false ) {
    $links = array();

    $limit_clause = ($limit_count === FALSE && $start_count === FALSE) ? '' : 'LIMIT ' . mysql_real_escape_string($limit_count) . ", " . mysql_real_escape_string($start_count);

    $sql = "SELECT LinkID, RiderUserID, DesiredDepartureTime, DesiredArrivalTime, EstimatedMinutes, link_history.FranchiseID, Distance, DriverUserID,
                   LinkStatus, NumberOfRiders, ReportedArrivalTime, QuotedCents, PrePadding, PostPadding, PercentagePadding,
                   FromDestinationID AS F_DestinationID, ToDestinationID AS T_DestinationID,
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
                   link_history.Last_Changed_By, link_history.Last_Changed_Date,
                   person_name.FirstName, person_name.LastName
            FROM (link_history, destination AS F_Dest, destination AS T_Dest, 
                 address AS F_Address, address AS T_Address, care_facility_user cfu, users as rider, person_name)
                 LEFT JOIN phone AS F_Phone ON F_Dest.PhoneID = F_Phone.PhoneID
                 LEFT JOIN phone AS T_Phone ON T_Dest.PhoneID = T_Phone.PhoneID
                 WHERE 
                  RiderUserID = cfu.UserID and cfu.CareFacilityID = $facility_id AND
                  (link_history.CustomTransitionType != 'DRIVER' OR 
                  link_history.CustomTransitionType IS NULL) AND
                  link_history.FromDestinationID = F_Dest.DestinationID AND
                  F_Dest.AddressID = F_Address.AddressID AND
                  link_history.ToDestinationID = T_Dest.DestinationID AND
                  T_Dest.AddressID = T_Address.AddressID AND
                  rider.UserID = RiderUserID and rider.PersonNameID = person_name.PersonNameID
                  AND ( (not cfu.DisconnectDate is null AND cfu.DisconnectDate > DesiredArrivalTime )
                  			OR cfu.DisconnectDate is null )
            ORDER BY DATE(DesiredArrivalTime) DESC,
                     DesiredArrivalTime ASC  $limit_clause";
    $result = mysql_query($sql) or die(mysql_error());
    if ($result) {
        while ($row = mysql_fetch_array($result)) {
            $links[] = $row;
        }
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get past links for rider $rider_user_id", $sql);
    }

    return $links;
}

function get_rider_total_past_links($rider_id, $destination_list = array(), $status_list = array() ){
	$safe_rider_id = mysql_real_escape_string($rider_id);
	$sql = "SELECT COUNT(*) as Count FROM link_history WHERE RiderUserID = $safe_rider_id AND (link_history.CustomTransitionType != 'DRIVER' OR 
                  link_history.CustomTransitionType IS NULL)";
  if(count($destination_list) > 0 || count($status_list) > 0)
	$sql = "SELECT COUNT(*) as Count FROM link_history, destination F_Dest, destination T_Dest WHERE RiderUserID = $safe_rider_id AND (link_history.CustomTransitionType != 'DRIVER' OR 
                  link_history.CustomTransitionType IS NULL)
                  and FromDestinationId = F_Dest.DestinationID and ToDestinationID = T_Dest.DestionationID "
                  .( count($destination_list) > 0 ? " and (F_Dest.DestinationGroupID in (".join(',',$destination_list).") or T_Dest.DestinationGroupID in (".join(',',$destination_list).")) " : "")
                  .( count($status_list) > 0 ? " and link_history.LinkStatus in ('".join("','",$status_list)."') " : "" );
	$result = mysql_query($sql);
	
	if($result){
		$count = mysql_fetch_array($result);
		$count = $count['Count'];
		return $count;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get total past links for rider $rider_user_id", $sql);
	}
}
function get_facility_total_past_links($facility_id){
	$sql = "SELECT LinkID FROM link_history, care_facility_user cfu WHERE RiderUserID = cfu.UserID and cfu.CareFacilityID = $facility_id AND (link_history.CustomTransitionType != 'DRIVER' OR 
                  link_history.CustomTransitionType IS NULL)";
	$result = mysql_query($sql);
	
	if($result){
		return mysql_num_rows($result);
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get total past links for rider $rider_user_id", $sql);
	}
}
function estimate_link_pickup_time( $link_id){
    if (is_null($link_id)) {
		$franchise = get_current_user_franchise();
		if(user_has_role(get_affected_user_id(), $franchise, 'Driver'))
			return "No Drive Scheduled";
		return "No Ride Scheduled";
    }

	$link = get_link($link_id);
	if($link === FALSE) {
		if(user_has_role(get_affected_user_id(),'Driver'))
			return "No Drive Scheduled";
		return "No Ride Scheduled";
    }
    $depart_times = get_link_departure_time($link);
    
    if ($twentyfour_hour) {
        $formatted = date('n/j/Y G:i:00', $depart_times['time_t']);
    } else {
        $formatted = date('n/j/Y g:i:00 A', $depart_times['time_t']);
    }

    return $formatted;
}

function estimate_next_scheduled_link_pickup_time($user_id=0) {
  $query = "select LinkID from link where RiderUserID=".(int)$user_id." and DesiredArrivalTime>now() order by DesiredArrivalTime asc";
  //echo $query;
  $result = mysql_query($query);
  $link_id = null;
  if (mysql_num_rows($result)>0) {
    $tmp = mysql_fetch_assoc($result);
	$link_id = $tmp['LinkID'];
  }
  //echo $link_id;
  return estimate_link_pickup_time($link_id);
}
/*
 * This function gets the scheduled links in an array formatted for the plan_ride.php page.
 */
function get_links_edit_array($link_id, $rider_user_id = NULL,  $table = 'link' ){
	if($rider_user_id == NULL)
		$rider_user_id = get_affected_user_id();
	$ride = get_ride_links_array($rider_user_id, null, $link_id, $table);
	if(count($ride) < 1)
		return FALSE;
	
	$ride = $ride[0];
	$date = get_date($ride[0]['DesiredArrivalTime']);
	$links = array();
	$links['TravelMonth'] = $date['Month'];
	$links['TravelDay'] = $date['Day'];
	$links['TravelYear'] = $date['Year'];
	$links['TravelDate'] = $date['Date'];
	$links['NumberOfRiders'] = array();
	$links['DepartureTimeConfirmed'] = array();
	$links['ArrivalTimeConfirmed'] = array();
	$links['Location'] = array();
	$links['LocationText'] = array();
	$links['LocationType'] = array();
	$links['destination_selector'] = array();
	$links['DepartureTimeType'] = array();
	$links['hour'] = array('Depart' => array(),
						   'Arrive' => array() );
	$links['minute'] = array('Depart' => array(),
						     'Arrive' => array() );
	$links['AM_PM'] = array('Depart' => array(),
						    'Arrive' => array() );
	$links['ArrivalTimeType'] = array();
	$links['LocationTime'] = array();
	$links['TransitionNote'] = array();
	$links['TransitionFlexTimeFlag'] = array();
	$links['PrePadding'] = array();
	$links['PostPadding'] = array();
	$links['Last_Changed_By'] = $ride[0]['Last_Changed_By'];
	$links['Last_Changed_Date'] = $ride[0]['Last_Changed_Date'];
	$links['Created_By'] = $ride[0]['Created_By'];
	$links['Created_Date'] = $ride[0]['Created_Date'];
	
	$last_destination = "String";
	$array_PH = 0; //array place holder
	for($i = 0; $i < count($ride); $i++){
		if($links['NumberOfRiders'] < $ride[$i]['NumberOfRiders'])
			$links['NumberOfRiders'] = $ride[$i]['NumberOfRiders'];
		if($ride[$i]['FromDestinationID'] != $last_destination){
			$destination = get_destination($ride[$i]['FromDestinationID']);
			$time = get_time($ride[$i]['DesiredArrivalTime']);
			$links['Location'][$array_PH] = $ride[$i]['FromDestinationID'];				
			$links['LocationText'][$array_PH] = $destination['Name'];
			$links['LocationType'][$array_PH] = rider_has_destination(get_user_rider_id( get_affected_user_id()), $ride[$i]['FromDestinationID']) ? 'Favorite' : 'Franchise';
			$links['destination_selector'][$array_PH] = "-{$ride[$i]['FromDestinationID']}";
			$links['DepartureTimeType'][$array_PH] = "NotConcern";
			$links['ArrivalTimeConfirmed'][$array_PH] = $ride[$i]['ArrivalTimeConfimed'] == 'Y' ? TRUE : FALSE;
			$links['hour']['Depart'][$array_PH] = 9;
			$links['hour']['Arrive'][$array_PH] = ($time['AM_PM'] == 'PM') ? ($time['Hour'] - 12) : $time['Hour'];
			$links['minute']['Depart'][$array_PH] = 0;
			$links['minute']['Arrive'][$array_PH] = $time['Minute'];
			$links['AM_PM']['Depart'][$array_PH] = 'AM';
			$links['AM_PM']['Arrive'][$array_PH] = $time['AM_PM'];
			$links['ArrivalTimeType'][$array_PH] = 'NotConcern';
			$links['TransitionNote'][$array_PH] = $ride[$i]['LinkNote'];
			$links['TransitionFlexTimeFlag'][$array_PH] = $ride[$i]['LinkFlexFlag'] == 'on' || $ride[$i]['LinkFlexFlag'] == 1 ? 1 : 0;
			$links['PrePadding'][$array_PH] = $ride[$i]['PrePadding'];
			$links['PostPadding'][$array_PH] = $ride[$i]['PostPadding'];

			//$links['NumberOfRiders'][$array_PH] = $ride[$i]['NumberOfRiders'];
			$last_destination = $ride[$i]['FromDestinationID'];//set last destination
			$array_PH++;
		} if($ride[$i]['ToDestinationID'] != $last_destination){
			$destination = get_destination($ride[$i]['ToDestinationID']);
			$time = get_time($ride[$i]['DesiredArrivalTime']);
			$links['Location'][$array_PH] = $ride[$i]['ToDestinationID'];				
			$links['LocationText'][$array_PH] = $destination['Name'];
			$links['LocationType'][$array_PH] = rider_has_destination(get_user_rider_id( get_affected_user_id()), $ride[$i]['ToDestinationID']) ? 'Favorite' : 'Franchise';
			$links['destination_selector'][$array_PH] = "-{$ride[$i]['ToDestinationID']}";
			$links['DepartureTimeType'][$array_PH] = "NotConcern";
			$links['DepartureTimeConfirmed'][$array_PH -1] = $ride[$i]['DepartureTimeConfimed'] == 'Y' ? TRUE : FALSE;
			$links['ArrivalTimeConfirmed'][$array_PH] = $ride[$i]['ArrivalTimeConfimed'] == 'Y' ? TRUE : FALSE;
			
			$links['hour']['Depart'][$array_PH] = 9;
			$links['hour']['Arrive'][$array_PH] = $time['Hour'];
			$links['minute']['Depart'][$array_PH] = 0;
			$links['minute']['Arrive'][$array_PH] = $time['Minute'];
			$links['AM_PM']['Depart'][$array_PH] = 'AM';
			$links['AM_PM']['Arrive'][$array_PH] = $time['AM_PM'];
			$links['ArrivalTimeType'][$array_PH] = 'ArriveAt';
			$links['NumberOfRiders'][$array_PH] = $ride[$i]['NumberOfRiders'];
			$links['TransitionNote'][$array_PH] = $ride[$i]['LinkNote'];
			$links['TransitionFlexTimeFlag'][$array_PH] = $ride[$i]['LinkFlexFlag'] == 'on' || $ride[$i]['LinkFlexFlag'] == 1 ? 1 : 0;
			$links['PrePadding'][$array_PH] = $ride[$i]['PrePadding'];
			$links['PostPadding'][$array_PH] = $ride[$i]['PostPadding'];
			$last_destination = $ride[$i]['ToDestinationID'];//set last destination
			$array_PH++;
		}
		$links['AffectedLinks'][] = $ride[$i]['LinkID'];
	}
	
	$links['Submit'] = 'Edit Ride';
	return $links;
}

function create_go_to_array( $DestinationID ){
    $default_home = get_destination( get_rider_default_home_destination( get_user_rider_id( get_affected_user_id() ) ) );
    $destination = get_destination( $DestinationID );

    $date = get_next_user_schedulable_link_time();
    $links = array();
    $links['TravelMonth'] = $date['Month'];
    $links['TravelDay'] = $date['Day'];
    $links['TravelYear'] = $date['Year'];
    $links['TravelDate'] = $date['Date'];
    $links['NumberOfRiders'] = 1;
    $links['Location'][0] = $default_home['DestinationID'];
    $links['LocationText'][0] = $default_home['Name'];
    $links['LocationType'][0] = rider_has_destination(get_user_rider_id( get_affected_user_id()), $default_home['DestinationID']) ? 'Favorite' : 'Franchise';
    $links['destination_selector'][0] = "-{$default_home['DestinationID']}";
    $links['DepartureTimeType'][0] = 'NotConcern';
    $links['ArrivalTimeType'][0] = 'NotConcern';
    $links['Location'][1] = $destination['DestinationID'];
    $links['LocationText'][1] = $destination['Name'];
    $links['LocationType'][1] = rider_has_destination(get_user_rider_id( get_affected_user_id()), $destination['DestinationID']) ? 'Favorite' : 'Franchise';
    $links['destination_selector'][1] = "-{$destination['DestinationID']}";
    $links['DepartureTimeType'][1] = 'NotConcern';
    $links['ArrivalTimeType'][1] = 'NotConcern';
    $links['Location'][2] = $default_home['DestinationID'];
    $links['LocationText'][2] = $default_home['Name'];
    $links['LocationType'][2] = rider_has_destination(get_user_rider_id( get_affected_user_id()), $default_home['DestinationID']) ? 'Favorite' : 'Franchise';
    $links['destination_selector'][2] = "-{$default_home['DestinationID']}";
    $links['DepartureTimeType'][2] = 'NotConcern';
    $links['ArrivalTimeType'][2] = 'NotConcern';
    return $links;
}

function create_come_from_array( $DestinationID ){
    $default_home = get_destination( get_rider_default_home_destination( get_user_rider_id( get_affected_user_id() ) ) );
    $destination = get_destination( $DestinationID );

    $date = get_next_user_schedulable_link_time();
    $links = array();
    $links['TravelMonth'] = $date['Month'];
    $links['TravelDay'] = $date['Day'];
    $links['TravelYear'] = $date['Year'];
    $links['TravelDate'] = $date['Date'];
    $links['NumberOfRiders'] = 1;
    $links['Location'][0] = $destination['DestinationID'];
    $links['LocationText'][0] = $desintation['Name'];
    $links['LocationType'][0] = rider_has_destination(get_user_rider_id( get_affected_user_id()), $destination['DestinationID']) ? 'Favorite' : 'Franchise';
    $links['destination_selector'][0] = "-{$destination['DestinationID']}";
    $links['DepartureTimeType'][0] = 'NotConcern';
    $links['ArrivalTimeType'][0] = 'NotConcern';
    $links['Location'][1] = $default_home['DestinationID'];
    $links['LocationText'][1] = $default_home['Name'];
    $links['LocationType'][1] = rider_has_destination(get_user_rider_id( get_affected_user_id()), $default_home['DestinationID']) ? 'Favorite' : 'Franchise';
    $links['destination_selector'][1] = "-{$default_home['DestinationID']}";
    $links['DepartureTimeType'][1] = 'NotConcern';
    $links['ArrivalTimeType'][1] = 'NotConcern';
    $links['Location'][2] = $destination['DestinationID'];
    $links['LocationText'][2] = $destination['Name'];
    $links['LocationType'][2] = rider_has_destination(get_user_rider_id( get_affected_user_id()), $destination['DestinationID']) ? 'Favorite' : 'Franchise';
    $links['destination_selector'][2] = "-{$destination['DestinationID']}";
    $links['DepartureTimeType'][2] = 'NotConcern';
    $links['ArrivalTimeType'][2] = 'NotConcern';
    return $links;
}

/*
 * this function creates an array of rides with an array of the links that they consist of.
 * $date specifies a certain date the ride is on.
 * $link_id specifies a LinkID, which the function will find the ride that contains that LinkID(returns only 1 ride in the array)
 * $table 
 * structure
 * 	array(
 * 		ride [ link, link, link ]
 * 		ride [ link ]
 * 	}
 */
function get_ride_links_array($rider_user_id, $date = NULL, $link_id = NULL , $table = 'link'){
	
	$safe_date = ($date == NULL) ? NULL : 'AND DesiredArrivalTime LIKE "' . mysql_real_escape_string($date) . '%"';
	$safe_rider_user_id = mysql_escape_string($rider_user_id);
	$safe_table = mysql_real_escape_string($table);
	$date_check = ($link_id == NULL) ? NULL : "AND DesiredArrivalTime LIKE CONCAT( (SELECT DATE_FORMAT( DesiredArrivalTime, '%Y-%m-%d' )
											   FROM link WHERE LinkID =$link_id ) , '%')";
	$sql = "SELECT LinkID, FromDestinationID, ToDestinationID, DesiredArrivalTime, 
		NumberOfRiders, DepartureTimeConfimed, ArrivalTimeConfirmed, LinkNote, LinkFlexFlag, PrePadding, PostPadding, Last_Changed_By, Last_Changed_Date, Created_By, Created_Date
		FROM $safe_table WHERE RiderUserID = $safe_rider_user_id $date_check $safe_date";
	$result = mysql_query($sql) or die(mysql_error());
	
	if($result){
		if(mysql_num_rows($result) < 1)
			return FALSE;
		$rides = array();
		$last_destination = 'disneyworld';
		$ride_PH = -1;//Ride array place holder
		$requested_ride = -1;//: the ride place holder of the $link_id given
		while($row = mysql_fetch_array($result)){
			if($row['FromDestinationID'] == $last_destination){
				$rides[$ride_PH][] = $row;
				$last_destination = $row['ToDestinationID'];
				if($row['LinkID'] == $link_id)
					$requested_ride = $ride_PH;
			} else {
				$ride_PH++;
				$rides[$ride_PH][] = $row;
				$last_destination = $row['ToDestinationID'];
				if($row['LinkID'] == $link_id)
					$requested_ride = $ride_PH;
			}
		}
		if($requested_ride < 0 && $link_id != NULL)
			return FALSE;
		else if($requested_ride >= 0)
			return array( $rides[$requested_ride] );
		return $rides;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get ride links array for date $date, link_id $link_id, table $table", $sql);
		return FALSE;
	}
}

function get_history_links($rider_user_id = NULL, $status = 'COMPLETE', $start_date = '',$end_date = ''){
	$rider = $rider_user_id != NULL ? " AND RiderUserID = " . mysql_real_escape_string($rider_user_id) : NULL;
	$safe_status = mysql_real_escape_string($status);
	$start = ($start_date != '') ? " AND UNIX_TIMESTAMP(`DesiredArrivalTime`) >= UNIX_TIMESTAMP('" . mysql_real_escape_string($start_date) . "')" : NULL;
	$end = ($end_date != '') ? " AND UNIX_TIMESTAMP(`DesiredArrivalTime`) <= UNIX_TIMESTAMP('" . mysql_real_escape_string($end_date) . "')" : NULL;
	
	$sql = "SELECT * FROM `link_history` LEFT JOIN `destination` ON link_history.ToDestinationID = destination.DestinationID WHERE `LinkStatus` = '$safe_status'$rider$start$end";
	$result = mysql_query($sql);

	if($result){
		if(mysql_num_rows($result) < 1)
			return array();
		while($row = mysql_fetch_array($result))
			$links[] = $row;
		return $links;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get completed links array for start date $start_date, end date $end_date", $sql);
	}
}
function get_links($rider_user_id = NULL, $start_date = '',$end_date = ''){
	$rider = $rider_user_id != NULL ? " AND RiderUserID = " . mysql_real_escape_string($rider_user_id) : NULL;
	$start = ($start_date != '') ? " AND UNIX_TIMESTAMP(`DesiredArrivalTime`) >= UNIX_TIMESTAMP('" . mysql_real_escape_string($start_date) . "')" : NULL;
	$end = ($end_date != '') ? " AND UNIX_TIMESTAMP(`DesiredArrivalTime`) <= UNIX_TIMESTAMP('" . mysql_real_escape_string($end_date) . "')" : NULL;
	
	$sql = "SELECT * FROM `link` LEFT JOIN `destination` ON link.ToDestinationID = destination.DestinationID WHERE 1$rider$start$end";
	$result = mysql_query($sql);

	if($result){
		if(mysql_num_rows($result) < 1)
			return array();
		while($row = mysql_fetch_array($result))
			$links[] = $row;
		return $links;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get links array for start date $start_date, end date $end_date", $sql);
	}
}

function get_link_monthly_count($franchise, $month,$year){
	$safe_franchise = mysql_real_escape_string($franchise);
	$safe_month = mysql_real_escape_string($month);
	$safe_year = mysql_real_escape_string($year);
		
	$sql = "SELECT ";
	$days_in_month = get_days_in_month($month,$year);
	for($i = 1; $i <= get_days_in_month($month,$year); $i++){
		$sql .= "(SELECT Count(*) FROM link_history WHERE UNIX_TIMESTAMP(`DesiredArrivalTime`) >= UNIX_TIMESTAMP('$safe_year-$safe_month-$i') AND UNIX_TIMESTAMP(`DesiredArrivalTime`) <= UNIX_TIMESTAMP('$safe_year-$safe_month-$i 23:59:59') AND LinkStatus != 'CANCELEDEARLY' AND LinkStatus != 'NOTSCHEDULED' AND LinkStatus != 'CANCELEDEARLY' AND FranchiseID = $safe_franchise) AS \"$i\"";
		if($i != $days_in_month)
			$sql .= ", ";
	}
	$result = mysql_query($sql);
	
	if($result){
		return mysql_fetch_array($result);		
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get link count for month $month, year $year", $sql);
		return FALSE;
	}
}
function get_link_weekly_count($franchise, $year, $day_of_week = FALSE){
	$safe_franchise = mysql_real_escape_string($franchise);
    $starting_date_dayOfWeek = date('w', strtotime("$year-1-1"));
    $days_skip = ($starting_date_dayOfWeek > 0) ? 7 - $starting_date_dayOfWeek : 1;
    $starting_date = strtotime("$year-1-" . $days_skip . "");
    $days_skip > 0 ? $starting_date = $starting_date - 604800 : '';
	$sql = "SELECT ";
	$num = 0;
	for($i = $starting_date; $num < 53; $num++){	    
		$sql .= "(SELECT Count(*) FROM link_history WHERE UNIX_TIMESTAMP(DesiredArrivalTime) >= '" . ($i - 604799) . "' AND  UNIX_TIMESTAMP(DesiredArrivalTime) <= '$i'  AND LinkStatus != 'CANCELEDEARLY' AND LinkStatus != 'NOTSCHEDULED' AND FranchiseID = $safe_franchise) AS\"$num\" ";
		$i = $i + 604800;
		if($num < 52)
			$sql .= ", ";
	}
	$result = mysql_query($sql);
	if($result){
		return mysql_fetch_array($result);
	} else{
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get link count for weeks year $year", $sql);
		return FALSE;
	}
}


function get_links_week_6_month_percentages($franchise){
/*	$safe_franchise = mysql_real_escape_string($franchise);
	$six_month_req = " AND UNIX_TIMESTAMP(DesiredArrivalTime) >= '" . strtotime( date("Y-n-j") . " -6 months") . "'";
	$sql = "SELECT ";
	
	
	
	$sql .= "(SELECT Count(*) FROM link_history WHERE DATE_FORMAT( DesiredArrivalTime , '%W') = 'Sunday' AND FranchiseID = $franchise$six_month_req) AS `Sunday`,";
	$sql .= "(SELECT Count(*) FROM link_history WHERE DATE_FORMAT( DesiredArrivalTime , '%W') = 'Monday' AND FranchiseID = $franchise$six_month_req) AS `Monday`,";
	$sql .= "(SELECT Count(*) FROM link_history WHERE DATE_FORMAT( DesiredArrivalTime , '%W') = 'Tuesday' AND FranchiseID = $franchise$six_month_req) AS `Tuesday`,";
	$sql .= "(SELECT Count(*) FROM link_history WHERE DATE_FORMAT( DesiredArrivalTime , '%W') = 'Wednesday' AND FranchiseID = $franchise$six_month_req) AS `Wednesday`,";
	$sql .= "(SELECT Count(*) FROM link_history WHERE DATE_FORMAT( DesiredArrivalTime , '%W') = 'Thursday' AND FranchiseID = $franchise$six_month_req) AS `Thursday`,";
	$sql .= "(SELECT Count(*) FROM link_history WHERE DATE_FORMAT( DesiredArrivalTime , '%W') = 'Friday' AND FranchiseID = $franchise$six_month_req) AS `Friday`,";
	$sql .= "(SELECT Count(*) FROM link_history WHERE DATE_FORMAT( DesiredArrivalTime , '%W') = 'Saturday' AND FranchiseID = $franchise$six_month_req) AS `Saturday`";
	*/
	$sql = "select DATE_FORMAT( DesiredArrivalTime , '%W'), count(*) from link_history where FranchiseID = $franchise and DesiredArrivalTime >= DATE(NOW()) - INTERVAL 6 MONTH and LinkStatus in ('COMPLETE','CANCELLEDLATE') and (CustomTransitionType is NULL or CustomTransitionType = 'RIDER') GROUP BY DATE_FORMAT( DesiredArrivalTime , '%W')";

	$result = mysql_query($sql);


		$totals = array();
		$sum = 0;
		while($rs = mysql_fetch_array($result)) {
			$totals[$rs[0]] = $rs[1];
			$sum += $rs[1];
		}

		$sum = $sum/2;
		return array('Sunday' => $totals['Sunday']/$sum,
					 'Monday' => $totals['Monday']/$sum,
					 'Tuesday' => $totals['Tuesday']/$sum,
					 'Wednesday' => $totals['Wednesday']/$sum,
					 'Thursday' => $totals['Thursday']/$sum,
					 'Friday' => $totals['Friday']/$sum,
					 'Saturday' => $totals['Saturday']/$sum);

}

function createDateRangeClause( $start_date = FALSE, $end_date = FALSE ){
    $dateRangeClause = '';
         
    if ($start_date && $end_date) {
        $dateRangeClause = "( DesiredArrivalTime BETWEEN '" . 
                               mysql_real_escape_string($start_date) . "' AND '" . mysql_real_escape_string($end_date) . "' )";
                               
    } elseif ($start_date) {
        $dateRangeClause = "( DesiredArrivalTime >= '" . mysql_real_escape_string($start_date) . "' )";
         
    } elseif ($end_date) {
        $dateRangeClause = "( DesiredArrivalTime <= '" . mysql_real_escape_string($end_date) . "' )";
         
    }
    return $dateRangeClause;  
}

function get_number_of_links($franchise, $start_date = NULL, $end_date = NULL){

    // The CustomTransitionType explicit positive test (below) works; the simpler tests for
    // the single negative case (`CustomTransitionType` <> 'DRIVER') did not work because it
    // did not handle the NULL case.
    //
    // A NULL field is considerred an error if it is not accounted-
    // for appropriately (the IS NULL test has to come first for _ANY_ data evaluation in a WHERE). 
    $driverExclusion = "((`CustomTransitionType` IS NULL) OR (`CustomTransitionType` = 'RIDER'))";
        
    $dateRange = createDateRangeClause( $start_date, $end_date );
    
    $safe_franchise = mysql_real_escape_string($franchise);
    $franchiseMatch = "( FranchiseID = $safe_franchise )";
    
    $where_clause = " WHERE $driverExclusion AND $dateRange AND $franchiseMatch ";

    // The UNION here provides all the Actuals (from link_history) and initially Scheduled link count.
    $sql = "SELECT COUNT(LinkID) AS TheCount, LinkStatus FROM link_history $where_clause
            GROUP BY LinkStatus
            UNION
            SELECT COUNT(LinkID) AS TheCount, 'SCHEDULED' AS LinkStatus FROM link $where_clause";

    //print_r("\nLink Count Query:\n". $sql);

	 $result = mysql_query($sql);
	
	 if ($result){
        $status_results = array();
        while ($row = mysql_fetch_array($result)) {
            $status_results[$row['LinkStatus']] = $row['TheCount'];
        }
		return $status_results;
	} else {
      print_r("\n" . mysql_error() . "\n");	   
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get number of links for start date: $start_date, end date: $end_date", $sql);
		return FALSE;
	}
}

function get_distance_of_links($franchise, $start_date = NULL, $end_date = NULL){

    // A NULL field is considerred an error if it is not accounted-
    // for appropriately (the IS NULL test has to come first for _ANY_ data evaluation in a WHERE). 
    $driverExclusion = "((`CustomTransitionType` IS NULL) OR (`CustomTransitionType` = 'RIDER'))";
        
    $dateRange = createDateRangeClause( $start_date, $end_date );
    
    $safe_franchise = mysql_real_escape_string($franchise);
    $franchiseMatch = "( FranchiseID = $safe_franchise )";
    
    $where_clause = " WHERE $driverExclusion AND $dateRange AND $franchiseMatch ";

    // The UNION here provides all the Actuals (from link_history) and initially Scheduled distance.
    $sql = "SELECT SUM(Distance) AS TotalDistance, LinkStatus FROM link_history $where_clause
            GROUP BY LinkStatus
            UNION
            SELECT SUM(Distance) AS TotalDistance, 'SCHEDULED' AS LinkStatus FROM link $where_clause ";

//	 print_r("\nLink Distance Query:\n". $sql);

    $result = mysql_query($sql);
	
	 if($result){
        $status_results = array();
        while ($row = mysql_fetch_array($result)) {
            $status_results[$row['LinkStatus']] = $row['TotalDistance'];
        }
		return $status_results;
	} else {
   	print_r("\n" . mysql_error() . "\n");
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get distance of links for start date: $start_date, end date: $end_date", $sql);
		return FALSE;
	}
}

function get_revenue_of_links($franchise, $start_date = NULL, $end_date = NULL){

    // A NULL field is considerred an error if it is not accounted-
    // for appropriately (the IS NULL test has to come first for _ANY_ data evaluation in a WHERE). 
    $driverExclusion = "((`CustomTransitionType` IS NULL) OR (`CustomTransitionType` = 'RIDER'))";
        
    $dateRange = createDateRangeClause( $start_date, $end_date );
    
    $safe_franchise = mysql_real_escape_string($franchise);
    $franchiseMatch = "( FranchiseID = $safe_franchise )";
    
    $where_clause = " WHERE $driverExclusion AND $dateRange AND $franchiseMatch ";

    // The UNION here provides all the Actuals (from link_history) and initially Scheduled link revenues.
    $sql = "SELECT SUM(QuotedCents) AS TotalCents, LinkStatus FROM link_history $where_clause
            GROUP BY LinkStatus
            UNION
            SELECT SUM(QuotedCents) AS TotalCents, 'SCHEDULED' AS LinkStatus FROM link $where_clause ";

//    print_r("\nLink Revenue Query:\n". $sql);
    
    $result = mysql_query($sql);
	
	 if ($result){
        $status_results = array();
        while ($row = mysql_fetch_array($result)) {
            $status_results[$row['LinkStatus']] = $row['TotalCents'];
        }
		return $status_results;
	} else {
      //print_r("\n" . mysql_error() . "\n");
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get QuotedCents of links for start date: $start_date, end date: $end_date", $sql);
		return FALSE;
	}
}

function get_next_travel_times_for_all_riders($riders = array()) {
	if(!is_array($riders))
		return array();
    $times = array();
    $sql = "SELECT RiderUserID, FranchiseID, MIN(DesiredArrivalTime) AS DesiredArrivalTime,PrePadding, PostPadding, EstimatedMinutes FROM link WHERE RiderUserID IN ( " . implode(", ", $riders) . " )
            AND DesiredArrivalTime > '" . date('Y-m-d H:i') . "'
    		GROUP BY RiderUserID"; 

    $result = mysql_query($sql);
    if ($result) {
        while ($row = mysql_fetch_array($result)) {
            $arrival = get_link_arrival_time($row);
            $departure = get_link_departure_time($row);
            $times[$row['RiderUserID']] = array('arrival' => $arrival,
                                            'departure' => $departure);
        }
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Error retrieving next arrival time for all riders", $sql);
    }

    return $times;
}

function get_transition_data($franchise, $start_date = '', $end_date = ''){
	$safe_franchise = mysql_real_escape_string($franchise);
	$start = ($start_date != '') ? " AND UNIX_TIMESTAMP(`DesiredArrivalTime`) >= UNIX_TIMESTAMP('" . mysql_real_escape_string($start_date) . "')" : NULL;
	$end = ($end_date != '') ? " AND UNIX_TIMESTAMP(`DesiredArrivalTime`) <= UNIX_TIMESTAMP('" . mysql_real_escape_string($end_date) . "')" : NULL;
	$franchise_W = " AND link_history.FranchiseID = $safe_franchise";
	$sql = "SELECT ";
	$sql .= "( SELECT COUNT(*) FROM deadhead_history LEFT JOIN link_history ON deadhead_history.NextLinkID = link_history.LinkID WHERE 1$franchise_W$start$end) AS \"Links\",";
	$sql .= "( SELECT SUM(deadhead_history.Distance) FROM deadhead_history LEFT JOIN link_history ON deadhead_history.NextLinkID = link_history.LinkID WHERE 1$franchise_W$start$end) AS \"Miles\",";
	$sql .= "( SELECT SUM(deadhead_history.QuotedCents) FROM deadhead_history LEFT JOIN link_history ON deadhead_history.NextLinkID = link_history.LinkID WHERE 1$franchise_W$start$end) AS \"Revenue\"";
	//echo $sql."<BR>";
	$result = mysql_query($sql) or die(mysql_error());
	
	if($result){
		return mysql_fetch_array($result);
	} else{
		 rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Error retrieving the transition data for reports", $sql);
	}
}


function calculate_riders_incomplete_ride_costs( $rider_user_id , $future_days = -1){
	$safe_rider_user_id = mysql_real_escape_string($rider_user_id);
	$sql = "SELECT SUM(QuotedCents) FROM link WHERE RiderUserID = $safe_rider_user_id AND (CustomTransitionType = 'RIDER' || CustomTransitionType IS NULL)";
	if($future_days > 0)
		$sql .= " AND DesiredArrivalTime BETWEEN CURDATE() + INTERVAL $future_days DAY AND CURDATE() ";
	$r = mysql_query($sql);
	if(mysql_num_rows($r) > 0) return mysql_fetch_array( $r )[0];
	else return "0";
}

function calculate_batch_rider_incomplete_ride_costs($riders = array()){
	if(!is_array($riders))
		return array();
	$balances = array();
	foreach($riders as $rider)
		$balances[$rider] = 0;
	//$safe_rider_user_id = mysql_real_escape_string($rider_user_id);
	$sql = "SELECT UserID, (SELECT SUM(QuotedCents) as Balance FROM link WHERE RiderUserID = users.UserID  AND (CustomTransitionType = 'RIDER' || CustomTransitionType IS NULL) ) AS Balance FROM users WHERE UserID IN ( " . implode(", ", $riders) . " ) ";
	$result = mysql_query( $sql );
	
	if($result){
		while($row = mysql_fetch_array($result)){
			$balances[$row['UserID']] = $row['Balance'];
		}
		return $balances;
	} else {
		
	}
	return $result[0];
}

function get_links_from_array($links){
	foreach($links as $id => $link)
		$links[$id] = mysql_real_escape_string($link);
	$sql = "SELECT LinkID, RiderUserID, DesiredDepartureTime, DesiredArrivalTime, Distance, EstimatedMinutes, 
                   QuotedCents, AssignedDriverUserID, VehicleID, link.FranchiseID, NumberOfRiders, DepartureTimeConfimed, ArrivalTimeConfirmed,
                   LinkStatus, FromDestinationID AS F_DestinationID, ToDestinationID AS T_DestinationID,
                   F_Dest.Name AS F_Name, F_Dest.IsPublic AS F_Public,
                   F_Dest.DestinationDetail AS F_DestinationDetail,
                   F_Address.AddressID AS F_AddressID, F_Address.Address1 AS F_Address1, 
                   F_Address.Address2 AS F_Address2, F_Address.City AS F_City, 
                   F_Address.State AS F_State, F_Address.ZIP5 AS F_ZIP5, 
                   F_Address.ZIP4 AS F_ZIP4,
                   T_Dest.Name AS T_Name, T_Dest.IsPublic AS T_IsPublic,
                   T_Dest.DestinationDetail AS T_DestinationDetail,
                   T_Address.AddressID AS T_AddressID, T_Address.Address1 AS T_Address1, 
                   T_Address.Address2 AS T_Address2,
                   T_Address.City AS T_City, T_Address.State AS T_State, 
                   T_Address.ZIP5 AS T_ZIP5, T_Address.ZIP4 AS T_ZIP4
            FROM link, destination AS F_Dest, destination AS T_Dest, 
                 address AS F_Address, address AS T_Address
            WHERE (link.LinkID = '" . implode("' OR link.LinkID ='", $links) . "' )AND
                  link.FromDestinationID = F_Dest.DestinationID AND
                  F_Dest.AddressID = F_Address.AddressID AND
                  link.ToDestinationID = T_Dest.DestinationID AND
                  T_Dest.AddressID = T_Address.AddressID AND
                  F_Dest.AddressID = F_Address.AddressID
            ORDER BY DesiredArrivalTime ASC";
	$result = mysql_query($sql);
		
	if($result){
		$links = array();
		
		while($row = mysql_fetch_array($result))
			$links[] = $row;
		return $links;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could no get links from array", $sql);
		return false;
	}
}

function get_basic_link_table($links){
	$links = get_links_from_array($links);
	
	$table = <<<HTML
<table border="1">
	<tr>
		<th>LinkID</th>
		<th>Desired Arrival</th>
		<th>#</th>
		<th>Rider</th>
		<th>From</th>
		<th>To</th>
		<th>Est. Travel Min.</th>
		<th>Dist. (Miles)</th>
	</tr>
HTML;
	foreach($links as $link){
		$name = get_displayable_person_name_string( get_user_person_name($link['RiderUserID']));
		$to = create_compact_display_address(array('Address1' => $link['T_Address1'],
												   'Address2' => $link['T_Address2'],
												   'City' => $link['T_City'],
												   'State' => $link['T_State'],
												   'ZIP5' => $link['T_ZIP5']));
					
		$from = create_compact_display_address(array('Address1' => $link['F_Address1'],
					  								 'Address2' => $link['F_Address2'],
					  								 'City' => $link['F_City'],
					  								 'State' => $link['F_State'],
					  								 'ZIP5' => $link['F_ZIP5']));
					
		$table .= <<<HTML
<tr>
	<td>{$link['LinkID']}</td>
	<td>{$link['DesiredArrivalTime']}</td>
	<td>{$link['NumberOfRiders']}</td>
	<td>$name</td>
	<td>$from</td>
	<td>$to</td>
	<td>{$link['EstimatedMinutes']}</td>
	<td>{$link['Distance']}</td>
</tr>
HTML;
	}
	$table .= <<<HTML
</table>
HTML;
	return $table;
}

function set_current_destination_time_confirm($link_id, $bool){
	$safe_link_id = mysql_real_escape_string($link_id);
	$safe_bool = $bool ? 'Y' : 'N';
	
	$sql = "UPDATE `link` SET `DepartureTimeConfimed` = '$safe_bool' WHERE `LinkID` =$safe_link_id;";
	$result = mysql_query($sql);
		
	if($result){
		return true;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could no set destination time confirm", $sql);
		return false;
	}
}

function set_current_arrival_time_confirm($link_id, $bool){
	$safe_link_id = mysql_real_escape_string($link_id);
	$safe_bool = $bool ? 'Y' : 'N';
	
	$sql = "UPDATE `link` SET `ArrivalTimeConfirmed` = '$safe_bool' WHERE `LinkID` =$safe_link_id;";
	$result = mysql_query($sql);
		
	if($result){
		return true;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could no set arrival time confirm", $sql);
		return false;
	}
}

function get_link_note($link_id){
	$safe_link_id = mysql_real_escape_string($link_id);
	$sql = "SELECT LinkID, RiderUserID, LinkNote FROM ((SELECT LinkID, RiderUserID, LinkNote FROM link) UNION (SELECT LinkID, RiderUserID, LinkNote FROM link_history)) t1 WHERE LinkID = $safe_link_id LIMIT 1;";
	$result = mysql_query($sql);
	
	if($result){
		return mysql_fetch_array($result);
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "could not get link note for link id $link_id", $sql);
		return false;
	}
}

function set_link_note($link_id, $note){
	$safe_link_id = mysql_real_escape_string($link_id);
	$safe_note = mysql_real_escape_string($note);
	$sql = "UPDATE link SET LinkNote = '$safe_note' WHERE LinkID = $safe_link_id LIMIT 1;";
	$result = mysql_query($sql);
	$sql = "UPDATE link_history SET LinkNote = '$safe_note' WHERE LinkID = $safe_link_id LIMIT 1;";
	$result2 = mysql_query($sql);
	
	if($result && $result2){
		return true;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "could not get link note for link id $link_id", $sql);
		return false;
	}
}

function get_links_rider_names($links){
	foreach($links as $id => $link)
		$links[$id] = mysql_real_escape_string($link);
	$sql = "select RiderUserID from link WHERE LinkID ='" . implode("' || LinkID = '", $links) . "'";
	$result = mysql_query($sql);
	$names = array();
	if($result) while($row = mysql_fetch_array($result)) $names[] = get_user_person_name($row['RiderUserID']);
	return $names;
}



function set_driver_confirm($link_id, $bool_confirmed){
    $safe_link_id = mysql_real_escape_string($link_id);
    $safe_bool_confirmed = $bool_confirmed ? 'Yes' : 'No';
    
    $sql = "UPDATE `link` SET DriverConfirmed = '$safe_bool_confirmed' WHERE LinkID = $safe_link_id LIMIT 1;";
    $result = mysql_query($sql);    
    if($result){
        return true;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "could not set driver confirm for link $link_id to $safe_bool_confirmed", $sql);
		return false;
    }
}

function get_driver_confirm($link_id){
	$safe_link_id = mysql_real_escape_string($link_id);
	$sql = "SELECT DriverConfirmed FROM `link` WHERE LinkID = $safe_link_id";
	$result = mysql_query($sql);
	
	if($result){
		$result = mysql_fetch_array($result);
		if($result['DriverConfirmed'] == 'Yes')
			return true;
		return false;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "could not get driver confirm for link $link_id", $sql);
		return false;
	}
}

function get_additional_riders($link_id){
    $safe_link_id = mysql_real_escape_string($link_id);
    $sql = "SELECT UserID, Title, FirstName, MiddleInitial, LastName, Suffix FROM users NATURAL JOIN person_name WHERE UserID IN (SELECT RiderUserID as UserID FROM link_additional_rider WHERE LinkID = $safe_link_id )";
    $result = mysql_query($sql);
    
    if($result){
        $riders = array();
        while($row = mysql_fetch_array($result))
            $riders[] = $row;
        return $riders;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "could not get additional riders for link $link_id", $sql);
		return false;
    }
}

function add_additional_rider($link_id, $rider_id){
    $safe_link_id = mysql_real_escape_string($link_id);
    $safe_rider_id = mysql_real_escape_string($rider_id);
    $sql = "INSERT INTO `link_additional_rider` (`LinkID` , `RiderUserID`)
                                        VALUES ('$safe_link_id', '$safe_rider_id')";
    $result = mysql_query($sql);
    
    if($result){
        return true;
    } else {
        return false;
    }
}

function utility_add_one_month($t) {

# $t is a time value.  As PHP arbitrarily adds 31 days to get the value for "next month", we need a ham-fisted approach
#	to get around this 
	
	$m = date('m',$t)+1;
	$y = date('Y',$t);
	
	if($m == 13) {
		$m = 1;
		$y++;
	}

	return strtotime($y.'-'.$m.'-'.date('d',$t));
}
?>
