<?php

require_once('include/database.php');
require_once('include/user.php');
require_once('include/rc_log.php');
require_once('include/franchise.php');

/**
 * Stores the initial HPS transaction request and returns a unique verification ID
 * @param user_id ID of user initiating transaction
 * @param cents Amount of transaction in cents
 * @return verification ID or -1 on error
 */
function store_hps_transaction_request( $user_id, $cents ) {
    $transaction_id = -1;
	$franchise_id = get_current_user_franchise();
	
    $safe_user = mysql_real_escape_string($user_id);
    $safe_cents = mysql_real_escape_string($cents);
	$safe_franchise = mysql_real_escape_string($franchise_id);
    $sql = "INSERT INTO hps_transaction_request (FranchiseID, UserID, RequestedCents)
            VALUES ('$safe_franchise', '$safe_user', '$safe_cents')";

    $result = mysql_query($sql);
    if ($result) {
        $transaction_id = mysql_insert_id();
    } else {
        rc_log_db_error(PEAR_LOG_CRIT, mysql_error(),
                        "Error initiating HPS transaction for $user_id/$cents", $sql);
    }

    return $transaction_id;
}

/**
 * Checks for the existence of an HPS transaction request for the given
 * user and transaction ID.  
 * @param $user_id User ID
 * @param $transaction_id Verification ID assigned by store_hps_transaction_request
 * @return array(EXISTS => bool, AMOUNT => cents):  
                                   EXISTS is boolean, TRUE if the transaction was found.
 *                                 AMOUNT is cents of requested transaction, set if EXISTS is TRUE,
 *                                 undefined otherwise.
 */
function verify_hps_transaction_requested($user_id, $verification_id) {
    $transaction_exists = FALSE;
    $requested_cents = 0;


    $safe_user = mysql_real_escape_string($user_id);
    $safe_vfid = mysql_real_escape_string($verification_id);

    $sql = "SELECT VerificationID, RequestedCents FROM hps_transaction_request
            WHERE VerificationID = '$safe_vfid' AND UserID = '$safe_user'";

    $result = mysql_query($sql);
    if ($result) {
        if (mysql_num_rows($result) == 1) {
            $row = mysql_fetch_assoc($result);
            $transaction_exists = TRUE;
            $requested_cents = $row['RequestedCents'];
        }
    } else {
        rc_log_db_error(PEAR_LOG_CRIT, mysql_error(),
                        "Error verifying HPS transaction request $user_id/$verification_id", $sql);
    }

    return array( 'EXISTS' => $transaction_exists, 'AMOUNT' => $requested_cents );

}

/**
 * Stores the HPS transaction result, but does not store a ledger entry cross-reference.
 * Ledger entry cross-reference must be stored using update_hps_result_ledger_id.
 * @return TRUE on success, FALSE on error
 */
function store_hps_transaction_result( $verification_id, 
                                       $txn_result_id, $txn_ref_number, 
                                       $auth_code, $txn_result_code, 
                                       $confirmed_cents ) {
    $store_success = FALSE;
	$franchise_id = get_current_user_franchise();
    $safe_vfid = mysql_real_escape_string($verification_id);
    $safe_txn_result_id = mysql_real_escape_string($txn_result_id);
    $safe_txn_ref_num = mysql_real_escape_string($txn_ref_number);
    $safe_auth_code = mysql_real_escape_string($auth_code);
    $safe_txn_result_code = mysql_real_escape_string($txn_result_code);
    $safe_cents = mysql_real_escape_string($confirmed_cents);
	$safe_franchise = mysql_real_escape_string($franchise_id);
	
    $sql = "INSERT INTO hps_transaction_result
                (VerificationID,TransactionID, ReferenceNumber, AuthCode, TxnResult, ConfirmedCents )
            VALUES ($safe_vfid, $safe_txn_result_id, $safe_txn_ref_num, '$safe_auth_code', 
                    $safe_txn_result_code, $safe_cents)";

    $result = mysql_query($sql);
    if ($result) {
        $store_success = TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_CRIT, mysql_error(),
                        "Error storing HPS transaction result", $sql);
    }

    return $store_success;
}


/**
 * Stores the ledger entry cross-reference to an HPS transaction result created with 
 * store_hps_transaction_result.
 * @return TRUE on success, FALSE on error
 */
function store_ledger_id_to_hps_transaction_result( $verification_id, $ledger_entry_id ) {
    $update_success = FALSE;

    $safe_vfid = mysql_real_escape_string($verification_id);
    $safe_ledger_id = mysql_real_escape_string($ledger_entry_id);

    $sql = "UPDATE hps_transaction_result
            SET LedgerEntryId = $safe_ledger_id
            WHERE VerificationID = $safe_vfid";

    $result = mysql_query($sql);
    if ($result) {
        $store_success = TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Error storing ledger reference to HPS transaction result $verification_id/$ledger_entry_id",
                        $sql);
    }

    return $update_success;
}

function mail_admin_for_transaction($userid, $name, $transNum, $transRefNum, $authCode, $resultNum, $usedAttempts, $cardType, $cardNumber, $InvoiceNum, $amount){
	$franchise_id = get_current_user_franchise();
	$club_name = get_franchise_name( $franchise_id );
	$club_emails = get_franchise_email_addresses($franchise_id, 'cc_processing');
	$date = date("m/d/Y H:i:s");
	$message = <<<MAIL
A transaction has been made by a user.

Date: $date
UserID: $userid
ClubID: $franchise_id - $club_name
Name: $name 
Amount Cents: $amount
Card Type: $cardType
Card Number: XXXX - XXXX - XXXX - $cardNumber
Transaction Number: $transNum
Transaction Reference Number: $transRefNum
Authorization Code: $authCode
Result Number: $resultNum
Used Attempts: $usedAttempts
Invoice Number: $InvoiceNum
	
Have A Great Day!
The System
MAIL;
	$user_message = <<<MAIL
Your transaction as been processed.

Date: $date
Name: $name
Amount Cents: $amount
Card Type: $cardType
Card Number: XXXX - XXXX - XXXX - $cardNumber
Transaction Number: $transNum
Transaction Reference Number: $transRefNum
Invoice Number: $InvoiceNum
	
Have A Great Day!
Riders Club of America
MAIL;
	if($email = get_user_email( $userid ))
		mail($email,'Riders Club Transaction Receipt' . $date, $message, DEFAULT_EMAIL_FROM);
		
	if (sizeof($club_emails)>0) {
	  foreach($club_emails as $email) {
	    if (isset($email) && ($email!='')) {
		  mail($email, 'Riders Club Transaction Receipt' . $date, $message, DEFAULT_EMAIL_FROM);
		}
	  }
	}
	mail(DEFAULT_ADMIN_EMAIL,'HPS-Transaction ' . $date, $message, DEFAULT_EMAIL_FROM);
}
?>
