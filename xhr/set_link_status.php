<?php
require_once "../include/database.php";

if(!isset($_GET["linkid"])) exit();

$sql = "update link set LinkStatus = '$_GET[status]' where LinkID = $_GET[linkid]";
mysql_query($sql);



?>