<?php
	include_once 'include/user.php';
	include_once 'include/franchise.php';
	include_once 'include/database.php';

$CorporateID = 1;
$DateRestrictions = 'DisplayStartDate <= CURDATE() AND DisplayEndDate >= CURDATE()';


function get_franchise_news_items($franchise_id){
	global $DateRestrictions;
	$safe_franchise_id = mysql_real_escape_string($franchise_id);
	$sql = "SELECT * FROM news_item WHERE FranchiseID = $safe_franchise_id AND $DateRestrictions";
	$result = mysql_query($sql);
	
	if($result){
		while($row = mysql_fetch_array($result)){
			echo create_news_item($row);
		}
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error getting franchise news for FranchiseID $franchise_id", $sql);
        return FALSE;
	}
}

function get_corporate_news_items(){
	global $CorporateID;
	global $DateRestrictions;
	$sql = "SELECT * FROM news_item WHERE FranchiseID = $CorporateID AND $DateRestrictions";
	$result = mysql_query($sql);
	
	if($result){
		while($row = mysql_fetch_array($result)){
			echo create_news_item($row);
		}
	} else {
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error getting corporate news for FranchiseID $CorporateID", $sql);
        return FALSE;
	}
}

function create_news_item($news_item){
	$html = "<div id=\"news_item:{$news_item['NewsItemID']}\">{$news_item['Text']}";
	$franchise = get_current_user_franchise(FALSE);
	if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, "Franchisee"))
		$html .= "<div><a href=\"insert.php?action=edit&id={$news_item['NewsItemID']}\">Edit</a> - <a href=\"post.php?action=delete&id={$news_item['NewsItemID']}\">Delete</a></div>";
	$html .= "</div>";
	return $html;
}
function remove_news_item($news_item_id){
	$safe_id = mysql_real_escape_string($news_item_id);
	$sql = "UPDATE `news_item` SET `DisplayEndDate` = '" . date("Y-m-d",time() - 86400) . "' WHERE `NewsItemID` =$safe_id LIMIT 1 ;";
	$result = mysql_query($sql);
	if(!$result){
		rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error removing news item $news_item_id", $sql);
        return FALSE;
	}
}
?>