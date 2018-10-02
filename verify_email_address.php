<?php
    include_once 'include/email.php';
	
	if(isset($_GET['id']) && isset($_GET['hash']) && $_GET['id'] != '' && $_GET['hash'] != ''){
		$email = get_email_address($_GET['id']);
		if($_GET['hash'] == sha1('Verify' . $email['EmailID'] . $email['EmailAddress'] . $email['IsVerified'])){
			$safe_id = mysql_real_escape_string($email['EmailID']);
			$sql = "UPDATE `email` SET `IsVerified` = 'Yes' WHERE `EmailID` =$safe_id LIMIT 1 ;";
			mysql_query($sql) or die('An Error has occured. Try again later');
			$message = "Thank You! We have now verified your email address.";
		} else {
			$message = "This is an invalid link. Please try again later.";
		}
	} else {
		$message = "This is an invalid link. Please try again later.";
	}
	include_once 'include/header.php';
?>
<center>
	<h2>Verify Your Email</h2>
	<br>
	<?php echo $message; ?>
</center>
<?php
	include_once 'include/footer.php';
?>
