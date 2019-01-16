<?php
	include_once 'include/user.php';
	include_once 'include/link_price.php';
	redirect_if_not_logged_in();
	
	$franchise = get_current_user_franchise();
	
	if(!current_user_has_role(1 , "FullAdmin") && !current_user_has_role($franchise, "Franchisee")){
		header("Location: home.php");
		die();	
	}
	
	if(count($_POST) > 0) {
		$sql = "update franchise set deadhead_plus = $_POST[deadhead_plus] where FranchiseID = $franchise";
		mysql_query($sql);
	}
	
	$sql = "select deadhead_plus from franchise where FranchiseID = $franchise";
	$r = msyql_query($sql);
	$rs = mysql_fetch_array($r);
	$deadhead_plus = $rs["deadhead_plus"];
?>
<form method=POST>
<h1>Deadhead Plus</h1>
Provide the maximum number of minutes for idle driver time:<br>
<input type=text size=4 name=deadhead_plus value=<?php echo $deadhead_plus; ?>><br><br>
<input type=submit value=Submit>
</form>
<?php
	include_once 'include/footer.php';
?>