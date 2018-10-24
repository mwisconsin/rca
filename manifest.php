<?php


include_once 'include/user.php';
redirect_if_not_logged_in();

require_once('include/rider.php');
require_once('include/driver.php');
require_once('include/link.php');
require_once('include/rc_log.php');
require_once('include/mapquest.php');
require_once('include/custom_ride_transition.php');
require_once 'include/franchise.php';
require_once 'include/date_time.php';
require_once 'include/address.php';
require_once 'include/class.email.php';

error_reporting(E_ALL);

#error_reporting(E_ALL);
global $ADDITIONAL_RC_JAVASCRIPT;
if (!is_array($ADDITIONAL_RC_JAVASCRIPT)) {
    $ADDITIONAL_RC_JAVASCRIPT = array();
}
$ADDITIONAL_RC_JAVASCRIPT[] = 'datepicker.js';


$franchise_id = get_current_user_franchise(TRUE);

if(!isset($_REQUEST['id'])){
	$driver_id = get_affected_user_id();
} else {
	$driver_id = $_REQUEST['id'];
}

if (!$driver_id) {
	echo "You are not listed as a driver.";
	include_once('include/footer.php');
	exit;
}
$driver_info = get_driver_person_info($driver_id, TRUE); 

$current_user_roles = get_user_roles(get_current_user_id(), $franchise_id);
$ReadOnly = 0;
if(current_user_has_role($franchise_id, 'Franchisee'))
	foreach($current_user_roles as $role) 
		if($role['Role'] == 'Franchisee') $ReadOnly = $role['ReadOnly'];
if(get_current_user_id() == get_affected_user_id()) $ReadOnly = 0;



if($_POST['CreateCustomTransition'] && $_POST['CustomTransition'] != null){
	header("Location: plan_ride.php?CustomTransDriver=$driver_id&ReleasedD=".$_POST['ReleaseDriver']."&CustomTransLinks=". implode(",",array_keys($_POST['CustomTransition'])));
}
	
$date = '';    
if(isset($_REQUEST['date']) && $_REQUEST['date'] != '') {
  $date = $_REQUEST['date'];
} else if(isset($_REQUEST['month']) && isset($_REQUEST['day']) && isset($_REQUEST['year'])) {
	$date = $_REQUEST['year'] . '-' . $_REQUEST['month'] . '-' . $_REQUEST['day'];
} else {
	#echo "got here";
	$links = get_driver_active_links( $driver_id, 'FUTURE' );
	#print_r($links);
	
	if(count($links) == 0) 
	{
    	$date = date('Y-m-d');  // Today
    	#echo '<br> Today <br>';
	}
	else
	{ 
		$date =date('Y-m-d', strtotime($links[0]['DesiredArrivalTime']));
		#echo '<br> as of ' . $date . ' - ' . $links[0]['DesiredDepartureTime'] . '<br>';
      $date2 = get_date($date);
      $_GET['month'] = $date2['Month'];
      $_GET['day'] = $date2['Day'];
      $_GET['year'] = $date2['Year'];
	}	
	//echo $date."<BR>";
}   
if($_POST['ReleaseDriver'] != null){
	driverreleased_all_on_date($driver_info['UserID'],$date);
}
	
	
    if ($_POST['UpdateArrivals']) {
        $keys = array_keys($_POST['hour']);
        if (count($keys)) {

            foreach ($keys as $update_link_id) {
                $posted_arrival = get_time_selector_post($update_link_id, 0);
                $reported_arrival = "{$posted_arrival['hour24']}:{$posted_arrival['minute']}";

                set_active_link_reported_arrival_time($update_link_id, $reported_arrival);
                set_completed_link_reported_arrival_time($update_link_id, $reported_arrival);
                // TODO:  Clean this up -- won't be in both tables
                // Should be in link_history if the date is prior to today.
            }
        }
    }
	
    if($_POST['CancelCustomTransition']){
    	$id = array_keys($_POST['CancelCustomTransition']);
    	$result = cancel_custom_ride_transition(get_link_custom_transition_id($id[0]));
    	
    	if($result){
    		$CTL_result = "<div class=\"reminder\">You have successfully cancelled the custom transition.</div>";
    	}
    }
    
    if($_POST['RevertCustomTransition']){
    	$id = array_keys($_POST['RevertCustomTransition']);
    	$result = revert_custom_ride_transition(get_link_custom_transition_id($id[0]));
    	
    	if($result){
    		$CTL_result = "<div class=\"reminder\">You have successfully reverted the custom transition to its original transition.</div>";
    	}
    }
    
    if($_POST['RequestRevertCustomTransition']){
    	$id = array_keys($_POST['RequestRevertCustomTransition']);
    	$CTL_result = "<form method=\"post\"><div class=\"reminder\" id=\"CTL\">Are you sure you want to revert to the original transition. This Cannot Redo This <center><input type=\"submit\" name=\"RevertCustomTransition[{$id[0]}]\" value=\"Revert\"> <input type=\"button\" onclick=\"$('CTL').setStyle('display','none');\" value=\"Cancel\"></center></div><form>";
    }
    
	if(isset($_POST["IndexPathSubmit"])) {
		foreach($_POST["IndexPath"] as $id => $v) {
			$sql = "update link set IndexPath = '$v' where LinkID = $id";
			mysql_query($sql);
		}
		$links = get_driver_active_links( $driver_id, 'FUTURE' );
	}
	
	if(isset($_POST['remove_location_start_or_finish']) && $_POST['remove_location_start_or_finish'] != '') {
		//echo($_POST['remove_location_start_or_finish']);
		if($_POST['remove_location_start_or_finish'] == 'start')
			unset($_SESSION['Manifest']['StartLocation']);
		if($_POST['remove_location_start_or_finish'] == 'end')
			unset($_SESSION['Manifest']['EndLocation']);
		header('Location: /manifest.php');
		exit();
	}
	
	//print_r($_SESSION['Manifest']);
	
	if(isset($_SESSION['Manifest']['StartLocation']['ERRORMESSAGE']) &&
			$_SESSION['Manifest']['StartLocation']['ERRORMESSAGE'] != '') {
			unset($_SESSION['Manifest']['StartLocation']);
			header('Location: /manifest.php');
			exit();	
	}		
	if(isset($_SESSION['Manifest']['EndLocation']['ERRORMESSAGE']) &&
			$_SESSION['Manifest']['EndLocation']['ERRORMESSAGE'] != ''){
			unset($_SESSION['Manifest']['EndLocation']);
			header('Location: /manifest.php');
			exit();	
	}		
	
	//print_r($_SESSION['Manifest']);		
	
	if($_POST['location_start_or_finish']){
		if($_POST['location_start_or_finish'] == 'start')
		{
			$_SESSION['Manifest']['StartLocation'] = usps_standardize_address(array('Address1' => $_POST['Address1'],
																				   'Address2' => $_POST['Address2'],
																				   'City' => $_POST['City'],
																				   'State' => $_POST['State'],
																				   'ZIP5' => $_POST['Zip5'],
																				   'ZIP4' => $_POST['Zip4']));
			$_SESSION['Manifest']['StartLocation']['Name'] = 'Starting Location';
		}
		else if($_POST['location_start_or_finish'] == 'end')
		{
			$_SESSION['Manifest']['EndLocation'] = usps_standardize_address(array('Address1' => $_POST['Address1'],
																				 'Address2' => $_POST['Address2'],
																				 'City' => $_POST['City'],
																				 'State' => $_POST['State'],
																				 'ZIP5' => $_POST['Zip5'],
																				 'ZIP4' => $_POST['Zip4']));
			$_SESSION['Manifest']['EndLocation']['Name'] = 'Ending Location';
		}
	}
	//print_r($_SESSION);
	include_once('include/header.php');
	echo "<style>
	.manifest_problem {
		background-color: lightblue;
	}
	.serious_manifest_problem {
		background-color: darkblue;
		color: white;
	}
	.manifest_try {
		background-color: cornsilk;
	}
	</style>
	";
	
	$pk = array_keys($_POST);
	for($i = 0; $i < count($pk); $i++) if(strstr($pk[$i],"button_email_drivers")) {
		$num_rides = str_replace("button_email_drivers_","",$pk[$i]);
		$sql = "select * from user_role
			natural join users
			natural join driver
			natural join email
			natural join person_name
			where
			user_role.franchiseID = 2
			and driver.DriverStatus = 'Active'
			and user_role.Role = 'Driver'";
		$r = mysql_query($sql);
		$i = 0;
		while($rs = mysql_fetch_array($r)) {
			#$to = $rs["EmailAddress"];
			$to = "mysterywisconsin@gmail.com";
			$from = "admin@myridersclub.com";
			$subject = "Riders Club: Need $num_rides Driver".($num_rides > 1 ? "s" : "")." for ".date('m/d/Y',strtotime($date));
			$body = "There ".($num_rides > 1 ? "are $num_rides rides" : "is 1 ride")." for ".date('m/d/Y',strtotime($date))." where we could use your help.

To view these driving opportunities, log in to the Riders Club site and navigate to your manifest page for ".date('l, F jS',strtotime($date)).". At the bottom of the page there will be a list of remaining rides as well as instructions on how to assign those to yourself. 

If you have any questions, please call the office number at 319-365-1511 and the on call person will help you, or get you in touch with someone who can.

Thank you for using Riders Club of Cedar Rapids!";	
			mail( $to, $subject, $body, "From: $from" );			
			$i++;
			if($i == 1) break;
		}
	}
	
	if($_POST["button_take_rides"] && isset($_POST["SelectIndexPath"]) && count($_POST["SelectIndexPath"]) > 0) {
		
		$sql = "select CustomTransitionID from link where IndexPath in ('".join("','",array_keys($_POST["SelectIndexPath"]))."')";

		$r = mysql_query($sql);
		$cts = [];
		while($rs = mysql_fetch_array($r)) if($rs["CustomTransitionID"] != "") { $cts[] = $rs["CustomTransitionID"]; }
		$sql = "update link set AssignedDriverUserID = ".get_affected_user_id()." where IndexPath in ('".join("','",array_keys($_POST["SelectIndexPath"]))."')";
		#echo $sql."<BR><BR>";
		mysql_query($sql);
		if(count($cts) > 0) {
			$sql = "update link set AssignedDriverUserID = ".get_affected_user_id()." where CustomTransitionID in ('".join("','",$cts)."')";
			#echo $sql."<BR><BR>";
			mysql_query($sql);			
		}

		error_reporting(E_ALL);
		$email = new Email();
		$u = get_user_account( get_affected_user_id() );
		$email_message = "Rides with the Index/Paths of ".join("','",array_keys($_POST["SelectIndexPath"]))." have been TAKEN by User ".$u['FirstName']." ".$u['LastName']." (".get_affected_user_id().").";
		$r = mysql_query("select fe.EmailType, e.EmailAddress as EmailAddress1, e2.EmailAddress as EmailAddress2, vacation_end, vacation_duration from franchise_email_settings fe, email e, email e2 where e.EmailID=fe.EmailID1 and e2.EmailID=fe.EmailID2 and fe.FranchiseID=".(int)$franchise_id." and EmailType = 'de_ride_taken'");
		#print_r($r);

		while($rs = mysql_fetch_array($r)) {
			#print_r($rs);
			$email->send($rs['EmailAddress1'], 'Rides Taken', $email_message);
			$email->send($rs['EmailAddress2'], 'Rides Taken', $email_message);
		}
	}
	
    $links = get_all_driver_history_and_active_links($driver_info['UserID'], $date);
#print_r($links);
	
	$trylinkids = [];
	if($_POST["button_try_above"] && isset($_POST["SelectIndexPath"]) && count($_POST["SelectIndexPath"]) > 0) {
		$sql = "select LinkID from link where IndexPath in ('".join("','",array_keys($_POST["SelectIndexPath"]))."')";
		$r = mysql_query($sql);
		while($rs = mysql_fetch_array($r)) $trylinkids[] = $rs["LinkID"];
		if(count($trylinkids) > 0) {
			$trylinks = get_links_from_array( $trylinkids );
			if(count($links) > 0) $links = array_merge($links,$trylinks);
			else $links = $trylinks;
		}
	}
	

	
    if ($links) {
        // Remove any link with a status containing CANCEL
        $cancels_removed = array_values(array_filter($links, create_function('$link_row_item', 
                                        'return (strpos($link_row_item["LinkStatus"], "CANCEL") === FALSE);')));
        $links = $cancels_removed;
    }

    $foundRide = false;
    
    // Add in deadhead/transition links
    $links_with_transition = array();
	if(isset($_SESSION['Manifest']['StartLocation'])){
//			echo "Session Manifest ";
//			print_r($_SESSION['Manifest']['StartLocation']);
				$transition_link = array(
					'LinkID' => 0,
					'RiderUserID' => 0,
					'DesiredArrivalTime' => '2010-09,-10 16:09:00',
					'FranchiseID' => $franchise_id,
					'Distance' => '5.9',
					'EstimatedMinutes' => '14',
					'QuotedCents' => 0,
					'DriverUserID' => $links[0]['DriverUserID'],
					'ReportedArrivalTime' => '',
					'LinkStatus' => 'UNKNOWN',
					'NumberOfRiders' => '0',
					'IsHistory' => $links[0]['IsHistory'],
				);
				foreach( array('Name', 'Public', 'DestinationID', 'DestinationDetail', 'AddressID', 
                           'Address1', 'Address2', 'City', 'State', 'ZIP5', 'ZIP4', 'VerifySource','Latitude','Longitude') as $field ) {
        	$transition_link["F_$field"] = $_SESSION['Manifest']['StartLocation'][$field];
        	$transition_link["T_$field"] = $links[0]["F_$field"];
        	$from_address[$field] = $_SESSION['Manifest']['StartLocation'][$field];
        	$to_address[$field] = $links[0]["F_$field"];
					$second_address[$field] = $links[0]["T_$field"];
        }
       

//            	echo "start location";
//		    	echo '<br />';
//        print_r( $from_address );
//				echo '<br />';
//				print_r( $to_address );
//				echo '<br />';
				$distance_and_time = get_mapquest_time_and_distance( $from_address, $to_address, @$_SESSION['Manifest']['StartLocation']['DesiredArrivalTime'] );
//				echo '<br /><br />second address:';
//				print_r($second_address);
//				$time2 = get_mapquest_time_and_distance($to_address, $second_address, @$_SESSION['Manifest']['StartLocation']['DesiredArrivalTime'] );
//				echo "<BR>time2: ";
//				print_r($time2);
				
				#$time2 = (ceil($time2['time'] / 60.0) + 10) * 60;
				#$time2 = (ceil($time2['time'] / 60.0)+2) * 60;
				$time2 = ($links[0]["EstimatedMinutes"] + $links[0]["PrePadding"] + $links[0]["PostPadding"]) * 60;
				
//				echo " moves to $time2";
//				echo "<BR>";
      	$transition_link['Distance'] = number_format($distance_and_time['distance'], 1);
     		$transition_link['EstimatedMinutes'] = ceil($distance_and_time['time'] / 60.0);
//				print_r($distance_and_time);
				$prev_depart_info = get_link_arrival_time($links[0]);
//				print_r( $prev_depart_info );
//				echo "DesiredArrivalTime = ".date('Y-m-d H:i:s',$prev_depart_info['time_t'])." - $time2<BR>";
        $transition_link['DesiredArrivalTime'] = date('Y-m-d H:i:s', $prev_depart_info['time_t']  - $time2);
				$leave_by = $prev_depart_info['time_t'] - $time2 - ($transition_link['EstimatedMinutes'] * 60);
				$transition_link['RiderPreferences'] = "Leave By " .  date("g:i a",  $leave_by) ;
     		$links_with_transition[] = $transition_link;
//     		echo 'count ' . count($links_with_transition);
//			    echo '<br />';
	}
	
    foreach ($links as $index => $next_link)
    {
    	//echo $index."<BR>";
    	//print_r($next_link);
    	// we only see rides if they have been confirmed or we are admin
        if($next_link['DriverConfirmed'] == 'Yes' || !current_user_has_role(1, 'FullAdmin') || !current_user_has_role($franchise_id, "Franchisee"))
        	$foundRide = true;
        
    	if ($index == 0) 
	    {
            $prev_link = $next_link;
            $links_with_transition[] = $prev_link;
           	//	echo 'count index == 0 ' . count($links_with_transition);
			//    echo '<br /><br />';
            continue;
        }
		
           		//echo 'count index != 0 ' . count($links_with_transition);
			    //echo '<br /><br />';
        if ($prev_link != null && array_filter(@$prev_link) && $prev_link['IsHistory'] != 'HISTORY' && $prev_link['T_AddressID'] != $next_link['F_AddressID']) {
            // If the departure time of next_link is at least ten minutes less than
            // the arrival time of prev_link, no transition.  Overlapping/parallel links
						
            $departure_time_info = get_link_departure_time($next_link);
            $arrival_time_info = get_link_arrival_time($prev_link);
            if ($arrival_time_info['time_t'] - (10 * 60) >= $departure_time_info['time_t']) {
                $links_with_transition[] = $next_link;
                $prev_link = $next_link;
                continue;
            }
			
            $transition_link = array(
                'LinkID' => 0,
                'RiderUserID' => 0,
                'DesiredArrivalTime' => '2010-09-10 16:09:00',
                'Distance' => '5.9',
                'EstimatedMinutes' => '14',
                'QuotedCents' => 0,
                'DriverUserID' => $prev_link['DriverUserID'],
                'ReportedArrivalTime' => '',
                'LinkStatus' => 'UNKNOWN',
                'NumberOfRiders' => '0',
                'IsHistory' => $next_link['IsHistory'],
            );

            foreach( array('Name', 'Public', 'DestinationID', 'DestinationDetail', 'AddressID', 
                           'Address1', 'Address2', 'City', 'State', 'ZIP5', 'ZIP4', 'VerifySource','Latitude','Longitude') as $field ) {
                $transition_link["F_$field"] = $prev_link["T_$field"];
                $transition_link["T_$field"] = $next_link["F_$field"];
                $from_address[$field] = $prev_link["T_$field"];
                $to_address[$field] = $next_link["F_$field"];
            }

            // Calculate distance and time
			//print_r($from_address);
			//	echo '<br /><br />';
			//	print_r($to_address);
			if($transition_link['Distance'] == 0 || $transition_link['EstimatedMinutes'])
			{
				$distance_and_time = get_mapquest_time_and_distance( $from_address, $to_address, $prev_link['DesiredArrivalTime'] );
				$transition_link['Distance'] = number_format($distance_and_time['distance'], 1);
				$transition_link['EstimatedMinutes'] = ceil($distance_and_time['time'] / 60.0);
			}
			$transition_link['FranchiseID'] = $franchise_id;
            // Departure time is arrival time at prev destination, so calculate DesiredArrivalTime backwards
            $prev_arrive_info = get_link_arrival_time($prev_link);
            $next_depart_info = get_link_departure_time($next_link);
            $transition_link['DesiredArrivalTime'] = date('Y-m-d H:i:s', 
                                                          $prev_arrive_info['time_t'] + $distance_and_time['time'] + 120);
			$leave_by = $next_depart_info['time_t'] - ($distance_and_time['time'] + 60);

			$transition_link['RiderPreferences'] = "Leave By " .  date("g:i a", $leave_by);
            $links_with_transition[] = $transition_link;
        }
        $links_with_transition[] = $next_link;
		$prev_link = $next_link;
        
    }

    
		if(isset($_SESSION['Manifest']['EndLocation']))
		{
			$transition_link = array(
				'LinkID' => 0,
				'RiderUserID' => 0,
				'DesiredArrivalTime' => '2010-09-10 16:09:00',
				'FranchiseID' => $franchise_id,
				'Distance' => '5.9',
				'EstimatedMinutes' => '14',
				'QuotedCents' => 0,
				'DriverUserID' => $prev_link['DriverUserID'],
				'ReportedArrivalTime' => '',
				'LinkStatus' => 'UNKNOWN',
				'NumberOfRiders' => '0',
				'IsHistory' => $next_link['IsHistory'],
			);
			foreach( array('Name', 'Public', 'DestinationID', 'DestinationDetail', 'AddressID', 
                           'Address1', 'Address2', 'City', 'State', 'ZIP5', 'ZIP4', 'VerifySource','Latitude','Longitude') as $field ) 
			{
                $transition_link["F_$field"] = $prev_link["T_$field"];
                $transition_link["T_$field"] = $_SESSION['Manifest']['EndLocation'][$field];
                $from_address[$field] =$prev_link["T_$field"];
                $to_address[$field] = $_SESSION['Manifest']['EndLocation'][$field];
           	}
//			echo "end location";
//			echo '<br />';
//			echo $from_address[$field];
//			echo '<br />';
//		    echo $to_address[$field];
//			echo '<br />';
		    $distance_and_time = get_mapquest_time_and_distance( $from_address, $to_address, $prev_link['DesiredArrivalTime'] );
            $transition_link['Distance'] = number_format($distance_and_time['distance'], 1);
           	$transition_link['EstimatedMinutes'] = ceil($distance_and_time['time'] / 60.0);
			$prev_depart_info = get_link_arrival_time($prev_link);
            $transition_link['DesiredArrivalTime'] = date('Y-m-d H:i:s', 
                                                          $prev_depart_info['time_t'] + $distance_and_time['time'] + 120);
			$leave_by =  $prev_depart_info['time_t'] + $distance_and_time['time'] + 120 - ( $transition_link['EstimatedMinutes'] * 60);
			$transition_link['RiderPreferences'] = "Leave By " .  date("g:i a", $leave_by); 
       		$links_with_transition[] = $transition_link;
		} 
           		//echo 'count done != 0 ' . count($links_with_transition);
			    //echo '<br />';
		$links = $links_with_transition;

//if($_GET['date']){
//	$date2 = get_date($_GET['date']);	
//	$_GET['month'] = $date2['Month'];
//	$_GET['day'] = $date2['Day'];
//	$_GET['year'] = $date2['Year'];
//}
if($_GET['date']){
	$date2 = get_date($_GET['date']);
	$_GET['month'] = $date2['Month'];
	$_GET['day'] = $date2['Day'];
	$_GET['year'] = $date2['Year'];
}

	
# no link number needed
# include depart time = previous arrive time
# arrive time (no pad for in and out of car)
# no rider name needed (since only driver)
# from = last drop off.  to = next pick-up
# blank rider preferences
# est travel minutes
# est distance
# highlight links without rider (shading) will make rides stand out
?>
<div style="text-align:center;"><h2><?php 
    echo $driver_info['FirstName'] ?>'s Scheduled Riders for <?php echo $date ?></h2></div>
    <?php echo $CTL_result; ?>
<div style="clear:both;">
<p>
<button name="PrintButton" class="noprint" onclick="window.print();">Print</button>
</p>
<form id="dateForm" method="get" class="noprint">
	<select name="month" id="month">
		<?php
			$months = array('January','February','March','April','May','June','July','August','September','October','November','December');
			
			foreach($months as $k => $v){
				echo "<option value=\"" . str_pad(($k + 1),2,'0',STR_PAD_LEFT) . "\"";
				
				if((isset($_GET['month']) && $_GET['month'] == ($k + 1)) || (!isset($_GET['month']) && date("n") == ($k + 1)))
					echo ' SELECTED';
				echo ">$v</option>";
			}
		?>
		
		
	</select> / 
	<select name="day" id="day">
	<?php
		for($i = 1; $i <= 31; $i++){
			echo '<option value="' . $i . '"';
			if((isset($_GET['day']) && $_GET['day'] == $i) || (!isset($_GET['day']) && date("d") == $i))
				echo ' SELECTED';
			echo '>' . $i . '</option>';
		}
	?>
	</select> / 
	<select name="year" id="year">
	<?php
		for($i = (int)date("Y")+1; $i >= (int)date("Y") - 5; $i--){
			echo '<option value="' . $i . '"';
			if((isset($_GET['year']) && $_GET['year'] == $i) || (!isset($_GET['year']) && date("Y") == $i))
				echo ' SELECTED';
			echo '>' . $i . '</option>';
		}
	?>
	</select>
	
	
          <script type="text/javascript">
          // <![CDATA[  
            var opts = {                            
                    formElements:{"year":"Y","month":"n","day":"j"},
                    statusFormat:"l-cc-sp-d-sp-F-sp-Y",
                    callbackFunctions:{
                        "dateset": [function(obj){
                            // Have to set these, because the callback gets called *before* the values are updated
                            var d = jQuery('#day');
                            var m = jQuery('#month');
                            var y = jQuery('#year');
                            // debugger;
                            if (d) { d.val(+obj.dd); }
                            if (m) { m.val(+obj.mm < 10 ? '0'+ +obj.mm : obj.mm); }
                            if (y) { y.val(obj.yyyy); }
                            // alert(jQuery('#day').val() + " " + jQuery('#month').val() + " " + jQuery('#year').val());
                            var f = $('dateForm'); if (f) { f.submit(); }
                        }]
                    }
            };           
            datePickerController.createDatePicker(opts);
          // ]]>
          </script>
        <?php 
        $next = get_date(get_next_day($date));
        $prev = get_date(get_previous_day($date));
		
        ?>
	<input  type="button" name="ChangeDate" value="Previous Day" onclick="window.location = 'manifest.php?month=<?php echo $prev['Month']; ?>&day=<?php echo $prev['Day']; ?>&year=<?php echo $prev['Year']; ?>'"> 
	<input  type="button" name="ChangeDate" value="Next Day" onclick="window.location = 'manifest.php?month=<?php echo $next['Month']; ?>&day=<?php echo $next['Day']; ?>&year=<?php echo $next['Year']; ?>'">
</form>
<br />
<div id="start_end_location_selector" style="display:none;">
	<form method="post" id="start_end_form">
        <table>
            <tr>
                <td>
                    <h3><span id="location_selector_title"></span></h3>
                </td>
            </tr>
            <tr>
                <td>
                    <input type="hidden" name="location_start_or_finish"  id="location_selector_start_or_end" value="start" />
                    <input type="hidden" name="remove_location_start_or_finish"  id="remove_location_selector_start_or_end" value="" />
                    <?php create_html_address_table(); ?>
                </td>
           </tr>
           <tr>
                <td>
                    <input type="submit" value="Save" />
                </td>
            </tr>
        </table>
	</form>
</div>
<?php if(!$ReadOnly) { ?>
<style>
#button_float {
	float: right; 
	text-align: center; 
	display: inline;	
}
#button_float_wrapper {
	height: 50px;
}
</style>
<div id=button_float_wrapper class="clearfix">
<div id=button_float class="noprint">
     <input class="noprint" type="button" value="Set Starting Location"
            onclick="$('location_selector_start_or_end').value = 'start';
                     $('start_end_location_selector').setStyle('display','');
                     $('location_selector_title').innerHTML = 'New Starting Location';
<?php                     
  $sql = "select * from user_address natural join address where addresstype = 'Physical' and userid = $driver_id";
  $r = mysql_query($sql);
  if(mysql_num_rows($r) > 0) {
  	$rs = mysql_fetch_assoc($r);
  	?>
  	$('Address1').value = '<?php echo addslashes($rs['Address1']); ?>';
  	$('Address2').value = '<?php echo addslashes($rs['Address2']); ?>';
  	$('City').value = '<?php echo addslashes($rs['City']); ?>';
  	$('State').value = '<?php echo addslashes($rs['State']); ?>';
  	$('Zip5').value = '<?php echo addslashes($rs['ZIP5']); ?>';
  	$('Zip4').value = '<?php echo addslashes($rs['ZIP4']); ?>';
  	<?php
  }                 
?>                     
                     " />
     <input class="noprint" type="button" value="Set Ending Location" 
            onclick="$('location_selector_start_or_end').value = 'end'; 
                     $('start_end_location_selector').setStyle('display',''); 
                     $('location_selector_title').innerHTML = 'New Ending Location';
<?php                     
  $sql = "select * from user_address natural join address where addresstype = 'Physical' and userid = $driver_id";
  $r = mysql_query($sql);
  if(mysql_num_rows($r) > 0) {
  	$rs = mysql_fetch_assoc($r);
  	?>
  	$('Address1').value = '<?php echo addslashes($rs['Address1']); ?>';
  	$('Address2').value = '<?php echo addslashes($rs['Address2']); ?>';
  	$('City').value = '<?php echo addslashes($rs['City']); ?>';
  	$('State').value = '<?php echo addslashes($rs['State']); ?>';
  	$('Zip5').value = '<?php echo addslashes($rs['ZIP5']); ?>';
  	$('Zip4').value = '<?php echo addslashes($rs['ZIP4']); ?>';
  	<?php
  }                 
?>                      
                     
                     
                     " /><br>
     <input class="noprint" type="button" value="Remove Starting Location" <?php if(!isset($_SESSION['Manifest']['StartLocation'])) echo "disabled"; ?> 
     				onclick="$('remove_location_selector_start_or_end').value = 'start';
     								 $('start_end_form').submit();"
     				/>
     <input class="noprint" type="button" value="Remove Ending Location" <?php if(!isset($_SESSION['Manifest']['EndLocation'])) echo "disabled"; ?> 
     				onclick="$('remove_location_selector_start_or_end').value = 'end';
     								 $('start_end_form').submit();"
     				/>
</div>
</div>
<?php 
} // ReadOnly
?>
<?php if (count($links) == 0) { ?>
<div style="font-size:18px; text-align:center; margin-top:120px;">
	You currently have no rides scheduled.<br />
</div>
<?php } else { ?>
<style type="text/css">
    tr.even td {
        background-color: #DDD;
    }
    tr.transition td {
        background-color: #FAA;
        font-size: 70%;
    }
</style>
<form method="POST" action="">
<table border="1" class=manifest>
    <tr>
    	<th class="noprint">Map</th>
   		<th class="noprint">SMS</th>
   		<?php if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise_id, "Franchisee")){ ?>
   		<th class="noprint">DR</th>
   		<?php } ?>
    	<th>Index</th>
        <th>Link ID</th>
        <th>Pickup Time</th>
        <th>Arrival Time</th>
        <th>#</th>
        <th>Rider</th>
        <th>Pickup From</th>
        <th>Arrive At</th>
        <th width="200px">Notes</th>
        <th>Rider Pref.</th>
        <th>Est'd Travel Mins</th>
        <th>Dist (miles)</th>
        <th>Enter Drop Time</th>
        <?php if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise_id, "Franchisee")){ ?>
		<th class="noprint">Released to Driver</th>  
        <th class="noprint">Custom Transition</th>
	       	<?php if(user_has_role(get_affected_user_id(), 1, 'FullAdmin') || user_has_role(get_affected_user_id(), $franchise_id, "Franchisee")) { ?>
        		<th class="noprint">Index Path</th>
        	<?php } ?>
        <?php } ?>
    </tr>
<?php
    $doing_history = ($links[0]['IsHistory'] == 'HISTORY');
    $alpha = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','x','y','z');
    $indexNum = 1;
    $alphaIndex = 0;
	

$last_arrival_time = 0;
if($foundRide)
{
	$releasedtodriverholder = 0; //This holds the released to driver holder.
	
	/* total volunteer minutes */
	$tvm = 0;

	foreach ($links as $link) 
    {

        if ($link['LinkStatus'] == 'CANCELEDEARLY')
            continue;
        
        if($link['DriverConfirmed'] == 'No' && !current_user_has_role(1, 'FullAdmin') && !current_user_has_role($franchise_id, "Franchisee"))
        	continue;
        
        if (!$doing_history && $link['IsHistory'] == 'HISTORY') {
            echo '<tr><td colspan="13"> HISTORY </td></tr>';
            $doing_history = TRUE;
        }

        $bg_class = ($bg_class == 'odd') ? 'even' : 'odd';
        
        if(in_array($link['LinkID'],$trylinkids)) $bg_class = 'manifest_try';


        if ($lf_rider_info = get_large_facility_rider_info_for_link($link['LinkID'])) {
            $rider_info = $lf_rider_info;
            $rider_name = get_lf_rider_person_info_string($lf_rider_info);
            //$rider_cell = get_lf_rider_person_info_string($lf_rider_info);
            $rider_pref_string = 'Preferences Not Set';
        } elseif ( $link['LinkID'] == 0 ) {
            $rider_name = 'TRANSITION';
            $rider_pref_string = $link['RiderPreferences'];
        } else {
            $rider_info = get_rider_person_info($link['RiderUserID']);
            $rider_name = get_displayable_person_name_string($rider_info);
            //$rider_cell = get_rider_person_info_string($link['RiderUserID'], TRUE);

            $rider_prefs = get_rider_prefs($link['RiderUserID']);
            
            $rider_pref_string = rider_preferences_to_display_string($rider_prefs);
            
        }
        
        $load_pad = ($link['LinkID'] == 0) ? 0 : 5;
        $drive_pad = ($link['LinkID'] == 0) ? 0 : 5;
        $rider_settings = get_user_rider_preferences($link['RiderUserID']);
        $departure_time_info = get_link_departure_time($link);
        $arrival_time_info = get_link_arrival_time($link);
				$estimated_minutes = get_link_estimated_minutes($link);

        $miles += $link['Distance'];
        $minutes += $link['EstimatedMinutes'];
        if (!isset($first_time_t)) {
            $first_time_t = $departure_time_info['time_t'];
        }
        $last_time_t = $arrival_time_info['time_t'];
        ?>
    <tr class="<?php echo ($link['LinkID'] == 0) ? 'transition' : $bg_class; ?>">
    	<td class="noprint"><a target="_blank" href="mapquest_link_map.php?<?php 
            if ($link['LinkID'] != 0) { 
                echo 'id=' . $link['LinkID']; 
            } else if($link['F_DestinationID'] == '' || $link['T_DestinationID'] == ''){
				echo 'T_address1=' . urlencode($link['T_Address1']) . '&T_address2=' . urlencode($link['T_Address2']) . '&T_city=' . urlencode($link['T_City']) . '&T_state=' . urlencode($link['T_State']) . '&T_zip5=' . urlencode($link['T_ZIP5']);
				echo '&F_address1=' . urlencode($link['F_Address1']) . '&F_address2=' . urlencode($link['F_Address2']) . '&F_city=' . urlencode($link['F_City']) . '&F_state=' . urlencode($link['F_State']) . '&F_zip5=' . urlencode($link['F_ZIP5']);
			} else {
                echo 'transition=' . $link['F_DestinationID'] . '&to=' . $link['T_DestinationID'];
            }
            ?>">Map</a></td>
        <?php
        	$sql = "select * from todays_links where LinkID = $link[LinkID]";
        	$r = mysql_query($sql);
        	$TextDriverForThisLink = 0;
        	if(mysql_num_rows($r) > 0) {
        		$rs = mysql_fetch_array($r);
        		$TextDriverForThisLink = $rs["TextDriverForThisLink"];
        	}
        ?>
        <td align=center class="noprint"><input onClick="updateTDFT(<?php echo $link["LinkID"]; ?>, this);" type=checkbox name=TextDriverForThisLink_<?php echo $link['LinkID']." ".($TextDriverForThisLink ? " checked" : ""); ?>></td>
        <?php if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise_id, "Franchisee")){ ?>
        <td align=center class="noprint"><input type=checkbox name=NewDriver_<?php echo $link['LinkID']; ?> class=NewDriverCheckbox ></td>
      	<?php } ?>	
        <td><?php 
        		if($link['LinkID'] != 0)
        			echo $indexNum++;
        		else
        			echo $alpha[$alphaIndex++]; ?></td>
        <td><a target="_blank" href="admin_schedule_link.php?LinkID=<?php echo $link['LinkID'] ?>"><?php echo $link['LinkID']; ?></a></td>
        <?php
        $class_pu_cell = "pickup_time_cell ";
        if($last_arrival_time > 0 && $last_arrival_time > $departure_time_info['time_t'])
        	$class_pu_cell .= ($last_arrival_time - $departure_time_info['time_t'] > 600 ? "serious_" : "")."manifest_problem";
				?>
        <td nowrap="nowrap" <?php echo " class=\"$class_pu_cell\" linkid=".$link["LinkID"]; ?>><?php echo date('g:i A', $departure_time_info['time_t']) ?></td>

        <td nowrap="nowrap" <?php if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise_id, "Franchisee")) echo " class=dropoff_time_cell linkid=".$link["LinkID"]; ?>><?php echo date('g:i A', $arrival_time_info['time_t']) ?></td>
        <?php
        if( $last_arrival_time > 0 )
        	$tvm += ($departure_time_info['time_t'] - $last_arrival_time) / 60 > 30 ? 30 : ($departure_time_info['time_t'] - $last_arrival_time) / 60;
        $tvm += ($arrival_time_info['time_t'] - $departure_time_info['time_t']) / 60;
        
        ?>
        <td><?php echo $link['NumberOfRiders']; ?></td>
        <td><?php echo $rider_name ."<br>";
		        $rider_count = 1;
                foreach(get_additional_riders($link['LinkID']) as $rider)
				{
                    echo get_displayable_person_name_string($rider) . "<br>";
					$rider_count += 1;
				}
				if($link['NumberOfRiders'] != $rider_count)
				{
					$preferences = get_user_rider_preferences($link['RiderUserID']);
					if($preferences['CaretakerID'] != NULL)
						{
						echo 'CG <br>';
						$CareTakerName = get_name( $preferences['CaretakerID'] );
						echo $CareTakerName['FirstName'] . ' ' . $CareTakerName['LastName'];}
				}
         ?></td>
        <?php
        $from_xout = "";
        
        $sql = "select * from link where CustomTransitionType = 'RIDER' and CustomTransitionID = $link[CustomTransitionID] and LinkStatus in ('CANCELEDLATE','WEATHERCANCEL','DESTINATIONCANCEL','HOSPITALCANCEL')";
        $r = mysql_query($sql);
        $result = array();
        if(mysql_num_rows($r) > 0) {
					// Make sure that every rider in the car to/from this destination has CANCELLEDLATE
					$result = mysql_fetch_assoc($r);
					$sql = "select * from link where CustomTransitionType = 'RIDER' and CustomTransitionID = $link[CustomTransitionID]
										and FromDestinationID = $link[F_DestinationID] and not RiderUserId = $result[RiderUserID] and not LinkStatus in ('CANCELEDLATE','WEATHERCANCEL','DESTINATIONCANCEL','HOSPITALCANCEL')";
					$r2 = mysql_query($sql);
					if(mysql_num_rows($r2) == 0) $from_xout = "xout";
        }
        ?>
        <td nowrap="nowrap" class="<?php echo $from_xout; ?>"><?php 
        	if(isset($link["F_DestinationID"])) {
	        	$sql = "select FirstName from users 
	        		natural join rider_destination
	        		natural join person_name where DestinationID = $link[F_DestinationID]";
	        	$rs = mysql_fetch_array(mysql_query($sql));
	          echo get_link_destination_table_cell_contents('F_', $link, FALSE, $rs["FirstName"]);
	        } else echo get_link_destination_table_cell_contents('F_', $link, FALSE);
	      ?>
	      </td>
				<?php            
        $to_xout = "";

        if(mysql_num_rows($r) > 0) {

					// Make sure that every rider in the car to/from this destination has CANCELLEDLATE
					$sql = "select * from link where CustomTransitionType = 'RIDER' and CustomTransitionID = $link[CustomTransitionID]
										and ToDestinationID = $link[T_DestinationID] and not RiderUserId = $result[RiderUserID] and not LinkStatus in ('CANCELEDLATE','WEATHERCANCEL','DESTINATIONCANCEL','HOSPITALCANCEL')";
					#echo $sql."<BR>";
					$r2 = mysql_query($sql);
					if(mysql_num_rows($r2) == 0) $to_xout = "xout";
        }				
				
				?>
         
        <td nowrap="nowrap" class="<?php echo $to_xout; ?>"><?php 
        	$sql = "select FirstName from users 
        		natural join rider_destination
        		natural join person_name where DestinationID = $link[T_DestinationID]";
        	$r2 = mysql_query($sql);
        	if(mysql_num_rows($r2) > 0) {
        		$rs = mysql_fetch_array($r2);
            echo get_link_destination_table_cell_contents('T_', $link, FALSE, $rs["FirstName"]); 
          } else echo get_link_destination_table_cell_contents('T_', $link, FALSE);
         ?></td>
        <?php
	        echo "<td class=link_note_cell><img style='display: none;' id=\"{$link['LinkID']}\" src=\"images/trans.gif\" onclick=\"pop_link_notes(this)\" class='" . 
	                (isset($link['LinkNote']) || isset($rider_settings['OtherNotes']) ? 'LinkNoteFilled' : 'LinkNoteBlank') . "' alt=\"df\" /> " .
	    		    (	isset($link['LinkNote']) || isset($rider_settings['OtherNotes']) 
	    		    		? $link['LinkNote'] . 
	    		    			($rider_settings['OtherNotes'] != '' && $link['LinkNote'] != '' ? "<br>...<br>" : "") . 
	    		    			$rider_settings['OtherNotes']
	    		    		: "");
	    		//echo var_dump($link);    		
	    		
	    		$next_rider_link = get_rider_next_ride($link["RiderUserID"], strtotime( $link["DesiredArrivalTime"].(date('I') ? "GMT-05" : "GMT-06") ), @$link['CustomTransitionID'] );
	    		if(isset($next_rider_link['LinkID'])) {
	    			if(($link['CustomTransitionID'] > 0 && $link['LinkID'] == $next_rider_link['CTI_LinkID']) || $link['CustomTransitionID'] == null)
	    				echo "<br><br>".$rider_info["FirstName"]."'s next driver will arrive at ".date('n/j g:ia',strtotime($next_rider_link['Departure Time']));
	    		}
		    	if($rider_count > 1) {
		    		foreach(get_additional_riders($link['LinkID']) as $rider) {
		    			$next_rider_link = get_rider_next_ride($rider["UserID"], strtotime( $link["DesiredArrivalTime"].(date('I') ? "GMT-05" : "GMT-06") ), @$link['CustomTransitionID'] );
			    		if(isset($next_rider_link['LinkID'])) {
			    			if(($link['CustomTransitionID'] > 0 && $link['LinkID'] == $next_rider_link['CTI_LinkID']) || $link['CustomTransitionID'] == null)
			    				echo "<br>".$rider["FirstName"]."'s next driver will arrive at ".date('n/j g:ia',strtotime($next_rider_link['Departure Time']));
			    		}
		    		}
		    	}
	    		echo "  </td>";
    		?>
        <!--<td><?php echo $link['LinkNote'] . ($rider_settings['OtherNotes'] != '' && $link['LinkNote'] != '' ? "<br>...<br>" : "") . $rider_settings['OtherNotes']; ?></td>-->
        <td><?php 
            echo "$rider_pref_string"; ?></td>
        <td><?php echo $estimated_minutes; ?></td>
        <td><?php echo $link['Distance'] ?></td>
        <td><?php 
                if ($link['LinkID'] != 0) {
                    if ($link['ReportedArrivalTime']) {
                        $reported_time_info = get_link_arrival_time($link, 'ReportedArrivalTime');
                        echo str_replace(' ', '&nbsp;', $reported_time_info['string']) . '<br />';

                        $selector_time = $reported_time_info['time_t'];
                    } else {
                        $selector_time = $arrival_time_info['time_t'] - (5 * 60);
                    }
                    echo '<span class="noprint">';
                    echo get_time_selector($link['LinkID'], 0, 
                                           date('h', $selector_time),
                                           date('i', $selector_time),
                                           date('A', $selector_time) );
                    echo '</span>';
                } else {
                    echo '&nbsp;';
                }
         ?></td>
		 
		 
		     	<?php 
		     	if((current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise_id, 'Franchisee'))&& $link['LinkID'] != 0){
         		if($link['IsHistory'] == 'HISTORY') { ?>
         			<td class="noprint">History</td>
         		<?php 
         		} else if($link['DriverConfirmed'] != 'Yes') { ?>
         			<td class="noprint"></td>
         		<?php  
         		} else { 
							$releasedtodriverholder = 1;
						?>
         			<td class="noprint"><center><img src=/images/green_check.gif></center></td>
         		<?php 
         		} 
					} 
					
					if((current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise_id, 'Franchisee'))&& $link['LinkID'] != 0){
         	if($link['IsHistory'] == 'HISTORY'){ ?>
         		<td class="noprint">History</td>
         	<?php } else if($link['CustomTransitionType'] != 'DRIVER'){ ?>
         		<td class="noprint"><center><input type="checkbox" name="CustomTransition[<?php echo $link['LinkID']; ?>]"></center></td>
         	<?php  } else { ?>
         		<td class="noprint"><input type="button" value="View CT" onClick="document.location = 'custom_transition.php?id=<?php echo $link['CustomTransitionID']; ?>'"><br><input type="submit" name="CancelCustomTransition[<?php echo $link['LinkID']; ?>]" value="Cancel CT" ></td>
         	<?php } ?>
         		<?php if((user_has_role(get_affected_user_id(), 1, 'FullAdmin') || user_has_role(get_affected_user_id(), $franchise_id, 'Franchisee'))&& $link['LinkID'] != 0){ ?>
         		<td class=noprint>
         			<input type=text size=4 name="IndexPath[<?php echo $link['LinkID']; ?>]" value="<?php echo $link['IndexPath']; ?>"><input type=submit name=IndexPathSubmit value="Upd">
         		</td>
         		<?php } ?>
         <?php }
             	echo "</tr>";
    	$last_arrival_time = $arrival_time_info['time_t'];
			 }

}
?>



<?php
if(count($links) > 0 && !$foundRide) {
?>
<div style="font-size:18px; text-align:center; margin-top:120px;">
	Manifests are currently being built.<br />
</div>
<?php
}
?>
</table>
</div>
<?php echo get_rider_preference_key(); ?>

<div><B> ML2 - Attend "Memory Loss Level 2" Riders for pick-up and drop-off.</div>
<div> WC - Wheelchair riders must be able to transfer to car on their own.  Do not load WC2.</B></div>
<div style="text-align: right;">
<?php if(!$ReadOnly) { ?>
<input class="noprint" name="UpdateArrivals" value="Update Arrival Times" type="submit" style="float:left;"/> <?php if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise_id, "Franchisee")){ ?>
<input type="hidden" name="releasedriver" value="<?php echo $releasedtodriverholder; ?>"/>
<input class="noprint" type="submit" name="CreateCustomTransition" value="Create Custom Transition"/>&nbsp;<input class="noprint" type="submit" name="ReleaseDriver" value="Release to Driver"/>
<?php if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise_id, 'Franchisee')) { ?>
&nbsp;<input class="noprint" type="button" name="NewDriver" value="New Driver" id="NewDriverButton"/>
<?php } ?>
</div>
<?php }
} ?>
</form>
<?php
    }
?>
<table>
    <tr><td><?php echo $miles; ?></td><td>Transport Miles</td></tr>
    <tr><td><?php echo $minutes; ?></td><td>Transport Minutes</td></tr>
    <tr><td><?php echo $tvm; ?></td><td>Total Volunteer Minutes</td></tr>
    <tr><td><?php
        $total_minutes = ($last_time_t - $first_time_t) / 60;
        echo $total_minutes;
    ?></td><td>Total Minutes start to last drop-off</td></tr>
</table>

<h3>If you have any difficulties, call <?php echo getFranchiseMainPhoneNumber($franchise_id); ?></h3>
<button name="PrintButton" class="noprint" onclick="window.print();">Print</button><br><br>

<div class="noprint">
<?php
	$sql = "select UserID from user_role natural join users where 
		users.status = 'ACTIVE' and 
		(
			(FranchiseID = $franchise_id and (Role = 'FullAdmin' or Role = 'Franchisee'))
			or
			(FranchiseID = 1 and Role = 'FullAdmin')
		)";
	$r = mysql_query($sql);
	$ids = [];
	while($rs = mysql_fetch_array($r)) $ids[] = $rs["UserID"];
	$auid = get_affected_user_id();
	
	$links = [];
	for($i = 0; $i < count($ids); $i++) {
		if($ids[$i] == $auid) continue;
		$mylinks = get_all_driver_history_and_active_links($ids[$i],$date);
		for($j = 0; $j < count($mylinks); $j++) { 
			$a_time = get_link_departure_time($mylinks[$j]);
			/* only show rides that are further out than now + 1 hour */
			if($a_time['time_t'] <= time() + 3600) continue;
			
			$sql = "select * from rider_driver_match where other_UserID = $auid and user_type = 'rider' and self_UserID = ".$mylinks[$j]['RiderUserID']." and rating < -1";
			$r = mysql_query($sql);
			if(mysql_num_rows($r) > 0) continue;

			$rider_prefs = [];
      if ($lf_rider_info = get_large_facility_rider_info_for_link($mylinks[$j]['LinkID'])) {
          $rider_info = $lf_rider_info;
          $rider_name = get_lf_rider_person_info_string($lf_rider_info);
          $rider_pref_string = 'Preferences Not Set';
      } elseif ( $mylinks[$j]['LinkID'] == 0 ) {
          $rider_name = 'TRANSITION';
          $rider_pref_string = $mylinks[$j]['RiderPreferences'];
      } else {
          $rider_info = get_rider_person_info($mylinks[$j]['RiderUserID']);
          $rider_name = get_displayable_person_name_string($rider_info);
          $rider_prefs = get_rider_prefs($mylinks[$j]['RiderUserID']);
          $rider_pref_string = rider_preferences_to_display_string($rider_prefs);    
      }
        			
			$sql = "select VehicleHeight from vehicle natural join vehicle_driver where UserID = $auid";
			$r = mysql_query($sql);
			if(mysql_num_rows($r) > 0) {
				$rs = mysql_fetch_array($r);
				if(
					(@$rider_prefs['HighVehicleOK'] == 'No' && $rs["VehicleHeight"] == 'HIGH') ||
					(@$rider_prefs['MediumVehicleOK'] == 'No' && $rs["VehicleHeight"] == 'MEDIUM') ||
					(@$rider_prefs['LowVehicleOK'] == 'No' && $rs["VehicleHeight"] == 'LOW')
				) continue;
			}
			
      if ($mylinks[$j]['LinkStatus'] == 'CANCELEDEARLY')
          continue;
      
      if($mylinks[$j]['DriverConfirmed'] == 'No' && !current_user_has_role(1, 'FullAdmin') && !current_user_has_role($franchise_id, "Franchisee"))
      	continue;
      	
      	
			if($mylinks[$j]['IndexPath'] != '') $links[] = $mylinks[$j];
		}
	}
	
	usort($links,function($a,$b) {
		$retval = strcmp($a['IndexPath'],$b['IndexPath']);
		#echo $a['IndexPath']." - ".$b['IndexPath']." = $retval<BR>";
		if($retval == 0) {
			$a_time = get_link_departure_time($a);
			$b_time = get_link_departure_time($b);
			$retval = $a_time['time_t'] - $b_time['time_t'];
		}
		return $retval;
	});
	

	if(count($links) > 0) {
?>
<h2>Take a Ride</h2>
Please examine the rides below. These are rides that have been marked by the admin as available for acquisition and/or need someone else to drive.
<br><br>
Rides are grouped by the "Index/path" column. To select one of the groups of rides, click the corresponding checkbox to the group where you have interest, and click the button "Take Rides"
<br><br>
If you aren't sure that the rides will work in your schedule, you can click the checkbox for a ride grouping and click "Try Above"
<br><br><form method=POST>
<input type=submit name=button_try_above id=button_try_above value="Try Above">&nbsp;&nbsp&nbsp;
<input type=submit name=button_take_rides id=button_take_rides value="Take Rides">&nbsp;&nbsp&nbsp;
<?php if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise_id, "Franchisee")) { ?><input type=submit name=button_email_drivers_<?php echo count($links); ?> id=button_email_drivers value="Admin: Email Drivers About These Rides"><?php } ?>
<br>
<table border=1 class=manifest>
	<tr>
		<th>Map</th>	
		<th>Select</th>
		<th>Index/path</th>
		<th>Link ID</th>
		<th>Pickup<br>Time</th>
		<th>Arrival<br>Time</th>
		<th>#</th>
		<th>Rider</th>
		<th>Pickup From</th>
		<th>Arrive At</th>
		<th>Notes</th>
		<th>Rider Pref.</th>
		<th>Est'd.<br>Travel<br>Mins</th>
		<th>Dist<br>(miles)</th>
	</tr>
<?php
	foreach ($links as $link) 
    {

				$rider_prefs = [];
        if ($lf_rider_info = get_large_facility_rider_info_for_link($link['LinkID'])) {
            $rider_info = $lf_rider_info;
            $rider_name = get_lf_rider_person_info_string($lf_rider_info);
            $rider_pref_string = 'Preferences Not Set';
        } elseif ( $link['LinkID'] == 0 ) {
            $rider_name = 'TRANSITION';
            $rider_pref_string = $link['RiderPreferences'];
        } else {
            $rider_info = get_rider_person_info($link['RiderUserID']);
            $rider_name = get_displayable_person_name_string($rider_info);
            $rider_prefs = get_rider_prefs($link['RiderUserID']);
            $rider_pref_string = rider_preferences_to_display_string($rider_prefs);    
        }
        				

        
        if (!$doing_history && $link['IsHistory'] == 'HISTORY') {
            echo '<tr><td colspan="13"> HISTORY </td></tr>';
            $doing_history = TRUE;
        }

        $bg_class = ($bg_class == 'odd') ? 'even' : 'odd';



        
        $load_pad = ($link['LinkID'] == 0) ? 0 : 5;
        $drive_pad = ($link['LinkID'] == 0) ? 0 : 5;
        $rider_settings = get_user_rider_preferences($link['RiderUserID']);
        $departure_time_info = get_link_departure_time($link);
        $arrival_time_info = get_link_arrival_time($link);
				$estimated_minutes = get_link_estimated_minutes($link);

        $miles += $link['Distance'];
        $minutes += $link['EstimatedMinutes'];
        if (!isset($first_time_t)) {
            $first_time_t = $departure_time_info['time_t'];
        }
        $last_time_t = $arrival_time_info['time_t'];
        ?>
    <tr id="Row<?php echo $link['LinkID']; ?>" class="<?php echo ($link['LinkID'] == 0) ? 'transition' : '' ?>">
    	<td class="noprint"><a target="_blank" href="mapquest_link_map.php?<?php 
            if ($link['LinkID'] != 0) { 
                echo 'id=' . $link['LinkID']; 
            } else if($link['F_DestinationID'] == '' || $link['T_DestinationID'] == ''){
				echo 'T_address1=' . urlencode($link['T_Address1']) . '&T_address2=' . urlencode($link['T_Address2']) . '&T_city=' . urlencode($link['T_City']) . '&T_state=' . urlencode($link['T_State']) . '&T_zip5=' . urlencode($link['T_ZIP5']);
				echo '&F_address1=' . urlencode($link['F_Address1']) . '&F_address2=' . urlencode($link['F_Address2']) . '&F_city=' . urlencode($link['F_City']) . '&F_state=' . urlencode($link['F_State']) . '&F_zip5=' . urlencode($link['F_ZIP5']);
			} else {
                echo 'transition=' . $link['F_DestinationID'] . '&to=' . $link['T_DestinationID'];
            }
            ?>">Map</a></td>	
    	<?php
    	$templinks = $links;
    	if($lastIndexPath != $link["IndexPath"]) {
	    	$rowspan = 1;
	    	foreach($templinks as $tlink) 
	    		if($link["LinkID"] != $tlink["LinkID"] && $tlink["IndexPath"] == $link["IndexPath"])
	    			$rowspan++;
	    	echo "<td rowspan={$rowspan} valign=middle>";
	    	echo "<input type=checkbox name=\"SelectIndexPath[".$link["IndexPath"] ."]\""
	    		.(isset($_POST["SelectIndexPath"]) && in_array($link["IndexPath"],array_keys($_POST["SelectIndexPath"])) ? " checked" : "")
	    		.">";
	    	echo "</td>";
	    	echo "<td rowspan={$rowspan} valign=middle>";
	    	echo $link["IndexPath"];
	    	echo "</td>";           
	    }
    	$lastIndexPath = $link["IndexPath"];
      $class_pu_cell = "pickup_time_cell ";
      if($last_arrival_time > 0 && $last_arrival_time > $departure_time_info['time_t'])
      	$class_pu_cell .= ($last_arrival_time - $departure_time_info['time_t'] > 600 ? "serious_" : "")."manifest_problem";
			?>
			<td><a target="_blank" href="admin_schedule_link.php?LinkID=<?php echo $link['LinkID'] ?>"><?php echo $link['LinkID']; ?></a></td>
      <td nowrap="nowrap" <?php echo " class=\"$class_pu_cell\" linkid=".$link["LinkID"]; ?>><?php echo date('g:i A', $departure_time_info['time_t']) ?></td>
      <td nowrap="nowrap" <?php if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise_id, "Franchisee")) echo " class=dropoff_time_cell linkid=".$link["LinkID"]; ?>><?php echo date('g:i A', $arrival_time_info['time_t']) ?></td>
        <td><?php echo $link['NumberOfRiders']; ?></td>
        <td><?php echo $rider_name ."<br>";
		        $rider_count = 1;
                foreach(get_additional_riders($link['LinkID']) as $rider)
				{
                    echo get_displayable_person_name_string($rider) . "<br>";
					$rider_count += 1;
				}
				if($link['NumberOfRiders'] != $rider_count)
				{
					$preferences = get_user_rider_preferences($link['RiderUserID']);
					if($preferences['CaretakerID'] != NULL)
						{
						echo 'CG <br>';
						$CareTakerName = get_name( $preferences['CaretakerID'] );
						echo $CareTakerName['FirstName'] . ' ' . $CareTakerName['LastName'];}
				}
         ?></td>
        <?php
        $from_xout = "";
        
        $sql = "select * from link where CustomTransitionType = 'RIDER' and CustomTransitionID = $link[CustomTransitionID] and LinkStatus in ('CANCELEDLATE','WEATHERCANCEL','DESTINATIONCANCEL','HOSPITALCANCEL')";
        $r = mysql_query($sql);
        $result = array();
        if(mysql_num_rows($r) > 0) {
					// Make sure that every rider in the car to/from this destination has CANCELLEDLATE
					$result = mysql_fetch_assoc($r);
					$sql = "select * from link where CustomTransitionType = 'RIDER' and CustomTransitionID = $link[CustomTransitionID]
										and FromDestinationID = $link[F_DestinationID] and not RiderUserId = $result[RiderUserID] and not LinkStatus in ('CANCELEDLATE','WEATHERCANCEL','DESTINATIONCANCEL','HOSPITALCANCEL')";
					$r2 = mysql_query($sql);
					if(mysql_num_rows($r2) == 0) $from_xout = "xout";
        }
        ?>
        <td nowrap="nowrap" class="<?php echo $from_xout; ?>"><?php 
        	if(isset($link["F_DestinationID"])) {
	        	$sql = "select FirstName from users 
	        		natural join rider_destination
	        		natural join person_name where DestinationID = $link[F_DestinationID]";
	        	$rs = mysql_fetch_array(mysql_query($sql));
	          echo get_link_destination_table_cell_contents('F_', $link, FALSE, $rs["FirstName"]);
	        }
	      ?>
	      </td>
	      <?php            
        $to_xout = "";

        if(mysql_num_rows($r) > 0) {

					// Make sure that every rider in the car to/from this destination has CANCELLEDLATE
					$sql = "select * from link where CustomTransitionType = 'RIDER' and CustomTransitionID = $link[CustomTransitionID]
										and ToDestinationID = $link[T_DestinationID] and not RiderUserId = $result[RiderUserID] and not LinkStatus in ('CANCELEDLATE','WEATHERCANCEL','DESTINATIONCANCEL','HOSPITALCANCEL')";
					#echo $sql."<BR>";
					$r2 = mysql_query($sql);
					if(mysql_num_rows($r2) == 0) $to_xout = "xout";
        }				
				
				?>
         
        <td nowrap="nowrap" class="<?php echo $to_xout; ?>"><?php 
        	$sql = "select FirstName from users 
        		natural join rider_destination
        		natural join person_name where DestinationID = $link[T_DestinationID]";
        	$r2 = mysql_query($sql);
        	if(mysql_num_rows($r2) > 0) {
        		$rs = mysql_fetch_array($r2);
            echo get_link_destination_table_cell_contents('T_', $link, FALSE, $rs["FirstName"]); 
          }
         ?></td>
        <?php
	        echo "<td class=link_note_cell><img style='display: none;' id=\"{$link['LinkID']}\" src=\"images/trans.gif\" onclick=\"pop_link_notes(this)\" class='" . 
	                (isset($link['LinkNote']) || isset($rider_settings['OtherNotes']) ? 'LinkNoteFilled' : 'LinkNoteBlank') . "' alt=\"df\" /> " .
	    		    (	isset($link['LinkNote']) || isset($rider_settings['OtherNotes']) 
	    		    		? $link['LinkNote'] . 
	    		    			($rider_settings['OtherNotes'] != '' && $link['LinkNote'] != '' ? "<br>...<br>" : "") . 
	    		    			$rider_settings['OtherNotes']
	    		    		: "");
	    		//echo var_dump($link);    		
	    		
	    		$next_rider_link = get_rider_next_ride($link["RiderUserID"], strtotime( $link["DesiredArrivalTime"].(date('I') ? "GMT-05" : "GMT-06") ), @$link['CustomTransitionID'] );
	    		if(isset($next_rider_link['LinkID'])) {
	    			if(($link['CustomTransitionID'] > 0 && $link['LinkID'] == $next_rider_link['CTI_LinkID']) || $link['CustomTransitionID'] == null)
	    				echo "<br><br>".$rider_info["FirstName"]."'s next driver will arrive at ".date('n/j g:ia',strtotime($next_rider_link['Departure Time']));
	    		}
		    	if($rider_count > 1) {
		    		foreach(get_additional_riders($link['LinkID']) as $rider) {
		    			$next_rider_link = get_rider_next_ride($rider["UserID"], strtotime( $link["DesiredArrivalTime"].(date('I') ? "GMT-05" : "GMT-06") ), @$link['CustomTransitionID'] );
			    		if(isset($next_rider_link['LinkID'])) {
			    			if(($link['CustomTransitionID'] > 0 && $link['LinkID'] == $next_rider_link['CTI_LinkID']) || $link['CustomTransitionID'] == null)
			    				echo "<br>".$rider["FirstName"]."'s next driver will arrive at ".date('n/j g:ia',strtotime($next_rider_link['Departure Time']));
			    		}
		    		}
		    	}
	    		echo "  </td>";
    		?>
        <!--<td><?php echo $link['LinkNote'] . ($rider_settings['OtherNotes'] != '' && $link['LinkNote'] != '' ? "<br>...<br>" : "") . $rider_settings['OtherNotes']; ?></td>-->
        <td><?php 
            echo "$rider_pref_string"; ?></td>
        <td><?php echo $estimated_minutes; ?></td>
        <td><?php echo $link['Distance'] ?></td>

    </tr>
    <?php
    } #foreach link
    ?>	
</table>
</form>
<?php		
	}
	
?>
</div> <!-- noprint -->
</div>
<div style="clear:both">&nbsp;</div>
<?php
	include_once 'include/footer.php';
?>
<style>
.pickup_time_cell, .dropoff_time_cell {
	cursor: pointer;
}	
.alert_time {
	font-weight: bold;
	color: red;
}
</style>
<script>
jQuery(function($) {
	$('input[name^="IndexPath"]').bind('keypress', function(e)
	{
	   if(e.keyCode == 13)
	   {
	      return false;
	   }
	});	
	
	
	
	$('.pickup_time_cell, .dropoff_time_cell').on('click',function(ev) {
		var cell = ev.delegateTarget;
		var linkid = $(cell).attr('linkid');
		var pickup_time = $('td.pickup_time_cell[linkid='+linkid+']').html();
		var dropoff_time= $('td.dropoff_time_cell[linkid='+linkid+']').html();
		var pickuphours = '<select class=pickuphours>'
		for(var i = 1; i <= 12; i++)
			pickuphours += '<option value='+i+(parseInt(pickup_time.substr(0,2),10)==i?' selected':'')+'>'+i+'</option>';
		pickuphours += '</select>';
		var dropoffhours = '<select class=dropoffhours>'
		for(var i = 1; i <= 12; i++)
			dropoffhours += '<option value='+i+(parseInt(dropoff_time.substr(0,2),10)==i?' selected':'')+'>'+i+'</option>';
		dropoffhours += '</select>';	
		var pickupminutes = '<select class=pickupminutes>'
		for(var i = 0; i <= 59; i++)
			pickupminutes += '<option value='+i+(parseInt(pickup_time.substr(2,2),10)==i?' selected':'')+'>'+(i<10?'0':'')+i+'</option>';
		pickupminutes += '</select>';		
		var dropoffminutes = '<select class=dropoffminutes>'
		for(var i = 0; i <= 59; i++)
			dropoffminutes += '<option value='+i+(parseInt(dropoff_time.substr(2,2),10)==i?' selected':'')+'>'+(i<10?'0':'')+i+'</option>';
		dropoffminutes += '</select>';	
		var pickupampm = '<select class=pickupampm>'
			+'<option value=AM'+(pickup_time.substr(6)=='AM'?' selected':'')+'>AM</option>'	
			+'<option value=PM'+(pickup_time.substr(6)=='PM'?' selected':'')+'>PM</option>'	
			+'</select>';	
		var dropoffampm = '<select class=dropoffampm>'
			+'<option value=AM'+(dropoff_time.substr(6)=='AM'?' selected':'')+'>AM</option>'	
			+'<option value=PM'+(dropoff_time.substr(6)=='PM'?' selected':'')+'>PM</option>'	
			+'</select>';	
		var pu_date = new Date();
		pu_date.setHours( parseInt(pickup_time.substr(0,2),10)+(parseInt(pickup_time.substr(0,2),10)>=1&&parseInt(pickup_time.substr(0,2),10)<12&&pickup_time.substr(6)=='PM'?12:0) );
		pu_date.setMinutes( pickup_time.substr(2,2) );
		var do_date = new Date();
		do_date.setHours( parseInt(dropoff_time.substr(0,2),10)+(parseInt(dropoff_time.substr(0,2),10)>=1&&parseInt(dropoff_time.substr(0,2),10)<12&&dropoff_time.substr(6)=='PM'?12:0) );
		do_date.setMinutes( dropoff_time.substr(2,2) );	
			
		$d = $('<div>Original Pickup Time: '+pickup_time
			+'<br>Original Dropoff Time: '+dropoff_time
			+'<br>Original Time Difference: <span class=oldtimediff>'+Math.floor(((do_date.getTime() - pu_date.getTime())/1000)/60)+'</span> minutes'
			+'<br><br><br>'
			+'New Pickup Time: '+pickuphours+pickupminutes+pickupampm+'<br>'
			+'New Dropoff Time: '+dropoffhours+dropoffminutes+dropoffampm+'<br>'
			+'New Time Difference: <span class=newtimediff>'+Math.floor(((do_date.getTime() - pu_date.getTime())/1000)/60)+'</span> minutes'
			+'<input type=hidden name=linkid value='+linkid+'>'
			+'</div>').dialog({
			title: "Change Pickup/Dropoff Time",
			width: 360,
			modal: true,
			buttons: [
				{ text: 'Ok',
					click: function() {
						$.post('/xhr/update_link_times.php',{
								linkid:$('input[name="linkid"]').val(),
								minutes:$('.newtimediff').html(),
								dropoff:$('.dropoffhours').val()+':'+($('.dropoffminutes').val()<10?'0':'')+$('.dropoffminutes').val()+' '+$('.dropoffampm').val() 
							}, function(data) {
								window.location.reload();
						});
					}
				},
				{ text: 'Cancel',
					click: function() {
						$d.dialog('close');
						$d.remove();
					}
				}
			]
		});
	});	
	$(document.body).on('change','.pickuphours,.pickupminutes,.pickupampm,.dropoffhours,.dropoffminutes,.dropoffampm',function() {
		var pu_date = new Date();
		var pu_hours = parseInt($('.pickuphours').val(),10);
		if(pu_hours == 12 && $('.pickupampm').val()=='AM') pu_hours = 0;
		else if(pu_hours < 12 && $('.pickupampm').val()=='PM') pu_hours += 12;
		pu_date.setHours( pu_hours );
		pu_date.setMinutes( $('.pickupminutes').val() );
		var do_date = new Date();
		var do_hours = parseInt($('.dropoffhours').val(),10);
		if(do_hours == 12 && $('.dropoffampm').val()=='AM') do_hours = 0;
		else if(do_hours < 12 && $('.dropoffampm').val()=='PM') do_hours += 12;		
		do_date.setHours( do_hours );
		do_date.setMinutes( $('.dropoffminutes').val() );			
		$('.newtimediff').html( Math.floor(((do_date.getTime() - pu_date.getTime())/1000)/60) );
		$('.newtimediff').removeClass('alert_time');
		if( parseInt( $('.newtimediff').html(), 10) < parseInt( $('.oldtimediff').html(), 10) ) $('.newtimediff').addClass('alert_time');
	});	
	
	$('#NewDriverButton').on('click',function() {
		driver_checkboxes = $('.NewDriverCheckbox:checked');
		if(driver_checkboxes.length == 0) {
			d = jQuery('<div>Please select at least one ride from the DR column.</div>').dialog({
				modal: true,
				buttons: [
					{
						text: 'Ok',
						click: function() { d.dialog('close'); }
					}
				],
				close: function() { d.remove(); }	
			});
			return;
		}
		d = jQuery('<div>Select from the list of available drivers:<br><select size=1 id=SelectNewDriver><?php
			$sql = "select distinct UserID,LastName,FirstName from users natural join driver natural join person_name natural join user_role 
				where DriverStatus = 'Active' and FranchiseID = $franchise_id and not users.UserId = $driver_id
				order by FirstName,LastName";
			$r = mysql_query($sql);
			while($rs = mysql_fetch_array($r)) { echo "<option value=$rs[UserID]>$rs[FirstName] $rs[LastName]</option>"; }
		?></select></div>').dialog({
				modal: true,
				title: 'Assign New Driver',
				buttons: [
					{
						text: 'Assign Rides',
						click: function() { 
							var links = new Array();
							for(var i = 0; i < driver_checkboxes.length; i++) links[links.length] = jQuery(driver_checkboxes[i]).attr('name').replace('NewDriver_','');
							jQuery.post('/xhr/newDriver.php',{ links: links.join(','), driver: jQuery('#SelectNewDriver').val() },function(data) {
								d.dialog('close'); 
								window.location.reload();							
							});

						}
					},
					{
						text: 'Cancel',
						click: function() { d.dialog('close'); }
					}
				],
				close: function() { d.remove(); },
				open: function() {
					$('#SelectNewDriver').on('keypress',function(e) {
						if(e.keyCode == 13) {
							$('.ui-dialog-buttonset > button:first').trigger('click');
						}
					});
				}
			});
	});
	
});	

function updateTDFT(linkid, cb) {
		jQuery.get('/xhr/updateTDFT.php?linkid='+linkid+'&to='+(cb.checked?1:0),function(data) {
			d = jQuery('<div>Ride Text Preferences Updated</div>').dialog({
				modal: true,
				buttons: [
					{
						text: 'Ok',
						click: function() { d.dialog('close'); }
					}
				],
				close: function() { d.remove(); }	
			});
		});
	}
	
</script>