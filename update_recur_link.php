<?php
require_once('include/database.php');

if(count($_POST) == 0) exit();

$RRStart = strtotime($_POST["RRStart"]);
$myRRStart = date('Y-m-d',$RRStart);
$RREnd = strtotime($_POST["RREnd"]);
$myRREnd = date('Y-m-d',$RREnd);

$sql = "update link set RecurRide = 'Y', RRFrequency = '{$_POST[RRFrequency]}',
	RRNumber = '{$_POST[RRNumber]}', RRStatus = '{$_POST[RRStatus]}', RRStart = '$myRRStart 00:00:00', RREnd = '$myRREnd 00:00:00',
	RROngoing = '{$_POST[Ongoing]}' where LinkID = $_POST[linkID]";
mysql_query($sql);
?>
1