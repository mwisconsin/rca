<?php



	require_once 'include/user.php';
	redirect_if_not_logged_in();
    require_once 'include/franchise.php';
	
	$franchise = get_current_user_franchise();
	
	if(!current_user_has_role(1, "FullAdmin") && !current_user_has_role($franchise, "Franchisee")){
		header("Location: home.php");
		die();
	}
	
    require_once 'include/ledger.php';
    require_once('include/supporters.php');

    include_once 'include/header.php';


    //$tz_offset_hours = timezone_offset_get(new DateTimeZone(date_default_timezone_get()), new DateTime('now'))/60/60;
    //$past_links = get_past_links($tz_offset_hours);
    $email_message = '';

    $alloc_prefs = get_all_driver_allocation_preferences($franchise);

    if ($alloc_prefs) {
        $year = date('Y'); $this_month = date('m');
        $this_month_start = (($this_month == 1) ? ($year - 1) : $year) . '-' . 
                            (($this_month == 1) ? 12 : ($this_month - 1)) . '-01';
        $next_month_start = "$year-$this_month-01";

        $effective_time_t = mktime( 0, 0, 0, $this_month, 0, $year);
        $effective_date = date('Y-m-d', $effective_time_t);
        
		$email_message .= "Allocating balances between $this_month_start and $next_month_start";
        echo "Allocating balances between $this_month_start and $next_month_start";
        //echo "<table border=\"1\"><tr><th>ID</th><th>Month Start</th><th>Month End</th><th>Diff</th><th>Curr</th></tr>";


        foreach ($alloc_prefs as $pref) {
            /*$month_start_balance = calculate_ledger_balance_on_date( 'USER', $pref['DriverUserID'], 
                                                                     $this_month_start, 'DRIVER' );*/
            $month_end_balance = calculate_ledger_balance_on_date( 'USER', $pref['DriverUserID'], 
                                                                   $next_month_start, 'DRIVER' );
            $allocate_cents = $month_end_balance;


            $pretty_balance = format_dollars($month_end_balance);
            
            if ($month_end_balance <= 0) {
                //echo "<p>Driver {$pref['DriverUserID']} has end-of-month balance $pretty_balance.  No allocation necessary.</p>";
                continue;
            } else {
			    echo "<p>Driver {$pref['DriverUserID']} has end-of-month balance $pretty_balance.  ";
				$email_message .= "<p>Driver {$pref['DriverUserID']} has end-of-month balance $pretty_balance.  ";
			}
                    //$end_date, $sub_account = NULL 
            //$allocate_cents = calculate_user_ledger_balance( $pref['DriverUserID'], 'DRIVER' );
            //$pretty_balance = format_dollars($allocate_cents);

            if ($pref['AllocationType'] == 'REIMBURSEMENT') {
                echo "Allocating driver {$pref['DriverUserID']} balance of {$allocate_cents} to Driver Reimbursement.";
								$email_message .= "Allocating driver {$pref['DriverUserID']} balance of {$allocate_cents} to Driver Reimbursement.";
                // This means the driver balance turns into a general balance.

                if (!db_start_transaction()) {
                    db_rollback_transaction();
                    echo "...could not start transaction!  Skipping...</p>";
										$email_message .= "...could not start transaction!  Skipping...</p>";
                    continue;  // Safer to just skip and let the admin try again.
                }

                $debit_id = debit_user($pref['DriverUserID'], $allocate_cents, 
                                       "Driver Allocation: Driver Reimbursement", $effective_date, 'DRIVER');
//                $credit_id = credit_user($pref['DriverUserID'], $allocate_cents,
//                                         "Driver Allocation: Driver Reimbursement", $effective_date, 'GENERAL');
								$credit_id = 1;
								
                $record_stored = store_driver_reimbursement_record($pref['DriverUserID'], $debit_id);

                if ($debit_id && $credit_id && $record_stored && db_commit_transaction()) {
                    echo "... Done!";
										$email_message .= "... Done!";
                } else {
                    echo "... Transaction error occurred.  Attempting to roll back.";
										$email_message .= "... Transaction error occurred.  Attempting to roll back.";
                    db_rollback_transaction();
                }

            } elseif ($pref['AllocationType'] == 'ADDTORIDERBALANCE') {
                echo "Allocating driver {$pref['DriverUserID']} balance of {$allocate_cents} to Rider Balance.";
								$email_message .= "Allocating driver {$pref['DriverUserID']} balance of {$allocate_cents} to Rider Balance.";
                // This means the driver balance turns into a general balance.

                if (!db_start_transaction()) {
                    db_rollback_transaction();
                    echo "...could not start transaction!  Skipping...</p>";
										$email_message .= "...could not start transaction!  Skipping...</p>";
                    continue;  // Safer to just skip and let the admin try again.
                }

                $debit_id = debit_user($pref['DriverUserID'], $allocate_cents, 
                                       "Driver Allocation: Rider Balance", $effective_date, 'DRIVER');
                $credit_id = credit_user($pref['DriverUserID'], $allocate_cents,
                                         "Driver Allocation: Rider Balance", $effective_date, 'GENERAL');
                $record_stored = store_driver_reimbursement_record($pref['DriverUserID'], $debit_id);

                if ($debit_id && $credit_id && $record_stored && db_commit_transaction()) {
                    echo "... Done!";
										$email_message .= "... Done!";
                } else {
                    echo "... Transaction error occurred.  Attempting to roll back.";
										$email_message .= "... Transaction error occurred.  Attempting to roll back.";
                    db_rollback_transaction();
                }            	
            	
            } elseif ($pref['AllocationType'] == 'CHARITY') {
                echo "Allocating driver {$pref['DriverUserID']} balance of {$allocate_cents} to " .
                     "charity #{$pref['AllocationID']}";
								$email_message .= "Allocating driver {$pref['DriverUserID']} balance of {$allocate_cents} to " .
                     "charity #{$pref['AllocationID']}";
                // This means the driver balance gets moved to the charity's general account.

                if (!db_start_transaction()) {
                    db_rollback_transaction();
                    echo "...could not start transaction!  Skipping...</p>";
					$email_message .= "...could not start transaction!  Skipping...</p>";
                    continue;  // Safer to just skip and let the admin try again.
                }

                $debit_id = debit_user($pref['DriverUserID'], $allocate_cents, 
                                       "Driver Allocation: To Charity {$pref['AllocationID']}", $effective_date, 'DRIVER');
                $credit_id = credit_charity($pref['AllocationID'], $allocate_cents,
                                            "Driver Allocation: From Driver {$pref['DriverUserID']}", $effective_date);

                $record_created = store_supporter_charity_record($pref['DriverUserID'], 
                                                                 $pref['AllocationID'], $debit_id);

                if ($debit_id && $credit_id && $record_created && db_commit_transaction()) {
                    echo "... Done!";
					$email_message .=  "... Done!";
                } else {
                    echo "... Transaction error occurred.  Attempting to roll back.";
					$email_message .= "... Transaction error occurred.  Attempting to roll back.";
                    db_rollback_transaction();
                }

            } elseif ($pref['AllocationType'] == 'RIDER') {
                echo "Allocating driver {$pref['DriverUserID']} balance of {$allocate_cents} to " .
                     "rider #{$pref['AllocationID']}";
				$email_message .= "Allocating driver {$pref['DriverUserID']} balance of {$allocate_cents} to " .
                     "rider #{$pref['AllocationID']}";
                // This means the driver balance moves to the rider's account.

                if (!db_start_transaction()) {
                    db_rollback_transaction();
                    echo "...could not start transaction!  Skipping...</p>";
					$email_message .= "...could not start transaction!  Skipping...</p>";
                    continue;  // Safer to just skip and let the admin try again.
                }

                $debit_id = debit_user($pref['DriverUserID'], $allocate_cents, 
                                       "Driver Allocation: To Rider {$pref['AllocationID']}", $effective_date, 'DRIVER');
                $credit_id = credit_user($pref['AllocationID'], $allocate_cents,
                                         "Driver Allocation: From Driver {$pref['DriverUserID']}", $effective_date, 'GENERAL');


                $record_stored = store_supporter_rider_record($pref['DriverUserID'], $debit_id,
                                                              $pref['AllocationID'], $credit_id); 

                if ($debit_id && $credit_id && $record_stored && db_commit_transaction()) {
                    echo "... Done!";
					$email_message .= "... Done!";
                } else {
                    echo "... Transaction error occurred.  Attempting to roll back.";
					$email_message .= "... Transaction error occurred.  Attempting to roll back.";
                    db_rollback_transaction();
                }

            } else {
                echo "Driver {$pref['DriverUserID']} has unknown type {$pref['AllocationType']}";
				$email_message .= "Driver {$pref['DriverUserID']} has unknown type {$pref['AllocationType']}";
            }

            echo '</p>';
			$email_message .= "</p>";
			
			

        }
    }
	
	
	$club_emails = get_franchise_email_addresses($franchise_id, 'de_month_end_allocation');
	if (sizeof($club_emails)>0) {
		foreach($club_emails as $email) {
		  if (isset($email) && ($email!='')) {
			//echo $email;
			mail($email,"Club ".$franchise_id."; Month end driver allocation summary", $email_message, DEFAULT_EMAIL_FROM);
		  }
		}
	}

?>
<?php
	include_once 'include/footer.php';
?>
