<?php

	require_once 'include/user.php';
	require_once 'include/franchise.php';
	
	 /**
	 * Returns the Driver Status and Franchise ID of the identified user as a hash.  Keys to the hash:
	 * DriverStatus, FranchiseID.
	 * @param user_id ID of user to get info for
	 * @return hash containing name fields or FALSE on error.
	 *///TODO: rewrite function to use driverID
	 
	redirect_if_not_logged_in();
	$franchise = get_current_user_franchise();
	
	$annual_fee_amount = get_user_annual_fee( $franchise );
	
	if(!current_user_has_role(1, 'FullAdmin') && !current_user_has_role($franchise, 'Franchisee'))
	{
		header("Location: home.php");
	}

    require_once 'include/ledger.php';
    require_once 'include/supporters.php';
	require_once 'include/link.php';

//    define(ANNUAL_FEE_AMOUNT, 6000);


    $admin_user_id = get_current_user_id();
    $user_id = get_affected_user_id();

		$effective_date = date('m/d/Y',time());
		
    if ($_POST['LedgerType'] && is_numeric($_POST['LedgerAmount']) && $_POST['Desc']) {

        //$effective_date = "{$_POST['EffectiveYear']}-{$_POST['EffectiveMonth']}-{$_POST['EffectiveDay']}";
        
        $effective_date = date('Y-m-d',strtotime($_POST["effective_date"]));

        $description = "MTXN$admin_user_id - {$_POST['Desc']}";

        if ($_POST['LedgerType'] == 'debit') {
            $entry_made = debit_user( $user_id, $_POST['LedgerAmount'], $description, $effective_date );

        } elseif ($_POST['LedgerType'] == 'credit') {
            $entry_made = credit_user( $user_id, $_POST['LedgerAmount'], $description, $effective_date );
        } elseif ($_POST['LedgerType'] == 'DriverReimbursement' && if_user_has_role($user_id, 'DRIVER')) {
            $reimb_desc = 'Cash Reimbursement for Driver ' . $description;
            $debit_id = debit_user( $user_id, $_POST['LedgerAmount'], $reimb_desc, $effective_date, 'DRIVER');
            $record_stored = store_driver_reimbursement_record($user_id, $debit_id);
            $entry_made = ($debit_id && $record_stored);
        }

        $entry_description = htmlspecialchars("$description:  {$_POST['LedgerType']} in the amount of {$_POST['LedgerAmount']} cents");

    }



    $rider_info = get_user_rider_info( $user_id );
    $rider_user_id = get_user_rider_id( $user_id );  //$rider_info['UserID'];
    $can_pay_annual_fee = FALSE;

    if ($rider_info) {
        $annual_fee_payment_date = get_rider_annual_fee_payment_date($rider_user_id);
        $ride_costs = calculate_riders_incomplete_ride_costs( get_user_rider_id( $user_id ) );

        if ($annual_fee_payment_date) {
            list( $y, $m, $d ) = explode('#', date('Y#m#d') );
            $curr_jd = gregoriantojd( $m, $d, $y );
            $fee_jd = gregoriantojd( $annual_fee_payment_date['Month'],
                                     $annual_fee_payment_date['Day'],
                                     $annual_fee_payment_date['Year'] );
            $next_jd = gregoriantojd( $annual_fee_payment_date['Month'],
                                      $annual_fee_payment_date['Day'],
                                      $annual_fee_payment_date['Year'] + 1 );

            $days_to_payment = $next_jd - $curr_jd;

            if ( $days_to_payment <= 31 ) {
                $can_pay_annual_fee = TRUE;
            }
        } else {
            $can_pay_annual_fee = TRUE;
        }

        if ($_POST['PayAnnualFeeFromBalance']) {
            $balance = calculate_user_ledger_balance( $user_id );
            
            if ($balance >= $annual_fee_amount) {
                //$effective_date = "{$_POST['EffectiveYear']}-{$_POST['EffectiveMonth']}-{$_POST['EffectiveDay']}";
                $effective_date = date('Y-m-d',strtotime($_POST["effective_date"]));
                $fee_ledger_id = debit_user( $user_id, $annual_fee_amount,
                                             "Applied annual rider fee of " . $annual_fee_amount,
                                             $effective_date ); 

                if ($fee_ledger_id) {
                    if (set_rider_annual_fee_payment_date( $rider_user_id, $effective_date )) {
                        rc_log(PEAR_LOG_INFO, "Applied rider annual fee payment of " .
                                              $annual_fee_amount . " for user $user_id; rider $rider_user_id");
                        $can_pay_annual_fee = FALSE;
                        $annual_fee_payment_date = get_rider_annual_fee_payment_date($rider_user_id);
                    } else {
                        rc_log(PEAR_LOG_ERR, "COULD NOT SET ANNUAL FEE FOR USER $user_id; rider $rider_user_id " .
                                             "AFTER DEBITING FROM ACCOUNT ($fee_ledger_id)");
                    }
                } else {
                    $error_string = "An error occurred trying to debit your account for your annual fee.";
                }
            }
        }
    }


    global $ADDITIONAL_RC_JAVASCRIPT;
    if (!is_array($ADDITIONAL_RC_JAVASCRIPT)) {
        $ADDITIONAL_RC_JAVASCRIPT = array();
    }
    $ADDITIONAL_RC_JAVASCRIPT[] = 'datepicker.js';
    include_once 'include/header.php';
?>
<script src="js/jquery.maskedinput.js" type="text/javascript"></script>
<script>
jQuery(function($) {
	$('input[name="LedgerAmount"]').mask("?999999",{"placeholder":""});
});	
</script>
<h1>Manual Ledger Entry</h1>

<?php if ($entry_made) { 
    echo "<p>$entry_description</p>";
} 
$balance = calculate_user_ledger_balance( $user_id, 'GENERAL' );
if($rider_info){ ?>
<p>Available Balance for user is <?php
    echo format_dollars($balance - $ride_costs);
?><br />
<?php } ?>
<p>General Balance for user is <?php
        echo format_dollars($balance);
?><br />
<?php 
    if (if_user_has_role($user_id, $franchise, 'DRIVER')) {
        $driver_balance = calculate_user_ledger_balance( $user_id, 'DRIVER' );

        echo "Driver Balance for user is " . format_dollars($driver_balance);
    }
?></p>
<style>
#manual_ledger_entry_form .req + span.ast {
	color: red;
}
</style>
<script>
function confirmThis(f) {
    var incomplete = jQuery('#manual_ledger_entry_form input[type="text"], #manual_ledger_entry_form input[type="radio"], #manual_ledger_entry_form select').filter(function() {

    										 if(jQuery(this).prop('name') != 'IsCheck') return jQuery(this).val() == '';
                     });
    //if incomplete contains any elements, the form has not been filled 

    if(incomplete.length) {
        alert('Please enter values in all fields.  The Description Field is very important.');
        //to prevent submission of the form
        return false;
    }	
    return true;
};	
</script>
<form id=manual_ledger_entry_form action="" method="POST">
    <input class=req type="radio" name="LedgerType" value="debit" />Debit <span class=ast>*</span><br />
    <input class=req type="radio" name="LedgerType" value="credit" />Credit <span class=ast>*</span><br />
<?php if (if_user_has_role($user_id, $franchise, 'DRIVER')) { ?>
    <input class=req type="radio" name="LedgerType" value="DriverReimbursement" />Driver Reimbursement<br />
<?php } ?>
    Amount (cents): <input class=req type="text" name="LedgerAmount" value="" size="5" /> <span class=ast>*</span><br />
    Description:  <input class=req type="text" name="Desc" value="" size="45" /> <span class=ast>*</span><br />
    Effective Date: <input type=text size=10 name=effective_date id=effective_date class=jq_datepicker value="<?php echo $effective_date; ?>">

    &nbsp;<div style="display: inline-table">
    <input type="radio" name="IsCheck" value="LocalCheck" id="LocalCheck" /> - Local Check - Adds 3 Days<br />
    <input type="radio" name="IsCheck" value="OutOfStateCheck" id="OutOfStateCheck" /> - Out-of-State Check - Adds 5 Days<br />
    <input type="radio" name="IsCheck" value="None" id="NotACheck" style="display: none;" />
    <script type="text/javascript">
    // <![CDATA[  
        function manual_ledger_entry_add_days_to_effective_date(days) {
        		var d = new Date($('effective_date').value);
        		d.setDate(d.getDate()+days);
        		$('effective_date').value = jQuery.datepicker.formatDate('mm/dd/yy',d);
 
//            var d = $('EffectiveDay');
//            var m = $('EffectiveMonth');
//            var y = $('EffectiveYear');
//
//            dateObj = new Date(y.value, m.value, parseInt(d.value, 10) + days);
//            if (d) { d.value = dateObj.getDate(); }
//            if (m) { m.value = dateObj.getMonth(); }
//            if (y) { y.value = dateObj.getYear; }
        }

        $('LocalCheck').addEvent('change',function(evt){
                manual_ledger_entry_add_days_to_effective_date(3);
        });

        $('OutOfStateCheck').addEvent('change',function(evt){
                manual_ledger_entry_add_days_to_effective_date(5);
            });
        
        jQuery(function($) {
        	$('#effective_date').datepicker({
        			onSelect: function(formattedDate, date, inst) {
        				Cookies.set('manual_ledger_entry_effective_date',formattedDate,{ expires: 1 });
        			}
        		});
        	if(Cookies.get('manual_ledger_entry_effective_date') != ''
        		&& typeof Cookies.get('manual_ledger_entry_effective_date') != 'undefined')
        		$('#effective_date').val(Cookies.get('manual_ledger_entry_effective_date'));
        });
    // ]]>
    </script>
    </div>


        
    <br /><br />

    <input name="Submit" type="button" value="Post to RMS" onClick="if(confirmThis(this.form)) this.form.submit();"/>

    <p>Annual Fee <?php
        if (!$rider_user_id) {
            echo 'does not apply to non-riders.';
        } elseif (!$annual_fee_payment_date || $annual_fee_payment_date['Year'] == 0) {
            echo 'has never been paid.';
        } else {
            echo "last paid {$annual_fee_payment_date['Year']}-{$annual_fee_payment_date['Month']}-{$annual_fee_payment_date['Day']}";
        }
    ?></p>

    <input type="submit" id="PayAnnualFeebutton" name="PayAnnualFeeFromBalance" value="Pay Annual Fee From Balance" <?php
        if ($balance < $annual_fee_amount || !$rider_user_id) { echo 'disabled="disabled"'; }
        if ($days_to_payment > 31 && !($balance < $annual_fee_amount || !$rider_user_id)){ 
        	echo " onclick=\"jQuery(this).css('display','none'); jQuery('#PayAnnualFeeConfirm').css('display',''); return false;\"";
        } else if ($days_to_payment <= 31 && !($balance < $annual_fee_amount || !$rider_user_id)){
        	echo "onclick=\"return true;\"";
        }
        else echo "onclick=\"return false;\"";
    ?>/>
    <?php if( $days_to_payment > 31 && !($balance < $annual_fee_amount || !$rider_user_id)){ ?>
    <div id="PayAnnualFeeConfirm" style="display:none;" class="reminder">
    	Are You sure you want to extend their annual fee through <?php 
            $new_end_time_str = $annual_fee_payment_date['Year'] + 2 . 
                                "-{$annual_fee_payment_date['Month']}-{$annual_fee_payment_date['Day']}";
            $new_end_time = strtotime($new_end_time_str);
            echo date( "F j, Y", $new_end_time); ?>?<br>
    	<input type="submit" value="Pay Now" name="PayAnnualFeeFromBalance">
    	<input type="button" value="Cancel" onclick="jQuery('#PayAnnualFeeConfirm').css('display','none'); jQuery('#PayAnnualFeebutton').css('display','');">
    </div>
    <?php } ?>
</form>


<?php
    include_once 'include/footer.php';
?>
