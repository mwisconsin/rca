<?php
include_once 'include/user.php';

redirect_if_not_logged_in();

$franchise = get_current_user_franchise();

if(!current_user_has_role(1 , "FullAdmin") && !current_user_has_role($franchise, "Franchisee")){
	header("Location: home.php");
	die();	
}

$sql = "select * from scheduling_afterhours where FranchiseID = $franchise";
$r = mysql_query($sql);
if(mysql_num_rows($r) == 0) {
	mysql_query("insert into scheduling_afterhours (FranchiseID) values ($franchise)");
	$r = mysql_query($sql);
}

$grid = array();
$days = array("su","mo","tu","we","th","fr","sa");
if(count($_POST) > 0) {
	$isql = "update scheduling_afterhours set ";

	for($i = 0; $i < count($days); $i++) {
		$bef = "before_".$days[$i];
		$aft = "after_".$days[$i];
		$d = new DateTime( $_POST[$bef] );	
		$isql .= ($isql == "update scheduling_afterhours set " ? "" : ", ")
			."before_".$days[$i]." = '".$d->format("H:i:s")."'";
		$d = new DateTime( $_POST[$aft] );	
		$isql .= ", after_".$days[$i]." = '".$d->format("H:i:s")."'";
	}
	$isql .= ", amount_of_charge = $_POST[amount_of_charge] where FranchiseID = $franchise";
	mysql_query($isql);
}
$r = mysql_query($sql);
$rs = mysql_fetch_assoc($r);
foreach($rs as $k => $v) $grid[$k] = $v;


include_once 'include/header.php';?>
<link rel="stylesheet" type="text/css" href="/css/jquery.timepicker.css">
<style>
input[type=text] {
	text-align: right;
	padding-right: 3px;
}	
</style>
<script src="/js/jquery.timepicker.min.js"></script>
<script>
jQuery(function($) {
	$('input[name^="before_"]').each(function(k,v) {
		if($(v).val() == '') $(v).val( k == 6 ? '9:00am' : '7:00am' );
		$(v).timepicker({disableTextInput:true});
	});
	$('input[name^="after_"]').each(function(k,v) {
		if($(v).val() == '') $(v).val( k == 6 ? '7:00pm' : '9:00pm' );
		$(v).timepicker({disableTextInput:true});
	});	
});	
</script>
<h2>Entry Grid for Additional Charge After Hours</h2>
<form method=POST>
<table id=chargegrid>
<tr align=center>
	<td></td>	
	<td>Su</td>
	<td>Mo</td>
	<td>Tu</td>
	<td>We</td>
	<td>Th</td>
	<td>Fr</td>
	<td>Sa</td>
</tr>	
<tr>
	<td>Before</td>	
	<td><input type=text size=5 name=before_su value="<?php echo $grid["before_su"]; ?>"></td>
	<td><input type=text size=5 name=before_mo value="<?php echo $grid["before_mo"]; ?>"></td>
	<td><input type=text size=5 name=before_tu value="<?php echo $grid["before_tu"]; ?>"></td>
	<td><input type=text size=5 name=before_we value="<?php echo $grid["before_we"]; ?>"></td>
	<td><input type=text size=5 name=before_th value="<?php echo $grid["before_th"]; ?>"></td>
	<td><input type=text size=5 name=before_fr value="<?php echo $grid["before_fr"]; ?>"></td>
	<td><input type=text size=5 name=before_sa value="<?php echo $grid["before_sa"]; ?>"></td>
</tr>
<tr>
	<td>After</td>	
	<td><input type=text size=5 name=after_su value="<?php echo $grid["after_su"]; ?>"></td>
	<td><input type=text size=5 name=after_mo value="<?php echo $grid["after_mo"]; ?>"></td>
	<td><input type=text size=5 name=after_tu value="<?php echo $grid["after_tu"]; ?>"></td>
	<td><input type=text size=5 name=after_we value="<?php echo $grid["after_we"]; ?>"></td>
	<td><input type=text size=5 name=after_th value="<?php echo $grid["after_th"]; ?>"></td>
	<td><input type=text size=5 name=after_fr value="<?php echo $grid["after_fr"]; ?>"></td>
	<td><input type=text size=5 name=after_sa value="<?php echo $grid["after_sa"]; ?>"></td>
</tr>
</table>
<br><br>
Amount of Additional Charge: <input type=text size=6 name=amount_of_charge value="<?php echo $grid["amount_of_charge"]; ?>">
<br><br>
<input type=submit value=Submit>
</form>
<?
include_once 'include/footer.php';
?>