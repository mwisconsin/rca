<?php
require_once('include/functions.php');
require_once('include/mapquest_distance.php');  // for backup
require_once('include/google_maps.php');
$MAPQUEST_API_KEY = MAPQUEST_API_KEY; //"Dmjtd%7Clu612hurng%2Cas%3Do5-50zah";

$MAPQUEST_API_MATRIX_URL = "https://open.mapquestapi.com/directions/v2/routematrix?key=$MAPQUEST_API_KEY";


/**
 * Converts a standard address hash to a Mapquest API Addr/City/State/ZIP string.
 * @param $address standard address hash
 * @return Mapquest API string for the address
 */
function address_to_mapquest_api_string($address) {
	$address['Address1'] = strip_apt_unit_etc($address['Address1']);
    return urlencode( "{$address['Address1']}, {$address['City']}, {$address['State']}" );
}

function address_to_geocode_string($address){
	return "{$address['Latitude']},{$address['Longitude']}";
}

/**
 * Gets the total distance and time between two addresses
 * @param $from_address starting address
 * @param $to_address ending address
 * @param $check_both check from->to distance and to->from distance (FS#76)
 * @return returns hash with distance in miles and time in seconds.  Keys:  distance, time
 */
function get_mapquest_time_and_distance($from_address, $to_address, $check_both = TRUE, $desiredArrivalTime = '') {

	/* if From address or To address is empty */
	if(!array_filter($from_address) || !array_filter($to_address)) {
		//echo "<!-- Note: Return Zero distance due to incomplete addresses -->\n";
		return array('distance' => $miles, 'time' => $seconds);
	}

	if ($from_address['VerifySource'] == 'Geocode') {
		$mapquest_from = address_to_geocode_string($from_address);
	} else {
		$mapquest_from = address_to_mapquest_api_string($from_address);
	}
	
	if ($to_address['VerifySource'] == 'Geocode') {
		$mapquest_to = address_to_geocode_string($to_address);
	} else {
		$mapquest_to = address_to_mapquest_api_string($to_address);
	}

    $results = get_mapquest_time_and_distance_results($mapquest_from, $mapquest_to, $desiredArrivalTime);
    if ($check_both) {
        $to_from_results = get_mapquest_time_and_distance_results($mapquest_to, $mapquest_from, $desiredArrivalTime);

        if ($to_from_results['distance'] < $results['distance']) {
            $results = $to_from_results;  // FS#76
        }
    }

    return $results;
}

function get_mapquest_time_and_distance_results($mapquest_from_string, $mapquest_to_string, $desiredArrivalTime = '') {
//echo 'from: '.$mapquest_from_string.'<br />to: '.$mapquest_to_string;
	global $MAPQUEST_API_MATRIX_URL;
// echo $mapquest_from_string;
// echo '<br>';
// echo $mapquest_to_string;
// echo '<br>';
// echo '----<br>';

// 	$sql = "select * from mapquest_cache where mapquest_from_string = '$mapquest_from_string' and mapquest_to_string = '$mapquest_to_string' and cache_date > TIMESTAMP( DATE_SUB( NOW( ) , INTERVAL 90 DAY ) )";
// 	echo $sql;
// 	$r = mysql_query($sql);
// 	if(mysql_num_rows($r) > 0) {
// 		$rs = mysql_fetch_array($r);
// 		return array('distance' => $rs["miles"], 'time' => $rs["seconds"]);
// 	}
	
	echo "<!-- REQUESTING FROM MAPQUEST -->\n";
	
    $request_url = $MAPQUEST_API_MATRIX_URL . 
                   "&ambiguities=ignore&outFormat=json&from=$mapquest_from_string&to=$mapquest_to_string" .
                   "&routeType=shortest";

	//echo $request_url;
	//echo '<br>';
	//echo '<br>';
	
    // Using cURL for better error detection and handling.
	
    $ch = curl_init();
	//echo '<br /><br />URL: '. $request_url . '<br /><br />';
    curl_setopt($ch, CURLOPT_URL, $request_url);   
    curl_setopt($ch, CURLOPT_HEADER, FALSE);   
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
    $response = curl_exec($ch);
    
	//echo $response;
	//echo '<br>';
	//echo '<br>';
	
	$decoded_response = json_decode($response, true);
	//echo '<br /><br />';
	//print_r($decoded_response);
    $miles = $decoded_response['distance'][1];
    $seconds = $decoded_response['time'][1];
    
    if (is_null($miles) || is_null($seconds)) {
        $xml_time_distance = get_xml_api_mapquest_time_and_distance($mapquest_from_string, $mapquest_to_string);
        $miles = $xml_time_distance['distance'];
        $seconds = $xml_time_distance['time'];
    }
	if($miles == 0 || $miles > 30 || is_null($miles) || $seconds == 0 || is_null($seconds)){
	    //echo 'google request made: '.$mapquest_from_string.' - '.$mapquest_to_string;
	   
	  // NOTE: Forced desiredArrivaltime to be '' because we don't want to cache that specific time for 90 days.
		$google_request = get_google_time_and_distance_results($mapquest_from_string, $mapquest_to_string, '');
		$miles = $google_request['distance'];
		$seconds = $google_request['time'];
	}
	
	$sql = "insert into mapquest_cache (mapquest_from_string, mapquest_to_string, miles, seconds) values ('"
		.mysql_real_escape_string($mapquest_from_string)."','".mysql_real_escape_string($mapquest_to_string)."',$miles,$seconds)";
	mysql_query($sql);
	
    return array('distance' => $miles, 'time' => $seconds);
}


/*  Usage Example 

    $address_home = array('Address1' => '4705 Sugar Pine Dr NE',
                          'City' => 'Cedar Rapids',
                          'State' => 'IA',
                          'ZIP5' => '52402');
    $address_wab = array('Address1' => '5250 N River Blvd NE',
                         'City' => 'Cedar Rapids',
                         'State' => 'IA',
                         'ZIP5' => '52411');

    $time_dist = get_mapquest_time_and_distance($address_home, $address_wab);
    echo "<pre>" . var_export($time_dist, TRUE) . "</pre>";
    */
?>
