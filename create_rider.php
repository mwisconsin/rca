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
	
	
		if(isset($_POST['RiderStatus']) && isset($_POST['QualificationReason']))
		{
			//print_r($_POST);
	//exit;
	error_reporting(1);
	$required_fields = array();
			$required_filled = true;
			
			foreach($required_fields as $k => $v){
				if($_POST[$v] == '')
					$required_filled = false;
			}
			
			if(!$required_filled){
				$error = 'All required name fields were not filled.';
			} else {
				$rider = array('RiderStatus' => $_POST['RiderStatus'], 
									'RiderWaiverReceived' => ($_POST['RiderWaiverReceived'] != ''
																							? date('Y-m-d',strtotime($_POST['RiderWaiverReceived'])) : ""),
								 'EmergencyContactID' => 'NULL',
								 'EmergencyContactRelationship' => 'NULL',
								 'ADAQualified' => $_POST['ADA'],
								 'default_num_in_car' => $_POST['default_num_in_car'],
								 'QualificationReason' => $_POST['QualificationReason'],
								 'DateOfBirth' => ($_POST['DateOfBirth'] != ''
																							? date('Y-m-d',strtotime($_POST['DateOfBirth'])) : "") );
								
				$rider_id = add_rider($rider, $user_id);
				//print_r($_POST);
				// check for phone additional fields
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
				//print_r($_POST['data']);
				
				
				if(!$required_filled_e){
					//$error = 'All required fields were not filled.';
					//echo 'error found';
					
				} else {
				    //echo 'emergency';
					if (createEmergencyContact($user_id, $_POST['data']['Emergency'], true, 'rider')) {
					
					} 
				}
				//exit;
				/// rider prefs
				if(isset($_POST['data']['Preferences']))
				{
					createRiderPrefs($user_id, $_POST['data']['Preferences']);
				}
				
				create_rider_default_home($rider_id);
				$_GET["redirect"] = "/make_payment.php";
				redirect(TRUE);
			}
		} else if( $rider_info = get_user_rider_info($user_id) && $rider_info['UserID'] == $user_id ) {
			redirect();
		}
			include_once 'include/header.php';
			
			?>
			<center><h2>Create Rider</h2></center>
			<?php echo '<center>' . $error . '</center>'; ?>
			<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?field=createrider' . $edit_url; ?>">
            <table cellpadding="0" cellspacing="0">
            <tr>
              <td valign="top">
			<table style="margin:auto; width:400px;">
				<tr>
					<td class="alignright">Rider Status</td>
					<td>
						<select name="RiderStatus">
							<option value="Active"<?php if ($_POST['RiderStatus']=='Active') { echo ' selected="selected"'; } ?>>Active</option>
							<option value="Inactive"<?php if ($_POST['RiderStatus']=='Inactive') { echo ' selected="selected"'; } ?>>Inactive</option>
						</select>
					</td>
				</tr>
				<tr>
					<td align=right >Rider Waiver Date</td>
					<td><input name=RiderWaiverReceived class=jq_datepicker value="<?php echo @$_POST["RiderWaiverReceived"]; ?>" size=10></td>
				</tr>
				<tr>
					<td align=right>Birth Date</td>
					<td><input name=DateOfBirth class=jq_datepicker value="<?php echo date('m/d/Y',strtotime(@$_GET['b'])); ?>" size=10></td>
				</tr>
				<tr>
                	<td class="alignright">ADA Qualified</td>
                	<td>
                    	<select name="ADA">
                        	<option value="No"<?php if ($_POST['ADA']=='No') { echo ' selected="selected"'; } ?>>No</option>
                            <option value="Yes"<?php if ($_POST['ADA']=='Yes') { echo ' selected="selected"'; } ?>>Yes</option>
                        </select>
                    </td>
                </tr>
				<tr>
                	<td class="alignright">Default # in Car</td>
                	<td>
                    	<input type=text name=default_num_in_car value="<?php echo @$_POST["default_num_in_car"] == '' ? 1 : $_POST["default_num_in_car"]; ?>">"
                    </td>
                </tr>
				<tr>
					<td colspan="2"><br>
						Qualification Reason:<br>
						<textarea name="QualificationReason" style="width:400px; height:100px;"><?php if ($_POST['QualificationReason']) { echo $_POST['QualificationReason']; } ?></textarea>
					</td>
				</tr>
				<tr>
					<td class="alignright" colspan="2"><input type="submit" name="save" value="Save" /></td>
				</tr>
			</table>
              </td>
              <td style="width:20px;"></td>
              <td valign="top">
<script language="javascript">
var panels = Array('panel_phone_number','panel_emergency_contact','panel_rider_prefs');
var tabs   = Array('tab_phone_number', 'tab_emergency_contact', 'tab_rider_prefs');
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
                  <a href="#" onclick="showPanel(0);" class="tab_button" id="tab_phone_number">Phone Numbers</a><a href="#" onclick="showPanel(1);" class="tab_button" id="tab_emergency_contact">Emergency Contact</a><a href="#" onclick="showPanel(2);" class="tab_button" id="tab_rider_prefs">Vehicle Preferences</a> 
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
                    <input type="hidden" name="data[PhoneNumber][0][Ext]" />
                    
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
                          <option value="HOME"<?php if ($_POST['data']['PhoneNumber'][1]['PhoneType']=='HOME') { echo ' selected="selected"'; } ?>>Home</option>
                          <option value="MOBILE"<?php if ($_POST['data']['PhoneNumber'][1]['PhoneType']=='MOBILE') { echo ' selected="selected"'; } ?>>Mobile</option>
                          <option value="WORK"<?php if ($_POST['data']['PhoneNumber'][1]['PhoneType']=='WORK') { echo ' selected="selected"'; } ?>>Work</option>
                          <option value="FAX"<?php if ($_POST['data']['PhoneNumber'][1]['PhoneType']=='FAX') { echo ' selected="selected"'; } ?>>Fax</option>
                          <option value="OTHER"<?php if ($_POST['data']['PhoneNumber'][1]['PhoneType']=='OTHER') { echo ' selected="selected"'; } ?>>Other</option>
                        </select>
                      </td>
                      <td><input style='width:120px' name="data[PhoneNumber][1][PhoneNumber]" maxlength="15" type="text" value="<?php if ($_POST['data']['PhoneNumber'][1]['PhoneNumber']) { echo $_POST['data']['PhoneNumber'][1]['PhoneNumber']; } ?>">
                      	 x<input style='width:33px' name="data[PhoneNumber][1][Ext]" maxlength="5" type="text" value="<?php if ($_POST['data']['PhoneNumber'][1]['Ext']) { echo $_POST['data']['PhoneNumber'][1]['Ext']; } ?>">
                      	</td>
                      <td><input name="data[PhoneNumber][IsPrimary]" type="radio" value="1"<?php if ($_POST['data']['PhoneNumber']['IsPrimary']==1) { echo ' checked="checked"'; } ?> /></td>
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
                          <option value="HOME"<?php if ($_POST['data']['PhoneNumber'][2]['PhoneType']=='HOME') { echo ' selected="selected"'; } ?>>Home</option>
                          <option value="MOBILE"<?php if ($_POST['data']['PhoneNumber'][2]['PhoneType']=='MOBILE') { echo ' selected="selected"'; } ?>>Mobile</option>
                          <option value="WORK"<?php if ($_POST['data']['PhoneNumber'][2]['PhoneType']=='WORK') { echo ' selected="selected"'; } ?>>Work</option>
                          <option value="FAX"<?php if ($_POST['data']['PhoneNumber'][2]['PhoneType']=='FAX') { echo ' selected="selected"'; } ?>>Fax</option>
                          <option value="OTHER"<?php if ($_POST['data']['PhoneNumber'][2]['PhoneType']=='OTHER') { echo ' selected="selected"'; } ?>>Other</option>
                        </select>
                      </td>
                      <td><input style='width:120px' name="data[PhoneNumber][2][PhoneNumber]" maxlength="15" type="text" value="<?php if ($_POST['data']['PhoneNumber'][2]['PhoneNumber']) { echo $_POST['data']['PhoneNumber'][2]['PhoneNumber']; } ?>">
                      	 x<input style='width:33px' name="data[PhoneNumber][2][Ext]" maxlength="5" type="text" value="<?php if ($_POST['data']['PhoneNumber'][2]['Ext']) { echo $_POST['data']['PhoneNumber'][2]['Ext']; } ?>">
                      	</td>
                      <td><input name="data[PhoneNumber][IsPrimary]" type="radio" value="2"<?php if ($_POST['data']['PhoneNumber']['IsPrimary']==2) { echo ' checked="checked"'; } ?> /></td>
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
                          <option value="HOME"<?php if ($_POST['data']['PhoneNumber'][3]['PhoneType']=='HOME') { echo ' selected="selected"'; } ?>>Home</option>
                          <option value="MOBILE"<?php if ($_POST['data']['PhoneNumber'][3]['PhoneType']=='MOBILE') { echo ' selected="selected"'; } ?>>Mobile</option>
                          <option value="WORK"<?php if ($_POST['data']['PhoneNumber'][3]['PhoneType']=='WORK') { echo ' selected="selected"'; } ?>>Work</option>
                          <option value="FAX"<?php if ($_POST['data']['PhoneNumber'][3]['PhoneType']=='FAX') { echo ' selected="selected"'; } ?>>Fax</option>
                          <option value="OTHER"<?php if ($_POST['data']['PhoneNumber'][3]['PhoneType']=='OTHER') { echo ' selected="selected"'; } ?>>Other</option>
                        </select>
                      </td>
                      <td><input style='width:120px' name="data[PhoneNumber][3][PhoneNumber]" maxlength="15" type="text" value="<?php if ($_POST['data']['PhoneNumber'][3]['PhoneNumber']) { echo $_POST['data']['PhoneNumber'][3]['PhoneNumber']; } ?>">
                      	 x<input style='width:33px' name="data[PhoneNumber][3][Ext]" maxlength="5" type="text" value="<?php if ($_POST['data']['PhoneNumber'][3]['Ext']) { echo $_POST['data']['PhoneNumber'][3]['Ext']; } ?>">
                      	</td>
                      <td><input name="data[PhoneNumber][IsPrimary]" type="radio" value="3"<?php if ($_POST['data']['PhoneNumber']['IsPrimary']==3) { echo ' checked="checked"'; } ?> /></td>
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
                                    <td><input type="text" name="data[Emergency][Title]" maxlength="10"  value="<?php if ($_POST['data']['Emergency']['Title']) { echo $_POST['data']['Emergency']['Title']; } ?>" style="width:50px;" /></td>
                                </tr>
                                <tr>
                                    <td class="alignright">*First Name</td>
                                    <td><input type="text" name="data[Emergency][FirstName]" maxlength="30"  value="<?php if ($_POST['data']['Emergency']['FirstName']) { echo $_POST['data']['Emergency']['FirstName']; } ?>" style="width:200px;" /></td>
                                </tr>
                                <tr>
                                    <td class="alignright">Middle Initial</td>
                                    <td><input type="text" name="data[Emergency][MiddleInitial]" maxlength="1"  value="<?php if ($_POST['data']['Emergency']['MiddleInitial']) { echo $_POST['data']['Emergency']['MiddleInitial']; } ?>" style="width:50px;" /></td>
                                </tr>
                                <tr>
                                    <td class="alignright">*Last Name</td>
                                    <td><input type="text" name="data[Emergency][LastName]" maxlength="30"  value="<?php if ($_POST['data']['Emergency']['LastName']) { echo $_POST['data']['Emergency']['LastName']; } ?>" style="width:200px;" /></td>
                                </tr>
                                <tr>
                                    <td class="alignright">Suffix</td>
                                    <td><input type="text" name="data[Emergency][Suffix]" maxlength="10"  value="<?php if ($_POST['data']['Emergency']['Suffix']) { echo $_POST['data']['Emergency']['Suffix']; } ?>" style="width:50px;" /></td>
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
                                	<input style='width:120px' type="text" name="data[Emergency][PhoneNumber][0]"  value="<?php if ($_POST['data']['Emergency']['PhoneNumber'][0]) { echo $_POST['data']['Emergency']['PhoneNumber'][0]; } ?>" maxlength="15" />
                                 x<input style='width:33px' type="text" name="data[Emergency][Ext][0]"  value="<?php if ($_POST['data']['Emergency']['Ext'][0]) { echo $_POST['data']['Emergency']['Ext'][0]; } ?>" maxlength="5" /><br>
                                 Name: <input style='width:120px' type="text" name="data[Emergency][PhoneDescription][0]"  value="<?php if ($_POST['data']['Emergency']['PhoneDescription'][0]) { echo $_POST['data']['Emergency']['PhoneDescription'][0]; } ?>" />
                                </td>
                            </tr>
                            </table>
                            <?php 
                              echo get_HTML_add_phone_Number_button();
                            ?>
                            <b>Contacts Email:</b>
                            <table style="margin:auto;">
                                <tr>
                                    <td>Email:</td>
                                    <td><input type="text" name="data[Emergency][Email]"  value="<?php if ($_POST['data']['Emergency']['Email']) { echo $_POST['data']['Emergency']['Email']; } ?>" maxlength="60" /></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="alignright">*Contact Relation</td>
                        <td><input type="text" name="data[Emergency][EmergencyContactRelationship]"  value="<?php if ($_POST['data']['Emergency']['EmergencyContactRelationship']) { echo $_POST['data']['Emergency']['EmergencyContactRelationship']; } ?>" style="width:200px;" maxlength="25" /></td>
                    </tr>
                    
                </table>
                  </div>
                  <div id="panel_rider_prefs" style="display:none;background-color:#bbbbbb;padding:5px;">
                  
                    <table style="margin:auto; width:550px;">
					<tr>
						<td class="alignright">Qualified Felon Driver:</td>
						<td>
							<select name="data[Preferences][FelonDriverOK]">
								<option value="No"<?php if ($_POST['data']['Preferences']['FelonDriverOK']=='No') { echo ' selected="selected"'; } ?>>No</option>
								<option value="Yes"<?php if ($_POST['data']['Preferences']['FelonDriverOK']=='Yes') { echo ' selected="selected"'; } ?>>Yes</option>								
							</select>
							<td >No hurt or threat</td>
						</td>
					</tr>
					<tr>
						<td class="alignright" style="width:175px;">Get in High Vehicle:</td>
						<td>
							<select name="data[Preferences][HighVehicleOK]">
								<option value="Yes"<?php if ($_POST['data']['Preferences']['HighVehicleOK']=='Yes') { echo ' selected="selected"'; } ?>>Yes</option>
								<option value="No"<?php if ($_POST['data']['Preferences']['HighVehicleOK']=='No') { echo ' selected="selected"'; } ?>>No</option>
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Get in Medium Vehicle:</td>
						<td>
							<select name="data[Preferences][MediumVehicleOK]">
								<option value="Yes"<?php if ($_POST['data']['Preferences']['MediumVehicleOK']=='Yes') { echo ' selected="selected"'; } ?>>Yes</option>
								<option value="No"<?php if ($_POST['data']['Preferences']['MediumVehicleOK']=='No') { echo ' selected="selected"'; } ?>>No</option>
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Get in Low Vehicle:</td>
						<td>
							<select name="data[Preferences][LowVehicleOK]">
								<option value="Yes"<?php if ($_POST['data']['Preferences']['LowVehicleOK']=='Yes') { echo ' selected="selected"'; } ?>>Yes</option>
								<option value="No"<?php if ($_POST['data']['Preferences']['LowVehicleOK']=='No') { echo ' selected="selected"'; } ?>>No</option>
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Driver Stays:</td>
						<td>
							<select name="data[Preferences][DriverStays]">
								<option value="No"<?php if ($_POST['data']['Preferences']['DriverStays']=='No') { echo ' selected="selected"'; } ?>>No</option>
								<option value="Yes"<?php if ($_POST['data']['Preferences']['DriverStays']=='Yes') { echo ' selected="selected"'; } ?>>Yes</option>								
							</select>
							<td >At our discretion</td>
						</td>
					</tr>
					<tr>
						<td class="alignright">Has Cane:</td>
						<td>
							<select name="data[Preferences][HasCane]">
								<option value="No"<?php if ($_POST['data']['Preferences']['HasCane']=='No') { echo ' selected="selected"'; } ?>>No</option>
								<option value="C1"<?php if ($_POST['data']['Preferences']['HasCane']=='C1') { echo ' selected="selected"'; } ?>>C1</option>
								<option value="C2"<?php if ($_POST['data']['Preferences']['HasCane']=='C2') { echo ' selected="selected"'; } ?>>C2</option>
							</select>
							<td >C2 = Quad Cane</td>
						</td>
					</tr>
					<tr>
						<td class="alignright">Has Walker:</td>
						<td>
							<select name="data[Preferences][HasWalker]">
								<option value="No"<?php if ($_POST['data']['Preferences']['HasWalker']=='No') { echo ' selected="selected"'; } ?>>No</option>
								<option value="W1"<?php if ($_POST['data']['Preferences']['HasWalker']=='W1') { echo ' selected="selected"'; } ?>>W1</option>
								<option value="W2"<?php if ($_POST['data']['Preferences']['HasWalker']=='W2') { echo ' selected="selected"'; } ?>>W2</option>								
							</select>
							<td >W1 = folds flat</td>
						</td>
					</tr>
					<tr>
						<td class="alignright">Has Wheelchair:</td>
						<td>
							<select name="data[Preferences][HasWheelchair]">
								<option value="No"<?php if ($_POST['data']['Preferences']['HasWheelchair']=='No') { echo ' selected="selected"'; } ?>>No</option>
								<option value="WC1"<?php if ($_POST['data']['Preferences']['HasWheelchair']=='WC1') { echo ' selected="selected"'; } ?>>WC1</option>
								<option value="WC2"<?php if ($_POST['data']['Preferences']['HasWheelchair']=='WC2') { echo ' selected="selected"'; } ?>>WC2</option>								
							</select>
							<td >WC2 = Wheelchair, no load</td>
						</td>
					</tr>
					<tr>
						<td class="alignright">Needs Package Help:</td>
						<td>
							<select name="data[Preferences][NeedsPackageHelp]">
								<option value="No"<?php if ($_POST['data']['Preferences']['NeedsPackageHelp']=='No') { echo ' selected="selected"'; } ?>>No</option>
								<option value="Yes"<?php if ($_POST['data']['Preferences']['NeedsPackageHelp']=='Yes') { echo ' selected="selected"'; } ?>>Yes</option>								
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Needs Help To Car:</td>
						<td>
							<select name="data[Preferences][NeedsHelpToCar]">
								<option value="No"<?php if ($_POST['data']['Preferences']['NeedsHelpToCar']=='No') { echo ' selected="selected"'; } ?>>No</option>
								<option value="Yes"<?php if ($_POST['data']['Preferences']['NeedsHelpToCar']=='Yes') { echo ' selected="selected"'; } ?>>Yes</option>								
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Enter Driver Side:</td>
						<td>
							<select name="data[Preferences][EnterDriverSide]">
								<option value="Yes"<?php if ($_POST['data']['Preferences']['EnterDriverSide']=='Yes') { echo ' selected="selected"'; } ?>>Yes</option>
								<option value="No"<?php if ($_POST['data']['Preferences']['EnterDriverSide']=='No') { echo ' selected="selected"'; } ?>>No</option>							
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Enter Passenger Side:</td>
						<td>
							<select name="data[Preferences][EnterPassengerSide]">
								<option value="Yes"<?php if ($_POST['data']['Preferences']['EnterPassengerSide']=='Yes') { echo ' selected="selected"'; } ?>>Yes</option>
								<option value="No"<?php if ($_POST['data']['Preferences']['EnterPassengerSide']=='No') { echo ' selected="selected"'; } ?>>No</option>					
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Has Service Animal:</td>
						<td>
							<select name="data[Preferences][HasServiceAnimal]">
								<option value="Yes"<?php if ($_POST['data']['Preferences']['HasServiceAnimal']=='Yes') { echo ' selected="selected"'; } ?>>Yes</option>
								<option value="No"<?php if ($_POST['data']['Preferences']['HasServiceAnimal']=='No') { echo ' selected="selected"'; } ?>>No</option>					
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Has Small Pet In Carrier:</td>
						<td>
							<select name="data[Preferences][HasSmallPetInCarrier]">
								<option value="Yes"<?php if ($_POST['data']['Preferences']['HasSmallPetInCarrier']=='Yes') { echo ' selected="selected"'; } ?>>Yes</option>
								<option value="No"<?php if ($_POST['data']['Preferences']['HasSmallPetInCarrier']=='No') { echo ' selected="selected"'; } ?>>No</option>					
							</select>
						</td>
					</tr>
                    <tr>
						<td class="alignright">Sensitive to smells:</td>
						<td>
							<select name="data[Preferences][SensitiveToSmells]">
								<option value="No"<?php if ($_POST['data']['Preferences']['SensitiveToSmells']=='No') { echo ' selected="selected"'; } ?>>No</option>
								<option value="Yes"<?php if ($_POST['data']['Preferences']['SensitiveToSmells']=='Yes') { echo ' selected="selected"'; } ?>>Yes</option>								
							</select>
						</td>
					</tr>
					<tr>
						<td class="alignright">Smoker or perfume user:</td>
						<td>
							<select name="data[Preferences][SmokerOrPerfumeUser]">
								<option value="No"<?php if ($_POST['data']['Preferences']['SmokerOrPerfumeUser']=='No') { echo ' selected="selected"'; } ?>>No</option>
								<option value="Yes"<?php if ($_POST['data']['Preferences']['SmokerOrPerfumeUser']=='Yes') { echo ' selected="selected"'; } ?>>Yes</option>								
							</select>
							<td >No smoking during ride</td>
						</td>
					</tr>
					<tr>
						<td class="alignright">Has Memory Loss:</td>
						<td>
							<select name="data[Preferences][HasMemoryLoss]">
								<option value="No"<?php if ($_POST['data']['Preferences']['HasMemoryLoss']=='No') { echo ' selected="selected"'; } ?>>No</option>
								<option value="ML1"<?php if ($_POST['data']['Preferences']['HasMemoryLoss']=='ML1') { echo ' selected="selected"'; } ?>>ML1</option>
								<option value="ML2"<?php if ($_POST['data']['Preferences']['HasMemoryLoss']=='ML2') { echo ' selected="selected"'; } ?>>ML2</option>								
							</select>
							<td >ML1= Slight, ML2= Severe</td>
						</td>
					</tr>
					<tr>
						<td class="alignright">Vision Level:</td>
						<td>
							<select name="data[Preferences][VisionLevel]">
								<option value="VL0"<?php if ($_POST['data']['Preferences']['VisionLevel']=='VL0') { echo ' selected="selected"'; } ?>>VL0</option>
								<option value="Part"<?php if ($_POST['data']['Preferences']['VisionLevel']=='Part') { echo ' selected="selected"'; } ?>>Part</option>
								<option value="None"<?php if ($_POST['data']['Preferences']['VisionLevel']=='None') { echo ' selected="selected"'; } ?>>None</option>
								<option value="VL1"<?php if ($_POST['data']['Preferences']['VisionLevel']=='VL1') { echo ' selected="selected"'; } ?>>VL1</option>
								<option value="VL2"<?php if ($_POST['data']['Preferences']['VisionLevel']=='VL2') { echo ' selected="selected"'; } ?>>VL2</option>								
							</select>
							<td >Part= Partial, VL1= Cannot Correct to 20/20, VL2= Blind</td>
						</td>
					</tr>
					<tr>
						<td class="alignright">Hearing Level:</td>
						<td>
							<select name="data[Preferences][HearingLevel]">
								<option value="OK"<?php if ($_POST['data']['Preferences']['HearingLevel']=='OK') { echo ' selected="selected"'; } ?>>OK</option>
								<option value="HL1"<?php if ($_POST['data']['Preferences']['HearingLevel']=='HL1') { echo ' selected="selected"'; } ?>>HL1</option>
								<option value="HL2"<?php if ($_POST['data']['Preferences']['HearingLevel']=='HL2') { echo ' selected="selected"'; } ?>>HL2</option>
								<option value="HL3"<?php if ($_POST['data']['Preferences']['HearingLevel']=='HL3') { echo ' selected="selected"'; } ?>>HL3</option>				
							</select>
							<td >HL1= Some hearing loss, HL2= Asst. Device, HL3= Little or no hearing</td>
						</td>
					</tr>
					<tr>
						<td class="alignright">Has Caretaker:</td>
						<td>
							<select name="data[Preferences][HasCaretaker]">
								<option value="No"<?php if ($_POST['data']['Preferences']['HasCaretaker']=='No') { echo ' selected="selected"'; } ?>>No</option>
								<option value="Yes"<?php if ($_POST['data']['Preferences']['HasCaretaker']=='Yes') { echo ' selected="selected"'; } ?>>Yes</option>								
							</select>
						</td>
					</tr>
					<tr>
					   <td colspan="2"><hr></td>
					</tr>
					<tr>
						<td colspan="2">
							<b>Caretaker Info</b><br><span style="font-size:11px;">*only if you checked Yes to the last question.</span><br>
							<table>
								<tr>
									<td class="alignright" width="80px">Title</td>
									<td><input type="text" name="data[Preferences][Title]" maxlength="10"  value="<?php if ($_POST['data']['Preferences']['Title']) { echo $_POST['data']['Preferences']['Title']; } ?>" style="width:50px;" /></td>
								</tr>
								<tr>
									<td class="alignright">First Name</td>
									<td><input type="text" name="data[Preferences][FirstName]" maxlength="30"  value="<?php if ($_POST['data']['Preferences']['FirstName']) { echo $_POST['data']['Preferences']['FirstName']; } ?>" style="width:200px;" /></td>
								</tr>
								<tr>
									<td class="alignright">Middle Initial</td>
									<td><input type="text" name="data[Preferences][MiddleInitial]" maxlength="1"  value="<?php if ($_POST['data']['Preferences']['MiddleInitial']) { echo $_POST['data']['Preferences']['MiddleInitial']; } ?>" style="width:50px;" /></td>
								</tr>
								<tr>
									<td class="alignright">Last Name</td>
									<td><input type="text" name="data[Preferences][LastName]" maxlength="30"  value="<?php if ($_POST['data']['Preferences']['LastName']) { echo $_POST['data']['Preferences']['LastName']; } ?>" style="width:200px;" /></td>
								</tr>
								<tr>
									<td class="alignright">Suffix</td>
									<td><input type="text" name="data[Preferences][Suffix]" maxlength="10"  value="<?php if ($_POST['data']['Preferences']['Suffix']) { echo $_POST['data']['Preferences']['Suffix']; } ?>" /></td>
								</tr>
								<tr>
					               <td class="alignright">Caretaker Birthday</td>
					           <td>
					           <?php 
					               print_month_select("data[Preferences][CaretakerBirthMonth]");
					               echo " / ";
					               print_day_select("data[Preferences][CaretakerBirthDay]");
					               echo " / ";
					               print_year_select(date("Y") - 110, 92, "data[Preferences][CaretakerBirthYear]");
					           ?>
					           </td>
					       </tr>
					       <tr>
					           <td class="alignright">Background Checked</td>
					           <td>
					               <select name="data[Preferences][CaretakerBackgroundChecked]">
					                   <option value="Yes"<?php if ($_POST['data']['Preferences']['CaretakerBackgroundChecked']=='Yes') { echo ' selected="selected"'; } ?>>Yes</option>
					                   <option value="No"<?php if ($_POST['data']['Preferences']['CaretakerBackgroundChecked']=='No') { echo ' selected="selected"'; } ?>>No</option>
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
							<textarea style="width:350px; height:100px;" name="data[Preferences][OtherNotes]"><?php if ($_POST['data']['Preferences']['OtherNotes']) { echo $_POST['data']['Preferences']['OtherNotes']; } ?></textarea>
						</td>
					</tr>
					
				</table>
                </div>
                </div>
              
              </td>
            </tr>
            </table>
		</form>
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
<script language="javascript">showPanel(0);</script>