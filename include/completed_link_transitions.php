<?php

require_once 'include/link.php';
require_once 'include/ledger.php';
require_once 'include/completed_link_ledger_xref.php';
require_once 'include/driver_rate_card.php';

$franchise_id = 2;

// TRUE means should pay (for rider) or be paid (for driver)
// FALSE means no payment
$link_status_payment_matrix = array(
		'UNKNOWN' => array( 'DRIVER' => FALSE, 'RIDER' => FALSE),
        'COMPLETE' => array( 'DRIVER' => TRUE, 'RIDER' => TRUE ),
        'DRIVERNOSHOW' => array( 'DRIVER' => FALSE, 'RIDER' => FALSE ),
        'CANCELEDLATE' => array( 'DRIVER' => TRUE, 'RIDER' => TRUE ),
        'CANCELEDEARLY' => array( 'DRIVER' => FALSE, 'RIDER' => FALSE),
        'NOTSCHEDULED' => array( 'DRIVER' => FALSE, 'RIDER' => FALSE),
        'WEATHERCANCEL' => array('DRIVER' => FALSE, 'RIDER' => FALSE),
        'DESTINATIONCANCEL' => array('DRIVER' => FALSE, 'RIDER' => FALSE),
        'HOSPITALCANCEL' => array('DRIVER' => FALSE, 'RIDER' => FALSE) );

$custom_transition = array ('RIDER' =>array( 
											'COMPLETE' => array( 'DRIVER' => FALSE, 'RIDER' => TRUE ),
        									'DRIVERNOSHOW' => array( 'DRIVER' => FALSE, 'RIDER' => FALSE ),
        									'CANCELEDLATE' => array( 'DRIVER' => FALSE, 'RIDER' => TRUE ),
        									'CANCELEDEARLY' => array( 'DRIVER' => FALSE, 'RIDER' => FALSE),
        									'NOTSCHEDULED' => array( 'DRIVER' => FALSE, 'RIDER' => FALSE),
        									'WEATHERCANCEL' => array('DRIVER' => FALSE, 'RIDER' => FALSE),
        									'DESTINATIONCANCEL' => array('DRIVER' => FALSE, 'RIDER' => FALSE),
        									'HOSPITALCANCEL' => array('DRIVER' => FALSE, 'RIDER' => FALSE) ),
        					'DRIVER' => array( 
        									'COMPLETE' => array( 'DRIVER' => TRUE, 'RIDER' => FALSE ),
        									'DRIVERNOSHOW' => array( 'DRIVER' => FALSE, 'RIDER' => FALSE ),
        									'CANCELEDLATE' => array( 'DRIVER' => TRUE, 'RIDER' => FALSE ),
        									'CANCELEDEARLY' => array( 'DRIVER' => FALSE, 'RIDER' => FALSE),
        									'NOTSCHEDULED' => array( 'DRIVER' => FALSE, 'RIDER' => FALSE),
        									'WEATHERCANCEL' => array('DRIVER' => FALSE, 'RIDER' => FALSE),
        									'DESTINATIONCANCEL' => array('DRIVER' => FALSE, 'RIDER' => FALSE),
        									'HOSPITALCANCEL' => array('DRIVER' => FALSE, 'RIDER' => FALSE) )
        								);
function update_completed_link_status_time_driver( $link_id, $new_status, 
                                                   $arrival_hour, $arrival_min, $arrival_ampm,
                                                   $new_driver_user_id) {
    $current_link = get_past_link($link_id);
    $driver_rate_card = get_current_driver_rate_card($current_link['FranchiseID']);
    if (!$current_link) {
        return FALSE;
    }
	
    if (is_null($new_driver_user_id)) {
        // NULL means we're not changing the driver, but we'll save ourselves the DB lookup.
        $new_driver_info = get_driver_info($current_link['DriverUserID']);
    } else {
        $new_driver_info = get_user_driver_info($new_driver_user_id);
    }

    $must_update_driver = ( $new_driver_info['UserID'] != $current_link['DriverUserID']);

    $must_update_time = FALSE;

    if ($new_status == 'COMPLETE' || $new_status == 'CANCELEDLATE') {
        $arrival_hour = ($arrival_ampm == 'PM' && $arrival_hour != 12) ?
                        $arrival_hour + 12 : $arrival_hour;

        if (is_null($current_link['ReportedArrivalTime']) && ($arrival_hour !== NULL && $arrival_min !== NULL && $arrival_ampm !== NULL) ) {
            $must_update_time = TRUE;
        } else {
            $exploded_time = explode(':', $current_link['ReportedArrivalTime']);

            if ( (((int)$arrival_hour) != ((int)$exploded_time[0])) ||
                 (((int)$arrival_min) != ((int)$exploded_time[1])) ) {
                $must_update_time = TRUE;
            }
        }
    } 

echo $must_update_driver;
    $must_update_status = ($new_status != $current_link['LinkStatus']);
    if ($must_update_status || $must_update_time || $must_update_driver) {
        $start = db_start_transaction();
        if (!$start) {
            db_rollback_transaction();
            echo "<p>Could not start a transaction; could not make requested update.</p>";
            return FALSE;
        }
    }


    if ($must_update_status || $must_update_driver) {
        $driver_and_status_update_success = TRUE;

        // Need to figure out whether payments need to be adjusted
        global $link_status_payment_matrix;
        global $custom_transition;
        $orig_status = $current_link['LinkStatus'];
        $rider_paid = $link_status_payment_matrix[$orig_status]['RIDER'];
        $rider_should_pay = $link_status_payment_matrix[$new_status]['RIDER'];

        $driver_was_paid = $link_status_payment_matrix[$orig_status]['DRIVER'];
        $driver_should_get_paid = $link_status_payment_matrix[$new_status]['DRIVER'];
        
        $rider_user_id = $current_link['RiderUserID'];
         
		if($current_link['CustomTransitionType'] != Null){
			$driver_should_get_paid = $custom_transition[$current_link['CustomTransitionType']][$new_status]['DRIVER'];
			$driver_was_paid = $custom_transition[$current_link['CustomTransitionType']][$orig_status]['DRIVER'];
			$rider_should_pay = $custom_transition[$current_link['CustomTransitionType']][$new_status]['RIDER'];
			$rider_paid = $custom_transition[$current_link['CustomTransitionType']][$orig_status]['RIDER'];
		}

        if (is_null($current_link['DriverUserID'])) {
            $driver_user_id = NULL;
        } else {
            $driver_user_id = $current_link['DriverUserID'];
        }

        $ledger_seq_num = get_max_link_ledger_sequence_num($link_id) + 1;


        // Get previous ledgering amounts
        $prev_ledger = get_last_link_ledger_transaction_set($link_id);
        if (count($prev_ledger) > 0) {
            $prev_ledger_amounts = array();
            foreach($prev_ledger as $prev) {
                $prev_ledger_amounts[$prev['EntityRole']][$prev['EntityID']] += $prev['Cents'];
            }
        }
    
        // TODO:  If there is no driver entry in the last set, search the history?
        $driver_amount = abs($prev_ledger_amounts['DRIVER'][$current_link['DriverUserID']]);
        if (!$driver_amount) {
            if (count($prev_ledger_amounts['DRIVER']) == 1) {
                // TODO:  Get key driver ID, and get amount
                list($ignore_key, $driver_amount) = reset($prev_ledger_amounts['DRIVER']);
                unset($ignore_key);
                $driver_amount = abs($driver_amount);
            }

            if (!$driver_amount) {
                // TODO :  search history.
                $driver_amount = floor($driver_rate_card['CentsPerMile'] * ($current_link['Distance']));
            }
        }

        $rider_amount = $current_link['QuotedCents'];

        // If driver changed, remove payment to previous driver if set
        if ($must_update_driver) {
            // update ledger
            if ($prev_ledger_amounts['DRIVER'][$driver_user_id] > 0) {
                $removed_driver_ledger_id = debit_user( $driver_user_id, 
                        $prev_ledger_amounts['DRIVER'][$driver_user_id],
                        "Driver debit when driver $driver_user_id removed from " .
                        "link {$current_link['LinkID']}", NULL, 'DRIVER' );
                $driver_x_stored = store_adjustment_link_ledger_xref( $current_link['LinkID'], 
                                                                      $driver_ledger_id, 'DRIVER',
                                                                      $ledger_seq_num);
                $driver_and_status_update_success &= $driver_x_stored;
            }

            // update driver
            $driver_id_updated = set_completed_link_driver_id($link_id, $new_driver_info['UserID']);
            $driver_and_status_update_success &= $driver_id_updated;

        }

        // Driver amount = sum of DRIVER transactions for previous set s.t. EntityID = link driver user id
        // Rider amount = sum of RIDER transactions for previous set
        if ($driver_was_paid && !$driver_should_get_paid) {
            // Debit driver
            $driver_ledger_id = debit_user( $driver_user_id, $driver_amount,
                    "Driver debit for link {$current_link['LinkID']} transition from " .
                    "{$current_link['LinkStatus']} to $new_status", NULL, 'DRIVER' );

            $driver_x_stored = store_adjustment_link_ledger_xref( $current_link['LinkID'], 
                                                                  $driver_ledger_id, 'DRIVER',
                                                                  $ledger_seq_num);
            $driver_and_status_update_success &= $driver_x_stored;

        } elseif (!$driver_was_paid && $driver_should_get_paid) {
            // Credit driver
            $driver_ledger_id = credit_user( $new_driver_user_id, $driver_amount,
                    "Driver credit for link {$current_link['LinkID']} transition from " .
                    "{$current_link['LinkStatus']} to $new_status", NULL, 'DRIVER' );

            $driver_x_stored = store_adjustment_link_ledger_xref( $current_link['LinkID'], 
                                                                  $driver_ledger_id, 'DRIVER',
                                                                  $ledger_seq_num);
            $driver_and_status_update_success &= $driver_x_stored;
        } 



        if ($rider_paid && !$rider_should_pay) {
            // credit rider
            $rider_ledger_id = credit_user( $rider_user_id, $rider_amount,
                    "Rider credit for link {$current_link['LinkID']} transition from " .
                    "{$current_link['LinkStatus']} to $new_status" );
            // TODO:  Multiple payors?

            $rider_x_stored = store_adjustment_link_ledger_xref( $current_link['LinkID'], 
                                                                 $rider_ledger_id, 'RIDER',
                                                                 $ledger_seq_num);
            $driver_and_status_update_success &= $rider_x_stored;
        } elseif (!$rider_paid && $rider_should_pay) {
            // debit rider
            $rider_ledger_id = debit_user( $rider_user_id, $rider_amount,
                    "Rider debit for link {$current_link['LinkID']} transition from " .
                    "{$current_link['LinkStatus']} to $new_status" );

            $rider_x_stored = store_adjustment_link_ledger_xref( $current_link['LinkID'], 
                                                                 $rider_ledger_id, 'RIDER',
                                                                 $ledger_seq_num);
            $driver_and_status_update_success &= $rider_x_stored;
        }


        if (!set_completed_link_status($current_link['LinkID'], $new_status)) {
            $driver_and_status_update_success = FALSE;
        }

    }

    if ($must_update_time) {
        $time_updated = set_completed_link_reported_arrival_time($current_link['LinkID'], 
                                                                 "$arrival_hour:$arrival_min");
    }
    if ($start &&
            ((($must_update_status || $must_update_driver_id) &&
               $driver_and_status_update_success) ||
              !$must_update_status) &&
            (($must_update_time && $time_updated) ||
              !$must_update_time)) {

        $commit = db_commit_transaction();
        if ($commit) {
            // TODO:
            //echo "<p>UPDATED {$current_link['LinkID']}.</p>";
        } else {
            // TODO:
            echo "<p>Transaction error occurred for link {$current_link['LinkID']}.</p>";
            db_rollback_transaction();
        }
    } else {
        // TODO:
        //echo "<p>Nothing to update for {$current_link['LinkID']}.  Try again.</p>";
        $rollback = db_rollback_transaction();
    }
}

?>