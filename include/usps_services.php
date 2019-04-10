<?php

require_once(__DIR__ . '/../../' . 'private_include/riders_club_params.php');

$USPS_RETURNED_HEADER = '';

/**
 * Standardizes an address using the USPS Address Standardization 
 * web tool.  http://www.usps.com/webtools/htm/Address-Information.htm
 * @param original_address Associative array holding the address.  
 *        Keys:  Address1, Address2, City, State, ZIP5, ZIP4
 * @return Associative array holding the standardized address.
 *         Keys are the same as for the input parameter.
 *         Success is indicated by the value associated with the key SUCCESS.
 *         If return['SUCCESS'] is FALSE, other values should not be used,
 *         but the ERRORMESSAGE key will be populated.
 */
function usps_standardize_address( $original_address ) {
    global $USPS_RETURNED_HEADER;
    $USPS_RETURNED_HEADER = '';

		if(@$original_address['Latitude'] != '' && @$original_address['Longitude'] != '') {
			// address has already been geolocated
			$original_address['SUCCESS'] = TRUE;
			return $original_address;
		}

    $xml = get_usps_address_verify_request_xml($original_address);
    $url = get_usps_webtools_url();

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);   
    curl_setopt($ch, CURLOPT_HEADER, FALSE);   
    curl_setopt($ch, CURLOPT_HEADERFUNCTION, 'usps_header_function');   
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
    curl_setopt($ch, CURLOPT_POST, TRUE);   
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'API=Verify&XML=' . ($xml));

    $response = curl_exec($ch);

    if ( usps_standardization_error_occurred( $response ) ) {
        // TODO:  Handle/log error somehow?
        // TODO:  Maybe flag as a task for an admin to look at?
        $standard_address = FALSE;
        $error_message = get_usps_error_message( $response );

        $mail_body = "URL:  $url\n" .
                     "XML:  $xml\n" .
                     "RESPONSE:  $response\n";

        mail('matt.schouten@gmail.com', 'Error in USPS APIs...again.', $mail_body);
        //echo htmlspecialchars($mail_body);
        $standard_address = array( 'SUCCESS' => FALSE,
                                   'ERRORMESSAGE' => $error_message );
    } else {
        $standard_address = parse_usps_standardization_response( $response );    
        $standard_address['ZIP5'] = $standard_address['Zip5'];
        $standard_address['ZIP4'] = $standard_address['Zip4'];
        $standard_address['SUCCESS'] = TRUE;
    }

    return $standard_address;
}

function usps_header_function($ch, $header) {
    global $USPS_RETURNED_HEADER;
    $USPS_RETURNED_HEADER .= $header;

    return strlen($header);
}

/**
 * Returns the URL to access the USPS webtools.
 * TODO:  Add a parameter to specify the service?
 */
function get_usps_webtools_url() {
    global $__USPS_WEBTOOLS_SERVER;
    
    $dll = (strpos($__USPS_WEBTOOLS_SERVER, 'test') === FALSE) ? '/ShippingAPI.dll' : '/ShippingAPITest.dll';

    return $__USPS_WEBTOOLS_SERVER . $dll;
}

/**
 * Converts an address hash to the XML for a USPS address standardization
 * request to the USPS web tool.
 * $__USPS_WEBTOOLS_USERNAME must be set in the private parameters.
 * http://www.usps.com/webtools/htm/Address-Information.htm
 * @param address Associative array holding the address.  
 *        Keys:  Address1, Address2, City, State, ZIP5, ZIP4
 * @return XML string
 */
function get_usps_address_verify_request_xml( $address ) {
    global $__USPS_WEBTOOLS_USERNAME;

    $temp_address = array();
    // XML generated must be trimmed (Address service doesn't like superfluous 
    // spaces) and any HTML-like characters escaped.
    foreach ($address as $k => $v) {
        $temp_address[$k] = htmlspecialchars(trim($v));
    }
    // Slight capitalization mismatch between us and USPS:
    $temp_address['Zip5'] = $temp_address['ZIP5'];
    $temp_address['Zip4'] = $temp_address['ZIP4'];

    // USPS thinks Address 1 is apartment/suite/etc and address2 is suite.
    list($temp_address['Address1'], $temp_address['Address2']) = array($temp_address['Address2'], $temp_address['Address1']);

    $fields = array('Address1', 'Address2', 'City', 'State', 'Zip5', 'Zip4');
    $address_xml = '';
    foreach ($fields as $field) {
        // TODO:  May need to elide fields that are empty?
        $address_xml .= "<{$field}>{$temp_address[$field]}</{$field}>";
    }


    $xml = '<AddressValidateRequest USERID="' . $__USPS_WEBTOOLS_USERNAME . 
           '"><Address ID="0">' . 
           $address_xml . 
           '</Address></AddressValidateRequest>';

    return $xml;
}

/**
 * Determines whether an error occurred while processing a USPS standardization
 * request.
 * @param $response USPS response
 * @return TRUE if an error occurred, FALSE otherwise.
 */
function usps_standardization_error_occurred($response) {
    return (stripos($response, '<Error>') !== FALSE);
}

/**
 * Parses a USPS address standardization response into a normal address hash.
 * Right now this only handles a single address per response.
 * @param $response USPS response
 * @return Address hash.  Undefined if the response was not a valid address. 
 */
function parse_usps_standardization_response( $response ) {
    $standard_address = array();
//echo $response;
    // Quick and dirty parse.
    $parser = xml_parser_create();
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, FALSE);
    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, FALSE);
    xml_parse_into_struct($parser, $response, $values, $index);
    xml_parser_free($parser);

    // Traverse and pull out address fields
    // Expected structure is 
    // <AddressValidateResponse>
    //    <Address ID="#">
    //       <Address1>...</Address1>
    //       <Address2>...</Address2>
    //       <City>...</City>
    //       <State>...</State>
    //       <Zip5>...</Zip5>
    //       <Zip4>...</Zip4>
    //       <ReturnText>Iff multiple addresses found</ReturnText>
    //    </Address>
    // </AddressValidateResponse>

    $in_address = FALSE;

    // TODO:  Error checking, like making sure it's an AddressValidateResponse
    // (Then define behavior on errors)

    // Quick and dirty traversal state machine
    foreach ($values as $xml_event) {
        if ($in_address && isset($xml_event['value']) && 
                $xml_event['type'] != 'cdata') {
            $standard_address[$xml_event['tag']] = $xml_event['value'];
        }

        if ($xml_event['tag'] == 'Address' && $xml_event['type'] != 'cdata') {
            $in_address = ($xml_event['type'] == 'open');
        }
    }

    // USPS thinks Address 1 is apartment/suite/etc and address2 is suite.
    list($standard_address['Address1'], $standard_address['Address2']) = array($standard_address['Address2'], $standard_address['Address1']);
    if (is_null($standard_address['Address2'])) {
        unset($standard_address['Address2']);
    }

    /*echo "Response:  " . htmlspecialchars($response);
    echo "<br /><br />Addr: " . var_export($standard_address, TRUE);*/

    return $standard_address;
}

function get_usps_error_message( $response ) {
    // Quick and dirty:  Pull out the "description" tag.
    if ( preg_match( '/\<Description\>(.+)\<\/Description\>/', $response, $matches ) ) {
        //var_export($matches);
        return $matches[1];
    }
    return FALSE;
}

?>
