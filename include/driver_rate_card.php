<?php
require_once('include/database.php');
require_once('include/rc_log.php');

function create_driver_rate_card($franchise, $centsPerMile, $effective_date, $update_replace_date = TRUE){
	$safe_franchise = mysql_real_escape_string($franchise);
	$safe_cents = mysql_real_escape_string($centsPerMile);
	$safe_effective_date = mysql_real_escape_string($effective_date);
	
	if($update_replace_date){
		$sql = "UPDATE `driver_rate_card` SET `ReplacedDate` = '$safe_effective_date' WHERE `FranchiseID` = $safe_franchise AND `ReplacedDate` IS NULL;";
		$result = mysql_query($sql);
		
		if(!$result){
			rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not update replaced date during driver rate card update $franchise_id", $sql);
   	    return false;
		}
	}
	
	$sql = "INSERT INTO  `driver_rate_card` (`FranchiseID`, `CentsPerMile` , `EffectiveDate` , `ReplacedDate` )
														VALUES ('$safe_franchise', '$safe_cents', '$safe_effective_date', NULL );";
														
	$result = mysql_query($sql) or die(mysql_error());
	
	if($result){
		return true;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get driver rate card for franchise $franchise_id", $sql);
   	    return false;
	}									
}

function delete_driver_rate_card($franchise, $effective_date, $replace_date){
	$safe_franchise = mysql_real_escape_string($franchise);
	$safe_effective_date = mysql_real_escape_string($effective_date);
	$safe_replace_date = ($replace_date == NULL ? 'IS NULL' : "= '" . mysql_real_escape_string($replace_date) . "'");
	$safe_replace_date2 = ($replace_date == NULL ? ' NULL' : "= '" . mysql_real_escape_string($replace_date) . "'");
	$sql = "DELETE FROM `driver_rate_card` WHERE `FranchiseID` = $safe_franchise AND `EffectiveDate` = '$safe_effective_date' AND `ReplacedDate` $safe_replace_date LIMIT 1";
	$result = mysql_query($sql);
	
	if($result){
		$sql = "UPDATE `driver_rate_card` SET `ReplacedDate` $safe_replace_date2 WHERE FranchiseID = $safe_franchise AND `ReplacedDate` = '$safe_effective_date' LIMIT 1";
		$result = mysql_query($sql);
		if($result)
			return true;
		return false;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not delete driver rate card update $franchise_id", $sql);
   	    return false;
	}
}

function get_current_driver_rate_card($franchise_id) {
    $safe_franchise_id = mysql_real_escape_string($franchise_id);

	$sql = "SELECT FranchiseID, CentsPerMile, EffectiveDate, ReplacedDate
	        FROM driver_rate_card
            WHERE  (ReplacedDate IS NOT NULL && NOW() BETWEEN EffectiveDate And ReplacedDate) || (ReplacedDate IS NULL && EffectiveDate <= NOW() ) AND
	                FranchiseID = $safe_franchise_id";

	$result = mysql_query($sql) or die(mysql_error());
	
	if ($result) {
	    if(mysql_num_rows($result) > 0)
   	     	return mysql_fetch_array($result);
   	    return false;
   	} else {
   		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get driver rate card for franchise $franchise_id", $sql);
   	    return false;
   	}
}

function get_driver_rate_card($franchise_id, $effective_date, $replaced_date) {
    $safe_franchise_id = mysql_real_escape_string($franchise_id);
	$safe_effective_date = mysql_real_escape_string($effective_date);
	$safe_replaced_date = $replaced_date == null ?  'IS NULL' : "= '" . mysql_real_escape_string($replaced_date) . "'";
	
	$sql = "SELECT FranchiseID, CentsPerMile, EffectiveDate, ReplacedDate
	        FROM driver_rate_card
            WHERE  ReplacedDate $safe_replaced_date AND EffectiveDate = '$safe_effective_date' AND
	                FranchiseID = $safe_franchise_id";

	$result = mysql_query($sql) or die(mysql_error());
	
	if ($result) {
	    if(mysql_num_rows($result) > 0)
   	     	return mysql_fetch_array($result);
   	    return false;
   	} else {
   		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get driver rate card for franchise $franchise_id", $sql);
   	    return false;
   	}
}
function get_past_driver_rate_cards($franchise_id){
	$safe_franchise_id = mysql_real_escape_string($franchise_id);

	$sql = "SELECT *, IF( ReplacedDate IS NULL, '9999-12-31', ReplacedDate) as ordering FROM driver_rate_card WHERE FranchiseID = $safe_franchise_id ORDER BY ordering DESC";
	$result = mysql_query($sql);
	
	if($result){
		$cards = array();
		while($row = mysql_fetch_array($result)){
			$cards[] = $row;
		}
		return $cards;
		
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not get driver rate card for franchise $franchise_id", $sql);
   	    return false;
	}	
}

function edit_driver_rate_card($franchise, $effective_date, $replace_date, $centsPerMile){
	$safe_franchise = mysql_real_escape_string($franchise);
	$safe_effective_date = mysql_real_escape_string($effective_date);
	$safe_replace_date = $replace_date == null ? 'IS NULL' : "= '" . mysql_real_escape_string($replace_date) . "'";
	$safe_centsPerMile = mysql_real_escape_string($centsPerMile);
	
	$sql = "UPDATE `driver_rate_card` SET CentsPerMile = '$safe_centsPerMile' WHERE FranchiseID = $safe_franchise AND EffectiveDate = '$safe_effective_date' AND ReplacedDate $safe_replace_date LIMIT 1;";
	$result = mysql_query($sql);
		
	if($result){
		return true;
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not edit driver rate card for franchise $franchise_id", $sql);
   	    return false;
	}
}
?>