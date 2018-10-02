<?php
include_once 'include/repeat_date.php';
$post_loaded_weather_time_delay = array();


function get_all_daily_delays($franchise){
	$safe_franchise = mysql_real_escape_string($franchise);
	
	$sql = "SELECT * FROM travel_time_delay LEFT JOIN date ON travel_time_delay.DateID = `date`.DateID WHERE FranchiseID = $safe_franchise AND Type = 'DATE'";
	$result = mysql_query($sql);
	
	if($result){
		$delays = array();
		
		while($row = mysql_fetch_array($result))
			$delays[] = $row;

		return $delays;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get all date delay for franchise $franchise", $sql);
		return FALSE;	
	}
}

function create_daily_delay($franchise, $percent, $date, $length, $weekdays,$occurance, $repeat_occur = 1){
	
	if(!$date = add_repeat_date($date, $length,  NULL, $weekdays, $occurance, $repeat_occur))
		return FALSE;
	
	$safe_date_id = mysql_real_escape_string($date);
	$safe_franchise = mysql_real_escape_string($franchise);
	$safe_percent = mysql_real_escape_string($percent);
	
	$sql = "INSERT INTO `travel_time_delay` (`FranchiseID` , `DateID` , `PercentageDelay`, `Type`)
								VALUES ('$safe_franchise', '$safe_date_id', '$safe_percent', 'DATE' );";
	$result = mysql_query($sql);
	
	if($result){
		return mysql_insert_id();
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not create date delay for date $date and franchise $franchise and date at $date", $sql);
		return FALSE;
	}
}

function get_daily_delay($franchise, $date){
	$safe_franchise = mysql_real_escape_string($franchise);
	
	$date = strtotime($date);
	$safe_date = date("Y-n-j",$date);
	$date_where = repeat_date_where($date);
	
	$sql = "SELECT * FROM `travel_time_delay` LEFT JOIN `date` ON travel_time_delay.DateID = `date`.DateID
			WHERE FranchiseID = $safe_franchise AND $date_where";
	$result = mysql_query($sql);
	$percent = 1;
	if($result){
		while($row = mysql_fetch_array($result)){
			if(check_recurring_date($row, $date) && $row['PercentageDelay'] > $percent){
				$percent = $row['PercentageDelay'];
			}
		}
		return $percent;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not create date delay for date $date and franchise $franchise and date at $date", $sql);
		return FALSE;
	}
}

function delete_daily_percent($franchise_id, $delay_id){
	$safe_delay_id = mysql_real_escape_string($delay_id);
	$safe_franchise_id = mysql_real_escape_string($franchise_id);
	$sql = "DELETE travel_time_delay, `date` FROM `travel_time_delay` LEFT JOIN `date` ON `date`.DateID = `travel_time_delay`.DateID  WHERE `FranchiseID` = $safe_franchise_id AND `DelayID` = $safe_delay_id AND `Type` = 'DATE'";
	$result = mysql_query($sql);
	
	if($result){
		return TRUE;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not delete date delay for delay $delay_id and franchise $franchise", $sql);
		return FALSE;
	}
}

function get_weather_time_delay($franchise, $date){
	if(isset($post_loaded_weather_time_delay[$franchise][$date]))
		return $post_loaded_weather_time_delay[$franchise][$date];
	$safe_franchise = mysql_real_escape_string($franchise);
	$safe_date = mysql_real_escape_string($date);
	
	$sql = "SELECT * FROM `travel_time_delay` LEFT JOIN `date` ON travel_time_delay.DateID = `date`.DateID
			WHERE FranchiseID = $safe_franchise AND BeginningDate = '$safe_date' AND Type = 'WEATHER' LIMIT 1; ";
	$result = mysql_query($sql);
	
	if($result){
		if(mysql_num_rows($result) > 0){
			$t = mysql_fetch_array($result);
			$post_loaded_weather_time_delay[$franchise][$date] = $t['PercentageDelay'];
			return $t['PercentageDelay'];
		}
		return 1;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get weather delay for date $date and franchise $franchise and date at $date", $sql);
		return FALSE;
	}
}

function set_weather_time_delay($franchise, $date, $percent){
	delete_weather_time_delay($franchise, $date);

	if(doubleval($percent) == 1)
		return TRUE;
	
	$safe_franchise = mysql_real_escape_string($franchise);
	$safe_date_id = add_repeat_date($date, '23:59:59', NULL, array(), 'ONCE', 1);
	$safe_percent = mysql_real_escape_string($percent);
	$safe_type = mysql_real_escape_string($type);
	
	$sql = "INSERT INTO `travel_time_delay` (`FranchiseID` , `DateID` , `PercentageDelay`, `Type`)
								VALUES ('$safe_franchise', '$safe_date_id', '$safe_percent', 'WEATHER' );";
	$result = mysql_query($sql);
	
	if($result){
		return TRUE;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not create weather delay for date $date and franchise $franchise_id and delay at $percent", $sql);
		return FALSE;
	}
}

function delete_weather_time_delay($franchise, $date){
	$safe_franchise_id = mysql_real_escape_string($franchise);
	$safe_date = mysql_real_escape_string($date);
	$sql = "DELETE travel_time_delay, `date` FROM `travel_time_delay` LEFT JOIN `date` ON `date`.DateID = `travel_time_delay`.DateID  WHERE `FranchiseID` = $safe_franchise_id AND `BeginningDate` = '$safe_date' AND Type = 'WEATHER'";
	$result = mysql_query($sql) or die(mysql_error());
	
	if($result){
		return TRUE;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not delete weather delay for date $date and franchise $franchise_id ", $sql);
		return FALSE;
	}
}

?>