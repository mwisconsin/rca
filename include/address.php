<?php

require_once('include/usps_services.php');
require_once('include/rc_log.php');
require_once 'include/functions.php';
require_once 'include/user.php';

/**
 * Sanitizes a standard address hash for MySQL.
 * @param $address standard address hash, un-sanitized
 * @return standard address hash, sanitized for MySQL
 */
function sanitize_address_for_mysql($address) {
    // TODO:  Specifically enumerate address fields?
    $return_hash = array();
    foreach ($address as $k => $v) {
    	if($k != 'VerifySource')
        	$return_hash[$k] = mysql_real_escape_string($v);
    }
	return $return_hash;
}

function verify_by_geocode($address){
	$safe_address = sanitize_address_for_mysql($address);
	
	$sql = "SELECT Latitude, Longitude FROM `address` WHERE Address1 LIKE '{$safe_address['Address1']}%' AND City = '{$safe_address['City']}' AND State = '{$safe_address['State']}' AND ZIP5 = '{$safe_address['ZIP5']}' AND verifySource = 'Geocode' LIMIT 1;";
	$result = mysql_query($sql);
	
	if($result){
		if(mysql_num_rows($result) < 1)
			return array("result" => FALSE);
		$row = mysql_fetch_array($result);
		return array("result" => TRUE,
					 "Latitude" => $row['Latitude'],
					 "Longitude" => $row['Longitude']);
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not verify address via Geocode", $sql);
		return array("result" => FALSE);
	}
}
function cross_check_geo_rca_db($address){
	$safe_address = sanitize_address_for_mysql($address);
	$sql = "SELECT * FROM address WHERE Address1 LIKE '{$safe_address['Address1']}%' AND City = '{$safe_address['City']}' AND State = '{$safe_address['State']}' AND ZIP5 = '{$safe_address['ZIP5']}' AND VerifySource = 'Geocode' LIMIT 1";
	$result = mysql_query($sql);
	
	if($result){
		if(mysql_num_rows($result) < 1){
			return FALSE;
		}
		$address = mysql_fetch_array($result);
		return $address;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not verify address against RCA database " . var_export($returned_address, TRUE), $sql);
	}
}

function parse_address1($address1){
	$address1 = trim($address1);
	$first_space = (stripos($address1, " ") !== FALSE) ? stripos($address1, " ") : FALSE;
	$second_space = ($first_space !== FALSE && stripos($address1, " ", $first_space + 1) !== FALSE) ? stripos($address1, " ", $first_space + 1) : FALSE;
	if( $first_space && $second_space){
		$address1 = substr($address1, 0, $second_space);
		return $address1;
	}
	return FALSE;
}

function verify_address($address){
	/* if geolocated, no need for USPS confirmation */
	if(@$address['Longitude'] != '' && @$address['Latitude'] != '') {
		$address['VerifySource'] = "'Geocode'";
		return $address;
	}
		
	$returned_address = usps_standardize_address( $address );
	$address_log_file = 'rc_address.log';
	
	if($returned_address['SUCCESS'] === TRUE) {
    	$returned_address['VerifySource'] = "'USPS'";
		$returned_address['IsVerified'] = 'Yes';
		if($geo_address = cross_check_geo_rca_db($returned_address)){
			$returned_address = $geo_address;
			$returned_address['VerifySource'] = "'Geocode'";
		}
			

        // Log the original and updated address.  Someday may DB log this.
        rc_log(PEAR_LOG_INFO, "Storing standardized address.  (OLD:  " .
                              var_export($address, TRUE) . ") NEW:  " .
                              var_export($returned_address, TRUE) . ")",
               $address_log_file);
		return $returned_address;
	} else {
		$return_address = $address;
		$return_address['VerifySource'] = "NULL";
		$return_address['IsVerified'] = 'No';
		
		$geo_address = $return_address;
		$geo_address['Address1'] = parse_address1($geo_address['Address1']);

		if($geo_address['Address1']){
			if($geo_address = cross_check_geo_rca_db($return_address)){
				$return_address = $geo_address;
				$return_address['VerifySource'] = "'Geocode'";
			}
				
		}
		return $return_address;
	} return FALSE;
}

/**
 * Adds an address to the database.
 * @param $address Address to add, standard address hash.
 * @param $should_usps_verify Request that USPS verify the address
 * @return address ID or FALSE on error
 */
function add_address($address, $should_verify = TRUE) {
	$return = FALSE;
	
    if ($should_verify) {
    	$safe_address = verify_address($address);
    } else {
    	$safe_address = sanitize_address_for_mysql($address);
		$safe_address['VerifySource'] = "NULL";
		$safe_address['IsVerified'] = 'No';
    }
	
	$safe_address['Latitude'] = ($safe_address['Latitude'] != NULL) ? $safe_address['Latitude'] : 'NULL';
	$safe_address['Longitude'] = ($safe_address['Longitude'] != NULL) ? $safe_address['Longitude'] : 'NULL';

    $sql = "INSERT INTO address (Address1, Address2, City, State, ZIP5, ZIP4, Latitude, Longitude,
                                 IsVerified, VerifySource)
            VALUES ( '{$safe_address['Address1']}',
                     '{$safe_address['Address2']}',
                     '{$safe_address['City']}',
                     '{$safe_address['State']}',
                     '{$safe_address['ZIP5']}',
                     '{$safe_address['ZIP4']}',
					 {$safe_address['Latitude']},
					 {$safe_address['Longitude']},
                     '{$safe_address['IsVerified']}',
                     {$safe_address['VerifySource']} );";  # Quotes should be part of this string, not added.

    $result = mysql_query($sql);

    if ($result) {
       $return = mysql_insert_id(); 
	   $address_log_file = 'rc_address.log';
       rc_log(PEAR_LOG_INFO, "Stored address $return.", $address_log_file);
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not add address " . var_export($address, TRUE), $sql);
    }

    return $return;
}

/**
 * Returns the address of the identified address as a hash.  Keys to the hash:
 * Address1, Address2, City, State, ZIP5, ZIP4.
 * @param address_id ID of address to get address for
 * @return hash containing Address fields or FALSE on error.
 */
function get_address( $address_id ){
	if($address_id == NULL)
		return NULL;
	
	$query = "SELECT * FROM `address` WHERE `AddressID` = " . mysql_real_escape_string($address_id);
	$result = mysql_query($query) or die(mysql_error());
	if(mysql_num_rows($result) != 1)
		return FALSE;
	$result = mysql_fetch_array($result, MYSQL_ASSOC);
	
	/* lpad zips with zeroes for addresses on the East Coast */
	$result["ZIP5"] = str_pad($result["ZIP5"],5,"0",STR_PAD_LEFT);
    return $result;
}

/**
 * Sets the address verification status for an address in the database.
 * @param $address_id Address to set status for
 * @param $is_verified Boolean indicating whether address is verified.   
 * @param $verify_type Source of verification.  String.  USPS or Admin.  Ignored if 
 *        is_verified is FALSE.
 * @return TRUE on success, FALSE on error
 */
function set_address_verification_status($address_id, $is_verified = FALSE, $verify_type = 'IGNORED') {
    $safe_address_id = mysql_real_escape_string($address_id);
    $safe_verified = (($is_verified) ? 'Yes' : 'No');
    $safe_source = (($is_verified) ? "'" . mysql_real_escape_string($verify_type) . "'" : 'NULL');

    $sql = "UPDATE address SET 
                IsVerified = '$safe_verified',
                VerifySource = $safe_source
            WHERE AddressID = $safe_address_id";
    $result = mysql_query($sql);

    if ($result) {
        return TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Error setting verification for $address_id to $is_verified:$verify_type",
                        $sql);
        return FALSE;
    }
}


/**
 * Links an address to a user.
 * @param address_id ID of address
 * @param type Type of address (Physical, Mailing, Billing, Additional)
 * @param user_id ID of user
 * @return TRUE if successful, FALSE otherwise
 */
function link_address_to_user($address_id, $type, $user_id) {
    $safe_address_id = mysql_real_escape_string($address_id);
    $safe_type = mysql_real_escape_string($type);
    $safe_user_id = mysql_real_escape_string($user_id);

    $sql = "INSERT INTO user_address (UserID, AddressID, AddressType)
                        VALUES ($safe_user_id, $safe_address_id, '$safe_type')";

    $result = mysql_query($sql);

    if ($result) {
        return TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Error linking address $address_id ($type) to user $user_id", $sql);
        return FALSE;
    }

}

function update_address($address_id, $address, $should_usps_verify = TRUE, $is_geocode_verified = FALSE){
	$address_log_file = 'rc_address.log';
	$safe_address = sanitize_address_for_mysql($address);
	$safe_address_id = mysql_real_escape_string($address_id);

	if ($should_usps_verify) {
        $returned_address = usps_standardize_address( $address );
        if ($returned_address['SUCCESS'] === TRUE) {
            $safe_is_verified = 'Yes';
            $safe_verify_source = "'USPS'";
            $safe_address = sanitize_address_for_mysql($returned_address);

            // Log the original and updated address.  Someday may DB log this.
            rc_log(PEAR_LOG_INFO, "Storing standardized address.  (OLD:  " .
                                  var_export($address, TRUE) . ") NEW:  " .
                                  var_export($returned_address, TRUE) . ")",
                   $address_log_file);
        }
    }

    if ($is_geocode_verified && !isset($address['Latitude'], $address['Longitude'])) {
        // This is a problem.  Need coordinates to geoverify.
        rc_log_db_error(PEAR_LOG_ERR, 'Non-MySQL Error', 
                        "Address claims geocode verified, but does not have coordinates.", 
                        var_export($address, TRUE));
        return FALSE;
    } elseif ($is_geocode_verified) {
        $safe_verify_source = "'Geocode'";
    }

    if (!isset($safe_verify_source) && $safe_verify_source != "'USPS'") {
        $safe_address = sanitize_address_for_mysql($address);
        $safe_is_verified = 'No';
        $safe_verify_source = 'NULL';
    }
	
	$safe_address['Latitude'] = ($safe_address['Latitude'] != NULL) ? $safe_address['Latitude'] : 'NULL';
	$safe_address['Longitude'] = ($safe_address['Longitude'] != NULL) ? $safe_address['Longitude'] : 'NULL';

	$sql = "UPDATE `address` SET `Address1` = '{$safe_address['Address1']}',
                     			 `Address2` = '{$safe_address['Address2']}',
                     			 `City` = '{$safe_address['City']}',
                     			 `State` = '{$safe_address['State']}',
                     			 `ZIP5` = '{$safe_address['ZIP5']}',
                     			 `ZIP4` = '{$safe_address['ZIP4']}',
					             `Latitude` = {$safe_address['Latitude']},
					             `Longitude` = {$safe_address['Longitude']},
								 `IsVerified` = '$safe_is_verified',
								 `VerifySource` = $safe_verify_source
			WHERE `AddressID` =$safe_address_id LIMIT 1 ;";
	#echo $sql."<BR><BR>";
	$result = mysql_query($sql);
	
	if ($result) {
		return TRUE;
    } else {
		echo "Could not update address $address_id : " . mysql_error() . " : $sql";
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not update address $address_id", $sql);
	}
    return FALSE;
}
function delete_address( $address_id ){
	$safe_address_id = mysql_real_escape_string($address_id);
	
	$sql = "DELETE FROM `address` WHERE `AddressID` = '$safe_address_id' LIMIT 1;";
	$result = mysql_query($sql);
	
	if(!$result){
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not delete address $address_id", $sql);
	}
}
function delete_all_user_address( $user_id ){
	$safe_user_id = mysql_real_escape_string( $user_id );
	$addresses = get_user_addresses( $user_id );
	
	$sql = "DELETE FROM `user_address` WHERE `UserID` = $safe_user_id";
	$result = mysql_query($sql);
	
	if(!$result){
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not delete user phone number link $user_id", $sql);
		return FALSE;
	}
	
	if($addresses != FALSE){
		while($row = mysql_fetch_array($addresses)){
			delete_address($row['AddressID']);
		}
	}
}
/**
 * Creates a nice form table of an address
 * @param object $prefix prefix of the name of the field (for multiple addresses on one page)
 * @param object $address an address to automatically put in the field
 */
function create_html_address_table($prefix = '', $address = NULL, $verify = TRUE, $postfix=''){
	$franchise = get_current_user_franchise(FALSE);
	?>
	<table name="Address_Table" style="margin:auto; text-align:left;">
		<tr>
			<td colspan="3">*Street Address<br/><input id="<?php echo $prefix; ?>Address1<?php echo $postfix; ?>" name="<?php echo $prefix; ?>Address1<?php echo $postfix; ?>" type="text" value="<?php echo $address['Address1']; ?>" <?php if(current_user_has_role(1,'FullAdmin') || current_user_has_role($franchise, 'Franchisee')){?>style="width:305px;"<?php } else {?>style="width:250px;"<?php } ?> /></td>
		</tr>
		<tr>
			<td colspan="3">Address 2 <span style="font-size:.75em;">Apt, floor, suite, etc.</span><br><input id="<?php echo $prefix; ?>Address2<?php echo $postfix; ?>" value="<?php echo $address['Address2']; ?>" name="<?php echo $prefix; ?>Address2<?php echo $postfix; ?>" type="text" <?php if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee')){?>style="width:305px;"<?php } else {?>style="width:250px;"<?php } ?> /></td>
		</tr>
		<tr>
			<td>City<br /><input id="<?php echo $prefix; ?>City<?php echo $postfix; ?>" value="<?php echo $address['City']; ?>" name="<?php echo $prefix; ?>City<?php echo $postfix; ?>" maxlength="30" type="text" style="width:150px;" /></td>
			<td>State<br />
            	<?php get_state_dropdown($prefix, $address['State'], $postfix); ?>
            </td>
			<td>Zip<br /><input id="<?php echo $prefix; ?>Zip5<?php echo $postfix; ?>"  value="<?php echo $address['ZIP5'] != '' ? sprintf("%05s", $address['ZIP5']) : ''; ?>" name="<?php echo $prefix; ?>Zip5<?php echo $postfix; ?>" maxlength="5" type="text" style="width:40px;" /><?php if(current_user_has_role($franchise, 'FullAdmin') || current_user_has_role($franchise, "Franchisee")){?> - <input type="text" id="<?php echo $prefix; ?>Zip4<?php echo $postfix; ?>" value="<?php echo $address['ZIP4'] != '' ? sprintf("%04s", $address['ZIP4']) : ''; ?>" name="<?php echo $prefix; ?>Zip4<?php echo $postfix; ?>" maxlength="4" style="width:35px;" /><?php } ?></td>
		</tr>
	</table>
	<input type=hidden id="<?php echo $prefix; ?>Latitude<?php echo $postfix; ?>" name="<?php echo $prefix; ?>Latitude<?php echo $postfix; ?>" value="<?php echo @$address['Latitude']; ?>">
	<input type=hidden id="<?php echo $prefix; ?>Longitude<?php echo $postfix; ?>" name="<?php echo $prefix; ?>Longitude<?php echo $postfix; ?>" value="<?php echo @$address['Longitude']; ?>">
	<?php if($verify){ 
	        if ($postfix=='') {
	?>
	<script src="js/address.js" type="text/javascript"></script>
	<?php 
	        } else {
			?>
            <script src="js/address2.js" type="text/javascript"></script>
            <?php
			}
	      } ?>
	<?php
}
/**
 * Creates a table of an Address
 * @param object $Address1
 * @param object $Address2
 * @param object $City
 * @param object $State
 * @param object $Zip
 * @return 
 */
function create_html_display_address($address,$css = 'margin:auto;'){
	if(!is_array($address))
		$address = get_address($address);
	if($address['Address1'] == NULL || $address['City'] == NULL || $address['State'] == NULL)
		return false;
	?>
	<table id="<?php echo $address['AddressID']; ?>" name="address_display" style="<?php echo $css; ?>">
		<tr>
			<td nowrap="nowrap"><?php echo $address['Address1']; ?></td>
		<tr>
			<td><?php echo $address['Address2']; ?></td>
		</tr>
		<tr>
			<td><?php echo $address['City']; ?> <?php echo $address['State']; ?>, <?php echo $address['ZIP5'] != '' ? sprintf("%05s", $address['ZIP5']) : '';?></td>
		</tr>
	</table>
	<?php
}

function create_compact_display_address($address) {
    return "{$address['Address1']}<br />" . 
           (($address['Address2']) ? "{$address['Address2']}<br />" : '') .
           "{$address['City']}, {$address['State']}  ". ($address['ZIP5'] != '' ? sprintf("%05s", $address['ZIP5']) : '');
}

/**
 * Creates a simple address input field, without much styling.
 * @param object $prefix prefix of the name of the field (for multiple addresses on one page)
 * @param object $address an address to automatically put in the field
 */
function create_simple_address_input($prefix = '', $address = NULL, $verify = FALSE) {
	?>
	<table name="Address_Table">
		<tr>
			<td colspan="3">Street Address<br/>
                <input id="<?php echo $prefix; ?>Address1" name="<?php 
                                 echo $prefix; ?>Address1" type="text" value="<?php 
                                 echo $address['Address1']; ?>" style="width: 20em;" /></td>
		</tr>
		<tr>
			<td colspan="3">Address 2 <span style="font-size:0.75em;">Apt, floor, suite, etc.</span><br />
                <input id="<?php echo $prefix; ?>Address2" value="<?php 
                                 echo $address['Address2']; ?>" name="<?php 
                                 echo $prefix; ?>Address2" type="text" style="width: 20em;" /></td>
		</tr>
		<tr>
			<td>City<br />
                <input id="<?php echo $prefix; ?>City" value="<?php 
                                 echo $address['City']; ?>" name="<?php 
                                 echo $prefix; ?>City" maxlength="30" type="text" style="width:15em;" /></td>
			<td>State<br />
            	<?php get_state_dropdown(NULL, $address['State']); ?>
            </td>
			<td>Zip<br /><input id="<?php echo $prefix; ?>Zip5"  value="<?php 
                                          echo $address['ZIP5'] != '' ? sprintf("%05s", $address['ZIP5']) : ''; ?>" name="<?php 
                                          echo $prefix; ?>Zip5" maxlength="5" type="text" style="width: 5em;" /></td>
		</tr>
	</table>
	<?php if ($verify) { ?>
        <script src="js/address.js" type="text/javascript"></script>
	<?php } 
}

function get_address_field_list() {
    return array('Address1', 'Address2', 'City', 'State', 'Zip5', 'Zip4');
}

?>
