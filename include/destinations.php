<?php

require_once('include/database.php');
require_once('include/address.php');
require_once('include/rc_log.php');

/**
 * Gets all destinations for a franchise, organized by destination group.
 * @param $franchise_id ID of franchise to get destinations for
 * @return hash with two keys:  Groups, Destinations.
 *          Groups contains destination groups as hashes (keys:  Groups/Destinations)
 *          Destinations contains actual destination IDs.
 */
function get_franchise_destinations( $franchise_id ) {

    return get_destinations_by_franchise_and_group($franchise_id, 0);
}


/**
 * Gets all destinations for a franchise and destination group, including 
 * subgroups and destinations within that subgroup.
 * @param $franchise_id ID of franchise to get destinations for
 * @param $destination_group_id ID of destnation group to get destinations for.
 * @return hash with two keys:  Groups, Destinations.
 *          Groups contains array of destination groups as hashes 
 *          (keys:  DestinationGroupID, ParentGroupID, Name, FranchiseID, 
 *                  Groups, Destinations)
 *          Destinations contains array of destination hashes
 */
function get_destinations_by_franchise_and_group($franchise_id, $destination_group_id) {
    $safe_franchise_id = mysql_real_escape_string($franchise_id);
    $safe_destination_group_id = mysql_real_escape_string($destination_group_id);

    $dest_sql = "SELECT DestinationID, DestinationGroupID, Name, AddressID,
                       IsPublic, FranchiseID, DestinationDetail, PhoneNumber, AdditionalMinutes,
                       (SELECT GROUP_CONCAT(TagName SEPARATOR ' ') 
                        FROM destination_tag NATURAL JOIN destination_tag_list
                        WHERE destination_tag.DestinationID = destination.DestinationID
                        GROUP BY destination_tag.DestinationID) AS TagString
                FROM destination LEFT JOIN phone ON destination.PhoneID = phone.PhoneID
                WHERE FranchiseID = $safe_franchise_id AND
                      DestinationGroupID = $safe_destination_group_id AND
                      IsPublic = 'Yes' AND IsPublicApproved = 'Yes'
                ORDER BY DestinationID ASC";

    $group_sql = "SELECT DestinationGroupID, ParentGroupID, Name, FranchiseID
                 FROM destination_group
                 WHERE FranchiseID = $safe_franchise_id AND
                       ParentGroupID = $safe_destination_group_id
                 ORDER BY Name ASC";

    $dest_result = mysql_query($dest_sql);
    if ($dest_result) {
        while ($row = mysql_fetch_array($dest_result, MYSQL_ASSOC)) {
            $destinations[] = $row;
        }
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Error getting destinations for F$franchise_id G$destination_group_id", $dest_sql);
    }


    $group_result = mysql_query($group_sql);
    if ($group_result) {
        $groups = array();
        while ($row = mysql_fetch_array($group_result, MYSQL_ASSOC)) {
            $groups[] = $row;
        }

        foreach ($groups as &$group) {
            $sub_destinations = get_destinations_by_franchise_and_group($franchise_id, 
                                                                        $group['DestinationGroupID']);
            $group['Groups'] = $sub_destinations['Groups'];
            $group['Destinations'] = $sub_destinations['Destinations'];
        }
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Error getting subgroups for F$franchise_id G$destination_group_id", $group_sql);
    }


                       
    return array('Groups' => $groups, 'Destinations' => $destinations);
}


/**
 * Gets all rider-specific destinations (any entries for that rider in the 
 * rider-destination table).
 * @param $rider_user_id ID of rider to get destinations for
 * @return array of destination hashes (ID, Name, IsPublic, Address info); 
 *          empty array if no destinations; FALSE on error
 */
function get_rider_destinations( $rider_user_id ) {
    if (!$rider_user_id) { return FALSE; }

    $destinations = array();
    $safe_rider_user_id = mysql_real_escape_string($rider_user_id);
	
    $sql = "SELECT DestinationID, Name, IsPublic, destination.PhoneID, PhoneNumber, Ext, AddressID, Address1, Address2, City,
                   State, ZIP5, ZIP4, IsPublicApproved, DestinationDetail,
                   (SELECT GROUP_CONCAT(TagName SEPARATOR ' ') 
                    FROM destination_tag NATURAL JOIN destination_tag_list
                    WHERE destination_tag.DestinationID = destination.DestinationID
                    GROUP BY destination_tag.DestinationID) AS TagString,
                    Latitude, Longitude, AdditionalMinutes
            FROM rider_destination NATURAL JOIN destination NATURAL JOIN address 
                 LEFT JOIN phone ON destination.PhoneID = phone.PhoneID
            WHERE rider_destination.UserID = $safe_rider_user_id
            ORDER BY FIELD(Name, 'Default Home'), Name DESC";
    // TODO:  What's a sensible order?
    $result = mysql_query($sql);

    if ($result) {
        while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
            $destinations[] = $row;
        }
    } else {
        rc_log_db_error(PEAR_LOG_WARNING, mysql_error(),
                        "Could not get destinations for rider $rider_user_id", $sql);
        $destinations = FALSE;
    }
	
    return $destinations;
}

function in_add_destination_blacklist() {
    $sql = "select AD_ind from rider where UserID = ".get_affected_user_id();
    $result = mysql_query($sql);
    if($result) {
        $row = mysql_fetch_array($result,MYSQL_ASSOC);
        if($row["AD_ind"] == 0) return TRUE;
        else return FALSE;
    } else {
        rc_log_db_error(PEAR_LOG_WARNING, mysql_error(),
                        "Could not get information for rider in_add_destination_blacklist()", $sql);
    }
}


/**
 * Creates a destination in the database.  The address entry will also be created.
 * @param $name Name for destination
 * @param $address Hash containing address info
 * @param $is_public Boolean indicating whether destination may be shared with the world
 * @param $is_public_approved Boolean indicating whether destination was approved by an admin
 *                            to share with the world.
 * @param $franchise_id ID of franchise to own the destination (generally the user's 
 *                      owning franchise?)
 * @param $destination_group Optional ID of destination group this new destination 
 *                           should belong to.
 * @return ID of new destination; FALSE on error.
 */
function create_new_destination($name, $address, $franchise_id, $is_public, $is_public_approved = FALSE,
                                $destination_group = -1, $destination_phone = NULL, $destination_detail = NULL, 
                                $destination_phone_ext = NULL, $AdditionalMinutes = 0) {
    
    if(in_add_destination_blacklist())
        return FALSE;

    $address_id = add_address($address, TRUE);
    if ($address_id === FALSE) {
        return FALSE;
    }
		if($AdditionalMinutes == '' || $AdditionalMinutes == NULL) $AdditionalMinutes = 0;
		
    $destination_id = create_destination_for_address_id($name, $address_id, $franchise_id,
                                                        $is_public, $is_public_approved, $destination_group,
                                                        $destination_phone, $destination_detail, $destination_phone_ext,
                                                        $AdditionalMinutes);

    return $destination_id;
}

/**
 * Creates a destination in the database given an existing address ID.
 * @param $name Name for destination
 * @param $address_id ID of existing address
 * @param $is_public Boolean indicating whether destination may be shared with the world
 * @param $is_public_approved Boolean indicating whether destination was approved by an admin
 *                            to share with the world.
 * @param $franchise_id ID of franchise to own the destination (generally the user's 
 *                      owning franchise?)
 * @param $destination_group Optional ID of destination group this new destination 
 *                           should belong to.
 * @return ID of new destination; FALSE on error.
 */
function create_destination_for_address_id($name, $address_id, $franchise_id,
                                           $is_public, $is_public_approved = FALSE,
                                           $destination_group_id , $destination_phone, 
                                           $destination_detail = NULL, $destination_phone_ext = NULL, 
                                           $AdditionalMinutes = 0) {
    $return = FALSE;

    $safe_name = mysql_real_escape_string($name);
    $safe_public = ($is_public) ? 'Yes' : 'No';
    $safe_approved = ($is_public_approved) ? 'Yes' : 'No';
    $safe_franchise_id = mysql_real_escape_string($franchise_id);
	$safe_destination_phone = ($destination_phone != '') ? add_phone_number($destination_phone, 'UNKNOWN', 'N', 0, $destination_phone_ext) : 'NULL';
    $safe_destination_group_id = ($destination_group_id == 0 || $destination_group_id == -1) ? 'NULL' : mysql_real_escape_string($destination_group_id);
    $safe_address_id = mysql_real_escape_string($address_id); 
    $safe_detail = (is_null($destination_detail)) ? 'NULL' : 
                                    "'" . mysql_real_escape_string($destination_detail) . "'"; 

    $sql = "INSERT INTO destination (DestinationGroupID, Name, AddressID, IsPublic, 
                                     IsPublicApproved, FranchiseID, PhoneID, DestinationDetail, AdditionalMinutes)
            VALUES ($safe_destination_group_id, '$safe_name', $safe_address_id, 
                    '$safe_public', '$safe_approved', $safe_franchise_id, $safe_destination_phone,
                    $safe_detail, $AdditionalMinutes)";
 		#echo $sql;
    $result = mysql_query($sql);

    if ($result) {
       $return = mysql_insert_id(); 
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not add destination for address $address_id", $sql);
    }

    return $return;
}

function edit_destination($destination_id, $name, $address, 
		$franchise_id, $destination_group_id, $destination_phone, 
		$destination_detail, $is_public, $is_public_approved = false, 
		$destination_phone_ext = '', $is_local_area = TRUE, $on_demand = FALSE, $AdditionalMinutes = 0){
	
    $is_local_area = $is_local_area == null ? 0 : $is_local_area;
    $on_demand = $on_demand == null ? 0 : $on_demand;
	$safe_destination_id = mysql_real_escape_string( $destination_id );
	$safe_name = mysql_real_escape_string( $name );
	$safe_franchise_id = mysql_real_escape_string( $franchise_id );
	$safe_destination_group_id = ($destination_group_id == NULL || $destination_group_id == -1) ? '' : 'DestinationGroupID = ' . mysql_real_escape_string($destination_group_id) . ',';
    $safe_detail = (is_null($destination_detail)) ? 'NULL' : 
                                    "'" . mysql_real_escape_string($destination_detail) . "'";
	if ($is_public !== 'NOEDIT') {
        $public_clause = $is_public ? " IsPublic = 'Yes'," : " IsPublic = 'No',";
        $public_clause .= " IsPublicApproved = '".($is_public_approved ? 'Yes' : 'No')."',";
    }
	update_address($address['id'],$address);
	$destination = get_destination($safe_destination_id);
	$phone = 'NULL';
	if($destination_phone != ''){
		if($destination['PhoneID'] == NULL){
			$phone = add_phone_number($destination_phone, 'UNKNOWN', 'N', 0, $destination_phone_ext);
		} else {
			$phone = update_phone_number( $destination['PhoneID'], $destination_phone, 'UNKNOWN', 'N', 0, $destination_phone_ext);
		}
	} else if($destination_phone == '' && $destination['PhoneID'] != NULL){
		$phone = 'NULL';
		delete_phone_number($destination['PhoneID']);
	}
	
	$sql = "UPDATE destination SET $safe_destination_group_id
                                   Name = '$safe_name', 
                                   FranchiseID = $safe_franchise_id, 
                                   PhoneID = $phone, 
                                   $public_clause
                                   DestinationDetail = $safe_detail,
                                   is_local_area_override = $is_local_area,
                                   on_demand_override = $on_demand,
                                   AdditionalMinutes = $AdditionalMinutes
                               WHERE DestinationID = $safe_destination_id LIMIT 1 ;";

	$result = mysql_query($sql) or die(mysql_error());
	if($destination_phone == '' && $destination['PhoneID'] != NULL){
		delete_phone_number($destination['PhoneID']);
	}
	if(!$result){
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not update destination for destination $destination_id", $sql);
	}
}

/**
 * Creates a rider-destination association.  Destination must already exist.
 * @param $rider_user_id ID of rider to associate with destination
 * @param $destination_id ID of destination to associate with rider.
 * @return TRUE on success, FALSE on failure.
 */
function add_destination_for_rider($rider_user_id, $destination_id) {
	if(rider_has_destination($rider_user_id, $destination_id))
		return TRUE;
    $safe_rider_user_id = mysql_real_escape_string($rider_user_id);
    $safe_destination_id = mysql_real_escape_string($destination_id);

    $sql = "INSERT IGNORE INTO rider_destination (UserID, DestinationID)
            VALUES ($safe_rider_user_id, $safe_destination_id)";

    $result = mysql_query($sql) or die(mysql_error());
    if (!$result) {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not associate rider $rider_user_id with destination $destination_id",
                        $sql);
    }
    return $result;
}

/**
 * Removes a rider-destination association.  
 * @param $rider_user_id ID of rider to remove destination association from.
 * @param $destination_id ID of destination to de-associate.
 * @return TRUE on success, FALSE on failure.
 */
function remove_destination_for_rider($rider_user_id, $destination_id) {
    $safe_rider_user_id = mysql_real_escape_string($rider_user_id);
    $safe_destination_id = mysql_real_escape_string($destination_id);

    $sql = "DELETE FROM rider_destination
            WHERE UserID = $safe_rider_user_id AND
                  DestinationID = $safe_destination_id";

    $result = mysql_query($sql);
    if (!$result) {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not remove rider/dest association for $rider_user_id, $destination_id", 
                        $sql);
    }
    return $result;
}



function get_destination_selection_widget( $franchise_id, $next_href, $id_suffix = '' ) {
    $destinations = get_franchise_destinations($franchise_id);    
    
    $widget = get_display_list_for_group_dest($destinations, $next_href, $id_suffix);

    return $widget;
}

function get_display_list_for_group_dest( $group_dest_hash, $href, $id_suffix = '' ) {
    if (//!isset($group_dest_hash) ||
        !array_key_exists('Destinations', $group_dest_hash)) {
        return '';
    }

    $list = '<ul class="list">';
	
    foreach ($group_dest_hash['Groups'] as $group) {
        $list .= "\n<li>" . '<span onclick="toggle_public_destination_group(\'' .
                 urlencode($group['Name']) . $id_suffix . "')\">" .
                 htmlspecialchars($group['Name']) . '</span>';
        $list .= "\n<div id=\"" . urlencode($group['Name']) . $id_suffix . '" style="display:none">';
        $list .= get_display_list_for_group_dest($group, $href, $id_suffix);
        $list .= "\n</div>";
        $list .= '</li>';
    }
    //echo $group_dest_hash['Name'];
    //echo '<pre>';
    //print_r($group_dest_hash);
   // echo '</pre>';
    
    if (isset($group_dest_hash['Destinations'])) {
        foreach ($group_dest_hash['Destinations'] as $dest) {
            $bp_prefix = (strpos($dest['TagString'], 'BUSINESS_PARTNER')) ? '** ' : '';
            $bp_suffix = (strpos($dest['TagString'], 'BUSINESS_PARTNER')) ? ' (PARTNER)' : '';
            $dest_name = "{$bp_prefix}{$dest['Name']}{$bp_suffix} - {$dest['DestinationDetail']}";
            $list .= "<li><a class=\"destination_link\" href=\"" .
                     $href . $dest['DestinationID'] . 
                     "\" value=\"{$dest['DestinationID']}\" title=\"{$dest['PhoneNumber']}\">{$dest_name}</a></li>";
        }
    } 
    $list .= '</ul>';


    // TODO:  Nice UI to handle the case where there is no dest or subgroup in a group
    // TODO:  Nice JS to decorate the links - remove link - add AJAXy UI flow

    return $list;
}

function get_destination($destination_id) {
    $safe_dest_id = mysql_real_escape_string($destination_id);

    $sql = "SELECT DestinationID, DestinationGroupID, Name, IsPublic, IsPublicApproved, destination.PhoneID, PhoneNumber, Ext, DestinationDetail,
                   Address1, Address2, City, State, ZIP5, ZIP4, Latitude, Longitude, AddressID, VerifySource, FranchiseID, DestinationGroupID, is_local_area_override, on_demand_override, AdditionalMinutes
            FROM (destination NATURAL JOIN address) LEFT JOIN phone ON  destination.PhoneID = phone.PhoneID
            WHERE destination.DestinationID = $safe_dest_id";

    $result = mysql_query($sql);

    if ($result) {
        $destination = mysql_fetch_array($result, MYSQL_ASSOC);  // DestinationID is PK; should only be one row
        // TODO:  check for # of rows
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Could not get destination $destination_id", $sql);
        $destination = FALSE;
    }
    return $destination;
}

/* Given at least Address, Line 1, fetch an address row from the database, hopefully with geocoding information
*/
function get_destination_from_address($address1, $address2 = '', $city = '', $state = '', $zip = '') {
	$sql = "SELECT * FROM address where not Latitude = '' and not Longitude = '' and Address1 = '".mysql_real_escape_string($address1)."'"
		.($address2 != '' ? " and Address2 = '".mysql_real_escape_string($address2)."'" : "")
		.($city != '' ? " and City = '".mysql_real_escape_string($city)."'" : "")
		.($state != '' ? " and State = '".mysql_real_escape_string($state)."'" : "")
		.($zip != '' ? " and ZIP5 = '".mysql_real_escape_string($zip)."'" : "");
	$r = mysql_query($sql);
	if(mysql_num_rows($r) > 0) $rs = mysql_fetch_array($r);
	else $rs = array();
	return $rs;
}	

function display_destination($destination_id) {
    $destination = get_destination($destination_id);

    if ($destination === FALSE) {
        echo "BAD DESTINATION";  // TODO:  better error handling
        return;
    }

    // TODO:  Make this better.

    echo "<p><strong>{$destination['Name']}</strong>" . ( ($destination['DestinationDetail']) ?  "<br />{$destination['DestinationDetail']}" : '' ) . "<br />{$destination['Address1']}<br />" .
         (($destination['Address2']) ? "{$destination['Address2']}<br />" : '') . 
         "{$destination['City']}, {$destination['State']}  {$destination['ZIP5']}<br>
         {$destination['PhoneNumber']}"
         .($destination['Ext'] != '' ? " x".$destination['Ext'] : "")
         ."</p>";
}

function get_destination_display_string($destination_entry) {
    $bp_prefix = (strpos($destination_entry['TagString'], 'BUSINESS_PARTNER')) ? '** ' : '';
    return "{$bp_prefix}{$destination_entry['Name']}" . ($destination_entry['DestinationDetail'] ?  "- {$destination_entry['DestinationDetail']}" : "")  . " - {$destination_entry['Address1']}, {$destination_entry['City']}, {$destination_entry['State']}";
}

/**
 * Gets an HTML string containing a nice representation of the rider's destination list.
 * @param $rider_user_id ID of rider
 * @param $next_href HREF to prepend links with
 * @return String of self-contained HTML widget with links.
 */
function get_rider_destination_list( $rider_user_id, $next_href ) {
    $destinations = get_rider_destinations($rider_user_id);

    $html = '<ul class="list">';
    foreach ($destinations as $dest) {
        $html .= "<li><a class=\"destination_link\" href=\"" .
                     $next_href . $dest['DestinationID'] . 
                     "\">{$dest['Name']} - {$dest['Address1']}, {$dest['City']}, {$dest['State']}</a></li>";

    }
    $html .= '</ul>';

    return $html;
}

function get_rider_default_home_destination( $rider_user_id ){
	$safe_rider_user_id = mysql_real_escape_string($rider_user_id);

    $sql = "SELECT IFNULL(
                ( SELECT DestinationID FROM rider_destination NATURAL JOIN destination
                  WHERE UserID = $safe_rider_user_id AND Name LIKE 'Home' LIMIT 1 ), 0 ) AS HomeID,
                   IFNULL(
                ( SELECT DestinationID FROM rider_destination NATURAL JOIN destination
                  WHERE UserID = $safe_rider_user_id AND Name LIKE 'Default Home' LIMIT 1 ), 0) AS DefaultHomeID";
        

	$result = mysql_query($sql);

	if($result){
		$data = mysql_fetch_array($result);
		if($data['DefaultHomeID'] != 0)
			return $data['DefaultHomeID'];
		else if($data['HomeID'] != 0)
			return $data['HomeID'];
		else
			return FALSE;
	} else {
		//error
	}
}

function get_rider_destination_selector($rider_user_id, $num, $initial_prompt = FALSE, $selected_id = FALSE, $visible = TRUE, $destinations = NULL) {
    if($destinations === NULL)
    	$rider_destinations = get_rider_destinations( $rider_user_id );
    else
    	$rider_destinations = $destinations;
    
    $sql = "select PhoneNumber from phone,franchise,user_role where phone.phoneid = franchise.phoneid and franchise.franchiseid = user_role.franchiseid and user_role.UserID = $rider_user_id";
    $r = mysql_query($sql);
    $phone = '';
    if(mysql_num_rows($r) > 0) {
    	$rs = mysql_fetch_array($r);
    	$phone = $rs["PhoneNumber"];
    }
    $ret = "<select id=\"destination_selector[$num]\" name=\"destination_selector[$num]\" onChange=\"checkYourself('$phone'); checkOnDemand(this.options[this.selectedIndex].value,$num); \">";
	
    if ($initial_prompt !== FALSE) {
        $ret .= '<option value="NOTSET" >' . $initial_prompt . '</option>';
    }
	
    if ($rider_destinations && count($rider_destinations) > 0) {
        foreach ($rider_destinations as $dest_arr) {
            $ret .= '<option title="' . $dest_arr['PhoneNumber'] . ($dest_arr['Ext'] != ''?' x'.$dest_arr['Ext']:'').'" value="-' . $dest_arr['DestinationID'] . '"' .
                    (($dest_arr['DestinationID'] == $selected_id) ? ' selected="selected" ' : ' ') . 
                    '>' . get_destination_display_string($dest_arr) . '</option>';
        } 
    }
    $ret .= '</select>';

    return $ret;
}

function get_destinations_for_links($link_array){
	$sql = "SELECT * FROM (destination LEFT JOIN address ON destination.AddressID = address.AddressID) LEFT JOIN phone ON phone.PhoneID = destination.PhoneID WHERE `DestinationID` IN (SELECT FromDestinationID FROM link WHERE LinkID IN ('" . implode("','",$link_array) . "')) OR `DestinationID` IN (SELECT ToDestinationID FROM link WHERE LinkID IN ('" . implode("','",$link_array) . "'))";
	
	$result = mysql_query($sql);
	
	if($result){
		$dest = array();
		while($row = mysql_fetch_array($result))
			$dest[] = $row;
		return $dest;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
	                    "Could not get destinations for links", $sql);
		return false;
	}
}

function delete_destination( $destination_id ){
	$destination = get_destination($destination_id);
	
	$safe_destination_id = mysql_real_escape_string( $destination_id );
	$sql = "DELETE FROM `destination` WHERE `DestinationID` = $safe_destination_id";
	$result = mysql_query($sql);
	
	if(!$result){
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
	                    "Could not delete destination $destination_id", $sql);
	    return FALSE;
	}
	delete_address($destination['AddressID']);
	
	return TRUE;
}
function delete_public_destination( $destination_id ){
	$safe_destination_id = mysql_real_escape_string( $destination_id );
	
	$sql = "SELECT COUNT(*) FROM rider_destination WHERE DestinationID =$safe_destination_id";
	$result = mysql_fetch_array( mysql_query( $sql ) );
	
	if($result[0] >= 1){ //if destination is connected to a user
		$sql = "UPDATE `destination` SET DestinationGroupID = NULL, `IsPublic` = 'No', `IsPublicApproved` = 'No' WHERE `DestinationID` =$safe_destination_id LIMIT 1 ;";
		$result = mysql_query($sql) or die(mysql_error());
	
		if(!$result){
			rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
		                    "Could not update destination $destination_id", $sql);
		    return FALSE;
		}
	} else { //if destination is not connected to a user
		delete_destination( $destination_id );
	}
	
}
function delete_public_group( $group_id ){
	$safe_group_id = mysql_real_escape_string( $group_id );
	$sql = "SELECT DestinationID FROM destination WHERE DestinationGroupID =$safe_group_id";
	$result = mysql_query($sql);
	
	if($result){
		while($row = mysql_fetch_array($result)){
			delete_public_destination( $row['DestinationID'] );
			echo $row['DestinationID'];
		}
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
		                    "Could not get destinations for group $group_id", $sql);
	}
	
	$sql = "SELECT DestinationGroupID FROM destination_group WHERE ParentGroupID =$safe_group_id";
	$result = mysql_query($sql);
	
	if($result){
		while($row = mysql_fetch_array($result)){
			delete_public_group( $row['DestinationGroupID'] );
		}
		$sql = "DELETE FROM destination_group WHERE DestinationGroupID = $safe_group_id LIMIT 1";
		$result = mysql_query($sql);
		
		if(!$result){
			rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
		                    "Could not delete group $group_id", $sql);
		}
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
		                    "Could not get destinations subgroups of group $group_id", $sql);
	}
}
function accept_deny_public_destination($destination_id, $choice, $add_to_group_id = NULL){
		$safe_id = mysql_real_escape_string( $destination_id );
		
		if($choice == 'accept'){
			$safe_group_id = mysql_real_escape_string( $add_to_group_id );
			
			$sql = "UPDATE destination SET IsPublic = 'Yes', IsPublicApproved = 'Yes', DestinationGroupID =$safe_group_id WHERE DestinationID =$safe_id LIMIT 1;";
			$result = mysql_query($sql);
			
			if(!$result){
				rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
		                    "Could not accept destination $destination_id", $sql);
			}
		} else if($choice == 'deny'){

			$sql = "UPDATE destination SET IsPublic = 'No', IsPublicApproved = 'No' WHERE DestinationID =$safe_id LIMIT 1;";
			$result = mysql_query($sql);
			
			if(!$result){
				rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
		                    "Could not deny destination $destination_id", $sql);
			}
		}
}
function rider_has_destination($rider_user_id, $destination_id){
	$safe_rider_user_id = mysql_real_escape_string($rider_user_id);
	$safe_destination_id = mysql_real_escape_string($destination_id);
	$sql = "SELECT Count(*) FROM rider_destination WHERE UserID = $safe_rider_user_id AND DestinationID = $safe_destination_id";
	$result = mysql_query($sql);
	
	if($result){
		$rows = mysql_fetch_array($result);
		if($rows[0] >= 1)
			return TRUE;
		return FALSE;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
		                "Could not verify if rider $rider_user_id has destination $destination_id", $sql);
	}
}



function get_all_destination_tags_alphabetical($franchise_id) {
    $safe_franchise_id = ($franchise_id) ? mysql_real_escape_string($franchise_id) : '0';

    $sql = "SELECT TagID, TagName, FranchiseID
            FROM destination_tag_list
            WHERE FranchiseID = $safe_franchise_id OR
                  FranchiseID IS NULL
            ORDER BY TagName ASC";

    $result = mysql_query($sql);

    if ($result) {
        $tags = array();
        while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
            $tags[] = $row;
        }
        return $tags;
    } else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
		                "Could not get destination tags for franchise $franchise", $sql);
        return FALSE;
    }
}

function get_destination_tags($destination_id) {
    $safe_destination_id = mysql_real_escape_string($destination_id);

    $sql = "SELECT DestinationID, TagID, TagName, FranchiseID, TagInfo1, TagInfo2
            FROM destination_tag NATURAL JOIN destination_tag_list
            WHERE DestinationID = $safe_destination_id";

    $result = mysql_query($sql);

    if ($result) {
        $tags = array();
        while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
            $tags[$row['TagName']] = $row;
        }
        return $tags;
    } else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
		                "Could not get destination tags for destination $destination_id", $sql);
        return FALSE;
    }
}


function set_destination_tags($destination_id, $posted_tag_ids) {
    $safe_destination_id = mysql_real_escape_string($destination_id);

    $delete_sql = "DELETE FROM destination_tag WHERE DestinationID = $safe_destination_id";
    $result = mysql_query($delete_sql);

    if ($result && count($posted_tag_ids)) {
        $separator = ''; 

        foreach ($posted_tag_ids as $tag_record) {
            $tag_id = $tag_record['TagID'];
            if (is_int($tag_id)) {
                // Tag ID is safe because it's an int
                $safe_taginfo1 = (is_null($tag_record['TagInfo1']) ? 'NULL' :
                                    "'" . mysql_real_escape_string($tag_record['TagInfo1']) . "'");
                $safe_taginfo2 = (is_null($tag_record['TagInfo2']) ? 'NULL' :
                                    "'" . mysql_real_escape_string($tag_record['TagInfo2']) . "'");


                $insert_values .= "$separator($safe_destination_id, $tag_id, $safe_taginfo1, $safe_taginfo2)"; 
                $separator = ', ';
            }
        }

        if ($insert_values) { 
            $insert_sql = "INSERT INTO destination_tag (DestinationID, TagID, TagInfo1, TagInfo2) 
                           VALUES $insert_values";

            $result = mysql_query($insert_sql);
            if ($result) {
                return TRUE;
            } else {
                rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                                "Could not add new destination tags for $destination_id", $sql);
                return FALSE;
            }
        }
    } else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
		                "Could not remove existing destination tags for $destination_id", $sql);
        return FALSE;
    }



}

function is_destination_grocery_store( $destination_id ) {
	$sql = "select DestinationGroupID from destination where DestinationID = $destination_id";
	$r = mysql_query($sql);
	if($r) {
		$rs = mysql_fetch_array($r);
		if($rs["DestinationGroupID"] != "" && $rs["DestinationGroupID"] != null) {
			if( get_destination_group_root( $rs["DestinationGroupID"] ) == 1 ) return true;
		}
	}
	return false;
}

function get_destination_group_root( $gid ) {
	$sql = "select ParentGroupID from destination_group where DestinationGroupID = $gid";
	$rs = mysql_fetch_array(mysql_query($sql));
	if($rs["ParentGroupID"] == 0) return $gid;
	else return get_destination_group_root( $rs["ParentGroupID"] );
}

?>
