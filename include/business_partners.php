<?php

require_once('include/database.php');
require_once('include/rc_log.php');
//error_reporting(E_ALL);
function get_business_partners($franchise_id) {
    $safe_franchise_id = mysql_real_escape_string($franchise_id);

    $sql = "SELECT FranchiseID, BusinessPartnerID, Name, ContactID
            FROM business_partner
            WHERE FranchiseID = $safe_franchise_id
            ORDER BY Name ASC";

    $result = mysql_query($sql);

    if ($result) {
        $partners = array();
        while ($row = mysql_fetch_array($result)) {
            $partners[] = $row;
        }
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get business partners for $franchise_id", $sql);
        $partners = FALSE;
    }
    return $partners;
}

function add_business_partner($franchise_id, $name, $contact_id = NULL) {
    $safe_franchise_id = mysql_real_escape_string($franchise_id);
    $safe_name = mysql_real_escape_string($name);
    $safe_contact_id = (is_null($contact_id)) ? 'NULL' : mysql_real_escape_string($contact_id);

    $sql = "INSERT INTO business_partner (FranchiseID, Name, ContactID)
            VALUES ($safe_franchise_id, '$safe_name', $safe_contact_id)";

    $result = mysql_query($sql);

    if ($result) {
        $partner_id = mysql_insert_id();
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not add business partner", $sql);
        $partner_id = FALSE;
    }
    return $partner_id;
}

function get_business_partner_record($partner_id) {
    $safe_partner_id = mysql_real_escape_string($partner_id);

    $sql = "SELECT FranchiseID, BusinessPartnerID, Name, ContactID
            FROM business_partner
            WHERE BusinessPartnerID = $safe_partner_id";

    $result = mysql_query($sql);

    if ($result) {
        $row = mysql_fetch_array($result);
        return $row;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get business partner record for $partner", $sql);
    }
    return FALSE;
}

function get_business_partner_active_terms($partner_id) {
    $safe_partner_id = mysql_real_escape_string($partner_id);

    $sql = "SELECT *
            FROM business_partner_terms
            WHERE BusinessPartnerID = $safe_partner_id AND
                  EndDate >= DATE(NOW())
            ORDER BY StartDate ASC";

    $result = mysql_query($sql);

    if ($result) {
        $terms = array();
        while ($row = mysql_fetch_array($result)) {
            $terms[] = $row;
        }
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get business partner active terms for $partner_id", $sql);
        $terms = FALSE;
    }
    return $terms;
}

function get_business_partner_past_terms($partner_id) {
    $safe_partner_id = mysql_real_escape_string($partner_id);

    $sql = "SELECT *
            FROM business_partner_terms
            WHERE BusinessPartnerID = $safe_partner_id AND
                  EndDate < DATE(NOW())
            ORDER BY StartDate ASC";

    $result = mysql_query($sql);

    if ($result) {
        $terms = array();
        while ($row = mysql_fetch_array($result)) {
            $terms[] = $row;
        }
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get business partner past terms for $partner_id", $sql);
        $terms = FALSE;
    }
    return $terms;
}

function add_or_edit_business_partner_terms($partner_id, $start_date, $end_date, $travel_type,
                                            $payment_type, $payment_details) {
    $safe_partner_id = mysql_real_escape_string($partner_id);
    $safe_start_date = mysql_real_escape_string($start_date);
    $safe_end_date = mysql_real_escape_string($end_date);
    $safe_travel_type = mysql_real_escape_string($travel_type);
    $safe_payment_type = mysql_real_escape_string($payment_type);
    $safe_payment_details = mysql_real_escape_string($payment_details);

    $sql = "INSERT INTO business_partner_terms (BusinessPartnerID, StartDate, EndDate, 
                        TravelType, PaymentType, PaymentDetails)
            VALUES ($safe_partner_id, '$safe_start_date', '$safe_end_date', '$safe_travel_type',
                    '$safe_payment_type', $safe_payment_details)
            ON DUPLICATE KEY UPDATE StartDate = '$safe_start_date', EndDate = '$safe_end_date',
                                    TravelType = '$safe_travel_type', PaymentType = '$safe_payment_type',
                                    PaymentDetails = '$safe_payment_details' ";

    $result = mysql_query($sql);

    if ($result) {
        return TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not add/edit business partner terms", $sql);
    }
    return FALSE;
}

function get_business_partner_terms_on_date($partner_id, $date) {
    $safe_partner_id = mysql_real_escape_string($partner_id);
    $safe_date = mysql_real_escape_string($date);

    $sql = "SELECT *
            FROM business_partner_terms
            WHERE BusinessPartnerID = $safe_partner_id AND
                  '$safe_date' BETWEEN StartDate AND EndDate
            ORDER BY StartDate ASC LIMIT 1";

    $result = mysql_query($sql);

    if ($result) {
        $terms = mysql_fetch_array($result);
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get business partner terms for $partner_id on $date", $sql);
        $terms = FALSE;
    }
    return $terms;
}

function store_business_partner_link_reference($bp_id, $link_id, $bp_cents) {
    $safe_bp_id = mysql_real_escape_string($bp_id);
    $safe_link_id = mysql_real_escape_string($link_id);
    $safe_bp_cents = mysql_real_escape_string($bp_cents);

    $sql = "INSERT INTO business_partner_link_history (BusinessPartnerID, LinkID, BPCents)
            VALUES ($safe_bp_id, $safe_link_id, $safe_bp_cents)";

    $result = mysql_query($sql);

    if ($result) {
        return TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not add business partner link ($bp_id, $link_id)", $sql);
    }
    return FALSE;
}

function get_business_partner_links($bp_facility_id, $start_date, $end_date) {
    $links = FALSE;

    $safe_partner_id = mysql_real_escape_string($bp_facility_id);
    $safe_start_date = mysql_real_escape_string(date('Y-m-d',strtotime($start_date))." 0:00:00");
    $safe_end_date = mysql_real_escape_string(date('Y-m-d',strtotime($end_date))." 23:59:59");

    $sql = "SELECT FromDestinationID, ToDestinationID, DesiredArrivalTime, LinkID,
                   Distance, EstimatedMinutes, QuotedCents, BPCents, users.UserID,
                   Title, FirstName, MiddleInitial, LastName, Suffix,
                   IF( FromDestinationID IN (SELECT DestinationID 
                                             FROM destination_tag NATURAL JOIN destination_tag_list
                                             WHERE TagName = 'BUSINESS_PARTNER' AND TagInfo1 = $safe_partner_id ),
                       'YES', 'NO') AS FromIsPartner,
                   IF( ToDestinationID IN (SELECT DestinationID 
                                             FROM destination_tag NATURAL JOIN destination_tag_list
                                             WHERE TagName = 'BUSINESS_PARTNER' AND TagInfo1 = $safe_partner_id ),
                       'YES', 'NO') AS ToIsPartner
            FROM business_partner_link_history NATURAL JOIN link_history, 
                 users NATURAL JOIN person_name
            WHERE BusinessPartnerID = $safe_partner_id AND
                  DesiredArrivalTime BETWEEN '$safe_start_date' AND '$safe_end_date' AND
                  link_history.RiderUserID = users.UserID
            ORDER BY DesiredArrivalTime ASC ";

    $result = mysql_query($sql);


        $links = array();
        while ($row = mysql_fetch_array($result)) {
            $links[] = $row;
        }


    return $links;
}


?>
