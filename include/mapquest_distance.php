<?php

#$keycode = 'Fmjtd%7Clu612h0t2u%2C2w%3Do5-5az51'; 
$MAPQUEST_CLIENT_ID = '83642';
$MAPQUEST_PASSWORD = 'aX8sA5bW'; 

$MAPQUEST_ROUTE_URL = 'http://route.free.mapquest.com';
$MAPQUEST_GEOCODE_URL = 'http://geocode.free.mapquest.com';

function get_xml_api_mapquest_time_and_distance($from_address, $to_address) {
    global $MAPQUEST_CLIENT_ID, $MAPQUEST_PASSWORD, $MAPQUEST_ROUTE_URL;

    $from_geo_address_xml = mapquest_geocode_address(array($from_address));
    $to_geo_address_xml = mapquest_geocode_address(array($to_address));

    $route_request_xml = <<<XML
<DoRoute Version="2">
<LocationCollection Count="2">
    {$from_geo_address_xml}
    {$to_geo_address_xml}
</LocationCollection>

<RouteOptions>
    <NarrativeType>-1</NarrativeType>
</RouteOptions>

<Authentication Version="2">
    <Password>{$MAPQUEST_PASSWORD}</Password>
    <ClientId>{$MAPQUEST_CLIENT_ID}</ClientId>
</Authentication>
</DoRoute>
XML;

    $post_fields = "e=5&$route_request_xml";
    $url = $MAPQUEST_ROUTE_URL. '/mq/mqserver.dll';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);   
    curl_setopt($ch, CURLOPT_HEADER, FALSE);   
    //curl_setopt($ch, CURLOPT_HEADERFUNCTION, 'some header func name');   
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
    curl_setopt($ch, CURLOPT_POST, TRUE);   
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);

    $response = curl_exec($ch);

    $dist_time = parse_route_distances_from_mapquest_response( $response );

    $distance = ((int)($dist_time['miles'] * 100)) / 100;

    return array('distance' => $distance, 'time' => $dist_time['seconds']);
}



function mapquest_geocode_address($address) {

    // TODO:  There is a batch geocode function that could speed up 
    //        geocoding multiple addresses.  That would be useful.
    // TODO:  Caching geocoded addresses could be good too.
    global $MAPQUEST_CLIENT_ID, $MAPQUEST_PASSWORD, $MAPQUEST_GEOCODE_URL;
    // TODO:  Make sure all fields are set

    // Escape any characters in the address that might break Mapquest's parser
    foreach ($address as $k=>$v) {
        $address[$k] = urlencode($v);
    }

	$address['Address1'] = strip_apt_unit_etc($address['Address1']);
	
    // Generate the geocode XML request
    $geocode_xml = <<<GEOCODE
<Geocode Version="1">
    <Address>
        <AdminArea1>US</AdminArea1>
        <AdminArea3>{$address['State']}</AdminArea3>
        <AdminArea5>{$address['City']}</AdminArea5>
        <PostalCode>{$address['ZIP5']}</PostalCode>
        <Street>{$address['Address1']}</Street>
    </Address>
    <Authentication Version="2">
        <Password>{$MAPQUEST_PASSWORD}</Password>
        <ClientId>{$MAPQUEST_CLIENT_ID}</ClientId>
    </Authentication>
</Geocode>
GEOCODE;

    $post_fields = "e=5&$geocode_xml";

    $url = $MAPQUEST_GEOCODE_URL . '/mq/mqserver.dll';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);   
    curl_setopt($ch, CURLOPT_HEADER, TRUE);   
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
    curl_setopt($ch, CURLOPT_POST, TRUE);   
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);

    $response = curl_exec($ch);

    // Chances are we'll want to use the GeoAddress element verbatim.  
    // Ignore other elements for now.  YAGNI.

    if (preg_match('/(<GeoAddress.+?<\/GeoAddress>)/', $response, $matches)) {
        $geo_address = $matches[1];
    }

    // TODO:  Error handling
    return $geo_address;
}


function parse_route_distances_from_mapquest_response( $response ) {

    // Quick and dirty parse.
    $parser = xml_parser_create();
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, FALSE);
    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, FALSE);
    xml_parse_into_struct($parser, $response, $values, $index);
    xml_parser_free($parser);

    // Traverse and pull out address fields
    // Expected structure is 
    // <DoRouteResponse>
    //   <RouteResults>
    //      <TrekRoutes Count=#">
    //         <TrekRoute>
    //           <Maneuvers Count="4">
    //                <Maneuver Version="1">
    //                    <Streets Count="1">
    //                        <Item>16TH AVE</Item>
    //                    </Streets>
    //                    <TurnType>0</TurnType>
    //                    <Distance>0.081000</Distance>
    //                    <Time>18</Time>
    //                    <Direction>8</Direction>
    //                </Maneuver>
    //         </TrekRoute>
    //      </TrekRoutes>
    //   </RouteResults>
    // </DoRouteResponse>

    $in_trekroute = FALSE;

    $distance = array();
    $time = array();

    // Quick and dirty traversal state machine
    foreach ($values as $xml_event) {
        if ($in_trekroute && $xml_event['tag'] == 'Distance' && isset($xml_event['value'])) {
            $distance[$curr_trekroute] += $xml_event['value'];
        } elseif ($in_trekroute && $xml_event['tag'] == 'Time' && isset($xml_event['value'])) {
            $time[$curr_trekroute] += $xml_event['value'];
        }

        if ($xml_event['tag'] == 'TrekRoute' && $xml_event['type'] != 'cdata') {
            $in_trekroute = ($xml_event['type'] == 'open');

            if ($in_trekroute) {
                $curr_trekroute++;
            }
        }
    }

    if (count($distance) == 0 || count($time) == 0) {
        return FALSE;
    }

    $max_dist_index = 0;
    $max_time_index = 0;

    foreach ($distance as $idx => $d) {
        if ($d > $distance[$max_dist_index]) {
            $max_dist_index = $idx;
        }
    }

    foreach ($time as $idx => $t) {
        if ($t > $time[$max_time_index]) {
            $max_time_index = $idx;
        }
    }

    return array('miles' => $distance[$max_dist_index], 
                 'seconds' => $time[$max_time_index]);
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
		$index = stripos($new_address,' TRLR ');
	
	if($index == 0)
		$index = stripos($new_address,' RM ');
	
	if($index == 0)
		$index = stripos($new_address,' # ');
	
	if($index > 0)
		$new_address = substr($new_address, 0, $index);
		
	return $new_address;
}
   
/*

Application Authentication:
Client ID    : 83642
Password      : aX8sA5bW
API Keycode  : Fmjtd%7Clu612h0t2u%2C2w%3Do5-5az51

API Servers:
Standard Maps : map.free.mapquest.com (port 80)
Routing      : route.free.mapquest.com (port 80)
Geocoding    : geocode.free.mapquest.com (port 80)
Spatial Search: spatial.free.mapquest.com (port 80)
   */
/*
    $address_home = array('Address1' => '4705 Sugar Pine Dr NE',
                          'City' => 'Cedar Rapids',
                          'State' => 'IA',
                          'ZIP5' => '52402');
    $address_wab = array('Address1' => '5250 N River Blvd NE',
                         'City' => 'Cedar Rapids',
                         'State' => 'IA',
                         'ZIP5' => '52411');
    $address_wab = array('Address1' => '2965 16th Ave',
                         'City' => 'Marion',
                         'State' => 'IA',
                         'ZIP5' => '52302');

    get_xml_api_mapquest_time_and_distance($address_home, $address_wab);
    echo "<pre>" . var_export($response, TRUE) . "</pre>";
    echo "---------";

    echo date('Y/m/d h:I:s');
/**/

?>
