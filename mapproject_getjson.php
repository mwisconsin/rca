<?php
require_once 'include/link.php';
require_once 'include/franchise.php';
require_once 'include/functions.php'; 

$out = array();

if(isset($_GET['driver'])) {
	$links = get_all_driver_history_and_active_links( $_GET['driver'], 'ALLDATES' );
	foreach($links as $link) {
		if(isset($_GET["date_from"]) && $_GET["date_from"] != ''
			&& strtotime($link["DesiredArrivalTime"]) < strtotime($_GET["date_from"].' '.@$_GET["time_from"])
			) continue;
		if(isset($_GET["date_to"]) && $_GET["date_to"] != ''
			&& strtotime($link["DesiredArrivalTime"]) > strtotime($_GET["date_to"].' '.@$_GET["time_to"])
			) continue;
		if(isset($_GET["days"]) 
			&& !in_array( date('D',strtotime($link["DesiredArrivalTime"])), array_keys($_GET["days"])))
				continue;
		
		$from = get_destination($link['F_DestinationID']);
		$to = get_destination($link['T_DestinationID']);
		$out[] = array($from,$to);									
	}
}

echo json_encode($out);
?>