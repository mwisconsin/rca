<?php
	include_once 'include/charity_donation.php';
	include_once 'include/user.php';
	include_once 'include/name.php';
	include_once 'include/header.php';
	redirect_if_not_logged_in();
	
	require_once 'include/franchise.php';
	$franchise = get_current_user_franchise();
	
	if(!current_user_has_role(1, 'FullAdmin') && !current_user_has_role($franchise, 'Franchisee')){
		header("Location: home.php");
		die();
	}
	
	$year = isset($_GET['Year']) ? $_GET['Year'] : date("Y");
	
?>
<center><h2>Donation Reports for <?php echo $year; ?></h2></center>

<form>
	View reports for year 
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
<br />

<?php
	$groups = array('FullAdmin', 'Rider', 'Driver', 'Supporter', 'Franchisee', 'VolunteerAdmin', 'CareFacilityAdmin', 'LargeFacilityAdmin', 'SuperUser');
	$group_totals = array();
	$sql = "select UserID from rider where RiderStatus = 'Deceased'";
	$dpr = mysql_query($sql);
	$dead_people = array();
	while($rs = mysql_fetch_array($dpr)) $dead_people[] = $rs["UserID"];
	
	foreach($groups as $group){
	$group_totals[$group] = array();
	$user_donations = get_user_charity_donations($group);
	if(count($user_donations) < 1)
		continue;
	if($group == 'Rider' && in_array($user_donations["SupporterUserID"],$dead_people)) continue;
?>
<h2><?php echo $group . 's'; ?></h2>
<table id="sortabletable" class="sortable" border="1" width="1100px">
	<tr>
		<th>UID</th>
        <th width="300px">Name</th>
        <th>CID</th>
        <th width="300px">Chairty Name</th>
        <th width="80px">Jan</th>
        <th width="80px">Feb</th>
        <th width="80px">Mar</th>
        <th width="80px">Apr</th>
        <th width="80px">May</th>
        <th width="80px">Jun</th>
        <th width="80px">Jul</th>
        <th width="80px">Aug</th>
        <th width="80px">Sep</th>
        <th width="80px">Oct</th>
        <th width="80px">Nov</th>
        <th width="80px">Dec</th>
        <th width="80px">Total</th>
	</tr>
    
    <?php
    	$months = array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');

		$month_totals = array();
		foreach($user_donations as $donation){
			$res = get_users_yearly_donations($donation['SupporterUserID'], $donation['CharityID'], $year);
			if($res["yt"] == 0) continue;
			$monthlydata = get_users_monthly_donations($donation['SupporterUserID'], $donation['CharityID'], $year);
			
			$user_total = 0;
			?>
            <tr>
                <td><?php echo $donation['SupporterUserID']; ?></td>
                <td><?php echo get_displayable_person_name_string(get_user_person_name( $donation['SupporterUserID'] ) ); ?></td>
                <td><?php echo $donation['CharityID']; ?></td>
                <td><?php echo $donation['CharityName']; ?></td>
                <?php
                	$group_totals[$group][$donation['CharityID']]['Name'] = $donation['CharityName'];
                	$group_totals[$group][$donation['CharityID']]['ID'] = $donation['CharityID'];
                	foreach($months as $month){
                		
                		$user_total += $monthlydata[$month];
                		$month_totals[$month] += $monthlydata[$month];
                		$group_totals[$group][$donation['CharityID']][$month] += $monthlydata[$month];
                	?>
                <td><?php  echo format_dollars($monthlydata[$month]); ?></td>
                <?php } ?>
                <td><?php echo format_dollars($user_total); ?></td>
			</tr>
			<?php
		}
	?>
	<tr class="sortbottom">
		<td colspan="4"></td>
		<?php
			$group_total = 0;
			foreach($months as $month){
				$group_total += $month_totals[$month];
				echo "<td>" . format_dollars($month_totals[$month]) . "</td>";
			} 
		?>
		<td><?php echo format_dollars($group_total); ?></td>
	</tr>
</table><br>
<table id="sortabletable" class="sortable" border="1" width="1100px">
	<tr>
		<th>CID</th>
        <th width="300px">Chairty Name</th>
        <th width="80px">Jan</th>
        <th width="80px">Feb</th>
        <th width="80px">Mar</th>
        <th width="80px">Apr</th>
        <th width="80px">May</th>
        <th width="80px">Jun</th>
        <th width="80px">Jul</th>
        <th width="80px">Aug</th>
        <th width="80px">Sep</th>
        <th width="80px">Oct</th>
        <th width="80px">Nov</th>
        <th width="80px">Dec</th>
        <th width="80px">Total</th>
    </tr>
    <?php
    	$subgroup_month_totals = array();
    	foreach($group_totals[$group] as $charity_totals){
    		
 		?>
 		<tr>
 			<td><?php echo $charity_totals['ID']; ?></td>
 			<td><?php echo $charity_totals['Name'] ?></td>
 			<?php foreach($months as $month){
 				$group_totals[$group][$charity_totals['ID']]['Total'] += $charity_totals[$month];
 				$subgroup_month_totals[$month] += $charity_totals[$month];
 			 ?>
 			<td><?php echo format_dollars($charity_totals[$month]); ?></td>
 			<?php }?>
 			<td><?php echo format_dollars($group_totals[$group][$charity_totals['ID']]['Total']); ?></td>
 		</tr>
 		<?php } $total = 0; ?>
 		<tr class="sortbottom">
 			<td colspan="2"></td>
 			<?php foreach($months as $month){ 
 				$total += $subgroup_month_totals[$month];
 			?>
 			<td><?php echo format_dollars($subgroup_month_totals[$month]); ?></td>
 			<?php } ?>
 			<td><?php echo format_dollars($total); ?></td>
 		</tr>
</table><?php } ?>
<?php 
	foreach($groups as $group){
		foreach($group_totals[$group] as $charity){
			$grand_total[$charity['ID']]['ID'] = $charity['ID'];
			$grand_total[$charity['ID']]['Name'] = $charity['Name'];
			foreach($months as $month){
				$grand_total[$charity['ID']][$month] += $charity[$month];
			}
		}
	}
?>
<h2>Group Grand Totals</h2>
<table id="sortabletable" class="sortable" border="1" width="1100px">
	<tr>
		<th>CID</th>
        <th width="300px">Chairty Name</th>
        <th width="80px">Jan</th>
        <th width="80px">Feb</th>
        <th width="80px">Mar</th>
        <th width="80px">Apr</th>
        <th width="80px">May</th>
        <th width="80px">Jun</th>
        <th width="80px">Jul</th>
        <th width="80px">Aug</th>
        <th width="80px">Sep</th>
        <th width="80px">Oct</th>
        <th width="80px">Nov</th>
        <th width="80px">Dec</th>
        <th width="80px">Total</th>
    </tr>
    <?php $month_total = array();
    	foreach($grand_total as $charity){ 
    		$charity_total = 0;
    ?>
    <tr>
    	<td><?php echo $charity['ID']; ?></td>
    	<td><?php echo $charity['Name']; ?></td>
    	<?php foreach($months as $month){
    			$month_total[$month] += $charity[$month]; 
    			$charity_total += $charity[$month];
    	?>
    	<td><?php echo format_dollars($charity[$month]); ?></td>
    	<?php } ?>
    	<td><?php echo format_dollars($charity_total); ?></td>
    </tr>
    <?php } ?>
    <tr class="sortbottom">
    	<td colspan="2">Grand Total</td>
    	<?php $super_grand_total = 0;
    	 	foreach($months as $month){ 
    	 		$super_grand_total += $month_total[$month];
    	?>
    	<td><?php echo format_dollars($month_total[$month]); ?></td>
   		<?php } ?>
   		<td><?php echo format_dollars($super_grand_total); ?></td>
   	</tr>
</table>
<?php
	include_once 'include/footer.php';
?>