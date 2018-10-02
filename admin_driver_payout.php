<?php
include_once('include/header.php');
include_once('include/driver_rate_card.php');
redirect_if_not_logged_in();

$franchise = get_current_user_franchise();

if(!current_user_has_role(1 , "FullAdmin") && !current_user_has_role($franchise, "Franchisee")){
	header("Location: home.php");
	die();	
}

if($_POST['newDriverRateCard'] && !isset($_POST['effectiveDate']) && !isset($_POST['replacedDate'])){
	$centsPerMile = $_POST['CentsPerMile'];
	$effective_date = $_POST['Year'] . "-" . $_POST['Month'] . "-" . $_POST['Day'];
	$result = create_driver_rate_card($franchise, $centsPerMile, $effective_date);
	
	if($result)
		$announce = "Successfully created a new driver rate card.";
	else
		$announce = "The system fail to put a new driver rate card into the database.";
}

if($_POST['newDriverRateCard'] && $_POST['effectiveDate'] && $_POST['replacedDate']){
	$centsPerMile = $_POST['CentsPerMile'];
	$effective_date = $_POST['effectiveDate'];
	$replace_date = $_POST['replacedDate'] == 'null' ? NULL : $_POST['replacedDate'];
	$result = edit_driver_rate_card($franchise, $effective_date, $replace_date, $centsPerMile);
	
	if($result)
		$announce = "Successfully editted a driver rate card.";
	else
		$announce = "The system fail to edit a driver rate card.";
}

if($_POST['delete']){
	 $effective = array_keys($_POST['delete']);
	 $replaced = array_keys($_POST['delete'][$effective[0]]);
	 $replaced[0] = $replaced[0] == 'null' ? NULL : $replaced[0];
	 $result = delete_driver_rate_card($franchise, $effective[0], $replaced[0]);
	 if($result)
	     $announce = "The driver rate card has been deleted.";
	 else
	     $announce = "The system has failed to delete the driver rate card.";
}

if($_POST['edit']){
	 $effective = array_keys($_POST['edit']);
	 $replaced = array_keys($_POST['edit'][$effective[0]]);
	 $replaced[0] = $replaced[0] == 'null' ? NULL : $replaced[0];
	 $edit = "<input type=\"hidden\" value=\"{$effective[0]}\" name=\"effectiveDate\"><input type=\"hidden\" value=\"" . ($rec['ReplacedDate'] == NULL ? 'null' : $rec['ReplacedDate']) . "\" name=\"replacedDate\">";
	 $card = get_driver_rate_card($franchise, $effective[0], $replaced[0]);
	 $edit_value = $card['CentsPerMile'];
}


$records = get_past_driver_rate_cards($franchise);
?>
<center><h2>Driver Payout</h2></center>
<?php
	if($announce)
		echo "<div class=\"reminder\">" . $announce .  "</div>";
?>
<form method="post">
Set a new rate per mile: <input type="text" name="CentsPerMile" value="<?php echo $edit_value; ?>" size="12"> (cents / mile)
<br><?php if(!isset($edit)) echo "Beginning: " . get_date_drop_downs(''); echo $edit ?> <input type="submit" name="newDriverRateCard" value="Save">
</form>
<br>
<h3>Past Records</h3>
<form method="post">
<?php 
	$t = "Future Card";
	foreach($records as $rec){ ?>
<table border="1" width="300px">
	<tr>
			<td colspan="4">
				<?php
		              if(($rec['ReplacedDate'] !== NULL && time() >= strtotime($rec['EffectiveDate']) && time() < strtotime($rec['ReplacedDate']) ) || ($rec['ReplacedDate'] === NULL && strtotime($rec['EffectiveDate']) <= time() ) ){
		                  echo "Current Card";
		                  $t = "Past Card";
		              } else
		                  echo $t;
		         ?>
			</td>
	</tr>
	<tr>
		<td>Price:</td>
		<td><?php echo $rec['CentsPerMile']; ?></td>
		<td colspan="2">
			<input type="submit" name="edit[<?php echo $rec['EffectiveDate']; ?>][<?php echo $rec['ReplacedDate'] == NULL ? 'null' : $rec['ReplacedDate']; ?>]" value="Edit">
			<input type="submit" name="delete[<?php echo $rec['EffectiveDate']; ?>][<?php echo $rec['ReplacedDate'] == NULL ? 'null' : $rec['ReplacedDate']; ?>]" value="Delete">
		</td>
	</tr>
	<tr>
		<td>Start</td>
		<td><?php echo $rec['EffectiveDate']; ?></td>
		<td>Finish</td>
		<td><?php echo $rec['ReplacedDate'] != null ? $rec['ReplacedDate'] : "Not Set"; ?></td>
	</tr>
</table><br>
<?php } ?>
</form>
<?php
include_once('include/footer.php');
?>