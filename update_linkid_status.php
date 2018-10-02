<?php
include_once 'include/database.php';

if(!isset($_POST["linkid"]) || !isset($_POST["status"]) || !isset($_POST["CTID"])) exit();

$sql = "update link set LinkStatus = '$_POST[status]' where linkid = $_POST[linkid] and CustomTransitionID = $_POST[CTID]";
$result = mysql_query($sql) or die('failed query: ' . $sql . ' :' . mysql_error());

echo "1";

?>