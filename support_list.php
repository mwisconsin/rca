<?php
    include_once 'include/user.php';
	redirect_if_not_logged_in();

    require_once('include/rc_log.php');
    require_once('include/supporters.php');
    require_once('include/ledger.php');
    require_once('include/link.php');
	require_once('include/franchise.php');

    $user_id = get_affected_user_id();
	$franchise = get_current_user_franchise();
    $user_balance = calculate_user_ledger_balance($user_id, 'GENERAL');

    $charities = get_supporter_charities_with_ytd($user_id);
    $charities_by_id_xref = create_support_by_id_xref($charities, 'CharityID');
    $year = date('Y');
    $non_charities = get_supporter_rider_support_funds_with_ytd_by_yr($user_id, $year);
	$non_charities2 = get_supporter_rider_support_funds_with_ytd_by_yr($user_id, $year-1);
	$non_charities3 = get_supporter_rider_support_funds_with_ytd_by_yr($user_id, $year-2);
	$non_charities = merge_non_charities($non_charities, $non_charities2, $non_charities3);
	
    $non_charities_by_id_xref = create_support_by_id_xref($non_charities, 'UserID');
   
    if ($_POST['SubmitPreference']) {
        $matches = array();
        if (preg_match('/(\w+)\[(\d+)\]/', $_POST['DriverPref'], $matches)) {
            $raw_type = $matches[1];
            $raw_id = $matches[2];

            echo "YES";
            switch ($raw_type) {
                case 'Charity': 
                    $type = 'CHARITY';
                    break;
                case 'Rider':
                    $type = 'RIDER';
                    break;
                case 'DriverReimbursement':
                    $type = 'REIMBURSEMENT';
                    break;
                default:
                    $type = NULL;
            }

            if (!is_null($type) && is_numeric($raw_id)) {
                store_driver_allocation_preference($user_id, $type, $raw_id);
            }
        }
    }


    if ($_POST['SubmitDollars']) {
        $total = get_submitted_donation_total_cents();

        if ($total <= $user_balance) {
            display_charity_contribution_confirmation($user_id, $charities, $charities_by_id_xref,
                                                      $non_charities, $non_charities_by_id_xref);
        } else {
            $error_string[] = "Total gift exceeds your current balance.";
            // TODO:  "Would you like to add to your Supporting Friend balance?"
        }
    }

    if ($_POST['ConfirmDollars']) {
        $total = get_submitted_donation_total_cents();

        if ($total <= $user_balance) {
            if (is_array($_POST['Dollars'])) {
                foreach ($_POST['Dollars'] as $charity_id => $amount) {
                    if ($amount == 0) { continue; }
                    if (array_key_exists($charity_id, $charities_by_id_xref)) {
                        $cents = $amount * 100;
                        $charity_name = $charities[$charities_by_id_xref[$charity_id]]['CharityName'];

                        if (donate_to_charity($user_id, $charity_id, $charity_name, $cents)) {
                            $_POST['Dollars'][$charity_id] = 0;
                            // TODO:  EMAIL
                        } else {
                            $error_string[] = "Error occurred processing donation to $charity_name";
                        }
                    } else {
                        $error_string[] = "Charity $charity_id not found in your list.";
                    }
                }
            }

            if (is_array($_POST['RiderDollars'])) {
                foreach ($_POST['RiderDollars'] as $rider_user_id => $amount) {
                    if ($amount == 0) { continue; }
                    if (array_key_exists($rider_user_id, $non_charities_by_id_xref)) {
                        $cents = $amount * 100;
                        $user_item = $non_charities[$non_charities_by_id_xref[$rider_user_id]];
                        $disp_name = get_displayable_person_name_string($user_item) . 
                                     ", {$user_item['City']}, {$user_item['State']}";

                        if (donate_to_rider($user_id, $rider_user_id, $disp_name, $cents)) {
                            $_POST['RiderDollars'][$rider_user_id] = 0;
                            // TODO:  EMAIL
                        } else {
                            $error_string[] = "Error occurred processing donation to $disp_name";
                        }
                    } else {
                        $error_string[] = "Rider $rider_user_id not found in your list.";
                    }
                }
            }

            if (!$error_string) {
                $error_string[] = "Your contributions have been successfully processed!  Thank you!";
            }

        } else {
            $error_string[] = "Total gift exceeds your current balance.";
            // TODO:  "Would you like to add to your Supporting Friend balance?"
        }

        // Recalculate balance to account for transactions.
        $user_balance = calculate_user_ledger_balance($user_id, 'GENERAL');

        // Recalculate YTD amounts (xrefs don't need updates)
        $charities = get_supporter_charities_with_ytd($user_id);
        $non_charities = get_supporter_rider_support_funds_with_ytd_by_yr($user_id, $year);
	    $non_charities2 = get_supporter_rider_support_funds_with_ytd_by_yr($user_id, $year-1);
	    $non_charities3 = get_supporter_rider_support_funds_with_ytd_by_yr($user_id, $year-2);
	    $non_charities = merge_non_charities($non_charities, $non_charities2, $non_charities3);
    }


    $show_checkbox = user_has_role($user_id, $franchise, 'DRIVER');
    if ($show_checkbox) {
        $driver_allocation = get_driver_allocation_preference($user_id);
    }


	include_once('include/header.php');
?>
<h2 class="PageTitle">My Support List</h2>
<h3>Current Supporter Balance:  <?php echo format_dollars($user_balance); ?></h3>
<?php if ($show_checkbox) { ?>
<h3>Current Driver Balance:  <?php
    $driver_balance = calculate_user_ledger_balance( $user_id, 'DRIVER' );
    echo format_dollars($driver_balance); ?></h3>
<?php } ?>
<?php if (is_array($error_string)) { 
        foreach ($error_string as $err) { ?>
    <h4 style="color: red"><?php echo $err ?></h4>
<?php }
} ?>
<form method="POST" action="">
<input type="hidden" name="balance" id="balance_cents" value="<?php echo $user_balance ?>" />
<p>Charitable Funds (tax deduction form available)<br />
<br />
<?php
include_once "include/charity.php";
openCharityPopup();

?>
	You may add a charity to your donations list by click this <a href="javascript:openCharityList();">Add a Charity to my Donation List link</a>
<?php /* TODO
   (TODO:  Add charity to support List)*/ ?>
    </p>
<style>
.btable {
	border: 1px solid black;
	border-collapse: collapse;
}
.btable td {
	border: 1px solid #333;
}	
</style>
<table class=btable id=table_charities>
<tr><?php
    if ($show_checkbox) { echo '<th>Preference</th>'; } 
    ?><th>Gift (in dollars)</th><th>To</th><th>YTD Amount</th></tr>
<?php 
    if ($charities) {

        foreach ($charities as $charity) {
            $ytd_amount = format_dollars($charity['YTD_Cents']);

            if ($show_checkbox) {
                $checked = ($driver_allocation['AllocationType'] == 'CHARITY' &&
                            $driver_allocation['AllocationID'] == $charity['CharityID']) ?
                           'checked="checked" ' : '';
                $checkbox = "<td><input type=\"radio\" name=\"DriverPref\" " .
                            "value=\"Charity[{$charity['CharityID']}]\" {$checked} /></td>";
            }

            $dollar_amount = (is_numeric($_POST['Dollars'][$charity['CharityID']])) ? 
                                         $_POST['Dollars'][$charity['CharityID']] : '';

            $dollars = "<input type=\"text\" size=\"3\" name=\"Dollars[{$charity['CharityID']}]\" id=\"Dollars[{$charity['CharityID']}]\" value=\"{$dollar_amount}\" placeholder=\"0\"/>";
			if($charity['Approved'] == 'Y')
            	echo "<tr>{$checkbox}<td>\${$dollars}.00</td><td>{$charity['CharityName']}</td><td>{$ytd_amount}</td></tr>";
			else
				echo "<tr><td colspan='2'><b>Waiting On Approval</b></td><td>{$charity['CharityName']}</td><td>{$ytd_amount}</td></tr>";
        }
    }

?>
</table>
<br />


<p>Non-Charitable Funds (may not be deducted from taxes)<p>
You may add a rider to your support list by selecting this <a href="request_to_support_rider.php">Add a Rider to my Support List</a> link.
<br />
<table class=btable id=table_riders>
<tr><?php 
    if ($show_checkbox) { echo '<th>&nbsp;</th>'; } 
?><th>Gift (in dollars)</th><th>to</th><th>Current $ available for rides</th><th>YTD Amounts</th><th><?php echo date('Y', strtotime('last year')); ?> Amounts</th><th><?php echo date('Y', strtotime('2 years ago')); ?> Amounts</th></tr>

<?php 
    if ($show_checkbox) { // Driver Reimbursement is a special case
        $checked = ($driver_allocation['AllocationType'] == 'REIMBURSEMENT') ?
                   'checked="checked" ' : '';
        $checkbox = "<td><input type=\"radio\" name=\"DriverPref\" " .
                    "value=\"DriverReimbursement[{$user_id}]\" $checked/></td>";
        $driver_reimbursement_ytd = format_dollars(get_ytd_driver_reimbursement_amount($user_id));

        //$dollar_amount = (is_numeric($_POST['DriverReimbursement'][$user_id])) ? 
        //                             $_POST['DriverReimbursement'][$user_id] : 0;
        //$dollars = (if_user_has_role($user_id, 'FULL_ADMIN')) ?
        $dollars =           '&nbsp;'; //:
        //            "<input type=\"text\" size=\"3\" name=\"DriverReimbursement[{$user_id}]\" id=\"DriverReimbursement[{$user_id}]\" value=\"{$dollar_amount}\" />.00";

        echo "<tr>{$checkbox}<td>{$dollars}</td><td>Driver Reimbursement</td><td>&nbsp;</td>" .
             "<td>{$driver_reimbursement_ytd}</td></tr>";
    }

    if ($non_charities) {
        foreach ($non_charities as $non_charity) {
        	# UserID
        	$ytdbumper = (current_user_has_role(1, 'FullAdmin') ? "<a id=\"$non_charity[UserID]\" class=\"User_Redirect\" href=\"user_ledger.php\">" : "");
        	$ytdbackbumper = (current_user_has_role(1, 'FullAdmin') ? "</a>" : "");
          $ytd_amount = $ytdbumper . format_dollars($non_charity['YTD_Cents']) . $ytdbackbumper;
					$ytd_amount2 = $ytdbumper . format_dollars($non_charity['prev_year1']) . $ytdbackbumper;
					$ytd_amount3 = $ytdbumper . format_dollars($non_charity['prev_year2']) . $ytdbackbumper;

            if ($show_checkbox) {
                $checked = ($driver_allocation['AllocationType'] == 'RIDER' &&
                            $driver_allocation['AllocationID'] == $non_charity['UserID']) ?
                           'checked="checked" ' : '';
                $checkbox = "<td><input type=\"radio\" name=\"DriverPref\" " .
                            "value=\"Rider[{$non_charity['UserID']}]\" $checked/></td>";
            }
            $disp_name = (current_user_has_role(1, 'FullAdmin') ? "<a id=\"$non_charity[UserID]\" class=\"User_Redirect\" href=\"myrides.php\">" : "")
            						. get_displayable_person_name_string($non_charity) .
            						 (current_user_has_role(1, 'FullAdmin') ? "</a>" : "") .
                         ", {$non_charity['City']}, {$non_charity['State']}";

            if ($non_charity['UserID'] != $user_id) {
                $rider_balance = calculate_user_ledger_balance( $non_charity['UserID'] );
                $pending_costs = calculate_riders_incomplete_ride_costs( 
                                                    get_user_rider_id($non_charity['UserID']));
                $available_balance = 
                	(current_user_has_role(1, 'FullAdmin') ? "<a id=\"$non_charity[UserID]\" class=\"User_Redirect\" href=\"manual_ledger_entry.php\">" : "") .
                	format_dollars($rider_balance - $pending_costs) .
                	(current_user_has_role(1, 'FullAdmin') ? "</a>" : "");
            } else {
                $available_balance = '';
            }
            
            $begindate = strtotime($non_charity["BeginDate"]);
            $enddate = strtotime($non_charity["EndDate"]);
            if(time() < $begindate || time() > $enddate) $available_balance = 'N/A';


            $dollar_amount = (is_numeric($_POST['Dollars'][$non_charity['UserID']])) ? 
                                         $_POST['Dollars'][$non_charity['UserID']] : '';

            $dollars = "<input type=\"text\" size=\"3\" name=\"RiderDollars[{$non_charity['UserID']}]\" id=\"RiderDollars[{$non_charity['UserID']}]\" value=\"{$dollar_amount}\" placeholder=\"0\" />";

            echo "<tr>{$checkbox}<td>\${$dollars}.00</td><td>{$disp_name}</td><td>{$available_balance}</td><td>{$ytd_amount}</td><td>{$ytd_amount2}</td><td>{$ytd_amount3}</td></tr>";
        }
    }

?>

</table>
<p id="total_paragraph"><span style="font-weight: bold">Total</span> $<span id="gift_total"><?php 
    echo 0; ?></span>.00</p>
<p><?php
if ($show_checkbox) { ?>
<input type="submit" name="SubmitPreference" value="Submit Driver Month-End Preference" /> &nbsp;
<?php }
?><input type="submit" name="SubmitDollars" value="Submit Dollars as Allocated Above"/></p>
</form>

<script>
function confirmSubmission(f) {
	$outstring = '';
	if(jQuery('#table_charities input[name^="Dollars"]').length > 0) {
		jQuery('#table_charities input[name^="Dollars"]').each(function(k,v) {
			if($outstring.indexOf('Charitable') == -1)
				$outstring += 'Please confirm the Charitable Funds:<ul>';
			if(parseInt(jQuery(v).val(),10) > 0) {
				$outstring += '<li>$'+jQuery(v).val()+' to '+jQuery(v).parent('td').next('td').text()+'</li>'
			}
		});
		if($outstring.indexOf('<li>') > -1) 
			$outstring += "</ul>";
	}
	if(jQuery('#table_riders input[name^="RiderDollars"]').length > 0) {
		jQuery('#table_riders input[name^="RiderDollars"]').each(function(k,v) {
			if(parseInt(jQuery(v).val(),10) > 0) {
				if($outstring.indexOf('Non-Charitable') == -1)
					$outstring += 'Please confirm the Non-Charitable Funds:<ul>';
				$outstring += '<li>$'+jQuery(v).val()+' to '+jQuery(v).parent('td').next('td').text()+'</li>'
			}
		});
		$outstring += "</ul>";
	}
	if($outstring != '') {
		$d = jQuery('<div>'+$outstring+'</div>').dialog({
			modal: true,
			width: '400px',
			buttons: [
				{ text: 'Ok', click: function() { $d.dialog('close'); jQuery('#SubmitDollarsButton').click(); } },
				{ text: 'Cancel', click: function() { $d.dialog('close'); } }
			]
		});
	}
}	
</script>
<div style="clear:both">&nbsp;</div>

<?php
	include_once 'include/footer.php';


function create_support_by_id_xref($support, $key) {
    $xref = array();
    if (is_array($support)) {
        foreach ($support as $index => $support_item) {
            $xref[$support_item[$key]] = $index;
        }
    }

    return $xref;
}

function get_submitted_donation_total_cents() {
    $sum = 0;
    if (is_array($_POST['Dollars'])) {
        foreach ($_POST['Dollars'] as $idx => $amount) {
            if ($amount < 0) { 
                $_POST['Dollars'][$idx] = 0;
                continue; 
            }
            $sum += $amount;
        }
    }

    if (is_array($_POST['RiderDollars'])) {
        foreach ($_POST['RiderDollars'] as $idx => $amount) {
            if ($amount < 0) { 
                $_POST['RiderDollars'][$idx] = 0;
                continue; 
            }
            $sum += $amount;
        }
    }

    $sum *= 100;
    return $sum;
}

function donate_to_charity($user_id, $charity_id, $charity_name, $cents) {
    if ($cents == 0) { echo "NO CENTS"; return FALSE; }

    $ret = FALSE;
    $charity_name = htmlspecialchars($charity_name);
    if (db_start_transaction()) {
        // Deduct from user
        $debit_entry_id = debit_user($user_id, $cents, 
                                     "Contribution to '{$charity_name}' ({$charity_id})");

        // Add to charity
        $credited = credit_charity($charity_id, $cents, "Donation from {$user_id}");
        
        // Mark the debit as a charity donation
        $connected = store_supporter_charity_record($user_id, $charity_id, $debit_entry_id);
        
        if ($debit_entry_id && $credited && $connected) {
            if (db_commit_transaction()) {
                $ret = TRUE;
                $donation_amount = format_dollars($cents);
                $email_string = "User $user_id donated $donation_amount to {$charity_name} ({$charity_id})";
                mail(DEFAULT_ADMIN_EMAIL, 'Donation Record', $email_string, DEFAULT_EMAIL_FROM);
            }
        }
        
        if (!$ret) {
            db_rollback_transaction();
        }
    }

    return $ret;
}


function donate_to_rider($user_id, $rider_user_id, $rider_display_name, $cents) {
    if ($cents == 0) { echo "NO CENTS"; return FALSE; }

    $ret = FALSE;
    $rider_display_name = htmlspecialchars($rider_display_name);
    if (db_start_transaction()) {
        // Deduct from user
        $debit_entry_id = debit_user($user_id, $cents, 
                                     "Contribution to '{$rider_display_name}' ({$rider_user_id})");

        // Add to rider
        $credit_entry_id = credit_user($rider_user_id, $cents, "Donation from {$user_id}");
        
        // Mark the debit as a rider donation
        $connected = store_supporter_rider_record($user_id, $debit_entry_id,
                                                  $rider_user_id, $credit_entry_id);
        
        if ($debit_entry_id && $credit_entry_id && $connected) {
            if (db_commit_transaction()) {
                $ret = TRUE;
                $donation_amount = format_dollars($cents);
                $email_string = "User $user_id donated $donation_amount to " .
                                "{$rider_display_name} ({$rider_user_id})";
                mail(DEFAULT_ADMIN_EMAIL, 'Rider Support Record', $email_string, DEFAULT_EMAIL_FROM);
            }
        }
        
        if (!$ret) {
            db_rollback_transaction();
        }
    }

    return $ret;
}

function display_charity_contribution_confirmation($user_id, $charities, $charities_by_id_xref,
                                                   $non_charities, $non_charities_by_id_xref) {
    include_once('include/header.php');
?>
<h2 class="PageTitle">Confirm Support Allocation</h2>
<h3>Current Supporter Balance:  <?php
    global $user_balance;
    echo format_dollars($user_balance); ?></h3>
<form method="POST" action="">
<input type="hidden" name="balance" id="balance_cents" value="<?php echo $user_balance ?>" />
<?php // TODO:  Iterate through POSTED fields and include hidden for each ?>
<p>You have indicated you want to contribute to the following funds:</p>
<style>
.btable {
	border: 1px solid black;
	border-collapse: collapse;
}
.btable td {
	border: 1px solid #333;
}	
</style>
<table class=btable id=table_charities>
<tr><th>Gift (in dollars)</th><th>To</th></tr>
<?php 
    if ($charities) {
        foreach ($_POST['Dollars'] as $charity_id => $amount) {
            if ($amount == 0 || !is_numeric($charity_id) || !is_numeric($amount)) { continue; }
            if (array_key_exists($charity_id, $charities_by_id_xref)) {
                $gift_cell = format_dollars($amount * 100);
                $charity_name = $charities[$charities_by_id_xref[$charity_id]]['CharityName'];

                $input = "<input type=\"hidden\" name=\"Dollars[{$charity_id}]\" value=\"" .
                         "{$amount}\" />";

                echo "<tr><td>$gift_cell</td><td>{$charity_name}{$input}</td></tr>";

            }
        }
    }

    if ($non_charities) {
        foreach ($_POST['RiderDollars'] as $rider_user_id => $amount) {
            if ($amount == 0 || !is_numeric($rider_user_id) || !is_numeric($amount)) { continue; }
            if (array_key_exists($rider_user_id, $non_charities_by_id_xref)) {

                $gift_cell = format_dollars($amount * 100);
                $user_item = $non_charities[$non_charities_by_id_xref[$rider_user_id]];
                $disp_name = get_displayable_person_name_string($user_item) . 
                             ", {$user_item['City']}, {$user_item['State']}";

                $input = "<input type=\"hidden\" name=\"RiderDollars[{$rider_user_id}]\" value=\"" .
                         "{$amount}\" />";

                echo "<tr><td>$gift_cell</td><td>{$disp_name}{$input}</td></tr>";
            }
        }
    }
?>
</table>
<p>Select &quot;Yes&quot; to confirm or &quot;No&quot; to cancel.</p>
<?

?>
<input type="Submit" name="ConfirmDollars" value="Yes" id="YesButton"/> <input type="Submit" name="CancelConfirm" value="No" />
<?php
    if (is_array($_POST['Dollars'])) {
        foreach ($_POST['Dollars'] as $charity_id => $dollar_amount) {
            if (is_numeric($charity_id) && is_numeric($dollar_amount) && $dollar_amount > 0) { 
                echo "<input type=\"hidden\" name=\"Dollars[{$charity_id}]\" value=\"$dollar_amount\" />";
            }
        }
    }
?>

</form>

<div style="clear:both">&nbsp;</div>
<?php
    include_once('include/footer.php');
    exit;

}


?>
<script type="text/javascript">
var supportList = {
    init:  function() {
        supportList.decorate_amount_boxes(); 
    },

    decorate_amount_boxes: function() {
        $each($$('input[type="text"]'), function(item) {
            if (item.id.indexOf('Dollars') != -1) {
                item.addEvents( {'change': supportList.update_current_total} );
                item.addEvents( {'change': supportList.checkIsInt} );
                                 //'change': supportList.checkIsInt} );
            }
        });
    },

    update_current_total: function() {
        var running_total = 0;
        var entry_amount;

        var elts = $$('input[type="text"]');
        for (var i = 0; i < elts.length; i++) {
            var inputElt = elts[i];
            if (inputElt && inputElt.value != '' && inputElt.id.indexOf('Dollars') != -1) { 
                inputElt.value = inputElt.value.replace(/^\s+|\s+$/g, '');
                entry_amount = parseInt(inputElt.value, 10);
                if (!(entry_amount < 0)) {
                    // This will still "add" NaN
                    if (isNaN(entry_amount)) {
                        alert(inputElt + " " + inputElt.id);
                    }
                    running_total += parseInt(inputElt.value, 10);
                }
            }
        }

        var totalElt = $('gift_total');
        if (totalElt) {
            if (isNaN(running_total)) {
                running_total = '(Check your input!)';
            }
            totalElt.innerHTML = running_total;
        }

        var balanceElt = $('balance_cents');
        var totalParElt = $('total_paragraph');
        if (totalParElt && balanceElt && balanceElt.value < running_total * 100) {
            totalParElt.style.color = 'red';
        } else {
            totalParElt.style.color = 'black';
        }
    },

    isInt: function(str) {
        var intStr = parseInt(str, 10);
        if (isNaN(intStr)) {
            return false;
        }

        intStr = intStr.toString();

        return (intStr == str);
    },

    checkIsInt: function(evt) {
        if (evt.target.value == '') { evt.target.value = 0; }
        if (!supportList.isInt(evt.target.value)) {
            alert(evt.target.value + " is not a well-formed whole number");
        }

    }

}

window.addEvent('domready', supportList.init);

jQuery(function($) { $('#YesButton').focus(); });
</script>

<?php
function merge_non_charities($charity1, $charity2, $charity3) {
  for ($i=0; $i<sizeof($charity1); $i++) {
    $charity1[$i]['prev_year1'] = 0;
    for ($j=0; $j<sizeof($charity2); $j++) {
	  if ($charity1[$i]['UserID']==$charity2[$j]['UserID']) {
	    $charity1[$i]['prev_year1'] += $charity2[$j]['YTD_Cents'];
	  }
	}
  }
  
  for ($i=0; $i<sizeof($charity1); $i++) {
    $charity1[$i]['prev_year2'] = 0;
    for ($j=0; $j<sizeof($charity3); $j++) {
	  if ($charity1[$i]['UserID']==$charity3[$j]['UserID']) {
	    $charity1[$i]['prev_year2'] += $charity3[$j]['YTD_Cents'];
	  }
	}
  }
  return $charity1;
 
}
