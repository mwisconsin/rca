<?php
	include_once 'include/user.php';
	redirect_if_not_logged_in();

    require_once('include/driver_link.php');
    require_once('include/link.php');
    require_once('include/driver.php');
	require_once('include/rider_driver.php');
    require_once('include/date_time.php');
    require_once('include/db_driver_availability.php');
	require_once 'include/franchise.php';

	$franchise_id = get_current_user_franchise();
	
    if (!current_user_has_role(1, 'FullAdmin') && !current_user_has_role($franchise_id, "Franchisee")) {
        header('Location:  home.php');
		die();
    }

		if(isset($_POST["IndexPathSubmit"])) {
			foreach($_POST["IndexPath"] as $id => $v) {
				$sql = "update link set IndexPath = '$v' where LinkID = $id";
				mysql_query($sql);
			}
			$links = get_driver_active_links( $driver_id, 'FUTURE' );
		}
	
		$_REQUEST['ShowAllDrivers'] = 1;
		
    set_franchise_timezone(2);  // TODO:  Per-user/franchise TZ

    $link = get_link($_GET['LinkID']);

    if (FALSE === $link || $link == null) $link = get_history_link($_GET['LinkID']);
    if (FALSE === $link || $link == null) {
        // TODO:  Err of some sort?
        echo "Could not find link ({$_REQUEST['LinkID']}).";
        exit();
    }

    if ($link && $_POST['Assign']) {
        $assigned_driver = array_keys($_POST['Assign']);
        $assigned_driver = $assigned_driver[0];

        // Assign driver
        if (set_driver_for_link($link['LinkID'], $assigned_driver)) {
            $assign_success = TRUE;
        }

        // Re-request link info now that driver is assigned
        $link = get_link($_REQUEST['LinkID']);
    }

    if ($link && $_POST['RemoveDriver']) {
        if (!remove_driver_for_link( $link['LinkID'] )) {
            // TODO:  Err of some sort
            echo "Could not remove driver for link {$link['LinkID']}";
        }

        // Re-request link info now that driver is removed
        $link = get_link($_REQUEST['LinkID']);
    }

    $arrival_time_info = get_link_arrival_time($link);
    $link_time_t = $arrival_time_info['time_t'];
    $link_date = date('Y-m-d', $link_time_t);

    $departure_time_info = get_link_departure_time($link);

    if (count(@$_POST['UpdateHours']) && count($_POST['DailyHours'])) {
        foreach ($_POST['UpdateHours'] as $driver_id => $ignore) {
            if (isset($_POST['DailyHours'][$driver_id ])) {
                set_driver_daily_availability( $driver_id, $link_date, $_POST['DailyHours'][$driver_id] );
            }
        }
    }
    /*            <td><input type="text" size="1" name="DailyHours[<?php 
                    echo $driver_id ?>]" /><br /><input type="submit" name="UpdateHours[<?php
                    echo $driver_id ?>]" value="set" /></td>
*/

    // TODO:  Link Time padding

    // TODO:  Will have to ensure drivers are not scheduled at the same time
    $scheduled_drivers = get_drivers_scheduled_on_date($franchise_id, $link_date); 
    $all_unscheduled_drivers = get_drivers_not_scheduled_on_date($franchise_id, $link_date); 
    $unscheduled_drivers = array();

    // Remove any unscheduled drivers that are on vacation or not available.
    if ($all_unscheduled_drivers) {
        //$unscheduled_drivers = array(1);
        foreach ($all_unscheduled_drivers as $unscheduled_id) {
            $available = driver_is_available($unscheduled_id, $departure_time_info,
                                                              $arrival_time_info);
            if ($available) {
                $unscheduled_drivers[] = $unscheduled_id;
            }
        }
    }

    $next_link_id = get_next_link_id_needing_driver_on_date( $franchise_id, $link_date, $link['LinkID']);
    $prev_link_id = get_previous_link_id_needing_driver_on_date( $franchise_id, $link_date, $link['LinkID']);

    $next_link_no_driver_id = get_next_link_id_needing_driver_on_date( $franchise_id, $link_date, $link['LinkID'], TRUE);
    $prev_link_no_driver_id = get_previous_link_id_needing_driver_on_date( $franchise_id, $link_date, $link['LinkID'], TRUE);

    include_once('include/header.php');
?>
<?php if (isset($assign_success) && $assign_success) { ?>
<h2>Successfully Scheduled!</h2>
<?php } ?>

<h2>Schedule This Ride</h2>
<form method=POST>
<table border="1">
    <tr>
        <th>Ride ID</th>
        <th>Date</th>
        <th>Depart Time</th>
        <th>Arrival Time</th>
        <th>Rider</th>
        <th>From</th>
        <th>To</th>
        
        <th>Preferences<?php echo str_repeat('&nbsp;', 20); ?></th>
        <th>Driver</th>
        <th>Status</th>
        <th>Total Rider Dest Today</th>
        <th>Total Dest Left Today</th>
        <th>Remain Rider Time</th>
        <th># Riders</th>
        <th>Index Path</th>
    </tr>
    <tr>
<?php 
    echo get_link_as_admin_link_table_row($link, FALSE); 

    // TODO:  Rider stats
    $links_today = get_rider_links_on_same_day($link['LinkID']);
    if ($links_today) {
        $rider_dest_today = count($links_today);

        $latest_arrival = 0;
        $remain_dest_left = 0;
        // Above, we've gotten the arrival/departure for the link in question
        foreach ($links_today as &$today_link) {
            $tl_arrive = get_link_arrival_time($today_link);

            if ($tl_arrive['time_t'] > $latest_arrival) {
                $latest_arrival = $tl_arrive['time_t'];
            }

            if ($tl_arrive['time_t'] > $link_time_t) {
                $rider_dest_left++;
            }
        }
        $remain_rider_time = ceil(($latest_arrival - $departure_time_info['time_t']) / 60);
    } else {
        $rider_dest_today = '???';
        $rider_dest_left = '???';
        $remain_rider_time = '???';
    }
    ?>
        <td><?php echo $rider_dest_today ?></td>
        <td><?php echo $rider_dest_left ?></td>
        <td><?php echo $remain_rider_time ?> min</td>
        <td><?php echo $link['NumberOfRiders'] ?></td>
     		<td class=noprint>
     			<input type=text size=4 name="IndexPath[<?php echo $link['LinkID']; ?>]" value="<?php echo $link['IndexPath']; ?>"><input type=submit name=IndexPathSubmit value="Upd">
     		</td>
    </tr>

</table>
</form>
<p>
<?php 

if ($prev_link_id) {
    echo "<a href=\"admin_schedule_link.php?LinkID=$prev_link_id\">Previous Link</a>  ";
} else {
    // First link of the day
    $prev_day_time_t = $link_time_t - (24 * 60 * 60);
    $prev_day_year = date('Y', $prev_day_time_t);
    $prev_day_month = date('m', $prev_day_time_t);
    $prev_day_day = date('j', $prev_day_time_t);
    echo "<a href=\"admin_driver_links.php?Year=$prev_day_year&Month=$prev_day_month&Day=$prev_day_day\">PREVIOUS DAY</a>  ";
}

$link_year = date('Y', $link_time_t);
$link_month = date('m', $link_time_t);
$link_day = date('j', $link_time_t);
echo "<a href=\"admin_driver_links.php?Year=$link_year&Month=$link_month&Day=$link_day\">Link List</a>  ";

if ($next_link_id) {
    echo "<a href=\"admin_schedule_link.php?LinkID=$next_link_id\">Next Link</a>";
} else {
    // Last link of the day
    $next_day_time_t = $link_time_t + (24 * 60 * 60);
    $next_day_year = date('Y', $next_day_time_t);
    $next_day_month = date('m', $next_day_time_t);
    $next_day_day = date('j', $next_day_time_t);
    echo "<a href=\"admin_driver_links.php?Year=$next_day_year&Month=$next_day_month&Day=$next_day_day\">NEXT DAY</a>  ";
}

if ($next_link_no_driver_id || $prev_link_no_driver_id) {
    echo "</p><p>";
    if ($prev_link_no_driver_id) { 
        echo "<a href=\"admin_schedule_link.php?LinkID=$prev_link_no_driver_id\">Previous Link Without Driver</a>  ";
        echo str_repeat('&nbsp;', 10);
    }

    if ($next_link_no_driver_id) { 
        echo "<a href=\"admin_schedule_link.php?LinkID=$next_link_no_driver_id\">Next Link Without Driver</a>  ";
    }
}

?>
</p>
<?php 
if ($link['AssignedDriverUserID']) { 
?>
<form method="POST" action="">
<br /><input type="submit" name="RemoveDriver" value="Remove Driver" />
</form>
<?php
} 
?>


<h2>Available Drivers</h2>
<form method="POST" action="">
<table border="1">
    <tr>
        <th>Assign</th>
        <th>Driver ID</th>
        <th>Name</th>
        <th>Phone # (12 to 3)</th>
        <th>Total Weekly Hours</th>
        
        <th>City</th>
        <th>State</th>
        <th>ZIP</th>
        <th>Preferences</th>
        <th>Rider/Driver Match</th>
        <th>Rider/Driver Pref</th>
        
        <th>Room for # Pass</th>
        <th>Time from home to this loc</th>
    </tr>
    <?php
	$maxed_out = array();
    if (count($scheduled_drivers) != 0) {
        echo '<tr><th colspan="17" style="background-color: #CCC">Already driving this day</th></tr>';

        foreach ($scheduled_drivers as $driver_id => $links) {
            if (driver_is_available($driver_id, $departure_time_info, $arrival_time_info)) {
				if(!driver_reached_max_availability($driver_id, $link_date)) {
                	display_schedule_link_driver_entry($driver_id);
				} else {
					$maxed_out[] = $driver_id;
				}
            }
        }
    }

    if (count($unscheduled_drivers) != 0) {
        echo '<tr><th colspan="17" style="background-color: #CCC">Other available drivers</th></tr>';

        foreach ($unscheduled_drivers as $driver_id) {
            if(!driver_reached_max_availability($driver_id, $link_date))
                	display_schedule_link_driver_entry($driver_id);
				else
					$maxed_out[] = $driver_id;
        }
    }
	if (count($maxed_out) != 0) {
		echo '<tr><th colspan="17" style="background-color: #CCC">Availability limit hit drivers</th></tr>';
		foreach($maxed_out as $driver_id){
			display_schedule_link_driver_entry($driver_id);
		}
	}
	

    if (((count($scheduled_drivers) == 0 && count($unscheduled_drivers) == 0) &&
            count($all_unscheduled_drivers) != 0)
          || isset($_REQUEST['ShowAllDrivers'])) {

        if (isset($_REQUEST['ShowAllDrivers'])) {
            $all_vacationing_drivers = array();
            $all_unscheduled_drivers = array_merge(array_keys($scheduled_drivers), $all_unscheduled_drivers);
            echo '<tr><th colspan="17">ALL DRIVERS (Not on Vacation)</th></tr>';
        } else {
            echo '<tr><th colspan="17">Drivers Not Available</th></tr>';
        }
				$sql = "select driver.UserID as `id` from driver, users, person_name 
					where driver.UserID = users.UserID and users.PersonNameID = person_name.PersonNameID
						and driver.UserID in (".join(',',$all_unscheduled_drivers).")
						order by person_name.LastName, person_name.Firstname";
				
				$srs = mysql_query($sql);
				$sorted_unscheduled_drivers = array();
				while($rs = mysql_fetch_array($srs)) $sorted_unscheduled_drivers[] = $rs["id"];
				$all_unscheduled_drivers = $sorted_unscheduled_drivers;
        foreach ($all_unscheduled_drivers as $driver_id) {
            if (!is_numeric($driver_id)) { 
                continue;
            }

            if (isset($_REQUEST['ShowAllDrivers'])) {
                // Check to see if the driver is on vacation
                $vacations = get_driver_vacations($driver_id, $departure_time_info['mysql'], 
                                                              $arrival_time_info['mysql']);
                $vacation = (count($vacations) > 0);
                if ($vacation) {
                    $all_vacationing_drivers[] = $driver_id;
                    continue;
                }
            }

            display_schedule_link_driver_entry($driver_id);
        }

        if (isset($_REQUEST['ShowAllDrivers']) && count($all_vacationing_drivers)) {
            echo '<tr><th colspan="17">Drivers ON VACATION</th></tr>';
            foreach ($all_vacationing_drivers as $driver_id) {
                display_schedule_link_driver_entry($driver_id);
            }
        }
    } ?>
    
</table>

<table cellspacing="1" border="1" style="margin-top:20px;">
<tr>
  <td>CH 	Need Help to Car </td>
  <td>C 	Has Cane </td>
  <td>HV 	High Vehicle</td>
  <td>CG 	Caregiver* </td>
</tr>
<tr>
  <td>PH 	Package Help </td>
  <td>W 	Has Walker </td>
  <td>HV 	High Vehicle </td>
  <td>NFD 	No Felon Driver</td>
</tr>
<tr>
  <td>PS 	Passenger Side only</td>
  <td>SS 	Smell Sensitivity</td>
  <td>MV 	Medium Vehicle</td>
  <td></td>
</tr>
<tr>
  <td>DS 	Driver Side only</td>
  <td>PU 	Perfume User</td>
  <td>LV 	Low Vehicle</td>
  <td></td>
</tr>
</table>




<p>
<a href="http://<?php echo $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . 
                           '&ShowAllDrivers' ?>">Show All Drivers</a>
</p>


</form>
<?php
	include_once 'include/footer.php';



function get_driver_scheduled_minutes_on_date($driver_id, $date) {
    $day_links = get_driver_links_by_date($driver_id, $date);
    if ($day_links) {
        $first_link = $day_links[0];
        $last_link = $day_links[count($day_links) - 1];

        
        $start_time_info = get_link_departure_time($first_link);
        $finish_time_info = get_link_arrival_time($last_link);

        $minutes = ceil(($finish_time_info['time_t'] - $start_time_info['time_t']) / 60);
    } else {
        $minutes = 0;
    }
    return $minutes;
}


function display_schedule_link_driver_entry($driver_id) {
    global $link_date;

    $driver_info = get_driver_person_info($driver_id, TRUE); 
    $driver_mobiles = get_user_phone_numbers_type($driver_info['UserID'], 'MOBILE'); 
    $driver_settings = get_driver_settings_by_driver_id($driver_id);
    $driver_hours_today = get_driver_daily_availability( $driver_id, $link_date );
	$day_of_week = date("w");
	$driver_scheduled_today = get_driver_hours_worked($driver_id, date("Y-n-j", strtotime(date("Y-n-j") . " -$day_of_week days")), date("Y-n-j",strtotime(date("Y-n-j") . " +" . (6 - $day_of_week) . " days")));
    ?>

    <tr>
        <td><input type="submit" name="Assign[<?php echo $driver_id ?>]" value="Assign" /></td>
        <td><?php 
            echo '<a id="' . $driver_id . '" class="User_Redirect"href="manifest.php?date=' . urlencode($link_date) . '">' . 
                                        $driver_id . '</a>'; ?></td>
        <td nowrap="nowrap"><a id="<?php echo $driver_id; ?>" class="User_Redirect" href="account.php"><?php
            echo "{$driver_info['Title']} {$driver_info['FirstName']} {$driver_info['MiddleInitial']}" .
                  (($driver_info['MiddleInitial'] == '') ? '' : '. ') . 
                  "{$driver_info['LastName']} {$driver_info['Suffix']}"; 
        ?></a></td>
        <td nowrap><?php 
            if (count($driver_mobiles)) {
                foreach ($driver_mobiles as $num) {
                    echo $num['PhoneNumber'] . '<br />';
                    echo $num['Ext'] != '' ? 'x'.$num['Ext'] : '';
                }
            } else {
                echo "None Found!";
            }
        ?></td>
      
        <td><?php printf("%.1f", $driver_scheduled_today); ?></td>
        <td><?php echo $driver_info['City']; ?></td>
        <td><?php echo $driver_info['State']; ?></td>
        <td><?php echo $driver_info['ZIP5']; ?></td>
        <td><?php echo printDriverSettings( $driver_settings); ?></td>
        <td><?php
        //print_r($_REQUEST);
		$link = get_link($_REQUEST['LinkID']);
	
	echo driverRiderMatch($link['RiderUserID'], $driver_id);
		
		?></td>
                <td><?php echo rdm_match($link['RiderUserID'], $driver_id); ?></td>
        
        <td align="center"><?php echo primaryVehicleSeats($driver_id); ?></td>
        
        <td>TBD</td>
    </tr>
	
<?php
}




function printDriverSettings($settings) {
    $string_refs = array(
        'FelonRiderOK' => array('No' => 'NFR'),
        'StayWithRider' => array('Yes' => '' /*'Prefers Driver to Stay' Removed at Martin's request Jun 1 2010*/),
        'WillHelpWithPackage' => array('Yes' => 'W'),
        'WillHelpWithPackage' => array('Yes' => 'C'),
        'WillHelpWithPackage' => array('Yes' => 'PH'),
        'WillHelpToCar' => array('Yes' => 'CH'),
        'EnterDriverSide' => array('Yes' => 'DS'),
        'EnterPassengerSide' => array('Yes' => 'PS'),
        'EnterBoth' => array('OVERRIDE' => '' /*'Both Sides OK' UNNECESSARY*/),
        'HasCaretaker' => array('Yes' => 'CG'),
        'SensitiveToSmells' => array('Yes' => 'SS'),
        'SmokerOrPerfumeUser' => array('Yes' => 'PU')
    );
	
	$return_str = '';
	foreach($settings as $setting_name=>$setting_value) {
	  //$return_str .= $setting_name.':'.$setting_value;
	  foreach($string_refs as $ref_name=>$ref_value) {
	    //$return_str .= '-'.$ref_name.':'.$ref_value.'--\n<br />';
	    if (($setting_name==$ref_name) && (isset($ref_value[$setting_value]) && ($ref_value[$setting_value]!=''))) {
		  $return_str .= ($return_str=='') ? $ref_value[$setting_value] : ', '.$ref_value[$setting_value];
		}
	  }
	}
	return $return_str; 
}


function driverRiderMatch($riderid, $driverid) {

  $rider_query = "SELECT r.*, rp.*, u.HasFelony from rider r, rider_preferences rp, users u where rp.UserID=r.UserID and r.RiderStatus='Active' and r.UserID='".(int)$riderid."' and u.UserID=r.UserID";
  $rider_result = mysql_query($rider_query);
  $driver_query = "SELECT d.*, ds.*, u.HasFelony, v.* FROM driver d, driver_settings ds, users u, vehicle v, vehicle_driver vd WHERE d.UserID=ds.UserID and d.DriverStatus='Active' and d.UserID='".(int)$driverid."' and u.UserID=d.UserID and vd.UserID=u.UserID and v.VehicleID=vd.VehicleID";
  $driver_result = mysql_query($driver_query);
  
  $rider = mysql_fetch_assoc($rider_result);
  $driver = mysql_fetch_assoc($driver_result);
  
  $bad_match = false;
  $bad_match_desc = '';
  
  if (($rider['FelonDriverOK']=='No') && ($driver['HasFelony']=='Yes')) {
	$bad_match = true;
	$bad_match_desc .= ' NFD,';
  }
  if (($driver['FelonRiderOK']=='No') && ($rider['HasFelony']=='Yes')) {
    $bad_match = true;
	$bad_match_desc .=  ' NFR,';
  }
  
  if (($rider['NeedsHelpToCar']=='Yes') && ($driver['WillHelpToCar']=='No')) {
    $bad_match = true;
	$bad_match_desc .= ' CH,';
  }
  
  if (($rider['NeedsHelpWithPackage']=='Yes') && ($driver['WillHelpWithPackage']=='No')) {
    $bad_match = true;
	$bad_match_desc .= ' PH,';
  }
  
  if (($rider['HasWalker']=='Yes') && ($driver['CanHandleWalker']=='No')) {
    $bad_match = true;
	$bad_match_desc .= ' W,';
  }
  
  if (($rider['HasCane']=='Yes') && ($driver['CanHandleCane']=='No')) {
    $bad_match = true;
	$bad_match_desc .= ' C,';
  }
  
  if (($rider['EnterDriverSide']=='Yes') && ($driver['HasDriverSideRearDoor']=='No')) {
    $bad_match = true;
	$bad_match_desc .= ' DS,';

  }
  
  if (($rider['EnterPassengerSide']=='Yes') && ($driver['HasPassengerSideRearDoor']=='No')) {
    $bad_match = true;
	$bad_match_desc .= ' PS,';
  }
  
  
  
  
  
  if ($bad_match) {
    $bad_match_desc = '<span style="background-color:#ff0000;">'.$bad_match_desc;
  } else {
    $bad_match_desc = '<span style="">OK';
  }
  
  $bad_match_desc .= '</span>';
  
  
  return $bad_match_desc;
  /*

<>C		C
<>W		W
<>PS door	PS
<>DS door	DS
*/
}

function primaryVehicleSeats($driver_id) {
  $sql = "select v.MaxPassengers from vehicle_driver vd, vehicle v where vd.UserID='".(int)$driver_id."' and vd.VehicleID=v.VehicleID and vd.isPrimary='Yes'";
  $result = mysql_query($sql);
  if (mysql_num_rows($result)>0) {
    $row = mysql_fetch_assoc($result);
	return ($row['MaxPassengers']=='') ? 'N/A' : $row['MaxPassengers'];
  } else {
     $sql2 = "select v.MaxPassengers from vehicle_driver vd, vehicle v where vd.UserID='".(int)$driver_id."' and vd.VehicleID=v.VehicleID";
     $result2 = mysql_query($sql2);
	 if (mysql_num_rows($result)>0) {
	   $row = mysql_fetch_assoc($result2);
	   return ($row['MaxPassengers']=='') ? 'N/A' : $row['MaxPassengers'];
	 } else {
	   return 0;
	 }
  }
}

?>
