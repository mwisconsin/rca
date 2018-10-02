<?php
    chdir('..');
    require_once('include/database.php');

	if(!isset($_POST["mNickname"]) || $_POST["mNickname"] == "") exit();    
    
  $sql = "delete from hps_mutokens where muToken = '{$_POST[mNickname]}'";
  mysql_query($sql);  
    
?>