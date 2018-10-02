<?php
require_once "../include/database.php";

$sql = "insert into driver_allocation_preference (DriverUserID, AllocationType, AllocationID)
values ($_REQUEST[DriverID], 'CHARITY', $_REQUEST[CharityID]);";
mysql_query($sql);

$sql = "insert into supporter_charity (SupporterUserID, CharityID)
values ($_REQUEST[DriverID], $_REQUEST[CharityID]);";
mysql_query($sql);


?>