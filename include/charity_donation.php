<?php
	include_once('include/date_time.php');
function get_user_charity_donations($role = NULL){
	if($role !== NULL)
		$HAVING = " HAVING Role = '" . mysql_real_escape_string($role) . "'";
	$sql = "SELECT charity.CharityID, SupporterUserID, CharityName, (SELECT Role FROM user_role Where UserID = supporter_charity_record.SupporterUserID ORDER BY FIELD(Role, 'FullAdmin', 'Rider', 'Driver', 'Supporter', 'Franchisee', 'VolunteerAdmin', 'CareFacilityAdmin', 'LargeFacilityAdmin', 'SuperUser') LIMIT 1) AS Role FROM supporter_charity_record LEFT JOIN charity ON charity.CharityID = supporter_charity_record.CharityID GROUP BY SupporterUserID, CharityID$HAVING";
	echo "<!-- $sql -->\n\n";
	$result = mysql_query($sql);
	
	if($result){
		$charities = array();
		while($row = mysql_fetch_array($result))
			$charities[] = $row;
		return $charities;
	} else{
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 'Could not get charity donations', $sql);
        return FALSE;
	}
}

function get_users_monthly_donations( $user_id, $charity_id, $year){
	$safe_user_id = mysql_real_escape_string($user_id);
	$safe_charity_id = mysql_real_escape_string($charity_id);
	$sql = "SELECT
					(SELECT SUM(-Cents) FROM `supporter_charity_record` LEFT JOIN ledger ON supporter_charity_record.LedgerEntryID = ledger.LedgerEntryID WHERE SupporterUserID = $safe_user_id AND supporter_charity_record.CharityID = $safe_charity_id AND EffectiveDate >= '$year-1-1' AND EffectiveDate < '$year-2-1'  ) AS Jan,
					(SELECT SUM(-Cents) FROM `supporter_charity_record` LEFT JOIN ledger ON supporter_charity_record.LedgerEntryID = ledger.LedgerEntryID WHERE SupporterUserID = $safe_user_id AND supporter_charity_record.CharityID = $safe_charity_id AND EffectiveDate >= '$year-2-1' AND EffectiveDate < '$year-3-1') AS Feb, 
					(SELECT SUM(-Cents) FROM `supporter_charity_record` LEFT JOIN ledger ON supporter_charity_record.LedgerEntryID = ledger.LedgerEntryID WHERE SupporterUserID = $safe_user_id AND supporter_charity_record.CharityID = $safe_charity_id AND EffectiveDate >= '$year-3-1' AND EffectiveDate < '$year-4-1') AS Mar,
					(SELECT SUM(-Cents) FROM `supporter_charity_record` LEFT JOIN ledger ON supporter_charity_record.LedgerEntryID = ledger.LedgerEntryID WHERE SupporterUserID = $safe_user_id AND supporter_charity_record.CharityID = $safe_charity_id AND EffectiveDate >= '$year-4-1' AND EffectiveDate < '$year-5-1') AS Apr,
					(SELECT SUM(-Cents) FROM `supporter_charity_record` LEFT JOIN ledger ON supporter_charity_record.LedgerEntryID = ledger.LedgerEntryID WHERE SupporterUserID = $safe_user_id AND supporter_charity_record.CharityID = $safe_charity_id AND EffectiveDate >= '$year-5-1' AND EffectiveDate < '$year-6-1') AS May,
					(SELECT SUM(-Cents) FROM `supporter_charity_record` LEFT JOIN ledger ON supporter_charity_record.LedgerEntryID = ledger.LedgerEntryID WHERE SupporterUserID = $safe_user_id AND supporter_charity_record.CharityID = $safe_charity_id AND EffectiveDate >= '$year-6-1' AND EffectiveDate < '$year-7-1') AS Jun,
					(SELECT (SUM(-Cents)) FROM `supporter_charity_record` LEFT JOIN ledger ON supporter_charity_record.LedgerEntryID = ledger.LedgerEntryID WHERE SupporterUserID = $safe_user_id AND supporter_charity_record.CharityID = $safe_charity_id AND EffectiveDate >= '$year-7-1' AND EffectiveDate < '$year-8-1') AS Jul,
					(SELECT SUM(-Cents) FROM `supporter_charity_record` LEFT JOIN ledger ON supporter_charity_record.LedgerEntryID = ledger.LedgerEntryID WHERE SupporterUserID = $safe_user_id AND supporter_charity_record.CharityID = $safe_charity_id AND EffectiveDate >= '$year-8-1' AND EffectiveDate < '$year-9-1') AS Aug,
					(SELECT SUM(-Cents) FROM `supporter_charity_record` LEFT JOIN ledger ON supporter_charity_record.LedgerEntryID = ledger.LedgerEntryID WHERE SupporterUserID = $safe_user_id AND supporter_charity_record.CharityID = $safe_charity_id AND EffectiveDate >= '$year-9-1' AND EffectiveDate < '$year-10-1') AS Sep, 
					(SELECT SUM(-Cents) FROM `supporter_charity_record` LEFT JOIN ledger ON supporter_charity_record.LedgerEntryID = ledger.LedgerEntryID WHERE SupporterUserID = $safe_user_id AND supporter_charity_record.CharityID = $safe_charity_id AND EffectiveDate >= '$year-10-1' AND EffectiveDate < '$year-11-1') AS Oct, 
					(SELECT SUM(-Cents) FROM `supporter_charity_record` LEFT JOIN ledger ON supporter_charity_record.LedgerEntryID = ledger.LedgerEntryID WHERE SupporterUserID = $safe_user_id AND supporter_charity_record.CharityID = $safe_charity_id AND EffectiveDate >= '$year-11-1' AND EffectiveDate < '$year-12-1') AS Nov,
					(SELECT SUM(-Cents) FROM `supporter_charity_record` LEFT JOIN ledger ON supporter_charity_record.LedgerEntryID = ledger.LedgerEntryID WHERE SupporterUserID = $safe_user_id AND supporter_charity_record.CharityID = $safe_charity_id AND EffectiveDate >= '$year-12-1' AND EffectiveDate <= '$year-12-31 23:59:59') AS 'Dec' ";
					
	$result = mysql_query($sql);
	
	if($result){
		return mysql_fetch_array($result);
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 'Could not get users monthly chairty donations', $sql);
        return FALSE;
	}
 }
 
function get_users_yearly_donations( $user_id, $charity_id, $year) {
	$safe_user_id = mysql_real_escape_string($user_id);
	$safe_charity_id = mysql_real_escape_string($charity_id);
	$sql = "SELECT SUM(-Cents) as `yt` FROM `supporter_charity_record` LEFT JOIN ledger ON supporter_charity_record.LedgerEntryID = ledger.LedgerEntryID WHERE SupporterUserID = $safe_user_id AND supporter_charity_record.CharityID = $safe_charity_id AND EffectiveDate >= '$year-1-1' AND EffectiveDate <= '$year-12-31 23:59:59'";
	echo "<!-- $sql -->\n\n";
	$result = mysql_query($sql);
	
	if($result){
		return mysql_fetch_array($result);
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 'Could not get users monthly chairty donations', $sql);
        return FALSE;
	}	
}
?>