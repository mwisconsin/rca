<?php

	require_once(__DIR__ . '/../../' . 'private_include/riders_club_params.php');
	
	require_once 'include/user.php';
	require_once 'include/rider.php';
	require_once 'include/driver.php';
	require_once 'include/franchise.php';
	
    if(isset($_SESSION['UserID']))
        $user = $_SESSION['UserID'];
    /**
    * Get Site URL
    *
    * The function site_url() returns the URL of the server its on
    * 
    * @author Joel Bixby 
    */
    function site_url()
    {
				$isSecure = false;
				if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
				    $isSecure = true;
				}
				elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') {
				    $isSecure = true;
				}
				$REQUEST_PROTOCOL = $isSecure ? 'https' : 'http';
				$link = $REQUEST_PROTOCOL."://".$_SERVER["SERVER_NAME"].'/';   	
    	
    	
        return $link;
    }


function get_state(){
	if(is_logged_in()){
		
		$franchise_id = mysql_real_escape_string(get_current_user_franchise(false));
		$sql = "SELECT State FROM address WHERE ZIP5 IN (SELECT ZIP5 FROM franchise_service_area WHERE FranchiseID = '$franchise_id') AND `VerifySource` = 'USPS' Limit 1;";
		$result = mysql_query($sql) or die(mysql_error());
		
		if($result){
			
			$result = mysql_fetch_array($result);
			return $result['State'];	
		} else {
			return FALSE;
		}
	}
}

/**
 * Returns a hash containing all states.  Keys are the two-letter abbreviation (e.g. IA),
 * values are the full names (e.g. Iowa).
 */
function get_state_dropdown($prefix = '', $selected_state = NULL, $postfix = '') {
	
    $states = array(
        'AL' => 'Alabama',
        'AK' => 'Alaska',
        'AZ' => 'Arizona',
        'AR' => 'Arkansas',
        'CA' => 'California',
        'CO' => 'Colorado',
        'CT' => 'Connecticut',
        'DE' => 'Delaware',
        'FL' => 'Florida',
        'GA' => 'Georgia',
        'HI' => 'Hawaii',
        'ID' => 'Idaho',
        'IL' => 'Illinois',
        'IN' => 'Indiana',
        'IA' => 'Iowa',
        'KS' => 'Kansas',
        'KY' => 'Kentucky',
        'LA' => 'Louisiana',
        'ME' => 'Maine',
        'MD' => 'Maryland',
        'MA' => 'Massachusetts',
        'MI' => 'Michigan',
        'MN' => 'Minnesota',
        'MS' => 'Mississippi',
        'MO' => 'Missouri',
        'MT' => 'Montana',
        'NE' => 'Nebraska',
        'NV' => 'Nevada',
        'NH' => 'New Hampshire',
        'NJ' => 'New Jersey',
        'NM' => 'New Mexico',
        'NY' => 'New York',
        'NC' => 'North Carolina',
        'ND' => 'North Dakota',
        'OH' => 'Ohio',
        'OK' => 'Oklahoma',
        'OR' => 'Oregon',
        'PA' => 'Pennsylvania',
        'RI' => 'Rhode Island',
        'SC' => 'South Carolina',
        'SD' => 'South Dakota',
        'TN' => 'Tennessee',
        'TX' => 'Texas',
        'UT' => 'Utah',
        'VT' => 'Vermont',
        'VA' => 'Virginia',
        'WA' => 'Washington',
        'WV' => 'West Virginia',
        'WI' => 'Wisconsin',
        'WY' => 'Wyoming',
    );
	if($selected_state == NULL)
		$selected_state = get_state();
	?>
    <select name="<?php echo $prefix; ?>State<?php echo $postfix; ?>" id="<?php echo $prefix; ?>State<?php echo $postfix; ?>"><?php
                	
                    foreach ($states as $state_abbreviation => $state_name) {
                    	// We only want the abbreviations for now.
                        echo '<option value="' . $state_abbreviation . '" ';
						if($state_abbreviation == $selected_state || $state_name == $selected_state )
							echo 'SELECTED';
						echo '>' . $state_abbreviation . '</option>';
                } ?></select>
	<?php
}

function get_admin_drop_down($franchise, $update = FALSE){
	$safe_franchise = mysql_real_escape_string($franchise);
	$update_time = $_SESSION['admin_dropdown_time'];
	$array = $_SESSION['admin_dropdown_array'];
	if($array == NULL || $update == TRUE || (time() - $update_time) > 10) {//00){
		$dropdown = array('main_href' => 'admin.php');
		
		
		
		
		
		$admin_nav = array('Approved&nbsp;Users' => 'users.php?type=all');
		$dropdown = array_merge($dropdown, $admin_nav);
		$admin_nav = array('Add&nbsp;User' => 'add_user.php');
		$dropdown = array_merge($dropdown, $admin_nav);
		$admin_nav = array('Annual&nbsp;Fees&nbsp;Due' => 'user_updates.php?type=annualfee');
		$dropdown = array_merge($dropdown, $admin_nav);
		$subnav1_total = 0;
		$subnav1 = array('main_href'=>'#');
		$background_checks = sql_num_rows("SELECT COUNT(*) FROM `users` WHERE `Status` = 'ACTIVE' AND `ApplicationStatus` = 'APPLIED' AND UserID IN (SELECT UserID FROM user_role WHERE FranchiseID = $safe_franchise);");
		//if($background_checks[0] > 0) {
			$subnav1 = array_merge($subnav1, array( 'Applications&nbsp;(&nbsp;' . $background_checks[0] . '&nbsp;)' => 'users.php?type=applicants' ));
			//$subnav1 = array_merge($subnav1, array( 'Pending&nbsp;Background&nbsp;Checks&nbsp;(&nbsp;' . $background_checks[0] . '&nbsp;)' => 'users.php?type=applicants' ));
		//}
		$subnav1_total += $background_checks[0];
		$pending_user_charities = sql_num_rows("SELECT COUNT(*) FROM charity WHERE Approved = 'N';");
		//if ($pending_user_charities[0] > 0) {
			$subnav1 = array_merge($subnav1, array( 'Charities&nbsp;(&nbsp;' . $pending_user_charities[0] . '&nbsp;)' => 'admin_charity_request.php' ));
		//}
		$subnav1_total += $pending_user_charities[0];
		$pending_donations = sql_num_rows("SELECT COUNT(*) FROM donation WHERE FranchiseID = $safe_franchise AND PaymentReceived = 'N' OR DonorThanked = 'N' AND `DonationTime` != '0000-00-00';");
		//if ($pending_donations[0]>0) {
			$subnav1 = array_merge($subnav1, array( 'Donations&nbsp;(&nbsp;' . $pending_donations[0] . '&nbsp;)' => 'donations.php?type=pending' ));
		//}
		$subnav1_total += $pending_donations[0];
		$pending_public_destinations = sql_num_rows("SELECT Count(*) FROM `destination` WHERE `IsPublic` = 'YES' AND `IsPublicApproved` = 'NO' AND FranchiseID = $safe_franchise;");
		//if ($pending_public_destinations[0]>0)  {
		    $subnav1 = array_merge($subnav1, array( 'Public&nbsp;Destinations&nbsp;(&nbsp;' . $pending_public_destinations[0] . '&nbsp;)' => 'places.php' ));
		//}
		$subnav1_total += $pending_public_destinations[0];
		$admin_nav = array('Pending&nbsp;(&nbsp;'.$subnav1_total.'&nbsp;)' => $subnav1);
		$dropdown = array_merge($dropdown, $admin_nav);
		
		
		
		$subnav2 = array('main_href'=>'#');
		$subnav2_total = 0;
		
		$driver_settings = sql_num_rows("SELECT COUNT(*) FROM (driver NATURAL JOIN users) LEFT JOIN driver_settings ON driver_settings.UserID = users.UserID WHERE driver_settings.UserID IS NULL AND users.status = 'Active' AND driver.DriverStatus = 'Active' AND users.UserID IN (SELECT UserID FROM user_role WHERE FranchiseID = $franchise)");
		$subnav2 = array_merge($subnav2, array( 'Driver&nbsp;Prefs&nbsp;(&nbsp;' . $driver_settings[0] . '&nbsp;)' => 'user_updates.php?type=driverpreferences' ));
		$subnav2_total += $driver_settings[0];
		
		$riders_prefs = sql_num_rows("SELECT COUNT(*) FROM (rider LEFT JOIN rider_preferences ON rider.UserID = rider_preferences.UserID) LEFT JOIN users ON users.UserID = rider.UserID WHERE rider_preferences.UserID IS NULL AND rider.RiderStatus = 'Active' AND users.Status = 'Active' AND
      users.UserID IN (SELECT UserID FROM user_role WHERE FranchiseID = $franchise)");
		$subnav2 = array_merge($subnav2, array( 'Rider&nbsp;Prefs&nbsp;(&nbsp;' . $riders_prefs[0] . '&nbsp;)' => 'user_updates.php?type=riderpreferences' ));
		$subnav2_total += $riders_prefs[0];
		
		$supporter = sql_num_rows("SELECT count(*) FROM `supporter_rider_request` srr, `user_role` ur WHERE srr.SupporterUserID=ur.UserID and ur.FranchiseID=$safe_franchise");
		$subnav2 = array_merge($subnav2, array('Supporter&nbsp;Requests&nbsp;(&nbsp;'.$supporter[0].'&nbsp;)' => 'admin_support_requests.php'));
		$subnav2_total += $supporter[0];
		
		$driver_contact = sql_num_rows("SELECT COUNT(*) FROM driver LEFT JOIN users ON driver.UserID = users.UserID WHERE driver.EmergencyContactID IS NULL AND users.Status = 'Active' AND driver.DriverStatus = 'Active' AND users.UserID IN (SELECT UserID FROM user_role WHERE FranchiseID = $franchise)");
		$subnav2 = array_merge($subnav2, array( 'Driver&nbsp;Emergency&nbsp;Contact&nbsp;(&nbsp;' . $driver_contact[0] . '&nbsp;)' => 'user_updates.php?type=drivercontact' ));
		$subnav2_total += $driver_contact[0];
		
		$rider_contact = sql_num_rows("SELECT COUNT(*) FROM rider LEFT JOIN users ON rider.UserID = users.UserID WHERE rider.EmergencyContactID IS NULL AND users.Status = 'Active' AND       rider.RiderStatus = 'Active' AND users.UserID IN (SELECT UserID FROM user_role WHERE FranchiseID = $franchise)");
		$subnav2 = array_merge($subnav2, array( 'Rider&nbsp;Emergency&nbsp;Contact&nbsp;(&nbsp;' . $rider_contact[0] . '&nbsp;)' => 'user_updates.php?type=ridercontact' ));
		$subnav2_total += $rider_contact[0];
		
		
		$admin_nav = array('Users&nbsp;Needing&nbsp;Updates&nbsp;(&nbsp;'.$subnav2_total.'&nbsp;)' => $subnav2);
		$dropdown = array_merge($dropdown, $admin_nav);
		
		$admin_nav = array('Reports' => array('main_href' => 'reports.php',
		                                      'Variable' => 'reports.php',
											  'YTD&nbsp;Summary*' => '#',
											  'Graph' => 'table_reports.php',
											  'Donation' => 'donation_reports.php',
											  'Driver&nbsp;Payout' => 'driver_payout_report.php',
											  'Driver&nbsp;Availability' => 'driver_availability_report.php',
											  'ACH to Process' => 'ACHtoProcess.php',
											  'Traffic Study' => 'reportTrafficStudy.php'
											  ));
		$dropdown = array_merge($dropdown, $admin_nav);
		$sql = "SELECT * FROM `care_facility` where FranchiseID=".(int)$franchise;
		
		$care_facility_result = mysql_query($sql);
		if (mysql_num_rows($care_facility_result)>0) {
		    $facilities = array('main_href' => 'care_facilities.php');
			while ($facility = mysql_fetch_assoc($care_facility_result)) {
				$facilities[preg_replace('/\ /', '&nbsp;', $facility['CareFacilityName'])] = 'care_facility_users.php?id='.$facility['CareFacilityID'];
			}
		    
			$admin_nav = array('Care&nbsp;Facilities' => $facilities);
	    } else {
		    $admin_nav = array('Care&nbsp;Facilities' => 'care_facilities.php');
		}
		$dropdown = array_merge($dropdown, $admin_nav);
		
		$admin_nav = array('Business Partners' => 'admin_business_partners.php');
		$dropdown  = array_merge($dropdown, $admin_nav);
		
		$admin_nav = array('Edit Assigned Club' => 'club.php');
		$dropdown  = array_merge($dropdown, $admin_nav);
		
		/* 
                        
					   
					    array('Applications' => 'users.php?type=applicants',
					                               'Background&nbsp;Checks' => 'users.php?type=applicants',
												   'Charities' =>'admin_charity_request.php',
												   'Donations' => 'donations.php?type=pending',
												   'Public Destinations' => 'places.php'),
						'Users&nbsp;Needing&nbsp;Updates&nbsp;(6)' => 
							array('Driver&nbsp;Prefs'=>'user_updates.php?type=driverpreferences',
							      'Rider Prefs' => 'user_updates.php?type=riderpreferences'));
					  
		
	
		
        $annual_fee_due = sql_num_rows("SELECT COUNT(*) FROM `rider` NATURAL JOIN users WHERE AnnualFeePaymentDate < DATE_ADD(CURDATE(), INTERVAL -1 YEAR) AND RiderStatus='Active' AND Status = 'ACTIVE' AND rider.UserID IN (SELECT UserID FROM user_role WHERE FranchiseID = $safe_franchise)");
        $rider_count = sql_num_rows("SELECT COUNT(*) FROM `rider` NATURAL JOIN users WHERE RiderStatus = 'Active' AND Status = 'ACTIVE' AND rider.UserID IN (SELECT UserID FROM user_role WHERE FranchiseID = $safe_franchise)");
		
		
		
		
		
		if($riders_prefs[0] > 0)
			$dropdown = array_merge($dropdown, array( 'Riders&nbsp;Needing&nbsp;Preferences&nbsp;(&nbsp;' . $riders_prefs[0] . '&nbsp;)' => 'user_updates.php?type=riderpreferences' ));
		if($riders_contact[0] > 0)
			$dropdown = array_merge($dropdown, array( 'Riders&nbsp;Needing&nbsp;Emergency&nbsp;Contact&nbsp;(&nbsp;' . $riders_contact[0] . '&nbsp;)' => 'user_updates.php?type=ridercontact' ));
		if($driver_settings[0] > 0)
			$dropdown = array_merge($dropdown, array( 'Drivers&nbsp;Needing&nbsp;Settings&nbsp;(&nbsp;' . $driver_settings[0] . '&nbsp;)' => 'user_updates.php?type=driversettings' ));
		if($driver_contact[0] > 0)
			$dropdown = array_merge($dropdown, array( 'Drivers&nbsp;Needing&nbsp;Emergency&nbsp;Contact&nbsp;(&nbsp;' . $driver_contact[0] . '&nbsp;)' => 'user_updates.php?type=drivercontact' ));
		if($annual_fee_due[0] == 0) { $annual_fee_due[0] = '0'; }
			$dropdown = array_merge($dropdown, array( 'Annual&nbsp;Fees&nbsp;Due&nbsp;(&nbsp;' . $annual_fee_due[0] . '&nbsp;)&nbsp;of&nbsp;' . $rider_count[0] => 'user_updates.php?type=drivercontact' ));
		if($pending_donations[0] > 0)
			$dropdown = array_merge($dropdown, array( 'Pending&nbsp;Donations&nbsp;(&nbsp;' . $pending_donations[0] . '&nbsp;)' => 'user_updates.php?type=annualfee' ));
		
			
		$dropdown = array_merge($dropdown, array( 'Care&nbsp;Facilities' => 'care_facilities.php' ));
		$dropdown = array_merge($dropdown, array( 'Large&nbsp;Facilities' => 'admin_large_facilities.php' ));
		*/
		
		$_SESSION['admin_dropdown_time'] = time();
		$_SESSION['admin_dropdown_array'] = $dropdown;
		return $dropdown;
		if(count($dropdown) <= 1)
			return 'admin.php';
	} else {
		return $_SESSION['admin_dropdown_array'];
	}
}
function sql_num_rows($sql){
	$result = mysql_query($sql);
			
	if($result){
		return mysql_fetch_array($result);
	} else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "FAILED in sql_num_rows", $sql);
		return FALSE;
	}
}

function get_navigation_bar( $user_id ) {
	$franchise = 0;
	if(is_logged_in()){
		$franchise = get_current_user_franchise(FALSE);
		$full_admin_home = array('Admin&nbsp;Home' => get_admin_drop_down($franchise));
	}
	
	$current_user_roles = get_user_roles(get_current_user_id(), $franchise);
	$ReadOnly = 0;
	if(current_user_has_role($franchise, 'Franchisee'))
		foreach($current_user_roles as $role) 
			if($role['Role'] == 'Franchisee') $ReadOnly = $role['ReadOnly'];
	if(get_current_user_id() == get_affected_user_id()) $ReadOnly = 0;
	
    $rider_nav = array('My&nbsp;Rides' => 'myrides.php', 
                      	'Schedule&nbsp;a&nbsp;ride' => 'plan_ride.php', 
                       'My&nbsp;Places' => 'my_places.php',
                       'Make&nbsp;Payment' => 'make_payment.php', 
                       'Ledger' => 'user_ledger.php');
		if($ReadOnly) {
			unset($rider_nav['Schedule&nbsp;a&nbsp;ride']);
			unset($rider_nav['Make&nbsp;Payment']);
		}
    $driver_nav = array('Manifest' => 'manifest.php',
                        'Availability' => 'driver_availability.php', 
                        'Ledger' => 'user_ledger.php');

    $care_facility_admin_nav = array('Rides' => 'care_facility_rides.php', 
                                     'Users' => 'care_facility_users.php', 
                                     'Care&nbsp;Facility' => 'care_facility.php',
									 									 'Make&nbsp;Payment' => 'make_payment.php',
                                     'Ledger' => 'cf_ledger.php');
		if($ReadOnly) {
			unset($care_facility_admin_nav['Make&nbsp;Payment']);
		}
		
    $large_facility_admin_nav = array('Schedule&nbsp;Ride' => 'large_facility_plan_ride.php', 
                                      'Destinations' => 'large_facility_set_ride_destinations.php');
		if($ReadOnly) unset($large_facility_admin_nav['Schedule&nbsp;Ride']);
		
    $admin_nav = array('Approved&nbsp;Users' => 'users.php?type=all', 
                            'Add&nbsp;User' => 'add_user.php', 
                            'Find&nbsp;Drivers' => 'admin_driver_links.php',
							'main_href' => 'admin.php',
							'Reports' => 'reports.php');
							
	


    $supportingFriend_nav = array('Support&nbsp;List' => 'support_list.php',
                           'Make&nbsp;Payment' => 'make_payment.php',
                           'Ledger' => 'user_ledger.php');
    if($ReadOnly) unset($supportingFriend_nav['Make&nbsp;Payment']);
    if(is_logged_in() && (current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee'))){
			$rider_nav = array('My&nbsp;Rides' => 'myrides.php', 
                      	'Schedule&nbsp;a&nbsp;ride' => 'plan_ride.php', 
                       'My&nbsp;Places' => 'my_places.php',
                       'Make&nbsp;Payment' => array('Payment'=>'make_payment.php', 'Manual&nbsp;Ledger&nbsp;Entry' => 'manual_ledger_entry.php'),
                       'Ledger' => 'user_ledger.php');
	    if($ReadOnly) {
	    	unset($rider_nav['Schedule&nbsp;a&nbsp;ride']);
	    	unset($rider_nav['Make&nbsp;Payment']);
	    } else
	     $supportingFriend_nav['Make&nbsp;Payment'] = array( 'main_href' => 'make_payment.php', 'Manual&nbsp;Ledger&nbsp;Entry' => 'manual_ledger_entry.php');
    }
    $empty_nav = array();

    $all_role_nav = array( 'Account' => 'account.php',
                           'Log&nbsp;Out' => 'logout.php' );

    $nav_sets = array( 'Rider' => $rider_nav,
                       'Driver' => $driver_nav,
                       'FullAdmin' => $admin_nav,
                       'Supporter' => $supportingFriend_nav,
                       'Franchisee' => $admin_nav,
                       'CareFacilityAdmin' => $care_facility_admin_nav,
                       'LargeFacilityAdmin' => $large_facility_admin_nav
                       );

	if(!is_logged_in() || !get_current_user_franchise(FALSE))
    {
        $links = array( 'Home' => 'index.php', 
                        'Who We Are' => 'whoweare.php',
                        'What You Can Do' => 'donate.php',
                        'Where We Are' => 'whereweare.php',
                        'What Others Say' => 'whatotherssay.php',
                        'Careers' => 'careers.php',
                        'Build a Club' => 'buildaclub.php' );
	} else {
		$user_roles = get_user_roles(get_affected_user_id(), $franchise);
		$current_user_roles = get_user_roles(get_current_user_id(), $franchise);

		$links = array();
		if((current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee')) && !$ReadOnly) {
					$links = array_merge($links, $full_admin_home);
		}
		$links = array_merge($links, array( 'Home' => 'home.php' ));
		
		if(count($user_roles) == 0){
			
		} else if(count($user_roles) > 1){
			foreach($user_roles as $role){
				$links = array_merge($links, array( $role['Role'] => $nav_sets[$role['Role']]) );
			}
		} else {
			if(is_array($nav_sets[$user_roles[0]['Role']]))
				$links = array_merge($links, $nav_sets[$user_roles[0]['Role']]);
		}

		if($ReadOnly)	unset($links['FullAdmin']);

		
		/*
        foreach ($nav_sets as $role => $role_nav_set) {
            if (user_has_role($user_id, $role)) {
                $links = array_merge($links, $role_nav_set);
            }
        }*/
        $links = array_merge($links, $all_role_nav);
	}
?>
    <div class="nav">
			<?php
        foreach ($links as $text => $href) {
						$html_id = str_replace('&nbsp;', '',$text);
						if($text != 'main_href'){
						echo "<div class=\"nav_link\"";
						if(is_array($href))
							echo " onmouseout=\"$('DropDown" . $html_id . "').setStyle('display','none');\" onmouseover=\"$('DropDown" . $html_id . "').setStyle('display','block');\"";
						
						echo ">";
						echo "<div class=\"nav_link_dropdown_container\" >";
						if(!is_array($href))
							echo "<a href=\"" . $href . "\">";
						else if(@$href['main_href'] != '' || @$href['main_href'] != NULL )
							echo "<a href=\"" . $href['main_href'] . "\">";
						echo $text;
						if(!is_array($href))
							echo "</a>";
						if(is_array($href)){
							echo "<div id=\"DropDown" . $html_id . "\" class=\"Nav_Drop_Down\">";
								foreach($href as $droptext => $droplink){
									$html_id = str_replace('&nbsp;', '',$droptext);
									$html_id = str_replace(')', '',$html_id);
									$html_id = str_replace('(', '',$html_id);
									if($droptext != 'main_href'){
										echo "<div class=\"nav_sub_link\"";
										if(is_array($droplink))
										echo " onmouseout=\"$('DropDown" . $html_id . "').setStyle('display','none');\" onmouseover=\"$('DropDown" . $html_id . "').setStyle('display','block');\"";
										echo ">";
										if(!is_array($droplink))
											echo "<a href=\"" . $droplink . "\">";
										else if($droplink['main_href'] != '' || $droplink['main_href'] != NULL )
											echo "<a href=\"" . $droplink['main_href'] . "\">";
										echo $droptext;
										
										if(is_array($droplink)){
										    
											echo "<div id=\"DropDown" . $html_id . "\" class=\"Nav_Drop_Down\" style=\"border:1px solid black;margin-left:50px;margin-top:-5px;box-shadow: 4px 4px 4px rgba(0,0,0,0.6);\">";
												foreach($droplink as $droptext2 => $droplink2){
												  if ($droptext2!='main_href') {
												    echo "<a href=\"" . $droplink2 . "\">";
													echo $droptext2;
													echo "</a><br />";
												  }
												}
											echo '</div>';
										}
										
										//if(!is_array($droplink))
											echo "</a></div>";
										
										/*
										echo '<a href="' . $droplink . '">' . $droptext . "</a>";
										end($href);
										if(key($href) != $droptext)
											echo '<hr>';
										if (is_array($droplink)) {
										  print_r($droplink);
										}
										*/
									}
								}
							echo "</div>";
						}
						echo "</div>";
						echo "</div>";
					}
                }
            ?>
    </div>
<?php
}

function format_dollars($cents) {
    if ($cents >= 0) {
        return sprintf('$%d.%02.2d', $cents / 100, $cents % 100);
    } else {
        $cents = abs($cents);
        return sprintf('-$%d.%02.2d', $cents / 100, $cents % 100);
    }
}

function array_sort($array, $on, $order=SORT_ASC)
{
    $new_array = array();
    $sortable_array = array();

    if (count($array) > 0) {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $k2 => $v2) {
                    if ($k2 == $on) {
                        $sortable_array[$k] = $v2;
                    }
                }
            } else {
                $sortable_array[$k] = $v;
            }
        }

        switch ($order) {
            case SORT_ASC:
                asort($sortable_array);
            break;
            case SORT_DESC:
                arsort($sortable_array);
            break;
        }

        foreach ($sortable_array as $k => $v) {
            $new_array[$k] = $array[$k];
        }
    }

    return $new_array;
}

function multisort($array, $sort_by) {
    foreach ($array as $key => $value) {
        $evalstring = '';
        foreach ($sort_by as $sort_field) {
            $tmp[$sort_field][$key] = $value[$sort_field];
            $evalstring .= '$tmp[\'' . $sort_field . '\'], ';
        }
    }
    $evalstring .= '$array';
    $evalstring = 'array_multisort(' . $evalstring . ');';
    eval($evalstring);

    return $array;
} 

function sms_mailer( $UserID = 0, $Message = '') {
	if($UserID == 0 || $Message == '') return false;
	
	$sql = "select phone.PhoneNumber, sms_providers.domain
		from phone, sms_providers, users, user_phone
		where users.UserID = $UserID and users.UserID = user_phone.UserID and user_phone.PhoneID = phone.PhoneID
		and phone.PhoneType = 'MOBILE' and phone.canSMS = 'Y' and phone.ProviderID = sms_providers.id";
	$r = mysql_query($sql);
	if(mysql_num_rows($r) == 0) return false;
	
	while($rs = mysql_fetch_assoc($r)) {
		$clean_phone = preg_replace('/[^0-9]/','',$rs["PhoneNumber"]);
		$to = $clean_phone."@".$rs["domain"];
		$from = "admin@myridersclub.com";
		$subject = "Riders Club: Important Notice!";
		$body = $Message;	
		$result = mail( $to, $subject, $body, "From: $from" );
		return $result;
	}
}
?>
