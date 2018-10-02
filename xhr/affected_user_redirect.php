<?php
	chdir('..');
	require_once 'include/user.php';
	require_once 'include/franchise.php';
	if(!is_logged_in())
		die();
	session_start();
	
	$franchise = get_current_user_franchise();
	
	if(!current_user_has_role(1, 'FullAdmin') && !current_user_has_role($franchise, 'Franchisee'))
		die();
	$redirect_url = $_GET['redirect'];
	$new_affected_user = $_GET['userid'];
	
	set_affected_user_id($new_affected_user);
	
	header("Location: $redirect_url");
	chdir('xhr/');
?>