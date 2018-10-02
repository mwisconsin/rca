<?php
	include_once 'include/user.php';
	include_once 'include/link.php';
	include_once 'include/address.php';
	include_once 'include/custom_ride_transition.php';
	redirect_if_not_logged_in();
	#redirect_if_not_role('FullAdmin');
	
		$franchise = get_current_user_franchise();
		if(!current_user_has_role($franchise, 'FullAdmin') && !current_user_has_role($franchise,'Franchisee')){
			header("location: " . site_url() . "home.php");
			die();			
		}
	
	if(!isset($_REQUEST['id'])){
		header("location: home.php");
		die();
	}
	
	$from = "";
	$from_date = "";
	if(isset($_REQUEST['from']))
		$from = $_REQUEST['from'];
	
	if(isset($_REQUEST['rdate']))
		$from_date = $_REQUEST['rdate'];
	
	//echo $from . '-' . $from_date .'<br>';
	
	$location = check_location_of_custom_transition($_REQUEST['id']);
	
	$rider_links = get_custom_transition_rider_links($_REQUEST['id']);
	$driver_links = get_custom_transition_driver_links($_REQUEST['id']);
	
	
	if(!$location){ echo "failed to find the location of the links"; die(); }
	
	if($_POST['id'] && $_POST['NewStatus'] && $_POST['UpdDriverID'] && !isset($_POST['CancelTransition']) && $location['SameLocation']){
		
		if($location['Location'] == 'Current'){
			$updated = set_custom_transition_all_statuses($_POST['id'], $_POST['NewStatus']);
			
			foreach($rider_links as $link){
				if(set_link_driver_user_id($link['LinkID'], $_POST['UpdDriverID']))
					$changed = true;
			}
			foreach($driver_links as $link){
				if(set_link_driver_user_id($link['LinkID'], $_POST['UpdDriverID']))
					$changed = true;
			}
			if($updated && $changed){
				$announce = "Status and Driver Successfully Updated";
			}
		} else {
			$changed = false;
			foreach($rider_links as $link){
				if(update_completed_link_status_time_driver( $link['LinkID'], $_POST['NewStatus'], 
                                                   NULL, NULL, NULL,
                                                   $_POST['UpdDriverID']))
					$changed = true;
			}
			foreach($driver_links as $link){
				if(update_completed_link_status_time_driver( $link['LinkID'], $_POST['NewStatus'], 
                                                   NULL, NULL, NULL,
                                                   $_POST['UpdDriverID']))
					$changed = true;
			}
			
			if($changed){
				$announce = "Status Successfully Updated";
			}
		}
		$rider_links = get_custom_transition_rider_links($_REQUEST['id']);
		$driver_links = get_custom_transition_driver_links($_REQUEST['id']);
	}
	if($_POST['id'] && $_POST['CancelTransition'] && $location['SameLocation']){
		
		if($location['Location'] == 'Current'){
			$date = get_date(get_link_travel_date($driver_links[0]));
			$cancelled = cancel_custom_ride_transition($_POST['id']);
			if($cancelled){
				header("location: admin_driver_links.php?Year={$date['Year']}&Month={$date['Month']}&Day={$date['Day']}");
				die();
			}
		}
	}
	
	$link_statuses = array('UNKNOWN', 'COMPLETE', 'DRIVERNOSHOW', 'CANCELEDLATE', 'CANCELEDEARLY', 'NOTSCHEDULED', 'WEATHERCANCEL','DESTINATIONCANCEL','HOSPITALCANCEL');
	
	
	$driver_list = get_admin_work_as_driver_list('ALLFRANCHISES'); 
	
	
	include_once 'include/header.php';
	
?>
<style>
.LinkStatusChangeOutput {
	color: blue;
}	
</style>
<script>
jQuery(function($) {
	$.each($('.LinkStatusChange'),function(k,v) {
		if($(v).attr('currentStatus') != '') $(v).val($(v).attr('currentStatus'));
	});
	$('.LinkStatusChange').on('change',function() {
		var optionSelected = $(this).find("option:selected");
    var valueSelected  = optionSelected.val();	
    $that = $(this);
    $.post('/update_linkid_status.php',{ linkid : $(this).attr('linkid'), CTID : $(this).attr('ctid'), status : valueSelected }, function(data) {
    	if(data == '1') $('.LinkStatusChangeOutput[linkid="'+$that.attr('linkid')+'"]').html('Status Updated Successfully.');
    	else $('.LinkStatusChangeOutput[linkid="'+$that.attr('linkid')+'"]').html('There was an error updating the status.');
    });
	});
});	
	
</script>
<h2>Custom Transition</h2>

<?php if($location) echo "These links are location in {$location['Location']} links<br><br>"; ?>
<?php if($cancelled === false) echo "<div class=\"reminder\">The custom transition has failed to cancel</div><br>"; ?>
<?php if($announce) echo "<div class=\"reminder\">$announce</div>"; ?>
<?php 
if($location['SameLocation']){ ?>
<form id="form1" method="post">
<input type="hidden" name="id" value="<?php echo $_REQUEST['id']; ?>">
Set the Status:
<select id="NewStatusSelector" name="NewStatus">
	<?php
	foreach($link_statuses as $status){
		echo "<option value=\"$status\"";
		if($driver_links[0]['LinkStatus'] == $status)
			echo " SELECTED";
		echo ">$status</option>";
		}
		?>
	</select>
	<?php
	$selected_driver = $rider_links[0]['AssignedDriverUserID'];
	if (count($driver_list) > 0) {
                echo "<select id=\"UpdDriverID\" name=\"UpdDriverID\">";
                echo '<option value="NONESELECTED">None Selected</option>';
                foreach ($driver_list as $driver_entry) {
                    echo "<option value=\"{$driver_entry['UserID']}\" " .
                         (($selected_driver == $driver_entry['UserID']) ?
                                    'selected="selected" ' : '' ) .
                         ">{$driver_entry['LastName']}, {$driver_entry['FirstName']} {$driver_entry['MiddleInitial']} ({$driver_entry['UserID']})</option>";
                }
                echo '</select>';
            } else { 
                echo "...an error occurred.";
            } 
	?>
	<input type="submit" value="Update" name="UpdateCT">
	<?php if($location['Location'] == "Current"){ ?> OR <input type="submit" value="Cancel Transition" name="CancelTransition" /><?php } ?>
</form>
<?php } else { ?>
	<div class="reminder">Some of these links below are split between Current links and History links. Please run daily maintenance before editing.</div>
<?php } ?>
<br>
<button onclick="window.location = '<?php echo site_url() . "admin_driver_links.php?Year=2014&Month=09&Day=23";
                            ?>';" margin:5px;">Find Drivers</button>

<button onclick="window.location = '<?php echo site_url() . "manifest.php?Date=2014-09-23";
                            ?>';" margin:5px;">Manifest</button>

<h3>Rider Side</h3>

<table border="1" width="100%">
	<?php get_header(); 
    
    foreach($rider_links as $link)
    	echo display_row($link, 'rider');
		
	?>
</table>

<h3>Driver Side</h3>

<table border="1" width="100%">
	<?php get_header();
    foreach($driver_links as $link)
    	echo display_row($link, 'driver');
	 ?>
</table>
<?php

	function get_header(){
		?>
        <tr>
        	<th>LinkID</th>
            <th>Depart Time</th>
            <th>Arrival Time</th>
            <th>#</th>
            <th>Rider</th>
            <th>Depart From</th>
            <th>Arrive At</th>
            <th>Driver</th>
            <th>Distance</th>
            <th>Estimated Time</th>
            <th>Status</th>
        </tr>
        <?php
	}
	
	function display_row($link, $rowtype){
		
		$arrival_time = get_link_arrival_time($link);
		$departure_time = get_link_departure_time($link);
		$date = get_link_travel_date($link);
		$from_address = get_link_destination_table_cell_contents('F_', $link);
    	$to_address = get_link_destination_table_cell_contents('T_', $link);
		$rider_cell = get_rider_person_info_string($link['RiderUserID'], TRUE);
		$driver = get_driver_person_info($link['AssignedDriverUserID']);
		$driver_name = get_displayable_person_name_string($driver);
            $driver_cell = "<a id=\"{$link['AssignedDriverUserID']}\" class=\"User_Redirect\" href=\"account.php\">$driver_name</a> <br />" .
                           "<a id=\"{$link['AssignedDriverUserID']}\" class=\"User_Redirect\" href=\"manifest.php?date={$travel_date}\">(manifest)</a>"; 
		foreach(get_additional_riders($link['LinkID']) as $rider)
                   $rider_cell .= get_rider_person_info_string($rider['UserID'], TRUE);
		$row = <<<HTML
        <tr>
        	<td>{$link['LinkID']}</td>
			<td>$date<br>{$departure_time['string']}</td>
			<td>$date<br>{$arrival_time['string']}</td>
			<td>{$link['NumberOfRiders']}</td>
			<td>$rider_cell</td>
			<td>$from_address</td>
			<td>$to_address</td>
			<td>$driver_cell</td>
			<td>{$link['Distance']}</td>
			<td>{$link['EstimatedMinutes']}</td>
HTML;
			if($rowtype == 'driver') $row .= "<td>{$link['LinkStatus']}</td>";
			else $row .= <<<HTML
			<td><select class=LinkStatusChange linkid={$link['LinkID']} ctid={$_GET['id']} currentStatus = "{$link['LinkStatus']}">
				<option>UNKNOWN</option>
				<option>COMPLETE</option>
				<option>DRIVERNOSHOW</option>
				<option>CANCELEDLATE</option>
				<option>CANCELEDEARLY</option>
				<option>NOTSCHEDULED</option>
				<option>WEATHERCANCEL</option>
				<option>DESTINATIONCANCEL</option>
				<option>HOSPITALCANCEL</option>
			</select><br><span class=LinkStatusChangeOutput linkid={$link['LinkID']} ctid={$_GET['id']}></span> </td>
HTML;
			$row .= "</tr>";
    	return $row;    
	}
	include_once 'include/footer.php';
?>