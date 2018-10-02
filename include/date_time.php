<?php
require_once('include/rc_log.php');

	 $months = array('January', 'February', 'March', 'April', 'May', 'June',
                    'July', 'August', 'September', 'October', 'November', 'December');

function print_year_select( $start_year, $count, $name, $id = FALSE, $selected_year = FALSE, $disabled = FALSE, $onchange='' ) {
    $name = htmlspecialchars($name); 
    $id_attr = ($id == FALSE) ? '' : ' id="' . htmlspecialchars($id) . '"';
    $selected_year = ($selected_year === FALSE) ? $start_year : $selected_year;
	$disabled = !$disabled ? '' : ' DISABLED';
    echo "<select name=\"$name\"$id_attr$disabled";
	if ($onchange) {
	  echo ' onchange="'.$onchange.'" ';
	}
	echo ">";
    for ($year = $start_year; $year < ($start_year + $count); $year++) {
        echo '<option value="' . $year . '"' .
             ((($year) == $selected_year) ? ' selected="selected"' : '') .
             ">$year</option>\n";
    }
    echo '</select>';
}

function print_month_select( $name, $id = FALSE, $selected_month = FALSE, $disabled = FALSE, $onchange=''  ) {
    $name = htmlspecialchars($name); 
    $id_attr = ($id == FALSE) ? '' : ' id="' . htmlspecialchars($id) . '"';
    $selected_month = ($selected_month === FALSE) ? date('n') : $selected_month;
	$disabled = !$disabled ? '' : ' DISABLED';
    global $months;

    echo "<select name=\"$name\"$id_attr$disabled";
	if ($onchange) {
	  echo ' onchange="'.$onchange.'" ';
	}
	echo ">";
    foreach ($months as $index => $name) {
        echo '<option value="' . ($index + 1 < 10 ? '0' : '') . ($index + 1) . '"' .
             ((($index+1) == $selected_month) ? ' selected="selected"' : '') .
             ">$name</option>\n";
    }
    echo '</select>';
}

function print_day_select( $name, $id = FALSE, $selected_day = FALSE, $disabled = FALSE, $onchange = '' ) {
    $name = htmlspecialchars($name); 
    $id_attr = ($id == FALSE) ? '' : '" id="' . htmlspecialchars($id) . '"';
    $selected_day = ($selected_day === FALSE) ? date('j') + 1 : $selected_day;
	$disabled = !$disabled ? '' : ' DISABLED';
	
    echo '<select name="' . $name . $id_attr . "\"$disabled";
	if ($onchange) {
	  echo ' onchange="'.$onchange.'" ';
	}
	echo ">";
    for ($day = 1; $day < 32; $day++) {
        echo "<option value=\"".($day < 10 ? "0" : "")."$day\"" .
             (($day == $selected_day) ? ' selected="selected"' : '') .
             ">$day</option>";
    }
    echo '</select>';
}


function calculate_link_departure_time( $arrival_time, $estimated_minutes, $pad = 10 ) {
    // Pad = 5 minutes loading, 5 minutes drive-time padding.
    $total_time = $estimated_minutes + $pad;
    
    $leave_time = $arrive_time - $total_time;
}

function get_date($date){
	if($date == NULL)
		return FALSE;
	
	$time = strtotime($date);
	//2010-02-15 09:10:00
	return array('Month' => date("n",$time),
				 'Day' => date("j",$time),
				 'Year' => date("Y",$time),
	             'Date' => date("m/d/Y",$time));
}
function get_time($time){
	if($time == NULL)
		return FALSE;
	$time = strtotime($time);
	//2010-02-15 09:10:00
	return array('24Hour' => date("G", $time),
				 'Hour' => date("g",$time),
				 'Minute' => date("i",$time),
				 'Second' => date("s",$time),
				 'AM_PM' => date("A",$time),
				 '24String' => date("G:i:s",$time));
}
function get_days_in_month($month,$year){
	return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
}
function get_date_drop_downs($prefix, $date = NULL, $start_date = '1900', $end_date = NULL){
	global $months;
	if(is_int($date))
		$date = date('Y-n-j',$date);
	if($end_date == NULL)
		$end_date = (date("Y") + 10);
	if($date != NULL)
		$date = get_date($date);
	else
		$date = array('Month' => date("n"), 'Day' => date("j"), 'Year' => date("Y"));
	?><select name="<?php echo $prefix; ?>Month"><?php
	for($i = 1; $i <= 12; $i++){
		echo "<option value=\"$i\"";
			if(($i) == $date['Month']) echo ' SELECTED';
		echo ">" . $months[($i - 1)] . "</option>";
	}
	?></select> / <select name="<?php echo $prefix; ?>Day"><?php
	for($i = 1; $i <= 32; $i++){
		echo '<option value="' . $i . '"';
		if($i == $date['Day']) echo ' SELECTED';
		echo '>' . $i . '</option>';
	}
	?></select> / <select name="<?php echo $prefix; ?>Year"><?php
	for($i = $end_date; $i >= $start_date; $i--){
		echo '<option value="' . $i . '"';
		if($i == $date['Year']) echo ' SELECTED';
		echo '>' . $i . '</option>';
	}
	?></select><?php
}

function set_franchise_timezone($franchise_id) {
    // TODO:  Currently hard-coded to US Central
    // date_default_timezone_set('America/Chicago');
    
    $sql = "select OfficeTimeZone from franchise where FranchiseId = $franchise_id";

    $rs = mysql_fetch_assoc(mysql_query($sql));
    $php_timezone_map = array(
    	'CST' => 'America/Chicago',
    	'AKST' => 'America/Anchorage',
    	'EST' => 'America/New_York',
    	'MST' => 'America/Denver',
    	'PST' => 'America/Los_Angeles',
    	'HST' => 'America/Adak'
    );
    date_default_timezone_set($php_timezone_map[ $rs["OfficeTimeZone"] ]);
}


function get_time_selector($type, $num, $hour = FALSE, $minute = FALSE, $ampm = FALSE, $event = FALSE) {
    $selected_hour = ($hour == FALSE) ? 12 : $hour;
    $selected_min = ($hour == FALSE) ? 0 : $minute;
    $am_selected = ($ampm == 'AM') ? 'selected="selected" ' : '';
    $pm_selected = ($ampm == 'PM') ? 'selected="selected" ' : '';

    $ret = "<select $event name=\"hour[$type][$num]\">";
    for ($hr = 1; $hr <= 12; $hr++) {
        $ret .= "<option value=\"$hr\"" . 
                (($hr == $selected_hour) ? ' selected="selected"' : '') . ">$hr</option>";
    }
    $ret .= "</select>:<select $event name=\"minute[$type][$num]\">";
	if($selected_min % 5 != 0)
		$ret .= "<option value=\"$selected_min\" selected=\"selected\">$selected_min</option>";
    for ($min = 0; $min < 60; $min += 1) {
        $ret .= "<option value=\"$min\" " . 
                (($min == $selected_min) ? ' selected="selected"' : '') . '>' . sprintf('%02d', $min) . '</option>';
    }
    $ret .= "</select><select $event name=\"AM_PM[" . "$type][$num" . 
            ']"><option value="AM" ' .  $am_selected . '>AM</option><option value="PM" ' .
            $pm_selected . '>PM</option></select>';

    return $ret;
}

function get_time_selector_post($type, $num) {
    $ret = array();

    $ret['hour'] = $_POST['hour'][$type][$num];
    $ret['minute'] = $_POST['minute'][$type][$num];
    $ret['AM_PM'] = $_POST['AM_PM'][$type][$num];

    $ret['hour24'] = ($ret['AM_PM'] == 'PM' && $ret['hour'] != 12) ? $ret['hour'] + 12 : $ret['hour'];

    return $ret;
}

function format_date($date, $format){
    if ($date == NULL || $date == '0000-00-00') {
        return NULL;
    }

    return date($format, strtotime($date));
}

function get_year_selector($from, $to, $date = NULL,$prefix = ''){
	?>
	<select name="<?php echo $prefix; ?>Year"><?php
	for($i = $to; $i >= $from; $i--){
		echo '<option value="' . $i . '"';
		if((($date == NULL) && $i == date("Y")) || ($date != NULL && $date == $i)) echo ' SELECTED';
		echo '>' . $i . '</option>';
	}
	?></select>
    <?php
}


/**
 * Gets the annual fee payment date if the annual fee were paid on the specified date.
 * If the annual fee is in effect, the new date is the annual fee expiration date plus one year.
 * If no annual fee is in effect, the new date is the payment date.
 * If the payment date is null, today's date is assumed.
 * Dates are expected to be formatted YYYY-mm-dd. (MySQL format)
 */
function calculate_new_annual_fee_payment_date($payment_date = NULL, $extant_annual_fee_date = NULL) {
		rc_log(PEAR_LOG_INFO,"calculate_new_annual_fee_payment_date called: EffectiveDate $payment_date, AnnualFeePaymentDate $extant_annual_fee_date");
    if (is_null($payment_date)) {
        $payment_date = date('Y-m-d');
    }

    $exploded_payment_date = explode('-', $payment_date);
    $payment_time_t = mktime(12, 0, 0, $exploded_payment_date[1], 
                             $exploded_payment_date[2], $exploded_payment_date[0]); 

    if (is_null($extant_annual_fee_date)) {
        // No annual fee is in effect
        $new_date = $payment_date;
    } else {
        // figure out if the extant annual fee is current
        $extant_parts = explode('-', $extant_annual_fee_date);
        $extant_time = mktime(0, 0, 0, $extant_parts[1], $extant_parts[2], $extant_parts[0] + 1);

        if ($extant_time >= time()) { // Newer!
            // Add a year to the current annual fee date
            $new_date = ($extant_parts[0] + 1) . "-{$extant_parts[1]}-{$extant_parts[2]}";
        } else { // Older!
            $new_date = $payment_date;
        }
    }
		rc_log(PEAR_LOG_INFO,"calculate_new_annual_fee_payment_date returning $new_date");
    return $new_date;
}

function get_next_day($date){
	return date('Y-m-d',strtotime($date)+86400);
}
function get_previous_day($date){
	return date('Y-m-d',strtotime($date)-86400);

}

function dates_equal($date1, $date2){
	$date1 = get_date($date1);
	$date2 = get_date($date2);
	if($date1['Month'] == $date2['Month'] && $date1['Day'] == $date2['Day'] && $date1['Year'] == $date2['Year'])
		return true;
	return false;
}
?>
