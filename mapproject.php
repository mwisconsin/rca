<?php require('include/database.php'); ?>

<!DOCTYPE html>
<html>
  <head>
    <script type="text/javascript"
      src="https://maps.googleapis.com/maps/api/js?key=<?php echo GOOGLE_API_KEY; ?>">
    </script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
    <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css">
		<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>
		<script src="//cdnjs.cloudflare.com/ajax/libs/bluebird/2.9.4/bluebird.min.js"></script>
    <style type="text/css">
      html, body, #map-canvas, form { height: 100%; margin: 0; padding: 0;}
      #filter-control {
      	position: absolute;
      	left: 0;
      	bottom: 0;
      	height: 120px;
      	width: 370px;
      	background-color: white;
      }
      #filter-control > form > button {
      	position: absolute;
      	bottom: 0;
      	right: 0;
      	font-size: 160%;
      }
      #filter-control {
      	font-family: Helvetica, Sans-Serif;
      	font-size: 12px;
      }
      #filter-control table {
      	width: 100%;
      }
      #filter-control table tr td:not(:first-child) {
      	text-align: center;
      }
      .dts, .tim {
      	border: 1px solid black;
      	border-radius: 5px;
      	width: 78px;
      	text-align: center;
      }
      .ui-datepicker table {
      	font-size: .6em;
      }
      .ui-widget {
      	font-size: .7em;
      }
      .ui-datepicker .ui-datepicker-title {
      	line-height: 1.7em;
      }
    </style>
    <script type="text/javascript">
			var directionsDisplay;
			var directionsService = new google.maps.DirectionsService();
			var geocoder = new google.maps.Geocoder();
			var map;
			var colorlist = ['#FF0000','#808000','#FFFF00','#008000','#00FF00','#9616e8','#d8be43','#ade83c','#1727fb','#4aa5c0'];
			var polylist = [];
			for(var i = 0; i < colorlist.length; i++)
				polylist[i] = new google.maps.Polyline({
				    strokeColor: colorlist[i],
				    strokeOpacity: 1.0,
				    strokeWeight: 2
				});			
				
			function initialize() {
			  
			  var cedarrapids = new google.maps.LatLng(41.9831, -91.6686);
			  var mapOptions = {
			    zoom:12,
			    center: cedarrapids
			  }
			  map = new google.maps.Map(document.getElementById("map-canvas"), mapOptions);
			  
			}
			
			var p = 0;
			function calcRoute(start,end) {
				if(p == polylist.length) p = 0;
				var directionsDisplay = new google.maps.DirectionsRenderer({
					polylineOptions: polylist[p],
					preserveViewport: true
				});
				p++;
				directionsDisplay.setMap(map);

			  var request = {
			    travelMode: google.maps.TravelMode.DRIVING
			  };
			  if($('input[name="only_starts"]:checked').length > 0) request.origin = start;
			  if($('input[name="only_ends"]:checked').length > 0) request.destination = end;

				if(request.origin && request.destination)
				  directionsService.route(request, function(result, status) {
				    if (status == google.maps.DirectionsStatus.OK) {
				      directionsDisplay.setDirections(result);
				    }
				  });
				else if(request.origin) {
					if(typeof request.origin !== 'string')
						var marker = new google.maps.Marker({
							position: request.origin,
							map: map
						});
					else {
						geocoder.geocode( { 'address': request.origin}, function(results, status) {
					    if (status == google.maps.GeocoderStatus.OK) {
					      var marker = new google.maps.Marker({
					          map: map,
					          position: results[0].geometry.location
					      });
					    } else console.log('Geocode was not successful for the following reason: ' + status);
						});
					}
				}
				else if(request.destination) {
					if(typeof request.destination !== 'string')
						var marker = new google.maps.Marker({
							position: request.destination,
							map: map
						});
					else {
						geocoder.geocode( { 'address': request.destination}, function(results, status) {
					    if (status == google.maps.GeocoderStatus.OK) {
					      var marker = new google.maps.Marker({
					          map: map,
					          position: results[0].geometry.location
					      });
					    } else console.log('Geocode was not successful for the following reason: ' + status);
						});										
					}
				}
			}
      google.maps.event.addDomListener(window, 'load', initialize);

      $(function() {
	      $('#filter-control button').on('click',function() {
	      	$.get('mapproject_getjson.php', $('#filter-control form').serialize() ,function(data) {
	      		initialize();
						loopCalc(data,0);
	      	},'json');
	      	return false;
	      });
	      
	      function loopCalc(data,i) {
	      	return Promise.delay(700)
	      		.then(function() {
	      			if(i < data.length) {
		      			var s = data[i][0]["Latitude"] === null 
		      				? data[i][0]["Address1"]+' '+data[i][0]["City"]+' '+data[i][0]["State"]
		      				: new google.maps.LatLng(data[i][0]["Latitude"], data[i][0]["Longitude"]);
		      			var e = data[i][1]["Latitude"] === null 
		      				? data[i][1]["Address1"]+' '+data[i][1]["City"]+' '+data[i][1]["State"]
		      				: new google.maps.LatLng(data[i][1]["Latitude"], data[i][1]["Longitude"]);
		      			console.log(s,e);	
		      			calcRoute(s,e);   
		      		}
		      		loopCalc(data,i+=1);   			
	      		});
	      }
	      
	      $('.dts').datepicker({
	      	defaultDate: new Date(),
	      	showAnim: 'slide'
	      });
	      $('.dts').val( $.datepicker.formatDate('mm/dd/yy',new Date()) );
	      $('input[name="days_all"]').on('click',function() {
	      	if($(this).prop('checked')) $('.day_sel').prop('checked',false);
	      });
	      $('.day_sel').on('click',function() {
	      	if($(this).prop('checked')) $('input[name="days_all"]').prop('checked',false);
	      });
	    });
    </script>
  </head>
  <body>
<div id="map-canvas"></div>
<div id="filter-control">
	<form>
	<input type=hidden name=driver value=419>
	<table>
		<tr><th></th><th><b><u>Day Range</u></b></th><th><b><u>Time Range</u></b></th></tr>	
		<tr>
			<td>From:</td>
			<td><input class=dts name=date_from></span></td>
			<td><select class=tim name=time_from>
			<?php	
				$t = strtotime('12:00am');
				while($t < strtotime('11:59pm')) {
					$ts = date('g:ia',$t);
					echo "<option value='$ts'>$ts</option>";
					$t = strtotime('+1 hour',$t);
				}
			?>	
			</select></td>
		</tr>
		<tr>
			<td>To:</td>	
			<td><input class=dts name=date_to></span></td>
			<td><select class=tim name=time_to>
			<?php	
				$t = strtotime('12:00am');
				while($t < strtotime('11:59pm')) {
					$ts = date('g:ia',$t);
					echo "<option value='$ts'>$ts</option>";
					$t = strtotime('+1 hour',$t);
				}
			?>	
			</select></td>
		</tr>
		<tr>
			<td colspan=3>
				<input type=checkbox name=days_all>All&nbsp;
				<input type=checkbox name=days[Sun] class=day_sel>Sun&nbsp;	
				<input type=checkbox name=days[Mon] class=day_sel>Mon&nbsp;	
				<input type=checkbox name=days[Tue] class=day_sel>Tue&nbsp;	
				<input type=checkbox name=days[Wed] class=day_sel>Wed&nbsp;	
				<input type=checkbox name=days[Thu] class=day_sel>Thu&nbsp;	
				<input type=checkbox name=days[Fri] class=day_sel>Fri&nbsp;	
				<input type=checkbox name=days[Sat] class=day_sel>Sat&nbsp;	
			</td>	
		</tr>
		<tr>
			<td colspan=3>
				<input type=checkbox name=only_starts checked> Starts&nbsp;&nbsp;
				<input type=checkbox name=only_ends checked> Ends	
			</td>	
		</tr>
	</table>
	<button>Filter</button>	
	</form>
</div>
  </body>
</html>