<?php 

require_once('include/database.php');
require_once('include/rc_log.php');

function get_large_facilities($franchise_id) {
    $safe_franchise_id = mysql_real_escape_string($franchise_id);

    $sql = "SELECT LargeFacilityID, LargeFacilityName, FacilityAddressID, 
                   Address1, Address2, City, State, ZIP5, ZIP4, Latitude, Longitude, IsVerified, VerifySource
            FROM large_facility INNER JOIN address ON FacilityAddressID = AddressID
            WHERE FranchiseID = $safe_franchise_id
            ORDER BY LargeFacilityName ASC";

    $result = mysql_query($sql);

    if ($result) {
        $rows = array();
        while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
            $rows[$row['LargeFacilityID']] = $row;
        }
        return $rows;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), "Error retrieving large facilities for $franchise_id", $sql);
        return FALSE;
    }
}

function get_large_facility($facility_id) {
    $safe_facility_id = mysql_real_escape_string($facility_id);

    $sql = "SELECT LargeFacilityID, LargeFacilityName, FranchiseID, FacilityAddressID, RiderUserID, 
                   DefaultEmailID, FacilityDestinationID
            FROM large_facility
            WHERE LargeFacilityID = $safe_facility_id
            ORDER BY LargeFacilityName ASC";

    $result = mysql_query($sql);

    if ($result) {
        $row = mysql_fetch_array($result, MYSQL_ASSOC);
        return $row;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), "Error retrieving large facility $facility_id", $sql);
        return FALSE;
    }
}

function get_large_facility_contacts($facility_id) {
    $safe_facility_id = mysql_real_escape_string($facility_id);

    $sql = "SELECT ContactNameID, ContactPhoneID, ContactEmailID, ContactRole, ContactTitle
            FROM large_facility_contact
            WHERE LargeFacilityID = $safe_facility_id";

    $result = mysql_query($sql);

    if ($result) {
        $rows = array();
        while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error retrieving large facility contacts for $facility_id", $sql);
        return FALSE;
    }
}

function add_large_facility_contact( $facility_id, $name_id, $phone_id, 
                                     $email_id, $contact_role, $contact_title ) {
    $safe_facility_id = mysql_real_escape_string($facility_id);
    $safe_name_id = mysql_real_escape_string($name_id);
    $safe_phone_id = mysql_real_escape_string($phone_id);
    $safe_email_id = mysql_real_escape_string($email_id);
    $safe_contact_role = mysql_real_escape_string($contact_role);
    $safe_contact_title = mysql_real_escape_string($contact_title);
    
    $sql = "INSERT INTO large_facility_contact (LargeFacilityID, ContactNameID, ContactPhoneID,
                                                ContactEmailID, ContactRole, ContactTitle)
                                        VALUES ($safe_facility_id, $safe_name_id, $safe_phone_id,
                                                $safe_email_id, '$safe_contact_role', '$safe_contact_title');";
    $result = mysql_query($sql);
    
    if ($result) {
        return TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not create large facility contact for $facility_id", $sql);
    }
}


function add_large_facility($lf_name, $lf_franchise_id, $lf_address_id,
                            $lf_rider_id, $lf_email_id, $lf_dest_id ) {
    $safe_name = mysql_real_escape_string($lf_name);
    $safe_franchise_id = mysql_real_escape_string($lf_franchise_id);
    $safe_address_id = mysql_real_escape_string($lf_address_id);
    $safe_rider_id = mysql_real_escape_string($lf_rider_id);
    $safe_email_id = mysql_real_escape_string($lf_email_id);
    $safe_dest_id = mysql_real_escape_string($lf_dest_id);

    $sql = "INSERT INTO large_facility (LargeFacilityName, FranchiseID, FacilityAddressID,
                                        RiderUserID, DefaultEmailID, FacilityDestinationID)
            VALUES('$safe_name', $safe_franchise_id, $safe_address_id, $safe_rider_id,
                    $safe_email_id, $safe_dest_id)";
    $result = mysql_query($sql);

    if ($result) {
        return mysql_insert_id();
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), "Error adding new large facility", $sql);
        return FALSE;
    }
}

function connect_user_to_large_facility($user_id, $large_facility_id) {
    $safe_user_id = mysql_real_escape_string($user_id);
    $safe_facility_id = mysql_real_escape_string($large_facility_id);

    // TODO:  This does not currently re-associate (update), just associate (insert).
    $sql = "INSERT INTO large_facility_user (LargeFacilityID, UserID)
            VALUES ($safe_facility_id, $safe_user_id)";

    $result = mysql_query($sql);

    if ($result) {
        return TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Could not connect large facility $large_facility_id to user $user_id", $sql);
        return FALSE;
    }
}

function get_large_facility_riders($facility_id) {
    $safe_facility_id = mysql_real_escape_string($facility_id);

    $sql = "SELECT LfrRiderID, LargeFacilityID, PersonNameID, PersonAddressID, DateOfBirth, 
                   BackgroundCheck, HasFelony,
                   Title, FirstName, MiddleInitial, LastName, Suffix
            FROM large_facility_rider NATURAL JOIN person_name
            WHERE LargeFacilityID = $safe_facility_id
            ORDER BY LastName, FirstName, MiddleInitial, LfrRiderID";

    $result = mysql_query($sql);

    if ($result) {
        $rows = array();
        while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error retrieving large facility riders for $facility_id", $sql);
        return FALSE;
    }
}

function get_large_facility_rider($lf_rider_id) {
    $safe_id = mysql_real_escape_string($lf_rider_id);

    $sql = "SELECT LfrRiderID, LargeFacilityID, PersonNameID, PersonAddressID, DateOfBirth, 
                   BackgroundCheck, HasFelony,
                   Title, FirstName, MiddleInitial, LastName, Suffix
            FROM large_facility_rider NATURAL JOIN person_name
            WHERE LfrRiderID = $safe_id";

    $result = mysql_query($sql);

    if ($result) {
        $row = mysql_fetch_array($result, MYSQL_ASSOC);
        return $row;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error retrieving large facility rider $lf_rider_id", $sql);
        return FALSE;
    }
}

function add_large_facility_rider($facility_id, $name_id, $date_of_birth, $address_id = NULL) {
    $safe_facility_id = mysql_real_escape_string($facility_id);
    $safe_name_id = mysql_real_escape_string($name_id);
    $safe_dob = mysql_real_escape_string($date_of_birth);
    $safe_address_id = (is_null($address_id)) ? 'NULL' : mysql_real_escape_string($address_id);
    
    $sql = "INSERT INTO large_facility_rider (LargeFacilityID, PersonNameID,
                                              DateOfBirth, PersonAddressID)
                                        VALUES ($safe_facility_id, $safe_name_id, '$safe_dob',
                                                $safe_address_id);";
    $result = mysql_query($sql);
    
    if ($result) {
        return mysql_insert_id();
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not create large facility rider for $facility_id", $sql);
        return FALSE;
    }
}


/**
 * Gets a list of large facilities for the admin to select from
 */
function get_admin_work_as_lf_list( $franchise_id, $sort_order='L' ) {
    $lf_list = array(); 
    if ($franchise_id == 'ALLFRANCHISES') {
        $where_clause = '';
    } else {
        $safe_franchise_id = mysql_real_escape_string($franchise_id);
        $where_clause = "WHERE large_facility.FranchiseId = $safe_franchise_id";
    }

    switch ($sort_order) {
        case 'F':
            $sort_clause = 'ORDER BY LargeFacilityName';
            break;
        case 'L':
        default:
            $sort_clause = 'ORDER BY LargeFacilityName';
    }

    $sql = "SELECT LargeFacilityID, LargeFacilityName FROM large_facility
            $where_clause
            $sort_clause";

    $result = mysql_query($sql);

    if ($result) {
        while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
             $lf_list[] = $row;
        }
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error getting large facility work list for $franchise", $sql);
    }

    return $lf_list;
}


function get_large_facility_id_for_user($user_id) {
    $safe_uid = mysql_real_escape_string($user_id);

    $sql = "SELECT LargeFacilityID, UserID
            FROM large_facility_user
            WHERE UserID = $safe_uid";

    $result = mysql_query($sql);

    if ($result) {
        $row = mysql_fetch_array($result, MYSQL_ASSOC);
        return $row['LargeFacilityID'];
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error retrieving large facility id for user $user_id", $sql);
        rc_log_db_error(PEAR_LOG_ERR, 'CONTINUED', debug_backtrace(), '');         
        return FALSE;
    }
}

function get_working_large_facility() {
    if (isset($_SESSION['LF_Facility_ID'])) {
		return $_SESSION['LF_Facility_ID'];
    }
    return FALSE;
}

function connect_large_facility_to_link($large_facility_id, $large_facility_rider_id, $link_id) {
    $safe_facility_id = mysql_real_escape_string($large_facility_id);
    $safe_lfr_rider_id = mysql_real_escape_string($large_facility_rider_id);
    $safe_link_id = mysql_real_escape_string($link_id);

    $sql = "INSERT INTO large_facility_link (LargeFacilityID, LinkID, LfrRiderID)
                                        VALUES ($safe_facility_id, $safe_link_id,
                                                $safe_lfr_rider_id)";
    $result = mysql_query($sql);
    
    if ($result) {
        return TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not connect link $link_id to $large_facility_id/$large_facility_rider_id", $sql);
        return FALSE;
    }
}

function get_large_facility_rider_info_for_link($link_id) {
    $safe_link_id = mysql_real_escape_string($link_id);

    $sql = "SELECT LinkID, LfrRiderID, LargeFacilityID, PersonNameID, PersonAddressID, DateOfBirth, 
                   BackgroundCheck, HasFelony,
                   Title, FirstName, MiddleInitial, LastName, Suffix,
                   LargeFacilityName
            FROM large_facility_link NATURAL JOIN large_facility_rider NATURAL JOIN person_name
                 NATURAL JOIN large_facility
            WHERE large_facility_link.LinkID = $safe_link_id";

    $result = mysql_query($sql);

    if ($result) {
        $row = mysql_fetch_array($result, MYSQL_ASSOC);
        return $row;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error retrieving LF rider info for link $link_id", $sql);
        return FALSE;
    }
}

function get_lf_rider_person_info_string($lf_rider_info, $link_to_rider = FALSE) {
    if (is_null($lf_rider_info)) {
        $rider_string = "Bad Parameter GlfRPI";
    } else {
        $rider_name = get_displayable_person_name_string($lf_rider_info);
        
       /* if ($link_to_rider) {
            $rider_string = "<a href=\"account.php?id={$rider['UserID']}\">$rider_name</a><br />";
        } else {*/
            $rider_string = "$rider_name<br />";
        /*}*/

        $rider_string .= "({$lf_rider_info['LargeFacilityName']})<br />";

        if ($lf_rider_info['PersonAddressID']) {
            $address = get_address($lf_rider_info['PersonAddressID']);
            $rider_string .= '<span>' . 
                          "{$address['Address1']}<br />" . 
                          (($address['Address2']) ? $address['Address2'] : '') . '<br />' .
                          "{$address['City']}, {$address['State']}  {$address['ZIP5']}" . 
                          '</span>';
        }
    }

    return $rider_string;
}

?>
