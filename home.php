<?php
	require_once 'include/user.php';
	require_once 'include/driver.php';
	require_once 'include/rider.php';
	require_once 'include/link.php';
	require_once 'include/date_time.php';
    require_once('include/ledger.php');
	require_once 'include/news_item.php';
	require_once('include/franchise.php');
    require_once('include/care_facility.php');
	require_once 'include/supporters.php';
	require_once ('include/contact_narrative.php');
	
	redirect_if_not_logged_in();
	
	$user_id = get_affected_user_id();
	
    $franchise_id = get_current_user_franchise($user_id);
	if(isset($_POST['PreferedCharity'])){
		if($_POST['PreferedCharity'] == 'driver'){
			$type = 'REIMBURSEMENT';
			$id = $user_id;
		} elseif($_POST['PreferedCharity'] == 'riderbalance') {
			$type = 'ADDTORIDERBALANCE';
			$id = $user_id;
		} else {	
			$type = "Charity";
			$id = $_POST['PreferedCharity'];
		}
		if(store_driver_allocation_preference($user_id, $type, $id))
			$pref_return = "Preference Successfully updated.";
		else
			$pref_return = "A problem occurred while updating your preferene";
		
	}
	
	
	
	include_once 'include/header.php';
	include_once 'include/charity.php';
	openCharityPopup();
?>
<h2>Welcome to the Home Page</h2>
<style>
.cleanTable {
	border: 1px solid black;
	border-collapse: collapse;
}
.cleanTable TD {
	padding: 2px;
	border: 1px solid black;
}	
.cleanTable TH {
	padding: 2px;
	text-align: center;
	border: 1px solid black;
	border-bottom: 2px solid black;
}
</style>
<div style="float:right; clear:both; border-left:1px solid; width:300px; height:100%; padding:4px; margin-right:10px;">
    
	<div style="padding:2px; border-bottom:1px solid; margin-bottom:5px; font-size:.9em;">Messages From Your Local Office</div>
	<?php
		get_franchise_news_items($franchise_id);
	?>
	<br>
	<div style="padding:2px; border-bottom:1px solid; margin-bottom:5px; font-size:.9em;">Messages From Corporate</div>
	<?php
		get_corporate_news_items();
	?>
	<br>
</div>

<div style="float:left; width:500px; padding:4px;">
	<?php
	
		if(user_has_role($user_id,$franchise_id,'Driver'))
		{

		    $last_pay_date =get_drivers_last_pay_date($user_id);
		    $unpaid = calculate_user_ledger_balance($user_id, 'DRIVER');
			$unpaid_money =  format_dollars($unpaid);
			$driver_reimbursement_ytd = get_ytd_driver_reimbursement_amount($user_id);
			
			$paid = get_user_total_reimbursement_donation_YTD($user_id);
			$YTD_paid = format_dollars($paid);
			$YTD_paid_attended = get_user_driver_attened_miles_YTD( $user_id, $last_pay_date);
			$YTD_unpaid_attended = get_user_driver_attened_miles_YTD($user_id, $last_pay_date, false);
			$YTD_paid_unattended = get_user_driver_unattened_miles_YTD($user_id, $last_pay_date);
			$YTD_unpaid_unattended = get_user_driver_unattened_miles_YTD($user_id, $last_pay_date, false);
			
			$attended_miles_total = $YTD_paid_attended['Distance'] + $YTD_unpaid_attended['Distance'];
			$unattended_miles_total = $YTD_paid_unattended['Distance'] + $YTD_unpaid_unattended['Distance'];
			$total_money = $unpaid + $paid;
			$total_money = format_dollars($total_money);
		if(!get_user_driver_settings($user_id))
			echo "<div class=\"reminder\">You have not filled in your settings/preferences. <a href=\"edit_user.php?field=createdriversettings&redirect=home.php\">Do Now.</a></div>";
		if(!get_driver_vehicles(get_user_driver_id($user_id)))
			echo "<div class=\"reminder\">You have not given us your vehicle information yet. <a href=\"edit_user.php?field=createdrivervehicle&redirect=home.php\">Do Now.</a></div>";
		if(!get_driver_emergency_contact(get_user_driver_id($user_id)))
			echo "<div class=\"reminder\">You have not given us a emergency contact yet. <a href=\"edit_user.php?field=createdriveremergencycontact&redirect=home.php\">Do Now.</a></div>";
			$upcoming_rides = get_user_driver_next_drives($user_id);
	?>
	<span style="font-size:1.3em;">Finances</span><br>
    <form method="post">
        <table style="margin:auto; width:90%;">
            <tr>
                <td style="font-size:1.2em;" colspan="2">Accumulated Miles</td>
                <td style="font-size:.8em;">Attended Miles</td>
                <td style="font-size:.8em;">Transition Miles</td>
                <td style="font-size:.8em;">Value</td>
            </tr>
            <tr>
            	<td width="20px"></td>
                <td style="text-align:right;">Unpaid Mileage</td>
                <td style="border:1px solid;"><?php echo number_format($YTD_unpaid_attended['Distance'],1); ?></td>
                <td style="border:1px solid;"><?php echo number_format($YTD_unpaid_unattended['Distance'],1); ?></td>
                <td style="border:1px solid;"><?php echo $unpaid_money; ?></td>
            </tr>
            <tr>
            	<td></td>
                <td style="text-align:right;">Paid Mileage YTD</td>
                <td style="border:1px solid;"><?php echo number_format($YTD_paid_attended['Distance'],1); ?></td>
                <td style="border:1px solid;"><?php echo number_format($YTD_paid_unattended['Distance'],1); ?></td>
                <td style="border:1px solid;"><?php echo $YTD_paid; ?></td>
            </tr>
            <tr>
            	<td></td>
                <td style="text-align:right;">Rider Support, YTD</td>
                <td style="border:1px solid;"><?php echo number_format($attended_miles_total,1); ?></td>
                <td style="border:1px solid;"><?php echo number_format($unattended_miles_total,1); ?></td>
                <td style="border:1px solid;"><?php echo $total_money; ?></td>
            </tr>
            <tr>
            	<td style="height:10px;"></td>
            </tr>
            <?php
            	$total = 0;
                $charities = get_supporter_charities_with_ytd($user_id);
                $ytd_amount = format_dollars($charity['YTD_Cents']);
                $pref = get_driver_allocation_preference($user_id);

            ?>
            <tr>
            	<td style="font-size:.7em;">Prefer- ence</td>
            	<td colspan="2" style="font-size:1.2em;">Charitable Distribution</td>
            </tr>
            <?php 
            foreach($charities as $charity){ 
                $ytd_amount = format_dollars($charity['YTD_Cents']);
                $total += $charity['YTD_Cents'];
            ?>
                <tr>
                	<td><input type="radio" name="PreferedCharity"<?php if($pref['AllocationType'] == 'CHARITY' && $pref['AllocationID'] == $charity['CharityID']) echo ' CHECKED'; ?> value="<?php echo $charity['CharityID']; ?>"></td>
                    <td colspan="3"><?php echo $charity['CharityName']; 
                    	if (is_logged_in() 
                    		&& (current_user_has_role($franchise_id,'FullAdmin') || current_user_has_role($franchise_id,'Franchisee'))
                    		&& $charity['AlwaysShow'] == 'N'
                    		) {
                    		echo "<span style='position: relative; font-size: 70%; top: -3px;'>[<a style='color: red;' href=# onClick='adminRemoveCharity(".$charity['CharityID']."); return false;'>X</a>]</span>";
                    	}	
                    ?></td>
                    <td style="border:1px solid;"><?php  echo $ytd_amount ?></td>
                 </tr>
            <?php } 
            	$total += $driver_reimbursement_ytd;
            ?>
            <tr>
            	<td></td>
            	<td colspan=2 style="padding: 10px;"><a href=# onClick="openCharityList();">Current Charity Options</a></td>
            </tr>
            <tr>
            	<td></td>
            	<td colspan="2" style="font-size:1.2em;">Non-Charitable Distribution</td>
            </tr>
            <tr>
            	<td><input type="radio" name="PreferedCharity"<?php if($pref['AllocationType'] == 'REIMBURSEMENT') echo ' CHECKED'; ?> value="driver"></td>
                <td colspan="3">Driver Reimbursement</td>
                <td style="border:1px solid;"><?php  echo format_dollars($driver_reimbursement_ytd); ?></td>
            </tr>
            <tr>
            	<td><input type="radio" name="PreferedCharity"<?php if($pref['AllocationType'] == 'ADDTORIDERBALANCE') echo ' CHECKED'; ?> value="riderbalance"></td>
                <td colspan="3">Add to Rider Balance</td>
                <?php
                $addtoriderbalance_ytd = get_ytd_addtoriderbalance_amount($user_id);
                $total += $addtoriderbalance_ytd;
                ?>
                <td style="border:1px solid;"><?php  echo format_dollars($addtoriderbalance_ytd); ?></td>
            </tr>
            <tr>
            	<td style="height:10px;"></td>
            </tr>
            <tr>
            	<td></td>
            	<td style="font-size:1.2em;">Total Distribution - YTD</td>
            	<td colspan="2"></td>
            	<td style="border:1px solid;"><?php echo format_dollars($total); ?></td>
            </tr>
            <tr>
            	<td></td>
            	<td>Previous Year Distribution</td>
            	<td colspan="2"></td>
            	<td style="border:1px solid;"><?php echo format_dollars(get_user_total_reimbursement_donation_YTD($user_id, (date("Y") - 1))); ?></td>
            </tr>
        </table>
        <input type="submit" value="<?php echo isset($pref_return) ? $pref_return : 'Save Driver Preference'; ?>">
    </form>
	<span style="font-size:1.3em;">Planned Drives</span><br>
	<table style="margin:auto; width:90%;">
		<tr>
			<td>Total Drives Scheduled</td>
			<td style="border:1px solid;"><?php echo $upcoming_rides['NumberOfDrives']; ?></td>
		</tr>
		<tr>
			<td>Next Drive Date</td>
			<td style="border:1px solid;"><?php echo estimate_link_pickup_time($upcoming_rides['NextDrive']); ?></td>
		</tr>
		<tr>
			<td colspan="3">
				<a href="manifest.php">Today's Manifest</a>
				<?php if(date("G") >= '15') echo " - <a href=\"manifest.php?date=" . date('Y-m-d',time() + 86400) . "\">Tomorrow's Manifest</a>"; ?>
			</td>
		</tr>
	</table>
	<?php }
		if(user_has_role($user_id,$franchise_id, 'Rider'))
		{
			$rider = get_user_rider_info( $user_id );
			$links = get_rider_active_links( get_user_rider_id( $user_id ) );
			$balance = calculate_user_ledger_balance( $user_id );
			$ride_costs = calculate_riders_incomplete_ride_costs( get_user_rider_id( $user_id ) );
			$date = get_date($rider['AnnualFeePaymentDate']);
			if(!$date || ($date['Year'] == date("Y") - 1 && $date['Month'] <= date("n") && $date['Day'] <= date("j")))
			    if (isAnnualFeeRequired($franchise_id)) {
				    $due = true;
				} else {
				    $due = false;
				}
			else
				$due = FALSE;
				
			if(!get_rider_preferences(get_user_rider_id($user_id)))
				echo "<div class=\"reminder\">You have not filled in your preferences. <a href=\"edit_user.php?field=createriderpreferences&redirect=home.php\">Do Now.</a></div>";
			if(!get_rider_survey(get_user_rider_id($user_id)))
				echo "<div class=\"reminder\">You have not taken the rider survey yet. <a href=\"edit_user.php?field=createridersurvey&redirect=home.php\">Do Now.</a></div>";
			if(!get_rider_emergency_contact(get_user_rider_id($user_id)))
				echo "<div class=\"reminder\">You have not given us a emergency contact yet. <a href=\"edit_user.php?field=createrideremergencycontact&redirect=home.php\">Do Now.</a></div>";
	?>
	<span style="font-size:1.3em;">Finances</span><br>
	<div style="margin-left:25px;">
		<span style="font-size:1.3em;">Annual Fee</span><br>
		<table class=cleanTable  style="width:300px; margin-left:50px;">
			<tr>
				<th>Annual Fee</th>
				<td><?php if($due) echo "Not Paid"; else echo "Paid"; ?></td>
				<td><?php echo "{$date['Month']}/{$date['Day']}/{$date['Year']}"; ?></td>
			</tr>
			<tr>
				<td colspan="2">Next Annual Fee Due</td>
				<td><?php echo "{$date['Month']}/{$date['Day']}/" . ($date['Year'] + 1); ?></td>
			</tr>
		</table>
		<?php if($due){ ?>
		<a href="make_payment.php">pay annual fee</a><br>
		<?php } ?>
		<span style="font-size:1.3em;">Ride Balance </span><br>
		
		<?php
			
			$sql = "SELECT datediff(now(),min(LedgerEntryTime)) from ledger where EntityType = 'USER' and EntityID = $user_id";
			$rs = mysql_fetch_array(mysql_query($sql));
			if($rs[0] >= 30 && $rs[0] < 60) {
				$sql = "select sum(quotedcents) from (
					SELECT LinkID, quotedcents FROM `link` WHERE RiderUserId = $user_id and DesiredArrivalTime BETWEEN CURDATE() - INTERVAL 30 DAY AND CURDATE() and LinkStatus in ('CANCELEDLATE','COMPLETE') and (CustomTransitionType = 'RIDER' or CustomTransitionType is null)
					UNION 
					SELECT LinkID, quotedcents FROM `link_history` WHERE RiderUserId = $user_id and DesiredArrivalTime BETWEEN CURDATE() - INTERVAL 30 DAY AND CURDATE() and LinkStatus in ('CANCELEDLATE','COMPLETE') and (CustomTransitionType = 'RIDER' or CustomTransitionType is null)
					) a";		
				$rval = mysql_fetch_array(mysql_query($sql))[0];
			} else if($rs[0] >= 60 && $rs[0] < 90) {
				$sql = "select sum(quotedcents) from (
					SELECT LinkID, quotedcents FROM `link` WHERE RiderUserId = $user_id and DesiredArrivalTime BETWEEN CURDATE() - INTERVAL 60 DAY AND CURDATE() and LinkStatus in ('CANCELEDLATE','COMPLETE') and (CustomTransitionType = 'RIDER' or CustomTransitionType is null)
					UNION 
					SELECT LinkID, quotedcents FROM `link_history` WHERE RiderUserId = $user_id and DesiredArrivalTime BETWEEN CURDATE() - INTERVAL 60 DAY AND CURDATE() and LinkStatus in ('CANCELEDLATE','COMPLETE') and (CustomTransitionType = 'RIDER' or CustomTransitionType is null)
					) a";					
				$rval = mysql_fetch_array(mysql_query($sql))[0] / 2;
			} else if($rs[0] > 90) {
				$sql = "select sum(quotedcents) from (
					SELECT LinkID, quotedcents FROM `link` WHERE RiderUserId = $user_id and DesiredArrivalTime BETWEEN CURDATE() - INTERVAL 90 DAY AND CURDATE() and LinkStatus in ('CANCELEDLATE','COMPLETE') and (CustomTransitionType = 'RIDER' or CustomTransitionType is null)
					UNION 
					SELECT LinkID, quotedcents FROM `link_history` WHERE RiderUserId = $user_id and DesiredArrivalTime BETWEEN CURDATE() - INTERVAL 90 DAY AND CURDATE() and LinkStatus in ('CANCELEDLATE','COMPLETE') and (CustomTransitionType = 'RIDER' or CustomTransitionType is null)
					) a";				
				$rval = mysql_fetch_array(mysql_query($sql))[0] / 3;				
			} 
			$rval = $rval / 100; /* convert from cents to dollars */
			if($rs[0] < 30) $monthlyave = 'Not Enough History';
			else {
				$monthlyave = '$'.number_format($rval,2);
			}
		
			$ride_costs_30 = calculate_riders_incomplete_ride_costs( get_user_rider_id( $user_id ), 30 );
		?>
		<table class=cleanTable  style="margin-left:50px;">
			<tr valign=bottom>
				<th>Cur. Bal.</th>
				<th>Future Ride $</th>
				<th>30 Day<BR>Avail. Bal.</th>
				<th>Avail. Bal.</th>
				<th>Monthly Avg.</th>
			</tr>
			<tr>
				<td><?php echo format_dollars($balance); ?></td>
				<td><?php echo format_dollars($ride_costs); ?></td>
				<td><?php echo format_dollars($balance - $ride_costs_30); ?></td>
				<td><?php echo format_dollars($balance - $ride_costs); ?></td>
				<td><?php echo $monthlyave; ?></td>
			</tr>
		</table>
		<a style="font-size:.9em;" href="make_payment.php">add money to my account</a><br>
		<span style="font-size:1.3em;">RCA Contact Guidelines</span>(when we will contact you)<br>
		<table style="margin-left:50px;">
			<tr>
				<td>Minimum Threshold($10 Min.):</td>
				<td style="border:1px solid;">$ <?php printf("%d.%02.2d", $rider["RechargeThreshold"]/100, $rider["RechargeThreshold"] % 100); ?></td>
				<td rowspan="2"><a style="font-size:.9em;" href="make_payment.php">update charge information</a></td>
			</tr>
			<tr>
				<td class="alignright">Recharge Amount($40 Min.):</td>
				<td style="border:1px solid;">$ <?php printf("%d.%02.2d", $rider["RechargeAmount"]/100, $rider["RechargeAmount"] % 100); ?></td>
			</tr>
		</table>
	</div>
	<span style="font-size:1.3em;">Rides</span><br>
	<table style="margin:auto; width:90%;">
		<tr>
			<td>Total Rides Scheduled</td>
			<td style="border:1px solid;"><?php echo count( $links ); ?> Destinations</td>
		</tr>
		<tr>
			<td>Next Ride Date</td>
			<?php
			
			?>
			<td style="border:1px solid;"><?php echo estimate_next_scheduled_link_pickup_time($user_id); ?></td>
		</tr>
	</table>
	<?php 
	}
	if(if_user_has_role($user_id, $franchise_id, 'CareFacilityAdmin')){
		
		$facility_id = get_first_user_care_facility($user_id);
		$cfs = get_all_active_care_facility_user_info_xx( $franchise_id );
		for($i = 0; $i < count($cfs); $i++) if($cfs[$i]['CareFacilityID'] == $facility_id) break;
		
		
	?>
	<span style="font-size:1.3em;">Balance: <?php echo format_dollars(calculate_care_facility_balance($facility_id)); ?></span><br>
	<br>
	<span style="font-size:1.3em;">Available Balance: <?php echo format_dollars($cfs[$i]['AvailableBalance']); ?></span><br>
	<br>
	<span style="font-size:1.3em;">Recharge Threshold: <?php echo format_dollars($cfs[$i]['RechargeThreshold']); ?></span><br>
	<br>
	<span style="font-size:1.3em;">Rides</span><br>
	<table style="margin:auto; width:90%;">
		<tr>
			<td>Total Rides Scheduled</td>
			<td style="border:1px solid;"><?php echo count( $links ); ?> Destinations</td>
		</tr>
		<tr>
			<td>Next Ride Date</td>
			<?php
			?>
			<td style="border:1px solid;"><?php echo estimate_link_pickup_time($links[0]['LinkID']); ?></td>
		</tr>
	</table>
	<?php }
   
    if (if_user_has_role($user_id, $franchise_id, 'Supporter')) {
        display_supporter_home($user_id);
    }
    
    
    ?>
	
</div>
<?php

include_once 'include/footer.php';


exit;

function display_supporter_home($user_id) {
    echo "Thank you for being a supporting friend!";
}




?>
