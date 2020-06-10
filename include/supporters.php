<?php

require_once('include/database.php');
require_once('include/rc_log.php');


function get_supporter_charities($supporter_user_id) {
    $safe_uid = mysql_real_escape_string($supporter_user_id);

    $sql = "SELECT CharityID, CharityName
            FROM charity
            WHERE CharityID IN (1, 2, 3) OR
                  CharityID IN (SELECT CharityID FROM supporter_charity
                                WHERE SupporterUserID = $safe_uid)
            ORDER BY CharityID ASC";
    
    $result = mysql_query($sql);
    if ($result) {
        $charities = array();
        while ($row = mysql_fetch_array($result)) {
            $charities[] = $row;
        }
    } else {
        $charities = FALSE;
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get charities for $supporter_user_id", $sql);
    }

    return $charities;
}

function get_supporter_charities_with_ytd($supporter_user_id) {
    $safe_uid = mysql_real_escape_string($supporter_user_id);
	$user_franchises_result = mysql_query("select FranchiseID from user_role where UserID=".$safe_uid." GROUP BY FranchiseID");
	$user_franchises = '';
	
	while($row = mysql_fetch_assoc($user_franchises_result)) {
	  if ($user_franchises=='') {
	    $user_franchises .= $row['FranchiseID'];
	  } else {
	    $user_franchises .= ','.$row['FranchiseID'];
      }
	}
	$safe_year_date = date("Y");
    $sql = "SELECT charity.CharityID, charity.Approved, CharityName, SUM(-Cents) AS YTD_Cents, AlwaysShow
            FROM charity LEFT JOIN (supporter_charity_record NATURAL JOIN ledger)
                 ON (charity.CharityID = supporter_charity_record.CharityID AND
                     supporter_charity_record.SupporterUserID = $safe_uid AND EffectiveDate >= '$safe_year_date-01-01'
                                        AND EffectiveDate < '" . ($safe_year_date + 1) . "-01-01')
            WHERE charity.CharityID IN (1) OR (charity.AlwaysShow='Y' AND charity.FranchiseID in ($user_franchises)) OR
                  charity.CharityID IN (SELECT CharityID FROM supporter_charity
                                        WHERE SupporterUserID = $safe_uid)
            GROUP BY charity.CharityID
            ORDER BY charity.OrderID, charity.CharityID ASC";
    //echo $sql."<BR>";
    $result = mysql_query($sql);
    if ($result) {
        $charities = array();
        while ($row = mysql_fetch_array($result)) {
            $charities[] = $row;
        }
    } else {
        $charities = FALSE;
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get charities with YTD for $supporter_user_id", $sql);
    }

    return $charities;
}

function get_supporter_rider_support_charity_funds($supporter_user_id) {
    
}


function get_supporter_rider_support_funds_with_ytd_by_yr($supporter_user_id, $year) {
    $safe_uid = mysql_real_escape_string($supporter_user_id);
    $sql = "SELECT users.UserID, Title, FirstName, MiddleInitial, LastName, NickName, Suffix,
                   City, State, AddressType, 
                   SUM(IFNULL(Cents, 0)) AS YTD_Cents,
				   ledger.EntityType, ledger.EntityID, supporter_rider.BeginDate, supporter_rider.EndDate
            FROM (supporter_rider INNER JOIN 
                        (users NATURAL JOIN person_name NATURAL JOIN user_address NATURAL JOIN address) 
                  ON supporter_rider.RiderUserID = users.UserID) LEFT JOIN
                 (supporter_rider_record INNER JOIN ledger ON 
                        supporter_rider_record.RiderLedgerEntryID = ledger.LedgerEntryID and ledger.EffectiveDate>='$year-01-01' and ledger.EffectiveDate<'".($year+1)."-01-01') ON
                    (users.UserID = supporter_rider_record.RiderUserID AND
                     supporter_rider_record.SupporterUserID = $safe_uid)
            WHERE supporter_rider.SupporterUserID = $safe_uid AND
                  user_address.AddressID = (SELECT MIN(user_address.AddressID) 
                                            FROM user_address WHERE 
                                            UserID = supporter_rider.RiderUserID)
            GROUP BY users.UserID";

    $result = mysql_query($sql);
    if ($result) {
        $supportees = array();
        while ($row = mysql_fetch_array($result)) {
            $supportees[] = $row;
        }
    } else {
        $supportees = FALSE;
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get supportees with YTD for $supporter_user_id", $sql);
    }

    return $supportees;
}



function get_supporter_rider_support_funds_with_ytd($supporter_user_id) {
    $safe_uid = mysql_real_escape_string($supporter_user_id);
    $sql = "SELECT users.UserID, Title, FirstName, MiddleInitial, LastName, Suffix,
                   City, State, AddressType, 
                   SUM(IFNULL(Cents, 0)) AS YTD_Cents
            FROM (supporter_rider INNER JOIN 
                        (users NATURAL JOIN person_name NATURAL JOIN user_address NATURAL JOIN address) 
                  ON supporter_rider.RiderUserID = users.UserID) LEFT JOIN
                 (supporter_rider_record INNER JOIN ledger ON 
                        supporter_rider_record.RiderLedgerEntryID = ledger.LedgerEntryID) ON
                    (users.UserID = supporter_rider_record.RiderUserID AND
                     supporter_rider_record.SupporterUserID = $safe_uid)
            WHERE supporter_rider.SupporterUserID = $safe_uid AND
                  user_address.AddressID = (SELECT MIN(user_address.AddressID) 
                                            FROM user_address WHERE 
                                            UserID = supporter_rider.RiderUserID)
            GROUP BY users.UserID";

    $result = mysql_query($sql);
    if ($result) {
        $supportees = array();
        while ($row = mysql_fetch_array($result)) {
            $supportees[] = $row;
        }
    } else {
        $supportees = FALSE;
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get supportees with YTD for $supporter_user_id", $sql);
    }

    return $supportees;
}


function get_ytd_driver_reimbursement_amount($driver_user_id) {
    $safe_uid = mysql_real_escape_string($driver_user_id);
    $sql = "SELECT SUM(-Cents) AS YTD_Cents
            FROM driver_reimbursement_record INNER JOIN ledger
                 ON ReimbursementLedgerEntryID = LedgerEntryID
            WHERE driver_reimbursement_record.DriverUserID = $safe_uid 
            	AND Description = 'Driver Allocation: Driver Reimbursement'
            	AND EffectiveDate >= '" . date("Y") . "-1-1'";

    $result = mysql_query($sql);

    if ($result) {
        if ($row = mysql_fetch_array($result)) {
            return $row['YTD_Cents'];
        }
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get YTD reimbursement for driver $driver_user_id", $sql);
    }

    return FALSE;
}

function get_ytd_addtoriderbalance_amount($driver_user_id) {
    $safe_uid = mysql_real_escape_string($driver_user_id);
    $sql = "SELECT SUM(-Cents) AS YTD_Cents
            FROM driver_reimbursement_record INNER JOIN ledger
                 ON ReimbursementLedgerEntryID = LedgerEntryID
            WHERE driver_reimbursement_record.DriverUserID = $safe_uid 
            	AND Description = 'Driver Allocation: Rider Balance'
            	AND EffectiveDate >= '" . date("Y") . "-1-1'";

    $result = mysql_query($sql);

    if ($result) {
        if ($row = mysql_fetch_array($result)) {
            return $row['YTD_Cents'];
        }
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get YTD reimbursement for driver $driver_user_id", $sql);
    }

    return FALSE;
}

function store_driver_reimbursement_record($driver_user_id, $credit_ledger_id) {
    $safe_uid = mysql_real_escape_string($driver_user_id);
    $safe_ledger_id = mysql_real_escape_string($credit_ledger_id);

    $sql = "INSERT INTO driver_reimbursement_record (DriverUserID, ReimbursementLedgerEntryID)
            VALUES ($safe_uid, $safe_ledger_id)";

    $result = mysql_query($sql);

    if ($result) {
        return TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not store record for $safe_uid reimbursement - $credit_ledger_id", $sql);
    }
    return FALSE;
}

function store_supporter_charity_record($supporter_user_id, $charity_id, $ledger_entry_id) {
    $safe_uid = mysql_real_escape_string($supporter_user_id);
    $safe_charity = mysql_real_escape_string($charity_id);
    $safe_ledger = mysql_real_escape_string($ledger_entry_id);

    $sql = "INSERT INTO supporter_charity_record
            (SupporterUserID, CharityID, LedgerEntryID)
            VALUES ($safe_uid, $safe_charity, $safe_ledger)";

    $result = mysql_query($sql);

    if ($result) {
        return TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not store record for $safe_uid donation to $charity_id - $ledger_entry_id", $sql);
    }
    return FALSE;
}

function store_supporter_rider_record($supporter_user_id, $supporter_ledger_entry_id,
                                      $rider_user_id, $rider_ledger_entry_id) {
    $safe_supporter_uid = mysql_real_escape_string($supporter_user_id);
    $safe_supporter_ledger = mysql_real_escape_string($supporter_ledger_entry_id);
    $safe_rider_uid = mysql_real_escape_string($rider_user_id);
    $safe_rider_ledger = mysql_real_escape_string($rider_ledger_entry_id);

    $sql = "INSERT INTO supporter_rider_record
            (SupporterUserID, SupporterLedgerEntryID,
             RiderUserID, RiderLedgerEntryID)
            VALUES ($safe_supporter_uid, $safe_supporter_ledger,
                    $safe_rider_uid, $safe_rider_ledger)";

    $result = mysql_query($sql);

    if ($result) {
        return TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not store record for $safe_supporter_uid donation to rider " .
                        "$safe_rider_uid - $supporter_ledger_entry_id/$rider_ledger_entry_id", $sql);
    }
    return FALSE;
}

function store_supporter_request_for_rider($supporter_user_id, $name_string, $address_string, $phone_string) {
    $safe_uid = mysql_real_escape_string($supporter_user_id);

    $raw_rider_info_string = "Name: $name_string\nAddress: $address_string\nPhone: $phone_string";
    $safe_rider_info = mysql_real_escape_string($raw_rider_info_string);

    $sql = "INSERT INTO supporter_rider_request (SupporterUserID, RiderInfo)
            VALUES ($safe_uid, '$safe_rider_info')";

    $result = mysql_query($sql);

    if ($result) {
        return TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Unable to store supporter rider request:  $safe_uid - $raw_rider_info_string", $sql);
    }
    return FALSE;
}

function get_supporter_request_for_rider_count() {
    $sql = "SELECT COUNT(RequestID) AS The_Count FROM supporter_rider_request";

    $result = mysql_query($sql);

    if ($result) {
        $row = mysql_fetch_array($result);
        return $row['The_Count'];
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Unable to get count of supporter rider requests", $sql);
    }
    return 0;
}

function get_supporter_requests_for_rider($franchise) {
    $sql = "SELECT RequestID, SupporterUserID, RiderInfo FROM supporter_rider_request srr, `user_role` ur WHERE srr.SupporterUserID=ur.UserID and ur.FranchiseID=".$franchise."
            ORDER BY SupporterUserID";
			

    $result = mysql_query($sql);

    if ($result) {
        $requests = array();
        while ($row = mysql_fetch_array($result)) {
            $requests[] = $row;
        }
        return $requests;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Unable to get supporter rider requests", $sql);
    }
    return array();
}

function connect_supporter_to_rider($supporter_uid, $rider_uid) {
    $safe_sup_uid = mysql_real_escape_string($supporter_uid);
    $safe_rider_uid = mysql_real_escape_string($rider_uid);

    $sql = "INSERT INTO supporter_rider (SupporterUserID, RiderUserID)
            VALUES ($safe_sup_uid, $safe_rider_uid)
            ON DUPLICATE KEY UPDATE RiderUserID = $safe_rider_uid";
    // Don't want spurious errors if they're already connected

    $result = mysql_query($sql);

    if ($result) {
        return TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Unable to connect supporter $supporter_uid to rider $rider_uid", $sql);
    }
    return FALSE;
}

function delete_supporter_rider_request($request_id) {
    $safe_rid = mysql_real_escape_string($request_id);

    $sql = "DELETE FROM supporter_rider_request WHERE RequestID = $safe_rid";

    $result = mysql_query($sql);

    if ($result) {
        return TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Unable to delete supporter rider request $request_id", $sql);
    }
    return FALSE;

}

function store_driver_allocation_preference($driver_uid, $type, $id) {
    $safe_driver_uid = mysql_real_escape_string($driver_uid);
    $safe_type = mysql_real_escape_string($type);
    $safe_entity_id = mysql_real_escape_string($id);


    $sql = "INSERT INTO driver_allocation_preference
            (DriverUserID, AllocationType, AllocationID)
            VALUES ($safe_driver_uid, '$safe_type', $safe_entity_id)
            ON DUPLICATE KEY UPDATE
               AllocationType = '$safe_type', AllocationID = $safe_entity_id";

    $result = mysql_query($sql);

    if ($result) {
        return TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Unable to store allocation preference for driver $driver_uid ($type/$id)", $sql);
    }
    return FALSE;
}

function get_driver_allocation_preference($driver_uid) {
    $safe_driver_uid = mysql_real_escape_string($driver_uid);
    $sql = "SELECT DriverUserID, AllocationType, AllocationID FROM driver_allocation_preference
            WHERE DriverUserID = $safe_driver_uid";

    $result = mysql_query($sql);

    if ($result) {
        if ($row = mysql_fetch_array($result)) {
            return $row;
        }
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Unable to get driver allocation preferences for $driver_uid", $sql);
    }
    return array();
}

function get_all_driver_allocation_preferences($franchise) {
	$safe_franchise = mysql_real_escape_string($franchise);
    $sql = "SELECT DriverUserID, AllocationType, AllocationID FROM driver_allocation_preference WHERE DriverUserID IN (SELECT UserID FROM user_role WHERE FranchiseID = $safe_franchise)";

    $result = mysql_query($sql);

    if ($result) {
        $prefs = array();
        while ($row = mysql_fetch_array($result)) {
            $prefs[$row['DriverUserID']] = $row;
        }
        return $prefs;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Unable to get driver allocation preferences for all drivers", $sql);
    }
    return array();
}

function get_rider_supporting_friends($rider_uid){
	$safe_rider_uid = mysql_real_escape_string($rider_uid);
	
	$sql = "SELECT SupporterUserID UserID, Title, FirstName, MiddleInitial, LastName, Suffix FROM supporter_rider NATURAL JOIN users NATURAL JOIN person_name WHERE supporter_rider.SupporterUserID = users.UserID AND RiderUserID = $safe_rider_uid";
	$result = mysql_query($sql);
	
	if($result){
		$supporting_friends = array();
		while($row = mysql_fetch_array($result))
			$supporting_friends[] = $row;
		return $supporting_friends;
	}
}

function display_supporting_friends($supporting_friends){
	if($supporting_friends == NULL)
		return "This User Has No Supporting Friends";
	
	$html = "<ul>";
	foreach($supporting_friends as $friend){
		$html .= "<li><a id=\"{$friend['UserID']}\" class=\"User_Redirect\" href=\"make_payment.php\">" . get_displayable_person_name_string($friend) . "</a></li>";
	}
	$html .= "</ul>";
	return $html;
}
?>
