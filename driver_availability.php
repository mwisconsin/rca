<?php

require_once('include/user.php');
require_once('include/driver.php');
require_once('include/db_driver_availability.php');
require_once('include/date_time.php');

redirect_if_not_logged_in();
require_once 'include/franchise.php';
$franchise = get_current_user_franchise();


$days_of_week = array('Sunday','Monday','Tuesday','Wednesday',
                      'Thursday','Friday','Saturday');
$hours = array('12:00','12:30','1:00','1:30','2:00','2:30','3:00','3:30','4:00',
               '4:30','5:00','5:30','6:00','6:30','7:00','7:30','8:00','8:30',
               '9:00','9:30','10:00','10:30','11:00','11:30');
$meridiem = array('AM','PM');

$has_quit = false;
 
if(get_affected_user_id() !== get_current_user_id()){
	$name = get_displayable_person_name_string(get_user_person_name(get_current_user_id()));
	$admin_changed = "CHANGED BY ADMIN: $name USERID: " . get_current_user_id() . ", ";
}

if (isset($_POST['Submit'], $_POST['TimeSlot'])) 
{
    $is_available = $_POST['availability'] == 'Available';
    $availability = array();

    $has_quit = true;
    
    foreach ($days_of_week as $day) 
    {
        $can_combine = FALSE;
        $start_time = '';
        $end_time = '';

        foreach ($meridiem as $ampm) 
        {
            foreach ($hours as $hr) 
            {
                if (($is_available && $_POST['TimeSlot'][$day][$hr][$ampm] == 'CHECKED') ||
                    (!$is_available && $_POST['TimeSlot'][$day][$hr][$ampm] != 'CHECKED')) 
                {
                	
                	$has_quit = false;
                	
                    // Time slot was marked available

                    if ($can_combine)
                    {
                        // Previous timeslot was also available
                        // Nothing to do... 
                    } else {
                        // Previous timeslot was not available
                        $can_combine = TRUE;
                        $start_time = "$hr $ampm";
                    }
                } elseif ($can_combine) {
                    $availability[] = array('DayOfWeek' => $day,
                                            'StartTime' => $start_time,
                                            'EndTime' => "$hr $ampm");
                    $can_combine = FALSE;
                    $start_time = '';
                    $end_time = '';
                }

            } // End of hour loop
        } // End of am/pm loop
        
        if ($can_combine) {
            $availability[] = array('DayOfWeek' => $day,
                                    'StartTime' => $start_time,  
                                    'EndTime' => '23:59:59');
        }
    } // End of day loop

    $driver_id = get_user_driver_id(get_affected_user_id());
   
#echo "<pre>\n" . var_export($availability, true) . '</pre>';
    if(!$has_quit)   
    {
    	clear_driver_availability($driver_id);
        $success = add_driver_availability($driver_id, $availability);
        $site = site_url();
        $url = urldecode("/driver_availability.php&userid=$driver_id");
        $name = get_user_person_name($driver_id);
        $name  = get_displayable_person_name_string($name);
        $message = <<<MESSAGE
        $admin_changed User: $name UserID: $driver_id has just updated their weekly availability. {$site}xhr/affected_user_redirect.php?redirect=$url \n\n
You must be logged in for this link to be active. \n
The System.
MESSAGE;
	    if($driver_id != NULL && $driver_id != '') {
    	   mail(DEFAULT_CORD_EMAIL,"Weekly Availability Updated", $message, DEFAULT_EMAIL_FROM);
		   $club_emails = get_franchise_email_addresses($franchise_id, 'ra_threshold');
	       if (sizeof($club_emails)>0) {
		      foreach($club_emails as $email) {
			     if (isset($email) && ($email != '')) {
			      //echo $email;
				  mail($email,"Club ".$franchise_id."; Weekly Availability Updated", $message, DEFAULT_EMAIL_FROM);
			     }
		      }
		   }
	    }
    }
}

if (!isset($driver_id)) {
    $driver_id = get_user_driver_id(get_affected_user_id());
}
$current_availability = get_driver_availability($driver_id);

if ($_POST['HoursPerWeek']) {
    set_driver_max_hours_per_week( $driver_id, $_POST['HoursPerWeek'] ); 
}

if($_POST['HoursInput'] && $_POST['DaysInput'] && $_POST['TimeSelection'] ){
    set_driver_max_hours($driver_id, $_POST['HoursInput'], $_POST['TimeSelection'], $_POST['DaysInput'], $_POST['DaySelection']);
    $site = site_url();
    $url = urldecode("/driver_availability.php&userid=$driver_id");
    $name = get_user_person_name($driver_id);
    $name  = get_displayable_person_name_string($name);
    $message = <<<MESSAGE
$admin_changed User: $name UserID: $driver_id has just updated their maximum availability to {$_POST['HoursInput']} hours a {$_POST['TimeSelection']} and {$_POST['DaysInput']} days a {$_POST['DaySelection']}. {$site}xhr/affected_user_redirect.php?redirect=$url \n\n
You must be logged in for this link to be active.
\n
The System.
MESSAGE;
	if($driver_id != NULL && $driver_id != '') {
    	mail(DEFAULT_CORD_EMAIL,"Maximum Availability Updated", $message, DEFAULT_EMAIL_FROM);
		$club_emails = get_franchise_email_addresses($franchise_id, 'ra_threshold');
	    if (sizeof($club_emails)>0) {
		    foreach($club_emails as $email) {
			  if (isset($email) && ($email!='')) {
			    //echo $email;
				mail($email,"Club ".$franchise_id."; Weekly Availability Updated", $message, DEFAULT_EMAIL_FROM);
			  }
		    }
		}
	}
}

$driver_settings = get_driver_settings_by_driver_id( $driver_id );
$max_weekly_hours = $driver_settings['MaxHoursPerWeek'];


$vacations = get_driver_vacations($driver_id);
if ($_POST['RemoveVacations']) {
    remove_driver_vacations($driver_id); 
    $vacations = get_driver_vacations($driver_id);
} elseif ($_POST['RemovePastVacations']) {
    remove_driver_past_vacations($driver_id);
	$vacations = get_driver_vacations($driver_id);
} elseif ($_POST['SubmitVacation']) {
    remove_driver_vacations($driver_id);
    $vacations_string = '';
    if ($_POST['Enabled']) {
        foreach ($_POST['Enabled'] as $idx => $is_enabled) {
            if ($is_enabled) {
                // Get the date fields
                $start_date = "{$_POST['StartYear'][$idx]}-{$_POST['StartMonth'][$idx]}-{$_POST['StartDay'][$idx]}";
                $end_date = "{$_POST['EndYear'][$idx]}-{$_POST['EndMonth'][$idx]}-{$_POST['EndDay'][$idx]}";
                add_driver_vacation($driver_id, $start_date, $end_date);
                $vacations_string .= "Starting: $start_date |  Ending: $end_date \n";
				if (strtotime($end_date)<=time()) {
				  $vacations_error_string = 'Vacations end dates must be later than today\'s date';
				}
				if (strtotime($end_date)<strtotime($start_date)) {
				  $vacations_error_string = 'Vacations end dates must be later start date';
				}
            }
        }
    }
    $vacations = get_driver_vacations($driver_id);
    $site = site_url();
    $url = urldecode("/driver_availability.php&userid=$driver_id");
    $name = get_user_person_name($driver_id);
    $name  = get_displayable_person_name_string($name);
    $message = <<<MESSAGE
$admin_changed User: $name UserID: $driver_id has just updated their vacation times\n
\n
$vacations_string
\n
. {$site}xhr/affected_user_redirect.php?redirect=$url \n\n
You must be logged in for this link to be active.
\n
The System.
MESSAGE;
	if($driver_id != NULL && $driver_id != '' && $vacations_string) {
    	mail(DEFAULT_CORD_EMAIL,"Vacation Times Updated", $message, DEFAULT_EMAIL_FROM);
		$club_emails = get_franchise_email_addresses($franchise_id, 'ra_threshold');
	    if (sizeof($club_emails)>0) {
		    foreach($club_emails as $email) {
			  if (isset($email) && ($email!='')) {
			    //echo $email;
				mail($email,"Club ".$franchise_id."; Weekly Availability Updated", $message, DEFAULT_EMAIL_FROM);
			  }
		    }
		}
	}
}

function is_in_availability($day, $time_entry, $meridiem) {
    global $current_availability;

    list($hour, $minute) = explode(':', $time_entry);
    if ($meridiem == 'PM' && $hour != 12) { $hour += 12; }
    if ($meridiem == 'AM' && $hour == 12) { $hour = 0; }


    $loc_avail = $current_availability;
    if (!$loc_avail) { return FALSE; }

    foreach ($loc_avail as $index => $value) {
        if ($value['DayOfWeek'] != $day) {
            unset ($loc_avail[$index]);
            next;
        }

        $time_start = explode(':', $value['StartTime']);
        $time_end = explode(':', $value['EndTime']);

        if ($time_start[0] > $hour || $time_end[0] < $hour) {
            unset ($loc_avail[$index]);
            next;
        }

        if ($time_start[0] == $hour && $time_start[1] > $minute) {
            unset ($loc_avail[$index]);
            next;
        }

        if ($time_end[0] == $hour && $time_end[1] <= $minute) {
            unset ($loc_avail[$index]);
        }    
    }

    // After unsetting everything in the loc availability that does not match,
    // if we have anything left, we're good to go.
    return (count($loc_avail) != 0);
}

$ADDITIONAL_RC_JAVASCRIPT = array('datepicker.js');
include_once 'include/header.php';

//echo "<pre>\n" . var_export($driver_id, true) . '</pre>';
//echo "<pre>\n" . var_export($current_availability, true) . '</pre>';

?>
<table cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<div
				style="border: 1px solid #666666; box-shadow: 4px 4px 4px rgba(0, 0, 0, .6); padding: 10px; display: inline-block; float: none;">
				<h2>
					Maximum Available Hours<a name="MaximumHours"></a>
				</h2>
<?php 
	if(driver_reached_max_availability($driver_id))
		echo "<div class=\"reminder\">Maximum drive limit has been met.</div>";
?>
Last Updated: <?php if($driver_settings['AvailabilityLastUpdate'] != null )echo format_date($driver_settings['AvailabilityLastUpdate'],"n/j/Y g:i a"); else echo "Never Updated"; ?><br>

				I am willing to drive a maximum of:
				<form method="post">
					<ul class="Maximum_Hours">
						<li><input type="text"
							value="<?php echo $driver_settings['HoursPerTime']; ?>"
							name="HoursInput" size="2" /> hours per <select
							name="TimeSelection">
								<option value="Day"
									<?php if($driver_settings['HoursTimeUnit'] == 'Day') echo ' SELECTED'; ?>>Day</option>
								<option value="Week"
									<?php if($driver_settings['HoursTimeUnit'] == 'Week') echo ' SELECTED'; ?>>Week</option>
								<option value="Month"
									<?php if($driver_settings['HoursTimeUnit'] == 'Month') echo ' SELECTED'; ?>>Month</option>
						</select></li>
						<li class="Maximum_Hours_Divider"><br> OR <br> <br></li>
						<li><input type="text"
							value="<?php echo $driver_settings['DaysPerTime']; ?>"
							name="DaysInput" size="2" /> days per <select name="DaySelection">
								<option value="Week"
									<?php if($driver_settings['DaysTimeUnit'] == 'Week') echo ' SELECTED'; ?>>Week</option>
								<option value="Month"
									<?php if($driver_settings['DaysTimeUnit'] == 'Month') echo ' SELECTED'; ?>>Month</option>
								<option value="Year"
									<?php if($driver_settings['DaysTimeUnit'] == 'Year') echo ' SELECTED'; ?>>Year</option>
						</select></li>
						<br>
						<li class="Maximum_Hours_Submit"><input type="submit"
							value="Update Hours" name="submitHours" /></li>
					</ul>
				</form>
			</div>
		</td>
		<td style="width: 10px;"></td>
		<td valign="top">
			<div
				style="border: 1px solid #666666; box-shadow: 4px 4px 4px rgba(0, 0, 0, .6); padding: 10px; display: inline-block; float: none;">
				<center>
					<h2>
						Days I Am Not Able To Drive<a name="Vacation"></a>
					</h2>
				</center>
				Entries here override other entries. Please do not clear Available
				Hours/Times. <br>
Last Updated: <?php if($date = get_last_vacation_update_date($driver_id))echo format_date($date,"n/j/Y g:i a"); else echo "Never Updated"; ?>
<form method="post">
					<br>
    <?php
	if (isset($vacations_error_string)) {
	?>
    <p
						style="border-top: 1px solid #990000; border-bottom: 1px solid #990000; background-color: #ffeeee; font-weight: bold;"><?php echo $vacations_error_string; ?></p>
    <?php
	}
	?>
    <table border="1">
						<tr>
							<th>Enabled</th>
							<th align=center>Start</th>
							<th>&nbsp;&nbsp;&nbsp;</th>
							<th align=center>End</th>
						</tr>
        <?php

        function print_vacation_select($prefix, $idx) {
            global $vacations;

            $selected_month = date('m');
            $selected_day = date('j');
            $selected_year = date('Y');
            $selected_date = "{$selected_month}/".str_pad($selected_day,2,"0",STR_PAD_LEFT)."/{$selected_year}";

            if ($vacations[$idx]) {
                $date_parts = explode('-', $vacations[$idx][$prefix . 'Date']);
                if (count($date_parts) >= 3) {
                    list($selected_year, $selected_month, $selected_day) = $date_parts;
                }
            }
						/*
            print_month_select( "{$prefix}Month[$idx]", FALSE, $selected_month, '', "document.getElementById('Enabled_chk".$idx."').checked=true"); 
            print_day_select( "{$prefix}Day[$idx]", FALSE, $selected_day, '', "document.getElementById('Enabled_chk".$idx."').checked=true"); 
            print_year_select( date('Y')-1, 4, "{$prefix}Year[$idx]", FALSE, $selected_year, '', "document.getElementById('Enabled_chk".$idx."').checked=true"); 
            */
            echo "<input type=hidden name=\"{$prefix}Month[$idx]\" value=\"$selected_month\">";
            echo "<input type=hidden name=\"{$prefix}Day[$idx]\" value=\"$selected_day\">";
            echo "<input type=hidden name=\"{$prefix}Year[$idx]\" value=\"$selected_year\">";
           	
            echo "<input style='text-align: center;' type=text size=20 name=\"{$prefix}Date[$idx]\" id=\"{$prefix}Date{$idx}\" class=\"jq_datepicker\" value=\"{$selected_date}\">";
       			echo "<script>
		 jQuery(function($) { 	
		 	 $('#{$prefix}Date{$idx}').datepicker(\"option\",\"onSelect\",function(dtext,ob) {
		 		$('#Enabled_chk{$idx}').prop('checked',true);
		 		$('#Enabled_chk{$idx}').attr('checked',true);
		 		var s = dtext.split('/');
		 		$('input[name=\"{$prefix}Month[$idx]\"]').val( s[0] );
		 		$('input[name=\"{$prefix}Day[$idx]\"]').val( s[1] );
		 		$('input[name=\"{$prefix}Year[$idx]\"]').val( s[2] );
		 		
		 		if(\"$prefix\" == \"Start\") {
		 		$('input[name=\"EndMonth[$idx]\"]').val( s[0] );
		 		$('input[name=\"EndDay[$idx]\"]').val( s[1] );
		 		$('input[name=\"EndYear[$idx]\"]').val( s[2] );
		 			$('#EndDate{$idx}').val($('#{$prefix}Date{$idx}').val());
		 			var db = $('#EndDate{$idx}').datepicker().data('datepicker');
		 			db.update('minDate',new Date($('#{$prefix}Date{$idx}').val()));
		 		}
		 	});	
		 });
		 			</script>";
        }

            for ($v = 0; $v < 3; $v++) {
                echo '<tr><td><input type="checkbox" name="Enabled[' . $v . ']" ' .
                        (($vacations[$v]) ? 'checked="checked" ' : '') . ' id="Enabled_chk'.$v.'" /></td><td>';
                print_vacation_select('Start', $v);
                echo '  </td><td> </td><td>  ';
                print_vacation_select('End', $v);
                echo '</td>';
            }
        ?>
    </table>


					<input type="submit" name="SubmitVacation"
						value="Update Blocked Days" /> <input type="submit"
						name="RemovePastVacations" value="Remove Past Blocked Days" /> <input
						type="submit" name="RemoveVacations"
						value="Remove All Blocked Days" />
				</form>
			</div>
		</td>

	</tr>
	<tr>
		<td style="height: 10px;" colspan="3"></td>
	</tr>
	<tr>
		<td colspan="3">
			<div
				style="border: 1px solid #666666; box-shadow: 4px 4px 4px rgba(0, 0, 0, .6); padding: 10px; display: inline-block; float: none;">
				<h2>
					Available Times<a name="AvailableTimes"></a>
				</h2>
				<div class="red">
					<b> If you are not available on a day you normally drive, enter
						info above in "Days I Am Not Able To Drive" rather than removing
						your Available Times.</b>
				<br>
				<?php
				if(isset($_POST['Submit'], $_POST['TimeSlot']) && $has_quit) {?>
				<br><h2>
				    <b> You must Identify 'Days [you] are not able to drive' above <br>rather than clear your 'Availability' in the grid below. </b>
					</h2><br>
				<?php } ?>
				</div>
				
Last Updated: <?php echo format_date($current_availability[0]['TimeAdded'],"n/j/Y g:i a"); ?>
<form method="post">
					I want to choose times I am <input id="availability"
						onclick="toggle_table();" type="radio" name="availability"
						value="Available" checked="true">Available</input> <input
						type="radio" onclick="toggle_table();" name="availability"
						value="Not Available">Not Available. </input>
					<!--<input type="button" onclick="reset_table();" value="Clear"></input> -->
					<input type="submit" name="Submit" value="Update Times">
					<table cellspacing="0px" id="availability_table"
						class="availability_table" style="width: 900px;" border="1px">
						<tr>
							<th>&nbsp;</th>
			<?php
			for ($i = 0; $i <= count($days_of_week) - 1; $i++) { ?>
                <th><?php echo substr($days_of_week[$i],0,3) ?></th>
			<?php } ?>	
			<th>&nbsp;</th>
			<?php
			for ($i = 0; $i <= count($days_of_week) - 1; $i++) { ?>
                <th><?php echo substr($days_of_week[$i],0,3) ?></th>
			<?php } ?>	
		</tr>

		<?php
			for($j = 0; $j <= count($hours) - 1; $j++)
			{
				?>
				<tr>
							<td style="width: 70px;"><?php echo $hours[$j] . ' ' . $meridiem[0];?></td>
					<?php
					for($k = 0; $k <= count($days_of_week) - 1; $k++)
					{
                        $slot_value = '';
                        $bg_color = '#FFFFFF';
                        if (is_in_availability($days_of_week[$k], $hours[$j], $meridiem[0])) {
                            $slot_value = 'CHECKED';
                            $bg_color = '#00FF00';
                        }
                        
						?>
						<td id="<?php 
                            echo $days_of_week[$k] . $hours[$j] . $meridiem[0] . 
                                 '_cell' ?>" title="<?php 
                            echo $days_of_week[$k] . ', ' . $hours[$j] . '-'   . $hours[$j+1] . ' ' . $meridiem[0]  
                                  ?>" class="availability_cell" value="<?php
                            echo $slot_value ?>" style="background: <?php
                            echo $bg_color; ?>"><input
								id="<?php 
                            echo $days_of_week[$k] . $hours[$j] . $meridiem[0] . 
                                 '_cell_input' ?>"
								type="hidden"
								name="<?php 
                            echo 'TimeSlot[' . $days_of_week[$k] . '][' . $hours[$j] . '][' .
                                 $meridiem[0] . ']'; ?>"
								value="<?php 
                            echo $slot_value; ?>"></td>
						<?php
					}
					?>				
 					<td style="width: 70px;"><?php echo $hours[$j] . ' ' . $meridiem[1];?></td>
					<?php
					for($k = 0; $k <= count($days_of_week) - 1; $k++)
					{
                        $slot_value = '';
                        $bg_color = '#FFFFFF';
                        if (is_in_availability($days_of_week[$k], $hours[$j], $meridiem[1])) {
                            $slot_value = 'CHECKED';
                            $bg_color = '#00FF00';
                        }
                        
						?>
						<td id="<?php 
                            echo $days_of_week[$k] . $hours[$j] . $meridiem[1] . 
                                 '_cell' ?>" title="<?php 
                            echo $days_of_week[$k] . ', ' . $hours[$j] . '-'   . $hours[$j+1] . ' ' . $meridiem[1]  
                                  ?>" class="availability_cell" value="<?php
                            echo $slot_value ?>" style="background: <?php
                            echo $bg_color; ?>"><input
								id="<?php 
                            echo $days_of_week[$k] . $hours[$j] . $meridiem[1] . 
                                 '_cell_input' ?>"
								type="hidden"
								name="<?php 
                            echo 'TimeSlot[' . $days_of_week[$k] . '][' . $hours[$j] . '][' .
                                 $meridiem[1] . ']'; ?>"
								value="<?php 
                            echo $slot_value; ?>"></td>
						<?php
					}
					?>				
				</tr>
				<?php
			}
		?>
	</table>
					<input type="submit" name="Submit" value="Update Times">
				</form>
			</div>
		</td>
	</tr>

</table>


<script type="text/javascript">

    var is_dragging = false;

    function set_availability_color(cell) {
        var is_checked = (cell.value == 'CHECKED'); 
        var choosing_available_times = $('availability').checked;

        if (is_checked && choosing_available_times) {
            // Turn green
            cell.setStyle('background', '#00FF00');
        } else if (is_checked && !choosing_available_times) {
            // Turn red
            cell.setStyle('background', '#FF0000');
        } else {
            // Turn white
            cell.setStyle('background', '#FFFFFF');
        }
    }


    function availability_click_handler(evt) {
        evt.target.value = (evt.target.value == 'CHECKED') ? '' : 'CHECKED';
        $(evt.target.id+ '_input').value = evt.target.value;
        set_availability_color(evt.target);
    }

    function availability_mouseover_handler(evt) {
        if (is_dragging) {
            if (evt.target.value != 'CHECKED') {
                evt.target.value = 'CHECKED';
                $(evt.target.id+ '_input').value = evt.target.value;
                set_availability_color(evt.target);
            }
        }
    }


    function decorate_availabilities() {
        var availabilities = $(document.body).getElements('td.availability_cell');
        availabilities.addEvents( {
                'mousedown' : availability_click_handler,
                'mouseover' : availability_mouseover_handler,
                'mouseout'  : function (evt) { evt.stop(); },
        });

        $('availability_table').addEvents({
                'mousedown' : function(evt) {
                        is_dragging = true;
                },
                'mouseup' : function() {
                        is_dragging = false;
                },
                'mouseout' : function() {
                        is_dragging = false;
                },
        });

        $each(availabilities, function(item, index) {
            item.value = $(item.id+ '_input').value;
        });
    }

    window.addEvent('DOMContentLoaded', decorate_availabilities);

	
	function toggle_table(){
        var availabilities = $(document.body).getElements('td.availability_cell');
        $each(availabilities, function (item, index) {
            set_availability_color(item);            
        });
    }

    function reset_table() {
        var availabilities = $(document.body).getElements('td.availability_cell');
        
        $each(availabilities, function (item, index) {
            item.value = '';
            $(item.id+ '_input').value = '';
            item.setStyle('background', '#FFFFFF');
        });
    }


    
</script>
<style>
.Maximum_Hours {
	list-style: none;
}

.Maximum_Hours_Divider {
	padding-left: 50px;
}

.Maximum_Hours_Submit {
	padding-left: 110px;
}
</style>
<?php
	include_once 'include/footer.php';
?>
