<?php
//http://maps.googleapis.com/maps/api/distancematrix/json?origins=5111+broadlawn+dr+se,+Cedar+Rapids+IA+52403&destinations=2210+Martin+Gilman,+Ames+IA+50012&units=imperial&sensor=false

function get_google_time_and_distance_results($mapquest_from_string, $mapquest_to_string, $desiredArrivalTime = '') {
	//echo "GOOGLE REQUEST";
	
		$mapquest_from_string = str_replace("DESOTO","DESTO",$mapquest_from_string);
		$mapquest_to_string = str_replace("DESOTO","DESTO",$mapquest_to_string);
		$departure_time = strtotime($desiredArrivalTime);
	
    $request_url =  "https://maps.googleapis.com/maps/api/distancematrix/json?origins=$mapquest_from_string"
    	."&destinations=$mapquest_to_string&units=imperial&sensor=false"
    	."&key=".GOOGLE_API_KEY
    	.($desiredArrivalTime != '' ? "&departure_time=$departure_time" : "");

		//echo $request_url."<BR><BR>";
		
    // Using cURL for better error detection and handling.
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $request_url);   
    curl_setopt($ch, CURLOPT_HEADER, FALSE);   
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
    $response = curl_exec($ch);
    
		$decoded_response = json_decode($response, true);
		
		//print_r($decoded_response);
		
    $miles = ($decoded_response['rows'][0]['elements'][0]['distance']['value'] / 1609.344);
    $seconds = $decoded_response['rows'][0]['elements'][0]['duration']['value'];

    return array('distance' => round($miles,1), 'time' => $seconds);
}
?>