<?php
    include_once 'include/user.php';
    include_once 'include/care_facility.php';
    include_once 'include/address.php';
    include_once 'include/email.php';
    include_once 'include/phone.php';
    include_once 'include/name.php';
    require_once 'include/ledger.php';
    require_once 'include/franchise.php';
    require_once 'include/large_facility.php';
    
    redirect_if_not_logged_in();
	
    $franchise = get_current_user_franchise();
	
	if(!current_user_has_role(1 , "FullAdmin") && !current_user_has_role($franchise, "Franchisee")){
		header("Location: home.php");
		die();	
	}


    if (!isset($_REQUEST['action'])) {
        // TODO:  Redirect somewhere intelligent
    }

    switch ($_REQUEST['action']) {
        case 'edit':
            echo "Not yet implemented";
            break;
        case 'add':
            admin_large_facility_add_action();
            break;
        case 'addcontact':
            admin_large_facility_add_contact_action();
            break;
        default:
            if ($_REQUEST['id']) {
                admin_large_facility_view();
                // View
            } else {
                // TODO:  Redirect somewhere intelligent
            }
            break;
    }

    include_once 'include/footer.php';
    exit;

    
function admin_large_facility_view() {
    $facility = get_large_facility($_REQUEST['id']);

    if (!$facility) {
        header('Location: home.php');
    }

    include_once 'include/header.php';
?>
<div style="text-align: center">
<h2><?php echo $facility['LargeFacilityName']; ?></h2></div>
<b>Address</b>
<?php
    create_html_display_address($facility['FacilityAddressID'],'');
?>
<br />
<hr />
<b>Contacts</b><br />
<?php
    $contacts = get_large_facility_contacts( $facility['LargeFacilityID'] );

    if ($contacts) {
        foreach ($contacts as $contact) {
            $name = get_name( $contact['ContactNameID'] );
            $email = get_email_address( $contact['ContactEmailID'] );
            $phone = get_phone_number( $contact['ContactPhoneID'] );
            ?>
            <table>
                <tr>
                    <td colspan="2">Contact Name: <br><center><?php echo $name['FirstName'] . ' ' . $name['LastName']; ?></center></td>
                </tr>
                <tr>
                    <td class="alignright">Email: </td>
                    <td><?php echo $email['EmailAddress']; ?></td>
                </tr>
                <tr>
                    <td class="alignright">Phone: </td>
                    <td><?php echo $phone['PhoneNumber']; ?></td>
                </tr>
                <tr>
                    <td class="alignright">Role: </td>
                    <td><?php echo $contact['ContactRole']; ?></td>
                </tr>
                <tr>
                    <td class="alignright">Job Title: </td>
                    <td><?php echo $contact['ContactTitle']; ?></td>
                </tr>
            </table>
            <br />
            <?php
        } ?>
        <br />
<?php
    } else { ?>
        <p>You have no contacts for this care facility.</p>
<?php } ?>
    <a href="admin_large_facility.php?action=addcontact&id=<?php
        echo $facility['LargeFacilityID'] ?>">Add A New Contact</a><?php
    
}

/////////////////////////////////////////////////////////    
    if(isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])){
        if(isset($_POST['CareFacilityName']) && if_current_user_has_role('FullAdmin')){
            $fields = array('CareFacilityName','Address1','City','State','Zip5');
            $error = FALSE;
            foreach($fields as $k => $v){
                if(!isset($_POST[$v]) || $_POST[$v] == ''){
                    $error = TRUE;
                    echo $v;
                }
                    
            } if(!$error){
                $safe_id = mysql_real_escape_string($_GET['id']);
                $safe_name = mysql_real_escape_string($_POST['CareFacilityName']);
                $safe_franchise = mysql_real_escape_string($_POST['Franchise']);
                $facility = get_care_facility($_GET['id']);
                
                $address = array('Address1' => $_POST['Address1'],
                                 'Address2' => $_POST['Address2'],
                                 'City' => $_POST['City'],
                                 'State' => $_POST['State'],
                                 'ZIP5' => $_POST['Zip5'],
                                 'ZIP4' => $_POST['Zip4']);
                $address = update_address($facility['FacilityAddressID'],$address);
                
                $sql = "UPDATE `care_facility` SET `CareFacilityName` = '$safe_name', `FranchiseID` = '$safe_franchise' WHERE `CareFacilityID` =$safe_id LIMIT 1 ;";
                mysql_query($sql);
                
                header("location: care_facilities.php");
            }
            
        }
        include_once 'include/header.php';
        display_care_facility_header( $_GET['id'] );
        $facility = get_care_facility($_GET['id']);
        ?>
        <h2><center>Edit Care Facility</center></h2>
        <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?action=edit&id=<?php echo $_GET['id']; ?>">
            <table style="margin:auto">
                <tr>
                    <td class="alignright">Facility Name</td>
                    <td><input type="text" name="CareFacilityName" maxlength="50" value="<?php echo $facility['CareFacilityName']; ?>" style="width:20em;"></td>
                </tr>
                <tr>
                    <td class="alignright">Franchise</td>
                    <td>
                        <select name="Franchise">
                        <?php
                            $query = "SELECT * FROM `franchise` WHERE 1;";
                            $result = mysql_query($query) or die(mysql_query());
                            while($row = mysql_fetch_array($result)){
                                echo '<option value="' . $row['FranchiseID'] . '"';
                                if($row['FranchiseID'] == $facility['FranchiseID'])
                                    echo 'SELECTED';
                                echo '>' . $row['FranchiseName'] . '</option>';
                            }
                                
                        ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <?php create_html_address_table(NULL, get_address( $facility['FacilityAddressID'] ) ); ?>
                    </td>
                </tr>
                <tr>
                    <td class="alignright" colspan="2"><input type="submit" value="Save" /></td>
                </tr>
            </table>
        </form>
        <?php
    }    else if (isset($_GET['action']) && $_GET['action'] == 'edit_balance' && 
              isset($_GET['id']) && $_GET['id'] != ''){
        $cf_id = $_GET['id'];

        if (isset($_POST['UpdateBalance']) && if_current_user_has_role('FullAdmin')){
            $transaction_type = $_POST['TransactionType'];
            $transaction_cents = $_POST['Amount'] * 100;
            $transaction_desc = trim($_POST['Description']);

            if ($transaction_cents != 0 && $transaction_desc != '' && 
                ($transaction_type == 'Credit' || $transaction_type == 'Debit') ) {

                $user_id = get_current_user_id();
                $transaction_desc .= "  (updated by user $user_id)";

                if ($transaction_type == 'Credit') {
                    $result = credit_care_facility($cf_id, $transaction_cents, $transaction_desc);
                } elseif ($transaction_type == 'Debit') {
                    $result = debit_care_facility($cf_id, $transaction_cents, $transaction_desc);
                }
            } else {
                // TODO:  Display some error
                $error_message = "All fields are required.";
            }
        } 

        $cf_balance = calculate_care_facility_balance( $cf_id );


        include_once 'include/header.php';
         ?>
    <form method="post" action="edit_care_facility.php?action=edit_balance&id=<?php echo $cf_id ?>">
        <center>
            <h2>Credit/Debit Care Facility</h2>
        </center>
        <br />
        <table style="margin:auto;">
            <tr>
                <td colspan="2"><center><b><?php echo $care_facility['CareFacilityName']; ?></b></center></td>
            </tr><?php 
                if (isset($result) && $result > 0) { ?>
            <tr><td colspan="2"><?php echo $transaction_type ?>ed account for <br>
                $<?php echo $transaction_cents / 100 ?>.00</td></tr><?php 

                } elseif (isset($result)) { ?>
            <tr><td colspan="2">Request to <?php echo $transaction_type ?> account for 
                $<?php echo $transaction_cents / 100 ?>.00 failed.</td></tr><?php
                } elseif (isset($error_message)) { ?>
            <tr><td colspan="2">Could not complete transaction.<br />
                <?php echo $error_message ?></td></tr><?php
                } ?>
            <tr><td>Current Balance</td><td>$<?php printf("%d.%02d", $cf_balance/100, $cf_balance%100) ?></td></tr>
            <tr>
                <td><input type="radio" name="TransactionType" value="Credit" checked="checked" />Credit <br />
                    <input type="radio" name="TransactionType" value="Debit" />Debit<br >
                </td>
                <td>Amount<br>
                    $<input type="text" name="Amount" size="5" />.00</td>
            </tr>
            <tr><td colspan="2">Reason for Credit/Debit:<br />
                <input type="text" size="35" name="Description" /></td>
            </tr>
                
            <tr>
                <td><input type="button" value="Cancel" onclick="document.location = 'care_facilities.php';" /></td>
                <td class="alignright" colspan="2"><input type="submit" name="UpdateBalance" value="Update Balance" /></td>
            </tr>
        </table>
    </form>

<?php
    // End of edit balance section
    } else {
        header("location: care_facilities.php");
    }

?>


<?php
function admin_large_facility_add_action() {
    $address = NULL;
    if (isset($_POST['LargeFacilityName'])) {
        $fields = array('LargeFacilityName','Email','Address1','City','State','Zip5');
        $missing_fields = array();
        foreach ($fields as $fieldname) {
            if (!$_POST[$fieldname]) {
                $missing_fields[] = $fieldname;
                echo $v;
            }
        }


        $address = array('Address1' => $_POST['Address1'],
                         'Address2' => $_POST['Address2'],
                         'City' => $_POST['City'],
                         'State' => $_POST['State'],
                         'ZIP5' => $_POST['Zip5'],
                         'ZIP4' => $_POST['Zip4']);


        if (count($missing_fields) == 0) {
            if (db_start_transaction()) {
                $rider_info = array('RiderStatus' => 'Active',
                                    'FranchiseID' => $_POST['Franchise'],
                                    'EmergencyContactID' => NULL,  // TODO:  Get contact ID for facility
                                    'EmergencyContactRelationship' => NULL,
                                    'QualificationReason' => 'Large Facility User',
                                    'DateOfBirth' => date('Y-m-d'));

                $address_id = add_address($address);

                $destination_id = create_destination_for_address_id(
                                                         $_POST['LargeFacilityName'], $address_id, 
                                                         $_POST['Franchise'], TRUE, TRUE,
                                                         NULL, '');
                $email_id = add_email_address($_POST['Email']);
                $lf_person_name_id = add_person_name('LF', 'LargeFacility', '', $_POST['LargeFacilityName'], '');

                // TODO:  Create user first
                // Create user name as ZZLF_Franchiseid_Name
                $lf_username = "ZZLF_{$_POST['Franchise']}_{$_POST['LargeFacilityName']}";
                $lf_user_id = add_user( $lf_username, mt_rand(1001,mt_getrandmax()) . mt_rand(1001,mt_getrandmax()), 
                                        'ACTIVE', $email_id, $lf_person_name_id, FALSE, 'APPROVED', 'CHECKED');

                $rider_id = add_rider($rider_info, $lf_user_id);
                // TODO:  Need to add role as rider ?
                set_role_for_user( $lf_user_id, 'RIDER' );

                $facility_id = add_large_facility(htmlspecialchars($_POST['LargeFacilityName']),
                                                  $_POST['Franchise'], $address_id, $rider_id, $email_id,
                                                  $destination_id);

                if ($address_id && $destination_id && $rider_id && $email_id && $facility_id) {
                    db_commit_transaction();
                    // TODO:  Sensible redirect
                    header('Location: admin_large_facilities.php');

                } else {
                    db_rollback_transaction();
                    $error = 'An error occurred trying to add the facility.';
                }
            }
        } else { 
            $error = 'Missing required fields: ' . implode(', ', $missing_fields); 
        }
    } 

    include('include/header.php');
?>
    <h2><center>Create Large Facility</center></h2>
<?php if ($error) { echo '<p style="color: red;">' . "$error</p>"; } ?>
    <form method="POST" action="">
        <input type="hidden" name="action" value="add" />
        <table style="margin:auto">
            <tr><td class="alignright">Facility Name</td>
                <td><input type="text" name="LargeFacilityName" maxlength="50" value="<?php
                           echo $_POST['LargeFacilityName']; ?>" style="width:20em;"></td>
            </tr>
            <tr><td class="alignright">Franchise</td>
                <td><select name="Franchise"><?php
                    $franchises = get_franchise_name_id_list();
                    foreach ($franchises as $id => $name) {
                        echo "<option value=\"$id\">$name</option>";
                    } ?>
                    </select></td>
            </tr>
            <tr>
                <td class="alignright">Default Email</td>
                <td><input type="text" name="Email" maxlength="60" style="width:20em;" value="<?php
                        echo htmlspecialchars($_POST['Email']) ?>"></td>
            <tr>
                <td colspan="2">
                    <?php create_html_address_table('', $address); ?>
                </td>
            </tr>
            <tr>
                <td class="alignright" colspan="2"><input type="submit" value="Create" /></td>
            </tr>
        </table>
    </form>
<?php
}  // End of add action


function admin_large_facility_add_contact_action() {
    $required_fields = array('FirstName', 'LastName', 'Email', 'Phone', 'Role', 'JobTitle');
    $other_fields = array('MiddleInitial', 'Title', 'Suffix');
    $missing_fields = array();

    foreach($required_fields as $fieldname) {
        if (!$_POST[$fieldname]) {
            $missing_fields[] = $fieldname;
        } else {
            $contact_post[$fieldname] = $_POST[$fieldname];
        }
    }

    foreach($required_fields as $fieldname) {
        if (!$_POST[$fieldname]) {
            // Nothing
        } else {
            $contact_post[$fieldname] = htmlspecialchars($_POST[$fieldname]);
        }
    }

    if (count($missing_fields) == 0) {
        // No missing fields.  Add the contact info
        if (db_start_transaction()) {
            $person_name_id = add_person_name($contact_post['Title'], $contact_post['FirstName'],
                                              $contact_post['MiddleInitial'], $contact_post['LastName'],
                                              $contact_post['Suffix']);

            $phone_id = add_phone_number($contact_post['Phone'], 'WORK');
                    
            $email_id = add_email_address($contact_post['Email']);

            $facility_contact_added = add_large_facility_contact( $_POST['id'], $person_name_id, $phone_id, 
                                                                  $email_id, $contact_post['Role'],
                                                                  $contact_post['JobTitle']);

            if ($person_name_id && $phone_id && $email_id && $facility_contact_added) {
                db_commit_transaction();
                // TODO:  Sensible redirect
                header('Location: admin_large_facility?id=' . $_POST['id']);
                exit;
            } else {
                db_rollback_transaction();
                $error_message = 'An error occurred trying to add the facility contact.';
            }
        }
                

    } elseif (count($missing_fields) != count($required_fields)) {
        $error_message = 'Missing required fields: ' . implode(', ', $missing_fields); 
    }

    
    // TODO:  Left off ready to start large facilty add contact
    /*
    if(isset($_GET['action']) && $_GET['action'] == 'addcontact' && isset($_GET['id'])){
        if(isset($_POST['Email']) && if_current_user_has_role('FullAdmin')){
            $fields = array('FirstName','LastName','Email','Phone','JobTitle','Role');
            $error = FALSE;
            foreach($fields as $k => $v){
                if(!isset($_POST[$v]) || $_POST[$v] == ''){
                    $error = TRUE;
                    echo $v;
                }
                    
            } if(!$error){
            }
        }
        */
    include_once 'include/header.php';
    //display_care_facility_header( $_GET['id'] );
    ?>
    <?php if ($error_message) { echo '<p style="color: red">' . $error_message . '</p>'; } ?>
    <form method="POST" action="">
        <input type="hidden" name="action" value="addcontact" />
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($_REQUEST['id']) ?>" />
        <div style="text-align: center"><h2>Add Large Facility Contact</h2></div>
        <table style="margin:auto;">
            <tr>
                <td colspan="2">
                    <b>Contact Name</b>
                    <table>
                        <tr>
                            <td class="alignright">Title</td>
                            <td><input type="text" name="Title" maxlength="10" style="width:50px;" value="<?php
                                echo $contact_post['Title'] ?>" /></td>
                        </tr>
                        <tr>
                            <td class="alignright">*First Name</td>
                            <td><input type="text" name="FirstName" maxlength="30" style="width:20em;" value="<?php
                                echo $contact_post['FirstName'] ?>" /></td>
                        </tr>
                        <tr>
                            <td class="alignright">Middle Initial</td>
                            <td><input type="text" name="MiddleInitial" maxlength="1" style="width:50px;" value="<?php
                                echo $contact_post['MiddleInitial'] ?>" /></td>
                        </tr>
                        <tr>
                            <td class="alignright">*Last Name</td>
                            <td><input type="text" name="LastName" maxlength="30" style="width:20em;" value="<?php
                                echo $contact_post['LastName'] ?>" /></td>
                        </tr>
                        <tr>
                            <td class="alignright">Suffix</td>
                            <td><input type="text" name="Suffix" maxlength="10" style="width:50px;" value="<?php
                                echo $contact_post['Suffix'] ?>" /></td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td colspan="2"><b>Contact Email</b></td>
            </tr>
            <tr>
                <td class="alignright" style="width:80px;">*Email</td>
                <td><input type="text" name="Email" maxlength="60" style="width:20em;" value="<?php
                                echo $contact_post['Email'] ?>" /></td>
            </tr>
            <tr>
                <td colspan="2"><b>Contact Phone</b></td>
            </tr>
            <tr>
                <td class="alignright">*Number</td>
                <td><input type="text" name="Phone" maxlength="20" style="width:20em;" value="<?php
                                echo $contact_post['Phone'] ?>" /></td>
            </tr>
            <tr>
                <td colspan="2"><b>Contact Role</b></td>
            </tr>
            <tr>
                <td class="alignright">*Role</td>
                <td>
                    <select name="Role">
                        <option value="DecisionMaker">Decision Maker</option>
                        <option value="TransportCoordinator">Transport Coordinator</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td colspan="2"><b>Contact Job Title</b></td>
            </tr>
            <tr>
                <td class="alignright">*Title</td>
                <td><input type="text" name="JobTitle" maxlength="30" style="width:20em;" value="<?php
                                echo $contact_post['JobTitle'] ?>" /></td>
            </tr>
            <tr>
                <td class="alignright" colspan="2"><input type="submit" value="Create" /></td>
            </tr>
        </table>
    </form>
        <?php



}

?>
