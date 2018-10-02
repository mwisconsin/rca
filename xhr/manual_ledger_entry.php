<?php

	require_once 'include/user.php';
	require_once 'include/franchise.php';
	
	 /**
	 * Returns the Driver Status and Franchise ID of the identified user as a hash.  Keys to the hash:
	 * DriverStatus, FranchiseID.
	 * @param user_id ID of user to get info for
	 * @return hash containing name fields or FALSE on error.
	 *///TODO: rewrite function to use driverID
	 
	function get_user_annual_fee( $franchise_id )
	{
	    $safe_franchise_id = mysql_real_escape_string( $franchise_id );
	
	    $sql = "SELECT AnnualFee FROM franchise WHERE FranchiseID = $safe_franchise_id";
	    
		$result = mysql_query( $sql );
	    if ( $result ) 
	    {
	        $result = mysql_fetch_array( $result );
	    } 
	    else 
	    {
	        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
	                        "Error getting driver info for user $user_id", $sql);
	        $result = FALSE;
	    }
	
		return $result;
	}
	
	
	redirect_if_not_logged_in();
	$franchise = get_current_user_franchise();
	
	$annual_fee_amount = get_user_annual_fee( $franchise_id );
	echo "annual user fee = $annual_fee_amount";
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

    if ($_POST['LedgerType'] && is_numeric($_POST['LedgerAmount']) && $_POST['Desc'] && $_POST['Submit']) {
        $effective_date = "{$_POST['EffectiveYear']}-{$_POST['EffectiveMonth']}-{$_POST['EffectiveDay']}";

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
    $rider_user_id = $rider_info['UserID'];
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
            
            if ($balance >= $$annual_fee_amount) {
                $effective_date = "{$_POST['EffectiveYear']}-{$_POST['EffectiveMonth']}-{$_POST['EffectiveDay']}";
                $fee_ledger_id = debit_user( $user_id, $$annual_fee_amount,
                                             "Applied annual rider fee of " . $$annual_fee_amount,
                                             $effective_date ); 

                if ($fee_ledger_id) {
                    if (set_rider_annual_fee_payment_date( $rider_user_id, $effective_date )) {
                        rc_log(PEAR_LOG_INFO, "Applied rider annual fee payment of " .
                                              $$annual_fee_amount . " for user $user_id; rider $rider_user_id");
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


<form action="" method="POST">
    <input type="radio" name="LedgerType" value="debit" />Debit<br />
    <input type="radio" name="LedgerType" value="credit" />Credit<br />
<?php if (if_user_has_role($user_id, $franchise, 'DRIVER')) { ?>
    <input type="radio" name="LedgerType" value="DriverReimbursement" />Driver Reimbursement<br />
<?php } ?>
    Amount (cents): <input type="text" name="LedgerAmount" value="" size="5" /><br />
    Description:  <input type="text" name="Desc" value="" size="45" /><br />
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
    &nbsp;<div style="display: inline-table">
    <input type="radio" name="IsCheck" value="LocalCheck" id="LocalCheck" /> - Local Check - Adds 3 Days<br />
    <input type="radio" name="IsCheck" value="OutOfStateCheck" id="OutOfStateCheck" /> - Out-of-State Check - Adds 5 Days<br />
    <input type="radio" name="IsCheck" value="None" id="NotACheck" style="display: none;" />
    <script type="text/javascript">
    // <![CDATA[  
        function manual_ledger_entry_add_days_to_effective_date(days) {
            var d = $('EffectiveDay');
            var m = $('EffectiveMonth');
            var y = $('EffectiveYear');

            dateObj = new Date(y.value, m.value, parseInt(d.value, 10) + days);
            if (d) { d.value = dateObj.getDate(); }
            if (m) { m.value = dateObj.getMonth(); }
            if (y) { y.value = dateObj.getYear; }
        }

        $('LocalCheck').addEvents({
            'change': function(evt){
                manual_ledger_entry_add_days_to_effective_date(3);
            }
        });

        $('OutOfStateCheck').addEvents({
            'change': function(evt){
                manual_ledger_entry_add_days_to_effective_date(5);
            }
        });
    // ]]>
    </script>
    </div>


        
    <br /><br />

    <input name="Submit" type="submit" value="Submit" />

    <p>Annual Fee <?php
        if (!$rider_user_id) {
            echo 'does not apply to non-riders.';
        } elseif (!$annual_fee_payment_date) {
            echo 'has never been paid.';
        } else {
            echo "last paid {$annual_fee_payment_date['Year']}-{$annual_fee_payment_date['Month']}-{$annual_fee_payment_date['Day']}";
        }
    ?></p>

    <input type="submit" id="PayAnnualFeebutton" name="PayAnnualFeeFromBalance" value="Pay Annual Fee From Balance" <?php
        if ($balance < $annual_fee_amount || !$rider_user_id) { echo 'disabled="disabled"'; }
        if ($days_to_payment > 31 && !($balance < $annual_fee_amount || !$rider_user_id)){ 
        	echo " onclick=\"this.setStyle('display','none'); $('PayAnnualFeeConfirm').setStyle('display',''); return false;\"";
        }
    ?>/>
    <?php if( $days_to_payment > 31 && !($balance < $annual_fee_amount || !$rider_user_id)){ ?>
    <div id="PayAnnualFeeConfirm" style="display:none;" class="reminder">
    	Are You sure you want to extend their annual fee through <?php 
            $new_end_time_str = $annual_fee_payment_date['Year'] + 2 . 
                                "-{$annual_fee_payment_date['Month']}-{$annual_fee_payment_date['Day']}";
            $new_end_time = strtotime($new_end_time_str);
            echo date( "F j, Y", $new_end_time); ?>?<br>
    	<input type="submit" value="Pay Now" name="PayAnnualFeeFromBalance">
    	<input type="button" value="Cancel" onclick="$('PayAnnualFeeConfirm').setStyle('display','none'); $('PayAnnualFeebutton').setStyle('display','');">
    </div>
    <?php } ?>
</form>


<?php
    include_once 'include/footer.php';
?>
