<?php
	include_once 'include/user.php';
	require_once 'include/franchise.php';
	redirect_if_not_logged_in();
	
	$franchise = get_current_user_franchise();
	if(!current_user_has_role(1 , "FullAdmin") && !current_user_has_role($franchise, "Franchisee")){
		header("Location: home.php");
		die();	
	}
#redirect_if_not_role('FullAdmin');	
	
    require_once('include/driver_link.php');
    require_once('include/link.php');
    require_once('include/driver.php');
    include_once 'include/header.php';

    // TODO:  Something intelligent if link and driver ID are not set

    $link_id = $_REQUEST['LinkID'];
    $driver_id = $_REQUEST['DriverID'];
    $confirmed = isset($_REQUEST['ConfirmDriver']);


    if ($confirmed && isset($link_id, $driver_id)) {
        $set_success = set_driver_for_link($link_id, $driver_id);
        // TODO:  Do something with return value.  
    }


    $link_info = get_link($link_id);
    // TODO:  Probably need to get more driver info.
    $driver_name = get_driver_name($driver_id);

    // TODO:  Something intelligent if link already has a driver
    // TODO:  Something intelligent if driver is already scheduled at that time.
    // TODO:  Something intelligent if franchises, preferences, etc don't match.



?>

<h2 style="text-align:center;"><?php 
if ($confirmed && $link_info['AssignedDriverUserID'] == $driver_id) { ?>Driver Scheduled For Ride</h2>
    Successfully scheduled <?php echo $driver_name ?> for ride:<br />
    <table border="1"><tr><?php echo get_admin_link_table_headings(); ?></tr>
        <tr><?php echo get_link_as_admin_link_table_row($link_info); ?> </tr></table>
<?php 
}
elseif ($confirmed && $link_info['AssignedDriverUserID'] != $driver_id) { ?>Driver Not Scheduled</h2>
    Failed to schedule <?php echo $driver_name ?> for ride:<br />
    <table border="1"><tr><?php echo get_admin_link_table_headings(); ?></tr>
        <tr><?php echo get_link_as_admin_link_table_row($link_info); ?> </tr></table>
<?php 
}
elseif (!$confirmed) { ?>Schedule Driver for Ride</h2>
    Please review this information and confirm below.<br />
    Driver:  <?php echo $driver_name ?><br />
    <table border="1"><tr><?php echo get_admin_link_table_headings(); ?></tr>
        <tr><?php echo get_link_as_admin_link_table_row($link_info); ?> </tr></table>
    <form method="post" action="">
        <input type="hidden" id="DriverID" name="DriverID" value="<?php echo $driver_id ?>" />
        <input type="hidden" id="LinkID" name="LinkID" value="<?php echo $link_info['LinkID'] ?>" />
        <input type="submit" id="ConfirmDriver" name="ConfirmDriver" value="Confirm Driver" />
    </form>
<?php 
}
?>
    
<?php
	include_once 'include/footer.php';
?>
