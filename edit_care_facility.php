<?php
    include_once 'include/user.php';
	include_once 'include/care_facility.php';
	include_once 'include/address.php';
	include_once 'include/email.php';
	include_once 'include/phone.php';
	include_once 'include/name.php';
    require_once 'include/ledger.php';
	require_once 'include/franchise.php';
	
	redirect_if_not_logged_in();
	$franchise = get_current_user_franchise();
	if (!$_REQUEST['id'] && ($_GET['action']!='add')) {
        $affected_user_id = get_affected_user_id();
        if (user_has_role($affected_user_id, $franchise, 'CareFacilityAdmin') || (current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, "Franchisee"))) {
            $facility_id = get_first_user_care_facility( $affected_user_id );
        } else {
            header('Location: home.php');
        }
    } else {
        $facility_id = $_REQUEST['id'];
    }
	
	if(!is_real_care_facility($facility_id) && ($_GET['action']!='add'))
        header('Location: home.php');

	if((!current_user_has_role(1, 'FullAdmin') && !current_user_has_role($franchise, 'Franchisee')) && !if_current_user_has_care_facility($facility_id)){
        header('Location: home.php');
		die();
	}
	
	if(isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])){
		if(isset($_POST['CareFacilityName']) && (current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, "Franchisee"))){
			$fields = array('CareFacilityName','Address1','City','State','Zip5','Phone');
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
				
				$phoneid = add_phone_number($_POST['Phone'],'WORK','N',0,$_POST['Ext']);
				
				$sql = "UPDATE `care_facility` SET `CareFacilityName` = '$safe_name', `FranchiseID` = '$safe_franchise', PhoneID = $phoneid WHERE `CareFacilityID` =$safe_id LIMIT 1 ;";
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
					<td><input type="text" name="CareFacilityName" maxlength="50" value="<?php echo $facility['CareFacilityName']; ?>" style="width:200px;"></td>
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
					<td align=right>Phone</td>	
					<?php
					$phone = get_phone_number($facility['PhoneID']);
					?>
					<td><input type=text size=15 name=Phone value="<?php echo @$phone['PhoneNumber']; ?>">
						x<input type=text size=4 name=Ext value="<?php echo @$phone['Ext']; ?>">
						</td>
				</tr>
				<tr>
					<td class="alignright" colspan="2"><input type="submit" value="Save" /></td>
				</tr>
			</table>
		</form>
		<?php
	} else if(isset($_GET['action']) && $_GET['action'] == 'add'){
		if(isset($_POST['CareFacilityName']) && (current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, "Franchisee"))){
			$fields = array('CareFacilityName','Email','Address1','City','State','Zip5');
			$error = FALSE;
			foreach($fields as $k => $v){
				if(!isset($_POST[$v]) || $_POST[$v] == ''){
					$error = TRUE;
					echo $v;
				}
					
			} if(!$error){
				
				$address = array('Address1' => $_POST['Address1'],
								 'Address2' => $_POST['Address2'],
								 'City' => $_POST['City'],
								 'State' => $_POST['State'],
								 'ZIP5' => $_POST['Zip5'],
								 'ZIP4' => $_POST['Zip4']);
				$address = add_address($address);
				
				$email = add_email_address($_POST['Email']);
				
				add_care_facility($_POST['CareFacilityName'],$_POST['Franchise'],$address,$email);
				
				header("location: care_facilities.php");
			}
			
		}
		include_once 'include/header.php';
		?>
		<h2><center>Create Care Facility</center></h2>
		<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?action=add">
			<table style="margin:auto">
				<tr>
					<td class="alignright">Facility Name</td>
					<td><input type="text" name="CareFacilityName" maxlength="50" style="width:200px;"></td>
				</tr>
				<tr>
					<td class="alignright">Franchise</td>
					<td>
						<select name="Franchise">
						<?php
							$query = "SELECT * FROM `franchise` WHERE 1;";
							$result = mysql_query($query) or die(mysql_query());
							while($row = mysql_fetch_array($result))
								echo '<option value="' . $row['FranchiseID'] . '">' . $row['FranchiseName'] . '</option>';
						?>
						</select>
					</td>
				</tr>
				<tr>
					<td class="alignright">Default Email</td>
					<td><input type="text" name="Email" maxlength="60" style="width:200px;"></td>
				<tr>
					<td colspan="2">
						<?php create_html_address_table(); ?>
					</td>
				</tr>
				<tr>
					<td class="alignright" colspan="2"><input type="submit" value="Create" /></td>
				</tr>
			</table>
		</form>
		<?php
	} else if(isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id']) && $_GET['id'] != ''){
		if(isset($_POST['Delete']) && (current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, "Franchisee"))){
			$care_facility = get_care_facility( $_GET['id'] );
			
			//remove_all_facility_ride_links( $care_facility['CareFacilityID'] );
			remove_all_contacts_for_facility( $care_facility['CareFacilityID'] );
			remove_all_user_care_facility_connections( $care_facility['CareFacilityID'] );
			delete_care_facility( $care_facility['CareFacilityID'] );
			//delete_address( $care_facility['FacilityAddressID'] );

			header("location: care_facilities.php");
		}
		include_once 'include/header.php';
		$care_facility = get_care_facility($_GET['id']);
		$address = get_address($care_facility['FacilityAddressID']);
		?>
		<form method="post" action="<?php echo site_url() . 'edit_care_facility.php?action=delete&id=' . $_GET['id']; ?>">
			<center>
				<h2>Delete Care Facility</h2>
				Are You sure you want to delete this care facility?
			</center>
			<br>
			<table style="margin:auto;">
				<tr>
					<td colspan="2"><center><b><?php echo $care_facility['CareFacilityName']; ?></b></center></td>
				</tr>
				<tr>
					<td colspan="2"><?php create_html_display_address($address['AddressID']); ?></td>
				</tr>
				<tr>
					<td><input type="button" value="Cancel" onclick="document.location = '<?php echo site_url() . "care_facilities.php"; ?>';" /></td>
					<td class="alignright" colspan="2"><input type="submit" name="Delete" value="Delete" /></td>
				</tr>
			</table>
		</form>
		<?php
	} else if(isset($_GET['action']) && in_array($_GET['action'],array('addcontact','editcontact','deletecontact')) && isset($_GET['id'])){
		if(in_array($_GET['action'],array('addcontact','editcontact')) && count($_POST) > 0) {
			$fields = array('FirstName','LastName','Email','Phone','JobTitle','Role');
			$error = FALSE;
			foreach($fields as $k => $v){
				if(!isset($_POST[$v]) || $_POST[$v] == ''){
					$error = TRUE;
					echo $v;
				}
					
			} if(!$error){
				if($_GET['action'] == 'addcontact') {
					$person_name = add_person_name($_POST['Title'],$_POST['FirstName'],$_POST['MiddleInitial'],$_POST['LastName'],$_POST['Suffix']);		
					$phone = add_phone_number($_POST['Phone'],'WORK','N',0,$_POST['Ext']);
					$email = add_email_address($_POST['Email']);
					add_care_facility_contact($_GET['id'],$person_name,$phone,$email,$_POST['Role'],$_POST['JobTitle']);
				} else {
					$cfc = get_care_facility_contact( $_GET['id'], $_GET['nameid'] );
					mysql_query(sprintf("update person_name set Title = '%s',FirstName='%s',MiddleInitial='%s',LastName='%s',Suffix='%s' where PersonNameId = %d",
						$_POST['Title'],$_POST['FirstName'],$_POST['MiddleInitial'],$_POST['LastName'],$_POST['Suffix'],$_GET['nameid']));
					mysql_query(sprintf("update phone set PhoneNumber='%s', Ext='%s' where PhoneID=%d",$_POST['Phone'],$_POST['Ext'],$cfc["ContactPhoneID"]));
					mysql_query(sprintf("update email set EmailAddress='%s' where EmailID=%d",$_POST['Email'],$cfc["ContactEmailID"]));
					mysql_query(sprintf("update care_facility_contact set ContactRole='%s',ContactTitle='%s' where CareFacilityID=%d and ContactNameID=%d",
						$_POST['Role'],$_POST['JobTitle'],$_GET['id'],$_GET['nameid']));
				}
				header("Location: " . site_url() . "care_facility.php?id=" . $_GET['id']);
			}
		} else if($_GET['action'] == 'deletecontact' && count($_POST) == 0) {
			include_once 'include/header.php';
			display_care_facility_header( $_GET['id'] );		
			$cfc = get_care_facility_contact( $_GET['id'], $_GET['nameid'] );
			?>
			<h2>Are you sure you want to delete <?php echo $cfc["FirstName"]." ".$cfc["LastName"]; ?>?
			<form method=POST action="<?php echo site_url() . 'edit_care_facility.php?action=deletecontact&id=' . $_GET['id'] .'&nameid='.$_GET['nameid']; ?>">
				<p style='margin-left: 200px;'><input type=submit name="Yes" value="YES">&nbsp;&nbsp;&nbsp;&nbsp;
				<input type=submit name="No" value="NO"></p>
			</h2>
			</form>
			<?php	
			exit();
		} else if($_GET['action'] == 'deletecontact' && count($_POST) > 0) {
			if($_POST["Yes"] != "") {
				$cfc = get_care_facility_contact( $_GET['id'], $_GET['nameid'] );
				mysql_query(sprintf("delete from person_name where PersonNameID = %d",$_GET['nameid']));
				mysql_query(sprintf("delete from email where EmailID = %d",$cfc["ContactEmailID"]));
				mysql_query(sprintf("delete from phone where PhoneID = %d",$cfc["ContactPhoneID"]));
				mysql_query(sprintf("delete from care_facility_contact where CareFacilityID=%d and ContactNameID=%d",
					$_GET["id"],$_GET["nameid"]));
			}
			header("Location: " . site_url() . "care_facility.php?id=" . $_GET['id']);
		}
		include_once 'include/header.php';
		display_care_facility_header( $_GET['id'] );
		if($_GET['action'] == 'editcontact') $cfc = get_care_facility_contact( $_GET['id'], $_GET['nameid'] );
		else $cfc = array();
		?>
		<form method="post" action="<?php echo site_url() . 'edit_care_facility.php?action='.$_GET['action'].'&id=' . $_GET['id'] .($_GET['action'] == 'editcontact' ? '&nameid='.$_GET['nameid'] : ''); ?>">
			<center><h2><?php echo $_GET['action'] == 'addcontact' ? 'Add' : 'Edit'; ?> Facility Contact</h2></center>
			<table style="margin:auto;">
				<tr>
					<td colspan="2">
						<b>Contact Name</b>
						<table>
							<tr>
								<td class="alignright">Title</td>
								<td><input type="text" name="Title" maxlength="10" style="width:50px;" value="<?php echo @$cfc["Title"]; ?>" ></td>
							</tr>
							<tr>
								<td class="alignright">*First Name</td>
								<td><input type="text" name="FirstName" maxlength="30" style="width:200px;" value="<?php echo @$cfc["FirstName"]; ?>"></td>
							</tr>
							<tr>
								<td class="alignright">Middle Initial</td>
								<td><input type="text" name="MiddleInitial" maxlength="1" style="width:50px;" value="<?php echo @$cfc["MiddleInitial"]; ?>"></td>
							</tr>
							<tr>
								<td class="alignright">*Last Name</td>
								<td><input type="text" name="LastName" maxlength="30" style="width:200px;" value="<?php echo @$cfc["LastName"]; ?>"></td>
							</tr>
							<tr>
								<td class="alignright">Suffix</td>
								<td><input type="text" name="Suffix" maxlength="10" style="width:50px;" value="<?php echo @$cfc["Suffix"]; ?>"></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td colspan="2"><b>Contact Email</b></td>
				</tr>
				<tr>
					<td class="alignright" style="width:80px;">*Email</td>
					<td><input type="text" name="Email" maxlength="60" style="width:200px;" value="<?php echo @$cfc["EmailAddress"]; ?>"></td>
				</tr>
				<tr>
					<td colspan="2"><b>Contact Phone</b></td>
				</tr>
				<tr>
					<td class="alignright">*Number</td>
					<td><input type="text" name="Phone" maxlength="15" style="width:151px;" value="<?php echo @$cfc["PhoneNumber"]; ?>">
						x<input type="text" name="Ext" maxlength="5" style="width:33px;" value="<?php echo @$cfc["Ext"]; ?>"></td>
				</tr>
				<tr>
					<td colspan="2"><b>Contact Role</b></td>
				</tr>
				<tr>
					<td class="alignright">*Role</td>
					<td>
						<select name="Role">
							<option value="DecisionMaker" <?php echo @$cfc["ContactRole"] == "DecisionMaker" ? " selected" : ""; ?>>Decision Maker</option>
							<option value="TransportCoordinator" <?php echo @$cfc["ContactRole"] == "TransportCoordinator" ? " selected" : ""; ?>>Transport Coordinator</option>
						</select>
					</td>
				</tr>
				<tr>
					<td colspan="2"><b>Contact Job Title</b></td>
				</tr>
				<tr>
					<td class="alignright">*Title</td>
					<td><input type="text" name="JobTitle" maxlength="30" style="width:200px;" value="<?php echo @$cfc["ContactTitle"]; ?>"></td>
				</tr>
				<tr>
					<td class="alignright" colspan="2"><input type="submit" value="<?php echo $_GET["action"] == "addcontact" ? "Create" : "Submit Changes"; ?>" /></td>
				</tr>
			</table>
		</form>
		<?php



	} else if (isset($_GET['action']) && $_GET['action'] == 'edit_balance' && 
              isset($_GET['id']) && $_GET['id'] != ''){
        $cf_id = $_GET['id'];

        if (isset($_POST['UpdateBalance']) && (current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, "Franchisee"))){
            $transaction_type = $_POST['TransactionType'];
            $transaction_cents = $_POST['Amount'] * 100;
            $transaction_desc = trim($_POST['Description']);

            if ($transaction_cents != 0 && $transaction_desc != '' && 
                ($transaction_type == 'Credit' || $transaction_type == 'Debit') ) {

                $user_id = get_current_user_id();
                $transaction_desc .= "  (updated by user $user_id)";
                $effective_date = "{$_POST['EffectiveYear']}-{$_POST['EffectiveMonth']}-{$_POST['EffectiveDay']}";

                if ($transaction_type == 'Credit') {
                    $result = credit_care_facility($cf_id, $transaction_cents, $transaction_desc, $effective_date);
                } elseif ($transaction_type == 'Debit') {
                    $result = debit_care_facility($cf_id, $transaction_cents, $transaction_desc, $effective_date);
                }
            } else {
                // TODO:  Display some error
                $error_message = "All fields are required.";
            }
        } 

        if (isset($_POST['PayAnnualFeeFromBalance']) && (current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, "Franchisee"))) {
            define(ANNUAL_FEE_AMOUNT, get_user_annual_fee( $franchise ));
						$effective_date = "{$_POST['EffectiveYear']}-{$_POST['EffectiveMonth']}-{$_POST['EffectiveDay']}";

            $cf_balance = calculate_care_facility_balance( $cf_id );
            if ($cf_balance >= ANNUAL_FEE_AMOUNT) {
                
                $fee_ledger_id = debit_care_facility( $cf_id, ANNUAL_FEE_AMOUNT,
                                             "Applied annual care facility fee of " . ANNUAL_FEE_AMOUNT, $effective_date ); 
                if ($fee_ledger_id) {
                    if (set_care_facility_annual_fee_payment_date( $cf_id, $effective_date )) {
                        rc_log(PEAR_LOG_INFO, "Applied care facility annual fee payment of " .
                                              ANNUAL_FEE_AMOUNT . " for CF $cf_id");
                    } else {
                        rc_log(PEAR_LOG_ERR, "COULD NOT SET ANNUAL FEE FOR CARE FACILITY $cf_id " .
                                             "AFTER DEBITING FROM ACCOUNT ($fee_ledger_id)");
                    }
                } else {
                    $error_string = "An error occurred trying to debit this account for the annual fee.";
                }

            }
        }

        $cf_balance = calculate_care_facility_balance( $cf_id );
		$facility = get_care_facility($cf_id);

		$facility_info = get_care_facility( $facility_id );
		$facility_address = get_address($facility_info['FacilityAddressID']);
		$facility_contacts = get_care_facility_contacts_array( $facility_id );
		if ($facility_contacts) {
			foreach ($facility_contacts as $contact) {
				if ($contact['ContactRole'] == 'DecisionMaker') {
					$contact_name = get_name( $contact['ContactNameID'] );
					$facility_contact_name = get_displayable_person_name_string($contact_name);
		
					if ($contact['ContactTitle']) {
						$facility_contact_name .= ", {$contact['ContactTitle']}";
					}
				} elseif (!isset($facility_contact_name)) {
					$contact_name = get_name( $contact['ContactNameID'] );
					$facility_contact_name = get_displayable_person_name_string($contact_name);
		
					if ($contact['ContactTitle']) {
						$facility_contact_name .= ", {$contact['ContactTitle']}";
					}
				}
			}
		} else {
			$facility_contact_name = ' ';
		}
		
		include_once 'include/header.php';
         ?>
    <form method="post" action="edit_care_facility.php?action=edit_balance&id=<?php echo $cf_id ?>">
        <center>
        <br />
        <h2>Credit/Debit Care Facility</h2>
   		<?php echo $facility_info['CareFacilityName'] ?><br />
		<?php echo $facility_contact_name ?><br />
		<?php echo '<div>' . create_compact_display_address($facility_address) . '</div>'; ?>
        <br />
     </center>
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
            <tr><td>Available Balance</td><td>$<?php 
            	$cf = get_all_active_care_facility_user_info_xx($franchise,$cf_id);
            	echo number_format($cf[0]["AvailableBalance"]/100,2);
            	?></td></tr>
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
            <tr><td colspan="2">
                Effective Date: <?php
                    print_year_select( 2009, date('Y') - 2008, 'EffectiveYear', 'EffectiveYear', date('Y'));
                    print_month_select( 'EffectiveMonth', 'EffectiveMonth' );
                    print_day_select('EffectiveDay', 'EffectiveDay', date('j')); ?>
                  <script type="text/javascript">
                  // <![CDATA[  
                    var opts = {                            
                            formElements:{"EffectiveDay":"j","EffectiveYear":"Y","EffectiveMonth":"n"},
                            showWeeks:true,
                            statusFormat:"l-cc-sp-d-sp-F-sp-Y",
                            callbackFunctions:{
                                "dateset": [function(obj){
                                    var notCheck = $('NotACheck');
                                    if (notCheck) {
                                        notCheck.checked = true;
                                    }
                                }]
                            }
                        };           
                    datePickerController.createDatePicker(opts);
                  // ]]>
                </script>
            </td></tr>
                
            <tr>
                <td><input type="button" value="Cancel" onclick="document.location = 'care_facilities.php';" /></td>
                <td class="alignright" colspan="2"><input type="submit" name="UpdateBalance" value="Update Balance" /></td>
            </tr>
            <tr><td colspan="2"><hr /></td></tr>
            <tr><td colspan="2">Annual Fee Last Paid <?php echo $facility['AnnualFeePaymentDate'] ?></td></tr>
<?php 
            if ($cf_balance >= 6000) { ?>
            <tr><td colspan="2">
                    <input type="submit" name="PayAnnualFeeFromBalance" value="Pay Annual Fee From Balance" />
                </td></tr><?php 
            } ?>
        </table>
        
        <input type=hidden name=AnnualFeePaymentDateOverride id=AnnualFeePaymentDateOverride value=""> 
    </form>

<?php
    // End of edit balance section
    } else {
		header("location: care_facilities.php");
	}
?>
<p><hr /><a href="care_facilities.php">Return to Care Facilities</a></p>
<?php 
	include_once 'include/footer.php';
?>

