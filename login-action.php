<?php
    include_once 'include/database.php';
	include_once 'include/functions.php';
	include_once 'include/user.php';
	
    if(is_logged_in())
        header('location: home.php');
    if(!isset($_POST['UserName']) || !isset($_POST['Password']))
        header('location: login.php?login=failed');

    db_connect_readonly();
    $query = "SELECT * FROM `users` LEFT JOIN `email` ON (users.EmailID = email.EmailID) WHERE `UserName` = '" . mysql_real_escape_string(strtolower($_POST['UserName'])) . "' or EmailAddress = '" . mysql_real_escape_string(strtolower($_POST['UserName'])) . "' LIMIT 1;";
    $result = mysql_query($query) or die('failed query: ' . $query . ' :' . mysql_error());
    if(mysql_num_rows($result) == 0)
        header('location: login.php?login=failed');
	else
	{
		$userInfo = mysql_fetch_array($result);
		if($userInfo['Password'] == sha1($userInfo['Salt'] . $_POST['Password']) && $userInfo['Status'] == 'ACTIVE' && $userInfo['ApplicationStatus'] == 'APPROVED')
		{
			if(isset($_POST['Remember']) && $_POST['Remember'] == TRUE){
				setcookie('UserName',$_POST['UserName']);
			} else {
				setcookie('UserName','',time() - 10000);
				unset($_COOKIE['Username']);
			}
			login_user($userInfo['UserID']);
			
			if(isset($_SESSION['RedirectURL'])){
				header("location: {$_SESSION['RedirectURL']}");
			} else {
				header('location: home.php');
			}
    		
		}
    	else
			header('location: login.php?login=failed');
	}
?>