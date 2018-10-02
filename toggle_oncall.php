<?php
	include_once 'include/user.php';
	include_once 'include/care_facility.php';
	include_once 'include/driver.php';

	redirect_if_not_logged_in();
    
	$franchise = get_current_user_franchise();
	if(isset($_GET['id']) && $_GET['id'] != '')
	{
		if(!current_user_has_role($franchise, 'FullAdmin') && !care_facility_admin_has_rights_over_user($_GET['id']) && !current_user_has_role(1, 'FullAdmin') && !current_user_has_role($franchise, 'FullAdmin')){
			header("Location: home.php");
			die();
		}	
		$user_id = $_GET['id'];
		$edit_url = "&id=" . $user_id;
	}
	else
	{
		$user_id = get_affected_user_id();
		$edit_url = "";
	}
	
	
	
	driver_toggle_oncall($user_id);
	
	header('Location: /account.php#oncall');
	
	?>