<?php
require_once "../include/database.php";

if(!isset($_POST["linkid"])) exit();

$sql = "select DesiredArrivalTime, PrePadding, PostPadding from link where LinkID = $_POST[linkid]";
$link = mysql_fetch_array(mysql_query($sql));
$pre = $link["PrePadding"];
$post = $link["PostPadding"];
$newminutes = $_POST["minutes"] - $pre - $post;
if($newminutes < 0) {
	$newminutes = $_POST["minutes"];
	$pre = 0;
	$post = 0;
}
$dropoff = date('Y-m-d H:i:s',strtotime(date('Y-m-d',strtotime($link["DesiredArrivalTime"]))." $_POST[dropoff]"));
$departure = date('Y-m-d H:i:s',strtotime('-'.($newminutes + $pre + $post).' minute',strtotime($dropoff)));
$sql = "update link set DesiredDepartureTime = '$departure', DesiredArrivalTime = '$dropoff', EstimatedMinutes = $newminutes, PrePadding = $pre, PostPadding = $post
	where LinkID = $_POST[linkid]";
echo $sql;
mysql_query($sql);


?>