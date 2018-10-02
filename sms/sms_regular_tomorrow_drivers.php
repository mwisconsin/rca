<?php

require_once('../include/database.php');
require_once('../include/driver.php');
require_once('../include/functions.php');
require_once('../include/phone.php');
require_once('../include/email.php');
require_once('../include/user.php');
require_once('../include/name.php');
require_once('../include/franchise.php');

// Get the PHP helper library from twilio.com/docs/php/install
require_once '../vendor/autoload.php'; // Loads the library
use Twilio\Rest\Client;



$sid = 'AC982350305cc84a594983f6d7e1a4aea8';
$token = 'd0ae621eb7b9989a1646a994a3341e04';
$client = new Client($sid, $token);

db_connect();

$sql = "select d from holidays";
$r = mysqli_query($db_connection_link,$sql);
$holidays = array();
while($rs = mysqli_fetch_array($r,MYSQLI_BOTH)) $holidays[] = $rs["d"];

if(in_array(date('Y-m-d',time()),$holidays)
		|| date('w',time()) == 5
		|| date('w',time()) == 6) exit();


$tomorrows = array();
$tomorrows[] = date('Y-m-d',strtotime('+1 day'));
$tomorrow_days[] = date('l',strtotime('+1 day'));
$ti = 0;
while(in_array($tomorrows[$ti], $holidays) 
	|| date('w',strtotime($tomorrows[$ti])) == 5
	|| date('w',strtotime($tomorrows[$ti])) == 6) {
	$tomorrows[] = date('Y-m-d',strtotime('+1 day',strtotime($tomorrows[$ti])));
	$tomorrow_days[] = date('l',strtotime('+1 day',strtotime($tomorrows[$ti])));
	$ti++;
}

$sql = '';
for($i = 0; $i < count($tomorrows); $i++) {
	if($sql != '') $sql .= "\nUNION\n";
	$sql .= "select *, '".$tomorrows[$i]."' as CalDate from driver_availability
						natural join driver
						natural join users
						natural join user_phone
						natural join phone
						natural join person_name
					WHERE canSMS = 'Y' AND DriverStatus = 'Active'
					AND DayOfWeek = '" . $tomorrow_days[$i] . "'
					AND not UserID in (select UserID from user_role where Role in ('FullAdmin','Franchisee'))
					/* AND REPLACE(PhoneNumber,'-','') = '3195736866' */
					AND '" . $tomorrows[$i] ."' between TimeValid and TimeInvalid";
}
$sql .= "\nORDER BY UserID, DayOfWeek";

$r = mysqli_query($db_connection_link,$sql);
$userid = -1;
while($rs = mysqli_fetch_array($r,MYSQLI_BOTH)) {
	if($userid != $rs["UserID"]) {
		if($userid > 0) {
			$message .= "?\n\nRespond YES, or respond back with your available hours.\n\nThank you!\nRiders Club of America\n\n";
			send_sms( $message, str_replace("-","",$rs["PhoneNumber"]) );
		}
		$userid = $rs["UserID"];
		$message = "Hi, ".($rs["NickName"] != "" ? $rs["NickName"] : $rs["FirstName"]).",\n";
		$message .= "Are you still available to drive on ".$rs["DayOfWeek"].", ".date('m/d/Y',strtotime($rs["CalDate"]))
			." between ".$rs["StartTime"]." and ".$rs["EndTime"];
	} else {
		$message .= ", and ".$rs["DayOfWeek"].", ".date('m/d/Y',strtotime($rs["CalDate"]))
			." between ".$rs["StartTime"]." and ".$rs["EndTime"];
	}	
}
mysqli_data_seek($r,mysqli_num_rows($r)-1);
$rs = mysqli_fetch_array($r,MYSQLI_BOTH);
$message .= "?\n\nRespond YES, or respond back with your available hours.\n\nThank you!\nRiders Club of America\n\n";
send_sms( $message, str_replace("-","",$rs["PhoneNumber"]) );



function send_sms($m, $p) {
	global $client;
	
	$client->messages->create(
	  $p,
	  array(
	    'from' => '3193180343',
	    'body' => $m,
	  )
	);	
}


