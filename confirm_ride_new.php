<?php
include_once 'include/user.php';
redirect_if_not_logged_in();

require_once('include/rider.php');
require_once('include/link.php');
require_once('include/mapquest.php');
require_once('include/destinations.php');
require_once('include/link_price.php');

require_once('include/rc_log.php');
require_once('include/care_facility.php');
require_once('include/large_facility.php');
require_once('include/ledger.php');
require_once('include/franchise.php');
require_once('include/custom_ride_transition.php');
#error_reporting(E_ALL);
session_start();

$_SESSION['CACHED_SCHEDULE']['REFERER_TIME'] = time();
if ( strpos($_SERVER['HTTP_REFERER'], 'large_facility_set_ride_destinations.php') !== FALSE ||
     (strpos($_SERVER['HTTP_REFERER'], 'confirm_ride_new.php') !== FALSE && 
      $_SESSION['LFSCHED']['rider_id'])) {
    $large_facility_id = get_working_large_facility();
    $large_facility_info = get_large_facility($large_facility_id);
    $large_facility_rider_info = get_large_facility_rider($_SESSION['LFSCHED']['rider_id']);
    $rider_info = get_user_rider_info($large_facility_info['RiderUserID']);
} else {
    $rider_info = get_user_rider_info( get_affected_user_id() );
}
	
$ride_request_raw = $_REQUEST['rides'];

$hash = $_REQUEST['ver'];
if ($hash != sha1($ride_request_raw . 'A little extra verification')) {
  // TODO:  Redirect intelligently, log better
  rc_log(PEAR_LOG_ERR, "Hash mismatch receiving ride request for rider {$rider_info['UserID']}.");
}

if (!$ride_request_raw) {
  header('Location: home.php');
	die();
}

if (get_magic_quotes_gpc()) {
    $ride_request = unserialize(stripslashes(urldecode($ride_request_raw)));
} else {
    $ride_request = unserialize(urldecode($ride_request_raw)); 
}
    
if($ride_request['RiderID'] != get_affected_user_id()){
	unset($_SESSION['CACHED_SCHEDULE']);
  header("Location: plan_ride.php");
	die();
}
    
if($ride_request['Type'] == 'Finish Custom Transition'){
   $main_link = get_link($ride_request['AffectedLinks'][0]);
   $rider_info['UserID'] = $main_link['RiderUserID'];
   $rider_info['FranchiseID'] = $main_link['FranchiseID'];
}
if($ride_request['Type'] == 'Apply Changes'){
	$saved_driver_id = NULL;
	foreach($ride_request['AffectedLinks'] as $k => $v)
		if($saved_driver_id == NULL){
			$main_link = get_link($ride_request['AffectedLinks'][$k]);
			$saved_driver_id = $main_link['AssignedDriverUserID'];
		}
}

$franchise_id = get_current_user_franchise();
set_franchise_timezone($franchise_id);  

$service_area_zips = get_franchise_service_zips($franchise_id);
$is_out_of_area = FALSE;
$is_on_demand = FALSE;


for ($i = 0; $i < count($ride_request['links']); $i++) {
    $curr_link = $ride_request['links'][$i];
    $out_of_area_link = FALSE;
    
    $from_dest = get_destination($curr_link['from']);
    $to_dest = get_destination($curr_link['to']);
    if ($ride_request['Type'] != 'Finish Custom Transition' 
    	&& !$service_area_zips[$to_dest['ZIP5']]
    	&& $to_dest['is_local_area_override'] != TRUE) {
        $is_out_of_area = TRUE;
        $ride_request['links'][$i]['to_out_of_area'] = TRUE;
        $out_of_area_link = TRUE;
    }
    if ($ride_request['Type'] != 'Finish Custom Transition' 
    	&& !$service_area_zips[$from_dest['ZIP5']]
    	&& $from_dest['is_local_area_override'] != TRUE) {
        $is_out_of_area = TRUE;
        $ride_request['links'][$i]['from_out_of_area'] = TRUE;
        $out_of_area_link = TRUE;
    }
    
    if($curr_link['OnDemandFlag'] == 'on') $is_on_demand = TRUE;

    $distance_and_time = get_mapquest_time_and_distance( $from_dest, $to_dest, $curr_link['DesiredArrivalTime'] );
    $link_distance = round($distance_and_time['distance'], 2);
    $link_minutes = ceil($distance_and_time['time'] / 60.0);

    if ($out_of_area_link) {
        $link_price = get_out_of_area_link_price($link_distance, $franchise_id, 
                                                 $from_dest['DestinationID'], $to_dest['DestinationID']);
    } else {
        $link_price = get_link_price($link_distance, $franchise_id, 
                                     $from_dest['DestinationID'], $to_dest['DestinationID'],
                                     "{$ride_request['year']}-{$ride_request['month']}-{$ride_request['day']}");
    }
    
    if($is_on_demand) {
    	$link_price = get_taxi_link_price($link_distance);
    }
    
    if($out_of_area_link)
      // Minimum Price 10.00 for out_of_area
      $link_price['Total'] = $link_price['Total'] < 1000 ? 1000 : $link_price['Total'];


		/* add 5 minutes to travel time if the from destination is a grocery store */		
		if( is_destination_grocery_store( $curr_link['from'] ) ) $link_minutes += 5;
		
    $travel_time = (60 * $link_minutes) + (60 * 5) + (60 * 5);  // load, pad TODO:  configurable
    $travel_time += $from_dest["AdditionalMinutes"] + $to_dest["AdditionalMinutes"];
    // TODO:  TIMING HERE

    if (!isset($first_absolute_time_index) && 
        (isset($curr_link['from_time']) || isset($curr_link['to_time']))) {
        $first_absolute_time_index = $i;
    }

    $ride_request['links'][$i]['link_distance'] = $link_distance;
    $ride_request['links'][$i]['link_minutes'] = $link_minutes;
/*$ride_request['links'][$i]['link_minutes'] = get_link_estimated_minutes(array(
			'DesiredArrivalTime' => "{$ride_request['year']}{$ride_request['month']}-{$ride_request['day']} {$ride_request['Year']}", 
			'FranchiseId'=> $franchise_id, 
			'EstimatedMinutes' => $link_minutes
));*/
    $ride_request['links'][$i]['link_price'] = $link_price;
}

$padding = (5 * 60) + (5 * 60);  // TODO: Configurable.  Loading + padding

$first_time_link = $ride_request['links'][$first_absolute_time_index];

// Now calculate desired arrivals at all locations -- first backwards
for ($i = $first_absolute_time_index; $i >= 0; $i--) {
    $curr_link = $ride_request['links'][$i];
$padding = (($curr_link['PrePadding'] != NULL ? $curr_link['PrePadding'] : 5) * 60) + (($curr_link['PostPadding'] != NULL ? $curr_link['PostPadding'] : 5) * 60);
$padding += ($from_dest["AdditionalMinutes"] * 60) + ($to_dest["AdditionalMinutes"] * 60);


    if (isset($curr_link['to_time']) && isset($curr_link['from_time'])) {
        $arrive_time = mktime( $curr_link['to_time']['hour'], $curr_link['to_time']['minute'], 0, 
                               $ride_request['month'], $ride_request['day'], $ride_request['year']);
        $leave_time = mktime( $curr_link['from_time']['hour'], $curr_link['from_time']['minute'], 0, 
                              $ride_request['month'], $ride_request['day'], $ride_request['year']);

        $travel_time = ($curr_link['link_minutes'] * 60) + $padding;
        if ($leave_time + $travel_time > $arrive_time) {
            // TODO!
            $error_text .= "Transit time too long for ride #$i.<br />";
        } 
    } elseif (isset($curr_link['to_time'])) {
        $arrive_time = mktime( $curr_link['to_time']['hour'], $curr_link['to_time']['minute'], 0, 
                               $ride_request['month'], $ride_request['day'], $ride_request['year']);
        
        $travel_time = ($curr_link['link_minutes'] * 60) + $padding;
        $leave_time = $arrive_time - $travel_time;
    } elseif (isset($curr_link['from_time'])) {
        $leave_time = mktime( $curr_link['from_time']['hour'], $curr_link['from_time']['minute'], 0, 
                              $ride_request['month'], $ride_request['day'], $ride_request['year']);
        $travel_time = ($curr_link['link_minutes'] * 60) + $padding;
        $arrive_time = $leave_time + $travel_time;
    } elseif (isset($ride_request['links'][$i+1]['from_stay'])) {
        $arrive_time = $ride_request['links'][$i+1]['leave_time'] - 
                                (60 * $ride_request['links'][$i+1]['from_stay']);
        $travel_time = ($curr_link['link_minutes'] * 60) + $padding;
        $leave_time = $arrive_time - $travel_time;
    } else {
        $arrive_time = $ride_request['links'][$i+1]['leave_time'];
        $travel_time = ($curr_link['link_minutes'] * 60) + $padding;
        $leave_time = $arrive_time - $travel_time;
    }
    $ride_request['links'][$i]['leave_time'] = $leave_time;
    $ride_request['links'][$i]['arrive_time'] = $arrive_time;
}

// Now count up
for ($i = $first_absolute_time_index; $i < count($ride_request['links']); $i++) {
    $curr_link = $ride_request['links'][$i];
$padding = (($curr_link['PrePadding'] != NULL ? $curr_link['PrePadding'] : 5) * 60) + (($curr_link['PostPadding'] != NULL ? $curr_link['PostPadding'] : 5) * 60);
$padding += ($from_dest["AdditionalMinutes"] * 60) + ($to_dest["AdditionalMinutes"] * 60);


    if (isset($curr_link['to_time']) && isset($curr_link['from_time'])) {
        $arrive_time = mktime( $curr_link['to_time']['hour'], $curr_link['to_time']['minute'], 0, 
                               $ride_request['month'], $ride_request['day'], $ride_request['year']);
        $leave_time = mktime( $curr_link['from_time']['hour'], $curr_link['from_time']['minute'], 0, 
                              $ride_request['month'], $ride_request['day'], $ride_request['year']);

        $travel_time = ($curr_link['link_minutes'] * 60) + $padding;
        if ($leave_time + $travel_time > $arrive_time) {
            // TODO!
            $error_text .= "Transit time too long for ride #$i.<br />";
        } 
    } elseif (isset($curr_link['to_time'])) {
        $arrive_time = mktime( $curr_link['to_time']['hour'], $curr_link['to_time']['minute'], 0, 
                               $ride_request['month'], $ride_request['day'], $ride_request['year']);
        
        $travel_time = ($curr_link['link_minutes'] * 60) + $padding;
        $leave_time = $arrive_time - $travel_time;
    } elseif (isset($curr_link['from_time'])) {
        $leave_time = mktime( $curr_link['from_time']['hour'], $curr_link['from_time']['minute'], 0, 
                              $ride_request['month'], $ride_request['day'], $ride_request['year']);
        $travel_time = ($curr_link['link_minutes'] * 60) + $padding;
        $arrive_time = $leave_time + $travel_time;
    } elseif (isset($curr_link['from_stay'])) {

        $leave_time = $ride_request['links'][$i-1]['arrive_time'] + 
                                (60 * $curr_link['from_stay']); 
        $travel_time = ($curr_link['link_minutes'] * 60) + $padding;
        $arrive_time = $leave_time + $travel_time;
    } else {
        $leave_time = $ride_request['links'][$i-1]['arrive_time'] + (60 * 5);  // Some buffer
        $travel_time = ($curr_link['link_minutes'] * 60) + $padding;
        $arrive_time = $leave_time + $travel_time;
    }

    if (isset($curr_link['from_stay'])) {
        $max_leave_time = $ride_request['links'][$i-1]['arrive_time'] + (60 * $curr_link['from_stay']);
        if ($leave_time < $max_leave_time) {
            $curr_disp_idx = $i + 1;
            $error_text .= "Transit time for ride #$curr_disp_idx not compatible with stay time at destination for ride #$i.<br />";
        }
    } 

    $ride_request['links'][$i]['leave_time'] = $leave_time;
    $ride_request['links'][$i]['arrive_time'] = $arrive_time;
    
    $ride_request['links'][$i]['link_price']['RiderShare'] = 
    	$ride_request['links'][$i]['link_price']['RiderShare'] * ($curr_link['ridercount'] > 2 ? 2 : 1);
    
    if(outside_normal_times( $ride_request['links'][$i] )) {
    	$sql = "select * from scheduling_afterhours where FranchiseID = ".get_current_user_franchise();
			$rs = mysql_fetch_assoc(mysql_query($sql));
    	$ride_request['links'][$i]['link_price']['Total'] = 
    		$ride_request['links'][$i]['link_price']['Total'] + ($rs['amount_of_charge']*100);
    	$ride_request['links'][$i]['link_price']['RiderShare'] = 
    		$ride_request['links'][$i]['link_price']['RiderShare'] + ($rs['amount_of_charge']*100);
    }
}

if (!$error_text) {
	if (isset($_REQUEST['ConfirmLink'])) {
		$releasedtodriverholder=$_GET['ReleasedD'];

		if($ride_request['Type'] == 'Finish Custom Transition' && is_array($ride_request['AffectedLinks'])){
	    $driver_confirmed = true;
		foreach($ride_request['AffectedLinks'] as $link_id){
			if(!get_driver_confirm($link_id))
				$driver_confirmed = false;
			}
		}
	
		if($releasedtodriverholder > 0) {
			$driver_confirmed = true;	
		} else {
			$driver_confirmed = false;
		}
	
    if($ride_request['Type'] == 'Finish Custom Transition'){
    	$customTransitionID = create_new_custom_transition($ride_request['DriverID']);
    }
	
		$link_count = 0;
    foreach($ride_request['links'] as $link) {
      $from_dest_id = $link['from'];
      $to_dest_id = $link['to'];
			if($ride_request['Type'] != 'Finish Custom Transition'){				
				add_destination_for_rider($rider_info['UserID'],$from_dest_id);
				add_destination_for_rider($rider_info['UserID'],$to_dest_id);
			}
			if(count($link['riders']) > 0){
			  $rider_info['UserID'] = array_pop(array_reverse($link['riders']));
				$link['riders'][array_pop(array_reverse(array_keys($link['riders'])))] = NULL;
			}
			$departure_time = FALSE;
			if(isset($link['from_time']) || isset($link['from_stay'])){
				$departure_time = $link['leave_time'];
			}
			
			// double check confirmed check boxes and update 
			if 	(isset($_REQUEST['DepartureTimeConfirmed'][$link_count]) && $_REQUEST['DepartureTimeConfirmed'][$link_count]=='Yes') {
			  $link['DepartureTimeConfirmed'] = 1;
			  $ride_request['links'][$link_count]['DepartureTimeConfirmed'] = 1;
			} else {
			  $link['DepartureTimeConfirmed'] = 0;
			  $ride_request['links'][$link_count]['DepartureTimeConfirmed'] = 0;
			}
			if (isset($_REQUEST['ArrivalTimeConfirmed'][$link_count]) && $_REQUEST['ArrivalTimeConfirmed'][$link_count]=='Yes') {
			  $link['ArrivalTimeConfirmed'] = 1;
			  $ride_request['links'][$link_count]['ArrivalTimeConfirmed'] = 1;
			} else {
			  $link['ArrivalTimeConfirmed'] = 0;
			  $ride_request['links'][$link_count]['ArrivalTimeConfirmed'] = 0;
			}

     	$link_id = add_rider_link_request( $rider_info['UserID'], $from_dest_id,
                                         $to_dest_id, $link['arrive_time'],
                                         $link['link_distance'], $link['link_minutes'], 
                                         $link['link_price']['RiderShare'] ,
                                         $link['ridercount'], 
										   									 $link['DepartureTimeConfirmed'], 
										   									 $link['ArrivalTimeConfirmed'], 
										  									 $link['Note'], 
										  									 $link['FlexFlag'] == 'on' ? 1 : 0, 
										  									 $driver_confirmed, 
										  									 $link['PrePadding'], 
										  									 $link['PostPadding'], 
										  									 $departure_time,
										   									 get_current_user_id(), 
										   									 date("Y-m-d H:i:s"), 
										   									 @$link['Created_By'], 
										   									 @$link['Created_Date']);
										   									 
      if($ride_request['Type'] == 'Finish Custom Transition'){
				set_link_custom_transiton_type($link_id, 'DRIVER');
				set_link_driver_user_id($link_id, $ride_request['DriverID']);
				connect_link_with_custom_ride_transition($link_id, $customTransitionID);
				
				foreach($link['riders'] as $rider)
					if($rider !== NULL)
				   		add_additional_rider($link_id, $rider);
			} else if($ride_request['Type'] == 'Apply Changes') {
				if($saved_driver_id != NULL)
					set_link_driver_user_id($link_id, $saved_driver_id);
			}
		
      if ($rider_info['UserID'] &&
          $rider_care_facility = get_user_current_care_facility($rider_info['UserID'])) {
          connect_care_facility_to_link($rider_care_facility, $link_id); 
      }

      if ($large_facility_info)
      	connect_large_facility_to_link($large_facility_info['LargeFacilityID'], $_SESSION['LFSCHED']['rider_id'], $link_id);
		
			// if threshold was crossed
			$available_balance = calculate_user_ledger_balance($rider_info['UserID']);
			$threshold_balance = $rider_info['RechargeThreshold'];
			$ride_cost = $link['link_price']['Total'];
			if ($available_balance-$ride_cost<$threshold_balance && rider_is_valid_for_ab_review($franchise_id,$rider_info['UserID'])) {
			  $club_emails = get_franchise_email_addresses($franchise_id, 'ra_threshold');
			  if (sizeof($club_emails)>0)
				  foreach($club_emails as $email)
						if (isset($email) && ($email!=''))
						  mail($email, 'Rider Alert - Threshold' . $date, 'Rider '. $rider_info['UserID']. '; '.get_displayable_person_name_string(get_user_person_name($rider_info['UserID'])).', Club '.$franchise_id.' has exceeded their threshold.'."\n\r".'Rider Threshold: $'.number_format($threshold_balance/100,2)."\n\r".'Available Balance: $'.number_format(($available_balance-$ride_cost)/100,2), "From: Riders Club of America <admin@myridersclub.com>");
			}
			$link_count++;
    } // foreach($ride_request['links'] as $link)

        if (isset($ride_request['Type']) && $ride_request['Type'] == 'Apply Changes') {
            foreach($ride_request['AffectedLinks'] as $link_id){
                delete_link($link_id);
                //echo $link_id . '-';
            }
        }
        if(isset($ride_request['Type']) && $ride_request['Type'] == 'Finish Custom Transition'){
        	foreach($ride_request['AffectedLinks'] as $link_id){
        		set_link_custom_transiton_type($link_id, 'RIDER');
        		connect_link_with_custom_ride_transition($link_id, $customTransitionID);
       		}
        }
  $_SESSION['COPY_PATH_CACHED_SCHEDULE'] = $_SESSION['CACHED_SCHEDULE'];
	unset($_SESSION['CACHED_SCHEDULE']);
    }
}
if(isset($ride_request['Type']) && $ride_request['Type'] == 'Finish Custom Transition')
	$is_out_of_area = false;

// TODO:  If the ride is out-of-area, need to make sure it does not start/end out-of-area.
if ($is_out_of_area) {
    // TODO:  To get the wait charge, find the link that takes the rider OOA (LinkOut).
    //        Then find the link that brings the rider back into the area (LinkIn).
    //        Walk through the intermediate links, applying wait charges for each.
}
	
	include_once 'include/header.php';
?>
<form method="post" action="">
<h2 style="text-align:center;">Confirm Ride Request</h2>
<div style="width:320px; border:solid 1px #000; padding:5px; margin-bottom: 0.5em; text-align:center;">
<?php if (isset($error_text)) {
    echo '<span style="font:24px bold;">Scheduling Error Detected</span><br />';
    echo $error_text . '<br />';
} elseif (!isset($link_id)) { ?>
    <span style="font:24px bold;">Confirmation</span>
		<?php if ($is_out_of_area) { ?>
	    <p style="font-weight: bold">
	        You have requested a ride to a location outside of the franchise service area.  
	        We cannot guarantee a driver for out-of-area rides, but we will try to locate 
	        a driver for you.  Please note that there is a charge for time the driver will wait
	        out of the service area.  That charge is not included in the quote below.  Finally,
	        please realize that this ride request must be reviewed and approved before any 
	        attempt to schedule a driver will be made.
	    </p> 
		<?php } ?>
    <br>
    Read your ride information, then click "Confirm" if everything is correct.
    <br /><br />
    <?php 
    if($is_on_demand) echo "<b style='color: red;'>NOTE: THE RIDES BELOW INCLUDE ON DEMAND PRICING.</b><br><br>";
    
  	if ($ride_request['Type'] == 'Finish Custom Transition' || user_has_role($rider_info['UserID'], $franchise_id, 'Rider') ) {
    	if($ride_request['Type'] != 'Finish Custom Transition'){
				$available_balance = calculate_user_ledger_balance($rider_info['UserID']) - calculate_riders_incomplete_ride_costs($rider_info['UserID']);
        $recharge_threshold = $rider_info['RechargeThreshold'];
        $cf_string = "";
        
        if(get_user_current_care_facility($rider_info['UserID']) !== false) {
        	$care_facility_id = get_user_current_care_facility($rider_info['UserID']);
        	$care_facility = get_all_active_care_facility_user_info_xx($franchise_id, $care_facility_id)[0];
        	$available_balance = $care_facility['AvailableBalance'];
        	$recharge_threshold = $care_facility['RechargeThreshold'];
        	$cf_string = "CF ";
        }
        $threshold_string = format_dollars($recharge_threshold);
        $balance_string = format_dollars($available_balance);
        ?>
    <?php echo $cf_string; ?>Available Balance Before Being Charged = <?php echo $balance_string; ?><br />
    <?php echo $cf_string; ?>Threshold = <?php echo $threshold_string; ?><br />
    
    
    <?php 
			$days_left = get_days_until_fee_due($rider_info['UserID']);
			if(get_user_current_care_facility($rider_info['UserID']) !== false) {
				$care_facility_id = get_user_current_care_facility($rider_info['UserID']);
        $care_facility = get_all_active_care_facility_user_info_xx($franchise_id, $care_facility_id)[0];
        $afdate = $care_facility['AnnualFeePaymentDate'];
		    $interval = date_diff(date_create('@'.time()),date_create($afdate));
		    $days_left = $interval->format('%r%a');
			}
			if($days_left <= 30 && $days_left > 0)
				echo $cf_string."Annual fee is due in less then $days_left days.";
			else if($days_left < 0)
				echo "<br>".$cf_string."Annual fee is past due.";
			}
		} ?>
    <br>
    <br>
	   <?php
	   $releasedtodriverholder = $_REQUEST['releasedtodriverholder'];
		  $request = serialize($ride_request);
		  $hash = sha1($request . 'A little extra verification');
		  $request = urlencode($request);
		?>

		<input type=hidden name=ver value="<?php echo $hash; ?>">
		<input type=hidden name=rides value="<?php echo $request; ?>">
		<button type="button" onClick="window.location = 'plan_ride.php';">Back</button>
		<input type="hidden" name="releasedtodriverholder" id="releasedtodriverholder" value="<?php echo $releasedtodriverholder; ?>" />
		<input type="submit" name="ConfirmLink" value="Confirm" />
		<?php
		$grand_total = 0;
		$prices = array();
    for ($i = 0; $i < count($ride_request['links']); $i++) {
    	$link = $ride_request['links'][$i];
			if ($ride_request['Type'] != 'Finish Custom Transition'){
      	$price_total = $link['link_price']['Total'];
      	$price_total *= $link['ridercount'] > 2 ? 2 : 1;
      	if ($link['link_price']['FromPartnerID'] || $link['link_price']['ToPartnerID']) {
      	    $bp_total = $link['link_price']['FromPartnerAmount'] + 
     	                 $link['link_price']['ToPartnerAmount'];
     	      $prices[] = format_dollars($price_total).
      	    	'<hr />Business Partners will pay ' . format_dollars($bp_total) .
      	    	'<br />Your share is ' . format_dollars($link['link_price']['RiderShare']);
      	} else { 
      	    $prices[] = format_dollars($price_total);
      	}
      	$grand_total += $price_total;
      } 
    }   	
    echo join('/',$prices);
		if($ride_request['Type'] != 'Finish Custom Transition' && rider_is_valid_for_ab_review( $franchise_id, get_affected_user_id() ) 
			&& $available_balance - $grand_total <= $recharge_threshold ) {
			echo "<script>";
			echo "current_user = ".get_affected_user_id().";";
			echo <<<JS
jQuery(function($) {
	if($('#threshwarn').length > 0) {
		$('#threshwarn').dialog({
			title: 'Rider Account Balance - Notice',
			width: 545
		});
		$('#threshRechargeNo').on('click',function() {
			$.get('/xhr/update_user_requestcontact.php?user='+current_user,function() {
				$('#threshwarn').dialog('close');
			});
		});
		$('#threshRechargeYes').on('click',function() {
			$('#threshwarn').dialog('close');
			window.open('/make_payment.php?amt=' + $('#threshRechargeAmount').html().replace('$',''));
		});
		$('#threshRechargeOther').on('click',function() {
			var d = $('<div><input size=10 id=rechargeAmt></div>').dialog({
				title: 'Specify Recharge Amount',
				buttons: [
					{ text: 'Ok',
						click: function() {
							if(parseFloat($('#rechargeAmt').val(),10) > 0) {
								window.open('/make_payment.php?amt=' + $('#rechargeAmt').val());
								d.dialog('destroy');
								$('#threshwarn').dialog('close');
							}
						}
					},
					{ text: 'Cancel',
						click: function() {
							d.dialog('destroy');
						}
					}
				]
			});
		});
	}	

});		
		
JS;
		
		echo "</script>";
		echo "<div id='threshwarn'>";
		echo "After this ride is scheduled, your rider account will have <b>".format_dollars($available_balance - $grand_total)."</b> available for future rides.<BR><BR>";
		echo "This is below your threshold of <b>".format_dollars($recharge_threshold)."</b>.<BR><BR>";
		$sql = "select * from supporter_rider left join users on SupporterUserID = UserID natural join person_name where RiderUserID = ".get_affected_user_id();
		$r = mysql_query($sql);
		if(mysql_num_rows($r) > 0 && $rider_info['RechargePaymentType'] == 'ContactSupporter') {
			
			$friends = array();
			while($rs = mysql_fetch_array($r)) $friends[] = rtrim($rs["FirstName"]." ".$rs["LastName"]);	
			echo "We will contact your Supporting Friend for assistance: ".join(', ',$friends);
			
		} else {
			$sql = "select * from ach_to_process where userid = ".get_affected_user_id()." and dts > now()";
			$r = mysql_query($sql);
			$ach = 0;
			if(mysql_num_rows($r) > 0) {
				$rs = mysql_fetch_array($r);
				$ach = $rs["amount"];
				echo "We will process a payment of <b>$".$rs["amount"]." on ".date("l, n/d/Y",strtotime($rs["dts"]))."</b>.<BR><BR>";
			} 
				$sql = "select RechargeAmount, RechargePaymentType from users where UserID = ".get_affected_user_id();
				$rs = mysql_fetch_array(mysql_query($sql));
				$payment_verbiage = $rs["RechargePaymentType"] == "SendChecks" ? "Would you like to send in a check" : 
					($rs["RechargePaymentType"] == "CreditCard" ? "Would you like us to charge your credit card" :
					"Would you like us to process a payment request");
				echo "$payment_verbiage for <b><span id=threshRechargeAmount>".format_dollars($rs["RechargeAmount"])."</span></b>?<br>";
				echo "<input type=button value=Yes id=threshRechargeYes>&nbsp;&nbsp;&nbsp;";
				echo "<input type=button value=No id=threshRechargeNo>&nbsp;&nbsp;&nbsp;";
				echo "<input type=button value='Other Amount' id=threshRechargeOther>";
		}
			echo "</div>";

	}
	
	$sql = "select * from franchise where FranchiseID = $franchise_id";
	$franchise = mysql_fetch_array(mysql_query($sql));
	
	$thresh = $franchise['MinThreshold'];
	if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise_id, 'Franchisee'))
		$thresh = 0-($ach*100);
		
	if($available_balance - $grand_total < $thresh) {
		echo "<script>";
		echo <<<JS
jQuery(function($) {
	if($('#threshwarn').length > 0) $('#threshwarn').remove();
	if($('#threshnsf').length > 0) {
		$('#threshnsf').dialog({
			title: 'Rider Account Balance - NSF',
			modal: true,
			buttons: [
				{
					text: 'Ok',
					click: function() {
						window.location.href = '/plan_ride.php';
					}
				},
				{
					text: 'Make Payment',
					click: function() {
						window.location.href = '/make_payment.php';
					}
				}
			]
		});
	}		
});
JS;
		echo "</script>";
		echo "<div id='threshnsf'>";
		#echo "avail $available_balance, grand $grand_total, thresh $franchise[MinThreshold], ach $ach<br>";
		# echo "ccf: ".get_user_current_care_facility(get_affected_user_id())."<BR>";
		echo "You do not have sufficient funds to schedule this ride.";
		echo "</div>";
		
	}	 
} else { 
		check_rider_threshold_limits($rider_info['UserID']);
		if(user_has_role($rider_info['UserID'],  $franchise_id, 'Rider')){
			$available_balance = calculate_user_ledger_balance($rider_info['UserID']) - calculate_riders_incomplete_ride_costs($rider_info['UserID']);
		}
?>
    <span style="font:24px bold;">Ride Request Confirmed!</span>
    <br />
    Request <?php echo $link_id ?> was saved.<br />
    <?php 
    if (user_has_role($rider_info['UserID'], $franchise_id, 'Rider')) { 
        $threshold_string = format_dollars($rider_info['RechargeThreshold']);
        $balance_string = format_dollars($available_balance);
    ?>
    Available Balance = <?php echo $balance_string; ?><br />
    Threshold = <?php echo $threshold_string; ?>
    <?php } ?>
    <br />
    <a href="<?php if($ride_request['Type'] != 'Finish Custom Transition'){ ?>
    myrides.php">View Your Rides</a>
							<?php } else { ?> 
    	manifest.php?date=<?php echo "{$ride_request['year']}-{$ride_request['month']}-{$ride_request['day']}"; ?>">View Your Manifest</a>
    					<?php } ?>
    <br />
    <br />
    <a href="<?php if ($large_facility_info) { echo "large_facility_"; } 
                ?>plan_ride.php?Reset=true">Schedule Another Ride</a>
    <br><br>
    <a href="/plan_ride.php">Copy Path</a>
<?php } ?>
</div>
<?php if ($large_facility_info) {
    echo '<p>Facility: ' . $large_facility_info['LargeFacilityName'] . 
         '<br />Rider Name: ' . get_displayable_person_name_string($large_facility_rider_info) . '</p>';
}
    $additional_riders = 0;
    foreach($ride_request['links'] as $link){
        if(count($link['riders']) > $additional_riders)
            $additional_riders = count($link['riders']);
    }
    $additional_riders = ($additional_riders > 1) ? true : false;
?>
<table border="1">
    <tr><?php if($additional_riders){ ?><th width="200px">Riders</th><?php } ?><th>Depart</th><th>At</th><th>&nbsp;</th><th>Arrive</th><th>At</th><th>Estimated Time</th>
        <th>Distance</th><th>Price</th><th>Riders</th><?php if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise_id, 'Franchisee')) echo '<th>Notes</th>'; ?></tr>
<?php
		$grand_total = 0;
    for ($i = 0; $i < count($ride_request['links']); $i++) {
        $link = $ride_request['links'][$i];
?>
<tr><?php if($additional_riders){ ?>
    <td>
        <?php foreach($link['riders'] as $rider)
                echo get_displayable_person_name_string(get_user_person_name($rider)) . "<br>";
        ?>
    </td>
    <?php } ?>
    <td nowrap="nowrap" style="padding-left: 0.5em;"><?php display_destination($link['from']); ?></td>
    <?php 
    	if(outside_normal_times( $link ) && !isset($_REQUEST['ConfirmLink']) ) display_special_charge_popup( $link );
    ?>
    <td><?php echo date('g:i A', $link['leave_time']) . ' on ' .
                   date('l, F j, Y', $link['leave_time']) ?><br /><?php
                   if (isset($_REQUEST['ConfirmLink'])) { 
				     if (isset($_REQUEST['DepartureTimeConfirmed'][$i]) && ($_REQUEST['DepartureTimeConfirmed'][$i]==='Yes')) {
					   echo '<u>Conf_d</u>';
					 }
				   } else {
				     //echo '.'.$link['DepartureTimeConfirmed'].'.';
					 ?>
                     
                     Confirmed: <input type="checkbox" value="Yes" name="DepartureTimeConfirmed[<?php echo $i; ?>]" <?php if($link['DepartureTimeConfirmed'] == 1) {
	echo 'checked="checked"';
} else {
    echo '';
} ?> />               
                     <?php
                   }
				   ?></td>
    <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
    <td nowrap="nowrap"><?php display_destination($link['to']); ?></td>
    <td><?php 
    			if(date('l',$link['arrive_time']) != date('l',$link['leave_time'])) display_date_popup( $link );
    			echo date('g:i A', $link['arrive_time']) . ' on ' .
                   date('l, F j, Y', $link['arrive_time']) ?><br /><?php
                   if (isset($_REQUEST['ConfirmLink'])) {
				     if (isset($_REQUEST['ArrivalTimeConfirmed'][$i]) && ($_REQUEST['ArrivalTimeConfirmed'][$i]==='Yes')) {
					   echo '<u>Conf_d</u>';
					 }
				   } else {
				    // echo '.'.$link['ArrivalTimeConfirmed'].'.';
				     ?>
                     Confirmed: <input type="checkbox" value="Yes" name="ArrivalTimeConfirmed[<?php echo $i; ?>]" <?php if($link['ArrivalTimeConfirmed'] == 1) {
	echo 'checked="checked"';
} else {
    echo '';
} ?> />               
                     <?php
                   }
				   ?></td>
    <td><?php echo $link['link_minutes'] . ' minutes'; ?></td>
    <td><?php echo $link['link_distance'] . ' miles'; ?></td>
    <td><?php 
    			if ($ride_request['Type'] != 'Finish Custom Transition'){
                	$price_total = $link['link_price']['Total'];
                	$price_total *= $link['ridercount'] > 2 ? 2 : 1;
                	if ($link['link_price']['FromPartnerID'] || $link['link_price']['ToPartnerID']) {
                	    $bp_total = $link['link_price']['FromPartnerAmount'] + 
               	                 $link['link_price']['ToPartnerAmount'];
                	    echo format_dollars($price_total);
                	    echo '<hr />Business Partners will pay ' . format_dollars($bp_total);
                	    echo '<br />Your share is ' . format_dollars($link['link_price']['RiderShare']);
                	} else { 
                	    echo format_dollars($price_total);
                	}
                	$grand_total += $price_total;
                } ?></td>
     <td><?php echo $link['ridercount']; ?></td>
     <?php if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise_id, 'Franchisee')){ ?>
     	<td><?php echo $link['Note']; ?><BR><BR><?php
     		if($link['FlexFlag'] == 'on') echo "<B>Note: Time is Flexible</B>";
     		?></td>
     <?php } ?>
    </tr>

<?php
    }
?>
    </table>
    </form>
<?php
//	echo "total charge: $grand_total<BR>Available: $available_balance<br>Thresh: ".$rider_info['RechargeThreshold'];
	

    include_once 'include/footer.php';
    display_approaching_annual_fee_popup();
    


function display_special_charge_popup( $link ) {
	$sql = "select * from scheduling_afterhours where FranchiseID = ".get_current_user_franchise();
	$rs = mysql_fetch_assoc(mysql_query($sql));
	
	$str = "The Departure Time of ".date("g:i A",$link['leave_time'])." or Arrival Time of ".date("g:i A",$link['arrive_time'])." falls outside of the range of standard hours of operation for your Riders Club.&nbsp;If you continue booking this ride, you'll be charged an additional $".number_format($rs["amount_of_charge"],2)." for this ride.";
	echo '
		<script>
		jQuery(function($) {
			$("<div>'.$str.'</div>").dialog({
				title: "Important Notice: After Hours Ride",
				width: 500,
				modal: true,
				buttons: {
					"Confirm": function() { $(this).dialog("close"); },
					"Cancel and Return to Plan Ride": function() { window.location.href = "/plan_ride.php"; }
				}
			});
		});
		</script>
		';
}

function display_date_popup( $link ) {
	
	$str = "The Departure Time of ".date("g:i A, l",$link['leave_time'])." and  Arrival Time of ".date("g:i A, l",$link['arrive_time'])." fall on different days. If you didn't intend for that to happen, you may have incorrectly marked the period of your ride.";
	echo '
		<script>
		jQuery(function($) {
			$("<div>'.$str.'</div>").dialog({
				title: "Important Notice: Ride spanning two dates",
				width: 500,
				modal: true,
				buttons: {
					"Confirm": function() { $(this).dialog("close"); },
					"Cancel And Return to Plan Ride": function() { window.location.href = "/plan_ride.php"; }
				}
			});
		});
		</script>
		';
}

function display_approaching_annual_fee_popup() {
	$sql = "select DATEDIFF(DATE_ADD(AnnualFeePaymentDate, Interval 1 year),now()) from rider where DATEDIFF(DATE_ADD(AnnualFeePaymentDate, Interval 1 year),now()) < 45 and DATEDIFF(DATE_ADD(AnnualFeePaymentDate, Interval 1 year),now()) > 0 and UserID = ".get_affected_user_id();
	$r = mysql_query($sql);
	if(mysql_num_rows($r) > 0) {
		$rs = mysql_fetch_assoc($r);
		$sql = "select * from ach_to_process where status = 1 and paytype = 'ANNUAL_FEE' and userid = ".get_affected_user_id()." and dts > now()";
		$r = mysql_query($sql);
		if(mysql_num_rows($r) == 0)	echo '
		<script>
		jQuery(function($) {
			if(Cookies.get("seen_annual_fee_popup") !== "true")
				$("<div>Annual Fee is due in '.$rs[0].' day'.($rs[0] > 1 ? 's' : '').'. Pay now?</div>").dialog({
					title: "Annual Fee Due Soon",
					width: 500,
					modal: true,
					buttons: {
						"Yes": function() { 
							window.open( "/make_payment.php", "_blank" ); 
							$(this).dialog("close"); 
							Cookies.set("seen_annual_fee_popup", "true", { expires: 7 });
						},
						"Not Right Now": function() { 
							$(this).dialog("close"); 
							Cookies.set("seen_annual_fee_popup", "true", { expires: 7 });
						}
					}
				});
			});
		</script>
		';		
	}
}
?>