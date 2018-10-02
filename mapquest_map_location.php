<?php
    include_once 'include/address.php';
	include_once 'include/user.php';
	
	if(!is_logged_in())
		die("<script>window.close();</script>");
	if(!isset($_GET['id'])){
		header("Location: index.php");
	}
	
	$location_id = mysql_real_escape_string($_GET['id']);
	
	$address = get_address($location_id);
	

	
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
		$index = stripos($new_address,' TRLR ');
		
	if($index == 0)
		$index = stripos($new_address,' RM ');
	
	if($index == 0)
		$index = stripos($new_address,' # ');
	
	if($index > 0)
		$new_address = substr($new_address, 0, $index);
		
	return $new_address;
}
   
?>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
		<title>Riders Club of America - Location</title>
    <script src="https://open.mapquestapi.com/sdk/js/v7.2.s/mqa.toolkit.js?key=<?php echo MAPQUEST_API_KEY; ?>"></script>

    <script type="text/javascript">

      /*An example of using the MQA.EventUtil to hook into the window load event and execute defined function
      passed in as the last parameter. You could alternatively create a plain function here and have it
      executed whenever you like (e.g. <body onload="yourfunction">).*/

      MQA.EventUtil.observe(window, 'load', function() {

        /*Create an object for options*/
     var options={
       elt:document.getElementById('map'),       /*ID of element on the page where you want the map added*/
       zoom:19,                                  /*initial zoom level of the map*/
       latLng:{lat:<?php echo ((isset($address['Latitude'])) ? $address['Latitude'] : '42.010444'); ?>, lng:<?php echo ((isset($address['Longitude'])) ? $address['Longitude'] : '-91.637434'); ?>},  /*center of map in latitude/longitude */
       mtype:'map',                              /*map type (map)*/
       bestFitMargin:0,                          /*margin offset from the map viewport when applying a bestfit on shapes*/
       zoomOnDoubleClick:true,                    /*zoom in when double-clicking on map*/
       enableMouseWheelZoom:true
     };

        /*Construct an instance of MQA.TileMap with the options object*/
    window.map = new MQA.TileMap(options);

		MQA.withModule('zoomcontrol3', function() {	 
			map.addControl(
			new MQA.LargeZoomControl3(), 
			new MQA.MapCornerPlacement(MQA.MapCorner.TOP_LEFT)
			);
		});
		
	<?php if($address['Latitude'] == '') { ?>
        MQA.withModule('geocoder', function() {
          /*Executes a geocode and adds result to the map*/
      		map.geocodeAndAddLocations(
	          {
	          	street:'<?php echo strip_apt_unit_etc($address["Address1"]); ?>', 
	          	city:'<?php echo $address["City"]; ?>', 
	          	state:'<?php echo $address["State"]; ?>',
	          	postalCode:'<?php echo $address["ZIP5"].'-'.$address["ZIP4"]; ?>'
	          },
	         	function(response) {
	         		console.log(response);
	         	}
        	);
	         
        });
	<?php } else { ?>
	cords = map.getCenter();
	custom=new MQA.Poi( {lat:cords.getLatitude(), lng:cords.getLongitude()} );
  map.addShape(custom);
	<?php } ?>
	
		});
    </script>
	</head>
	<body style="margin:0px;">
		<div id="map" style="width:700px; height:370px;"></div>
		 <font size=5><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $address["Address1"] . ", " . $address["City"] . ", " , $address["State"]; ?></b></font>
</body>
</html>