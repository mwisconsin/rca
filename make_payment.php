<?php
    require_once('include/rider.php');
    require_once('include/user.php');
    require_once('include/ledger.php');
    require_once('include/link.php');
	require_once('include/supporters.php');
	require_once 'include/franchise.php';

	redirect_if_not_logged_in();

  $user_id = get_affected_user_id();
  $rider_id = 0;
	$franchise = get_current_user_franchise();
	$days_to_payment = 0;

    if (user_has_role($user_id, $franchise, 'Rider')) {
        $rider_id = get_user_rider_id($user_id);
        $rider_info = get_user_rider_info($user_id);
    }
    if (user_has_role($user_id, $franchise, 'Supporter') || user_has_role($user_id, $franchise, 'CareFacilityAdmin')) {
    	$sql = "select * from users where UserId = $user_id";
    	$rider_info = mysql_fetch_assoc(mysql_query($sql));
    }
    if (user_has_role($user_id, $franchise, 'Rider') || user_has_role($user_id, $franchise, 'Supporter') || user_has_role($user_id, $franchise, 'CareFacilityAdmin')) {
        define(ANNUAL_FEE_AMOUNT, getAnnualFeeAmount($franchise));

        $can_pay_annual_fee = FALSE;
        $annual_fee_payment_date = get_rider_annual_fee_payment_date($rider_id);
        if ($annual_fee_payment_date) {
        		$fee = strtotime($annual_fee_payment_date['Year'].'-'.$annual_fee_payment_date['Month'].'-'.$annual_fee_payment_date['Day']);
        		$next = strtotime('+1 year',$fee);
        		$days_to_payment = ($next - time()) / 86400;


            //if ( $days_to_payment <= 45 ) {
                $can_pay_annual_fee = TRUE;
            //}
        } else {
            $can_pay_annual_fee = TRUE;
        }
      
        if ($_POST['PayAnnualFeeFromBalance']) {
            $balance = calculate_user_ledger_balance( $user_id );
            
            if ($balance >= ANNUAL_FEE_AMOUNT) {
                $fee_ledger_id = debit_user( $user_id, ANNUAL_FEE_AMOUNT,
                                             "Applied annual rider fee of " . ANNUAL_FEE_AMOUNT ); 
                if ($fee_ledger_id) {
                    if (set_rider_annual_fee_payment_date( $rider_id )) {
                        rc_log(PEAR_LOG_INFO, "Applied rider annual fee payment of " .
                                              ANNUAL_FEE_AMOUNT . " for user $user_id; rider $rider_id");
                        $can_pay_annual_fee = FALSE;
                        $annual_fee_payment_date = get_rider_annual_fee_payment_date($rider_id);
                    } else {
                        rc_log(PEAR_LOG_ERR, "COULD NOT SET ANNUAL FEE FOR USER $user_id; rider $rider_id " .
                                             "AFTER DEBITING FROM ACCOUNT ($fee_ledger_id)");
                    }
                } else {
                    $error_string = "An error occurred trying to debit your account for your annual fee.";
                }
            }
        }
    }


    if ($_POST['MakePayment']) {
        // Verify user input
        $annual_fee_checked = (strtoupper($_POST['AnnualFeeCheck']) == 'YES');
        $charge_now_checked = (strtoupper($_POST['ChargeNowCheck']) == 'YES');
        $defray_checked = (strtoupper($_POST['DefrayProcessingCheck']) == 'YES');
        $add_amount = 0;

        if ($annual_fee_checked && !user_has_role($user_id,$franchise, 'Rider')) {
            rc_log(PEAR_LOG_WARN, "Non-Rider $user_id attempting to pay annual fee.");
            $error_string = "You indicated you wish to pay the annual fee, but our records show you are not a rider.";
        } else {
            if ($annual_fee_checked && !$can_pay_annual_fee) {
                rc_log(PEAR_LOG_WARN, "Rider $rider_id attempting to pay annual fee when not due.");
                $error_string = "You indicated you wish to pay the annual fee, but our records show the annual fee is not due at this time.";
            }

            if ($annual_fee_checked) {
                $annual_fee_payment = ANNUAL_FEE_AMOUNT;
                $add_amount += $annual_fee_payment;
            }
        }

        if ($charge_now_checked) {
            $charge_now_amount = intval($_POST['ChargeNowAmount'] * 100);
            $add_amount += $charge_now_amount;

            
        } 
		$minimum_charge = (if_user_has_role($user_id,$franchise, 'Rider')) ? 0 : 500;

            if ($add_amount < $minimum_charge) {
                // TODO:  Error of some sort
                $min_dollars = $minimum_charge_now / 100;
                $error_string = "Amount to Charge Now must be at least \${$min_dollars}.00";
            } 

				// if( $defray_amount == "" || $defray_amount == 0) $error_string = "Must supply a defray amount.";
        if ($defray_checked) {
            //$defray_amount = (0.04 * $add_amount);
            // if ($defray_amount > 400) { $defray_amount = 400; }
            $defray_amount = intval($_POST['DefrayAmount'] * 100);
            $add_amount += $defray_amount;
        }

        if (! ($annual_fee_checked || $charge_now_checked) ) {
            $error_string = "Please choose at least one item to pay.";
        }

        if (!$error_string) {
            $_SESSION['PaymentDetails'] = array( 'type' => 'Payment',
                                                 'total_amount' => $add_amount,
                                                 'annual_fee' => $annual_fee_checked,
                                                 'annual_fee_amount' => $annual_fee_payment,
                                                 'add_balance' => $charge_now_checked,
                                                 'add_balance_amount' => $charge_now_amount,
                                                 'defray' => $defray_checked,
                                                 'defray_amount' => $defray_amount );
            session_write_close();

            header('Location: process_payment.php');
        }
    }

    if (user_has_role($user_id,$franchise, 'Rider') ||
    	user_has_role($user_id,$franchise, 'Supporter') ||
    	user_has_role($user_id,$franchise, 'CareFacilityAdmin')
    		) {
        if ($_POST['ChangeAmount']) {
            $recharge_amount = intval($_POST['RechargeAmount']) * 100;
            $threshold_amount = intval($_POST['RechargeThreshold']) * 100;
            $add_four_percent = isset($_POST['DefrayProcessingCheck']) ?  TRUE : FALSE;
            if ($recharge_amount < 4000) {
                $error_string = "Recharge amount must be at least $40.00";
            } else if ($threshold_amount < getFranchiseMinThreshold($franchise)) {
                $error_string = "Recharge threshold must be at least $".number_format(getFranchiseMinThreshold($franchise)/100, 2);
            } else {
                set_rider_recharge_thresholds( $user_id, $threshold_amount, $recharge_amount, $add_four_percent );
            }
            $contact = !isset($_POST['AutoRechargePreference']) ? TRUE : FALSE;
            set_riders_recharge_contact_preference($user_id,$contact);
            
            if(isset($_POST['RechargeMethod']) && (current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise,"Franchisee"))){
				$ACHAccount = $_POST['RechargeMethod'] == 'ACHWithdrawal' ? $_POST['ACHAccount'] : NULL;
                set_rider_recharge_method($user_id, $_POST['RechargeMethod'], $ACHAccount);
            }
        }
    }


    $balance = calculate_user_ledger_balance( get_affected_user_id() );

    if (user_has_role($user_id, $franchise, 'Rider')) {
        $rider_id = get_user_rider_id($user_id);
        $rider_info = get_user_rider_info($user_id);
    }
    if (user_has_role($user_id, $franchise, 'Supporter') || user_has_role($user_id, $franchise, 'CareFacilityAdmin')) {
    	$sql = "select * from users where UserId = $user_id";
    	$rider_info = mysql_fetch_assoc(mysql_query($sql));
    }
	
    include_once 'include/header.php';
    echo "<script> jQuery(function($) { jQuery('#ChargeNowAmount').focus(); }); </script>";

    if (user_has_role($user_id,$franchise, 'Rider')) {
        echo "<h1>Make a Payment</h1>";
    } else {
        echo "<h1>Add Balance</h1>";
    }

	if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee')){
		echo "<a href=\"manual_ledger_entry.php\">Make Manual Ledger Entry</a>";
		echo "&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "<a href=\"/ACHtoProcess.php\">ACH To Process</a>";
	}

    if (isset($error_string)) {
        echo "<p style=\"border: 2px solid red\">$error_string</p>";
} 


if (if_user_has_role($user_id, $franchise, 'Rider')) { ?>
<h2>Annual Fee</h2>
<p>The annual fee is $<?php echo number_format(getAnnualFeeAmount($franchise)/100,2); ?>.  <?php
    if ($annual_fee_payment_date) {
        echo "Our records show your last annual fee payment was effective ";
        echo "{$annual_fee_payment_date['Month']}/{$annual_fee_payment_date['Day']}/" .
               $annual_fee_payment_date['Year']; 
        echo '.';
    } else {
        echo "Our records show your annual fee has not been paid.";
    }

    if ($can_pay_annual_fee) {  
        // TODO:  Check scheduled rides?
        echo '</p><p><form method="POST" action="">';
        echo 'You may pay your annual fee from your account balance. '
        	.($days_to_payment > 45 ? '<b style="color: red;">Note: Your annual fee is not due for '.number_format($days_to_payment,0).' days!</b>' : '');
        echo '<br /><input type="submit" name="PayAnnualFeeFromBalance" value="Pay Annual Fee From Balance" />';
        echo '</form>';
    }
        ?></p>
<?php
    // TODO:  IF annual fee is reasonably up-to-date (whatever that means) no payment box
    
    $incomplete_ride_balance = calculate_riders_incomplete_ride_costs( get_user_rider_id( $user_id ) );
?>
<h2>Rider Balance</h2>
<p>Your current balance is $<?php 
    echo number_format(($balance)/100, 2);
    ?></p>
<p>Your available balance is $<?php 
    echo number_format(($balance - $incomplete_ride_balance)/100, 2);
    ?></p>
<?php

$sql = "select amount, dts, paytype from ach_to_process where userid = $user_id and status = 1";
$r = mysql_query($sql);
if(mysql_num_rows($r) > 0) {
	echo "You have pending payments in the system:<BR>";
}
while($rs = mysql_fetch_array($r)) {
	echo "<dd>$".number_format($rs["amount"],2)." pending for ".($rs["paytype"] == "ANNUAL_FEE" ? "Annual Fee" : "Rider Account").", to process on or after ".date("m/d/Y",strtotime($rs["dts"]))."</dd><BR>";
}
} // END OF RIDER-SPECIFIC BLOCK 

if (if_user_has_role($user_id, $franchise, 'Supporter')) { 
echo "<BR><BR><BR>";
$sql = "select amount, dts, paytype from ach_to_process where userid = $user_id and status = 1";
$r = mysql_query($sql);
if(mysql_num_rows($r) > 0) {
	echo "You have pending payments in the system:<BR>";
}
while($rs = mysql_fetch_array($r)) {
	echo "<dd>$".number_format($rs["amount"],2)." pending for ".($rs["paytype"] == "ANNUAL_FEE" ? "Annual Fee" : "Rider Account").", to process on or after ".date("m/d/Y",strtotime($rs["dts"]))."</dd><BR>";
}
}
?>
<?php if($rider_info['RechargePaymentType'] == 'ContactSupporter' ){ ?>
<div style="background:#000; position:absolute; height:130px; width:550px; opacity:.8; text-align:center; font:bold 1.2em; color:#FFF; padding-top:64px;">
	Please Contact This Users Supporting Friend(s)
    
</div>
<div style="background:#fff; position:absolute;  text-align:center; width:510px; font:bold 1.2em; margin: 90px 20px 0 20px; color:#000;">
    <?php
		if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee'))
			echo display_supporting_friends( get_rider_supporting_friends( get_affected_user_id() ) );
	?>
</div>
<?php } ?>
<br><br>
<div style="border:1px solid #666666;box-shadow:4px 4px 4px rgba(0, 0, 0, .6);padding:10px;display:block;float:left;">
<h2 style='margin-bottom: 0px;'>RMS Payment Request</h2>
<script>
jQuery(function($) {

});	
</script>
<form method="POST">
	<div style='padding-left: 15px; margin-bottom: 10px;'>
		<input id=payment_method_credit_card type=radio name=payment_method value=credit_card <?php if($rider_info['RechargePaymentType'] == 'CreditCard') echo ' CHECKED'; ?>>Credit Card<br>
		<input id=payment_method_ach type=radio name=payment_method value=ach <?php if($rider_info['RechargePaymentType'] == 'ACHWithdrawal' || $rider_info['RechargePaymentType'] == 'SendChecks') echo ' CHECKED'; ?>>Draw from Bank Account On File <input size=10 type=text class="jq_datepicker" name="ach_date">
	</div>
<table border="1">
<?php 
if (user_has_role($user_id, $franchise, 'RIDER')) { ?>

<tr>
    <td><?php /* If annual fee is due in more than 30 days, don't let the user pay. */
?>
    <input type="checkbox" name="AnnualFeeCheck" id="AnnualFeeCheck" value="YES" <?php
            if (!$can_pay_annual_fee) { echo 'disabled="disabled" '; } ?> onChange="update_subtotal();"/></td>
    <td>Annual Fee<?php if($days_to_payment > 0) echo " (<b>NOTE:</b> Days to Next Payment ".number_format($days_to_payment,0).")"; ?></td>
    <td>$<input type="text" size="5" disabled="disabled" placeholder="<?php
        echo ($can_pay_annual_fee) ? number_format(ANNUAL_FEE_AMOUNT/100,2) : '0.00'; ?>" id="AnnualFeeAmount" name="AnnualFeeAmount" /></td>
</tr>

<?php // TODO:  Payment preferences:  Threshold and amount to charge at threshold not met ?>
<tr>
    <td>
    <input type="checkbox" name="ChargeNowCheck" id="ChargeNowCheck" value="YES" <?php if(isset($_GET["amt"]) && $_GET["amt"] > 0) echo "checked"; ?>/></td>
    <td>Add to my Rider Account Now (sug. minimum = $60.00)</td>
    <td>$<input type="text" name="ChargeNowAmount" id="ChargeNowAmount" size="5" placeholder="0.00" value="<?php if(isset($_GET["amt"]) && $_GET["amt"] > 0) echo $_GET["amt"]; ?>"/></td>
</tr>
<tr><td>&nbsp;</td><td>Subtotal: </td>
    <td>$<input type="text" name="Subtotal" id="Subtotal" size="5" value="" disabled="disabled"/></td>
</tr>
<tr><td><input type="hidden" name="DefrayProcessingCheck" id="DefrayProcessingCheck" value="YES" /></td>
    <td>Payment of 4% to help defray processing costs</td>
    <td>$<input type="text" name="DefrayAmount" id="DefrayAmount" size="5" value="" placeholder="0.00" 
    	<?php if(!current_user_has_role($franchise, 'FullAdmin')) echo "disabled=\"disabled\""; ?>/></td>
</tr>
<tr><td>&nbsp;</td><td>Total to Charge: </td>
    <td>$<input type="text" name="Total" id="Total" size="5" value="" disabled="disabled"/></td>
</tr>
<?php 
} else { // END OF RIDER-SPECIFIC BLOCK  ?>
<tr>
    <td>
    <input type="checkbox" name="ChargeNowCheck" id="ChargeNowCheck" value="YES" checked="checked" /></td>
    <td>Amount to Charge Now (minimum $5.00)</td>
    <td>$<input type="text" name="ChargeNowAmount" id="ChargeNowAmount" size="5" value="" /></td>
</tr>
<tr><td><input type="hidden" name="DefrayProcessingCheck" id="DefrayProcessingCheck" value="YES" /></td>
    <td>Payment of 4% to help defray processing costs</td>
    <td>$<input type="text" name="DefrayAmount" id="DefrayAmount" size="5" value="" placeholder="0.00" 
    	<?php if(!current_user_has_role($franchise, 'FullAdmin')) echo "disabled=\"disabled\""; ?> /></td>
</tr>
<tr><td>&nbsp;</td><td>Total to Charge: </td>
    <td>$<input type="text" name="Total" id="Total" size="5" value="" disabled="disabled"/></td>
</tr>
<?php 
}
    ?>

</table>
<?php // TODO:  Nice JS ?>

<br />
<input type="submit" name="MakePayment" id="MakePayment" value="Make Payment" />
</form>
</div>
<?php 
//if (user_has_role($user_id, $franchise, 'RIDER')) { 
?>
<div style="border:1px solid #666666;box-shadow:4px 4px 4px rgba(0, 0, 0, .6);padding:10px;display:block;float:right;margin-left:20px;width:410px">
<h2>Recharge Information</h2>
<form name="test" method="post">
<table border="1">
<tr><td>Recharge Threshold Amount</td>
	<td>$<input type="text" name="RechargeThreshold" size="5" value='<?php printf("%d.%02.2d", $rider_info["RechargeThreshold"]/100, $rider_info["RechargeThreshold"] % 100); ?>'></td>
</tr>
<tr><td>Recharge Amount</td>
	<td>$<input type="text" name="RechargeAmount" size="5" value='<?php printf("%d.%02.2d", $rider_info["RechargeAmount"]/100, $rider_info["RechargeAmount"] % 100); ?>'></td>
</tr>
<tr><td colspan="2"><input type="hidden" <?php if($rider_info['PayExtraCardFourPercent'] == 'Yes') echo 'CHECKED'; ?> name="DefrayProcessingCheck" />A 4% processing cost will be added</td>
</tr>
</table>
<br>
<input name="AutoRechargePreference" type="checkbox" <?php if($rider_info['ContactBeforeRecharge'] == 'RechargeAutomatically') echo 'checked="checked"'; ?> /> Refill my account automatically when Recharge Threshold is reached.<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Receive the Recharge Amount via the method selected below.<br>
<?php if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee')){?>
<input type="radio" name="RechargeMethod" value="CreditCard"<?php if($rider_info['RechargePaymentType'] == 'CreditCard') echo ' CHECKED'; ?>> Charge my secured credit card.<br>
<input type="radio" id="ACHAccount_radio" name="RechargeMethod" value="ACHWithdrawal"<?php if($rider_info['RechargePaymentType'] == 'ACHWithdrawal') echo ' CHECKED'; ?>> Withdraw from my assigned bank account.<br>
<div id="ACHAccount_cell" style="margin-left:30px;">
	<select name="ACHAccount" id="ACHAccount">
    	<option value="Checking"<?php if($rider_info['AccountForACH'] == 'Checking') echo ' SELECTED'; ?>>Checking</option>
        <option value="Savings"<?php if($rider_info['AccountForACH'] == 'Savings') echo ' SELECTED'; ?>>Savings</option>
    </select>
</div>
<input type="radio" name="RechargeMethod" value="SendChecks"<?php if($rider_info['RechargePaymentType'] == 'SendChecks') echo ' CHECKED'; ?>> I will send in checks when needed.<br>
<input type="radio" name="RechargeMethod" value="ContactSupporter"<?php if($rider_info['RechargePaymentType'] == 'ContactSupporter') echo ' CHECKED'; ?>> Contact my supporting friend.<br>
<?php } ?>
<br>
<input type="submit" name="ChangeAmount" value="Save Information">
</form>
</div>
<div style='clear: both'></div>
<?php
//}  // END OF RIDER-SPECIFIC BLOCK
?>


<script type="text/javascript">

is_admin = false;
<?php
if(current_user_has_role($franchise, 'FullAdmin')) echo "is_admin = true;\n\n";
error_reporting(E_ALL);

$mydate = date('m/d/Y',strtotime('+1 day'));
if(date('G',time()) > 14) {
	$sql = "select d from holidays";
	$r = mysql_query($sql);
	$holidays = array();
	while($rs = mysql_fetch_assoc($r)) $holidays[] = $rs["d"];
	
	$tomorrow = date('Y-m-d',strtotime('+1 day',strtotime($mydate)));
	while(in_array($tomorrow,$holidays)
				|| date('w',strtotime($tomorrow)) == 6
				|| date('w',strtotime($tomorrow)) == 0) 
			$tomorrow = date('Y-m-d',strtotime('+1 day',strtotime($tomorrow)));
	$mydate = date('m/d/Y',strtotime($tomorrow));
}
?>
		jQuery('input[name="ach_date"]').val( '<?php echo $mydate; ?>' );
		 
		jQuery('input[name="payment_method"]').on('change',function() {
			set_optional_payment_amount();	
			update_total();	
		});
		

    function set_optional_payment_amount() {
        var subtotal = update_subtotal();
        var defray = "0.00";

				if(jQuery('#payment_method_credit_card:checked').length > 0) {
	        defray = subtotal * 0.04;
	        $('DefrayAmount').value = (defray * 1.00).toFixed(2);
	      }
	      
        return $('DefrayAmount').value;
    }

    function update_subtotal() { 
        var annual_fee = 0;
        var charge_now = 0;

        if ($('AnnualFeeCheck')) {
            annual_fee = ($('AnnualFeeCheck').checked) ? 
                                    $('AnnualFeeAmount').placeholder : 0;
        }
        if ($('ChargeNowCheck')) {
            charge_now = ($('ChargeNowCheck').checked) ?
                                $('ChargeNowAmount').value : 0;
            if(charge_now < 60 && !is_admin) charge_now = 60;
        }



	        var subtotal = (annual_fee * 1.00) + (charge_now * 1.00);
	        if ($('Subtotal')) {
	            $('Subtotal').value = subtotal.toFixed(2);
	        }

        return subtotal;
    }
		jQuery('#DefrayAmount').on('change',update_total);
		
		
    function update_total(evt) {
    	if($('ChargeNowAmount').value > 0) $('ChargeNowCheck').checked = true;
    	else $('ChargeNowCheck').checked = false;
    		
        var subtotal = update_subtotal();
        var defray = set_optional_payment_amount();
        
        $('Total').value = ((subtotal * 1.00) + (defray * 1.00)).toFixed(2);
        
        return true;
    }

    function validate_form(evt) {
        if ($('AnnualFeeCheck')) {
            if ( !$('AnnualFeeCheck').checked && !$('ChargeNowCheck').checked && $('DefrayAmount').value == '' ) {
                alert("Please choose what you wish to pay.");
                evt.stop();
                return;
            }   
     		}
     		if(!$('payment_method_credit_card').checked && !$('payment_method_ach').checked) {
					alert('Please choose a method of payment.');
					evt.stop();
					return;
				}
				if ( parseFloat($('Total').value,10) <= 0) {
                alert("Amount to Charge Now must be greater than $0.00");
                evt.stop();
                return;
        }
        
        if($('payment_method_ach').checked) {
        	evt.stop();
        	
        	dia = jQuery('<div>Do you want to Draw '
        		+parseFloat(jQuery('#AnnualFeeCheck:checked').length > 0 ? +jQuery('#Total').val() : jQuery('#Total').val(),10).toFixed(2)
        		+' from your bank account on '
        		+jQuery('input[name="ach_date"]').val()
        		+'?</div>'
        		).dialog({
        			title: "Draw from your assigned Bank Account?",
        			width: 590,
        			buttons: [
        				{
        					text: 'Yes',
        					icons: {
        						primary: 'ui-icon-transfer-e-w'
        					},
        					click: function() {
        						jQuery.post('/make_ach_payment_insert.php',
        							{ userid: <?php echo $user_id; ?>, 
        								amount: +jQuery('#Total').val(), 
        								dts: jQuery('input[name="ach_date"]').val(),
        								annualfeeamount: jQuery('#AnnualFeeCheck:checked').length > 0 ? jQuery('#AnnualFeeAmount').attr('placeholder') : 0  },
        							function(data) {
        								if(data == "1") {
        									dia.dialog( "close" );
        									dianote = jQuery('<div>Your payment will be processed on '+jQuery('input[name="ach_date"]').val()+', or the next available banking day.</div>')
        										.dialog({
        											title: 'Confirmed',
        											buttons: [
        												{
        													text: 'Ok',
        													click: function() {
        														dianote.dialog('close');
        														window.location.href = '/make_payment.php';
        													}
        												}
        											]
        										});
        								}
        								dia.dialog( "close" );
        							});
        					}
        				},
        				{
        					text: 'No',
        					icons: {
        						primary: 'ui-icon-trash'
        					},
        					click: function() {
        						dia.dialog( "close" );
        					}
        				}
        			]
        			
        		});
        	
        }
    }

    function decorate_form() {
        var events_to_add = { 'click': update_total, 'change': update_total, 'keydown': update_total };
        var items_to_add_to = ['DefrayProcessingCheck', 'ChargeNowCheck',
                               'ChargeNowAmount', 'AnnualFeeCheck'];
        for (var i = 0; i < items_to_add_to.length; i++) {
            if ($(items_to_add_to[i])) {
                $(items_to_add_to[i]).addEvents( events_to_add )
            }
        }
        /*$('DefrayProcessingCheck').addEvents( events_to_add ); 
        $('ChargeNowCheck').addEvents( events_to_add ); 
        $('ChargeNowAmount').addEvents( events_to_add ); 
        $('AnnualFeeCheck').addEvents( events_to_add ); */
        $('MakePayment').addEvents( {
                'click': validate_form
        } );

    }
    window.addEvent('domready', decorate_form);
	var adjust_ACHAccount_selector = function(){
		if($('ACHAccount_radio').checked)
			$('ACHAccount_cell').setStyle('display','block');
		else
		   	$('ACHAccount_cell').setStyle('display','none');
	}
	window.addEvent('domready', adjust_ACHAccount_selector);
	var elements = document.getElementsByName("RechargeMethod");
	for( i = 0; i < elements.length; i++){
		elements[i].addEvent('click', adjust_ACHAccount_selector.create() );
	}
	
	jQuery(function() {
		if(parseFloat(jQuery('input[name="RechargeAmount"]').val(),10) > 0) jQuery('#ChargeNowAmount').attr('placeholder',jQuery('input[name="RechargeAmount"]').val() );
		//jQuery('#AnnualFeeAmount').val( '<?php echo number_format(getAnnualFeeAmount($franchise)/100,2); ?>' );
		if(parseFloat(jQuery('#ChargeNowAmount').val(),10) > 0) update_total();
	});
</script>
<?php
    include_once 'include/footer.php';
?>
