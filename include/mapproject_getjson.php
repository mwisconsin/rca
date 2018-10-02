<?php
require_once '/include/link.php';
require_once '/include/franchise.php';
require_once '/include/functions.php'; 

$out = array();

if(isset($_GET['rider'])) {
	$links = get_rider_active_links( $_GET['rider'] );
	
	foreach($links as $link) {
		$from = get_destination($link['F_DestinationID']);
		$to = get_destination($link['T_DestinationID']);
		$out[] = array($from,$to);

										
	}
}

echo json_encode($out);
?>