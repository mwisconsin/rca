<?php
    include_once 'include/user.php';
	include_once 'include/date_time.php';
	require_once 'include/franchise.php';
	redirect_if_not_logged_in();
	$franchise = get_current_user_franchise();
	
	
	
	
	$driver_id = get_user_driver_id( get_affected_user_id() );
	
	if(isset($_POST['FromMonth'])){
		$to = mysql_real_escape_string($_POST['ToYear']) . '-' . mysql_real_escape_string($_POST['ToMonth']) . '-' . mysql_real_escape_string($_POST['ToDay']);
		$from = mysql_real_escape_string($_POST['FromYear']) . '-' . mysql_real_escape_string($_POST['FromMonth']) . '-' . mysql_real_escape_string($_POST['FromDay']);
		$whereClause = " AND `DesiredArrivalTime` >= '$from' AND `DesiredArrivalTime` <= '$to 23:59:59'";
	} else if(isset($_REQUEST['Year']) && !isset($_REQUEST['Month']) && !isset($_REQUEST['Day'])){
		$year = mysql_real_escape_string($_REQUEST['Year']);
		$whereClause = " AND `DesiredArrivalTime` >= '$year-1-1' AND `DesiredArrivalTime` <= '$year-12-31 23:59:59";
	} else if(isset($_REQUEST['Year']) && isset($_REQUEST['Month']) && !isset($_REQUEST['Day'])){
		$year = mysql_real_escape_string($_REQUEST['Year']);
		$month = mysql_real_escape_string($_REQUEST['Month']);
		$days = get_days_in_month($month,$year);
		$whereClause = " AND `DesiredArrivalTime` >= '$year-$month-1' AND `DesiredArrivalTime` <= '$year-$month-$days 23:59:59'";
	}
	$sql = "SELECT 
				(SELECT Count(*) FROM `link_history` WHERE `LinkStatus` = 'COMPLETE'$whereClause) AS \"Completed\",
				(SELECT Count(*) FROM `link_history` WHERE `LinkStatus` = 'CANCELEDLATE'$whereClause) AS \"LateCanceled\",
				(SELECT Count(*) FROM `link` Where 1$whereClause) AS \"FutureLinks\",
				(SELECT AVG(QuotedCents) FROM link_history WHERE `LinkStatus` = 'COMPLETE'$whereClause) AS \"AveragePrice\",
				(SELECT SUM(QuotedCents) FROM link_history WHERE `LinkStatus` = 'COMPLETE'$whereClause) AS \"RideRevenue\",
				(SELECT COUNT( DISTINCT RiderUserID ) FROM `link_history` WHERE `LinkStatus` = 'COMPLETE'$whereClause) AS \"PayingRiders\"";
	$results = mysql_fetch_array( mysql_query( $sql ) );
	$months = array('January','February','March','April','May','June','July','August','September','October','November','December');
    include_once 'include/header.php';

?>
<center><h2>Driver Reports</h2></center>

<br>
By: <a href="?by=range">Range</a> | <a href="?by=month">Month</a> | <a href="?by=year">Year</a> | Or <a href="">View All</a>
<br>
<br>
<form action="<?php echo $_SERVER['PHP_SELF'] . '?by=' . $_REQUEST['by']; ?>" method="post">
	<?php
		if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee')){?>
		Driver: <select name="driverid">
			<?php
				$driver_list = get_admin_work_as_driver_list($franchise, $_SESSION['ASORTORDER']);
				
				foreach ($driver_list as $driver_entry) {
					echo "<option value=\"{$driver_entry['UserID']}\"";
					if($_REQUEST['driverid'])
						echo ' SELECTED';
					echo ">{$driver_entry['FirstName']} {$driver_entry['LastName']}</option>";
				}
			?>
		</select>
		<br>
		<br>
	<?php } ?>
	
	<?php if($_REQUEST['by'] == 'range'){ ?>
		From: 
		<select name="FromMonth">
			<?php
				$months = array('January','February','March','April','May','June','July','August','September','October','November','December');
				
				foreach($months as $k => $v){
					echo "<option value=\"" . ($k + 1) . "\"";
					
					if((isset($_POST['FromMonth']) && $_POST['FromMonth'] == ($k + 1)) || (!isset($_POST['FromMonth']) && date("n") == ($k + 1)))
						echo ' SELECTED';
					echo ">$v</option>";
				}
			?>
			
			
		</select> / 
		<select name="FromDay">
		<?php
			for($i = 1; $i <= 31; $i++){
				echo '<option value="' . $i . '"';
				if((isset($_POST['FromDay']) && $_POST['FromDay'] == $i) || (!isset($_POST['FromDay']) && date("d") == $i))
					echo 'SELECTED';
				echo '>' . $i . '</option>';
			}
		?>
		</select> / 
		<select name="FromYear">
		<?php
			for($i = (int)date("Y"); $i >= (int)date("Y") - 5; $i--){
				echo '<option value="' . $i . '"';
				if((isset($_POST['FromYear']) && $_POST['FromYear'] == $i) || (!isset($_POST['FromYear']) && date("d") == $i))
					echo 'SELECTED';
				echo '>' . $i . '</option>';
			}
		?>
		</select>
		 To:
		 <select name="ToMonth">
			<?php
				$months = array('January','February','March','April','May','June','July','August','September','October','November','December');
				
				foreach($months as $k => $v){
					echo "<option value=\"" . ($k + 1) . "\"";
					
					if((isset($_POST['ToMonth']) && $_POST['ToMonth'] == ($k + 1)) || (!isset($_POST['ToMonth']) && date("n") == ($k + 1)))
						echo ' SELECTED';
					echo ">$v</option>";
				}
			?>
			
			
		</select> / 
		<select name="ToDay">
		<?php
			for($i = 1; $i <= 31; $i++){
				echo '<option value="' . $i . '"';
				if((isset($_POST['ToDay']) && $_POST['ToDay'] == $i) || (!isset($_POST['ToDay']) && date("d") == $i))
					echo 'SELECTED';
				echo '>' . $i . '</option>';
			}
		?>
		</select> / 
		<select name="ToYear">
		<?php
			for($i = (int)date("Y"); $i >= (int)date("Y") - 5; $i--){
				echo '<option value="' . $i . '"';
				if((isset($_POST['ToYear']) && $_POST['ToYear'] == $i) || (!isset($_POST['ToYear']) && date("d") == $i))
					echo 'SELECTED';
				echo '>' . $i . '</option>';
			}
		?>
		</select>
	<?php } else if($_REQUEST['by'] == 'year'){ ?>
		Year: 
		<select name="Year">
			<?php
				for($i = 2000; $i <= date("Y") + 1; $i++){ ?>
				<option value="<?php echo $i; ?>" <?php if((isset($_REQUEST['Year']) && $i == $_REQUEST['Year']) || (!isset($_REQUEST['Year']) && $i == date("Y"))) echo "SELECTED"; ?>>
					<?php echo $i; ?>
				</option>
			<?php
				}
			?>
		</select>
		
	<?php } else if($_REQUEST['by'] == 'month'){ ?>
		Month:
		<select name="Month">
			<?php
				for($i = 1; $i <= 12; $i++){ ?>
				<option value="<?php echo $i; ?>" <?php if((isset($_REQUEST['Month']) && $i == $_REQUEST['Month']) || (!isset($_REQUEST['Month']) && $i == date("n"))) echo "SELECTED"; ?>>
					<?php echo $months[$i - 1]; ?>
				</option>
			<?php
				}
			?>
		</select> 
		
		Year: 
		<select name="Year">
			<?php
				for($i = 2000; $i <= date("Y") + 1; $i++){ ?>
				<option value="<?php echo $i; ?>" <?php if((isset($_REQUEST['Year']) && $i == $_REQUEST['Year']) || (!isset($_REQUEST['Year']) && $i == date("Y"))) echo "SELECTED"; ?>>
					<?php echo $i; ?>
				</option>
			<?php
				}
			?>
		</select>
	<?php } ?>
	<input type="submit" value="Find">
</form>
<br>
<center>
	<span style="font-size:1.3em;">
		<?php
			
			if($_REQUEST['by'] == 'range')
				echo "{$_POST['FromMonth']}/{$_POST['FromDay']}/{$_POST['FromYear']} - {$_POST['FromMonth']}/{$_POST['ToDay']}/{$_POST['ToYear']}";
		?>
	</span>
</center>
