<?php
    chdir('..');
    require_once('include/database.php');
    require_once('include/user.php');
    require_once('include/rider.php');
    require_once('include/ledger.php');
    require_once('include/hps_transactions.php');
    require_once('include/rc_log.php');
    require_once('include/name.php');
	require_once('include/care_facility.php');
	require_once ('include/franchise.php');
    session_start(); 

    $user_id = get_affected_user_id();
    $user_person_name = get_user_person_name($user_id);
	$franchise = get_current_user_franchise();

    if (!isset($_REQUEST['ResultNum'])) {
        $intResultNum = -1;
    } else {
        $intResultNum = $_REQUEST['ResultNum'];
    }

    If (!isset($_REQUEST['TransNum'])) {
        $intResultNum = -1;
    }

    
    If (!isset($_REQUEST['ClientSessionID'])) {
        $intResultNum = -1;
    } else If (!isset($_SESSION['TxnsVFNumb'])) {
        $intResultNum = -1;
    } else If ($_SESSION['TxnsVFNumb'] != $_REQUEST['ClientSessionID']) {
        $intResultNum = -1;
    }            

    $strTransNum = "";
    If (isset($_REQUEST['TransNum'])) {
        $strTransNum = $_REQUEST['TransNum'];
    }

    $strReferenceNumber = "";
    If (isset($_REQUEST['ReferenceNumber'])) 
    {
        $strReferenceNumber = $_REQUEST['ReferenceNumber'];
    }

    $strAuthorizationCode = "";
    If (isset($_REQUEST['AuthorizationCode'])) 
    {
        $strAuthorizationCode = $_REQUEST['AuthorizationCode'];
    }

    $strCardNumber = ""; 
    If (isset($_REQUEST['CardNumber'])) {
        $strCardNumber = "The last 4 card number is " + str_pad($_REQUEST['CardNumber'],4,'0');
    }  Else if (isset($_REQUEST['AccountNumber'])) {
        $strCardNumber = "Your checking account number is " + $_REQUEST['AccountNumber'];
    }

    $strUsedAttempts = ""; 
    If (isset($_REQUEST['UsedAttempts'])) {
        $strUsedAttempts = $_REQUEST['UsedAttempts'];
    } Else {
        $strUsedAttempts = "0";
    }

    $strCardType = ""; 
    If (isset($_REQUEST['CardType'])) {
        $strCardType = $_REQUEST['CardType'];
    } Else {
        $strCardType = "";
    }

    $strInvoiceNum = "";
    If (isset($_REQUEST['InvoiceNum'])) {
        $strInvoiceNum = $_REQUEST['InvoiceNum'];
    }  

    // Verify that the transaction result belongs to a real requested transaction
    $transaction_request = verify_hps_transaction_requested( $user_id, $_SESSION['TxnsVFNumb']);
    if ($transaction_request['EXISTS'] === TRUE /*&& $transaction_request['AMOUNT'] == $cents*/) {

        // SUCCESS reported by HPS portal.
        if ($intResultNum == 0) {

            if ($_SESSION['PaymentDetails']['total_amount'] != $transaction_request['AMOUNT']) {
                rc_log(PEAR_LOG_CRIT, 'Stored transaction amount does not match session amount for ' .
                                      $_SESSION['TxnsVFNumb'] . " SESS:  " . 
                                      var_export($_SESSION, TRUE) );
                echo "A critical error has occurred.  Please call Riders Club of America and refer " .
                     "to Transaction VF #{$SESSION['TxnsVFNumb']}.  We apologize.";
                exit;

                // TODO:  Should probably also check sums of payment amounts
            }

            // TODO:  error checking
            // Store the transaction result.
            $result_stored = store_hps_transaction_result( $_SESSION['TxnsVFNumb'],
                                       $strTransNum, $strReferenceNumber, 
                                       $strAuthorizationCode, $intResultNum, 
                                       $transaction_request['AMOUNT'] );
			
            //EMAIL ADMIN FOR TRANSACTION
			mail_admin_for_transaction($user_id, get_displayable_person_name_string($user_person_name), $strTransNum, $strReferenceNumber, $strAuthorizationCode, $intResultNum, $strUsedAttempts, $strCardType, $_REQUEST['CardNumber'], $strInvoiceNum, $transaction_request['AMOUNT']);
			
            // Credit the user's account (ledger)
            $description = "Added to account using $strCardType ending in {$_REQUEST['CardNumber']}; HPS Web Portal; " .
                           date('Y-m-d h:i:s');
						   
			if(user_has_role($user_id, $franchise, 'CareFacilityAdmin')){
				$care_facility_id = get_first_user_care_facility( $user_id );
				$ledger_id = credit_care_facility( $care_facility_id, $transaction_request['AMOUNT'], $description );
			} else {
        $ledger_id = credit_user( $user_id, $transaction_request['AMOUNT'], $description );
			}
            // Store the ledger entry to the transaction result.
            //$result_updated = store_ledger_id_to_hps_transaction_result( $strTransNum, $strReferenceNumber, $ledger_id );
            $result_updated = store_ledger_id_to_hps_transaction_result( $strReferenceNumber, $ledger_id );

            // If the transaction was for a rider's annual fee, debit their account for the 
            // annual fee amount ("apply" the fee)
            if ($_SESSION['PaymentDetails']['annual_fee'] === TRUE) {
                $rider_id = get_user_rider_id($user_id);
                if ($rider_id === FALSE) {
                    rc_log(PEAR_LOG_ERR, "Received rider annual fee payment from non-rider user $user_id");
                } else {
                    $annual_fee_amount = $_SESSION['PaymentDetails']['annual_fee_amount'];
                    $ledger_id = debit_user( $user_id, $annual_fee_amount,
                                             "Applied annual rider fee of " . $annual_fee_amount ); 
                    if ($ledger_id) {
                        if (set_rider_annual_fee_payment_date( $rider_id )) {
                            rc_log(PEAR_LOG_INFO, "Applied rider annual fee payment of " .
                                                  $annual_fee_amount . 
                                                  " for user $user_id; rider $rider_id");
                        }
                    } else {
                        rc_log(PEAR_LOG_ERR, "Could not debit user $user_id account for rider's annual fee");
                    }
                }
                            
            }

            // If the transaction included extra money to defray processing fees,
            // debit their account for the defray amount.
            if ($_SESSION['PaymentDetails']['defray'] === TRUE) {
                $defray_amount = $_SESSION['PaymentDetails']['defray_amount'];
				if(user_has_role(get_affected_user_id(), $franchise, 'CareFacilityAdmin')){
					$care_facility_id = get_first_user_care_facility( $user_id );
					$ledger_id = debit_care_facility( $care_facility_id,  $defray_amount, "Payment to defray processing fees");
						
				} else {
					$ledger_id = debit_user( $user_id, $defray_amount,
                                         "Payment to defray processing fees" );
				}
                if ($ledger_id) {
                    rc_log(PEAR_LOG_INFO, "Received extra payment of $defray_amount from $user_id" .
                                          " to defray processing fees." );
                }
            }
            

            //TODO  //You can modify the following JavaScript code to redirect to other URL.
            $_strJSCode = "window.onload = function(){document.getElementById('ResultInfor').style.display='block';";
            $_strJSCode = ($_strJSCode."document.getElementById('ResultMessage').style.display='block';}");
            //END
        } else {
            // Store INCOMPLETE transaction with transaction number and result code; zero for all else.
            $result_stored = store_hps_transaction_result( $_SESSION['TxnsVFNumb'],
                                       $strTransNum, $strReferenceNumber, 
                                       0, $intResultNum, 0);
            // UI error handling
            if ($intResultNum == 3) {
                //TODO  //You can modify the following JavaScript code to set the message.
                $_strJSCode = "window.onload = function(){document.getElementById('ResultMessage').style.display='block';";
                $_strJSCode = ($_strJSCode."document.getElementById('lblNotes').innerHTML='The transaction has been canceled because the Max.attempts reached.';}");
                //END
            } else if ($intResultNum == 4) {
                //TODO  //You can modify the following JavaScript code to set the message.
                $_strJSCode = "window.onload = function(){document.getElementById('ResultMessage').style.display='block';";
                $_strJSCode = ($_strJSCode."document.getElementById('lblNotes').innerHTML='The session has timed out.';}");
                //END
            } else {
                $_strJSCode = "window.onload = function(){parent.Back();}";
            }
        }

    } else {
        // TODO:  Transaction wasn't requested or value changed.  Either way, this is bad.
        // Need to figure out what the proper behavior is.
        rc_log(PEAR_LOG_ERR, "Received completed transaction for unknown Transaction ID:  $strTransNum"); 
        rc_log(PEAR_LOG_ERR, "- for $user_id/$strTransNum:  " . var_export($_REQUEST, TRUE));
        $_strJSCode = "window.onload = function(){document.getElementById('ResultMessage').style.display='block';";
        $_strJSCode = ($_strJSCode."document.getElementById('lblNotes').innerHTML='Unknown transaction.  Call customer support with this error message and Transaction ID $strTransNum.';}");
    }

$_SESSION['BKTxnsVFNumb'] = $_SESSION['TxnsVFNumb'];
unset($_SESSION['TxnsVFNumb']);
session_commit();
?>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>Process Return Page</title>
<style type="text/css">
Body {	background-color:RGB(255,255,255);}
.BT_BtnOut {	Width:70px; font-size: 9pt; font-weight:bold;COLOR: RGB(0,0,0);font-family : Arial;}
.BT_BtnOvr {	Width:70px; font-size: 9pt; font-weight:bold;COLOR: RGB(0,0,0);font-family : Arial;}
.BT_Field {	FONT-SIZE: 8pt; font-family : Arial;COLOR: RGB(0,0,0);}
.BT_FieldDescription {	FONT-WEIGHT: bold; FONT-SIZE: 8pt; font-family : Arial; COLOR : RGB(0,0,0);}
</style></head>
<body>
    <div>
        <form id="ProcessReturnForm">
            <table id="ResultInfor" style="display:none">
                <tr>
                    <td style="text-align:right; width:200px">
                        <a class="BT_FieldDescription">Transaction completed for </a></td>
                    <td style="text-align:left; width:200px"><a class="BT_Field"><?php echo get_displayable_person_name_string($user_person_name); ?></a></td>
                </tr>
                <tr>
                    <td style="text-align:right; width:200px">
                        <a class="BT_FieldDescription">Transaction Number :</a></td>
                    <td style="text-align:left; width:200px"><a class="BT_Field"><?php echo $strTransNum ?></a></td>
                </tr>
                <tr>
                    <td style="text-align:right; width:200px">
                        <a class="BT_FieldDescription">Reference Number :</a></td>
                    <td style="text-align:left; width:200px"><a class="BT_Field"><?php echo $strReferenceNumber ?></a></td>
                </tr>
                <tr>
                    <td style="text-align:right; width:200px">
                        <a class="BT_FieldDescription">Authorization Code :</a></td>
                    <td style="text-align:left; width:200px"><a class="BT_Field"><?php echo $strAuthorizationCode ?></a></td>
                </tr>
                <tr>
                    <td style="text-align:right; width:200px">
                        <a class="BT_FieldDescription">Result Number :</a></td>
                    <td style="text-align:left; width:200px"><a class="BT_Field"><?php echo $intResultNum ?></a></td>
                </tr>
                <tr>
                    <td style="text-align:right; width:200px">
                        <a class="BT_FieldDescription">Used Attempts :</a></td>
                    <td style="text-align:left; width:200px"><a class="BT_Field"><?php echo $strUsedAttempts ?></a></td>
                </tr>
                <tr>
                    <td style="text-align:right; width:200px">
                        <a class="BT_FieldDescription">Card Type :</a></td>
                    <td style="text-align:left; width:200px"><a class="BT_Field"><?php echo $strCardType ?></a></td>
                </tr>
                <tr>
                    <td style="text-align:right; width:200px">
                        <a class="BT_FieldDescription">Card Number :</a></td>
                    <td style="text-align:left; width:200px"><a class="BT_Field"><?php echo $strCardNumber ?></a></td>
                </tr>
                <tr>
                    <td style="text-align:right; width:200px">
                        <a class="BT_FieldDescription">Invoice Number :</a></td>
                    <td style="text-align:left; width:200px"><a class="BT_Field"><?php echo $strInvoiceNum ?></a></td>
                </tr>



<?php if ($_SESSION['PaymentDetails']['annual_fee']) { ?>
                <tr>
                    <td style="text-align:right; width:200px">
                        <a class="BT_FieldDescription">Annual Fee :</a></td>
                    <td style="text-align:left; width:200px"><a class="BT_Field"><?php 
                                $annual_fee_amount = $_SESSION['PaymentDetails']['annual_fee_amount'];
                                echo '$' . sprintf("%d.%02d", $annual_fee_amount/100,
                                                              $annual_fee_amount%100 ); ?></a></td>
                </tr>
<? }
   if ($_SESSION['PaymentDetails']['add_balance']) { ?>
                <tr>
                    <td style="text-align:right; width:200px">
                        <a class="BT_FieldDescription">Credit Added :</a></td>
                    <td style="text-align:left; width:200px"><a class="BT_Field"><?php 
                            $add_balance_amount = $_SESSION['PaymentDetails']['add_balance_amount'];
                            echo '$' . sprintf("%d.%02d", $add_balance_amount/100,
                                                          $add_balance_amount%100 ); ?></a></td>
                </tr>
<? }
   if ($_SESSION['PaymentDetails']['defray']) { ?>
                <tr>
                    <td style="text-align:right; width:200px">
                        <a class="BT_FieldDescription">Processing Fee :</a></td>
                    <td style="text-align:left; width:200px"><a class="BT_Field"><?php 
                            $defray_amount = $_SESSION['PaymentDetails']['defray_amount'];
                            echo '$' . sprintf("%d.%02d", $defray_amount/100,
                                                          $defray_amount%100 ); ?></a></td>
                </tr>
<? } ?>
                <tr>
                    <td style="text-align:right; width:200px">
                        <a class="BT_FieldDescription">Total Charged :</a></td>
                    <td style="text-align:left; width:200px"><a class="BT_Field"><?php 
                            $add_amount = $_SESSION['PaymentDetails']['total_amount'];
                            echo '$' . sprintf("%d.%02d", $add_amount/100,
                                                          $add_amount%100 ); ?></a></td>
                </tr>
            </table>
            <table id="ResultMessage" style="display:none">
                <tr>
                    <td style="text-align:center; height:50px" colspan="2">
                        <a id="lblNotes" class="BT_FieldDescription">You have been charged successfully.</a></td>
                </tr>
            <?php if($intResultNum == 0) : ?>
                <tr>
                    <td style="text-align: right">
                    </td>
                    <td style="text-align: right">
                        <input id="btnPrint" type="button" value="Print" onClick="window.print();" class="BT_BtnOut" 
                         onmouseover="this.className='BT_BtnOvr'" onMouseOut="this.className='BT_BtnOut';"/>
                        <input type="button" value="Home" onClick="top.location='../home.php';" class="BT_BtnOut" 
                         onmouseover="this.className='BT_BtnOvr'" onMouseOut="this.className='BT_BtnOut';" /></td>
                </tr>
                <?php else : ?>
                <tr>
                    <td style="text-align: right" >
                        <input type="button" value="Home" onClick="top.location='../home.php';" class="BT_BtnOut" 
                         onmouseover="this.className='BT_BtnOvr'" onMouseOut="this.className='BT_BtnOut';" /></td>
                </tr>
                <?php endif; ?>
            </table>
        </form>
    </div>
    <script type="text/javascript">
        function Back()
        {
            top.opener=null;
            top.open("","_self");
            top.close();
            window.close();
        }
    <?php
        echo "$_strJSCode";
     ?>
    </script>
</body>
</html>
<?php chdir('xhr/'); ?>