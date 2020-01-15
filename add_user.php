<?php
    include_once 'include/user.php';
	include_once 'include/address.php';
	include_once 'include/phone.php';
	include_once 'include/franchise.php';
	
	redirect_if_not_logged_in();
	
	
	$franchise_id = get_current_user_franchise();
	if(!current_user_has_role(1,'FullAdmin') && !current_user_has_role($franchise_id, 'Franchisee')){
		header("Location: home.php");
		die();
	}
	
    // Address is needed for re-filling the form on error, and for adding user
    $address = array('Address1' => $_POST['Address1'],
                     'Address2' => $_POST['Address2'],
                     'City' => $_POST['City'],
                     'State' => $_POST['State'],
                     'ZIP5' => $_POST['Zip5'],
                     'ZIP4' => $_POST['Zip4']);

	if(isset($_POST['Prefix']) && isset($_POST['FirstName'])&& isset($_POST['MiddleInitial'])&& isset($_POST['LastName'])&& isset($_POST['Suffix'])
	&& isset($_POST['UserName']) && isset($_POST['Email']) && isset($_POST['UserRole'])&& $_POST['UserRole'] != 'Select')
	{
        if (get_user_id_by_username($_POST['UserName'])) {
            $error = "This username already exists in the system.";
        } else {
            $required_fields = array('FirstName','LastName','UserName');
            $missing_fields = array();
            foreach($required_fields as $fieldname){
                if($_POST[$fieldname] == '') {
                    $missing_fields[] = $fieldname;
                }
            }

            if (count($missing_fields) > 0) {
                $error = "Missing required fields: " . implode(',', $missing_fields);
            }

        }

        if (!$error) {
            if (db_start_transaction()) { 
                if($_POST['Email'] != '')
                    $email_insert_id = add_email_address( $_POST['Email'] );
                else
                    $email_insert_id = NULL;
                
                $person_name_insert_id = add_person_name( $_POST['Prefix'], $_POST['FirstName'], $_POST['MiddleInitial'], $_POST['LastName'], $_POST['Suffix'], $_POST["Nickname"] );
                
                
                
                                 
                $password = mt_rand(1001,mt_getrandmax()) . mt_rand(1001,mt_getrandmax());
                
                $user_insert_id = add_user( strtolower($_POST['UserName']), $password, 'ACTIVE', $email_insert_id, $person_name_insert_id, 
                                            $_POST['Felony'], 'APPROVED', $_POST['BackgroundCheck'] );
											
                if($_POST['PhoneNumber'] != '')
                  add_phone_number_for_user(
                    	$_POST['PhoneNumber'],$_POST['PhoneNumberType'],$user_insert_id,
                  		$_POST['PhoneNumberType'] == 'MOBILE' ? $_POST['PhoneCanSMS'] : 'N',
                  		$_POST['PhoneNumberType'] == 'MOBILE' ? $_POST['PhoneMobileCarrier'] : 0,
                  		$_POST['PhoneNumberExt'],
                  		$_POST['PhoneNumberType'] == 'MOBILE' ? $_POST['sms_preferences'] : "FIRST"
                	);
                	
                if($_POST['Address1'] != '' && 
                        (($_POST['City'] != '' && $_POST['State'] != '') ||
                         ($_POST['Zip5'] != '')) ){
                    $address = add_address($address);
                    link_address_to_user($address,'Physical',$user_insert_id);
                }
                
                $role_set = set_role_for_user( $user_insert_id, $franchise_id, $_POST['UserRole']);
				
				$_SESSION['AddUser_Birthday'] = mktime(0,0,0,$_POST['Month'],$_POST['Day'],$_POST['Year']);
                if ($person_name_insert_id && $user_insert_id && $role_set) {
                    db_commit_transaction();
                
                    if($_POST['UserRole'] == 'Rider'){
                        header("location: " . site_url() . "xhr/affected_user_redirect.php?redirect=" .  urlencode( site_url() . "edit_user.php?field=createrider") . "&userid=$user_insert_id");
                    } else if($_POST['UserRole'] == 'Driver'){
                        header("location: " . site_url() . "xhr/affected_user_redirect.php?redirect=" .  urlencode( site_url() . "edit_user.php?field=createdriver") . "&userid=$user_insert_id");
                    } else if($_POST['UserRole'] == 'CareFacilityAdmin'){
                        header("location: " . site_url() . "xhr/affected_user_redirect.php?redirect=" .  urlencode( site_url() . "edit_user.php?field=addcarefacility") . "&userid=$user_insert_id");
                    } else if ($_POST['UserRole'] == 'LargeFacilityAdmin') {
                        header("Location: " . "xhr/affected_user_redirect.php?redirect=" .  urlencode( site_url() . "edit_user.php?field=connectlargefacility") . "&userid=$user_insert_id");
                    } else {
                        header("location: " . site_url() . "xhr/affected_user_redirect.php?redirect=" .  urlencode( site_url() . "account.php"). "&userid=$user_insert_id");
                    }
                } else {
                    db_rollback_transaction();
                    $error = 'A database error occurred while adding the user.';
                }
            }	
		}
	}
	
	include_once 'include/header.php';
?>
<script>
jQuery(function($) {
	$('select[name="PhoneNumberType"]').on('change load',function() {
		if($(this).val() == 'MOBILE') $('#PhoneNumberMobile').show();
		else $('#PhoneNumberMobile').hide();
	});
});	
	
</script>
<center><h2>Create a New User</h2></center>
<?php if ($error) { ?>
    <div style="align: center; color: red"><?php echo $error ?></div>
<?php } ?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
	<table style="margin:auto; width:450px;">
		<tr>
			<td style="font-weight:bold; width:200px;">Name</td>
		</tr>
		<tr>
			<td class="alignright">Prefix</td>
			<td><input type="text" name="Prefix" maxlength="10" value="<?php echo $_POST['Prefix']; ?>" style="width:50px;" /></td>
		</tr>
		<tr>
			<td class="alignright">*First</td>
			<td><input type="text" name="FirstName" maxlength="30" value="<?php echo $_POST['FirstName']; ?>" style="width:200px;" /></td>
		</tr>
		<tr>
			<td class="alignright">MiddleInitial</td>
			<td><input type="text" name="MiddleInitial" maxlength="1" value="<?php echo $_POST['MiddleInitial']; ?>" style="width:50px;" /></td>
		</tr>
		<tr>
			<td class="alignright">*Last</td>
			<td><input type="text" name="LastName" maxlength="30" value="<?php echo $_POST['LastName']; ?>" style="width:200px;" /></td>
		</tr>
		<tr>
			<td class="alignright">Suffix</td>
			<td><input type="text" name="Suffix" maxlength="10" value="<?php echo $_POST['Suffix']; ?>" style="width:50px;" /></td>
		</tr>
		<tr>
			<td class="alignright">Nickname</td>
			<td><input type="text" name="Nickname" maxlength="10" value="<?php echo $_POST['Nickname']; ?>" style="width:200px;" /></td>
		</tr>
		<tr>
			<td style="font-weight:bold; ">User Information</td>
		</tr>
		<tr>
			<td class="alignright">*User Name</td>
			<td><input type="text" name="UserName" value="<?php echo $_POST['UserName']; ?>" style="width:200px;" /></td>
		</tr>
		<tr>
			<td class="alignright">Email</td>
			<td><input type="text" name="Email" value="<?php echo $_POST['Email']; ?>" style="width:200px;" /></td>
		</tr>
		<tr>
			<td class="alignright">*User Role</td>
			<td>
				<select id="RoleSelector" name="UserRole">
					<option value="Rider">Rider</option>
					<option value="Driver">Driver</option>
					<option value="Supporter">Supporting Friend</option>
					<option value="Franchisee">Franchisee</option>
					<option value="CareFacilityAdmin">Care Facility Admin</option>
					<option value="LargeFacilityAdmin">Large Facility Admin</option>
                    <?php
					if (current_user_has_role(1,'FullAdmin')) {
					?>
                    <option value="FullAdmin">Full Admin</option>
                    <?php
					}
					?>
				</select>
			</td>
		</tr>
		<tr>
			<td style="font-weight:bold; ">Home address</td>
		</tr>
		<tr>
			<td colspan="2">
				<?php create_html_address_table(NULL, $address, FALSE);?>
			</td>
		</tr>
		<tr>
			<td style="font-weight:bold; ">Phone Number</td>
		</tr>
		<tr valign=top>
			<td align=right>Number/Type</td>
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
						<input type=radio name="sms_preferences" value='SUBSEQUENT' <?php echo $_POST["sms_preferences"] == "SUBSEQUENT" ? "checked" : ""; ?>> Text on Subsequent Rides<br>
						<input type=radio name="sms_preferences" value='ALL' <?php echo $_POST["sms_preferences"] == "ALL" ? "checked" : ""; ?>> Text on All Rides	
						
					</div>
			</td>
		</tr>
		<tr class="background">
			<td style="font-weight:bold; ">Background</td>
		</tr>
        <tr class="background">
        	<td class="alignright">Birthday</td>
            <td><?php get_date_drop_downs(''); ?></td>
        </tr>
		<tr class="background">
			<td class="alignright">*Felony</td>
			<td>
				<select name="Felony">
					<option value="No">No</option>
					<option value="Yes">Yes</option>
				</select>
			</td>
		</tr>
		<tr class="background">
			<td class="alignright">*Background</td>
			<td>
				<select name="BackgroundCheck">
					<option value="CHECKED">Checked</option>
					<option value="PENDING">Pending</option>
				</select>
			</td>
		</tr>
		
	</table>
	<br />
	<center><input type="submit" name="submit" value="Create" /></center>
</form>
<script>
    $('RoleSelector').addEvent('change',function(){
        if(this.value == 'Supporter'){
            $$('tr.background').each(function(item){
                item.setStyle('display','none');
            });
        } else {
            $$('tr.background').each(function(item){
                item.setStyle('display','');
            });
        }
    });
</script>
<?php
	include_once 'include/footer.php';
?>
