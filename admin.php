<?php
    include_once 'include/user.php';
	include_once 'include/franchise.php';
    include_once 'include/supporters.php';
	redirect_if_not_logged_in();
	
	$franchise = get_current_user_franchise();
	
	if(!current_user_has_role(1 , "FullAdmin") && !current_user_has_role($franchise, "Franchisee")){
		header("Location: home.php");
		die();	
	}
	
	include_once 'include/functions.php';
	
	
	
	get_admin_drop_down($franchise, TRUE);
	$safe_franchise = mysql_real_escape_string($franchise);
	
	include_once 'include/header.php';
	
	$background_checks = sql_num_rows("SELECT COUNT(*) FROM `users` WHERE `Status` = 'ACTIVE' AND `ApplicationStatus` = 'APPLIED' AND UserID IN (SELECT UserID FROM user_role WHERE FranchiseID = $safe_franchise);");
	$riders = find_number_of_users($safe_franchise,'Rider');
	$drivers = find_number_of_users($safe_franchise,'Driver');
	$supporters = find_number_of_users($safe_franchise,'Supporter');
	$referrals = 0;
	$careFacilityAdmins = find_number_of_users($safe_franchise,'CareFacilityAdmin');
	$riders_prefs = sql_num_rows("SELECT COUNT(*) FROM rider LEFT JOIN rider_preferences ON rider.UserID = rider_preferences.UserID WHERE rider_preferences.UserID IS NULL AND rider.UserID IN (SELECT UserID FROM user_role WHERE FranchiseID = $safe_franchise)");
	$riders_prefs = $riders_prefs[0];
	$rider_contact = sql_num_rows("SELECT COUNT(*) FROM `rider` WHERE EmergencyContactID IS NULL AND rider.UserID IN (SELECT UserID FROM user_role WHERE FranchiseID = $safe_franchise)");
	$rider_contact = $rider_contact[0];
	$driver_settings = sql_num_rows("SELECT COUNT(*) FROM driver LEFT JOIN driver_settings ON driver.UserID = driver_settings.UserID WHERE driver_settings.UserID IS NULL AND driver.UserID IN (SELECT UserID FROM user_role WHERE FranchiseID = $safe_franchise)");
	$driver_settings = $driver_settings[0];
	$driver_contact = sql_num_rows("SELECT COUNT(*) FROM `driver` WHERE EmergencyContactID IS NULL AND driver.UserID IN (SELECT UserID FROM user_role WHERE FranchiseID = $safe_franchise)");
	$driver_contact = $driver_contact[0];
	$annual_fee_due = sql_num_rows("SELECT COUNT(*) FROM `rider` WHERE AnnualFeePaymentDate < DATE_ADD(CURDATE(), INTERVAL -1 YEAR) AND rider.UserID IN (SELECT UserID FROM user_role WHERE FranchiseID = $safe_franchise)");
	$annual_fee_due = $annual_fee_due[0];
	$inactive_users = sql_num_rows("SELECT COUNT(*) FROM `users` WHERE `Status` = 'INACTIVE' AND UserID IN (SELECT UserID FROM user_role WHERE FranchiseID = $safe_franchise);");
	$pending_user_charities = sql_num_rows("SELECT COUNT(*) FROM charity WHERE Approved = 'N';");
?>
<h2>Admin Homepage</h2>

<ul>
  <li id="Individuals"><span style="font-size:1.2em">+ Work With Individuals( <?php echo $background_checks[0] + $riders_prefs + $rider_contact + $driver_settings + $driver_contact + $annual_fee_due;?> )</span><br></li>

<ul id="Individuals_collapse">
	<li id="PendingApplications">
		<b>Pending Applications (<?php echo $background_checks[0]; ?>)</b><br>
		<ul id="PendingApplications_collapse">
			<li><a href="users.php?type=applicants">Do Background Checks (<?php echo $background_checks[0]; ?>)</a></li>
		</ul>
	</li>
	<?php
		function find_number_of_users($franchise, $type){
			$safe_type = mysql_real_escape_string($type);
			$sql = "SELECT COUNT(*) FROM `users` NATURAL JOIN `user_role` WHERE `Status` = 'ACTIVE' AND `ApplicationStatus` = 'APPROVED' AND `Role` = '$safe_type' AND users.UserID IN (SELECT UserID FROM user_role WHERE FranchiseID = $franchise);";
			$rows = mysql_fetch_array( mysql_query($sql) );
			return $rows[0];
		}
		
	?>
	<li id="ApprovedApplications">
		<b>Approved Users - Complete (<?php echo $riders + $drivers + $supporters + $referrals + $careFacilityAdmins; ?>)</b> - <a href="users.php?type=all">View All</a> <a href="add_user.php">Add New User</a><br>
		<ul id="ApprovedApplications_collapse">
			<li><a href="users.php?type=riders">Riders (<?php echo $riders; ?>)</a></li>
			<li><a href="users.php?type=drivers">Drivers (<?php echo $drivers; ?>)</a></li>
			<li><a href="users.php?type=supporters">Supporting Friends (<?php echo $supporters; ?>)</a></li>
			<li>Referral Sources (<?php echo $referrals; ?>)</li>
			<li><a href="users.php?type=carefacilityadmins">Care Facility Admins (<?php echo $careFacilityAdmins; ?>)</a></li>
		</ul>
	</li>

	<li id="UsersNeedingUpdates">
		<b>Users Needing Updates (<?php echo $riders_prefs + $rider_contact + $driver_settings + $driver_contact + $annual_fee_due; ?>)</b><br>
		<ul id="UsersNeedingUpdates_collapse">
			<li><a href="user_updates.php?type=riderpreferences">Riders Needing Preferences (<?php echo $riders_prefs; ?>)</a></li>
			<li><a href="user_updates.php?type=ridercontact">Riders Needing Emergency Contact (<?php echo $rider_contact; ?>)</a></li>
			
			<li><a href="user_updates.php?type=driversettings">Drivers Needing settings (<?php echo $driver_settings; ?>)</a></li>
			<li><a href="user_updates.php?type=drivercontact">Drivers Needing Emergency Contact (<?php echo $driver_contact; ?>)</a></li>
			<li><a href="user_updates.php?type=annualfee">Annual Fee Due (<?php echo $annual_fee_due; ?>)</a></li>
		</ul>
	</li>
	<li><b>Inactive And Rejected Users (<?php echo $inactive_users[0]; ?>)</b> - <a href="users.php?type=inactive">View All</a></li>
</ul>
<?php
	$sql = "SELECT COUNT(*) FROM donation WHERE FranchiseID = $safe_franchise AND PaymentReceived = 'N' OR DonorThanked = 'N' AND `DonationTime` != '0000-00-00';";
	$pending = mysql_fetch_array( mysql_query( $sql ) );
	$sql = "SELECT COUNT(*) FROM donation WHERE FranchiseID = $safe_franchise AND PaymentReceived = 'Y' AND DonorThanked = 'Y' AND `DonationTime` != '0000-00-00';";
	$completed = mysql_fetch_array( mysql_query( $sql ) );
	$sql = "SELECT COUNT(*) FROM donation WHERE FranchiseID = $safe_franchise AND `DonationTime` = '0000-00-00';";
	$canceled = mysql_fetch_array( mysql_query( $sql ) );
?>
<span id="Donations" style="font-size:1.2em">+ Work With Donations ( <?php echo $pending[0]; ?> )</span><br>
<ul id="Donations_collapse">
	<li><a href="donations.php?type=pending">Pending Donations(<?php echo $pending[0]; ?>)</a></li>
	<li><a href="donations.php?type=completed">Completed Donations(<?php echo $completed[0]; ?>)</a></li>
	<li><a href="donations.php?type=canceled">Canceled Donations(<?php echo $canceled[0]; ?>)</a></li>
</ul>
<?php
	$sql = "SELECT COUNT(*) FROM destination WHERE IsPublic = 'Yes' AND IsPublicApproved = 'No' AND FranchiseID = $safe_franchise;";
	$result = mysql_fetch_array( mysql_query( $sql ) );
?>
<span id="Reports" style="font-size:1.2em">+ Work With Reports</span><br>
<ul id="Reports_collapse">
	<li><a href="reports.php">View Reports</a></li>
	<li><a href="table_reports.php">View Monthly Table Reports</a></li>
    <li><a href="donation_reports.php">View Donation Reports</a></li>
    <li><a href="driver_payout_report.php">View Driver Payout Reports</a></li>
    <li><a href="admin_driver_availability.php">View Driver Availability Reports</a></li>
</ul>
<span id="Places" style="font-size:1.2em">+ Work With Places ( <?php echo $result[0]; ?> )</span><br>
<ul id="Places_collapse">
	<li><a href="places.php">Approve/Reject Public Places (<?php echo $result[0]; ?>)</a></li>
	<li><a href="geocode_verify.php">Geocode Address Verfying</a></li>
</ul>
<span id="CareFacilities" style="font-size:1.2em">+ Work With Care Facilities</span><br>
<ul id="CareFacilities_collapse">
	<li><a href="care_facilities.php">View Care Facilities</a></li>
</ul>
<span id="LargeFacilities" style="font-size:1.2em">+ Work With Large Facilities</span><br>
<ul id="LargeFacilities_collapse">
	<li><a href="admin_large_facilities.php">View Large Facilities</a></li>
</ul>
<span id="FrontPage" style="font-size:1.2em">+ Update Inserts</span><br>
<ul id="FrontPage_collapse">
	<li><a href="index.php">View FrontPage</a></li>
	<li><a href="frontpage.php?action=add">Add New Frontpage Post</a></li>
	<li><a href="home.php">View Inserts</a></li>
	<li><a href="insert.php?action=add">Add New Insert</a></li>
</ul>
<span id="Priceing" style="font-size:1.2em">+ Work With Pricing</span><br>
<ul id="Priceing_collapse">
	<li><a href="admin_pricing.php">View Rate Cards</a></li>
	<li><a href="admin_driver_payout.php">View Driver Rate Cards</a></li>
</ul>
<span id="Scheduling" style="font-size:1.2em">+ Work With Scheduling</span><br>
<ul id="Scheduling_collapse">
	<li><a href="admin_scheduling_lockout.php">Set Scheduling Lockouts</a></li>
    <li><a href="admin_weather_delay.php">Weather Delays</a></li>
</ul>

<?php 
	$request_for_supporters =  get_supporter_request_for_rider_count();
?>
<span id="SupportRequests" style="font-size:1.2em">+ Support Requests ( <?php echo $request_for_supporters; ?> )</span><br>
<ul id="SupportRequests_collapse">
    <li><a href="admin_support_requests.php">Pending Support Requests (<?php
            echo $request_for_supporters; ?>)</a></li>
</ul>
<span id="BusinessPartners" style="font-size:1.2em">+ Business Partners</span><br>
<ul id="BusinessPartners_collapse">
    <li><a href="admin_business_partners.php">Business Partners</a></li>
</ul>
<span id="Charities" style="font-size:1.2em">+ Charities ( <?php echo $pending_user_charities[0]; ?> )</span><br>
<ul id="Charities_collapse">
    <li><a href="admin_charity_request.php">Pending / Available Charities( <?php echo $pending_user_charities[0]; ?> )</a></li>
</ul>
<span id="Clubs" style="font-size:1.2em">+ Clubs</span><br>
<ul id="Clubs_collapse">
    <li><a href="club.php">Edit Club</a> - Edits the club you are assigned to currently</li>
    <li><a href="driver_availability_report.php">Driver Availability Report</a> - Lists currently active drivers</li>
    
</ul>
</ul>
<br />


<script type="text/javascript">
	var collapsables = ['Priceing','Scheduling','Individuals','Donations','Reports','Places','CareFacilities','LargeFacilities',
                        'FrontPage','PendingApplications','ApprovedApplications','UsersNeedingUpdates', 
                        'SupportRequests', 'BusinessPartners','Charities', 'Clubs'];
	collapsables.each( function(item){
	    
	    document.getElementById(item).className = 'hidden_dropdown';
		$(item).addEvent('click', function(e){
			if($(this.id + '_collapse').getStyle('display') == 'none'){
				$(this.id + '_collapse').setStyle('display','block');
				e.target.className = 'visible_dropdown';
			} else {
				$(this.id + '_collapse').setStyle('display','none');
				e.target.className = 'hidden_dropdown';
			}
		});
		$(item + '_collapse').setStyle('display','none');
		$(item).setStyle('cursor','pointer');
		
	});
</script>

<?php
	include_once 'include/footer.php';
?>
