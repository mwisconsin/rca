<?php
	require_once 'include/care_facility.php';
	require_once 'include/user.php';
	require_once 'include/name.php';
	redirect_if_not_logged_in();
	
	require_once 'include/franchise.php';
	
	/*if(!is_real_care_facility($facility_id)){
		header("location: " . site_url() . 'home.php');
		die();
	}

	if(!current_user_has_role(1, 'FullAdmin') && !current_user_has_role($franchise, 'Franchisee') && !if_current_user_has_care_facility( $facility_id )){
		header("location: " . site_url() . 'home.php');
		die();
	}DOGDOG*/
	
    if (isset($_POST['FacilityID'], $_POST['DisconnectUID'], $_POST['Disconnect'])) {
        //$disconnect_date = "{$_POST['EffectiveYear']}-{$_POST['EffectiveMonth']}-{$_POST['EffectiveDay']}";
        $disconnect_date = date('Y-m-d',strtotime($_POST["effective_date"]));
        if (disconnect_user_from_care_facilities($_POST['DisconnectUID'], $disconnect_date)) {
            $message = 'User connection to care facility removed.';
        } else {
            $message = 'Could not disconnect user from care facility.';
        }
        $facility_id = $_POST['FacilityID'];
    } else {

        if(isset($_GET['id']) && $_GET['id'] != ''){
            $facility_id = $_GET['id'];
        } else {
            $facility_id = get_first_user_care_facility( get_affected_user_id() );
        }
    }

    global $ADDITIONAL_RC_JAVASCRIPT;
    if (!is_array($ADDITIONAL_RC_JAVASCRIPT)) {
        $ADDITIONAL_RC_JAVASCRIPT = array();
    }
    $ADDITIONAL_RC_JAVASCRIPT[] = 'datepicker.js';


    require_once 'include/header.php';
	display_care_facility_header( $facility_id );
	$facility = get_care_facility($facility_id);
?>
<h2 style="text-align:center;"><?php echo $facility['CareFacilityName']; ?> - Approved Users</h2>
	<table border="1" style="width:100%;">
		<tr>
			<th>Name</th>
			<th>Apartment</th>
			<th>Edit</th>
			<th>Phone</th>
            <th>Disconnect</th>
		</tr>
			<?php
				$users = get_care_facility_users($facility_id);
				
				if($users){
					foreach($users as $user)
					{
						if(get_user_rider_preferences($user['UserID'])){
							$name = get_user_person_name($user['UserID']);
							echo '<tr>';
							echo '<td>' . $name['FirstName'] . ' ' . $name['LastName'] . '</td>';
							echo '<td>';
							$apartment = get_address($user['AddressID']);
							echo $apartment['Address2'];
							echo '</td>';
							echo '<td>' . '<a href="account.php?id=' . $user['UserID'] . '">View</a>' . '</td>';
							echo '<td nowrap>';
							$phone = get_phone_number_for_user( $user['UserID'] );
							echo $phone['PhoneNumber'].($phone['Ext'] != '' ? ' x'.$phone['Ext'] : '');
							echo '</td>';
                            echo '<td><form method="POST">' .
                                     '<input type="hidden" name="FacilityID" value="' . $facility_id . '" />' .
                                     '<input type="hidden" name="DisconnectUID" value="' .
                                            $user['UserID'] . '" />' .
                                     '<input type="submit" name="Disconnect" value="Disconnect" />' .
                                     '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Effective Date: ';
                            echo "<input type=text size=10 class=jq_datepicker name=effective_date value=\"$effective_date\">";
//                            print_year_select( 2009, date('Y') - 2008, 'EffectiveYear', "EffectiveYear{$user['UserID']}", date('Y'));
//                            print_month_select( 'EffectiveMonth', "EffectiveMonth{$user['UserID']}" );
//                            print_day_select('EffectiveDay', "EffectiveDay{$user['UserID']}", date('j'));
//                            echo <<<JS
//                                  <script type="text/javascript">
//                                  // <![CDATA[  
//                                    var opts = {                            
//                                            formElements:{"EffectiveDay{$user['UserID']}":"j","EffectiveYear{$user['UserID']}":"Y","EffectiveMonth{$user['UserID']}":"n"},
//                                            statusFormat:"l-cc-sp-d-sp-F-sp-Y",
//                                            callbackFunctions:{
//                                                "dateset": [function(obj){
//                                                    var notCheck = $('NotACheck');
//                                                    if (notCheck) {
//                                                        notCheck.checked = true;
//                                                    }
//                                                }]
//                                            }
//                                        };           
//                                    datePickerController.createDatePicker(opts);
//                                  // ]]>
//                                  </script>
//JS;

                            echo '</form></td>';
                            echo '</tr>';
						}
					}
				}
			?>
	</table>
	<?php
		if(!$users)
			echo '<br><br><center>No Users Found.</center>';
	?>
	<?php if (isset($facility_id)) { ?><div style="text-align:right; padding:3px;">
        <a href="connect_care_facility_user.php?id=<?php echo $facility_id ?>">Connect Existing User</a> &nbsp;&nbsp; 
		<a href="add_care_facility_user.php?id=<?php echo $facility_id; ?>">New User Application</a>
	</div><?php } ?>
		
	<h2 style="text-align:center;"><?php echo $facility['CareFacilityName']; ?> - Applied Users</h2>
	<table border="1" style="width:100%;">
		<tr>
			<th>Name</th>
			<th>Status</th>
			<th>Edit</th>
		</tr>
			<?php
				$users = get_care_facility_users($facility_id, '');
				
				if($users){
					foreach($users as $user)
					{
						$preferences = get_user_rider_preferences($user['UserID']);
						if($user['ApplicationStatus'] != "APPROVED" || !$preferences){
							$name = get_name($user['PersonNameID']);
							echo '<tr>';
							echo '<td>' . $name['FirstName'] . ' ' . $name['LastName'] . '</td>';
							echo '<td>';
							echo $user['ApplicationStatus'];
							if(!$preferences){
								echo ' | <a href="' . site_url() . 'edit_user.php?field=createriderpreferences&redirect=' . rawurlencode($_SERVER['PHP_SELF'] . '?id=' . $facility_id ). '&id=' . $user['UserID'] . '">Preferences Needed</a>';
							}
							echo '</td>';
							echo '<td>' . '<a href="account.php?id=' . $user['UserID'] . '">View</a> ';
				 			echo '</td>';
							echo '</tr>';
						}
					}
				}
			?>
	</table>
	<?php
		if(!$users)
			echo '<br><br><center>No Users Applied.</center>';
	?>
<p><hr /><a href="care_facilities.php">Return to Care Facilities</a></p>
<?php
	require_once 'include/footer.php';
?>
