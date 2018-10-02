<?php
    include_once 'include/user.php';
	redirect_if_not_logged_in();

    require_once('include/rc_log.php');
    require_once('include/supporters.php');
    require_once('include/name.php');
    require_once('include/address.php');

    $user_id = get_affected_user_id();
    $user_balance = calculate_user_ledger_balance($user_id);

    if ($_POST['Cancel']) {
        header('Location: support_list.php');
    }

    if ($_POST['Submit']) {
        $name_info = get_name_fields_from_post('', NULL);
        $name_string = get_displayable_person_name_string($name_info['Name']);

        $address_fields = get_address_field_list();
        foreach ($address_fields as $field_name) {
            $address_string .= $_POST[$field_name] . "   ";
        }

        $phone_string = $_POST['PhoneNumber'] . "(" . $_POST['PhoneType'] . ")";

        if (store_supporter_request_for_rider($user_id, $name_string, 
                                              $address_string, $phone_string)) {
            display_supporter_request_confirmation();
            exit;
        }
    }

	include_once('include/header.php');
?>
<h2 class="PageTitle">Add a Rider to My Support List</h2>
<?php if (is_array($error_string)) { 
        foreach ($error_string as $err) { ?>
    <h4 style="color: red"><?php echo $err ?></h4>
<?php }
} ?>
<form method="POST" action="">
<?php 
    print_get_name_form_part($name_info['Name'], '', FALSE);
?>
<br />
<?php  
    create_simple_address_input('', $_POST);
?>
<br />
<table><tr><td>Phone Number:</td>
           <td><input type="text" name="PhoneNumber" value="<?php
               echo htmlspecialchars($_POST['PhoneNumber']); ?>" maxlength="20" /></td></tr>
       <tr><td>Type (e.g. Home, Cell)</td>
           <td><input type="text" name="PhoneType" value="<?php
               echo htmlspecialchars($_POST['PhoneType']); ?>" maxlength="20" /></td></tr>
</table>
<br />
<input type="submit" name="Submit" value="Submit" /> 
<input type="submit" name="Cancel" value="Cancel" />
</form>

<div style="clear:both">&nbsp;</div>

<?php
	include_once 'include/footer.php';

function display_supporter_request_confirmation() {
	include_once('include/header.php');
?>
<h2 class="PageTitle">Add a Rider to My Support List</h2>
<h3>Request Submitted!</h3>
<p>Your request has been submitted.  The administrator will confirm this individual as a 
   user of the system.  When they appear on your supported rider list, you will be able to 
   designate funds to their account.</p>
<div style="clear:both">&nbsp;</div>
<?php
    include_once('include/footer.php');
}
?>
