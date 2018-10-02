<?php

require_once('include/database.php');
require_once 'include/user.php';
require_once 'include/name.php';
require_once 'include/phone.php';
require_once 'include/email.php';
require_once 'include/address.php';
require_once 'include/date_time.php';
require_once 'include/franchise.php';

function get_all_active_care_facility_user_info_xx( $franchise, $CFID = -1 ) {
   $safe_franchise = mysql_real_escape_string($franchise);

    $sql = "SELECT care_facility.CareFacilityID, CareFacilityName, null as ApplicationDate, AnnualFeePaymentDate, null as WelcomePackageSentDate, null as CareFacilityWaver, FirstRide, null as FirstRideFollowupDate, LastRide, NextRide, IFNULL(Bal, 0) AS Balance, IFNULL(Bal, 0) - IFNULL(FutureDue, 0) AS AvailableBalance, RechargeThreshold, null as PictureWaiver,PhoneNumber 
	FROM `care_facility` 
	LEFT JOIN (SELECT CareFacilityID, MIN(link_history.DesiredArrivalTime) AS FirstRide, MAX(link_history.DesiredArrivalTime) AS LastRide 
							FROM `care_facility_user` 
							LEFT JOIN link_history ON link_history.RiderUserID = care_facility_user.UserID 
								AND link_history.DesiredArrivalTime BETWEEN ConnectionDate 
								AND IFNULL(DisconnectDate, DATE('3000-01-01')) AND link_history.LinkStatus = 'COMPLETE' 
							GROUP BY CareFacilityID) h ON h.CareFacilityID = care_facility.CareFacilityID 
	LEFT JOIN (SELECT CareFacilityID, MIN(link.DesiredArrivalTime) AS NextRide, 
							SUM(QuotedCents) AS FutureDue 
						FROM `care_facility_user` 
						LEFT JOIN link ON link.RiderUserID = care_facility_user.UserID 
							AND link.DesiredArrivalTime BETWEEN ConnectionDate 
							AND IFNULL(DisconnectDate, DATE('3000-01-01')) 
						GROUP BY CareFacilityID) f ON f.CareFacilityID = care_facility.CareFacilityID 
	LEFT JOIN (SELECT EntityID, SUM(Cents) AS Bal 
							FROM ledger 
							WHERE EntityType = 'CAREFACILITY' 
							GROUP BY EntityID) l ON l.EntityID = care_facility.CareFacilityID 
	LEFT JOIN phone on phone.PhoneID = care_facility.PhoneID
	WHERE care_facility.FranchiseID = $safe_franchise "
	.($CFID > -1 ? " and care_facility.CareFacilityID = $CFID " : "")
	." ORDER BY CareFacilityName";

	// care_facility.CareFacilityStatus = 'Active' AND  
	
    $result = mysql_query($sql) or die(mysql_error());

	if ($result) {
        $result_rows = array();
        
       	for ($i = 0; $row = mysql_fetch_array($result); $i++) {
            $result_rows[$i] = $row;
            if($result_rows[$i]['AnnualFeePaymentDate'] == null)
        			$result_rows[$i]['sort'] = 1;
            elseif(isset($result_rows[$i]['DaysOnAnnualFee'])) {
	            if($result_rows[$i]['DaysOnAnnualFee'] > 30)
	            	$result_rows[$i]['sort'] = -2;	
	            else if($result_rows[$i]['DaysOnAnnualFee'] <= 30 && $result_rows[$i]['DaysOnAnnualFee'] >= 0)
	            	$result_rows[$i]['sort'] = -1;
	          }	else 
           		$result_rows[$i]['sort'] = 0;
        	
        }
		return $result_rows;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get all active care facility users", $sql);
	}
    return FALSE;
}

function display_care_facility_header( $facility_id ){
	$facility = get_care_facility($facility_id);
	$facility_address = get_address($facility['FacilityAddressID']);
	echo '<span style="font-size:.9em;">' . $facility['CareFacilityName'] . ', ' . ucwords(strtolower($facility_address['Address1'] . ', ' . $facility_address['City'] . ', ')) . $facility_address['State'] . ' ' . $facility_address['ZIP'] . ' ' . '</span>';
}
function get_care_facility( $facility_id ){
	$safe_facility_id = mysql_real_escape_string($facility_id);
	
	$sql = "SELECT * FROM `care_facility` WHERE `CareFacilityID` =$safe_facility_id LIMIT 1;";
	$result = mysql_query($sql);
	
	if($result){
		if(mysql_num_rows($result) < 1)
			return FALSE;
		return mysql_fetch_array($result);
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get care facility $facility_id", $sql);
		return FALSE;
	}
}

function add_care_facility( $facility_name, $franchise_id, $address_id, $email_id) {
    $return = FALSE;
    
    $safe_facility_name = mysql_real_escape_string($facility_name);
    $safe_franchise_id = mysql_real_escape_string($franchise_id);
    $safe_address_id = mysql_real_escape_string($address_id);
	$safe_email_id = mysql_real_escape_string($email_id);

    $sql = "INSERT INTO care_facility (CareFacilityName, FranchiseID, FacilityAddressID, DefaultEmailID)
            VALUES ( '$safe_facility_name', $safe_franchise_id, $safe_address_id, $safe_email_id )";

    $result = mysql_query($sql);

    if ($result) {
       $return = mysql_insert_id(); 
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not add care facility $facility_name ($franchise_id)", $sql);
    }

    return $return;
}

function connect_user_to_care_facility($user_id, $care_facility_id) {
	$safe_user_id = mysql_real_escape_string($user_id);
    $safe_facility_id = mysql_real_escape_string($care_facility_id);
	
	$sql = "DELETE FROM `care_facility_user` WHERE `CareFacilityID` = $safe_facility_id AND `UserID` = $safe_user_id LIMIT 1;";
	mysql_query($sql);
    // TODO:  This does not currently re-associate (update), just associate (insert).
    $sql = "INSERT INTO care_facility_user (CareFacilityID, UserID, ConnectionDate, DisconnectDate)
            VALUES ($safe_facility_id, $safe_user_id, NOW(),NULL)";

    $result = mysql_query($sql) or die(mysql_error());

    if ($result) {
       return TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), "Could not associate facility " .
                        "$care_facility_id with user $user_id", $sql);
        return FALSE;
    }
}

function remove_user_care_facility_connections($user_id) {
    $safe_user_id = mysql_real_escape_string($user_id);

    $sql = "DELETE FROM care_facility_user WHERE UserID = $safe_user_id";

    $result = mysql_query($sql);

    if ($result) {
       return TRUE; 
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Could not remove facility associations for $user_id", $sql);
        return FALSE;
    }
}

function disconnect_user_from_care_facilities($user_id, $disconnect_date = NULL) {
    $start = db_start_transaction();
    if (!$start) {
        db_rollback_transaction();
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Could not start a transaction for disconnect_user_from_care_facilities", $sql);
        return FALSE;
    }
    
    $links_disconnected = disconnect_cf_user_rides_past_date($user_id, $disconnect_date);
    $user_disconnected = set_user_care_facility_disconnect_date($user_id, $disconnect_date);

    if ($links_disconnected && $user_disconnected) {
        $commit = db_commit_transaction();
        if ($commit) {
            return TRUE;
        } else {
            rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                            "Could not commit transaction for disconnect_user_from_care_facilities", $sql);
            db_rollback_transaction();
        }
    }
    return FALSE;
}

function disconnect_cf_user_rides_past_date($user_id, $disconnect_date = NULL) {
    $safe_user_id = mysql_real_escape_string($user_id);
    $safe_date = (is_null($disconnect_date)) ? 'NOW()' : "'" . mysql_real_escape_string($disconnect_date) . "'";

    $sql = "DELETE FROM care_facility_ride
            WHERE LinkID IN (SELECT LinkID FROM link
                             WHERE RiderUserID = $safe_user_id AND 
                                   DATE(DesiredArrivalTime) >= DATE($safe_date)
                             UNION 
                             SELECT LinkID FROM link_history
                             WHERE RiderUserID = $safe_user_id AND 
                                   DATE(DesiredArrivalTime) >= DATE($safe_date))"; 

    $result = mysql_query($sql);

    if ($result) {
       return TRUE; 
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Could not disconnect CF rides for user $user_id ($disconnect_date)", $sql);
        return FALSE;
    }
}

function set_user_care_facility_disconnect_date($user_id, $disconnect_date = NULL) {
    $safe_user_id = mysql_real_escape_string($user_id);
    $safe_date = (is_null($disconnect_date)) ? 'NOW()' : "'" . mysql_real_escape_string($disconnect_date) . "'";

    $sql = "UPDATE care_facility_user SET DisconnectDate = $safe_date
            WHERE UserID = $safe_user_id";

    $result = mysql_query($sql);

    if ($result) {
       return TRUE; 
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Could not disconnect user $user_id from care_facilities ($disconnect_date)", $sql);
        return FALSE;
    }
}


function connect_care_facility_to_link($care_facility_id, $link_id) {
    // TODO:  higher-level version of this to connect a CF to all links for 
    //        connected users on a day?  Might make sense.

    $safe_facility_id = mysql_real_escape_string($care_facility_id);
    $safe_link_id = mysql_real_escape_string($link_id);

    $sql = "INSERT INTO care_facility_ride (CareFacilityID, LinkID) 
            VALUES ($safe_facility_id, $safe_link_id)";

    $result = mysql_query($sql);

    if ($result) {
       return TRUE; 
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Could not link facility $care_facility_id to ride $link_id", $sql);
        return FALSE;
    }
}


/**
 * Returns the number of links associated with a care facility between the start
 * and end dates, inclusive.  
 * The start timestamp is midnight (0:00) on the start date.
 * The end timestamp is midnight (24:00) on the end date.
 * The link DesiredArrivalTime is used for the calculation.
 *
 * @param care_facility_id ID of facility
 * @param start_date Start of date range.  YYYY-MM-DD format.
 * @param end_date End of date range.  YYYY-MM-DD format.
 * @param int count of links or FALSE on error
 */
function get_care_facility_link_count($care_facility_id, $start_date, $end_date) {
    $safe_facility_id = mysql_real_escape_string($care_facility_id);
    $safe_start_date = mysql_real_escape_string("$start_date 0:00:00");
    $safe_end_date = mysql_real_escape_string("$end_date 24:00:00");

    $sql = "SELECT COUNT(*) AS link_count
            FROM care_facility_ride NATURAL JOIN link
            WHERE CareFacilityID = $safe_facility_id AND
                  DesiredArrivalTime BETWEEN '$safe_start_date' AND '$safe_end_date'";

    $result = mysql_query($sql);

    if ($result) {
        $row = mysql_fetch_array($result);
        return $row['link_count'];
    } else {
        rc_log_db_error(PEAR_LOG_WARNING, mysql_error(),
                        "Could not get link count for $care_facility_id", $sql);
        return FALSE;
    }

}

/**
 * Returns the link details for links associated with a care facility between the start
 * and end dates, inclusive.  
 * The start timestamp is midnight (0:00) on the start date.
 * The end timestamp is midnight (24:00) on the end date.
 * The link DesiredArrivalTime is used for the calculation.
 * Link details are all link contents and the name of the rider.
 *
 * @param care_facility_id ID of facility
 * @param start_date Start of date range.  YYYY-MM-DD format.
 * @param end_date End of date range.  YYYY-MM-DD format.
 * @param array of hashes (keys are column names) or FALSE on error.
 */
function get_care_facility_links($care_facility_id, $start_date, $end_date) {
    $links = FALSE;

    $safe_facility_id = mysql_real_escape_string($care_facility_id);
    $safe_start_date = mysql_real_escape_string("$start_date 0:00:00");
    $safe_end_date = mysql_real_escape_string("$end_date 23:59:59");

    $sql = "SELECT link_history.LinkID, link_history.LinkStatus, FromDestinationID, ToDestinationID, DesiredArrivalTime,
                   Distance, EstimatedMinutes, -sum(Cents) as QuotedCents, users.UserID,
                   Title, FirstName, MiddleInitial, LastName, Suffix
            FROM care_facility_ride NATURAL JOIN link_history
			     LEFT JOIN users ON link_history.RiderUserID = users.UserID 
                 NATURAL JOIN person_name
				 LEFT JOIN completed_link_ledger_xref clxr on clxr.LinkID = link_history.LinkID
				 LEFT JOIN 
				   (SELECT * FROM ledger WHERE SubAccount = 'GENERAL' AND EntityID = $safe_facility_id AND EffectiveDate BETWEEN '$safe_start_date' AND '$safe_end_date') as L
				 ON L.LedgerEntryID = clxr.LedgerEntryID
            WHERE CareFacilityID = $safe_facility_id AND
                  DesiredArrivalTime BETWEEN '$safe_start_date' AND '$safe_end_date' AND
                  (LinkStatus = 'COMPLETE' OR LinkStatus = 'CANCELEDLATE') AND 
				  (CustomTransitionID IS NULL OR CustomTransitionType = 'RIDER')
				  Group by link_history.LinkID
            ORDER BY link_history.RiderUserID, DesiredArrivalTime ASC ";
    // $sql = "SELECT link_history.LinkID, link_history.LinkStatus, FromDestinationID, ToDestinationID, DesiredArrivalTime,
                   // Distance, EstimatedMinutes, QuotedCents, users.UserID,
                   // Title, FirstName, MiddleInitial, LastName, Suffix
            // FROM care_facility_ride NATURAL JOIN link_history, 
                 // users NATURAL JOIN person_name
            // WHERE CareFacilityID = $safe_facility_id AND
                  // DesiredArrivalTime BETWEEN '$safe_start_date' AND '$safe_end_date' AND
                  // link_history.RiderUserID = users.UserID AND 
                  // (LinkStatus = 'COMPLETE' OR LinkStatus = 'CANCELEDLATE') AND 
				  // (CustomTransitionID IS NULL OR CustomTransitionType = 'RIDER')
            // ORDER BY DesiredArrivalTime ASC ";

    $result = mysql_query($sql);
    if ($result) {
        $links = array();
        while ($row = mysql_fetch_array($result)) {
            $links[] = $row;
        }
    } else {
        rc_log_db_error(PEAR_LOG_WARNING, mysql_error(),
                        "Could not get links for $care_facility_id", $sql);
        $links = FALSE;
    }

    return $links;
}

function get_care_facility_ledger_entries_for_invoice( $entity_id, $start_date , $end_date) {
    $safe_entity_id = mysql_real_escape_string($entity_id);

    $date_clause = "BETWEEN '" . mysql_real_escape_string("$start_date 0:00:00") . "' AND '" .
                                            mysql_real_escape_string("$end_date 23:59:59") . "'";
   
    $sql = "SELECT * FROM ledger 
            WHERE EntityType = 'CAREFACILITY' AND
                  EntityID = $safe_entity_id AND
                  EffectiveDate $date_clause AND
				  LedgerEntryID NOT IN (SELECT L.LedgerEntryID
                                        FROM care_facility_ride NATURAL JOIN link_history
										LEFT JOIN completed_link_ledger_xref clxr on clxr.LinkID = link_history.LinkID
				                        LEFT JOIN (SELECT * FROM ledger WHERE SubAccount = 'GENERAL' AND EntityID = $safe_entity_id AND EffectiveDate $date_clause) as L
										ON L.LedgerEntryID = clxr.LedgerEntryID
										WHERE EntityType = 'CAREFACILITY' AND
										      CareFacilityID = $safe_entity_id AND
										      DesiredArrivalTime $date_clause  AND
										      (LinkStatus = 'COMPLETE' OR LinkStatus = 'CANCELEDLATE') AND 
										      (CustomTransitionID IS NULL OR CustomTransitionType = 'RIDER'))
			GROUP BY LedgerEntryID
			ORDER BY EffectiveDate ASC, LedgerEntryTime ASC, LedgerEntryID ASC";
	// echo str_replace("\n","<br/>",$sql);
    $result = mysql_query($sql);
    if ($result) {
        $ledger_entries = array();
        while ($row = mysql_fetch_array($result)) {
            $ledger_entries[] = $row;
        }

        return $ledger_entries;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error getting ledger entries for $entity_type $entity_id ($start_date : $end_date)", $sql);
    }
    return FALSE;
}

/**
 * Returns the monthly fee for a number of rides.
 * @return cents
 */
function get_care_facility_monthly_ride_fee($link_count) {
    $fee = 0;

    if ($link_count <= 6) {
        $fee = 0;
    } elseif ($link_count <= 12) {
        $fee = 500;
    } elseif ($link_count <= 20) {
        $fee = 1000;
    } elseif ($link_count <= 30) {
        $fee = 1500;
    } elseif ($link_count <= 45) {
        $fee = 2000;
    } elseif ($link_count <= 60) {
        $fee = 2500;
    } elseif ($link_count <= 80) {
        $fee = 3000;
    } elseif ($link_count <= 100) {
        $fee = 3500;
    } elseif ($link_count <= 125) {
        $fee = 4000;
    } elseif ($link_count <= 150) {
        $fee = 4500;
    } elseif ($link_count <= 200) {
        $fee = 5000;
    } else {
        $excess_links = $link_count - 201;

        $buckets = floor($excess_links / 50);

        $fee = 5000 + (($buckets + 1) * 500);
    }

    return $fee;
}

function delete_care_facility( $facility_id ){
	$safe_facility_id = mysql_real_escape_string($facility_id);
	
	//$sql = "DELETE FROM `care_facility` WHERE `CareFacilityID` = $safe_facility_id LIMIT 1;";
	$sql = "Update care_facility set CareFacilityStatus = 'Inactive' where CareFacilityID = $facility_id";
	$result = mysql_query($sql);
	
	if(!$result){
		rc_log_db_error(PEAR_LOG_WARNING, mysql_error(),
                        "Could not delete care facility for $facility_id", $sql);
		return FALSE;
	}
	return TRUE;
}

function if_current_user_has_care_facility( $facility_id ){
	$safe_facility_id = mysql_real_escape_string($facility_id);
	$safe_user_id = mysql_real_escape_string(get_current_user_id());
	
	$sql = "SELECT * FROM `care_facility_user` NATURAL JOIN `user_role` 
            WHERE `UserID` =$safe_user_id AND 
                  `CareFacilityID` =$safe_facility_id AND 
                  (`Role`= 'CareFacilityAdmin' || `Role` = 'FullAdmin' || `Role` = 'SuperUser')  AND
                  NOW() BETWEEN ConnectionDate AND IFNULL(DisconnectDate, DATE('3000-01-01'))
                  LIMIT 1;";
	$result = mysql_query($sql);
	
	if($result){
		if(mysql_num_rows($result) >= 1)
			return TRUE;
		return FALSE;
	} else {
		rc_log_db_error(PEAR_LOG_WARNING, mysql_error(),
                        "Could not get care facility for $facility_id and user $safe_user_id", $sql);
		return FALSE;
	}
}

function get_care_facility_contacts( $facility_id ){
	$safe_facility_id = mysql_real_escape_string($facility_id);
	
	$sql = "SELECT * FROM `care_facility_contact` WHERE `CareFacilityID` =$safe_facility_id;";
	$result = mysql_query($sql);
	
	if($result){
		if(mysql_num_rows($result) < 1)
			return FALSE;
		return $result;
	} else {
		rc_log_db_error(PEAR_LOG_WARNING, mysql_error(),
                        "Could not get care facility contacts for $facility_id", $sql);
		return FALSE;
	}
}

function get_care_facility_contacts_array($facility_id) {
    $contact_list = FALSE;
	$safe_facility_id = mysql_real_escape_string($facility_id);
	
	$sql = "SELECT * FROM care_facility_contact WHERE CareFacilityID = $safe_facility_id";
	$result = mysql_query($sql);
	
	if ($result) {
        $contact_list = array();
        while ($row = mysql_fetch_array($result)) {
            $contact_list[] = $row;
        }
	} else {
		rc_log_db_error(PEAR_LOG_WARNING, mysql_error(),
                        "Could not get care facility contacts for $facility_id", $sql);
	}

    return $contact_list;
}

function get_care_facility_contact($facility_id, $name_id) {
	$sql = "select * from care_facility_contact, person_name, phone, email
		WHERE PersonNameID = ContactNameID
		AND PhoneID = ContactPhoneID
		AND EmailID = ContactEmailID
		AND CareFacilityID = $facility_id and ContactNameID = $name_id";
	$result = mysql_query($sql);
	return mysql_fetch_assoc($result);
}

function add_care_facility_contact( $facility_id , $name_id , $phone_id , $email_id , $contact_role , $contact_title ){
	$safe_facility_id = mysql_real_escape_string($facility_id);
	$safe_contact_name = mysql_real_escape_string($name_id);
	$safe_contact_phone = mysql_real_escape_string($phone_id);
	$safe_contact_email = mysql_real_escape_string($email_id);
	$safe_contact_role = mysql_real_escape_string($contact_role);
	$safe_contact_title = mysql_real_escape_string($contact_title);
	
	$sql = "INSERT INTO `care_facility_contact` (`CareFacilityID`, `ContactNameID`, `ContactPhoneID`,
												 `ContactEmailID`, `ContactRole`, `ContactTitle`)
										 VALUES ('$safe_facility_id', '$safe_contact_name', '$safe_contact_phone',
												 '$safe_contact_email', '$safe_contact_role', '$safe_contact_title');";
	$result = mysql_query($sql);
	
	if($result){
		return TRUE;
	} else {
		rc_log_db_error(PEAR_LOG_WARNING, mysql_error(),
                        "Could not create care facility contact for $facility_id", $sql);
	}
}

function remove_all_user_care_facility_connections( $facility_id ){
	$safe_facility_id = mysql_real_escape_string($facility_id);
	$sql = "SELECT `UserID`, `CareFacilityID`, `Role` 
			FROM `care_facility_user` NATURAL JOIN `user_role` 
			WHERE `CareFacilityID` =$safe_facility_id 
			AND (`Role` != 'FullAdmin' AND `Role` != 'SuperUser' AND `Role` != 'Driver' AND `Role` != 'Franchisee');";
	$result = mysql_query($sql);
	
	if($result){
		while($row = mysql_fetch_array($result))
			set_user_inactive( $row['UserID'] );
	} else {
		rc_log_db_error(PEAR_LOG_WARNING, mysql_error(),
                        "Could not get users for facility $facility_id", $sql);
	}
	$sql = "DELETE FROM `care_facility_user` WHERE `CareFacilityID` =$facility_id;";
	$result = mysql_query($sql);
	
	if(!$result){
		rc_log_db_error(PEAR_LOG_WARNING, mysql_error(),
                        "Could not remove all users of facility $facility_id", $sql);
	}
}
function remove_all_contacts_for_facility( $facility_id ){
	$safe_facility_id = mysql_real_escape_string($facility_id);
	$sql = "SELECT * FROM `care_facility_contact` WHERE `CareFacilityID` = $safe_facility_id;";
	$result = mysql_query($sql);
	$sql = "DELETE FROM `care_facility_contact` WHERE `CareFacilityID` = $safe_facility_id;";
	$result2 = mysql_query($sql);
	if($result2){
		if($result){
			while($row = mysql_fetch_array($result)){
				delete_name( $row['ContactNameID'] );
				delete_phone_number( $row['ContactPhoneID'] );
				delete_email_address( $row['ContactEmailID'] );
			}
		} else {
			rc_log_db_error(PEAR_LOG_WARNING, mysql_error(),
                        "Could not get all contacts of facility $facility_id", $sql);
		}
	} else {
		rc_log_db_error(PEAR_LOG_WARNING, mysql_error(),
                        "Could not remove all contacts of facility $facility_id", $sql);
	}
}
function remove_all_facility_ride_links( $facility_id ){
	$sql = "DELETE FROM `care_facility_ride` WHERE `CareFacilityID` = $safe_facility_id;";
	$result = mysql_query($sql);
	
	if(!$result){
		rc_log_db_error(PEAR_LOG_WARNING, mysql_error(),
                        "Could not remove all ride links of facility $facility_id", $sql);
		return FALSE;
	} else {
		return true;
	}
}
function get_first_user_care_facility( $user_id ){
	$safe_user_id = mysql_real_escape_string( $user_id );
	$sql = "SELECT * FROM `care_facility_user` WHERE `UserID` =$safe_user_id 
                AND NOW() BETWEEN ConnectionDate AND IFNULL(DisconnectDate, DATE('3000-01-01'))
                LIMIT 1;";
	$result = mysql_query($sql) or die(mysql_error() . ' : ' . $sql);
	if($result){
		if(mysql_num_rows($result) < 1)
			return FALSE;
		$row = mysql_fetch_array($result);
		return $row['CareFacilityID'];	
	} else {
		rc_log_db_error(PEAR_LOG_WARNING, mysql_error(),
                        "Could not get first care facility of user $user_id", $sql);
		return FALSE;
	}
	
	
}

function get_user_current_care_facility($user_id, $at_date = NULL) {
	
    $safe_user_id = mysql_real_escape_string($user_id);
	if($at_date !== NULL)
		$safe_date = "'" . mysql_real_escape_string($at_date) . "'";
	else
		$safe_date = "NOW()";
    $sql = "SELECT CareFacilityID, UserID, ConnectionDate, DisconnectDate
            FROM care_facility_user
            WHERE UserID = $safe_user_id AND
                  $safe_date BETWEEN ConnectionDate AND IFNULL(DisconnectDate, DATE('3000-01-01'))";
    
    $result = mysql_query($sql);

    if ($result) {
        if (mysql_num_rows($result) > 1) {
            rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                            "User $user_id has multiple current care facilities.", $sql);
        }

        if ($row = mysql_fetch_array($result)) {
            return $row['CareFacilityID'];
        } 
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Error retrieving current care facility for $user_id", $sql);
    }

    return FALSE;

}

function is_real_care_facility( $facility_id ){
	$safe_facility_id = mysql_real_escape_string($facility_id);
	$sql = "SELECT * FROM `care_facility` WHERE `CareFacilityID` =$safe_facility_id LIMIT 1;";
	$result = mysql_query($sql);
	
	if($result){
		if(mysql_num_rows($result) < 1)
			return FALSE;
		return TRUE;
	} else {
		rc_log_db_error(PEAR_LOG_WARNING, mysql_error(),
                        "Could not check if facility $facility_id is a real facility", $sql);
		return FALSE;
	}
}
function care_facility_admin_has_rights_over_user($user_id){
	$franchise = get_current_user_franchise();
	if(!current_user_has_role($franchise, 'CareFacilityAdmin')){
		return FALSE;
	}
	$safe_user_id = mysql_real_escape_string($user_id);
	$safe_current_user_id = mysql_real_escape_string(get_current_user_id());
	
	$sql = "SELECT `CareFacilityID` FROM `care_facility_user` WHERE `UserID` = $safe_user_id OR `UserID` = $safe_current_user_id LIMIT 2";
	$result = mysql_query($sql) or die(mysql_error());
	if($result){
		if(mysql_num_rows($result) < 2){
			return FALSE;
		}
			
		$row1 = mysql_fetch_array($result);
		$row2 = mysql_fetch_array($result);
		if($row1['CareFacilityID'] == $row2['CareFacilityID']){
			return TRUE;
		}
		return FALSE;
	} else {
		rc_log_db_error(PEAR_LOG_WARNING, mysql_error(),
                        "Could not check if facility admin $safe_current_user_id has rights over user $user_id", $sql);
		return FALSE;
	}
}
function get_care_facility_users($care_facility, $status = "ApplicationStatus = 'APPROVED' AND "){
	$safe_status = mysql_real_escape_string($status);
	$safe_care_facility = mysql_real_escape_string($care_facility);
	$sql = "SELECT * FROM (`care_facility_user`LEFT JOIN `user_role` ON care_facility_user.UserID = user_role.UserID ) LEFT JOIN  users ON users.UserID = care_facility_user.UserID	
            WHERE  $status 
                  Role = 'Rider' AND 
                  CareFacilityID =$safe_care_facility AND
                  NOW() BETWEEN ConnectionDate AND IFNULL(DisconnectDate, DATE('3000-01-01')) GROUP BY care_facility_user.UserID
                  ";
	$result = mysql_query($sql);
	
	if($result){
		if(mysql_num_rows($result) < 1)
			return FALSE;
		while($row = mysql_fetch_array($result))
			$users[] = $row;
			return $users;
	} else {
		rc_log_db_error(PEAR_LOG_WARNING, mysql_error(),
                        "Could not get users for care facility $care_facility", $sql);
	}
	
}


function set_care_facility_annual_fee_payment_date( $facility_id, $date=NULL ) {
	$safe_facility_id = mysql_real_escape_string($facility_id);

    $facility_info = get_care_facility($facility_id); 
    $extant_date = $facility_info['AnnualFeePaymentDate'];
    $safe_date = mysql_real_escape_string(calculate_new_annual_fee_payment_date($date, $extant_date));

    $sql = "UPDATE care_facility SET AnnualFeePaymentDate = '$safe_date'
            WHERE CareFacilityID = $safe_facility_id";

    $result = mysql_query($sql);
    if ($result) {
        rc_log(PEAR_LOG_INFO, "Set the annual fee date for care facility $facility_id to $safe_date");
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 'Error setting CF annual fee date', $sql);
    }

    return $result;
}

function get_care_facility_annual_fee_payment_dates_by_user() {
    $sql = "SELECT UserID, CareFacilityID, AnnualFeePaymentDate
            FROM care_facility_user NATURAL JOIN care_facility
            WHERE NOW() BETWEEN ConnectionDate AND IFNULL(DisconnectDate, DATE('3030-1-1'))";

    $result = mysql_query($sql);

	if ($result) {
        $result_rows = array();
        while ($row = mysql_fetch_array($result)) {
            $result_rows[$row['UserID']] = $row;
        }
		return $result_rows;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get CF annual fee payment dates by user", $query);
	}

    return FALSE;
}
function get_all_active_care_facility_info($franchise) {
    $safe_franchise = mysql_real_escape_string($franchise);

    $sql = "SELECT CareFacilityName, CareFacilityID, AnnualFeePaymentDate, RechargeThreshold, ApplicationDate, WelcomePackageSentDate, FirstRideFollowupDate, CareFacilityWaver, PictureWaiver FROM care_facility WHERE CareFacilityStatus = 'Active' AND  FranchiseID = $safe_franchise";

    $result = mysql_query($sql) or die($sql);

	if ($result) {
        $result_rows = array();
        $cf_fee_dates = get_care_facility_annual_fee_payment_dates_by_user();
        
       	for ($i = 0; $row = mysql_fetch_array($result); $i++) {
            $result_rows[$i] = $row;
            $result_rows[$i]['DaysOnAnnualFee'] = floor(((strtotime($result_rows[$i]['AnnualFeePaymentDate']) + (60 * 60 * 24 * 365)) - time()) / (60 * 60 * 24));
            if($result_rows[$i]['AnnualFeePaymentDate'] == null)
               $result_rows[$i]['sort'] = 1;
            else if($result_rows[$i]['DaysOnAnnualFee'] > 30)
            	$result_rows[$i]['sort'] = -2;	
            else if($result_rows[$i]['DaysOnAnnualFee'] <= 30 && $result_rows[$i]['DaysOnAnnualFee'] >= 0)
            	$result_rows[$i]['sort'] = -1;
           	else 
           		$result_rows[$i]['sort'] = 0;
        }
		return multisort($result_rows, array('sort','CareFacilityName'));
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get all active Care Facilities", $sql);
	}

    return FALSE;
}

         //*********
function get_next_travel_times_for_facilities_list($riders = array()) {
	if(!is_array($riders))
		return array();
    $times = array();
	$sql = "SELECT * FROM (`care_facility_user`LEFT JOIN `user_role` ON care_facility_user.UserID = user_role.UserID ) LEFT JOIN  users ON users.UserID = care_facility_user.UserID	
            WHERE  $status 
                  Role = 'Rider' AND 
                  CareFacilityID =$safe_care_facility AND
                  NOW() BETWEEN ConnectionDate AND IFNULL(DisconnectDate, DATE('3000-01-01')) GROUP BY care_facility_user.UserID
                  ";
    $sql = "SELECT RiderUserID, FranchiseID, MIN(DesiredArrivalTime) AS DesiredArrivalTime,PrePadding, PostPadding, EstimatedMinutes FROM link WHERE RiderUserID IN ( " . implode(", ", $riders) . " )
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


?>
