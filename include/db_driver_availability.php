<?php

require_once('include/database.php');
require_once('include/rc_log.php');

/**
 * Retrieves the availability of a driver.  Only currently-valid items are retrieved.
 * @param user_id Driver to get availability for
 * @return array of availability hashes (keys:  AvailabilityItemID, DayOfWeek, StartTime,
 *                                              EndTime, TimeAdded, TimeValid, TimeInvalid)
 *         or FALSE on error
 */
function get_driver_availability($user_id) {
    $safe_user_id = mysql_real_escape_string($user_id);

    $sql = "SELECT AvailabilityItemID, DayOfWeek, StartTime, EndTime, 
                   TimeAdded, TimeValid, TimeInvalid
            FROM driver_availability
            WHERE UserID = $safe_user_id AND
                  TimeValid <= NOW() AND
                  NOW() <= TimeInvalid";


    $result = mysql_query($sql);

    if ($result) {
        $availability_items = array();
        while ($row = mysql_fetch_array($result)) {
            $availability_items[] = $row;
        }
        return $availability_items;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Error getting driver availability for $user_id", $sql);
        return FALSE;
    }
}

function clear_driver_availability($user_id) {
    $safe_user_id = mysql_real_escape_string($user_id);

    $copy_sql = "INSERT INTO driver_availability_history
                    (AvailabilityItemID, UserID, DayOfWeek, StartTime, EndTime,
                     TimeAdded, TimeValid, TimeInvalid)
                    SELECT AvailabilityItemID, UserID, DayOfWeek, StartTime, EndTime,
                           TimeAdded, TimeValid, TimeInvalid
                    FROM driver_availability WHERE UserID = $safe_user_id";

    $result = mysql_query($copy_sql);
    if (!$result) {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Error copying driver $user_id record to history", $copy_sql);
    }

    $sql = "DELETE FROM driver_availability
            WHERE UserID = $safe_user_id";

    $result = mysql_query($sql);

    if ($result) {
        return TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Error clearing driver availability for $user_id", $sql);
        return FALSE;
    }
}

/**
 * Adds one or more driver availability entries.
 * @param $user_id ID of driver to set availability for
 * @param $availability_array array of hashes: 
 *                              keys (DayOfWeek, StartTime, EndTime)
 * @return TRUE if successful, FALSE otherwise.
 */
function add_driver_availability($user_id, $availability_array) {
    $safe_user_id = mysql_real_escape_string($user_id);

    $row_entries = array();
    foreach ($availability_array as $av_entry) {
        $safe_day = mysql_real_escape_string($av_entry['DayOfWeek']);
        $safe_start = mysql_real_escape_string($av_entry['StartTime']);
        $safe_end = mysql_real_escape_string($av_entry['EndTime']);

        $row_entries[] = "($safe_user_id, '$safe_day', " .
                         "STR_TO_DATE('$safe_start', '%l:%i %p'), ".
                         "STR_TO_DATE('$safe_end', '" .
                             ((strpos($safe_end, 'M') === FALSE) ?
                                /* 24 HR */ '%k:%i:%s' : /* 12 HR */ '%l:%i %p') .
                            "'))"; 
    }

    $sql = "INSERT INTO driver_availability (UserID, DayOfWeek, StartTime, EndTime)
            VALUES " .
            implode(', ', $row_entries);

    $result = mysql_query($sql);

    if ($result) {
        return TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Error setting driver availability for $user_id", $sql);
        return FALSE;
    }
}


/**
 * Retrieves the requested vacation of a driver.  
 * @param user_id Driver to get vacation for
 * @param start_date Optional parameter.  If this is set, only vacations that start on or
 *                   after the requested start date will be returned.
 * @param end_date Optional parameter.  If this is set, only vacations that start on or before the
 *                 requested end date will be returned.
 *                  
 * @return array of vacation hashes (keys: VacationID, UserID, StartDate, EndDate, TimeAdded)
 *         or FALSE on error
 */
function get_driver_vacations($user_id, $start_date = FALSE, $end_date = FALSE) {
    $safe_user_id = mysql_real_escape_string($user_id);
    if ($start_date) {
        $safe_start = mysql_real_escape_string($start_date);
        $start_where_clause = " AND StartDate <= DATE('$safe_start')";
    }

    if ($end_date) {
        $safe_end = mysql_real_escape_string($end_date);
        $end_where_clause = " AND DATE('$safe_end') <= EndDate";
    }

    $sql = "SELECT VacationID, UserID, StartDate, EndDate, TimeAdded
            FROM driver_vacation
            WHERE UserID = $safe_user_id
                  $start_where_clause
                  $end_where_clause
            ORDER BY StartDate ASC";

    $result = mysql_query($sql);

    if ($result) {
        $vacations = array();
        while ($row = mysql_fetch_array($result)) {
            $vacations[] = $row;
        }
        return $vacations;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Error getting driver vacations for $user_id ($start_date, $end_date)", $sql);
    }
    return FALSE;
}

function remove_driver_vacations($user_id) {
    $safe_user_id = mysql_real_escape_string($user_id);

    $sql = "DELETE FROM driver_vacation WHERE UserID = $safe_user_id";

    $result = mysql_query($sql);

    if ($result) {
        return TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Error deleting vacation entries for driver $user_id", $sql);
    }
    return FALSE;
}

function remove_driver_past_vacations($user_id) {
    $safe_user_id = mysql_real_escape_string($user_id);

    $sql = "DELETE FROM driver_vacation WHERE UserID = $safe_user_id and EndDate<now()";

    $result = mysql_query($sql);

    if ($result) {
        return TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Error deleting vacation entries for driver $user_id", $sql);
    }
    return FALSE;
}

function add_driver_vacation($user_id, $start_date, $end_date) {
    $safe_user_id = mysql_real_escape_string($user_id);
    $safe_start = mysql_real_escape_string($start_date);
    $safe_end = mysql_real_escape_string($end_date);

    $sql = "INSERT INTO driver_vacation (UserID, StartDate, EndDate)
            VALUES ($safe_user_id, DATE('$safe_start'), DATE('$safe_end'))";
    $result = mysql_query($sql);

    if ($result) {
        return mysql_insert_id();
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not add vacation entry for $user_id ($start_date to $end_date)", $sql);
        return FALSE;
    }
}

/**
 * Determines whether a driver is generally available between the given times.  This
 * function checks scheduled vacations.  It does not check whether the driver has 
 * other rides scheduled.
 * @param $user_id ID of driver to check availability for
 * @param $start_date_time date/time structure with MySQL date/time (key: mysql) and time_t (key: time_t)
 * @param $end_date_time date/time structure with MySQL date/time (key: mysql) and time_t (key: time_t)
 * @return boolean (TRUE means available)
 */
function driver_is_available( $user_id, $start_date_time, $end_date_time ) {
	  #print_r($end_date_time);
    if (!$user_id) {
        return FALSE;
    }

    $vacation = get_driver_vacations($user_id, $start_date_time['mysql'], $end_date_time['mysql']);

    if (count($vacation) != 0) {
        //echo "DRIVER $user_id IS ON VACATION<br />";
        //var_export($vacation);
        return FALSE;
    }

    $availability = get_driver_availability($user_id);

    // There is a chance that a ride spans days (11:50PM - 12:10AM).  Remove availabilities
    // not on the ride days.
    $start_day = date('l', $start_date_time['time_t']);
    $end_day = date('l', $end_date_time['time_t']);
    
    $day_filtered = array_filter( $availability,
                                  create_function('$av', "return (\$av['DayOfWeek']=='$start_day' || \$av['DayOfWeek']=='$end_day');") );

    if (count($day_filtered) == 0) {
        //echo "DRIVER $user_id NOT AVAILABLE BY DAY<br />";
        return FALSE;
    }

    // Map all of the availabilities into time_t structures.  If the start day is different than the end day,
    // need to handle that.
    $start_day   = date('d', $start_date_time['time_t']);
    $start_month = date('m', $start_date_time['time_t']);
    $start_year  = date('Y', $start_date_time['time_t']);
    $end_day   = date('d', $end_date_time['time_t']);
    $end_month = date('m', $end_date_time['time_t']);
    $end_year  = date('Y', $end_date_time['time_t']);
    foreach ($day_filtered as &$av) {
        list($st_hr, $st_min, $st_sec) = explode(':', $av['StartTime']);
        list($e_hr, $e_min, $e_sec) = explode(':', $av['EndTime']);
        if ($av['DayOfWeek'] != $start_day) {
            $av['start_time_t'] = mktime($st_hr, $st_min, 0, $end_month, $end_day, $end_year);
            $av['end_time_t'] = mktime($e_hr, $e_min, 0, $end_month, $end_day, $end_year);  
        } else {
            $av['start_time_t'] = mktime($st_hr, $st_min, 0, $start_month, $start_day, $start_year);
            $av['end_time_t'] = mktime($e_hr, $e_min, 0, $start_month, $start_day, $start_year);  // START is correct
        }
    }

    // Now remove any items that the start and end both fall outside of.
    $time_filtered = array_filter( $day_filtered,
                                   create_function('$av', "return !(
                                                            (\$av['start_time_t'] > {$end_date_time['time_t']}) ||
                                                            (\$av['end_time_t'] < {$start_date_time['time_t']} ));") );

    if (count($time_filtered) == 0) {
        //echo "DRIVER $user_id NOT AVAILABLE BY TIME<br />";
        return FALSE;
    }

    // Combine any items that have a start/end timestamp that are the same (e.g. midnight/0AM)
    uasort($time_filtered, create_function('$av1, $av2', 'if ($av1[\'start_time_t\'] < $av2[\'start_time_t\']) return -1;
                                                          if ($av1[\'start_time_t\'] > $av2[\'start_time_t\']) return 1;
                                                          return 0;'));
    $prev_start = 0;
    $prev_end = 0;
    foreach ($time_filtered as $idx => &$tf_item) {
        if ($tf_item['start_time_t'] == $prev_end) {
            $tf_item['start_time_t'] = $prev_start;
        }
        $prev_start = $tf_item['start_time_t'];
        $prev_end = $tf_item['end_time_t'];
    }

    
    // Now everything should be combined.  Now we remove any items that the start or end fall outside of.
    $final = array_filter( $time_filtered,
                           create_function('$av', "return (
                                                    (\$av['start_time_t'] <= {$start_date_time['time_t']}) && 
                                                    (\$av['end_time_t'] >= {$end_date_time['time_t']} ));") );

    if (count($final) == 0) {
        //echo "DRIVER $user_id NOT AVAILABLE BY TIME<br />";
        return FALSE;
    }

        //echo "DRIVER $user_id AVAILABLE  <br />";
    return TRUE; 
}


function set_driver_daily_availability( $user_id, $date, $available_hours ) {
    $safe_user_id = mysql_real_escape_string($user_id);
    $safe_date = mysql_real_escape_string($date);
    $safe_hours = mysql_real_escape_string($available_hours);

    $sql = "INSERT INTO driver_daily_availability (UserID, DriverDate, AvailableHours)
            VALUES ($safe_user_id, DATE('$safe_date'), $safe_hours)
            ON DUPLICATE KEY UPDATE AvailableHours = $safe_hours";

    $result = mysql_query($sql);

    if ($result) {
        return TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not set daily availability for $user_id on $date to $available_hours", $sql);
        return FALSE;
    }
} 


function get_driver_daily_availability( $user_id, $date ) {
    $safe_user_id = mysql_real_escape_string($user_id);
    $safe_date = mysql_real_escape_string($date);

    $sql = "SELECT AvailableHours FROM driver_daily_availability
            WHERE UserID = $safe_user_id AND
                  DriverDate = '$safe_date'";

    $result = mysql_query($sql);

    if ($result) {
        $row = mysql_fetch_array($result, MYSQL_ASSOC);

        return (($row) ? $row['AvailableHours'] : '');
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Error getting driver daily availability for $user_id on $date", $sql);
    }
    return '';
}



function set_driver_max_hours_per_week( $user_id, $max_weekly_hours ) {
    $safe_user_id = mysql_real_escape_string($user_id);
    $safe_hours = mysql_real_escape_string($max_weekly_hours);

    $sql = "UPDATE driver_settings SET MaxHoursPerWeek = $safe_hours
            WHERE UserID=$safe_user_id";

    $result = mysql_query($sql);

    if ($result) {
        return TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not set max weekly hours for $user_id to $max_weekly_hours", $sql);
        return FALSE;
    }
} 
/*
 *	This is meant to be cached. It will be very slow.
 *
 */
function load_all_available_drivers($franchiseID){
	$safe_franchise = mysql_real_escape_string($franchiseID);
	$array = array();
	$days = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
	for($day = 0; $day < 7; $day++){
		for($hour = 0; $hour < 24; $hour++){
			for($i = 0; $i < 2; $i++){
				$min = $i % 1 == 1 ? '15' : '45';
				$sql = "SELECT driver_availability.UserID FROM driver_availability LEFT JOIN driver ON driver_availability.UserID = driver.UserID WHERE DriverStatus = 'Active' AND DayOfWeek = '{$days[$day]}' AND StartTime <= CAST('$hour:$min:00' as TIME) AND EndTime > CAST('$hour:$min:00' as TIME) AND driver.UserID IN ( SELECT UserID FROM user_role WHERE FranchiseID = '$safe_franchise') AND driver.UserID NOT IN (SELECT UserID FROM driver_vacation WHERE NOW() BETWEEN StartDate AND EndDate) GROUP BY UserID";
				$result = mysql_query($sql) or die(mysql_error());
				
				while($row = mysql_fetch_array($result)){
					$array[$day][$hour][$i]['drivers'][] = $row['UserID'];
					$array[$day][$hour][$i]['num'] += 1;
				}
			}
		}
	}
	return $array;
}

function get_last_vacation_update_date($user_id){
	$safe_user_id = mysql_real_escape_string($user_id);
	
	$sql = "SELECT * FROM `driver_vacation` WHERE UserID = $safe_user_id ORDER BY TimeAdded DESC LIMIT 1; ";
	$result = mysql_query($sql);
	
	if($result){
		if($result = mysql_fetch_array($result))
			return $result['TimeAdded'];
		return false;
	} else {
		 rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get last vacation update date", $sql);
        return FALSE;
	}
}

function get_driver_days_worked($driver_id, $start_date, $end_date){
	$safe_driver_id = mysql_real_escape_string($driver_id);
	$safe_start_date = mysql_real_escape_string($start_date);
	$safe_end_date = mysql_real_escape_string($end_date);
	
	$sql  = "SELECT COUNT(*) FROM (SELECT * FROM ((SELECT DATE(DesiredArrivalTime) AS date FROM link WHERE AssignedDriverUserID = $safe_driver_id) UNION (SELECT DATE(DesiredArrivalTime) AS date  FROM link_history WHERE DriverUserID = $safe_driver_id)) AS t1 WHERE date BETWEEN '$safe_start_date' AND '$safe_end_date' GROUP BY date) AS t2";
	$result = mysql_query($sql);
	
	if($result){
		$result = mysql_fetch_array($result);
		return $result[0];
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get driver days worked", $sql);
        return FALSE;
	}
}
function get_driver_days_worked_excluding_assignment_day($driver_id, $start_date, $end_date, $assignment_day){
	$safe_driver_id = mysql_real_escape_string($driver_id);
	$safe_start_date = mysql_real_escape_string($start_date);
	$safe_end_date = mysql_real_escape_string($end_date);
	$safe_assignment_day = mysql_real_escape_string($assignment_day);
	
	$sql  = "SELECT COUNT(*) FROM (SELECT * FROM ((SELECT DATE(DesiredArrivalTime) AS date FROM link WHERE AssignedDriverUserID = $safe_driver_id) UNION (SELECT DATE(DesiredArrivalTime) AS date  FROM link_history WHERE DriverUserID = $safe_driver_id)) AS t1 WHERE date BETWEEN '$safe_start_date' AND '$safe_end_date' and date not like '$safe_assignment_day%' GROUP BY date) AS t2";
	//echo $sql;
	$result = mysql_query($sql);
	
	if($result){
		$result = mysql_fetch_array($result);
		return $result[0];
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get driver days worked", $sql);
        return FALSE;
	}
}


//$default_allowed_break = the amount of time a driver is allowed to count as clocked hours between rides
function get_driver_hours_worked($driver_id, $start_date, $end_date, $default_allowed_break = 30){
	$safe_driver_id = mysql_real_escape_string($driver_id);
	$safe_start_date = mysql_real_escape_string($start_date);
	$safe_end_date = mysql_real_escape_string($end_date);
	
	$sql  = "SELECT * FROM ( (SELECT LinkID, DesiredArrivalTime, PrePadding, PostPadding, EstimatedMinutes FROM link WHERE AssignedDriverUserID = $driver_id) UNION (SELECT LinkID, DesiredArrivalTime, PrePadding, PostPadding, EstimatedMinutes  FROM link_history WHERE DriverUserID = $driver_id) ) as t1 WHERE DATE(DesiredArrivalTime) >= '$safe_start_date' AND DATE(DesiredArrivalTime) <= '$safe_end_date' ORDER BY DesiredArrivalTime";
	$result = mysql_query($sql);
	
	if($result){
		$links = array();
		while($row = mysql_fetch_array($result)){
			$links[] = array( date( "Y-m-d H:i:s", strtotime($row['DesiredArrivalTime'] . " - " . ($row['PrePadding'] + $row['PostPadding'] + $row['EstimatedMinutes']) . " minutes")) ,
							  $row['DesiredArrivalTime'], 
							  $row['PrePadding'], 
							  $row['PostPadding'], 
							  $row['EstimatedMinutes']);
		}
		
		$total_time = strtotime($links[0][1]) - strtotime($links[0][0]);
		$start_range = $links[0][1];
		$end_range = $links[0][1];
		
		if(count($links) < 2)
			return $total_time / 60 / 60;
		
		for($i = 1; $i < count($links); $i++){
			
			if(((strtotime($links[$i][0]) - strtotime($end_range)) / 60) > $default_allowed_break){// if time is more then default
				$total_time += strtotime($end_range) - strtotime($start_range);
				$start_range = $links[$i][0];
				$end_range = $links[$i][1];
			} else {                										// if time is less then default
				$end_range = $links[$i][1];
			}
				
		}

		$total_time += strtotime($end_range) - strtotime($start_range);
		return $total_time / 60 / 60;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get driver hours worked", $sql);
        return FALSE;
	}
}

function driver_reached_max_availability($driver_id, $on_date = NULL){
	$safe_driver_id = mysql_real_escape_string($driver_id);
	$sql = "SELECT HoursPerTime, DaysPerTime, HoursTimeUnit, DaysTimeUnit FROM driver_settings WHERE UserID = $safe_driver_id LIMIT 1;";

	$result = mysql_query($sql);
	//echo $on_date;
	if($result && mysql_num_rows($result) == 1){
		$result = mysql_fetch_array($result);
		if($result['DaysTimeUnit'] == 'Week'){
			$day_of_week = date("w", strtotime($on_date));
			$start_date = date("Y-n-j", strtotime(date("Y-n-j", strtotime($on_date)) . " -$day_of_week days"));
			$end_date = date("Y-n-j",strtotime(date("Y-n-j", strtotime($on_date)) . " +" . (6 - $day_of_week) . " days"));
						//echo 'drma'.$driver_id.'|'.$start_date . ' ' . $end_date.'|';
		} else if($result['DaysTimeUnit'] == 'Month'){
			$start_date = date("Y-n-1");
			$end_date = date("Y-n-") . get_days_in_month(date("n"),date("Y"));
		} else {
			$start_date = date("Y-1-1");
			$end_date = date("Y-12-31");
		}
		$days = get_driver_days_worked_excluding_assignment_day($driver_id, $start_date, $end_date, date("Y-n-j", strtotime($on_date)));
		//echo $days.'-'.$result['DaysPerTime'].'-----------';
		
		$days_good = false;
		if(($on_date !== NULL && ($days >= $result['DaysPerTime']) && strtotime($on_date) > strtotime(date("Y-n-j"))) || ($on_date === NULL && $result['DaysPerTime'] <= $days)) {
			$days_good = true;
			//echo 'good';
		} else {
		  //echo 'bad';
		}
		
			
			
		if($result['HoursTimeUnit'] == 'Day'){
			$start_date = date("Y-n-j");
			$end_date = date("Y-n-j");
		} else if($result['HoursTimeUnit'] == 'Week'){
			$day_of_week = date("w", strtotime($on_date));
			$start_date = date("Y-n-j", strtotime(date("Y-n-j", strtotime($on_date)) . " -$day_of_week days"));
			$end_date = date("Y-n-j",strtotime(date("Y-n-j", strtotime($on_date)) . " +" . (6 - $day_of_week) . " days"));
			$num_hours = get_driver_hours_worked($driver_id, $start_date, $end_date);
			//echo $driver_id.'|'.$start_date. ' ' .$end_date.'|'.$num_hours.';';
		} else {
			$start_date = date("Y-n-1");
			$end_date = date("Y-n-") . get_days_in_month(date("n"),date("Y"));
		}
		$hours_good = false;
		if($result['HoursPerTime'] <= get_driver_hours_worked($driver_id, $start_date, $end_date)) {
			$hours_good = true;
		} else {
		    //echo 'bad2';
		}
		
		if ($days_good || $hours_good) {
		  return TRUE;
		} else {
		  return FALSE;
		}
	}
	return false;
}
?>
