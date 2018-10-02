<?php
	require_once('include/user.php');
	redirect_if_not_logged_in();

    require_once('include/destinations.php');
    require_once('include/rider.php');
    require_once('include/date_time.php');
    require_once('include/link.php');
    require_once('include/large_facility.php');

    if (!(if_current_user_has_role('FullAdmin') || if_current_user_has_role('LargeFacilityAdmin'))) {
        header('Location: home.php');
    }

    session_start();
    unset ($_SESSION['LF_Facility_ID']);
    if (user_has_role(get_affected_user_id(), 'LargeFacilityAdmin')) {
        if ($lf_id = get_large_facility_id_for_user(get_affected_user_id())) {
            $_SESSION['LF_Facility_ID'] = $lf_id;
        }
    }

    $facility_id = get_working_large_facility();
    if (!$facility_id) {
        $error_message = 'No current large facility ID set.';
    } else {
        $facility_info = get_large_facility($facility_id);

        // TODO:  Check validity
       
        if ($_POST['Continue']) {
            $to_from = $_POST['ToOrFrom'];
            $when_type = $_POST['When'];
            $travel_time = array('hour' => $_POST['hour'][$when_type][0],
                                 'minute' => $_POST['minute'][$when_type][0],
                                 'AM_PM' => $_POST['AM_PM'][$when_type][0]);

            $travel_date = array('year' => $_POST['TravelYear'],
                                 'month' => $_POST['TravelMonth'],
                                 'day' => $_POST['TravelDay']);
            
            if (!date_is_schedulable($travel_date['year'], $travel_date['month'], $travel_date['day'],
                                    (($travel_time['hour'] < 12 && $travel_time['AM_PM'] == 'PM') ?
                                        $travel_time['hour'] + 12 : $travel_time['hour']),
                                    $travel_time['minute'])) {
                $error_message = 'Selected time and date is not schedulable.'; 
            }


            if ($_POST['ForWhom'] == 'New') {
                $name_post = get_name_fields_from_post();
                if ($name_post['RequiredFieldsPresent'] && $_POST['DOB_Year'] && $_POST['DOB_Month'] &&
                                                           $_POST['DOB_Day']) {
                    $date_of_birth = "{$_POST['DOB_Year']}-{$_POST['DOB_Month']}-{$_POST['DOB_Day']}";

                    if (db_start_transaction()) {
                        $name_id = add_person_name( $name_post['Name']['Title'], $name_post['Name']['FirstName'], 
                                                    $name_post['Name']['MiddleInitial'],
                                                    $name_post['Name']['LastName'], $name_post['Name']['Suffix'] );

                        $rider_id = add_large_facility_rider($facility_id, $name_id, $date_of_birth);

                        if ($name_id && $rider_id) {
                            db_commit_transaction();
                        } else {
                            db_rollback_transaction();
                            $error_message = 'An error occurred while adding the rider record.';
                        }
                    } else {
                        $error_message = 'Could not add the rider record.';
                    }
                } else {
                    $error_message = 'Required name fields are missing';
                }
            } else {
                $rider_id = $_POST['ExistingRider'];
            }

            // Set session vars
            $_SESSION['LFSCHED'] = array( 'travel_time' => $travel_time,
                                          'travel_date' => $travel_date,
                                          'rider_id' => $rider_id,
                                          'dob_year' => $_POST['DOB_Year'],
                                          'dob_month' => $_POST['DOB_Month'],
                                          'dob_day' => $_POST['DOB_Day'],
                                          'to_from' => $to_from,
                                          'when_type' => $when_type );


            session_commit();

            if (!$error_message) {
                header('Location: large_facility_set_ride_destinations.php');
                exit;
            }
        } 
            
        $riders = get_large_facility_riders($facility_id);
    }

    global $ADDITIONAL_RC_JAVASCRIPT;
    if (!is_array($ADDITIONAL_RC_JAVASCRIPT)) {
        $ADDITIONAL_RC_JAVASCRIPT = array();
    }
    $ADDITIONAL_RC_JAVASCRIPT[] = 'datepicker.js';

include('include/header.php');
?>

<br /><br />
<?php if ($error_message) { ?>
    <div><p style="color: red"><?php echo $error_message; ?></p></div>
<?php } ?>
<form method="POST" action="">
    <fieldset><legend>To or From</legend>
    <div style="float: left">
        <input type="radio" name="ToOrFrom" value="To" <?php
            if ($_SESSION['LFSCHED']['to_from'] == 'To') { echo 'checked="checked" '; } ?>/> To<br />
        <input type="radio" name="ToOrFrom" value="From" <?php
            if ($_SESSION['LFSCHED']['to_from'] == 'From') { echo 'checked="checked" '; } ?>/> From
    </div>
    <div style="margin-left: 2em; float: left">
        <?php  create_html_display_address($facility_info['FacilityAddressID']); ?>
    </div>
    <div style="clear: both;"> </div>
    </fieldset>
    <br />
    <fieldset><legend>For Whom</legend>
    <?php if (count($riders)) { ?>
    <input type="radio" name="ForWhom" value="Existing" <?php
            if ($_SESSION['LFSCHED']['rider_id']) { echo 'checked="checked" '; } ?>/> Existing Rider - 
        <select name="ExistingRider">
            <?php
                foreach ($riders as $rider) {
                    // TODO:  SELECTED if returning
                    echo '<option value="' . $rider['LfrRiderID'] . '">' . 
                         "{$rider['LastName']}, {$rider['FirstName']} {$rider['MiddleInitial']}</option>";
                } ?>
        </select> <br />
    <?php } ?>
    <input type="radio" name="ForWhom" value="New" <?php if (!count($riders) || !$_SESSION['LFSCHED']['rider_id']) {
        echo 'checked="checked" '; } ?>/> New Rider - 
    <div style="margin-left: 10em; margin-bottom: 1em;"><?php print_get_name_form_part($name_post['Name'], '', FALSE); ?>
    <br />
    <table id="dob">
        <tr><td nowrap="nowrap">Date of Birth</td>
            <td><?php 
                $selected_month = ($_SESSION['LFSCHED']['dob_month']) ? $_SESSION['LFSCHED']['dob_month'] : FALSE;
                print_month_select( 'DOB_Month', 'DOB_Month', $selected_month); ?></td>
            <td><?php 
                $selected_day = ($_SESSION['LFSCHED']['dob_day']) ? $_SESSION['LFSCHED']['dob_day'] : FALSE;
                print_day_select( 'DOB_Day', 'DOB_Day', $selected_day); ?></td>
            <td><?php
                $selected_year = ($_SESSION['LFSCHED']['dob_year']) ? $_SESSION['LFSCHED']['dob_year'] : date('Y');
                print_year_select( 1900, date('Y'), 'DOB_Year', 'DOB_Year', $selected_year );
            ?>
             <script type="text/javascript">
            // <![CDATA[  
              var dob_opts = {                            
                      formElements:{"DOB_Year":"Y","DOB_Month":"n","DOB_Day":"j"},
                      statusFormat:"l-cc-sp-d-sp-F-sp-Y"       
                      };           
              datePickerController.createDatePicker(dob_opts);
            // ]]>
            </script>
            </td>
        </tr>
    </table>
    </div>
    </fieldset>
    <br />
    <fieldset><legend>When</legend>
        <input type="radio" name="When" value="Arrive" <?php
                if ($_SESSION['LFSCHED']['when_type'] == 'Arrive') { echo 'checked="checked" '; }
                ?>/> Arrive At - <?php
            echo get_time_selector('Arrive', $idx, $_SESSION['LFSCHED']['travel_time']['hour'],
                                                   $_SESSION['LFSCHED']['travel_time']['minute'],
                                                   $_SESSION['LFSCHED']['travel_time']['AM_PM']);
            ?><br />
        <input type="radio" name="When" value="Depart" <?php
                if ($_SESSION['LFSCHED']['when_type'] == 'Depart') { echo 'checked="checked" '; }
                ?>/> Depart At - <?php
            echo get_time_selector('Depart', $idx, $_SESSION['LFSCHED']['travel_time']['hour'],
                                                   $_SESSION['LFSCHED']['travel_time']['minute'],
                                                   $_SESSION['LFSCHED']['travel_time']['AM_PM']);
            ?><br />
        
    <table id="travel_date">
        <tr><td nowrap="nowrap">What date do you want to travel?</td><?php 
            $earliest_ride = get_next_user_schedulable_link_time(); ?>
            <td><?php 
                $selected_month = ($month) ? $month : $earliest_ride['Month'];
                print_month_select( 'TravelMonth', 'TravelMonth', $selected_month); ?></td>
            <td><?php 
                $selected_day = ($day) ? $day : $earliest_ride['Day'];
                print_day_select( 'TravelDay', 'TravelDay', $selected_day); ?></td>
            <td><?php
                $selected_year = ($year) ? $year : date('Y');
                if (if_current_user_has_role('FullAdmin')) {
                    print_year_select( date('Y') - 3, 5, 'TravelYear', 'TravelYear', $selected_year );
                } else {
                    print_year_select( date('Y'), 2, 'TravelYear', 'TravelYear', $selected_year );
                }
            ?>
             <script type="text/javascript">
            // <![CDATA[  
              var travel_opts = {                            
                      formElements:{"TravelYear":"Y","TravelMonth":"n","TravelDay":"j"},
                      statusFormat:"l-cc-sp-d-sp-F-sp-Y"       
                      };           
              datePickerController.createDatePicker(travel_opts);
            // ]]>
            </script>
            </td>
        </tr>
    </table>
    </fieldset>

    <br />
    <input type="submit" name="Continue" value="Continue" />

</form>

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
