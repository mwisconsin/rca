<?php
require_once "../include/database.php";

/*
    [a] = Arrival Link ID to set to Y
    [d] = Destionation Link ID to set to Y
*/

$sql = "update link set ArrivalTimeConfirmed = 'Y' where LinkID = $_GET[a]";
mysql_query($sql);

$sql = "update link set DepartureTimeConfimed = 'Y' where LinkID = $_GET[d]";
mysql_query($sql);

?>