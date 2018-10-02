<?php
require_once "../include/database.php";

if(!isset($_GET["user"])) exit();

$sql = "update users set RequestContact = now() where UserID = $_GET[user]";
mysql_query($sql);

?>