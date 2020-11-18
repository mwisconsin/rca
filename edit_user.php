<?php
    include_once 'include/user.php';
	include_once 'include/rider.php';
	include_once 'include/driver.php';
	include_once('include/franchise.php');
	include_once 'include/address.php';
	include_once 'include/name.php';
	include_once 'include/phone.php';
	include_once 'include/email.php';
	include_once 'include/care_facility.php';
	include_once 'include/emergency_contact.php';
	include_once 'include/date_time.php';
    require_once('include/large_facility.php');
	redirect_if_not_logged_in();
	
	$franchise = get_current_user_franchise();
	if(isset($_GET['id'])){
		if(!care_facility_admin_has_rights_over_user($_GET['id']) && !current_user_has_role(1, 'FullAdmin') && (!current_user_has_role($franchise, "Franchisee") && !user_has_franchise($user_id, $franchise) )){
			header("Location: home.php");
			die();
		}
		$user_id = $_GET['id'];
		$edit_url = "&id=" . $user_id;
		if(isset($_GET['redirect']))
			$edit_url .= "&redirect=" . $_GET['redirect'];
	} else {
		if(isset($_GET['redirect']))
			$edit_url = "&redirect=" . $_GET['redirect'];
		$user_id = get_affected_user_id();
	}
	$add_user_birthday = $_SESSION['AddUser_Birthday'];
	function redirect($redirect = TRUE){
		global $user_id;
		if(!isset($_GET['redirect']) || $redirect == FALSE){
			if($_GET['id'])
				header("location: " . site_url() . "account.php?id=" . $user_id);
			else
				header("location: " . site_url() . "account.php");
		} else {
			header("location: " . $_GET['redirect']);
		}
	}
	
	
	if(isset($_GET['field']) && $_GET['field'] == "name")
	{
		if(isset($_POST['Title']) && isset($_POST['FirstName']) && isset($_POST['MiddleInitial']) && isset($_POST['LastName']) && isset($_POST['Suffix']))
		{
			$required_fields = array('FirstName','LastName');
			$required_filled = true;
			
			foreach($required_fields as $k => $v){
				if($_POST[$v] == '')
					$required_filled = false;
			}
			
			if(!$required_filled){
				$error = 'All required name fields were not filled.';
			} else {
                $person_name = get_user_person_name($user_id);
                update_person_name( $person_name['PersonNameID'], $_POST['Title'], $_POST['FirstName'], 
                                    $_POST['MiddleInitial'], $_POST['LastName'], $_POST['Suffix'], $_POST['Nickname']);
				
				redirect();
			}
			
		}

		if(isset($_FILES['image'])) {
			include "imgupload.config.php";
			include "imgupload.class.php";

			$img = new ImageUpload;

			$result = $img->uploadImages($_FILES['image']);

			if(!empty($result->error)){
				foreach($result->error as $errMsg){
					echo $errMsg;
				}
			} else {
				if(!empty($result->ids)){
					foreach($result->ids as $id){
						$sql = "update person_name set profile_image = $id where PersonNameID = ". $person_name['PersonNameID'];
						mysql_query($sql);
					}
				}
				
				
			}
		}

		include_once 'include/header.php';
		
		$name = get_user_person_name($user_id);
		?>
			<center><h2>Edit Name</h2></center>
			<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?field=name' . $edit_url; ?>" enctype="multipart/form-data">
            <?php 
                echo $error; 
                print_get_name_form_part($name, '', TRUE, 'margin:auto; width:400px;'); 
            ?>
			</form>
		<?php
	}
	else if(isset($_GET['field']) && $_GET['field'] == "user" // && (current_user_has_role(1, "FullAdmin") || current_user_has_role($franchise, "Franchisee"))
		)
	{
		
		if(isset($_POST['UserName']) && isset($_POST['Status']))
		{
			$account = get_user_account($user_id);
			$query = "UPDATE `users` SET `UserName` = '" . mysql_real_escape_string($_POST['UserName']) . "', `Status` = '" . mysql_real_escape_string($_POST['Status']) . "' WHERE `users`.`UserID` ='" . mysql_real_escape_string($user_id) . "' LIMIT 1 ;";
			
			if (!mysql_query($query)) {
                rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                            "Cannot update user account", $query);
                $announce = "The specified username is already in use.";
				
            } else
				redirect();
		}
		
			include_once 'include/header.php';
			
			$account = get_user_account($user_id);
			?>
				<center><h2>Edit Account</h2></center>
                <?php if($announce) echo "<div class=\"reminder\">$announce</div>"; ?>
				<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?field=user' . $edit_url; ?>">
					<table style="margin:auto; width:400px;">
						<tr>
							<td class="alignright">User Name</td>
							<td><input type="text" name="UserName" value="<?php echo $account['UserName']; ?>" maxlength="100" ></td>
						</tr>
   						<tr>
							<td class="alignright">Status</td>
							<td>
								<select name="Status">
									<option<?php if($account['Status'] == 'INACTIVE') echo ' SELECTED'; ?> value="INACTIVE">Inactive</option>
									<option<?php if($account['Status'] == 'ACTIVE') echo ' SELECTED'; ?> value="ACTIVE">Active</option>
								</select>
							</td>
						</tr>
						<tr>
							<td class="alignright" colspan="2"><input type="submit" name="Save" value="Save" /></td>
						</tr>
					</table>
				</form>
			<?php
		
	}
	else if(isset($_GET['field']) && $_GET['field'] == "email")
	{
		if(isset($_POST['Email']))
		{
			if($_POST['Email'] == ''){
				$error = "Please fill in your email";
			} else {
				$query = "SELECT `EmailID` FROM `users` WHERE `UserID` = '" . mysql_real_escape_string($user_id) . "' LIMIT 1;";
				$result = mysql_query($query) or die(mysql_error());
				$result = mysql_fetch_array($result);
				$query = "UPDATE `email` SET `EmailAddress` = '" . mysql_real_escape_string($_POST['Email']) . "' WHERE `email`.`EmailID` ='" . $result['EmailID'] . "' LIMIT 1 ;";
				mysql_query($query) or die(mysql_error());
				
				redirect();
			}
		}
		include_once 'include/header.php';
		
		?>
		<center><h2>Change Email</h2></center>
		<?php echo '<center>' . $error . '</center>'; ?>
		<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?field=email' . $edit_url; ?>">
			<table style="margin:auto; width:300px;">
				<tr>
					<td class="alignright">Email</td>
					<td><input type="text" style="width:200px" name="Email" value="<?php echo get_user_email($user_id); ?>"></td>
				</tr>
				<tr>
					<td class="alignright" colspan="2"><input type="submit" name="save" value="Save" /></td>
				</tr>
			</table>
		</form>
		<?php
	}
	else if(isset($_GET['field']) && $_GET['field'] == "password")
	{
		if(isset($_POST['NewPassword']) && isset($_POST['NewPassword2']))
		{
			if($_POST['NewPassword'] == $_POST['NewPassword2'])
			{
				$account = get_user_account($user_id);
				if(!current_user_has_role(1, 'FullAdmin') && !current_user_has_role($franchise, "Franchisee") || get_current_user_id() == $user_id)
				{
					
					$query = "SELECT * FROM `users` WHERE `UserID` ='" . $user_id . "' AND `Password` ='" . sha1($account['Salt'] . $_POST['OldPassword']) . "' LIMIT 1;";
					$result = mysql_query($query) or die(mysql_error());
					if(mysql_num_rows($result) != 1)
					{
						if($user_id == get_current_user_id())
							header("location: " . site_url() . "edit_user.php?field=password&error=oldpass");
						else
							header("location: " . site_url() . "edit_user.php?field=password&error=oldpass" . $edit_url);
					}
					else
					{
						$query = "UPDATE `users` SET `Password` = '" . sha1(strtolower($account['Salt']) . $_POST['NewPassword']) . "' WHERE `users`.`UserID` ='" . $user_id . "' LIMIT 1 ;";
						mysql_query($query) or die(mysql_error());
						
						redirect();
					}
				}
				else
				{
					$query = "UPDATE `users` SET `Password` = '" . sha1(strtolower($account['Salt']) . $_POST['NewPassword']) . "' WHERE `users`.`UserID` ='" . $user_id . "' LIMIT 1 ;";
					mysql_query($query) or die(mysql_error());
					
					redirect();
				}
			}
			else
			{
				header("location: " . site_url() . "edit_user.php?field=password&error=retype" . $edit_url);
			}
		}
		else
		{
			include_once 'include/header.php';
			
			?>
			<center><h2>Change Password</h2></center>
			<?php
				if(isset($_GET['error']) && $_GET['error'] == 'retype')
					echo '<center>Your new password and retype password did not match.</center><br>';
				if(isset($_GET['error']) && $_GET['error'] == 'oldpass')
					echo '<center>Your old pasword was not correct.</center><br>';
			?>
			<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?field=password' . $edit_url; ?>">
				<table style="margin:auto; width:400px;">
					<?php
						if((!current_user_has_role(1, 'FullAdmin') && !current_user_has_role($franchise, "Franchisee")) || get_current_user_id() == $user_id)
							echo '<tr>
									<td class="alignright">Old Password</td>
									<td><input type="password" style="width:200px" name="OldPassword" /></td>
								</tr>';
					?>
					
					<tr>
						<td class="alignright">New Password</td>
						<td><input type="password" style="width:200px" name="NewPassword" /></td>
					</tr>
					<tr>
						<td class="alignright">Retype New Password</td>
						<td><input type="password" style="width:200px" name="NewPassword2" /></td>
					</tr>
					<tr>
						<td class="alignright" colspan="2"><input type="submit" name="save" value="Save" /></td>
					</tr>
				</table>
			</form>
			<?php
		}
	}
	else if(isset($_GET['field']) && $_GET['field'] == "createdriver")
	{
		header('Location: create_driver.php?id='.$user_id.'&b='.date('Y-m-d', $add_user_birthday));
	
	}
	else if(isset($_GET['field']) && $_GET['field'] == "createaddress")
	{
		if(isset($_POST['Address1']) && isset($_POST['City']) && isset($_POST['State']) && isset($_POST['Zip5']))
		{
			$required_fields = array('Address1');
			$required_filled = true;
			
			foreach($required_fields as $k => $v){
				if($_POST[$v] == '')
					$required_filled = false;
			}
			
			if(!$required_filled){
				$error = 'All required name fields were not filled.';
			} else {				
				$address = array('Address1' => $_POST['Address1'],
								   'Address2' => $_POST['Address2'],
								   'City' => $_POST['City'],
								   'State' => $_POST['State'],
								   'ZIP5' => $_POST['Zip5'],
								   'ZIP4' => $_POST['Zip4']);
				$address = add_address($address);
				link_address_to_user($address, $_POST['AddressType'], $user_id);
				
				redirect();
			}
		}
		include_once 'include/header.php';
		
		?>
		<center><h2>New Address</h2></center>
		<?php echo '<center>' . $error . '</center>'; ?>
		<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?field=createaddress' . $edit_url; ?>">
			<table style="margin:auto; width:350px;">
				<tr>
					<td class="alignright">Address Type</td>
					<td>
						<select name="AddressType">
							<option value="Physical">Physical</option>
							<option value="Mailing">Mailing</option>
							<option value="Billing">Billing</option>
							<option value="Additional">Additional</option>
						</select>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<?php create_html_address_table(); ?>
					</td>
				</tr>
				<tr>
					<td class="alignright" colspan="2"><input type="submit" name="save" value="Save" /></td>
				</tr>
			</table>
		</form>
		<?php
	} else if(isset($_GET['field']) && $_GET['field'] == "editaddress"){
		if(isset($_POST['addressid']) && isset($_POST['Address1']) && isset($_POST['City']) && isset($_POST['State']) && isset($_POST['Zip5']))
		{
			$required_fields = array('Address1');
			$required_filled = true;
			
			foreach($required_fields as $k => $v){
				if($_POST[$v] == '')
					$required_filled = false;
			}
			if(!$required_filled){
				$error = 'All required name fields were not filled.';
			} else {				
				$address = array('Address1' => $_POST['Address1'],
								   'Address2' => $_POST['Address2'],
								   'City' => $_POST['City'],
								   'State' => $_POST['State'],
								   'ZIP5' => $_POST['Zip5'],
								   'ZIP4' => $_POST['Zip4']);
				update_user_address($user_id, $_POST['addressid'], $address, $_POST['AddressType']);
				redirect();
			}
		} else {
		  $address = get_user_address_array($user_id);
		  
			$address = $address[$_GET['addressid']];
		}
		include_once 'include/header.php';
		
		
		?>
		<center><h2>Edit Address</h2></center>
		<?php echo '<center>' . $error . '</center>'; ?>
		<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . "?addressid={$_GET['addressid']}&field=editaddress" . $edit_url; ?>">
			<input type="hidden" name="addressid" value="<?php echo $_GET['addressid']; ?>">
			<table style="margin:auto; width:350px;">
				<tr>
					<td class="alignright">Address Type</td>
					<td>
						<select name="AddressType">
							<option value="Physical"<?php if($address['AddressType'] == 'Physical') echo ' SELECTED'; ?>>Physical</option>
							<option value="Mailing"<?php if($address['AddressType'] == 'Mailing') echo ' SELECTED'; ?>>Mailing</option>
							<option value="Billing"<?php if($address['AddressType'] == 'Billing') echo ' SELECTED'; ?>>Billing</option>
							<option value="Additional"<?php if($address['AddressType'] == 'Additional') echo ' SELECTED'; ?>>Additional</option>
						</select>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<?php create_html_address_table(NULL,$address); ?>
					</td>
				</tr>
				<tr>
					<td class="alignright" colspan="2"><input type="submit" name="save" value="Save" /></td>
				</tr>
			</table>
		</form>
		<?php
	}
	else if(isset($_GET['field']) && isset($_GET['addressid']) && $_GET['field'] == "deleteaddress")
	{
		if(isset($_POST['Delete']))
		{
			$query = "SELECT * FROM `user_address` WHERE `UserID`='" . mysql_real_escape_string($user_id) . "' AND `AddressID` = '" . mysql_real_escape_string($_GET['addressid']) . "' LIMIT 1;";
			$result = mysql_query($query) or die(mysql_error());
			$user_address = mysql_fetch_array($result);
			
			$query = "DELETE FROM `user_address` WHERE `UserID` = '" . mysql_real_escape_string($user_id) . "' AND `AddressID` = '" . mysql_real_escape_string($user_address['AddressID']) . "' LIMIT 1;";
			mysql_query($query) or die(mysql_error());
			$query = "DELETE FROM `address` WHERE `address`.`AddressID` = '" . mysql_real_escape_string($user_address['AddressID']) . "' LIMIT 1;";
			mysql_query($query) or die(mysql_error());
			
			redirect();
		}
		else
		{
			$query = "SELECT * FROM `user_address` WHERE `UserID`='" . mysql_real_escape_string($user_id) . "' AND `AddressID` = '" . mysql_real_escape_string($_GET['addressid']) . "' LIMIT 1;";
			$result = mysql_query($query) or die(mysql_error());
			$driver_address = mysql_fetch_array($result);
			$address = get_address($driver_address['AddressID']);
			include_once 'include/header.php';
			
			?>
			<center><h2>Delete Address</h2></center>
			<center>Are you sure you want to delete this address?</center><br>
			<table style="margin:auto; text-align:left;">
				<tr>
					<td colspan="3" style="font-weight:bold;"><?php echo $driver_address['AddressType']; ?></td>
				</tr>
				<tr>
					<td colspan="3"><?php echo $address['Address1']; ?></td>
				</tr>
				<tr>
					<td><?php echo $address['City'] . ','; ?></td>
					<td><?php echo $address['State']; ?></td>
					<td><?php echo $address['ZIP5']; ?></td>
				</tr>
			</table>
			<div class="alignright" style="margin:auto; width:400px;">
				<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?field=deleteaddress&addressid=' . $_GET['addressid'] . $edit_url; ?>">
					<input type="submit" name="Delete" value="Delete" />
				</form>
			</div>
			<?php
		}
	}
	else if(isset($_GET['field']) && $_GET['field'] == "driver")
	{
		if(isset($_POST['DriverStatus']))
		{
			$required_fields = array('State','LicenseNumber');
			$required_filled = true;
			
			foreach($required_fields as $k => $v){
				if($_POST[$v] == ''){
					$required_filled = false;
					echo $k;
				}
					
			}
			
			if(!$required_filled){
				$error = 'All required name fields were not filled.';
			} else {
				$birthday = mysql_real_escape_string($_POST['BirthYear']) . '-' . mysql_real_escape_string($_POST['BirthMonth']) . '-' . mysql_real_escape_string($_POST['BirthDay']);
				$expiration = mysql_real_escape_string($_POST['LicenseExpirationYear']) . '-' . mysql_real_escape_string($_POST['LicenseExpirationMonth']) . '-' . mysql_real_escape_string($_POST['LicenseExpirationDay']);
				$issue = mysql_real_escape_string($_POST['LicenseIssueYear']) . '-' . mysql_real_escape_string($_POST['LicenseIssueMonth']) . '-' . mysql_real_escape_string($_POST['LicenseIssueDay']);
				
				$query = "UPDATE `driver` SET `DriverStatus` = '" . mysql_real_escape_string($_POST['DriverStatus']) . "',`LicenseState` = '" . mysql_real_escape_string($_POST['State']) . "',`LicenseNumber` = '" . mysql_real_escape_string($_POST['LicenseNumber']) . "',
				`DateOfBirth` = '" . $birthday . "', `LicenseIssueDate` = '" . $issue . "', `LicenseExpireDate` = '" . $expiration . "', 
				`CopyofLicenseOnFile` = '" . mysql_real_escape_string($_POST['CopyOfDL']) . "', DriverAgreementRecorded = '".date('Y-m-d',strtotime($_POST["DriverAgreementRecorded"]))."'
				WHERE `UserID` ='" . mysql_real_escape_string($user_id) . "' LIMIT 1;";
				mysql_query($query) or die(mysql_error());
				
				redirect();
			}
		}
		$driver_info = get_user_driver_info($user_id);
		$issue_date = strtotime($driver_info['LicenseIssueDate']);
		$expiration_date = strtotime($driver_info['LicenseExpireDate']);
		include_once 'include/header.php';
		
		?>
		<center><h2>Edit Driver</h2></center>
		<?php echo '<center>' . $error . '</center>'; ?>
		<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?field=driver' . $edit_url; ?>">
			<table style="margin:auto; width:450px;">
				<tr>
					<td class="alignright">Driver Status</td>
					<td>
						<select name="DriverStatus">
							<option value="NotApproved"<?php if($driver_info['DriverStatus'] == 'NotApproved') echo ' SELECTED'; ?>>Not Approved</option>
							<option value="Active"<?php if($driver_info['DriverStatus'] == 'Active') echo ' SELECTED'; ?>>Active</option>
							<option value="Inactive"<?php if($driver_info['DriverStatus'] == 'Inactive') echo ' SELECTED'; ?>>Inactive</option>
						</select>
					</td>
				</tr>
				<tr>
					<td align=right>Driver Agreement</td>	
					<td><input name=DriverAgreementRecorded class=jq_datepicker value="<?php echo date('m/d/Y',strtotime($driver_info['DriverAgreementRecorded'])); ?>" size=10></td>
				</tr>
				<tr>
					<td class="alignright">Copy of DL</td>
					<td>
						<select name="CopyOfDL">
							<option value="No"<?php if($driver_info['CopyofLicenseOnFile'] == 'No') echo " SELECTED"; ?>>No</option>
							<option value="Yes"<?php if($driver_info['CopyofLicenseOnFile'] == 'Yes') echo " SELECTED"; ?>>Yes</option>
						</select>
					</td>
				</tr>
				<tr>
					<td class="alignright">Birth Date</td>
					<td>
						<?php get_date_drop_downs('Birth', $driver_info['DateOfBirth']); ?>
					</td>
				</tr>
				<tr>
					<td class="alignright">DL State</td>
					<td><?php get_state_dropdown(NULL, $driver_info['LicenseState']); ?></td>
				</tr>
				<tr>
					<td class="alignright">DL Number</td>
					<td><input type="text" name="LicenseNumber" value="<?php echo $driver_info['LicenseNumber']; ?>" maxlength="15"></td>
				</tr>
				<tr>
					<td class="alignright">Driver License Expiration</td>
					<td><?php get_date_drop_downs('LicenseExpiration', $expiration_date, date("Y") - 10, date("Y") + 10); ?></td>
				</tr>
                <tr>
					<td class="alignright">Driver License Issue Date</td>
					<td><?php get_date_drop_downs('LicenseIssue', $issue_date, date("Y") - 10, date("Y") + 10); ?></td>
				</tr>
				<tr>
					<td class="alignright" colspan="2"><input type="submit" name="save" value="Save" /></td>
				</tr>
			</table>
		</form>
		<?php
	}
	else if(isset($_GET['field']) && $_GET['field'] == "createdriversettings")
	{
		if(isset($_POST['FelonRiderOK']) && isset($_POST['StayWithRider']) && isset($_POST['WillHelpWithPackage']) && isset($_POST['WillHelpToCar']) && isset($_POST['OtherNotes']))
		{
			$query = "INSERT INTO `driver_settings` (`UserID`, `FelonRiderOK`, `StayWithRider`, `WillHelpWithPackage`, 
								  `WillHelpToCar`, `SensitiveToSmells`, `SmokerOrPerfumeUser`, `MemoryLevelReq`, `VisionLevelReq`, `HearingLevelReq`, `ServiceDog`, `PetCarrier`, 
			                      `UnaccompaniedMinor`, `MaxHoursPerWeek`, `ContactPreference`, `OtherNotes`)
			VALUES ('" . mysql_real_escape_string($user_id) . "', '" . mysql_real_escape_string($_POST['FelonRiderOK']) . "', 
					 '" . mysql_real_escape_string($_POST['StayWithRider']) . "', '" . mysql_real_escape_string($_POST['WillHelpWithPackage']) . "', 
			         '" . mysql_real_escape_string($_POST['WillHelpToCar']) . "', '" . mysql_real_escape_string($_POST['SensitiveToSmells']) . "', 
			         '" . mysql_real_escape_string($_POST['MemoryLevelReq']) . "', '" . mysql_real_escape_string($_POST['VisionLevelReq']) . "',
			         '" . mysql_real_escape_string($_POST['HearingLevelReq']) . "', 
					 '" . mysql_real_escape_string($_POST['SmokerOrPerfumeUser']) . "', '" . mysql_real_escape_string($_POST['ServiceDog']) . "', 
			         '" . mysql_real_escape_string($_POST['PetCarrier']) . "', '" . mysql_real_escape_string($_POST['UnaccompaniedMinor']) . "', 
			         '" . mysql_real_escape_string($_POST['MaxHoursPerWeek']) . "', '" . mysql_real_escape_string($_POST['ContactPreference']) . "', '" . mysql_real_escape_string($_POST['OtherNotes']) . "');";
			
			#echo $query."<BR>";
			
			mysql_query($query) or die(mysql_error());
			
			redirect();
		}
		else
		{
			include_once 'include/header.php';
			
			?>
			<center><h2>Create Driver Settings</h2></center>
			<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?field=createdriversettings' . $edit_url; ?>">
				<table style="margin:auto; width:950px;">
					<tr>
						<td style="width:220px;" class="alignright">Drive with qualified felon:</td>
						<td>
							<select name="FelonRiderOK">
								<option value="Yes">Yes</option>
								<option value="No">No</option>
							</select>
							<td >Did not hurt or threatened someone.</td>
						</td>
					</tr>
					<tr>
						<td class="alignright">Stay with the rider:</td>
						<td>
							<select name="StayWithRider">
								<option value="No">No</option>
								<option value="Yes">Yes</option>
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Will help rider with package:</td>
						<td>
							<select name="WillHelpWithPackage">
								<option value="No">No</option>
								<option value="Yes">Yes</option>
							</select>
							<td >1 Cart limit.</td>	
						</td>
					</tr>
					<tr>
						<td class="alignright">Will help rider to car:</td>
						<td>
							<select name="WillHelpToCar">
								<option value="No">No</option>
								<option value="Yes">Yes</option>
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Sensitive to smells:</td>
						<td>
							<select name="SensitiveToSmells">
								<option value="No">No</option>
								<option value="Yes">Yes</option>
							</select>
							<td >Smoking not allowed during transport.</td>	
						</td>
					</tr>
					<tr>
						<td class="alignright">Smoker or perfume user:</td>
						<td>
							<select name="SmokerOrPerfumeUser">
								<option value="No">No</option>
								<option value="Yes">Yes</option>
							</select>
							<td >Smoking is not permitted while passenger in car</td>
						</td>
					</tr>
					<tr>
						<td class="alignright">Rider Memory Level:</td>
						<td>
							<select name="MemoryLevelReq">
								<option value="Full">Full</option>
								<option value="ML1">ML1</option>
								<option value="ML2">ML2</option>
							</select>
							<td >ML1 = Slight Memory Loss, ML2 = Severe Memory Loss </td>
						</td>
					</tr>
					<tr>
						<td class="alignright">Rider Vision Level:</td>
						<td>
							<select name="VisionLevelReq">
								<option value="VL0">VL0</option>
								<option value="Part">Part</option>
								<option value="None">None</option>
								<option value="VL1">VL1</option>
								<option value="VL2">VL2</option>
							</select>
							<td >Part= Partial, VL1= Cannot Correct to 20/20, VL2= Blind</td>
						</td>
					</tr>
										<tr>
						<td class="alignright">Hearing Level Required:</td>
						<td>
							<select name="HearingLevelReq">
								<option value="HL0">HL0</option>
								<option value="HL1">HL1</option>
								<option value="HL2">HL2</option>
								<option value="HL3">HL3</option>
							</select>
							<td>HL1= Some hearing loss, HL2= Asst. Device, HL3= Little or no hearing</td>
						</td>
					</tr>
					<tr>
						<td class="alignright">Service dog:</td>
						<td>
							<select name="ServiceDog">
								<option value="No">No</option>
								<option value="Yes">Yes</option>
							</select>
							<td >Rider to provide blanket for car seat.</td>	
						</td>
					</tr>
										<tr>
						<td class="alignright">Pet carrier:</td>
						<td>
							<select name="PetCarrier">
								<option value="No">No</option>
								<option value="Yes">Yes</option>
							</select>
						</td>
					</tr>
										<tr>
						<td class="alignright">Unaccompanied minor:</td>
						<td>
							<select name="UnaccompaniedMinor">
								<option value="No">No</option>
								<option value="Yes">Yes</option>
							</select>
							<td >Must ride in back. Seatbelt required.</td>	
						</td>
					</tr>
										<tr>
						<td class="alignright">Maximum hours you drive a week:</td>
						<td>
							<select name="MaxHoursPerWeek">
								<?php
									for($i = 2; $i < 60; $i++){
										echo "<option value=\"$i\"";
										if($i == 4)
											echo 'SELECTED';
										echo ">$i</option>";
									}
								?>
							</select>
						</td>
					</tr>
					<tr>
			 <?php		if(!(current_user_has_role(1, "FullAdmin") && !current_user_has_role($franchise, "Franchisee"))) echo 'visibility: Hidden;' ?>
						<td > Contact preference: </td>
						<td>	
							<textarea name="ContactPreference" style="width:50px; height:16px;"></textarea>
						</td>
					</tr>
					<tr>
						<td colspan="3">
							Other notes you would like us to know:
							<textarea name="OtherNotes" style="width:950px; height:100px;"></textarea>
						</td>
					</tr>
										<tr>
						<td class="alignright" colspan="3"><input type="submit" name="save" value="Save" /></td>
					</tr>
				</table>
			</form>
			<?php
		}
	}
	else if(isset($_GET['field']) && $_GET['field'] == "driversettings")
	{
		if(isset($_POST['FelonRiderOK']) && isset($_POST['StayWithRider']) && isset($_POST['WillHelpWithPackage']) && isset($_POST['WillHelpToCar']) && isset($_POST['OtherNotes']))
		{
			$query ="UPDATE `driver_settings` SET `FelonRiderOK` = '" . mysql_real_escape_string($_POST['FelonRiderOK']) . "',`StayWithRider` = '" . mysql_real_escape_string($_POST['StayWithRider']) . 
			        "', `WillHelpWithPackage` = '" . mysql_real_escape_string($_POST['WillHelpWithPackage']) . "', `WillHelpToCar` = '" . mysql_real_escape_string($_POST['WillHelpToCar']) . 
			        "', `SensitiveToSmells` = '" . mysql_real_escape_string($_POST['SensitiveToSmells']) . "', `SmokerOrPerfumeUser` = '" . mysql_real_escape_string($_POST['SmokerOrPerfumeUser']) . 
			        "', `MemoryLevelReq` = '" . mysql_real_escape_string($_POST['MemoryLevelReq']) . "', `VisionLevelReq` = '" . mysql_real_escape_string($_POST['VisionLevelReq']) .
			        "', `HearingLevelReq` = '" . mysql_real_escape_string($_POST['HearingLevelReq']) .
			        "', `ServiceDog` = '" . mysql_real_escape_string($_POST['ServiceDog']) . "', `PetCarrier` = '" . mysql_real_escape_string($_POST['PetCarrier']) .
			        "', `UnaccompaniedMinor` = '" . mysql_real_escape_string($_POST['UnaccompaniedMinor']) . "', `ContactPreference` = '" . mysql_real_escape_string($_POST['ContactPreference']) . 
			        "', `ContactPreference` = '" . mysql_real_escape_string($_POST['ContactPreference']) . "', `OtherNotes` = '" . mysql_real_escape_string($_POST['OtherNotes']) . 
			        "' WHERE `driver_settings`.`UserID` ='" . mysql_real_escape_string($user_id) . "' LIMIT 1 ;";
			mysql_query($query) or die(mysql_error());
			
			redirect();
		}
		else
		{
			$driver_settings = get_user_driver_settings($user_id);
			include_once 'include/header.php';
			
			?>
			<center><h2>Edit Driver Settings</h2></center>
			<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?field=driversettings' . $edit_url; ?>">
				<table style="margin:auto; width:750px;">
					<tr>
						<td style="width:220px;" class="alignright">Drive qualified felon:</td>
						<td>
							<select name="FelonRiderOK">
								<option value="Yes" <?php if($driver_settings['FelonRiderOK'] == 'Yes') echo 'SELECTED'; ?>>Yes</option>
								<option value="No" <?php if($driver_settings['FelonRiderOK'] == 'No') echo 'SELECTED'; ?>>No</option>
							</select>
							<td >Did not hurt or threatened someone.</td>
						</td>
					</tr>
					<tr>
						<td class="alignright">Stay with the rider:</td>
						<td>
							<select name="StayWithRider">
								<option value="No" <?php if($driver_settings['StayWithRider'] == 'No') echo 'SELECTED'; ?>>No</option>
								<option value="Yes" <?php if($driver_settings['StayWithRider'] == 'Yes') echo 'SELECTED'; ?>>Yes</option>
							</select>
							<td >Brief Stay.</td>
						</td>
					</tr>
					<tr>
						<td class="alignright">Will help rider with package:</td>
						<td>
							<select name="WillHelpWithPackage">
								<option value="No" <?php if($driver_settings['WillHelpWithPackage'] == 'No') echo 'SELECTED'; ?>>No</option>
								<option value="Yes" <?php if($driver_settings['WillHelpWithPackage'] == 'Yes') echo 'SELECTED'; ?>>Yes</option>
							</select>
							<td >1 Cart limit.</td>
						</td>
					</tr>
					<tr>
						<td class="alignright">Will help rider to car:</td>
						<td>
							<select name="WillHelpToCar">
								<option value="No" <?php if($driver_settings['WillHelpToCar'] == 'No') echo 'SELECTED'; ?>>No</option>
								<option value="Yes" <?php if($driver_settings['WillHelpToCar'] == 'Yes') echo 'SELECTED'; ?>>Yes</option>
							</select>
						</td>
					</tr>
                    <tr>
						<td class="alignright">Sensitive to smells:</td>
						<td>
							<select name="SensitiveToSmells">
								<option value="No" <?php if($driver_settings['SensitiveToSmells'] == 'No') echo 'SELECTED'; ?>>No</option>
								<option value="Yes" <?php if($driver_settings['SensitiveToSmells'] == 'Yes') echo 'SELECTED'; ?>>Yes</option>
							</select>
							<td >Smoking not allowed during transport.</td>						
						</td>
					</tr>
					<tr>
						<td class="alignright">Smoker or perfume user:</td>
						<td>
							<select name="SmokerOrPerfumeUser">
								<option value="No" <?php if($driver_settings['SmokerOrPerfumeUser'] == 'No') echo 'SELECTED'; ?>>No</option>
								<option value="Yes" <?php if($driver_settings['SmokerOrPerfumeUser'] == 'Yes') echo 'SELECTED'; ?>>Yes</option>
							</select>
							<td >Smoking is not permitted while passenger is in the car</td>
						</td>
					</tr>
					<tr>
						<td class="alignright">Rider Memory Level:</td>
						<td>
							<select name="MemoryLevelReq">
								<option value="Full" <?php if($driver_settings['MemoryLevelReq'] == 'Full') echo 'SELECTED'; ?>>Full</option>
								<option value="ML1" <?php if($driver_settings['MemoryLevelReq'] == 'ML1') echo 'SELECTED'; ?>>ML1</option>
								<option value="ML2" <?php if($driver_settings['MemoryLevelReq'] == 'ML2') echo 'SELECTED'; ?>>ML2</option>
							</select>
							<td >ML1 = Slight Memory Loss, ML2 = Severe Memory Loss </td>
						</td>
					</tr>
					<tr>
						<td class="alignright">Rider Vision Level:</td>
						<td>
							<select name="VisionLevelReq">
								<option value="VL0" <?php if($driver_settings['VisionLevelReq'] == 'VL0') echo 'SELECTED'; ?>>VL0</option>
								<option value="Part" <?php if($driver_settings['VisionLevelReq'] == 'Part') echo 'SELECTED'; ?>>Part</option>
								<option value="None" <?php if($driver_settings['VisionLevelReq'] == 'None') echo 'SELECTED'; ?>>None</option>
								<option value="VL1" <?php if($driver_settings['VisionLevelReq'] == 'VL1') echo 'SELECTED'; ?>>VL1</option>
								<option value="VL2" <?php if($driver_settings['VisionLevelReq'] == 'VL2') echo 'SELECTED'; ?>>VL2</option>
							</select>
							<td >Part= Partial, VL1= Cannot Correct to 20/20, VL2= Blind</td>
						</td>
					</tr>
					<tr>
						<td class="alignright">Rider Hearing Level:</td>
						<td>
							<select name="HearingLevelReq">
								<option value="HL0" <?php if($driver_settings['HearingLevelReq'] == 'HL0') echo 'SELECTED'; ?>>HL0</option>
								<option value="HL1" <?php if($driver_settings['HearingLevelReq'] == 'HL1') echo 'SELECTED'; ?>>HL1</option>
								<option value="HL2" <?php if($driver_settings['HearingLevelReq'] == 'HL2') echo 'SELECTED'; ?>>HL2</option>
								<option value="HL3" <?php if($driver_settings['HearingLevelReq'] == 'HL3') echo 'SELECTED'; ?>>HL3</option>
							</select>
							<td >HL1= Some hearing loss, HL2= Asst. Device, HL3= Little or no hearing</td>
						</td>
					</tr>
					<tr>
						<td class="alignright">Service dog:</td>
						<td>
							<select name="ServiceDog">
								<option value="No" <?php if($driver_settings['ServiceDog'] == 'No') echo 'SELECTED'; ?>>No</option>
								<option value="Yes" <?php if($driver_settings['ServiceDog'] == 'Yes') echo 'SELECTED'; ?>>Yes</option>
							</select>
							<td >Rider to provide blanket for car seat.</td>	
						</td>
					</tr>
										<tr>
						<td class="alignright">Pet Carrier:</td>
						<td>
							<select name="PetCarrier">
								<option value="No" <?php if($driver_settings['PetCarrier'] == 'No') echo 'SELECTED'; ?>>No</option>
								<option value="Yes" <?php if($driver_settings['PetCarrier'] == 'Yes') echo 'SELECTED'; ?>>Yes</option>
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Unaccompanied Minor:</td>
						<td>
							<select name="UnaccompaniedMinor">
								<option value="No" <?php if($driver_settings['UnaccompaniedMinor'] == 'No') echo 'SELECTED'; ?>>No</option>
								<option value="Yes" <?php if($driver_settings['UnaccompaniedMinor'] == 'Yes') echo 'SELECTED'; ?>>Yes</option>
							</select>
							<td >Must ride in back. Seatbelt required.</td>	
						</td>
					</tr>
					<tr>
			 <?php		if(!(current_user_has_role(1, "FullAdmin") && !current_user_has_role($franchise, "Franchisee"))) echo 'visibility: Hidden;' ?>
					<td class="alignright"> Contact preference: </td>
						<td>	
							<textarea name="ContactPreference" style="width:50px; height:16px;"><?php echo $driver_settings['ContactPreference']; ?></textarea>
						</td>
					</tr>
					<tr>
						<td colspan=2 align=center>(examples: T=Text, M=Mobile, H=Home, W=Work, etc)</td>	
					</tr>
					<tr>
						<td colspan="2">
							Other notes you would like us to know:<br>
							<textarea style="width:350px; height:100px;" name="OtherNotes"><?php echo $driver_settings['OtherNotes']; ?></textarea>
						</td>
					</tr>
					<tr>
						<td class="alignright" colspan="2"><input type="submit" name="save" value="Save" /></td>
					</tr>
				</table>
			</form>
			<?php
		}
	}
	else if(isset($_GET['field']) && $_GET['field'] == "createrider")
	{
	    header('Location: create_rider.php?id='.$user_id.'&b='.date('Y-m-d',$add_user_birthday));
	}
	else if(isset($_GET['field']) && $_GET['field'] == "rider" && (current_user_has_role(1, "FullAdmin") || current_user_has_role($franchise, "Franchisee")))
	{
		
		if(isset($_POST['RiderStatus'])  &&  isset($_POST['QualificationReason']))
		{
			$required_fields = array();
			$required_filled = true;
			
			foreach($required_fields as $k => $v){
				if($_POST[$v] == '')
					$required_filled = false;
			}
			
			if(!$required_filled){
				$error = 'All required name fields were not filled.';
			} else {
				$rider_info = get_user_rider_info($user_id);
				$birthday = mysql_real_escape_string($_POST['BirthYear']) . '-' . mysql_real_escape_string($_POST['BirthMonth']) . '-' . mysql_real_escape_string($_POST['BirthDay']);
				
				$query = "UPDATE `rider` SET `RiderStatus` = '" . mysql_real_escape_string($_POST['RiderStatus']) . "',
						`EmergencyContactRelationship` = '" . mysql_real_escape_string($_POST['EmergencyContactRelationship']) . "',`QualificationReason` = '" . mysql_real_escape_string($_POST['QualificationReason']) . "', `DateOfBirth` = '" . mysql_real_escape_string($birthday) . "', ADAQualified = '" . mysql_real_escape_string($_POST['ADA']) . "'
						, RiderWaiverReceived = '".date('Y-m-d',strtotime($_POST[RiderWaiverReceived]))."'
						, FirstPadding = $_POST[FirstPadding], PrePadding = $_POST[PrePadding], PostPadding = $_POST[PostPadding]
						, default_num_in_car = $_POST[default_num_in_car]
						, CanScheduleRides = $_POST[CanScheduleRides]
						 WHERE `rider`.`UserID` ='" . mysql_real_escape_string($user_id) . "' LIMIT 1 ;";
				
				//echo $query;
				
				mysql_query($query) or die(mysql_error());
				
				$query = "UPDATE users set Cutoff_Hour = $_POST[Cutoff_Hour] where UserID = $user_id";
				
				mysql_query($query);
				
				redirect();
			}
		}
		include_once 'include/header.php';
		
		$rider_info = get_user_rider_info($user_id);
		$date = get_date($rider_info['DateOfBirth']);
		$fee_date = get_date($rider_info['AnnualFeePaymentDate']);
		?>
		<center><h2>Edit Rider</h2></center>
		<?php echo '<center>' . $error . '</center>'; ?>
		<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?field=rider' . $edit_url; ?>">
			<table style="margin:auto; width:400px;">
				<tr>
					<td class="alignright">Rider Status</td>
					<td>
						<select name="RiderStatus">
							<option value="NotApproved" <?php if($rider_info['RiderStatus'] == "NotApproved") echo 'SELECTED'; ?>>Not Approved</option>
							<option value="Active" <?php if($rider_info['RiderStatus'] == "Active") echo 'SELECTED'; ?>>Active</option>
							<option value="Inactive" <?php if($rider_info['RiderStatus'] == "Inactive") echo 'SELECTED'; ?>>Inactive</option>
							<option value="Deceased" <?php if($rider_info['RiderStatus'] == "Deceased") echo 'SELECTED'; ?>>Deceased</option>
						</select>
					</td>
				</tr>
				<tr>
					<td style='text-align: right;'>Rider Waiver Date:</td>	
					<td>
						<?php if((current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee'))) { ?>
						<input name=RiderWaiverReceived class=jq_datepicker value="<?php echo format_date($rider_info['RiderWaiverReceived'],"m/d/Y"); ?>" size=10></td>
					<?php } else echo format_date($rider_info['RiderWaiverReceived'],"m/d/Y"); ?>
				</tr>
				<tr>
					<td style='text-align: right;'>Birth Date</td>
					<td>
						<select name="BirthMonth">
							<option value="1" <?php if($date['Month'] == 1) echo 'SELECTED'; ?>>January</option>
							<option value="2" <?php if($ate['Month'] == 2) echo 'SELECTED'; ?>>February</option>
							<option value="3" <?php if($date['Month'] == 3) echo 'SELECTED'; ?>>March</option>
							<option value="4" <?php if($date['Month'] == 4) echo 'SELECTED'; ?>>April</option>
							<option value="5" <?php if($date['Month'] == 5) echo 'SELECTED'; ?>>May</option>
							<option value="6" <?php if($date['Month'] == 6) echo 'SELECTED'; ?>>June</option>
							<option value="7" <?php if($date['Month'] == 7) echo 'SELECTED'; ?>>July</option>
							<option value="8" <?php if($date['Month'] == 8) echo 'SELECTED'; ?>>August</option>
							<option value="9" <?php if($date['Month'] == 9) echo 'SELECTED'; ?>>September</option>
							<option value="10" <?php if($date['Month'] == 10) echo 'SELECTED'; ?>>October</option>
							<option value="11" <?php if($date['Month'] == 11) echo 'SELECTED'; ?>>November</option>
							<option value="12" <?php if($date['Month'] == 12) echo 'SELECTED'; ?>>December</option>
						</select> / 
						<select name="BirthDay">
							<?php
								for($i = 1; $i <= 32; $i++)
								{
									echo '<option value="' . $i . '" ';
									if($i == $date['Day'])
										echo 'SELECTED';
									echo '>' . $i . '</option>';
								}
							?>
						</select> / 
						<select name="BirthYear">
							<?php
								for($i = (int)date("Y") - 1; $i >= (int)date("Y")- 109; $i--)
								{
									echo '<option value="' . $i . '" ';
									if($i == $date['Year'])
										echo 'SELECTED';
									echo '>' . $i . '</option>';
								}
							?>
						</select>
					</td>
				</tr>
                <tr>
                	<td class="alignright">ADA Qualified</td>
                	<td>
                    	<select name="ADA">
                        	<option value="No"<?php if($rider_info['ADAQualified'] == 'No') echo ' SELECTED'; ?>>No</option>
                            <option value="Yes"<?php if($rider_info['ADAQualified'] == 'Yes') echo ' SELECTED'; ?>>Yes</option>
                        </select>
                    </td>
                </tr>
                <tr>
                	<Td class="alignright" style='vertical-align: middle;'>Padding</Td>	
                	<td>
                		<table>
                			<tr>
                				<td nowrap>First: <input type=text size=2 name=FirstPadding value="<?php echo $rider_info["FirstPadding"]; ?>"></td>
                				<td nowrap>Pre: <input type=text size=2 name=PrePadding value="<?php echo $rider_info["PrePadding"]; ?>"></td>
                				<td nowrap>Post: <input type=text size=2 name=PostPadding value="<?php echo $rider_info["PostPadding"]; ?>"></td>
                			</tr>	
                		</table>
                	</td>
				</tr>
                <tr>
                	<Td class="alignright" style='vertical-align: middle;'>Default # In Car</Td>	
                	<td>
						<input type=text size=1 name=default_num_in_car value="<?php echo $rider_info["default_num_in_car"]; ?>">
                	</td>
				</tr>
                <tr>
                	<Td class="alignright" style='vertical-align: middle;'>Can Schedule Rides</Td>	
                	<td>
						<select size=1 name=CanScheduleRides>
						<option value=1 <?php if($rider_info["CanScheduleRides"] == 1) echo "selected"; ?>>Yes</option>
						<option value=0 <?php if($rider_info["CanScheduleRides"] == 0) echo "selected"; ?>>No</option>
						</select>
                	</td>
                </tr>
				<tr>
					<td colspan="2"><br>
						Qualification Reason:<br>
						<textarea name="QualificationReason" style="width:400px; height:100px;"><?php echo $rider_info['QualificationReason']; ?></textarea>
					</td>
				</tr>
				<tr>
					<td nowrap>Scheduling Cutoff Hour</td>	
					<td><input type=text size=2 name=Cutoff_Hour value="<?php echo $rider_info['Cutoff_Hour']; ?>"></td>
				</tr>
				<tr>
					<td class="alignright" colspan="2"><input type="submit" name="save" value="Save" /></td>
				</tr>
			</table>
		</form>
		<?php
	}
	else if(isset($_GET['field']) && $_GET['field'] == "createriderpreferences")
	{
		if(isset($_POST['HighVehicleOK']) && isset($_POST['MediumVehicleOK']) && isset($_POST['LowVehicleOK']) &&
		 isset($_POST['FelonDriverOK']) && isset($_POST['DriverStays']) && 
		 isset($_POST['HasWalker']) && isset($_POST['HasWheelchair']) && isset($_POST['HasCane']) && 
		 isset($_POST['VisionLevel']) && isset($_POST['NeedsPackageHelp']) && isset($_POST['NeedsHelpToCar']) && 
		 isset($_POST['EnterDriverSide']) && isset($_POST['EnterPassengerSide']) && isset($_POST['HasMemoryLoss']) && 
		 isset($_POST['HasCaretaker'])&& isset($_POST['OtherNotes']) && isset($_POST['Title']) && 
		 isset($_POST['FirstName']) && isset($_POST['MiddleInitial']) && isset($_POST['LastName']) && isset($_POST['Suffix']))
		{
			createRiderPrefs($user_id, $_POST);
			redirect();
		}
		else
		{
			include_once 'include/header.php';
			
			?>
			<center><h2>Create Rider Preferences</h2></center>
			<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?field=createriderpreferences' . $edit_url; ?>">
				<table style="margin:auto; ">
					<tr>
						<td class="alignright" style="width:175px;">High Vehicle:</td>
						<td>
							<select name="HighVehicleOK">
								<option value="Yes">Yes</option>
								<option value="No">No</option>
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Medium Vehicle:</td>
						<td>
							<select name="MediumVehicleOK">
								<option value="Yes">Yes</option>
								<option value="No">No</option>
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Low Vehicle:</td>
						<td>
							<select name="LowVehicleOK">
								<option value="Yes">Yes</option>
								<option value="No">No</option>
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Felon Driver:</td>
						<td>
							<select name="FelonDriverOK">
								<option value="No">No</option>
								<option value="Yes">Yes</option>								
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Driver Stays:</td>
						<td>
							<select name="DriverStays">
								<option value="No">No</option>
								<option value="Yes">Yes</option>								
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Has Walker:</td>
						<td>
							<select name="HasWalker">
								<option value="No">No</option>
								<option value="W1">W1</option>
								<option value="W2">W2</option>
								<option value="W3">W3</option>								
							</select>
							<td >W1 = Flat Folding Walker, W2 = Walker With Handles, W3 = XL Walker </td>
						</td>
					</tr>
					<tr>
						<td class="alignright">Has Wheelchair:</td>
						<td>
							<select name="HasWheelchair">
								<option value="No">No</option>
								<option value="WC1">WC1</option>
								<option value="WC2">WC2</option>								
							</select>
							<td >WC1 = Transfer Chair, WC2 = Wheelchair </td>
						</td>
					</tr>
					<tr>
						<td class="alignright">Has Cane:</td>
						<td>
							<select name="HasCane">
								<option value="No">No</option>
								<option value="C1">C1</option>
								<option value="C2">C2</option>
								<option value="Yes">Yes</option>								
							</select>
							<td >C1 = Standard Cane, C2 = Quad Cane </td>
						</td>
					</tr>
					<tr>
						<td class="alignright">Vision Level:</td>
						<td>
							<select name="VisionLevel">
								<option value="VL0">VL0</option>
								<option value="Part">Part</option>
								<option value="None">None</option>
								<option value="VL1">VL1</option>
								<option value="VL2">VL2</option>								
							</select>
							<td >Part= Partial, VL1= Cannot Correct to 20/20, VL2= Blind </td>
						</td>
					</tr>
					<tr>
						<td class="alignright">Hearing Level:</td>
						<td>
							<select name="HearingLevel">
								<option value="OK">OK</option>
								<option value="HL1">HL1</option>
								<option value="HL2">HL2</option>
								<option value="HL3">HL3</option>								
							</select>
							<td >HL1= Some hearing loss, HL2= Asst. Device, HL3= Little or no hearing</td>
						</td>
					</tr>
					<tr>
						<td class="alignright">Needs Package Help:</td>
						<td>
							<select name="NeedsPackageHelp">
								<option value="No">No</option>
								<option value="Yes">Yes</option>								
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Needs Help To Car:</td>
						<td>
							<select name="NeedsHelpToCar">
								<option value="No">No</option>
								<option value="Yes">Yes</option>								
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Enter Driver Side:</td>
						<td>
							<select name="EnterDriverSide">
								<option value="Yes">Yes</option>
								<option value="No">No</option>							
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Enter Passenger Side:</td>
						<td>
							<select name="EnterPassengerSide">
								<option value="Yes">Yes</option>
								<option value="No">No</option>					
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Has Service Animal:</td>
						<td>
							<select name="HasServiceAnimal">
								<option value="No">No</option>					
								<option value="Yes">Yes</option>								
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Has Small Pet In Carrier:</td>
						<td>
							<select name="HasSmallPetInCarrier">
								<option value="No">No</option>								
								<option value="Yes">Yes</option>
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Sensitive to smells:</td>
						<td>
							<select name="SensitiveToSmells">
								<option value="No">No</option>
								<option value="Yes">Yes</option>								
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Smoker or perfume user:</td>
						<td>
							<select name="SmokerOrPerfumeUser">
								<option value="No">No</option>
								<option value="Yes">Yes</option>								
							</select>
							<td >Smoking is not permitted while passenger in car</td>
						</td>
					</tr>
					<tr>
						<td class="alignright">Has Memory Loss:</td>
						<td>
							<select name="HasMemoryLoss">
								<option value="No">No</option>
								<option value="ML1">ML1</option>
								<option value="ML2">ML2</option>								
							</select>
							<td >ML1 = Slight Memory Loss, ML2 = Severe Memory Loss </td>
						</td>
					</tr>
					<tr>
						<td class="alignright">Has Additional Rider:</td>
						<td>
							<select name="HasCaretaker">
								<option value="No">No</option>
								<option value="Yes">Yes</option>								
							</select>
						</td>
					</tr>
					<tr>
					   <td colspan="2"><hr></td>
					</tr>
					<tr>
						<td colspan="2">
							<b>Additional Rider Info</b><br><span style="font-size:11px;">*only if you checked Yes to the Additional Rider question.</span><br>
							<table>
								<tr>
									<td class="alignright" width="80px">Title</td>
									<td><input type="text" name="Title" maxlength="10" value="<?php echo $contact_name['Title']; ?>" style="width:50px;" /></td>
								</tr>
								<tr>
									<td class="alignright">First Name</td>
									<td><input type="text" name="FirstName" maxlength="30" value="<?php echo $contact_name['FirstName']; ?>" style="width:200px;" /></td>
								</tr>
								<tr>
									<td class="alignright">Middle Initial</td>
									<td><input type="text" name="MiddleInitial" maxlength="1" value="<?php echo $contact_name['MiddleInitial']; ?>" style="width:50px;" /></td>
								</tr>
								<tr>
									<td class="alignright">Last Name</td>
									<td><input type="text" name="LastName" maxlength="30" value="<?php echo $contact_name['LastName']; ?>" style="width:200px;" /></td>
								</tr>
								<tr>
									<td class="alignright">Suffix</td>
									<td><input type="text" name="Suffix" maxlength="10" value="<?php echo $contact_name['Suffix']; ?>" style="width:50px;" /></td>
								</tr>
								<tr>
					               <td class="alignright">Additional Rider Birthday</td>
					           <td>
					           <?php 
					               print_month_select("CaretakerBirthMonth");
					               echo " / ";
					               print_day_select("CaretakerBirthDay");
					               echo " / ";
					               print_year_select(date("Y") - 110, 92, "CaretakerBirthYear");
					           ?>
					           </td>
					       </tr>
					       <tr>
					           <td class="alignright">Additional Rider Background Checked</td>
					           <td>
					               <select name="CaretakerBackgroundCheck">
					                   <option value="Yes">Yes</option>
					                   <option value="No">No</option>
					               </select>
					           </td>
					       </tr>
				        </table>
							
						</td>
					</tr>
					<tr>
					   <td colspan="2"><hr></td>
					</tr>
					<tr>
						<td colspan="2">
							Other Notes:<br>
							<textarea style="width:350px; height:100px;" name="OtherNotes"></textarea>
						</td>
					</tr>
					<tr>
						<td class="alignright" colspan="2"><input type="submit" name="save" value="Save" /></td>
					</tr>
				</table>
			</form>
			<?php
		}
	}
	else if(isset($_GET['field']) && $_GET['field'] == "createdrivervehicle")
	{
		if(isset($_POST['VehicleYear']) && isset($_POST['VehicleMake']) && isset($_POST['VehicleModel']) && isset($_POST['VehicleColor'])
		 && isset($_POST['VehicleDescription']) && isset($_POST['LicenseState']) && isset($_POST['LicenseNumber']) && isset($_POST['VehicleHeight'])
		  && isset($_POST['CanHandleWalker']) && isset($_POST['CanHandleCane']) && isset($_POST['HasDriverSideRearDoor']) && isset($_POST['HasPassengerSideRearDoor']))
		{
			$required_fields = array('VehicleMake','VehicleModel','VehicleColor',
				'VehicleDescription','LicenseState','LicenseNumber');
			$required_filled = true;
			
			foreach($required_fields as $k => $v){
				if($_POST[$v] == '')
					$required_filled = false;
			}
			
			if(!$required_filled){
				$error = 'All required name fields were not filled.';
			} else {
				$query = "INSERT INTO `vehicle` (`VehicleID` ,`VehicleYear` ,`VehicleMake` ,`VehicleModel` ,`VehicleColor` ,
				`VehicleDescription` ,`LicenseState` ,`LicenseNumber` ,`VehicleHeight` ,`CanHandleWalker` ,`CanHandleCane` ,
				`Wheelchair`, `HasDriverSideRearDoor` ,`HasPassengerSideRearDoor`, `MaxPassengers`)
				VALUES (NULL , '" . mysql_real_escape_string($_POST['VehicleYear']) . "', '" . mysql_real_escape_string($_POST['VehicleMake']) . "', '" . mysql_real_escape_string($_POST['VehicleModel']) . "', '" . mysql_real_escape_string($_POST['VehicleColor']) . "',
				'" . mysql_real_escape_string($_POST['VehicleDescription']) . "', '" . mysql_real_escape_string($_POST['LicenseState']) . "', '" . mysql_real_escape_string($_POST['LicenseNumber']) . "', '" . mysql_real_escape_string($_POST['VehicleHeight']) . "',
				'" . mysql_real_escape_string($_POST['CanHandleWalker']) . "', '" . mysql_real_escape_string($_POST['CanHandleCane']) . "', '" . mysql_real_escape_string($_POST['Wheelchair']) . "', '" . mysql_real_escape_string($_POST['HasDriverSideRearDoor']) . "', 
				'" . mysql_real_escape_string($_POST['HasPassengerSideRearDoor']) . "', '" . mysql_real_escape_string($_POST['MaxPassengers']) . "');";
				mysql_query($query) or die(mysql_error());
				$vehicle_id = mysql_insert_id();
				
				$query = "INSERT INTO `vehicle_driver` (`VehicleID` ,`userID`)
															VALUES ('" . mysql_real_escape_string($vehicle_id) . "', '" . mysql_real_escape_string($user_id) . "');";
				mysql_query($query) or die(mysql_error());
				
				redirect();
			}
		}
		include_once 'include/header.php';
		
		?>
		<center><h2>Create a Vehicle</h2></center>
		<?php echo '<center>' . $error . '</center>'; ?>
		<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?field=createdrivervehicle' . $edit_url; ?>">
			<table style="margin:auto; width:350px;">
				<tr>
					<td class="alignright">Vehicle Year:</td>
					<td>
						<select name="VehicleYear">
							<?php
								for($i = (int)Date("Y") + 2; $i > (int)Date("Y") - 30; $i--)
									echo '<option value="' . $i . '">' . $i . '</option>';
							?>
						</select>
					</td>
				</tr>
				<tr>
					<td class="alignright">Vehicle Make:</td>
					<td><input type="text" name="VehicleMake" value="<?php echo $_POST['VehicleMake']; ?>" maxlength="20" /></td>
				</tr>
				<tr>
					<td class="alignright">Vehicle Model:</td>
					<td><input type="text" name="VehicleModel" value="<?php echo $_POST['VehicleModel']; ?>" maxlength="20" /></td>
				</tr>
				<tr>
					<td class="alignright">Vehicle Color:</td>
					<td><input type="text" name="VehicleColor" value="<?php echo $_POST['VehicleColor']; ?>" maxlength="15" /></td>
					</tr>
				<tr>
					<td class="alignright">Vehicle Description:</td>
					<td><input type="text" name="VehicleDescription" value="<?php echo $_POST['VehicleDescription']; ?>" maxlength="50" /></td>
				</tr>
				<tr>
					<td class="alignright">License Plate State:</td>
					<td><input type="text" name="LicenseState" value="<?php echo $_POST['LicenseState']; ?>" maxlength="2" /></td>
				</tr>
				<tr>
					<td class="alignright">License Plate Number:</td>
					<td><input type="text" name="LicenseNumber" value="<?php echo $_POST['LicenseNumber']; ?>" maxlength="12" /></td>
				</tr>
				<tr>
					<td class="alignright">Vehicle Height:</td>
					<td>
						<select name="VehicleHeight">
							<option value="HIGH"<?php if($_POST['VehicleHeight'] == "HIGH") echo ' SELECTED'; ?>>High</option>
							<option value="MEDIUM"<?php if($_POST['VehicleHeight'] == "MEDIUM") echo ' SELECTED'; ?>>Medium</option>
							<option value="LOW"<?php if($_POST['VehicleHeight'] == "LOW") echo ' SELECTED'; ?>>Low</option>
							<option value="UNKNOWN"<?php if($_POST['VehicleHeight'] == "UNKNOWN") echo ' SELECTED'; ?>>Unknown</option>
						</select>
					</td>
				</tr>
				<tr>
					<td class="alignright">Can Handle Walker:</td>
					<td>
						<select name="CanHandleWalker">
							<option value="No"<?php if($_POST['CanHandleWalker'] == "No") echo ' SELECTED'; ?>>No</option>
							<option value="W1"<?php if($_POST['CanHandleWalker'] == "W1") echo ' SELECTED'; ?>>W1</option>
							<option value="W2"<?php if($_POST['CanHandleWalker'] == "W2") echo ' SELECTED'; ?>>W2</option>
							<option value="W2"<?php if($_POST['CanHandleWalker'] == "W3") echo ' SELECTED'; ?>>W3</option>
							<option value="Yes"<?php if($_POST['CanHandleWalker'] == "Yes") echo ' SELECTED'; ?>>Yes</option>
						</select>
							<td>W1 = Flat Folding Walker, W2 = Handles stick out, W3 = XL Walker</td>
					</td>
				</tr>
				<tr>
					<td class="alignright">Can Handle Wheelchair:</td>
					<td>
						<select name="Wheelchair">
							<option value="No"<?php if($_POST['Wheelchair'] == "No") echo ' SELECTED'; ?>>No</option>
							<option value="WC1"<?php if($_POST['Wheelchair'] == "WC1") echo ' SELECTED'; ?>>WC1</option>
							<option value="WC2"<?php if($_POST['Wheelchair'] == "WC2") echo ' SELECTED'; ?>>WC2</option>
						</select>
							<td >WC1 = Transfer Chair, WC2 = Wheelchair, Do not Transport</td>
					</td>
				</tr>
				<tr>
					<td class="alignright">Can Handle Cane:</td>
					<td>
						<select name="CanHandleCane">
							<option value="No"<?php if($_POST['CanHandleCane'] == "No") echo ' SELECTED'; ?>>No</option>
							<option value="C1"<?php if($_POST['CanHandleCane'] == "C1") echo ' SELECTED'; ?>>C1</option>
							<option value="C2"<?php if($_POST['CanHandleCane'] == "C2") echo ' SELECTED'; ?>>C2</option>
							<option value="Yes"<?php if($_POST['CanHandleCane'] == "Yes") echo ' SELECTED'; ?>>Yes</option>
						</select>
							<td >C1 = Standard Cane, C2 = Quad Cane </td>
					</td>
				</tr>
				<tr>
					<td class="alignright">Has Driver Side Rear Door:</td>
					<td>
						<select name="HasDriverSideRearDoor">
							<option value="No"<?php if($_POST['HasDriverSideRearDoor'] == "No") echo ' SELECTED'; ?>>No</option>
							<option value="Yes"<?php if($_POST['HasDriverSideRearDoor'] == "Yes") echo ' SELECTED'; ?>>Yes</option>
						</select>
					</td>
				</tr>
				<tr>
					<td class="alignright">Has Passenger Side Rear Door:</td>
					<td>
						<select name="HasPassengerSideRearDoor">
							<option value="No"<?php if($_POST['HasPassengerSideRearDoor'] == "No") echo ' SELECTED'; ?>>No</option>
							<option value="Yes"<?php if($_POST['HasPassengerSideRearDoor'] == "Yes") echo ' SELECTED'; ?>>Yes</option>
						</select>
					</td>
				</tr>
                <tr>
                	<td class="alignright">Max Passengers:</td>
					<td>
						<input name="MaxPassengers" value="<?php echo $_POST['MaxPassengers']; ?>" maxlength="3" type="text"  />
					</td>
                </tr>
				<tr>
					<td class="alignright" colspan="2"><input type="submit" name="save" value="Save" /></td>
				</tr>
			</table>
		</form>
		<?php
	} else if(isset($_GET['field']) && $_GET['field'] == "editdrivervehicle"){
		if(isset($_POST['VehicleYear']) && isset($_POST['vehicleid']))
		{
			$required_fields = array('VehicleMake','VehicleModel','VehicleColor','VehicleDescription','LicenseState','LicenseNumber');
			$required_filled = true;
			
			foreach($required_fields as $k => $v){
				if($_POST[$v] == '')
					$required_filled = false;
			}
			
			if(!$required_filled){
				$error = 'All required name fields were not filled.';
			} else {
				$sql = "UPDATE `vehicle` SET `VehicleYear` = '" . mysql_real_escape_string($_POST['VehicleYear']) . "',
											 `VehicleMake` = '" . mysql_real_escape_string($_POST['VehicleMake']) . "',
											 `VehicleModel` = '" . mysql_real_escape_string($_POST['VehicleModel']) . "',
											 `VehicleColor` = '" . mysql_real_escape_string($_POST['VehicleColor']) . "',
											 `VehicleDescription` = '" . mysql_real_escape_string($_POST['VehicleDescription']) . "',
											 `LicenseState` = '" . mysql_real_escape_string($_POST['LicenseState']) . "',
											 `LicenseNumber` = '" . mysql_real_escape_string($_POST['LicenseNumber']) . "',
											 `VehicleHeight` = '" . mysql_real_escape_string($_POST['VehicleHeight']) . "',
											 `CanHandleCane` = '" . mysql_real_escape_string($_POST['CanHandleCane']) . "',
											 `CanHandleWalker` = '" . mysql_real_escape_string($_POST['CanHandleWalker']) . "',
											 `Wheelchair` = '" . mysql_real_escape_string($_POST['Wheelchair']) . "',
											 `HasDriverSideRearDoor` = '" . mysql_real_escape_string($_POST['HasDriverSideRearDoor']) . "',
											 `HasPassengerSideRearDoor` = '" . mysql_real_escape_string($_POST['HasPassengerSideRearDoor']) . "',
											 `MaxPassengers` = '" . mysql_real_escape_string($_POST['MaxPassengers']) . "' 
						WHERE `VehicleID` = " . mysql_real_escape_string($_POST['vehicleid']) . " LIMIT 1 ;";
				mysql_query($sql);
				
				redirect();
			}
		}
		include_once 'include/header.php';
		
		$vehicle = get_vehicle($_GET['vehicleid']);
		?>
		<center><h2>Edit Vehicle</h2></center>
		<?php echo '<center>' . $error . '</center>'; ?>
		<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?field=editdrivervehicle' . $edit_url; ?>">
			<input type="hidden" name="vehicleid" value="<?php echo $_GET['vehicleid']; ?>">
			<table style="margin:auto; width:950px;">
				<tr>
					<td class="alignright">Vehicle Year:</td>
					<td>
						<select name="VehicleYear">
							<?php
								for($i = (int)Date("Y") + 2; $i > (int)Date("Y") - 30; $i--){
									echo '<option value="' . $i . '"';
									if($i == $vehicle['VehicleYear'])
										echo ' SELECTED';
									echo '>' . $i . '</option>';
								}
									
							?>
						</select>
					</td>
				</tr>
				<tr>
					<td class="alignright">Vehicle Make:</td>
					<td><input type="text" name="VehicleMake" value="<?php echo $vehicle['VehicleMake']; ?>" maxlength="20" /></td>
				</tr>
				<tr>
					<td class="alignright">Vehicle Model:</td>
					<td><input type="text" name="VehicleModel" value="<?php echo $vehicle['VehicleModel']; ?>" maxlength="20" /></td>
				</tr>
				<tr>
					<td class="alignright">Vehicle Color:</td>
					<td><input type="text" name="VehicleColor" value="<?php echo $vehicle['VehicleColor']; ?>" maxlength="15" /></td>
					</tr>
				<tr>
					<td class="alignright">Vehicle Description:</td>
					<td><input type="text" name="VehicleDescription" value="<?php echo $vehicle['VehicleDescription']; ?>" maxlength="50" /></td>
				</tr>
				<tr>
					<td class="alignright">License Plate State:</td>
					<td><input type="text" name="LicenseState" value="<?php echo $vehicle['LicenseState']; ?>" maxlength="2" /></td>
				</tr>
				<tr>
					<td class="alignright">License Plate Number:</td>
					<td><input type="text" name="LicenseNumber" value="<?php echo $vehicle['LicenseNumber']; ?>" maxlength="12" /></td>
				</tr>
				<tr>
					<td class="alignright">Vehicle Height:</td>
					<td>
						<select name="VehicleHeight">
							<option value="HIGH"<?php if($vehicle['VehicleHeight'] == 'HIGH') echo 'SELECTED'; ?>>High</option>
							<option value="MEDIUM"<?php if($vehicle['VehicleHeight'] == 'MEDIUM') echo 'SELECTED'; ?>>Medium</option>
							<option value="LOW"<?php if($vehicle['VehicleHeight'] == 'LOW') echo 'SELECTED'; ?>>Low</option>
							<option value="UNKNOWN"<?php if($vehicle['VehicleHeight'] == 'UNKNOWN') echo 'SELECTED'; ?>>Unknown</option>
						</select>
					</td>
				</tr>
				<tr>
					<td class="alignright">Can Handle Cane:</td>
					<td>
						<select name="CanHandleCane">
							<option value="No"<?php if($vehicle['CanHandleCane'] == "No") echo ' SELECTED'; ?>>No</option>
							<option value="C1"<?php if($vehicle['CanHandleCane'] == "C1") echo ' SELECTED'; ?>>C1</option>
							<option value="C2"<?php if($vehicle['CanHandleCane'] == "C2") echo ' SELECTED'; ?>>C2</option>
							<option value="Yes"<?php if($vehicle['CanHandleCane'] == "Yes") echo ' SELECTED'; ?>>Yes</option>
						</select>
							<td >C1 = Standard Cane, C2 = Quad Cane </td>
					</td>
				</tr>
				<tr>
					<td class="alignright">Can Handle Walker:</td>
					<td>
						<select name="CanHandleWalker">
							<option value="No"<?php if($vehicle['CanHandleWalker'] == "No") echo ' SELECTED'; ?>>No</option>
							<option value="W1"<?php if($vehicle['CanHandleWalker'] == "W1") echo ' SELECTED'; ?>>W1</option>
							<option value="W2"<?php if($vehicle['CanHandleWalker'] == "W2") echo ' SELECTED'; ?>>W2</option>
							<option value="W3"<?php if($vehicle['CanHandleWalker'] == "W3") echo ' SELECTED'; ?>>W3</option>
							<option value="Yes"<?php if($vehicle['CanHandleWalker'] == "Yes") echo ' SELECTED'; ?>>Yes</option>
						</select>
							<td >W1 = Flat Folding Walker, W2 = Handles stick out, W3 = XL Walker</td>
					</td>
				</tr>
				<tr>
					<td class="alignright">Can Handle Wheelchair:</td>
					<td>
						<select name="Wheelchair">
							<option value="No"<?php if($vehicle['Wheelchair'] == "No") echo ' SELECTED'; ?>>No</option>
							<option value="WC1"<?php if($vehicle['Wheelchair'] == "WC1") echo ' SELECTED'; ?>>WC1</option>
							<option value="WC2"<?php if($vehicle['Wheelchair'] == "WC2") echo ' SELECTED'; ?>>WC2</option>
						</select>
							<td >WC1 = Transfer Chair, WC2 = Wheelchair, Do not Load</td>
					</td>
				</tr>
				<tr>
					<td class="alignright">Has Driver Side Rear Door:</td>
					<td>
						<select name="HasDriverSideRearDoor">
							<option value="No"<?php if($vehicle['HasDriverSideRearDoor'] == 'No') echo 'SELECTED'; ?>>No</option>
							<option value="Yes"<?php if($vehicle['HasDriverSideRearDoor'] == 'Yes') echo 'SELECTED'; ?>>Yes</option>
						</select>
					</td>
				</tr>
				<tr>
					<td class="alignright">Has Passenger Side Rear Door:</td>
					<td>
						<select name="HasPassengerSideRearDoor">
							<option value="No"<?php if($vehicle['HasPassengerSideRearDoor'] == 'No') echo 'SELECTED'; ?>>No</option>
							<option value="Yes"<?php if($vehicle['HasPassengerSideRearDoor'] == 'Yes') echo 'SELECTED'; ?>>Yes</option>
						</select>
					</td>
				</tr>
                 <tr>
                	<td class="alignright">Max Passengers:</td>
					<td>
						<input name="MaxPassengers" maxlength="3" value="<?php echo $vehicle['MaxPassengers']; ?>" type="text"  />
					</td>
                </tr>
				<tr>
					<td class="alignright" colspan="2"><input type="submit" name="save" value="Save" /></td>
				</tr>
			</table>
		</form>
		<?php
	} else if(isset($_GET['field']) && $_GET['field'] == "deletedrivervehicle"){
		if(isset($_GET['vehicleid']) && isset($_POST['Delete']))
		{
			$query = "SELECT * FROM `vehicle_driver` WHERE `UserID` = '" . mysql_real_escape_string($user_id) . "' AND `VehicleID` = '" . mysql_real_escape_string($_GET['vehicleid']) . "' LIMIT 1;";
			$result = mysql_query($query) or die(mysql_error());
			$result = mysql_fetch_array($result);
			
			$query = "DELETE FROM `vehicle_driver` WHERE `vehicle_driver`.`VehicleID` = '" . mysql_real_escape_string($result['VehicleID']) . "' AND `vehicle_driver`.`UserID` = '" . mysql_real_escape_string($result['UserID']) . "' LIMIT 1";
			mysql_query($query) or die(mysql_error());
			
			$query = "DELETE FROM `vehicle` WHERE `vehicle`.`VehicleID` = '" . mysql_real_escape_string($result['VehicleID']) . "' LIMIT 1";
			mysql_query($query) or die(mysql_error());
			
			redirect();
		}
		else
		{
			$vehicle = get_vehicle($_GET['vehicleid']);
			include_once 'include/header.php';
			
			?>
			<center><h2>Delete a Vehicle</h2></center>
			<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?field=deletedrivervehicle&vehicleid=' . $_GET['vehicleid'] . $edit_url; ?>">
				<table style="margin:auto; width:350px;">
					<tr>
						<td colspan="2"><center><b>Vehicle Identity: <?php echo $_GET['vehicleid']; ?></b></center></td>
					</tr>
					<tr>
						<td class="alignright" style="width:175px;">Vehicle Year:</td>
						<td><?php echo $vehicle['VehicleYear']; ?></td>
					</tr>
					<tr>
						<td class="alignright">Vehicle Make:</td>
						<td><?php echo $vehicle['VehicleMake']; ?></td>
					</tr>
					<tr>
						<td class="alignright">Vehicle Model:</td>
						<td><?php echo $vehicle['VehicleModel']; ?></td>
					</tr>
					<tr>
						<td class="alignright">Vehicle Color:</td>
						<td><?php echo $vehicle['VehicleColor']; ?></td>
					</tr>
					<tr>
						<td class="alignright">Vehicle Description:</td>
						<td><?php echo $vehicle['VehicleDescription']; ?></td>
					</tr>
					<tr>
						<td class="alignright">License State:</td>
						<td><?php echo $vehicle['LicenseState']; ?></td>
					</tr>
					<tr>
						<td class="alignright">License Number:</td>
						<td><?php echo $vehicle['LicenseNumber']; ?></td>
					</tr>
					<tr>
						<td class="alignright">Vehicle Height:</td>
						<td><?php echo $vehicle['VehicleHeight']; ?></td>
					</tr>
					<tr>
						<td class="alignright">Can Handle Walker:</td>
						<td><?php echo $vehicle['CanHandleWalker']; ?></td>
					</tr>
					<tr>
						<td class="alignright">Can Handle Cane:</td>
						<td><?php echo $vehicle['CanHandleCane']; ?></td>
					</tr>
					<tr>
						<td class="alignright">Driver Side Door:</td>
						<td><?php echo $vehicle['HasDriverSideRearDoor']; ?></td>
					</tr>
					<tr>
						<td class="alignright">Passenger Side door:</td>
						<td><?php echo $vehicle['HasPassengerSideRearDoor']; ?></td>
					</tr>
					<tr>
						<td class="alignright" colspan="2"><input type="submit" name="Delete" value="Delete" /></td>
					</tr>
				</table>
			</form>
			<?php
		}
	}
	else if(isset($_GET['field']) && $_GET['field'] == "riderpreferences")
	{
		if(isset($_POST['HighVehicleOK']) && isset($_POST['MediumVehicleOK']) && isset($_POST['LowVehicleOK']) &&
		 isset($_POST['FelonDriverOK']) && isset($_POST['DriverStays']) && 
		 isset($_POST['HasWalker']) && isset($_POST['HasWheelchair']) && isset($_POST['HasCane']) && 
		 isset($_POST['NeedsPackageHelp']) && isset($_POST['NeedsHelpToCar']) && 
		 isset($_POST['EnterDriverSide']) && isset($_POST['EnterPassengerSide']) && isset($_POST['HasMemoryLoss']) && 
		 isset($_POST['VisionLevel'])&& isset($_POST['HasCaretaker'])&& isset($_POST['OtherNotes']) && isset($_POST['Title'])
		  && isset($_POST['FirstName']) && isset($_POST['MiddleInitial']) && isset($_POST['LastName']) && isset($_POST['Suffix']))
		{
			$preferences = get_user_rider_preferences($user_id);
			$caretaker_id = NULL;
			
			if($preferences['HasCaretaker'] == "Yes" && $_POST['HasCaretaker'] == "No"){
                remove_rider_caretaker($user_id);
				$caretaker_id = NULL;
			} else if($preferences['HasCaretaker'] == "No" && $_POST['HasCaretaker'] == "Yes"){
				//create caretaker
				$caretaker_id = add_person_name($_POST['Title'],$_POST['FirstName'],$_POST['MiddleInitial'],$_POST['LastName'],$_POST['Suffix'],$_POST['NickName']);
			} else if($preferences['HasCaretaker'] == "Yes" && $_POST['HasCaretaker'] == "Yes") {
				//update caretaker
				$caretaker_id = update_person_name($preferences['CaretakerID'],$_POST['Title'],$_POST['FirstName'],$_POST['MiddleInitial'],$_POST['LastName'],$_POST['Suffix'],$_POST['NickName']);
			}
			
			if($caretaker_id != NULL)
				$caretaker_mysql = ",`CaretakerID` = '" . mysql_real_escape_string($caretaker_id) . "'";
			else
				$caretaker_mysql = ",`CaretakerID` = NULL";
				
             $care_taker_birthdate = mysql_real_escape_string($_POST['CaretakerBirthYear'] . "-" . $_POST['CaretakerBirthMonth'] . "-" . $_POST['CaretakerBirthDay']);
             
			$query = "UPDATE `rider_preferences` SET `HighVehicleOK` = '" . mysql_real_escape_string($_POST['HighVehicleOK']) . 
			         "', `MediumVehicleOK` = '" . mysql_real_escape_string($_POST['MediumVehicleOK']) . 
			         "', `LowVehicleOK` = '" . mysql_real_escape_string($_POST['LowVehicleOK']) . 
			         "', `FelonDriverOK` = '" . mysql_real_escape_string($_POST['FelonDriverOK']) .
			         "', `DriverStays` = '" . mysql_real_escape_string($_POST['DriverStays']) .
			         "', `HasWalker` = '" . mysql_real_escape_string($_POST['HasWalker']) . 
			         "', `HasWheelchair` = '" . mysql_real_escape_string($_POST['HasWheelchair']) . 
			         "', `HasCane` = '" . mysql_real_escape_string($_POST['HasCane']) . 
			         "', `NeedsPackageHelp` = '" . mysql_real_escape_string($_POST['NeedsPackageHelp']) .
			         "', `NeedsHelpToCar` = '" . mysql_real_escape_string($_POST['NeedsHelpToCar']) . 
			         "', `EnterDriverSide` = '" . mysql_real_escape_string($_POST['EnterDriverSide']) . 
			         "', `EnterPassengerSide` = '" . mysql_real_escape_string($_POST['EnterPassengerSide']) .
			         "', `HasServiceAnimal` = '" . mysql_real_escape_string($_POST['HasServiceAnimal']) .
			         "', `HasSmallPetInCarrier` = '" . mysql_real_escape_string($_POST['HasSmallPetInCarrier']) .
			         "', `HasMemoryLoss` = '" . mysql_real_escape_string($_POST['HasMemoryLoss']) .
			         "', `VisionLevel` = '" . mysql_real_escape_string($_POST['VisionLevel']) .
			         "', `HearingLevel` = '" . mysql_real_escape_string($_POST['HearingLevel']) .
			         "', `HasCaretaker` = '" . mysql_real_escape_string($_POST['HasCaretaker']) . 
			         "', `SensitiveToSmells` = '" . mysql_real_escape_string($_POST['SensitiveToSmells']) .
			         "', `SmokerOrPerfumeUser` = '" . mysql_real_escape_string($_POST['SmokerOrPerfumeUser']) .
					 "', `CaretakerBirthday` = '".$care_taker_birthdate. 
					 "', FrontSeat = '".$_POST['FrontSeat'] .
			         "', `OtherNotes` = '" . mysql_real_escape_string($_POST['OtherNotes']) . "'" .
			         $caretaker_mysql . " WHERE `rider_preferences`.`UserID` = '" . mysql_real_escape_string($user_id) . "' LIMIT 1;";
			mysql_query($query) or die(mysql_error() . " : " . $query);
			 
			redirect();
		}
		else
		{
			include_once 'include/header.php';
			
			$preferences = get_user_rider_preferences($user_id);
			if($preferences['CaretakerID'] != NULL)
				$CareTakerName = get_name( $preferences['CaretakerID'] );
			?>
			<center><h2>Edit Rider Preferences</h2></center>
			<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?field=riderpreferences' . $edit_url; ?>">
				<table style="margin:auto;">
					<tr>
						<td class="alignright" style="width:175px;">High Vehicle:</td>
						<td>
							<select name="HighVehicleOK">
								<option value="Yes" <?php if($preferences['HighVehicleOK'] == "Yes") echo 'SELECTED'; ?>>Yes</option>
								<option value="No" <?php if($preferences['HighVehicleOK'] == "No") echo 'SELECTED'; ?>>No</option>
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Medium Vehicle:</td>
						<td>
							<select name="MediumVehicleOK">
								<option value="Yes" <?php if($preferences['MediumVehicleOK'] == "Yes") echo 'SELECTED'; ?>>Yes</option>
								<option value="No" <?php if($preferences['MediumVehicleOK'] == "No") echo 'SELECTED'; ?>>No</option>
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Low Vehicle:</td>
						<td>
							<select name="LowVehicleOK">
								<option value="Yes" <?php if($preferences['LowVehicleOK'] == "Yes") echo 'SELECTED'; ?>>Yes</option>
								<option value="No" <?php if($preferences['LowVehicleOK'] == "No") echo 'SELECTED'; ?>>No</option>
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Felon Driver OK:</td>
						<td>
							<select name="FelonDriverOK">
								<option value="No" <?php if($preferences['FelonDriverOK'] == "No") echo 'SELECTED'; ?>>No</option>
								<option value="Yes" <?php if($preferences['FelonDriverOK'] == "Yes") echo 'SELECTED'; ?>>Yes</option>								
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Driver Stays:</td>
						<td>
							<select name="DriverStays">
								<option value="No" <?php if($preferences['DriverStays'] == "No") echo 'SELECTED'; ?>>No</option>
								<option value="Yes" <?php if($preferences['DriverStays'] == "Yes") echo 'SELECTED'; ?>>Yes</option>								
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Has Walker:</td>
						<td>
							<select name="HasWalker">
								<option value="No" <?php if($preferences['HasWalker'] == "No") echo 'SELECTED'; ?>>No</option>
								<option value="W1" <?php if($preferences['HasWalker'] == "W1") echo 'SELECTED'; ?>>W1</option>
								<option value="W2" <?php if($preferences['HasWalker'] == "W2") echo 'SELECTED'; ?>>W2</option>
								<option value="W3" <?php if($preferences['HasWalker'] == "W3") echo 'SELECTED'; ?>>W3</option>								
							</select>
							<td >W1 = Walker Folds Flat, W2 = Handles stick out, W3 = XL Walker </td>
						</td>
					</tr>
					<tr>
						<td class="alignright">Has Wheelchair:</td>
						<td>
							<select name="HasWheelchair">
								<option value="No" <?php if($preferences['HasWheelchair'] == "No") echo 'SELECTED'; ?>>No</option>
								<option value="WC1" <?php if($preferences['HasWheelchair'] == "WC1") echo 'SELECTED'; ?>>WC1</option>
								<option value="WC2" <?php if($preferences['HasWheelchair'] == "WC2") echo 'SELECTED'; ?>>WC2</option>								
							</select>
							<td >WC1 = Transfer Chair, WC2 = Wheelchair, Do not load </td>
						</td>
					</tr>
					<tr>
						<td class="alignright">Has Cane:</td>
						<td>
							<select name="HasCane">
								<option value="No" <?php if($preferences['HasCane'] == "No") echo 'SELECTED'; ?>>No</option>
								<option value="C1" <?php if($preferences['HasCane'] == "C1") echo 'SELECTED'; ?>>C1</option>
								<option value="C2" <?php if($preferences['HasCane'] == "C2") echo 'SELECTED'; ?>>C2</option>
								<option value="Yes" <?php if($preferences['HasCane'] == "Yes") echo 'SELECTED'; ?>>Yes</option>								
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Needs Package Help:</td>
						<td>
							<select name="NeedsPackageHelp">
								<option value="No" <?php if($preferences['NeedsPackageHelp'] == "No") echo 'SELECTED'; ?>>No</option>
								<option value="Yes" <?php if($preferences['NeedsPackageHelp'] == "Yes") echo 'SELECTED'; ?>>Yes</option>								
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Needs Help To Car:</td>
						<td>
							<select name="NeedsHelpToCar">
								<option value="No" <?php if($preferences['NeedsHelpToCar'] == "No") echo 'SELECTED'; ?>>No</option>
								<option value="Yes" <?php if($preferences['NeedsHelpToCar'] == "Yes") echo 'SELECTED'; ?>>Yes</option>								
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Enter Driver Side:</td>
						<td>
							<select name="EnterDriverSide">
								<option value="Yes" <?php if($preferences['EnterDriverSide'] == "Yes") echo 'SELECTED'; ?>>Yes</option>
								<option value="No" <?php if($preferences['EnterDriverSide'] == "No") echo 'SELECTED'; ?>>No</option>							
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Enter Passenger Side:</td>
						<td>
							<select name="EnterPassengerSide">
								<option value="Yes" <?php if($preferences['EnterPassengerSide'] == "Yes") echo 'SELECTED'; ?>>Yes</option>
								<option value="No" <?php if($preferences['EnterPassengerSide'] == "No") echo 'SELECTED'; ?>>No</option>					
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Has Service Animal:</td>
						<td>
							<select name="HasServiceAnimal">
								<option value="Yes" <?php if($preferences['HasServiceAnimal'] == "Yes") echo 'SELECTED'; ?>>Yes</option>
								<option value="No" <?php if($preferences['HasServiceAnimal'] == "No") echo 'SELECTED'; ?>>No</option>					
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Has Small Pet In Carrier:</td>
						<td>
							<select name="HasSmallPetInCarrier">
								<option value="Yes" <?php if($preferences['HasSmallPetInCarrier'] == "Yes") echo 'SELECTED'; ?>>Yes</option>
								<option value="No" <?php if($preferences['HasSmallPetInCarrier'] == "No") echo 'SELECTED'; ?>>No</option>					
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Sensitive to smells:</td>
						<td>
							<select name="SensitiveToSmells">
								<option value="No" <?php if($preferences['SensitiveToSmells'] == "No") echo 'SELECTED'; ?>>No</option>
								<option value="Yes" <?php if($preferences['SensitiveToSmells'] == "Yes") echo 'SELECTED'; ?>>Yes</option>								
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Smoker or perfume user:</td>
						<td>
							<select name="SmokerOrPerfumeUser">
								<option value="No" <?php if($preferences['SmokerOrPerfumeUser'] == "No") echo 'SELECTED'; ?>>No</option>
								<option value="Yes" <?php if($preferences['SmokerOrPerfumeUser'] == "Yes") echo 'SELECTED'; ?>>Yes</option>								
							</select>
							<td >Smoking is not permitted while passenger in car</td>
						</td>
					</tr>
					<tr>
						<td class="alignright">Has Memory Loss:</td>
						<td>
							<select name="HasMemoryLoss">
								<option value="No" <?php if($preferences['HasMemoryLoss'] == "No") echo 'SELECTED'; ?>>No</option>
								<option value="ML1" <?php if($preferences['HasMemoryLoss'] == "ML1") echo 'SELECTED'; ?>>ML1</option>
								<option value="ML2" <?php if($preferences['HasMemoryLoss'] == "ML2") echo 'SELECTED'; ?>>ML2</option>								
							</select>
							<td >ML1 = Slight Memory Loss, ML2 = Severe Memory Loss </td>
						</td>
					</tr>
					<tr>
						<td class="alignright">Vision Level:</td>
						<td>
							<select name="VisionLevel">
								<option value="VL0"<?php if ($preferences['VisionLevel']=='VL0') { echo ' selected="selected"'; } ?>>VL0</option>
								<option value="Part"<?php if ($preferences['VisionLevel']=='Part') { echo ' selected="selected"'; } ?>>Part</option>
								<option value="None"<?php if ($preferences['VisionLevel']=='None') { echo ' selected="selected"'; } ?>>None</option>
								<option value="VL1"<?php if ($preferences['VisionLevel']=='VL1') { echo ' selected="selected"'; } ?>>VL1</option>
								<option value="VL2"<?php if ($preferences['VisionLevel']=='VL2') { echo ' selected="selected"'; } ?>>VL2</option>								
							</select>
							<td >VL0= Full, Part= Partial, VL1= Cannot Correct to 20/20, VL2= Blind</td>
						</td>
					</tr>
					<tr>
						<td class="alignright">Hearing Level:</td>
						<td>
							<select name="HearingLevel">
								<option value="OK"<?php if ($preferences['HearingLevel']=='OK') { echo ' selected="selected"'; } ?>>OK</option>
								<option value="HL1"<?php if ($preferences['HearingLevel']=='HL1') { echo ' selected="selected"'; } ?>>HL1</option>
								<option value="HL2"<?php if ($preferences['HearingLevel']=='HL2') { echo ' selected="selected"'; } ?>>HL2</option>
								<option value="HL3"<?php if ($preferences['HearingLevel']=='HL3') { echo ' selected="selected"'; } ?>>HL3</option>				
							</select>
							<td >HL1= Some hearing loss, HL2= Asst. Device, HL3= Little or no hearing</td>
						</td>
					</tr>
					<tr>
						<td class="alignright">Additional Rider:</td>
						<td>
							<select name="HasCaretaker">
								<option value="No" <?php if($preferences['HasCaretaker'] == "No") echo 'SELECTED'; ?>>No</option>
								<option value="Yes" <?php if($preferences['HasCaretaker'] == "Yes") echo 'SELECTED'; ?>>Yes</option>								
							</select>
						</td>
					</tr>
					<?php if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, "Franchisee")) { ?>
					<tr>
						<td class="alignright">Front Seat Preference:</td>
						<td>
							<select name="FrontSeat">
								<option value="No" <?php if($preferences['FrontSeat'] == "No") echo 'SELECTED'; ?>>No</option>
								<option value="Yes" <?php if($preferences['FrontSeat'] == "Yes") echo 'SELECTED'; ?>>Yes</option>								
							</select>
						</td>
					</tr>
					<?php } ?>
					<tr>
						<td colspan="2">
							<b>Additional Rider Name</b><br><span style="font-size:11px;">*only if you checked Yes to the Additional Rider question.</span><br>
							<table>
								<tr>
									<td class="alignright" width="80px">Title</td>
									<td><input type="text" name="Title" maxlength="10" value="<?php echo $CareTakerName['Title']; ?>" style="width:50px;" /></td>
								</tr>
								<tr>
									<td class="alignright">First Name</td>
									<td><input type="text" name="FirstName" maxlength="30" value="<?php echo $CareTakerName['FirstName']; ?>" style="width:200px;" /></td>
								</tr>
								<tr>
									<td class="alignright">NickName</td>
									<td><input type="text" name="NickName" maxlength="30" value="<?php echo $CareTakerName['NickName']; ?>" style="width:200px;" /></td>
								</tr>
								<tr>
									<td class="alignright">Middle Initial</td>
									<td><input type="text" name="MiddleInitial" maxlength="1" value="<?php echo $CareTakerName['MiddleInitial']; ?>" style="width:50px;" /></td>
								</tr>
								<tr>
									<td class="alignright">Last Name</td>
									<td><input type="text" name="LastName" maxlength="30" value="<?php echo $CareTakerName['LastName']; ?>" style="width:200px;" /></td>
								</tr>
								<tr>
									<td class="alignright">Suffix</td>
									<td><input type="text" name="Suffix" maxlength="10" value="<?php echo $CareTakerName['Suffix']; ?>" style="width:50px;" /></td>
								</tr>
								<tr>
					               <td class="alignright">Additional Rider Birthday</td>
					           <td>
					           <?php
					               if($preferences['CaretakerBirthday'] != NULL)
					               $date = get_date($preferences['CaretakerBirthday']);
					               print_month_select("CaretakerBirthMonth", FALSE, $date['Month']);
					               echo " / ";
					               print_day_select("CaretakerBirthDay", FALSE, $date['Day']);
					               echo " / ";
					               print_year_select(date("Y") - 110, 92, "CaretakerBirthYear", FALSE, $date['Year']);
					           ?>
					           </td>
					       </tr>
					       <tr>
					           <td class="alignright">Additional Rider Background Checked</td>
					           <td>
					               <select name="CaretakerBackgroundCheck">
					                   <option value="Yes"<?php if($preferences['CaretakerBackgroundCheck'] == 'Yes') echo ' SELECTED'; ?>>Yes</option>
					                   <option value="No"<?php if($preferences['CaretakerBackgroundCheck'] == 'No') echo ' SELECTED'; ?>>No</option>
					               </select>
					           </td>
					       </tr>
							</table>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							Other Notes:<br>
							<textarea style="width:350px; height:100px;" name="OtherNotes"><?php echo $preferences['OtherNotes']; ?></textarea>
						</td>
					</tr>
					<tr>
						<td class="alignright" colspan="2"><input type="submit" name="save" value="Save" /></td>
					</tr>
				</table>
			</form>
		<?php
		}
	}
	else if(isset($_GET['field']) && $_GET['field'] == "createphonenumber")
	{
		if(isset($_POST['PhoneType']) && isset($_POST['PhoneNumber']))
		{
			$required_fields = array('PhoneNumber');
			$required_filled = true;
			
			foreach($required_fields as $k => $v){
				if($_POST[$v] == '')
					$required_filled = false;
			}
			
			if(!$required_filled){
				$error = 'All required name fields were not filled.';
			} else {
				$phone_id = add_phone_number_for_user($_POST['PhoneNumber'],$_POST['PhoneType'],$user_id, $_POST["canSMS"] == 'on' ? 'Y': 'N', $_POST["sms_provider"], $_POST["Ext"], $_POST["sms_preferences"], $_POST["phonedescription"]);
				if($_POST['IsPrimary'])
					set_primary_phone_for_user($user_id, $phone_id);
				redirect();
			}
		}
		include_once 'include/header.php';
		
		?>
		<script>
		jQuery(function($) {
			$('select[name="PhoneType"]').on('change',function() {
				if($(this).val() == 'MOBILE') $('.mobile_row').show();
				else $('.mobile_row').hide();
			});
			
			$('select[name="PhoneType"]').parents('form').on('submit',function() {
				if($('input[name="canSMS"]:checked').length > 0 && $('select[name="sms_provider"]').val() == 0) {
					alert('You must select a provider if you want to receive Text Messages.');
					$('select[name="sms_provider"]').focus();
					return false;
				}
				return true;
			});
		});	
		</script>
		<center><h2>Create Phone Number</h2></center>
		<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?field=createphonenumber' . $edit_url; ?>">
			<table style="margin:auto; width:400px;">
				<tr>
					<td class="alignright">Phone Number Type:</td>
					<td>
						<select name="PhoneType">
							<option value="HOME">Home</option>
							<option value="MOBILE">Mobile</option>
							<option value="WORK">Work</option>
							<option value="FAX">Fax</option>
							<option value="OTHER">Other</option>
						</select>
					</td>
				</tr>
				<tr>
					<td class="alignright">Phone Number:</td>
					<td><input type="text" name="PhoneNumber" maxlength="20" style="vertical-align: bottom;" /> x<input type="text" name="Ext" maxlength="20" size=5 style="vertical-align: bottom;" /></td>
				</tr>
        <tr>
                	<td class="alignright">Is Primary:</td>
                    <td><input type="checkbox" name="IsPrimary"  /></td>
				</tr>
                <tr class="mobile_row" style='display: none;'>
                	<td nowrap class="alignright">Can We Send you Text Messages?:</td>
                    <td nowrap><input type="checkbox" name="canSMS"  /> (note: Standard Text message rates apply)</td>
								<tr>
                <tr class="mobile_row" style='display: none;'>
                	<td nowrap class="alignright">Cell Provider:</td>
                    <td nowrap><select name=sms_provider size=1><option value=0>Select...</option><?php
                    	$sql = "select id, name from sms_providers order by name";
                    	$r = mysql_query($sql);
                    	while($rs = mysql_fetch_assoc($r)) echo "<option value=$rs[id]>$rs[name]</option>\n";
                    	?></select></td>
								<tr>
								<tr class="mobile_row" style="display: none;">
									<td nowrap class="alignright">SMS Preferences</td>	
									<td nowrap>
										<input type=radio name="sms_preferences" value='FIRST' > Text on 1st Ride<br>
										<input type=radio name="sms_preferences" value='SUBSEQUENT' > Text on Subsequent Rides<br>
										<input type=radio name="sms_preferences" value='ALL' > Text on All Rides<br>
										<!--<input type=radio name="sms_preferences" value='SIXTY' > Text if more than 60m between rides-->
									</td>
								</tr>
								<tr>
									<td class=alignright>Description:</td>	
									<td><input type=text name="phonedescription" size=20 maxlength=20></td>
								</tr>
					<td class="alignright" colspan="2"><input type="submit" name="save" value="Save" /></td>
				</tr>
			</table>
		</form>
		<?php
	}
	else if(isset($_GET['field']) && isset($_GET['phoneid']) && $_GET['field'] == "editphonenumber")
	{
		if(isset($_POST['PhoneType']) && isset($_POST['PhoneNumber']))
		{
			$required_fields = array('PhoneNumber');
			$required_filled = true;
			
			foreach($required_fields as $k => $v){
				if($_POST[$v] == '')
					$required_filled = false;
			}
			
			if(!$required_filled){
				$error = 'All required name fields were not filled.';
			} else {
				$phone_id = edit_phone_number_for_user($_GET['phoneid'], $_POST['PhoneNumber'], $_POST['PhoneType'], $user_id, $_POST['canSMS'] == 'on' ? 'Y': 'N', $_POST["sms_provider"], $_POST["Ext"], $_POST["sms_preferences"], $_POST["phonedescription"]);
				if($_POST['IsPrimary'])
					set_primary_phone_for_user($user_id, $phone_id);
				redirect();
			}
		}
		include_once 'include/header.php';
		$phone = get_user_phone_numbers($user_id);
		$phone = $phone[$_GET['phoneid']];
		?>
		<center><h2>Edit Phone Number</h2></center>
		<script>
		jQuery(function($) {
			$('select[name="PhoneType"]').on('change',function() {
				if($(this).val() == 'MOBILE') $('.mobile_row').show();
				else $('.mobile_row').hide();
			});
			
			if($('select[name="PhoneType"]').val() == 'MOBILE') $('.mobile_row').show();
			
			$('select[name="PhoneType"]').parents('form').on('submit',function() {
				if($('input[name="canSMS"]:checked').length > 0 && $('select[name="sms_provider"]').val() == 0) {
					alert('You must select a provider if you want to receive Text Messages.');
					$('select[name="sms_provider"]').focus();
					return false;
				}
				return true;
			});
		});	
		</script>
		<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?field=editphonenumber&phoneid=' . $_GET['phoneid'] . $edit_url; ?>">
			<table style="margin:auto; width:400px;">
				<tr>
					<td class="alignright">Phone Number Type:</td>
					<td>
						<select name="PhoneType">
							<option value="HOME"<?php if($phone['PhoneType'] == 'HOME') echo ' SELECTED'; ?>>Home</option>
							<option value="MOBILE"<?php if($phone['PhoneType'] == 'MOBILE') echo ' SELECTED'; ?>>Mobile</option>
							<option value="WORK"<?php if($phone['PhoneType'] == 'WORK') echo ' SELECTED'; ?>>Work</option>
							<option value="FAX"<?php if($phone['PhoneType'] == 'FAX') echo ' SELECTED'; ?>>Fax</option>
							<option value="OTHER"<?php if($phone['PhoneType'] == 'OTHER') echo ' SELECTED'; ?>>Other</option>
						</select>
					</td>
				</tr>
				<tr>
					<td align=right>Phone Number:</td>
					<td nowrap style='vertical-align: bottom;'><input type="text" name="PhoneNumber" value="<?php echo $phone['PhoneNumber']; ?>" maxlength="20"  style="vertical-align: bottom;"/> x<input type=text name="Ext" size=5 value="<?php echo $phone["Ext"]; ?>" maxlength=20 style="vertical-align: bottom;"/></td>
				</tr>
                <tr>
                	<td class="alignright">Is Primary:</td>
                    <td><input type="checkbox" name="IsPrimary" <?php if($phone['IsPrimary'] == 'Yes') echo ' CHECKED'; ?> /></td>
        </tr>
                <tr class="mobile_row" style='display: none;'>
                	<td nowrap class="alignright">Can We Send you Text Messages?:</td>
                    <td nowrap><input type="checkbox" name="canSMS" <?php if($phone["canSMS"] == 'Y') echo "CHECKED"; ?> /> (note: Standard Text message rates apply)</td>
								<tr>
                <tr class="mobile_row" style='display: none;'>
                	<td nowrap class="alignright">Cell Provider:</td>
                    <td nowrap><select name=sms_provider size=1><option value=0>Select...</option><?php
                    	$sql = "select id, name from sms_providers order by name";
                    	$r = mysql_query($sql);
                    	while($rs = mysql_fetch_assoc($r)) echo "<option value=$rs[id] ".($phone["ProviderID"] == $rs["id"] ? "SELECTED" : "").">$rs[name]</option>\n";
                    	?></select></td>
								<tr>
								<tr class="mobile_row" style="display: none;">
									<td nowrap class="alignright">SMS Preferences</td>	
									<td nowrap>
										<input type=radio name="sms_preferences" value='FIRST' <?php echo $phone["sms_preferences"] == "FIRST" ? "checked" : ""; ?>> Text on 1st Ride<br>
										<input type=radio name="sms_preferences" value='SUBSEQUENT' <?php echo $phone["sms_preferences"] == "SUBSEQUENT" ? "checked" : ""; ?>> Text on Subsequent Rides<br>
										<input type=radio name="sms_preferences" value='ALL' <?php echo $phone["sms_preferences"] == "ALL" ? "checked" : ""; ?>> Text on All Rides<br>
										<!--<input type=radio name="sms_preferences" value='SIXTY' <?php echo $phone["sms_preferences"] == "SIXTY" ? "checked" : ""; ?>> Text if more than 60m between rides-->
									</td>
								</tr>
								<tr>
									<td class="alignright">Description:</td>	
									<td><input type=text size=20 name=phonedescription value="<?php echo $phone["phonedescription"]; ?>"></td>
								</tr>
				<tr>
					<td class="alignright" colspan="2"><input type="submit" name="save" value="Save" /></td>
				</tr>
			</table>
		</form>
		<?php
	}
	else if(isset($_GET['field']) && isset($_GET['phoneid']) && $_GET['field'] == "deletephonenumber")
	{
		if(isset($_POST['Delete']))
		{
			$query = "SELECT * FROM `user_phone` WHERE `UserID` = '" . $user_id . "' AND `PhoneID` = '" . $_GET['phoneid'] . "' LIMIT 1;";
			$result = mysql_query($query) or die(mysql_error());
			$result = mysql_fetch_array($result);
			
			$query = "DELETE FROM `user_phone` WHERE `UserID` = '" . $result['UserID'] . "' AND `PhoneID` = '" . $result['PhoneID'] . "' LIMIT 1;";
			mysql_query($query) or die(mysql_error());
			
			$query = "select * from emergency_contact_phone where PhoneID = $_GET[phoneid]";
			$r = mysql_query($query);
			if(mysql_num_rows($r) > 0) 
				mysql_query("delete from emergency_contact_phone where PhoneID = $_GET[phoneid]");
			
			$query = "DELETE FROM `phone` WHERE `PhoneID` = '" . $result['PhoneID'] . "' LIMIT 1";
			mysql_query($query) or die(mysql_error());
			
			if($user_id == get_current_user_id())
				header("location: " . site_url() . "account.php");
			else
				header("location: " . site_url() . "account.php?id=" . $user_id);
		}
		else
		{
			include_once 'include/header.php';
			
			$phone = get_phone_number($_GET['phoneid']);
			?>
			<center><h2>Delete Phone Number</h2></center>
			<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?field=deletephonenumber&phoneid=' . $_GET['phoneid'] . $edit_url; ?>">
				<table style="margin:auto; width:300px;">
					<tr>
						<td class="alignright">Phone Number Type:</td>
						<td><?php echo $phone['PhoneType']; ?></td>
					</tr>
					<tr>
						<td class="alignright">Phone Number:</td>
						<td><?php echo $phone['PhoneNumber']; ?></td>
					</tr>
					<tr>
						<td class="alignright" colspan="2"><input type="submit" name="Delete" value="Delete" /></td>
					</tr>
				</table>
			</form>
			<?php
		}
	}
	else if(isset($_GET['field']) && $_GET['field'] == "addrole" && (current_user_has_role(1, "FullAdmin") || current_user_has_role($franchise, "Franchisee")))
	{
		if(isset($_POST['Role']))
		{
			if(current_user_has_role(1, "FullAdmin")){
				$franchise = $_POST['Franchise'];
				if($_POST['Role'] == "FullAdmin")
					$franchise = 1;
			} else {
				$franchise = get_current_user_franchise();
			}
			set_role_for_user($user_id, $franchise, $_POST['Role'], $_POST['ReadOnly'] == 'on' ? 1 : 0);
			unset($_SESSION['UserFranchises']);
			$rider_id = get_user_rider_id( $user_id );
			$driver_id = get_user_driver_id( $user_id );
			
			if($rider_id){
				$sql = "UPDATE `rider` SET `RiderStatus` = 'Active' WHERE `UserID` =$user_id LIMIT 1;";
				mysql_query($sql);
			} else if($driver_id){
				$sql = "UPDATE `driver` SET `DriverStatus` = 'Active' WHERE `UserID` =$driver_id LIMIT 1;";
				mysql_query($sql);
			}
			
			if($user_id == get_affected_user_id()){
				if($_POST['Role'] == 'Rider'){
					header("location: " . site_url() . "edit_user.php?field=createrider");
				} else if($_POST['Role'] == 'Driver'){
					header("location: " . site_url() . "edit_user.php?field=createdriver");
				} else if($_POST['Role'] == 'CareFacilityAdmin'){
					header("location: " . site_url() . "edit_user.php?field=addcarefacility");
				} else if($_POST['Role'] == 'LargeFacilityAdmin'){
					header("Location: edit_user.php?field=connectlargefacility");
				} else{
					header("location: " . site_url() . "account.php");
				}
			} else{
				if($_POST['Role'] == 'Rider'){
					header("location: " . site_url() . "edit_user.php?field=createrider&id=" . $user_id);
				} else if($_POST['Role'] == 'Driver'){
					header("location: " . site_url() . "edit_user.php?field=createdriver&id=" . $user_id);
				} else if($_POST['Role'] == 'CareFacilityAdmin'){
					header("location: " . site_url() . "edit_user.php?field=addcarefacility&id=" . $user_id);
				} else if($_POST['Role'] == 'LargeFacilityAdmin'){
					header("Location: edit_user.php?field=connectlargefacility&id=" . $user_id);
				} else{
					header("location: " . site_url() . "account.php?&id=" . $user_id);
				}
			}
				
		}
		include_once 'include/header.php';
		
		?>
		<h2><center>User Roles</center></h2>
		<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?field=addrole' . $edit_url; ?>">
			<table style="margin:auto;">
				<tr>
					<td>Add Role</td>
					<td>
						<select name="Role" onChange="if(this.options[this.selectedIndex].value == 'Franchisee') jQuery('#ReadOnly').show(); else jQuery('#ReadOnly').hide();">
							<?php
								if(!user_has_role($user_id,'Rider')) echo '<option value="Rider">Rider</option>';
								if(!user_has_role($user_id,'Driver')) echo '<option value="Driver">Driver</option>';
								if(!user_has_role($user_id,'Franchisee')) echo '<option value="Franchisee">Franchisee</option>';
								if(!user_has_role($user_id,'Supporter')) echo '<option value="Supporter">Supporter</option>';
								if(!user_has_role($user_id,'CareFacilityAdmin')) echo '<option value="CareFacilityAdmin">Care Facility Admin</option>';
								if(!user_has_role($user_id,'LargeFacilityAdmin')) echo '<option value="LargeFacilityAdmin">Large Facility Admin</option>';
								if(!user_has_role($user_id,'FullAdmin') && current_user_has_role(1, "FullAdmin")) echo '<option value="FullAdmin">Full Admin</option>'; ?>
						</select>
						<div id=ReadOnly style='display: none;'><input type=checkbox name=ReadOnly> readonly</div>
					</td>
                <?php if(current_user_has_role($franchise, "FullAdmin")){ ?>
                </tr>
                <tr>
                	<td></td>
                	<td>
                    	<select name="Franchise">
                        	<?php $franchises = get_user_franchises(get_current_user_id());
								foreach($franchises as $franchise){
									echo "<option value=\"{$franchise['FranchiseID']}\"";
										if($franchise['FranchiseID'] == get_current_user_franchise())
											echo " SELECTED";
									echo ">{$franchise['FranchiseName']}</option>";
								}
							?>
                        </select>
                    </td>
                 <?php } 
                 ?>
                
					<td>
						<input type="submit" value="Add">
					</td>
				</tr>
			</table>
		</form>
		<br>
		<table style="margin:auto;">
			<tr>
				<td class="alignright">Current Roles:</td>
				<td>
					<?php
						$roles = get_user_roles($user_id);
						if($roles){
							$numRoles = count($roles);
							
							foreach( $roles as $row){
								echo $row['Role'];

								if($numRoles > 1 && !(($row['Role'] == "FullAdmin") && !current_user_has_role(1, "FullAdmin")))
									echo '[ <a href="' . site_url() . 'edit_user.php?field=removerole&role=' . $row['Role'] . $edit_url . '">Remove</a> ]' . '<br>';
								else
									echo '<br>';
							}
							
						}
					?>
				</td>				
			</tr>
		</table>
		<?php
	}
	else if(isset($_GET['field']) && isset($_GET['role']) && $_GET['field'] == "removerole" && (current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, "Franchisee")))
	{
		$query = "DELETE FROM `user_role` WHERE `UserID` = '" . mysql_real_escape_string($user_id) . "' AND `Role` = '" . mysql_real_escape_string($_GET['role']) . "' LIMIT 1;";
		mysql_query($query) or die(mysql_error());

        $safe_user_id = mysql_real_escape_string( $user_id );
		if($_GET['role'] == 'Rider'){
			$sql = "UPDATE `rider` SET `RiderStatus` = 'Inactive' WHERE `UserID` = $safe_user_id";
			mysql_query($sql);
		} else if($_GET['role'] == 'Driver'){
			$sql = "UPDATE `driver` SET `DriverStatus` = 'Inactive' WHERE `UserID` = $safe_user_id";
			mysql_query($sql);
		} else if($_GET['role'] == 'CareFacilityAdmin'){
			$sql = "DELETE FROM `care_facility_user` WHERE `UserID` = $safe_user_id";
			mysql_query($sql);
		}
		redirect();
	}
	else if(isset($_GET['field']) && $_GET['field'] == "createrideremergencycontact")
	{
		$rider_info = get_user_rider_info($user_id);
		if($rider_info['EmergencyContactID'] != NULL){
			redirect();
		} else if(isset($_POST['Title'])){
			$required_fields = array('FirstName','LastName','Address1','PhoneNumber','EmergencyContactRelationship');
			$required_filled = true;
			
			foreach($required_fields as $k => $v){
				if($_POST[$v] == '')
					$required_filled = false;
			}
			
			if(!$required_filled){
				$error = 'All required fields were not filled.';
			} else {
				if (createEmergencyContact($user_id, $_POST)) {
				
				} else {
				  die('could not create emergency contact');
				}
				redirect('/account.php');
			}
		}
		include_once 'include/header.php';
		
		?>
		<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?field=createrideremergencycontact' . $edit_url; ?>">
			<?php echo '<center>' . $error . '</center>'; ?>
			<table style="margin:auto;">
				<tr>
					<td colspan="2">
						<br><center><b>Emergency Contact</b></center>
						<br>
						<table>
							<tr>
								<td><b>Contact Name:</b></td>
							</tr>
							<tr>
								<td class="alignright">Title</td>
								<td><input type="text" name="Title" maxlength="10" value="<?php echo $_POST['Title']; ?>" style="width:50px;" /></td>
							</tr>
							<tr>
								<td class="alignright">*First Name</td>
								<td><input type="text" name="FirstName" maxlength="30" value="<?php echo $_POST['FirstName']; ?>" style="width:200px;" /></td>
							</tr>
							<tr>
								<td class="alignright">Middle Initial</td>
								<td><input type="text" name="MiddleInitial" maxlength="1" value="<?php echo $_POST['MiddleInitial']; ?>" style="width:50px;" /></td>
							</tr>
							<tr>
								<td class="alignright">*Last Name</td>
								<td><input type="text" name="LastName" maxlength="30" value="<?php echo $_POST['LastName']; ?>" style="width:200px;" /></td>
							</tr>
							<tr>
								<td class="alignright">Suffix</td>
								<td><input type="text" name="Suffix" maxlength="10" value="<?php echo $_POST['Suffix']; ?>" style="width:50px;" /></td>
							</tr>
						</table>
						<b>Contacts Address:</b>
						<?php
							create_html_address_table('', $_POST);
						?>
						<b>Contacts Phone Number:</b>
						<table style="margin:auto;" id="phone1">
						<tr>
							<td>*Phone Number Type:</td>
							<td>
								<select name="PhoneType[0]">
									<option <?php if($_POST['PhoneType'] == "HOME") echo 'SELECTED'; ?> value="HOME">Home</option>
									<option value="MOBILE" <?php if($_POST['PhoneType'] == "MOBILE") echo 'SELECTED'; ?>>Mobile</option>
									<option value="WORK" <?php if($_POST['PhoneType'] == "WORK") echo 'SELECTED'; ?>>Work</option>
									<option value="UNKNOWN" <?php if($_POST['PhoneType'] == "UNKNOWN") echo 'SELECTED'; ?>>Unknown</option>
									<option value="OTHER" <?php if($_POST['PhoneType'] == "OTHER") echo 'SELECTED'; ?>>Other</option>
								</select>
							</td>
						</tr>
						<tr valign=top>
							<td>*Phone Number</td>
							<td>
								<input type="text" name="PhoneNumber[0]" value="<?php echo $_POST['PhoneNumber'][0]; ?>" maxlength="15" style="width:120px"/>
								 x<input type="text" name="Ext[0]" value="<?php echo $_POST['Ext'][0]; ?>" maxlength="5" style="width:33px"/><br>
								 Name: <input type="text" name="PhoneDescription[0]" value="<?php echo $_POST['PhoneDescription']; ?>" style="width:120px;"/></td>
						</tr>
						</table>
						<?php 
						  echo get_HTML_add_phone_Number_button();
						?>
						<b>Contacts Email:</b>
						<table style="margin:auto;">
							<tr>
								<td>Email:</td>
								<td><input type="text" name="Email" value="<?php echo $_POST['Email']; ?>" maxlength="60" /></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td class="alignright">*Contact Relation</td>
					<td><input type="text" name="EmergencyContactRelationship" value="<?php echo $_POST['EmergencyContactRelationship']; ?>" style="width:200px;" maxlength="25" /></td>
				</tr>
				<tr>
					<td class="alignright" colspan="2"><input type="submit" name="save" value="Save" /></td>
				</tr>
			</table>
		</form>
		<?php
	}
	else if(isset($_GET['field']) && $_GET['field'] == "createdriveremergencycontact")
	{
		$driver_info = get_user_driver_info($user_id);
		if($driver_info['EmergencyContactID'] != NULL){
			redirect();
		} else if(isset($_POST['Title'])){
			$required_fields = array('FirstName','LastName','Address1','PhoneNumber','EmergencyContactRelationship');
			$required_filled = true;
			
			foreach($required_fields as $k => $v){
				if($_POST[$v] == '')
					$required_filled = false;
			}
			
			if(!$required_filled){
				$error = 'All required fields were not filled.';
			} else {
				$contact_name = add_person_name($_POST['Title'],$_POST['FirstName'],$_POST['MiddleInitial'],$_POST['LastName'],$_POST['Suffix']);
				
				$address = array('Address1' => $_POST['Address1'],
								 'Address2' => $_POST['Address2'],
								 'City' => $_POST['City'],
								 'State' => $_POST['State'],
								 'ZIP5' => $_POST['Zip5'],
								 'ZIP4' => $_POST['Zip4']);
				$contact_address = add_address($address);
				
				$contact_phone = add_phone_number($_POST['PhoneNumber'][0],$_POST['PhoneType'][0]);
				
				$contact_email = ($_POST['Email'] != '') ? add_email_address($_POST['Email']) : 'NULL';
				
				$query = "INSERT INTO `emergency_contact` (`EmergencyContactID`, `EmergencyContactName`, `Address`, `Phone`, `Email`) VALUES (NULL, '$contact_name', '$contact_address', '$contact_phone', $contact_email);";
				mysql_query($query) or die(mysql_error());
				$contact_id = mysql_insert_id();
				
				$safe_driver_user_id = mysql_real_escape_string($user_id);
				$safe_relationship = mysql_real_escape_string($_POST['EmergencyContactRelationship']);
				$query ="UPDATE `driver` SET `EmergencyContactID` = $contact_id, `EmergencyContactRelationship` = '$safe_relationship' WHERE `UserID` =$safe_driver_user_id LIMIT 1 ;";
				mysql_query($query) or die(mysql_error());
				
				$keys = array_keys($_POST['PhoneNumber']);
				for($i = 0; $i < count($keys); $i++){
				    if($keys[$i] < 0 && $_POST['PhoneNumber'][ $keys[$i] ] != ''){
				        $phone_id = add_phone_number($_POST['PhoneNumber'][ $keys[$i] ],$_POST['PhoneType'][ $keys[$i] ],'N','',$_POST['Ext'][ $keys[$i] ],'FIRST',$_POST['PhoneDescription'][ $keys[$i] ]);
				        link_phone_number_to_emergency_contact($contact_id, $phone_id);
				    }
				}
				notify_emergency_contact($user_id, $contact_id);
				redirect();
			}
		}
		include_once 'include/header.php';
		
		?>
		<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?field=createdriveremergencycontact' . $edit_url; ?>">
			<?php echo '<center>' . $error . '</center>'; ?>
			<table style="margin:auto;">
				<tr>
					<td colspan="2">
						<br><center><b>Emergency Contact</b></center>
						<br>
						<table>
							<tr>
								<td><b>Contact Name:</b></td>
							</tr>
							<tr>
								<td class="alignright">Title</td>
								<td><input type="text" name="Title" maxlength="10" value="<?php echo $_POST['Title']; ?>" style="width:50px;" /></td>
							</tr>
							<tr>
								<td class="alignright">*First Name</td>
								<td><input type="text" name="FirstName" maxlength="30" value="<?php echo $_POST['FirstName']; ?>" style="width:200px;" /></td>
							</tr>
							<tr>
								<td class="alignright">Middle Initial</td>
								<td><input type="text" name="MiddleInitial" maxlength="1" value="<?php echo $_POST['MiddleInitial']; ?>" style="width:50px;" /></td>
							</tr>
							<tr>
								<td class="alignright">*Last Name</td>
								<td><input type="text" name="LastName" maxlength="30" value="<?php echo $_POST['LastName']; ?>" style="width:200px;" /></td>
							</tr>
							<tr>
								<td class="alignright">Suffix</td>
								<td><input type="text" name="Suffix" maxlength="10" value="<?php echo $_POST['Suffix']; ?>" style="width:50px;" /></td>
							</tr>
						</table>
						<b>Contacts Address:</b>
						<?php
							create_html_address_table('',$_POST);
						?>
						<b>Contacts Phone Number:</b>
						<table style="margin:auto;">
						<tr>
							<td>*Phone Number Type:</td>
							<td>
								<select name="PhoneType[0]">
									<option <?php if($_POST['PhoneType'] == "HOME") echo 'SELECTED'; ?> value="HOME">Home</option>
									<option value="MOBILE" <?php if($_POST['PhoneType'] == "MOBILE") echo 'SELECTED'; ?>>Mobile</option>
									<option value="WORK" <?php if($_POST['PhoneType'] == "WORK") echo 'SELECTED'; ?>>Work</option>
									<option value="UNKNOWN" <?php if($_POST['PhoneType'] == "UNKNOWN") echo 'SELECTED'; ?>>Unknown</option>
									<option value="OTHER" <?php if($_POST['PhoneType'] == "OTHER") echo 'SELECTED'; ?>>Other</option>
								</select>
							</td>
						</tr>
						<tr valign=top>
							<td>*Phone Number</td>
							<td>
								<input type="text" name="PhoneNumber[0]" value="<?php echo $_POST['PhoneNumber']; ?>" maxlength="20" style="vertical-align: bottom;" style="width:120px;"/> x<input type="text" name="Ext[0]" value="<?php echo $_POST['Ext']; ?>" maxlength="20" style="vertical-align: bottom; width:33px;"/><br>
								Name: <input type="text" name="PhoneDescription[0]" value="<?php echo $_POST['PhoneDescription']; ?>" style="width:120px;"/></td>
						</tr>
						</table>
						<?php echo get_HTML_add_phone_Number_button(); ?>
						<b>Contacts Email:</b>
						<table style="margin:auto;">
							<tr>
								<td>Email:</td>
								<td><input type="text" name="Email" value="<?php echo $_POST['Email']; ?>" maxlength="60" /></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td class="alignright">*Contact Relation</td>
					<td><input type="text" name="EmergencyContactRelationship" value="<?php echo $_POST['EmergencyContactRelationship']; ?>" style="width:200px;" maxlength="25" /></td>
				</tr>
				<tr>
					<td class="alignright" colspan="2"><input type="submit" name="save" value="Save" /></td>
				</tr>
			</table>
		</form>
		<?php
	}
	else if(isset($_GET['field']) && $_GET['field'] == "driveremergencycontact")
	{
		if(!get_driver_emergency_contact(get_user_driver_id($user_id))){
			redirect();
		} else if(isset($_POST['Title'])){
			$required_fields = array('FirstName','LastName','Address1','PhoneNumber','EmergencyContactRelationship');
			$required_filled = true;
			
			foreach($required_fields as $k => $v){
				if($_POST[$v] == '')
					$required_filled = false;
			}
			
			if(!$required_filled){
				$error = 'All required name fields were not filled.';
			} else {
				$emergency_contact = get_driver_emergency_contact(get_user_driver_id($user_id));
				
				$contact_name = update_person_name($emergency_contact['EmergencyContactName'], $_POST['Title'],$_POST['FirstName'],$_POST['MiddleInitial'],$_POST['LastName'],$_POST['Suffix']);
				
				$address = array('Address1' => $_POST['Address1'],
								 'Address2' => $_POST['Address2'],
								 'City' => $_POST['City'],
								 'State' => $_POST['State'],
								 'ZIP5' => $_POST['Zip5'],
								 'ZIP4' => $_POST['Zip4']);
				$contact_address = update_address($emergency_contact['Address'],$address);
				if(count($_POST['PhoneNumber']) > 0) {
				   
					foreach($_POST['PhoneNumber'] as $key=>$value){
							//echo $key."<BR>";
					    if($key > 0){
					        if($value == ''){
					            remove_phone_number_for_emergency_contact($emergency_contact['EmergencyContactID'], $key);
					            delete_phone_number($key);
					        } else
							    
					            update_phone_number($key,$_POST['PhoneNumber'][ $key ],$_POST['PhoneType'][ $key ],'N','',$_POST['Ext'][ $key ],'FIRST',$_POST['PhoneDescription'][ $key ]);
					    } else if($key <= 0 && $_POST['PhoneNumber'][ $key ] != ''){
					    			//echo "Adding Phone Number ".$_POST['PhoneNumber'][ $key ]."<BR>";
						        $phone_id = add_phone_number($_POST['PhoneNumber'][ $key ],$_POST['PhoneType'][ $key ],'N','',$_POST['Ext'][ $key ],'FIRST',$_POST['PhoneDescription'][ $key ]);
						        link_phone_number_to_emergency_contact($emergency_contact['EmergencyContactID'], $phone_id);
							} else {
								$phone_id = update_phone_number($key,$_POST['PhoneNumber'][ $key ],$_POST['PhoneType'][ $key ],'N','',$_POST['Ext'][ $key ],'FIRST',$_POST['PhoneDescription'][ $key ]);
								link_phone_number_to_emergency_contact($emergency_contact['EmergencyContactID'], $phone_id);
							}
					}        
		    } else {
		        if($_POST['PhoneNumber'][ 0 ] == ''){
		            remove_phone_number_for_emergency_contact($emergency_contact['EmergencyContactID'], $emergency_contact['Phone']);
		            delete_phone_number($emergency_contact['Phone']);
		        } else
		            $contact_phone = update_phone_number($emergency_contact['Phone'],$_POST['PhoneNumber'][0],$_POST['PhoneType'][0],'N','',$_POST['Ext'][0],'FIRST',$_POST['PhoneDescription'][0]);
		    }

//exit;
				
				if($emergency_contact['Email'] == NULL){
					$contact_email = ($_POST['Email'] != '') ? add_email_address($_POST['Email']) : 'NULL';
					$sql = "UPDATE `emergency_contact` SET `Email` = $contact_email WHERE `EmergencyContactID` ={$emergency_contact['EmergencyContactID']} LIMIT 1 ;";
					mysql_query($sql);
				} else{
					$contact_email = update_email_address($emergency_contact['Email'], $_POST['Email']);
				}
				
				$safe_driver_user_id = mysql_real_escape_string($user_id);
				$safe_relationship = mysql_real_escape_string($_POST['EmergencyContactRelationship']);
				$query ="UPDATE `driver` SET `EmergencyContactRelationship` = '$safe_relationship'WHERE `UserID` =$safe_driver_user_id LIMIT 1 ;";
				mysql_query($query) or die(mysql_error());
				
				redirect();
			}
		}
		include_once 'include/header.php';
		
		$emergency_contact = get_driver_emergency_contact(get_user_driver_id($user_id));
		$contact_name = get_name($emergency_contact['EmergencyContactName']);
		$contact_address = get_address($emergency_contact['Address']);
		$contact_email = get_email_address($emergency_contact['Email']);
		$contact_phone = get_phone_number($emergency_contact['Phone']);
		$contact_secondary_phone = get_emergency_contact_secondary_phones($emergency_contact['EmergencyContactID']);
		?>
		<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?field=driveremergencycontact' . $edit_url; ?>">
			<?php echo '<center>' . $error . '</center>'; ?>
			<table style="margin:auto;">
				<tr>
					<td colspan="2">
						<br><center><b>Emergency Contact</b></center>
						<br>
						<table>
							<tr>
								<td><b>Contact Name:</b></td>
							</tr>
							<tr>
								<td class="alignright">Title</td>
								<td><input type="text" name="Title" maxlength="10" value="<?php echo $contact_name['Title']; ?>" style="width:50px;" /></td>
							</tr>
							<tr>
								<td class="alignright">First Name</td>
								<td><input type="text" name="FirstName" maxlength="30" value="<?php echo $contact_name['FirstName']; ?>" style="width:200px;" /></td>
							</tr>
							<tr>
								<td class="alignright">Middle Initial</td>
								<td><input type="text" name="MiddleInitial" maxlength="1" value="<?php echo $contact_name['MiddleInitial']; ?>" style="width:50px;" /></td>
							</tr>
							<tr>
								<td class="alignright">Last Name</td>
								<td><input type="text" name="LastName" maxlength="30" value="<?php echo $contact_name['LastName']; ?>" style="width:200px;" /></td>
							</tr>
							<tr>
								<td class="alignright">Suffix</td>
								<td><input type="text" name="Suffix" maxlength="10" value="<?php echo $contact_name['Suffix']; ?>" style="width:50px;" /></td>
							</tr>
						</table>
						<b>Contacts Address:</b>
						<?php
							create_html_address_table(NULL,$contact_address);
						?>
						<b>Contacts Phone Number:</b>
                        <?php //print_r($emergency_contact); ?>
						<?php if($emergency_contact['Phone'] !== null){ ?>
						<table style="margin:auto;">
						<tr>
							<td>Phone Number Type:</td>
							<td>
								<select name="PhoneType[<?php echo $emergency_contact['Phone']; ?>]">
									<option <?php if($contact_phone['PhoneType'] == "HOME") echo 'SELECTED'; ?> value="HOME">Home</option>
									<option value="MOBILE" <?php if($contact_phone['PhoneType'] == "MOBILE") echo 'SELECTED'; ?>>Mobile</option>
									<option value="WORK" <?php if($contact_phone['PhoneType'] == "WORK") echo 'SELECTED'; ?>>Work</option>
									<option value="UNKNOWN" <?php if($contact_phone['PhoneType'] == "UNKNOWN") echo 'SELECTED'; ?>>Unknown</option>
									<option value="OTHER" <?php if($contact_phone['PhoneType'] == "OTHER") echo 'SELECTED'; ?>>Other</option>
								</select>
							</td>
						</tr>
						<tr valign=top>
							<td>Phone Number</td>
							<td>
								<input type="text" name="PhoneNumber[<?php echo $emergency_contact['Phone']; ?>]" value="<?php echo $contact_phone['PhoneNumber']; ?>" maxlength="20" style="width: 120px;"/> x<input type="text" name="Ext[<?php echo $emergency_contact['Phone']; ?>]" value="<?php echo $contact_phone['Ext']; ?>" maxlength="20" style="vertical-align: bottom; width: 33px;"/><br>
								Name: <input type="text" name="PhoneDescription[<?php echo $emergency_contact['Phone']; ?>]" value="<?php echo $contact_phone['phonedescription']; ?>" style="width: 120px;"/></td>
						</tr>
						</table>
						<?php } ?>
						<?php 
							foreach($contact_secondary_phone as $phone)
							//print_r($phone);
								echo get_HTML_phone_number_input($phone);
						 echo get_HTML_add_phone_Number_button($emergency_contact['Phone'] === null); ?>
						<b>Contacts Email:</b>
						<table style="margin:auto;">
							<tr>
								<td>Email:</td>
								<td><input type="text" name="Email" value="<?php echo $contact_email['EmailAddress']; ?>" maxlength="60" /></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td class="alignright">Contact Relation</td>
					<td><input type="text" name="EmergencyContactRelationship" value="<?php echo $emergency_contact['EmergencyContactRelationship']; ?>" style="width:200px;" maxlength="25" /></td>
				</tr>
				<tr>
					<td class="alignright" colspan="2"><input type="submit" name="save" value="Save" /></td>
				</tr>
			</table>
		</form>
		<?php
	}
	else if(isset($_GET['field']) && $_GET['field'] == "rideremergencycontact")
	{
		if(!get_rider_emergency_contact(get_user_rider_id($user_id))){
			redirect();
		} else if(isset($_POST['Title'])){
			$required_fields = array('FirstName','LastName','Address1','EmergencyContactRelationship');
			$required_filled = true;

			foreach($required_fields as $k => $v){
				if($_POST[$v] == '')
					$required_filled = false;
			}
			if(count($_POST['PhoneNumber']) == 0) $required_filled = false;
			else {
				$keys = array_keys($_POST['PhoneNumber']);
				if($_POST['PhoneNumber'][$keys[0]] == '') $required_filled = false;
			}
			
			if(!$required_filled){
				$error = 'All required name fields were not filled.';
			} else {
				$emergency_contact = get_rider_emergency_contact(get_user_rider_id($user_id));
				
				$contact_name = update_person_name($emergency_contact['EmergencyContactName'], $_POST['Title'],$_POST['FirstName'],$_POST['MiddleInitial'],$_POST['LastName'],$_POST['Suffix']);
				
				$address = array('Address1' => $_POST['Address1'],
								 'Address2' => $_POST['Address2'],
								 'City' => $_POST['City'],
								 'State' => $_POST['State'],
								 'ZIP5' => $_POST['Zip5'],
								 'ZIP4' => $_POST['Zip4']);
				$contact_address = update_address($emergency_contact['Address'],$address);
				
				
				//$contact_phone = update_phone_number($emergency_contact['Phone'],$_POST['PhoneNumber'],$_POST['PhoneType']);
				$keys = array_keys($_POST['PhoneNumber']);
				for($i = 0; $i < count($keys); $i++){
				    if($keys[$i] > 0){
				        if($_POST['PhoneNumber'][ $keys[$i] ] == ''){
				            remove_phone_number_for_emergency_contact($emergency_contact['EmergencyContactID'], $keys[$i]);
				            delete_phone_number($keys[$i]);
				        } else
				            update_phone_number($keys[$i],$_POST['PhoneNumber'][ $keys[$i] ],$_POST['PhoneType'][ $keys[$i] ],'N','',$_POST['Ext'][ $keys[$i] ],'FIRST',$_POST['PhoneDescription'][ $keys[$i] ]);
				    } else if($keys[$i] < 0 && $_POST['PhoneNumber'][ $keys[$i] ] != ''){
				        $phone_id = add_phone_number($_POST['PhoneNumber'][ $keys[$i] ],$_POST['PhoneType'][ $keys[$i] ],'N','',$_POST['Ext'][ $keys[$i] ],'FIRST',$_POST['PhoneDescription'][ $keys[$i] ]);
				        link_phone_number_to_emergency_contact($emergency_contact['EmergencyContactID'], $phone_id);
				    } else {
				        if($_POST['PhoneNumber'][ $keys[$i] ] == ''){
				            remove_phone_number_for_emergency_contact($emergency_contact['EmergencyContactID'], $emergency_contact['Phone']);
				            delete_phone_number($keys[$i]);
				        } else
				            $contact_phone = update_phone_number($keys[$i],$_POST['PhoneNumber'][0],$_POST['PhoneType'][0],'N','',$_POST['Ext'][0],'FIRST',$_POST['PhoneDescription'][0]);
				    }
				}
				
				if($emergency_contact['Email'] == NULL){
					$contact_email = ($_POST['Email'] != '') ? add_email_address($_POST['Email']) : 'NULL';
					$sql = "UPDATE `emergency_contact` SET `Email` = $contact_email WHERE `EmergencyContactID` ={$emergency_contact['EmergencyContactID']} LIMIT 1 ;";
					mysql_query($sql);
				} else{
					$contact_email = update_email_address($emergency_contact['Email'], $_POST['Email']);
				}
				
				
				$safe_user_id = mysql_real_escape_string($user_id);
				$safe_relationship = mysql_real_escape_string($_POST['EmergencyContactRelationship']);
				$query ="UPDATE `rider` SET `EmergencyContactRelationship` = '$safe_relationship' WHERE `UserID` = $safe_user_id";
				mysql_query($query) or die(mysql_error());
				
				redirect();
			}
		}
		include_once 'include/header.php';
		
		$emergency_contact = get_rider_emergency_contact(get_user_rider_id($user_id));
		$contact_name = get_name($emergency_contact['EmergencyContactName']);
		$contact_address = get_address($emergency_contact['Address']);
		$contact_email = get_email_address($emergency_contact['Email']);
		$contact_phone = get_phone_number($emergency_contact['Phone']);
		$contact_secondary_phone = get_emergency_contact_secondary_phones($emergency_contact['EmergencyContactID']);
		?>
		<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?field=rideremergencycontact' . $edit_url; ?>">
			<?php echo '<center>' . $error . '</center>'; ?>
			<table style="margin:auto;">
				<tr>
					<td colspan="2">
						<br><center><b>Emergency Contact</b></center>
						<br>
						<table>
							<tr>
								<td><b>Contact Name:</b></td>
							</tr>
							<tr>
								<td class="alignright">Title</td>
								<td><input type="text" name="Title" maxlength="10" value="<?php echo $contact_name['Title']; ?>" style="width:50px;" /></td>
							</tr>
							<tr>
								<td class="alignright">First Name</td>
								<td><input type="text" name="FirstName" maxlength="30" value="<?php echo $contact_name['FirstName']; ?>" style="width:200px;" /></td>
							</tr>
							<tr>
								<td class="alignright">Middle Initial</td>
								<td><input type="text" name="MiddleInitial" maxlength="1" value="<?php echo $contact_name['MiddleInitial']; ?>" style="width:50px;" /></td>
							</tr>
							<tr>
								<td class="alignright">Last Name</td>
								<td><input type="text" name="LastName" maxlength="30" value="<?php echo $contact_name['LastName']; ?>" style="width:200px;" /></td>
							</tr>
							<tr>
								<td class="alignright">Suffix</td>
								<td><input type="text" name="Suffix" maxlength="10" value="<?php echo $contact_name['Suffix']; ?>" style="width:50px;" /></td>
							</tr>
						</table>
						<b>Contacts Address:</b>
						<?php
							create_html_address_table(NULL, $contact_address);
						?>
						<b>Contacts Phone Number:</b>
						<?php if($emergency_contact['Phone'] !== null){ ?>
						<table style="margin:auto;">
						<tr>
							<td>Phone Number Type:</td>
							<td>
								<select name="PhoneType[<?php echo $emergency_contact['Phone']; ?>]">
									<option <?php if($contact_phone['PhoneType'] == "HOME") echo 'SELECTED'; ?> value="HOME">Home</option>
									<option value="MOBILE" <?php if($contact_phone['PhoneType'] == "MOBILE") echo 'SELECTED'; ?>>Mobile</option>
									<option value="WORK" <?php if($contact_phone['PhoneType'] == "WORK") echo 'SELECTED'; ?>>Work</option>
									<option value="UNKNOWN" <?php if($contact_phone['PhoneType'] == "UNKNOWN") echo 'SELECTED'; ?>>Unknown</option>
									<option value="OTHER" <?php if($contact_phone['PhoneType'] == "OTHER") echo 'SELECTED'; ?>>Other</option>
								</select>
							</td>
						</tr>
						<tr valign=top>
							<td>Phone Number</td>
							<td>
								<input type="text" name="PhoneNumber[<?php echo $emergency_contact['Phone']; ?>]" value="<?php echo $contact_phone['PhoneNumber']; ?>" maxlength="15" style="width:120px"/>
								 x<input type="text" name="Ext[<?php echo $emergency_contact['Phone']; ?>]" value="<?php echo $contact_phone['Ext']; ?>" maxlength="20" style="vertical-align: bottom; width:33px;"/><br>
								 Name: <input type="text" name="PhoneDescription[<?php echo $emergency_contact['Phone']; ?>]" value="<?php echo $contact_phone['phonedescription']; ?>" style="width: 120px;"/></td>
						</tr>
						</table>
						<?php } ?>
						<?php 
							foreach($contact_secondary_phone as $phone)
								echo get_HTML_phone_number_input($phone);
				            echo get_HTML_add_phone_Number_button(); ?>
						<b>Contacts Email:</b>
						<table style="margin:auto;">
							<tr>
								<td>Email:</td>
								<td><input type="text" name="Email" value="<?php echo $contact_email['EmailAddress']; ?>" maxlength="60" /></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td class="alignright">Contact Relation</td>
					<td><input type="text" name="EmergencyContactRelationship" value="<?php echo $emergency_contact['EmergencyContactRelationship']; ?>" style="width:200px;" maxlength="25" /></td>
				</tr>
				<tr>
					<td class="alignright" colspan="2"><input type="submit" name="save" value="Save" /></td>
				</tr>
			</table>
		</form>
		<?php
	}
	else if(isset($_GET['field']) && $_GET['field'] == "createridersurvey")
	{
		if(get_rider_survey(get_user_rider_id($user_id))){
			redirect();
		} else if(isset($_POST['FormSubmitted'])){
			$required_fields = array('MaritalStatus', 'LivingSituation', 'Housing');
			$required_filled = true;
		
			foreach($required_fields as $k => $v){
				if($_POST[$v] == '')
					$required_filled = false;
			}
			
			if(!$required_filled){
				$error = 'All required name fields were not filled.';
			} else {
				$survey = array('DriveOwnCar','CityBus','Taxi',
								'Walk','FamilyOrFriend','RotaryMember','KiwanisMember','LionsMember',
								'ElksMember','EaglesMember','AAAMember','AARPMember','FratSororityMember','KofCMember',
								'MasonsMember','OthersDriveAlways','OthersDriveAtNight','OthersDriveHighTraffic',
								'OthersDriveUnfamiliar','OthersDriveHighway','OthersDriveBadWeather','OtherTransport',
								'OtherMembership','MaritalStatus','LivingSituation','Housing');
				$query = "INSERT INTO `rider_survey` (`UserID`,";
				for($i = 0; $i < count($survey); $i++){
					$query .= " `" . $survey[$i] . "`";
					if((count($survey) - $i) != 1)
						$query .= ",";
				}
				$query .= ") VALUES (" . mysql_real_escape_string($user_id) . ",";
				for($i = 0; $i < count($survey) - 5; $i++){
					$query .= " '" . ( isset($_POST[$survey[$i]]) ? 'Yes' : 'No' ) . "',";
				}
				$query .= "'" . mysql_real_escape_string($_POST['OtherTransport']) . "', '" . mysql_real_escape_string($_POST['OtherMembership']) . "','" . mysql_real_escape_string($_POST['MaritalStatus']) . "','" . mysql_real_escape_string($_POST['LivingSituation']) . "','" . mysql_real_escape_string($_POST['Housing']) . "'";
				$query .= ");";
				mysql_query($query) or die(mysql_error());
				
				redirect();
			}
		}
		include_once 'include/header.php';
		
		?>
			<center><h2>Rider Survey</h2></center>
			<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?field=createridersurvey' . $edit_url; ?>">
				<?php echo '<center>' . $error . '</center>'; ?>
				<input type="hidden" name="FormSubmitted" value="True" />
				<table style="margin:auto; clear:both;">
					<tr>
						<td>
							<span style="font-size:1.2em;">Marital status:</span><br>
							<ul>
								<input type="radio" name="MaritalStatus" value="Married"> Married<br>
								<input type="radio" name="MaritalStatus" value="Widowed"> Widowed<br>
								<input type="radio" name="MaritalStatus" value="Divorced"> Divorced<br>
								<input type="radio" name="MaritalStatus" value="Significant Other"> Significant Other<br>
								<input type="radio" name="MaritalStatus" value="Single"> Single<br>
							</ul>
						</td>
						<td>
							<span style="font-size:1.2em;">I live:</span>
							<ul>
								<input type="radio" name="LivingSituation" value="Alone"> Alone<br>
								<input type="radio" name="LivingSituation" value="With Spouse"> With Spouse<br>
								<input type="radio" name="LivingSituation" value="With Children"> With Children<br>
								<input type="radio" name="LivingSituation" value="With Friend"> With Friend<br>
								<input type="radio" name="LivingSituation" value="Other"> Other<br>
							</ul>
						</td>
						<td>
							<span style="font-size:1.2em;">My housing is:</span>
							<ul>
								<input type="radio" name="Housing" value="Private Home"> Private Home<br>
								<input type="radio" name="Housing" value="Apartment"> Apartment<br>
								<input type="radio" name="Housing" value="Independent Living"> Independent Living<br>
								<input type="radio" name="Housing" value="Assisted Living"> Assisted Living<br>
								<input type="radio" name="Housing" value="Other"> Other<br>
							</ul>
						</td>
					</tr>
					<tr>
						<td>
							<span style="font-size:1.2em;">My means of transport include:</span><br>
							<ul>
								<input type="checkbox" name="DriveOwnCar" />
								Drive And/Or Own A Car<br>
								<input type="checkbox" name="CityBus" />
								Ride The City Bus<br>
								<input type="checkbox" name="Taxi" />
								Take A Taxi<br>
								<input type="checkbox" name="Walk" />
								Walk<br>
								<input type="checkbox" name="FamilyOrFriend" />
								Ride From Friend Or Family<br>
								Other <input type="text" name="OtherTransport" maxlength="50">
							</ul>
						</td>
						<td valign="top">
							<span style="font-size:1.2em;">I am a member of:</span><br>
							<ul>
								<input type="checkbox" name="RotaryMember" />
								Rotary<br>
								<input type="checkbox" name="KiwanisMember" />
								Kiwanis Member<br>
								<input type="checkbox" name="KiwanisMember" />
								Lions Member<br>
								<input type="checkbox" name="ElksMember" />
								Elks Member<br>
								<input type="checkbox" name="EaglesMember" />
								Eagles Member<br>
							</ul>
						</td>
						<td valign="top">
							<ul>
								<input type="checkbox" name="AAAMember" />
								AAA Member<br>
								<input type="checkbox" name="AARPMember" />
								AARP Member<br>
								<input type="checkbox" name="FratSororityMember" />
								Frat Sorority Member<br>
								<input type="checkbox" name="KofCMember" />
								K of C Member<br>
								<input type="checkbox" name="MasonsMember" />
								Masons Member<br>
								Other Memberships <input type="text" name="OtherMembership" maxlength="50">
							</ul>
						</td>
					</tr>
					<tr>
						<td>
							<span style="font-size:1.2em;">I prefer others to drive:</span><br>
							<ul>
								<input type="checkbox" name="OthersDriveAlways" />
								Always<br>
								<input type="checkbox" name="OthersDriveAtNight" />
								At Night<br>
							</ul>
						</td>
						<td>
							<ul>
								<input type="checkbox" name="OthersDriveHighTraffic" />
								High Traffic<br>
								<input type="checkbox" name="OthersDriveUnfamiliar" />
								In Unfamiliar Places<br>
							</ul>
						</td>
						<td>
							<ul>
								<input type="checkbox" name="OthersDriveHighway" />
								On Highway<br>
								<input type="checkbox" name="OthersDriveBadWeather" />
								During Bad Weather
							</ul>
						</td>
					</tr>
					<tr>
						<td class="alignright" colspan="3"><input type="submit" name="save" value="Save" /></td>
					</tr>
				</table>
			</form>
		<?php
	}
	else if(isset($_GET['field']) && $_GET['field'] == "requestpasswordchange")
	{
		if(current_user_has_role(1 , 'FullAdmin') || current_user_has_role($franchise, "Franchisee")){
			$user = get_user_account($user_id);
			$email = get_email_address($user['EmailID']);
			$name = get_name($user['PersonNameID']);
			$name = $name['FirstName'] . ' ' . $name['LastName'];
			$hash = sha1($user['Salt'] . $user['UserName'] . $user['Password']);
			$link = site_url() . "new_password.php?id=$user_id&hash=$hash";
			mail($email['EmailAddress'], 'Password Change Request - Riders Club of America', "Dear $name,\n\nWe have been informed that you need your password reset. Please follow the link below to change your password and log into your account.\n\n$link\n\nRiders Club of America\n\nIf you have further questions about our service, please contact the office at 319.365.1511.  We have staff available Monday through Friday, between 9:00 a.m. and 3:00 p.m.\n\nThank-you", DEFAULT_EMAIL_FROM);
			
			if($user_id == get_current_user_id())
				header("location: " . site_url() . "account.php?requestsent=true");
			else
				header("location: " . site_url() . "account.php?id=" . $user_id . "&requestsent=true");
		} else {
			if($user_id == get_current_user_id())
				header("location: " . site_url() . "account.php?requestsent=true");
			else
				header("location: " . site_url() . "account.php?id=" . $user_id . "&requestsent=true");
		}
	}
	else if(isset($_GET['field']) && $_GET['field'] == "addcarefacility"  && (current_user_has_role(1,'FullAdmin') || current_user_has_role($franchise, "Franchisee")))
	{
		if(isset($_POST['Facility'])){
			connect_user_to_care_facility($user_id,$_POST['Facility']);
			redirect();
		}
		include_once 'include/header.php';
		
		?>
		<center><h2>Add to Care Facility</h2></center>
		<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?field=addcarefacility' . $edit_url; ?>">
			<table style="margin:auto;">
				<tr>
					<td>Care Facility</td>
					<td>
						<select name="Facility">
							<?php
								$sql = "SELECT `CareFacilityID`, `CareFacilityName`,`FacilityAddressID` FROM `care_facility`;";
								$result = mysql_query($sql);
								while($row = mysql_fetch_array($result)){
									echo '<option value="' . $row['CareFacilityID'] . '">' .$row['CareFacilityName'] . ' - ';
									$address = get_address($row['FacilityAddressID']);
									echo $address['City'] . '</option>';
								}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<td class="alignright" colspan="2"><input type="submit" name="save" value="Save" /></td>
				</tr>
			</table>
		</form>
		<?php
    } else if ($_GET['field'] == "connectlargefacility" && (current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, "Franchisee"))) {
        // TODO:  Franchise admin should be able to update at some point
        if(isset($_POST['Facility'])){
            connect_user_to_large_facility($user_id,$_POST['Facility']);
            redirect();
        }
        include_once 'include/header.php';
        
        ?>
        <center><h2>Connect to Large Facility</h2></center>
        <form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?field=connectlargefacility' . $edit_url; ?>">
            <table style="margin:auto;">
                <tr>
                    <td>Large Facility</td>
                    <td><?php
                        $franchise_id = get_current_user_franchise();
                        $facilities = get_large_facilities( $franchise_id ); 
                        if ($facilities) { ?>                        
                        <select name="Facility">
                            <?php
                                foreach ($facilities as $id => $facility) {
                                    echo '<option value="' . $id . '">' . $facility['LargeFacilityName'] . ' - ';
                                    echo $facility['City'] . '</option>';
                                }
                            ?>
                        </select><?php
                        } else {
                            echo "No large facilities in franchise $franchise_id";
                        } ?>
                    </td>
                </tr>
                <tr>
                    <td class="alignright" colspan="2"><input type="submit" name="save" value="Save" /></td>
                </tr>
            </table>
        </form>
        <?php
    } else if(isset($_GET['field']) && $_GET['field'] == "deactivateuser" && (current_user_has_role(1,'FullAdmin') || current_user_has_role($franchise, "Franchisee")) ){
		if(isset($user_id) && isset($_POST['Deactivate']) && $_POST['Deactivate'] == "Deactivate"){
			set_user_inactive( $user_id );
			redirect();
		}
		include_once 'include/header.php';
		
		?>
		<center><h2>Deactivate Account</h2></center>
		<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?field=deactivateuser' . $edit_url; ?>">
			<table style="margin:auto;">
				<tr>
					<td>Are you sure you want to DEACTIVATE this account?</td>
				</tr>
				<tr>
					<td class="alignright"><div style="float:left;"><input type="button" name="save" value="Cancel" /></div><input type="submit" name="Deactivate" value="Deactivate" /></td>
				</tr>
			</table>
		</form>
		<?php
	} else if(isset($_GET['field']) && $_GET['field'] == "notifyemergencycontact" && (current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, "Franchisee"))){
		if (if_user_has_role(get_affected_user_id(), $franchise, 'Driver')) {
			$driver = get_driver_info(get_user_driver_id( get_affected_user_id()));
			$emergency_id = $driver['EmergencyContactID'];
		} else {
		 	$rider = get_user_rider_info( get_affected_user_id());
			$emergency_id = $rider['EmergencyContactID'];
		}
		 notify_emergency_contact($user_id, $emergency_id);
		$emergency_contact = get_emergency_contact($emergency_id);
		$email_address = get_email_address($emergency_contact['Email']);
		include_once 'include/header.php';
		
		?>
		<center><h2>Notify Emergency Contact</h2></center>
		<form method="post" action="account.php?s=0<?php echo $edit_url; ?>">
			<table style="margin:auto;">
				<tr>
					<td>An Email has been sent to <?php echo $email_address['EmailAddress']; ?></td>
				</tr>
				<tr>
					<td class="alignright"><input type="submit" name="Back" value="Back" /></td>
				</tr>
			</table>
		</form>
		<?php
	}  else if(isset($_GET['field']) && $_GET['field'] == "createemail"){
		if(isset($_POST['Email']))
		{
			if($_POST['Email'] == ''){
				$error = "Please fill in your email";
			} else {
				
				$email_id = add_email_address($_POST['Email']);
				
				$sql = "UPDATE `users` SET `EmailID` = $email_id WHERE `UserID` = $user_id LIMIT 1 ;";
				mysql_query($sql) or die(mysql_error());
				
				redirect();
			}
		}
		include_once 'include/header.php';
		
		?>
		<center><h2>Create Email</h2></center>
		<?php echo '<center>' . $error . '</center>'; ?>
		<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?field=createemail' . $edit_url; ?>">
			<table style="margin:auto; width:300px;">
				<tr>
					<td class="alignright">Email</td>
					<td><input type="text" style="width:200px" name="Email" value="<?php echo $_POST['Email']; ?>"></td>
				</tr>
				<tr>
					<td class="alignright" colspan="2"><input type="submit" name="save" value="Save" /></td>
				</tr>
			</table>
		</form>
		<?php
	}  else if(isset($_GET['field']) && $_GET['field'] == "createinsuranceinfo"){
		if(isset($_POST['CompanyName']))
		{
			$required_fields = array('CompanyName', 'PolicyNumber', 'PerPersonLiability','PerAccidentLiability','PropertyDamageLiability','CombinedSingleLimit','ExpirationMonth','ExpirationDay','ExpirationYear');
			$required_filled = true;
		
			foreach($required_fields as $k => $v){
				if($_POST[$v] == '')
					$required_filled = false;
			}
			
			if(!$required_filled){
				$error = 'All required name fields were not filled.';
			} else {
				
				$address =  add_address(array("Address1" => $_POST['Address1'],
													 		  "Address2" => $_POST['Address2'],
													  		  "City" => $_POST['City'],
													 		  "State" => $_POST['State'],
													  		  "ZIP5" => $_POST['Zip5'],
													  		  "ZIP4" => $_POST['Zip4']));
										
				$name = add_person_name($_POST['Title'],$_POST['FirstName'],$_POST['MiddleInitial'],$_POST['LastName'],$_POST['Suffix']);
				
				$phone =  add_phone_number($_POST['AgentPhoneNumber'], 'OTHER', 'N', 0, $_POST['AgentPhoneExt']);
				
				if(current_user_has_role(1, 'FullAdmin')){
					$date = "{$_POST['InsuranceVerifiedYear']}-{$_POST['InsuranceVerifiedMonth']}-{$_POST['InsuranceVerifiedDay']}";
				} else {
					$date = NULL;
				}
				
				create_driver_insurance_info($user_id, $_POST['CompanyName'], $_POST['PolicyNumber'], $_POST['PerPersonLiability'], 
								      $_POST['PerAccidentLiability'], $_POST['PropertyDamageLiability'], $_POST['CombinedSingleLimit'], "{$_POST['ExpirationYear']}-{$_POST['ExpirationMonth']}-{$_POST['ExpirationDay']}", $name, $phone, $address, $_POST['CertificateOfInsuranceOnFile'],  $_POST['CopyOfInsuranceCardOnFile'],$date);
				
				redirect('/account.php');
			}
		}
		include_once 'include/header.php';
		
		$post_date = isset($_POST['ExpirationYear']) ? "{$_POST['ExpirationYear']}-{$_POST['ExpirationMonth']}-{$_POST['ExpirationDay']}" : NULL;
		$verified_date = isset($_POST['InsuranceVerifiedYear']) ? "{$_POST['InsuranceVerifiedYear']}-{$_POST['InsuranceVerifiedMonth']}-{$_POST['InsuranceVerifiedDay']}" : NULL;
		?>
		<center><h2>Create Driver Insurance Information</h2></center>
		<?php echo '<center style="color:red;">' . $error . '</center>'; ?>
		<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?field=createinsuranceinfo' . $edit_url; ?>">
			<table style="margin:auto;">
				<tr>
					<td class="alignright">Company Name</td>
					<td><input type="text" style="width:200px" name="CompanyName" value="<?php echo $_POST['CompanyName']; ?>"></td>
				</tr>
                <tr>
                	<td class="alignright">Agent Name</td>
                </tr>
                <tr>
                	<td colspan="2" style="padding-left:120px;">
                    	<?php 
							$name = array('Title' => $_POST['Title'],
													'FirstName' => $_POST['FirstName'],
													'MiddleInitial' => $_POST['MiddleInitial'],
													'LastName' => $_POST['LastName'],
													'Suffix' => $_POST['Suffix']);
						echo  print_get_name_form_part($name, NULL, FALSE); ?>
                    </td>
                </tr>
                <tr>
                	<td class="alignright">Agent Phone Number</td>
                    <td><input type="text" style="width:151px" name="AgentPhoneNumber" value="<?php echo $_POST['AgentPhoneNumber']; ?>">
                   	 x<input type="text" style="width:33px" name="AgentPhoneExt" value="<?php echo $_POST['AgentPhoneExt']; ?>">
                   </td>
                </tr>
                 <tr>
                	<td class="alignright">Agent Address</td>
                </tr>
                <tr>
                	<td colspan="2">
                    	<?php
							$address = array("Address1" => $_POST['Address1'],
													  "Address2" => $_POST['Address2'],
													  "City" => $_POST['City'],
													  "State" => $_POST['State'],
													  "ZIP5" => $_POST['Zip5'],
													  "ZIP4" => $_POST['Zip4']);
						?>
                    	<?php echo create_html_address_table(NULL, $address); ?>
                    </td>
                </tr>
				<tr>
					<td class="alignright">Policy Number</td>
					<td><input type="text" style="width:200px" name="PolicyNumber" value="<?php echo $_POST['PolicyNumber']; ?>"></td>
				</tr>
				<tr>
					<td class="alignright">Per Person Liability</td>
					<td><input type="text" style="width:200px" name="PerPersonLiability" value="<?php echo $_POST['PerPersonLiability']; ?>"></td>
				</tr>
				<tr>
					<td class="alignright">Per Accident Liability</td>
					<td><input type="text" style="width:200px" name="PerAccidentLiability" value="<?php echo $_POST['PerAccidentLiability']; ?>"></td>
				</tr>
				<tr>
					<td class="alignright">Property Damage Liability</td>
					<td><input type="text" style="width:200px" name="PropertyDamageLiability" value="<?php echo $_POST['PropertyDamageLiability']; ?>"></td>
				</tr>
				<tr>
					<td class="alignright">Combined Single Limit</td>
					<td><input type="text" style="width:200px" name="CombinedSingleLimit" value="<?php echo $_POST['CombinedSingleLimit']; ?>"></td>
				</tr>
				<tr>
					<td class="alignright">Policy Expiration Date</td>
					<td><?php get_date_drop_downs('Expiration',$post_date); ?></td>
				</tr>
                <tr>
					<td class="alignright">Certificate Of Insurance On File</td>
					<td>
                    	<select name="CertificateOfInsuranceOnFile">
                        	<option<?php if($_POST['CertificateOfInsuranceOnFile'] == 'Yes') echo " SELCTED"; ?> value="Yes">Yes</option>
                            <option<?php if($_POST['CertificateOfInsuranceOnFile'] == 'No') echo " SELCTED"; ?> value="No">No</option>
                        </select>
                    </td>
				</tr>
                <tr>
					<td class="alignright">Copy Of Insurance Card On File</td>
					<td>
                    	<select name="CopyOfInsuranceCardOnFile">
                        	<option<?php if($_POST['CopyOfInsuranceCardOnFile'] == 'Yes') echo " SELCTED"; ?> value="Yes">Yes</option>
                            <option<?php if($_POST['CopyOfInsuranceCardOnFile'] == 'No') echo " SELCTED"; ?> value="No">No</option>
                        </select>
                    </td>
				</tr>
				<?php if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, "Franchisee")){ ?>
				<tr>
					<td class="alignright">Copy Of Insurance Verified</td>
					<td><?php get_date_drop_downs('InsuranceVerified',$verified_date); ?></td>
				</tr>
				<?php } ?>
				<tr>
					<td class="alignright" colspan="2"><input type="submit" name="save" value="Save" /></td>
				</tr>
			</table>
		</form>
		<?php
	}  else if(isset($_GET['field']) && $_GET['field'] == "insuranceinfo"){
		if(isset($_POST['CompanyName']))
		{
			$required_fields = array('CompanyName', 'PolicyNumber', 'PerPersonLiability','PerAccidentLiability','PropertyDamageLiability','CombinedSingleLimit','ExpirationMonth','ExpirationDay','ExpirationYear');
			$required_filled = true;
		
			foreach($required_fields as $k => $v){
				if($_POST[$v] == '')
					$required_filled = false;
			}
			
			if(!$required_filled){
				$error = 'All required name fields were not filled.';
			} else {
				$insurance = get_driver_insurance_info( $user_id );
				
				update_person_name( $insurance['AgentNameID'], $_POST['Title'],$_POST['FirstName'],$_POST['MiddleInitial'],$_POST['LastName'],$_POST['Suffix']);
				
				$address =  array("Address1" => $_POST['Address1'],
										   "Address2" => $_POST['Address2'],
										   "City" => $_POST['City'],
										   "State" => $_POST['State'],
										   "ZIP5" => $_POST['Zip5'],
										   "ZIP4" => $_POST['Zip4']);
				update_address($insurance['AgentAddressID'], $address);
				
				update_phone_number($insurance['AgentPhoneID'], $_POST['AgentPhoneNumber'], 'OTHER', 'N', 0, $_POST['AgentPhoneExt']);
				
				if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, "Franchisee"))
					$date = "{$_POST['InsuranceVerifiedYear']}-{$_POST['InsuranceVerifiedMonth']}-{$_POST['InsuranceVerifiedDay']}";
				else
					$date = null;
				
				set_driver_insurance_info($user_id, $_POST['CompanyName'], $_POST['PolicyNumber'], $_POST['PerPersonLiability'], 
								      $_POST['PerAccidentLiability'], $_POST['PropertyDamageLiability'], $_POST['CombinedSingleLimit'], "{$_POST['ExpirationYear']}-{$_POST['ExpirationMonth']}-{$_POST['ExpirationDay']}", $_POST['CertificateOfInsuranceOnFile'], $_POST['CopyOfInsuranceCardOnFile'], $date);
				
				redirect('/account.php');
			}
		}
		include_once 'include/header.php';
		
		$insurance = get_driver_insurance_info( $user_id );
      if ($error != '')
         $insurance = $post;
		?>
		<center><h2>Edit Driver Insurance Information</h2></center>
		<?php echo '<center>' . $error . '</center>'; ?>
		<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?field=insuranceinfo' . $edit_url; ?>">
			<table style="margin:auto;">
				<tr>
					<td class="alignright">Company Name</td>
					<td><input type="text" style="width:200px" name="CompanyName" value="<?php echo $insurance['CompanyName']; ?>"></td>
				</tr>
                <tr>
                	<td class="alignright">Agent Name</td>
                </tr>
                <tr>
                	<td colspan="2" style="padding-left:120px;">
                    	<?php echo  print_get_name_form_part($insurance, NULL, FALSE); ?>
                    </td>
                </tr>
                <tr>
                	<td class="alignright">Agent Phone Number</td>
                    <td><input type="text" style="width:151px" name="AgentPhoneNumber" value="<?php echo $insurance['PhoneNumber']; ?>">
                    	 x<input type="text" style="width:33px" name="AgentPhoneExt" value="<?php echo $insurance['Ext']; ?>">
                    </td>
                </tr>
                 <tr>
                	<td class="alignright">Agent Address</td>
                </tr>
                <tr>
                	<td colspan="2">
                    	<?php echo create_html_address_table('',$insurance); ?>
                    </td>
                </tr>
				<tr>
					<td class="alignright">Policy Number</td>
					<td><input type="text" style="width:200px" name="PolicyNumber" value="<?php echo $insurance['PolicyNumber']; ?>"></td>
				</tr>
				<tr>
					<td class="alignright">Per Person Liability</td>
					<td><input type="text" style="width:200px" name="PerPersonLiability" value="<?php echo $insurance['PerPersonLiability']; ?>"></td>
				</tr>
				<tr>
					<td class="alignright">Per Accident Liability</td>
					<td><input type="text" style="width:200px" name="PerAccidentLiability" value="<?php echo $insurance['PerAccidentLiability']; ?>"></td>
				</tr>
				<tr>
					<td class="alignright">Property Damage Liability</td>
					<td><input type="text" style="width:200px" name="PropertyDamageLiability" value="<?php echo $insurance['PropertyDamageLiability']; ?>"></td>
				</tr>
				<tr>
					<td class="alignright">Combined Single Limit</td>
					<td><input type="text" style="width:200px" name="CombinedSingleLimit" value="<?php echo $insurance['CombinedSingleLimit']; ?>"></td>
				</tr>
				<tr>
					<td class="alignright">Policy Expiration Date</td>
					<td><?php get_date_drop_downs('Expiration',$insurance['PolicyExpirationDate']); ?></td>
				</tr>
                <tr>
					<td class="alignright">Certificate Of Insurance On File</td>
					<td>
                    	<select name="CertificateOfInsuranceOnFile">
                        	<option<?php if($insurance['CertificateOfInsuranceOnFile'] == 'Yes') echo " SELECTED"; ?> value="Yes">Yes</option>
                            <option<?php if($insurance['CertificateOfInsuranceOnFile'] == 'No') echo " SELECTED"; ?> value="No">No</option>
                        </select>
                    </td>
				</tr>
                <tr>
					<td class="alignright">Copy Of Insurance Card On File</td>
					<td>
                    	<select name="CopyOfInsuranceCardOnFile">
                        	<option<?php if($insurance['CopyOfInsuranceCardOnFile'] == 'Yes') echo " SELECTED"; ?> value="Yes">Yes</option>
                            <option<?php if($insurance['CopyOfInsuranceCardOnFile'] == 'No') echo " SELECTED"; ?> value="No">No</option>
                        </select>
                    </td>
				</tr>
				<?php if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, "Franchisee")){ ?>
				<tr>
					<td class="alignright">Copy Of Insurance Verified</td>
					<td><?php get_date_drop_downs('InsuranceVerified',$insurance['InsuranceVerified']); ?></td>
				</tr>
				<?php } ?>
				<tr>
					<td class="alignright" colspan="2"><input type="submit" name="save" value="Save" /></td>
				</tr>
			</table>
		</form>
		<?php
	}  else if(isset($_GET['field']) && $_GET['field'] == "setprimaryphone" && isset($_GET['phoneid']) ){
		set_primary_phone_for_user($user_id, $_GET['phoneid']);
		redirect();
	}else if(isset($_GET['field']) && $_GET['field'] == "background" && current_user_has_role($franchise,'FullAdmin')){
		
		if(isset($_POST['Felony']) && isset($_POST['aliases'])){
			update_user_background($user_id, $_POST['aliases'], $_POST['BackgroundCheck'], $_POST['Felony'], $_POST['FelonyDescription']);
			redirect();
		}
		$alias = get_user_alias($user_id);
		$account = get_user_account($user_id);
		include_once 'include/header.php';
		?>
        <center>
        	<h2>Edit Background Information</h2>
        </center>
        <form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?field=background' . $edit_url; ?>">
        <table style="margin:auto;" width="450px">
        	<tr>
            	<td class="alignright">Aliases</td>
                <td><input type="text" maxlength="50" size="40" name="aliases" value="<?php echo $alias['Alias']; ?>" /></td>
            </tr>
        	<tr>
				<td class="alignright">Felony</td>
				<td>
					<select name="Felony">
						<option<?php if($account['HasFelony'] == 'Yes') echo ' SELECTED'; ?> value="Yes">Yes</option>
						<option<?php if($account['HasFelony'] == 'No') echo ' SELECTED'; ?> value="No">No</option>
					</select>
				</td>
			</tr>
            <tr>
            	<td class="alrignright">If yes, What?</td>
                <td><input type="text" maxlength="50" size="40" name="FelonyDescription" value="<?php echo $account['FelonyDescription']; ?>" /></td>
            </tr>
			<tr>
				<td class="alignright">Background</td>
				<td>
					<select name="BackgroundCheck">
						<option<?php if($account['BackgroundCheck'] == 'PENDING') echo ' SELECTED'; ?> value="PENDING">Pending</option>
						<option<?php if($account['BackgroundCheck'] == 'CHECKED') echo ' SELECTED'; ?> value="CHECKED">Checked</option>
					</select>
				</td>
			</tr>
            <tr>
					<td class="alignright" colspan="2"><input type="submit" name="save" value="Save" /></td>
			</tr>
        </table>
        </form>
        

        
        
        <?php
	} else {
		header("location: " . site_url() . "home.php");
        exit;
    }
	
	include_once 'include/footer.php';
?>
