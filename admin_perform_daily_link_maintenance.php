<?php

	
	require_once 'include/user.php';
	require_once 'include/franchise.php';
	redirect_if_not_logged_in();
    
	$franchise_id = get_current_user_franchise();
	
	if(!current_user_has_role(1, "FullAdmin") && !(current_user_has_role($franchise_id, "Franchisee") && user_has_franchise(get_current_user_id(), $franchise_id))){
		header("Location: home.php");
		die();	
	}
	
    require_once 'include/link.php';
    require_once 'include/ledger.php';
    require_once 'include/completed_link_ledger_xref.php';
    require_once 'include/completed_link_transitions.php';
    require_once 'include/care_facility.php';
    require_once 'include/link_price.php';
    require_once('include/franchise.php');
    require_once('include/link_price.php');
    require_once 'include/driver_rate_card.php';
    require_once 'include/driver_link.php';
    require_once('include/deadhead.php');	

    include_once 'include/header.php';

    global $link_status_payment_matrix; // from completed_link_transitions.php
	
  if (isset($_POST['CreateTransitionMiles']) && $_POST['CreateTransitionMiles'] == 'Create Transition Miles') {
		if($_POST['transition_date_from'] != '') {
      $transition_success = create_transition_miles($franchise_id,
                                                    date('Y-m-d',strtotime($_POST['transition_date_from'])),
                                                    date('Y-m-d',strtotime($_POST['transition_date_to']))
                                                    );    			
		} else $transition_success = create_transition_miles($franchise_id, date('Y-m-d'));
		
    if($transition_success)
    	$transition_alloc_confirmation .= "<div class=\"reminder\">You have successfully created transtion miles</div>";
   	else if($transition_success === NULL)
   		$transition_alloc_confirmation .= "<div class=\"reminder\">No transitions found. All up to date.</div>";
   	else
   		$transition_alloc_confirmation .= "<div class=\"reminder\">Transitions failed to create.</div>";
	}
		
	
    $tz_offset_hours = timezone_offset_get(new DateTimeZone(date_default_timezone_get()), new DateTime('now'))/60/60;
	$status_past_tense = array('UNKNOWN' => 'completed',
        'COMPLETE' => 'completed',
        'DRIVERNOSHOW' => 'driver no show',
        'CANCELEDLATE' => 'canceled late',
        'CANCELEDEARLY' => 'canceled early',
        'NOTSCHEDULED' => 'not scheduled',
        'WEATHERCANCEL' => 'weather canceled',
        'DESTINATIONCANCEL' => 'destination canceled',
        'HOSPITALCANCEL' => 'hospital canceled' );
	if(!$_POST['StartProcess'] &&!$_POST['StartYesterday'] &&!$_POST["SelectedDateButton"]){
	?><br><br>
		<?php if($transition_alloc_confirmation != '') echo "Creation Transition Miles Result: <B>$transition_alloc_confirmation</B><BR><BR>"; ?>
		<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/themes/smoothness/jquery-ui.css" />
		<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/jquery-ui.min.js"></script>
    <form method="post">
    To begin the process of doing link maintenance thru an arbitrary date, select the date: <input type="text" class="jq_datepicker" size=10 name="SelectedDate"> and <input type="submit" value="Click Here" name="SelectedDateButton">
    </br>  
    To begin the process of doing link maintenance thru yesterday <input type="submit" value="Click Here" name="StartYesterday">
    </br>  
    To begin the process of doing link maintenance thru now <input type="submit" value="Click Here" name="StartProcess">
    </form>
    <?php
		require_once 'include/footer.php';
		die();
	}
	

	
	echo "
	<div style='border: 1px solid black; padding: 10px; width: 350px; margin: 20px auto;'>
	<form method=POST>
		<input type=submit name=CreateTransitionMiles value='Create Transition Miles'><br><br>
		<b>Optional:</b><br><br>From: <input type=text class=jq_datepicker size=10 name=transition_date_from> To: <input type=text class=jq_datepicker size=10 name=transition_date_to>
	</form>
	</div>
	";
	
   if($_POST['StartYesterday'])
    $past_links = get_past_links($franchise, $tz_offset_hours,true);
    else if($_POST["SelectedDateButton"]) {
	    if(isset($_POST["SelectedDate"])) $past_links = get_links_before_date($franchise, $tz_offset_hours, strtotime($_POST["SelectedDate"]));
	    else $past_links = array();
  	} else
    $past_links = get_past_links($franchise, $tz_offset_hours);
	//print_r($past_links);
    if (!count($past_links)) {
        echo "No past links found!";
        exit;
    }

    echo "Beginning to process " . count($past_links) . " links...<br />";
	
    $driver_times = array();
    $service_area_zips = get_franchise_service_zips($franchise_id);
	if(!$service_area_zips || count($service_area_zips) < 1){
		echo "Service Areas Were Not Found."; die();
	}
	
	$driver_rate_card = get_current_driver_rate_card($franchise_id);
    if(!$driver_rate_card){
    	echo "Failure to get driver rate card.";
    	die();
    }
	
	$out_of_area_pricing = get_current_out_of_area_rate_card($franchise_id);
	
	
	
	
    foreach ($past_links as $past_link) {
        //var_export($past_link);
        $comments = '';
		//echo '<pre>';
		//print_r($past_link);
		//echo "</pre>";
        $skipping_ledger = FALSE;

        $rider_info = get_rider_person_info($past_link['RiderUserID']);
		$is_out_of_area = FALSE;
		$from_dest = get_destination($past_link['FromDestinationID']);
        $to_dest = get_destination($past_link['ToDestinationID']);
		if (!$service_area_zips[$to_dest['ZIP5']]) {
            $is_out_of_area = TRUE;
        }
        if (!$service_area_zips[$from_dest['ZIP5']]) {
            $is_out_of_area = TRUE;
        }
		
		
        $driver_user_id = $past_link['AssignedDriverUserID'];
		if($past_link['LinkStatus'] == '')
			$past_link['LinkStatus'] = 'UNKNOWN';
        $start = db_start_transaction();
        if (!$start) {
            db_rollback_transaction();
            echo "<p>Could not start a transaction for link {$past_link['LinkID']}</p>";
            continue;  // Safer to just skip and let the admin try again.
        }
		
		if($past_link['Distance'] == 0 && $past_link['EstimatedMinutes'] == 0){
			db_rollback_transaction();
			echo "<p>link {$past_link['LinkID']} on DATE has Distance = 0 AND EstimatedMinutes = 0. </p>";
			continue;
		}
        
		if(($link_status_payment_matrix[$past_link['LinkStatus']]['DRIVER'] == TRUE || $link_status_payment_matrix[$past_link['LinkStatus']]['RIDER'] == TRUE) && !$driver_user_id){
			echo 'skip';
			db_rollback_transaction();
            echo "<p>Skipping ride {$past_link['LinkID']} because it is chargeable with no driver</p>";
            continue;  // skip if it is chargeable and doesnt have a driver
        } else {
			// Move the ride to history
			echo 'move';
        	$moved = move_link_to_history($past_link['LinkID']);
        }

        // Ledger impacts only if the ride occurred, which we approximate (FOR NOW:  TODO)
        // based on whether a driver was assigned.  Future:  allow drivers to 
        // show completed.  Still not 100% diagnostic, but better.
        if ($driver_user_id) {
            // TODO:  Keep track of the driver's times 
            if(!$is_out_of_area){
            	$quote = get_context_link_price($past_link, $past_link['Distance'], $past_link['FranchiseID'],
                                    	$past_link['FromDestinationID'], $past_link['ToDestinationID'],
                                    	$past_link['DesiredArrivalTime']);
            } else {
            	$quote = get_out_of_area_link_price($past_link['Distance'], $past_link['FranchiseID'], 
                                                     $past_link['FromDestinationID'], $past_link['ToDestinationID'], $past_link);
            }
            $rider_share = $quote['RiderShare'];
            // TODO:  If rider share not equal to quote, there's a problem
            
            // Charge the rider
            // TODO:  Business Partner?
            // Check whether the rider should pay
            if (($past_link['LinkStatus'] == 'UNKNOWN' ||
                ($past_link['LinkStatus'] != 'UNKNOWN' &&
                 $link_status_payment_matrix[$past_link['LinkStatus']]['RIDER'] === TRUE)) && $past_link['CustomTransitionType'] != 'DRIVER') {
                    
                if ($rider_care_facility = get_user_current_care_facility($rider_info['UserID'], $past_link['DesiredArrivalTime'])) {
                    // TODO:  Check whether link is connected to CF
                    $user_debit_ledger_id = debit_care_facility( $rider_care_facility, $rider_share,
                        "Applied charge for {$status_past_tense[$past_link['LinkStatus']]} ride {$past_link['LinkID']} for {$rider_info['UserID']} " .
                        "arriving {$past_link['DesiredArrivalTime']}",$past_link['DesiredArrivalTime'], "GENERAL", $past_link['LinkID']);

                } else {
                    $user_debit_ledger_id = debit_user( $rider_info['UserID'], $rider_share,
                            "Applied charge for {$status_past_tense[$past_link['LinkStatus']]} ride {$past_link['LinkID']} arriving " .
                            "{$past_link['DesiredArrivalTime']}", $past_link['DesiredArrivalTime'], "GENERAL", $past_link['LinkID']);
                }


                // Charge business partners if needed
                if ($quote['FromPartnerID'] && $quote['FromPartnerID'] == $quote['ToPartnerID']) {
                        store_business_partner_link_reference($quote['FromPartnerID'], 
                                                              $past_link['LinkID'],
                                                              $quote['FromPartnerAmount'] + $quote['ToPartnerAmount']);
                } else {
                    if ($quote['FromPartnerID'] && $quote['FromPartnerAmount']) {
                        store_business_partner_link_reference($quote['FromPartnerID'], 
                                                              $past_link['LinkID'],
                                                              $quote['FromPartnerAmount']);
                    }
                    if ($quote['ToPartnerID'] && $quote['ToPartnerAmount']) {
                        store_business_partner_link_reference($quote['ToPartnerID'], 
                                                              $past_link['LinkID'],
                                                              $quote['ToPartnerAmount']);
                    }
                }

                // Need to keep track of who originally paid for what.  This will be important 
                // for adjustments (especially multiple adjustments) and audits.
                $rider_x_stored = store_original_link_ledger_xref( $past_link['LinkID'], 
                                                                   $user_debit_ledger_id, 'RIDER' );
            } else {
                $comments .= "  Rider not debited due to status {$past_link['LinkStatus']}.";
                if($past_link['CustomTransitionType'] == 'DRIVER')
                	$comments .= "  And CustomTranstionType is DRIVER";
                $rider_x_stored = TRUE; // Commit - nothing to store for rider xref
                $user_debit_ledger_id = TRUE;
            }


            // Check whether the driver should be paid
            if (($past_link['LinkStatus'] == 'UNKNOWN' ||
                ($past_link['LinkStatus'] != 'UNKNOWN' &&
                 $link_status_payment_matrix[$past_link['LinkStatus']]['DRIVER'] === TRUE)) && $past_link['CustomTransitionType'] != 'RIDER') {
                // Credit the driver
                //$credit_amount = 14 * (floor($past_link['Distance']));
                if(!$is_out_of_area){
                	$credit_amount = floor($driver_rate_card['CentsPerMile'] * ($past_link['Distance'])); 
                	$driver_credit_ledger_id = credit_user( $driver_user_id, $credit_amount, 
                        "Driver credit for {$status_past_tense[$past_link['LinkStatus']]} ride {$past_link['LinkID']} arriving " .
                        "{$past_link['DesiredArrivalTime']}", $past_link['DesiredArrivalTime'], 'DRIVER');
				} else {
					$credit_amount = floor(($out_of_area_pricing['DriverPerMileCents'] * ($past_link['Distance']))+$out_of_area_pricing["EnterCarCents"]);
					$driver_credit_ledger_id = credit_user( $driver_user_id, $credit_amount, 
                        "Driver credit for {$status_past_tense[$past_link['LinkStatus']]} out of area ride {$past_link['LinkID']} arriving " .
                        "{$past_link['DesiredArrivalTime']}", $past_link['DesiredArrivalTime'], 'DRIVER');
				}
                $driver_x_stored = store_original_link_ledger_xref( $past_link['LinkID'], 
                                                                    $driver_credit_ledger_id, 'DRIVER' );
            } else {
                $comments .= "  Driver not credited due to status {$past_link['LinkStatus']}.";
                if($past_link['CustomTransitionType'] == 'RIDER')
                	$comments .= " And CustomTransitionType is RIDER";
                $driver_x_stored = TRUE; // Commit - nothin to store for rider xref
                $driver_credit_ledger_id = TRUE;
            }



        } else {
            $comments .= "  No driver assigned; skipping ledgering.";
            $skipping_ledger = TRUE;
        }
		
        if ($start && $moved && (
                ($skipping_ledger) || ($user_debit_ledger_id && $driver_credit_ledger_id &&
                                       $rider_x_stored && $driver_x_stored)
           )) {
            $commit = db_commit_transaction();
            if ($commit) {
                echo "<p>Moved and created ledger entries for link {$past_link['LinkID']}. {$comments}</p>";
            } else {
                echo "<p>Transaction error occurred for link {$past_link['LinkID']}. {$comments}</p>";
                db_rollback_transaction();
            }
        } else {
            echo "<p>Something went wrong processing link {$past_link['LinkID']}.  Try again.($start , $moved ,  , )</p>";
            $rollback = db_rollback_transaction();
        }
    }
?>
