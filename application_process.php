<?php
    include_once 'include/user.php';
	include_once 'include/driver.php';
	include_once 'include/rider.php';
	include_once 'include/email.php';
	include_once 'include/name.php';
	include_once 'include/care_facility.php';
	require_once 'include/franchise.php';
	redirect_if_not_logged_in();
	
	$franchise = get_current_user_franchise();
	if(!current_user_has_role(1 , "FullAdmin") && !current_user_has_role($franchise, "Franchisee")){
		header("Location: home.php");
		die();	
	}
	
	if(!isset($_GET['id']))
		header("location: " . site_url());
	if(isset($_GET['action']) && $_GET['action'] == 'approve'){
		
		$query = "UPDATE `users` SET `ApprovalDate` = NOW(), `ApplicationStatus` = 'APPROVED', `BackgroundCheck` = 'CHECKED' WHERE `UserID` = '" . mysql_real_escape_string($_GET['id']) . "' LIMIT 1 ;";
		mysql_query($query) or die(mysql_error());
		
		if(get_user_driver_info($_GET['id'])){
			$driver = get_user_driver_info($_GET['id']);
			$query = "UPDATE `driver` SET `DriverStatus` = 'Active' WHERE `UserID` = " . mysql_real_escape_string($driver['UserID']) . " LIMIT 1;";
			mysql_query($query);
		}
		if(get_user_rider_info($_GET['id'])){
			$rider = get_user_rider_info($_GET['id']);
			$query = "UPDATE `rider` SET `RiderStatus` = 'Active' WHERE `UserID` = " . mysql_real_escape_string($rider['UserID']) . " LIMIT 1;";
			mysql_query($query);
			
			create_rider_default_home($rider['UserID']);
		}
		
		$user = get_user_account( $_GET['id'] );
		$name = get_name($user['PersonNameID']);
		$name = $name['FirstName'] . " " . $name['LastName'];
		
		$if_care_user = get_first_user_care_facility( $_GET['id']);
		if($if_care_user){
			$facility = get_care_facility($if_care_user);
			$message = "\nDear " . $facility['CareFacilityName'] . ",\n\nWe are pleased to inform you that $name has passed the background check and once you fill out their preferences they can began taking rides.\n\nRiders Club of America\n\nIf you have further questions about our service, please contact the office at 319.365.1511.  We have staff available Monday through Friday, between 9:00 a.m. and 3:00 p.m.\n\nThank-you";
		} else{
			$login_hash = sha1($user['Salt'] . $user['UserName'] . $user['Password']);
			$login_url = site_url() . 'new_password.php?id=' . $_GET['id'] . '&hash=' . $login_hash;
			$message = "\nDear $name,\n\nWe are pleased to inform you that you have passed the background check and we are ready for more information. Please follow the link below to change the password to your new account and to enter the rest of your information.\n\n$login_url\n\nRiders Club of America\n\nIf you have further questions about our service, please contact the office at 319.365.1511.  We have staff available Monday through Friday, between 9:00 a.m. and 3:00 p.m.\n\nThank-you";

		}
		$user_email = get_email_address($user['EmailID']);
		mail($user_email['EmailAddress'],'Riders Club of America - Application',$message, DEFAULT_EMAIL_FROM);
		
		header("location: " . site_url() . 'users.php?type=applicants');		
	} else if(isset($_GET['action']) && $_GET['action'] == 'reject'){
		
		$query = "UPDATE `users` SET `Status` = 'INACTIVE', `ApplicationStatus` = 'REJECTED' WHERE `UserID` = '" . mysql_real_escape_string($_GET['id']) . "' LIMIT 1;";
		mysql_query($query) or die(mysql_error());
		
		$user = get_user_account( $_GET['id'] );
		$name = get_name($user['PersonNameID']);
		$name = $name['FirstName'] . " " . $name['LastName'];
		$if_care_user = get_first_user_care_facility( $_GET['id']);
		if($if_care_user){
			$facility = get_care_facility($if_care_user);
			$message = "\nDear " . $facility['CareFacilityName'] . ",\n\nWe are sorry to inform you that " . $name . " have been denied their request for a Riders Club membership.\n\nRiders Club of America\n\nIf you have further questions about our service, please contact the office at 319.365.1511.  We have staff available Monday through Friday, between 9:00 a.m. and 3:00 p.m.\n\nThank-you";
		} else {
			$message = "\nDear $name,\n\nWe are sorry to inform you that you have been denied your request for a Riders Club membership.\n\nRiders Club of America\n\nIf you have further questions about our service, please contact the office at 319.365.1511.  We have staff available Monday through Friday, between 9:00 a.m. and 3:00 p.m.\n\nThank-you";

		}
				$user_email = get_email_address($user['EmailID']);
		mail($user_email['EmailAddress'],'Riders Club of America - Application',$message, DEFAULT_EMAIL_FROM);
		
		header("location: " . site_url() . 'users.php?type=applicants');
		
	} else {
		header("location: " . site_url());
	}
?>
