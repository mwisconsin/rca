<?php
require_once "../include/database.php";

if(!isset($_POST['links']) && !isset($_POST['driver'])) exit();

$sql = "update link set AssignedDriverUserID = $_POST[driver] where LinkID in ($_POST[links])";
mysql_query($sql);


?>