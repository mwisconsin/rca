<?php
    require_once('include/user.php');
    redirect_if_not_logged_in();

    require_once('include/destinations.php');
    require_once('include/rider.php');
    require_once('include/date_time.php');
    require_once('include/link.php');
    require_once('include/name.php');
    require_once('include/large_facility.php');

    if (!(if_current_user_has_role('FullAdmin') || if_current_user_has_role('LargeFacilityAdmin'))) {
        header('Location: home.php');
    }

    $facility_id = get_working_large_facility();
    $facility_info = get_large_facility($facility_id);
    $facility_address = get_address($facility_info['FacilityAddressID']);

    session_start();
    if ($_POST['Continue']) {
        $_SESSION['LFSCHED']['dest_type'] = $_POST['DestType'];
        $_SESSION['LFSCHED']['location_text'] = $_POST['LocationText'];
        $_SESSION['LFSCHED']['location_id'] = $_POST['LocationID'];
        session_commit();


        // There is only one time, so that means no complicated sorting out of times.

        // TODO: Verify destinations

        $all_valid = TRUE;
        $error_string = array();
        $td = $_SESSION['LFSCHED']['travel_date'];

        // Verify travel date
        if (!date_is_schedulable($td['year'], $td['month'], $td['day'], 0, 0) &&
            !if_current_user_has_role('FullAdmin')) {
            $all_valid = FALSE;
            $error_string[] = "Unschedulable date.";
            rc_log(PEAR_LOG_ERR, "Unschedulable date.");
        }

        // Build a "standard" link array and send to the usual confirm page.
        $the_link = array();
        if ($_SESSION['LFSCHED']['to_from'] == 'To') {
            $the_link['to'] = $facility_info['FacilityDestinationID'];
            $the_link['from'] = abs($_POST['LocationID']);
        } elseif ($_SESSION['LFSCHED']['to_from'] == 'From') {
            $the_link['to'] = abs($_POST['LocationID']);
            $the_link['from'] = $facility_info['FacilityDestinationID'];
        } else {
            $all_valid = FALSE;
            $error_string[] = "Unknown whether the ride is to or from the facility";
        }

        $tt = $_SESSION['LFSCHED']['travel_time'];
        if ($_SESSION['LFSCHED']['when_type'] == 'Arrive') {
            $the_link['to_time']['hour'] = ($tt['AM_PM'] == 'PM' && $tt['hour'] != 12) ? $tt['hour'] + 12 : $tt['hour'];
            $the_link['to_time']['minute'] = $tt['minute'];
        } elseif ($_SESSION['LFSCHED']['when_type'] == 'Depart') {
            $the_link['from_time']['hour'] = ($tt['AM_PM'] == 'PM' && $tt['hour'] != 12) ? $tt['hour'] + 12 : $tt['hour'];
            $the_link['from_time']['minute'] = $tt['minute'];
        } else {
            $all_valid = FALSE;
            $error_string[] = "Unknown whether listed time is arrival or departure.";
        }

        $links = array( $the_link );
        if ($all_valid) {
            // Build up a nice data structure.  Pass it along.
            $requested_rides = array( 'year' => $td['year'],
                                      'month' => $td['month'],
                                      'day' => $td['day'],
                                      'links' => $links,
                                      'ridercount' => 1
                                      );

            $request = serialize($requested_rides);
            $hash = sha1($request . 'A little extra verification');
            $request = urlencode($request);

            header("Location: confirm_ride_new.php?ver=$hash&rides=$request");
            exit;
        }

    }


$travel_date = $_SESSION['LFSCHED']['travel_date'];
$travel_time = $_SESSION['LFSCHED']['travel_time'];
$lf_rider_info = get_large_facility_rider($_SESSION['LFSCHED']['rider_id']);
$rider_info = get_user_rider_info($facility_info['RiderUserID']);
$location_name = $_SESSION['LFSCHED']['location_text'];

include('include/header.php');
?>

<br /><br />
<?php if (isset($error_string) && is_array($error_string) && count($error_string) > 0) {
    foreach ($error_string as $err) {
        echo "<span class=\"error_text\">$err</span><br />\n";
    }
    echo "<br />\n";
} ?>
<form method="POST" action="">
    <fieldset><legend>Ride Information</legend>
    Ride For:  <?php
        echo "{$lf_rider_info['FirstName']} {$lf_rider_info['LastName']}"; ?><br />
    Going <?php echo $_SESSION['LFSCHED']['to_from'] ?>:  <?php
        echo "{$facility_address['Address1']}, {$facility_address['City']}, {$facility_address['State']}"; ?><br />
    <?php echo $_SESSION['LFSCHED']['when_type'] ?>: <?php 
        printf("%d:%02.2d %s", $travel_time['hour'], $travel_time['minute'], $travel_time['AM_PM']);
        echo " on {$travel_date['month']}-{$travel_date['day']}."; ?>
    </fieldset>


    <fieldset><legend><?php echo ($_SESSION['LFSCHED']['to_from'] == 'To') ? 'From' : 'To'; ?></legend>
    <div style="float: left">
        <span id="LocationName"><?php echo $location_name ?></span><br />
        <input type="hidden" id="LocationText" name="LocationText" value="<?php 
            echo $location_name ?>" />
        <input type="radio" name="DestType" value="rider" <?php
            if ($_SESSION['LFSCHED']['dest_type'] == 'rider') { echo 'checked="checked" '; } ?>/> Rider Destination -  
            
    <?php $rider_dest_select = get_rider_destination_selector($facility_info['RiderUserID'], 0, 
                                                        'Select a destination...', 
                                                        abs($cached_schedule['Location'][$idx]));
echo $rider_dest_select ?>
        <br />
        <input type="radio" name="DestType" value="public" <?php
            if ($_SESSION['LFSCHED']['dest_type'] == 'public') { echo 'checked="checked" '; } ?>/> Public Destination<br />

<div id="DestinationSelectionWidget" style="display:none;">
<?php echo get_destination_selection_widget($facility_info['FranchiseID'], $href); // TODO:  better HREF ?>
    <input type="hidden" id="LocationID" name="LocationID" value="<?php echo $_SESSION['location_id'] ?>" />
</div>
    <br />
    <input type="button" value="Create Destination" onclick="window.open('add_destination.php','AddDestination', 'menubar=no,width=380,height=370,toolbar=no');" />
    </div><div style="clear: both;"> </div>
    </fieldset>

    <br />
    <input type="submit" name="Continue" value="Continue" />

<div style="float:right;">
</div>
</form>



<script type="text/javascript">

    function decorate_radio_buttons() {
        $each($$('input[type="radio"]'), function(item) {
            item.addEvents( {
                'change': change_destination_selector
            });
        });
    }


    function decorate_buttons() {
         decorate_radio_buttons();

        decorate_public_destinations(0);
        decorate_public_destinations(1);

        decorate_location_selector(0);
        decorate_location_selector(1);
    }

    function decorate_public_destinations(index) {
        $each($$('div#DestinationSelectionWidget a'), function(item) {
            item.addEvents( {
                'click' : function(evt) {
                    evt.stop();

                    var link = evt.target;
                    var href = link.href;
                    destinationID = href.substring(href.lastIndexOf('/') + 1, href.length);
                    var text = link.innerHTML;
                    set_destination(text, destinationID);
                }
            });
        });
    }

    function decorate_location_selector(index) {
        $('destination_selector[0]').addEvents( {
                'change' : function(evt) {
                    var sel = evt.target;
                    var opt = sel.options[sel.selectedIndex];
                    set_destination(opt.text, opt.value);
                }
        });
    }

    function change_destination_selector(evt) {
        var b_index = 0; 
        if (evt.target.value == 'public') {
            $('destination_selector[' + b_index + ']').setStyle('display', 'none');
            $('DestinationSelectionWidget').setStyle('display', 'block');
            decorate_public_destinations(b_index);
        } else if (evt.target.value == 'rider') {
            $('destination_selector[' + b_index + ']').setStyle('display', '');
            $('DestinationSelectionWidget').setStyle('display', 'none');
        }
    }

    function set_destination(description, id) {
        $('LocationName').set('text', description);
        $('LocationText').value = description;
        $('LocationID').value = id;
    }

    window.addEvent('domready', decorate_buttons);

    function new_destination(DestinationID, DestinationName, DestinationAddress){
        var dest_list = $('destination_selector[0]');
        var new_option_text = DestinationName + ' - ' + DestinationAddress; 

        new Element('option',{'html': new_option_text, 'value': '-' + DestinationID}).inject($('destination_selector[0]'), 'bottom');
        if($('destination_selector[0]').value == 'NOTSET'){
            $('destination_selector[0]').selectedIndex = $('destination_selector[0]').length - 1;
            set_destination(new_option_text, DestinationID);
        }
        
    }


	function toggle_public_destination_group($name){
	    if($($name).getStyle('display') == 'none')
	        $($name).setStyle('display', '');
	    else
	        $($name).setStyle('display','none');
	}

</script>

<?php
    include_once 'include/footer.php';


function date_is_schedulable($year, $month, $day, $hour, $minute, $reset = FALSE) {
    global $earliest_ride;
    if ($reset || !isset($earliest_ride)) {
        $earliest_ride = get_next_user_schedulable_link_time();
        $earliest = mktime($earliest_ride['Hour'], 0, 0,
                           $earliest_ride['Month'], $earliest_ride['Day'], $earliest_ride['Year']);
    }

    $timestamp = mktime($hour, $minute, 0, $month, $day, $year);
    
    if ($timestamp < $earliest) {
        return FALSE;
    }

    // TODO:  The logic in this is not complete, most likely.
    return TRUE;
}

?>
