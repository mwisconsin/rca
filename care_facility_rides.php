<?php
    include_once 'include/user.php';
	redirect_if_not_logged_in();

    require_once('include/rider.php');
    require_once('include/link.php');
    require_once('include/rc_log.php');
	require_once 'include/franchise.php';
	require_once 'include/care_facility.php';
	
	if (isset($_GET['uid']) && current_user_has_role(1, 'FullAdmin')) {
	  set_affected_user_id($_GET['uid']);
	}
	
	
	
	$franchise = get_current_user_franchise();

	include_once('include/header.php');
	$user_id = get_affected_user_id();
  $rider_info = get_user_rider_info( $user_id );
  
  $facility = isset($_GET["fid"]) ? $_GET["fid"] : get_first_user_care_facility( get_affected_user_id() );
  
  $rides = get_facility_active_links( $facility );

 	$past_rides = get_facility_past_links( $facility, ($_GET['p'] ? $_GET['p'] * 15 : 0) , 15);
	$total_past_rides = get_facility_total_past_links($facility);
	
    if (is_array($_POST['DeleteLink'])) {
        // Flip to collapse - key is 'Delete', value is link ID
        $delete_link = array_flip($_POST['DeleteLink']);
        $delete_link_id = $delete_link['Delete Link'];

        // Admins can delete any link - users can only delete their own
        // TODO:  Delete only if the link is future - other rules?
        $can_delete = FALSE; 

        if (current_user_has_role( $franchise, 'FullAdmin' )) {
            $can_delete = TRUE;
        } elseif (count($rides) > 0) {
            // search the list-o-rides
            foreach ($rides as $potential_match) {
                if ($potential_match['LinkID'] == $delete_link_id &&
                    $potential_match['RiderUserID'] == $rider_info['UserID']) {

                    $next_schedulable = get_next_user_schedulable_link_time();
                    $link_time = get_link_arrival_time($potential_match);
                    $can_delete = ($next_schedulable['time_t'] < $link_time['time_t'] );
                    break;
                }
            }
        } 

        if ($can_delete) {
            move_link_to_history($delete_link_id);
            set_completed_link_status($delete_link_id,'CANCELEDEARLY');
            //delete_link($delete_link_id);        

            // Reload rides from DB
            $rides = get_rider_active_links( $rider_info['UserID'] );
        } else {
            // Log the invalid attempt
            rc_log(PEAR_LOG_WARN, "User " . get_current_user_id() . " attempted to delete " .
                                  "link $delete_link_id");

        }
    }
    
    if (count($rides) == 0) {
?>
<div style="font-size:18px; text-align:center; margin-top:120px;">
	You have no rides scheduled.<br />
	<span style="font-size:15px;">To schedule a ride <a href="plan_ride.php">Click Here</a></span>
</div>
<?php 
    } else {
?>
<div style="text-align:center;"><h2>Care Facility Rides</h2></div>
<div>
<form method="POST" action="">
<table border="1" style='min-width: 1046px'>
<?php

	/* Limit the ability of users to delete rides */
	/* Between Friday Noon and Sunday Noon, if they aren't an admin */
	
		$can_delete_rides = TRUE;
		if( time() >= strtotime('next Friday noon',strtotime('previous Sunday'))
			&& time() <= strtotime('next Sunday noon',strtotime('previous Sunday'))
			&& !current_user_has_role(1, 'FullAdmin')
			&& !current_user_has_role($franchise, 'Franchisee')) $can_delete_rides = FALSE;
			
    echo '<tr>';
    echo get_user_link_table_headings($can_delete_rides, FALSE, FALSE, TRUE);
    echo '</tr>';

    $can_delete_all = current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee');

    $next_schedulable = get_next_user_schedulable_link_time();

    foreach ($rides as $link) {

        if ($link['LinkStatus'] == 'CANCELEDEARLY') {
            continue;
        }

        if ($can_delete_all) {
            $editable = TRUE;
        } else {
            $link_time = get_link_arrival_time($link);
            $editable = ($next_schedulable['time_t'] < $link_time['time_t'] && !isset($link['CustomTransitionID']));
        }

        echo '<tr>';
        echo get_link_as_user_link_table_row($link, $editable, FALSE, FALSE, FALSE, TRUE);
        echo '</tr>';
    }

?>
</table>
</div>
</form>
<?php
    }
?>




<?php
    if (count($past_rides) == 0) {
?>
<div style="font-size:18px; text-align:center; margin-top:120px;">
	You have no past rides.<br />
</div>
<?php 
    } else {
?>
<div style="text-align:center;"><h2 id="past_rides">Care Facility Past Rides</h2></div>
<div>
<?php pagelet(); ?>
<table border="1" style='min-width: 1046px'>
<?php

    echo '<tr>';
    echo get_user_link_table_headings(FALSE, TRUE, FALSE, TRUE);
    echo '</tr>';

    $can_delete_all = current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee');
    $next_schedulable = get_next_user_schedulable_link_time();
    foreach ($past_rides as $past) {
        $link_time = get_link_arrival_time($past);

        echo '<tr>';
        echo get_link_as_user_link_table_row($past, FALSE, TRUE, TRUE, FALSE, TRUE);
        echo '</tr>';
    }

?>
</table>
<?php pagelet(); ?>
</div>
<?php
    }
?>



<div style="clear:both">&nbsp;</div>

<?php

function pagelet(){
	global $total_past_rides;
?>
<?php if($_GET['p'] && $_GET['p'] >= 1){ ?>
<a href="care_facility_rides.php?p=<?php echo $_GET['p'] - 1; ?>#past_rides">Previous</a>&nbsp;
<?php } 
$p = $_GET['p'] ? $_GET['p'] : 0;
$start = $p <= 3 ? 1 : ($p >= floor($total_past_rides / 15) - 3 ? floor($total_past_rides / 15) - 6 : $p - 3);
if($start < 1)
	$start = 1;
//echo $start;
//echo ($total_past_rides / 15);
for($i = $start; $i < $start + 7 && $total_past_rides / 15 > $i; $i++){
	//echo $p;
	echo "<a href=\"care_facility_rides.php?p=$i#past_rides\"" . ($i-1 == $p ? " class=\"pagelet_selected\"": "") . ">$i</a>&nbsp;";	
}


 if(($total_past_rides / 15) > 1 && $p < floor($total_past_rides / 15)){ ?>
<a href="care_facility_rides.php?p=<?php echo $p+1; ?>#past_rides">Next</a>
<?php } 	
}
?>
<style>
	.pagelet_selected {
		font-size:1.15em;
	}
</style>

<?php 
	include_once 'include/footer.php';
?>
