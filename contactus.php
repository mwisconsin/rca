<?php

	require_once('../private_include/riders_club_params.php');
	require_once('include/franchise.php');
	require_once 'include/user.php';

	$Email = DEFAULT_ADMIN_EMAIL;
	$Phone = '( 319 ) 365 - 1511';
	if(is_logged_in())
	{
	$user_id = get_affected_user_id();
    $franchise_id = get_current_user_franchise($user_id);
	$Email = getFranchisePrimaryContactEmail($franchise_id);
	$Phone = getFranchiseMainPhoneNumber($franchise_id);
	}

	if(isset($_POST['Name']) && $_POST['Name'] != '' && isset($_POST['ReturnEmail']) && $_POST['ReturnEmail'] != '' && isset($_POST['Subject'])&& $_POST['Subject'] != '' && isset($_POST['Message']) && $_POST['Message'] != ''){
		if (mail($Email,
                 'Contact Us Form : ' . $_POST['Subject'],
                 "From: " . $_POST['Name'] . "\nReturn Address: " . $_POST['ReturnEmail'] . "\n\n" . $_POST['Message'],
                 "From:" . $_POST['ReturnEmail'])){
			header("location:" . $_SERVER['PHP_SELF'] . "?result=sent");
		} else {
			header("location:" . $_SERVER['PHP_SELF'] . "?result=failed");
		}
		
	}
	else if((isset($_POST['Name']) && $_POST['Name'] == '') || isset($_POST['ReturnEmail']) && $_POST['ReturnEmail'] == '' || (isset($_POST['Subject']) && $_POST['Subject'] == '') || (isset($_POST['Message']) && $_POST['Message'] == ''))
		$error = 'missing info';
	include_once 'include/header.php';
?>
<h2><center>Contact Us</center></h2>
Please feel free to contact us by one of the following:<br>
<br>
<b>Phone:</b>
<ul>
	Office - <?php echo $Phone; ?>
</ul>
<br>
<b>Email:</b>
<ul>
	<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
		<?php
			if($error == 'missing info')
				echo '<b>You are missing required fields.</b><br>';
			else if(isset($_GET['result']) && $_GET['result'] == "sent")
				echo '<b>Your message has been sent! We will get back to you as soon as possible.</b><br>';
			else if(isset($_GET['result']) && $_GET['result'] == "failed")
				echo '<b>Your message failed while trying to send. We are working on this problem and will get back to you as soon as possible.</b><br>';
		?>
		*Name:<br>
		<input type="text" name="Name" value="<?php echo $_POST['Name']; ?>" style="width:250px;"><br>
		*Return Email Address:<br>
		<input type="text" name="ReturnEmail" value="<?php echo $_POST['ReturnEmail']; ?>" style="width:250px;"><br>
		*Subject:<br>
		<input type="text" name="Subject" value="<?php echo $_POST['Subject']; ?>" style="width:250px;"><br>
		*Message:<br>
		<textarea name="Message" style="width:450px; height:200px;"><?php echo $_POST['Message']; ?></textarea><br>
		<input type="submit" value="Send" style="margin:0px 0px 0px 400px;">
	</form>
</ul>
<?php
	include_once 'include/footer.php';
?>
