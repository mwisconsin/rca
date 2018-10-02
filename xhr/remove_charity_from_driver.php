<?php
require_once "../include/database.php";

$sql = "delete from driver_allocation_preference where DriverUserID = $_REQUEST[DriverID] and AllocationID = $_REQUEST[CharityID]";
mysql_query($sql);

$sql = "delete from supporter_charity where SupporterUserID = $_REQUEST[DriverID] and CharityID = $_REQUEST[CharityID]";
mysql_query($sql);

?>