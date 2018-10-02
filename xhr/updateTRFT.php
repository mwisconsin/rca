<?php
require_once "../include/database.php";

if(!isset($_GET["linkid"]) && !isset($_GET['to'])) exit();

$sql = "update todays_links set TextRiderForThisLink = $_GET[to] where LinkID = $_GET[linkid]";
mysql_query($sql);


?>