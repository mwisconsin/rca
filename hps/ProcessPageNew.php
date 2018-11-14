<?php 
chdir('..');
require_once('include/database.php');
require_once('include/user.php');
require_once('include/hps_transactions.php');
require_once('include/rc_log.php');
require_once('include/franchise.php');
require_once('include/care_facility.php');
#error_reporting(E_ALL);
session_start(); 

$user_id = get_affected_user_id();
$user_person_name = get_user_person_name($user_id);

$transaction_cents = $_SESSION['PaymentDetails']['total_amount'];
$franchise_id = get_current_user_franchise();
rc_log(PEAR_LOG_INFO,"ProcessPageNew for $user_id, $user_person_name, $transaction_cents, $franchise_id");
// Session must be initialized here for HPS.

//Initialize the transaction verification number within the session variable to be null.
$_SESSION["TxnsVFNumb"] = null;
//END

$strProcessType = "";
$strTransNum = "";
$strTransTypeRequest = "";
if (isset($_REQUEST['ProcessType']))
{
    $strProcessType = $_REQUEST['ProcessType'];
}

if (isset($_REQUEST['TransType']))
{
    $strTransTypeRequest = $_REQUEST['TransType'];
}
$strTransType = "Sale"; 

$franchise_name = get_franchise_name($franchise_id);
		
//TODO
//Assign a page title for the process page to variable strPageTitle below.
$strPageTitle = "Riders Club of America - " . $franchise_name;
//END

//Assign a variable strPromptCancel below to show Prompt Cancel on the WebConnect page.
//Value is Yes or No.
$strPromptCancel = "Yes";
//END

//Assign a variable strShowTransNo below to show transaction number on the WebConnect page.
//Value is Yes or No.
$strShowTransNo = "Yes";
//END

//Assign variable strCustomerID, strCustomerName below to store customer number and customer name for the WebConnect page.
// strCustomerID is WebSiteAlias_OurCustomerID
// TODO: What is our website alias?
$strCustomerID = 'RC'. $franchise_id . '_' . $user_id;  // "TestCustomer";
$strCustomerName = $user_person_name['FirstName'] . ' ' . 
                   $user_person_name['MiddleInitial'] . ' ' .
                   $user_person_name['LastName'];  //"TestCustomer";
//END

//Assign variable strCustomerFirst/LastName below to store customer's first/last name for the WebConnect page.
$strCustomerFirstName = $user_person_name['FirstName'];
$strCustomerLastName = $user_person_name['LastName'];
//END

// Get the user's billing address, if there is one.
//        $addresses = get_user_address_array( $user_id );
//        if (count($addresses) > 0) {
//            foreach ($addresses as $addr) {
//                if ($addr['AddressType'] == 'Billing') {
//                    $arr_address = $addr;
//                } elseif (!isset($address)) {
//                    $arr_address = $addr;
//                }
//            }
//        }
$arr_address = $_POST;
        
$phones = get_user_phone_numbers( $user_id );
foreach($phones as $ph) if($ph['isPrimary'] == 'Yes') { $phone = $ph; break; }

// Store the transaction request and generate a verification ID.
// Could also use session ID or a random number.
$strVerifyNum = store_hps_transaction_request( $user_id, $transaction_cents );
$strTransNum = $strVerifyNum;
if ($strVerifyNum == -1) {
    // TODO:  Could not generate a verification ID.  Problem with our DB.
    rc_log(PEAR_LOG_ERR, 'Could not generate verification ID for HPS transaction');
}
//Store the verification number to the session. This verification number will be used to verify the request once the request is passed back from the WebConnect Portal with a result.
$_SESSION["TxnsVFNumb"] = $strVerifyNum;
//END

//Assign your website's URL to the variable strURL below.
$strURL = 'http://www.myridersclub.com/';
//$strURL = 'http://ridersclub/';

//END 

if (substr($strURL, -1) == "/"){
$strURL = substr($strURL, 0, -1);
} 
$strReturnURL = ($strURL."/hps/ProcessReturnPage.php");

//TODO
//Assign the WebConnect Portal's URL to the variable strRedirectURL below.
// Test URL
//$strRedirectURL = "https://hps.webportal.test.secureexchange.net";
$strRedirectURL = "https://webportal.trans.secureexchange.net";

if (substr($strRedirectURL, -1) == "/"){
$strRedirectURL = substr($strRedirectURL, 0, -1);
} 
$strRedirectURL = ($strRedirectURL."/PaymentMain.aspx");
//END 

//TODO
//Assign basic transaction information (i.e. the total amount of the transaction) to the variable totalAmount below. It must be number with 2 decimal places.
//$totalAmount = 15.50;
$totalAmount = sprintf("%d.%02d", $transaction_cents/100,
                                  $transaction_cents%100);
//END 

//TODO
//Assign the merchant's information, such as WebConnect User ID to the variable strMerchantID below.
$strMerchantID = "ridernet" ;
//END    

session_commit();
        
        
require_once "newhps/Hps.php";

$config = new HpsServicesConfig();
$config->secretApiKey = $__HPS_SECRET_KEY;

// the following variables will be provided to you during certificaiton.
$config->versionNumber = '2290';
$config->developerId = '002914';

$chargeService = new HpsCreditService($config);

$address = new HpsAddress();
$address->address = $arr_address['Address1'];
$address->city = $arr_address['City'];
$address->state = $arr_address['State'];
$address->zip = $arr_address['Zip5'];
$address->country = "United States";

$validCardHolder = new HpsCardHolder();
$validCardHolder->firstName = $strCustomerFirstName;
$validCardHolder->lastName = $strCustomerLastName;
$validCardHolder->address = $address;
#$validCardHolder->phoneNumber = preg_replace('/[^0-9]/', '', $phone['PhoneNumber']);

$suToken = new HpsTokenData();


if(isset($_POST['token_value']) && $_POST['token_value'] != "" 
	&& (!isset($_POST["card_number"]) || $_POST["card_number"] == "")) {
	$suToken->tokenValue = $_POST['token_value'];
	rc_log(PEAR_LOG_INFO,"Using Token Value: ".$_POST['token_value']);
} else {
	$validCard = new HpsCreditCard();
	$validCard->number = $_POST["card_number"];
	$validCard->cvv = $_POST["card_cvc"];
	$validCard->expMonth = $_POST["exp_month"];
	$validCard->expYear = $_POST["exp_year"];
	try {
		rc_log(PEAR_LOG_INFO,"Attempting to Verify Card");
		$response = $chargeService->verify($validCard, $validCardHolder,true );
	} catch (CardException $e) {
  		rc_log(PEAR_LOG_ERR,"Verify Error: ".$e->getMessage());
      echo 'Verify Failure: ' . $e->getMessage();
	} catch (Exception $e) {
  		rc_log(PEAR_LOG_ERR,"Verify Error: ".$e->getMessage());
      echo 'Verify Failure: ' . $e->getMessage();
  }

	$suTokenValue = $response->tokenData->tokenValue;
	$response = $chargeService->updateTokenExpiration($suTokenValue, $_POST["exp_month"], $_POST["exp_year"]);	
	$suToken->tokenValue = $suTokenValue;

}

$response = chargeToken($chargeService, $suToken, $validCardHolder);
?>
<html>
<body>
<?php

if (is_string($response)) {
		rc_log(PEAR_LOG_ERR, $response);
    echo "error: " . $response;
    exit;
} else {

	if(!isset($_POST["token_value"]) || $_POST["token_value"] == "") {
		$sql = "insert into hps_mutokens (UserID, muToken, Nickname) values ($user_id, '{$suToken->tokenValue}', '{$_POST[Nickname]}')";
		$strNickname = $_POST["token_value"];
		mysql_query($sql);
		rc_log(PEAR_LOG_INFO, "New Nickname $strNickname: $sql");
	}	else {
		#echo $_POST[token_value]."<BR>";
		$sql = "select Nickname from hps_mutokens where muToken = '{$_POST[token_value]}'";
		$rs = mysql_fetch_array(mysql_query($sql));
		rc_log(PEAR_LOG_INFO,"Get Nickname $rs[Nickname]");
		$strNickname = $rs["Nickname"];
	}
	$strReferenceNumber = $response->referenceNumber;
	$strTransNum = $response->transactionId;
	$strAuthorizationCode = $response->authorizationCode;
	$strInvoiceNum = $response->invoiceNumber;
	$strCardType = $response->cardType;
	$strUsedAttempts = 1;
	$intResultNum = 0;
	
  $result_stored = store_hps_transaction_result( $strVerifyNum,
                             $strTransNum, $strReferenceNumber, 
                             $strAuthorizationCode, $strTransNum, 
                             $transaction_cents );	
  mail_admin_for_transaction($user_id, get_displayable_person_name_string($user_person_name), 
  	$strTransNum, $strReferenceNumber, $strAuthorizationCode, $intResultNum, 
  	$strUsedAttempts, $strCardType, $strNickname, 
  	$strInvoiceNum, $transaction_cents);	
  
  $description = "Added to account using {$strNickname}; HPS Web Portal; " .
                 date('Y-m-d h:i:s');    
	if(user_has_role($user_id, $franchise_id, 'CareFacilityAdmin')){
		$care_facility_id = get_first_user_care_facility( $user_id );
		$ledger_id = credit_care_facility( $care_facility_id, $transaction_cents, $description );
		rc_log(PEAR_LOG_INFO,"Credited Care Facility $care_facility_id (user $user_id) with $transaction_cents");
	} else {
    $ledger_id = credit_user( $user_id, $transaction_cents, $description );
	}    
	$result_updated = store_ledger_id_to_hps_transaction_result( $strReferenceNumber, $ledger_id );
	
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
		if(user_has_role($user_id, $franchise_id, 'CareFacilityAdmin')){
			$care_facility_id = get_first_user_care_facility( $user_id );
			$ledger_id = debit_care_facility( $care_facility_id,  $defray_amount, "Payment to defray processing fees");
      rc_log(PEAR_LOG_INFO, "Received extra payment of $defray_amount from Care Facility $care_facility_id (user $user_id)" .
                            " to defray processing fees." );				
		} else {
			$ledger_id = debit_user( $user_id, $defray_amount,
		                                 "Payment to defray processing fees" );
      rc_log(PEAR_LOG_INFO, "Received extra payment of $defray_amount from $user_id" .
                            " to defray processing fees." );		          
		}
  }

	
?>
    <div>
        <form id="ProcessReturnForm">
            <table id="ResultInfor">
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
                    <td style="text-align:left; width:200px"><a class="BT_Field"><?php echo $strNickname ?></a></td>
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
            <table id="ResultMessage">
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

<?php
	exit;
}
        
function chargeToken($chargeService, $suToken, $validCardHolder)
{
		global $totalAmount;
    try {
    		#rc_log(PEAR_LOG_INFO,"Trying charge $totalAmount USD for token ".var_dump($suToken)." validCardHolder ".var_dump($validCardHolder));
        $response = $chargeService->charge(
            $totalAmount,
            'usd',
            $suToken,
            $validCardHolder
        );
    } catch (CardException $e) {
    		rc_log(PEAR_LOG_ERR,"Error: ".$e->getMessage());
        return 'Failure: ' . $e->getMessage();
    } catch (Exception $e) {
    		rc_log(PEAR_LOG_ERR,"Error: ".$e->getMessage());
        return 'Failure: ' . $e->getMessage();
    }
    return $response;
}
?>
</body>
</html>
