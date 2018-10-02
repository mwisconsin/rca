<?php 
	include 'include/user.php';
	redirect_if_not_logged_in();
	include_once 'include/name.php';
	include_once 'include/charity.php';
	include_once 'include/address.php';
	include_once 'include/franchise.php';
	$franchise = get_current_user_franchise();
	
	$editing = false;
	$approve = false;
	if(($_GET['action'] == 'edit') && ($_GET['id'] != '') && (current_user_has_role(1 , "FullAdmin") || current_user_has_role($franchise, "Franchisee"))){
		$editing = true;
		$id = $_GET['id'];
		$charity = get_charity($id);
		$edit_url = "?action=edit&id=" . $id;
	}
	if(($_GET['action'] == 'approve') && ($_GET['id'] != '') && (current_user_has_role(1 , "FullAdmin") || current_user_has_role($franchise, "Franchisee"))){
		$approve= true;
		$id = $_GET['id'];
		$charity = get_charity($id);
		$edit_url = "?action=approve&id=" . $id;
	}
	$required = array('CharityName','FirstName','LastName','ContactTitle','ContactPhone','Address1','City','State','Zip5');
	$error = FALSE;
	if($_POST['submited']){
		
		foreach($required as $field){
			if(!isset($_POST[$field]) || $_POST[$field] == '')
				$error = TRUE;
		}
		if(!$error){
			$name_id  = add_person_name( $_POST['Title'], $_POST['FirstName'], $_POST['MiddleInital'],$_POST['LastName'], $_POST['Suffix']);
			$address_id =  add_address(array( 'Address1' => $_POST['Address1'],
																'Address2' => $_POST['Address2'],
																'City' => $_POST['City'],
																'State' => $_POST['State'],
																'ZIP5' => $_POST['Zip5'],
																'ZIP4' => $_POST['Zip4']));
			$phone_id = add_phone_number($_POST['ContactPhone'], 'OTHER', 'N', 0, $_POST['ContactPhoneExt'] );
			$email_id = (!isset($_POST['ContactEmail']) || $_POST['ContactEmail'] == '') ? NULL : add_email_address($_POST['ContactEmail']);
																														   
			$charity = add_charity($_POST['CharityName'],$franchise,$address_id,$name_id,$phone_id,$email_id,$_POST['ContactTitle'],$_POST['CharityHours']);
			
			link_user_with_charity(get_affected_user_id(), $charity);
			header("Location: support_list.php");
		}
	}
	if(isset($_POST['edited']) || isset($_POST['approve']) ){
		if($charity['ContactAddressID'] == NULL){
			$address_id =  add_address(array( 'Address1' => $_POST['Address1'],
																'Address2' => $_POST['Address2'],
																'City' => $_POST['City'],
																'State' => $_POST['State'],
																'ZIP5' => $_POST['Zip5'],
																'ZIP4' => $_POST['Zip4']));
		} else {
			$address_id = $charity['ContactAddressID'];
			update_address($address_id, array( 'Address1' => $_POST['Address1'],
															   	 'Address2' => $_POST['Address2'],
															'City' => $_POST['City'],
															'State' => $_POST['State'],
															'ZIP5' => $_POST['Zip5'],
															'ZIP4' => $_POST['Zip4']));
		}
		if($charity['ContactNameID'] == NULL){
			$name_id  = add_person_name( $_POST['Title'], $_POST['FirstName'], $_POST['MiddleInital'],$_POST['LastName'], $_POST['Suffix']);
		} else {
			$name_id  = $charity['ContactNameID'];
			update_person_name($name_id, $_POST['Title'], $_POST['FirstName'], $_POST['MiddleInital'],$_POST['LastName'], $_POST['Suffix']);
		}
		if($charity['ContactEmailID'] == NULL){
			$email_id  = add_email_address($_POST['ContactEmail']);
		} else {
			$email_id  = $charity['ContactEmailID'];
			update_email_address( $email_id, $_POST['ContactEmail'] );
		}
		if($charity['ContactPhoneID'] == NULL){
			$phone_id  = add_phone_number($_POST['ContactPhone'], 'OTHER', 'N', 0, $_POST['ContactPhoneExt'] );
		} else {
			$phone_id  = $charity['ContactPhoneID'];
			update_phone_number( $phone_id, $_POST['ContactPhone'], 'OTHER', 'N', 0, $_POST['ContactPhoneExt']);
		}
		if(isset($_POST['approve']))
			$approved = TRUE;
		else
			$approved = ( $charity['Approved'] == 'Y') ? TRUE : FALSE;
		
		update_charity( $charity['CharityID'], $_POST['CharityName'],$address_id,$name_id,$phone_id,$email_id,$_POST['ContactTitle'],$_POST['CharityHours'],$approved);
		if(isset($_POST['approved']))
			header("Location: admin_charity_request.php");
		else
			header("Location: admin_charity_request.php");
	}
	if($_GET['action'] == 'delete' && $_POST['deleteid'] != '' &&  (current_user_has_role(1 , "FullAdmin") || current_user_has_role($franchise, "Franchisee"))){
		delete_charity($_POST['deleteid']);
		header("Location: admin_charity_request.php?delete=true");
	}
	
	
	include 'include/header.php';
	
	if($_GET['action'] == 'delete' && $_GET['id'] != '' &&  (current_user_has_role(1 , "FullAdmin") || current_user_has_role($franchise, "Franchisee"))){
		$id = $_GET['id'];
		$charity = get_charity($id);
		?><br /><br /><br /><br /><br /><br />
        <form method="post" action="<?php echo $_SESSION['PHP_SELF']; ?>">
        	<input type="hidden" name="deleteid" value="<?php echo $id; ?>" />
            <table style="margin:auto;">
            	<tr>
            		<td colspan="2"> Are you sure you want to delete this charity? ( <?php echo $charity['CharityName']; ?> )</td>
                </tr>
                <tr>
                	<td><input type="button" value="Cancel" onclick="document.location = 'admin_charity_request.php';" /></td>
                    <td class="alignright"><input type="submit" value="Delete"  /></td>
                </tr>
            </table>
        </form>
        <?php
		include_once 'include/footer.php';
		die();
	}
?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . $edit_url; ?>">
	<input type="hidden" value="ridersclub" name="<?php if($editing) echo "edited"; else if($approve) echo "approve"; else echo "submited"; ?>"/>
    <?php if($editing){ ?>
    	<h2>Editing Charity</h2>
     <?php } else if($approve) { ?>
     	<h2>Approve Charity</h2>
    <?php } else { ?>
    	<h2>New Charity</h2>
    <?php
		}
		if($error){
			echo "One or more items were not filled in. All required are shown by a (*).<br /><br />";
		}
	?>
    <table>
        <tr>
            <td>Charity Name*</td>
            <td><input type="text" name="CharityName" value="<?php echo $charity['CharityName']; ?>" style="width:200px;" maxlength="40" /></td>
        </tr>
        <tr>
            <td>Contact Name*</td>
        </tr>
        <tr>
            <td colspan="2" style="padding-left:26px;">
                <?php
                     print_get_name_form_part($charity,NULL,FALSE);
                ?>
            </td>
        </tr>
        <tr>
            <td>Contact Title*</td>
            <td><input type="text" name="ContactTitle" value="<?php echo $charity['ContactTitle']; ?>" style="width:200px;" maxlength="30" /></td>
        </tr>
        <tr>
            <td>Contact Email</td>
            <td><input type="text" name="ContactEmail" value="<?php echo $charity['EmailAddress']; ?>" style="width:200px;" maxlength="60" /></td>
        </tr>
        <tr>
            <td>Contact Phone*</td>
            <td><input type="text" name="ContactPhone" value="<?php echo $charity['PhoneNumber']; ?>" style="width:150px;"  />
            		x<input type="text" name="ContactPhoneExt" value="<?php echo $charity['Ext']; ?>" style="width:36px;" />	
            </td>
        </tr>
        <tr>
            <td>Contact Address</td>
        </tr>
        <tr>
            <td colspan="2">
                <?php
                    create_html_address_table(NULL, $charity);
                ?>
            </td>
        </tr>
        <tr>
            <td>Office Hours</td>
            <td><input type="text" name="CharityHours" value="<?php echo $charity['Hours']; ?>" style="width:200px" maxlength="40" /></td>
        </tr>
        <tr>
            <td colspan="2" class="alignright">
                <?php if($editing){ ?>
    				<input type="submit" value="Save" />
     			<?php } else if($approve) { ?>
     				<input type="submit" value="Approve" />
   				<?php } else { ?>
    				<input type="submit" value="Edit" />
   				<?php
					} 
				?>
            </td>
        </tr>
    </table>
</form>

<script type="text/javascript">
	 
</script>
<?php include 'include/footer.php'; ?>