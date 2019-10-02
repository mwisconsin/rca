<?php
error_reporting(0);
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
    if(isset($_POST['DriverStatus']))
		{
			$required_fields = array('LicenseState','LicenseNumber');
			$required_filled = true;
			
			foreach($required_fields as $k => $v){
				if($_POST[$v] == '')
					$required_filled = false;
			}
			
			if(!$required_filled){
				$error = 'All required name fields were not filled.';
			} else {
				$birthday = mysql_real_escape_string($_POST['BirthYear']) . '-' . mysql_real_escape_string($_POST['BirthMonth']) . '-' . mysql_real_escape_string($_POST['BirthDay']);
				$expiration = mysql_real_escape_string($_POST['LicenseExpirationYear']) . '-' . mysql_real_escape_string($_POST['LicenseExpirationMonth']) . '-' . mysql_real_escape_string($_POST['LicenseExpirationDay']);
				$issue = mysql_real_escape_string($_POST['LicenseIssueYear']) . '-' . mysql_real_escape_string($_POST['LicenseIssueMonth']) . '-' . mysql_real_escape_string($_POST['LicenseIssueDay']);
				
				$query = " INSERT INTO `driver` (`UserID` ,`DriverStatus` ,`EmergencyContactID` ,`EmergencyContactRelationship` ,`LicenseState` ,`LicenseNumber` ,`DateOfBirth`, `LicenseIssueDate`, `LicenseExpireDate`, `CopyofLicenseOnFile`, DriverAgreementRecorded)
	VALUES (" . mysql_real_escape_string($user_id) . ", '" . mysql_real_escape_string($_POST['DriverStatus']) . "', NULL, NULL, '" . mysql_real_escape_string($_POST['LicenseState']) . "', '" . mysql_real_escape_string($_POST['LicenseNumber']) . "', '" . $birthday 
	. "', '" . $issue . "', '" . $expiration . "', '" . mysql_real_escape_string($_POST['CopyOfDL']) . "','".date('Y-m-d',strtotime($_POST["DriverAgreementRecorded"]))."')
				ON DUPLICATE KEY UPDATE `DriverStatus` = '" . mysql_real_escape_string($_POST['DriverStatus']) . "' ";
				mysql_query($query) or die(mysql_error() . ": " . $query);
				
				
			
			// check phone numbers
				if (isset($_POST['data']['PhoneNumber'])) {
				  $count = 0;
				  //print_r($_POST['data']['PhoneNumber']);
				  foreach($_POST['data']['PhoneNumber'] as $tmpname=>$phone_number) {
				   // echo '<br />';
					//print_r($phone_number);
				    if ($tmpname!='IsPrimary') {
					  if ($phone_number['PhoneNumber']!='') {
					    //echo 'here';
						
					    $phone_id = add_phone_number_for_user($phone_number['PhoneNumber'],$phone_number['PhoneType'],$user_id
					    	,($phone_number['CanSendTexts'] == 'on' ? 'Y' : 'N'), $phone_number['CellProvider'], $phone_number['Ext'], $phone_number["sms_preferences"]);
					    if (isset($_POST['data']['PhoneNumber']['IsPrimary']) && ($_POST['data']['PhoneNumber']['IsPrimary']==$count))
						  set_primary_phone_for_user($user_id, $phone_id);
					  }
					  $count++;
					}
				  }
				  //exit;
				}
				
				// emergency contact stuff
				$required_fields_e = array('FirstName','LastName','Address1','EmergencyContactRelationship');
				$required_filled_e = true;
				
				foreach($required_fields_e as $k => $v){
				   // echo $v;
					if($_POST['data']['Emergency'][$v] == '')
						$required_filled_e = false;
				}
				
				
				
				if(!$required_filled_e){
					$error = 'All required fields were not filled.';
					//echo 'error found';
					
				} else {
				    //echo 'emergency';
					if (createEmergencyContact($user_id, $_POST['data']['Emergency'], true, 'driver')) {
					
					} 
				}

				if(isset($_POST['data']['DriverSettings']['FelonRiderOK']) 
					&& isset($_POST['data']['DriverSettings']['StayWithRider']) 
					&& isset($_POST['data']['DriverSettings']['WillHelpWithPackage']) 
					&& isset($_POST['data']['DriverSettings']['WillHelpToCar']) 
					&& isset($_POST['data']['DriverSettings']['OtherNotes']))
				{

					$query = "INSERT INTO `driver_settings` (`UserID`, `FelonRiderOK`, `StayWithRider`, `WillHelpWithPackage`, `WillHelpToCar`, `SensitiveToSmells`, `SmokerOrPerfumeUser`,  `ServiceDog`,
							              `PetCarrier`, `UnaccompaniedMinor`, `MaxHoursPerWeek`, `ContactPreference`, `OtherNotes`)
					VALUES ('" . mysql_real_escape_string($user_id) . "', '" . mysql_real_escape_string($_POST['data']['DriverSettings']['FelonRiderOK']) . "', '" . mysql_real_escape_string($_POST['data']['DriverSettings']['StayWithRider']) .
					        "', '" . mysql_real_escape_string($_POST['data']['DriverSettings']['WillHelpWithPackage']) . "', '" . mysql_real_escape_string($_POST['data']['DriverSettings']['WillHelpToCar']) . 
					        "', '" . mysql_real_escape_string($_POST['data']['DriverSettings']['SensitiveToSmells']) . "', '" . mysql_real_escape_string($_POST['data']['DriverSettings']['SmokerOrPerfumeUser']) . 
			                "', '" . mysql_real_escape_string($_POST['data']['DriverSettings']['ServiceDog']) . "', '" . mysql_real_escape_string($_POST['data']['DriverSettings']['Pet carrier']) . "', '" . mysql_real_escape_string($_POST['data']['DriverSettings']['UnaccompaniedMinor']) . 
					        "', '" . mysql_real_escape_string($_POST['data']['DriverSettings']['MaxHoursPerWeek']) . "', '" . mysql_real_escape_string($_POST['data']['DriverSettings']['ContactPreference']) . "', '" . mysql_real_escape_string($_POST['data']['DriverSettings']['OtherNotes']) . "');";

					mysql_query($query) or die(mysql_error());

				}
				
					if(isset($_POST['data']['Vehicle']['VehicleYear']) && isset($_POST['data']['Vehicle']['VehicleMake']) && isset($_POST['data']['Vehicle']['VehicleModel']) && isset($_POST['data']['Vehicle']['VehicleColor'])
				 && isset($_POST['data']['Vehicle']['VehicleDescription']) && isset($_POST['data']['Vehicle']['LicenseState']) && isset($_POST['data']['Vehicle']['LicenseNumber']) && isset($_POST['data']['Vehicle']['VehicleHeight'])
				  && isset($_POST['data']['Vehicle']['CanHandleWalker']) && isset($_POST['data']['Vehicle']['CanHandleCane']) && isset($_POST['data']['Vehicle']['HasDriverSideRearDoor']) && isset($_POST['data']['Vehicle']['HasPassengerSideRearDoor']))
				{
					$required_fields_v = array('VehicleMake','VehicleModel','VehicleColor','VehicleDescription','LicenseState','LicenseNumber');
					$required_filled_v = true;
					
					foreach($required_fields_v as $k => $v){
						if($_POST['data']['Vehicle'][$v] == '')
							$required_filled_v = false;
					}
					
					if(!$required_filled_v){
						//$error = 'All required name fields were not filled.';
					} else {
						$query = "INSERT INTO `vehicle` (`VehicleID` ,`VehicleYear` ,`VehicleMake` ,`VehicleModel` ,`VehicleColor` ,
						`VehicleDescription` ,`LicenseState` ,`LicenseNumber` ,`VehicleHeight` ,`CanHandleWalker` ,`CanHandleCane` ,
						`HasDriverSideRearDoor` ,`HasPassengerSideRearDoor`, `MaxPassengers`)
						VALUES (NULL , '" . mysql_real_escape_string($_POST['data']['Vehicle']['VehicleYear']) . "', '" . mysql_real_escape_string($_POST['data']['Vehicle']['VehicleMake']) . "', '" . mysql_real_escape_string($_POST['data']['Vehicle']['VehicleModel']) . "', '" . mysql_real_escape_string($_POST['data']['Vehicle']['VehicleColor']) . "',
						'" . mysql_real_escape_string($_POST['data']['Vehicle']['VehicleDescription']) . "', '" . mysql_real_escape_string($_POST['data']['Vehicle']['LicenseState']) . "', '" . mysql_real_escape_string($_POST['data']['Vehicle']['LicenseNumber']) . "', '" . mysql_real_escape_string($_POST['data']['Vehicle']['VehicleHeight']) . "',
						'" . mysql_real_escape_string($_POST['data']['Vehicle']['CanHandleWalker']) . "', '" . mysql_real_escape_string($_POST['data']['Vehicle']['CanHandleCane']) . "', '" . mysql_real_escape_string($_POST['data']['Vehicle']['HasDriverSideRearDoor']) . "', '" . mysql_real_escape_string($_POST['data']['Vehicle']['HasPassengerSideRearDoor']) . "', '" . mysql_real_escape_string($_POST['data']['Vehicle']['MaxPassengers']) . "');";
						mysql_query($query) or die(mysql_error());
						$vehicle_id = mysql_insert_id();
						
						$query = "INSERT INTO `vehicle_driver` (`VehicleID` ,`userID`)
																	VALUES ('" . mysql_real_escape_string($vehicle_id) . "', '" . mysql_real_escape_string($user_id) . "');";
						mysql_query($query) or die(mysql_error());
						
						//redirect();
					}
				}
			
			$_GET["redirect"] = '/driver_availability.php';
			// check driver settings
			  redirect(TRUE);
			}
		} else if( get_user_driver_info( $user_id ) ){
			redirect();
		}
		
		
		
		
		include_once 'include/header.php';
		
		?>
		<center><h2>Create Driver</h2></center>
		<?php echo '<center>' . $error . '</center>'; ?>
		<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?field=createdriver' . $edit_url; ?>">
        <table cellpadding="0" cellspacing="0">
        <tr>
          <td valign="top">
			<table style="margin:auto; width:400px;">
				<tr>
					<td class="alignright">Driver Status</td>
					<td>
						<select name="DriverStatus">
							<option value="Active">Active</option>
							<option value="Inactive">Inactive</option>
						</select>
					</td>
				</tr>
				<tr>
					<td align=right>Driver Agreement</td>	
					<td><input name=DriverAgreementRecorded class=jq_datepicker value="<?php echo @$_POST["DriverAgreementRecorded"]; ?>" size=10></td>
				</tr>
				<tr>
					<td class="alignright">Copy of DL</td>
					<td>
						<select name="CopyOfDL">
							<option value="No">No</option>
							<option value="Yes">Yes</option>
						</select>
					</td>
				</tr>
				<tr>
					<td class="alignright">Birth Date</td>
					<td><?php get_date_drop_downs('Birth', $_GET['b']); ?>
					</td>
				</tr>
				<tr>
					<td class="alignright">*Driver License State</td>
					<td>
						<?php
                        	get_state_dropdown('License');
						?>
					</td>
				</tr>
				<tr>
					<td class="alignright">*Driver License Number</td>
					<td><input type="text" name="LicenseNumber" maxlength="15" value="<?php if ($_POST['LicenseNumber']) { echo $_POST['LicenseNumber']; } ?>"></td>
				</tr>
				<tr>
					<td class="alignright">Driver License Expiration</td>
					<td><?php get_date_drop_downs('LicenseExpiration', $_GET['b'], date("Y") - 10, date("Y") + 10); ?></td>
				</tr>
                <tr>
					<td class="alignright">Driver License Issue Date</td>
					<td><?php get_date_drop_downs('LicenseIssue', $_GET['b'], date("Y") - 10, date("Y") + 10); ?></td>
				</tr>
				<tr>
					<td class="alignright" colspan="2"><input type="submit" name="save" value="Save" /></td>
				</tr>
			</table>
          </td>
          <td style="width:20px;"></td>
          <td valign="top" width="700">
          
          <script language="javascript">
var panels = Array('panel_phone_number','panel_emergency_contact','panel_driver_settings', 'panel_driver_vehicle');
var tabs   = Array('tab_phone_number', 'tab_emergency_contact', 'tab_driver_settings', 'tab_driver_vehicle');
function showPanel(index) {
  // hida all panels
  //alert(panel_name);
  for (i=0; i<panels.length; i++) {
    document.getElementById(panels[i]).style.display = 'none';
	document.getElementById(tabs[i]).className = 'tab_button';
  }
  document.getElementById(panels[index]).style.display = 'block';
  document.getElementById(tabs[index]).className = 'tab_button_selected';
}
</script>    
                <div style="border:1px solid black;box-shadow:2px 2px 2px rgba(125,125,125, .6);padding:10px;">
                <h2>Additional Information</h2>
                  <a href="#" onclick="showPanel(0);" class="tab_button" id="tab_phone_number">Phone Numbers</a><a href="#" onclick="showPanel(1);" class="tab_button" id="tab_emergency_contact">Emergency Contact</a><a href="#" onclick="showPanel(2);" class="tab_button" id="tab_driver_settings">Driver Settings</a><a href="#" onclick="showPanel(3);" class="tab_button" id="tab_driver_vehicle">Driver Vehicle</a>  
                  <div id="panel_phone_number" style="background-color:#bbbbbb;padding:5px;">
				  Enter additional phone numbers.
                  <table style="margin: auto; width: 300px;">
                    <tr>
                      <th>Type</th>
                      <th>Number</th>
                      <th>Primary</th>
                    </tr>
                    <input type="hidden" name="data[PhoneNumber][0][PhoneType]" />
                    <input type="hidden" name="data[PhoneNumber][0][PhoneNumber]" />
                    <script>
                    
                    	function toggleMobile( sel, num ) {
                    		if(jQuery(sel).val() == 'MOBILE') jQuery('.mobile_toggle[phonenumber='+num+']').show();
                    		else jQuery('.mobile_toggle[phonenumber='+num+']').hide();
                    	}
                    
                    </script>
                    <style>
                    	.sms_provider {
                    		width: 233px;
                    	}
                    	.mobile_toggle {
                    		display: none;
                    	}	
                    </style>                    
                    <tr>
                      <td>
                        <select name="data[PhoneNumber][1][PhoneType]" onChange="toggleMobile(this,1);">
                          <option <?php if($_POST['data']['PhoneNumber'][1]['PhoneType'] == "HOME") echo 'SELECTED'; ?> value="HOME">Home</option>
                          <option <?php if($_POST['data']['PhoneNumber'][1]['PhoneType'] == "MOBILE") echo 'SELECTED'; ?> value="MOBILE">Mobile</option>
                          <option <?php if($_POST['data']['PhoneNumber'][1]['PhoneType'] == "WORK") echo 'SELECTED'; ?> value="WORK">Work</option>
                          <option <?php if($_POST['data']['PhoneNumber'][1]['PhoneType'] == "FAX") echo 'SELECTED'; ?> value="FAX">Fax</option>
                          <option <?php if($_POST['data']['PhoneNumber'][1]['PhoneType'] == "OTHER") echo 'SELECTED'; ?> value="OTHER">Other</option>
                        </select>
                      </td>
                      <td nowrap><input style="width:120px;" name="data[PhoneNumber][1][PhoneNumber]" maxlength="20" type="text" value="<?php if ($_POST['LicenseNumber']) { echo $_POST['LicenseNumber']; } ?>">
                       	 x<input style='width:33px' name="data[PhoneNumber][1][Ext]" maxlength="5" type="text" value="<?php if ($_POST['data']['PhoneNumber'][1]['Ext']) { echo $_POST['data']['PhoneNumber'][1]['Ext']; } ?>">
											</td>
                      <td><input name="data[PhoneNumber][IsPrimary]" type="radio" value="1"<?php if ($_POST['data']['PhoneNumber']['IsPrimary']==1) { echo ' checked="checked"'; } ?>></td>
                    </tr>
                    <tr class=mobile_toggle phonenumber=1>
                    	<td colspan=2>
                    			Can we send texts?  <input type=checkbox name="data[PhoneNumber][1][CanSendTexts]" <?php if($_POST['data']['PhoneNumber'][1]['CanSendTexts'] == 'on') echo 'checked'; ?> >
                    	</td>	
                    	<td></td>
                    </tr>
                    <tr class=mobile_toggle phonenumber=1>
                    	<td>
                    		Provider:	
                    	</td>
                    	<td colspan=2>
                    		<select class=sms_provider name="data[PhoneNumber][1][CellProvider]" size=1><option value=0>Select...</option><?php
		                    	$sql = "select id, name from sms_providers order by name";
		                    	$r = mysql_query($sql);
		                    	while($rs = mysql_fetch_assoc($r)) 
		                    		echo "<option value=$rs[id] "
		                    			.($_POST['data']['PhoneNumber'][1]['CellProvider'] ? "selected" : "")
		                    			.">$rs[name]</option>\n";
		                    	?></select>
                    	</td>
                    </tr>        
                    <tr valign=top class=mobile_toggle phonenumber=1>
                    	<td>
                    		Preferences:	
                    	</td>
                    	<td colspan=2>
												<input type=radio name="sms_preferences" value='FIRST' <?php echo $_POST['data']['PhoneNumber'][1]['sms_preferences'] == 'FIRST' ? 'checked' : ''; ?>> Text on 1st Ride<br>
												<input type=radio name="sms_preferences" value='SUBSEQUENT' <?php echo $_POST['data']['PhoneNumber'][1]['sms_preferences'] == 'SUBSEQUENT' ? 'checked' : ''; ?>> Text on Subsequent Rides<br>
                    	</td>
                    </tr>                           
                    <tr>
                      <td>
                        <select name="data[PhoneNumber][2][PhoneType]" onChange="toggleMobile(this,2);">
                          <option <?php if($_POST['data']['PhoneNumber'][2]['PhoneType'] == "HOME") echo 'SELECTED'; ?> value="HOME">Home</option>
                          <option <?php if($_POST['data']['PhoneNumber'][2]['PhoneType'] == "MOBILE") echo 'SELECTED'; ?> value="MOBILE">Mobile</option>
                          <option <?php if($_POST['data']['PhoneNumber'][2]['PhoneType'] == "WORK") echo 'SELECTED'; ?> value="WORK">Work</option>
                          <option <?php if($_POST['data']['PhoneNumber'][2]['PhoneType'] == "FAX") echo 'SELECTED'; ?> value="FAX">Fax</option>
                          <option <?php if($_POST['data']['PhoneNumber'][2]['PhoneType'] == "OTHER") echo 'SELECTED'; ?> value="OTHER">Other</option>
                        </select>
                      </td>
                      <td><input style='width:120px' name="data[PhoneNumber][2][PhoneNumber]" maxlength="20" type="text" value="<?php if ($_POST['data']['PhoneNumber'][2]['PhoneNumber']) { echo $_POST['data']['PhoneNumber'][2]['PhoneNumber']; } ?>">
                      	 x<input style='width:33px' name="data[PhoneNumber][2][Ext]" maxlength="5" type="text" value="<?php if ($_POST['data']['PhoneNumber'][2]['Ext']) { echo $_POST['data']['PhoneNumber'][2]['Ext']; } ?>">
                      	</td>
                      <td><input name="data[PhoneNumber][IsPrimary]" type="radio" value="2"<?php if ($_POST['data']['PhoneNumber']['IsPrimary']==2) { echo ' checked="checked"'; } ?>></td>
                    </tr>
                    <tr class=mobile_toggle phonenumber=2>
                    	<td colspan=2>
                    			Can we send texts?  <input type=checkbox name="data[PhoneNumber][2][CanSendTexts]" <?php if($_POST['data']['PhoneNumber'][2]['CanSendTexts'] == 'on') echo 'checked'; ?> >
                    	</td>	
                    	<td></td>
                    </tr>
                    <tr class=mobile_toggle phonenumber=2>
                    	<td>
                    		Provider:	
                    	</td>
                    	<td colspan=2>
                    		<select class=sms_provider name="data[PhoneNumber][2][CellProvider]" size=1><option value=0>Select...</option><?php
		                    	$sql = "select id, name from sms_providers order by name";
		                    	$r = mysql_query($sql);
		                    	while($rs = mysql_fetch_assoc($r)) 
		                    		echo "<option value=$rs[id] "
		                    			.($_POST['data']['PhoneNumber'][2]['CellProvider'] ? "selected" : "")
		                    			.">$rs[name]</option>\n";
		                    	?></select>
                    	</td>
                    </tr>
                    <tr valign=top class=mobile_toggle phonenumber=2>
                    	<td>
                    		Preferences:	
                    	</td>
                    	<td colspan=2>
												<input type=radio name="sms_preferences" value='FIRST' <?php echo $_POST['data']['PhoneNumber'][2]['sms_preferences'] == 'FIRST' ? 'checked' : ''; ?>> Text on 1st Ride<br>
												<input type=radio name="sms_preferences" value='SUBSEQUENT' <?php echo $_POST['data']['PhoneNumber'][2]['sms_preferences'] == 'SUBSEQUENT' ? 'checked' : ''; ?>> Text on Subsequent Rides<br>
                    	</td>
                    </tr>                                        
                    <tr>
                      <td>
                        <select name="data[PhoneNumber][3][PhoneType]" onChange="toggleMobile(this,3);">
                          <option <?php if($_POST['data']['PhoneNumber'][3]['PhoneType'] == "HOME") echo 'SELECTED'; ?> value="HOME">Home</option>
                          <option <?php if($_POST['data']['PhoneNumber'][3]['PhoneType'] == "MOBILE") echo 'SELECTED'; ?> value="MOBILE">Mobile</option>
                          <option <?php if($_POST['data']['PhoneNumber'][3]['PhoneType'] == "WORK") echo 'SELECTED'; ?> value="WORK">Work</option>
                          <option <?php if($_POST['data']['PhoneNumber'][3]['PhoneType'] == "FAX") echo 'SELECTED'; ?> value="FAX">Fax</option>
                          <option <?php if($_POST['data']['PhoneNumber'][3]['PhoneType'] == "OTHER") echo 'SELECTED'; ?> value="OTHER">Other</option>
                        </select>
                      </td>
                      <td><input style='width:120px' name="data[PhoneNumber][3][PhoneNumber]" maxlength="20" type="text" value="<?php if ($_POST['data']['PhoneNumber'][3]['PhoneNumber']) { echo $_POST['data']['PhoneNumber'][3]['PhoneNumber']; } ?>">
                      	 x<input style='width:33px' name="data[PhoneNumber][3][Ext]" maxlength="5" type="text" value="<?php if ($_POST['data']['PhoneNumber'][3]['Ext']) { echo $_POST['data']['PhoneNumber'][3]['Ext']; } ?>">
                      	</td>
                      <td><input name="data[PhoneNumber][IsPrimary]" type="radio" value="3"<?php if ($_POST['data']['PhoneNumber']['IsPrimary']==3) { echo ' checked="checked"'; } ?>></td>
                    </tr>
                    <tr class=mobile_toggle phonenumber=3>
                    	<td colspan=2>
                    			Can we send texts?  <input type=checkbox name="data[PhoneNumber][3][CanSendTexts]" <?php if($_POST['data']['PhoneNumber'][3]['CanSendTexts'] == 'on') echo 'checked'; ?> >
                    	</td>	
                    	<td></td>
                    </tr>
                    <tr class=mobile_toggle phonenumber=3>
                    	<td>
                    		Provider:	
                    	</td>
                    	<td colspan=2>
                    		<select class=sms_provider name="data[PhoneNumber][3][CellProvider]" size=1><option value=0>Select...</option><?php
		                    	$sql = "select id, name from sms_providers order by name";
		                    	$r = mysql_query($sql);
		                    	while($rs = mysql_fetch_assoc($r)) 
		                    		echo "<option value=$rs[id] "
		                    			.($_POST['data']['PhoneNumber'][3]['CellProvider'] ? "selected" : "")
		                    			.">$rs[name]</option>\n";
		                    	?></select>
                    	</td>
                    </tr>                   
                    <tr valign=top class=mobile_toggle phonenumber=3>
                    	<td>
                    		Preferences:	
                    	</td>
                    	<td colspan=2>
												<input type=radio name="sms_preferences" value='FIRST' <?php echo $_POST['data']['PhoneNumber'][3]['sms_preferences'] == 'FIRST' ? 'checked' : ''; ?>> Text on 1st Ride<br>
												<input type=radio name="sms_preferences" value='SUBSEQUENT' <?php echo $_POST['data']['PhoneNumber'][3]['sms_preferences'] == 'SUBSEQUENT' ? 'checked' : ''; ?>> Text on Subsequent Rides<br>
                    	</td>
                    </tr>                    
                    </table>
                  </div>
                  <div id="panel_emergency_contact" style="display:none;background-color:#bbbbbb;padding:5px;">
                    Enter emergency contact information.
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
                                    <td><input type="text" name="data[Emergency][Title]" maxlength="10" value="<?php if ($_POST['data']['Emergency']['Title']) { echo $_POST['data']['Emergency']['Title']; } ?>" style="width:50px;" /></td>
                                </tr>
                                <tr>
                                    <td class="alignright">*First Name</td>
                                    <td><input type="text" name="data[Emergency][FirstName]" maxlength="30" value="<?php if ($_POST['data']['Emergency']['FirstName']) { echo $_POST['data']['Emergency']['FirstName']; } ?>" style="width:200px;" /></td>
                                </tr>
                                <tr>
                                    <td class="alignright">Middle Initial</td>
                                    <td><input type="text" name="data[Emergency][MiddleInitial]" maxlength="1" value="<?php if ($_POST['data']['Emergency']['MiddleInitial']) { echo $_POST['data']['Emergency']['MiddleInitial']; } ?>" style="width:50px;" /></td>
                                </tr>
                                <tr>
                                    <td class="alignright">*Last Name</td>
                                    <td><input type="text" name="data[Emergency][LastName]" maxlength="30" value="<?php if ($_POST['data']['Emergency']['LastName']) { echo $_POST['data']['Emergency']['LastName']; } ?>" style="width:200px;" /></td>
                                </tr>
                                <tr>
                                    <td class="alignright">Suffix</td>
                                    <td><input type="text" name="data[Emergency][Suffix]" maxlength="10" value="<?php if ($_POST['data']['Emergency']['Suffix']) { echo $_POST['data']['Emergency']['Suffix']; } ?>" style="width:50px;" /></td>
                                </tr>
                            </table>
                            <b>Contacts Address:</b>
                            <?php
                                create_html_address_table('data[Emergency][', $_POST, true, ']');
                            ?>
                            <b>Contacts Phone Number:</b>
                            <table style="margin:auto;" id="phone1">
                            <tr>
                                <td>*Phone Number Type:</td>
                                <td>
                                    <select name="data[Emergency][PhoneType][0]">
                                        <option <?php if($_POST['data']['Emergency']['PhoneType'] == "HOME") echo 'SELECTED'; ?> value="HOME">Home</option>
                                        <option value="MOBILE" <?php if($_POST['data']['Emergency']['PhoneType'] == "MOBILE") echo 'SELECTED'; ?>>Mobile</option>
                                        <option value="WORK" <?php if($_POST['data']['Emergency']['PhoneType'] == "WORK") echo 'SELECTED'; ?>>Work</option>
                                        <option value="UNKNOWN" <?php if($_POST['data']['Emergency']['PhoneType'] == "UNKNOWN") echo 'SELECTED'; ?>>Unknown</option>
                                        <option value="OTHER" <?php if($_POST['data']['Emergency']['PhoneType'] == "OTHER") echo 'SELECTED'; ?>>Other</option>
                                    </select>
                                </td>
                            </tr>
                            <tr valign=top>
                                <td>*Phone Number</td>
                                <td>
                                	<input style='width:120px' type="text" name="data[Emergency][PhoneNumber][0]"  value="<?php if ($_POST['data']['Emergency']['PhoneNumber'][0]) { echo $_POST['data']['Emergency']['PhoneNumber'][0]; } ?>" maxlength="15" />
                                 x<input style='width:33px' type="text" name="data[Emergency][Ext][0]"  value="<?php if ($_POST['data']['Emergency']['Ext'][0]) { echo $_POST['data']['Emergency']['Ext'][0]; } ?>" maxlength="5" /><br>
                                 Name: <input style='width:120px' type="text" name="data[Emergency][PhoneDescription][0]"  value="<?php if ($_POST['data']['Emergency']['PhoneDescription'][0]) { echo $_POST['data']['Emergency']['PhoneDescription'][0]; } ?>" />
                                </td>
                            </tr>
                            </table>
                            <?php 
                              echo get_HTML_add_phone_Number_button(FALSE,TRUE);
                            ?>
                            <b>Contacts Email:</b>
                            <table style="margin:auto;">
                                <tr>
                                    <td>Email:</td>
                                    <td><input type="text" name="data[Emergency][Email]" value="<?php if ($_POST['data']['Emergency']['Email']) { echo $_POST['data']['Emergency']['Email']; } ?>" maxlength="60" /></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="alignright">*Contact Relation</td>
                        <td><input type="text" name="data[Emergency][EmergencyContactRelationship]" value="<?php if ($_POST['data']['Emergency']['EmergencyContactRelationship']) { echo $_POST['data']['Emergency']['EmergencyContactRelationship']; } ?>" style="width:200px;" maxlength="25" /></td>
                    </tr>
                    
                </table>
                  </div>
                  <div id="panel_driver_settings" style="display:none;background-color:#bbbbbb;padding:5px;">
                  <center><h2>Create Driver Settings</h2></center>
				<table style="margin:auto; width:550px;">
					<tr>
						<td style="width:220px;" class="alignright">Drive with a qualified felon in car:</td>
						<td>
							<select name="data[DriverSettings][FelonRiderOK]">
								<option value="Yes">Yes</option>
								<option value="No">No</option>
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Stay with the rider:</td>
						<td>
							<select name="data[DriverSettings][StayWithRider]">
								<option value="No">No</option>
								<option value="Yes">Yes</option>
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Will help rider with package:</td>
						<td>
							<select name="data[DriverSettings][WillHelpWithPackage]">
								<option value="No">No</option>
								<option value="Yes">Yes</option>
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Will help rider to car:</td>
						<td>
							<select name="data[DriverSettings][WillHelpToCar]">
								<option value="No">No</option>
								<option value="Yes">Yes</option>
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Sensitive to smells:</td>
						<td>
							<select name="data[DriverSettings][SensitiveToSmells]">
								<option value="No">No</option>
								<option value="Yes">Yes</option>
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Smoker or perfume user:</td>
						<td>
							<select name="data[DriverSettings][SmokerOrPerfumeUser]">
								<option value="No">No</option>
								<option value="Yes">Yes</option>
							</select>
							<td>No smoking during ride</td>
						</td>
					</tr>
					<tr>
						<td class="alignright">Memory Level Required:</td>
						<td>
							<select name="data[DriverSettings][MemoryLevelReq]">
								<option value="Full">Full</option>
								<option value="ML1">ML1</option>
								<option value="ML2">ML2</option>
							</select>
							<td>ML1 = Slight, ML2 = Severe</td>
						</td>
					</tr>
					<tr>
						<td class="alignright">Vision Level Required:</td>
						<td>
							<select name="data[DriverSettings][VisionLevelReq]">
								<option value="VL0">VL0</option>
								<option value="Part">Part</option>
								<option value="None">None</option>
								<option value="VL1">VL1</option>
								<option value="VL2">VL2</option>								
							</select>
							<td>Part= Partial, VL1= Cannot Correct to 20/20, VL2= Blind</td>
						</td>
					</tr>
										<tr>
						<td class="alignright">Hearing Level Required:</td>
						<td>
							<select name="data[DriverSettings][HearingLevelReq]">
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
							<select name="data[DriverSettings][ServiceDog]">
								<option value="No">No</option>
								<option value="Yes">Yes</option>
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Pet carrier:</td>
						<td>
							<select name="data[DriverSettings][PetCarrier]">
								<option value="No">No</option>
								<option value="Yes">Yes</option>
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Unaccompanied minor:</td>
						<td>
							<select name="data[DriverSettings][UnaccompaniedMinor]">
								<option value="No">No</option>
								<option value="Yes">Yes</option>
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright"> Contact preference: </td>
						<td>	
							<textarea name="data[DriverSettings][ContactPreference]" style="width:50px; height:16px;"></textarea>
						</td>
					</tr>
					<tr>
						<td colspan=2 align=center>(examples:  T=Text, M=Mobile, H=Home, W=Work, etc)</td>	
					</tr>
					<tr>
						<td class="alignright">Maximum hours you drive a week:</td>
						<td>
							<select name="data[DriverSettings][MaxHoursPerWeek]">
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
						<td colspan="2">
							Other notes you would like us to know:
							<textarea name="data[DriverSettings][OtherNotes]" style="width:350px; height:100px;"></textarea>
						</td>
					</tr>
					
				</table>
                  </div>
                  <div id="panel_driver_vehicle" style="display:none;background-color:#bbbbbb;padding:5px;">
                  <table style="margin:auto; width:550px;">
				<tr>
					<td class="alignright">Vehicle Year:</td>
					<td>
						<select name="data[Vehicle][VehicleYear]">
							<?php
								for($i = (int)Date("Y") + 2; $i > (int)Date("Y") - 30; $i--)
									echo '<option value="' . $i . '">' . $i . '</option>';
							?>
						</select>
					</td>
				</tr>
				<tr>
					<td class="alignright">Vehicle Make:</td>
					<td><input type="text" name="data[Vehicle][VehicleMake]" value="<?php echo $_POST['VehicleMake']; ?>" maxlength="20" /></td>
				</tr>
				<tr>
					<td class="alignright">Vehicle Model:</td>
					<td><input type="text" name="data[Vehicle][VehicleModel]" value="<?php echo $_POST['VehicleModel']; ?>" maxlength="20" /></td>
				</tr>
				<tr>
					<td class="alignright">Vehicle Color:</td>
					<td><input type="text" name="data[Vehicle][VehicleColor]" value="<?php echo $_POST['VehicleColor']; ?>" maxlength="15" /></td>
					</tr>
				<tr>
					<td class="alignright">Vehicle Description:</td>
					<td><input type="text" name="data[Vehicle][VehicleDescription]" value="<?php echo $_POST['VehicleDescription']; ?>" maxlength="50" /></td>
				</tr>
				<tr>
					<td class="alignright">License Plate State:</td>
					<td><input type="text" name="data[Vehicle][LicenseState]" value="<?php echo $_POST['LicenseState']; ?>" maxlength="2" /></td>
				</tr>
				<tr>
					<td class="alignright">License Plate Number:</td>
					<td><input type="text" name="data[Vehicle][LicenseNumber]" value="<?php echo $_POST['LicenseNumber']; ?>" maxlength="12" /></td>
				</tr>
				<tr>
					<td class="alignright">Vehicle Height:</td>
					<td>
						<select name="data[Vehicle][VehicleHeight]">
							<option value="HIGH"<?php if($_POST['VehicleHeight'] == "HIGH") echo ' SELECTED'; ?>>High</option>
							<option value="MEDIUM"<?php if($_POST['VehicleHeight'] == "MEDIUM") echo ' SELECTED'; ?>>Medium</option>
							<option value="LOW"<?php if($_POST['VehicleHeight'] == "LOW") echo ' SELECTED'; ?>>Low</option>
							<option value="UNKNOWN"<?php if($_POST['VehicleHeight'] == "UNKNOWN") echo ' SELECTED'; ?>>Unknown</option>
						</select>
					</td>
				</tr>
				<tr>
					<td class="alignright">Can Handle Cane:</td>
					<td>
						<select name="data[Vehicle][CanHandleCane]">
							<option value="No"<?php if($_POST['CanHandleCane'] == "No") echo ' SELECTED'; ?>>No</option>
							<option value="C1"<?php if($_POST['CanHandleCane'] == "C1") echo ' SELECTED'; ?>>C1</option>
							<option value="C2"<?php if($_POST['CanHandleCane'] == "C2") echo ' SELECTED'; ?>>C2</option>
						</select>
							<td>C2 = Quad Cane</td>
					</td>
				</tr>
				<tr>
					<td class="alignright">Can Handle Walker:</td>
					<td>
						<select name="data[Vehicle][CanHandleWalker]">
							<option value="No"<?php if($_POST['CanHandleWalker'] == "No") echo ' SELECTED'; ?>>No</option>
							<option value="W1"<?php if($_POST['CanHandleCane'] == "W1") echo ' SELECTED'; ?>>W1</option>
							<option value="W2"<?php if($_POST['CanHandleCane'] == "W2") echo ' SELECTED'; ?>>W2</option>
							<option value="W3"<?php if($_POST['CanHandleCane'] == "W3") echo ' SELECTED'; ?>>W3</option>
						</select>
							<td>W1 = Flat, W2 = handles, W3 = XL Walker</td>
					</td>
				</tr>
				<tr>
					<td class="alignright">Can Handle Wheelchair:</td>
					<td>
						<select name="data[Vehicle][Wheelchair]">
							<option value="No"<?php if($_POST['CanHandleWalker'] == "No") echo ' SELECTED'; ?>>No</option>
							<option value="WC1"<?php if($_POST['CanHandleCane'] == "WC1") echo ' SELECTED'; ?>>WC1</option>
							<option value="WC2"<?php if($_POST['CanHandleCane'] == "WC2") echo ' SELECTED'; ?>>WC2</option>
						</select>
							<td>WC1 = Transfer chair</td>
					</td>
				</tr>
				<tr>
					<td class="alignright">Has Driver Side Rear Door:</td>
					<td>
						<select name="data[Vehicle][HasDriverSideRearDoor]">
							<option value="No"<?php if($_POST['HasDriverSideRearDoor'] == "No") echo ' SELECTED'; ?>>No</option>
							<option value="Yes"<?php if($_POST['HasDriverSideRearDoor'] == "Yes") echo ' SELECTED'; ?>>Yes</option>
						</select>
							<td>WC2= Do not load WC2</td>
					</td>
				</tr>
				<tr>
					<td class="alignright">Has Pass. Side Rear Door:</td>
					<td>
						<select name="data[Vehicle][HasPassengerSideRearDoor]">
							<option value="No"<?php if($_POST['HasPassengerSideRearDoor'] == "No") echo ' SELECTED'; ?>>No</option>
							<option value="Yes"<?php if($_POST['HasPassengerSideRearDoor'] == "Yes") echo ' SELECTED'; ?>>Yes</option>
						</select>
					</td>
				</tr>
                <tr>
                	<td class="alignright">Max Passengers:</td>
					<td>
						<input name="data[Vehicle][MaxPassengers]" value="<?php echo $_POST['MaxPassengers']; ?>" maxlength="3" type="text"  />
					</td>
                </tr>
				
			</table>
            </div>
          </td>
        </tr>
        </table>
		</form>
        
        
<script language="javascript">showPanel(0);</script>
<?php
include_once 'include/footer.php';


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
	
?>