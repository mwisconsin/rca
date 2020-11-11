<?php
	include_once 'include/user.php';
	include_once 'include/name.php';
	include_once 'include/rider.php';
	include_once 'include/driver.php';
	include_once 'include/rider_driver.php';
	include_once 'include/franchise.php';
	include_once 'include/address.php';
	include_once 'include/email.php';
	include_once 'include/phone.php';
	include_once 'include/care_facility.php';
	redirect_if_not_logged_in();
    
	$franchise = get_current_user_franchise();
	if(isset($_GET['id']) && $_GET['id'] != '')
	{
		if(!current_user_has_role($franchise, 'FullAdmin') && !care_facility_admin_has_rights_over_user($_GET['id']) && !current_user_has_role(1, 'FullAdmin') && !current_user_has_role($franchise, 'FullAdmin')){
			header("Location: home.php");
			die();
		}	
		$user_id = $_GET['id'];
		$edit_url = "&id=" . $user_id;
	}
	else
	{
		$user_id = get_affected_user_id();
		$edit_url = "";
	}
	
	$current_user_roles = get_user_roles(get_current_user_id(), $franchise);
	$ReadOnly = 0;
	if(current_user_has_role($franchise, 'Franchisee'))
		foreach($current_user_roles as $role) 
			if($role['Role'] == 'Franchisee') $ReadOnly = $role['ReadOnly'];
	if(get_current_user_id() == get_affected_user_id()) $ReadOnly = 0;
	
	user_string( $user_id, TRUE);
	$account = get_user_account($user_id);
	$Person_name = get_name($account['PersonNameID']);
	$alias = get_user_alias($user_id);
	$email = get_email_address($account['EmailID']);
	
	if(count($_POST)) {
		if(isset($_POST['PlaceOnHold'])) {
			mysql_query("update rider set OnHold = 1 where UserID = $user_id");
		}
		if(isset($_POST['RemoveHold'])) {
			mysql_query("update rider set OnHold = 0 where UserID = $user_id");
		}		
	}
	
	include_once 'include/header.php';
	
	if($account['ApplicationStatus'] != 'APPROVED' && (current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee'))){
		?>
		<center><button onclick="document.location = '<?php echo site_url() . "application_process.php?id=$user_id&action=approve"; ?>';">APPROVE</button> <button onclick="document.location = '<?php echo site_url() . "application_process.php?id=$user_id&action=reject"; ?>';">REJECT</button></center>
		<?php
	}
?>
<h2>Account Information</h2>
<hr />
<script>
function CloneUser() {
	$d = jQuery('#CloneUserTemplate').clone();
	$d.dialog({
		width: 'auto', modal: true, title: 'Clone Rider',
		buttons: [
			{
				text: 'Ok',
				click: function() {
					$d.find('form').append('<input type=hidden name=UserID value='+jQuery('#UserID').html()+'>');
					jQuery.post('/xhr/clone_user.php', $d.find('form').serialize(), function(data) {
						$d.dialog('close');
						if(data != '') {
							$e = jQuery('<div>Error: '+data+'</div>');
							$e.dialog({ width: 'auto', modal: true, title: 'Error',
								buttons: [{ text: 'Ok', click: function() { $e.dialog('close'); } }]
							});
						}
						
					});
				}
			},
			{
				text: 'Cancel',
				click: function() {
					$d.dialog('close');
				}
			}
		],
		close: function() { $d.remove(); }	
	});
}	
</script>
<style>
#CloneUserTemplate {
	display: none;
}	
#CloneUserTemplate textarea {
	width: 100%;
}
</style>
<div id=CloneUserTemplate>
	<form>
	<table>
		<tr>
			<td align=right>New Username:</td>	
			<td><input name=NewUsername></td>
		</tr>
		<tr>
			<td align=right>New Email Address</td>	
			<td><input name=NewEmailAddress></td>
		</tr>
		<tr>
			<td colspan=2 align=center>New Qualifications:</td>	
		</tr>
		<tr>
			<td colspan=2><textarea name=NewQualifications></textarea></td>
		</tr>
	</table>
	</form>
</div>
<div class="account_subject">
	<span class="account_subject_text">Name: </span><?php echo get_displayable_person_name_string( $Person_name ); ?> <?php
		if(!$ReadOnly) {
			?>
			<span class="account_subject_edit">(<a href="<?php echo site_url() . 'edit_user.php?field=name' . $edit_url; ?>">Edit Info</a>)</span>
			<?php
		}
	
	if (current_user_has_role(1, 'FullAdmin') || current_user_has_role($link_row['FranchiseID'], 'Franchisee')) {
	?>
	<span class="account_subject_edit">(<a href="#" onClick="CloneUser(); return false;">Clone Rider</a>)
	<?php 
		if(user_has_role($user_id,$franchise,'Rider')) {
			$rider_info = get_user_rider_info($user_id);
			echo "<form method=POST style='display: inline;'>";
			if($rider_info['OnHold'] == 1) {
				echo "&nbsp;&nbsp;&nbsp;<b style='color: red;'>USER IS ON HOLD</b> <input style='font-size: 80%;' type=submit name=RemoveHold value=\"Remove Hold\">";
			} else echo "&nbsp;&nbsp;&nbsp;<input style='font-size: 80%;' type=submit name=PlaceOnHold value=\"Place On Hold\">";
			echo "</form>";
		}
	
	} ?></span>
</div>
<div class="account_subject">
	<span class="account_subject_text">Addresses </span><br>
	<?php
		if(get_user_addresses($user_id))
		{
			$addresses = get_user_address_array($user_id);
			foreach($addresses as $address)
			{
				?>
              <div style="float:left; margin:10px;">
              		<span style="font-weight:bold;"><?php echo $address['AddressType']; ?></span>
              			<?php
              			if(!$ReadOnly) {
              			?>
                    <div style="float:right; font-size:.8em;">
                    <a href="<?php echo site_url() . 'my_places.php?new_destination_from_addressid=' . $address['AddressID'] . "&addresstype=". $address['AddressType']; ?>">Edit</a> 
                    </div>
                    <?php
                  	}
                        create_html_display_address($address, '');
                    ?>
            	</div>
            <?php
			}
			echo '<div class="float_clear"></div><br/>';
			if(!$ReadOnly)
				echo '<a href="' . site_url() . 'my_places.php">Add additional addresses</a>';
		}
		else {
			echo 'You have no addresses saved.';
			if(!$ReadOnly) echo '<a href="' . site_url() . 'edit_user.php?field=createaddress' . $edit_url . '">Add an address.</a>';
		}
?>
</div>
<div class="account_subject">
	<span class="account_subject_text">Phone Numbers</span><br />
	<?php
        if ($phone_numbers = get_user_phone_numbers($user_id)) {
            foreach ($phone_numbers as $phone) {
				?>
				<table>
					<tr>
						<td><?php echo $phone['PhoneType'].($phone["phonedescription"] == "" ? "" : " (".$phone["phonedescription"].")"); ?>:</td>
						<td><?php echo $phone['PhoneNumber'].($phone["Ext"] != '' ? ' x'.$phone["Ext"]: ""); ?></td>
						<?php
						if(!$ReadOnly) {
						?>
						<td>[ <?php if($phone['IsPrimary'] == 'Yes') echo 'Primary'; else {  ?><a href="edit_user.php?field=setprimaryphone&phoneid=<?php echo $phone['PhoneID'] . $edit_url; ?>">Set Primary</a><?php } ?> ] <a href="<?php echo site_url() . 'edit_user.php?field=editphonenumber&phoneid=' . $phone['PhoneID'] . $edit_url; ?>">Edit</a> <a href="<?php echo site_url() . 'edit_user.php?field=deletephonenumber&phoneid=' . $phone['PhoneID'] . $edit_url; ?>">Delete</a></td>
						<?php
						}
						?>
					</tr>

				</table>
				<?php
			}
			if(!$ReadOnly) echo '<br /><a href="' . site_url() . 'edit_user.php?field=createphonenumber' . $edit_url . '">Add Another Number</a>';
		}
		else
			if(!$ReadOnly) echo 'You have no phone numbers saved. <a href="' . site_url() . 'edit_user.php?field=createphonenumber' . $edit_url . '">Add a New Number.</a>'
	?>
</div>
<div class="account_subject">
	<span class="account_subject_text">User Information </span>
	<?php
		if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee') && !$ReadOnly){
			?><span class="account_subject_edit">( <a href="edit_user.php?field=user<?php echo $edit_url; ?>">Edit</a> )</span><?php
			echo '<span class="account_subject_edit">(<a href="' . site_url() . 'edit_user.php?field=deactivateuser' . $edit_url . '">Deactivate Account</a>)</span>';
			echo '<span class="account_subject_edit">(<a href="' . site_url() . 'edit_user.php?field=deleteuser' . $edit_url . '">Delete Account</a>)</span>';
		}
		
	?>
    <table width="100%">
    	<tr>
    		<td class="alignright" width="105px">UserID:</td>
            <td width="43%" id=UserID><?php echo $account['UserID']; ?></td>
            <td class="alignright" width="80px">User Status:</td>
            <td><?php echo $account['Status']; ?></td>
    	</tr>
    	<tr>
    		<td class="alignright">User Name:</td>
            <td><?php echo $account['UserName'];
            	if(!$ReadOnly) { ?> [ <a class="account_subject_edit" href="edit_user.php?field=user<?php echo $edit_url; ?>">Change</a> ]
            <?php } ?></td>
            <td class="alignright">User Roles:</td>
            <td rowspan="2" valign="top">
            	<?php
					$roles = get_user_roles($user_id, $franchise);
					if($roles){
						foreach($roles as $row)
							echo $row['Role'] . ' - ' . ($row['Role'] == 'FullAdmin' ? "All Clubs" : $row['FranchiseName']) . '<br>';
					}
					
				
				if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee') && !$ReadOnly)
					echo '[ <a class="account_subject_edit" href="'.  site_url() . 'edit_user.php?field=addrole' . $edit_url . '">Change Roles</a> ]';
				?>
            </td>
    	</tr>
        <tr>
    		<td class="alignright">Password:</td>
            <td>
            	&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull; 
				<?php if(!$ReadOnly) { ?>
				[ <a class="account_subject_edit" href="<?php echo site_url() . 'edit_user.php?field=password' . $edit_url; ?>">Change Password</a> ]
				<?php } ?>
				<?php 
					if(!isset($_REQUEST['requestsent']) && (current_user_has_role( 1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee')) && !$ReadOnly ) 
						echo '[ <a class="account_subject_edit" href="' . site_url() . 'edit_user.php?field=requestpasswordchange' . $edit_url . '">Request Password Change</a> ]'; 
					else if($_REQUEST['requestsent'] == 'true' && (current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee')) && !$ReadOnly)
						echo '[ Request Sent ]';
				?>
            </td>
    	</tr>
        <tr>
    		<td class="alignright">Email:</td>
            <td>
            	<?php
            	if($email != FALSE){
					echo $email['EmailAddress']; ?> 
					[ <?php if($email['IsVerified'] == 'Yes'){ echo 'Verified'; } else { echo 'Unverified'; } ?> ] 
					<?php
					if(!isset($_GET['id']) || (current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee'))){
						if(!$ReadOnly) {
						?>
						[ <a class="account_subject_edit" href="<?php echo site_url() . 'edit_user.php?field=email' . $edit_url; ?>">Change Email</a> ]
						<?php
						}
					} ?>
				 <?php } else { ?>
				 	No Email Address <?php
				 		if(!$ReadOnly) { ?>
				 			[ <a class="account_subject_edit" href="<?php echo site_url() . 'edit_user.php?field=createemail' . $edit_url; ?>">Create Email Address</a> ]
				 		<?php
				 		}
				 } ?>
            </td>
            
    	</tr>
    </table>
</div>
<div  class="account_subject">
	<span class="account_subject_text">Background Check</span><?php if((current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee')) && !$ReadOnly){ ?> ( <a class="account_subject_edit"  href="edit_user.php?field=background<?php echo $edit_url; ?>">edit</a> )<?php } ?>
    <table width="100%">
    	<tr>
        	<td width="105px" class="alignright">Check Status:</td>
            <td width="10%"><?php echo $account['BackgroundCheck']; ?></td>
            <td width="400"  class="alignright">Felony:</td>
            <td><?php echo $account['HasFelony']; ?></td>
        </tr>
        <tr>
        	<td class="alignright">User Aliases</td>
            <td><?php echo $alias['Alias']; ?></td>
            <td class="alignright">Felony Description:</td>
            <td><?php echo $account['FelonyDescription']; ?></td>
        </tr>
    </table>
</div>

<?php
	if(user_has_role($user_id,$franchise, 'Driver'))
	{
	    if (isset($_GET['make_primary'])) {
		//echo 'here';
		  mysql_query("update vehicle_driver set isPrimary='No' where UserID='".$user_id."'");
		  mysql_query("update vehicle_driver set isPrimary='Yes' where UserID='".$user_id."' and VehicleID='".(int)$_GET['make_primary']."'");
		}
		$driver_id = get_user_driver_id($user_id);
?>
		<div class="account_subject">
			<span class="account_subject_text">Driver Information</span>
			<?php
				if((current_user_has_role( 1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee')) && get_user_driver_info($user_id) && !$ReadOnly)
					echo '<span class="account_subject_edit">(<a href="' . site_url() . 'edit_user.php?field=driver' . $edit_url . '">Edit Info</a>)</span>';
			?>
			<br />
			<?php 
				if(get_user_driver_info($user_id))
				{
					$driver_info = get_user_driver_info($user_id);
					?>
                        <table width="100%">
                        	<tr>
                            	<td width="105px" class="alignright">Driver Status:</td>
                                <td><?php echo $driver_info['DriverStatus']; ?></td>
                                <td class="alignright">DL State:</td>
                                <td><?php echo $driver_info['LicenseState']; ?></td>
                                <td class="alignright">DL Expires:</td>
                                <td><?php echo $driver_info['LicenseExpireDate']; ?></td>
                            </tr>
                            <tr>
                            	<td class="alignright">Copy of DL:</td>
                                <td><?php echo $driver_info['CopyofLicenseOnFile']; ?></td>
                                <td class="alignright">DL #:</td>
                                <td><?php echo $driver_info['LicenseNumber']; ?></td>
                                <td class="alignright">DL Issued:</td>
                                <td><?php echo $driver_info['LicenseIssueDate']; ?></td>
                            </tr>
                            <tr>
                            	<td class="alignright">Birth Date:</td>
                                <td><?php echo date('m/d/Y',strtotime($driver_info['DateOfBirth'])); ?></td>
                              <td align=right>Driver Agreement:</td>
                              	<td><?php echo date('m/d/Y',strtotime($driver_info['DriverAgreementRecorded'])); ?></td>
                            </tr>
                        </table>
                        </div>
                        <div class="account_subject">
						<b>Emergency Contact</b>
						<?php
							if(get_driver_emergency_contact($driver_id))
							{
								if(!$ReadOnly) {
								echo '[ <a href="' . site_url() . 'edit_user.php?field=driveremergencycontact' . $edit_url . '">edit</a> ]';
								if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee'))
									echo '[ <a href="' . site_url() . 'edit_user.php?field=notifyemergencycontact' . $edit_url . '&contactid=' . $driver_info['EmergencyContactID'] . '">notify</a> ]';
								}
								$emergency_contact = get_emergency_contact($driver_info['EmergencyContactID']); 
								$contact_name = get_name($emergency_contact['EmergencyContactName']);
								$contact_address = get_address($emergency_contact['Address']);
								$contact_email = get_email_address($emergency_contact['Email']);
								$contact_phone = get_phone_number($emergency_contact['Phone']);
								$contact_secondary_phone = get_emergency_contact_secondary_phones($driver_info['EmergencyContactID']);
								
								$sql = "select isVerified from email where EmailAddress = '$contact_email[EmailAddress]'";
								$rs = mysql_fetch_assoc(mysql_query($sql));
						?>
						<br>
						<table>
							<tr>
								<td colspan="2"><?php echo get_displayable_person_name_string( $contact_name ); ?></td>
							</tr>
							<tr>
								<td colspan="2">
									<table id="<?php echo $emergency_contact['Address']; ?>" name="address_display">
										<tr>
											<td colspan="3">
												<?php echo $contact_address['Address1'];?>
											</td>
										</tr>
										<tr>
											<td>
												<?php echo $contact_address['City'] . ","; ?>
											</td>
											<td>
												<?php echo $contact_address['State']; ?>
											</td>
											<td>
												<?php echo $contact_address['ZIP5'];?>
											</td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td class="alignright"><?php echo $contact_phone['PhoneType']; ?> Phone: </td>
								<td><?php echo $contact_phone['PhoneNumber'].($contact_phone['Ext'] != '' ? ' x'.$contact_phone['Ext'] : '').($contact_phone['phonedescription'] != '' ? ' ('.$contact_phone['phonedescription'].')' : ''); ?> <?php
									/*if(current_user_has_role( 1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee')) {*/
										?><A href=/edit_user.php?field=deletephonenumber&phoneid=<?php echo $contact_phone["PhoneID"]; ?>>Delete</a><?php
									/*}*/
									?>
									</td>
							</tr>
							<?php foreach($contact_secondary_phone as $phone){ ?>
							<tr>
								<td class="alignright"><?php echo $phone['PhoneType']; ?> Phone:</td>
								<td><?php echo $phone['PhoneNumber'].($phone['Ext'] != '' ? ' x'.$phone['Ext'] : '').($phone['phonedescription'] != '' ? ' ('.$phone['phonedescription'].')' : ''); ?><?php
									/*if(current_user_has_role( 1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee')) {*/
										?><A href=/edit_user.php?field=deletephonenumber&phoneid=<?php echo $phone["PhoneID"]; ?>>Delete</a><?php
									/*}*/
									?>
								</td>
							</tr>
							<?php } ?>
							<tr>
								<td class="alignright">Email:</td>
								<td><?php echo $contact_email['EmailAddress'].( $rs["isVerified"] == "Yes" ? " [Verified]" : "" ); ?></td>
							</tr>
							<tr>
								<td class="alignright">Relation:</td>
								<td><?php echo $driver_info['EmergencyContactRelationship']; ?></td>
							</tr>
						</table>
						<?php
						}
						else
							echo '<br>You dont have an emergency contact. <a href="' . site_url() . 'edit_user.php?field=createdriveremergencycontact' . $edit_url . '">Create</a>.';
						?>
					</div>
					<br>
                    <div class="account_subject">
					<b>Driver Settings</b>
					<?php
						if(get_driver_settings_by_driver_id($driver_id) && !$ReadOnly)
							echo '<span class="account_subject_edit">(<a href="' . site_url() . 'edit_user.php?field=driversettings' . $edit_url . '">Edit Info</a>)</span>';
					?>
					<br />
					<?php	
					if($driver_settings = get_driver_settings_by_driver_id($driver_id))
					{
						?>
                        <table width="100%">
                        	<tr>
                            	<td width="105px" class="alignright">Will Drive Felon:</td>
                                <td><?php echo $driver_settings['FelonRiderOK']; ?></td>
                                <td class="alignright">Sensitive To Smells:</td>
                                <td><?php echo $driver_settings['SensitiveToSmells']; ?></td>
                                <td class="alignright">Will Help With Package:</td>
                                <td><?php echo $driver_settings['WillHelpWithPackage']; ?></td>
                                <td class="alignright">Rider Vision Level:</td>
                                <td><?php echo $driver_settings['VisionLevelReq']; ?></td>
                            </tr>
                        	<tr>
                            	<td class="alignright">Stay With Rider:</td>
                                <td><?php echo $driver_settings['StayWithRider']; ?></td>
                                <td class="alignright">Smoker/Perfume User:</td>
                                <td><?php echo $driver_settings['SmokerOrPerfumeUser']; ?></td>
                                <td class="alignright">Will Help To Car:</td>
                                <td><?php echo $driver_settings['WillHelpToCar']; ?></td>
                                <td class="alignright">Rider Memory Level:</td>
                                <td><?php echo $driver_settings['MemoryLevelReq']; ?></td>
                            </tr>
                            <tr>
                            	<td class="alignright">Service Dog:</td>
                                <td><?php echo $driver_settings['ServiceDog']; ?></td>
                                <td class="alignright">Pet Carrier:</td>
                                <td><?php echo $driver_settings['PetCarrier']; ?></td>
                                <td class="alignright">Unaccompanied Minor:</td>
                                <td><?php echo $driver_settings['UnaccompaniedMinor']; ?></td>
                            
	                 <?php if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee')){ ?>
                            
                            	<td class="alignright">Contact Pref:</td>
                                <td colspan="3"><?php echo $driver_settings['ContactPreference']; ?></td>
                            </tr>
                    <?php } ?>
                        	<tr valign=top>
                            	<td class="alignright" nowrap>Vision Level Required:</td>
                                <td><?php echo $driver_settings['VisionLevelReq']; ?></td>
                                <td class="alignright">Hearing Level Required:</td>
                                <td><?php echo $driver_settings['HearingLevelReq']; ?></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                            	<td class="alignright">Other Notes:</td>
                                <td colspan="3"><?php echo $driver_settings['OtherNotes']; ?></td>
                            </tr>
                        </table>
						<?php
					}
					else
						if(!$ReadOnly) echo 'You have no driver settings. <a href="' . site_url() . 'edit_user.php?field=createdriversettings' . $edit_url . '">Create</a>';
					?>
                    <?php
					if (current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee')) {
					?>
                    <p style="padding:10px;font-weight:bold;">Rider Ranking</p>
                        <?php if(!$ReadOnly) echo '<span class="account_subject_edit" style="padding:10px;">(<a href="' . site_url() . 'edit_rider_driver_match.php?id=' . $user_id . '">Edit Info</a>)</span><br /><br />'; ?>
                        <div><?php echo rdm_preferences($user_id); ?></div>
                    
                    <?php
					}
					?>
                    <a name="oncall"></a><br>
                    <p style="padding:10px;font-weight:bold;">On Call Status</p>
                        <?php if(!$ReadOnly && (current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee'))) echo '<span class="account_subject_edit" style="padding:10px;">(<a href="' . site_url() . 'toggle_oncall.php?id=' . $user_id . '">Toggle Status</a>)</span><br /><br />'; ?>
                        <div>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo driver_oncall_status($user_id); ?></div>
                        </div>
                    <br>
                    <div class="account_subject">
					<b>Insurance Information</b>
					<?php
					if( $insurance = get_driver_insurance_info( $driver_id ) ){
						if(!$ReadOnly && (current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee'))) { ?>
						(<a href="edit_user.php?field=insuranceinfo<?php echo $edit_url; ?>">Edit</a>)
					<?php } ?>
						<table width="100%">
							<tr>
                            	<td width="105px" class="alignright">Agent Name:</td>
								<td><?php echo get_displayable_person_name_string($insurance); ?></td>
								<td class="alignright">Company Name:</td>
								<td><?php echo $insurance['CompanyName']; ?></td>
                                <td class="alignright">Per Person Liability:</td>
								<td><?php echo $insurance['PerPersonLiability']; ?></td>
							</tr>
							<tr>
                            	<td class="alignright">Agent Phone:</td>
								<td><?php echo $insurance['PhoneNumber'].($insurance['Ext'] != '' ? ' x'.$insurance['Ext'] : ''); ?></td>
								<td class="alignright">Policy Number:</td>
								<td><?php echo $insurance['PolicyNumber']; ?></td>
                                <td class="alignright">Per Accident Liability:</td>
								<td><?php echo $insurance['PerAccidentLiability']; ?></td>
							</tr>
							<tr>
                            	<td class="alignright">Agent Address:</td>
								<td><?php echo create_html_display_address($insurance,''); ?></td>
                                <td class="alignright">Policy Expiration Date:</td>
								<td valign="top"><?php echo $insurance['PolicyExpirationDate']; ?></td>
								<td class="alignright">Property Damage Liability:</td>
								<td valign="top"><?php echo $insurance['PropertyDamageLiability']; ?></td>
							</tr>
							<tr>
                            
                            	<td class="alignright">Cert. Of Ins. On File:</td>
								<td valign="top"><?php echo $insurance['CertificateOfInsuranceOnFile']; ?></td>
								<td class="alignright">Copy Of Ins. Card On File:</td>
								<td valign="top"><?php echo $insurance['CopyOfInsuranceCardOnFile']; ?></td>
                                <td class="alignright">Combined Single Limit:</td>
								<td valign="top"><?php echo $insurance['CombinedSingleLimit']; ?></td>
							</tr>
							<tr>
                            	<td class="alignright">Insurance Verified</td>
								<td valign="top"><?php echo $insurance['InsuranceVerified']; ?></td>
							</tr>
						</table>
						<?php
					} else {
						echo "<br>You have not given us your driving insurance information.";
						if(!$ReadOnly) echo "<a href=\"edit_user.php?field=createinsuranceinfo" . $edit_url . "\">Create Now</a>";
					}
					?><br></div>
                    <div class="account_subject">
					<b>Registered Vehicles</b><br>
					<?php
					if(get_driver_vehicles($driver_id))
					{
						$result = get_driver_vehicles($driver_id);
						while($row = mysql_fetch_array($result))
						{
						    
							$vehicle = get_vehicle($row['VehicleID']);
							?>                            
                            <table width="100%">
                                <tr>
                                	<th class="alignright" width="105px">Vehicle ID:</th>
                                	<th><?php echo $row['VehicleID']; ?></th>
                                    <td colspan="4"><?php echo ($row['isPrimary']=='Yes') ? '<b>Primary</b>' : ($ReadOnly ? '' : '[<a href="account.php?make_primary='.$row['VehicleID'].'">Make Primary</a>]'); 
                                    	if(!$ReadOnly) { ?> <span style="font-size:12px;">[<?php echo '<a href="' . site_url() . 'edit_user.php?field=editdrivervehicle' . $edit_url . '&vehicleid=' . $row['VehicleID'] . '">edit</a>'; ?>] [<?php echo '<a href="' . site_url() . 'edit_user.php?field=deletedrivervehicle' . $edit_url . '&vehicleid=' . $row['VehicleID'] . '">Delete</a>'; ?>]</span>
                                    	<?php } ?>
                                    </td>
                                    <td><?php echo "{$vehicle['VehicleYear']} {$vehicle['VehicleMake']} {$vehicle['VehicleModel']}"; ?></td>
                                    <td><?php echo "{$vehicle['VehicleHeight']} height, {$vehicle['VehicleColor']} {$vehicle['VehicleDescription']}"; ?></td>
                                    <td><?php echo "{$vehicle['LicenseState']} - {$vehicle['LicenseNumber']} Holds {$vehicle['MaxPassengers']}"; ?></td>
                                </tr>
                                <tr>
									<td colspan="5"></td>
                                    <td class="alignright">Can Handle Cane:</td>
                                    <td><?php echo $vehicle['CanHandleCane']; ?></td>
                                	<td class="alignright">Can Handle Walker:</td>
                                	<td><?php echo $vehicle['CanHandleWalker']; ?></td>
                                    <td class="alignright">Can Handle Wheelchair:</td>
                                    <td><?php echo $vehicle['Wheelchair']; ?></td>
                                </tr>
                                <tr>
                                	<td colspan="5"></td>
                                    <td class="alignright">Passenger Side Door:</td>
                                    <td><?php echo $vehicle['HasPassengerSideRearDoor']; ?></td>
                                    <td class="alignright">Driver Side Door:</td>
                                    <td><?php echo $vehicle['HasDriverSideRearDoor']; ?></td>
                                </tr>
                            </table><br />
                            
                            <hr width="70%" />
							<?php
						}
						if(!$ReadOnly) echo '<br /><a href="' . site_url() . 'edit_user.php?field=createdrivervehicle' . $edit_url . '">Add Another Vehicle</a>';
					}
					else
						echo'You have no registered vehicles.';
						if(!$ReadOnly) echo '<a href="' . site_url() . 'edit_user.php?field=createdrivervehicle' . $edit_url . '">Register now.</a>';
					
				}
				else
				{
					echo 'Driver account has not been configured.';
					if((current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee')) && !$ReadOnly)
						echo' <a href="' . site_url() . 'edit_user.php?field=createdriver' . $edit_url . '">Create Now.</a>';
				}
			?>
		</div>
<?php
	}
?>
<?php
	if(user_has_role($user_id,$franchise,'Rider'))
	{
?>
		<div class="account_subject">
			<span class="account_subject_text">Rider Information</span>
			<?php
				if((current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee')) && get_user_rider_info($user_id) && !$ReadOnly)
					echo '<span class="account_subject_edit">(<a href="' . site_url() . 'edit_user.php?field=rider' . $edit_url . '">Edit Info</a>)</span>';
			?>
			<br />
			<?php 
				if(get_user_rider_info($user_id))
				{
					$rider_info = get_user_rider_info($user_id);
					?>
                    <table width="100%">
                    	<tr>
                        	<td width="105px" class="alignright">Rider Status:</td>
                            <td><?php echo $rider_info['RiderStatus']; ?></td>
                            <td class="alignright">Rider Waiver Date:</td>
                            <td><?php echo date('m/d/Y',strtotime($rider_info['RiderWaiverReceived'])); ?></td>                          	
                        </tr>
                        <tr>
                        	<td class="alignright">Annual Fee:</td>
                            <td><?php echo $rider_info['AnnualFeePaymentDate']; ?></td>
                            <td class="alignright">Birth Date:</td>
                            <td><?php echo date('m/d/Y',strtotime($rider_info['DateOfBirth'])); ?></td>
                        </tr>
                        <tr>
                        	<td class="alignright">ADA Qualified:</td>
                            <td><?php echo $rider_info['ADAQualified']; ?></td>
                            <td class="alignright">Qualifications:</td>
                            <td><?php echo $rider_info['QualificationReason']; ?></td>
                        </tr>
                        <tr>
                        	<td class="alignright">Default # in Car:</td>
                            <td><?php echo $rider_info['default_num_in_car']; ?></td>
                        <?php if(current_user_has_role(1, "FullAdmin") || current_user_has_role($franchise, "Franchisee")) { ?>
                        	<td class="alignright">Scheduling Cutoff Hour:</td>
                        	<td><?php echo $rider_info['Cutoff_Hour']; ?></td>
                        </tr>
						<?php } else { ?>
							<td colspan=2></td>
						<?php } ?>
						<?php if(current_user_has_role(1, "FullAdmin") || current_user_has_role($franchise, "Franchisee")) { ?>
						<tr>
							<td class="alignright" nowrap>Can Schedule A Ride:</td>
							<td><?php echo $rider_info['CanScheduleRides'] == 1 ? 'Yes' : 'No'; ?></td>
						</tr>
						<?php } ?>

                    </table>
                    <br />
                    </div>
                    <div class="account_subject">
					<b>Emergency Contact</b>
					<?php
						if(get_rider_emergency_contact(get_user_rider_id($user_id)))
						{
							if(!$ReadOnly) {
								echo '[ <a href="' . site_url() . 'edit_user.php?field=rideremergencycontact' . $edit_url . '">edit</a> ]';
								if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee'))
									echo '[ <a href="' . site_url() . 'edit_user.php?field=notifyemergencycontact' . $edit_url . '&contactid=' . $rider_info['EmergencyContactID'] . '">notify</a> ]';
							}
							$emergency_contact = get_rider_emergency_contact(get_user_rider_id($user_id)); 
							$contact_name = get_name($emergency_contact['EmergencyContactName']);
							$contact_address = get_address($emergency_contact['Address']);
							$contact_email = get_email_address($emergency_contact['Email']);
							$contact_phone = get_phone_number($emergency_contact['Phone']);
							$contact_secondary_phone = get_emergency_contact_secondary_phones($emergency_contact['EmergencyContactID']);
							
							$sql = "select isVerified from email where EmailAddress = '$contact_email[EmailAddress]'";
							$rs = mysql_fetch_assoc(mysql_query($sql));
							
							?>
							<br>
							<table>
								<tr>
									<td colspan="2"><?php echo get_displayable_person_name_string( $contact_name ); ?></td>
								</tr>
								<tr>
									<td colspan="2">
										<table id="<?php echo $emergency_contact['Address']; ?>" name="address_display">
											<tr>
												<td colspan="3">
													<?php echo $contact_address['Address1'];?>
												</td>
											</tr>
											<tr>
												<td>
													<?php echo $contact_address['City'] . ","; ?>
												</td>
												<td>
													<?php echo $contact_address['State']; ?>
												</td>
												<td>
													<?php echo $contact_address['ZIP5'];?>
												</td>
											</tr>
										</table>
									</td>
								</tr>
								<tr>
									<td class="alignright"><?php echo $contact_phone['PhoneType']; ?> Phone:</td>
									<td><?php echo $contact_phone['PhoneNumber'].($contact_phone['Ext'] != '' ? ' x'.$contact_phone['Ext'] : '').($contact_phone['phonedescription'] != '' ? ' ('.$contact_phone['phonedescription'].')' : ''); ?></td>
								</tr>
								<?php foreach($contact_secondary_phone as $phone){ ?>
								<tr>
								    <td class="alignright"><?php echo $phone['PhoneType']; ?> Phone:</td>
								    <td><?php echo $phone['PhoneNumber'].($phone['Ext'] != '' ? ' x'.$phone['Ext'] : '').($phone['phonedescription'] != '' ? ' ('.$phone['phonedescription'].')' : ''); ?></td>
				                </tr>
                                <?php } ?>
								<tr>
									<td class="alignright">Email:</td>
									<td><?php echo $contact_email['EmailAddress'].($rs["isVerified"] == "Yes" ? " [ Verified ]" : ""); ?></td>
								</tr>
								<tr>
									<td class="alignright">Relation:</td>
									<td><?php echo $rider_info['EmergencyContactRelationship']; ?></td>
								</tr>
							</table>
						<?php
						}
						else {
							echo '<br>You dont have an emergency contact.';
							if(!$ReadOnly) echo '<a href="' . site_url() . 'edit_user.php?field=createrideremergencycontact' . $edit_url . '">Create</a>.';
						}
						?>
                        </div>
                        <div class="account_subject">
						<b>Rider Preferences</b>
                          <p style="padding:10px;font-weight:bold;">Vehicle Preferences</p>
						<?php
						if(get_user_rider_preferences($user_id) && !$ReadOnly)
							echo '<span class="account_subject_edit">(<a href="' . site_url() . 'edit_user.php?field=riderpreferences' . $edit_url . '">Edit Info</a>)</span>';
						
						if(get_user_rider_preferences($user_id))
						{
							$preferences = get_user_rider_preferences($user_id);
							?>
							<table width="100%">
								<tr>
									<td class="alignright">Ride with Felon:</td>
									<td><?php echo $preferences['FelonDriverOK']; ?></td>
									<td width="155px" class="alignright">High Vehicle:</td>
									<td><?php echo $preferences['HighVehicleOK']; ?></td>
									<td class="alignright">Medium Vehicle:</td>
									<td><?php echo $preferences['MediumVehicleOK']; ?></td>
									<td class="alignright">Low Vehicle:</td>
									<td><?php echo $preferences['LowVehicleOK']; ?></td>
								</tr>
								<tr>
									<td class="alignright">Driver Stays:</td>
									<td><?php echo $preferences['DriverStays']; ?></td>
									<td class="alignright">Has Cane:</td>
									<td><?php echo $preferences['HasCane']; ?></td>
									<td class="alignright">Has Walker:</td>
									<td><?php echo $preferences['HasWalker']; ?></td>
									<td class="alignright">Has Wheelchair:</td>
									<td><?php echo $preferences['HasWheelchair']; ?></td>
								</tr>
								<tr>
									<td class="alignright">Needs Package Help:</td>
									<td><?php echo $preferences['NeedsPackageHelp']; ?></td>
									<td class="alignright">Needs Help To Car:</td>
									<td><?php echo $preferences['NeedsHelpToCar']; ?></td>
									<td class="alignright">Enter Driver Side:</td>
									<td><?php echo $preferences['EnterDriverSide']; ?></td>
									<td class="alignright">Enter Passenger Side:</td>
									<td><?php echo $preferences['EnterPassengerSide']; ?></td>
								</tr>
								<tr>
									<td class="alignright">Sensitive to smells:</td>
									<td><?php echo $preferences['SensitiveToSmells']; ?></td>
									<td class="alignright">Smoker/perfume user:</td>
									<td><?php echo $preferences['SmokerOrPerfumeUser']; ?></td>
									<td class="alignright">Memory Loss:</td>
									<td><?php echo $preferences['HasMemoryLoss']; ?></td>
									<td class="alignright">Vision Level:</td>
									<td><?php echo $preferences['VisionLevel']; ?></td>
								</tr>
								<tr>
									<td class="alignright">Addl Rider?:</td>
									<td><?php echo $preferences['HasCaretaker']; ?></td>
                                    <td class="alignright">Addl Rider:</td>
									<td><?php echo  get_displayable_person_name_string( get_name($preferences['CaretakerID'])); ?></td>
									<td class="alignright">Has Service Animal:</td>
									<td><?php echo $preferences['HasServiceAnimal']; ?></td>
									<td class="alignright">Has Pet Carrier:</td>
									<td><?php echo $preferences['HasSmallPetInCarrier']; ?></td>
								</tr>
								<tr>
								    <?php if($preferences['HasCaretaker'] == "Yes"){ ?>
								    <td class="alignright">Addl Rider BD:</td>
									<td><?php echo format_date($preferences['CaretakerBirthday'],"n/j/Y"); ?></td>
									<td class="alignright">Addl Rider BG ch:</td>
									<td><?php echo $preferences['CaretakerBackgroundCheck']; ?></td>
									<td class="alignright">Hearing Level:</td>
									<td><?php echo $preferences['HearingLevel']; ?></td>
									<?php } ?>
                                </tr>
								<?php if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee')) { ?>
								<tr>
									<td class="alignright">Front Seat Preference:</td>
									<td><?php echo $preferences['FrontSeat']; ?></td>
								</tr>
								<?php } ?>
								<tr>
								 	<td class="alignright">Other Notes:</td>
									<td colspan="6"><?php echo $preferences['OtherNotes']; ?></td>
                                </tr>
							</table>
							<?php
						} else {
							echo '<br />You have no preferences saved. ';
							if(!$ReadOnly) echo '<a href="' . site_url() . 'edit_user.php?field=createriderpreferences' . $edit_url . '">Create Preferences.</a>';
						}
						
						?>
                        <?php
						if (current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee')) {
						?>
                        <p style="padding:10px;font-weight:bold;">Driver Ranking</p>
                        <?php if(!$ReadOnly) echo '<span class="account_subject_edit" style="padding:10px;">(<a href="' . site_url() . 'edit_rider_driver_match.php?id=' . $user_id . '">Edit Info</a>)</span><br /><br />'; ?>
                        <div><?php echo rdm_preferences($user_id); ?></div>
                        <?php
						}
				}
				else
					echo 'Rider account has not been configured. <a href="' . site_url() . 'edit_user.php?field=createrider' . $edit_url . '">Create Now.</a>';
			?>
		</div>
<?php
	}
?>
<?php
	if(if_user_has_role($user_id,$franchise, 'CareFacilityAdmin')){
		$care_facility_id = get_first_user_care_facility( $user_id );
		?>
		<div class="account_subject">
			<span class="account_subject_text">Care Facility</span><br>
			<?php
				if(!$care_facility_id){
					echo 'You have not assoiated yourself with a care facility.';
					if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee'))
						echo '<a href="' . site_url() . 'edit_user.php?field=addcarefacility' . $edit_url . '">Create</a>.';
				} else {
					echo 'You are connected with:<br><br>';
					$care_facility = get_care_facility($care_facility_id);
					echo $care_facility['CareFacilityName'];
				}
			?>
		</div>
		<?php
	}
?>
<?php
    include_once 'include/footer.php';
?>
