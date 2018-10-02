<?php
	chdir('..');
	require_once('include/user.php');
	require_once('include/date_time.php');
    require_once('include/rc_log.php');
	require_once 'include/franchise.php';
	
	$userid = $_GET['userid'];
	$field = $_GET['field'];
	$value = $_GET['value'];
	
	$table_fields = array('WelcomePackageSentDate' => 'rider',
									'FirstRideDate' => 'rider',
									'FirstRideFollowupDate' => 'rider',
									'RiderWaiverReceived' => 'rider',
									'DriverApprovalDate' => 'driver',
									'FirstDriveFollowup' => 'driver',
									'WelcomePackageSent' => 'driver',
									'DriverAgreementRecorded' => 'driver',
									'PolicyExpirationDate' => 'driver_insurance',
									'LicenseExpireDate' => 'driver',
									'InsuranceVerified' => 'driver_insurance',
									'RiderPictureWaiver' => 'rider',
									'DriverPictureWaiver' => 'driver');
	function row_exists($user_id, $field){
		global $table_fields;
		$safe_user_id = mysql_real_escape_string($user_id);
		$sql = "SELECT * FROM {$table_fields[$field]} WHERE UserID = $safe_user_id LIMIT 1;";
		$result = mysql_query($sql) or die($sql);
		if(mysql_num_rows($result) > 0)
			return true;
		return false;
	}
	
	
	if(!is_logged_in())
		die('!Error! You must be logged in to edit.');
	$franchise = get_current_user_franchise();
	if(!current_user_has_role(1, 'FullAdmin') && !current_user_has_role($franchise, 'Franchisee'))
		die('!Error! You must be an admin to edit.');
	if(!array_key_exists($field, $table_fields))
		die('!Error! Field does not exist or is not editable.');
	if(format_date($value,'Y-m-d') == '1969-12-31')
		die('!Error! The date was not formatted correctly');
	if(!row_exists($userid,$field))
		die('!NOROW! This user does not have this data created yet. Please go to their account page to enter it.');
	if($value == ''){
		$date = 'NULL';
	} else {
		$date = "'" . mysql_real_escape_string(format_date($value,'Y-m-d')) . "'";
	}

    $identity = array('field' => 'UserID', 'id' => mysql_real_escape_string($userid));
		
	$sql = "UPDATE `{$table_fields[$field]}` SET `$field` = $date 
            WHERE `{$identity['field']}`={$identity['id']} LIMIT 1;";
	$result = mysql_query($sql) or die(mysql_error() . $sql);
	
	if($result){
		echo format_date($value,'m/d/y');
	} else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
	                	"Error updating user dates", $sql);
		die('!Error! There was a problem updating the database. The date may or may not have been saved.');
	}
	chdir('xhr/');
?>
