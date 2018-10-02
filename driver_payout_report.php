<?php
require_once('include/user.php');
require_once('include/driver.php');
require_once('include/date_time.php');

redirect_if_not_logged_in();

$franchise = get_current_user_franchise();

if(!current_user_has_role(1, "FullAdmin") && !current_user_has_role($franchise, "Franchisee")){
	header("Location: home.php");
	die();	
}

$year = isset($_GET['Year']) ? $_GET['Year'] : date("Y");
$month = isset($_GET['Month']) && $_GET['Month'] != -1 ? $_GET['Month'] : null;

require_once('include/header.php');
?>
<br>
<h2>Driver Payout Reports</h2>
<form>
	View reports for year 
	<select name="Month" onchange="this.getParent().submit();">
		<option value="-1">All</option>
    	<?php 
    		$months = array('January', 'February', 'March', 'April', 'May', 'June',
                    'July', 'August', 'September', 'October', 'November', 'December');
			for($i = 0; $i < count($months); $i++){
				echo "<option value='" . ($i + 1) . "' ";
					if($_GET['Month'] == $i + 1)
						echo "SELECTED";
				echo ">{$months[$i]}</option>";
				
			}
		?>
    </select>
    <select name="Year" onchange="this.getParent().submit();">
    	<?php 
			for($i = 2010; $i <= date("Y"); $i++){
				echo "<option value ='$i' ";
					if((!isset($_GET['Year']) && $i == date("Y")) ||( isset($_GET['Year']) && $_GET['Year'] == $i))
						echo "SELECTED";
				echo ">$i</option>";
				
			}
		?>
    </select>
</form>
<br>
<table id="sortabletable" class="sortable" width="100%" border="1">
	<tr>
		<td>Rank</td>
		<td>Name</td>
		<td>User ID</td>
		
		<td>Ride Time</td>
		<td>Trans. Time</td>
		<td>Total Time</td>
		
		<td>Rides</td>
		<td>Trans. Rides</td>
		<td>Total Rides</td>
		
		<td>Rider Miles</td>
		<td>Trans. Miles</td>
		<td>Total Miles</td>
		
		<td>Payout</td>
		<td>Trans. Payout</td>
		<td>Total Payout</td>
		
		
		<td>Tr/ Ride Ratio</td>
	</tr>
	<?php
		$drivers = get_drivers_for_payout_reports($franchise, $month, $year);
		$rank = 0;
		foreach($drivers as $driver){
		$driver_with_rider = get_driver_with_rider_data($franchise, $driver['UserID'], $month, $year);
		$driver_without_rider = get_driver_without_rider_data($franchise, $driver['UserID'], $month, $year);
		$rank += 1;
	?>
	<tr>
		<td><?php echo $rank; ?></td>
		<td><?php echo get_displayable_person_name_string($driver);?></td>
		<td><?php echo $driver['UserID']; ?></td>
		<td><?php echo round($driver_with_rider['sumMinutes'] / 60, 2); ?></td>
		<td><?php echo round($driver_without_rider['sumMinutes'] / 60, 2); ?></td>
		<td><?php echo round(($driver_with_rider['sumMinutes'] + $driver_without_rider['sumMinutes']) / 60, 2); ?></td>
		<td><?php echo $driver_with_rider['numRides']; ?></td>
		<td><?php echo $driver_without_rider['numRides']; ?></td>
		<td><?php echo $driver['rides']; ?></td>
		<td><?php echo $driver_with_rider['sumDistance']; ?></td>
		<td><?php echo $driver_without_rider['sumDistance']; ?></td>
		<td><?php echo $driver_with_rider['sumDistance'] + $driver_without_rider['sumDistance']; ?></td>
		<td><?php echo format_dollars($driver_with_rider['sumCents']); ?></td>
		<td><?php echo format_dollars($driver_without_rider['sumCents']); ?></td>
		<td><?php echo format_dollars($driver_with_rider['sumCents'] + $driver_without_rider['sumCents']); ?></td>
		<td><?php echo number_format($driver_without_rider['numRides'] / $driver['rides'], 2); ?></td>
	</tr>
	<?php 
		$total_w_donated_minutes += $driver_with_rider['sumMinutes'];
		$total_wo_donated_minutes += $driver_without_rider['sumMinutes'];
		$total_donated_minutes += $driver_with_rider['sumMinutes'] + $driver_without_rider['sumMinutes'];
	
		$total_rides_transition += $driver['rides'];
		$total_w_rides += $driver_with_rider['numRides'];
		$total_w_distance += $driver_with_rider['sumDistance'];
		$total_w_cents += $driver_with_rider['sumCents'];
		$total_wo_rides += $driver_without_rider['numRides'];
		$total_wo_distance += $driver_without_rider['sumDistance'];
		$total_wo_cents += $driver_without_rider['sumCents'];
		$total_distance += $driver_with_rider['sumDistance'] + $driver_without_rider['sumDistance'];
		$total_cents += $driver_with_rider['sumCents'] + $driver_without_rider['sumCents'];
		$avg_ratio = ($avg_ratio + ($driver_without_rider['numRides'] / $driver['rides'])) / 2;
		}	
		
	?>
	<tr class="sortbottom">
		<td colspan="3">Totals</td>
		<td><?php echo round($total_w_donated_minutes / 60, 2); ?></td>
		<td><?php echo round($total_wo_donated_minutes / 60, 2); ?></td>
		<td><?php echo round($total_donated_minutes /60, 2); ?></td>
		<td><?php echo $total_w_rides; ?></td>
		<td><?php echo $total_wo_rides; ?></td>
		<td><?php echo $total_rides_transition; ?></td>
		<td><?php echo $total_w_distance; ?></td>
		<td><?php echo $total_wo_distance; ?></td>
		<td><?php echo $total_distance; ?></td>
		<td><?php echo format_dollars($total_w_cents); ?></td>
		<td><?php echo format_dollars($total_wo_cents); ?></td>
		<td><?php echo format_dollars($total_cents); ?></td>
		<td><?php echo number_format($avg_ratio, 2); ?></td>
	</tr>
</table>
<?php
require_once('include/footer.php');
?>