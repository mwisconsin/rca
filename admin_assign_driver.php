<?php
	include_once 'include/user.php';
	redirect_if_not_logged_in();

    require_once('include/driver_link.php');
    require_once('include/link.php');
    require_once('include/driver.php');
    require_once('include/date_time.php');
    require_once('include/completed_link_transitions.php');
    require_once('include/deadhead.php');
	require_once 'include/franchise.php';
	
	$franchise = get_current_user_franchise();
    if (!current_user_has_role(1, 'FullAdmin') && !current_user_has_role($franchise, 'Franchisee')) {
        header('Location:  home.php');
		die();
    }
	
	
	echo 'here';
	print_r($_REQUEST);
	if ($_REQUEST['LinkID'] && $_POST['driver_assign']) {
	  
	  $link = get_link($_REQUEST['LinkID']);
	  $assigned_driver = $_POST['driver_assign'];
	   // Assign driver
        if (set_driver_for_link($link['LinkID'], $assigned_driver)) {
            $assign_success = TRUE;
        }

        // Re-request link info now that driver is assigned
        $link = get_link($_REQUEST['LinkID']);
	}
exit;

}

?>