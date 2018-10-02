<?php
	include_once 'include/user.php';
	require_once 'include/franchise.php';
	redirect_if_not_logged_in();
	
	$franchise = get_current_user_franchise();
    require_once('include/supporters.php');

    if (!current_user_has_role(1, 'FullAdmin') && !current_user_has_role($franchise, 'Franchisee')) {
        header('Location:  home.php');
		die();
    }
     
    if ($_POST['Submit']) {
		if($_POST['Submit'] == "Connect"){
        	if (db_start_transaction()) {
            	if (connect_supporter_to_rider($_POST['SupporterUID'], $_POST['RiderUID']) &&
                	delete_supporter_rider_request($_POST['RequestID']) &&
                	db_commit_transaction()) {
                	$error_string = array('Connection made.');
            	} else {
                	$error_string = array('An error occurred connecting the supporter to the rider.');
            	}
        	}
        } else if($_POST['Submit'] == "Cancel"){
        	delete_supporter_rider_request($_POST['RequestID']);
        }
    }

    $requests = get_supporter_requests_for_rider($franchise);
    include_once('include/header.php');
?>

<h2 class="PageTitle">Requests to Support Rider</h2>
<?php if (is_array($error_string)) { 
        foreach ($error_string as $err) { ?>
    <h4 style="color: red"><?php echo $err ?></h4>
<?php }
}?>
<h3><?php echo count($requests) ?> Request<?php if (count($requests) != 1) { echo 's'; } ?> Found</h3>

<table border="1"><tr><th>Requesting User</th><th>Request Info</th><th>Rider UID</th></tr>
<?php
    if (count($requests)) {
        foreach ($requests as $request) {
            $rider_info = nl2br($request['RiderInfo']);
            echo '<form action="" method="POST">';
            echo '<input type="hidden" name="SupporterUID" value="' . $request['SupporterUserID'] . '" />';
            echo '<input type="hidden" name="RequestID" value="' . $request['RequestID'] . '" />';
            echo "<tr><td>{$request['SupporterUserID']}</td><td>{$rider_info}</td><td>";
            echo '<input type="text" name="RiderUID" value="" size="3" />';
            echo ' <input type="Submit" name="Submit" value="Connect" /><input type="Submit" name="Submit" value="Cancel" />';
            echo "</tr></form>";
        }
    }
?>
</table>

<?php
	include_once 'include/footer.php';

?>
