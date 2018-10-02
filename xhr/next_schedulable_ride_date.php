<?php
chdir('..');
include_once 'include/user.php';
include_once 'include/scheduling_lockout.php';
    require_once('include/link.php');
    
if(!is_logged_in())
	die();
$year = $_GET['Year'];
$month = $_GET['Month'];
$day = $_GET['Day'];
$next = $_GET['Direction'] == 'next' ? true : false;
$direction = $next ? ' +1 day' : ' -1 day';
//$earliest_ride = get_next_user_schedulable_link_time();
//if(dates_equal(date("Y-n-j"), $year . "-". $month . "-" . $day) 
//	|| (time() - strtotime($year . "-". $month . "-" . $day) >= (24 * 60 * 60 ))
//	|| ( $direction = ' -1 day' 
//				&& strtotime("{$earliest_ride['Year']}-{$earliest_ride['Month']}-{$earliest_ride['Day']}") >
//					strtotime("{$_GET['Year']}-{$_GET['Month']}-{$_GET['Day']} $direction")
//			)
//	)
//	$allowed = "\"Allowed\": \"FALSE\",";
#echo $year . "-". $month . "-" . $day . $direction."<BR>";
$time = strtotime($year . "-". $month . "-" . $day);

$result = check_scheduable_date(date("Y-n-j H:m"), date("Y-n-j H:m", $time), false);
//print_r($result);
if($next){

		$return_date = strtotime($result['NextDate']);

} else{
	//if(!$result['RESULT'])
		$return_date = strtotime($result['PreviousDate']);

}
//print_r($return_date);
echo "{{$allowed}\"Year\": \"" . date("Y", $return_date) . "\", \"Month\": \"" . date("n", $return_date) . "\", \"Day\": \"" . date("j", $return_date) . "\"}";
?>