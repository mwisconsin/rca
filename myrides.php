<?php
    include_once 'include/user.php';
	redirect_if_not_logged_in();

    require_once('include/rider.php');
    require_once('include/link.php');
    require_once('include/rc_log.php');
	require_once 'include/franchise.php';
	
	if (isset($_GET['uid']) && current_user_has_role(1, 'FullAdmin')) {
	  set_affected_user_id($_GET['uid']);
	}
	

	
	$franchise = get_current_user_franchise();
	if(!user_has_role(get_affected_user_id(), $franchise, 'Rider'))
		header("location: home.php");
		
	$current_user_roles = get_user_roles(get_current_user_id(), $franchise);
	$ReadOnly = 0;
	if(current_user_has_role($franchise, 'Franchisee'))
		foreach($current_user_roles as $role) 
			if($role['Role'] == 'Franchisee') $ReadOnly = $role['ReadOnly'];
	if(get_current_user_id() == get_affected_user_id()) $ReadOnly = 0;

	include_once('include/header.php');
	$user_id = get_affected_user_id();
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

        if ((current_user_has_role( $franchise, 'FullAdmin' ) && !$ReadOnly) ||
        		(current_user_has_role( $franchise, 'Franchisee' ) && !$ReadOnly)) {
            $can_delete = TRUE;
        } elseif (count($rides) > 0) {
            // search the list-o-rides
            foreach ($rides as $potential_match) {
                if ($potential_match['LinkID'] == $delete_link_id &&
                    $potential_match['RiderUserID'] == $rider_info['UserID']) {

                    $next_schedulable = get_next_user_schedulable_link_time();
                    $link_time = get_link_arrival_time($potential_match);
                    $can_delete = ($next_schedulable['time_t'] < $link_time['time_t'] );
                    if($ReadOnly) $can_delete = false;
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
?>
<script>
function confirmAnD(a,d) {
	jQuery.get('/xhr/confirmAnD.php',{ a:a, d:d },function(data) {
		window.location.reload();
	});	
}	
</script>
<?php    
    if (count($rides) == 0) {
?>
<div style="font-size:18px; text-align:center; margin-top:120px;">
	You have no rides scheduled.<br />
	<span style="font-size:15px;">To schedule a ride <a href="plan_ride.php">Click Here</a></span>
</div>
<?php 
    } else {
?>
<div style="text-align:center;"><h2 style='margin: 0px;'>My Rides</h2><a target='_blah' href=/medical_rides.php>Medical Rides Only</a><br><br></div>
<div>
<form method="POST" action="">
<table border="1">
<?php

	/* Limit the ability of users to delete rides */
	/* Between Friday Noon and Sunday Noon, if they aren't an admin */
	
		$can_delete_rides = TRUE;
		if( time() >= strtotime('next Friday noon',strtotime('previous Sunday'))
			&& time() <= strtotime('next Sunday noon',strtotime('previous Sunday'))
			&& !current_user_has_role(1, 'FullAdmin')
			&& !current_user_has_role($franchise, 'Franchisee')) $can_delete_rides = FALSE;
		if($ReadOnly) $can_delete_rides = FALSE;
			
    echo '<tr>';
    echo get_user_link_table_headings($can_delete_rides, TRUE);
    if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee'))
    	echo "<TH width=300>Notes</TH>";
    echo '</tr>';

    $can_delete_all = (current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee')) && !$ReadOnly;

    $next_schedulable = get_next_user_schedulable_link_time();
    
    $priorLinkArrival = 0;
    $priorLink = 0;

    foreach ($rides as $link) {

        if ($link['LinkStatus'] == 'CANCELEDEARLY') {
            continue;
        }
        //print_r($link);

        if ($can_delete_all) {
            $editable = TRUE;
        } else {
            $link_time = get_link_arrival_time($link);
            $editable = ($next_schedulable['time_t'] < $link_time['time_t'] && !isset($link['CustomTransitionID']));
        }

        //echo "prior $priorLinkArrival current ".$link['F_DestinationID']."<br>";
        if($priorLinkArrival == $link['F_DestinationID']
        	&& current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee')) {
        	$sql = "select ArrivalTimeConfirmed from link where LinkID = $priorLink";
        	$priorLinkRow = mysql_fetch_assoc(mysql_query($sql));
        	//echo $link['DepartureTimeConfimed']." ".$priorLinkRow['ArrivalTimeConfirmed']."<BR>";
        	if($link['DepartureTimeConfimed'] == 'N' && $priorLinkRow['ArrivalTimeConfirmed'] == 'N') {
        		echo "<tr><td colspan=14 align=right style='padding-right: 20px;'><input type=button value='Confirm Prior Arrive and Next Depart'
        			onClick=\"confirmAnD($priorLink,".$link['LinkID'].");\"></td></tr>";
        	}
        }
        
        echo '<tr>';
        echo get_link_as_user_link_table_row($link, $editable, TRUE);
        if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee')) {
	        echo "<td class=link_note_cell><img style='display: none;' id=\"{$link['LinkID']}\" src=\"images/trans.gif\" onclick=\"pop_link_notes(this)\" class='" . 
	                (isset($link['LinkNote']) || isset($rider_settings['OtherNotes']) ? 'LinkNoteFilled' : 'LinkNoteBlank') . "' alt=\"df\" /> " .
	    		    (	isset($link['LinkNote']) || isset($rider_settings['OtherNotes']) 
	    		    		? $link['LinkNote'] . 
	    		    			($rider_settings['OtherNotes'] != '' && $link['LinkNote'] != '' ? "<br>...<br>" : "") . 
	    		    			$rider_settings['OtherNotes']
	    		    		: "") 
	    		   .( isset($link['LinkFlexFlag']) && $link['LinkFlexFlag'] != 0 ? '<br><br><b>Note: Time is Flexible.</b>' : '' )
	    		   . "  </td>";        	
        	
        }
        echo '</tr>';
        

        
        $priorLinkArrival = $link['T_DestinationID'];
        $priorLink = $link['LinkID'];
        
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
<div style="text-align:center;"><h2 style='margin: 0px;'>Past Rides</h2><a target='_blah' href=/medical_rides.php>Medical Rides Only</a><br><br></div>
<div>
<?php pagelet(); ?>
<table border="1">
<?php

    echo '<tr>';
    echo get_user_link_table_headings(FALSE, TRUE);
    if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee'))
    	echo "<TH width=300>Notes</TH>";
    echo '</tr>';

    $can_delete_all = current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee');
    $next_schedulable = get_next_user_schedulable_link_time();
    foreach ($past_rides as $past) {
        $link_time = get_link_arrival_time($past);

        echo '<tr>';
        echo get_link_as_user_link_table_row($past, FALSE, TRUE, TRUE);
        if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee')) {
	        echo "<td class=link_note_cell><img style='display: none;' id=\"{$past['LinkID']}\" src=\"images/trans.gif\" onclick=\"pop_link_notes(this)\" class='" . 
	                (isset($past['LinkNote']) || isset($rider_settings['OtherNotes']) ? 'LinkNoteFilled' : 'LinkNoteBlank') . "' alt=\"df\" /> " .
	    		    (	isset($past['LinkNote']) || isset($rider_settings['OtherNotes']) 
	    		    		? $past['LinkNote'] . 
	    		    			($rider_settings['OtherNotes'] != '' && $past['LinkNote'] != '' ? "<br>...<br>" : "") . 
	    		    			$rider_settings['OtherNotes']
	    		    		: "") 
	    		   .($past['LinkFlexFlag'] != 0 ? '<br><b>Time is Flexible</b>' : '')
	    		   . "  </td>";        	
        	
        }
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
<a href="myrides.php?p=<?php echo $_GET['p'] - 1; ?>#past_rides">Previous</a>&nbsp;
<?php } 
$p = $_GET['p'] ? $_GET['p'] : 0;
$start = $p <= 3 ? 1 : ($p >= floor($total_past_rides / 15) - 3 ? floor($total_past_rides / 15) - 6 : $p - 3);
if($start < 1)
	$start = 1;
//echo $start;
//echo ($total_past_rides / 15);
for($i = $start; $i < $start + 7 && $total_past_rides / 15 > $i; $i++){
	//echo $p;
	echo "<a href=\"myrides.php?p=$i#past_rides\"" . ($i-1 == $p ? " class=\"pagelet_selected\"": "") . ">$i</a>&nbsp;";	
}


 if(($total_past_rides / 15) > 1 && $p < floor($total_past_rides / 15)){ ?>
<a href="myrides.php?p=<?php echo $p+1; ?>#past_rides">Next</a>
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
