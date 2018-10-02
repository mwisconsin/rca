<?php

function add_repeat_date($beginning_date, $length, $ending_date, $week_days = array(), $repeat_type, $repeat_value){
	$safe_beginning_date = mysql_real_escape_string($beginning_date);
	$safe_ending_date = $ending_date == NULL ? "NULL" : "'" . mysql_real_escape_string($ending_date) . "'";
	$safe_length = mysql_real_escape_string($length);
	$safe_week_days = 0;
	foreach($week_days as $day){
		if($day	== 'SUNDAY')
			$safe_week_days += 1;
		else if($day == 'MONDAY')
			$safe_week_days += 2;
		else if($day == 'TUESDAY')
			$safe_week_days += 4;
		else if($day == 'WEDNESDAY')
			$safe_week_days += 8;
		else if($day == 'THURSDAY')
			$safe_week_days += 16;
		else if($day == 'FRIDAY')
			$safe_week_days += 32;
		else if($day == 'SATURDAY')
			$safe_week_days += 64;
	}
	$safe_repeat_type = mysql_real_escape_string($repeat_type);
	$safe_repeat_value = mysql_real_escape_string($repeat_value);
	
	$sql = "INSERT INTO `date` (`DateID`, `BeginningDate`, `Length`, `EndingDate`, `WeekDay`, `RepeatType`, `RepeatNum`) 
						VALUES (NULL, '$safe_beginning_date', '$safe_length', $safe_ending_date, '$safe_week_days', 
								'$safe_repeat_type', '$safe_repeat_value');";
	$result = mysql_query($sql);
	
	if($result){
		return mysql_insert_id();	
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error creating recurring date", $sql);
        return FALSE;
	}
}

function get_repeat_date($date_id){
	$safe_date_id = mysql_real_escape_string($date_id);
	
	$sql = "SELECT * FROM `date` WHERE `DateID` = $safe_date_id LIMIT 1;";
	$result = mysql_query($sql);
	
	if($result){
		if(mysql_num_rows($result) > 0)
			return mysql_fetch_array($result);
		return FALSE;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error getting recurring date", $sql);
        return FALSE;
	}
}

function delete_repeat_date($date_id){
	$safe_date_id = mysql_real_escape_string($date_id);
	
	$sql = "DELETE FROM `date` WHERE `DateID` = $safe_date_id;";
	$result = mysql_query($sql);
	
	if($result){
		return TRUE;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error deleting repeat date", $sql);
        return FALSE;
	}
}

function get_date_days($day_int){
	$days = array();
	if($day_int - 64 >= 0){
		$day_int -= 64;
		array_push($days, "SATURDAY");	
	} else if($day_int - 32 >= 0){
		$day_int -= 32;
		array_push($days, "FRIDAY");	
	} else if($day_int - 16 >= 0){
		$day_int -= 16;
		array_push($days, "THURSDAY");	
	} else if($day_int - 8 >= 0){
		$day_int -= 8;
		array_push($days, "WEDNESDAY");	
	} else if($day_int - 4 >= 0){
		$day_int -= 4;
		array_push($days, "TUESDAY");	
	} else if($day_int - 2 >= 0){
		$day_int -= 2;
		array_push($days, "MONDAY");	
	} else if($day_int - 1 >= 0){
		$day_int -= 1;
		array_push($days, "SUNDAY");	
	}
	return $days;
}

function check_recurring_date($date_row, $date){
	$date_time = strtotime($date);
	$date_day = strtoupper(date("l",$date_time));
	$time = strtotime(date("H:i:s", $date_time));
	
	$weekdays = get_date_days($date_row['WeekDay']);
	$beginning_time = strtotime(date("H:i:s", $date_row['BeginningDate']));
	$ending_time = $beginning_time + (strtotime($date_row['Length']) - strtotime('today'));
	
	if($date_row['RepeatType'] == 'ONCE'){
		if($beginning_time <= $date_time && $ending_time >= $date_time )
			return TRUE;
		return FALSE;
	} else if($date_row['RepeatType'] == 'WEEKLY'){
		if(array_search($date_day, $weekdays) && ($beginning_time <= $time && $ending_time >= $time) )
			return TRUE;
		return FALSE;
	}
	
	return FALSE;
}

function repeat_date_where($date){
	$date = strtotime($date);
	
	$day_of_week = 2 ^ date("w");
	$str = "";
		
	$str .= " (RepeatType = 'ONCE' AND DATE(`BeginningDate`) = DATE('$date')) OR"; // ONCE
	$str .= " (RepeatType = 'WEEKLY' AND WeekDay >= $day_of_week)";
	
	return $str;
}

function repeat_date_to_string($row){
	$arr = array();
	$str = "";
	if($row['RepeatType'] == 'ONCE'){
		$str = date("n/j/Y", strtotime($row['BeginningDate']));
	} else if($row['RepeatType'] == 'WEEKLY'){
		$days = get_date_days($row['WeekDay']);
		foreach($days as $n => $day){
			$str .= $day . ($n + 1 == count($days) ? "" : " ,");
		}
	}
	$arr['Day'] = $str;

	$arr['Time'] = date("h:i:s a", strtotime($row['BeginningDate'])) . " - " . 
				   date("h:i:s a", strtotime($row['BeginningDate']) + (strtotime($row['Length']) - strtotime('today')) );
	return $arr;
}