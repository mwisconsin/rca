<?php
    require_once('include/user.php');
    redirect_if_not_logged_in();
	require_once 'include/franchise.php';
    require_once('include/date_time.php');
    require_once('include/ledger.php');

	$franchise = get_current_user_franchise();
	if ((!current_user_has_role(1, 'FullAdmin') && !current_user_has_role($franchise, 'Franchisee')) || !($_REQUEST['id'])) {
        echo '<script type="text/javascript">history.go(-1);</script>';
        exit;
    }

    $entry_id = $_REQUEST['id'];

    //var_export($_POST);
    if ($_POST['id'] && $_POST['id'] == $entry_id && $_POST['AppendedDescription']) {
        append_to_description($entry_id, $_POST['AppendedDescription']);
    }

    $ledger_entry = get_ledger_entry($entry_id);

    if (!$ledger_entry) {
        $errors[] = "Could not retrieve ledger entry";
    }



include_once('include/header.php');

?>
<h2>Ledger Entry Detail</h2>


<table border="1">
<tr><th>Entry ID</th><th>Entity Type</th><th>Entity ID</th><th>Subaccount</th><th>Amount (cents)</th><th>Description</th><th>Entry Date</th><th>Effective Date</th></tr>
<?php
    if ($ledger_entry) {
        echo '<tr>';
        echo "<td>{$ledger_entry['LedgerEntryID']}</td>";
        echo "<td>{$ledger_entry['EntityType']}</td>";
        echo "<td>{$ledger_entry['EntityID']}</td>";
        echo "<td>{$ledger_entry['SubAccount']}</td>";
        echo "<td>{$ledger_entry['Cents']}</td>";
        echo "<td>{$ledger_entry['Description']}</td>";
        echo "<td>{$ledger_entry['LedgerEntryTime']}</td>";
        echo "<td>{$ledger_entry['EffectiveDate']}</td>";
        echo '</tr>';
    }
?>
</table>
<br /><br />
<form method="POST">
Append to Description:  <input type="text" name="AppendedDescription" size="30" /><br />
<input type="submit" name="Submit" />
<input type="hidden" name="id" value="<?php echo $entry_id ?>" />
</form>

<?php
	include_once 'include/footer.php';

?>
