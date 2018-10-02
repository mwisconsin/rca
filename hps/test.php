<?php
    chdir('..');
    require_once('include/database.php');
    require_once('include/user.php');
    require_once('include/rider.php');
    require_once('include/ledger.php');
    require_once('include/hps_transactions.php');
    require_once('include/rc_log.php');
    require_once('include/name.php');
	require_once('include/care_facility.php');
	require_once ('include/franchise.php');
    session_start(); 
error_reporting(E_ALL);

    $user_id = get_affected_user_id();
    $user_person_name = get_user_person_name($user_id);
		$franchise = get_current_user_franchise();

echo $user_id."<BR>";
echo $franchise."<BR>";

echo user_has_role(get_affected_user_id(), $franchise, 'CareFacilityAdmin')."<BR>";
$care_facility_id = get_first_user_care_facility( $user_id );
echo 	$care_facility_id."<BR>";	

$defray_amount = 700;
$transaction_request = array();
$transaction_request['AMOUNT'] = 18200;
            $description = "TEST Added to account using VISA ending in 9999; HPS Web Portal; " .
                           date('Y-m-d h:i:s');
                           
			if(user_has_role(get_affected_user_id(), $franchise, 'CareFacilityAdmin')){
				$care_facility_id = get_first_user_care_facility( $user_id );
				$ledger_id = credit_care_facility( $care_facility_id, $transaction_request['AMOUNT'], $description );
			} else {
            	$ledger_id = credit_user( $user_id, $transaction_request['AMOUNT'], $description );
			}
			
				if(user_has_role(get_affected_user_id(), $franchise, 'CareFacilityAdmin')){
					$care_facility_id = get_first_user_care_facility( $user_id );
					$ledger_id = debit_care_facility( $care_facility_id,  $defray_amount, "TEST Payment to defray processing fees");
						
				} else {
					$ledger_id = debit_user( $user_id, $defray_amount,
                                         "TEST Payment to defray processing fees" );
				}

?>