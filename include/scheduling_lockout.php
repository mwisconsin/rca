<?php
include_once 'include/date_time.php';

$weekday = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
function get_scheduling_lockout_times($franchise){
	$franchise = mysql_real_escape_string($franchise);
	$sql = "SELECT * FROM scheduling_lockout WHERE FranchiseID = $franchise";
	$result = mysql_query($sql);
	
	if($result){
		$rtn = array();
		while($row = mysql_fetch_array($result))
			$rtn[] = scheduling_lockout_toString($row);
		return $rtn;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Could not get scheduling lockout times", $sql);
        return FALSE;
	}
}

function scheduling_lockout_toString($lockout){
	global $weekday;
	if($lockout['RangeType'] == 'RANGE'){
		$lockout['StartString'] = format_date($lockout['StartDate'], "Y/n/j g:i a");
		$lockout['EndString'] = format_date($lockout['EndDate'], "Y/n/j g:i a");
	} else if($lockout['RangeType'] == 'WEEKLY'){
		$lockout['StartString'] = $weekday[$lockout['StartDay']] . " " . format_date($lockout['StartDate'], "g:i a");
		$lockout['EndString'] = $weekday[$lockout['EndDay']] . " " . format_date($lockout['EndDate'], "g:i a");
	} else {
		$lockout['StartString'] = format_date($lockout['StartDate'], "%%%%/n/j g:i a");
		$lockout['EndString'] = format_date($lockout['EndDate'], "%%%%/n/j g:i a");
	}
	return $lockout;
}

function next_schedulable_date($date){
	$sql = "";
}

function add_scheduling_lockout_time($FranchiseID, $LockoutType, $RangeType, $StartDate, $EndDate, $StartDay = NULL, $EndDay = NULL){
	$safe_franchise = mysql_real_escape_string($FranchiseID);
	$safe_lockout = mysql_real_escape_string($LockoutType);
	$safe_range = mysql_real_escape_string($RangeType);
	$safe_start = mysql_real_escape_string($StartDate);
	$safe_end = mysql_real_escape_string($EndDate);
	$safe_start_day = $StartDay !== NULL ? "'" . mysql_real_escape_string($StartDay) . "'" : 'NULL';
	$safe_end_day = $EndDay !== NULL ? "'" . mysql_real_escape_string($EndDay) . "'" : 'NULL';
	
	$sql = "INSERT INTO `scheduling_lockout` (`SchedulingLockoutID` ,`FranchiseID` ,`LockoutType` ,`RangeType` ,
												`StartDate` ,`EndDate` ,`StartDay` ,`EndDay`)
									  VALUES (NULL , $safe_franchise, '$safe_lockout', '$safe_range', 
											  '$safe_start', '$safe_end', $safe_start_day, $safe_end_day);";
	$result = mysql_query($sql);
	
	if($result){
		return true;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Could not add scheduling lockout times", $sql);
		return false;
	}
}

function delete_scheduling_lockout_time($lockout_id){
	$safe_lockout_id = mysql_real_escape_string($lockout_id);
	$sql = "DELETE FROM `scheduling_lockout` WHERE `SchedulingLockoutID` = $safe_lockout_id";
	$result = mysql_query($sql);
	
	if($result){
		return true;
	} else {
		return false;
	}
}

/********************************************************************************/
//	check_scheduable_date
//
//	Block out specific static days that have been entered into the database (i.e. holidays)
//	Otherwise: Minimum date that can be scheduled is the next business day after
//  	"today's" morning.  So, Tuesday can be scheduled up to 11:59am on Monday.  After that point,
//    the next business day is Wednesday.
//
//	Returns:  array( 
//		RESULT => true/false is $date_of_ride a good date to schedule
//		NextDate => next date that would be available for scheduling (Y-n-j)
//		PreviousDate => earliest date prior to lockout area (Y-n-j)
//
//	5/3:  Updated for special logic for Friday afternoons
//  3/30/17: Updated for variable cutoff hour because some riders are lazy and need to be
//           taught a lesson.
//
//	Note:  This is documentation.  Isn't it great?? -- Thomas
//
/********************************************************************************/

function check_scheduable_date($date_scheduling, $date_of_ride, $verify_times = true, $userid = -1){
	//echo "check_scheduable_date $date_scheduling, $date_of_ride, $verify_times<BR>";
	// If date_scheduling >= date_of_ride return false and use fillers for Next and Previous dates.
	if( strtotime($date_scheduling) > strtotime($date_of_ride) 
		 ) {
		return array('RESULT' => FALSE, 
					 'NextDate' => date("Y-n-j", strtotime($date_scheduling) + ( 24 * 60 * 60)), 
					 'PreviousDate' =>  date("Y-n-j", strtotime($date_scheduling)));
		}
		
	$dstime = strtotime($date_scheduling);
	$dortime = strtotime($date_of_ride);
	//echo $date_of_ride."<BR>";
	$target_dortime = strtotime($date_of_ride.' +1 day');  // general assumption: the next day
	$target_prev_dortime = strtotime($date_of_ride.' -1 day'); // general assumption: the prior day

	$cutoff_hour = 12;
	$sql = "select Cutoff_Hour from users where UserID = $userid";
	$r = mysql_query($sql);
	if(mysql_num_rows($r) > 0) {
		$rs = mysql_fetch_array($r);
		$cutoff_hour = $rs["Cutoff_Hour"];
	}

	// If the date_scheduling time is after cutoff_hour (default 12pm), advance the date of ride a day
	if(date('G',$dstime) >= $cutoff_hour && $dortime - $dstime < 86400) 
		$target_dortime = strtotime(date('Y-m-d',$target_dortime).' +1 day');
		
	// special circumstance:  If it's Friday after cutoff hour (default 12pm), scheduling personnell won't see anything
	// scheduled until Monday, which will be too late.  So, the earliest rides that can be scheduled
	// on Friday afternoon are Tuesday.
	if(date('G',$dstime) >= $cutoff_hour && date('D',$dstime) == 'Fri')
		$target_dortime = strtotime(date('Y-m-d',$target_dortime).' +2 days');

	$holidays = array();
	$sql = "select d from holidays";
	$r = mysql_query($sql);
	while($rs = mysql_fetch_array($r)) { $holidays[] = $rs["d"]; }
	
	// While the date we're going to return is a holiday, advance that date 1 day
	while( in_array( date('Y-m-d',$target_dortime), $holidays ) )
			$target_dortime = strtotime(date('Y-m-d',$target_dortime).' +1 Weekday');	 	
			
	// While the date we're going to return is a holiday, reduce that date 1 day
	while( in_array( date('Y-m-d',$target_prev_dortime), $holidays ) )
			$target_prev_dortime = strtotime(date('Y-m-d',$target_prev_dortime).' -1 Weekday');	 
	//echo date('Y-n-j',$target_prev_dortime);
	$return = array("RESULT" => TRUE, 
					'NextDate' => date("Y-n-j", $target_dortime ), 
					'PreviousDate' => date("Y-n-j", $target_prev_dortime ) );
	//print_r($return);				
	return $return;

}
