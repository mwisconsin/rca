<?php
include_once('include/user.php');
require_once('include/driver.php');
require_once('include/db_driver_availability.php');
require_once('include/date_time.php');

$days_of_week = array('Sunday','Monday','Tuesday','Wednesday',
                      'Thursday','Friday','Saturday');
$hours = array('12:00','12:30','1:00','1:30','2:00','2:30','3:00','3:30','4:00',
               '4:30','5:00','5:30','6:00','6:30','7:00','7:30','8:00','8:30',
               '9:00','9:30','10:00','10:30','11:00','11:30');
$meridiem = array('AM','PM');

redirect_if_not_logged_in();

$franchise_id = get_current_user_franchise();
if(!current_user_has_role(1 , "FullAdmin") && !current_user_has_role($franchise_id, "Franchisee")){
	header("Location: home.php");
	die();	
}



if(isset($_SESSION['driverAvailability']) && !$_POST['Reset']){
$drivers = $_SESSION['driverAvailability'];
} else {
$_SESSION['driverAvailability'] = load_all_available_drivers($franchise_id);
$drivers = $_SESSION['driverAvailability'];
$_SESSION['driverAvailabilityUPATED'] = time();
}
include_once('include/header.php');

?>
<h2>Driver Availability Reports</h2>
<div id='ad' style="float:right; width: 300px;">
	<center>
    	<span style="font-size:1.2em;">Available drivers</span><br>
        <span id="date_string"></span>
    </center>
	<br>
	<table width="100%" border="1px" id="AvailableDrivers">
	</table>
</div>
<form method="post">
This data is up to date since: <?php echo date("n/j/Y g:i a", $_SESSION['driverAvailabilityUPATED']); ?> <input type="submit" name="Reset" value="Reset Now">
</form>
<table cellspacing="0px" id="availability_table" class="availability_table" style="width:500px;" border="1px">
        <tr>
			<th>&nbsp;</th>
			<?php
			for ($i = 0; $i <= count($days_of_week) - 1; $i++) { ?>
                <th><?php echo substr($days_of_week[$i],0,3) ?></th>
			<?php } ?>	
		</tr>

		<?php
		$hour = 0;
		for($i = 0; $i <= 1; $i++)
		{
			
			for($j = 0; $j <= count($hours) - 1; $j++)
			{
				?>
				<tr>
					<td style="width:70px;"><?php echo $hours[$j] . ' ' . $meridiem[$i];?></td>
					<?php
					for($k = 0; $k <= count($days_of_week) - 1; $k++)
					{
						$min = $hour - floor($hour) == .5 ? 1: 0;

                        $slot_value = $drivers[$k][floor($hour)][$min]['num'];
                        
						?>
						<td class="availability_cell" onclick="load_time(<?php echo $k; ?>,<?php echo floor($hour); ?>,<?php echo $min; ?>);">
                            <?php echo $slot_value == '' ? '0': $slot_value; ?>
                        </td>
						<?php
						
					}
					$hour += .5;
					?>				
				</tr>
				<?php
			}
		}
		?>
	</table>
	
<script type="text/javascript">
$drivers = '<?php echo get_json_drivers(); ?>';
$drivers = JSON.decode($drivers);
$table = new HtmlTable($('AvailableDrivers'), {headers: ['UserID', 'Work As User']});
$table.push([{
	content: 'Select A Time',
	properties:{
		colspan: 2
	}
}]);
function load_date_string(day, hour, min){
	$day = ["Sunday",'Monday', "Tuesday","Wednesday", "Thursday", "Friday", "Saturday"];
	$min = ["00", "30"];
	$AM_PM = "am"
	if(hour > 12){
		hour -= 12;
		$AM_PM = "pm";
	}
	$('date_string').innerHTML = $day[day] + " " + hour + ":" + $min[min] +  " " + $AM_PM;
}
function load_time(day,hour,min){
	load_date_string(day, hour, min);
	$table.empty();
	$drivers[day][hour][min].each(function(item){
		$table.push([item, '<a href="/xhr/affected_user_redirect.php?redirect=/account.php&userid=' + item + '">' + load_name(item) + '</a>']);
	});
	window.scrollTo(0,100);
}

function load_name(user_id){
	var newDate = new Date;
	newDate = newDate.getTime();
	
	var jsonRequest = new Request.JSON({url: 'xhr/get_person_name.php', onSuccess: function(person){
    	var date = newDate;
    	$('name' + date).innerHTML = person.namestring;
}}).get({'id': user_id});
	
    return '<span id="name' + newDate + '"></span>';

}
</script>
<?php include_once('include/footer.php');

function get_json_drivers(){
	global $drivers;
	$json = "{";
	for($day = 0; $day < 7; $day++){
		$json .= " \"$day\": {";
		for($hour = 0; $hour < 24; $hour++){
			$json .= " \"$hour\": {";
			for($min = 0; $min < 2; $min++){
				$json .= " \"$min\": ";
				$json .= "[";
				$json .= is_array($drivers[$day][$hour][$min]['drivers']) ? ("\"" . implode("\",\"",$drivers[$day][$hour][$min]['drivers']) . "\"" ): "";
				$json .= "]";
				$json .= "";
				$json .= ($min != 1 ? ',' : '');
			}
			$json .= "}";
			$json .= ($hour != 23 ? ',' : '');
		}
		$json .= "}";
		$json .= ($day != 6 ? ',' : '');
	}
	$json .= " }";
	return $json;
}
?>