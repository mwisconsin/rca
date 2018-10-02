<?php
    include_once 'include/user.php';
	require_once 'include/franchise.php';
	
	redirect_if_not_logged_in();
	
	$franchise = get_current_user_franchise();
	
	if(current_user_has_role(1, "FullAdmin") || current_user_has_role($franchise, "Franchisee")){
		header("Location: home.php");
		die();	
	}
	
	include_once 'include/header.php';
	
	if(isset($_GET['type']) &&  $_GET['type'] == 'riderpreferences' ){
		$sql = "SELECT users.UserID, users.PersonNameID FROM (rider LEFT JOIN rider_preferences ON rider.UserID = rider_preferences.UserID) LEFT JOIN users ON users.UserID = rider.UserID WHERE rider_preferences.UserID IS NULL AND rider.RiderStatus = 'Active' AND users.Status = 'Active' AND
      users.UserID IN (SELECT UserID FROM user_role WHERE FranchiseID = $franchise)";
		$result = mysql_query($sql) or die(mysql_error());
	?>
		<h2>Riders Needing Preferences</h2>
		<table border="1" width="100%">
			<tr>
				<td>User ID</td><td>Last Name</td><td>First Name</td><td width="200px">Profile</td><td>Create Preferences</td>
			</tr>
			<?php
				while($row = mysql_fetch_array($result)){
					$name = get_name( $row['PersonNameID'] );
				?>
					<tr>
						<td><a href="account.php?id=<?php echo $row['UserID']; ?>"><?php echo $row['UserID']; ?></a></td>
						<td><?php echo $name['LastName']; ?></td>
						<td><?php echo $name['FirstName']; ?></td>
						<td><a href="account.php?id=<?php echo $row['UserID']; ?>"><button>View <?php echo $name['FirstName']; ?>'s Profile</button></a></td>
						<td><a href="edit_user.php?field=createriderpreferences&id=<?php echo $row['UserID']; ?>"><button>Set <?php echo $name['FirstName']; ?>'s Preferences</button></a></td>
					</tr>
				<?php
				}
			?>
		</table>
	<?php
	} else if(isset($_GET['type']) &&  $_GET['type'] == 'ridercontact' ){
		$sql = "SELECT users.UserID, users.PersonNameID FROM rider LEFT JOIN users ON rider.UserID = users.UserID WHERE rider.EmergencyContactID IS NULL AND users.Status = 'Active' AND 
      rider.RiderStatus = 'Active' AND 
      users.UserID IN (SELECT UserID FROM user_role WHERE FranchiseID = $franchise)";
		$result = mysql_query($sql);
	?>
		<h2>Riders Needing Emergency Contact</h2>
		<table border="1" width="100%">
			<tr>
				<td>User ID</td><td>Last Name</td><td>First Name</td><td>Profile</td><td>Create Emergency Contact</td>
			</tr>
			<?php
				while($row = mysql_fetch_array($result)){
					$name = get_name( $row['PersonNameID'] );
				?>
					<tr>
						<td><a href="account.php?id=<?php echo $row['UserID']; ?>"><?php echo $row['UserID']; ?></a></td>
						<td><?php echo $name['LastName']; ?></td>
						<td><?php echo $name['FirstName']; ?></td>
						<td><a href="account.php?id=<?php echo $row['UserID']; ?>"><button>View <?php echo $name['FirstName']; ?>'s Profile</button></a></td>
						<td><a href="edit_user.php?field=createrideremergencycontact&id=<?php echo $row['UserID']; ?>"><button>Set <?php echo $name['FirstName']; ?>'s Emergency Contact</button></a></td>
					</tr>
				<?php
				}
			?>
		</table>
	<?php
	} else if(isset($_GET['type']) &&  $_GET['type'] == 'driverpreferences' ){
		$sql = "SELECT users.UserID, users.PersonNameID FROM (driver NATURAL JOIN users) LEFT JOIN driver_settings ON driver_settings.UserID = users.UserID WHERE driver_settings.UserID IS NULL AND
      users.status = 'Active' AND driver.DriverStatus = 'Active' AND
      users.UserID IN (SELECT UserID FROM user_role WHERE FranchiseID = $franchise)";
		$result = mysql_query($sql);
	?>
		<h2>Drivers Needing Settings</h2>
		<table border="1" width="100%">
			<tr>
				<td>User ID</td><td>Last Name</td><td>First Name</td><td>Profile</td><td>Create Settings</td>
			</tr>
			<?php
				while($row = mysql_fetch_array($result)){
					$name = get_name( $row['PersonNameID'] );
				?>
					<tr>
						<td><a href="account.php?id=<?php echo $row['UserID']; ?>"><?php echo $row['UserID']; ?></a></td>
						<td><?php echo $name['LastName']; ?></td>
						<td><?php echo $name['FirstName']; ?></td>
						<td><a href="account.php?id=<?php echo $row['UserID']; ?>"><button>View <?php echo $name['FirstName']; ?>'s Profile</button></a></td>
						<td><a href="edit_user.php?field=createdriversettings&id=<?php echo $row['UserID']; ?>"><button>Set <?php echo $name['FirstName']; ?>'s Settings</button></a></td>
					</tr>
				<?php
				}
			?>
		</table>
	<?php	
	} else if(isset($_GET['type']) &&  $_GET['type'] == 'drivercontact' ){
		$sql = "SELECT users.UserID, users.PersonNameID FROM driver LEFT JOIN users ON driver.UserID = users.UserID WHERE driver.EmergencyContactID IS NULL AND users.Status = 'Active' AND driver.DriverStatus = 'Active' AND
      users.UserID IN (SELECT UserID FROM user_role WHERE FranchiseID = $franchise)";
		$result = mysql_query($sql);
	?>
		<h2>Drivers Needing Emergency Contacts</h2>
		<table border="1" width="100%">
			<tr>
				<td>User ID</td><td>Last Name</td><td>First Name</td><td>Profile</td><td>Create Emergency Contact</td>
			</tr>
			<?php
				while($row = mysql_fetch_array($result)){
					$name = get_name( $row['PersonNameID'] );
				?>
					<tr>
						<td><a href="account.php?id=<?php echo $row['UserID']; ?>"><?php echo $row['UserID']; ?></a></td>
						<td><?php echo $name['LastName']; ?></td>
						<td><?php echo $name['FirstName']; ?></td>
						<td><a href="account.php?id=<?php echo $row['UserID']; ?>"><button>View <?php echo $name['FirstName']; ?>'s Profile</button></a></td>
						<td><a href="/edit_user.php?field=createdriveremergencycontact&id=<?php echo $row['UserID']; ?>"><button>Set <?php echo $name['FirstName']; ?>'s Emergency Contact</button></a></td>
					</tr>
				<?php
				}
			?>
		</table>
	<?php	
	} else if(isset($_GET['type']) &&  $_GET['type'] == 'annualfee' ){
		$sql = "SELECT users.UserID, users.PersonNameID 
			FROM (`rider` LEFT JOIN users ON rider.UserID = users.UserID) 
			WHERE AnnualFeePaymentDate < DATE_ADD(DATE_ADD(CURDATE(), INTERVAL -1 YEAR), INTERVAL -45 DAY)
			AND users.UserID IN (SELECT UserID FROM user_role WHERE FranchiseID = $franchise)
			And rider.riderStatus = 'Active'";
		$result = mysql_query($sql);
	?>
		<h2>Riders With Fees Due</h2>
		<table border="1" width="100%">
			<tr>
				<td>User ID</td><td>Last Name</td><td>First Name</td><td>Profile</td>
			</tr>
			<?php
				while($row = mysql_fetch_array($result)){
					$name = get_name( $row['PersonNameID'] );
				?>
					<tr>
						<td><a href="account.php?id=<?php echo $row['UserID']; ?>"><?php echo $row['UserID']; ?></a></td>
						<td><?php echo $name['LastName']; ?></td>
						<td><?php echo $name['FirstName']; ?></td>
						<td><a href="account.php?id=<?php echo $row['UserID']; ?>"><button>View <?php echo $name['FirstName']; ?>'s Profile</button></a></td>
						
					</tr>
				<?php
				}
			?>
		</table>
	<?php	
	}
?>

<?php
	include_once 'include/footer.php';
?>
