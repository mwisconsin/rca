<?php
    //require_once 'include/address.php';
    //require_once 'include/destinations.php';
    require_once 'include/link.php';
	require_once 'include/franchise.php';
	require_once 'include/functions.php'; 
    //require_once('include/rider.php');
	//require_once 'include/date_time.php';

	error_reporting(E_ALL);
	
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
		$fa = get_destination_from_address($_GET["F_address1"],$_GET["F_address2"],$_GET["F_city"],$_GET["F_state"],$_GET["F_zip5"]);
		if(count($fa) > 0) $from = $fa;
		
		$to = array('Address1' => $_GET['T_address1'],
					  	  'Address2' => $_GET['T_address2'],
						  'City' => $_GET['T_city'],
						  'State' => $_GET['T_state'],
						  'ZIP5' => $_GET['T_zip5']);
		$ta = get_destination_from_address($_GET["T_address1"],$_GET["T_address2"],$_GET["T_city"],$_GET["T_state"],$_GET["T_zip5"]);
		if(count($ta) > 0) $to = $ta;

		$call_yourself = "T_address1={$_GET[T_address1]}&T_Address2={$_GET[T_address2]}&T_city={$_GET[T_city]}&T_state={$_GET[T_state]}&T_zip5={$_GET[T_zip5]}".
			"&F_address1={$_GET[F_address1]}&F_Address2={$_GET[F_address2]}&F_city={$_GET[F_city]}&F_state={$_GET[F_state]}&F_zip5={$_GET[F_zip5]}";
	} else {
        header("Location: index.php");
    }

function strip_apt_unit_etc($address_line_1)
{
	$address_line_1 = preg_replace('/[,]* APT .*/','',$address_line_1);
	$address_line_1 = preg_replace('/[,]* STE .*/','',$address_line_1);
	$address_line_1 = preg_replace('/[,]* UNIT .*/','',$address_line_1);
	$address_line_1 = preg_replace('/[,]* TRLR .*/','',$address_line_1);
	$address_line_1 = preg_replace('/[,]* RM .*/','',$address_line_1);
	$address_line_1 = preg_replace('/[,]* # .*/','',$address_line_1);
	
	return $address_line_1;
}
  
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
		<title>Riders Club of America - Ride Detail</title>
    <script src="https://open.mapquestapi.com/sdk/js/v7.2.s/mqa.toolkit.js?key=<?php echo MAPQUEST_API_KEY; ?>"></script>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
		<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">
		<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    <script type="text/javascript">

      /*An example of using the MQA.EventUtil to hook into the window load event and execute defined function
      passed in as the last parameter. You could alternatively create a plain function here and have it
      executed whenever you like (e.g. <body onload="yourfunction">).*/

      MQA.EventUtil.observe(window, 'load', function() {

        /*Create an object for options*/
        var options={
          elt:document.getElementById('map'),        /*ID of element on the page where you want the map added*/
          zoom:10,                                   /*initial zoom level of map*/
              latLng:{lat:42.05750000, lng:-91.55080000},    /*center of map in latitude/longitude*/
          mtype:'map'                                /*map type (map)*/
        };

        /*Construct an instance of MQA.TileMap with the options object*/
        window.map = new MQA.TileMap(options);

		MQA.withModule('zoomcontrol3', function() {	 
			map.addControl(
			new MQA.LargeZoomControl3(), 
			new MQA.MapCornerPlacement(MQA.MapCorner.TOP_LEFT)
			);
		});
		
        MQA.withModule('directions', function() {
              map.addRoute([
              <?php if($from["Latitude"] != '') { ?>
              		{ latLng: { lat: <?php echo $from["Latitude"]; ?>, lng: <?php echo $from["Longitude"]; ?> } },
              	<?php } else { ?>
              		{street: '<?php echo strip_apt_unit_etc($from["Address1"]); ?>', city: '<?php echo $from["City"]; ?>', state: '<?php echo $from["State"]; ?>'},
              	<?php } ?>
              <?php if($to["Latitude"] != '') { ?>
              		{ latLng: { lat: <?php echo $to["Latitude"]; ?>, lng: <?php echo $to["Longitude"]; ?> } },
              <?php } else { ?>		
                {street: '<?php echo strip_apt_unit_etc($to["Address1"]); ?>', city: '<?php echo $to["City"]; ?>', state: '<?php echo $to["State"]; ?>'},
              <?php } ?>   
						],
            /*Add options.*/
            { routeOptions:{routeType: <?php echo "'" . $route_type . "'" ?>},ribbonOptions:{draggable:true}},
			
            /*Add the callback function to the route call.*/
            displayNarrative
          );
        });
      });

      /*Example function inspecting the route data and generating a narrative for display.*/
      function displayNarrative(data){
        if(data.route){
          var legs = data.route.legs, html = '', i = 0, j = 0, trek, maneuver;
          html += '<table width="650"><tbody>';

          for (; i < legs.length; i++) {
            for (j = 0; j < legs[i].maneuvers.length; j++) {
              maneuver = legs[i].maneuvers[j];
              html += '<tr>';
              html += '<td>';

              if (maneuver.iconUrl) {
                html += '<img src="' + maneuver.iconUrl + '">  ';
              }

              for (k = 0; k < maneuver.signs.length; k++) {
                var sign = maneuver.signs[k];
                if (sign && sign.url) {
                  html += '<img src="' + sign.url + '">  ';
                }
              }
              html += '</td><td>' + maneuver.narrative + '</td><td></td><td nowrap="nowrap">';
			  if (maneuver.distance > 0.0)
			  {
				html += maneuver.distance.toFixed(1) + ' Miles';
			  }
              html += '</td></tr>';
			  
            }
          }
          //html += '</tbody></table>'; Old output - 5/19/2015 - Ian Seiler
		  html += document.getElementById('lURLTime').innerHTML=data.route.formattedTime; 
		  html += document.getElementById('lURLDistance').innerHTML=data.route.distance; //"+data.route.distance+'<table><tbody>'; //New output showing total time and distance - Ian Seiler - 5/19/2015
          document.getElementById('narrative').innerHTML = html;
        }
      }

		function fixmap(address) {
			$.post('/xhr/fixmap.php',{ address: address },function(data) {
				$d = $('<div style="text-align: center;"><b>Notification Sent</b><br><br>Thank you, a notification has been sent to the admin for this address.</div>').dialog({
					modal: true,
					buttons: {
						'Ok': function() { $d.dialog('close'); }
					}
				});
			});
		}
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
			<tr><td  nowrap="nowrap"> From&nbsp;- </td><td><?php echo $link['F_Name'] . " " . $link['F_DestinationDetail'] . ", " . $link['F_Address1'] . " " . $link['F_Address2'] . ", " . $link['F_City'] ; ?> <input type=button value='Fix Map' title='Click here if you think the geocode on the map is wrong for this address. The admin will be informed, and will attempt to fix it.' onClick='fixmap("<?php echo "Rider Name: $rider_name, Address: ".$link['F_Address1'] . " " . $link['F_Address2'] . ", " . $link['F_City']. "(LinkID ".$link['LinkID'].")"; ?>");'></tr></td>
			<tr><td nowrap="nowrap"> To&nbsp;- </td><td><?php echo $link['T_Name'] . " " . $link['T_DestinationDetail'] . ", " . $link['T_Address1'] . " " . $link['T_Address2'] . ", " . $link['T_City'] ; ?> <input type=button value='Fix Map' title='Click here if you think the geocode on the map is wrong for this address. The admin will be informed, and will attempt to fix it.' onClick='fixmap("<?php echo "Rider Name: $rider_name, Address: ".$link['T_Address1'] . " " . $link['T_Address2'] . ", " . $link['T_City']. "(LinkID ".$link['LinkID'].")"; ?>");'></tr></td>
			<tr><td> Notes&nbsp;- </td><td><?php echo $link['LinkNote'] . " " . $rider_settings['OtherNotes']; ?></td> </tr></div></h3>
		<?php } else { ?>
			<tr><td>From&nbsp;- </td><td><?php echo $from["Address1"]. " " . $from["Address2"] . ", " . $from["City"] ; ?></td> </tr>
			<tr><td>To&nbsp;- </td><td> <?php echo $to['Address1'] . " " . $to['Address2'] . ", " . $to['City']; ?></td></tr>
		<?php } ?>
		<tr><td></td></tr> </tbody></table></td></tr> 
		<tr><td> </td></tr>
		<tr><td><div id="map" style="float:left; clear:both; border:2px solid #12436A; width:650px; height:400px;"></div></td></tr>
 		<tr><td></td></tr>
 		<tr><td>
        <table width="650"><tbody><tr><td><font size=5>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Driving Directions&nbsp;&nbsp;</font><?php echo "(" . $route_type . " route)";?></td>
        <td class="noprint">
		
		<input type="button" value="Show <?php echo $alternate_route_type; ?> route " onClick = "document.location.href = '/mapquest_link_map.php?<?php echo $call_yourself . "&routetype=" . $alternate_route_type; ?>'">		

		</td>	<td><button name="PrintButton" class="noprint" onclick="window.print();">Print this page</button> </td>	</tr></tbody></table></td></tr>
 		<tr><td>
		<div id="narrative" style="width:700px;"></div>
		<body>
		
			<p>Distance in miles: <span id="lURLDistance"></span></p>
			<p>HH:MM:SS: <span id="lURLTime"></span></p>
		</body>
		
		</td></tr></tbody></table></b></font>
	</body>
</html>

