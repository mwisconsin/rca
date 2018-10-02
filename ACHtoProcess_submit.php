<?php

	require_once 'include/user.php';
	require_once 'include/franchise.php';

  require_once 'include/ledger.php';
  require_once 'include/supporters.php';
	require_once 'include/link.php';
	require_once 'include/date_time.php';
	require_once 'include/care_facility.php';
	error_reporting(E_ALL);

	$dr = json_decode($_POST["datarows"]);
	$effective_date = date('Y-m-d',strtotime($_POST["effective_date"]));
	
	//var_dump($dr);
	//exit();
	
	for($i = 0; $i < count($dr); $i++) {
		$user_id = $dr[$i][5];
		$sql = "select * from user_role where Role = 'CareFacilityAdmin' and UserID = $user_id";
		$is_cfa_r = mysql_query($sql);
		$rider_info = mysql_fetch_assoc(mysql_query("select RechargePaymentType from users where UserID = $user_id"));
		#$rider_info = get_user_rider_info($user_id);
		#echo $dr[$i][3]."\n";
		$ledger_amount = preg_replace('/[^\d]/','',$dr[$i][3]); /* convert to cents */
		if(@$_POST["special_action"] != 'delete') {
			if($dr[$i][4] == 'Add') {
				$description = $rider_info['RechargePaymentType'] == 'SendChecks' ? "Check processed electronically via ACH"
					: "CBP - Check By Phone Payment";
				
				if(mysql_num_rows($is_cfa_r) > 0) {
					$entry_made = credit_care_facility( get_user_current_care_facility( $user_id ), $ledger_amount, $description, $effective_date );

				} else
					$entry_made = credit_user( $user_id, $ledger_amount, $description, $effective_date );
				
			} else {
				
				#echo $ledger_amount;
				#exit();
				
				$description = 'Applied annual rider fee of '.$ledger_amount;
				if(mysql_num_rows($is_cfa_r) > 0) {
					$cfid = get_user_current_care_facility( $user_id );
					$entry_made = debit_care_facility( $cfid , $ledger_amount, $description, $effective_date );
					$sql = "select AnnualFeePaymentDate from care_facility where CareFacilityID = $cfid";
					
					$rs = mysql_fetch_assoc(mysql_query($sql));
					
					$sql = "update care_facility set AnnualFeePaymentDate = '"
						.calculate_new_annual_fee_payment_date( $effective_date, date('Y-m-d',strtotime($rs["AnnualFeePaymentDate"])) )
						."' where CareFacilityID = $cfid";
					mysql_query($sql);					
				} else {
					$entry_made = debit_user( $user_id, $ledger_amount, $description, $effective_date );
				
					$sql = "select AnnualFeePaymentDate from rider where UserId = $user_id";
					
					$rs = mysql_fetch_assoc(mysql_query($sql));
					
					$sql = "update rider set AnnualFeePaymentDate = '"
						.calculate_new_annual_fee_payment_date( $effective_date, date('Y-m-d',strtotime($rs["AnnualFeePaymentDate"])) )
						."' where UserId = $user_id";
					mysql_query($sql);
				}
				
				
			}
		}
  	
  	$sql = "update ach_to_process set status = 0 where id = ".$dr[$i][7];
  	//echo $sql."\n";
  	$m = mysql_query($sql);
  	if(!$m) echo mysql_errno($link) . ": " . mysql_error($link). "\n";
  }


?>