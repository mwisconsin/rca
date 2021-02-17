<?php 
	require_once('include/completed_link_transitions.php');
function mysql_escape_link_array($array){
	if(is_array($array)){
		foreach($array as $id => $value){
			$array[$id] = mysql_real_escape_string($value);
		}
		return $array;
	} else return false;
}

function confirm_valid_driver_rides($driverID, $linkID_array){
	if(count($linkID_array) > 0){
		$link_count = count($linkID_array);
		$safe_driver = mysql_real_escape_string($driverID);
		$safe_array = mysql_escape_link_array($linkID_array);
		if(!$safe_array)
			return false;
		$sql = "SELECT Count(LinkID) AS Links, Date(`DesiredArrivalTime`) AS Date, `AssignedDriverUserID` AS Driver 
				FROM `link` 
				WHERE LinkID IN ('" . implode("','", $safe_array) .  "') 
				GROUP BY AssignedDriverUserID, Date(`DesiredArrivalTime`) 
				HAVING Links = $link_count AND Driver = $safe_driver";
		$result = mysql_query($sql) or die(mysql_error());
		if($result){
			if(mysql_num_rows($result) == 1)
				return array_merge(array('Result'=> true), mysql_fetch_array($result));
			return array('Result'=> false);
		} else {
			rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        	"Could not confirm_valid_driver_rides", $sql);

			return false;
		}
	} return false;
}

function create_new_custom_transition( $driverID){
	$safe_driverID = mysql_real_escape_string($driverID);
	
	$sql = "INSERT INTO `custom_ride_transition` (`CustomTransitionID`, `CustomDriverID`) VALUES (NULL, '$safe_driverID');";
	$result = mysql_query($sql);
	
	if($result){
		return mysql_insert_id();
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        	"Could not create custom ride transition for driver $driverID", $sql);
		return false;
	}
}

function connect_link_with_custom_ride_transition($linkID, $CustomTransitionID){
	$safe_linkID = mysql_real_escape_string($linkID);
	$safe_CustomTransitionID = mysql_real_escape_string($CustomTransitionID);
	
	$sql = "UPDATE `link` SET `CustomTransitionID` = $safe_CustomTransitionID WHERE `LinkID` = $safe_linkID;";
	$result = mysql_query($sql);
	$sql = "UPDATE `link_history` SET `CustomTransitionID` = $safe_CustomTransitionID WHERE `LinkID` = $safe_linkID;";
	$result2 = mysql_query($sql);
	
	if($result){
		return true;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        	"Could not connect link $linkID with custom ride transition $CustomTransitionID", $sql);
		return false;
	}
}


function driverreleased_all_on_date($userid, $dateupdating){
	$safe_userid = mysql_real_escape_string($userid);
	$safe_dateupdating = mysql_real_escape_string($dateupdating);
	
	$sql = "UPDATE link SET DriverConfirmed = 'Yes', DriverConfirmedDTS = CURRENT_TIMESTAMP() WHERE AssignedDriverUserID = $userid and DATE_FORMAT(DesiredArrivalTime,'%Y-%m-%d') = '$dateupdating'";

	$result = mysql_query($sql);
	
	if($result){
		return true;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        	"driverreleased_all_on_date", $sql);
		return false;
	}
}


function get_link_custom_transition_id($link_id){
	$safe_link_id = mysql_real_escape_string($link_id);
	
	$sql = "SELECT CustomTransitionID FROM ((SELECT LinkID, CustomTransitionID FROM link) UNION (SELECT LinkID, CustomTransitionID FROM link_history)) t1 WHERE LinkID = $safe_link_id LIMIT 1;";
	$result = mysql_query($sql);
	
	if($result){
		if(mysql_num_rows($result) < 1)
			return false;
		$row = mysql_fetch_array($result);
		return $row['CustomTransitionID'];
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get custom transition id for link $link_id", $sql);

		return false;
	}
}

function cancel_custom_ride_transition($transition_id){
	$safe_transition_id = mysql_real_escape_string($transition_id);
	$sql = "DELETE FROM `link` WHERE `CustomTransitionID` = $safe_transition_id AND CustomTransitionType = 'DRIVER'";
	$result = mysql_query($sql);
	if(!$result){
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not delete the DRIVER transitions for transition id $transition_id", $sql);
		return false;
	}
	
	$sql = "UPDATE `link` SET `CustomTransitionType` = NULL, `CustomTransitionID` = NULL WHERE `CustomTransitionID` = $safe_transition_id AND CustomTransitionType = 'RIDER'";
	$result = mysql_query($sql);
	if(!$result){
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not update the RIDER transitions for transition id $transition_id", $sql);
		return false;
	}
	return true;
}

function set_link_custom_transiton_type($link_id, $type){
	$safe_link_id = mysql_real_escape_string($link_id);
	$safe_type = mysql_real_escape_string($type);
	$sql = "UPDATE `link` SET `CustomTransitionType` = '$safe_type' WHERE `LinkID` =$safe_link_id;";
	$result = mysql_query($sql);
	
	if($result){
		return true;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not update transition type on link $link_id to type $type", $sql);
		return false;
	}
}


function set_custom_transition_all_statuses($transition_id, $new_status){
	$safe_transition_id = mysql_real_escape_string($transition_id);
	$safe_new_status = mysql_real_escape_string($new_status);
	
	$sql = "UPDATE link SET LinkStatus = '$safe_new_status' WHERE CustomTransitionID = $safe_transition_id";
	$result = mysql_query($sql);
	
	if($result){
		return TRUE;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not update statuses on custom transition links", $sql);
		return false;
	}
}

function get_custom_transition_rider_links($transition_id){
	$safe_transition_id =mysql_real_escape_string($transition_id);
	
	$sql = "SELECT LinkID, RiderUserID, DesiredArrivalTime, Distance, EstimatedMinutes, 
                   QuotedCents, AssignedDriverUserID, all_links.FranchiseID, NumberOfRiders, PrePadding, PostPadding, DepartureTimeConfimed,
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
                   T_Address.ZIP5 AS T_ZIP5, T_Address.ZIP4 AS T_ZIP4,
                   CustomTransitionID, CustomTransitionType
            FROM (
				SELECT LinkID, CustomTransitionID, CustomTransitionType, RiderUserID, DesiredArrivalTime, Distance, EstimatedMinutes,QuotedCents, AssignedDriverUserID, FranchiseID, NumberOfRiders, PrePadding, PostPadding, DepartureTimeConfimed, LinkStatus, FromDestinationID, ToDestinationID FROM link WHERE CustomTransitionID = $safe_transition_id AND CustomTransitionType = 'Rider' 
					UNION 
				SELECT LinkID, CustomTransitionID, CustomTransitionType, RiderUserID, DesiredArrivalTime, Distance, EstimatedMinutes,QuotedCents, DriverUserID as AssignedDriverUserID, FranchiseID, NumberOfRiders, PrePadding, PostPadding, 'Y' as DepartureTimeConfimed, LinkStatus, FromDestinationID, ToDestinationID FROM link_history WHERE CustomTransitionID = $safe_transition_id AND CustomTransitionType = 'Rider') as all_links , 
				 destination AS F_Dest, destination AS T_Dest, 
                 address AS F_Address, address AS T_Address
            WHERE 
                  all_links.FromDestinationID = F_Dest.DestinationID AND
                  F_Dest.AddressID = F_Address.AddressID AND
                  all_links.ToDestinationID = T_Dest.DestinationID AND
                  T_Dest.AddressID = T_Address.AddressID
				  
            ORDER BY DesiredArrivalTime ASC";
	
	$result = mysql_query($sql);
	
	if($result){
		$links = array();
		while($row = mysql_fetch_array($result))
			$links[] = $row;
		return $links;
	} else{
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get transition links for rider side", $sql);
		return false;
	}
}

function get_custom_transition_driver_links($transition_id){
	$safe_transition_id =mysql_real_escape_string($transition_id);
	
	$sql = "SELECT LinkID, RiderUserID, DesiredArrivalTime, Distance, EstimatedMinutes, 
                   QuotedCents, AssignedDriverUserID, all_links.FranchiseID, NumberOfRiders, PrePadding, PostPadding, DepartureTimeConfimed,
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
                   T_Address.ZIP5 AS T_ZIP5, T_Address.ZIP4 AS T_ZIP4,
                   CustomTransitionID, CustomTransitionType
            FROM (
				SELECT LinkID, CustomTransitionID, CustomTransitionType, RiderUserID, DesiredArrivalTime, Distance, EstimatedMinutes,QuotedCents, AssignedDriverUserID, FranchiseID, NumberOfRiders, PrePadding, PostPadding, DepartureTimeConfimed, LinkStatus, FromDestinationID, ToDestinationID FROM link WHERE CustomTransitionID = $safe_transition_id AND CustomTransitionType = 'Driver' 
					UNION 
				SELECT LinkID, CustomTransitionID, CustomTransitionType, RiderUserID, DesiredArrivalTime, Distance, EstimatedMinutes,QuotedCents, DriverUserID as AssignedDriverUserID, FranchiseID, NumberOfRiders, PrePadding, PostPadding, 'Y' as DepartureTimeConfimed, LinkStatus, FromDestinationID, ToDestinationID FROM link_history WHERE CustomTransitionID = $safe_transition_id AND CustomTransitionType = 'Driver') as all_links , 
				 destination AS F_Dest, destination AS T_Dest, 
                 address AS F_Address, address AS T_Address
            WHERE 
                  all_links.FromDestinationID = F_Dest.DestinationID AND
                  F_Dest.AddressID = F_Address.AddressID AND
                  all_links.ToDestinationID = T_Dest.DestinationID AND
                  T_Dest.AddressID = T_Address.AddressID
				  
            ORDER BY DesiredArrivalTime ASC";
	
	$result = mysql_query($sql);
	
	if($result){
		$links = array();
		while($row = mysql_fetch_array($result))
			$links[] = $row;
		return $links;
	} else{
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get transition links for rider side", $sql);
		return false;
	}
}


function check_location_of_custom_transition($transition_id){
	$safe_transition = mysql_real_escape_string($transition_id);
	
	$sql = "SELECT COUNT(*) as size FROM (SELECT LinkID FROM link WHERE CustomTransitionID = $safe_transition UNION SELECT LinkID FROM link_history WHERE CustomTransitionID = $safe_transition) as t1";
	$result = mysql_query($sql);
	
	if($result){
		$total = mysql_fetch_array($result);
		$sql = "SELECT Count(*) as size FROM link WHERE CustomTransitionID = $safe_transition";
		$result = mysql_query($sql);
		$total_link = mysql_fetch_array($result);
		
		$sql = "SELECT Count(*) as size FROM link_history WHERE CustomTransitionID = $safe_transition";
		$result = mysql_query($sql);
		$total_link_history = mysql_fetch_array($result);
		
		return array('SameLocation' => (($total['size'] - $total_link_history['size'] == 0) || ($total['size'] - $total_link['size'] == 0)) ? TRUE : FALSE,
					 'Location' => ($total['size'] - $total_link_history['size'] == 0) ? "History" : "Current");
		
		
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get transition links for rider side", $sql);
		return false;
	}
}
//8006 8025 8061 8062
?>