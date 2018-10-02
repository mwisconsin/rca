<?php
	require_once('include/user.php');
	require_once('include/name.php');
	require_once('include/functions.php');
	require_once('include/link.php');
	require_once 'include/franchise.php';
	//error_reporting(E_ALL);
	redirect_if_not_logged_in();
	$franchise = get_current_user_franchise();
	if (!current_user_has_role(1, 'FullAdmin') && !current_user_has_role($franchise, "Franchisee")) {
        echo '<script type="text/javascript">history.go(-1);</script>';
        exit;
    }

    require_once('include/business_partners.php');


    $partner_id = $_REQUEST['id']; 
	$print = isset($_REQUEST['print']);

    $partner_record = get_business_partner_record($partner_id);
	if (!$partner_record) {
        echo 'Unknown business partner:  ' . htmlspecialchars($_REQUEST['id']);
        exit;
    }


//    $start_time_t = mktime(0, 0, 0, date('n') - 1, 1, date('Y'));
//    $end_time_t = mktime(0, 0, 0, date('n'), 0, date('Y'));

//    $start_date = date('Y-m-d', $start_time_t);
//    $end_date = date('Y-m-d', $end_time_t);
//
//    $start = array('Year' => date('Y', $start_time_t), 
//                   'Month' => date('n', $start_time_t), 
//                   'Day' => date('j', $start_time_t) );
//    $end = array('Year' => date('Y', $end_time_t), 
//                   'Month' => date('n', $end_time_t), 
//                   'Day' => date('j', $end_time_t) );
//
//
//
//    if (is_numeric($_REQUEST['StartYear']) && 
//        is_numeric($_REQUEST['StartMonth']) && 
//        is_numeric($_REQUEST['StartDay'])) {
//
//        $start_date = "{$_REQUEST['StartYear']}-{$_REQUEST['StartMonth']}-{$_REQUEST['StartDay']}";
//        $start['Year'] = $_REQUEST['StartYear'];
//        $start['Month'] = $_REQUEST['StartMonth'];
//        $start['Day'] = $_REQUEST['StartDay'];
//    }  
//
//    if (is_numeric($_REQUEST['EndYear']) && 
//        is_numeric($_REQUEST['EndMonth']) && 
//        is_numeric($_REQUEST['EndDay'])) {
//
//        $end_date = "{$_REQUEST['EndYear']}-{$_REQUEST['EndMonth']}-{$_REQUEST['EndDay']}";
//        $end['Year'] = $_REQUEST['EndYear'];
//        $end['Month'] = $_REQUEST['EndMonth'];
//        $end['Day'] = $_REQUEST['EndDay'];
//    }  

?>


<?php

		$start_date = isset($_REQUEST["start_date"]) ? $_REQUEST["start_date"] : date('m/d/Y',strtotime('first day of last month'));
		$end_date = isset($_REQUEST["end_date"]) ? $_REQUEST["end_date"] : date('m/d/Y',strtotime('last day of last month'));

    // TODO:  Partner contact and address
    $invoicee_contact = array('Name' => $partner_record['Name'],
                              'ContactName' => '',
                              'Address' => NULL);

    $invoice_columns = array( 'Location', 'LinkDate', 'OrigTime', 'DestTime',
                              'From', 'To', 'Miles', 'LinkCents', 'PaymentType', 
                              'PartnerCents' );
    

    $invoice_column_names = array( 'Location' => 'Location', 'LinkDate' => 'Date', 
                                   'OrigTime' => 'Orig Time', 'DestTime' => 'Dest Time',
                                   'From' => 'From', 'To' => 'To', 'Miles' => 'Miles', 
                                   'LinkCents' => 'Price', 'PaymentType' => 'Pmt Type', 
                                   'PartnerCents' => 'Pmt Due' );




    $bp_links = get_business_partner_links($partner_id, $start_date, $end_date);

    $rider_total = 0;
    $bp_total = 0;
    $bp_loc_links = array();


        $bp_loc_links = array();
        $location_summary = array();

        $destinations = array();
        $location_names = array();

        // Iterate through the bulk links and sort into buckets for the locations
        // (and as we go, subtotal the riders)
        foreach ($bp_links as $bp_link) {
            $bp_loc = ($bp_link['FromIsPartner'] == 'YES') ? $bp_link['FromDestinationID'] : 
                                                             $bp_link['ToDestinationID'];

            $arrival_time = get_link_arrival_time($bp_link);

            $link_date = date('m/d/Y', $arrival_time['time_t']);
            $orig_time = date('G:i', $arrival_time['time_t'] - (($bp_link['EstimatedMinutes'] + 10) * 60));
            $dest_time = date('G:i', $arrival_time['time_t']);


            if (array_key_exists($bp_link['FromDestinationID'], $destinations)) {
                $from_destination = $destinations[$bp_link['FromDestinationID']];
            } else {
                $raw_destination = get_destination($bp_link['FromDestinationID']);
                if ($raw_destination['IsPublic'] == 'Yes' && $raw_destination['IsPublicApproved'] == 'Yes') {
                    $from_destination = $raw_destination['Name'] . '<br />' . 
                                        create_compact_display_address($raw_destination);
                } else {
                    $from_destination = create_compact_display_address($raw_destination);
                }

                if ($bp_link['FromIsPartner'] == 'YES') {
                    $from_destination = "<b>$from_destination</b>";
                    $location_summary[$bp_loc]['Address'] = $raw_destination;
                }

                $destinations[$bp_link['FromDestinationID']] = $from_destination;
                $location_names[$bp_link['FromDestinationID']] = $raw_destination['Name'] . 
                                                                 (($raw_destination['DestinationDetail'] ?
                                                                  "<br />{$raw_destination['DestinationDetail']}" :
                                                                  ''));
            }

            if (array_key_exists($bp_link['ToDestinationID'], $destinations)) {
                $to_destination = $destinations[$bp_link['ToDestinationID']];
            } else {
                $raw_destination = get_destination($bp_link['ToDestinationID']);
                if ($raw_destination['IsPublic'] == 'Yes' && $raw_destination['IsPublicApproved'] == 'Yes') {
                    $to_destination = $raw_destination['Name'] . '<br />' . 
                                        create_compact_display_address($raw_destination);
                } else {
                    $to_destination = create_compact_display_address($raw_destination);
                }

                if ($bp_link['ToIsPartner'] == 'YES') {
                    $to_destination = "<b>$to_destination</b>";
                    $location_summary[$bp_loc]['Address'] = $raw_destination;
                }

                $destinations[$bp_link['ToDestinationID']] = $to_destination;
                $location_names[$bp_link['ToDestinationID']] = $raw_destination['Name'] . 
                                                               (($raw_destination['DestinationDetail'] ?
                                                                "<br />{$raw_destination['DestinationDetail']}" :
                                                                ''));
            }

            $location_name = ($bp_link['FromIsPartner'] == 'YES') ? 
                                       $location_names[$bp_link['FromDestinationID']] :
                                       $location_names[$bp_link['ToDestinationID']];

            $link_price = $bp_link['QuotedCents'] + $bp_link['BPCents'];

            $bp_loc_links[$bp_loc][] = array( 'Location' => $location_name,
                                              'LinkDate' => $link_date,
                                              'OrigTime' => $orig_time,
                                              'DestTime' => $dest_time,
                                              'From' => $from_destination,
                                              'To' => $to_destination,
                                              'Miles' => $bp_link['Distance'],
                                              'PartnerCents' => format_dollars($bp_link['BPCents']),
                                              'LinkCents' => format_dollars($link_price) );

            $location_summary[$bp_loc]['LinkCents'] += $link_price;
            $location_summary[$bp_loc]['PartnerCents'] += $bp_link['BPCents'];
            $location_summary[$bp_loc]['Location'] = $location_name;
            
			if(($_REQUEST['LocationID'] && $_REQUEST['LocationID'] != -1 && $_REQUEST['LocationID'] == $bp_loc) || !isset($_REQUEST['LocationID']) || $_REQUEST['LocationID'] == -1 ){
            	$rider_total += $link_price;
            	$bp_total += $bp_link['BPCents'];
            }
        }

	if($_REQUEST['LocationID'] && $_REQUEST['LocationID'] != -1){
		$invoicee_contact = array('Name' => $location_summary[$_REQUEST['LocationID']]['Location'],
                                  'ContactName' => '',
                                  'Address' => $location_summary[$_REQUEST['LocationID']]['Address']);
	}
    $invoice_data = array();
    if ($bp_loc_links) {
        foreach ($bp_loc_links as $bp_loc => $value) {
        	if(($_REQUEST['LocationID'] && $_REQUEST['LocationID'] != -1 && $_REQUEST['LocationID'] == $bp_loc) || !isset($_REQUEST['LocationID']) || $_REQUEST['LocationID'] == -1 ){
            	$location_summary[$bp_loc]['LinkCents'] = format_dollars($location_summary[$bp_loc]['LinkCents']);
            	$location_summary[$bp_loc]['PartnerCents'] = format_dollars($location_summary[$bp_loc]['PartnerCents']);
			
            	$invoice_data[] = array('Rows' => $value,
            	                        'SummaryRow' => $location_summary[$bp_loc]);
			}
        }
    } 

	
    global $ADDITIONAL_RC_JAVASCRIPT;
    if (!is_array($ADDITIONAL_RC_JAVASCRIPT)) {
        $ADDITIONAL_RC_JAVASCRIPT = array();
    }
    $ADDITIONAL_RC_JAVASCRIPT[] = 'datepicker.js';
    if(!$print){
    	include('include/header.php');
    	echo "<h1>Business Partner Invoice</h1>";
   	} else { 
   		include('include/print_header.php');
   	?>
   		<img src="images/logo3.png" alt="" style="float:left;" /><br><br><br><br><br><br><br><div style="font-size:3em; text-align:center;">Business Partner Invoice</div>
   	<?php }
?>


<div class="noprint">
	<form method="POST" action="">
	<?php
	if(!$print){
		$locationID = array_keys($location_summary);
		?>
		<select name="LocationID">
			<option value="-1">View All</option>
			<?php for($i = 0; $i < count($location_summary); $i++){ ?>
				<option value="<?php echo $locationID[$i]; ?>"<?php if($_REQUEST['LocationID'] == $locationID[$i]) echo ' SELECTED'; ?>><?php echo $location_summary[$locationID[$i]]['Location']; ?></option>
			<?php } ?>
		</select> <?php
		echo "<input type=\"submit\" value=\"print\" name=\"print\"><br>";
	}
	if(!$print){?>
		Start Date: <input type=text size=10 value="<?php echo $start_date; ?>" class=jq_datepicker name=start_date>
    <br />
    End Date: <input type=text size=10 value="<?php echo $end_date; ?>" class=jq_datepicker name=end_date>
    &nbsp;&nbsp;&nbsp;
    <?php } else { ?>
    <div style="text-align:right; float:right;">
    	Start Date: <?php echo date('F d, Y',strtotime($start_date)); ?><br>
    	End Date: <?php echo date('F d, Y',strtotime($end_date)); ?>
    </div>
    <?php } if(!$print){ ?>
    <input type="submit" name="Filter" value="Apply Date Range Filter" />
    <?php } ?>
    <input type="hidden" name="id" value="<?php echo $partner_id ?>" />
    <br /><br />
    </form>
</div>

<?php 
    // Use the template to display the main data table
    include('templates/invoices.php'); 
?>

<br />
<table border="1">
<tr><td>Rider usage Total</td><td><?php
    echo format_dollars($rider_total) ?></td></tr>
<tr><td>Business Partner Total</td><td><?php
    echo format_dollars($bp_total) ?></td></tr>
</table>
<?php if($print){ ?>
<hr>
<table class="PaymentSlip" width="100%">
	<tr>
		<td><b>Please Remit with payment</b></td>
		<td style="text-align:right;" colspan="3"><?php echo date("n/j/Y");?></td>
	</tr>
	<tr>
		<td>From:</td>
		<td><?php echo create_compact_display_address($invoicee_contact['Address']); ?></td>
		<td>Directors Name</td>
		<td style="text-align:right;">
			<?php echo $invoicee_contact['Name'];?><br>
			Re: Business Partner Agreement<br>
			<?php echo format_dollars($bp_total) ?> - Due upon Request
			For rides from <?php echo date('m/d/Y',strtotime($start_date)); ?> through <?php echo date('m/d/Y',strtotime($end_date)); ?>

		</td>
	</tr>
	<tr>
		<td>Mail Payment To:</td>
		<td>
			Riders Club of America<br>
			222 Third Ave SE, Suite 220<br>
			Cedar Rapids, IA 52401-1524
		</td>
		<td>Directors Name</td>
	</tr>

</table>
<?php } ?>
<?php
	if(!$print)
    	include('include/footer.php');
    else
    	include('include/print_footer.php');
?>

