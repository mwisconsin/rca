<?php 
    chdir('..');
    require_once('include/database.php');
    require_once('include/user.php');
    require_once('include/hps_transactions.php');
    require_once('include/rc_log.php');
    session_start(); 


    // The session carries the donation and donor info.  The donor is not logged in, so
    // the user-centric approach is changed here.
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

    $donor_person_name = get_name($donation_info['DonorNameID']);

    $transaction_cents = $donation_info['PaymentDetails']['amount'];

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

        //TODO
        //Assign a page title for the process page to variable strPageTitle below.
        $strPageTitle = "Riders Club";
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
        $strCustomerID = 'RCDN_' . $donation_info['DonationRecordID'];
        $strCustomerName = $donor_person_name['FirstName'] . ' ' . 
                           $donor_person_name['MiddleInitial'] . ' ' .
                           $donor_person_name['LastName'];  
        //END

        //Assign variable strCustomerFirst/LastName below to store customer's first/last name for the WebConnect page.
        $strCustomerFirstName = $donor_person_name['FirstName'];
        $strCustomerLastName = $donor_person_name['LastName'];
        //END

        // Get the user's billing address, if there is one.
        $address = get_address( $donation_info['DonorAddressID'] );
        // TODO:  Check that it exists?

        // Store the transaction request and generate a verification ID.
        // Could also use session ID or a random number.
        // TODO:  Should allow donor ID
        //$strVerifyNum = store_hps_transaction_request( $user_id, $transaction_cents );
        $strVerifyNum = $donation_info['DonationRecordID'];
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
        $strReturnURL = ($strURL."/hps/donor_process_return_page.php");

		//TODO
        //Assign the WebConnect Portal's URL to the variable strRedirectURL below.
        // Test URL
        $strRedirectURL = "https://webportal.trans.secureexchange.net";
        //$strRedirectURL = "https://hps.webportal.test.secureexchange.net";
		
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
?>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>Process Page</title>
</head>
<body>
    <div>
        <form id="ProcessForm" method="post">
            <input type="hidden" name="ClientSessionID" value="<?php echo 'RCDN' . $strVerifyNum ?>" />
            <input type="hidden" name="ProcessType" value="<?php echo $strProcessType ?>" />
            <input type="hidden" name="TransType" value="<?php echo $strTransType ?>" />
            <input type="hidden" name="UserName" value="<?php echo $strMerchantID ?>" />
            <input type="hidden" name="Amount" value="<?php echo $totalAmount ?>" />
            <input type="hidden" name="TransNum" value="<?php echo $strTransNum ?>" />
            <input type="hidden" name="ReturnURL" value="<?php echo $strReturnURL ?>" />
            <input type="hidden" name="HasHeader" value="true" />
            <input type="hidden" name="NameOnCard" value="<?php echo $strCustomerName ?>" />
            <input type="hidden" name="Address" value="<?php echo $address['Address1'] ?>" />
            <input type="hidden" name="City" value="<?php echo $address['City'] ?>" />
            <input type="hidden" name="State" value="<?php echo $address['State'] ?>" />
            <input type="hidden" name="Zip" value="<?php echo $address['ZIP5'] ?>" />
            <input type="hidden" name="PageTitle" value="<?php echo  $strPageTitle ?>" />
            <input type="hidden" name="TxnsTitle" value="" />
            <input type="hidden" name="TxnsNumCaption" value="" />
            <input type="hidden" name="TxnsAmountCaption" value="" />
            <input type="hidden" name="CONFeeCaption" value="" />
            <input type="hidden" name="TotalAmountCaption" value="" />
            <input type="hidden" name="FinishButtonCaption" value="" />
            <input type="hidden" name="TotalAttempts" value="" />
            <input type="hidden" name="UsedAttempts" value="" />
            <input type="hidden" name="ContactPhone" value="" />
            <input type="hidden" name="PromptCancel" value="<?php echo  $strPromptCancel ?>" />
            <input type="hidden" name="ShowTransNo" value="<?php echo  $strShowTransNo ?>" />
            <input type="hidden" name="CanSaveCard" value="No" />
            <input type="hidden" name="CustomerID" value="<?php echo  $strCustomerID ?>" />
            <input type="hidden" name="CustomerName" value="<?php echo  $strCustomerName ?>" />
            <input type="hidden" name="PONum" value="" />
            <input type="hidden" name="InvoiceNo" value="" />
            <input type="hidden" name="FirstName" value="<?php echo $strCustomerFirstName ?>" />
            <input type="hidden" name="LastName" value="<?php echo $strCustomerLastName ?>" />
            <input type="hidden" name="TaxAmount" value="" />
            <input type="hidden" name="CustomerFirstName" value="<?php echo  $strCustomerFirstName ?>" />
            <input type="hidden" name="CustomerLastName" value="<?php echo  $strCustomerLastName ?>" />
            <input type="hidden" name="CustomerStreet2" value="<?php echo $address['Address2'] ?>" />
            <input type="hidden" name="CustomerStreet3" value="" />
            <input type="hidden" name="CustomerEmail" value="" />
            <input type="hidden" name="CustomerDayPhone" value="" />
            <input type="hidden" name="CustomerNightPhone" value="" />
            <input type="hidden" name="CustomerMobile" value="" />
            <input type="hidden" name="CustomerFax" value="" />
            <input type="hidden" name="CustomerProvince" value="" />
            <input type="hidden" name="CustomerCountryID" value="" />
        </form>
    </div>
</body>
<script type="text/javascript">
    window.onload=function()
    {
	    document.forms[0].action = "<?php echo $strRedirectURL ?>";
	    document.forms[0].submit();
    }    
</script>
</html>
<?php chdir('xhr/'); ?>