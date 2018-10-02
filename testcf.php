<?php
	error_reporting(E_ALL);
    require_once('include/database.php');
    require_once('include/user.php');
    require_once('include/hps_transactions.php');
    require_once('include/rc_log.php');
	require_once('include/franchise.php');
	require_once('include/care_facility.php');
	error_reporting(E_ALL);
	echo "user_has_role: [".user_has_role(856, 2, 'CareFacilityAdmin')."]<BR>";
	
	
		$care_facility_id = get_first_user_care_facility( 856 );
		echo "Care Facility ID for Chris is $care_facility_id<br>";
		
		
		#$ledger_id = credit_care_facility( 1, 15600, 'Test CC' );