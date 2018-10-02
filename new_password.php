<?php
	include_once 'include/functions.php';
    include_once 'include/user.php';
	
	if(!isset($_GET['id']) || !isset($_GET['hash']))
		header("location: " . site_url());
		
	$user_id = $_GET['id'];
	$user_hash = $_GET['hash'];
	$user = get_user_account( $_GET['id'] );
	$test_hash = sha1($user['Salt'] . $user['UserName'] . $user['Password']);
	
	if($test_hash == $user_hash && (isset($_POST['NewPassword']) && isset($_POST['RetypePassword']))){

		if($_POST['NewPassword'] == $_POST['RetypePassword']){
			
			$password = sha1($user['Salt'] . $_POST['NewPassword']);
			$safe_user_id = mysql_real_escape_string($_GET['id']);
			$sql = "UPDATE `users` SET `Password` = '$password' WHERE `UserID` =$safe_user_id LIMIT 1 ;";
			mysql_query($sql) or die('An error has occured while updating your password');
			
			login_user($user['UserID']);
    		header('location: ' . site_url() . 'home.php');
		} else {
			$error = "Your retyped password and your new password did not match.<br>";
		}
		
		
	} else if($test_hash != $user_hash){
		$error = "This page has expired. Please request a new password changing link.<br>";
	}
	include_once 'include/header.php';
?>
<form action="<?php echo $_SERVER['PHP_SELF'] . '?id=' . $_GET['id'] . '&hash=' . $_GET['hash']; ?>" method="post">
	<center><h2>Pick A New Password</h2></center>
	<center><?php echo $error; ?></center><br>
	<table style="margin:auto;">
		<tr>
			<td class="alignright">New Password</td>
			<td><input name="NewPassword" type="password"></td>
		</tr>
		<tr>
			<td class="alignright">Retype Password</td>
			<td><input name="RetypePassword" type="password"></td>
		</tr>
		<tr>
			<td class="alignright" colspan="2"><input type="submit" name="Save" value="Save" /></td>
		</tr>
	</table>
</form>
<?php
	include_once 'include/footer.php';
?>
