<?php
	include_once 'include/user.php';
	include_once 'include/rider.php';
	include_once 'include/link.php';
	include_once 'include/name.php';
    include_once 'include/care_facility.php';
    include_once 'include/supporters.php';
    include_once 'include/charity.php';
	include_once 'include/franchise.php';
	
	redirect_if_not_logged_in();
	
	$ADDITIONAL_RC_JAVASCRIPT[] = 'editable_dates.js';
	
	$franchise_id = get_current_user_franchise();
	
	if(current_user_has_role(1, "FullAdmin") && current_user_has_role($franchise_id, "Franchisee")){
		header("Location: home.php");
		die();	
	}
	
    include_once 'include/header.php';
	
	echo "<script type=\"text/javascript\" src=\"js/tiny_table.js\"></script>";
	if(isset($_GET['type']) && $_GET['type'] == 'applicants' || $_GET['type'] == 'all'){
		?>
			<h2 style="text-align:center;">Applicants</h2>
        <table width="100%" cellpadding="0" cellspacing="0" border="1">
            <tr>
                <th>Name</th>
                <th>Applying For</th>
                <th>Application Date</th>
                <th>Phone</th>
                <th>Edit</th>
            </tr>
            <?php
				
                $users = get_all_active_applied_users($franchise_id);
				$user_ids = array();
				foreach($users as $user)
					$user_ids[] = $user['UserID'];
				$phoneNumbs = get_phone_number_for_users($user_ids);
				
                if ($users) {
                    foreach ($users as $user_row) {
                        //$name = get_user_person_name($row['UserID']);
						$phone = $phoneNumbs[$user_row['UserID']];
                        echo '<tr>';
                        echo '<td>' . $user_row['FirstName'] . ' ' . $user_row['LastName'] . '</td>';
                        echo '<td>';
                        echo $user_row['Role'];
                        echo '</td>';
						echo "<td>". format_date($user_row['ApplicationDate'], "n/j/Y") . "</td>";
						echo "<td>" . $phone . "</td>";
                        echo '<td><a href="account.php?id=' . $user_row['UserID'] . '&action=review">View</a></td>';
                        echo '</tr>';
                    }
                    unset ($users);
                }
			?>
        </table>
        
		<?php   //This section of code identifies inactive riders *****
	} if(isset($_GET['type']) && $_GET['type'] == 'inactive'){
		?>
			<h2 style="text-align:center;">Inactive Accounts</h2>
		<div style="margin:10px 50px 10px 50px;">
			<div class="border">
				<div class="column1">Name</div>
				<div class="column2">Roles</div>
				<div class="column3">Edit</div>
			</div>
			<?php
				
                $users = get_all_inactive_users($franchise_id);
				
                if ($users && count($users) > 0) {
                    foreach ($users as $user_row) {
                        echo '<div class="border">';
                        echo '<div class="column1">' . $user_row['FirstName'] . ' ' . $user_row['LastName'] . '</div>';
                        echo '<div class="column2">';
                        $roles = get_user_roles($user_row['UserID']);
                        if($roles){
                            foreach ($roles as $role) {
                                echo $role["Role"] . ' ';
                            }
                        }
                        echo '</div>';
                        echo '<div class="column3"><a href="account.php?id=' . $user_row['UserID'] . 
                                '&action=review">View</a></div>';
                        echo '</div>';
                    }
                } else {
					echo '<br><br><center>No Users Found.</center>';
                }
			?>
		</div>
		<?php  //This section of code identifies riders ******
	} if(isset($_GET['type']) && $_GET['type'] == 'riders' || $_GET['type'] == 'all') {
		
        $users = get_all_active_rider_user_info($franchise_id, ($_GET['sortby'] == 'LastName') );
		$riders = array();
		foreach($users as $user){
			$riders[] = $user['UserID'];
		}
		
		?>
		<h2 style="text-align:center;">Riders</h2>
        <a href="<?php if(!isset($_GET['sortby']) || $_GET['sortby'] == "LastName") echo $_SERVER['PHP_SELF'] . "?type=" . $_GET['type'] . "&sortby=FirstName";  else echo $_SERVER['PHP_SELF'] . "?type=" . $_GET['type'] . "&sortby=LastName"; ?>">
        <input type="button" value="<?php if(!isset($_GET['sortby']) || $_GET['sortby'] == "LastName") echo "Sort By First Name";  else echo "Sort By Last Name"; ?>" />
        </a>
		<table id="test1" class=" add_titles" width="1000px" border="1px" cellspacing="0px">
			<tr class="head">
				<th style="text-align:center;">ID</th>
                <th width="205px">Name</th>
                <th style="text-align:center;">Age</th>
                <th width="60px", style="text-align:center;">Sign Up</th>
                <th width="60px", style="text-align:center;">Ann. Fee Pd</th>
                <th width="60px", style="text-align:center;">Welc. Pkg.</th>
                <th width="60px", style="text-align:center;">Rider Waiver</th>
                <th width="60px", style="text-align:center;">First Ride</th>
                <th width="60px", style="text-align:center;">Ride 1 Fol/up</th>
                <th width="60px", style="text-align:center;">Last Ride</th>
                <th width="60px", style="text-align:center;">Next Ride</th>
                <th width="60px", style="text-align:center;">Cur. Bal.</th>
                <th width="60px", style="text-align:center;">Avail. Bal.</th>
                <th width="60px", style="text-align:center;">Thres-hold</th>
                <th width="20px", class="nosort", style="text-align:center;">CF</th>
                <th width="85px", style="text-align:center;">Phone</th>
                <th width="40px", style="text-align:center;">Pic Waiv</th>
			</tr>
			<?php
                $travel_times = get_next_travel_times_for_all_riders($riders);
				$balances = calculate_batch_user_ledger_balance($riders);
				$incomplete_balances = calculate_batch_rider_incomplete_ride_costs($riders);
				$phone_numbers = get_phone_number_for_users($riders);
				$first_rides = get_batch_riders_first_ride($riders);
				$last_rides = get_batch_riders_last_ride($riders);
				//print_r($first_rides);
				$total_balance;
				$total_available;
                
                foreach ($users as $user_row) {
				//while($row = mysql_fetch_array($result)){
					//$name = get_name($row['PersonNameID']);
					$balance = $balances[$user_row['UserID']];
					$total_balance += $balance;
					$ride_costs = $incomplete_balances[$user_row['UserID']];
					$total_available += ($balance - $ride_costs);
                    $annual_fee =  $user_row['AnnualFeePaymentDate']; 
					$phone = $phone_numbers[$user_row['UserID']];
					?>
					<tr id="<?php echo $user_row['UserID']; ?>">
						<td style="text-align:center;"><a class="User_Redirect" id="<?php 
                                echo $user_row['UserID']; ?>" href="account.php"><?php 
                                echo $user_row['UserID']; ?></a></td>
						<td><?php echo get_displayable_person_name_string( $user_row); ?></td>
                        <td  style="text-align:center;">
							<?php
                            	$bday = get_date($user_row['DateOfBirth']);
								echo date("Y") - $bday['Year'];
							?>
                        </td>
						<td id="ApplicationDate" nowrap="nowrap" class="black_over_white_center" ><?php 
                                        echo format_date($user_row['ApplicationDate'], "n/j/y"); ?></td>
						<td 
						 <?php 
                                if($user_row['DaysOnAnnualFee'] > -9000 && $user_row['DaysOnAnnualFee'] <= 0) 
                                   echo 'class="black_over_pink_center"';
                                else if($user_row['DaysOnAnnualFee'] >= 0 && $user_row['DaysOnAnnualFee'] <= 30) 
                                   echo 'class="black_over_yellow_center"';
                                else 
                                   echo 'class="black_over_white_center"'; 
						        echo '>';
                            echo "<a class=\"User_Redirect\" id=\"$user_row[UserID]\" href=\"manual_ledger_entry.php\">".format_date($annual_fee, 'n/j/y')."</a>";
                            ?></td>
						<td id="WelcomePackageSentDate" class="black_over_white_center editable_date"><?php echo 
                                        format_date($user_row['WelcomePackageSentDate'], "n/j/y"); ?></td>
                        <td id="RiderWaiverReceived" class="black_over_white_center editable_date"><?php 
                                        echo format_date($user_row['RiderWaiverReceived'], "n/j/y"); ?></td>
						<td id="FirstRideDate" class="black_over_white_center" ><?php 
                                        echo format_date($first_rides[$user_row['UserID']], "n/j/y"); ?></td>
						<td id="FirstRideFollowupDate"  class="black_over_white_center editable_date"><?php 
                                        echo format_date($user_row['FirstRideFollowupDate'], "n/j/y"); ?></td>
						<td id="LastRideDate"<?php 
							if (isset($user_row['UserID']) && isset($last_rides[$user_row['UserID']]))
							{
								$last_ride = $last_rides[$user_row['UserID']];
								$cell_color = 'class="black_over_white_center" ';
								if(strtotime($last_ride) < strtotime("-270 days"))
		                            $cell_color =  'class="black_over_pink_center" ';
								elseif(strtotime($last_ride) < strtotime("-180 days"))
		                            $cell_color =  'class="black_over_orange_center" ';
								elseif(strtotime($last_ride) < strtotime("-90 days"))
		                            $cell_color =  'class="black_over_yellow_center" ';
						        echo "$cell_color > <a class=\"User_Redirect\" id=\"$user_row[UserID]\" href=\"myrides.php\">" . format_date($last_ride, "n/j/y"); 
							}
						//echo "<a class=\"User_Redirect\" id=\"$user_row[UserID]\" href=\"myrides.php\">".format_date($last_rides[$user_row['UserID']], "n/j/y"); ?></a></td>
                        <td class="black_over_white_center" ><?php 
                            if ($travel_times[$user_row['UserID']]) {
                                echo "<a class=\"User_Redirect\" id=\"$user_row[UserID]\" href=\"myrides.php\">".date('n/j/y', $travel_times[$user_row['UserID']]['arrival']['time_t'])."</a>";
                            } else {
                                echo ' - ';
                            }
                        ?></td>
                        <td style="text-align:right;"><?php 
                        	echo "<a class=\"User_Redirect\" id=\"$user_row[UserID]\" href=\"user_ledger.php\">";
                        	printf("$%d.%02.2d", $balance/100, $balance % 100); 
                        	echo "</a>";
                        ?></td>
                        <td style="text-align:right;"<?php 
                                if ($balance - $ride_costs <= $user_row['RechargeThreshold'] && !$user_row['CareFacility']) {
                                    echo " class=\"Table_Warning_Cell\">"; 
                                } else {  
                                    echo '>';
                                }
                                echo "<a class=\"User_Redirect\" id=\"$user_row[UserID]\" href=\"manual_ledger_entry.php\">";
                                echo format_dollars( $balance - $ride_costs ); ?></a></td>
                        <td style="text-align:right;"><?php 
                        	echo "<a class=\"User_Redirect\" id=\"$user_row[UserID]\" href=\"make_payment.php\">"
                        		.format_dollars($user_row['RechargeThreshold']); ?></a></td>
                        <td style="text-align:center;"><?php if($user_row['CareFacility']) echo '<a href="care_facility.php?id=' . $user_row['CareFacility'] . '">x</a>'; ?></td>
                        <td><?php echo $phone; ?></td>
                        <td id="RiderPictureWaiver" class="editable_date"><?php echo format_date($user_row['RiderPictureWaiver'], "n/j/y"); ?></td>
					</tr>
					<?
				}
			?>
            <tr>
            	<td></td>
            	<td></td>
            	<td></td>
            	<td></td>
            	<td></td>
            	<td></td>
            	<td></td>
            	<td></td>
            	<td></td>
            	<td></td>
            	<td></td>
                <td style="text-align:right;"><?php echo format_dollars($total_balance); ?></td>
                <td style="text-align:right;"><?php echo format_dollars($total_available); ?></td>
            </tr>
		</table>
		<?php   //This section of code identifies Care Facilities ******
	} if(isset($_GET['type']) && $_GET['type'] == 'carefacility' || $_GET['type'] == 'all') {
	
		$carefacilities = get_all_active_care_facility_user_info_xx($franchise_id);
		$total_balance = 0;
        $total_available = 0;
		?>
		<h2  style="text-align:center;">Care Facilities</h2>
		<table id="test2" class="add_titles" width="1000px" border="1px" cellspacing="0px">
			<tr class="head">
				<th style="text-align:center;">ID</th>
                <th width="205px">Name</th>
                <th width="60px", style="text-align:center;">Sign Up</th>
                <th width="60px", style="text-align:center;">Ann. Fee Pd</th>
                <th width="60px", style="text-align:center;">Welc. Pkg.</th>
                <th width="60px", style="text-align:center;">C/F Waiver</th>
                <th width="60px", style="text-align:center;">First Ride</th>
                <th width="60px", style="text-align:center;">Ride 1 Fol/up</th>
                <th width="60px", style="text-align:center;">Last Ride</th>
                <th width="60px", style="text-align:center;">Next Ride</th>
                <th width="60px", style="text-align:center;">Current Bal.</th>
                <th width="60px", style="text-align:center;">Avail. Bal.</th>
                <th width="60px", style="text-align:center;">Thresh.</th>
                <th class="nosort", style="text-align:center;">CF</th>
                <th width="85px", style="text-align:center;">Phone</th>
                <th width="40px", style="text-align:center;">Pic Waiv</th>
			</tr>
			<?php
				//print_r($first_rides);
				$total_balance;
				$total_available;
                
                foreach ($carefacilities as $carefacility_row) {
					$total_balance += $carefacility_row["Balance"];
					$total_available += $carefacility_row["AvailableBalance"];
					?>
					<tr id="<?php echo $carefacility_row['CareFacilityID']; ?>">
						<td style="text-align:center;"><a class="User_Redirect" id="<?php 
                                echo $carefacility_row['CareFacilityID']; ?>" href="account.php"><?php 
                                echo $carefacility_row['CareFacilityID']; ?></a></td>
						<td><?php echo $carefacility_row['CareFacilityName']; ?></td>
						<td id="ApplicationDate" nowrap="nowrap"><?php 
                                        echo format_date($carefacility_row['ApplicationDate'], "n/j/y"); ?></td>
						<td <?php 
                         //       if($carefacility_row['AnnualFeePaymentDate'] > -9000 && $carefacility_row['AnnualFeePaymentDate'] <= 0) {
                         //           echo 'class="annual_fee_over_due"';
                          //      } else if($carefacility_row['AnnualFeePaymentDate'] >= 0 && $carefacility_row['AnnualFeePaymentDate'] <= 30) {
                          //           echo 'class="annual_fee_month_away"';
                           //     }
						?>><?php 
                            echo format_date($carefacility_row['AnnualFeePaymentDate'], 'n/j/y');
                            ?></td>
						<td id="WelcomePackageSentDate" class="editable_date"><?php echo 
                                        format_date($carefacility_row['WelcomePackageSentDate'], "n/j/y"); ?></td>
                        <td id="RiderWaiverReceived" class="editable_date"><?php 
                                        echo format_date($carefacility_row['CareFacilityWaver'], "n/j/y"); ?></td>
						<td id="FirstRideDate"><?php 
                                        echo format_date($carefacility_row['FirstRide'], "n/j/y"); ?></td>
						<td id="FirstRideFollowupDate" class="editable_date"><?php 
                                        echo format_date($carefacility_row['FirstRideFollowupDate'], "n/j/y"); ?></td>
						<td id="LastRideDate"><?php 
                                        echo format_date($carefacility_row['LastRide'], "n/j/y"); ?></td>
                        <td><?php 
                            if ($carefacility_row['NextRide']) {
                                echo format_date( $carefacility_row['NextRide'], "n/j/y");
                            } else {
                                echo ' - ';
                            }
                        ?></td>
                        <td style="text-align:right;"><?php printf("$%d.%02.2d", $carefacility_row['Balance']/100, $carefacility_row['Balance']% 100); ?></td>
                        <td  style="text-align:right;"<?php 
                                if ($carefacility_row['AvailableBalance'] <= $carefacility_row['RechargeThreshold'] && !$user_row['CareFacility']) {
                                    echo " class=\"Table_Warning_Cell\">"; 
                                } else {  
                                    echo '>';
                                }
                                echo format_dollars($carefacility_row['AvailableBalance'] ); ?></td>
                        <td style="text-align:right;"><?php echo format_dollars($carefacility_row['RechargeThreshold']); ?></td>
                        <td style="text-align:center;"><?php   ?></td>
                        <td><?php   ?></td>
                        <td id="RiderPictureWaiver" class="editable_date"><?php echo format_date($carefacility_row['PictureWaiver'], "n/j/y"); ?></td>
					</tr>
					<?
				}
			?>
            <tr>
            	<td></td>
            	<td></td>
            	<td></td>
            	<td></td>
            	<td></td>
            	<td></td>
            	<td></td>
            	<td></td>
            	<td></td>
            	<td></td>
                <td style="text-align:right;"><?php echo format_dollars($total_balance); ?></td>
                <td style="text-align:right;"><?php echo format_dollars($total_available); ?></td>
            </tr>
		</table>

<!-- Active rider code begin -->
		
		<?php  //This section of code identifies active riders ******
	} if(isset($_GET['type']) && $_GET['type'] == 'riders' || $_GET['type'] == 'all') {
		
        $users = get_all_active_rider_user_info($franchise_id, ($_GET['sortby'] == 'LastName') );
		$riders = array();
		foreach($users as $user){
			$riders[] = $user['UserID'];
		}
		
		?>
		<h2 style="text-align:center;">Active Riders</h2>
        <a href="<?php if(!isset($_GET['sortby']) || $_GET['sortby'] == "LastName") echo $_SERVER['PHP_SELF'] . "?type=" . $_GET['type'] . "&sortby=FirstName";  else echo $_SERVER['PHP_SELF'] . "?type=" . $_GET['type'] . "&sortby=LastName"; ?>">
        <input type="button" value="<?php if(!isset($_GET['sortby']) || $_GET['sortby'] == "LastName") echo "Sort By First Name";  else echo "Sort By Last Name"; ?>" />
        </a>
		<table id="test1" class=" add_titles" width="1000px" border="1px" cellspacing="0px">
			<tr class="head">
				<th style="text-align:center;">ID</th>
                <th width="205px">Name</th>
                <th style="text-align:center;">Age</th>
				<th style="text-align:center;">Status</th>
                <th width="60px", style="text-align:center;">Sign Up</th>
                <th width="60px", style="text-align:center;">Ann. Fee Pd</th>
                <th width="60px", style="text-align:center;">Welc. Pkg.</th>
                <th width="60px", style="text-align:center;">Rider Waiver</th>
                <th width="60px", style="text-align:center;">First Ride</th>
                <th width="60px", style="text-align:center;">Ride 1 Fol/up</th>
                <th width="60px", style="text-align:center;">Last Ride</th>
                <th width="60px", style="text-align:center;">Next Ride</th>
                <th width="60px", style="text-align:center;">Cur. Bal.</th>
                <th width="60px", style="text-align:center;">Avail. Bal.</th>
                <th width="60px", style="text-align:center;">Thres-hold</th>
                <th width="20px", class="nosort", style="text-align:center;">CF</th>
                <th width="85px", style="text-align:center;">Phone</th>
                <th width="40px", style="text-align:center;">Pic Waiv</th>
			</tr>
			<?php
                $travel_times = get_next_travel_times_for_all_riders($riders);
				$balances = calculate_batch_user_ledger_balance($riders);
				$incomplete_balances = calculate_batch_rider_incomplete_ride_costs($riders);
				$phone_numbers = get_phone_number_for_users($riders);
				$first_rides = get_batch_riders_first_ride($riders);
				$last_rides = get_batch_riders_last_ride($riders);
				//print_r($first_rides);
				$total_balance;
				$total_available;
                
                foreach ($users as $user_row) {
				//while($row = mysql_fetch_array($result)){
					//$name = get_name($row['PersonNameID']);
					$balance = $balances[$user_row['UserID']];
					$total_balance += $balance;
					$ride_costs = $incomplete_balances[$user_row['UserID']];
					$total_available += ($balance - $ride_costs);
                    $annual_fee =  $user_row['AnnualFeePaymentDate']; 
					$phone = $phone_numbers[$user_row['UserID']];
					?>
					<tr id="<?php echo $user_row['UserID']; ?>">
						<td style="text-align:center;"><a class="User_Redirect" id="<?php 
                                echo $user_row['UserID']; ?>" href="account.php"><?php 
                                echo $user_row['UserID']; ?></a>
						</td>
						<td><?php echo get_displayable_person_name_string( $user_row); ?></td>
                        <td  style="text-align:center;">
							<?php
                            	$bday = get_date($user_row['DateOfBirth']);
								echo date("Y") - $bday['Year'];
							?>
                        </td>
						<td  style="text-align:center;">
							<?php
                            	$status = get_date($user_row['Status']);
								
							?>
                        </td>
						<td id="ApplicationDate" nowrap="nowrap" class="black_over_white_center" ><?php 
                                        echo format_date($user_row['ApplicationDate'], "n/j/y"); ?></td>
						<td 
						 <?php 
                                if($user_row['DaysOnAnnualFee'] > -9000 && $user_row['DaysOnAnnualFee'] <= 0) 
                                   echo 'class="black_over_pink_center"';
                                else if($user_row['DaysOnAnnualFee'] >= 0 && $user_row['DaysOnAnnualFee'] <= 30) 
                                   echo 'class="black_over_yellow_center"';
                                else 
                                   echo 'class="black_over_white_center"'; 
						        echo '>';
                            echo "<a class=\"User_Redirect\" id=\"$user_row[UserID]\" href=\"manual_ledger_entry.php\">".format_date($annual_fee, 'n/j/y')."</a>";
                            ?></td>
						<td id="WelcomePackageSentDate" class="black_over_white_center editable_date"><?php echo 
                                        format_date($user_row['WelcomePackageSentDate'], "n/j/y"); ?></td>
                        <td id="RiderWaiverReceived" class="black_over_white_center editable_date"><?php 
                                        echo format_date($user_row['RiderWaiverReceived'], "n/j/y"); ?></td>
						<td id="FirstRideDate" class="black_over_white_center" ><?php 
                                        echo format_date($first_rides[$user_row['UserID']], "n/j/y"); ?></td>
						<td id="FirstRideFollowupDate"  class="black_over_white_center editable_date"><?php 
                                        echo format_date($user_row['FirstRideFollowupDate'], "n/j/y"); ?></td>
						<td id="LastRideDate"<?php 
							if (isset($user_row['UserID']) && isset($last_rides[$user_row['UserID']]))
							{
								$last_ride = $last_rides[$user_row['UserID']];
								$cell_color = 'class="black_over_white_center" ';
								if(strtotime($last_ride) < strtotime("-270 days"))
		                            $cell_color =  'class="black_over_pink_center" ';
								elseif(strtotime($last_ride) < strtotime("-180 days"))
		                            $cell_color =  'class="black_over_orange_center" ';
								elseif(strtotime($last_ride) < strtotime("-90 days"))
		                            $cell_color =  'class="black_over_yellow_center" ';
						        echo "$cell_color > <a class=\"User_Redirect\" id=\"$user_row[UserID]\" href=\"myrides.php\">" . format_date($last_ride, "n/j/y"); 
							}
						//echo "<a class=\"User_Redirect\" id=\"$user_row[UserID]\" href=\"myrides.php\">".format_date($last_rides[$user_row['UserID']], "n/j/y"); ?></a></td>
                        <td class="black_over_white_center" ><?php 
                            if ($travel_times[$user_row['UserID']]) {
                                echo "<a class=\"User_Redirect\" id=\"$user_row[UserID]\" href=\"myrides.php\">".date('n/j/y', $travel_times[$user_row['UserID']]['arrival']['time_t'])."</a>";
                            } else {
                                echo ' - ';
                            }
                        ?></td>
                        <td style="text-align:right;"><?php 
                        	echo "<a class=\"User_Redirect\" id=\"$user_row[UserID]\" href=\"user_ledger.php\">";
                        	printf("$%d.%02.2d", $balance/100, $balance % 100); 
                        	echo "</a>";
                        ?></td>
                        <td style="text-align:right;"<?php 
                                if ($balance - $ride_costs <= $user_row['RechargeThreshold'] && !$user_row['CareFacility']) {
                                    echo " class=\"Table_Warning_Cell\">"; 
                                } else {  
                                    echo '>';
                                }
                                echo "<a class=\"User_Redirect\" id=\"$user_row[UserID]\" href=\"manual_ledger_entry.php\">";
                                echo format_dollars( $balance - $ride_costs ); ?></a></td>
                        <td style="text-align:right;"><?php 
                        	echo "<a class=\"User_Redirect\" id=\"$user_row[UserID]\" href=\"make_payment.php\">"
                        		.format_dollars($user_row['RechargeThreshold']); ?></a></td>
                        <td style="text-align:center;"><?php if($user_row['CareFacility']) echo '<a href="care_facility.php?id=' . $user_row['CareFacility'] . '">x</a>'; ?></td>
                        <td><?php echo $phone; ?></td>
                        <td id="RiderPictureWaiver" class="editable_date"><?php echo format_date($user_row['RiderPictureWaiver'], "n/j/y"); ?></td>
					</tr>
					<?
				}
			?>
            <tr>
            	<td></td>
            	<td></td>
            	<td></td>
            	<td></td>
            	<td></td>
            	<td></td>
            	<td></td>
            	<td></td>
            	<td></td>
            	<td></td>
            	<td></td>
                <td style="text-align:right;"><?php echo format_dollars($total_balance); ?></td>
                <td style="text-align:right;"><?php echo format_dollars($total_available); ?></td>
            </tr>
		</table>
		
		<!-- Active riders code end -->
		
		<?php   //This section of code identifies Supporting Friends ******
	} if(isset($_GET['type']) && $_GET['type'] == 'supporters' || $_GET['type'] == 'all'){
		//
		$supporting_friends = get_all_supporting_friends($franchise_id);
		
		$sf_ids = array();
		$sf_rider_ids = array();
		foreach($supporting_friends as $sf)
        {
            $sf_ids[] = $sf['UserID'];
			if(!is_null($sf['RiderUserID']))
				$sf_rider_ids[] = $sf['RiderUserID'];
        }
		$balances = calculate_batch_user_ledger_balance($sf_rider_ids); 
		$incomplete_balances = calculate_batch_rider_incomplete_ride_costs($sf_rider_ids);
		$phone_numbers = get_phone_number_for_users($sf_ids);
		$last_rides = get_batch_riders_last_ride($sf_rider_ids);
        $travel_times = get_next_travel_times_for_all_riders($sf_rider_ids);
        
		echo ''
        ?>
		<h2 style="text-align:center;">Supporting Friends</h2>
		<table width="100%" border="1px" cellspacing="0px">
			<tr>
				<th width="30px", style="text-align:center;">ID</th>
            <th width="185px">Name</th>
            <th width="60px", style="text-align:center;">SF Since</th>
            <th width="185px">Supported Rider (R)</th>
            <th width="30px", style="text-align:center;">R ID</th>
            <th width="60px", style="text-align:center;">R Status</th>
            <th width="60px", style="text-align:center;">R Ann. Fee Due</th>
            <th width="60px", style="text-align:center;">R Last Ride</th>
            <th width="60px", style="text-align:center;">R Next Ride</th>
            <th width="60px", style="text-align:center;">R Cur. Bal.</th>
            <th width="60px", style="text-align:center;">R Avail. Bal.</th>
            <th width="60px", style="text-align:center;">R Thres.</th>
            <th width="85px", style="text-align:center;">SF Phone</th>
			</tr>
			<?php
				foreach($supporting_friends as $row){
                    $balance = $balances[$row['RiderUserID']];
                    $ride_costs = $incomplete_balances[$row['RiderUserID']];
                    $available = ($balance - $ride_costs);
                    $annual_fee =  $row['AnnualFeePaymentDate'];
                    $phone = $phone_numbers[$row['UserID']];

					?>
					<tr>
						<td style="text-align:center;"><a class="User_Redirect" id="<?php echo $row['UserID']; ?>" href="account.php"><?php echo $row['UserID']; ?></a></td>
						<td><?php echo get_displayable_person_name_string($row); ?></td>
						<td width="60px"  style="text-align:center;"><?php echo format_date($row['FirstLedger'], 'n/j/y') ; ?></td>
						<td><?php echo get_displayable_person_name_string($row, 'Rider'); ?></td>
						<td style="text-align:center;"><a class="User_Redirect" id="<?php echo $row['RiderUserID']; ?>" href="account.php"><?php echo $row['RiderUserID']; ?></a></td>
						<td><?php echo $row['Status']; ?></td>
						<td width="60px" style="text-align:center;">
						    <?php 
						    if (isset($row['RiderUserID']))
						    {
						    echo "<a class=\"User_Redirect\" id=\"$row[RiderUserID]\" href=\"manual_ledger_entry.php\">".format_date(date('Y-m-d',strtotime('+1 year', strtotime($annual_fee))), 'n/j/y')."</a>"; 
						    }
						    ?></td>
						<td id="LastRideDate>"
						<?php 
							if (isset($row['RiderUserID']) && isset($last_rides[$row['RiderUserID']]))
							{
								$last_ride = $last_rides[$row['RiderUserID']];
								$cell_color = 'class="black_over_white_center" ';
								if(strtotime($last_ride) < strtotime("-270 days"))
		                            $cell_color =  'class="white_over_red_center" ';
								elseif(strtotime($last_ride) < strtotime("-180 days"))
		                            $cell_color =  'class="black_over_orange_center" ';
								elseif(strtotime($last_ride) < strtotime("-90 days"))
		                            $cell_color =  'class="black_over_yellow_center" ';
						        echo "$cell_color <a class=\"User_Redirect\" id=\"$row[RiderUserID]\" href=\"myrides.php\">" . format_date($last_ride, "n/j/y"); 
							}
                        ?></a></td>
                        <td><?php 
                        	if (isset($row['RiderUserID']))
						    {                            
						    	if ($travel_times[$row['RiderUserID']]) {
                                    echo "<a class=\"black_over_white_center\" class=\"User_Redirect\" id=\"$row[RiderUserID]\" href=\"myrides.php\">".date('n/j/y', $travel_times[$row['RiderUserID']]['arrival']['time_t'])."</a>";
                                } else {
                                    echo ' - ';
                                }
						    }
                        ?></td>
						<td style="text-align:right;">
						<?php 
						 if (isset($row['RiderUserID']))
						    {
						    	echo "<a class=\"User_Redirect\" id=\"$row[RiderUserID]\" href=\"user_ledger.php\">";
                        	    echo format_dollars($balance); 
                        	    echo "</a>";
						    }
                        ?></td>
                        <td style="text-align:right;"
                        <?php 
						 if (isset($row['RiderUserID']))
						    {
                                if ($available <= $row['RechargeThreshold'] && !$row['CareFacility']) {
                                    echo " class=\"Table_Warning_Cell\">"; 
                                } else {  
                                    echo '>';
                                }
                            echo "<a class=\"User_Redirect\" id=\"$row[RiderUserID]\" href=\"manual_ledger_entry.php\">";
                            echo format_dollars( $available ); 
                            }?></a></td>						
						<td style="text-align:right;">
							<?php 
							   if (isset($row['RiderUserID']))
						       {
						          echo  format_dollars($row['RechargeThreshold']);
						       } ?></td>
						<td><?php echo $phone; ?></td>
					</tr>
					<?
				}
			?>
		</table>
		<?php
	} if(isset($_GET['type']) && $_GET['type'] == 'drivers' || $_GET['type'] == 'all'){
		$drivers = get_all_driver_info($franchise_id);
		$driver_ids = array();
		foreach($drivers as $driver)
			$driver_ids[] = $driver['UserID'];
		$driver_phone = get_phone_number_for_users($driver_ids);
		$alloc = get_all_driver_allocation_preferences($franchise_id);
		?>
		<h2>Drivers</h2>
		<table  id="sortabletable" class="sortable add_titles" width="1000px" border="1px" cellspacing="0px">
			<tr>
				<th width="30px", style="text-align:center;">ID </th>
                <th width="200px">Name</th>
                <th width="30px", style="text-align:center;">Age </th>
                <th width="60px", style="text-align:center;">Dr. Train</th>
                <th width="60px", style="text-align:center;">Pol. & Proc.</th>
                <th width="60px", style="text-align:center;">Dr. Agr.</th>
                <th width="30px", style="text-align:center;">Copy DL/ Ins</th>
                <th width="60px", style="text-align:center;">Ins. Ver.</th>
                <th width="60px", style="text-align:center;">Ins. Exp.</th>
                <th width="60px", style="text-align:center;">DL Exp.</th>
                <th width="60px", style="text-align:center;">1st Dr.<BR>FL</th>
                <th width="60px", style="text-align:center;">Last<br>Ride</th>
                <th width="200px">Current Donate</th>
                <th width="90px", style="text-align:center;">Phone Number</th>
                <th width="40px", style="text-align:center;">Pic Waiv </th>
			</tr>
			<?php
            	$charityNames = get_charity_names();
				foreach($drivers as $row){
					//$total_payout =  format_dollars(get_ytd_driver_reimbursement_amount($user_id));;
					//calculate_driver_compensation($row['UserID']);
					//$total_donated = $donated_to_charity = get_users_total_donations_to_charities($row['UserID']);
					$phone = $driver_phone[$row['UserID']];
					//$total =calculate_ledger_balance_on_date(  'User', $row['UserID'], date("y-n-j"), 'Driver');
					$pref = $alloc[$row['UserID']];
					$Chighlight = "";
					if($pref['AllocationType'] == "REIMBURSEMENT"){
					   $pref = "DRIVER";
					} else if($pref['AllocationType'] == "CHARITY") {
					   
					   $pref = $charityNames[$pref['AllocationID']];
					} else {
					   $Chighlight = ' class="annual_fee_month_away"';
					   $pref = "NOT SET";
					}
					$highlight = "";
					if($row['InsuranceVerified'] != NULL && (strtotime($row['InsuranceVerified']) + (334 * 24 * 60 * 60)) - time() < 0){
					   if((strtotime($row['InsuranceVerified']) + (365 * 24 * 60 * 60)) - time() < 0)
					       $highlight = ' annual_fee_over_due';
					   else
					       $highlight = ' annual_fee_month_away';
					}
					$DLhighlight = "";
					if($row['LicenseExpireDate'] != NULL && strtotime($row['LicenseExpireDate']) - (31 * 24 * 60 * 60) - time() < 0){
					   if(strtotime($row['LicenseExpireDate']) - time() < 0)
					       $DLhighlight = ' annual_fee_over_due';
					   else
					       $DLhighlight = ' annual_fee_month_away';
					}
					?>
					<tr id="<?php echo $row['UserID']; ?>">
						<td style="text-align:center;"><a class="User_Redirect" id="<?php echo $row['UserID']; ?>" href="account.php"><?php echo $row['UserID']; ?></a></td>
						<td><?php echo get_displayable_person_name_string($row); ?></td>
						<td style="text-align:center;">							
                  <?php/* this is the code from the rider section for calculating age. need more.
                            	$bday = get_date($user_row['DateOfBirth']);
								echo date("Y") - $bday['Year'];*/
							?></td>
						<td id="DriverApprovalDate" class="editable_date"  style="text-align:center;"><?php echo format_date($row['DriverApprovalDate'], "n/j/y"); ?></td>
						<td id="WelcomePackageSent" class="editable_date" style="text-align:center;"><?php echo format_date($row['WelcomePackageSent'], "n/j/y"); ?></td>
						<td id="DriverAgreementRecorded" class="editable_date" style="text-align:center;"><?php echo format_date($row['DriverAgreementRecorded'], "n/j/y"); ?></td>
						<td style="text-align:center;"><?php echo ($row['CopyofLicenseOnFile'] == "Yes" ? 'Y' : 'N') . "/" . ($row['CopyOfInsuranceCardOnFile'] == "Yes" ? 'Y' : 'N');  ?></td>
						<td style="text-align:center;" id="InsuranceVerified" class="editable_date <?php echo $highlight; ?>"><?php echo format_date($row['InsuranceVerified'], "n/j/y"); ?></td>
                        <td style="text-align:center;" id="PolicyExpirationDate" class="editable_date"><?php echo format_date($row['PolicyExpirationDate'], "n/j/y"); ?></td>
						<td style="text-align:center;" id="LicenseExpireDate" class="editable_date<?php echo $DLhighlight; ?>"><?php echo format_date($row['LicenseExpireDate'], "n/j/y"); ?></td>
						<td id="FirstDriveFollowup" class="editable_date" style="text-align:center;"><?php /*change this to the driver last ride*/
                  echo format_date($row['FirstDriveFollowup'], "n/j/y"); ?></td> 
            <?php
            $sql = "select max(DesiredArrivalTime) as dts from link_history where DriverUserID = ". $row['UserID'];
            $rs = mysql_fetch_assoc(mysql_query($sql));
            if($rs['dts'] != '') {
            	echo "<td style=\"";
	            $last_date = strtotime($rs['dts']);
	            $days = floor( (time() - $last_date) / (60*60*24) );
	            if($days > 60 && $days <= 120) echo "background-color: yellow;";
	            if($days > 120 && $days <= 180) echo "background-color: orange;";
	            if($days > 180) echo "background-color: pink;";
	            echo "\">".date('m/d/y',$last_date)."</td>";
	          } else echo "<td></td>";
            ?>
						<td<?php echo $Chighlight; ?>><?php echo $pref; ?></td>
                        <td><?php echo $phone; ?></td>
                        <td id="DriverPictureWaiver" class="editable_date" style="text-align:center;"><?php echo format_date($user_row['DriverPictureWaiver'], "n/j/y"); ?></td>
					</tr>
					<?
				}
			?>
		</table>
		<?php
	} if(isset($_GET['type']) && $_GET['type'] == 'admins' || $_GET['type'] == 'all'){
		$admins = get_all_admins($franchise_id);
		?>
		<h2>Admins</h2>
		<table id="sortabletable" class="sortable add_titles" width="1000px" border="1px" cellspacing="0px">
			<tr>
				<th>ID</th><th>Name</th>
			</tr>
			<?php
				foreach($admins as $row){
					//$name = get_name($row['PersonNameID']);
					?>
					<tr>
						<td style="text-align:center;"><a class="User_Redirect" id="<?php echo $row['UserID']; ?>" href="account.php"><?php echo $row['UserID']; ?></a></td>
						<td><?php echo get_displayable_person_name_string($row); ?></td>
					</tr>
					<?
				}
			?>
		</table>
		<?php
	} if(isset($_GET['type']) && $_GET['type'] == 'carefacilityadmins' || $_GET['type'] == 'all'){
		$cf_admins = get_care_facility_admins($franchise_id);
		?>
		<h2>Care Facility Admins</h2>
		<table  id="sortabletable" class="sortable add_titles" width="1000px" border="1px" cellspacing="0px">
			<tr>
				<th>ID</th><th>Name</th>
			</tr>
			<?php
				foreach($cf_admins as $row){

					?>
					<tr>
						<td style="text-align:center;"><a class="User_Redirect" id="<?php echo $row['UserID']; ?>" href="account.php"><?php echo $row['UserID']; ?></a></td>
						<td><?php echo get_displayable_person_name_string($row); ?></td>
					</tr>
					<?
				}
			?>
		</table>
		<?php
	}	
?>
<script type="text/javascript">
  var sorter = new TINY.table.sorter("sorter");
	sorter.head = "head";
	sorter.asc = "asc";
	sorter.desc = "desc";
	sorter.even = "evenrow";
	sorter.odd = "oddrow";
	sorter.evensel = "evenselected";
	sorter.oddsel = "oddselected";
	sorter.paginate = true;
	sorter.currentid = "currentpage";
	sorter.limitid = "pagelimit";
	sorter.init("test1",1);
</script>
<script type="text/javascript">
	var tables = $$('.add_titles');
	Array.each(tables, function(table, index){
		var rows = table.getChildren();
		rows = rows.getChildren();
		row1 = rows[0][0].getChildren();
		for( var i = 1; i < rows[0].length; i++){
			var cols = rows[0][i].getChildren();
			for( var j = 0; j < cols.length; j++){
				cols[j].title = row1[j].innerHTML;
			}
		}
	});
	
</script>
<?php
	include_once 'include/footer.php';
?>
