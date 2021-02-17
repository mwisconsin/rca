<?php
    //require_once 'include/address.php';
    //require_once 'include/destinations.php';
    require_once 'include/link.php';
	require_once 'include/franchise.php';
	require_once 'include/functions.php'; 
    //require_once('include/rider.php');
	//require_once 'include/date_time.php';

	$franchise_id = get_current_user_franchise(FALSE);

 	$route_type = 'shortest';
    if (isset($_GET['routetype'])) {
       $route_type = $_GET['routetype'];
	}
	$alternate_route_type = 'fastest';
	if ($route_type == 'fastest') {
		$alternate_route_type = 'shortest';
		}
	$has_link_id = FALSE;
	if (isset($_GET['id'])) {
		$has_link_id = TRUE;
		$call_yourself = 'id=' . $_GET['id'];
        $link = get_link($_GET['id']);
		if(is_null($link) || !$link){
			$link = get_history_link($_GET['id']);
		}
        // Need to get the destination to check whether it should geocode
        $from = get_destination($link['F_DestinationID']);
        $to = get_destination($link['T_DestinationID']);
        if ($lf_rider_info = get_large_facility_rider_info_for_link($link['LinkID'])) {
            $rider_info = $lf_rider_info;
            $rider_name = get_lf_rider_person_info_string($lf_rider_info);
            //$rider_cell = get_lf_rider_person_info_string($lf_rider_info);
            $rider_pref_string = 'Preferences Not Set';
        } elseif ( $link['LinkID'] == 0 ) {
            $rider_name = 'TRANSITION';
            $rider_pref_string = $link['RiderPreferences'];
        } else {
            $rider_info = get_rider_person_info($link['RiderUserID']);
            $rider_name = get_displayable_person_name_string($rider_info);
            //$rider_cell = get_rider_person_info_string($link['RiderUserID'], TRUE);

            $rider_prefs = get_rider_prefs($link['RiderUserID']);
            $rider_pref_string = rider_preferences_to_display_string($rider_prefs);
        }
        
        $load_pad = ($link['LinkID'] == 0) ? 0 : 5;
        $drive_pad = ($link['LinkID'] == 0) ? 0 : 5;
        $rider_settings = get_user_rider_preferences($link['RiderUserID']);
        $departure_time_info = get_link_departure_time($link);
        $arrival_time_info = get_link_arrival_time($link);
		$estimated_minutes = get_link_estimated_minutes($link);

        $miles += $link['Distance'];
        $minutes += $link['EstimatedMinutes'];
        if (!isset($first_time_t)) {
            $first_time_t = $departure_time_info['time_t'];
        }
        $last_time_t = $arrival_time_info['time_t'];
    } elseif (isset($_GET['transition'], $_GET['to'])) {
		$call_yourself = 'transition=' . $_GET['transition'] . "&to=" . $_GET['to'];
        $from = get_destination($_GET['transition']);
        $to = get_destination($_GET['to']);
    } else if(isset($_GET['T_address1'],$_GET['F_address1'])){
		$from = array('Address1' => $_GET['F_address1'],
					  		   'Address2' => $_GET['F_address2'],
							   'City' => $_GET['F_city'],
							   'State' => $_GET['F_state'],
							   'ZIP5' => $_GET['F_zip5']);
		$to = array('Address1' => $_GET['T_address1'],
					  	  'Address2' => $_GET['T_address2'],
						  'City' => $_GET['T_city'],
						  'State' => $_GET['T_state'],
						  'ZIP5' => $_GET['T_zip5']);
	} else {
        header("Location: index.php");
    }

function strip_apt_unit_etc($address_line_1)
{
	$new_address = $address_line_1;
	
	$index = stripos($new_address,', APT ');
	
	if($index == 0)
		$index = stripos($new_address,' APT ');
	
	if($index == 0)
		$index = stripos($new_address,', STE ');
	
	if($index == 0)
		$index = stripos($new_address,' STE ');
	
	if($index == 0)
		$index = stripos($new_address,', UNIT ');
	
	if($index == 0)
		$index = stripos($new_address,' UNIT ');
	
	if($index == 0)
		$index = stripos($new_address,', TRLR ');
	
	if($index == 0)
		$index = stripos($new_address,' RM ');
	
	if($index == 0)
		$index = stripos($new_address,' # ');
	
	if($index > 0)
		$new_address = substr($new_address, 0, $index);
		
	return $new_address;
}
   
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
		<title>Riders Club of America - Ride Detail</title>
<script type="text/javascript"
      src="https://maps.googleapis.com/maps/api/js?key=<?php echo GOOGLE_API_KEY; ?>">
<script>
 function initialize() {
   var mapProp = {
     center:new google.maps.LatLng(42.05750000, -91.55080000),
     zoom:10,
     mapTypeId:google.maps.MapTypeId.ROADMAP
   };
   var map=new google.maps.Map(document.getElementById("googleMap"),mapProp);
 }
 google.maps.event.addDomListener(window, 'load', initialize);
</script>
	</head>
	<body>
		<font size=4><b>
		<table><tbody>
		<tr><td><div style="float:left;">
            <img src="<?php  echo site_url(); echo getFranchiseLogo($franchise_id);	?>" alt="">
			<!--img src="documents/images/logo-small2.jpg" align="Riders Club of America"//-->
		</div>
		<div> <font size=5></br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 
		<?php if ($has_link_id == TRUE){ echo "Ride Detail"; 
		} else{ echo "Transition Detail"; } ?>
		</font></div>
		</td></tr>
 		<tr><td> </td></tr>
		<tr> <td><table width="650"><tbody>
		<?php if ($has_link_id == TRUE){ ?>
			<tr> <td> Rider&nbsp;- </td><td><?php echo $rider_name; ?>
			&nbsp;&nbsp;&nbsp;&nbsp;Pickup Time - <?php echo date('g:i A', $departure_time_info['time_t']) ?> 
			&nbsp;&nbsp;&nbsp;&nbsp;Dropoff Time - <?php echo date('g:i A', $arrival_time_info['time_t']) ?>	 </td> </tr>
			<?php  foreach(get_additional_riders($link['LinkID']) as $rider){
               echo "<tr> <td> Additional Rider - </td><td>" . get_displayable_person_name_string($rider) . "</td> </tr>";
			   }?>
			<tr><td  nowrap="nowrap"> From&nbsp;- </td><td><?php echo $link['F_Name'] . " " . preg_replace("/[\n\r]+/","<br>",$link['F_DestinationDetail']) . ", " . $link['F_Address1'] . " " . $link['F_Address2'] . ", " . $link['F_City'] ; ?> </tr></td>
			<tr><td nowrap="nowrap"> To&nbsp;- </td><td><?php echo $link['T_Name'] . " " . preg_replace("/[\n\r]+/","<br>",$link['T_DestinationDetail']) . ", " . $link['T_Address1'] . " " . $link['T_Address2'] . ", " . $link['T_City'] ; ?> </tr></td>
			<tr><td> Notes&nbsp;- </td><td><?php echo $link['LinkNote'] . " " . $rider_settings['OtherNotes']; ?></td> </tr></div></h3>
		<?php } else { ?>
			<tr><td>From&nbsp;- </td><td><?php echo $from["Address1"]. " " . $from["Address2"] . ", " . $from["City"] ; ?></td> </tr>
			<tr><td>To&nbsp;- </td><td> <?php echo $to["Address1"]. " " . $to["Address2"] . ", " . $to["City"]; ?></td></tr>
		<?php } ?>
		<tr><td></td></tr> </tbody></table></td></tr> 
		<tr><td> </td></tr>
		<tr><td><div id="googleMap" style="float:left; clear:both; border:2px solid #12436A; width:650px; height:400px;"></div></td></tr>
 		<tr><td></td></tr>
 		<tr><td>
        <table width="650"><tbody><tr><td><font size=5>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Driving Directions&nbsp;&nbsp;</font><?php echo "(" . $route_type . " route)";?></td>
        <td class="noprint">
		
		<input type="button" value="Show <?php echo $alternate_route_type; ?> route " onClick = "document.location = 'mapquest_link_map.php?<?php echo $call_yourself . "&routetype=" . $alternate_route_type; ?>'">		

		</td>	<td><button name="PrintButton" class="noprint" onclick="window.print();">Print this page</button> </td>	</tr></tbody></table></td></tr>
 		<tr><td>
		<div id="narrative" style="width:700px;"></div>
		</td></tr></tbody></table></b></font>
	</body>
</html>

