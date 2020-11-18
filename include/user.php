<?php session_start();

require_once('include/database.php');
require_once('include/functions.php');
require_once('include/rc_log.php');
require_once 'include/rider.php';
require_once 'include/driver.php';
require_once 'include/address.php';
require_once 'include/phone.php';
require_once 'include/name.php';
require_once 'include/email.php';
require_once 'include/franchise.php';

// Memoization for role
$USER_user_role_memo = array();
$USER_user_name_memo = array();


	function get_user_annual_fee( $franchise_id )
	{
	    $safe_franchise_id = mysql_real_escape_string( $franchise_id );
	
	    $sql = "SELECT AnnualFee FROM franchise WHERE FranchiseID = $safe_franchise_id";
	    
		$result = mysql_query( $sql );
	    if ( $result ) 
	    {
	        $arr = mysql_fetch_array( $result );
			$result = $arr['AnnualFee'];
	    } 
	    else 
	    {
	        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
	                        "Error getting driver info for user $user_id", $sql);
	        $result = FALSE;
	    }
	
		return $result;
	}
	
	
/**
 * Sets session variables for user
 * @params UserID of user
 */
function login_user( $user_id ){
	$user = get_user_account($user_id);
	$_SESSION['UserID'] = $user['UserID'];
	$session_hash = sha1($user['UserID'] . $user['Salt'] . $user['EmailID'] . $user['PersonNameID']);
	$_SESSION['SessionHash'] = $session_hash;
}
/**
 * is_logged_in() checks to see a user is logged in by comparing the $_SESSION hash with the real hash
 * @return returns true on logged in or false on not logged in.
 */
function is_logged_in(){
	if(isset($_SESSION['UserID']) && isset($_SESSION['SessionHash'])){
		$user = get_user_account($_SESSION['UserID']);
		if($_SESSION['SessionHash'] == sha1($user['UserID'] . $user['Salt'] . $user['EmailID'] . $user['PersonNameID']) && $_SESSION['SessionHash'] != '' && $user)
			return TRUE;
		return FALSE;
	}
	return FALSE;
}
/**
 * Returns the user ID of the current logged-in user.
 * @return User ID if a user is logged in.  FALSE if no user is logged in.
 */
function get_current_user_id() {
    if(isset($_SESSION['UserID']))
		return $_SESSION['UserID'];
    return FALSE;
}
/**
 * Redirects if logged in user is not logged in.
 * @param $redirect_url Secondary url to be redirected to.
 */
function redirect_if_not_logged_in( $redirect_url = FALSE ){	
	if(!is_logged_in())
	{
		if(!$redirect_url){
			$_SESSION['RedirectURL'] = $_SERVER['PHP_SELF'];
			header("location: login.php");
		} else {
			header("location: " . $redirect_url);
		}
		die();
	}
}
/**
 * Redirects if logged in user is not of a certain role.
 * @param $role_only role specified.
 */
function redirect_if_not_role( $role_only ){
	if(is_logged_in())
	{
		$franchise = get_current_user_franchise();
		if(!current_user_has_role($franchise, $role_only)){
			header("location: " . site_url() . "home.php");
			die();
		}
	} else 
		redirect_if_not_logged_in();
}
/**
 * Returns the Driver addresses of the identified user as a Mysql query result.
 * @param user_id ID of user to get addresses for
 * @return mysql query result containing address id AND address type fields or FALSE on error.
 */
function get_user_addresses( $user_id ){
	$query = "SELECT * FROM `user_address` WHERE `UserID` = '" . mysql_real_escape_string($user_id) . "';";
    //echo $query;
	$result = mysql_query($query) or die(mysql_error());
	if(mysql_num_rows($result) < 1)
		return FALSE;
	return $result;
}

/**
 * Returns an array of user addresses (all address fields and IDs).
 * @param user_id ID of user to get addresses for
 * @return array - each entry is an address.  Not ordered particularly.  FALSE on error; empty array if none.
 */
function get_user_address_array( $user_id ) {
    $safe_user_id = mysql_real_escape_string($user_id);

    $sql = "SELECT AddressID, Address1, Address2, City, State, ZIP5, ZIP4, IsVerified, VerifySource,
                   Latitude, Longitude, AddressType
            FROM user_address NATURAL JOIN address
            WHERE UserID = $safe_user_id";

    $result = mysql_query($sql);
    
    if ($result) {
        $addresses = array();
        while ($row = mysql_fetch_array($result)) {
            $addresses[$row['AddressID']] = $row; 
        }
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 'Could not get addresses for user $user_id', $sql);
        $addresses = FALSE;
    }

    return $addresses;
}

/**
 * Returns the username and account status of the identified user as a hash.  Keys to the hash:
 * UserName, Status.
 * @param user_id ID of user to get info for
 * @return hash containing name fields or FALSE on error.
 */
function get_user_account( $user_id ){
    static $LAST_USER_REQUESTED;
    static $LAST_USER_ACCOUNT_INFO;

    if ($LAST_USER_REQUESTED == $user_id) {
        return $LAST_USER_ACCOUNT_INFO;
    }

    $safe_user_id = mysql_real_escape_string($user_id);
    $sql = "SELECT * FROM users, person_name WHERE UserID = $safe_user_id and person_name.PersonNameID = users.PersonNameID";

    $result = mysql_query($sql);
    if ($result) {
        $LAST_USER_ACCOUNT_INFO = mysql_fetch_assoc($result);
        $LAST_USER_REQUESTED = $user_id;

        return $LAST_USER_ACCOUNT_INFO;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error getting user account for UID $user_id", $sql);
    }

    return FALSE;
}

/**
 * Returns the email of the identified user.
 * @param user_id ID of user to get email for
 * @return email address of user_id. false of error
 */
function get_user_email( $user_id ){
	$query = "SELECT email.`EmailID`, `EmailAddress` FROM `users` LEFT JOIN `email` ON users.EmailID = email.EmailID WHERE `UserID` = '" . mysql_real_escape_string($user_id) . "' LIMIT 1;";
	$result = mysql_query($query) or die(mysql_error());
	if(mysql_num_rows($result) != 1)
		return FALSE;
	$result = mysql_fetch_array($result);
	return $result['EmailAddress'];
}
/**
 * Returns the name of the identified user as a hash.
 * @param user_id ID of user to get role for
 * @return $user_id role or FALSE on error.
 */
function get_user_roles( $user_id, $franchise = NULL ) {
	$safe_franchise = mysql_real_escape_string($franchise);
	$safe_user = mysql_real_escape_string($user_id);
//	if($franchise != NULL)
//		$franchise_W = "(FranchiseID = $safe_franchise AND `UserID` = '$safe_user') OR (`UserID` = $safe_user AND Role = 'FullAdmin')";	
//	else
		$franchise_W = "UserID = $safe_user";
		
	$query = "SELECT * FROM `user_role` ";
//	$query .= "natural JOIN franchise ";
	$query .= "WHERE $franchise_W";
	$result = mysql_query($query);
	if(mysql_num_rows($result) < 1)
		return FALSE;
	$roles = array();
	while($row = mysql_fetch_assoc($result))
		$roles[] = $row;
	return $roles;
}
function if_user_has_role( $user_id,$franchise, $role ){
    return (user_has_role($user_id,$franchise, $role));
    // TODO:  DEPRECATED - Change to user_has_role to avoid double-if
}

function user_has_role($user_id, $franchise, $role) {
	
	global $USER_user_role_memo;
	$safe_franchise = '';
	
	if(!is_logged_in())
		return FALSE;
    if (isset($USER_user_role_memo[$user_id][$franchise][$role])) {
        return ($USER_user_role_memo[$user_id][$franchise][$role]);
    }

    $safe_uid = mysql_real_escape_string($user_id);
    $safe_role = mysql_real_escape_string($role);
	
	if(strtoupper($role) != strtoupper('FullAdmin')){
	$safe_franchise = mysql_real_escape_string($franchise);
	$safe_franchise = " AND FranchiseID = '$safe_franchise'";
	}
	
    $sql = "SELECT COUNT(UserID) AS HasRole FROM user_role WHERE UserID = $safe_uid AND Role = '$safe_role'$safe_franchise";
  
	$result = mysql_query($sql);
    if ($result) {
        $row = mysql_fetch_assoc($result);

        // Memoize
        $USER_user_role_memo[$user_id][$franchise][$role] = ($row['HasRole'] >= 1);

        return ($row['HasRole'] >= 1);
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error checking if user $user_id has role $role", $sql);
    }

	return FALSE;
}


function if_current_user_has_role( $franchise, $role ){
	return if_user_has_role(get_current_user_id(),$franchise, $role);
	// TODO:  DEPRECATED
}

function current_user_has_role($franchise, $role) {
	return user_has_role(get_current_user_id(), $franchise, $role);
}

function affected_user_has_role($franchise, $role) {
	return user_has_role(get_affected_user_id(), $franchise, $role);
}

/**
 * Returns the name of the identified user as a hash.  Keys to the hash:
 * Title, FirstName, MiddleInitial, LastName, Suffix.
 * @param user_id ID of user to get name for
 * @return hash containing name fields or FALSE on error.
 */
function get_user_person_name( $user_id ) {
#    global $USER_user_name_memo;
#    if (isset($USER_user_name_memo[$user_id][$name])) {
#        return ($USER_user_name_memo[$user_id][$name]);
#    }

    $safe_uid = mysql_real_escape_string($user_id);
    $sql = "SELECT PersonNameID, Title, FirstName, MiddleInitial, LastName, Suffix, NickName, profile_image 
            FROM `users` NATURAL JOIN `person_name`  
            WHERE `UserID` = $safe_uid";
    $result = mysql_query($sql);

    if ($result) {
        $row = mysql_fetch_array($result);

        // Memoize
        #$USER_user_name_memo[$user_id][$name] = $row;

        return $row;
    } else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get person name for $user_id", $sql);
		return FALSE;
    }
}
function get_user_phone_numbers( $user_id ){
    $safe_user_id = mysql_real_escape_string($user_id);
	$query = "SELECT * FROM `user_phone` NATURAL JOIN `phone`
              WHERE `UserID` = $safe_user_id
              ORDER BY PhoneType";
	$result = mysql_query($query);
	
	if ($result) {
        $numbers = array();
        while ($row = mysql_fetch_array($result)) {
            $numbers[$row['PhoneID']] = $row;
        }
		return $numbers;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get any phone numbers for user $user_id", $query);
		return FALSE;
	}
}

function get_user_phone_numbers_type($user_id, $type) {
    $safe_uid = mysql_real_escape_string($user_id);
    if ($type != FALSE) {
        $safe_type = mysql_real_escape_string($type);
        $type_where_clause = "AND PhoneType = '$safe_type'";
    }

    $sql = "SELECT PhoneID, PhoneType, PhoneNumber, Ext, phonedescription 
            FROM user_phone NATURAL JOIN phone
            WHERE UserID = $safe_uid 
                  $type_where_clause";

	$result = mysql_query($sql);
	
	if ($result) {
        $numbers = array();
        while ($row = mysql_fetch_array($result)) {
            $numbers[] = $row;
        }
        return $numbers;
	} else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error,
                        "Error retrieving phone numbers for $user_id / $type", $sql);
		return FALSE;
	}
}

/**
 * Adds a user to the system.  Assumes all pre-work has been done (email and name already added)
 * (TODO:  func that does all the work?)
 *
 * @param username unique username within the system
 * @param password password for user
 * @param status user's activity status within the system.  ACTIVE or INACTIVE
 * @param email_id ID of email address for user within the system
 * @param name_id ID of "person-name" for user within the system
 * @param has_felony does the user have a felony conviction.  Yes or No
 * @param application_status Status of the user's application.  APPLIED, APPROVED, REJECTED
 * @return userID on success, FALSE otherwise
 */
function add_user( $username, $password, $status, $email_id, $name_id, 
                   $has_felony, $application_status = 'APPLIED', $background_check = 'PENDING' ) {
    $salt = time();  // TODO:  Longer, more random-ish salt over larger alphabet?

    $safe_username = mysql_real_escape_string( $username );
    $safe_status = mysql_real_escape_string( $status );
    $safe_email_id = ($email_id != NULL) ? mysql_real_escape_string( $email_id ) : 'NULL';
    $safe_name_id = mysql_real_escape_string( $name_id );
    $safe_has_felony = mysql_real_escape_string( $has_felony );
    $safe_application_status = mysql_real_escape_string( $application_status );
	$safe_background_check = mysql_real_escape_string( $background_check );

    $safe_password_hash = mysql_real_escape_string( sha1($salt . $password) );

    $sql = "INSERT INTO users (UserName, Password, Salt, Status, EmailID,
                               PersonNameID, HasFelony, ApplicationStatus, BackgroundCheck, ApplicationDate)
                VALUES ('$safe_username', '$safe_password_hash', '$salt', '$safe_status',
                        $safe_email_id, $safe_name_id, '$safe_has_felony',
                        '$safe_application_status', '$safe_background_check', NOW() )";
    
    $result = mysql_query($sql);
    if ($result) {
        return mysql_insert_id();
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), "Could not add user.", $sql);
		echo mysql_error() . ' | ' . $sql;
        return FALSE;
    }
}
function get_user_alias( $user_id ){
	$safe_user_id = mysql_real_escape_string($user_id);
	$sql = "SELECT * FROM `background_check_alias` WHERE `UserID` = $safe_user_id ORDER BY AliasID DESC limit 1";
	$result = mysql_query($sql);
	
	if($result){
		if(mysql_num_rows($result) < 1)
			return FALSE;
		return mysql_fetch_array($result);
	} else {
		echo "Could not get alias for UserID $user_id :" . mysql_error();
		return FALSE;
	}
}
function set_user_inactive( $user_id ){
	$safe_user_id = mysql_real_escape_string( $user_id );
	$return = TRUE;
	$sql = "UPDATE `users` SET `Status` = 'INACTIVE' WHERE `UserID` =$safe_user_id LIMIT 1 ;";
	$result = mysql_query($sql);
	$franchise = get_current_user_franchise();
	if(!result){
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), "Could not set user Inactive $safe_user_id", $sql);
			$return = FALSE;
	}
	
	if(user_has_role($user_id, $franchise, 'Rider')){
		$safe_user_id = mysql_real_escape_string( $user_id );
		$sql = "UPDATE `rider` SET `RiderStatus` = 'Inactive' WHERE `UserID` =$safe_user_id LIMIT 1 ;";
		$result = mysql_query($sql);
		
		if(!result){
			rc_log_db_error(PEAR_LOG_ERR, mysql_error(), "Could not set Rider Inactive $safe_user_id", $sql);
			$return = FALSE;
		}
	}
	
	if(user_has_role($user_id, $franchise, 'Driver')){
		$safe_user_id = mysql_real_escape_string($user_id);
		$sql = "UPDATE `driver` SET `DriverStatus` = 'Inactive' WHERE `UserID` =$safe_user_id LIMIT 1 ;";
		$result = mysql_query($sql);
		
		if(!result){
			rc_log_db_error(PEAR_LOG_ERR, mysql_error(), "Could not set Driver Inactive $safe_user_id", $sql);
			$return = FALSE;
		}
	}
	
	$sql = "update care_facility_user set DisconnectDate = '".date('Y-m-d',time())."' where UserID = $user_id";
	mysql_query($sql);
	
	return $return;
}


/**
 * Returns the user ID of the 'affected' user.  If the logged-in user is a regular
 * user (e.g. rider, driver), the affected user ID is that of the logged-in user.
 * If the logged-in user is an admin working as another user, the affected user ID
 * is that of the other user.
 *
 * @return User ID of the affected user.  FALSE if the user is not logged in.
 */
function get_affected_user_id() {
    //session_start();

    $affected_uid = FALSE;
	$franchise = get_current_user_franchise(FALSE);
    // TODO:  Clean way of making sure the user belongs to the admin?
    if ($franchise && (current_user_has_role($franchise, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee')) &&
       (isset($_SESSION['AffectedUserID']) && $_SESSION['UserID'] &&
            $_SESSION['AUIDREQ'] &&
            sha1('AUIDSECURITYHASH' . $_SESSION['AffectedUserID'] . $_SESSION['UserID']) ==
                    $_SESSION['AUIDREQ'])) {
            
        $affected_uid = $_SESSION['AffectedUserID'];
    } else {
        $affected_uid = get_current_user_id();
    }

    return $affected_uid;
}

/**
 * Sets the user ID of the 'affected' user in the session.  This only has an effect if the 
 * current user is an admin, and the new affected user is 'under' the admin.
 *
 * @param $user_id ID of the user to set as the affected user.
 */
function set_affected_user_id($user_id) {
	$franchise = get_current_user_franchise();
    // TODO:  Some clean way of making sure the user belongs.
    if ((current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee')) && user_has_franchise($user_id, $franchise)) {
        $_SESSION['AffectedUserID'] = $user_id;
        $_SESSION['AUIDREQ'] = sha1('AUIDSECURITYHASH' . $user_id . $_SESSION['UserID']);
    } else {
        clear_affected_user_id();
    }
}

function clear_affected_user_id() {
    unset($_SESSION['AffectedUserID']);
    unset($_SESSION['AUIDREQ']);
}

function delete_user( $user_id ){
	return true;
}
function user_string( $user_id, $reset = FALSE ){
	if($user_id == NULL)
		return;
	#$reset = TRUE;
  if(isset($_SESSION['UserString'][$user_id]) && !$reset){
      echo $_SESSION['UserString'][$user_id];
      return;   
  }
	$user_id = mysql_real_escape_string( $user_id );
	$user = get_user_account( $user_id );
	if(!$user)
		return;
	$name = get_name( $user['PersonNameID'] );
	$phone = get_phone_number_for_user($user_id);
	$email = ($user['EmailID'] != NULL) ? get_email_address( $user['EmailID'] ) : '';
	$sql = "SELECT Address1, Address2, City, State, ZIP5, ZIP4, DateOfBirth
		FROM user_address NATURAL JOIN address 
		LEFT JOIN rider ON user_address.UserID = rider.UserID 
		WHERE user_address.UserID = $user_id AND user_address.AddressType = 'Physical' 
		AND user_address.AddressID = address.AddressID LIMIT 1;";
	$address = mysql_fetch_array( mysql_query( $sql ) );
	$_SESSION['UserString'][$user_id] = "{$name['FirstName']} "
		.(trim($name['NickName']) != '' ? "(<b>{$name['NickName']}</b>)" : "")
		." {$name['LastName']}, {$phone['PhoneType']} {$phone['PhoneNumber']}".($phone['Ext'] != '' ? " x".$phone['Ext'] : '')
		.", ".@$email['EmailAddress'].", {$address['Address1']}, "
		.(trim(@$address['Address2']) != '' ? "{$address['Address2']}, " : "")
		."{$address['City']}, {$address['State']} {$address['ZIP5']}-{$address['ZIP4']}";
	
	$r = mysql_query(	"select * from rider where UserID = $user_id" );
	if(mysql_num_rows($r))
		$_SESSION['UserString'][$user_id] .= ", {$address['DateOfBirth']}";
	else {
		$r = mysql_query( "select ContactPreference from driver_settings where UserID = $user_id" );
		if($r) while($rs = mysql_fetch_array( $r ))
			$_SESSION['UserString'][$user_id] .= ", $rs[ContactPreference]";
	}
		
	$sql = "SELECT CaretakerID, CaretakerBirthday, HasMemoryLoss from rider_preferences where UserID = $user_id";
	$r = mysql_query( $sql );
	if(mysql_num_rows($r) > 0) {
		$ctdob = mysql_fetch_assoc($r);
		if($ctdob["HasMemoryLoss"] == "ML1" || $ctdob["HasMemoryLoss"] == "ML2") $_SESSION['UserString'][$user_id] .= ", ".$ctdob["HasMemoryLoss"];
		if($ctdob["CaretakerID"] != "" && $ctdob["CaretakerBirthday"] != '') $_SESSION['UserString'][$user_id] .= " & 2nd ".date('Y-m-d',strtotime($ctdob["CaretakerBirthday"]));
	}
	$_SESSION['UserString'][$user_id] .= "<BR>";
	if(!$reset)
		echo $_SESSION['UserString'][$user_id];
}

function get_user_id_by_username( $username ) {
    $safe_username = mysql_real_escape_string($username);

    $sql = "SELECT UserID FROM users WHERE UserName = '$safe_username'";

    $result = mysql_query($sql);

    if ($result) {
        $row = mysql_fetch_array($result);

        return $row['UserID'];
    } else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), "Error fetching UserID for username $username", $sql);
		return FALSE;
    }
}

function set_role_for_user( $user_id, $franchise, $role, $ReadOnly = 0 ) {
    if (if_user_has_role( $user_id, $franchise, $role )) {
        return TRUE;
    }

    // Clear the memoization for the role
    global $USER_user_role_memo;
    unset($USER_user_role_memo[$user_id][$role]);

    $safe_uid = mysql_real_escape_string($user_id);
    $safe_role = mysql_real_escape_string($role);
	$safe_franchise = mysql_real_escape_string($franchise);

    $sql = "INSERT INTO user_role (UserID, FranchiseID, Role, ReadOnly) VALUES ($safe_uid, $safe_franchise, '$safe_role', $ReadOnly)";

    $result = mysql_query($sql);
    if ($result) {
        return TRUE;
    } else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), "Error adding role $role to user $user_id", $sql);
		return FALSE;
    }

}

function set_primary_phone_for_user($user_id, $phone_id){
	$safe_user_id = mysql_real_escape_string($user_id);
	$safe_phone_id = mysql_real_escape_string($phone_id);
	
	$sql = "UPDATE `user_phone` SET IsPrimary = 'No' WHERE UserID = $safe_user_id;";
	$result = mysql_query($sql);
	
	if(!$result){
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), "Error removing all primary numbers for user $user_id while setting new primary number", $sql);
		return FALSE;
	}
	
	$sql ="UPDATE `user_phone` SET IsPrimary = 'Yes' WHERE UserID = $safe_user_id AND PhoneID = $safe_phone_id;";
	$result = mysql_query($sql);
	
	if($result){
		return TRUE;
	} else{
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), "Error setting primary number for phoneid $phone_id for user $user_id", $sql);
		return FALSE;
	}
}

function update_user_address($user_id, $address_id, $address, $type){
	update_address($address_id, $address);
	$safe_user_id = mysql_real_escape_string($user_id);
	$safe_address_id = mysql_real_escape_string($address_id);
	$safe_type = mysql_real_escape_string($type);
	
	$sql = "UPDATE `user_address` SET `AddressType` = '$safe_type' WHERE `UserID` = $safe_user_id AND `AddressID` = $safe_address_id  LIMIT 1 ;";
	$result = mysql_query($sql);
	
	if($result)
		return true;
	else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), "Error updating user's $user_id address $address_id to address type $type", $sql);
		return FALSE;
	}
}
function update_user_background($user_id, $aliases, $background_status, $felony, $felony_description){
	$safe_user_id = mysql_real_escape_string($user_id);
	$safe_aliases = mysql_real_escape_string($aliases);
	$safe_felony = mysql_real_escape_string($felony);
	$background_status = mysql_real_escape_string($background_status);
	$safe_felony_description = mysql_real_escape_string($felony_description);
	
	$sql = "UPDATE `users` SET `HasFelony` = '$safe_felony',
												 `FelonyDescription` = '$safe_felony_description',
												 `BackgroundCheck` = '$background_status' WHERE `users`.`UserID` = $safe_user_id;";
	$result = mysql_query($sql);
	
	if($result){
		$sql = "INSERT INTO `background_check_alias` (`AliasID` , `UserID` , `Alias`)
																	VALUES ( NULL , '$safe_user_id', '$safe_aliases');";
		$result = mysql_query($sql);
		
		if($result){
			return true;
		} else {
			rc_log_db_error(PEAR_LOG_ERR, mysql_error(), "error adding new aliases for user: $user_id s", $sql);
		return FALSE;
		}
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), "error updating user: $user_id background information", $sql);
		return FALSE;
	}
}

function get_all_active_applied_users($franchise) {
	$safe_franchise_id = mysql_real_escape_string($franchise);
    $sql = "SELECT UserID, FirstName, MiddleInitial, LastName, ApplicationDate, Role FROM `users` NATURAL JOIN person_name NATURAL JOIN user_role WHERE `Status` = 'ACTIVE' AND 
                    `ApplicationStatus` = 'APPLIED' AND UserID IN (SELECT UserID FROM user_role WHERE FranchiseID = $safe_franchise_id ) ORDER BY LastName, FirstName;";

    $result = mysql_query($sql);

	if ($result) {
        $result_rows = array();
        while ($row = mysql_fetch_array($result)) {
            $result_rows[] = $row;
        }
		return $result_rows;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get all active applied users", $query);
	}

    return FALSE;
}

function get_all_inactive_users($franchise) {
	$safe_franchise = mysql_real_escape_string($franchise);
	
    $sql = "SELECT * FROM `users` NATURAL JOIN person_name
    	WHERE NOT users.Status = 'ACTIVE' AND UserID IN (SELECT UserID FROM user_role WHERE FranchiseID = $safe_franchise ) 
    	ORDER BY LastName, FirstName; ";

    $result = mysql_query($sql);

	if ($result) {
        $result_rows = array();
        while ($row = mysql_fetch_array($result)) {
            $result_rows[] = $row;
        }
		return $result_rows;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get all inactive users", $query);
	}

    return FALSE;
}

function get_all_supporting_friends($franchise) {
	$safe_franchise = mysql_real_escape_string($franchise);
	
	$sql = 
"SELECT users.UserID, sn.FirstName, sn.MiddleInitial, sn.LastName , FirstLedger, ur.UserID AS RiderUserID,  rn.FirstName AS RiderFirstName, rn.MiddleInitial AS RiderMiddleInitial,
	    rn.LastName AS RiderLastName, ur.Status, rider.AnnualFeePaymentDate, users.RechargeThreshold
FROM (SELECT UserID FROM user_role WHERE Role = 'Supporter' AND FranchiseID = 2) AS support_status
NATURAL JOIN users
LEFT JOIN person_name AS sn ON sn.PersonNameID = users.PersonNameID
LEFT JOIN (Select EntityID, MIN(EffectiveDate) AS FirstLedger FROM ledger WHERE EntityType = 'USER' GROUP BY EntityID)
           AS sl on sl.EntityID = users.UserID 
LEFT JOIN supporter_rider AS sr ON sr.SupporterUserID = sl.EntityID
LEFT JOIN users AS ur on ur.UserID = sr.RiderUserID
LEFT JOIN person_name AS rn ON rn.PersonNameID = ur.PersonNameID
LEFT JOIN rider ON sr.RiderUserID = rider.UserID
WHERE users.Status = 'ACTIVE' ORDER BY LastName, FirstName;";
	$result = mysql_query($sql);
	
	if($result){
		$result_rows = array();
        while ($row = mysql_fetch_array($result)) {
            $result_rows[] = $row;
        }
		return $result_rows;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get all inactive users", $query);
		return FALSE;
	}
}

function get_all_driver_info($franchise) {
	$safe_franchise = mysql_real_escape_string($franchise);
	
	$sql = "SELECT PersonNameID, users.UserID, FirstName, LastName, MiddleInitial, DriverPictureWaiver, 
								 LicenseExpireDate,WelcomePackageSent,FirstDriveFollowup,
								 DriverAgreementRecorded,PolicyExpirationDate, DriverApprovalDate, 
								 InsuranceVerified, CopyofLicenseOnFile, CopyOfInsuranceCardOnFile 
		FROM (users  NATURAL JOIN user_role 
								 NATURAL JOIN person_name 
								 NATURAL JOIN driver)  
	  LEFT JOIN driver_insurance ON users.UserID = driver_insurance.UserID 
	  WHERE Role = 'Driver' AND Status = 'ACTIVE' 
	  AND users.UserID IN (SELECT UserID 
	 											 FROM user_role 
	 											 WHERE FranchiseID = $safe_franchise ) 
	 	ORDER BY LastName, FirstName;";
	$result = mysql_query($sql);
	
	if($result){
		$result_rows = array();
        while ($row = mysql_fetch_array($result)) {
            $result_rows[] = $row;
        }
		return $result_rows;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get all driver users", $query);
		return FALSE;
	}
}

function get_all_admins($franchise) {
	$safe_franchise = mysql_real_escape_string($franchise);
	
	$sql = "SELECT UserID, FirstName, MiddleInitial, LastName FROM users NATURAL JOIN user_role NATURAL JOIN person_name WHERE (Role = 'Franchisee' || Role = 'FullAdmin') AND `Status` = 'ACTIVE' AND users.UserID IN (SELECT UserID FROM user_role WHERE FranchiseID = $safe_franchise) GROUP BY users.UserID ORDER BY LastName, FirstName ";
	$result = mysql_query($sql);

	if($result){
		$result_rows = array();
        while ($row = mysql_fetch_array($result)) {
            $result_rows[] = $row;
        }
		return $result_rows;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get all admin users", $query);
		return FALSE;
	}
}

function get_care_facility_admins($franchise){
	$safe_franchise = mysql_real_escape_string($franchise);
	
	$sql = "SELECT UserID, FirstName, MiddleInitial, LastName FROM users NATURAL JOIN user_role NATURAL JOIN person_name WHERE Role = 'CareFacilityAdmin' AND users.UserID IN (SELECT UserID FROM user_role WHERE FranchiseID = $safe_franchise) ORDER BY LastName, FirstName;";
	$result = mysql_query($sql);
	
	if($result){
		$result_rows = array();
        while ($row = mysql_fetch_array($result)) {
            $result_rows[] = $row;
        }
		return $result_rows;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get all admin users", $query);
		return FALSE;
	}
}

function get_all_active_rider_user_info($franchise, $sort_by_last_name = TRUE) {
    $safe_franchise = mysql_real_escape_string($franchise);

    $sql = "SELECT *
            FROM users NATURAL JOIN user_role NATURAL JOIN rider NATURAL JOIN person_name
            WHERE Role = 'Rider' AND RiderStatus = 'Active' AND users.UserID IN (SELECT UserID FROM user_role WHERE FranchiseID = $safe_franchise)
            GROUP BY users.UserID";

		//$sql = "SELECT PersonNameID, FirstName, LastName, UserID, DateOfBirth, AnnualFeePaymentDate, IFNULL(DATEDIFF( TIMESTAMP(DATE_ADD(`AnnualFeePaymentDate`, INTERVAL 1 YEAR)), NOW()), '-9000') AS 'DaysOnAnnualFee', IF(DATEDIFF( TIMESTAMP(DATE_ADD(`AnnualFeePaymentDate`, INTERVAL 1 YEAR)), NOW()) > 30,1,IF(DATEDIFF( TIMESTAMP(DATE_ADD(`AnnualFeePaymentDate`, INTERVAL 1 YEAR)), NOW()) > 0, 0,IF(DATEDIFF( TIMESTAMP(DATE_ADD(`AnnualFeePaymentDate`, INTERVAL 1 YEAR)), NOW()) <= 0, -1, -2) )) AS SortAnnualFee,ApplicationDate, WelcomePackageSentDate, FirstRideDate, FirstRideFollowupDate, RechargeThreshold,RiderWaiverReceived, (SELECT DesiredArrivalTime FROM (SELECT RiderUserID, DesiredArrivalTime FROM link UNION SELECT RiderUserID, DesiredArrivalTime FROM link_history) t2 WHERE RiderUserID = users.UserID ORDER BY DesiredArrivalTime ASC LIMIT 1) AS FirstRide FROM users NATURAL JOIN user_role NATURAL JOIN rider NATURAL JOIN person_name WHERE Role = 'Rider' AND RiderStatus = 'Active' ORDER BY SortAnnualFee DESC, $sort_clause";

    $result = mysql_query($sql) or die($sql);

	if ($result) {
        $result_rows = array();
        $cf_fee_dates = get_care_facility_annual_fee_payment_dates_by_user();
        
       	for ($i = 0; $row = mysql_fetch_array($result); $i++) {
            $result_rows[$i] = $row;
            $result_rows[$i]['CareFacility'] = false;
            if (isset($cf_fee_dates[$row['UserID']])){
            	$result_rows[$i]['AnnualFeePaymentDate'] = $cf_fee_dates[$row['UserID']]['AnnualFeePaymentDate'];
            	$result_rows[$i]['CareFacility'] = $cf_fee_dates[$row['UserID']]['CareFacilityID'];
            }
            $result_rows[$i]['DaysOnAnnualFee'] = floor(((strtotime($result_rows[$i]['AnnualFeePaymentDate']) + (60 * 60 * 24 * 365)) - time()) / (60 * 60 * 24));
            if($result_rows[$i]['AnnualFeePaymentDate'] == null)
        		$result_rows[$i]['sort'] = 1;
            else if($result_rows[$i]['DaysOnAnnualFee'] > 30)
            	$result_rows[$i]['sort'] = -2;	
            else if($result_rows[$i]['DaysOnAnnualFee'] <= 30 && $result_rows[$i]['DaysOnAnnualFee'] >= 0)
            	$result_rows[$i]['sort'] = -1;
           	else 
           		$result_rows[$i]['sort'] = 0;
        	
        }
		return multisort($result_rows, array('sort',($sort_by_last_name) ? 'LastName' : 'FirstName'));
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get all active rider users", $sql);
	}

    return FALSE;
}

function get_all_inactive_rider_user_info($franchise, $sort_by_last_name = TRUE) {
    $safe_franchise = mysql_real_escape_string($franchise);

    $sql = "SELECT FirstName, LastName, Status, UserID, DateOfBirth, AnnualFeePaymentDate, ApplicationDate, WelcomePackageSentDate, FirstRideDate, FirstRideFollowupDate, RechargeThreshold, RiderPictureWaiver, RiderWaiverReceived
            FROM users NATURAL JOIN user_role NATURAL JOIN rider NATURAL JOIN person_name
            WHERE Role = 'Rider' AND NOT RiderStatus = 'Active' AND users.UserID IN (SELECT UserID FROM user_role WHERE FranchiseID = $safe_franchise)
            	and users.Status = 'ACTIVE'
            GROUP BY users.UserID";
		
		//$sql = "SELECT PersonNameID, FirstName, LastName, UserID, DateOfBirth, AnnualFeePaymentDate, IFNULL(DATEDIFF( TIMESTAMP(DATE_ADD(`AnnualFeePaymentDate`, INTERVAL 1 YEAR)), NOW()), '-9000') AS 'DaysOnAnnualFee', IF(DATEDIFF( TIMESTAMP(DATE_ADD(`AnnualFeePaymentDate`, INTERVAL 1 YEAR)), NOW()) > 30,1,IF(DATEDIFF( TIMESTAMP(DATE_ADD(`AnnualFeePaymentDate`, INTERVAL 1 YEAR)), NOW()) > 0, 0,IF(DATEDIFF( TIMESTAMP(DATE_ADD(`AnnualFeePaymentDate`, INTERVAL 1 YEAR)), NOW()) <= 0, -1, -2) )) AS SortAnnualFee,ApplicationDate, WelcomePackageSentDate, FirstRideDate, FirstRideFollowupDate, RechargeThreshold,RiderWaiverReceived, (SELECT DesiredArrivalTime FROM (SELECT RiderUserID, DesiredArrivalTime FROM link UNION SELECT RiderUserID, DesiredArrivalTime FROM link_history) t2 WHERE RiderUserID = users.UserID ORDER BY DesiredArrivalTime ASC LIMIT 1) AS FirstRide FROM users NATURAL JOIN user_role NATURAL JOIN rider NATURAL JOIN person_name WHERE Role = 'Rider' AND RiderStatus = 'Active' ORDER BY SortAnnualFee DESC, $sort_clause";

    $result = mysql_query($sql) or die($sql);

	if ($result) {
        $result_rows = array();
        $cf_fee_dates = get_care_facility_annual_fee_payment_dates_by_user();
        
       	for ($i = 0; $row = mysql_fetch_array($result); $i++) {
            $result_rows[$i] = $row;
            $result_rows[$i]['CareFacility'] = false;
            if (isset($cf_fee_dates[$row['UserID']])) {
            	$result_rows[$i]['AnnualFeePaymentDate'] = $cf_fee_dates[$row['UserID']]['AnnualFeePaymentDate'];
            	$result_rows[$i]['CareFacility'] = $cf_fee_dates[$row['UserID']]['CareFacilityID'];
            }
            $result_rows[$i]['DaysOnAnnualFee'] = floor(((strtotime($result_rows[$i]['AnnualFeePaymentDate']) + (60 * 60 * 24 * 365)) - time()) / (60 * 60 * 24));
            if($result_rows[$i]['AnnualFeePaymentDate'] == null)
        		$result_rows[$i]['sort'] = 1;
            else if($result_rows[$i]['DaysOnAnnualFee'] > 30)
            	$result_rows[$i]['sort'] = -2;	
            else if($result_rows[$i]['DaysOnAnnualFee'] <= 30 && $result_rows[$i]['DaysOnAnnualFee'] >= 0)
            	$result_rows[$i]['sort'] = -1;
           	else 
           		$result_rows[$i]['sort'] = 0;
        	
        }
		return multisort($result_rows, array('sort',($sort_by_last_name) ? 'LastName' : 'FirstName'));
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get all active rider users", $sql);
	}

    return FALSE;
}



?>
