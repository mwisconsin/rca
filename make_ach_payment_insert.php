<?php
    require_once('include/rider.php');
    require_once('include/user.php');
    
if(count($_POST) == 0) exit();

$mqdate = date('Y-m-d',strtotime($_POST['dts']));

$af_amount = @$_POST["annualfeeamount"] > 0 ? $_POST["annualfeeamount"] : 0;
if($_POST["amount"] > 0) {
	$m = mysql_query("insert into ach_to_process (userid, amount, dts, paytype) values ($_POST[userid],$_POST[amount],'$mqdate','ADD_TO_ACCOUNT')");
	if(!$m) {
		echo mysql_errno($link) . ": " . mysql_error($link). "\n";
		exit();
	}
}
if($af_amount > 0) {
	//$af_amount = 0- $af_amount; /* AFs should be entered as negative amounts */
	$m = mysql_query("insert into ach_to_process (userid, amount, dts, paytype) values ($_POST[userid],$af_amount,'$mqdate','ANNUAL_FEE')");
	if(!$m) {
		echo mysql_errno($link) . ": " . mysql_error($link). "\n";
		exit();
	}	
}
	
echo "1";
?>