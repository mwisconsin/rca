<?php
    require_once('include/care_facility.php');
    require_once('include/user.php');
	redirect_if_not_logged_in();
    if (!isset($_REQUEST['id'])) {
        header('Location: home.php');
		die();
    }

	$franchise = get_current_user_franchise();
	
    if (! (current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee') || current_user_has_role($franchise, 'CareFacilityAdmin')) ) {
        header('Location: home.php');
		die();
    }

    $facility_id = $_REQUEST['id'];
    $facility_info = get_care_facility($facility_id);

    // TODO:  If the user is a care facility admin and not a full admin, make sure they have
    // admin privs for the requested CF.
    if ($facility_id != $facility_info['CareFacilityID']) {
        $error_string[] = 'Requested care facility could not be found.';
    }

    if ($_POST['Connect']) {
        if (!connect_user_to_care_facility($_POST['User'], $facility_id)) {
            $error_string[] = 'Error connecting user to facility.  The user may already be connected.  ' .
                              'If that is not the case, an unknown error occurred.';
        } else {
            $error_string[] = "User {$_POST['User']} successfully connected to facility.";
        } 
    }

    include_once 'include/header.php';
	display_care_facility_header( $facility_id );
	
?>
<h2 class="PageTitle">Connect Existing User to <?php echo $facility_info['CareFacilityName'] ?></h2>
<?php if (is_array($error_string)) { 
        foreach ($error_string as $err) { ?>
    <h4 style="color: red"><?php echo $err ?></h4>
<?php }
} ?>
<form method="POST" action="">
<select name="User">
<?php
    $rider_list = get_admin_work_as_rider_list( $facility_info['FranchiseID'], 'F' );

    if (is_array($rider_list)) {
        echo "\t";
        foreach ($rider_list as $rider) {
            $rider_name = get_displayable_person_name_string($rider);
            echo "<option value=\"{$rider['UserID']}\">$rider_name</option>";
        }
    }

?>
</select>
<input type="hidden" name="id" value="<?php echo $facility_id ?>" />
<input type="submit" name="Connect" value="Connect User" />
</form>

<br />
<br />
<a href="care_facility_users.php?id=<?php echo $facility_id ?>">View Facility Users</a>
<?php
	include_once 'include/footer.php';
?>
