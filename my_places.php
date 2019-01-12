<?php
	include_once 'include/user.php';
	redirect_if_not_logged_in();
	include_once 'include/address.php';
	include_once 'include/phone.php';
	require_once 'include/franchise.php';
	include_once 'include/destinations.php';
	include_once 'include/public_destination_selector.php';
	include_once 'include/rider.php';
	
	$is_admin = (current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise_id, "Franchisee")) ? true : false;
	
	$franchise_id = get_current_user_franchise();
	if ($_GET['id'] && (current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise_id, "Franchisee"))) {
        // TODO:  Check for rights
		$user_id = $_GET['id'];
		$edit_url = "&id=" . $_GET['id'];
	} else {
		$user_id = get_affected_user_id();
	}

	if (!user_has_role($user_id,$franchise_id, 'Rider')) {
		header('Location: home.php');
    } else {
        $rider_id = $user_id;
    }
	
	// TODO:  Func'ify	
	if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['placeid']) && $_GET['placeid'] != '') {
        remove_destination_for_rider($rider_id, $_GET['placeid']);
	}
	//print_r($_POST['DestinationSelectorValue']);	
	if($_POST['DestinationSelectorValue'][0]){
		$added = add_destination_for_rider($rider_id,$_POST['DestinationSelectorValue'][0]);
		//print_r($rider_id .  " " . $_POST['DestinationSelectorValue'][0]);	
	}
    if (isset($_POST['Address1'])) {
        $address = array('Address1' => $_POST['Address1'],
                         'Address2' => $_POST['Address2'],
                         'City' => $_POST['City'],
                         'State' => $_POST['State'],
                         'ZIP5' => $_POST['Zip5'],
                         'ZIP4' => $_POST['Zip4']);

        if (isset($_POST['Latitude'], $_POST['Longitude']) && $_POST['UseGeocode'] == TRUE 
                && current_user_has_role(1,'FullAdmin')) {
            $address['Latitude'] = $_POST['Latitude'];
            $address['Longitude'] = $_POST['Longitude'];
            $should_geocode = TRUE;
        }
    }


	if (isset($address) && $_POST['action'] == 'create') {
		$error = FALSE;
		$fields = array('Destination','Address1');
		foreach($fields as $k => $v) {
			if ($_POST[$v] == '' || !isset($_POST[$v])) {
				$error = TRUE;
			}
		}
		if (!$error) {
			$rider = get_user_rider_info($user_id);
			$new_destination = create_new_destination($_POST['Destination'], $address,
                                                      $franchise_id, $_POST['Public'],
                                                      NULL, $_POST["DestinationGroup"], $_POST['DestinationPhone'], 
                                                      $_POST['DestinationDetail'], $_POST['DestinationPhoneExt']);
			add_destination_for_rider($rider_id,$new_destination);
      if ($should_geocode) {
          update_address($new_destination['AddressID'], $address, FALSE, TRUE);
      }
		}
	}

	if ($_REQUEST['action'] == 'edit' && isset($_REQUEST['placeid'])) {
		if (isset($_POST['Address1']) && $_POST['action'] == 'edit') {
			$error = FALSE;
			$fields = array('Destination','Address1');
			foreach($fields as $k => $v) {
				if ($_POST[$v] == '' || !isset($_POST[$v])) {
					$error = TRUE;
				}
			}

			if (!$error) {
				$destination = get_destination($_POST['placeid']);
                $address['id'] = $destination['AddressID'];

				$rider = get_user_rider_info($user_id);
				$is_public = $_POST['Public'] ? true : false;
				edit_destination($_POST['placeid'], $_POST['Destination'], $address, 
                                 $franchise_id, $_POST["DestinationGroup"], $_POST['DestinationPhone'], 
                                 $_POST['DestinationDetail'], $is_public, $_POST['DestinationPhoneExt'], $_POST['is_local_area'] == 'on' ? TRUE : FALSE);	
                if ($should_geocode) {
                    update_address($address['id'], $address, FALSE, TRUE);
                }
			} 
			else {
				$place = get_destination($_GET['placeid']);
            }
		} else {
			$place = get_destination($_GET['placeid']);
        }
		
	}

    if ($place['AddressID']) {
        $db_address = get_address($place['AddressID']);
    }

    include_once 'include/header.php';
	
	
?>
<?php
    if (current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise_id, "Franchisee")) { ?>
    	<script src="https://open.mapquestapi.com/sdk/js/v7.2.s/mqa.toolkit.js?key=<?php echo MAPQUEST_API_KEY; ?>"></script>
<?php
    }
?>

<?php 
    if (current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise_id, 'Franchisee')) { ?>
<div id="map" style="float:right; clear:both; border:3px solid #12436A; width:530px; height:205px;">
</div>
<?php } ?>
<div style="float:right; width:530px;">
	<h3>Add Some of Our Public Destinations!</h3>
    Choose from hundreds of our already created destinations. Just type in the name of the place you want to add and hit ENTER when you've selected it!<br><br>
    <form method="post">
    <center>
    	<?php echo create_public_destination_selector(0); ?>
    </center>
    <input style="float:right; margin-top:-1.8em;" type="submit" name="Finish" value="Save">
    </form>
</div>
<form action="<?php echo $_SERVER['PHP_SELF']; if (isset($_GET['id'])) echo '?id=' . $_GET['id']; ?>" method="post">
	<?php if ($error) { echo "You were missing one or more of the required fields."; } ?>
    <?php 
        if (isset($_GET['placeid'])) {?>
            <input type="hidden" name="placeid" value="<?php echo $_GET['placeid']; ?>">
    <?php } ?>
            <input type="hidden" name="action" value="<?php if ($_GET['action'] == 'edit') echo 'edit'; else echo 'create'; ?>">

	<table>
		<tr>
			<td style="padding-left:4px;" colspan="2">
				*Destination Name<br>
				<input type="text" name="Destination" value="<?php echo $place['Name']; ?>" style="width:250px;">
			</td>
		</tr>
		<tr>
			<td style="padding-left:4px;" colspan="2">
				Destination Details<br>
				<input type="text" name="DestinationDetail" value="<?php echo $place['DestinationDetail']; ?>" style="width:250px;">
			</td>
		</tr>
		<tr>
			<td style="padding-left:4px;" colspan="2">
				Destination Type<br>
				<select name="DestinationGroup" size=1>
					<option value=-1></option>
				<?php
				$sql = "select DestinationGroupID, Name from destination_group order by Name";
				$r = mysql_query($sql);
				while($rs = mysql_fetch_array($r))
					echo "<option value=$rs[DestinationGroupID] "
						.($place["DestinationGroupID"] == $rs["DestinationGroupID"] ? "selected" : "")
						.">$rs[Name]</option>\n";
				
				?>	
				</select>
			</td>			
		</tr>
		<tr>
			<td style="padding-left:4px;" colspan="2">
				<table cellpadding=0 cellspacing=0 border=0>
					<tr>
						<td>Destination Phone Number:</td>
						<td>Extension</td>
					</tr>
					<tr>
						<td><input type="text" name="DestinationPhone" value="<?php $phone = get_phone_number($place['PhoneID']); echo $phone['PhoneNumber']; ?>" style="width:180px;" /></td>
						<td><input type="text" name="DestinationPhoneExt" value="<?php echo $phone['Ext']; ?>" style="width: 66px;"></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<?php
					create_html_address_table(null, $db_address);
				?>
			</td>
		</tr>
<?php 
    if (current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise_id, 'Franchisee')) { ?>
		<tr>
			<td style="padding-left:4px;">Latitude<br><input id="Latitude" name="Latitude" value="<?php 
                        echo $db_address['Latitude']; ?>" type="text" size="21"></td>
			<td>Longitude<br><input id="Longitude" name="Longitude" type="text" size="21" value="<?php
                        echo $db_address['Longitude']; ?>"></td>
		</tr>
		<tr>
            <td style="padding-left:4px;">Use Geocode? <input name="UseGeocode" type="checkbox" <?php 
                if ($db_address['VerifySource'] == 'Geocode') {
                    echo 'checked="checked" ';
                } ?> />
        </tr>
<?php } 
?>
		<tr valign=top>
			<td><?php if($place['Name'] != 'Default Home') { ?>
				Share This Location <input type="checkbox"<?php if ($place['IsPublic'] == 'Yes') echo ' Checked="true"'; ?> name="Public">
				<?php } ?>
				<?php
					if($_GET['action'] == 'edit') {
						echo "<br>Is In Local Area? <input type=checkbox name=is_local_area ".( !is_zip_out_of_area( $franchise, $db_address['ZIP5'] ) || $place['is_local_area_override'] == TRUE ? 'checked' : '' ).">";
					}
				?></td>
			<td class="alignright"><input type="submit" value="<?php if ($_GET['action'] == 'edit') { ?>Submit Changes<? } else { ?>Add Address<?php } ?>"></td>
		</tr>
	</table>

	
</form>
<h2>My Places</h2>
<?php if($added) { ?><div class="reminder">You Have successfully Added a New Destination!</div><?php } ?>
<hr>
<table style="margin:auto; width:100%;" border="1">
	<tr>
		<th></th>
		<th>Name</th>
		<th nowrap="nowrap">Phone</th>
		<th>Address</th>
		<th>Addr2</th>
		<th nowrap="nowrap">City, State, ZIP</th>
		<?php if($is_admin) echo "<th>GeoCode</th>"; ?>
	</tr>
	<?php
		$destinations = get_rider_destinations( $rider_id );
//		echo '<pre>';
//		print_r($destinations);
//		echo '</pre>';
		//echo mysql_error();
		for($i = count($destinations) - 1; $i >= 0; $i--) {
			$delete_link = site_url() . "my_places.php?action=delete&placeid=" . $destinations[$i]['DestinationID'] . $edit_url;
			$edit_link = site_url() . "my_places.php?action=edit&placeid=" . $destinations[$i]['DestinationID'] . $edit_url;
			$goto_link = site_url() . "plan_ride.php?goto=" . $destinations[$i]['DestinationID'] . $edit_url;
			$comefrom_link = site_url() . "plan_ride.php?comefrom=" . $destinations[$i]['DestinationID'] . $edit_url;
			$phone = get_phone_number( $destinations[$i]['PhoneID'] );
		?>
		<tr>
			<td width="145px">
				<input onclick="window.open('mapquest_map_location.php?id=<?php echo $destinations[$i]['AddressID']; ?>','Window1',
	'menubar=no,width=700,height=400,toolbar=no'); return false;" type="button" value="Map">
				<?php if (($destinations[$i]['IsPublicApproved'] == 'No' && $destinations[$i]['Name'] != 'Default Home') || (current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise_id, 'Franchisee'))) {?>
					<input type="button" value="edit" onclick="document.location ='<?php echo $edit_link; ?>'">
				<?php }if ($destinations[$i]['Name'] != 'Default Home' || ( current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise_id, 'Franchisee'))) { ?>
					<input type="button" value="Delete" onclick="document.location ='<?php echo $delete_link; ?>'">
				<?php }if ($destinations[$i]['Name'] != 'Default Home') { ?>
                    <BR><input type="button" value="Go To" onclick="document.location ='<?php echo $goto_link; ?>'">
                    	<input type="button" value="Come From" onclick="document.location ='<?php echo $comefrom_link; ?>'">
                <?php } ?>
			</td>
			<td><?php 
                    echo $destinations[$i]['Name']; 
                    if (!is_null($destinations[$i]['DestinationDetail'])) {
                       echo "<br />{$destinations[$i]['DestinationDetail']}";
                    }
                ?></td>
			<td>
				<?php
					echo $phone['PhoneNumber'].($phone['Ext'] != '' ? ' x'.$phone['Ext'] : '');
				?>
			</td>
			<td><?php echo $destinations[$i]['Address1']; ?></td>
			<td><?php echo $destinations[$i]['Address2']; ?></td>
			<td><?php echo $destinations[$i]['City'] . ', ' . $destinations[$i]['State'] . ', ' . $destinations[$i]['ZIP5']; ?></td>
			<?php if($is_admin) {
				echo "<td>";
				echo $destinations[$i]["Longitude"] != "" && $destinations[$i]["Latitude"] != "" ? " Yes" : " No";
				echo "</td>";
			} ?>
		</tr>
		<?php
		} ?>
</table>

<?php 
    // Geocoding

    if (current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise_id, 'Franchisee')) { ?>
<script src="js/mapquest/mqcommon.js"></script>
<script src="js/mapquest/mqexec.js"></script>
<script src="js/mapquest/mqobjects.js"></script>
<script src="js/mapquest/mqutils.js"></script>
<script type="text/javascript">
	var proxyServerName = "";
	var proxyServerPort = "";

	var ProxyServerPath = "js/mapquest/crossdomain/JSReqHandler.php";
	
	//mq server variables
	var geoServerName = "geocode.free.mapquest.com";
	var spatServerName = "spatial.free.mapquest.com";
	var mqServerPort = "80";
	var mqServerPath = "mq";
    
     /*Create an object for options*/
     var options={
       elt:$('map'),       /*ID of element on the page where you want the map added*/
       zoom:9,                                  /*initial zoom level of the map*/
       latLng:{lat:<?php echo ((isset($db_address['Latitude'])) ? $db_address['Latitude'] : '42.010444'); ?>, lng:<?php echo ((isset($db_address['Longitude'])) ? $db_address['Longitude'] : '-91.637434'); ?>},  /*center of map in latitude/longitude */
       mtype:'map',                              /*map type (map)*/
       bestFitMargin:0,                          /*margin offset from the map viewport when applying a bestfit on shapes*/
       zoomOnDoubleClick:true,                    /*zoom in when double-clicking on map*/
       enableMouseWheelZoom:true
     };
 
     /*Construct an instance of MQA.TileMap with the options object*/
     myMap = new MQA.TileMap(options);
			MQA.withModule('largezoom', function() {
			 
			    myMap.addControl(
			      new MQA.LargeZoom(),
			      new MQA.MapCornerPlacement(MQA.MapCorner.TOP_LEFT, new MQA.Size(5,5))
			    );
			 
			  });
  	
	//myMap.addControl(new MQA.ZoomControl());
	
	cords = myMap.getCenter();
	custom=new MQA.Poi( {lat:cords.getLatitude(), lng:cords.getLongitude()} );
  myMap.addShape(custom);

	$('map').addEvent('mouseup',function() {
		cords = myMap.getCenter();
		$('Latitude').value = cords.getLatitude();
		$('Longitude').value = cords.getLongitude();
		myMap.removeAllShapes();
		custom=new MQA.Poi( {lat:cords.getLatitude(), lng:cords.getLongitude()} );
	  myMap.addShape(custom);
	});
	$('map').addEvent('mouseleave', function() {
		$('mqmaptilediv').fireEvent('mouseup');
	});
	function set_destination($idx, field2, field3, field4){
		$('DestinationSelectorValue[' + $idx + "]").value = field3;
	}
	window.addEvent('domready', function(){
    	load_public_destination_data(<?php echo $franchise_id; ?>);
		create_public_destination_selector(0);
    });
</script>
<?php 
    }
?>

<?php
	echo get_public_destination_selector_js();
    include_once 'include/footer.php';
?>
