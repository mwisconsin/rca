<?php
require_once "../include/database.php";
require_once "../include/supporters.php";

if(!isset($_POST["userid"]) && !isset($_POST["ncid"])) exit();

disconnect_supporter_from_rider( $_POST["userid"], $_POST["ncid"]);

?>