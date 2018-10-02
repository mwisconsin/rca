<?php
    include_once 'include/user.php';
    redirect_if_not_logged_in();

    require_once('include/date_time.php');
    require_once('include/ledger.php');
    require_once('include/care_facility.php');

	$franchise = get_current_user_franchise();

    if (! (current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee') || current_user_has_role($franchise, 'CareFacilityAdmin')) ) {
        header('Location: home.php');
		die();
    }

    if (!$_REQUEST['id']) {
        $affected_user_id = get_affected_user_id();
        if (user_has_role($affected_user_id, $franchise, 'CareFacilityAdmin')) {
            $facility_id = get_first_user_care_facility( $affected_user_id );
        }
    } else {
        $facility_id = $_REQUEST['id'];
    }

    if (!$facility_id) {
        header('Location: home.php');
    }

    $facility_info = get_care_facility($facility_id);

    // TODO:  If the user is a care facility admin and not a full admin, make sure they have
    // admin privs for the requested CF.
    if ($facility_id != $facility_info['CareFacilityID']) {
        $error_string[] = 'Requested care facility could not be found.';
    }

    $start_date = strtotime('-1 month', time() + 86400);
	$start_date_string = date("Y-m-d", $start_date);
    $start_date = strtotime($start_date_string);
    $end_date = NULL;

    $start = array('Year' => date('Y', $start_date), 'Month' => date('n', $start_date), 'Day' => date('j', $start_date ));
    $end = array('Year' => date('Y'), 'Month' => date('n'), 'Day' => date('j') );

    if (is_numeric($_POST['StartYear']) && 
        is_numeric($_POST['StartMonth']) && 
        is_numeric($_POST['StartDay'])) {

        $start_date_string = "{$_POST['StartYear']}-{$_POST['StartMonth']}-{$_POST['StartDay']}" ;
		$start_date = strtotime($start_date_string);
        $start['Year'] = $_POST['StartYear'];
        $start['Month'] = $_POST['StartMonth'];
        $start['Day'] = $_POST['StartDay'];
    }  

    if (is_numeric($_POST['EndYear']) && 
        is_numeric($_POST['EndMonth']) && 
        is_numeric($_POST['EndDay'])) {

        $end_date = "{$_POST['EndYear']}-{$_POST['EndMonth']}-{$_POST['EndDay']}";
        $end['Year'] = $_POST['EndYear'];
        $end['Month'] = $_POST['EndMonth'];
        $end['Day'] = $_POST['EndDay'];
    }  

    $ledger_entries = get_care_facility_ledger_entries($facility_id, null, $end_date);


    include_once('include/header.php');

	if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise_id, "Franchisee")){
        echo "<a href=\"edit_care_facility.php?action=edit_balance&id={$facility_id}\">Manual Ledger Entry</a>";
	}
?>

<h2><?php display_care_facility_header($facility_id ); ?>
<br /><br />
Ledger Detail</h2>
<?php if (is_array($error_string)) { 
        foreach ($error_string as $err) { ?>
    <h4 style="color: red"><?php echo $err ?></h4>
<?php }
} ?>

<form method="POST" action="">
Start Date: <?php print_year_select(2009, date('Y')-2008, 'StartYear', 'StartYear', $start['Year']);
                  print_month_select('StartMonth', 'StartMonth', $start['Month']);
                  print_day_select('StartDay', 'StartDay', $start['Day']); ?> <br />
End Date: <?php print_year_select(2009, date('Y')-2008, 'EndYear', 'EndYear', $end['Year']);
                print_month_select('EndMonth', 'EndMonth', $end['Month']);
                print_day_select('EndDay', 'EndDay', $end['Day']); ?>
<input type="submit" name="Filter" value="Apply Date Range Filter" />
                <br /><br />

<table border="1">
<tr><th>Entry ID</th><th>Entry Date</th><th>Effective Date</th><th>Description</th><th>Debit</th><th>Credit</th><th>Balance</th></tr>
<?php
    if ($ledger_entries) {
		
		$balance = 0;
        foreach ($ledger_entries as $entry) {
			if ($entry['EffectiveDate'] < date("Y-m-d",$start_date)) {
				$balance = $entry['EffectiveBalance'];
			}
        }
		$balance_date = date('m/d/Y', strtotime("-1 day", $start_date));
		$balance_str = format_dollars($balance);
		echo '<tr><td>&nbsp;</td><td>&nbsp;</td><td>' . $balance_date . '</td><td> Balance </td><td>&nbsp;</td><td>&nbsp;</td><td align=right>' . $balance_str . '</td></tr>';

        foreach ($ledger_entries as $entry) {
			if ($entry['EffectiveDate'] >= date("Y-m-d",$start_date)) {
				echo '<tr>';

				echo "<td>{$entry['LedgerEntryID']}</td>";

				// TODO:  Nicer format for date/time
				$entry_time_t = strtotime($entry['LedgerEntryTime']);
				//$nice_date = date('M j, Y G:i', $entry_time_t);
				$nice_date = date('m/d/Y', $entry_time_t);
				echo "<td>{$nice_date}</td>";

				// TODO:  Nicer format for date/time
				$entry_time_t = strtotime($entry['EffectiveDate']);
				$nice_date = date('m/d/Y', $entry_time_t);
				echo "<td>{$nice_date}</td>";

				echo "<td>{$entry['Description']}</td>";

				if ($entry['Cents'] > 0) {
					// Credit, not debit
					$amount_str = format_dollars($entry['Cents']);
					echo "<td align=right>&nbsp;</td><td align=right>{$amount_str}</td>";
				} else {
					// Debit
					$amount_str = format_dollars(abs($entry['Cents']));
					echo "<td align=right>{$amount_str}</td><td align=right>&nbsp;</td>";
				}        

				$balance_str = format_dollars($entry['EffectiveBalance']);
				echo "<td align=right>$balance_str</td>";

				echo '</tr>';
			}
        }
    }
?>
</table>

<?php
	include_once 'include/footer.php';
?>
