<?php
    chdir('..');
    require_once('include/database.php');
    require_once('include/user.php');
    require_once('include/hps_transactions.php');
    require_once('include/rc_log.php');
	require_once('include/franchise.php');
    //TODO
	  //Initialize variable strTransNum below by assiging it the transaction number that is to be processed. It must not to be empty.
	  //$strTransNum = "IN123456";
    session_start();
    $strTransNum = $_SESSION['TransactionNumber'];
    //END
    $user_id = get_affected_user_id();
    $user_person_name = get_user_person_name($user_id);

    $transaction_cents = $_SESSION['PaymentDetails']['total_amount'];
		$franchise_id = get_current_user_franchise();
	    
        // Get the user's billing address, if there is one.
        $addresses = get_user_address_array( $user_id );
        if (count($addresses) > 0) {
            foreach ($addresses as $addr) {
                if ($addr['AddressType'] == 'Billing') {
                    $arr_address = $addr;
                    break;
                } elseif ($addr['AddressType'] == 'Physical') {
                    $arr_address = $addr;
                    break;
                } else if (!isset($address)) {
                    $arr_address = $addr;
                }
            }
        }
?>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>Purchase Page</title>
<style type="text/css">
Body {	background-color:RGB(255,255,255);
font-family: Verdana, Arial, Sans-Serif;	
}
.BT_BtnOut {	Width:70px; font-size: 9pt; font-weight:bold;COLOR: RGB(0,0,0);font-family : Arial;}
.BT_BtnOvr {	Width:70px; font-size: 9pt; font-weight:bold;COLOR: RGB(0,0,0);font-family : Arial;}
.BT_Field {	FONT-SIZE: 8pt; font-family : Arial;COLOR: RGB(0,0,0);}
.BT_FieldDescription {	FONT-WEIGHT: bold; FONT-SIZE: 8pt; font-family : Arial; COLOR : RGB(0,0,0);}

#manageNicknamesForm {
	display: none;
	text-align: center;
}
</style>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.2/jquery.min.js"></script>
<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css">
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>
<script>
jQuery(function($) {
	$('#manageNicknames').on('click',function() {
		$d = $('#manageNicknamesForm').dialog({
			title: "Manage Nicknames",
			buttons: [
				{
					text: "Delete",
					icons: {
						primary: "ui-icon-trash"
					},
					click: function() {
						$.post('/hps/deleteNickname.php',$('#manageNicknamesForm form').serialize(),function(data) {
							$('select[name="token_value"] option[value="'+$('#manageNicknamesForm select').val()+'"]').remove();
							$('#manageNicknamesForm select option:selected').remove();
							if($('#manageNicknamesForm select option').length == 1) $('#nicknameBlock').hide();
							$d.dialog('close');
						});
					}
				},
				{
					text: "Cancel",
					icons: {
						primary: "ui-icon-circle-close"
					},
					click: function() {
						$d.dialog('close');
					}
				}
			]
		});
	});
});
</script>                          
</head>
<body>

                            

<form id="payment_form" method="post" action="ProcessPageNew.php">
<?php

$sql = "select * from hps_mutokens where UserID = ".get_affected_user_id();
$r = mysql_query($sql);
if(mysql_num_rows($r) > 0) {
	echo "<div id=nicknameBlock>";
	echo "<B>Note:  There is a/are Credit Card authorization(s) already on file for this user.</B><br><br>";
	echo "<B>Either select from one of the listed Card Nicknames below, <br>or fill out the Credit Card forms below the list:</B><br>";
	echo "<select name=token_value size=1><option value=\"\"></option>";
	while($rs = mysql_fetch_array($r)) {
		echo "<option value=\"{$rs[muToken]}\">{$rs[Nickname]}</option>\n";
	}
	echo "</select><input type=button id=manageNicknames value=\"Manage Nicknames\"><br><br>";
	echo "</div>";
}
?>

<br><b style='color: red;'>Attempting to process a charge of <?php echo format_dollars($transaction_cents); ?></b><br><br>

<table cellpadding=3 cellspacing=0 border=0>
<tr>
<td>Address:</td>
<td><input type="text" id="Address" name="Address1" value="<?php echo $arr_address['Address1']; ?>" /></td>
</tr>	
<tr>
<td>City:</td>
<td><input type="text" id="City" name="City" value="<?php echo $arr_address['City']; ?>" /></td>
</tr>	
<tr>
<td>State:</td>
<td><input type="text" id="State" name="State" value="<?php echo $arr_address['State']; ?>" /></td>
</tr>	
<tr>
<td>Zip:</td>
<td><input type="text" id="Zip" name="Zip5" value="<?php echo $arr_address['ZIP5']; ?>" /></td>
</tr>	
<tr><td colspan=2>&nbsp;</td></tr>
<tr>
<td>Card number:</td>
<td><input type="text" id="card_number" name="card_number" value="" /></td>
</tr>	
<tr>
<td>Exp month:</td>
<td><input type="text" id="exp_month" name="exp_month" value="" placeholder="99"/></td>
</tr>	
<tr>
<td>Exp year:</td>
<td><input type="text" id="exp_year" name="exp_year" value="" placeholder="9999" /></td>
</tr>	
<tr>
<td>Card cvc:</td>
<td><input type="text" id="card_cvc" name="card_cvc" value="" /></td>
</tr>	
<tr>
<td>Nickname for this card (i.e. "Visa1234"):</td>
<td><input type="text" id="Nickname" name="Nickname" value="" /></td>
</tr>	
</table><br><Br>
<input type="submit" value="Submit Payment" />														
														</form>	
														
														
<div id=manageNicknamesForm>
	<b>Select A Nickname to Delete:</b><br>
<form>
	<select id=mNickname name=mNickname size=1>
		<option value=""></option>
<?php 
mysql_data_seek($r,0); 
while($rs = mysql_fetch_array($r)) echo "<option value='$rs[muToken]'>$rs[Nickname]</option>";
?>
</select>
</form>	
</div>
</body>
</html>
