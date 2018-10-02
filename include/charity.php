<?php

function add_charity($charity_name,$franchise, $contact_address,$contact_name,$contact_phone,$contact_email,$contact_title,$charity_hours){
	$safe_charity_name = mysql_real_escape_string($charity_name);
	$safe_franchise = mysql_real_escape_string($franchise);
	$safe_contact_address = ($contact_address != NULL) ? mysql_real_escape_string($contact_address) : 'Null';
	$safe_contact_name = ($contact_name != NULL) ? mysql_real_escape_string($contact_name) : 'Null';
	$safe_contact_phone = ($contact_phone != NULL) ? mysql_real_escape_string($contact_phone) : 'Null';
	$safe_contact_email = ($contact_email != NULL) ? mysql_real_escape_string($contact_email) : 'Null';
	$safe_contact_title = mysql_real_escape_string($contact_title);
	$safe_charity_hours = mysql_real_escape_string($charity_hours);
	
	$sql = "INSERT INTO charity (`CharityID`, `FranchiseID`, `ContactAddressID`, `ContactNameID`, `ContactPhoneID`, `ContactEmailID`, `ContactTitle`, `Hours`, `CharityName`)
						VALUES (NULL,$safe_franchise,$safe_contact_address,$safe_contact_name,$safe_contact_phone,$safe_contact_email, '$safe_contact_title', '$safe_charity_hours', '$safe_charity_name');";
	$result = mysql_query($sql);
	
	if($result){
		return mysql_insert_id();
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error creating new chairity with info ($charity_name,$contact_name,$contact_address,$contact_name,$contact_phone,$contact_email,$contact_title,$charity_hours)", $sql);	
		return FALSE;
	}
}
function update_charity( $charity_id, $charity_name,$contact_address,$contact_name,$contact_phone,$contact_email,$contact_title,$charity_hours,$charity_approved){
	$safe_charity_id = mysql_real_escape_string($charity_id);
	$safe_charity_name = mysql_real_escape_string($charity_name);
	$safe_contact_address = ($contact_address != NULL) ? mysql_real_escape_string($contact_address) : 'Null';
	$safe_contact_name = ($contact_name != NULL) ? mysql_real_escape_string($contact_name) : 'Null';
	$safe_contact_phone = ($contact_phone != NULL) ? mysql_real_escape_string($contact_phone) : 'Null';
	$safe_contact_email = ($contact_email != NULL) ? mysql_real_escape_string($contact_email) : 'Null';
	$safe_contact_title = mysql_real_escape_string($contact_title);
	$safe_charity_hours = mysql_real_escape_string($charity_hours);
	$safe_approved = $charity_approved ? 'Y' : 'N';
	
	$sql = "UPDATE `charity` SET `ContactAddressID` = $safe_contact_address,
													`ContactNameID` = $safe_contact_name,
													`ContactPhoneID` = $safe_contact_phone,
													`ContactEmailID` = $safe_contact_email,
													`ContactTitle` = '$safe_contact_title',
													`Hours` = '$safe_charity_hours',
													`Approved` = '$safe_approved',
													`CharityName` = '$safe_charity_name' WHERE `CharityID` = $safe_charity_id;";
	$result = mysql_query($sql);
	
	if($result){
		return TRUE;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error updating chairity with info ($charity_name,$contact_name,$contact_address,$contact_name,$contact_phone,$contact_email,$contact_title,$charity_hours)", $sql);	
		return FALSE;
	}
}

function link_user_with_charity($user_id, $charity_id){
	$safe_user_id = mysql_real_escape_string($user_id);
	$safe_charity_id = mysql_real_escape_string($charity_id);
	
	$sql = "INSERT INTO `supporter_charity` (`SupporterUserID`, `CharityID`) VALUES ($safe_user_id, $safe_charity_id);";
	$result = mysql_query($sql) or die(mysql_error() . $sql);
	
	if($result){
		return TRUE;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error associating user $user_id with chairty $charity_id", $sql);
		return FALSE;
	}
}

function get_charity($charity_id){
	$safe_charity_id = mysql_real_escape_string($charity_id);
	$sql = "SELECT * FROM (((charity LEFT JOIN person_name ON charity.ContactNameID = person_name.PersonNameID) LEFT JOIN phone ON phone.PhoneID = charity.ContactPhoneID) LEFT JOIN address ON address.AddressID = charity.ContactAddressID) LEFT JOIN email ON email.EmailID = charity.ContactEmailID WHERE CharityID = $safe_charity_id";
	
	$result = mysql_query($sql);
	if($result){
		if(mysql_num_rows($result) < 1)
			return FALSE;
		return mysql_fetch_array($result);
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error associating user $user_id with chairty $charity_id", $sql);
		return FALSE;
	}
}

function get_approved_charities(){
	$sql = "SELECT *, (SELECT COUNT(*) FROM supporter_charity_record WHERE CharityID = charity.CharityID) AS transactions FROM (((charity LEFT JOIN person_name ON charity.ContactNameID = person_name.PersonNameID) LEFT JOIN phone ON phone.PhoneID = charity.ContactPhoneID) LEFT JOIN address ON address.AddressID = charity.ContactAddressID) LEFT JOIN email ON email.EmailID = charity.ContactEmailID WHERE Approved = 'Y'";
	$result = mysql_query($sql);
	
	if($result){
		$charities = array();
		while($row = mysql_fetch_array($result))
			$charities[] = $row;
		return $charities;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error getting all approved charities", $sql);
		return FALSE;
	}
}
function get_pending_charities(){
	$sql = "SELECT *, (SELECT COUNT(*) FROM supporter_charity_record WHERE CharityID = charity.CharityID) AS transactions FROM (((charity LEFT JOIN person_name ON charity.ContactNameID = person_name.PersonNameID) LEFT JOIN phone ON phone.PhoneID = charity.ContactPhoneID) LEFT JOIN address ON address.AddressID = charity.ContactAddressID) LEFT JOIN email ON email.EmailID = charity.ContactEmailID WHERE Approved = 'N'";
	$result = mysql_query($sql);
	
	if($result){
		$charities = array();
		while($row = mysql_fetch_array($result))
			$charities[] = $row;
		return $charities;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error getting all approved charities", $sql);
		return FALSE;
	}
}

function delete_charity($charity_id){
	$charity = get_charity($charity_id);
	$sql = "DELETE FROM  `supporter_charity` WHERE `CharityID` = {$charity['CharityID']};";
	$result = mysql_query($sql);
	
	if($result){
	
		$sql = "DELETE FROM `charity` WHERE `CharityID` = {$charity['CharityID']} LIMIT 1;";
		$result = mysql_query($sql);
		
		if($result){
			delete_email_address( $charity['ContactEmailID'] );
			delete_phone_number($charity['ContactPhoneID'] );
			delete_name($charity['ContactNameID'] );
			delete_address($charity['ContactAddressID'] );
			return true;
		} else{
			rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
							"Error deleting charity $charity_id", $sql);
			return false;
		}
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
							"Error removing supporters for charity $charity_id", $sql);
			return false;
	}
}

function get_charity_names(){
//    $safe_charity_id = mysql_real_escape_string($charity_id);
	$sql = "SELECT CharityID, CharityName FROM charity";
	
	$result = mysql_query($sql);
	if($result){
	    $ret = array();
        while($row = mysql_fetch_array($result))
            $ret[$row['CharityID']] = $row['CharityName'];
        return $ret;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error getting charity name with chairty $charity_id", $sql);
		return FALSE;
	}
}

function openCharityPopup() {
	$franchise_id = get_current_user_franchise();
	$user_id = get_affected_user_id();
	$sql = "select CharityID, CharityName from charity where Approved = 'Y' and FranchiseID = $franchise_id order by CharityName";
	$r = mysql_query($sql);
	$a = array();
	while($rs = mysql_fetch_array($r)) $a[] = $rs;
	echo "<script>charities = ".json_encode($a)."</script>";
	
?>
<script>

function openCharityList() {
	$d = jQuery('<div><select><OPTION VALUE=-1>Select one...</OPTION></select><BR><BR><B>OR</B><BR><BR><A href=/request_new_charity.php>Request a New Charity</a></div>').dialog({
		title: 'Select New Charity',
		width: 'auto',
		buttons: [
			{
				text: 'Ok',
				click: function() {
					if($d.find('select').val() == -1) return;
					jQuery.post('/xhr/add_charity_to_driver.php',{ 'DriverID' : <?php echo $user_id; ?>, 'CharityID' : $d.find('select').val() }, function() {
						window.location.reload();
					});
					$d.dialog('close');
				}	
			},
			{
				text: 'Cancel',
				click: function() {
					$d.dialog('close');
				}
			}
		],
		close: function() {
			$d.remove();
		}
	});
	jQuery.each(charities,function(k,v) {
		$d.find('select').append('<option value='+v.CharityID+'>'+v.CharityName+'</option>');
	});
}	

function adminRemoveCharity(charityid) {
	$d = jQuery('<div>Are you sure you want to delete this charity?</div>').dialog({
		title: 'Delete Charity',
		modal: true,
		width: 'auto',
		buttons: [
			{
				text: 'Yes',
				click: function() {
					jQuery.post('/xhr/remove_charity_from_driver.php',{ 'DriverID' : <?php echo $user_id; ?>, 'CharityID' : charityid }, function() {
						window.location.href = '/home.php';
					});						
				}
			},
			{
				text: 'No',
				click: function() {
					$d.dialog('close');
				}
			}
		],
		close: function() {
			$d.remove();
		}
	});

}

</script>




<?php
}
?>