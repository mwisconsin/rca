<?php
	include_once 'include/user.php';
	include_once 'include/care_facility.php';
	include_once 'include/address.php';
	include_once 'include/name.php';
	include_once 'include/email.php';
	include_once 'include/phone.php';
    require_once('include/franchise.php');
	
	redirect_if_not_logged_in();
	$franchise  = get_current_user_franchise();
	
    if (!$_REQUEST['id']) {
        $affected_user_id = get_affected_user_id();
        if (user_has_role($affected_user_id, $franchise, 'CareFacilityAdmin')) {
            $facility_id = get_first_user_care_facility( $affected_user_id );
        } else {
            header('Location: home.php');
        }
    } else {
        $facility_id = $_REQUEST['id'];
    }

	if(!is_real_care_facility($facility_id))
        header('Location: home.php');

	/*if((!current_user_has_role('FullAdmin') && !current_user_has_role($franchise, 'Franchisee')) && !if_current_user_has_care_facility($facility_id)){
        header('Location: home.php');
		die();
	}DOGDOG*/ 
	$facility = get_care_facility( $facility_id );
	$facility_address = get_address( $facility['FacilityAddressID'] );
    include_once 'include/header.php';
?>
<center><h2><?php echo $facility['CareFacilityName']; ?></h2></center>
<br>
Facility Name:  <?php echo $facility['CareFacilityName']; ?><br />
Club:  <?php echo get_franchise_name( $facility['FranchiseID'] ); ?><br />
<a href="edit_care_facility.php?action=edit&id=<?php echo $facility_id; ?>">Edit Facility and Address</a><br />
<hr />
<b>Address</b>
<?php
	create_html_display_address($facility_address['AddressID'],'');
?>
<br />
<b>Phone: </b>
<?php
	$phone = get_phone_number($facility['PhoneID']);
	echo $phone['PhoneNumber'].($phone['Ext'] != '' ? ' x'.$phone['Ext'] : '');
?>
<br>
<br>
<hr />
<b>Annual Fee Payment Date</b>
<p><?php echo $facility['AnnualFeePaymentDate']; ?></p>
<hr />
<b>Contacts</b><br>
<?php
	$contacts = get_care_facility_contacts( $facility_id );
	$contact_num = 0;
	if($contacts){
		while($row = mysql_fetch_array($contacts)){
			$contact_num++;
			$name = get_name( $row['ContactNameID'] );
			$email = get_email_address( $row['ContactEmailID'] );
			$phone = get_phone_number( $row['ContactPhoneID'] );
			?>
			<table>
				<tr>
					<td colspan="2">Contact Number: <?php echo $contact_num; ?><br><center><?php echo $name['FirstName'] . ' ' . $name['LastName']; ?><br>
						<button onClick="window.location.href='/edit_care_facility.php?action=editcontact&id=<?php echo $facility_id; ?>&nameid=<?php echo $row['ContactNameID']; ?>'; return false;">Edit</button>
						<button onClick="window.location.href='/edit_care_facility.php?action=deletecontact&id=<?php echo $facility_id; ?>&nameid=<?php echo $row['ContactNameID']; ?>'; return false;">Delete</button></center></td>
				</tr>
				<tr>
					<td class="alignright">Email</td>
					<td><?php echo $email['EmailAddress']; ?></td>
				</tr>
				<tr>
					<td class="alignright">Phone</td>
					<td><?php echo $phone['PhoneNumber'].($phone['Ext'] != '' ? ' x'.$phone['Ext'] : ''); ?></td>
				</tr>
				<tr>
					<td class="alignright">Role</td>
					<td><?php echo $row['ContactRole']; ?></td>
				</tr>
				<tr>
					<td class="alignright">Job Title</td>
					<td><?php echo $row['ContactTitle']; ?></td>
				</tr>
			</table>
			<?php
		}
		echo '<br><a href="' . site_url() . 'edit_care_facility.php?action=addcontact&id=' . $facility_id . '">Add A New Contact</a>';
	} else {
		echo 'You have no contacts for this care facility. <a href="' . site_url() . 'edit_care_facility.php?action=addcontact&id=' . $facility_id . '">add</a>';
	}
	
?>
<?php if (current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee')) { ?>
<p><hr /><a href="care_facilities.php">Return to Care Facilities</a></p>
<?php } ?>
<?php
	include_once 'include/footer.php';
?>
