<?php

	require_once 'include/user.php';
	redirect_if_not_logged_in();
    require_once 'include/franchise.php';
	
	$franchise = get_current_user_franchise();
	
	if(!current_user_has_role(1, "FullAdmin") && !current_user_has_role($franchise, "Franchisee")){
		header("Location: home.php");
		die();
	}
	
    require_once 'include/ledger.php';

    include_once 'include/header.php';

    $year = date('Y'); 
    $this_month = date('m');
    $last_month_start = (($this_month == 1) ? ($year - 1) : $year) . '-' . 
                        (($this_month == 1) ? 12 : ($this_month - 1)) . '-01';
    $this_month_start = "$year-$this_month-01";
	
    $this_month_end = date('m-t-Y 12:59:59',strtotime($last_month_start));
    $effective_time_t = mktime( 0, 0, 0, $this_month, 0, $year);
    $effective_date = date('Y-m-d', $effective_time_t);
        
    echo "<br /> Adding additional use charge for rides between $last_month_start and $this_month_start <br />";

    $safe_franchise = mysql_real_escape_string($franchise);
    $safe_from = mysql_real_escape_string($last_month_start);
    $safe_to = mysql_real_escape_string($this_month_start);
        
    $sql = "SELECT FranchiseID, Summary.* , MAX(ChargeCents) AS CHARGE
            FROM (SELECT CareFacilityID, SUM(RideCount) as RideCount, SUM(ChargeCount) as ChargeCount 
                  FROM (SELECT * FROM (SELECT CareFacilityID, COUNT(*) AS RideCount, 0 AS ChargeCount
                                       FROM care_facility_ride NATURAL JOIN link_history
                                       WHERE DesiredArrivalTime BETWEEN '$safe_from' AND '$safe_to' AND
                                             (LinkStatus = 'COMPLETE' OR LinkStatus = 'CANCELEDLATE') AND 
                                             (CustomTransitionID IS NULL OR CustomTransitionType = 'RIDER')
                                              GROUP BY CareFacilityID) Rides  
                        UNION	  
                        SELECT * FROM (SELECT EntityID AS CareFacilityID,0 as RideCount, COUNT(*) AS ChargeCount
                                       FROM ledger
                                       WHERE EntityType = 'CAREFACILITY' AND
                                             EffectiveDate BETWEEN '$safe_from' AND '$safe_to' AND
	                                         Description like 'Additional Us%'
                                       GROUP BY EntityID) Charges) Merge
                  GROUP BY CareFacilityID
                  ORDER BY CareFacilityID) Summary
            JOIN care_facility_additional_use_charge ON RideCount >= MinimumRideCount AND
                                                        FranchiseID = $safe_franchise AND
                                                        EffectiveFrom <= '$safe_from' AND
					                (EffectiveTo IS NULL OR EffectiveTo >= '$safe_from')
            WHERE ChargeCount = 0
            GROUP BY FranchiseID, CareFacilityID"; 
    //echo "<br /> $sql<br />";
        
    $result = mysql_query($sql);
    $added_charges_count = 0;
    
    if ($result)
    {
        $summary = array();
        while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) 
            $summary[] = $row;

        foreach ($summary as $ride_summary)
    	{
    		$care_facility_id = $ride_summary['CareFacilityID'];
    		$ride_count = $ride_summary['RideCount'];
    		$cents = $ride_summary['CHARGE'];
    		$description = 'Additional Use Charge: ' . $ride_count . ' Rides';
    		//echo $care_facility_id . " " . $ride_count . " " . $cents . " " . $description . ' ' . $effective_date . '<br />';
    		$return = 'did not work';
    	    $return = debit_care_facility( $care_facility_id, $cents, $description,  $effective_date);   
            $added_charges_count += 1;
    	    //echo ($return + 5) . '<br />'; 		
    	}
    }
    echo $added_charges_count .  " Charges added.";
?>
