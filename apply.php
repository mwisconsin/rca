<?php
    require_once('include/address.php');
    require_once('include/rider.php');
    require_once('include/driver.php');
    require_once('include/functions.php');
    require_once('include/phone.php');
    require_once('include/email.php');
    require_once('include/user.php');
    require_once('include/name.php');
    require_once('include/franchise.php');
    
	function create_phone_numbers($user_id) {
        if ($_POST['MobilePhone'] != '') {
            add_phone_number_for_user($_POST['MobilePhone'], 'MOBILE', $user_id); 
        } 
        if ($_POST['WorkPhone'] != '') {
            add_phone_number_for_user($_POST['WorkPhone'], 'WORK', $user_id); 
        } 
        if ($_POST['HomePhone'] != '') {
            add_phone_number_for_user($_POST['HomePhone'], 'HOME', $user_id); 
        }
    }
	
	function verify_email_address( $name_id, $email_id){
		 $email = get_email_address($email_id);
		 $name = get_name($name_id);
         $nice_name = get_displayable_person_name_string($name);
		 $link = site_url() . "verify_email_address.php?id=$email_id&hash=" . sha1('Verify' . $email['EmailID'] . $email['EmailAddress'] . $email['IsVerified']);


         $mail_text = <<<MAIL
Dear {$nice_name},
     
Please help us verify your email address by following the link below.  Your email address, {$email['EmailAddress']}, was listed on an application.  If you applied to Riders Club of America, and this is your correct email address, please follow the link below.
    
$link

Riders Club of America

If you have further questions about our service, please contact the office at 319.365.1511.  We have staff available Monday through Friday, between 9:00 a.m. and 3:00 p.m.

Thank you.
MAIL;


		 mail($email['EmailAddress'], "Riders Club of America - Email Verification", $mail_text, DEFAULT_EMAIL_FROM);
	}
	
	function check_username_availible($username){
		$safe_username = mysql_real_escape_string($username);
		
		$query = "SELECT COUNT(*) FROM `users` WHERE `UserName` = '$safe_username' LIMIT 1;";
		$result = mysql_fetch_array( mysql_query($query) );
		if($result[0] > 0)
			return FALSE;
		return TRUE;
	}
	
    function create_person_name($field_prefix) {
        $person_name_id = add_person_name( $_POST[$field_prefix . 'Title'],
                                           $_POST[$field_prefix . 'FirstName'],
                                           $_POST[$field_prefix . 'MiddleInitial'],
                                           $_POST[$field_prefix . 'LastName'],
                                           $_POST[$field_prefix . 'Suffix'] );
        return $person_name_id;
    }
	if(isset($_POST['Email'])){
		if($_POST['Email'] != '' && check_username_availible(strtolower($_POST['Email']))){
			$username = strtolower($_POST['Email']);
		} else if(check_username_availible(strtolower($_POST['FirstName'] . $_POST['LastName']))){
			$username = strtolower($_POST['FirstName'] . $_POST['LastName']);
		} else {
			$number = 1;
			while(check_username_availible(strtolower($_POST['FirstName'] . $_POST['LastName'] . $number)) == FALSE)
				$number++;
			$username = strtolower($_POST['FirstName'] . $_POST['LastName'] . $number);
		}
		if($_POST['UserRole'] == 'Rider'){
            $franchise_id = get_franchise_by_zip($_POST['Zip5']); // TODO:  if no franchise found, do something intelligent
            $franchise_id = ($franchise_id) ? $franchise_id : 1;

			//create address
			$rider_address = array('Address1' => $_POST['Address1'],
								   'Address2' => $_POST['Address2'],
								   'City' => $_POST['City'],
								   'State' => $_POST['State'],
								   'ZIP5' => $_POST['Zip5'],
								   'ZIP4' => $_POST['Zip4']);
			$rider_address = add_address($rider_address);
			//create name
            $rider_name = create_person_name('');
			//create email
            $rider_email = ($_POST['Email'] != '') ? add_email_address($_POST['Email']) : NULL;
			//create user
            $user_id = add_user( $username, strtolower(mt_rand(1001,mt_getrandmax()) . mt_rand(1001,mt_getrandmax())), 'ACTIVE',
                                 $rider_email, $rider_name, $_POST['Felony'], 'APPLIED');
			//create rider
			$birthday = mysql_real_escape_string($_POST['BirthYear']) . '-' . mysql_real_escape_string($_POST['BirthMonth']) . '-' . mysql_real_escape_string($_POST['BirthDay']);
			$rider = array('RiderStatus' => 'NotApproved',
						   'EmergencyContactID' => 'NULL',
						   'EmergencyContactRelationship' => 'NULL',
						   'QualificationReason' => $_POST['Qualification'],
						   'DateOfBirth' => $birthday);
			$rider_id = add_rider($rider, $user_id);
			//link address to user
            link_address_to_user($rider_address, 'Physical', $user_id);

			//create phone numbers
            create_phone_numbers($user_id);
			
			//create background check
			$query = "INSERT INTO `background_check_alias` (`AliasID`, `UserID`, `Alias`)
						VALUES (NULL, '" . mysql_real_escape_string($user_id) . "', '" . mysql_real_escape_string($_POST['Aliases']) . "');";
			mysql_query($query) or die(mysql_error());
			//create user role
			add_franchise_role($user_id, $franchise_id, 'Rider');
			
            if (!is_null($rider_email)) {
                verify_email_address($rider_name,$rider_email);
            }
			
			header("location: " . site_url() . "apply_finish.php");
		} else if($_POST['UserRole'] == 'Driver'){
            $franchise_id = get_franchise_by_zip($_POST['Zip5']); // TODO:  if no franchise found, do something intelligent
            $franchise_id = ($franchise_id) ? $franchise_id : 1;

			//create address
			$driver_address = array('Address1' => $_POST['Address1'],
								    'Address2' => $_POST['Address2'],
								    'City' => $_POST['City'],
								    'State' => $_POST['State'],
								    'ZIP5' => $_POST['Zip5'],
								    'ZIP4' => $_POST['Zip4']);
			$driver_address = add_address($driver_address);
			//create name
            $driver_name = create_person_name('');

			//create email
            $driver_email = ($_POST['Email'] != '') ? add_email_address($_POST['Email']) : NULL;

			//create user
            $user_id = add_user( $username, strtolower(mt_rand(1001,mt_getrandmax()) . mt_rand(1001,mt_getrandmax())), 'ACTIVE',
                                 $driver_email, $driver_name, $_POST['Felony'], 'APPLIED');
			//create driver
            $safe_user_id = mysql_real_escape_string($user_id);
			$birthday = mysql_real_escape_string($_POST['BirthYear']) . '-' . mysql_real_escape_string($_POST['BirthMonth']) . '-' . mysql_real_escape_string($_POST['BirthDay']);
			$query = "INSERT INTO `driver` (`UserID`, `DriverStatus`, `EmergencyContactID`, `EmergencyContactRelationship`, `LicenseState`, `LicenseNumber`, `DateOfBirth`)
			VALUES ($safe_user_id, 'NotApproved', NULL, NULL, '" . mysql_real_escape_string($_POST['LicenseState']) . "', '" . mysql_real_escape_string($_POST['LicenseNumber']) . "', '" . $birthday . "');";
            $create_driver_result = mysql_query($query);
            if (!$create_driver_result) {
                rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error creating driver record for user $safe_user_id", $query);
                die('A database error occurred.  Please call your local franchise for help.');
                exit;
            }
			$driver_id = mysql_insert_id();

			//link address to user
            link_address_to_user($driver_address, 'Physical', $user_id);
			
			//create phone numbers
            create_phone_numbers($user_id);

			//create background check
			$query = "INSERT INTO `background_check_alias` (`AliasID`, `UserID`, `Alias`)
						VALUES (NULL, '" . mysql_real_escape_string($user_id) . "', '" . mysql_real_escape_string($_POST['Aliases']) . "');";
			mysql_query($query) or die(mysql_error());
			//create user role
			add_franchise_role($user_id, $franchise_id, 'Driver');
			
			verify_email_address($driver_name,$driver_email);
			
			header("location: " . site_url() . "apply_finish.php");
		} else if($_POST['UserRole'] == 'Franchisee'){
			//create name
            $franchisee_name = create_person_name('');

			//create email
            $franchisee_email = ($_POST['Email'] != '') ? add_email_address($_POST['Email']) : NULL;

			//create user
            $user_id = add_user( $username, strtolower(mt_rand(1001,mt_getrandmax()) . mt_rand(1001,mt_getrandmax())), 'ACTIVE',
                                 $franchisee_email, $franchisee_name, $_POST['Felony'], 'APPLIED');
			//create address
			$franchisee_address = array('Address1' => $_POST['Address1'],
								   		'Address2' => $_POST['Address2'],
								   		'City' => $_POST['City'],
								   		'State' => $_POST['State'],
								   		'ZIP5' => $_POST['Zip5'],
								   		'ZIP4' => $_POST['Zip4']);
			$franchisee_address = add_address($franchisee_address);
			link_address_to_user($franchisee_address, 'Physical', $user_id);
			//create phone numbers
            create_phone_numbers($user_id);

			//create user role
			add_franchise_role($user_id, $franchise_id, 'Franchisee');
			//create background check
			$query = "INSERT INTO `background_check_alias` (`AliasID`, `UserID`, `Alias`)
						VALUES (NULL, '" . mysql_real_escape_string($user_id) . "', '" . mysql_real_escape_string($_POST['Aliases']) . "');";
			mysql_query($query) or die(mysql_error());
			
			verify_email_address($franchisee_name,$franchisee_email);
			
			header("location: " . site_url() . "apply_finish.php");
		} else if($_POST['UserRole'] == 'Supporter'){
			//create name
            $supporter_name = create_person_name('');

			//create email
            $supporter_email = ($_POST['Email'] != '') ? add_email_address($_POST['Email']) : NULL;

			//create user
            $user_id = add_user( $username, strtolower(mt_rand(1001,mt_getrandmax()) . mt_rand(1001,mt_getrandmax())), 'ACTIVE',
                                 $supporter_email, $supporter_name, $_POST['Felony'], 'APPROVED', 'CHECKED');
			
			//create phone numbers
            create_phone_numbers($user_id);
			
			//create address
			$supporter_address = array('Address1' => $_POST['Address1'],
								  	   'Address2' => $_POST['Address2'],
								       'City' => $_POST['City'],
								       'State' => $_POST['State'],
								       'ZIP5' => $_POST['Zip5'],
								       'ZIP4' => $_POST['Zip4']);
			$supporter_address = add_address($supporter_address);
			link_address_to_user($supporter_address, 'Physical', $user_id);

			//create user role
			add_franchise_role($user_id,$franchise_id, 'Supporter');
			
			//create background check
			$query = "INSERT INTO `background_check_alias` (`AliasID`, `UserID`, `Alias`)
						VALUES (NULL, '" . mysql_real_escape_string($user_id) . "', '" . mysql_real_escape_string($_POST['Aliases']) . "');";
			mysql_query($query) or die(mysql_error());
			
			verify_email_address($supporter_name,$supporter_email);
			
			header("location: " . site_url() . "apply_finish.php");
		}
	}
	include_once 'include/header.php';
?>
<form id="application" name="application" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
	<div style="float:right; width:450px; border:solid 1px #000; margin:0px 30px 0px 15px; padding:2px; position:relative;">
		<?php echo '<b><center>' . $error . '</center></b>'; ?>
		<span style="font-size:1.5em;"> I would like to apply as a:</span><br>
		<ul>
			<li class="type"><input type="radio" id="RiderSelect" name="UserRole" value="Rider" checked/> Rider</li>
			<li class="type"><input type="radio" id="DriverSelect" name="UserRole" value="Driver" /> Driver</li>
			<li class="type"><input type="radio" id="SupporterSelect" name="UserRole" value="Supporter" /> Supporting Friend</li>
			<li class="type"><input type="radio" id="FranchiseeSelect" name="UserRole" value="Franchisee" /> Start a Club</li>
		</ul>
		<div id="errors"></div>
		<div id="Step1">
			<div id="DOB">
				*Birthday:
				<ul>
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
							{
								echo '<option value="' . $i . '">' . $i . '</option>';
							}
						?>
					</select> / 
					<select name="BirthYear">
						<?php
							for($i = (int)date("Y") - 16; $i >= (int)date("Y")- 109; $i--)
							{
								echo '<option value="' . $i . '">' . $i . '</option>';
							}
						?>
					</select>
				</ul>
			</div>
			<div id="Qualification">
				*How do you Qualify?
				<ul>
					<li class="type"><input type="radio" name="Qualification" value="Age" /> Age (55+)</li>
					<li class="type"><input type="radio" name="Qualification" value="Medical Condition" /> Medical Condition</li>
				</ul>
			</div>
			Name:
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
			Physical Address
			<?php
				create_html_address_table();
			?>
			<input type="button" value="Next" onclick="check1();">
		</div>
		<div id="Step2">
			*Phone Numbers:
				<table style="width:450px;">
					<tr>
						<td class="textAlign">Mobile</td>
						<td><input id="MobilePhone" name="MobilePhone" type="text" maxlength="15" style="width:150px;" /></td>
					</tr>
					<tr>
						<td class="textAlign">Work</td>
						<td><input id="WorkPhone" name="WorkPhone" type="text" maxlength="15" style="width:150px;" /></td>
					</tr>
					<tr>
						<td class="textAlign">Home</td>
						<td><input name="HomePhone" id="HomePhone" type="text" maxlength="15" style="width:150px;" /></td>
					</tr>
				</table>
			Email:
				<table style="width:275px;">
					<tr>
						<td class="textAlign">Email Address</td>
						<td><input type="text" id="Email" name="Email" maxlength="60" style="width:150px;" /></td>
					</tr>
				</table>
			Background Information:<br><br>
			Any/All Aliases or Other Names:
			<ul>
				<input type="text" id="Aliases" name="Aliases" maxlength="50" style="width:350px;">
			</ul>
			*Have you ever been convicted of a Felony?
			<ul>
				<li class="type"><input type="radio" id="FelonyYes" name="Felony" value="Yes">Yes</li>
				<li class="type"><input id="FelonyNo" type="radio" name="Felony" value="No">No</li>
			</ul>
			<div id="driverlicense">
				Driver License Information:<br>
				<table style="width:330px;">
					<tr>
						<td class="textAlign">*License State</td>
						<td>
							<?php
								get_state_dropdown('License');
							?>
	                    </td>
					</tr>
					<tr>
						<td class="textAlign">*License Number</td>
						<td><input type="text" id="LicenseNumber" name="LicenseNumber" maxlength="15" style="width:150px;"></td>
					</tr>
					<tr>
						<td class="textAlign">*Expiration Date</td>
						<td>
							<select name="ExpirationMonth">
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
							<select name="ExpirationDay">
								<?php
									for($i = 1; $i <= 32; $i++)
									{
										echo '<option value="' . $i . '">' . $i . '</option>';
									}
								?>
							</select> / 
							<select name="ExpirationYear">
								<?php
									for($i = (int)date("Y") + 8; $i >= (int)date("Y")- 1; $i--)
									{
										echo '<option value="' . $i . '">' . $i . '</option>';
									}
								?>
							</select>
						</td>
					</tr>
				</table>
			</div>
			<input type="button" value="Finish" onclick="check2();">
		</div>
	</div>
</form>

<h2 id="header"><center>Help us to know about you!</center></h2>
<span id="text">
	<p>
		Whether you are a rider, a driver, or a supporting friend, we need to know about you.
	</p>
	<p>
		Please select the role for which you are applying, fill in any number you would like us to be able to contact you by, and press the "next" button to go on.
	</p>
</span>

<script type="text/javascript">
	window.addEvent('domready',function(){
		$('Step2').setStyle('display','none');
		
		user_roles = ['RiderSelect','DriverSelect','FranchiseeSelect','SupporterSelect'];
		
		// Default to RiderSelect
		$('DOB').setStyle('display','block');
		$('Qualification').setStyle('display','block');
		$('driverlicense').setStyle('display','none');		
		
		user_roles.each(function(item,index){
			$(item).addEvent('click',function(){
				$('Step1').setStyle('display','block');
				$('Step2').setStyle('display','none');
				$('errors').innerHTML ="";
				if(this.id == 'RiderSelect'){
					$('DOB').setStyle('display','block');
					$('Qualification').setStyle('display','block');
					$('driverlicense').setStyle('display','none');
				} if(this.id == 'DriverSelect'){
					$('DOB').setStyle('display','block');
					$('Qualification').setStyle('display','none');
					$('driverlicense').setStyle('display','block');
				} if(this.id == 'FranchiseeSelect'){
					$('DOB').setStyle('display','block');
					$('Qualification').setStyle('display','none');
					$('driverlicense').setStyle('display','block');
				} if(this.id == 'SupporterSelect'){
					$('DOB').setStyle('display','none');
					$('Qualification').setStyle('display','none');
					$('driverlicense').setStyle('display','none');
				}
			});
		});
	});
	function radio_set(radio_array){
		for(i =0; i < radio_array.length; i++){
			if (radio_array[i].checked)
				return true;
		}
		return false;
	}
	function check1(){
		fields = ['FirstName','LastName','Address1','City','State','Zip5'];
		$('errors').store('errors', false);
		fields.each(function(item,index){
			if($(item).value == ''){
				$('errors').store('errors', true);
				$('errors').innerHTML = "<center><b>You are missing required name or address fields.</b></center>";
			}
		});
		if(radio_set(document.application.Qualification) == false && document.application.UserRole[0].checked){
			$('errors').store('errors', true);
			$('errors').innerHTML = "<center><b>You need to select why you qualify.</b></center>";
		}
		if(radio_set(document.application.UserRole) == false){
			$('errors').store('errors', true);
			$('errors').innerHTML = "<center><b>You need to select a role to apply as.</b></center>";
		}	
		if($('errors').retrieve('errors') == false){
			$('errors').innerHTML = "";
			$('Step1').setStyle('display','none');
			$('Step2').setStyle('display','block');
			$('text').innerHTML = '<p>We will need at least 1 phone number to contact you with.</p><p>Your email address allows us to let you know when we have completed the background check so we can move forward in the process.</p>'
		}
	}
	function check2(){
		$('errors').store('errors', false);
		if(radio_set(document.application.Felony) == false){
			$('errors').store('errors', true);
			$('errors').innerHTML = "<center><b>You need to select if you have or have not commited a felony.</b></center>";
		}
		if(document.application.UserRole[1].checked && $('LicenseNumber').value == ''){
			if (radio_set(document.application.Felony) == false) {
				$('errors').store('errors', true);
				$('errors').innerHTML = "<center><b>You are missing the License Number field.</b></center>";
			}
		}
		if($('HomePhone').value == '' && $('WorkPhone').value == '' && $('MobilePhone').value == ''){
			$('errors').store('errors', true);
			$('errors').innerHTML = "<center><b>You need atleast one number for us to contact you.</b></center>";
		}
		if(radio_set(document.application.UserRole) == false){
			$('errors').store('errors', true);
			$('errors').innerHTML = "<center><b>You need to select a role to apply for.</b></center>";
		}
		if($('errors').retrieve('errors') == false){
			
			if(Browser.Engine.trident)
				$('application').submit();
			else
				$('application').fireEvent('submit');
		}
	}
</script>
<?php
	include_once 'include/footer.php';
?>
