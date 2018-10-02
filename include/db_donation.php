<?php

require_once('include/database.php');

function add_donation_request( $donor_name_id, $franchise_id, $donor_address_id, $donor_email_id, $donation_type,
                               $payment_type, $frequency, $donation_cents ) {
    $safe_donor_name_id = mysql_real_escape_string($donor_name_id);
    $safe_donor_address_id = mysql_real_escape_string($donor_address_id);
    $safe_donor_email_id = ($donor_email_id != NULL) ? mysql_real_escape_string($donor_email_id) : 'NULL';
    $safe_donation_type = mysql_real_escape_string($donation_type);
    $safe_payment_type = mysql_real_escape_string($payment_type);
    $safe_frequency = mysql_real_escape_string($frequency);
    $safe_donation_cents = mysql_real_escape_string($donation_cents);
	$safe_franchise_id = mysql_real_escape_string($franchise_id);

    $sql = "INSERT INTO donation (FranchiseID, DonorNameID, DonorAddressID, DonorEmailID, DonationType,
                                  PaymentType, Frequency, DonationCents)
            VALUES ($safe_franchise_id, $safe_donor_name_id, $safe_donor_address_id, $safe_donor_email_id, '$safe_donation_type',
                    '$safe_payment_type', '$safe_frequency', $safe_donation_cents)";
    $result = mysql_query($sql);

    if (!$result) {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error inserting donation request!", $sql);
        return FALSE;
    }     

    return mysql_insert_id();
}

function get_donation_record( $donation_id ) {
    $safe_donation_id = mysql_real_escape_string($donation_id);

    $sql = "SELECT * FROM donation WHERE DonationID = $safe_donation_id";

    $result = mysql_query($sql);
    
	if ($result) {
        $record = mysql_fetch_array($result, MYSQL_ASSOC);
        return $record;
	} else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error retrieving donation record $donation_id", $sql);
		return FALSE;
	}
}


/**
 * Stores the HPS donation result.
 * @return TRUE on success, FALSE on error
 */
function store_hps_donation_result( $donation_id, 
                                    $txn_result_id, $txn_ref_number, 
                                    $auth_code, $txn_result_code, 
                                    $confirmed_cents ) {
    $store_success = FALSE;

    $safe_dnid = mysql_real_escape_string($donation_id);
    $safe_txn_result_id = mysql_real_escape_string($txn_result_id);
    $safe_txn_ref_num = mysql_real_escape_string($txn_ref_number);
    $safe_auth_code = mysql_real_escape_string($auth_code);
    $safe_txn_result_code = mysql_real_escape_string($txn_result_code);
    $safe_cents = mysql_real_escape_string($confirmed_cents);

    $sql = "INSERT INTO hps_donation_result
                (DonationID, TransactionID, ReferenceNumber, AuthCode, TxnResult, ConfirmedCents )
            VALUES ($safe_dnid, $safe_txn_result_id, '$safe_txn_ref_num', '$safe_auth_code', 
                    $safe_txn_result_code, $safe_cents)";

    $result = mysql_query($sql);
    if ($result) {
        $store_success = TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_CRIT, mysql_error(),
                        "Error storing HPS donation result", $sql);
    }

    return $store_success;
}


function set_donation_payment_received( $donation_id ) {
    $retval = FALSE;

    $safe_dnid = mysql_real_escape_string($donation_id);
    
    $sql = "UPDATE donation SET PaymentReceived = 'Y' WHERE DonationID = $safe_dnid";

    $result = mysql_query($sql);
    if ($result) {
        $retval = TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_CRIT, mysql_error(),
                        "Error marking payment received for donation $donation_id", $sql);
    }

    return $retval;
}

function get_donation_hash( $donation_id ){
	$safe_donation_id = mysql_real_escape_string( $donation_id );
	$sql = "SELECT DonationID, DonorNameID, DonorAddressID, DonationCents FROM donation WHERE DonationID = $safe_donation_id LIMIT 1;";
	$result = mysql_query($sql);
	
	if($result){
		if(mysql_num_rows($result) > 0){
			$donation = mysql_fetch_array($result);
			return sha1($donation['DonationID'] . $donation['DonorNameID'] . $donation['DonorAddressID'] . $donation['DonationCents']);
		} return FALSE;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error retrieving donation hash $donation_id", $sql);
		return FALSE;
	}
}
?>
