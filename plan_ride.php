<?php

	include_once 'include/user.php';
	redirect_if_not_logged_in();
    
    require_once('include/destinations.php');
    require_once('include/rider.php');
    require_once('include/date_time.php');
    require_once('include/link.php');
    require_once('include/franchise.php');
    require_once('include/care_facility.php');
    require_once('include/custom_ride_transition.php');
    require_once('include/scheduling_lockout.php');

    session_start();

    $rider_info = get_user_rider_info( get_affected_user_id() );
    $annual_fee_up_to_date = rider_annual_fee_is_current(get_affected_user_id());
    if (!$annual_fee_up_to_date &&
        $cf_id = get_first_user_care_facility( get_affected_user_id() )) {
        $annual_fee_up_to_date = TRUE;
        //$annual_fee_up_to_date = care_facility_annual_fee_is_current(get_affected_user_id());
        // Is CF annual fee date actually stored somewhere?
    }
    $franchise_id = get_current_user_franchise();
    set_franchise_timezone($franchise_id);
	
  //if(count($_POST) > 0) print_r($_POST);
	   
		if(isset($_SESSION['COPY_PATH_CACHED_SCHEDULE']))
			$_SESSION['CACHED_SCHEDULE'] = $_SESSION['COPY_PATH_CACHED_SCHEDULE'];
		
    if (isset($_REQUEST['Reset'])) {
        unset($_SESSION['CACHED_SCHEDULE']);
        unset($_SESSION['COPY_PATH_CACHED_SCHEDULE']);
    } elseif (isset($_POST) && count($_POST) && isset($_POST['Submit'])) {
        //echo '<div style="align: left"><pre>' . var_export($_POST, TRUE) . '</pre>';
        $all_valid = TRUE;
        // POST looks like:
        // TravelMonth / TravelDay (autodetermine year)
        // Location array (index is int, key is dest ID)
        //              (private destinations have negative ID)
        //              (verify ownership of private destinations, verify public are really public)
        //
        // Time Types:
        // ArrivalTimeType array
        //      NotConcern or ArriveAt
        // DepartureTimeType array
        //      NotConcern, LeaveAt, or StayMinutes

        // For each link:
        //      For each destination, if ArrivalTimeType is ArriveAt:
        //          hour['Arrive'] array (index is int, corresponds to location index)
        //          minute['Arrive'] array (index is int, corresponds to location index)
        //          AM_PM['Arrive'] array (index is int, corresponds to location index)
        //      For each starting point, if DepartureTimeType is LeaveAt 
        //          hour['Depart'] array (index is int, corresponds to location index)
        //          minute['Depart'] array (index is int, corresponds to location index)
        //          AM_PM['Depart'] array (index is int, corresponds to location index)
        //      For each starting point, if DepartureTimeType is StayMinutes
        //          LocationTime array (index is int, corresponds to location index), minutes

        // Admins can set any destinations and (future) times
        //          But admins can only set destinations that exist.

        $error_string = array();
        $month = $_POST['TravelMonth'];
        $day = $_POST['TravelDay'];
        $year = $_POST['TravelYear'];

        
        if(isset($_POST["TravelDate"])) {
        	list($month,$day,$year) = explode('/',$_POST["TravelDate"]);
        }
       
     	$releasedtodriverholder=$_GET['ReleasedD'];

        // Verify travel date
        if (!date_is_schedulable($year, $month, $day, 0, 0) &&
            (!current_user_has_role(1, 'FullAdmin') && !current_user_has_role($franchise_id, 'Franchisee'))) {
            $all_valid = FALSE;
            $error_string[] = "Unschedulable date.";
            rc_log(PEAR_LOG_ERR, "Unschedulable date.");
        }
function null_check($test){
	if($test === null)
		return null;
	return $test;
}
function clean_POST(){
	$index = array();
	foreach(array_keys($_POST['Location']) as $L){
		$index[] = $L;
	}
	$new_post = $_POST;
	
	$new_post['Location'] = null;
	$new_post['LocationText'] = null;
	$new_post['LocationType'] = null;
	$new_post['destination_selector'] = null;
	$new_post['DestinationSelectorValue'] = null;
	$new_post['hour']['Depart'] = null;
	$new_post['hour']['Arrive'] = null;
	$new_post['minute']['Depart'] = null;
	$new_post['minute']['Arrive'] = null;
	$new_post['AM_PM']['Depart'] = null;
	$new_post['AM_PM']['Arrive'] = null;
	$new_post['NumberOfRiders'] = null;
	$new_post['ArrivalTimeType'] = null;
	$new_post['LocationTime'] = null;
	$new_post['riders'] = null;
	$vars = array('Location','LocationText','LocationType','destination_selector','DestinationSelectorValue','NumberOfRiders','ArrivalTimeType','LocationTime','riders');
	for($i = 0; $i < count($index); $i++){
		foreach($vars as $v){
			if($_POST[$v][$index[$i]] !== null)
				$new_post[$v][$i] = $_POST[$v][$index[$i]];
		}
		foreach(array('hour','minute','AM_PM') as $t){
			foreach(array('Depart','Arrive') as $w){
				if($_POST[$t][$w][$index[$i]] !== null)
					$new_post[$t][$w][$i] = $_POST[$t][$w][$index[$i]];
			}
		}

		
	}
	$_POST = $new_post;
}
function get_links_from_post() {
    // A link has an origin, a destination, and optional origin/destination times

    $links = array();

    if (!is_array($_POST['Location'])) {
        return $links;
    }
    
    for ($index = 0; $index < count($_POST['Location']) - 1; $index++) {
        if (isset($_POST['Location'][$index + 1])) {
            $curr_link = array();
            $curr_link['from'] = $_POST['Location'][$index];
            $curr_link['to'] = $_POST['Location'][$index + 1];
			$curr_link['ridercount'] = $_POST['NumberOfRiders'][$index + 1];
			$curr_link['Note'] = $_POST['TransitionNote'][$index + 1];
			$curr_link['FlexFlag'] = $_POST['TransitionFlexTimeFlag'][$index+1];
			$curr_link['OnDemandFlag'] = $_POST['OnDemandFlag'][$index+1];
			$curr_link['riders'] = $_POST['riders'][$index + 1];
			$curr_link['DepartureTimeConfirmed'] = isset($_POST['DepartureTimeConfirmed'][$index ]) ? TRUE : FALSE;
			$curr_link['ArrivalTimeConfirmed'] = isset($_POST['ArrivalTimeConfirmed'][$index+1 ]) ? TRUE : FALSE;
			$curr_link['PrePadding'] = $_POST['PrePadding'][$index];
			$curr_link['PostPadding'] = $_POST['PostPadding'][$index + 1];
			$curr_link['Last_Changed_By'] = $_POST['Last_Changed_By'];
			$curr_link['Last_Changed_Date'] = $_POST['Last_Changed_Date'];
			$curr_link['Created_By'] = $_POST['Created_By'];
			$curr_link['Created_Date'] = $_POST['Created_Date'];
			
			
            if ($_POST['ArrivalTimeType'][$index+1] == 'ArriveAt') {
                $hour = $_POST['hour']['Arrive'][$index+1];
                $curr_link['to_time']['hour'] = ($_POST['AM_PM']['Arrive'][$index+1] == 'PM' && $hour != 12) ?
                    $hour + 12 : $hour;
                $curr_link['to_time']['minute'] = $_POST['minute']['Arrive'][$index+1];
            }

            if ($_POST['DepartureTimeType'][$index] == 'LeaveAt') {
                $hour = $_POST['hour']['Depart'][$index];
                $curr_link['from_time']['hour'] = ($_POST['AM_PM']['Depart'][$index] == 'PM' && $hour != 12) ?
                    $hour + 12 : $hour;
                $curr_link['from_time']['minute'] = $_POST['minute']['Depart'][$index];
            } elseif ($_POST['DepartureTimeType'][$index] == 'StayMinutes') {
                $curr_link['from_stay'] = $_POST['LocationTime'][$index];
            }
        }

        $links[] = $curr_link;
    }
    return $links;
}

function preliminary_time_check($link_array, $date = NULL) {
    // Each stop does not need a time, but if there are times, they need to be increasing.
    // Departure time for a link needs to be earlier than arrival time
    // Arrival time at a destination needs to be earlier than departure time from there
    $at_least_one_time_set = FALSE;
    $all_times_increasing = TRUE;
    $last_known_time = 0;
    $errors = array();


    if (!is_array($link_array)) {
        $errors[] = "Server error.";
        return FALSE;
    }
	//echo "<pre>";
    //print_r($link_array);
	//echo "</pre>";
    foreach ($link_array as $index => $link) {
        // Time variables:
        //      From:  from_stay, from_time
        //      To:    to_time
        if ($link['from_stay']) {
            $last_known_time += $link['from_stay'];
        }
		if($link['from_time'] && $date != NULL){
			$time = "$date {$link['from_time']['hour']}:{$link['from_time']['minute']}:00";
			//echo "check_scheduleable_date ".date('Y-n-j G:i:s')." | ".$time."<BR>";
			$result = check_scheduable_date(date('Y-n-j G:i:s'),$time);
			//print_r($result);
			//print_r($_SESSION);
			if(!$result['RESULT'] && !$_SESSION['lockoutAllow']){
				$_SESSION['lockoutError'] = true;
				
				$errors[] = "Date and time of link $index is at an non-schedulable time";
				$all_times_increasing = FALSE;
			}
		}
		if($link['to_time'] && ($link['from_time'] || $link['from_stay'])){
			$errors[] = "Stop $index departure and stop " . ($index + 1) . " arrival are both set. Please only choose one to allow us to calculate travel time between them.";
		}
		
		if($link['to_time'] && $date != NULL){
			$time = "$date {$link['to_time']['hour']}:{$link['to_time']['minute']}:00";
			//echo "check_scheduleable_date ".date('Y-n-j G:i:s')." | ".$time."<BR>";
			$result = check_scheduable_date(date('Y-n-j G:i:s'),$time);
			//print_r($result);
			//print_r($_SESSION);
			if(!$result['RESULT'] && !$_SESSION['lockoutAllow']){
				$_SESSION['lockoutError'] = true;
				
				$errors[] = "Date and time of link $index is at an non-schedulable time";
				$all_times_increasing = FALSE;
			}
		}
        // Possibilities for absolute time:
            // Neither from nor to time set
            // From time set only
            // To time set only
            // Both from/to set
        if ($link['from_time'] && $link['to_time']) {
            $at_least_one_time_set = TRUE;

            $to_time = ($link['to_time']['hour'] * 60) + $link['to_time']['minute'];
            $from_time = ($link['from_time']['hour'] * 60) + $link['from_time']['minute'];

            if ($to_time <= $from_time || $from_time < $last_known_time) {
                $all_times_increasing = FALSE;
                $errors[] = "Time in link $index runs backwards.";
            }

            $last_known_time = $to_time;
        } elseif ($link['from_time']) {
            $at_least_one_time_set = TRUE;

            $from_time = ($link['from_time']['hour'] * 60) + $link['from_time']['minute'];
            if ($from_time < $last_known_time) {
                $errors[] = "FROM TIME IS $from_time, LAST KNOWN is $last_known_time";
                $all_times_increasing = FALSE;
                $errors[] = "Departure time from stop $index is too early.";
            }

            $last_known_time = $from_time;
        } elseif ($link['to_time']) {
            $at_least_one_time_set = TRUE;

            $to_time = ($link['to_time']['hour'] * 60) + $link['to_time']['minute'];
            if ($to_time <= $last_known_time) {
                $all_times_increasing = FALSE;
                $err_index = $index + 1;
                $errors[] = "Arrival time at stop $err_index is too early.";
            }
            $last_known_time = $to_time;
        } else {
            // If both are set, add a 5-minute pad just to be safe...
            $last_known_time += 5;
        }
    }

    if (!$at_least_one_time_set) {
        $errors[] = 'At least one arrival or departure time must be set.';
    }
    return array($all_times_increasing, $at_least_one_time_set, $errors);
}


function valid_destination($dest_id) {
	if($_POST['Submit'] == 'Finish Custom Transition'){
		$dest_record = get_destination(-$dest_id);
        if ($dest_record === FALSE)
            return FALSE;
        return TRUE;
	}
    static $user_dest_hash = 0;
    if ($user_dest_hash === 0) {
        global $rider_info;
        $user_destinations = get_rider_destinations($rider_info['UserID']);
        $user_dest_hash = array_flip(array_map( create_function('$d', 'return $d[\'DestinationID\'];'),
                                                $user_destinations ) );
    }
    if ($dest_id < 0) {
        // Private destination
        if (!array_key_exists(-$dest_id, $user_dest_hash)) {
            return FALSE;
        }
    } else {
        $dest_record = get_destination($dest_id);
        if ($dest_record === FALSE) {
            return FALSE;
            
        }
    }
    return TRUE;
}

// Modifies $link_array by abs-ing negative (private) dests
function destinations_valid(&$link_array) {
    // TODO:  Not from POST? 
    $all_valid = TRUE;
    $errors = array();
    $prev_dest_id = -999;

    if (!is_array($link_array)) {
        return FALSE;
    }

    foreach ($link_array as $index => $link) {
        $from_id = $link['from'];
        $to_id = $link['to'];
        if ($from_id != $prev_dest_id) {
            if (!valid_destination($from_id)) {
                $all_valid = FALSE;
                $errors[] = "Stop $index is not valid for you.";
            }
        }

        if (!valid_destination($to_id)) {
            $all_valid = FALSE;
            $err_index = $index + 1;
            $errors[] = "Stop $err_index is not valid for you.";
        }

        $link_array[$index]['from'] = abs($from_id);
        $link_array[$index]['to'] = abs($to_id);

        $prev_dest_id = $to_id;
    }
    return array($all_valid, $errors);
}
		if($_POST['lockoutOverride'] == true && (current_user_has_role( 1, 'FullAdmin') || current_user_has_role($franchise_id, 'Franchisee'))){
			$_SESSION['lockoutAllow'] = TRUE;
		}
        clean_POST();
        $links = get_links_from_post();

    //echo "All VALID:  <PRE>" . var_export($all_valid, TRUE) . "</PRE>";
        list($all_increasing, $known_time, $errors) = preliminary_time_check($links, "$year-$month-$day");
        if (!$all_increasing || !$known_time) {
    		//echo "All increasing:  <PRE>" . var_export($all_increasing, TRUE) . "</PRE>";
    		//echo "known_time:  <PRE>" . var_export($known_time, TRUE) . "</PRE>";
    
            $all_valid = FALSE;
            $error_string = array_merge($error_string, $errors);
    		//echo "errors found:  <PRE>" . var_export($errors, TRUE) . "</PRE>";
    		//echo "strs:  <PRE>" . var_export($error_string, TRUE) . "</PRE>";
            unset($errors);
        }

    //echo "All VALID:  <PRE>" . var_export($all_valid, TRUE) . "</PRE>";
        list($destinations_valid, $errors) = destinations_valid($links);
        if (!$destinations_valid) {
            $all_valid = FALSE;
            $error_string = array_merge($error_string, $errors);
            unset($errors);
        }

        
    //echo "LINKS FOUND:  <PRE>" . var_export($links, TRUE) . "</PRE>";
    //echo "All VALID:  <PRE>" . var_export($all_valid, TRUE) . "</PRE>";
    //echo "Errors:  <PRE>" . var_export($error_string, TRUE) . "</PRE>";

        session_start();
        //echo '<pre>';
        //print_r($_POST);
        //echo '</pre>';
        $_SESSION['CACHED_SCHEDULE'] = $_POST;
        $_SESSION['CACHED_SCHEDULE']['Affected_User'] = get_affected_user_id();
        if ($all_valid) {
     	
            // Build up a nice data structure.  Pass it along.
            $requested_rides = array( 'year' => $year,
                                      'month' => $month,
                                      'day' => $day,
                                      'links' => $links
                                      );
			if(isset($_POST['AffectedLinks'])){
				$requested_rides['AffectedLinks'] = explode(",", $_POST['AffectedLinks']);
			}
			$requested_rides['Type'] = $_POST['Submit'];
			$requested_rides['RiderID'] = get_affected_user_id();
			$requested_rides['DriverID'] = $_POST['DriverID'];
				
            $request = serialize($requested_rides);
            $hash = sha1($request . 'A little extra verification');
            $request = urlencode($request);
            $_SESSION['CACHED_SCHEDULE'] = $_POST;
            $_SESSION['CACHED_SCHEDULE']['Affected_User'] = get_affected_user_id();
            unset($_SESSION['lockoutAllow']);
            //header("Location: confirm_ride_new.php?ver=$hash&rides=$request&ReleasedD=$releasedtodriverholder");
            echo "<html>";
            echo "<body onLoad=\"document.getElementById('myForm').submit();\">";
            echo "<form id=myForm method=POST action=confirm_ride_new.php>";
            echo "<input type=hidden name=ver value=\"$hash\">";
            echo "<input type=hidden name=rides value=\"$request\">";
            echo "<input type=hidden name=ReleaseD value=\"$releasedtodriverholder\">";
            echo "</form>";
            echo "</body></html>";
            exit;
        }

    }

$href = '';

    session_start();
    if ( (strpos($_SERVER['HTTP_REFERER'], 'plan_ride.php') == FALSE &&
         strpos($_SERVER['HTTP_REFERER'], 'confirm_ride') == FALSE) || 
       ( isset($_SESSION['CACHED_SCHEDULE']['REFERER_TIME']) && 
       $_SESSION['CACHED_SCHEDULE']['REFERER_TIME'] + 120 <= time() ) || 
       (isset($_SESSION['CACHED_SCHEDULE']['Affected_User']) && 
       $_SESSION['CACHED_SCHEDULE']['Affected_User'] != get_affected_user_id()) ) {
        unset($_SESSION['CACHED_SCHEDULE']);
    }
    if(isset($_REQUEST['CustomTransDriver']) || $_SESSION['CACHED_SCHEDULE']['Submit'] == 'Finish Custom Transition'){
			if(!isset($_REQUEST['CustomTransDriver']) && $_SESSION['CACHED_SCHEDULE']){
				$_REQUEST['CustomTransLinks'] = $_SESSION['CACHED_SCHEDULE']['AffectedLinks'];
				$_REQUEST['CustomTransDriver'] = $_SESSION['CACHED_SCHEDULE']['DriverID'];
				
		}

    	$link_array = explode(",", $_REQUEST['CustomTransLinks']);
    	$valid_links = confirm_valid_driver_rides($_REQUEST['CustomTransDriver'],$link_array);
    	if($valid_links['Result']){

    		$CustomTransition = true;
    		$date = get_date($valid_links['Date']);
    		$month = $date['Month'];
        	$day = $date['Day'];
        	$year = $date['Year'];
        	$DriverID = $_REQUEST['CustomTransDriver'];
        	if(isset($_POST['Links']))
        		$cached_schedule = $_POST;
			else if(isset($_SESSION['CACHED_SCHEDULE']))
				$cached_schedule = $_SESSION['CACHED_SCHEDULE'];
        	$cached_schedule['AffectedLinks'] =  $_REQUEST['CustomTransLinks'];
        	$cached_schedule['Submit'] = 'Finish Custom Transition';
    	} else {
    		echo "The Links Provided Are Not Valid."; die();
    	}
    	
    }else if(isset($_REQUEST['edit'])){
    	$cached_schedule = get_links_edit_array($_REQUEST['edit']);
    	
    	if(count($_POST) > 0) $cached_schedule = $_POST;
		
		$month = $cached_schedule['TravelMonth'];
        $day = $cached_schedule['TravelDay'];
        $year = $cached_schedule['TravelYear'];
    } else if(isset($_REQUEST['goto'])){
        $cached_schedule = create_go_to_array( $_REQUEST['goto'] );
        
        $month = $cached_schedule['TravelMonth'];
        $day = $cached_schedule['TravelDay'];
        $year = $cached_schedule['TravelYear'];
    } else if(isset($_REQUEST['comefrom'])){
        $cached_schedule = create_come_from_array( $_REQUEST['comefrom'] );
        
        $month = $cached_schedule['TravelMonth'];
        $day = $cached_schedule['TravelDay'];
        $year = $cached_schedule['TravelYear'];
    } else {
        $cached_schedule = $_SESSION['CACHED_SCHEDULE'];
        $month = $cached_schedule['TravelMonth'];
        $day = $cached_schedule['TravelDay'];
        $year = $cached_schedule['TravelYear'];
    }
	//echo '<pre>';
	//print_r($cached_schedule);
	//echo '</pre>';
    $rider_preferences = get_rider_preferences( $rider_info['UserID'] );


    global $ADDITIONAL_RC_JAVASCRIPT;
    if (!is_array($ADDITIONAL_RC_JAVASCRIPT)) {
        $ADDITIONAL_RC_JAVASCRIPT = array();
    }
    $ADDITIONAL_RC_JAVASCRIPT[] = 'datepicker.js';

	include_once('include/header.php');
	include_once('include/public_destination_selector.php');
?>
<style>
	.list {
		padding-left:15px;
		
	}
	.border {
		border-bottom: 1px solid #000;
		padding:3px;
		height:20px;
	}
	.destination {
		float:left;
		text-decoration:none;
		margin:0px 30px 0px 30px;
		width:200px;
	}
	.date{
		float:left;
		text-decoration:none;
		margin:0px 30px 0px 30px;
		width:150px;
	}
	.location {
		float:left;
		text-decoration:none;
		margin:0px 30px 0px 30px;
		width:200px;
		height:inherit;
		overflow:hidden;
	}
    .error_text {
        color: red;
    }
    
  select[name="destination_selector[0]"],
  select[name="destination_selector[1]"] {
  	max-width: 450px;
  }
</style>
<script>

	
</script>
<?php echo get_public_destination_selector_js(); ?>
 <h2 style="text-align:center;">Plan a Ride</h2>
<?php
    if (!$annual_fee_up_to_date && !$CustomTransition) {
        echo "<p>Our records indicate that your annual fee is not up-to-date.  Your annual fee " .
             "must be paid to use our service.</p>";
        if (!current_user_has_role(1, 'FullAdmin') && !current_user_has_role($franchise_id, 'Franchisee')) {
            include_once 'include/footer.php';
            exit;
        } else {
            echo "<p>As a full admin, you may still schedule a ride.</p>";
		
        }
    }
?>
<?php 
    if (isset($to_destination)) {  ?>
<div style="float: right; width: 100%; min-height: 30px;">
    Going to:  <?php echo $to_destination['Name']; ?>
</div>
<?php 
    }  ?>
<?php 
	
	
	if (isset($error_string) && is_array($error_string) && count($error_string) > 0) {
    foreach ($error_string as $err) {
        echo "<span class=\"error_text\">$err</span><br />\n";
    }
    echo "<br />\n";
} ?>
<form method="post" action="/plan_ride.php" name="plan_form">
<?php 
	if($_SESSION['lockoutError'] == true && (current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise_id, 'Franchisee'))){
		unset($_SESSION['lockoutError']);
		echo "<div class=\"reminder\">
			As an admin you can override the lockout. <input type=\"button\" value=\"Override\" onclick=\"$('lockoutOverrideValue').value = 'true'; $('submitRideForm').click();\">
			<input type=\"hidden\" id=\"lockoutOverrideValue\" name=\"lockoutOverride\" value=\"false\">

		</div>";
		?>
		<input type="hidden" name="releasedtodriverholder" id="releasedtodriverholder" value="<?php echo $releasedtodriverholder; ?>" />
		<?php
	}
	//print_r($cached_schedule);
    if(isset($cached_schedule['REFERER_TIME'])){?>
        <div class="reminder">You are currently working on a ride that <b>was <?php echo isset($_SESSION['COPY_PATH_CACHED_SCHEDULE']) ? '' : 'not'; ?></b> saved from <?php echo date('M j, ', $cached_schedule['REFERER_TIME'])?> at 
        <?php echo date('g:i a', $cached_schedule['REFERER_TIME'])?> for a ride travelling on <?php echo $cached_schedule['TravelMonth'].'/'.$cached_schedule['TravelDay'].'/'.$cached_schedule['TravelYear']; ?>.
        <?php if(isset($cached_schedule['AffectedLinks'])) echo ' Editing this ride will replace links ' . $cached_schedule['AffectedLinks'];?> 
        <br>To clear this and start a new ride 
        <input type="submit" value="Click Here" name ="Reset"></div>
<?php } 

	if($CustomTransition){
		echo get_basic_link_table($link_array);
?>
	<input type="hidden" name="DriverID" value="<?php echo $DriverID; ?>">


	<?php }	?>
	<input type="hidden" name="Last_Changed_By" value="<?php echo @$cached_schedule['Last_Changed_By']; ?>">
	<input type="hidden" name="Last_Changed_Date" value="<?php echo @$cached_schedule['Last_Changed_Date']; ?>">
	<input type="hidden" name="Created_By" value="<?php echo @$cached_schedule['Created_By'] != '' && @$cached_schedule['Created_By'] != 0 ? $cached_schedule['Created_By'] : get_current_user_id(); ?>">
	<input type="hidden" name="Created_Date" value="<?php echo @$cached_schedule['Created_Date'] != '' ? $cached_schedule['Created_Date'] : date("Y-m-d H:i:s"); ?>">
	<input  type="hidden" name="TravelMonth" value="<?php echo $month; ?>">
	<input  type="hidden" name="TravelDay" value="<?php echo $day; ?>">
	<input  type="hidden" name="TravelYear" value="<?php echo $year; ?>">
<table id="travel_date">
    <tr><td nowrap="nowrap">What date do you want to travel?</td><?php 
    $earliest_ride = get_next_user_schedulable_link_time( $rider_info['UserID'] );
    $selected_month = isset($month) && $month != '' ? $month : $earliest_ride['Month'];
    $selected_day = isset($day)  && $day != '' ? $day : $earliest_ride['Day'];
    $selected_year = isset($year) && $year != '' ? $year : $earliest_ride['Year'];
		 ?>
		 <td><input type=text size=10 name=TravelDate value="<?php echo join('/',array($selected_month,$selected_day,$selected_year)); ?>" class="jq_datepicker" id="TravelDate"></td>
		 <script>
		 jQuery(function($) {
		 	var s = $('#TravelDate').val().split('/');
	 		$('input[name="TravelMonth"]').val( s[0] );
	 		$('input[name="TravelDay"]').val( s[1] );
	 		$('input[name="TravelYear"]').val( s[2] );		 	
		 	$('#TravelDate').datepicker("option","onSelect",function(dtext,ob) {
		 		var s = dtext.split('/');
		 		$('input[name="TravelMonth"]').val( s[0] );
		 		$('input[name="TravelDay"]').val( s[1] );
		 		$('input[name="TravelYear"]').val( s[2] );
		 	});	
		 });	
		 	
		 </script>
		 <!--
        <td><?php
            
            print_month_select( 'TravelMonth', 'TravelMonth', $selected_month, $CustomTransition); ?></td>
        <td><?php 
            
            print_day_select( 'TravelDay', 'TravelDay', $selected_day, $CustomTransition); ?></td>
        <td nowrap="nowrap"><?php
            
            if (current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise_id, 'Franchisee')) {
                print_year_select( date('Y') - 3, 5, 'TravelYear', 'TravelYear', $selected_year, $CustomTransition );
            } else {
                print_year_select( date('Y'), 2, 'TravelYear', 'TravelYear', $selected_year, $CustomTransition );
            }
        ?>
      <script type="text/javascript">

        var opts = {                   
        	<?php
        		if(!current_user_has_role($franchise_id, 'FullAdmin')) {
        			$earliest_ride['Day'] = str_pad($earliest_ride['Day'],2,'0',STR_PAD_LEFT);
        			echo "rangeLow: \"{$earliest_ride['Year']}{$earliest_ride['Month']}{$earliest_ride['Day']}\",";
        		}
        	?>      
                formElements:{"TravelYear":"Y","TravelMonth":"n","TravelDay":"j"},
                statusFormat:"l-cc-sp-d-sp-F-sp-Y"       
                };           
        datePickerController.createDatePicker(opts);


      </script>
      -->
      <td>
      <input id="PrevDay" type="hidden" value="Previous Day">
      <input id="NextDay" type="hidden" value="Next Day">
              </td>

    </tr>
    <tr>
    	<td colspan=2>
      <?php
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
        <?php echo $cf_string; ?>Available Balance = <?php echo $balance_string; ?><br>
        <?php
					$sql = "select * from ach_to_process where userid = ".get_affected_user_id()." and dts > now()";
					$r = mysql_query($sql);
					$ach = 0;
					if(mysql_num_rows($r) > 0) {
						$rs = mysql_fetch_array($r);
						$ach = $rs["amount"];
						echo "We will process a payment of <b>$".$rs["amount"]." on ".date("l, n/d/Y",strtotime($rs["dts"]))."</b>.<BR>";
					}         
        ?>
		</td>
	</tr>
</table>
<br />
<div style="float: left; padding-left: 35em; padding-right: 12em;">
	<input type="submit" class="AddNewLocationRow" name="AddNewLocationRow" value="Add Stop" />
</div>
<br />
<br>
<table border="1" id="route_table">
    <tr><th>&nbsp;</th><th>Riders</th><th>Location</th><th>"Arrive At Location" Time</th><th>"Depart From Location" Time</th>
    	<?php if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise_id, 'Franchisee')) 
    		echo '<th>Note</th><th>On Demand Flag</th>'; 
    	?>
    </tr>
    <tr id="pickup_row"><td>Pickup Location</td>
    	<td>
        </td>
        <td><?php echo get_destination_cell(0) ?></td>
        <?php echo get_time_cells(0) ?>
    </tr>
<?php
    $last = 1;
    if (isset($cached_schedule['Location'])) {
        foreach ($cached_schedule['Location'] as $idx => $ignore) {
            if (is_numeric($idx) && $idx > $last) {
                $last = $idx;
            }
        }
        
    }
    for ($row_idx = 1; $row_idx <= $last; $row_idx++) { 
    ?>

    <tr id="stop[<?php echo $row_idx ?>]" stop_row="<?php echo $row_idx ?>"><td>(Stop <?php echo $row_idx ?>) <button type="button" onclick="remove_row(<?php echo $row_idx; ?>)">remove</button></td>
    <td style="vertical-align:top;">
    	<select style="margin-top:-20px;" name="NumberOfRiders[<?php echo $row_idx; ?>]" id="NumberOfRiders[<?php echo $row_idx; ?>]">
        	<?php
        		for($i = 1; $i <= 4; $i++)
        			echo "<option value=$i"
        				.($cached_schedule['NumberOfRiders'][$row_idx] == $i ? " selected" : "")
        				.">$i</option>";
            ?></select>
    </td>
    	<td>
            <?php echo get_destination_cell($row_idx) ?>
        </td><?php echo get_time_cells($row_idx) ?>
    </tr>
<?php } ?>
</table>
<br />

<?php
if(isset($cached_schedule['AffectedLinks'])){?>
<input type="hidden" name="AffectedLinks" value="<?php echo is_array($cached_schedule['AffectedLinks']) ? implode(',', $cached_schedule['AffectedLinks']) : $cached_schedule['AffectedLinks']; ?>">
<?php } ?>


<div style="float: left; padding-left: 35em; padding-right: 12em; width:40%;">
<div style="float:right; clear:both;">
<input type="hidden" name="releasedtodriverholder" id="releasedtodriverholder" value="<?php echo $releasedtodriverholder; ?>" />
<input type="submit" id="submitRideForm" name="Submit" value="<?php if(isset($cached_schedule['Submit']) && $cached_schedule['Submit'] == 'Edit Ride' || $cached_schedule['Submit'] == 'Apply Changes') echo 'Apply Changes'; else if(isset($cached_schedule['Submit']) && $cached_schedule['Submit'] == 'Finish Custom Transition') echo 'Finish Custom Transition'; else echo 'Schedule Ride'; ?>" />
</div>

<input type="submit" class="AddNewLocationRow" name="AddNewLocationRow" value="Add Stop" />
<br />
<br />
	<input type="button" value="Create Destination" onclick="window.open('add_destination.php','AddDestination', 'menubar=no,width=380,height=370,toolbar=no');" />
<br />
<br />
<input type="submit" name="Reset" value="Reset Ride" />
</div>

</form>
<script type="text/javascript" src="js/plan_ride.js"></script>
<script type="text/javascript">
function addRadioButtonChangeEvent() {
	jQuery('.radio_selector_span select').on('change',function() {
		jQuery(this).parent('span').parent('span').find('input[type="radio"]').prop('checked',true);
	});
}
jQuery(function($) {

	addRadioButtonChangeEvent();
});	
	
    var index_placeholder = '<?php 
        $tsi_placeholder = 'ZZZQQQZZZ';
        echo $tsi_placeholder;  ?>';

    var time_selector_template = '<?php
        $no_newlines = str_replace( array("\n", "\r", "'"), array('', '', "\'"), get_time_cells($tsi_placeholder));
        echo $no_newlines;
        ?>';

    var user_destination_template = '<?php
        $no_newlines = str_replace( array("\n","\r", "'"), array('','', "\'"), get_destination_cell($tsi_placeholder));
        echo $no_newlines;
        ?>';

    function add_location_row() {
        var last_row_index = get_last_row_index();
        var new_row_index = (last_row_index*1) + 1;

        var new_row = new Element('tr', { id: 'stop[' + new_row_index + ']'});

        var replace_regex = new RegExp(index_placeholder, 'g');
    
        new_row.setProperty('stop_row', new_row_index);
        new_row.inject('stop[' + last_row_index + ']', 'after');
        new_row.set('html', 
                            '<td>(Stop ' + new_row_index + 
                            ') <button type="button" onclick="remove_row(' + new_row_index + ')">remove</button></td><td style="vertical-align:top;">'
                            		+'<select style="margin-top:-20px;" name="NumberOfRiders[' + new_row_index+ ']">'
                            		+'<option value="1" <?php echo $cached_schedule['NumberOfRiders']==1?" selected":""; ?>>1</option>'
                            		+'<option value="2" <?php echo $cached_schedule['NumberOfRiders']==2?" selected":""; ?>>2</option>'
                            		+'<option value="3" <?php echo $cached_schedule['NumberOfRiders']==3?" selected":""; ?>>3</option>'
                            		+'</select></td><td>' + 
                            user_destination_template.replace(replace_regex, new_row_index) +
                            '</td>' +
                            time_selector_template.replace(replace_regex, new_row_index) );

        //$('DepartCell[' + last_row_index + ']').setStyle('display', 'table-cell');
        $('DepartCell[' + last_row_index + ']').setStyle('visibility', '');
        $('DepartCell[' + new_row_index + ']').setStyle('visibility', 'hidden');

        // Now decorate the new radio buttons
        decorate_radio_buttons();
        decorate_location_selector(new_row_index);
        //decorate_public_destinations(new_row_index);
		create_public_destination_selector(new_row_index);
		decorate_stay_time_input(new_row_index);
		addRadioButtonChangeEvent();
    }
    window.addEvent('domready', function(){
    	load_public_destination_data(<?php echo $franchise_id; ?>);
    });
	
	
	function checkRadios() {
  $bad = false;
  for(i=1; i<document.forms.plan_form.elements.length; i++) {
    var element = document.forms.plan_form.elements[i];
	var frm = document.forms['plan_form'].elements;
	if ((element.name.substr(0,15)=='ArrivalTimeType') && (element.checked) && (element.value=='ArriveAt')) {
	  //alert(element.name+': '+element.value);
	  var index = element.name.substring(16,element.name.length-1)
	  //alert(index);
	  //alert(document.forms.plan_form.elements['DepartureTimeType['+(index-1)+']'][1].value);
	  if (document.forms.plan_form.elements['DepartureTimeType['+(index-1)+']'][1].checked) {
	    alert('Please be aware that you have set a departure time and arrival time, you may be creating invalid times. Row '+(index)+' and Row '+(parseInt(index)+1));
	  }
	}
  }
}
</script>

<?php
    
    include_once 'include/footer.php';


function OLD_DEPRECATED_get_time_selector($type, $num, $hour = FALSE, $minute = FALSE, $ampm = FALSE) {
    $selected_hour = ($hour == FALSE) ? 9 : $hour;
    $selected_min = ($hour == FALSE) ? 0 : $minute;
    $am_selected = ($ampm == 'AM') ? 'selected="selected" ' : '';
    $pm_selected = ($ampm == 'PM') ? 'selected="selected" ' : '';

    $ret = "<select name=\"hour[$type][$num]\">";
    for ($hr = 1; $hr <= 12; $hr++) {
        $ret .= "<option value=\"$hr\"" . 
                (($hr == $selected_hour) ? ' selected="selected"' : '') . ">$hr</option>";
    }
    $ret .= "</select>:<select name=\"minute[$type][$num]\">";
    for ($min = 0; $min < 60; $min += 5) {
        $ret .= "<option value=\"$min\" " . 
                (($min == $selected_min) ? ' selected="selected"' : '') . '>' . sprintf('%02d', $min) . '</option>';
    }
    $ret .= '</select><select name="AM_PM[' . "$type][$num" . 
            ']"><option value="AM" ' .  $am_selected . '>AM</option><option value="PM" ' .
            $pm_selected . '>PM</option></select>';

    return $ret;
}


function date_is_schedulable($year, $month, $day, $hour, $minute, $reset = FALSE) {
    global $earliest_ride;
    if ($reset || !isset($earliest_ride)) {
        $earliest_ride = get_next_user_schedulable_link_time();
        $earliest = mktime($earliest_ride['Hour'], 0, 0,
                           $earliest_ride['Month'], $earliest_ride['Day'], $earliest_ride['Year']);
    }

    $timestamp = mktime($hour, $minute, 0, $month, $day, $year);
	
	if ($timestamp < $earliest) {
        return FALSE;
    }

    // TODO:  The logic in this is not complete, most likely.
    return TRUE;
}


function get_destination_cell( $idx ) {
    global $cached_schedule;
    global $rider_info;
    global $href;
    global $franchise_id;


    if ($cached_schedule['LocationType'][$idx] == 'Favorite' || 
        !isset($cached_schedule['LocationType'][$idx])) {
            $favorite_checked = 'checked="checked" ';
			
    } else {
            $favorite_checked = '';
			$favorite_style = 'style="display:none;" ';
    }


    if ($cached_schedule['LocationType'][$idx] == 'Franchise') {
        $franchise_checked = 'checked="checked" ';
    } else {
        $franchise_checked = '';
		$franchise_style = 'style="display:none;" ';
    }
	global $CustomTransition;
    if ($idx == 0 && !$cached_schedule['Location'][$idx] && !$CustomTransition) {
        $location_name = 'Home';
        $cached_schedule['Location'][$idx] = -get_rider_default_home_destination($rider_info['UserID']);
    } else {
        $location_name = ($cached_schedule['LocationText'][$idx]) ?  $cached_schedule['LocationText'][$idx] :
                                                                     'Select a Destination...';
    }
	
	global $link_array;
    $rider_dest_select = get_rider_destination_selector($rider_info['UserID'], $idx, 
                                                        'Select a destination...', 
                                                        abs($cached_schedule['Location'][$idx]), NULL ,!$CustomTransition ? NULL : get_destinations_for_links($link_array)); 
	if($CustomTransition && $idx !== 0){
		$link_riders = "<br>Riders:<br> ";
		$users = get_links_rider_names($link_array);
		
		$riders = 0;
		foreach($users as $user){
			if(!isset($cached_schedule['riders'][$idx]) || in_array($user['UserID'], $cached_schedule['riders'][$idx])){
		    	$link_riders .= "<div>" . get_displayable_person_name_string($user) . "<input type=\"hidden\" name=\"riders[$idx][$riders]\" value=\"{$user['UserID']}\">[<a href=\"\"onclick=\"this.getParent().destroy(); return false;\">Remove</a>]</div>";
            	$riders++;
			}
        }
		
	}
	
    //$dest_selection_widget = get_destination_selection_widget($franchise_id, $href, "[$idx]"); // TODO:  better HREF 
    $dest_selection_widget = create_public_destination_selector($idx, $cached_schedule['Location'][$idx] );
	
$cell = <<<HTML
        <span id="LocationName[$idx]">$location_name</span><br />
        <input type="hidden" name="Location[$idx]" id="Location[$idx]" value="{$cached_schedule['Location'][$idx]}" />
        <input type="hidden" name="LocationText[$idx]" id="LocationText[$idx]" value="{$cached_schedule['LocationText'][$idx]}" />
        <input type="radio" id="LocationType[$idx][favorite]" name="LocationType[$idx]" value="Favorite" $favorite_checked/><span class="Location_Selector" onclick="$('LocationType[$idx][favorite]').checked = true; change_destination_selector(null, $('LocationType[$idx][favorite]'));">Use Saved Destination<br /></span>
HTML;
        if($CustomTransition)
        	$hidden_class = " Hidden";
$cell .= <<<HTML
        <input class="$hidden_class" type="radio" id="LocationType[$idx][franchise]" name="LocationType[$idx]" value="Franchise" $franchise_checked/><span id="PublicDestText"class="Location_Selector$hidden_class" onclick="$('LocationType[$idx][franchise]').checked = true; change_destination_selector(null, $('LocationType[$idx][franchise]'));">Use Public Destination<br /></span>
HTML;

		$cell .= $rider_dest_select;
		$cell .="<div id=\"DestinationWidget$idx\"$franchise_style>$dest_selection_widget</div>";
		$cell .= $link_riders;

    return $cell;
}

function get_time_cells($idx) {
    global $cached_schedule;
    //print_r($cached_schedule);
	global $franchise_id;
	global $rider_info;
	
	//print_r($rider_info);
    // TODO:  From/To
    $arrive_time_selector = get_time_selector('Arrive', $idx, $cached_schedule['hour']['Arrive'][$idx],
                                                              $cached_schedule['minute']['Arrive'][$idx],
                                                              $cached_schedule['AM_PM']['Arrive'][$idx], '');
    $depart_time_selector = get_time_selector('Depart', $idx, $cached_schedule['hour']['Depart'][$idx],
                                                              $cached_schedule['minute']['Depart'][$idx],
                                                              $cached_schedule['AM_PM']['Depart'][$idx], '');
    $depart_unconcern_checked = '';
    $depart_leave_at_checked = '';
    
    $arrival_unconcern_checked = '';
    $arrive_at_checked = '';

    switch ($cached_schedule['DepartureTimeType'][$idx]) {
        case 'LeaveAt':
            $depart_leave_at_checked = ' checked="checked" ';
            $stay_checked = '';
            break;
        case 'StayMinutes':
            $stay_checked = ' checked="checked" ';
            break;
        case 'NotConcern':
        		$depart_unconcern_checked = ' checked="checked" ';
        		$stay_checked = '';
        		break;
        default:
            $stay_checked = ' checked="checked" ';
           
    }

    switch ($cached_schedule['ArrivalTimeType'][$idx]) {
        case 'ArriveAt':
            $arrive_at_checked = ' checked="checked" ';
            break;
        case 'NotConcern':
        default:
            $arrival_unconcern_checked = ' checked="checked" ';
    }
	$radio_span_selector = '<span class=radio_selector_span>';
	$radio_span_selector_end = '</span>';
	$default_pre_padding = $rider_info["PrePadding"] + ($idx == 0 ? $rider_info["FirstPadding"] : 0);
	$default_post_padding = $rider_info["PostPadding"];
	
    if ($idx === 0) {
        if ($depart_unconcern_checked == $depart_leave_at_checked) {
            $depart_unconcern_checked = 'checked="checked" ';
        }
        if(current_user_has_role(1, "FullAdmin") || current_user_has_role($franchise_id, 'Franchisee')){
			
			
			$checkbox = '<br /><input type="checkbox" name="DepartureTimeConfirmed[' . $idx . ']" ';
			if( $cached_schedule['DepartureTimeConfirmed'][$idx] == TRUE)
				$checkbox .= 'checked="checked"';
			$checkbox .= '/> Departure Time Confirmed';
			
			$pre_padding = "<br>Loading Padding: <select name=\"PrePadding[$idx]\">";
			for($i = 40; $i >= 0; $i--){
				$pre_padding .= "<option value=\"$i\"";
				if(
					(isset($cached_schedule['PrePadding'][$idx]) && $i == $cached_schedule['PrePadding'][$idx])
					|| 
					(!isset($cached_schedule['PrePadding'][$idx]) && $i == $default_pre_padding)
					)
					$pre_padding .= " SELECTED";
				$pre_padding .= ">$i</option>";
			}
			$pre_padding .= "</select> Minutes";
			
		}
$cell = <<<HTML
    <td>&nbsp;</td>
    <td nowrap="nowrap">
        $radio_span_selector<input type="radio" name="DepartureTimeType[$idx]" id="DepartureTimeType[$idx]" value="NotConcern" $depart_unconcern_checked/> Time is not a concern$radio_span_selector_end<br />

        $radio_span_selector<input type="radio" onclick="checkRadios()" name="DepartureTimeType[$idx]" id="DepartureTimeType[$idx]" value="LeaveAt" $depart_leave_at_checked/>
        I need to leave here at<br><span style='margin-left:20px;'>$depart_time_selector</span> $radio_span_selector_end $checkbox $pre_padding</span>
    </td>
HTML;
   
    } else {
	if(current_user_has_role(1, "FullAdmin") || current_user_has_role($franchise_id, 'Franchisee')){
		$arrival_checkbox = '<br /><input type="checkbox" name="ArrivalTimeConfirmed[' . ($idx) . ']" ';
		if( $cached_schedule['ArrivalTimeConfirmed'][$idx-1] == TRUE)
			$arrival_checkbox .= 'checked="checked"';
		$arrival_checkbox .= '/> Arrival Time Confirmed';
		
		$checkbox = '<br /><input type="checkbox" name="DepartureTimeConfirmed[' . $idx . ']" ';
		if( $cached_schedule['DepartureTimeConfirmed'][$idx] == TRUE)
			$checkbox .= 'checked="checked"';
		$checkbox .= '/> Departure Time Confirmed';

		$note_row = "<td style=\"vertical-align:top; border: 0px;\"><textarea style=\"margin-top:-20px;\" name=\"TransitionNote[$idx]\" rows=3 cols=20>{$cached_schedule['TransitionNote'][$idx]}</textarea>"
			."<input type=checkbox name=\"TransitionFlexTimeFlag[$idx]\" ".(@$cached_schedule['TransitionFlexTimeFlag'][$idx] === 1 || @$cached_schedule['TransitionFlexTimeFlag'][$idx] === 'on' ? 'checked' : '')."> Time is Flexible"
			."</td>";
		$on_demand_row = "<td style=\"vertical-align:top; border: 0px;\"><input type=checkbox name=\"OnDemandFlag[$idx]\" ".(@$cached_schedule['OnDemandFlag'][$idx] === 1 || @$cached_schedule['OnDemandFlag'][$idx] === 'on' ? 'checked' : '')."> On Demand"
			."</td>";
		$post_padding = "<br>Drop Off Padding: <select name=\"PostPadding[$idx]\">";
		for($i = 10; $i >= 0; $i--){
			$post_padding .= "<option value=\"$i\"";
			if((isset($cached_schedule['PostPadding'][$idx]) && $i == $cached_schedule['PostPadding'][$idx]) || (!isset($cached_schedule['PostPadding'][$idx]) && $i == $default_post_padding))
				$post_padding .= " SELECTED";
			$post_padding .= ">$i</option>";
		}
		$post_padding .= "</select> Minutes";
		$pre_padding = "<br>Loading Padding: <select name=\"PrePadding[$idx]\">";
		for($i = 10; $i >= 0; $i--){
			$pre_padding .= "<option value=\"$i\"";
			if(isset($cached_schedule['PrePadding'][$idx]) && $i == $cached_schedule['PrePadding'][$idx] || !isset($cached_schedule['PrePadding'][$idx]) && $i == $default_pre_padding)
				$pre_padding .= " SELECTED";
			$pre_padding .= ">$i</option>";
		}
		$pre_padding .= "</select> Minutes";
		
	}
$cltime = $cached_schedule['LocationTime'][$idx] != '' ? $cached_schedule['LocationTime'][$idx] : '0';
$cell = <<<HTML
    <td nowrap="nowrap">
        $radio_span_selector<input type="radio" name="ArrivalTimeType[$idx]" id="ArrivalTimeType[$idx]" value="NotConcern" $arrival_unconcern_checked/> Time is not a concern$radio_span_selector_end<br />

        $radio_span_selector<input type="radio" onclick="checkRadios()" name="ArrivalTimeType[$idx]" id="ArrivalTimeType[$idx]" value="ArriveAt" $arrive_at_checked/>
        I need to get here at<br><span style='margin-left:20px;'>$arrive_time_selector</span> $radio_span_selector_end  $arrival_checkbox$post_padding</span>
    </td>
    <td nowrap="nowrap" id="DepartCell[$idx]">
        $radio_span_selector<input type="radio" name="DepartureTimeType[$idx]" id="DepartureTimeType[$idx]" value="NotConcern" $depart_unconcern_checked/> Time is not a concern$radio_span_selector_end<br />

        $radio_span_selector<input type="radio" onclick="checkRadios()" name="DepartureTimeType[$idx]" id="DepartureTimeType[$idx]" value="LeaveAt" $depart_leave_at_checked/>
        I need to leave here at<br><span style='margin-left:20px;'>$depart_time_selector</span> $radio_span_selector_end</span><br />

        $radio_span_selector<input type="radio" name="DepartureTimeType[$idx]" id="DepartureTimeType[$idx]" value="StayMinutes" $stay_checked/>$radio_span_selector_end
        I need to spend <input type="text" onFocus="jQuery(this).prev('span').find('input').prop('checked','true');" name="LocationTime[$idx]" id="LocationTime[$idx]" size="4" value="$cltime"/> minutes here$radio_span_selector_end	$checkbox$pre_padding
    </td>
    $note_row																																													
    $on_demand_row
HTML;
    }

    return $cell;
}
?>