<?php
	require_once 'include/address.php';
	require_once 'include/name.php';
	require_once 'include/email.php';
	require_once 'include/db_donation.php';
	require_once 'include/franchise.php';
	
	if(isset($_POST['Donate'])){
		$check = array('FirstName',
                       'LastName',
                       'Address1',
                       'City',
                       'State',
                       'Zip5',
                       'EmailAddress',
                       'Password',
                       'ConfirmPassword',
                       'PaymentSource',
                       'PaymentFrequency',
                       'PaymentAmount');

		$missing = FALSE;
		foreach($check as $k => $v){
			if(trim($_POST[$v]) == '') {
				$missing = TRUE;
            }
			//echo $_POST[$k];
		}

		if ($_POST['PaymentAmount'] == 'other' && number_format($_POST['CustomAmount'], 0) == '0'){
			$missing = TRUE;
		}

        $pass_match = ($_POST['Password'] == $_POST['ConfirmPassword']);

		if (!$missing && $pass_match) {
            // Create a Supporting Friend entry
            if (db_start_transaction()) {
                $name_id = add_person_name($_POST['Title'], $_POST['FirstName'], $_POST['MiddleInital'],
                                           $_POST['LastName'],$_POST['Suffix']);

                $user_email_address = $_POST['EmailAddress'];

                $email_id = add_email_address($user_email_address);
                
                $user_id = add_user( $user_email_address, $_POST['Password'], 'ACTIVE', 
                                     $email_id, $name_id, 'UNKNOWN', 'APPROVED' );
				

                $address = array('Address1' => $_POST['Address1'],
                                 'Address2' => $_POST['Address2'],
                                 'City' => $_POST['City'],
                                 'State' => $_POST['State'],
                                 'ZIP5' => $_POST['Zip5'],
                                 'ZIP4' => $_POST['Zip4']);
                $address_id = add_address($address); 
                link_address_to_user($address_id, 'Physical', $user_id);
				
                $role_set = set_role_for_user($user_id, $_POST['Franchise'], 'Supporter');

                if ($_POST['PaymentAmount'] == 'Other') {
                    $amount = intval($_POST['CustomAmount']) * 100;
                } else {
                    $amount = intval($_POST['PaymentAmount']) * 100;
                }
            
                $donation_id = add_donation_request( $name_id, $_POST['Franchise'], $address_id, $email_id, $_POST['DonationType'], 
                                                     $_POST['PaymentSource'], $_POST['PaymentFrequency'], $amount );
                if ($name_id && $address_id && $user_id && $role_set ) {
                    $supporter_added = db_commit_transaction();
                }

                if (!$supporter_added) {
                    db_rollback_transaction();
                }
            }  // End of transaction start
                
            if ($supporter_added) {
                // Log the user in
                login_user($user_id);

                // TODO:  Bring this to the regular HPS method

                if($_POST['PaymentSource'] == 'CREDIT'){

                    // TODO:  Check to make sure DB insertions were all correct
                    session_start();

                    $_SESSION['PaymentDetails'] = array( 'type' => 'Donation',
                                                         'total_amount' => $amount,
                                                         'add_balance' => TRUE,
                                                         'add_balance_amount' => $amount /*,
                                                         'defray' => FALSE,
                                                         'defray_amount' => $defray_amount*/ );
                    session_write_close();

                    header('Location: process_payment.php');


                    /*$donation_info = array(
                            'PaymentDetails' => array( 'type' =>'Donation',
                                                       'amount' => $amount ),
                            'DonorAddressID' => $address,
                            'DonorNameID' => $name,
                            'DonationRecordID' => $donation_id
                            );

                    $_SESSION['DonationRecord'] = urlencode(serialize($donation_info));
                    $_SESSION['DonationRecordUUID'] = sha1('D0NAT10nSALT' . $_SESSION['DonationRecord']);
                    session_write_close();

                    header('Location: process_donation.php?type=credit');*/
                } else {
                    header('Location: process_donation.php?type=mail');
                }
            }
		}

        $address = array('Address1' => $_POST['Address1'],
                         'Address2' => $_POST['Address2'],
                         'City' => $_POST['City'],
                         'State' => $_POST['State'],
                         'ZIP5' => $_POST['Zip5'],
                         'ZIP4' => $_POST['Zip4']);
	}


	
    include_once 'include/header.php';
?>
<div style="float:right; width:450px; clear:both; border:solid 1px #000; margin:0px 30px 0px 15px; padding:2px; position:relative;">
	<form method="post">
		<span style="font-size:1.3em;">*Donating to: </span>
        <select name="Franchise">
        	<?php
				$franchises = get_franchise_name_id_list();
				foreach($franchises as $id => $franchise){
					echo "<option value=\"$id\">$franchise</option>";
				}
			?>
        </select>
        <select name="DonationType">
        	<option value="GENGERAL">General Fund</option>
            <option value="RIDERSHIPFUND">Ridership Fund</option>
        </select>
		<br>
		<?php
			if($missing){
				echo '<center>You were missing required fields</center>';
			}
            if (!$pass_match) {
                echo '<center>Your selected passwords did not match.</center>';
            }
		?>
		<table width="100%">
			<tr>
				<td style="font-size:1.1em;">Name</td>
				<td class="alignright">Title</td>
				<td><input type="text" name="Title" size="5" maxlength="10" value="<?php echo $_POST['Title']?>" /></td>
			</tr>
			<tr>
				<td class="alignright" colspan="2">*First Name</td>
				<td><input type="text" name="FirstName" size="25" maxlength="30" value="<?php echo $_POST['FirstName']
                                        ?>" /></td>
			</tr>
			<tr>
				<td class="alignright" colspan="2">Middle inital</td>
				<td><input type="text" name="MiddleInital" size="5" maxlength="1" value="<?php echo $_POST['MiddleInitial']
                            ?>" /></td>
			</tr>
			<tr>
				<td class="alignright" colspan="2">*Last Name</td>
				<td><input type="text" name="LastName" size="25" maxlength="30" value="<?php echo $_POST['LastName'] ?>" /></td>
			</tr>
			<tr>
				<td class="alignright" colspan="2">Suffix</td>
				<td><input type="text" name="Suffix" size="5" maxlength="10" value="<?php echo $_POST['Suffix'] ?>" /></td>
			</tr>
			<tr>
				<td style="font-size:1.1em;">Mailing Address</td>
				<td colspan="2">
					<?php create_html_address_table('', $address); ?>
				</td>
			</tr>
			<tr>
				<td class="alignright" colspan="2">*Email Address<br />
                    <span style="font-size: smaller">(Your email address will be used for donation receipts.  
                                                      It is also your username)</td>
				<td valign="top"><input type="text" name="EmailAddress" size="25" maxlength="60" value="<?php
                        echo $_POST['EmailAddress'] ?>" /></td>
			</tr>
            <tr>
				<td class="alignright" colspan="2">*Select a Password</td>
				<td valign="top"><input type="password" name="Password" size="25" maxlength="60"></td>
			</tr>
            <tr>
				<td class="alignright" colspan="2">*Confirm Password</td>
				<td valign="top"><input type="password" name="ConfirmPassword" size="25" maxlength="60"></td>
			</tr>
			<tr>
				<td colspan="2" class="alignright">I Would Like To Pay By:</td>
				<td>
					<input type="radio" name="PaymentSource" value="CREDIT" checked> Charge To A Credit Card<br>
					<input type="radio" name="PaymentSource" value="MAILED"> Mail In Check Or Money Order
				</td>
			</tr>
			<tr>
				<td colspan="2" class="alignright">Frequency Of Giving:</td>
				<td>
					<table>
						<tr>
							<td><input type="radio" name="PaymentFrequency" value="WEEKLY"> Weekly</td>
							<td><input type="radio" name="PaymentFrequency" value="MONTHLY"> Monthly</td>
						</tr>
						<tr>
							<td><input type="radio" name="PaymentFrequency" value="ANNUALLY"> Annually</td>
							<td><input type="radio" name="PaymentFrequency" value="ONETIME" CHECKED> One Time</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="alignright">My Contribution:</td>
				<td>
					<table>
						<tr>
							<td><input type="radio" name="PaymentAmount" value="1000"> $1,000</td>
							<td><input type="radio" name="PaymentAmount" value="500"> $500</td>
						</tr>
						<tr>
							<td><input type="radio" name="PaymentAmount" value="250"> $250</td>
							<td><input type="radio" name="PaymentAmount" value="100"> $100</td>
						</tr>
						<tr>
							<td><input type="radio" name="PaymentAmount" value="50"> $50</td>
							<td><input type="radio" name="PaymentAmount" value="Other"> Other $ <input type="text" size="1" name="CustomAmount"> .00</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td colspan="3" class="alignright">
					<input type="hidden" name="Donate" value="YesPlease">
					<input type="submit" value="Donate">
				</td>
			</tr>
		</table>
	</form>
</div>
<h2>Charitable Contributions</h2>
Your generous gifts make it possible for us to provide this service. Since we are  
registered as a 501.c.3 non-profit organization with the IRS, all contributions 
which are general in nature can be itemized as a charitable gift.<br>
<ol>
	<li>
		Please select the purpose of your gift in the top banner.<br>
		<ol type="A">
			<li>
				Contributions to Riders Club of America allow us to perform our
				day to day.
			</li>
			<li>
				Contributions to the Ridership Fund help riders who cannot afford 
				the program.
			</li>
		</ol>
	</li>
	<li>
		Tell us your name and mailing address, so we can send a confirmation letter 
		for your taxes.
	</li>
	<li>
		Your email address lets us send you our newsletter so you can see the effects 
		of your contribution.
	</li>
	<li>
		Select how you would like to pay so we can collect the correct information.
	</li>
	<li>
		Let us know if this is a one-time gift or if you would like to be a regular 
		contributor.
	</li>
</ol>
<p>If you already have donated and set up a password, please <a href="index.php">log in</a>
   to manage your donations.</p>

<?php
	include_once 'include/footer.php';
?>
