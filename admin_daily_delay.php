<?php
include_once 'include/user.php';
include_once 'include/time_delay.php';
include_once 'include/repeat_date.php';
include_once 'include/franchise.php';
redirect_if_not_logged_in();

$franchise = get_current_user_franchise();

if(!current_user_has_role(1 , "FullAdmin") && !current_user_has_role($franchise, "Franchisee")){
	header("Location: home.php");
	die();	
}

include_once 'include/header.php';

if($_POST['DeleteDelay']){
	$key = array_keys($_POST['DeleteDelay']);
	$delete_result = delete_daily_percent($franchise, $key[0]);
}

if($_POST['Finish']){
	
	if($_POST['Occurance'] == 'WEEKLY' && !(count($_POST['weekday']) > 0)){
		$errors[] = "You must select at least one day of the week.";
	}
	$_POST['hour']['Start'][0] = ($_POST['AM_PM']['Start'][0] == 'AM' ? $_POST['hour']['Start'][0] : $_POST['hour']['Start'][0] + 12);
	$_POST['hour']['End'][0] = ($_POST['AM_PM']['End'][0] == 'AM' ? $_POST['hour']['End'][0] : $_POST['hour']['End'][0] + 12);
	$time_1 = strtotime("{$_POST['hour']['Start'][0]}:{$_POST['minute']['Start'][0]}:00");
	$time_2 = strtotime("{$_POST['hour']['End'][0]}:{$_POST['minute']['End'][0]}:00");
 	if( $time_2 - $time_1 < 0){
		$errors[] = "You have set a end time before your start time.";
	}
	$length = date("H:i:s",($time_2 - $time_1));
	$date = "{$_POST['Year']}-{$_POST['Month']}-{$_POST['Day']}" . "{$_POST['hour']['Start'][0]}:{$_POST['minute']['Start'][0]}:00";
	$percent = $_POST['PosOrNeg'] * $_POST['Percent'];
	
	if(!$errors){
		$created = create_daily_delay($franchise, $percent, $date, $length, $weekdays,$_POST['Occurance']);
	}
}


?>
<h1>Daily Delay</h1>
Add a new Time Delay
<?php 
	if(is_array($errors)){?>	
	<ul style="color:#C00;">
    	<?php
			foreach($errors as $error)
				echo "<li>$error</li>";
		?>
    </ul>
<?php
    }
	if($delete_result) echo "<div class=\"reminder\">You have successfully deleted the daily delay</div>";
?>
<form method="post">
<table>
	<tr>
    	<td>Occurance</td>
        <td>
        	<select id="OccurSelector" name="Occurance">
            	<option value="ONCE"<?php if($_POST['Occurance'] == "ONCE") echo "SELECTED"; ?>>Once</option>
                <option value="WEEKLY"<?php if($_POST['Occurance'] == "WEEKLY") echo "SELECTED"; ?>>Weekly</option>
            </select>
    </tr>
    <tr id="dateSelect"<?php if($_POST['Occurance'] == 'WEEKLY') echo 'style="display:none;"'; ?>>
    	<td>Date</td>
        <td><?php 
			$date = NULL;
			if($_POST['Month'])
				$date = strtotime($_POST['Year'] . "-" . $_POST['Month'] . "-" . $_POST['Day']);
		get_date_drop_downs('', $date, date("Y") - 1); ?></td>
    </tr>
    <tr id="weekDaySelect"<?php if(!$_POST['Occurance'] || $_POST['Occurance'] == 'ONCE') echo 'style="display:none;"'; ?>>
    	<td colspan="2">
            <label><input type="checkbox" value="SUNDAY" name="weekday[0]"<?php if($_POST['weekday'][0]) echo "CHECKED"; ?>>Sunday</label> | 
            <label><input type="checkbox" value="MONDAY" name="weekday[1]"<?php if($_POST['weekday'][1]) echo "CHECKED"; ?>>Monday</label> | 
            <label><input type="checkbox" value="TUESDAY" name="weekday[2]"<?php if($_POST['weekday'][2]) echo "CHECKED"; ?>>Tuesday</label> | 
            <label><input type="checkbox" value="WEDNESDAY" name="weekday[3]"<?php if($_POST['weekday'][3]) echo "CHECKED"; ?>>Wednesday</label><br>
            <label><input type="checkbox" value="THURSDAY" name="weekday[4]"<?php if($_POST['weekday'][4]) echo "CHECKED"; ?>>Thursday</label> | 
            <label><input type="checkbox" value="FRIDAY" name="weekday[5]"<?php if($_POST['weekday'][5]) echo "CHECKED"; ?>>Friday</label> | 
            <label><input type="checkbox" value="SATURAY" name="weekday[6]"<?php if($_POST['weekday'][6]) echo "CHECKED"; ?>>Saturday</label>
        </td>
    </tr>
    <tr>
    	<td>Start Time</td>
        <td><?php echo  get_time_selector('Start', 0,$_POST['hour']['Start'][0], $_POST['minute']['Start'][0] , $_POST['AM_PM']['Start'][0]); ?></td>
    </tr>
    <tr>
    	<td>End Time</td>
        <td><?php echo  get_time_selector('End', 0,$_POST['hour']['End'][0], $_POST['minute']['End'][0] , $_POST['AM_PM']['End'][0]); ?></td>
    </tr>
    <tr>
    	<td></td>
        <td><label><input type="checkbox" name="AllDay" value="AllDay"<?php if($_POST['AllDay']) echo "CHECKED"; ?>>All Day</label>
    </tr>
    <tr>
    	<td>Travel Time</td>
        <td>
        	<select name="Percent">
            	<?php for($i = 0; $i < 100; $i += 5){ ?>
                <option value="<?php echo $i; ?>" <?php if($i == $_POST['Percent']) echo "SELECTED"; ?>><?php echo $i; ?> %</option>
                <?php } ?>
            </select>
            <select name="PosOrNeg">
            	<option value="1"<?php if($_POST['PosOrNeg'] == "1") echo "SELECTED"; ?>>Increase</option>
                <option value="-1"<?php if($_POST['PosOrNeg'] == "-1") echo "SELECTED"; ?>>Decrease</option>
            </select>
        </td>
    </tr>
    <tr>
    	<td colspan="2" style="text-align:right;"><input type="submit" name="Finish" value="Save"></td>
    </tr>
    
</table>
</form>
<form method="post">
<table width="700px" border="1">
	<tr>
    	<th>Date/Days</th>
        <th>Times</th>
        <th>Delay</th>
        <th>Action</th>
    </tr>
    <?php
		$delays = get_all_daily_delays($franchise);
		foreach($delays as $delay){
			$toString = repeat_date_to_string($delay);
			echo "<td>" . $toString['Day'] . "</td>";
			echo "<td>" . $toString['Time'] . "</td>";
			echo "<td>" . (($delay['PercentageDelay'] > 1) ? (($delay['PercentageDelay'] - 1) * 100) . "% Increase" : ((1 - $delay['PercentageDelay']) * 100) . "% Decrease") . " </td>";
			echo "<td><input type=\"submit\" name=\"DeleteDelay[{$delay['DelayID']}]\" value=\"Delete\"></td>";
		}
	?>
</table>
</form>
<script type="text/javascript">
	$('OccurSelector').addEvent('change', function(){
		if(	$('OccurSelector').value == 'ONCE'){
			$('dateSelect').setStyle('display', '');
			$('weekDaySelect').setStyle('display','none');
		} else {
			$('dateSelect').setStyle('display', 'none');
			$('weekDaySelect').setStyle('display','');
		}
	});

</script>
<?php
include_once 'include/footer.php';
?>