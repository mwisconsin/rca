<?php
require_once "../include/database.php";

if(!isset($_GET["id"])) echo json_encode(array( "on_demand_override" => 0, "stop_number" => $_GET["stop_number"]));

$id = str_replace('-','',$_GET["id"]);
$sql = "select on_demand_override from destination where DestinationID = $id";
$rs = mysql_fetch_array(mysql_query($sql));
echo json_encode(array( "on_demand_override" => $rs[0], "stop_number" => $_GET["stop_number"]))

?>