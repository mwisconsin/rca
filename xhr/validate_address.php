<?php
chdir('..');

require_once('include/usps_services.php');
require_once('include/address.php');

$address = array('Address1' => $_REQUEST['Address1'],
				 'Address2' => $_REQUEST['Address2'],
				 'City' => $_REQUEST['City'],
				 'State' => $_REQUEST['State'],
				 'ZIP5' => $_REQUEST['ZIP5'],
				 'ZIP4' => $_REQUEST['ZIP4']);

$returned_address = usps_standardize_address( $address );

if($returned_address['SUCCESS'] === TRUE){
	$verified = true;
	echo "true";
} else {
	$result = verify_by_geocode($address);
	if($result['result']){
		$verified = true;
		echo "true";
	}
}

if(!isset($verified)){
    // Error message is $returned_address['ERRORMESSAGE']
	echo "false: ".$returned_address['ERRORMESSAGE'];
}

chdir('xhr/');
?>
