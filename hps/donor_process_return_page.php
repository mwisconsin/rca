<?php
    chdir('..');
    require_once('include/database.php');
    require_once('include/user.php');
    require_once('include/ledger.php');
    require_once('include/rc_log.php');
    require_once('include/db_donation.php');
    session_start(); 


    $calculated_hash = sha1('D0NAT10nSALT' . $_SESSION['DonationRecord']);
    if ($calculated_hash != $_SESSION['DonationRecordUUID']) {
        // TODO:  Error diagnostics/logging.
        // Likely this is a bad browser...or an attempt to tamper with the session.
        echo "An unknown error occurred setting up a credit card transaction.";
        exit;
    }
                
    $donation_info = unserialize(urldecode($_SESSION['DonationRecord']));
    if ($donation_info === FALSE) {
        // TODO:  Error diagnostics/logging.
        // Likely this is a bad browser...or an attempt to tamper with the session.
        echo "A serialization error occurred processing your donation information.";
        exit;
    }

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
    } else If ( 'RCDN' . $_SESSION['TxnsVFNumb'] != $_REQUEST['ClientSessionID']) {
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
        $strCardNumber = "The last 4 card number is " + $_REQUEST['CardNumber'];
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
    $donation_db = get_donation_record( $donation_info['DonationRecordID'] );  
    //$transaction_request = verify_hps_transaction_requested( $user_id, $_SESSION['TxnsVFNumb']);
    if ($donation_db !== FALSE) {

        // SUCCESS reported by HPS portal.
        if ($intResultNum == 0) {

            if ($donation_info['PaymentDetails']['amount'] != $donation_db['DonationCents']) {
                rc_log(PEAR_LOG_CRIT, 'Stored donation amount does not match session amount for ' .
                                      $donation_info['DonationRecordID'] . " SESS:  " . 
                                      var_export($_SESSION, TRUE) );
                echo "A critical error has occurred.  Please call Riders Club of America and refer " .
                     "to Transaction VFDN #{$SESSION['TxnsVFNumb']}.  We apologize.";
                exit;

                // TODO:  Should probably also check sums of payment amounts
            }

            // TODO:  error checking
            // Store the transaction result.
            $result_stored = store_hps_donation_result( $_SESSION['TxnsVFNumb'],
                                       $strTransNum, $strReferenceNumber, 
                                       $strAuthorizationCode, $intResultNum, 
                                       $donation_db['DonationCents'] );

            $marked_paid = set_donation_payment_received( $donation_info['DonationRecordID'] );
            
            // Credit the user's account (ledger)
            $description = "Added to account using $strCardType ending in {$_REQUEST['CardNumber']}; HPS Web Portal; " .
                           date('Y-m-d h:i:s');

            //TODO  //You can modify the following JavaScript code to redirect to other URL.
            $_strJSCode = "window.onload = function(){document.getElementById('ResultInfor').style.display='block';";
            $_strJSCode = ($_strJSCode."document.getElementById('ResultMessage').style.display='block';}");
            //END
        } else {
            // Store INCOMPLETE transaction with transaction number and result code; zero for all else.
            $result_stored = store_hps_donation_result( $_SESSION['TxnsVFNumb'],
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
        rc_log(PEAR_LOG_ERR, "Received completed transaction for unknown donation transaction:  $strTransNum"); 
        rc_log(PEAR_LOG_ERR, "- for $strTransNum:  " . var_export($_REQUEST, TRUE));
        $_strJSCode = "window.onload = function(){document.getElementById('ResultMessage').style.display='block';";
        $_strJSCode = ($_strJSCode."document.getElementById('lblNotes').innerHTML='Unknown transaction.  Call customer support with this error message and Transaction ID $strTransNum.';}");
    }
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
    <h2>Thank you for your donation!</h2>
        <form id="ProcessReturnForm">
            <table id="ResultInfor" style="display:none">
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

                <tr>
                    <td style="text-align:right; width:200px">
                        <a class="BT_FieldDescription">Total Charged :</a></td>
                    <td style="text-align:left; width:200px"><a class="BT_Field"><?php 
                            $amount = $donation_db['DonationCents'];
                            echo '$' . sprintf("%d.%02d", $amount/100,
                                                          $amount%100 ); ?></a></td>
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
                        <input type="button" value="Close" onClick="javascript:Back();" class="BT_BtnOut" 
                         onmouseover="this.className='BT_BtnOvr'" onMouseOut="this.className='BT_BtnOut';" /></td>
                </tr>
                <?php else : ?>
                <tr>
                    <td style="text-align: right" >
                        <input type="button" value="Close" onClick="javascript:Back();" class="BT_BtnOut" 
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