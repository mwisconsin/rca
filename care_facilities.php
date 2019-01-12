<?php
	include_once 'include/user.php';
	include_once 'include/address.php';
	include_once 'include/franchise.php'; 
	redirect_if_not_logged_in();
	
	$franchise = get_current_user_franchise();
	
	if(!current_user_has_role(1, 'FullAdmin') && !current_user_has_role($franchise, 'Franchisee')){
		header("Location: home.php");
		die();	
	}
	
	
    include_once 'include/header.php';

	if(count($_POST) > 0)
		for($i = 0; $i < count(array_keys($_POST)); $i++)
			if(strstr(array_keys($_POST)[$i],'reactivate_')) {
				( $r, $k ) = explode("_",array_keys($_POST)[$i]);
				$sql = "UPDATE care_facility set CareFacilityStatus = 'Active' where CareFacilityID = $k";
				mysql_query($sql);
			}
			
	$sql = "SELECT * FROM `care_facility` where FranchiseID=".(int)$franchise." and CareFacilityStatus = 'Active'";
	$result = mysql_query($sql) or die("failed to get care facilities");
	
?>
<center><h2>Care Facilities</h2></center>
<table border="1" style="margin:auto; width:100%;">
	<tr>
		<th>Name</th>
		<th>Address</th>
		<th>Balance</th>
		<th style="width:250px;">Actions</th>
	</tr>
	<?php
		
		
		while($row = mysql_fetch_array($result)){
			$address = get_address($row['FacilityAddressID'])
			?>
			<tr>
				<td><?php echo $row['CareFacilityName']; ?></td>
				<td><?php create_html_display_address($address['AddressID']); ?></td>
				<td>
					<?php echo format_dollars(calculate_care_facility_balance($row['CareFacilityID'])); ?>
				</td>
				<td>
					<a href="care_facility.php?id=<?php echo $row['CareFacilityID']; ?>">Account Info</a> 
					<a href="care_facility_users.php?id=<?php echo $row['CareFacilityID']; ?>">Users</a> 
					<a href="edit_care_facility.php?action=edit_balance&id=<?php echo $row['CareFacilityID']; ?>">Manual Ledger Entry</a>
					<a href="cf_ledger.php?id=<?php echo $row['CareFacilityID']; ?>">Ledger</a>
					<a href="care_facility_invoice.php?id=<?php echo $row['CareFacilityID']; ?>">Invoice</a>
					<a href="edit_care_facility.php?action=delete&id=<?php echo $row['CareFacilityID']; ?>">Delete Account</a> 
				</td>
			</tr>
			<?php
		}
	?>
</table>
<div style="text-align:right; padding:3px;">
	<a href="edit_care_facility.php?action=add">Add Care Facility</a>
</div>

<?php
	$sql = "SELECT * FROM `care_facility` where FranchiseID=".(int)$franchise." and CareFacilityStatus = 'Inactive'";
	$result = mysql_query($sql) or die("failed to get care facilities");
	
?>
<form method=POST>
<center><h2><u>Inactive</u> Care Facilities</h2></center>
<table border="1" style="margin:auto; width:100%;">
	<tr>
		<th>Name</th>
		<th>Address</th>
		<th>Balance</th>
		<th style="width:250px;">Actions</th>
	</tr>
	<?php
		
		
		while($row = mysql_fetch_array($result)){
			$address = get_address($row['FacilityAddressID'])
			?>
			<tr>
				<td><?php echo $row['CareFacilityName']; ?></td>
				<td><?php create_html_display_address($address['AddressID']); ?></td>
				<td>
					<?php echo format_dollars(calculate_care_facility_balance($row['CareFacilityID'])); ?>
				</td>
				<td><!--
					<a href="care_facility.php?id=<?php echo $row['CareFacilityID']; ?>">Account Info</a> 
					<a href="care_facility_users.php?id=<?php echo $row['CareFacilityID']; ?>">Users</a> 
					<a href="edit_care_facility.php?action=edit_balance&id=<?php echo $row['CareFacilityID']; ?>">Manual Ledger Entry</a>
					<a href="cf_ledger.php?id=<?php echo $row['CareFacilityID']; ?>">Ledger</a>
					<a href="care_facility_invoice.php?id=<?php echo $row['CareFacilityID']; ?>">Invoice</a>
					<a href="edit_care_facility.php?action=delete&id=<?php echo $row['CareFacilityID']; ?>">Delete Account</a> 
					-->
					<input type=submit name=reactivate_<?php echo $row["CareFacilityID"]; ?> value="Reactivate">
				</td>
			</tr>
			<?php
		}
	?>
</table>
</form>
<?php
	include_once 'include/footer.php';
?>
