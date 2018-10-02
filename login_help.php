<?php 
	include_once 'include/user.php';
	include_once 'include/name.php';
	include_once 'include/email.php';

	if(!isset($_GET['field'])){
		header("location:" . site_url());
	} else if($_GET['field'] == 'password'){
		$header = "Find out your password";
		$explination = "Please fill in your Username and we will send an email to you to get your password changed.";
		$input_box = "Username";
		if(isset($_POST[$input_box])){
			$safe_user_name = mysql_real_escape_string(strtolower($_POST[$input_box]));
			$sql = "SELECT * FROM `users` WHERE `UserName` = '$safe_user_name' LIMIT 1;";
			$result = mysql_query($sql);
			if($result){
				if(mysql_num_rows($result) < 1){
					$success = true;
				} else {
					$user = mysql_fetch_array($result);
					$user = get_user_account($user['UserID']);
					$email = get_email_address($user['EmailID']);
					$name = get_name($user['PersonNameID']);
					$name = $name['FirstName'] . ' ' . $name['LastName'];
					
					$hash = sha1($user['Salt'] . $user['UserName'] . $user['Password']);
					
					$link = site_url() . "new_password.php?id=" . $user['UserID'] . "&hash=$hash";
					mail($email['EmailAddress'], 'Password Change Request - Riders Club of America', "Dear $name,\n\nWe have been informed that you need your password reset. Please follow the link below to change your password and log into your account.\n\n$link\n\nRiders Club of America\n\nIf you have further questions about our service, please contact the office at 319.365.1511.  We have staff available Monday through Friday, between 9:00 a.m. and 3:00 p.m.\n\nThank-you", DEFAULT_EMAIL_FROM);
					
					$success = true;
				}
			}
		}
	} else if($_GET['field'] == 'username'){
		$header = "Find out your username";
		$explination = "Please fill in your Email Address so we can send you a email with your Username in it.";
		$input_box = "Email";
		if(isset($_POST[$input_box])){
			$safe_user_email = mysql_real_escape_string(strtolower($_POST[$input_box]));
			$sql = "SELECT * FROM `users` NATURAL JOIN `email` WHERE `EmailAddress` = '$safe_user_email' LIMIT 1;";
			$result = mysql_query($sql);
			if($result){
				if(mysql_num_rows($result) < 1){
					$success = true;
				} else {
					$user = mysql_fetch_array($result);
					$user = get_user_account($user['UserID']);
					$email = get_email_address($user['EmailID']);
					$name = get_name($user['PersonNameID']);
					$name = $name['FirstName'] . ' ' . $name['LastName'];
					
					$hash = sha1($user['Salt'] . $user['UserName'] . $user['Password']);
					
					$link = site_url() . "new_password.php?id=$user_id&hash=$hash";
					mail($email['EmailAddress'], 'Password Change Request - Riders Club of America', "Dear $name,\n\nWe have been informed that you need your username.\n\nYour username is: " . $user['UserName'] . "\n\nRiders Club of America\n\nIf you have further questions about our service, please contact the office at 319.365.1511.  We have staff available Monday through Friday, between 9:00 a.m. and 3:00 p.m.\n\nThank-you", DEFAULT_EMAIL_FROM);
					
					$success = true;
				}
			}
		}
	}
    include_once 'include/header.php';
?>
<h2><center><?php echo $header; ?></center></h2>
<center>
	<?php
		if($success == true)
			echo "Success! Please check your email account for instructions on how to find your Username and/or Password.";
		else
			echo $explination; ?>
</center><br>
<form method="post" action="<?php echo $SERVER['PHP_SELF']; ?>?field=<?php echo $_GET['field']; ?>">
	<table style="margin:auto;">
		<tr>
			<td><?php echo $input_box; ?>:</td>
			<td><input type="text" name="<?php echo $input_box; ?>"></td>
		</tr>
		<tr>
			<td class="alignright" colspan="2"><input type="submit" name="Save" value="Submit" /></td>
		</tr>
	</table>
</form>
<?php
	include_once 'include/footer.php';
?>
