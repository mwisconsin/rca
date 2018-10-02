<?php
include_once 'include/user.php';
include_once 'include/scheduling_lockout.php';

redirect_if_not_logged_in();

$franchise = get_current_user_franchise();

if(!current_user_has_role(1 , "FullAdmin") && !current_user_has_role($franchise, "Franchisee")){
	header("Location: home.php");
	die();	
}

if($_POST['NewLockoutTime']){
	if($_POST['AM_PM']['Time'][0] == "AM" && $_POST['hour']['Time'][0] == 12)
		$_POST['hour']['Time'][0] = "0";
	else if($_POST['AM_PM']['Time'][0] == "PM" && $_POST['hour']['Time'][0] < 12)
		$_POST['hour']['Time'][0] += 12;
		
	if($_POST['AM_PM']['Time'][1] == "AM" && $_POST['hour']['Time'][1] == 12)
		$_POST['hour']['Time'][1] = "0";
	else if($_POST['AM_PM']['Time'][1] == "PM" && $_POST['hour']['Time'][1] < 12)
		$_POST['hour']['Time'][1] += 12;
		
		
	if($_POST['RangeType'] == "WEEKLY"){
		if($_POST['LockoutType'] == "DAYAFTER"){
			$_POST['EndDay'] = $_POST['StartDay'];
			$_POST['hour']['Time'][1] = 23;
			$_POST['minute']['Time'][1] = 59;
		}
		$StartDate = date("Y-n-j") . " {$_POST['hour']['Time'][0]}:{$_POST['minute']['Time'][0]}:00";
		$EndDate = date("Y-n-j") . " {$_POST['hour']['Time'][1]}:{$_POST['minute']['Time'][1]}:59";
	} else {
		$StartDate = "{$_POST['1Year']}-{$_POST['1Month']}-{$_POST['1Day']} {$_POST['hour']['Time'][0]}:{$_POST['minute']['Time'][0]}:00";
		$EndDate = "{$_POST['2Year']}-{$_POST['2Month']}-{$_POST['2Day']} {$_POST['hour']['Time'][1]}:{$_POST['minute']['Time'][1]}:59";
	}
	$result = add_scheduling_lockout_time($franchise, $_POST['LockoutType'], $_POST['RangeType'], $StartDate, $EndDate, $_POST['StartDay'], $_POST['EndDay']);
	if($result)
		$announce = "New lockout time added.<br>";
	else
		$announce = "A problem occurred when trying to add a new lockout time.<br>";
}
if($_POST['ScheduleLockout']){
	foreach(array_keys($_POST['ScheduleLockout']) as $lock){
	
		if(delete_scheduling_lockout_time($lock))
			$announce .= "scheduling lockout $lock has been deleted.<br>";
		else
			$announce .= "scheduling lockout $lock failed to delete.<br>";
	}
}
include_once 'include/header.php';?>
<h2>Scheduling Lockout</h2>
<?php if($announce){ echo "<div class=\"reminder\">" . $announce . "</div>"; } ?>
<form method="post">
	<table>
		<tr>
			<td>Range Type</td>
			<td>
				<select name="RangeType" id="RangeType" onchange="change_start_and_end_times();">
					<option value="RANGE">Once</option>
					<option value="WEEKLY">Weekly</option>
					<option value="RANGEYEARLY">Recurring Yearly</option>
				</select>
			</td>
		</tr>
		<tr>
			<td>Lockout Type</td>
			<td>
				<select name="LockoutType" id="LockoutType" onchange="change_start_and_end_times()">
					<option value="DURING">During Time</option>
					<option value="DAYAFTER">Day After Time</option>
				</select>
			</td>
		</tr>
		<tr>
			<td>Start Day/Time</td>
			<td id="StartTime"></td>
		</tr>
		<tr>
			<td>End Day/Time</td>
			<td id="EndTime"></td>
		</tr>
		<tr>
			<td colspan="2"><input type="submit" value="Save" name="NewLockoutTime"></td>
		</tr>
	</table>
</form>
<form method="post">
	<table border="1" width="900px">
		<tr>
			<th>Range Type</th>
			<th>Type</th>
			<th>Start Date</th>
			<th>End Date</th>
			<th>Action</th>
		</tr>
		<?php
		$lockout_times = get_scheduling_lockout_times($franchise);
		foreach($lockout_times as $t){
		?>
		<tr>
			<td><?php echo $t['RangeType']; ?></td>
			<td><?php echo $t['LockoutType']; ?></td>
			<td><?php echo $t['StartString']; ?></td>
			<td><?php echo $t['EndString']; ?></td>
			<td><input type="submit" value="Delete" name="ScheduleLockout[<?php echo $t['SchedulingLockoutID']; ?>]"</td>
		</tr>
		<?php
		} ?>
	</table>
</form>
<script type="text/javascript">
	function change_start_and_end_times(){
		if($('RangeType').value == "RANGE" || $('RangeType').value == "RANGEYEARLY"){
			$('StartTime').innerHTML = '<?php get_date_drop_downs('1'); echo " " . get_time_selector('Time',0);?>';
			$('EndTime').innerHTML = '<?php get_date_drop_downs('2'); echo " " . get_time_selector('Time',1);?>';
		} else {
			$('StartTime').innerHTML = '<?php echo get_weekly_time_selector(true); echo " " . get_time_selector('Time',0); ?>';
			if($('LockoutType').value == "DURING")
				$('EndTime').innerHTML = '<?php echo get_weekly_time_selector(false); echo " " . get_time_selector('Time', 1); ?>';
			else
				$('EndTime').innerHTML = "";
		}
	}
	change_start_and_end_times();
</script>
<?php
	function get_weekly_time_selector($start = TRUE){
		if($start){
			$rtn = "<select name=\"StartDay\">";
			$rtn .= "<option value=\"0\">Sunday</option>";
			$rtn .= "<option value=\"1\">Monday</option>";
			$rtn .= "<option value=\"2\">Tuesday</option>";
			$rtn .= "<option value=\"3\">Wednesday</option>";
			$rtn .= "<option value=\"4\">Thursday</option>";
			$rtn .= "<option value=\"5\">Friday</option>";
			$rtn .= "<option value=\"6\">Saturday</option>";
			$rtn .= "</select>";
		} else {
			$rtn = "<select name=\"EndDay\">";
			$rtn .= "<option value=\"0\">Sunday</option>";
			$rtn .= "<option value=\"1\">Monday</option>";
			$rtn .= "<option value=\"2\">Tuesday</option>";
			$rtn .= "<option value=\"3\">Wednesday</option>";
			$rtn .= "<option value=\"4\">Thursday</option>";
			$rtn .= "<option value=\"5\">Friday</option>";
			$rtn .= "<option value=\"6\">Saturday</option>";
			$rtn .= "</select>";
		}
		return $rtn;
	}

include_once 'include/footer.php';
?>