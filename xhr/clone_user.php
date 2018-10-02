<?php
chdir('..');
require_once "include/rider.php";

/*
    [NewUsername] => asdfasdf
    [NewEmailAddress] => asdfas
    [NewQualifications] => asdfasdfs
    [UserID] => 662
*/

if( @$_POST['UserID'] == '' ) exit();

clone_rider( $_POST['UserID'], $_POST['NewUsername'], $_POST['NewEmailAddress'], $_POST['NewQualifications'] );

?>