<?php

/* Collect the proper DB information so it can be displayed on the screen */

	include_once 'include/database.php';
	include_once 'include/user.php';
	include_once 'include/date_time.php';
	include_once 'include/link.php';
	include_once 'include/rider.php';
	include_once 'include/destinations.php';
	include_once 'include/name.php';

// CONFIRM CLUB

	redirect_if_not_logged_in();
	$franchise = get_current_user_franchise();
	
// ADMIN ONLY (Full or Club)CAN view report information
	
	if(current_user_has_role(1, "FullAdmin") || current_user_has_role($franchise, "Franchisee")){
		header("Location: home.php");
		die();	
	}
	
//DATE RANGE being reviewed, start with today  *** CHANGE TO CURRENT MONTH	
	
	
	$dates = array('From' => date("Y-m-d"),
					   'To' => date("Y-m-d") . " 23:59:59" );
	if(!isset($_REQUEST['by'])) $_REQUEST['by'] = 'range';
	
	if(isset($_POST['FromMonth'])){
		$to = mysql_real_escape_string($_POST['ToYear']) . '-' . mysql_real_escape_string($_POST['ToMonth']) . '-' . mysql_real_escape_string($_POST['ToDay']);
		$from = mysql_real_escape_string($_POST['FromYear']) . '-' . mysql_real_escape_string($_POST['FromMonth']) . '-' . mysql_real_escape_string($_POST['FromDay']);
		
		$dates = array('From' => $from,
					   'To' => $to . " 23:59:59" );
		
	} else if(isset($_REQUEST['Year']) && !isset($_REQUEST['Month']) && !isset($_REQUEST['Day'])){
		$year = mysql_real_escape_string($_REQUEST['Year']);
		
		$dates = array('From' => "$year-1-1",
					   'To' => "$year-12-31 23:59:59" );
					   
	} else if(isset($_REQUEST['Year']) && isset($_REQUEST['Month']) && !isset($_REQUEST['Day'])){
		$year = mysql_real_escape_string($_REQUEST['Year']);
		$month = mysql_real_escape_string($_REQUEST['Month']);
		$days = get_days_in_month($month,$year);
		
		$dates = array('From' => "$year-$month-1",
					   'To' => "$year-$month-$days 23:59:59" );
		
	} else if(isset($_REQUEST['Year']) && isset($_REQUEST['Month']) && isset($_REQUEST['Day'])){
		$year = mysql_real_escape_string($_REQUEST['Year']);
		$month = mysql_real_escape_string($_REQUEST['Month']);
		$day = mysql_real_escape_string($_REQUEST['Day']);
		$timestamp = mktime(0,0,0,$month,$day,$year);
		$day_of_week = date("w",$timestamp);

		$start_day = $day - $day_of_week;
		$end_day = $start_day + 6;
		
		$start_month = $month;
		$end_month = $month;
		
		$start_year = $year;
		$end_year = $year;
		
		if($start_day <= 0){
			$start_month--;
			$start_day = get_days_in_month($start_month,$start_year) + $start_day;
			if($start_month <= 0){
				$start_year--;
				$start_month = 12;
			}
		}
		if($end_day > get_days_in_month($end_month,$end_year)){
			$days = ($end_day - get_days_in_month($end_month,$end_year));
			$end_month++;
			$end_day = $days;
			if($end_month > 12){
				$end_month = 1;
				$end_year++;
			}
		}
		$dates = array('From' => "$start_year-$start_month-$start_day",
					   'To' => "$end_year-$end_month-$end_day 23:59:59" );
	}
	$results = get_rider_report_numbers($franchise, $dates['From'],$dates['To']);
	$months = array('January','February','March','April','May','June','July','August','September','October','November','December');
    include_once 'include/header.php';
?>
<center><h2>Ride Reports</h2></center>
<br>
By:<a href="reports.php?by=range">Range</a>|<a href="reports.php?by=week">Week </a>|<a href="reports.php?by=month">Month</a>|<a href="reports.php?by=year">Year</a>| Or <a href="reports.php">View All</a>
<br>
<br>
<form action="<?php echo $_SERVER['PHP_SELF'] . '?by=' . $_REQUEST['by']; ?>"method="post">
	<?php if($_REQUEST['by'] == 'range'){ ?>
	From: <select name="FromMonth">
		<?php
				$months = array('January','February','March','April','May','June','July','August','September','October','November','December');
				
				foreach($months as $k => $v){
					echo "<option value=\"" . ($k + 1) . "\"";
					
					if((isset($_POST['FromMonth']) && $_POST['FromMonth'] == ($k + 1)) || (!isset($_POST['FromMonth']) && date("n") == ($k + 1)))
						echo ' SELECTED';
					echo ">$v</option>";
				}
			?>


	</select> / <select name="FromDay">
		<?php
			for($i = 1; $i <= 31; $i++){
				echo '<option value="' . $i . '"';
				if((isset($_POST['FromDay']) && $_POST['FromDay'] == $i) || (!isset($_POST['FromDay']) && date("d") == $i))
					echo 'SELECTED';
				echo '>' . $i . '</option>';
			}
		?>
	</select> / <select name="FromYear">
		<?php
			for($i = (int)date("Y"); $i >= (int)date("Y") - 5; $i--){
				echo '<option value="' . $i . '"';
				if((isset($_POST['FromYear']) && $_POST['FromYear'] == $i) || (!isset($_POST['FromYear']) && date("d") == $i))
					echo 'SELECTED';
				echo '>' . $i . '</option>';
			}
		?>
	</select> To: <select name="ToMonth">
		<?php
				$months = array('January','February','March','April','May','June','July','August','September','October','November','December');
				
				foreach($months as $k => $v){
					echo "<option value=\"" . ($k + 1) . "\"";
					
					if((isset($_POST['ToMonth']) && $_POST['ToMonth'] == ($k + 1)) || (!isset($_POST['ToMonth']) && date("n") == ($k + 1)))
						echo ' SELECTED';
					echo ">$v</option>";
				}
			?>


	</select> / <select name="ToDay">
		<?php
			for($i = 1; $i <= 31; $i++){
				echo '<option value="' . $i . '"';
				if((isset($_POST['ToDay']) && $_POST['ToDay'] == $i) || (!isset($_POST['ToDay']) && date("d") == $i))
					echo 'SELECTED';
				echo '>' . $i . '</option>';
			}
		?>
	</select> / <select name="ToYear">
		<?php
			for($i = (int)date("Y"); $i >= (int)date("Y") - 5; $i--){
				echo '<option value="' . $i . '"';
				if((isset($_POST['ToYear']) && $_POST['ToYear'] == $i) || (!isset($_POST['ToYear']) && date("d") == $i))
					echo 'SELECTED';
				echo '>' . $i . '</option>';
			}
		?>
	</select>
	<?php } else if($_REQUEST['by'] == 'year'){ ?>
	Year: <select name="Year">
		<?php
				for($i = 2000; $i <= date("Y") + 1; $i++){ ?>
		<option value="<?php echo $i; ?>"
			<?php if((isset($_REQUEST['Year']) && $i == $_REQUEST['Year']) || (!isset($_REQUEST['Year']) && $i == date("Y"))) echo "SELECTED"; ?>>
			<?php echo $i; ?>
		</option>
		<?php
				}
			?>
	</select>

	<?php } else if($_REQUEST['by'] == 'month'){ ?>
	Month: <select name="Month">
		<?php
				for($i = 1; $i <= 12; $i++){ ?>
		<option value="<?php echo $i; ?>"
			<?php if((isset($_REQUEST['Month']) && $i == $_REQUEST['Month']) || (!isset($_REQUEST['Month']) && $i == date("n"))) echo "SELECTED"; ?>>
			<?php echo $months[$i - 1]; ?>
		</option>
		<?php
				}
			?>
	</select> Year: <select name="Year">
		<?php
				for($i = 2000; $i <= date("Y") + 1; $i++){ ?>
		<option value="<?php echo $i; ?>"
			<?php if((isset($_REQUEST['Year']) && $i == $_REQUEST['Year']) || (!isset($_REQUEST['Year']) && $i == date("Y"))) echo "SELECTED"; ?>>
			<?php echo $i; ?>
		</option>
		<?php
				}
			?>
	</select>
	<?php } else if($_REQUEST['by'] == 'week'){ ?>
	Please pick day somewhere in the week you would like to see:<br> <select
		name="Month">
		<?php
				for($i = 1; $i <= 12; $i++){ ?>
		<option value="<?php echo $i; ?>"
			<?php if((isset($_REQUEST['Month']) && $i == $_REQUEST['Month']) || (!isset($_REQUEST['Month']) && $i == date("n"))) echo "SELECTED"; ?>>
			<?php echo $months[$i - 1]; ?>
		</option>
		<?php
				}
			?>
	</select> / <select name="Day">
		<?php
				for($i = 1; $i <= 31; $i++){ ?>
		<option value="<?php echo $i; ?>"
			<?php if((isset($_REQUEST['Day']) && $i == $_REQUEST['Day']) || (!isset($_REQUEST['Day']) && $i == date("j"))) echo "SELECTED"; ?>>
			<?php echo $i; ?>
		</option>
		<?php
				}
			?>
	</select> / <select name="Year">
		<?php
				for($i = 2000; $i <= date("Y") + 1; $i++){ ?>
		<option value="<?php echo $i; ?>"
			<?php if((isset($_REQUEST['Year']) && $i == $_REQUEST['Year']) || (!isset($_REQUEST['Year']) && $i == date("Y"))) echo "SELECTED"; ?>>
			<?php echo $i; ?>
		</option>
		<?php
				}
			?>
	</select>
	<?php } ?>
	<input type="submit" value="Find" style="clear: both;">
	<button type="button" id="ShowAll" style="float: right;">Show
		All</button>
	<button type="button" id="HideAll" style="float: right;">Hide
		All</button>


</form>
<br>
<center>
	<span style="font-size: 1.3em;"> <?php
			$to = get_date($dates['To']);
			$from = get_date($dates['From']);
			if($_REQUEST['by'] == 'range' || $_REQUEST['by'] == 'week')

				echo "{$from['Month']}/{$from['Day']}/{$from['Year']} - {$to['Month']}/{$to['Day']}/{$to['Year']}";
		?>
	</span>
</center>
<!--START new layout -->




<?php

/*THIS MAY BE WHERE THE CUSTOM TRANSITION TYPE NEEDS TO BE FILTERED <> DRIVER WHEN LOOKING AT 
 LINK_HISTORY, FORMULA IN LINK.PHP  */


	$links = get_number_of_links($franchise, $dates['From'],$dates['To']);
	$distance = get_distance_of_links($franchise, $dates['From'],$dates['To']);
	$revenue = get_revenue_of_links($franchise, $dates['From'],$dates['To']);

	$transition = get_transition_data($franchise, $dates['From'], $dates['To']);
	//print_r($transition);
?>

<!-- TABLE 1: Ride Information -->


<button type="button" id="ride" style="float: right;">Show Data</button>
<table border="1" width="870px">
	<tr>
		<td></td>
		<th>Completed</th>
		<th>Scheduled</th>
		<th>Canceled Late</th>
		<th>Sub-Totals</th>
		<th>Canceled Early</th>
		<th>Not Scheduled</th>
		<th>Driver No Show</th>
		<th>Weather Cancel</th>
		<th>Dest. Cancel</th>
		<!-- <td width="110px"></td> No Longer need a spacer for visual effect -->
		<th>Transition</th>
	</tr>

	<!-- Identify value of contents in first row -->

	<tr>
		<th># Links</th>
		<td><?php echo $links['COMPLETE']; ?></td>
		<td><?php echo $links['SCHEDULED']; ?></td>
		<td><?php echo $links['CANCELEDLATE']; ?></td>
		<td><?php echo $links['SCHEDULED'] + $links['COMPLETE'] +  $links['CANCELEDLATE']; ?></td>
		<td><?php echo $links['CANCELEDEARLY']; ?></td>
		<td><?php echo $links['NOTSCHEDULED']; ?></td>
		<td><?php echo $links['DRIVERNOSHOW']; ?></td>
		<td><?php echo $links['WEATHERCANCEL']; ?></td>
		<td><?php echo $links['DESTINATIONCANCEL']; ?></td>
		<td><?php echo $transition['Links']; ?></td>
	</tr>

	<!-- Identify value of contents in second row -->

	<tr>
		<th># Miles</th>
		<td><?php echo $distance['COMPLETE']; ?></td>
		<td><?php echo $distance['SCHEDULED']; ?></td>
		<td><?php echo $distance['CANCELEDLATE']; ?></td>
		<td><?php echo $distance['SCHEDULED'] + $distance['COMPLETE'] + $distance['CANCELEDLATE']; ?></td>
		<td><?php echo $distance['CANCELEDEARLY']; ?></td>
		<td><?php echo $distance['NOTSCHEDULED']; ?></td>
		<td><?php echo $distance['DRIVERNOSHOW']; ?></td>
		<td><?php echo $distance['WEATHERCANCEL']; ?></td>
		<td><?php echo $distance['DESTINATIONCANCEL']; ?></td>
		<td><?php echo $transition['Miles']; ?></td>
	</tr>

	<!-- Identify value of contents in third row formula) -->

	<tr>
		<th>Miles / Link</th>
		<td><?php print_division($distance['COMPLETE'], $links['COMPLETE'], 2); ?></td>
		<td><?php print_division($distance['SCHEDULED'], $links['SCHEDULED'], 2); ?></td>
		<td><?php print_division($distance['CANCELEDLATE'], $links['CANCELEDLATE'], 2); ?></td>
		<td><?php print_division($distance['SCHEDULED'] + $distance['COMPLETE'] + $distance['CANCELEDLATE'], 
                                     $links['SCHEDULED'] + $links['COMPLETE'] + $links['CANCELEDLATE'], 2); ?></td>
		<td><?php print_division($distance['CANCELEDEARLY'], $links['CANCELEDEARLY'], 2); ?></td>
		<td><?php print_division($distance['NOTSCHEDULED'], $links['NOTSCHEDULED'], 2); ?></td>
		<td><?php print_division($distance['DRIVERNOSHOW'], $links['DRIVERNOSHOW'], 2); ?></td>
		<td><?php print_division($distance['WEATHERCANCEL'], $links['WEATHERCANCEL'], 2); ?></td>
		<td><?php print_division($distance['DESTINATIONCANCEL'], $links['DESTINATIONCANCEL'], 2); ?></td>
		<td><?php print_division($transition['Miles'], $transition['Links'], 2); ?></td>
	</tr>
</table>

<!--HIDDEN TABLE 1: Support Ride Information-->

<br />
<table border="1" width="850px" id="ride_collapse">
	<tr>
		<!-- build a single row for the headers -->
		<th>Arrival Status</th>
		<th>Arrival Date</th>
		<th>UserID</th>
		<th>Name</th>
		<th>Destination</th>
		<th>Fee</th>
		<th>Mileage</th>
		<th>Driver</th>
		<th>Custom Trans Type</th> <!--Add the Transition Type to insure proper math-->
	</tr>

	<!--determine date range for hidden table-->

	<?php
        if ($dates['From'] && $dates['To']) {
            $date_clause = "DesiredArrivalTime BETWEEN '" . mysql_real_escape_string($dates['From']) . 
                                    "' AND '" . mysql_real_escape_string($dates['To']) . "' ";
        } elseif ($dates['From']) {
            $date_clause = "DesiredArrivalTime >= '" . mysql_real_escape_string($dates['From']) . "' "; 
        } elseif ($dates['To']) {
            $date_clause = "DesiredArrivalTime <= '" . mysql_real_escape_string($dates['To']) . "' "; 
        } else {
            $date_clause = '1=1 ';
        }
		
//show requested information within date range

		
		
		$safe_franchise = mysql_real_escape_string($franchise);
		$sql = "SELECT link.FranchiseID, 'SCHEDULED' AS LinkStatus, DesiredArrivalTime, RiderUserID, ToDestinationID, QuotedCents, 
                       Distance, AssignedDriverUserID AS DriverUserID,
                       users.PersonNameID, Title, FirstName, MiddleInitial, LastName, Suffix,
                       destination.Name
                       FROM link LEFT JOIN (users NATURAL JOIN person_name) ON RiderUserID = UserID 
                                 LEFT JOIN destination ON ToDestinationID = destination.DestinationID
                       WHERE $date_clause AND link.FranchiseID = $safe_franchise 
                UNION 
                SELECT link_history.FranchiseID, LinkStatus, DesiredArrivalTime, RiderUserID, ToDestinationID, QuotedCents, Distance, DriverUserID,
                       users.PersonNameID, Title, FirstName, MiddleInitial, LastName, Suffix,
                       destination.Name
                       FROM link_history LEFT JOIN (users NATURAL JOIN person_name) ON RiderUserID = UserID 
                                         LEFT JOIN destination ON ToDestinationID = destination.DestinationID
                       WHERE $date_clause AND link_history.FranchiseID = $safe_franchise 
                       ORDER BY DesiredArrivalTime";
		$result = mysql_query($sql);
        if (!$result) {
            rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                            "Report query failed", $sql);
        }
		
		while ($row = mysql_fetch_array($result)) {
            $user_id = $row['RiderUserID'];
            echo "<tr>";
			echo "<td>{$row['LinkStatus']}</td>";	/* Column 1 = Status */
			echo "<td>{$row['DesiredArrivalTime']}</td>";  /* C2 = Arrival Date */
			echo "<td><a href=\"account.php\" class=\"User_Redirect\" id=\"$user_id\">$user_id</a></td>";
			/* C3 = Rider User ID */			
			echo "<td>" . get_displayable_person_name_string($row) . "</td>";
			/* C4 = Rider Name */
			echo "<td>{$row['Name']}</td>"; /* C5 = destination */
			echo "<td>";
			printf("$%d.%02.2d", $row['QuotedCents'] / 100, $row['QuotedCents'] % 100);
			echo "</td>";    /* C6 = Fee Paid for Link */
			echo "<td>{$row['Distance']}</td>";  /* C7 = Mileage */
			$driver = $row['DriverUserID'] ? get_displayable_person_name_string( get_user_person_name( $row['DriverUserID'] )) : 'Not Set';
			echo "<td>" . $driver . "</td>";     /* C8 = Driver */
            /*$CTT = $row['CustomTransitionType'] */
            /*echo "<td>" . $CTT . "</td>";      C9 =  Custom Transition Type for this event */
            echo "</tr>";
            $total_distance += $row['Distance'];
            $total_cents += $row['QuotedCents'];
		}
		echo '<tr>';
			echo '<th colspan="5" class="alignright">Totals</th>';
			echo "<td>";
			printf("$%d.%02.2d", $total_cents / 100, $total_cents % 100);
			echo "</td>";
			echo "<td>$total_distance</td>";
			echo "<td></td>";
		echo '</tr>';		
	?>
</table>
<!-- End Hidden Table 1 

TABLE 2: Rider Information -->

<?php 
	$riders = get_number_of_paying_riders($franchise, $dates['From'],$dates['To']); 

	//THIS FORMULA WORKS FOR NEW RIDERS, FORMULA FOR HIDDEN TABLE IS WRONG
	
	$new_riders = get_number_of_new_riders($franchise, $dates['From'], $dates['To']);
?>
<br />
<button type="button" id="rider" style="float: right;">Show
	Data</button>
<table border="1" width="800px">
	<tr>
		<td></td>
		<th>Completed</th>
		<th>Scheduled</th>
		<th>Canceled Late</th>
		<th>Unique Totals</th>
		<th>Canceled Early</th>
		<th>Not Scheduled</th>
		<th>Driver No Show</th>
		<th>Weather Cancel</th>
		<th>Dest. Cancel</th>
	</tr>

	<!-- Name the first row and present information -->

	<tr>
		<th>Unique Riders</th>
		<td><?php echo $riders['COMPLETE']; ?></td>
		<td><?php echo $riders['SCHEDULED']; ?></td>
		<td><?php echo $riders['CANCELEDLATE']; ?></td>
		<td><?php echo $riders['Total']; ?></td>
		<td><?php echo $riders['CANCELEDEARLY']; ?></td>
		<td><?php echo $riders['NOTSCHEDULED']; ?></td>
		<td><?php echo $riders['DRIVERNOSHOW']; ?></td>
		<td><?php echo $riders['WEATHERCANCEL']; ?></td>
		<td><?php echo $riders['DESTINATIONCANCEL']; ?></td>
	</tr>

	<!-- Name the second row and present information-->

	<tr>
		<th>New Riders</th>
		<td><?php echo $new_riders['COMPLETE']; ?></td>
		<td><?php echo $new_riders['SCHEDULED']; ?></td>
		<td><?php echo $new_riders['CANCELEDLATE']; ?></td>
		<td><?php echo $new_riders['Total']; ?></td>
		<td><?php echo $new_riders['CANCELEDEARLY']; ?></td>
		<td><?php echo $new_riders['NOTSCHEDULED']; ?></td>
		<td><?php echo $new_riders['DRIVERNOSHOW']; ?></td>
		<td><?php echo $new_riders['WEATHERCANCEL']; ?></td>
		<td><?php echo $new_riders['DESTINATIONCANCEL']; ?></td>
	</tr>
</table>

<!-- HIDDEN TABLE 2: RIder Information -->

<br />
<table border="1" width="850px" id="rider_collapse"> <!-- Tim Change to 850px from 750 -->

	<tr>
		<!-- This is the Header titles -->
		<th>UserID</th>
		<th>Name</th>
		<th>Links</th>
		<th>Time Frame Mileage</th>
		<th>Credit Spent</th>
		<th>First Ride</th>
	</tr>

	<!-- Collect information to post on 2nd HIDDEN Table -->

	<?php
        $sql = "SELECT RiderUserID, COUNT(*) AS Links, SUM(Distance) AS Distance, 
                                    SUM(QuotedCents) AS Cents, MIN(DesiredArrivalTime) AS FirstRangeArrivalTime,
                                    (SELECT MIN(DesiredArrivalTime) FROM link 
                                            WHERE RiderUserID = t1.RiderUserID) AS LinkMinArrivalTime,
                                    (SELECT MIN(DesiredArrivalTime) FROM link_history
                                            WHERE RiderUserID = t1.RiderUserID) AS LinkHistMinArrivalTime,
                                    users.PersonNameID, Title, FirstName, MiddleInitial, LastName, Suffix
                FROM ( SELECT RiderUserID, LinkStatus, DesiredArrivalTime, Distance, QuotedCents, FranchiseID FROM link
                       UNION
                       SELECT RiderUserID, LinkStatus, DesiredArrivalTime, Distance, QuotedCents, FranchiseID FROM link_history ) t1
                     LEFT JOIN (users NATURAL JOIN person_name) ON RiderUserID = UserID 
                WHERE $date_clause AND FranchiseID = $safe_franchise AND
                      LinkStatus IN ('COMPLETE', 'CANCELEDLATE', 'UNKNOWN')
                GROUP BY RiderUserID";

		$result = mysql_query($sql);
		
		// Test to see if program needs to escape before breaking
		

        if (!$result) {
            rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                            "Report query failed", $sql);
            die(mysql_error());
        }

    // reset variables to 0
        
		$total_cents = 0;
		$total_distance = 0;
		while($row = mysql_fetch_array($result)){
			$user_id = get_rider_user_id($row['RiderUserID']);
		echo '<tr>';
			echo "<td><a href=\"account.php\" class=\"User_Redirect\" id=\"$user_id\">$user_id</a></td>";
			$name = get_user_person_name( $user_id );
			echo "<td>" . get_displayable_person_name_string($name) . "</td>";
			echo "<td>{$row['Links']}</td>";
			echo "<td>{$row['Distance']}</td>";
			echo "<td>";
			printf("$%d.%02.2d", $row['Cents'] / 100, $row['Cents'] % 100);
			echo "</td>";
			
    //THIS FORMULA DOES NOT APPEAR CORRECT; SHOULD DETERMINE IF FIRST RIDE EVER FOR RIDER			

            if ($row['LinkMinArrivalTime'] == $row['FirstRangeArrivalTime'] ||
                $row['LinkHistMinArrivalTime'] == $row['FirstRangeArrivalTime']) {
                $is_new = 'Yes';
            } else {
                $is_new = 'No';
            }
			echo "<td>{$is_new}</td>";
		echo '</tr>';
		
		// do the math for the totals at the bottom of the table		
		
		$total_links += $row['Links'];
		$total_distance += $row['Distance'];
		$total_cents = $total_cents + $row['Cents'];
		}
		echo '<tr>';
		// rather than spanning 2 columns, leave first column blank so copies to Excel better	
			echo "<td></td>";		
			echo '<th class="alignright">Totals</th>';
			echo "<td>$total_links</td>";
			echo "<td>$total_distance</td>";
			echo "<td>";
			printf("$%d.%02.2d", $total_cents / 100, $total_cents % 100);
			echo "</td>";
			echo "<td></td>";
		echo '</tr>';
	?>
</table>

		<!-- End Hidden TABLE 2 TABLE 3: Driver Information--> 
		<!-- Collect info for Driver information Table -->

<?php
	$drivers = get_number_of_working_drivers($franchise, $dates['From'], $dates['To']);
	$new_drivers = get_number_of_new_drivers($franchise, $dates['From'], $dates['To']);

?>
<br />
<button type="button" id="driver" style="float: right;">Show
	Data</button>
<table border="1" width="800px">
	<tr>
		<td></td>
		<th>Completed</th>
		<th>Scheduled</th>
		<th>Canceled Late</th>
		<th>Sub-Totals</th>
		<th>Canceled Early</th>
		<th>Not Scheduled</th>
		<th>Driver No Show</th>
		<th>Weather Cancel</th>
		<th>Dest. Cancel</th>
	</tr>
	<tr>
		<th>Drivers Used</th>
		<td><?php echo $drivers['COMPLETE']; ?></td>
		<td><?php echo $drivers['SCHEDULED']; ?></td>
		<td><?php echo $drivers['CANCELEDLATE']; ?></td>
		<td><?php echo $drivers['Total']; ?></td>
		<td><?php echo $drivers['CANCELEDEARLY']; ?></td>
		<td><?php echo $drivers['NOTSCHEDULED']; ?></td>
		<td><?php echo $drivers['DRIVERNOSHOW']; ?></td>
		<td><?php echo $drivers['WEATHERCANCEL']; ?></td>
		<td><?php echo $drivers['DESTINATIONCANCEL']; ?></td>
	</tr>
	<tr>
		<th>New Drivers</th>
		<td><?php echo $new_drivers['COMPLETE']; ?></td>
		<td><?php echo $new_drivers['SCHEDULED']; ?></td>
		<td><?php echo $new_drivers['CANCELEDLATE']; ?></td>
		<td><?php echo $new_drivers['Total']; ?></td>
		<td><?php echo $new_drivers['CANCELEDEARLY']; ?></td>
		<td><?php echo $new_drivers['NOTSCHEDULED']; ?></td>
		<td><?php echo $new_drivers['DRIVERNOSHOW']; ?></td>
		<td><?php echo $new_drivers['WEATHERCANCEL']; ?></td>
		<td><?php echo $new_drivers['DESTINATIONCANCEL']; ?></td>
	</tr>

</table>

		<!-- end TABLE 3, begin HIDDEN TABLE 3 -->

<br />
<table border="1" width="850px" id="driver_collapse">
	<tr>
		<th>UserID</th>
		<th>Name</th>
		<th>New</th> 
		<!-- THIS FORMULA IS WRONG AND ALSO NEEDS TO BE BUILT IN THE driver.php FILE AS A FUNCTION. -->
		<!-- IDENTIFY UNIQUE DRIVERS (CURRENTLY LISTS ONE) AND DATE OF FIRST DRIVE EVER -->
		<!-- (CURRENTLY LISTS FIRST RIDE IN RANGE). Formula in TABLE 3 above gives expected results -->
		<?php
			$sql = "SELECT *, IF( (SELECT DesiredArrivalTime 
                                      FROM (SELECT DesiredArrivalTime, AssignedDriverUserID DriverUserID 
                                              FROM link 
                                            UNION 
                                            SELECT DesiredArrivalTime, DriverUserID 
                                                FROM link_history 
                                            ORDER BY DesiredArrivalTime) t3 
                                      WHERE DriverUserID = t1.DriverUserID LIMIT 1) BETWEEN 
                                            '{$dates['To']}' AND '{$dates['From']}', 'Yes','No') AS New 
                    FROM (SELECT FranchiseID, AssignedDriverUserID DriverUserID, DesiredArrivalTime 
                          FROM link 
                          UNION 
                          SELECT FranchiseID, DriverUserID, DesiredArrivalTime 
                          FROM link_history) t1 
                    WHERE DriverUserID IS NOT NULL AND $date_clause AND FranchiseID = $safe_franchise
                    GROUP BY DriverUserID";

			$result = mysql_query($sql);
            if (!$result) {
                rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                                "Report query failed", $sql);
                die(mysql_error());
            }

			while($row = mysql_fetch_row($result)){
				$user_id = get_driver_user_id($row[0]);
				$name = get_user_person_name( $user_id );
			echo '<tr>';
				echo "<td><a href=\"account.php\" class=\"User_Redirect\" id=\"$user_id\">$user_id </a></td>";
				echo '<td>' .  get_displayable_person_name_string($name) . "</td>";
				echo "<td>{$row['2']}</td>";
			echo '</tr>';
			}
		?>
	</tr>
</table>

		<!-- end HIDDEN TABLE 3 -->
		<!-- Begin TABLE 4 -->


<br />
<table border="1" width="800px">
	<tr>
		<td></td>
		<th>Completed</th>
		<th>Scheduled</th>
		<th>Canceled Late</th>
		<th>Sub-Totals</th>
		<th>Canceled Early</th>
		<th>Not Scheduled</th>
		<th>Driver No Show</th>
		<th>Weather Cancel</th>
		<th>Dest. Cancel</th>

	</tr>

	<tr>
		<th>Riders / Driver</th>
		<td><?php echo divide_items($riders['COMPLETE'], $drivers['COMPLETE']); ?></td>
		<td><?php echo divide_items($riders['SCHEDULED'], $drivers['SCHEDULED']); ?></td>
		<td><?php echo divide_items($riders['CANCELEDLATE'], $drivers['CANCELEDLATE']); ?></td>
		<td><?php echo divide_items($riders['COMPLETE'] + $riders['SCHEDULED'] + $riders['CANCELEDLATE'], $drivers['COMPLETE']  + $drivers['SCHEDULED'] + $drivers['CANCELEDLATE']); ?></td>
		<td><?php echo divide_items($riders['CANCELEDEARLY'], $drivers['CANCELEDEARLY']); ?></td>
		<td><?php echo divide_items($riders['NOTSCHEDULED'],$drivers['NOTSCHEDULED']); ?></td>
		<td><?php echo divide_items($riders['DRIVERNOSHOW'], $drivers['DRIVERNOSHOW']); ?></td>
		<td><?php echo divide_items($riders['WEATHERCANCEL'], $drivers['WEATHERCANCEL']); ?></td>
		<td><?php echo divide_items($riders['DESTINATIONCANCEL'], $drivers['DESTINATIONCANCEL']); ?></td>
	</tr>
</table>

		<!-- End TABLE 4 -->
		<!-- begin TABLE 5 – NOT SURE IF WE NEED TABLE 5.-->
		<!-- INFO CAN BE APPENDED TO BOTTOM OF TABLE 4 -->


<br />
<table border="1" width="800px">
	<tr>
		<td></td>
		<th>Completed</th>
		<th>Scheduled</th>
		<th>Canceled Late</th>
		<th>Sub-Totals</th>
		<th>Canceled Early</th>
		<th>Not Scheduled</th>
		<th>Driver No Show</th>
		<th>Weather Cancel</th>
		<th>Dest. Cancel</th>
	</tr>
	<tr>
	
		<?php
		function divide_items($first_item, $second_item){
			if($first_item == NULL || $second_item == NULL || $second_item <= 0)
				return "-----------";
			return round(number_format($first_item / $second_item, 2), 2);
		}
	
	?>
		<th>Links / Rider</th>
		<td><?php echo divide_items($links['COMPLETE'], $riders['COMPLETE']); ?></td>
		<td><?php echo divide_items($links['SCHEDULED'] , $riders['SCHEDULED']); ?></td>
		<td><?php echo divide_items($links['CANCELEDLATE'], $riders['CANCELEDLATE']); ?></td>
		<td><?php echo divide_items($links['SCHEDULED'] + $links['COMPLETE'] +  $links['CANCELEDLATE'], $riders['COMPLETE'] + $riders['SCHEDULED'] + $riders['CANCELEDLATE']); ?></td>
		<td><?php echo divide_items($links['CANCELEDEARLY'], $riders['CANCELEDEARLY']); ?></td>
		<td><?php echo divide_items($links['NOTSCHEDULED'], $riders['NOTSCHEDULED']); ?></td>
		<td><?php echo divide_items($links['DRIVERNOSHOW'], $riders['DRIVERNOSHOW']); ?></td>
		<td><?php echo divide_items($links['WEATHERCANCEL'], $riders['WEATHERCANCEL']); ?></td>
		<td><?php echo divide_items($links['DESTINATIONCANCEL'], $riders['DESTINATIONCANCEL']); ?></td>
	</tr>
	<tr>
		<th>Links / Driver</th>
		<td><?php echo divide_items($links['COMPLETE'], $drivers['COMPLETE']); ?></td>
		<td><?php echo divide_items($links['SCHEDULED'], $drivers['SCHEDULED']); ?></td>
		<td><?php echo divide_items($links['CANCELEDLATE'], $drivers['CANCELEDLATE']); ?></td>
		<td><?php echo divide_items($links['SCHEDULED'] + $links['COMPLETE'] +  $links['CANCELEDLATE'], $drivers['COMPLETE'] + $drivers['SCHEDULED'] + $drivers['CANCELEDLATE']); ?></td>
		<td><?php echo divide_items($links['CANCELEDEARLY'], $drivers['CANCELEDEARLY']); ?></td>
		<td><?php echo divide_items($links['NOTSCHEDULED'], $drivers['NOTSCHEDULED']); ?></td>
		<td><?php echo divide_items($links['DRIVERNOSHOW'], $drivers['DRIVERNOSHOW']); ?></td>
		<td><?php echo divide_items($links['WEATHERCANCEL'], $drivers['WEATHERCANCEL']); ?></td>
		<td><?php echo divide_items($links['DESTINATIONCANCEL'], $drivers['DESTINATIONCANCEL']); ?></td>
	</tr>
</table>

		<!-- end TABLE 5 -->
		<!-- begin TABLE 6 -->

<br />
<?php 
	function find_average($cents, $people){
		return $cents / ($people == 0 ? 1 : $people); 
	}
	function find_average_price($cents, $people){
		if($cents == 0 || $people == 0)
			echo '-----------';
		else
		 printf("$%d.%02.2d", find_average($cents,$people)/100, find_average($cents,$people)% 100);
	}
?>
<table border="1" width="870px">
	<tr>
		<td></td>
		<th>Completed</th>
		<th>Scheduled</th>
		<th>Canceled Late</th>
		<th>Sub-Totals</th>
		<th>Canceled Early</th>
		<th>Not Scheduled</th>
		<th>Driver No Show</th>
		<th>Weather Cancel</th> 
		<th>Dest. Cancel</th> 
		<th>Transition</th>
	</tr>
	<tr>
		<th>$ Revenue</th>
		<td><?php printf("$%d.%02.2d", $revenue['COMPLETE']/100, $revenue['COMPLETE'] % 100); ?></td>
		<td><?php printf("$%d.%02.2d", $revenue['SCHEDULED'] /100, $revenue['SCHEDULED'] % 100); ?></td>
		<td><?php printf("$%d.%02.2d", $revenue['CANCELEDLATE']/100, $revenue['CANCELEDLATE'] % 100); ?></td>
		<td><?php $total = $revenue['SCHEDULED'] + $revenue['COMPLETE'] + $revenue['CANCELEDLATE']; 
				printf("$%d.%02.2d", $total / 100, $total % 100);
				?>
		</td>
		<td><?php printf("$%d.%02.2d", $revenue['CANCELEDEARLY']/100, $revenue['CANCELEDEARLY'] % 100); ?></td>
		<td><?php printf("$%d.%02.2d", $revenue['NOTSCHEDULED']/100, $revenue['NOTSCHEDULED'] % 100); ?></td>
		<td><?php printf("$%d.%02.2d", $revenue['DRIVERNOSHOW']/100, $revenue['DRIVERNOSHOW'] % 100); ?></td>
		<td><?php printf("$%d.%02.2d", $revenue['WEATHERCANCEL']/100, $revenue['WEATHERCANCEL'] % 100); ?></td>
		<td><?php printf("$%d.%02.2d", $revenue['DESTINATIONCANCEL']/100, $revenue['DESTINATIONCANCEL'] % 100); ?></td>
		<td><?php printf("$%d.%02.2d", $transition['Revenue']/100, $transition['Revenue'] % 100); ?></td>
	</tr>
	<tr>
		<th>$ / Rider</th>
		<td><?php find_average_price($revenue['COMPLETE'], $riders['COMPLETE']); ?></td>
		<td><?php find_average_price($revenue['SCHEDULED'],$riders['SCHEDULED']); ?></td>
		<td><?php find_average_price($revenue['CANCELEDLATE'],$riders['CANCELEDLATE']); ?></td>
		<td><?php find_average_price($revenue['COMPLETE'] + $revenue['SCHEDULED'] + $revenue['CANCELEDLATE'],
                                     $riders['COMPLETE'] + $riders['SCHEDULED'] + $riders['CANCELEDLATE']);
            ?></td>
		<td><?php find_average_price($revenue['CANCELEDEARLY'],$riders['CANCELEDEARLY']); ?></td>
		<td><?php find_average_price($revenue['NOTSCHEDULED'],$riders['NOTSCHEDULED']); ?></td>
		<td><?php find_average_price($revenue['DRIVERNOSHOW'],$riders['DRIVERNOSHOW']); ?></td>
		<td><?php printf("$%d.%02.2d", $revenue['WEATHERCANCEL']/100, $riders['WEATHERCANCEL'] % 100); ?></td>
		<td><?php printf("$%d.%02.2d", $revenue['DESTINATIONCANCEL']/100, $riders['DESTINATIONCANCEL'] % 100); ?></td>
		<td>N/A</td>
	</tr>
	<tr>
		<th>$ / Driver</th>
		<td><?php find_average_price($revenue['COMPLETE'],$drivers['COMPLETE']); ?></td>
		<td><?php find_average_price($revenue['SCHEDULED'],$drivers['SCHEDULED']); ?></td>
		<td><?php find_average_price($revenue['CANCELEDLATE'],$drivers['CANCELEDLATE']); ?></td>
		<td><?php find_average_price($revenue['COMPLETE'] + $revenue['SCHEDULED'] + $revenue['CANCELEDLATE'],
                                     $drivers['COMPLETE'] + $drivers['SCHEDULED'] + $drivers['CANCELEDLATE']);
            ?></td>
		<td><?php find_average_price($revenue['CANCELEDEARLY'],$drivers['CANCELEDEARLY']); ?></td>
		<td><?php find_average_price($revenue['NOTSCHEDULED'],$drivers['NOTSCHEDULED']); ?></td>
		<td><?php find_average_price($revenue['DRIVERNOSHOW'],$drivers['DRIVERNOSHOW']); ?></td>
		<td><?php find_average_price($revenue['WEATHERCANCEL'],$drivers['WEATHERCANCEL']); ?></td>
		<td><?php find_average_price($revenue['DESTINATIONCANCEL'],$drivers['DESTINATIONCANCEL']); ?></td>
		<td>N/A</td>
	</tr>
	<tr>
		<th>$ / Link</th>
		<td><?php find_average_price($revenue['COMPLETE'],$links['COMPLETE']); ?></td>
		<td><?php find_average_price($revenue['SCHEDULED'],$links['SCHEDULED']); ?></td>
		<td><?php find_average_price($revenue['CANCELEDLATE'],$links['CANCELEDLATE']); ?></td>
		<td><?php find_average_price($revenue['COMPLETE'] + $revenue['SCHEDULED'] + $revenue['CANCELEDLATE'],
                                     $links['COMPLETE'] + $links['SCHEDULED'] + $links['CANCELEDLATE']);
            ?></td>
		<td><?php find_average_price($revenue['CANCELEDEARLY'],$links['CANCELEDEARLY']); ?></td>
		<td><?php find_average_price($revenue['NOTSCHEDULED'],$links['NOTSCHEDULED']); ?></td>
		<td><?php find_average_price($revenue['DRIVERNOSHOW'],$links['DRIVERNOSHOW']); ?></td>
		<td><?php find_average_price($revenue['WEATHERCANCEL'],$links['WEATHERCANCEL']); ?></td>
		<td><?php find_average_price($revenue['DESTINATIONCANCEL'],$links['DESTINATIONCANCEL']); ?></td>
		<td><?php find_average_price($transition['Revenue'], $transition['Links']); ?></td>
	</tr>
</table>

		<!-- end TABLE 6 -->
		<!--begin TABLE 7 --> 


<br />
<table width="400px" border="1">
	<tr>
		<th>Zip</th>
		<th>City</th>
		<th># Departs</th>
		<th># Arrive</th>
	</tr>
	<?php		
        $sql = "SELECT ZIP5, City, COUNT(ZIP5) AS Depart
                FROM (SELECT LinkID, FromDestinationID FROM link
                          WHERE LinkStatus IN ('COMPLETE', 'CANCELEDLATE', 'UNKNOWN') AND $date_clause AND FranchiseID = $safe_franchise
                      UNION
                      SELECT LinkID, FromDestinationID FROM link_history
                          WHERE LinkStatus IN ('COMPLETE', 'CANCELEDLATE', 'UNKNOWN') AND $date_clause AND FranchiseID = $safe_franchise
                      ) all_link, destination NATURAL JOIN address
                WHERE all_link.FromDestinationID = destination.DestinationID
                GROUP BY ZIP5
                ORDER BY ZIP5";

        $result = mysql_query($sql);

        if (!$result) {
            rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                            "Report query failed", $sql);
            die(mysql_error());
        } else {
            $zips = array();
            while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
                $zips[$row['ZIP5']] = $row;
            }
        }

        $sql = "SELECT ZIP5, City, COUNT(ZIP5) AS Arrive
                FROM (SELECT LinkID, ToDestinationID FROM link
                          WHERE LinkStatus IN ('COMPLETE', 'CANCELEDLATE', 'UNKNOWN') AND $date_clause AND FranchiseID = $safe_franchise
                      UNION
                      SELECT LinkID, FromDestinationID FROM link_history
                          WHERE LinkStatus IN ('COMPLETE', 'CANCELEDLATE', 'UNKNOWN') AND $date_clause AND FranchiseID = $safe_franchise
                      ) all_link, destination NATURAL JOIN address
                WHERE all_link.ToDestinationID = destination.DestinationID
                GROUP BY ZIP5
                ORDER BY ZIP5";

        $result = mysql_query($sql);

        if (!$result) {
            rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                            "Report query failed", $sql);
            die(mysql_error());
        } else {
            while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
                if (array_key_exists($row['ZIP5'], $zips)) {
                    $zips[$row['ZIP5']]['Arrive'] = $row['Arrive'];
                } else {
                    $zips[$row['ZIP5']] = $row;
                    $zips[$row['ZIP5']]['Depart'] = 0;
                }
            }
        }

        ksort($zips);
        foreach ($zips as $zip) { 
			echo '<tr>';
				echo "<td>{$zip['ZIP5']}</td>";
				echo "<td>{$zip['City']}</td>";
				echo "<td>{$zip['Depart']}</td>";
				echo "<td>{$zip['Arrive']}</td>";
			echo '</tr>';
		}
	
	?>
</table>

<!--END new layout -->
<script type="text/javascript">
	var collapsables = ['ride','rider','driver'];
	collapsables.each( function(item){
		$(item).addEvent('click', function(){
			if($(this.id + '_collapse').getStyle('display') == 'none'){
				$(this.id + '_collapse').setStyle('display','');
			} else {
				$(this.id + '_collapse').setStyle('display','none');
			}
		});
		$(item + '_collapse').setStyle('display','none');
		$(item).setStyle('cursor','pointer');
	});
	$('ShowAll').addEvent('click', function(){
		collapsables.each( function(item) {
			$(item + '_collapse').setStyle('display','');
		});
	});
	$('HideAll').addEvent('click', function(){
		collapsables.each( function(item) {
			$(item + '_collapse').setStyle('display','none');
		});
	});
</script>
<?php

function print_division($numerator, $denominator, $decimals, $div_by_zero = "---") {
    if ($denominator == 0) {
        echo $div_by_zero;
    } else {
        echo number_format($numerator/$denominator, 2);
    }
}
//CAN WE SHOW THE FOOTER WITHOUT PRINTING IT?
	include_once 'include/footer.php';
?>
