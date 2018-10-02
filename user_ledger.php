<?php
    include_once 'include/user.php';
    redirect_if_not_logged_in();

    require_once('include/date_time.php');
    require_once('include/ledger.php');
	require_once('include/franchise.php');
	$franchise = get_current_user_franchise();

    global $ADDITIONAL_RC_JAVASCRIPT;
    if (!is_array($ADDITIONAL_RC_JAVASCRIPT)) {
        $ADDITIONAL_RC_JAVASCRIPT = array();
    }
    $ADDITIONAL_RC_JAVASCRIPT[] = 'datepicker.js';
    include_once('include/header.php');
    $user_id = get_affected_user_id();


    $thirty_one_days_ago = time() - (86400 * 31);
    $start_date = date('Y-n-j', $thirty_one_days_ago);
    $end_date = NULL;

    $start = array('Year' => date('Y', $thirty_one_days_ago), 
                   'Month' => date('n', $thirty_one_days_ago), 
                   'Day' => date('j', $thirty_one_days_ago) );
    $end = array('Year' => date('Y'), 'Month' => date('n'), 'Day' => date('j') );

    if (is_numeric($_REQUEST['StartYear']) && 
        is_numeric($_REQUEST['StartMonth']) && 
        is_numeric($_REQUEST['StartDay'])) {

        $start_date = "{$_REQUEST['StartYear']}-{$_REQUEST['StartMonth']}-{$_REQUEST['StartDay']}";
        $start['Year'] = $_REQUEST['StartYear'];
        $start['Month'] = $_REQUEST['StartMonth'];
        $start['Day'] = $_REQUEST['StartDay'];
    }  

    if (is_numeric($_REQUEST['EndYear']) && 
        is_numeric($_REQUEST['EndMonth']) && 
        is_numeric($_REQUEST['EndDay'])) {

        $end_date = "{$_REQUEST['EndYear']}-{$_REQUEST['EndMonth']}-{$_REQUEST['EndDay']}";
        $end['Year'] = $_REQUEST['EndYear'];
        $end['Month'] = $_REQUEST['EndMonth'];
        $end['Day'] = $_REQUEST['EndDay'];
    }  
//echo "USER ID $user_id<br><br>";
    $ledger_entries = get_user_ledger_entries($user_id, $start_date, $end_date);


	if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee')){
		echo "<a href=\"manual_ledger_entry.php\">Make Manual Ledger Entry</a>";
	}

?>
<h2>Transaction Record</h2>

<form method="POST" action="">
Start Date: <?php print_year_select(2009, date('Y')-2008, 'StartYear', 'StartYear', $start['Year']);
                  print_month_select('StartMonth', 'StartMonth', $start['Month']);
                  print_day_select('StartDay', 'StartDay', $start['Day']); ?>
    <script type="text/javascript">
    // <![CDATA[  
        var opts = {                            
                formElements:{"StartDay":"j","StartYear":"Y","StartMonth":"n"},
                statusFormat:"l-cc-sp-d-sp-F-sp-Y"
            };           
        datePickerController.createDatePicker(opts);
    // ]]>
    </script>
                  <br />
End Date: <?php print_year_select(2009, date('Y')-2008, 'EndYear', 'EndYear', $end['Year']);
                print_month_select('EndMonth', 'EndMonth', $end['Month']);
                print_day_select('EndDay', 'EndDay', $end['Day']); ?>
    <script type="text/javascript">
    // <![CDATA[  
        var opts = {                            
                formElements:{"EndDay":"j","EndYear":"Y","EndMonth":"n"},
                statusFormat:"l-cc-sp-d-sp-F-sp-Y"
            };           
        datePickerController.createDatePicker(opts);
    // ]]>
    </script>
&nbsp;&nbsp;&nbsp;
<input type="submit" name="Filter" value="Apply Date Range Filter" />
                <br /><br />



<table border="1">
<tr><th>#</th><th>Entry ID</th><th>Entry Date</th><th>Effective Date</th><th>Description</th><th>Debit</th><th>Credit</th><th>Balance</th></tr>
<?php
    if ($ledger_entries) {
        $index = 1;
        foreach ($ledger_entries as $entry) {
            echo '<tr>';

            echo '<td>' . $index++ . '</td>';

            if (current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee')) {
                echo "<td><a href=\"ledger_detail.php?id={$entry['LedgerEntryID']}\">{$entry['LedgerEntryID']}</a></td>";
            } else {
                echo "<td>{$entry['LedgerEntryID']}</td>";
            }

            // TODO:  Nicer format for date/time
            $entry_time_t = strtotime($entry['LedgerEntryTime']);
            //$nice_date = date('M j, Y G:i', $entry_time_t);
            $nice_date = date('M j, Y', $entry_time_t);
            echo "<td>{$nice_date}</td>";

            // TODO:  Nicer format for date/time
            $entry_time_t = strtotime($entry['EffectiveDate']);
            $nice_date = date('M j, Y', $entry_time_t);
            echo "<td>{$nice_date}</td>";

            echo "<td>{$entry['Description']}</td>";

            if ($entry['Cents'] > 0) {
                // Credit, not debit
                $amount_str = format_dollars($entry['Cents']);
                echo "<td>&nbsp;</td><td>{$amount_str}</td>";
            } else {
                // Debit
                $amount_str = format_dollars(abs($entry['Cents']));
                echo "<td>{$amount_str}</td><td>&nbsp;</td>";
            }        

            $balance_str = format_dollars($entry['EffectiveBalance']);
            echo "<td>$balance_str</td>";

            echo '</tr>';
        }
    }
?>
</table>

<?php
	include_once 'include/footer.php';

?>
