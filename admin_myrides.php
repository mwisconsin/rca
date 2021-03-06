<?php
    include_once 'include/user.php';
	redirect_if_not_logged_in();

    require_once('include/rider.php');
    require_once('include/link.php');
    require_once('include/rc_log.php');
	require_once 'include/franchise.php';
	
	$franchise = get_current_user_franchise();
	if(!user_has_role(get_affected_user_id(), $franchise, 'Rider'))
		//header("location: home.php");

	include_once('include/header.php');
	$user_id = if (isset($_GET['uid'])) ? $_GET['uid'] : get_affected_user_id();
    $rider_info = get_user_rider_info( $user_id );
    $rides = get_rider_active_links( $user_id );

    $past_rides = get_rider_past_links( $user_id, ($_GET['p'] ? $_GET['p'] * 15 : 0) , 15);
	$total_past_rides = get_rider_total_past_links($user_id);
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
<div style="text-align:center;"><h2>My Rides</h2></div>
<div>
<form method="POST" action="">
<table border="1">
<?php

    echo '<tr>';
    echo get_user_link_table_headings(TRUE);
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
        echo get_link_as_user_link_table_row($link, $editable);
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
<div style="text-align:center;"><h2 id="past_rides">Past Rides</h2></div>
<div>
<?php pagelet(); ?>
<table border="1">
<?php

    echo '<tr>';
    echo get_user_link_table_headings(FALSE, TRUE);
    echo '</tr>';

    $can_delete_all = current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee');
    $next_schedulable = get_next_user_schedulable_link_time();
    foreach ($past_rides as $past) {
        $link_time = get_link_arrival_time($past);

        echo '<tr>';
        echo get_link_as_user_link_table_row($past, FALSE, TRUE, TRUE);
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
<?php if($_GET['p'] && $_GET['p'] > 1){ ?>
<a href="myrides.php?p=<?php echo $_GET['p'] - 1; ?>#past_rides">Previous</a>&nbsp;
<?php } 
$p = $_GET['p'] ? $_GET['p'] : 1;
$start = $p <= 3 ? 1 : ($p >= floor($total_past_rides / 15) - 3 ? floor($total_past_rides / 15) - 6 : $p - 3);
if($start < 1)
	$start = 1;
//echo $start;
//echo ($total_past_rides / 15);
for($i = $start; $i < $start + 7 && $total_past_rides / 15 > $i; $i++){
	
	echo "<a href=\"myrides.php?p=$i#past_rides\"" . ($i == $p ? " class=\"pagelet_selected\"": "") . ">$i</a>&nbsp;";	
}


 if(($total_past_rides / 15) > 1 && $p < floor($total_past_rides / 15)){ ?>
<a href="myrides.php?p=<?php echo $_GET['p'] + 1; ?>#past_rides">Next</a>
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
