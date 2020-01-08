<?php
	include_once 'include/care_facility.php';
	include_once 'include/user.php';
	
	redirect_if_not_logged_in();
	
	include_once 'include/address.php';
	require_once 'include/franchise.php';
    if(isset($_GET['id']) && $_GET['id'] != '' ){
		$facility_id = $_GET['id'];
	} else {
		$facility_id = get_first_user_care_facility( get_current_user_id() );
	}
	if(!is_real_care_facility($facility_id))
		header("location: " . site_url() . 'home.php');
	$franchise = get_current_user_franchise();
	if((!current_user_has_role(1, 'FullAdmin') && !current_user_has_role($franchise,'Franchisee')) && !if_current_user_has_care_facility( $facility_id )){
		header("location: " . site_url() . 'home.php');
		die();
	}
	$facility = get_care_facility( $facility_id );
	
	function check_username_in_use( $username ){
		$sql = "SELECT `UserName` FROM `users` WHERE `UserName` = '$username' LIMIT 1";
		$result = mysql_query($sql) or die(mysql_error() . ' | ' . $sql);
		if(mysql_num_rows($result) < 1)
			return FALSE;
		return TRUE;
	}
	
	if(isset($_POST['FirstName'])){
		$required_fields = array('FirstName','LastName','Address1','City','State','Zip5');
		$required_filled = true;

		foreach($required_fields as $k => $v){
			if($_POST[$v] == '')
				$required_filled = false;
		}

		if(!$required_filled){
			$error = 'All required name fields were not filled.';
		} else {
			//create address
			$rider_address = array('Address1' => $_POST['Address1'],
								   'Address2' => $_POST['Address2'],
								   'City' => $_POST['City'],
								   'State' => $_POST['State'],
								   'ZIP5' => $_POST['Zip5'],
								   'ZIP4' => $_POST['Zip4']);
			$rider_address = add_address($rider_address);
			//email
			$rider_email = get_email_address($facility['DefaultEmailID']);
			$rider_email = add_email_address($rider_email['EmailAddress']);
			//name
			$person_name_id = add_person_name( $_POST['Title'],$_POST['FirstName'],$_POST['MiddleInitial'],$_POST['LastName'],$_POST['Suffix'] );
			//create user
			$user_name = strtolower($facility['CareFacilityName'] . '_' . substr($_POST['FirstName'], 0,1) . $_POST['LastName']);
			$postfix = 1;
			while(check_username_in_use( $user_name ) == TRUE){
				$user_name = strtolower($facility['CareFacilityName'] . '_' . substr($_POST['FirstName'], 0,1) . $_POST['LastName']) . $postfix;
				$postfix++;
			}
            $user_id = add_user( $user_name, mt_rand(1001,mt_getrandmax()) . mt_rand(1001,mt_getrandmax()), 'ACTIVE',
								 $rider_email, $person_name_id, $_POST['Felony']);
			if($_POST['PhoneNumber'] != '')
				add_phone_number_for_user(
					$_POST['PhoneNumber'],$_POST['PhoneNumberType'],$user_id,
						$_POST['PhoneNumberType'] == 'MOBILE' ? $_POST['PhoneCanSMS'] : 'N',
						$_POST['PhoneNumberType'] == 'MOBILE' ? $_POST['PhoneMobileCarrier'] : 0,
						$_POST['PhoneNumberExt'],
						$_POST['PhoneNumberType'] == 'MOBILE' ? $_POST['sms_preferences'] : "FIRST"
						, $_POST["phonedescription"]
				);
			//create rider
			$birthday = mysql_real_escape_string($_POST['BirthYear']) . '-' . mysql_real_escape_string($_POST['BirthMonth']) . '-' . mysql_real_escape_string($_POST['BirthDay']);
			$rider = array('RiderStatus' => 'NotApproved',
						   'FranchiseID' => $facility['FranchiseID'],
						   'EmergencyContactID' => 'NULL',
						   'EmergencyContactRelationship' => 'NULL',
						   'QualificationReason' => 'Living in care facility',
						   'DateOfBirth' => $birthday);
			$rider_id = add_rider($rider, $user_id);
			//link address to user
            link_address_to_user($rider_address, 'Physical', $user_id);
			//create background check
			$query = "INSERT INTO `background_check_alias` (`AliasID`, `UserID`, `Alias`)
						VALUES (NULL, '" . mysql_real_escape_string($user_id) . "', '" . mysql_real_escape_string($_POST['Aliases']) . "');";
			mysql_query($query) or die(mysql_error());
			//create user role
			$query = "INSERT INTO `user_role` (`UserID`, `Role`, `FranchiseID`)
					  VALUES ('" . mysql_real_escape_string($user_id) . "', 'Rider', '".$facility['FranchiseID']."');";
			mysql_query($query) or die(mysql_error());
			
			connect_user_to_care_facility($user_id,$facility_id);
			
			header("location: " . site_url() . 'care_facility_users.php?id=' . $facility_id);
		}
	}

	
	$facility_address = get_address( $facility['FacilityAddressID'] );
	$address = array('Address1' => $facility_address['Address1'],
					 'City' => $facility_address['City'],
					 'State' => $facility_address['State'],
					 'ZIP5' => $facility_address['ZIP5'],
					 'ZIP4' => $facility_address['ZIP4']);
	include_once 'include/header.php';
	display_care_facility_header( $facility_id );
?>
<script>
jQuery(function($) {
	$('select[name="PhoneNumberType"]').on('change load',function() {
		if($(this).val() == 'MOBILE') $('#PhoneNumberMobile').show();
		else $('#PhoneNumberMobile').hide();
	});
});	
	
</script>
<center><h2>Facility Rider Application</h2></center>
<center><?php echo $error; ?></center>
<form action="<?php echo $_SERVER['PHP_SELF'] . '?id=' . $facility_id; ?>" method="post">
	<table style="margin:auto;">
		<tr>
			<td colspan="2"><b>Name</b></td>
		</tr>
		<tr>
			<td colspan="2">
				<table style="width:450px;">
					<tr>
						<td class="textAlign">Prefix</td>
						<td><input type="text" name="Title" maxlength="10" style="width:40px;"/></td>
					</tr>
					<tr>
						<td class="textAlign">*First</td>
						<td><input type="text" id="FirstName" name="FirstName" maxlength="30" style="width:150px;"/></td>
					</tr>
					<tr>
						<td class="textAlign">Middle</td>
						<td><input type="text" name="MiddleInitial" maxlength="1" style="width:30px;"/></td>
					</tr>
					<tr>
						<td class="textAlign">*Last</td>
						<td><input type="text" id="LastName" name="LastName" maxlength="30" style="width:150px;"/></td>
					</tr>
					<tr>
						<td class="textAlign">Suffix</td>
						<td><input type="text" name="Suffix" maxlength="10" style="width:40px;" /></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td colspan="2"><b>Physical Address</b></td>
		</tr>
		<tr>
			<td colspan="2">
				<?php create_html_address_table(NULL, $address); ?>
			</td>
		</tr>
		<tr>
			<td style="font-weight:bold; " colspan="2">Phone Number</td>
		</tr>
		<tr valign=top>
			<td width=106>Number/Type</td>
			<td nowrap><input type="text" name="PhoneNumber" value="<?php echo $_POST['PhoneNumber']; ?>" style="width:160px;" />
					<input type="text" placeholder="Extension" name="PhoneNumberExt" value="<?php echo $_POST['PhoneNumberExt']; ?>" style="width: 66px;" />
					<select style='height: 21px;' size=1 name="PhoneNumberType">
						<option <?php echo $_POST['PhoneNumberType'] == 'HOME' ? 'selected' : ''; ?>>HOME</option>	
						<option <?php echo $_POST['PhoneNumberType'] == 'MOBILE' ? 'selected' : ''; ?>>MOBILE</option>
						<option <?php echo $_POST['PhoneNumberType'] == 'WORK' ? 'selected' : ''; ?>>WORK</option>
						<option <?php echo $_POST['PhoneNumberType'] == 'FAX' ? 'selected' : ''; ?>>FAX</option>
						<option <?php echo $_POST['PhoneNumberType'] == 'OTHER' ? 'selected' : ''; ?>>OTHER</option>
					</select><br>
					<div id=PhoneNumberMobile style='padding-left: 20px; display: none;'>
						Can Accept Texts? <select size=1 name="PhoneCanSMS">
							<option <?php echo $_POST['PhoneCanSMS'] == 'Y' ? 'selected' : ''; ?>>Y</option>
							<option <?php echo $_POST['PhoneCanSMS'] == 'N' ? 'selected' : ''; ?>>N</option>
							</select><BR>
						Carrier: <select size=1 name="PhoneMobileCarrier" style="width: 160px;">
						<?php
						$sql = "select id, name from sms_providers order by name";
						$r = mysql_query($sql);
						while($rs = mysql_fetch_array($r))
							echo "<option value=$rs[id] ".($rs["id"] == $_POST["PhoneMobileCarrier"] ? "selected" : "").">$rs[name]</option>";
						?></select><br>
						Preference:<BR>
						<input type=radio name="sms_preferences" value='FIRST' <?php echo $_POST["sms_preferences"] == "FIRST" ? "checked" : ""; ?>> Text on 1st Ride<br>
						<input type=radio name="sms_preferences" value='SUBSEQUENT' <?php echo $_POST["sms_preferences"] == "SUBSEQUENT" ? "checked" : ""; ?>> Text on Subsequent Rides	
						
					</div>
			</td>
		</tr>
		<tr>
			<td>Description</td>
			<td><input type=text name=phonedescription size=30></td>
		</tr>
		<tr>
			<td colspan="2"><b>Birthday</b> <b style='color: red;'>*</b></td>
		</tr>
		<tr>
			<td></td>
			<td>
				<select name="BirthMonth">
					<option value="1">January</option>
					<option value="2">February</option>
					<option value="3">March</option>
					<option value="4">April</option>
					<option value="5">May</option>
					<option value="6">June</option>
					<option value="7">July</option>
					<option value="8">August</option>
					<option value="9">September</option>
					<option value="10">October</option>
					<option value="11">November</option>
					<option value="12">December</option>
				</select> / 
				<select name="BirthDay">
					<?php
						for($i = 1; $i <= 31; $i++)
							echo '<option value="' . $i . '">' . $i . '</option>';
					?>
				</select> / 
				<select name="BirthYear">
					<?php
						for($i = (int)date("Y") - 5; $i >= (int)date("Y")- 115; $i--)
							echo '<option value="' . $i . '">' . $i . '</option>';
					?>
				</select>
			</td>
		</tr>
		<tr>
			<td colspan="2"><b>Background Check</b></td>
		</tr>
		<tr>
			<td colspan="2">
				Any/All Aliases or Other Names:<br><br>
				<input type="text" maxlength="50" name="Aliases" style="width:320px; margin:0px 0px 10px 60px;"><br>
				Have You Ever Commited a Felony? <b style='color: red;'>*</b><br>
				<input type="radio" name="Felony" value="Yes" style="margin-left:60px;">Yes<input type="radio" name="Felony" value="No" style="margin-left:20px;">No
			</td>
		</tr>
		<tr>
			<td class="alignright" colspan="2"><input type="submit" name="Save" value="Submit"></td>
		</tr>
		<tr>
			<td colspan=2 style='margin-top: 50px;'><b style='color: red;'>*</b> = Required Field</td>
		</tr>
	</table>
</form>
<?php
	include_once 'include/footer.php';
?>
