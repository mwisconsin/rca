<?php
	include_once 'include/user.php';
	include_once 'include/rider.php';
	include_once 'include/driver.php';
	include_once 'include/franchise.php';
	include_once 'include/address.php';
	redirect_if_not_logged_in();

    if ( !if_current_user_has_role('FullAdmin')) {
        header("location: " . site_url() . "account.php");
    }
	

// TODO:   THIS IS A TEMPORARY HACK!
// AND BY "THIS" I MEAN THIS WHOLE FILE!

    $name = get_user_person_name($user_id);
	$account = get_user_account($user_id);

    if ($_REQUEST['field'] == 'verifiedaddress' && isset($_REQUEST['addressid'])) {
        set_address_verification_status($_REQUEST['addressid'], TRUE, $verify_type = 'ADMIN');
        header("location: " . site_url() . "account.php?id={$_REQUEST['id']}");
echo "Should have verified address";
    }

	
	include_once 'include/header.php';
?>
<h2>Address Verification</h2>
<hr />
<div class="account_subject">
							<?php $address = get_address($_REQUEST['addressid']);
							?>
								<table>
									<tr>
										<td colspan="3" style="font-weight:bold; text-align:center;"><?php echo $address['AddressType']; ?></td>
									</tr>
									<tr>
										<td colspan="3">
											<?php echo $address['Address1'];?>
										</td>
                                        <td rowspan="3">
                                            <a href="http://zip4.usps.com/zip4/welcome.jsp">Validate this address</a> (todo:  automatic)
<br />
											<a href="<?php echo 'verify_address.php?field=verifiedaddress&addressid=' . $address['AddressID'] . "&id={$_REQUEST['id']}"; ?>">Click Here When Verified</a>
                                        </td>
									</tr>
									<?php
										if($address['Address2'] != '')
											echo'<tr colspan="3"><td>' . $address['Address2'] . '</td><tr>';
									?>
									<tr>
										<td>
											<?php echo $address['City'] . ","; ?>
										</td>
										<td>
											<?php echo $address['State']; ?>
										</td>
										<td>
											<?php echo $address['ZIP5'];?>
										</td>
									</tr>
								</table>

<?php
    include_once 'include/footer.php';
?>
