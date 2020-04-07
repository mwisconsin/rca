<?php
    chdir('..');

    require_once('include/user.php');
	require_once('include/franchise.php');
    $franchise = get_current_user_franchise();
    if(!current_user_has_role(1, 'FullAdmin') && !current_user_has_role($franchise, 'Franchisee')){
		die();	
	}
	
	function get_all_accessible_users(){
	$current_user = get_current_user_id();
	$franchise = get_current_user_franchise(false);
	if(!$franchise)
		die('{"users":[]}');
	
	if(current_user_has_role($franchise, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee')) {
		$sql = "SELECT users.UserID, FirstName, MiddleInitial, LastName, NickName, group_concat(PhoneNumber separator ' ') as PhoneNumber, group_concat(Role separator ' ') 
                FROM (users NATURAL JOIN person_name
                        LEFT JOIN user_role ON users.UserID = user_role.UserID 
                        LEFT JOIN user_phone ON users.UserID = user_phone.UserID
                        LEFT JOIN phone ON user_phone.PhoneID = phone.PhoneID)
                WHERE users.Status != 'INACTIVE' AND users.UserID IN (SELECT UserID FROM user_role WHERE FranchiseID = '$franchise')
                GROUP BY users.UserID ORDER BY LastName, FirstName";
	}else {
		$sql = "";
	}
	$result = mysql_query($sql) or die(mysql_error());
	
	if($result){
		if(mysql_num_rows($result) < 1)
			return array();
		while($row = mysql_fetch_array($result))
			$users[] = $row;
		return $users;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Could not get all accessible users for UserID" . get_current_user_id(), $sql);
	}
}

	$users = get_all_accessible_users();
	$last = $users[count($users) - 1];
	echo '{"users":[';
	foreach($users as $user){
		echo "[{$user['UserID']},\"{$user['FirstName']} ".($user['NickName'] != '' ? "($user[NickName]) " : "")."{$user['LastName']}\",\"{$user[5]}\",\"{$user[6]}\"]";
		if($user != $last)
			echo ',';
	}
	echo ']}';
	
	chdir('xhr/');
?>