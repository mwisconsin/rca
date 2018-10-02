<?php
	chdir('..');
	include_once("include/user.php");
	include_once("include/name.php");
	if(!is_logged_in())
		die();
	$id = $_REQUEST['id'];
	if(!$_REQUEST['id']){
		echo "{\"namestring\":\"error\"}";
		die();
	}
	$name = get_user_person_name($id);
	
	echo "{\"namestring\":\"" . get_displayable_person_name_string($name) . "\", \"first\":\"{$name['FirstName']}\", \"middle\":\"{$name['MiddleInitial']}\", \"last\":\"{$name['LastName']}\"}";
	
	chdir('xhr/');
?>