<?php
chdir('..');
	require_once 'include/destinations.php';
    require_once 'include/large_facility.php';
    // TODO:  set error message if user not logged in - potential intrusion vector
	/**
	 * {
	 *	"error": 0,
     *	"errorMessage": null,
     *	"destinationID": 2,
     *	"destinationName": "test"
	 * }
	 * 
	 **/
	echo "{";
    if(isset($_POST['Address1'])){
		$error = FALSE;
		$fields = array('Destination','Address1','City','State','Zip5');
		foreach($fields as $k => $v){
			if($_POST[$v] == '' || !isset($_POST[$v])){
				$error = TRUE;
			}
				
		}
		
		if(!$error){
			$address = array('Address1' => $_POST['Address1'],
							 'Address2' => $_POST['Address2'],
							 'City' => $_POST['City'],
							 'State' => $_POST['State'],
							 'ZIP5' => $_POST['Zip5'],
							 'ZIP4' => $_POST['Zip4']);

            // For a full admin, LF ID takes precedence over a rider ID.
            $affected_user_id = get_affected_user_id();
            $franchise_id = get_current_user_franchise(false);
			
			$new_destination = create_new_destination($_POST['Destination'], $address, $franchise_id,
                                                $_POST['Public'], NULL, $_POST['DestinationGroup'], $_POST['DestinationPhone'], 
                                                $_POST['DestinationDetail'], $_POST['DestinationPhoneExt']);
			add_destination_for_rider($affected_user_id,$new_destination);
			$destination = get_destination($new_destination);
			
			echo '"error": 0,"errorMessage": null,"destinationID": ' . $new_destination . 
                 ',"destinationName": "' . $destination['Name'] . '", "destinationAddress": "' . 
                 "{$destination['Address1']}, {$destination['City']}, {$destination['State']} {$destination['ZIP5']}" . 
                 "\", \"destinationDetail\": \"{$destination['DestinationDetail']}\"";

		} else {
			echo '"error": 1,"errorMessage": "One or more required fields were not filled.","destinationID": null,"destinationName": null';
		}
	} else {
		echo '"error": 1,"errorMessage": "Not all data was recieved."';
	}
	
	echo "}";
	chdir('xhr/');
?>
