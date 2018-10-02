<?php
    chdir('..');

    require_once('include/user.php');
	require_once 'include/franchise.php';

    session_start();
	if(!is_logged_in())
		die();
	$franchise = get_current_user_franchise();
    if ((current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee'))  && $_REQUEST['uid']) {
		
    	if($_REQUEST['uid'] == 'SETCURRENTUSER')
    		set_affected_user_id(get_current_user_id());
    	else
        	set_affected_user_id($_REQUEST['uid']);
        $person_name = get_user_person_name(get_affected_user_id());

        echo "{$person_name['LastName']}, {$person_name['FirstName']} {$person_name['MiddleInitial']} (".
             $_REQUEST['uid'] . ')';
    } else if ((current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee')) && $_REQUEST['SortOrder']) {
        switch($_REQUEST['SortOrder']) {
            case 'F':
                $_SESSION['ASORTORDER'] = 'F';
                break;
            default:
                $_SESSION['ASORTORDER'] = 'L';
                break;
        }
    } elseif ((current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee')) && isset($_REQUEST['uid'])) {
        clear_affected_user_id();
    } 

chdir('xhr/');
?>
