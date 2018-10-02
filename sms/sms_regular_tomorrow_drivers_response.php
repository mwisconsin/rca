<?php

require_once('../include/database.php');
require_once('../include/driver.php');
require_once('../include/functions.php');
require_once('../include/phone.php');
require_once('../include/email.php');
require_once('../include/user.php');
require_once('../include/name.php');
require_once('../include/franchise.php');

$from = $_POST["From"];
$response = $_POST["Body"];

db_connect();

$sql = "select d from holidays";
$r = mysqli_query($db_connection_link,$sql);
$holidays = array();
while($rs = mysqli_fetch_array($r,MYSQLI_BOTH)) $holidays[] = $rs["d"];

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
					/* AND REPLACE(PhoneNumber,'-','') = '".str_replace('+1','',$from)."' */
					AND '" . $tomorrows[$i] ."' between TimeValid and TimeInvalid";
}
$sql .= "\nORDER BY UserID, DayOfWeek";

$r = mysqli_query($db_connection_link,$sql);
$m = '';
while($rs = mysqli_fetch_array($r,MYSQLI_BOTH)) {
	$m .= "\t".$rs["DayOfWeek"].", ".date('m/d/Y',strtotime($rs["CalDate"]))."\n";
}
$message = "Driver Schedule Response from: ".$rs["FirstName"]." ".$rs["LastName"]." (".$rs["UserID"].")\n";
$message .= "Driver Schedule Confirmation: \n";
$message .= $m;
$message .= "\nDriver Response: ".$response;

$to = "coord@myridersclub.com";
$from = "admin@myridersclub.com";
$subject = "Riders Club: Driver Response from SMS";
$result = mail( $to, $subject, $message, "From: $from" );

echo <<<EOT
<?xml version="1.0" encoding="UTF-8" ?>
<Response>
    <Message>Thank you for your response! The Club Administrator will review your response and adjust the schedule appropriately.</Message>
</Response>
EOT;

?>