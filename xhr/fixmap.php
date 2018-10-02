<?php
chdir('..');
	require_once 'include/destinations.php';
  require_once 'include/large_facility.php';
  require_once 'include/class.email.php';
  $franchise_id = get_current_user_franchise(TRUE);
	$email = new Email();
	$u = get_user_account( get_affected_user_id() );
	$email_message = "User ".$u['FirstName']." ".$u['LastName']." (".get_affected_user_id().") has requested that you review the GeoCode for ".$_POST["address"];
	$r = mysql_query("select fe.EmailType, e.EmailAddress as EmailAddress1, e2.EmailAddress as EmailAddress2, vacation_end, vacation_duration from franchise_email_settings fe, email e, email e2 where e.EmailID=fe.EmailID1 and e2.EmailID=fe.EmailID2 and fe.FranchiseID=".(int)$franchise_id." and EmailType = 'de_fix_map'");
	print_r($r);

	while($rs = mysql_fetch_array($r)) {
		print_r($rs);
		$email->send($rs['EmailAddress1'], 'Map Fix Request', $email_message);
		$email->send($rs['EmailAddress2'], 'Map Fix Request', $email_message);
	}  

	chdir('xhr/');
?>
