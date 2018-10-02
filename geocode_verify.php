<?php
	include_once 'include/user.php';
	include_once 'include/address.php';
	
	redirect_if_not_logged_in();
	#redirect_if_not_role('FullAdmin');
		$franchise = get_current_user_franchise();
		if(!current_user_has_role($franchise, 'FullAdmin') && !current_user_has_role($franchise,'Franchisee')){
			header("location: " . site_url() . "home.php");
			die();			
		}
	
	if(isset($_POST['NewAddress'])){
		$address = array('Address1' => strtoupper($_POST['Address1']),
						 'Address2' => $_POST['Address2'],
						 'City' => strtoupper($_POST['City']),
						 'State' => strtoupper($_POST['State']),
						 'ZIP5' => $_POST['Zip5'],
						 'ZIP4' => $_POST['Zip4'],
						 'Latitude' => $_POST['Latitude'],
						 'Longitude' => $_POST['Longitude']);
		$address_id = add_address($address, FALSE);
		
		$sql = "UPDATE address SET IsVerified = 'Yes', VerifySource = 'Geocode' WHERE AddressID = $address_id LIMIT 1;";
		mysql_query($sql);
	}
	if(isset($_GET['delete'])){
		$safe_id = mysql_real_escape_string($_GET['delete']);
		$sql = "DELETE FROM address WHERE VerifySource = 'Geocode' AND AddressID = $safe_id LIMIT 1;";
		mysql_query($sql);
	}
	
    include_once 'include/header.php';
?>
<script src="http://btilelog.access.mapquest.com/tilelog/transaction?transaction=script&key=mjtd%7Cluu72907n1%2C2n%3Do5-547wu&itk=true&v=5.3.s&ipkg=controls1" type="text/javascript"></script>
<h2><center>Geocode Verify</center></h2>
<div id="map" style="float:right; clear:both; border:3px solid #12436A; width:530px; height:205px;">
</div>
<form method="post">
	<table>
		<tr>
			<td colspan="2">
				<?php
					create_html_address_table(NULL, NULL, FALSE);
				?>
			</td>
		</tr>
		<tr>
			<td style="padding-left:4px;">*Latitude<br><input id="Latitude" name="Latitude" value="42.010444" type="text" size="21"></td>
			<td>*Longitude<br><input id="Longitude" name="Longitude" type="text" size="21" value="-91.637434"></td>
		</tr>
		<tr>
			<td colspan="2" class="alignright"><input type="submit" name="NewAddress" value="Add Geocode Address"></td>
		</tr>
	</table>
</form>
<br>
<table width="100%" border="1">
	<tr>
		<td>Address</td>
		<td>Latitude</td>
		<td>Longitude</td>
		<td>Remove</td>
	</tr>
	<?php
		$sql = "SELECT address.AddressID, address.Address1, address.City, address.State, address.ZIP5, address.Latitude, address.Longitude 
FROM ((( address LEFT JOIN destination ON address.AddressID = destination.AddressID ) LEFT JOIN user_address ON address.AddressID = user_address.AddressID ) LEFT JOIN emergency_contact ON address.AddressID = emergency_contact.Address ) LEFT JOIN donation ON address.AddressID = donation.DonorAddressID WHERE destination.AddressID IS NULL AND user_address.AddressID IS NULL AND emergency_contact.Address IS NULL AND donation.DonorAddressID IS NULL AND address.VerifySource = 'Geocode'";
		$result = mysql_query($sql);
		
		while($row = mysql_fetch_array($result)){
			?>
			<tr>
				<td><?php echo $row['Address1'] . ', ' . $row['City'] . ', ' . $row['State'] . ', ' .  $row['ZIP5']; ?></td>
				<td><?php echo $row['Latitude'] ?></td>
				<td><?php echo $row['Longitude']; ?></td>
				<td width="75px"><a href="?delete=<?php echo $row['AddressID']; ?>"><input type="button" value="Remove"></a></td>
			</tr>
			<?php
		}
	?></td>
</table>
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
	
	myMap = new MQA.TileMap($('map'),9,new MQA.LatLng(42.010444,-91.637434),'map');
	myMap.addControl(new MQA.ZoomControl());

	$('mqmaptilediv').addEvent('mouseup',function(){
		cords = myMap.getCenter();
		$('Latitude').value = cords.getLatitude();
		$('Longitude').value = cords.getLongitude();
	});
	$('map').addEvent('mouseleave', function(){
		$('mqmaptilediv').fireEvent('mouseup');
	});
</script>
<?php
	include_once 'include/footer.php';
?>
