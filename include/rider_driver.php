<?php

  function rdm_preferences($user_id) {
    $result  = mysql_query("select pn.FirstName, pn.LastName, rdm.rating from rider_driver_match rdm, users u, person_name pn where rdm.self_UserID='".(int)$user_id."' and u.UserID=rdm.other_UserID and pn.PersonNameID=u.PersonNameID and u.Status = 'ACTIVE' order by rdm.rating desc");
	$html = '';
	while($row=mysql_fetch_assoc($result)) {
	  $html .= '<span style="padding:10px;">'.$row['FirstName'].' '.$row['LastName'].' '.$row['rating'].'</span>';
	}
	return $html;
  }
  
  function rdm_match($rider_id, $driver_id) {
    $result = mysql_query("select rdm.rating from rider_driver_match rdm where self_UserID='".(int)$rider_id."' and other_UserID='".(int)$driver_id."'");
	if (mysql_num_rows($result)!=0) {
	  $row = mysql_fetch_assoc($result);
	  $rider_pref = $row['rating'];
	} else {
	  $rider_pref = 0;
	}
	$result = mysql_query("select rdm.rating from rider_driver_match rdm where self_UserID='".(int)$driver_id."' and other_UserID='".(int)$rider_id."'");
	if (mysql_num_rows($result)!=0) {
	  $row = mysql_fetch_assoc($result);
	  $driver_pref = $row['rating'];
	} else {
	  $driver_pref = 0;
	}
	echo 'Rider: '.$rider_pref.'<br />Driver: '.$driver_pref;
  }

?>