<?php
include_once 'include/user.php';
include_once 'include/franchise.php';
redirect_if_not_logged_in();

$franchise = get_current_user_franchise();

if(!current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee')){
	header("location: home.php");
}


if($_POST['removeZip']){
	$zip = array_keys($_POST['removeZip']);
	$removed = remove_franchise_service_zip($franchise, $zip[0]);
	if($removed)
		$removed = "<div class=\"reminder\">You have sucessfully removed the zip from your serice area.</div>";
	else
		$removed = "<div class=\"reminder\">A problem occured when trying to delete the zip code.</div>";
}

if($_POST['addZipService']){
	$added = add_franchise_service_area($franchise, $_POST['addZip']);
	if($added)
		$added = "<div class=\"reminder\">You have sucessfully added the zip to your serice area.</div>";
	else
		$added = "<div class=\"reminder\">A problem occured when trying to add the zip code.</div>";
}


if (isset($_POST['FranchiseName']) && isset($_POST['OfficeHours']) && isset($_POST['ProcessingFee']) && isset($_POST['Email'])) {
  updateFranchise($franchise, $_POST['FranchiseName'], $_POST['OfficeHours'], @$_POST['OfficeTimeZone'], $_POST['ProcessingFee'], $_POST['Email']);
}


$franchise_result = mysql_query("select * from franchise where FranchiseID='".(int)$franchise."'");
$franchise_row = mysql_fetch_assoc($franchise_result);

include_once 'include/header.php';
?>
<h3>Club Name & Hours</h3>

<form method="post">
	<table>
    	<tr>
        	<td align="right"><b>Name:</b></td>
            <td><input type="text" name="FranchiseName" value="<?php echo $franchise_row['FranchiseName']; ?>"></td>
        </tr>
        <tr>
        	<td align="right"><b>Hours:</b></td>
            <td><input type="text" name="OfficeHours" value="<?php echo $franchise_row['OfficeHours']; ?>"></td>
        </tr>
        <tr>
        	<td align="right"><b>Processing Fee:</b></td>
            <td><input type="text" name="ProcessingFee" value="<?php echo $franchise_row['ProcessingFee']; ?>"></td>
        </tr>
        <tr>
        	<td align="right"><b>Email:</b></td>
            <td><input type="text" name="Email" value="<?php echo $franchise_row['Email']; ?>"></td>
        </tr>
        <tr>
        	<td colspan="2" class="alignright"><input type="submit" value="Update" name="UpdateFranchise"></td>
        </tr>
   </table>
</form>


<h3>Service Area</h3>
<?php echo $added . $removed; ?>

<table border="1" width="200px">
	<tr>
    	<th width="170px">Zip</th>
        <th>Action</th>
    </tr>
    <form method="post">
<?php $zips = get_franchise_service_zips($franchise); 
	foreach($zips as $zip){
		echo "<tr><td>$zip</td><td><input type=\"submit\" name=\"removeZip[$zip]\" value=\"Remove\"></td></tr>";
	}
?>
	</form>
    <form method="post">
	<tr>
    	<td><input type="text" width="100%" name="addZip"></td>
        <td><input type="submit" name="addZipService" value="Add"></td>
    </tr>
    </form>
</table>

<?php

include_once 'include/footer.php';

?>