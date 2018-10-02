<?php
    chdir('..');
    include_once('include/destinations.php');
    
    function get_javascript_public_destinations($franchise, $group_id = 0, $group_string = ""){
	$safe_group_id = mysql_real_escape_string($group_id);
	$safe_franchise = mysql_real_escape_string($franchise);
//	$sql = "SELECT * FROM destination_group WHERE IsPublicApproved = 'Yes' and FranchiseID = $safe_franchise;";
//	$result = mysql_query($sql);
//	$sub_groups = array();
//	while($row = mysql_fetch_array($result)){
//		$sub_groups[] = get_javascript_public_destinations($franchise, $row['DestinationGroupID'], trim($group_string . "," . $row['Name']));
//	}
	$final_array = array();
	$sql = "SELECT * FROM (destination NATURAL JOIN address) LEFT JOIN phone ON destination.PhoneID = phone.PhoneID WHERE IsPublicApproved = 'Yes' AND IsPublic = 'Yes'  AND FranchiseID = $safe_franchise;";
	$result = mysql_query($sql);
	while($row = mysql_fetch_array($result)){
		$final_array[] = array('id'=>$row['DestinationID'], 'name' => $row['Name'], 'description' => $row['DestinationDetail'], 'phone' => $row['PhoneNumber'], 'groups' => $group_string, 'address' => "{$row['Address1']}, {$row['City']} {$row['State']}"); 
	}
	
	for($i = 0; $i < count($sub_groups); $i++)
		$final_array = array_merge($final_array, $sub_groups[$i]);
	return $final_array;
}

function get_javascript_destination_array($array){
	$string = "[";
	
	for($i = 0; $i < count($array); $i++){
		$string .= "{\"id\":\""  . $array[$i]['id'] . "\",\"name\":\"" . urlencode ($array[$i]['name']) .  "\",\"description\":\"" . urlencode ($array[$i]['description']) . "\",\"phone\":\"" . urlencode ($array[$i]['phone'])  . "\",\"groups\":\"" . urlencode ($array[$i]['groups']) . "\",\"address\":\"" . urlencode ($array[$i]['address'])  . "\"}";
		if($i != count($array) - 1)
			$string .= ",";
	}
	return $string . "]";
}

if($_REQUEST['franchise'])
    $franchise = $_REQUEST['franchise'];
else
    $franchise = 1;
echo get_javascript_destination_array(get_javascript_public_destinations($franchise));

chdir('xhr/');
?>