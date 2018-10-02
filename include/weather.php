<?php

$WEATHER_PARTNER_ID = 1212900376;
$WEATHER_LICENSE_KEY = '2f63870e07a18fd9';

$post_loaded_weather_delay;

function get_weather($zip_code){
	global $WEATHER_PARTNER_ID;
	global $WEATHER_LICENSE_KEY;
	$weather_url = "http://xoap.weather.com/weather/local/$zip_code?cc=*&dayf=5&link=xoap&prod=xoap&par=$WEATHER_PARTNER_ID&key=$WEATHER_LICENSE_KEY";
	//echo $weather_url;
	$ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $weather_url);   
    curl_setopt($ch, CURLOPT_HEADER, FALSE);   
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 

    $response = curl_exec($ch);
	$xml_array = simplexml_load_string($response);
	$xml_array = weather2array($xml_array);
	return $xml_array;
}
function weather2array($object) { return @json_decode(@json_encode($object),1); } 


?>