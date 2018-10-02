<?php
// This is now duplicated as select_club.php
// This copy should not be being used
	include_once('include/user.php');
	include_once('include/franchise.php');

	redirect_if_not_logged_in();


	$user_id = get_current_user_id();
	
	$user_franchises = get_user_franchises($user_id);
	
	
	if(count($user_franchises) < 1 || !$user_franchises)
		$announce = "Warning! You are not connected with a club. Please contact your local office.";
	else if(count($user_franchises) == 1){
		$_SESSION['UserFranchiseID'] = $user_franchises[0]['FranchiseID'];		
		if(isset($_SESSION['RedirectURL']))
			header("location: " . $_SESSION['RedirectURL']);
		header("location: home.php");
		unset($_SESSION['RedirectURL']);
		die();
	} else if(!isset($_SESSION['UserFranchiseID']) AND $default_franchise = get_user_default_franchise($user_id)){
		
		$_SESSION['UserFranchiseID'] = $default_franchise;		
		if(isset($_SESSION['RedirectURL']))
			header("location: " . $_SESSION['RedirectURL']);
		header("location: home.php");
		unset($_SESSION['RedirectURL']);
		die();
			
	}
	
	if($_POST['Franchise']){
		$_SESSION['UserFranchiseID'] = $_POST['Franchise'];
		
		if($_POST['SaveFranchise'])
			save_user_default_franchise($user_id, $_POST['Franchise']);
		
		unset($_SESSION['admin_dropdown_array']);
		unset($_SESSION['admin_dropdown_time']);
		if(isset($_SESSION['RedirectURL']))
			header("location: " . $_SESSION['RedirectURL']);
		header("location: home.php");
		die();
	}
	include_once('include/header.php');
?>
<center><h2>Select a club</h2></center>
<?php
	if($announce) echo "<div class=\"reminder\">$announce</div><br>";
?>
You are linked to more then one club in our system. Please select a club.<br>
<form method="post">
	<select name="Franchise">
    	<?php
			foreach($user_franchises as $f){
				echo "<option value=\"{$f['FranchiseID']}\">" . $f['FranchiseName'] . "</option>";
			}
		?>
    </select>
    <input type="submit" value="Select"><br>
    <label><input type="checkbox" name="SaveFranchise">Save As Default</label>
</form>
<br>
<br>
Or, <a href="logout.php">Log Out</a>
<?php
	include_once("include/footer.php");
	
?>