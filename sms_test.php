<?php
include_once 'include/database.php';

function sms_mailer( $UserID = 0, $Message = '') {
	if($UserID == 0 || $Message == '') return false;
	
	$sql = "select phone.PhoneNumber, sms_providers.domain
		from phone, sms_providers, users, user_phone
		where users.UserID = $UserID and users.UserID = user_phone.UserID and user_phone.PhoneID = phone.PhoneID
		and phone.PhoneType = 'MOBILE' and phone.canSMS = 'Y' and phone.ProviderID = sms_providers.id";
	$r = mysql_query($sql);
	if(mysql_num_rows($r) == 0) return false;
	
	while($rs = mysql_fetch_assoc($r)) {
		$clean_phone = preg_replace('/[^0-9]/','',$rs["PhoneNumber"]);
		$to = $clean_phone."@".$rs["domain"];
		$from = "admin@myridersclub.com";
		$subject = "Riders Club: Important Notice!";
		$body = $Message;	
		$result = mail( $to, $subject, $body, "From: $from" );
		return $result;
	}
}

echo "Testing SMS Mailer";
echo sms_mailer( 5, "You have an upcoming ride:  Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean pellentesque erat tempor sodales sagittis. Vivamus sit amet lorem libero. Vestibulum suscipit semper lorem, a fringilla urna facilisis sit amet. Phasellus in cursus purus. Maecenas vitae venenatis arcu. Nam et volutpat est. Nulla sollicitudin risus at lorem dignissim sollicitudin. Phasellus felis mauris, tempor non posuere sed, blandit et elit. Morbi vehicula, enim at consequat elementum, tortor urna congue leo, in viverra enim dui at sapien. Proin vitae risus eget libero pellentesque condimentum. In et sapien sed sapien consectetur commodo feugiat a dolor. Sed pellentesque metus imperdiet maximus dictum. Phasellus at tincidunt enim. In sit amet ante arcu. Donec a est gravida, sagittis lacus sit amet, lobortis urna.");

?>