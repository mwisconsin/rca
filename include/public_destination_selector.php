<?php
include_once('include/destinations.php');
function create_public_destination_selector($idx, $destination = NULL){
	if($destination !== NULL){
		$dest = get_destination($destination);
		$text = $dest['Name'];
	} else
		$text = "Select a public destination...";
	return "<div class=\"DestinationSelectorContainer\"><input type=\"hidden\" name=\"DestinationSelectorValue[$idx]\" id=\"DestinationSelectorValue[$idx]\"  /><input type=\"text\" id=\"DestinationSelector[$idx]\" name=\"" . time() . $idx . "\" value=\"$text\" class=\"DestinationSelectorText\" size=\"30\" /></div>";
}

function get_public_destination_selector_js(){
    return "<script type=\"text/javascript\" src=\"js/public_destination_selector.js\"></script>";
}
?>