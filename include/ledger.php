<?php

require_once('include/database.php');
require_once('include/rc_log.php');

/**
 * Calculates the user's balance from the ledger table.
 * @param user_id ID of user to calculate balance for
 * @return balance in cents
 */
function calculate_user_ledger_balance( $user_id, $sub_account = NULL ) {
    return calculate_ledger_balance('USER', $user_id, $sub_account);
}

function calculate_care_facility_balance( $care_facility_id, $as_of = NULL ) {
    return calculate_ledger_balance('CAREFACILITY', $care_facility_id, NULL, $as_of);
}

function calculate_ledger_balance( $entity_type, $entity_id, $sub_account = NULL, $as_of = NULL ) {
    $balance = 0; 
    $safe_type = mysql_real_escape_string($entity_type);
    $safe_id = mysql_real_escape_string($entity_id);
    
    if (is_null($as_of)) {
        $date_clause = '';
    } else {
        $date_clause = " AND DATE(EffectiveDate) <= DATE('" . mysql_real_escape_string($as_of) . "')";
    }

    if (!is_null($sub_account)) {
        $safe_subacct = mysql_real_escape_string($sub_account);
        $subacct_where = " AND SubAccount = '$safe_subacct' ";
    }

    // Leaving sub_account as NULL means the total balance is calculated for all subaccounts
    $sql = "SELECT IFNULL(SUM(Cents), 0) AS Balance FROM ledger 
            WHERE EntityID=$safe_id AND
                  EntityType='$safe_type'  $subacct_where  $date_clause";

    $result = mysql_query($sql);
    if ($result) {
        $row = mysql_fetch_array($result, MYSQL_ASSOC);

        $balance = $row['Balance'];
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error calculating balance for $entity_type $entity_id $sub_account", $sql);
    }

    return $balance;
}


function calculate_batch_user_ledger_balance($user_ids = array(), $sub_account = NULL){
	if(!is_array($user_ids) || count($user_ids) == 0)
		return array();

	foreach($user_ids as $user_id){
		$balances[$user_id] = 0;
	}
    //$safe_id = mysql_real_escape_string($entity_id);
    $subacct_where = "";
    $safe_subacct = "";
    if (!is_null($sub_account)) {
        $safe_subacct = mysql_real_escape_string($sub_account);
        $subacct_where = " AND SubAccount = '$safe_subacct' ";
    }
    
	$sql = "SELECT UserID, (SELECT IFNULL(SUM(Cents), 0) AS Balance FROM ledger 
            WHERE EntityID=users.UserID AND
                  EntityType='USER'  $subacct_where)  as Balance
			FROM users WHERE UserID IN ( " . implode(", ", $user_ids) . " )";

	$result = mysql_query($sql) or die(mysql_error());
	
	if($result){
		while($row = mysql_fetch_array($result)){
			$balances[$row['UserID']] = $row['Balance'];	
		}
		return $balances;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error calculating balance for batch of users", $sql);
		return false;
	}
}

/**
 *  Stores a credit for a user to the database.  A credit is a balance increase.
 *  This function will add the (postive) cents to the user's account balance.
 *  This function will return a ledger entry ID if successful, -1 if the amount was
 *  negative (i.e. a debit instead of a credit), or FALSE if storing to the database
 *  failed.
 *  @param user_id ID of user to credit
 *  @param cents cents to add to user's account.  Must be positive.
 *  @param description Description or explanation of credit.  Human-readable.
 *  @param effective_date Effective date of the transaction.  NULL to set it to the current date/time.
 *  @param sub_account SubAccount to apply to.  Defaults to GENERAL.  ('DRIVER' is the other choice.)
 *  @return ledger entry ID on success, -1 if cents is negative, or FALSE on error
 */
function credit_user( $user_id, $cents, $description, $effective_date = NULL, $sub_account = 'GENERAL', $link_id = 0) {
    if ($cents < 0) { 
        return -1;
    }
    return store_ledger_entry('USER', $user_id, $cents, $description, $effective_date, $sub_account, $link_id);
}

function credit_care_facility( $care_facility_id, $cents, $description, $effective_date = NULL, $sub_account = 'GENERAL', $link_id = 0) {
    if ($cents < 0) { 
        return -1;
    }
    return store_ledger_entry('CAREFACILITY', $care_facility_id, $cents, $description, $effective_date, $sub_account, $link_id);
}

function credit_charity( $charity_id, $cents, $description, $effective_date = NULL, $sub_account = 'GENERAL', $link_id = 0) {
    if ($cents < 0) { 
        return -1;
    }
    return store_ledger_entry('CHARITY', $charity_id, $cents, $description, $effective_date, $sub_account, $link_id);
}


/**
 *  Stores a debit for a user to the database.  A debit is a balance decrease.
 *  This function will subtract the (positive) cents value from the user's account balance.
 *  This function will return a ledger entry ID if successful, -1 if the amount was
 *  negative (i.e. a credit instead of a debit), or FALSE if storing to the database
 *  failed.
 *  @param user_id ID of user to credit
 *  @param cents cents to subtract from user's account.  Must be positive.
 *  @param description Description or explanation of debit.  Human-readable.
 *  @param effective_date Effective date of the transaction.  NULL to set it to the current date/time.
 *  @param sub_account SubAccount to apply to.  Defaults to GENERAL.  ('DRIVER' is the other choice.)
 *  @return ledger entry ID on success, -1 if cents is negative, or FALSE on error
 */
function debit_user( $user_id, $cents, $description, $effective_date = NULL, $sub_account = 'GENERAL', $link_id = -2) {
    if ($cents < 0) { 
        return -1;
    }
    return store_ledger_entry('USER', $user_id, ($cents * -1), $description, $effective_date, $sub_account, $link_id);
}

function debit_care_facility( $care_facility_id, $cents, $description, $effective_date = NULL, $sub_account = 'GENERAL', $link_id = 0) {
    if ($cents < 0) { 
        return -1;
    }
    return store_ledger_entry('CAREFACILITY', $care_facility_id, ($cents * -1), $description, $effective_date, $sub_account, $link_id);
}

function debit_charity( $charity_id, $cents, $description, $effective_date = NULL, $sub_account = 'GENERAL', $link_id = 0) {
    if ($cents < 0) { 
        return -1;
    }
    return store_ledger_entry('CHARITY', $charity_id, ($cents * -1), $description, $effective_date, $sub_account, $link_id);
}

/**
 *  Stores a ledger entry for to the database.  This function should not be called by 
 *  client code (instead use credit_* and debit_*).
 *  This function will add the cents value (positive or negative) to the user's account balance.
 *  Postive is a credit, negative is a debit.
 *  This function will return a ledger entry ID if successful or FALSE if storing to the database
 *  failed.
 *  @param entity_type Type of entity:  USER or CAREFACILITY
 *  @param entity_id ID of entity (user/care facility)
 *  @param cents cents to add to entity's account.
 *  @param description Description or explanation of balance change.  Human-readable.
 *  @param effective_date Date the ledger entry is 'effective'.  Set to NULL for NOW(), or valid MySQL date.
 *  @param sub_account subaccount - currently 'DRIVER' is the only non-GENERAL subaccount
 *  @return ledger entry ID on success or FALSE on error
 */
function store_ledger_entry( $entity_type, $entity_id, $cents, $description, 
                             $effective_date = NULL, $sub_account = 'GENERAL', $link_id = -3) {
    $transaction_id = FALSE;
    
    $safe_type = mysql_real_escape_string($entity_type);
    $safe_entity = mysql_real_escape_string($entity_id);
    $safe_cents = mysql_real_escape_string($cents);
    $safe_desc = mysql_real_escape_string($description);
    $safe_eff_date = (is_null($effective_date)) ? 'NOW()' : "'" . mysql_real_escape_string($effective_date) . "'";
    $safe_subacct = mysql_real_escape_string($sub_account);
    $safe_link_id = mysql_real_escape_string($link_id);

    $sql = "INSERT INTO ledger (EntityType, EntityID, SubAccount, Cents, Description, EffectiveDate, LinkID) VALUES
            ('$safe_type', $safe_entity, '$safe_subacct', $safe_cents, '$safe_desc', $safe_eff_date, $safe_link_id)";
    $result = mysql_query($sql);
    
    if ($result) {
        $transaction_id = mysql_insert_id();
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 'Error storing ledger transaction', $sql);
    }

    return $transaction_id;
}


function get_user_ledger_entries($user_id, $start_date = NULL, $end_date = NULL) {
    return get_entity_ledger_entries('USER', $user_id, $start_date, $end_date);
}

function get_care_facility_ledger_entries($facility_id, $start_date = NULL, $end_date = NULL) {
    return get_entity_ledger_entries('CAREFACILITY', $facility_id, $start_date, $end_date);
}

function get_entity_ledger_entries($entity_type, $entity_id, $start_date = NULL, $end_date = NULL) {
    $safe_entity_type = mysql_real_escape_string($entity_type);
    $safe_entity_id = mysql_real_escape_string($entity_id);

    if (is_null($start_date) && is_null($end_date)) {
        $date_clause = '';
    } elseif (is_null($start_date)) {
        $date_clause = " AND DATE(EffectiveDate) <= DATE('" . mysql_real_escape_string($end_date) . "')";
    } elseif (is_null($end_date)) {
        $date_clause = " AND DATE(EffectiveDate) >= DATE('" . mysql_real_escape_string($start_date) . "')";
    } else {
        $date_clause = " AND DATE(EffectiveDate) BETWEEN DATE('" . 
                                            mysql_real_escape_string($start_date) . "') AND DATE('" .
                                            mysql_real_escape_string($end_date) . "')";
    }

	if (is_null($start_date)) {
	$run_tot = "(SELECT @runtot := 0) as test";
	} else { 
	$run_tot = "(SELECT @runtot := (SELECT IFNULL(SUM(Cents), 0) AS Balance FROM ledger 
            WHERE EntityID=$safe_entity_id AND EntityType='$safe_entity_type' AND 
			DATE(EffectiveDate) < DATE('" . mysql_real_escape_string($start_date) . "'))) as test";
	}
    $sql = 	"SELECT LedgerEntryID, SubAccount, Cents, Description, LedgerEntryTime, EffectiveDate,
                   (@runtot := @runtot + Cents) AS EffectiveBalance
            FROM  (SELECT * FROM ledger 
                   WHERE EntityType = '$safe_entity_type' AND
                         EntityID = $safe_entity_id 
                         $date_clause
						 GROUP BY LedgerEntryID) as A,
            $run_tot						 
            ORDER BY EffectiveDate ASC, LedgerEntryTime ASC, LedgerEntryID ASC";
	// echo str_replace("\n","<br/>",$sql);
    $result = mysql_query($sql);
    if ($result) {
        $ledger_entries = array();
        while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
            $ledger_entries[] = $row;
        }

        return $ledger_entries;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error getting ledger entries for $entity_type $entity_id ($start_date : $end_date)", $sql);
    }

    return FALSE;
}

function calculate_ledger_balance_on_date( $entity_type, $entity_id, $end_date, $sub_account = NULL ) {
    $balance = 0; 
    $safe_type = mysql_real_escape_string($entity_type);
    $safe_id = mysql_real_escape_string($entity_id);

    $safe_date = mysql_real_escape_string($end_date);
    
    if (!is_null($sub_account)) {
        $safe_subacct = mysql_real_escape_string($sub_account);
        $subacct_where = " AND SubAccount = '$safe_subacct' ";
    }

    // Leaving sub_account as NULL means the total balance is calculated for all subaccounts
    $sql = "SELECT IFNULL(SUM(Cents), 0) AS Balance FROM ledger 
            WHERE EntityID=$safe_id AND
                  EntityType='$safe_type' AND
                  DATE(EffectiveDate) < DATE('$safe_date') $subacct_where";
    //echo "$sql<br />";

    $result = mysql_query($sql);
    if ($result) {
        $row = mysql_fetch_array($result, MYSQL_ASSOC);

        $balance = $row['Balance'];
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error calculating balance on $end_date for $entity_type $entity_id $sub_account", $sql);
    }

    return $balance;
}


function get_ledger_entry($ledger_entry_id) {
    $safe_entry_id = mysql_real_escape_string($ledger_entry_id);


    $sql = "SELECT LedgerEntryID, EntityType, EntityID, SubAccount, Cents, Description, LedgerEntryTime, EffectiveDate
            FROM ledger 
            WHERE LedgerEntryID = $safe_entry_id";

    $result = mysql_query($sql);
    if ($result) {
        return mysql_fetch_array($result, MYSQL_ASSOC);
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error getting ledger entry $ledger_entry_id", $sql);
    }

    return FALSE;
}

function append_to_description($ledger_entry_id, $appended_description) {
    $safe_entry_id = mysql_real_escape_string($ledger_entry_id);
    $safe_desc = mysql_real_escape_string($appended_description);
    
    $sql = "UPDATE ledger SET Description = CONCAT(Description, '  $safe_desc')
            WHERE LedgerEntryID = $safe_entry_id";

    $result = mysql_query($sql);
    
    if ($result) {
        return TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error appending '$appended_description' to ledger entry $ledger_entry_id", $sql);
        return FALSE;
    }
}

function get_user_credits_by_subaccount_by_year($user_id, $subaccount = 'GENERAL', $year = NULL) {
	$safe_user_id = mysql_real_escape_string($user_id);
	$safe_subaccount = mysql_real_escape_string($subaccount);

    if (is_null($year)) {
        $year = date('Y');
    }
    $safe_year = mysql_real_escape_string($year);

    $sql = "SELECT SUM(Cents) AS Credits FROM ledger 
            WHERE EntityType = 'USER' AND
                  EntityID = $safe_user_id AND
                  SubAccount = '$safe_subaccount' AND
                  YEAR(EffectiveDate) = $safe_year AND
                  Cents > 0";

    $result = mysql_query($sql);
    if ($result) {
        $result_row = mysql_fetch_array($result, MYSQL_ASSOC);
        return $result_row['Credits'];
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error getting credits for user $user_id in subaccount $subaccount ($year)", $sql);
    }

    return 0;
}

function get_drivers_last_pay_date($user_id){
	$safe_user_id = mysql_real_escape_string($user_id);
	$sql = "SELECT EffectiveDate FROM ((SELECT DriverUserID AS UserID, EffectiveDate FROM driver_reimbursement_record LEFT JOIN ledger ON driver_reimbursement_record.ReimbursementLedgerEntryID = ledger.LedgerEntryID) UNION (SELECT SupporterUserID AS UserID, EffectiveDate FROM supporter_charity_record LEFT JOIN ledger ON supporter_charity_record.LedgerEntryID = ledger.LedgerEntryID)) t1 WHERE t1.UserID = $safe_user_id ORDER BY EffectiveDate DESC LIMIT 1;";
	$result = mysql_query($sql);
	
	if(mysql_num_rows($result) <= 0)
		return '0000-0-0';
	$result = mysql_fetch_array($result);
	return $result['EffectiveDate'];
}

function get_user_total_reimbursement_donation_YTD($user_id, $year = NULL){
	if($year === NULL)
		$year = date("Y");
	$safe_year = mysql_real_escape_string($year);
	$safe_user_id = mysql_real_escape_string($user_id);
	$sql = "SELECT SUM(Cents) FROM ((SELECT Cents,DriverUserID AS UserID, EffectiveDate FROM driver_reimbursement_record LEFT JOIN ledger ON driver_reimbursement_record.ReimbursementLedgerEntryID = ledger.LedgerEntryID) UNION (SELECT Cents, SupporterUserID AS USERID, EffectiveDate FROM supporter_charity_record LEFT JOIN ledger ON supporter_charity_record.LedgerEntryID = ledger.LedgerEntryID)) t1 WHERE t1.UserID = $safe_user_id AND EffectiveDate >= '" . $safe_year . "-1-1' AND EffectiveDate < '" . ($safe_year + 1) . "-1-1'";
	$result = mysql_query($sql);
	if($result){
		$result = mysql_fetch_array($result);
		return abs($result[0]);
	} else {
	 	rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error getting user total reimbursement/ donation for user $user_id", $sql);
	}
}
?>
