<?php
require_once('include/database.php');
require_once('include/mapquest.php');
require_once('include/destinations.php');
require_once('include/link.php');
$safe_id = mysql_real_escape_string($_GET['id']);

$query = "SELECT * FROM deadhead_history WHERE EstimatedMinutes = 0";
$result = mysql_query($query) or die(mysql_error());

if($result){
	$test = true;
	while($row = mysql_fetch_array($result)){
		$F_destination = get_destination($row['FromDestinationID']);
		$T_destination = get_destination($row['ToDestinationID']);
		$link = get_link($row["PreviousLinkID"]);
	
		$distance_and_time = get_mapquest_time_and_distance( $F_destination, $T_destination, $link['DesiredArrivalTime'] );
		$safe_distance = mysql_real_escape_string($distance_and_time['time'] / 60);
		$safe_link_id = mysql_real_escape_string($row['DeadheadLinkID']);
		$sql = "UPDATE deadhead_history SET EstimatedMinutes = '$safe_distance' WHERE DeadheadLinkID = $safe_link_id";
		mysql_query($sql) or die(mysql_error());
	}
}
?>