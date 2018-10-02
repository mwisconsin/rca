<?php
	require_once('include/user.php');
	require_once('include/name.php');
	require_once('include/functions.php');
	require_once('include/link.php');
	redirect_if_not_logged_in();
	$franchise = get_current_user_franchise();
	if (!current_user_has_role(1, 'FullAdmin') && !current_user_has_role($franchise, 'Franchisee')) {
        echo '<script type="text/javascript">history.go(-1);</script>';
        exit;
    }

    require_once('include/care_facility.php');


    $facility_id = $_REQUEST['id']; 
	
	if (!is_real_care_facility($facility_id)) {
        echo 'Unknown care facility ID:  ' . htmlspecialchars($_REQUEST['id']);
        exit;
    }


    $start_time_t = mktime(0, 0, 0, date('n') - 1, 1, date('Y'));

    $start_date = date('Y-m-d', $start_time_t);

    $start = array('Year' => date('Y', $start_time_t), 
                   'Month' => date('n', $start_time_t), 
                   'Day' => '01' );

    if (is_numeric($_REQUEST['StartYear']) && is_numeric($_REQUEST['StartMonth'])) {

        $start_date = "{$_REQUEST['StartYear']}-{$_REQUEST['StartMonth']}-01";
        $start['Year'] = $_REQUEST['StartYear'];
        $start['Month'] = $_REQUEST['StartMonth'];
    }  

    $end_date = date("Y-m-t",  strtotime( $start_date));

    $facility_info = get_care_facility( $facility_id );
    $facility_address = get_address($facility_info['FacilityAddressID']);
    $facility_contacts = get_care_facility_contacts_array( $facility_id );
    if ($facility_contacts) {
        foreach ($facility_contacts as $contact) {
            if ($contact['ContactRole'] == 'DecisionMaker') {
                $contact_name = get_name( $contact['ContactNameID'] );
                $facility_contact_name = get_displayable_person_name_string($contact_name);

                if ($contact['ContactTitle']) {
                    $facility_contact_name .= ", {$contact['ContactTitle']}";
                }
            } elseif (!isset($facility_contact_name)) {
                $contact_name = get_name( $contact['ContactNameID'] );
                $facility_contact_name = get_displayable_person_name_string($contact_name);

                if ($contact['ContactTitle']) {
                    $facility_contact_name .= ", {$contact['ContactTitle']}";
                }
            }
        }
    } else {
        $facility_contact_name = ' ';
    }


    $invoicee_contact = array('Name' => $facility_info['CareFacilityName'],
                              'ContactName' => $facility_contact_name,
                              'Address' => $facility_address);

    $invoice_columns = array( 'ResidentName', 'LinkDate', 'OrigTime', 'DestTime',
                              'From', 'To', 'Miles', 'Status', 'RecommendedCharge', 
                              'LinkCents' );
    

    $invoice_column_names = array( 'ResidentName' => 'Facility Rider', 'LinkDate' => 'Date', 
                                   'OrigTime' => 'Orig<br />Time', 'DestTime' => 'Dest<br />Time',
                                   'From' => 'From', 'To' => 'To', 'Miles' => 'Miles', 'Status' => 'Status', 
                                   'RecommendedCharge' => 'Recmd<br /> Rider <br /> Charge', 
                                   'LinkCents' => 'Care <br />Facility<br /> Charge' );

    $cf_links = get_care_facility_links($facility_id, $start_date, $end_date);

    $resident_total = 0;
    $cf_total = 0;
	$ride_count = 0;

    if ($cf_links) {
        $cf_rider_links = array();
        $rider_summary = array();

        $destinations = array();

        // Iterate through the bulk links and sort into buckets for the riders
        // (and as we go, subtotal the riders)
        foreach ($cf_links as $cf_link) {
            $rider_uid = $cf_link['UserID'];

            $rider_name = get_displayable_person_name_string($cf_link);
            $arrival_time = get_link_arrival_time($cf_link);

            $link_date = date('m/d/y', $arrival_time['time_t']);
            $orig_time = date('G:i', $arrival_time['time_t'] - (($cf_link['EstimatedMinutes'] + 10) * 60));
            $dest_time = date('G:i', $arrival_time['time_t']);


            if (array_key_exists($cf_link['FromDestinationID'], $destinations)) {
                $from_destination = $destinations[$cf_link['FromDestinationID']];
            } else {
                $raw_destination = get_destination($cf_link['FromDestinationID']);
                if ($raw_destination['IsPublic'] == 'Yes' && $raw_destination['IsPublicApproved'] == 'Yes') {
                    $from_destination = $raw_destination['Name'] . '<br />' . 
                                        create_compact_display_address($raw_destination);
                } else {
                    $from_destination = create_compact_display_address($raw_destination);
                }
                $destinations[$cf_link['FromDestinationID']] = $from_destination;
            }

            if (array_key_exists($cf_link['ToDestinationID'], $destinations)) {
                $to_destination = $destinations[$cf_link['ToDestinationID']];
            } else {
                $raw_destination = get_destination($cf_link['ToDestinationID']);
                if ($raw_destination['IsPublic'] == 'Yes' && $raw_destination['IsPublicApproved'] == 'Yes') {
                    $to_destination = $raw_destination['Name'] . '<br />' . 
                                        create_compact_display_address($raw_destination);
                } else {
                    $to_destination = create_compact_display_address($raw_destination);
                }
                $destinations[$cf_link['ToDestinationID']] = $to_destination;
            }



            $cf_rider_links[$rider_uid][] = array( 'ResidentName' => $rider_name,
                                                   'LinkDate' => $link_date,
                                                   'OrigTime' => $orig_time,
                                                   'DestTime' => $dest_time,
                                                   'From' => $from_destination,
                                                   'To' => $to_destination,
                                                   'Miles' => $cf_link['Distance'],
                                                   'Status' => $cf_link['LinkStatus'],
                                                   'RecommendedCharge' => format_dollars(
                                                                            $cf_link['QuotedCents'] + 100),
                                                   'LinkCents' => format_dollars($cf_link['QuotedCents']) );
			$ride_count += 1;

            $rider_summary[$rider_uid]['LinkCents'] += $cf_link['QuotedCents'];
            $rider_summary[$rider_uid]['RecommendedCharge'] += $cf_link['QuotedCents'] + 100;
            $rider_summary[$rider_uid]['ResidentName'] = $rider_name;

            $cf_total += $cf_link['QuotedCents'];
            $resident_total += $cf_link['QuotedCents'] + 100;
        }
    } else {
        // TODO:  Single blank entry with summary row...
    }


    $invoice_data = array();
    if ($cf_rider_links) {
        foreach ($cf_rider_links as $rider_uid => $value) {
            $rider_summary[$rider_uid]['LinkCents'] = format_dollars($rider_summary[$rider_uid]['LinkCents']);
            $rider_summary[$rider_uid]['RecommendedCharge'] = format_dollars($rider_summary[$rider_uid]['RecommendedCharge']);

            $invoice_data[] = array('Rows' => $value,
                                    'SummaryRow' => $rider_summary[$rider_uid]);

        }
    } 

	$total_summary['LinkCents'] = format_dollars($cf_total);
	$total_summary['RecommendedCharge'] = format_dollars($resident_total);
	$total_summary['ResidentName'] = "Totals";

	$empty = array(  );
	$invoice_data[] = array('Rows' => $empty,
							'SummaryRow' => $total_summary);
							
	$balance = calculate_ledger_balance_on_date( 'CAREFACILITY',$facility_id, $start_date);
	$ledger_entries = get_care_facility_ledger_entries_for_invoice($facility_id, $start_date, $end_date);
	
    global $ADDITIONAL_RC_JAVASCRIPT;
    if (!is_array($ADDITIONAL_RC_JAVASCRIPT)) {
        $ADDITIONAL_RC_JAVASCRIPT = array();
    }
    $ADDITIONAL_RC_JAVASCRIPT[] = 'datepicker.js';
    include('include/header.php');
	echo $start_date . ' - ' . $end_date;
?>
<h1>Care Facility Invoice</h1>

<div class="noprint">
    <form method="POST" action="">
    Invoice for month: <?php print_month_select('StartMonth', 'StartMonth', $start['Month']); print_year_select(2009, date('Y')-2008, 'StartYear', 'StartYear', $start['Year']); ?>
        <script type="text/javascript">
        // <![CDATA[  
            var opts = {                            
                    formElements:{"StartDay":"j","StartYear":"Y","StartMonth":"n"},
                    statusFormat:"l-cc-sp-d-sp-F-sp-Y"
                };           
            datePickerController.createDatePicker(opts);
        // ]]>
        </script>
    &nbsp;&nbsp;&nbsp;
    <input type="submit" name="Filter" value="Select New Invoice Month" />
    <input type="hidden" name="id" value="<?php echo $facility_id ?>" />
    <br /><br />
    </form>
</div>

<?php 
    // Use the template to display the main data table
    include('templates/invoices.php'); 
?>
<h2> Summary </h2>
<?php  
	$balance_date=  date('m/d/y', strtotime("-1 day", strtotime($start_date)));
?>
<table border="1" width="100%">
<tr> <td> </td> <td> Date </td><td> Description </td><td> </td> <td> </td> <td> </td> <td> </td> <td> </td> <td>  Debit </td><td> Credit </td></tr>
<tr> <td> </td> <td> <?php echo  $balance_date; ?> </td><td> Balance forward </td> <td> </td> <td> </td> <td> </td> <td> </td><td> </td> <td> 
<?php
	if ($balance < 0){
		echo format_dollars(-$balance) . '</td><td>';}
	else {
		echo '</td><td>' . format_dollars($balance);}
?></td></tr>
<?php
	if ($ledger_entries){
        foreach ($ledger_entries as $ledger_entry) {
			echo '<tr><td></td> <td> ' . date('m/d/y', strtotime($ledger_entry["EffectiveDate"])) . '</td><td>' .  $ledger_entry["Description"]  . '</td> <td> </td> <td> </td> <td> </td> <td> </td><td></td> <td> ';
			if ($ledger_entry["Cents"] < 0){
				echo format_dollars(-$ledger_entry["Cents"]) . '</td><td>';}
			else {
				echo '</td><td>' . format_dollars($ledger_entry["Cents"]);}
			echo '</td></tr>';
			$balance += $ledger_entry["Cents"];
	}}
	$balance -= $cf_total;
?>
<tr> <td> </td> <td> <?php echo date('m/d/y', strtotime($end_date)); ?> </td><td><?php echo $ride_count; ?> rides </td><td> </td> <td> </td> <td> </td></td> <td>  <td> </td> <td> <?php echo format_dollars($cf_total) ?></td><td> </td></tr> 
<tr> <td> </td> <td> <?php echo date('m/d/y', strtotime($end_date)); ?> </td><td> Ending balance </td><td></td> <td> </td> <td> </td> <td> </td> <td> </td> <td>  
<?php
	if ($balance < 0){
		echo format_dollars(-$balance) . '</td><td>';}
	else {
		echo '</td><td>' . format_dollars($balance);}
?></td></tr>
</table>
<?php
    include('include/footer.php');
?>
