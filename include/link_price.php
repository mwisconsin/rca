<?php

require_once('include/destinations.php');
require_once('include/business_partners.php');
require_once('include/franchise.php');
require_once('include/mapquest.php');
require_once('include/destinations.php');

function outside_normal_times( $link ) {
	
	$sql = "select * from scheduling_afterhours where FranchiseID = ".get_current_user_franchise();
	$rs = mysql_fetch_assoc(mysql_query($sql));
	
	$dn = strtolower(substr(date("l",$link['leave_time']),0,2));
	$bd = new DateTime( $rs['before_'.$dn] );
	$ad = new DateTime( $rs['after_'.$dn] );
	if(date("Hi",$link['leave_time']) < $bd->format("Hi") || date("Hi",$link['leave_time']) > $ad->format("Hi")) return true;
	#echo date("Hi",$link['arrive_time'])." < ".$bd->format("Hi")." || ".date("Hi",$link['arrive_time'])." > ".$ad->format("Hi")."<BR>";
	if(date("Hi",$link['arrive_time']) < $bd->format("Hi") || date("Hi",$link['arrive_time']) > $ad->format("Hi")) return true;	
	
}

function get_context_link_price($link, $distance, $franchise_id, $from_dest_id, $to_dest_id, $link_date) {
	$service_area_zips = get_franchise_service_zips($franchise_id);
	$is_out_of_area = FALSE;
  $out_of_area_link = FALSE;
    
  $from_dest = get_destination($from_dest_id);
  $to_dest = get_destination($to_dest_id);
  if (!$service_area_zips[$to_dest['ZIP5']]	&& $to_dest['is_local_area_override'] != TRUE) {
    $is_out_of_area = TRUE;
    $out_of_area_link = TRUE;
  }
  if (!$service_area_zips[$from_dest['ZIP5']]	&& $from_dest['is_local_area_override'] != TRUE) {
    $is_out_of_area = TRUE;
    $out_of_area_link = TRUE;
  }

  $distance_and_time = get_mapquest_time_and_distance( $from_dest, $to_dest, $link['DesiredArrivalTime'] );
  $link_distance = round($distance_and_time['distance'], 2);
  $link_minutes = ceil($distance_and_time['time'] / 60.0);

	$link_price = @$link['QuotedCents'];
	
	if($link_price == '' || $link_price == 0) {
	  if ($out_of_area_link)
	  	$link_price = get_out_of_area_link_price($link_distance, $franchise_id, $from_dest['DestinationID'], $to_dest['DestinationID']);
	  else
	   	$link_price = get_link_price($distance, $franchise_id, $from_dest_id, $to_dest_id, $link_date);
	    
	  if($out_of_area_link)
			// Minimum Price 10.00 for out_of_area
	    $link_price['Total'] = $link_price['Total'] < 1000 ? 1000 : $link_price['Total'];
		
		// Double link price in the case of more than 2 riders
		$link_price['Total'] = $link_price['Total'] * ($link['ridercount'] > 2 ? 2 : 1);
	
	  if(outside_normal_times( $link )) {
	  	$sql = "select * from scheduling_afterhours where FranchiseID = ".get_current_user_franchise();
			$rs = mysql_fetch_assoc(mysql_query($sql));	
			$link_price['Total'] += ($rs['amount_of_charge']*100);
			$link_price['RiderShare'] += ($rs['amount_of_charge']*100);
		}
	} else {
		$link_price = array(
			'Total' => $link_price,
			'RiderShare' => $link_price
		);
	}
		
	return $link_price;
}

function get_taxi_link_price($distance) {
	// 3.00 in the door, plus 3.00 per mile (mileage rounded up).
	$quote = [];
	$quote['Total'] = 300 + (ceil($distance) * 300);
	$quote['RiderShare'] = $quote['Total'];
	return $quote;
}

/**
 * Returns the price of a link of a given distance in cents.  Can be set on a per-franchise basis.
 * @param $distance link distance in miles.
 * @param $franchise_id franchise setting rate card
 * @param $from_dest_id origination
 * @param $franchise_id termination
 * @return price in cents.
 */
function get_link_price($distance, $franchise_id, $from_dest_id, $to_dest_id, $link_date) {
    // TODO: May need to base the quote on the ride date, not current
    $card = get_current_rate_card($franchise_id, $link_date);

    if ($card === FALSE) {
        return FALSE;  // TODO: Default for not-found card or no valid max dist
    }
    $quote = array( 'Total' => 0 );
    $quote['Total'] = 0;  // TODO:  Default?
    foreach ($card as $rate) {
        $quote['Total'] = $rate['Cents'];
        #echo $rate['MaxDistance'] . '<br>';
        if ($rate['MaxDistance'] >= $distance) {
            break;
        }
    }

    $from_tags = get_destination_tags($from_dest_id);
    $to_tags = get_destination_tags($to_dest_id);

    // Right now, just looking for DOUBLEFEE.
    // In the future, look for business partners.
    if ($from_tags['DOUBLEFEE'] || $to_tags['DOUBLEFEE']) {
        $quote['Total'] *= 2;
    }

    if ($from_tags['BUSINESS_PARTNER']) {
        // Get business partner terms so we can check dates and amounts
        $partner_terms = get_business_partner_terms_on_date( $from_tags['BUSINESS_PARTNER']['TagInfo1'], $link_date );

        // If the terms are valid, get the amount.
        if ($partner_terms && $partner_terms['TravelType'] != 'TO_ONLY') {
            if ($partner_terms['PaymentType'] == 'PERCENTAGE') {
                $quote['FromPartnerAmount'] = ($quote['Total'] * ($partner_terms['PaymentDetails'])) / 100.0;
            } else { // FLAT_AMOUNT
                $quote['FromPartnerAmount'] = $partner_terms['PaymentDetails'];
            }
            $quote['FromPartnerID'] = $partner_terms['BusinessPartnerID'];
        }
    }

    if ($to_tags['BUSINESS_PARTNER']) {
        $partner_terms = get_business_partner_terms_on_date( $to_tags['BUSINESS_PARTNER']['TagInfo1'], $link_date );

        // If the terms are valid, get the amount.
        if ($partner_terms && $partner_terms['TravelType'] != 'FROM_ONLY') {
            if ($partner_terms['PaymentType'] == 'PERCENTAGE') {
                $quote['ToPartnerAmount'] = ($quote['Total'] * ($partner_terms['PaymentDetails'])) / 100.0;
            } else { // FLAT_AMOUNT
                $quote['ToPartnerAmount'] = $partner_terms['PaymentDetails'];
            }
            $quote['ToPartnerID'] = $partner_terms['BusinessPartnerID'];
        }
    }

    $quote['RiderShare'] = $quote['Total'] - $quote['FromPartnerAmount'] - $quote['ToPartnerAmount'];
    if ($quote['RiderShare'] < 0) {
        $quote['RiderShare'] = 0;

        if ($quote['ToPartnerID'] && $quote['FromPartnerID']) {
            // Split the difference
            $overpayment = $quote['Total'] - $quote['FromPartnerAmount'] - $quote['ToPartnerAmount'];
            $quote['ToPartnerAmount'] += ($overpayment / 2);
            $quote['FromPartnerAmount'] += ($overpayment / 2);
        } elseif ($quote['ToPartnerID']) {
            $quote['ToPartnerAmount'] = $quote['Total'];
        } elseif ($quote['FromPartnerID']) {
            $quote['FromPartnerAmount'] = $quote['Total'];
        }
    }

    return $quote;
}

function get_current_rate_card($franchise_id, $link_date) {
    $safe_franchise_id = mysql_real_escape_string($franchise_id);
    $sql = "SELECT FranchiseID, MaxDistance, Cents, EffectiveDate, ReplacedDate
            FROM rate_card
            WHERE  (ReplacedDate IS NOT NULL && '$link_date' >= EffectiveDate && '$link_date' < ReplacedDate) || (ReplacedDate IS NULL && EffectiveDate <= '$link_date' ) AND
                  FranchiseID = $safe_franchise_id
            ORDER BY MaxDistance ASC";

    $result = mysql_query($sql);

    if ($result) {
        $rate_card = array();
        while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
            $rate_card[] = $row;
        }
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get rate card for franchise $franchise_id", $sql);
        $rate_card = FALSE;
    }
    return $rate_card;
}

function get_past_rate_cards($franchise_id){
	$safe_franchise_id = mysql_real_escape_string($franchise_id);
	
	$sql = "SELECT EffectiveDate,IF( ReplacedDate IS NULL, '9999-12-31', ReplacedDate) as ordering, ReplacedDate FROM rate_card WHERE FranchiseID = $safe_franchise_id GROUP BY EffectiveDate, ReplacedDate ORDER BY ordering DESC";
	$result = mysql_query($sql) or die(mysql_error());
	if($result){
		$cards = array();
		while($row = mysql_fetch_array($result)){
			$replaced_date = $row['ReplacedDate'] == null ? 'IS NULL' : '= \'' . $row['ReplacedDate'] . '\'';
				
			$sql = "SELECT * FROM rate_card WHERE FranchiseID = $safe_franchise_id && EffectiveDate = '{$row['EffectiveDate']}' && ReplacedDate $replaced_date";
			$result2 = mysql_query($sql);
			
			if($result){
				$card = array();
				while($row2 = mysql_fetch_array($result2)){
					$card[] = $row2;
				}
				$cards[] = $card;
			}
		}
		return $cards;
	} else
		return false;
}
function get_past_out_of_area_rate_cards($franchise_id){
	$safe_franchise_id = mysql_real_escape_string($franchise_id);

    $sql = "SELECT FranchiseID, RiderPerMileCents, DriverPerMileCents, RiderPerHourWaitCents, 
                   DriverPerHourWaitCents, EffectiveDate, ReplacedDate, IF( ReplacedDate IS NULL, '9999-12-31', ReplacedDate) as ordering
            FROM out_of_area_rate_card
            WHERE FranchiseID = $safe_franchise_id ORDER BY ordering DESC";
	$result = mysql_query($sql);
	if($result){
		$cards = array();
		while($row = mysql_fetch_array($result)){
			$cards[] = $row;
		}
		return $cards;
	} else
		return false;
}

function get_out_of_area_link_price($distance, $franchise_id, $from_dest_id, $to_dest_id, $link = []) {
	
		if(@$link['QuotedCents'] != '' && @$link['QuotedCents'] > 0) return $link['QuotedCents'];
		
    $card = get_current_out_of_area_rate_card($franchise_id);

    if ($card === FALSE) {
        return FALSE;  // TODO: Default for not-found card or no valid max dist
    }

    $quote = ceil($distance) * $card['RiderPerMileCents'];
	
    $from_tags = get_destination_tags($from_dest_id);
    $to_tags = get_destination_tags($to_dest_id);

    // Right now, just looking for DOUBLEFEE.
    // In the future, look for business partners.
    if ($from_tags['DOUBLEFEE'] || $to_tags['DOUBLEFEE']) {
        $quote *= 2;
    }
    $quote = array('Total' => $quote, 'RiderShare' => $quote);
    return $quote;
}

function get_current_out_of_area_rate_card($franchise_id) {
    $safe_franchise_id = mysql_real_escape_string($franchise_id);

    $sql = "SELECT FranchiseID, RiderPerMileCents, DriverPerMileCents, RiderPerHourWaitCents, 
                   DriverPerHourWaitCents, EffectiveDate, ReplacedDate
            FROM out_of_area_rate_card
            WHERE (ReplacedDate IS NOT NULL && NOW() BETWEEN EffectiveDate And ReplacedDate) || (ReplacedDate IS NULL && EffectiveDate <= NOW() ) AND
                  FranchiseID = $safe_franchise_id";

    $result = mysql_query($sql);

    if ($result) {
        $rate_card = mysql_fetch_array($result, MYSQL_ASSOC);
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get out-of-area rate card for franchise $franchise_id", $sql);
        $rate_card = FALSE;
    }
    return $rate_card;
}

function quote_link_cents($franchise_id, $miles) {
}

function set_new_rate_card($franchise, $effective_date, $mile_arrays, $replaced_date = NULL){
	$franchise_id = mysql_real_escape_string($franchise);
	$safe_effective_date = mysql_real_escape_string($effective_date);
	$safe_replaced_date = $replaced_date === NULL ? 'NULL' : "'" . mysql_real_escape_string($replaced_date) . "'";
	if($replaced_date === NULL){
	   $sql = "UPDATE `rate_card` SET `ReplacedDate` = '$safe_effective_date' WHERE `FranchiseID` = '$franchise_id' AND `ReplacedDate` IS NULL";
	   $result = mysql_query($sql) or die(mysql_error());
	   if(!$result){
            rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not set new rate card for franchise $franchise_id", $sql);
		  return false;
       }
	}
	
	$sql = "INSERT INTO `rate_card` (`FranchiseID`, `MaxDistance`, `Cents`, `EffectiveDate`, `ReplacedDate`) VALUES ";
	for($i=0; $i < count($mile_arrays); $i++){
		if($mile_arrays[$i]['Distance'] != '' && $mile_arrays[$i]['Price'] != ''){
			$sql .= "('$franchise_id', '" . mysql_real_escape_string($mile_arrays[$i]['Distance']). "', '" . mysql_real_escape_string($mile_arrays	[$i]['Price']). "', '$safe_effective_date', $safe_replaced_date)";
		}
		if($mile_arrays[$i + 1]['Distance'] != '' && $mile_arrays[$i + 1]['Price'] != '' || $i < count($miles_array) - 1)
				$sql .= ",";
	}
	$result = mysql_query($sql) or die(mysql_error());
	if($result){
		return true;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not set rate card for franchise $franchise_id", $sql);
		return false;
	}
}

function set_new_out_of_area_rate_card($franchise, $RiderPerMileCents, $DriverPerMileCents, $RiderPerHourWaitCents, $DriverPerHourWaitCents, $effective_date, $replaced_date = null){
	$franchise = mysql_real_escape_string($franchise);
	$safe_effective_date = mysql_real_escape_string($effective_date);
	$safe_replaced_date = $replaced_date === NULL ? 'NULL' : "'" . mysql_real_escape_string($replaced_date) . "'";
	if($replaced_date === NULL){
		$sql = "UPDATE `out_of_area_rate_card` SET `ReplacedDate` = $safe_effective_date WHERE `FranchiseID` = '$franchise' AND `ReplacedDate` IS NULL";
		$result = mysql_query($sql);
		if(!$result){
			rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
    	                    "Could not set out-of-area rate card for franchise $franchise", $sql);
			return false;
		}
	}

	$RiderPerMileCents = mysql_real_escape_string($RiderPerMileCents);
	$DriverPerMileCents = mysql_real_escape_string($DriverPerMileCents);
	$RiderPerHourWaitCents = mysql_real_escape_string($RiderPerHourWaitCents);
	$DriverPerHourWaitCents = mysql_real_escape_string($DriverPerHourWaitCents);
	
	$sql = "INSERT INTO `out_of_area_rate_card` (`FranchiseID`, `RiderPerMileCents`, `DriverPerMileCents`, `RiderPerHourWaitCents`, `DriverPerHourWaitCents`, `EffectiveDate`, `ReplacedDate`) 
			VALUES ( '$franchise', '$RiderPerMileCents', '$DriverPerMileCents', '$RiderPerHourWaitCents', '$DriverPerHourWaitCents', '$effective_date', $safe_replaced_date);";
	$result = mysql_query($sql);
	
	if($result){
		return true;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not set out-of-area rate card for franchise $franchise_id", $sql);
		return false;
	}
}

function delete_rate_card($franchise, $effective_date, $replaced_date, $fix_replaced_date = TRUE){
    $franchise = mysql_real_escape_string($franchise);
    $effective_date = mysql_real_escape_string($effective_date);
    $safe_replaced_date = $replaced_date !== NULL ? " = '" . mysql_real_escape_string($replaced_date) . "'" : " IS NULL";
    $safe_replaced_date2 = $replaced_date !== NULL ? " = '" . mysql_real_escape_string($replaced_date) . "'" : " = NULL";
    
    $sql = "DELETE FROM `rate_card` WHERE `FranchiseID` = $franchise AND `EffectiveDate` = '$effective_date' AND `ReplacedDate`$safe_replaced_date";
    $result = mysql_query($sql) or die($sql . mysql_error());
    
    if($result){
        $sql = "UPDATE `rate_card` SET `ReplacedDate`$safe_replaced_date2 WHERE ReplacedDate = '$effective_date'";
        echo $effective_date;
        if($fix_replaced_date)
            $result = mysql_query($sql) or die($sql . mysql_error());
        else
            $result = true;
        
        if($result){
            return true;
        } else {
            rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not replace rate cards when deleting for franchise $franchise_id", $sql);
		return false;
        }
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not delete rate card for franchise $franchise_id", $sql);
		return false;
    }
}

function delete_out_of_area_rate_card($franchise, $effective_date, $replaced_date, $fix_replaced_date = TRUE){
    $franchise = mysql_real_escape_string($franchise);
    $effective_date = mysql_real_escape_string($effective_date);
    $safe_replaced_date = $replaced_date !== NULL ? " = '" . mysql_real_escape_string($replaced_date) . "'" : " IS NULL";
    $safe_replaced_date2 = $replaced_date !== NULL ? " = '" . mysql_real_escape_string($replaced_date) . "'" : " = NULL";
    
    $sql = "DELETE FROM `out_of_area_rate_card` WHERE `FranchiseID` = $franchise AND `EffectiveDate` = '$effective_date' AND `ReplacedDate`$safe_replaced_date";
    $result = mysql_query($sql) or die($sql . mysql_error());
    
    if($result){
        $sql = "UPDATE `out_of_area_rate_card` SET `ReplacedDate`$safe_replaced_date2 WHERE ReplacedDate = '$effective_date'";
        if($fix_replaced_date)
            $result = mysql_query($sql) or die($sql . mysql_error());
        else
            $result = true;
        
        if($result){
            return true;
        } else {
            rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not replace rate cards when deleting for franchise $franchise_id", $sql);
		return false;
        }
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not delete rate card for franchise $franchise_id", $sql);
		return false;
    }
}


function edit_rate_card($franchise, $effective_date, $replaced_date, $mile_array){
    $delete = delete_rate_card($franchise, $effective_date, $replaced_date, FALSE);
    if(!$delete)
        return false;
    $add_new = set_new_rate_card($franchise, $effective_date, $mile_array, $replaced_date);
    if(!$add_new)
        return false;
    return true;
}

function edit_out_of_area_rate_card($franchise, $effective_date, $replaced_date, $RiderPerMileCents, $DriverPerMileCents, $RiderPerHourWaitCents, $DriverPerHourWaitCents){
    $delete = delete_out_of_area_rate_card($franchise, $effective_date, $replaced_date, FALSE);
    if(!$delete)
        return false;
    $add_new = set_new_out_of_area_rate_card($franchise, $RiderPerMileCents, $DriverPerMileCents, $RiderPerHourWaitCents, $DriverPerHourWaitCents, $effective_date, $replaced_date);
    if(!$add_new)
        return false;
    return true;
}

function get_rate_card($franchise, $effective_date, $replaced_date){
    $franchise = mysql_real_escape_string($franchise);
    $effective_date = mysql_real_escape_string($effective_date);
    $replaced_date = $replaced_date == NULL ? 'IS NULL' : "= '" . mysql_real_escape_string($replaced_date) . "'";
    
    $sql = "SELECT * FROM rate_card WHERE FranchiseID = $franchise AND EffectiveDate = '$effective_date' AND ReplacedDate $replaced_date ORDER BY MaxDistance";
    $result = mysql_query($sql) or die($sql);
    
    if($result){
        $rtn = array();
        while($row = mysql_fetch_array($result))
            $rtn[] = $row;
        return $rtn;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get rate card for franchise $franchise_id", $sql);
		return false;
    }
}

function get_out_of_area_rate_card($franchise, $effective_date, $replaced_date){
    $franchise = mysql_real_escape_string($franchise);
    $effective_date = mysql_real_escape_string($effective_date);
    $replaced_date = $replaced_date == NULL ? 'IS NULL' : "= '" . mysql_real_escape_string($replaced_date) . "'";
    
    $sql = "SELECT * FROM out_of_area_rate_card WHERE FranchiseID = $franchise AND EffectiveDate = '$effective_date' AND ReplacedDate $replaced_date LIMIT 1;";
    $result = mysql_query($sql) or die($sql);
    
    if($result){
    	if(mysql_num_rows($result) < 1)
    		return false;
        return mysql_fetch_array($result);
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get rate card for franchise $franchise_id", $sql);
		return false;
    }
}