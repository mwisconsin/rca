<?php
require_once('include/database.php');
require_once('include/rc_log.php');
require_once('include/mapquest.php');
require_once('include/destinations.php');
require_once 'include/driver_rate_card.php';

function create_transition_miles($franchise_id, $date, $to_date = '') {
#list($year, $month, $day) = explode('-', $date);

	$dates = array();
	$dates[] = $date;
	if($to_date != '') {
		$tmpdate = $date;
		while($tmpdate != $to_date) {
			$tmpdate = date('Y-m-d',strtotime("+1 day",strtotime($tmpdate)));
			$dates[] = $tmpdate;
		}
	}

	foreach($dates as $date) {
		
		$past_rider_links = get_rider_history_links_on_date($franchise_id, $date);

    if (count($past_rider_links) > 0) {
        foreach ($past_rider_links as $index => $link) {
            if ($link['LinkStatus'] == 'COMPLETE' ||
                $link['LinkStatus'] == 'CANCELEDLATE') {		
                	
								$desiredArrivalTimeMinus5Minutes = date('H:i:s',strtotime("-5 minute",strtotime($link['DesiredArrivalTime'])) ) ;
								$sql = "update link_history set ReportedArrivalTime = '$desiredArrivalTimeMinus5Minutes' where LinkID = $link[LinkID]";
								mysql_query($sql);                	
           	}
        }
    }

    $past_links = get_history_links_on_date($franchise_id, $date);
    
		$driver_links = array();
		$link_index = array();
		
    $driver_rate_card = get_current_driver_rate_card($franchise_id);
    if(!$driver_rate_card){
    	echo "Failure to get driver rate card.";
    	die();
    }

    if (count($past_links) > 0) {
        foreach ($past_links as $index => $link) {
            if ($link['LinkStatus'] == 'COMPLETE' ||
                $link['LinkStatus'] == 'CANCELEDLATE') {

                $driver_links[$link['DriverUserID']][] = $link['LinkID'];
                $link_index[$link['LinkID']] = $index;
                
								$desiredArrivalTimeMinus5Minutes = date('H:i:s',strtotime("-5 minute",strtotime($link['DesiredArrivalTime'])) ) ;
								$sql = "update link_history set ReportedArrivalTime = '$desiredArrivalTimeMinus5Minutes' where LinkID = $link[LinkID]";
								mysql_query($sql);
            }
        }
    } else {
        echo "No past links on $date ?";
        #fixing for dates that may have no rides (i.e. holidays, etc)
        #return NULL;
        continue;
    }

    if (count($driver_links) > 0) {
        foreach ($driver_links as $driver_id => $links) {

            $driver_user_id = get_driver_user_id($driver_id);
            if (count($links) > 1) {
                $count = count($links) - 1;
                //echo "<br />For driver $driver_id, we need to create $count transitions.<br />";
                for ($i = 0; $i < $count; $i++) {
                    $from_id = $links[$i];
                    $to_id = $links[$i+1];
                    $from = $past_links[$link_index[$from_id]];
                    $to = $past_links[$link_index[$to_id]];

                   // echo "{$from['ToDestinationID']} TO {$to['FromDestinationID']}";
                    if ($from['ToDestinationID'] == $to['FromDestinationID']) {
                        //echo " - same dest - skipping <br />";
                        continue;
                    }

										$desiredArrivalTimeMinus5Minutes = date('H:i:s',strtotime("-5 minute",strtotime($to['DesiredArrivalTime'])) ) ;
										$sql = "update link_history set ReportedArrivalTime = '$desiredArrivalTimeMinus5Minutes' where LinkID = $to_id";
										//echo $sql."<BR>";
										mysql_query($sql);

                    $from_dest = get_destination($from['ToDestinationID']);
                    $to_dest = get_destination($to['FromDestinationID']);

                    // Get distance/cost
                    $distance_and_time = get_mapquest_time_and_distance( $from_dest, $to_dest, TRUE, $to['DesiredArrivalTime'] );
                    $link_distance = $distance_and_time['distance'];
                    $driver_credit_cents = floor($driver_rate_card['CentsPerMile'] * $link_distance);

                    //echo " - $link_distance miles = $driver_credit_cents cents<br />";

                    $start = db_start_transaction();
                    if (!$start) {
                        db_rollback_transaction();
                        echo "<p>Could not start a transaction; could not make requested update.</p>";
                        continue;
                    }

                    // Credit
                    $driver_ledger_id = credit_user( $driver_user_id, $driver_credit_cents,
                                        "Driver credit for transition between link $from_id and $to_id", 
                                        $date, 'DRIVER' );

                    // Store to DB
                    $deadhead_id = store_deadhead_link($driver_id, $from_id, $to_id, 
                                                       $from_dest['FranchiseID'], $from_dest['DestinationID'],
                                                       $to_dest['DestinationID'], $link_distance,
                                                       /* maximize transition time at 30 minutes */
                                                       ($distance_and_time['time']/60 > 30 ? 30 : $distance_and_time['time']/60),
                                                       $driver_credit_cents, $driver_ledger_id);

                    if ($start && $driver_ledger_id && $deadhead_id) {
                        $commit = db_commit_transaction();
                        if ($commit) {
                            //echo " - COMPLETED<br />";
                        } else {
                            echo " - FAILED<br />";
                            db_rollback_transaction();
                            return false;
                        }
                    } else {
						
						if(!$start || !$driver_ledger_id)
                        	echo " - Failed to create transition.(start: $start, ledger id: $driver_ledger_id, deadhead: $deadhead_id)<br />";
                        db_rollback_transaction();
                        //return false;
                    }
                }
            }
        }
    } else {
        echo "No driver links?";
        return NULL;
    }
  }

  return true;
}

function store_deadhead_link($driver_id, $from_link_id, $to_link_id, $franchise_id,
                             $from_destination_id, $to_destination_id, $distance, $minutes,
                             $quoted_cents, $ledger_entry_id) {
    $deadhead_id = FALSE;
    
    $safe_driver_id = mysql_real_escape_string($driver_id);
    $safe_from_link_id = mysql_real_escape_string($from_link_id);
    $safe_to_link_id = mysql_real_escape_string($to_link_id);
    $safe_franchise_id = mysql_real_escape_string($franchise_id);
    $safe_from_destination_id = mysql_real_escape_string($from_destination_id);
    $safe_to_destination_id = mysql_real_escape_string($to_destination_id);
    $safe_distance = mysql_real_escape_string($distance);
    $safe_minutes = mysql_real_escape_string($minutes);
    $safe_quoted_cents = mysql_real_escape_string($quoted_cents);
    $safe_ledger_entry_id = mysql_real_escape_string($ledger_entry_id);

    $sql = "INSERT INTO deadhead_history (UserID, PreviousLinkID, NextLinkID, FranchiseID,
                                          FromDestinationID, ToDestinationID, Distance,
                                          EstimatedMinutes, QuotedCents, LedgerEntryID)
                        VALUES ($safe_driver_id, $safe_from_link_id, $safe_to_link_id, $safe_franchise_id,
                                $safe_from_destination_id, $safe_to_destination_id, $safe_distance,
                                $safe_minutes, $safe_quoted_cents, $safe_ledger_entry_id)";

    $result = mysql_query($sql);
    
    if ($result) {
        $deadhead_id = mysql_insert_id();
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 'Error storing deadhead link', $sql);
    }

    return $deadhead_id;
}

?>
