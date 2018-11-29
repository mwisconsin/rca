<?php
	include_once 'include/user.php';
	redirect_if_not_logged_in();

    require_once('include/driver_link.php');
    require_once('include/link.php');
    require_once('include/driver.php');
    require_once('include/date_time.php');
    require_once('include/completed_link_transitions.php');
    require_once('include/deadhead.php');
	require_once 'include/franchise.php';
//	error_reporting(E_ALL);
	$franchise = get_current_user_franchise();
    if (!current_user_has_role(1, 'FullAdmin') && !current_user_has_role($franchise, 'Franchisee')) {
        header('Location:  home.php');
		die();
    }
	
	
	//exit;
	/*if ($_POST['driver_assign']) {
	  $link_ids = array_keys($_POST['driver_assign']);
	  foreach ($link_ids as $link_id) {
	    if ($_POST['driver_assign'][$link_id]) {
		  //echo 'lll';
		  $assigned_driver = $_POST['driver_assign'][$link_id];
		  
		   // Assign driver
			if (set_driver_for_link($link_id, $assigned_driver)) {
				$assign_success = TRUE;
			}
	
		}
	  }
	}*/
//exit;
	
	
    $link_statuses = array('UNKNOWN', 'COMPLETE', 'DRIVERNOSHOW', 'CANCELEDLATE', 'CANCELEDEARLY', 'NOTSCHEDULED', 'WEATHERCANCEL','DESTINATIONCANCEL','HOSPITALCANCEL');
    //echo '<pre>';
    //print_r($_POST);
    //echo '</pre>';
    
    if(isset($_POST['UpdateCurrent']) && $_POST['UpdateCurrent'] != ''){
        $_POST['UpdateCurr'] = $_POST['Status'];
    }
    
    if(isset($_POST['AllDriverConfirm']) && $_POST['AllDriverConfirm'] != ''){
        $keys = array_keys($_POST['link']);
        
        foreach($keys as $key)
            set_driver_confirm($key, TRUE );
        $update_text = "<div class=\"reminder\">All links have been released to drivers.</div>";
        
    }
    
    if (isset($_POST['UpdateCurr']) && $_POST['UpdateCurr'] != '') {
        if (is_array($_POST['UpdateCurr'])) {
            $update_keys = array_keys($_POST['UpdateCurr']);
        }
        if (is_array($update_keys)) {
            foreach( $update_keys as $upd_curr_id ) {
                set_driver_confirm($upd_curr_id, isset($_POST['DriverConfirmed'][$upd_curr_id]) );
                set_active_link_status($upd_curr_id, $_POST['Status'][$upd_curr_id]);
								if ($_POST['note'][$upd_curr_id]) {
								  set_link_note($upd_curr_id, $_POST['note'][$upd_curr_id]);
								}
                set_current_destination_time_confirm($upd_curr_id, isset($_POST['DestinationConfirm'][$upd_curr_id]));
                set_current_arrival_time_confirm($upd_curr_id, isset($_POST['ArrivalConfirm'][$upd_curr_id]));
                if ($_POST['Status'][$upd_curr_id] == 'CANCELEDEARLY' || $_POST['Status'][$upd_curr_id] == 'DESTINATIONCANCEL' || $_POST['Status'][$upd_curr_id] == 'HOSPITALCANCEL' || $_POST['Status'][$upd_curr_id] == 'WEATHERCANCEL') {
                    move_link_to_history($upd_curr_id);
                }
                // $update_text .= "Set status for $upd_curr_id to {$_POST['Status'][$upd_curr_id]}<br />";
				
								if (isset($_POST['driver_assign'][$upd_curr_id])) {
								  //echo 'lll';
								  $assigned_driver = $_POST['driver_assign'][$upd_curr_id];
								  
								   // Assign driver
									if (set_driver_for_link($upd_curr_id, $assigned_driver)) {
										$assign_success = TRUE;
									}
			
								}
								if (isset($_POST['remove_driver'][$upd_curr_id])) {
									remove_driver_for_link($upd_curr_id);
								}
            }
        }
    }
    if(isset($_POST['UpdateHist&TransitionMiles']) && $_POST['UpdateHist&TransitionMiles'] != ''){
    	$_POST['CreateTransitionMiles'] = 'Create Transition Miles';
    	$_POST['UpdateHist'] = 'Update All History';
    }
    

    if (isset($_POST['UpdateHist']) && $_POST['UpdateHist'] != '') {
        if (is_array($_POST['UpdateHist'])) {
            $update_keys = array_keys($_POST['UpdateHist']);
        } elseif ($_POST['UpdateHist'] == 'Update All History' AND is_array($_POST['Status']) ) { 
            $update_keys = array_keys($_POST['Status']);
        } else {
        	$update_keys = array();
        }

        if (is_array($update_keys)) {
            foreach( $update_keys as $upd_hist_id ) {
                if ($_POST['UpdDriverID'][$upd_hist_id] == 'NONESELECTED') {
                    // If there is no driver, status must be NOTSCHEDULED or CANCELEDEARLY
                    // Otherwise this is an error and should not be processed.
                    if ($_POST['Status'][$upd_hist_id] == 'NOTSCHEDULED' ||
                        $_POST['Status'][$upd_hist_id] == 'CANCELEDEARLY') {

                        update_completed_link_status_time_driver ( $upd_hist_id, 
                                                                   $_POST['Status'][$upd_hist_id], 
                                                                   $_POST['hour'][$upd_hist_id],
                                                                   $_POST['minute'][$upd_hist_id],
                                                                   $_POST['AM_PM'][$upd_hist_id],
                                                                   $_POST['UpdDriverID'][$upd_hist_id]);
                        set_completed_link_driver_id($upd_hist_id, NULL);
                    } else {
                        $update_text .= "Could not update $upd_hist_id - Status requires driver selected.<br />";
                    }
                    continue;
                }

                if (in_array($_POST['Status'][$upd_hist_id], $link_statuses) &&
                    isset($_POST['hour'][$upd_hist_id], $_POST['minute'][$upd_hist_id],
                          $_POST['AM_PM'][$upd_hist_id], $_POST['UpdDriverID'][$upd_hist_id])) {

                    update_completed_link_status_time_driver ( $upd_hist_id, 
                                                               $_POST['Status'][$upd_hist_id], 
                                                               $_POST['hour'][$upd_hist_id],
                                                               $_POST['minute'][$upd_hist_id],
                                                               $_POST['AM_PM'][$upd_hist_id],
                                                               $_POST['UpdDriverID'][$upd_hist_id]);


                }

            }
            $history_confirmation .= "<div class=\"reminder\">You have successfully updated the ride history</div>";
        }

        unset($update_keys);
        
    }
    if (isset($_POST['DeleteLink']) && is_array($_POST['DeleteLink'])) {
        //print_r($_POST['DeleteLink']);
        foreach( array_keys($_POST['DeleteLink']) as $link_id ) {
            move_link_to_history($link_id);
            set_completed_link_status($link_id,'CANCELEDEARLY');
        }
    }
	if (isset($_POST['RemoveDriver']) && is_array($_POST['RemoveDriver'])) {
        foreach( array_keys($_POST['RemoveDriver']) as $link_id ) {
            remove_driver_for_link( $link_id );
        }
    }
    
    if(isset($_POST['IndexPath'])) {
    	foreach($_POST['IndexPath'] as $id => $path) {
    		$sql = "update link set IndexPath = '$path' where LinkID = $id";
    		mysql_query($sql);
    	}
    }


    $franchise_id = $franchise;  // TODO:  Franchise of admin user, or input by full admin

		if(isset($_POST["selected_date"]) || isset($_GET["selected_date"])) {
			$selected_date = isset($_POST["selected_date"]) ? $_POST["selected_date"] : $_GET["selected_date"];
			$selected_year = date('Y',strtotime( $selected_date ));
			$selected_month = date('m',strtotime( $selected_date ));
			$selected_day = date('d',strtotime( $selected_date ));
		} else {
			$selected_date = isset($_POST['Year']) ? "$_POST[Month]/$_POST[Day]/$_POST[Year]" :
						( isset($_GET['Year']) ? "$_GET[Month]/$_GET[Day]/$_GET[Year]" : date('m/d/Y') );
	    $selected_year = isset($_POST['Year']) ? $_POST['Year'] : 
	                        (($_GET['Year']) ? $_GET['Year'] : date('Y'));
	    $selected_month = isset($_POST['Month']) ? $_POST['Month'] : 
	                        (($_GET['Month']) ? $_GET['Month'] : date('m'));
	    $selected_day = isset($_POST['Day']) ? $_POST['Day'] : 
	                        (($_GET['Day']) ? $_GET['Day'] : date('d'));
		}
		
    if(isset($_POST["return_selected_date"]) && $_POST["return_selected_date"] != '') {
    	$selected_date = $_POST["return_selected_date"];
			$selected_year = date('Y',strtotime( $selected_date ));
			$selected_month = date('m',strtotime( $selected_date ));
			$selected_day = date('d',strtotime( $selected_date ));    	
    }

    if (isset($_POST['CreateTransitionMiles']) && $_POST['CreateTransitionMiles'] == 'Create Transition Miles') {
    		if($_POST['transition_date_from'] != '') {
	        $transition_success = create_transition_miles($franchise_id,
	                                                      date('Y-m-d',strtotime($_POST['transition_date_from'])),
	                                                      date('Y-m-d',strtotime($_POST['transition_date_to']))
	                                                      );    			
    		} else
        	$transition_success = create_transition_miles($franchise_id,
                                                      "$selected_year-$selected_month-$selected_day");
        if($transition_success)
        	$transition_alloc_confirmation .= "<div class=\"reminder\">You have successfully created transtion miles</div>";
       	else if($transition_success === NULL)
       		$transition_alloc_confirmation .= "<div class=\"reminder\">No transitions found. All up to date.</div>";
       	else
       		$transition_alloc_confirmation .= "<div class=\"reminder\">Transitions failed to create.</div>";
    }

    $links = get_schedule_driver_links_on_date($franchise_id, 
                                               "$selected_year-$selected_month-$selected_day");
    if (count($links) > 0) {
        $links = estimate_link_groups($links);
    }

    if (mktime(0, 0, 0, $selected_month, $selected_day, $selected_year) < time()) {
        $past_links = get_history_links_on_date($franchise_id,
                                                "$selected_year-$selected_month-$selected_day");
    }
	
	
	//IF(DesiredArrivalTime IS NOT NULL, FROM_UNIXTIME(UNIX_TIMESTAMP(DesiredArrivalTime) - ((EstimatedMinutes + PrePadding + PostPadding) * 60)),DesiredDepartureTime) as DesiredDepartureTime
	
    global $ADDITIONAL_RC_JAVASCRIPT;
    if (!is_array($ADDITIONAL_RC_JAVASCRIPT)) {
        $ADDITIONAL_RC_JAVASCRIPT = array();
    }
    $ADDITIONAL_RC_JAVASCRIPT[] = 'datepicker.js';
    include_once('include/header.php');
    echo $history_confirmation;
    echo $transition_alloc_confirmation;
    echo $update_text;
    

?>


<h2 style="text-align:center;">Master Manifest</h2>
<h3><?php 
    echo count($links) . ' Current Link' . ((count($links) == 1) ? '' : 's') . ' and ' .
         count($past_links) . ' Past Link' . ((count($past_links) == 1) ? '' : 's') . ' ' .
         'found on ' .  
         date('D, F j Y', mktime(0, 0, 0, $selected_month, $selected_day, $selected_year)); ?></h3>

<form id="theForm" method="POST">

<div class="noprint">
<table id="travel_date" >
    <tr><td nowrap="nowrap">Select Date:  </td>
    		<td><input size=10 name=selected_date value="<?php echo $selected_date; ?>" class=jq_datepicker></td>
        <td><input type="submit" name="Change Date" value="Change Date" /></td>
    </tr>
</table>
<p>
<?php
    $one_day = 24 * 60 * 60;
    //$now = time(date(
    $now = mktime(0, 0, 0, $selected_month, $selected_day, $selected_year);
    $tomorrow = $now + $one_day;
    $yesterday = $now - $one_day;

    $tomorrow_params = 'Year=' . date('Y', $tomorrow) . '&Month=' . date('m', $tomorrow) .
                       '&Day=' . date('d', $tomorrow);
    $yesterday_params = 'Year=' . date('Y', $yesterday) . '&Month=' . date('m', $yesterday) .
                       '&Day=' . date('d', $yesterday);
?>
<a href="admin_driver_links.php?<?php echo $yesterday_params ?>">Previous Day</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="admin_driver_links.php">Today</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="admin_driver_links.php?<?php echo $tomorrow_params ?>">Next Day</a>
</p>
<input type="submit" name="UpdateCurrent" value="Update All Current" /> <input type="submit" name="AllDriverConfirm" value="Release All To Drivers"><br><br>
</div>
<style>
.admin_driver_links_table tr:nth-child(odd) {
	background-color: #CCC;
}	
</style>
<table border="1" class="admin_driver_links_table">
    <tr><th>&nbsp;</th><?php echo get_admin_driver_links_link_table_headings(); ?></tr>

<?php 
    unset ($link);

    if (count($links) == 0) {
        echo '    <tr><td colspan="10">None Found</td></tr>';
    }
    foreach ($links as $link) { ?>

    <tr><?php 
        echo get_link_as_admin_driver_links_link_table_row($link, $selected_date);
        //echo get_arrival_driver_columns($link);
    ?></tr>
<?php } ?>

</table><br>
<input type="submit" name="UpdateCurrent" value="Update All Current" /> <input type="submit" name="AllDriverConfirm" value="Release All To Drivers"><br>
<?php echo get_rider_preference_key();
if (isset($past_links)) { ?>
<h2 style="text-align:center;">Link History</h2>
<input type="button" value="Update All History"  onClick="$('UH').click();"/>  &nbsp;
<input type="button" value="Create Transition Miles"  onClick="$('CTM').click();" />
<input type="button" value="Update All History And Create Transition Miles" onClick="$('UHCTM').click();" /><br><br>
<table border="1" class="admin_driver_links_table">
    <tr><?php echo get_admin_driver_links_link_table_headings(FALSE, FALSE, false);
              echo get_status_arrival_driver_headings(); ?>
    </tr>
<?php 
// TODO:  Past links need to have their driver-set link changed
// (different semantics for changing assigned driver, different table,
// different ledger impact...)
    $driver_list = get_admin_work_as_driver_list($franchise); 
    unset ($link);

    if (count($past_links) == 0) {
        echo '    <tr><td colspan="10">None Found</td></tr>';
    }
    foreach ($past_links as $link) { ?>

    <tr>
        <?php 
        unset($link_statuses[0]);
        $link['AssignedDriverUserID'] = $link['DriverUserID'];
        $table_row = get_link_as_admin_driver_links_link_table_row($link, $selected_date, FALSE, FALSE, FALSE, FALSE);  // history can't be deleted, I say

        // Trim the Status column - last cell
        $last = strrpos( $table_row, '<td>' );
        $trimmed_row = substr($table_row, 0, $last);
        echo $trimmed_row;

        echo '<td>' . get_status_column($link) . '</td>';
        echo get_arrival_driver_columns($link);
    ?>
    </tr>
<?php } ?>

</table>
<br />
<input type=hidden id=return_selected_date name="return_selected_date" value="" />
<input type=hidden id=transition_date_from name="transition_date_from" value="" />
<input type=hidden id=transition_date_to name="transition_date_to" value="" />
<input id="UH" type="submit" name="UpdateHist" value="Update All History" />  &nbsp;
<input id="CTM" type="submit" name="CreateTransitionMiles" value="Create Transition Miles" onClick="if(jQuery('#transition_date_from').val() == '') { popTransitionQuery(); return false; }" />
<input id="UHCTM" type="submit" name="UpdateHist&TransitionMiles" value="Update All History And Create Transition Miles" onClick="if(jQuery('#return_selected_date').val() == '') { popQuery(); return false; }" />
</form>

<?php } ?>
<script type="text/javascript">
	function pop_link_notes(object){
		window.open('view_link_note.php?id=' + String(object.id).replace('noteimage_',''), "Link Notes", "height=200, width=400 resizable=false");
	}
	function refresh_window(){
		$('theForm').submit();
	}
	function activateNote(noteid) {
		jQuery('#noteimage_'+noteid).removeClass('LinkNoteBlank').addClass('LinkNoteFilled');
		jQuery('#noteimage_'+noteid).parent('td').find('.noteind').html('*note');
	}
	function deactivateNote(noteid) {
		jQuery('#noteimage_'+noteid).removeClass('LinkNoteFilled').addClass('LinkNoteBlank');
		jQuery('#noteimage_'+noteid).parent('td').find('.noteind').html('');
	}
	
	function popQuery() {
		$d = jQuery('<div><b id=popQueryMess>Please Choose a Return Date:</b><BR><div id=popQueryCal></div></div>').dialog({
			modal: true,
			title: 'Choose Return Date',
			width: '560px',
			buttons: [
				{
					text: 'Cancel',
					click: function() {
						$d.dialog('close');
					}
				},
				{
					text: 'Ok',
					click: function() {
						if(jQuery('#popQueryCal').datepicker( "getDate" ) == null) {
							jQuery('#popQueryMess').css('color','red');
							return false;
						}
						jQuery('input[name="return_selected_date"]').val( 
							jQuery.datepicker.formatDate( 'mm/dd/yy',	jQuery('#popQueryCal').datepicker( "getDate" ) )
						);
						$d.dialog('close');
						jQuery('#UHCTM').trigger('click');
					}
				}
			],
			close: function() {
				$d.remove();
			},
			open: function() {
				jQuery('#popQueryCal').datepicker({
	    		showOtherMonths: true,
	      	selectOtherMonths: true,
	      	numberOfMonths: 2,
	      	showCurrentAtPos: 0,
	      	dateFormat: 'mm/dd/yy',
	      	defaultDate: '<?php echo $selected_date; ?>'
				});
				jQuery('#popQueryCal').datepicker('setDate', new Date( '<?php echo $selected_date; ?>' ));
			},
			onSelect: function() {
				jQuery('#popQueryMess').css('color','black');
			}
		});
	}
	
	function popTransitionQuery() {
		$d = jQuery('<div><b id=popQueryMess style="text-align:center;">Process for the following range:</b><BR><input type=text id=popTransitionQueryCalFrom style="width:100px;"> <input type=text id=popTransitionQueryCalTo style="width:100px;"></div>').dialog({
			modal: true,
			title: 'Process Date Range',
			width: '300px',
			buttons: [
				{
					text: 'Cancel',
					click: function() {
						$d.dialog('close');
					}
				},
				{
					text: 'Ok',
					click: function() {
						if(jQuery('#popTransitionQueryCalFrom').datepicker( "getDate" ) == null) {
							jQuery('#popQueryMess').css('color','red');
							return false;
						}
						jQuery('input[name="transition_date_from"]').val( 
							jQuery.datepicker.formatDate( 'mm/dd/yy',	jQuery('#popTransitionQueryCalFrom').datepicker( "getDate" ) )
						);
						jQuery('input[name="transition_date_to"]').val( 
							jQuery.datepicker.formatDate( 'mm/dd/yy',	jQuery('#popTransitionQueryCalTo').datepicker( "getDate" ) )
						);
						$d.dialog('close');
						jQuery('#CTM').trigger('click');
					}
				}
			],
			close: function() {
				$d.remove();
			},
			open: function() {
				jQuery('#popTransitionQueryCalFrom').datepicker({
					language: 'en',
	      	selectOtherMonths: true,
	      	numberOfMonths: 1,
	      	showCurrentAtPos: 0,
	      	dateFormat: 'mm/dd/yy',
	      	startDate: new Date('<?php echo $selected_date; ?>')
				});
				//jQuery('#popTransitionQueryCalFrom').datepicker('setDate', new Date( '<?php echo $selected_date; ?>' ));
				jQuery('#popTransitionQueryCalTo').datepicker({
					language: 'en',
	    		showOtherMonths: true,
	      	selectOtherMonths: true,
	      	numberOfMonths: 1,
	      	showCurrentAtPos: 0,
	      	dateFormat: 'mm/dd/yy',
	      	startDate: new Date('<?php echo $selected_date; ?>')
				});
				//jQuery('#popTransitionQueryCalTo').datepicker('setDate', new Date( '<?php echo $selected_date; ?>' ));
			},
			onSelect: function() {
				jQuery('#popQueryMess').css('color','black');
			}
		});
	}
</script>
<?php
	include_once 'include/footer.php';



function estimate_link_groups($links) {
    $INIT_MIN_TIME = 999999999999;
    if (count($links) > 0) {

        $max_key = ord('A');
        $groups = array( $max_key => array('ArrivalTime' => array('time_t' => 0),
                                           'Destination' => -1));

        // Links are sorted by departure time
        foreach ($links as $key => $link_entry) {
            if ($link_entry['LinkStatus'] == 'CANCELEDEARLY') {
                continue;
            }


            // Search groups to find the group having the smallest (arrival time + travel time) 
            // that is less than the link's departure time.
            $min_time_group = -1;
            $min_time = $INIT_MIN_TIME;
            $link_departure_time = get_link_departure_time($link_entry);
            foreach ($groups as $group_index => $g) {
                $travel_time = ($g['ArrivalTime']['time_t'] == 0) ? 0 :
                                    get_estimated_travel_minutes($g['Destination'], $link_entry['FromDestinationID']) * 60;

                $predicted_arrival_time = $travel_time + $g['ArrivalTime']['time_t'];

                // Allow a maximum of 2.5 hours between links (avoid split shifts)
                if ($predicted_arrival_time != 0 &&
                    ($predicted_arrival_time + (2.5 * 60 * 60)) < $link_departure_time['time_t']) {
                    continue;
                }

                if ($predicted_arrival_time < $min_time &&
                    $predicted_arrival_time < $link_departure_time['time_t']) {
                    $min_time = $travel_time + $g['ArrivalTime']['time_t'];
                    $min_time_group = $group_index;
                }
            }

            // If a group was not found, we need a new group
            if ($min_time_group == -1) {
                $groups[$max_key + 1] = array('ArrivalTime' => array('time_t' => 0),
                                              'Destination' => $link_entry['ToDestinationID']);
                $min_time_group = $max_key + 1;
                $max_key = $min_time_group;
            }


            // Set the link group's arrival time and destination
            $groups[$min_time_group]['ArrivalTime'] = get_link_arrival_time($link_entry);
            $groups[$min_time_group]['Destination'] = $link_entry['ToDestinationID'];

            // Set the link's group
            $links[$key]['EstimatedGroup'] = chr($min_time_group);
        }
    }

    return $links;
}

function get_estimated_travel_minutes($from_dest, $to_dest) {
    return (($from_dest == $to_dest) ? 0 : 9.9);
}

function my_get_time_selector($link_id, $hour = FALSE, $minute = FALSE, $ampm = FALSE) {
    $selected_hour = ($hour == FALSE) ? 12 : $hour;
    $selected_min = ($hour == FALSE) ? 0 : $minute;
    $am_selected = ($ampm == 'AM') ? 'selected="selected" ' : '';
    $pm_selected = ($ampm == 'PM') ? 'selected="selected" ' : '';

    $ret = "<select name=\"hour[$link_id]\">";
    for ($hr = 1; $hr <= 12; $hr++) {
        $ret .= "<option value=\"$hr\"" . 
                (($hr == $selected_hour) ? ' selected="selected"' : '') . ">$hr</option>";
    }
    $ret .= "</select>:<select name=\"minute[$link_id]\">";
    for ($min = 0; $min < 60; $min += 5) {
        $ret .= "<option value=\"$min\" " . 
                (($min == $selected_min) ? ' selected="selected"' : '') . '>' . sprintf('%02d', $min) . '</option>';
    }
    $ret .= '</select><select name="AM_PM[' . "$link_id" . 
            ']"><option value="AM" ' .  $am_selected . '>AM</option><option value="PM" ' .
            $pm_selected . '>PM</option></select>';

    return $ret;
}

function get_admin_driver_links_link_table_headings($include_delete=TRUE, $include_depature_confirm = TRUE, $include_driver_confirm = TRUE) {
    $prefs_spacer = str_repeat('&nbsp;', 13);
    if ($include_delete) { $delete.= "\n<th class=\"noprint\">Action</th>"; }
    if($include_depature_confirm) { $dc = "<th>Conf.</th>";}
    //if($include_driver_confirm) $drc = "<th>dr. conf.</th>";
$ret = <<<HEADINGS
<th class="noprint">Ride ID</th>
<th>Pickup Time</th>
<th>Arrival Time</th>
<th>#</th>
<th>Rider</th>
<th>Pickup From</th>
<th>Arrive At</th>{$dc}
<th>Note</th>
<th>Prefs</th>
<th>Driver</th>
<th>Status</th>{$drc}
{$delete}
HEADINGS;
// TODO: make Reported Arrival visible at noon the previous day


    return $ret;
}

function get_status_arrival_driver_headings() {
$ret = <<<HEADINGS
<th>Reported Arrival</th>
<th>Updated Driver</th>
<th>Update</th>
HEADINGS;

    return $ret;
}

function get_link_as_admin_driver_links_link_table_row( $link_row, $selected_date_in, $include_delete = TRUE, $include_group = TRUE, $include_depature_confirm = TRUE, $include_driver_confirm = TRUE ) {
    global $franchise;
    // Modified from link.php get_link_as_admin_link_table_row
    $arrival_times = get_link_arrival_time($link_row);
    $departure_times = get_link_departure_time($link_row);
    $travel_date = get_link_travel_date($link_row);
		
  	$sql = "select FirstName from users 
  		natural join rider_destination
  		natural join person_name where DestinationID = $link_row[FromDestinationID]";

  	$rs = mysql_fetch_array(mysql_query($sql));
    $from_address = get_link_destination_table_cell_contents('F_', $link_row, TRUE, $rs["FirstName"]);
  	$sql = "select FirstName from users 
  		natural join rider_destination
  		natural join person_name where DestinationID = $link_row[ToDestinationID]";

  	$rs = mysql_fetch_array(mysql_query($sql));
    $to_address = get_link_destination_table_cell_contents('T_', $link_row, TRUE, $rs["FirstName"]);

    if ($lf_rider_info = get_large_facility_rider_info_for_link($link_row['LinkID'])) {
        $rider_cell = get_lf_rider_person_info_string($lf_rider_info);
    } else {
        $rider_cell = get_rider_person_info_string($link_row['RiderUserID'], TRUE);
        foreach(get_additional_riders($link_row['LinkID']) as $rider)
                   $rider_cell .= get_rider_person_info_string($rider['UserID'], TRUE);
    }
    $sql = "select * from todays_links where LinkID = $link_row[LinkID]";
    $tlr = mysql_query($sql);
    $tlrs = array();
    if(mysql_num_rows($tlr)) {
    	$tlrs = mysql_fetch_array($tlr);
    	$rider_cell .= "<br><input onClick=\"updateTRFT("
  			.$link_row["LinkID"].",this);\" type=checkbox name=TextRiderForThisLink_".$link_row['LinkID']." ".($tlrs["TextRiderForThisLink"]==1?" checked":"")."> RSMS";
    }

    $prefs_string = rider_preferences_to_display_string(get_rider_prefs($link_row['RiderUserID']));
    if (is_null($link_row['AssignedDriverUserID'])) {
        $driver_cell = '<select name="driver_assign['.$link_row['LinkID'].']" onchange=""><option value="">None Assigned</option>'.getDriverOptions($franchise).'</select>'; //'None Assigned';
    } else {
        $driver = get_driver_person_info($link_row['AssignedDriverUserID']);
        if ($driver === FALSE) {
            $driver_cell = "Unknown Driver ID {$link_row['AssignedDriverUserID']}";
        } else {
            $driver_name =  "{$driver['Title']} {$driver['FirstName']} {$driver['MiddleInitial']}. " .
                           "{$driver['LastName']} {$driver['Suffix']}"; 
            $driver_cell = "<a id=\"{$link_row['AssignedDriverUserID']}\" class=\"User_Redirect\" href=\"account.php\">$driver_name</a> <br />" .
                           "<a id=\"{$link_row['AssignedDriverUserID']}\" class=\"User_Redirect\" href=\"manifest.php?date={$travel_date}\">(manifest)</a>"; 

            if ($driver_mobiles = get_user_phone_numbers($driver['UserID'])) {
                $driver_cell .= '<br /><span>'; 
                foreach ($driver_mobiles as $mobile) {
                	$driver_cell .= ($mobile['IsPrimary'] == 'Yes' ? '<b>' : ' ') . "{$mobile['PhoneNumber']} ({$mobile['PhoneType'][0]})" . 
                		($mobile['IsPrimary'] == 'Yes' ? '*</b>' : ' ') . "<br />";
                	$driver_cell .= $mobile["phonedescription"] == "" ? "" : "<span class=manifest_phonedescription>$mobile[phonedescription]</span><br>";
                }

                $driver_cell .= '</span>';
            }
            if($driver["VehicleHeight"] != "") {
            	$driver_cell .= "<span>";
            	$driver_cell .= substr($driver["VehicleHeight"],0,1)." $driver[VehicleColor] $driver[VehicleDescription] </span><br>";
            }
            
            $driver_cell .= '<input type=checkbox name="remove_driver['.$link_row['LinkID'].']"> Drop Driver '.($link_row['IndexPath'] != '' ? '('.$link_row['IndexPath'].')' : '');
        }
    }
    if(mysql_num_rows($tlr)) {
    	$driver_cell .= "<br><input onClick=\"updateTDFT("
  			.$link_row["LinkID"].",this);\" type=checkbox name=TextDriverForThisLink_".$link_row['LinkID']." ".($tlrs["TextDriverForThisLink"]==1?" checked":"")."> DSMS";
    }    
    if($include_depature_confirm){
    	$destination_confirm = "<td nowrap align=center>";
    $destination_confirm .= "Orig.<br><input type=\"checkbox\" name=\"ArrivalConfirm[{$link_row['LinkID']}]\" " . ($link_row['ArrivalTimeConfirmed'] == 'Y' ? ' CHECKED' : '') . ">";
		$destination_confirm .= "<br>Dest.<br><input type=\"checkbox\" name=\"DestinationConfirm[{$link_row['LinkID']}]\" " . ($link_row['DepartureTimeConfimed'] == 'Y' ? ' CHECKED' : '') . ">";
		if($include_driver_confirm){
    	//$driver_confirm = "<td>";
			$destination_confirm .= "<br>Dr.<br><input type=\"checkbox\" name=\"DriverConfirmed[{$link_row['LinkID']}]\" " . ($link_row['DriverConfirmed'] == 'Yes' ? ' CHECKED' : '') . ">";
		//$driver_confirm .= "</td>";
		}
		$destination_confirm .= "</td>";
	}
	/*
	if($include_driver_confirm){
    	$driver_confirm = "<td>";
		$driver_confirm .= "<input type=\"checkbox\" name=\"DriverConfirmed[{$link_row['LinkID']}]\" " . ($link_row['DriverConfirmed'] == 'Yes' ? ' CHECKED' : '') . ">";
		$driver_confirm .= "</td>";
	}
	*/
    $status_cell = get_status_column($link_row);

	$note_cell = "<td><img id=\"noteimage_{$link_row['LinkID']}\" src=\"images/trans.gif\" onclick=\"pop_link_notes(this)\" class='" . 
                ($link_row['LinkNote'] == "" || $link_row['LinkNote'] == NULL ? 'LinkNoteBlank' : 'LinkNoteFilled') . "' alt=\"df\" /> " .
    		    ($link_row['LinkNote'] == "" || $link_row['LinkNote'] == NULL ? '<span class=noteind></span>' : '<span class=noteind>*note</span>') 
    		    .($link_row['LinkFlexFlag'] != 0 ? '<br><b>Time is Flexible</b>' : '')
    		    . "  </td>";
		
    if ($include_delete) {
        $delete .= "\n" . '<td class="noprint"><input type="submit" name="UpdateCurr[' . $link_row['LinkID'].']" value="Update" />';
        if($link_row['CustomTransitionType'] != null)
       		$delete .= '<a id="' . $link_row['RiderUserID'] . '" class="User_Redirect" href="plan_ride.php?edit=' . $link_row['LinkID'] . '"><input type="button"  value="Edit Ride" /></a>';
				$delete .= '<input type="submit" name="RemoveDriver[' . $link_row['LinkID'] . ']" value="Remove Driver" />';
				$delete .= '<br><input type=text size=5 placeholder="Index" name="IndexPath[' . $link_row['LinkID'] . ']" value="' . $link_row['IndexPath'] .'" />';
				$delete .= '</td>';
    }

    if ($include_group) {
        $group_row = "<td>{$link_row['EstimatedGroup']}</td>";
    }
	if($link_row['CustomTransitionID'] !== NULL){
		$status_cell = $link_row['LinkStatus'];
		$delete = '<td><input type="submit" name="UpdateCurr[' . $link_row['LinkID'] . 
                ']" value="Update" /><br><input type="button" value="View Custom Transition" onclick="document.location = \'custom_transition.php?id=' . $link_row['CustomTransitionID'] . '&from=adl&rdate=' . date('Y-m-d', strtotime( $selected_date_in ) ) . '\'" /></td>';	
	}
    
    $ret = <<<ROW
{$group_row}
<td class="noprint"><a href="admin_schedule_link.php?LinkID={$link_row['LinkID']}">{$link_row['LinkID']}</a></td>
<td nowrap="nowrap" class="pickup_time_cell" linkid={$link_row['LinkID']}>{$departure_times['string']}</td>
<td nowrap="nowrap" class="dropoff_time_cell" linkid={$link_row['LinkID']}>{$arrival_times['string']}</td>
<td nowrap="nowrap"><a href="myrides.php?uid={$link_row['RiderUserID']}">{$link_row['NumberOfRiders']}</a></td>
<td nowrap="nowrap">{$rider_cell}</td>
<td nowrap="nowrap">$from_address</td>
<td nowrap="nowrap">$to_address</td>
$destination_confirm
$note_cell
<td>{$prefs_string}</td>
<td nowrap="nowrap">{$driver_cell}</td>
<td>{$status_cell}<input type="hidden" value="" name="link[{$link_row['LinkID']}]"></td>{$driver_confirm} {$delete}
ROW;


    return $ret;
}

function get_status_column($link_row) {
    global $link_statuses;
    $status_cell = "{$link_row['LinkStatus']}<select name=\"Status[{$link_row['LinkID']}]\" onchange=\"toggleNoteField(this, ".$link_row['LinkID'].");\">";
    foreach ($link_statuses as $status) {
    	$selected = ($status == $link_row['LinkStatus']) ? ' selected="selected"' : '';
     	$status_cell .= "<option value=\"$status\"$selected>$status</option>";
   	}
   	$status_cell .= '</select>';
		if (current_user_has_role(1, 'FullAdmin') || current_user_has_role($link_row['FranchiseID'], 'Franchisee')) {
      if($link_row['Last_Changed_By'] != 0) {
      	$u = get_user_account($link_row['Last_Changed_By']);
      	$status_cell .= '<br>Last Changed By: '.$u["FirstName"]." ".$u["LastName"]."<BR>";
      	$status_cell .= "on ".date('m/d/Y H:i A',strtotime($link_row["Last_Changed_Date"]));
      }			
		}
	$status_cell .= '<div style="display:none;" id="note'.$link_row['LinkID'].'">';
	$note_row = get_link_note($link_row['LinkID']);
	$note = '';
	if ($note_row) {
	  $note = $note_row['LinkNote'];
	}
	$status_cell .= 'Note:<br /><textarea disabled="true" name="note['.$link_row['LinkID'].']"  id="note_field'.$link_row['LinkID'].'">'.$note.'</textarea>';
	$status_cell .= '</div>';
	
    return $status_cell;
}

function get_arrival_driver_columns($link) {
    global $driver_list;
?>
        <td nowrap="nowrap"><?php 
            $reported_arrival_t = strtotime($link['ReportedArrivalTime']);
            if ($reported_arrival_t) {
                echo date('g:i A', $reported_arrival_t);
            } else { 
                echo 'Not set';
                $reported_arrival_t = strtotime($link['DesiredArrivalTime']) - 1;
                $reported_arrival_t = $reported_arrival_t - ($reported_arrival_t % 300);
                /* Round to the next lowest five-minute increment (subtract 0-5 minutes) */

            }
            echo '<br />';
            echo my_get_time_selector($link['LinkID'], date('g', $reported_arrival_t), 
                                                    date('i', $reported_arrival_t),
                                                    date('A', $reported_arrival_t));
        ?></td>
        <td><?php 
            if (count($driver_list) > 0) {
                echo "<select id=\"UpdDriverID[{$link['LinkID']}]\" name=\"UpdDriverID[{$link['LinkID']}]\">";
                echo '<option value="NONESELECTED">None Selected</option>';
                foreach ($driver_list as $driver_entry) {
                    echo "<option value=\"{$driver_entry['UserID']}\" " .
                         (($link['DriverUserID'] == $driver_entry['UserID']) ?
                                    'selected="selected" ' : '' ) .
                         ">{$driver_entry['LastName']}, {$driver_entry['FirstName']} {$driver_entry['MiddleInitial']} ({$driver_entry['UserID']})</option>";
                }
                echo '</select>';
            } else { 
                echo "...an error occurred.";
            } 
        
        ?></td>
        <td><input type="submit" name="UpdateHist[<?php echo $link['LinkID']; ?>]" value="Update" /></td>
<?php 
}

?>

<script language="javascript">
function toggleNoteField(select_box, link_id) {
//alert('here');
  if ((select_box.value == 'COMPLETE') || (select_box.value =='UNKNOWN')) {
    document.getElementById('note_field'+link_id).disabled = true;
    document.getElementById('note'+link_id).style.display = 'none';
  } else {
    document.getElementById('note_field'+link_id).disabled = false;
    document.getElementById('note'+link_id).style.display = 'block';
  }
}
</script>
<style>
.pickup_time_cell, .dropoff_time_cell {
	cursor: pointer;
}	
.alert_time {
	font-weight: bold;
	color: red;
}
</style>
<script>
jQuery(function($) {
	$('.pickup_time_cell, .dropoff_time_cell').on('click',function(ev) {
		var cell = ev.delegateTarget;
		var linkid = $(cell).attr('linkid');
		var pickup_time = $('td.pickup_time_cell[linkid='+linkid+']').html();
		var dropoff_time= $('td.dropoff_time_cell[linkid='+linkid+']').html();
		var pickuphours = '<select class=pickuphours>'
		for(var i = 1; i <= 12; i++)
			pickuphours += '<option value='+i+(parseInt(pickup_time.substr(0,2),10)==i?' selected':'')+'>'+i+'</option>';
		pickuphours += '</select>';
		var dropoffhours = '<select class=dropoffhours>'
		for(var i = 1; i <= 12; i++)
			dropoffhours += '<option value='+i+(parseInt(dropoff_time.substr(0,2),10)==i?' selected':'')+'>'+i+'</option>';
		dropoffhours += '</select>';	
		var pickupminutes = '<select class=pickupminutes>'
		for(var i = 0; i <= 59; i++)
			pickupminutes += '<option value='+i+(parseInt(pickup_time.substr(3,2),10)==i?' selected':'')+'>'+(i<10?'0':'')+i+'</option>';
		pickupminutes += '</select>';		
		var dropoffminutes = '<select class=dropoffminutes>'
		for(var i = 0; i <= 59; i++)
			dropoffminutes += '<option value='+i+(parseInt(dropoff_time.substr(3,2),10)==i?' selected':'')+'>'+(i<10?'0':'')+i+'</option>';
		dropoffminutes += '</select>';	
		var pickupampm = '<select class=pickupampm>'
			+'<option value=AM'+(pickup_time.substr(6)=='AM'?' selected':'')+'>AM</option>'	
			+'<option value=PM'+(pickup_time.substr(6)=='PM'?' selected':'')+'>PM</option>'	
			+'</select>';	
		var dropoffampm = '<select class=dropoffampm>'
			+'<option value=AM'+(dropoff_time.substr(6)=='AM'?' selected':'')+'>AM</option>'	
			+'<option value=PM'+(dropoff_time.substr(6)=='PM'?' selected':'')+'>PM</option>'	
			+'</select>';	
		var pu_date = new Date();
		pu_date.setHours( parseInt(pickup_time.substr(0,2),10)+(parseInt(pickup_time.substr(0,2),10)>=1&&parseInt(pickup_time.substr(0,2),10)<12&&pickup_time.substr(6)=='PM'?12:0) );
		pu_date.setMinutes( pickup_time.substr(3,2) );
		var do_date = new Date();
		do_date.setHours( parseInt(dropoff_time.substr(0,2),10)+(parseInt(dropoff_time.substr(0,2),10)>=1&&parseInt(dropoff_time.substr(0,2),10)<12&&dropoff_time.substr(6)=='PM'?12:0) );
		do_date.setMinutes( dropoff_time.substr(3,2) );	
			
		$d = $('<div>Original Pickup Time: '+pickup_time
			+'<br>Original Dropoff Time: '+dropoff_time
			+'<br>Original Time Difference: <span class=oldtimediff>'+Math.floor(((do_date.getTime() - pu_date.getTime())/1000)/60)+'</span> minutes'
			+'<br><br><br>'
			+'New Pickup Time: '+pickuphours+pickupminutes+pickupampm+'<br>'
			+'New Dropoff Time: '+dropoffhours+dropoffminutes+dropoffampm+'<br>'
			+'New Time Difference: <span class=newtimediff>'+Math.floor(((do_date.getTime() - pu_date.getTime())/1000)/60)+'</span> minutes'
			+'<input type=hidden name=linkid value='+linkid+'>'
			+'</div>').dialog({
			title: "Change Pickup/Dropoff Time",
			width: 360,
			modal: true,
			buttons: [
				{ text: 'Ok',
					click: function() {
						$.post('/xhr/update_link_times.php',{
								linkid:$('input[name="linkid"]').val(),
								minutes:$('.newtimediff').html(),
								dropoff:$('.dropoffhours').val()+':'+($('.dropoffminutes').val()<10?'0':'')+$('.dropoffminutes').val()+' '+$('.dropoffampm').val() 
							}, function(data) {
								window.location.reload();
						});
					}
				},
				{ text: 'Cancel',
					click: function() {
						$d.dialog('close');
						$d.remove();
					}
				}
			]
		});
	});	
	$(document.body).on('change','.pickuphours,.pickupminutes,.pickupampm,.dropoffhours,.dropoffminutes,.dropoffampm',function() {
		var pu_date = new Date();
		var pu_hours = parseInt($('.pickuphours').val(),10);
		if(pu_hours == 12 && $('.pickupampm').val()=='AM') pu_hours = 0;
		else if(pu_hours < 12 && $('.pickupampm').val()=='PM') pu_hours += 12;
		pu_date.setHours( pu_hours );
		pu_date.setMinutes( $('.pickupminutes').val() );
		var do_date = new Date();
		var do_hours = parseInt($('.dropoffhours').val(),10);
		if(do_hours == 12 && $('.dropoffampm').val()=='AM') do_hours = 0;
		else if(do_hours < 12 && $('.dropoffampm').val()=='PM') do_hours += 12;		
		do_date.setHours( do_hours );
		do_date.setMinutes( $('.dropoffminutes').val() );			
		$('.newtimediff').html( Math.floor(((do_date.getTime() - pu_date.getTime())/1000)/60) );
		$('.newtimediff').removeClass('alert_time');
		if( parseInt( $('.newtimediff').html(), 10) < parseInt( $('.oldtimediff').html(), 10) ) $('.newtimediff').addClass('alert_time');
	});	
});	

function updateTRFT( linkid, cb ) {
	jQuery.get('/xhr/updateTRFT.php?linkid='+linkid+'&to='+(cb.checked?1:0),function(data) {
		d = jQuery('<div>Ride Text Preferences Updated</div>').dialog({
			modal: true,
			buttons: [
				{
					text: 'Ok',
					click: function() { d.dialog('close'); }
				}
			],
			close: function() { d.remove(); }	
		});
	});			
}
function updateTDFT(linkid, cb) {
		jQuery.get('/xhr/updateTDFT.php?linkid='+linkid+'&to='+(cb.checked?1:0),function(data) {
			d = jQuery('<div>Ride Text Preferences Updated</div>').dialog({
				modal: true,
				buttons: [
					{
						text: 'Ok',
						click: function() { d.dialog('close'); }
					}
				],
				close: function() { d.remove(); }	
			});
		});
	}
</script>