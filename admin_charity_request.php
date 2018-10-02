<?php
	require_once 'include/user.php';
	require_once 'include/charity.php';
	require_once 'include/franchise.php';

	$franchise = get_current_user_franchise();

	if(!current_user_has_role(1 , "FullAdmin") && !current_user_has_role($franchise, "Franchisee")){
		header("Location: home.php");
		die();	
	}
	
	require_once 'include/header.php'; 
?>
<h2>Pending Charity Requests</h2>
<table border="1" width="600px">
<tr>
	<td style='display:none;'></td>
	<th>Charity Name</th>
    <td width="100px"></td>

</tr>
<?php
	$pending_charities = get_pending_charities();
	foreach($pending_charities as $charity){
		echo "<tr><td style='display:none;'><input type='button' value='Select' /></td><td>{$charity['CharityName']}</td><td><input type='submit' value='View'  onclick=" .  '"document.location = ' . "'request_new_charity.php?id={$charity['CharityID']}&action=approve'" . '"' .  " />";
		if($charity['transactions']  <= 0)
			echo "<input type='submit' value='Delete'  onclick=" .  '"document.location = ' . "'request_new_charity.php?id={$charity['CharityID']}&action=delete'" . '"' .  " />";
		echo "</td></tr>";
	}
?>
</table>

<h2>Current Charities</h2>
<table border="1" width="600px">
<tr>
	<td style="display:none;"></td>
	<th>Charity Name</th>

    <td width="95px"></td>
</tr>
<?php
	$charities = get_approved_charities();
	foreach($charities as $charity){
		echo "<tr><td style='display:none;'><input type='button' value='Select' /></td><td>{$charity['CharityName']}</td><td><input type='submit' value='Edit' onclick=" .  '"document.location = ' . "'request_new_charity.php?id={$charity['CharityID']}&action=edit'" . '"' .  " />";
		if($charity['transactions'] <= 0)
			echo "<input type='submit' value='Delete'  onclick=" .  '"document.location = ' . "'request_new_charity.php?id={$charity['CharityID']}&action=delete'" . '"' .  "' />";
		echo "</td></tr>";
	}
?>
</table>
<?php include 'include/footer.php'; ?>