<?php
	require_once('include/user.php');
	redirect_if_not_logged_in();
	require_once('include/business_partners.php');
	require_once('include/html_util.php');
	
	$franchise_id = get_current_user_franchise();
	
    if(!current_user_has_role(1 , "FullAdmin") && !current_user_has_role($franchise, "Franchisee")){
		header("Location: home.php");
		die();	
	}

    if ($_POST['AddPartner']) {
        $partner_id = add_business_partner($franchise_id, $_POST['PartnerName']);
        if (!$partner_id) {
            $error_string[] = 'Could not add business partner';
        } else {
            header('Location:  admin_business_partners.php');
        }
    }

    if ($_POST['AddTerms'] && $_GET['bpid']) {
        $required_fields = array('StartDate', 'EndDate', 'TravelType', 'PaymentType', 'PaymentDetails');
        list($all_filled, $missing_fields) = check_required_fields($required_fields);

		if (!$all_filled) {
			$error_string[] = 'Missing fields required to add terms: ' . implode(', ', $missing_fields);
        } else {
            if (add_or_edit_business_partner_terms($_GET['bpid'], $_POST['StartDate'], $_POST['EndDate'], 
                                                   $_POST['TravelType'], $_POST['PaymentType'],
                                                   $_POST['PaymentDetails'])) {
                $error_string[] = 'ADDED requested terms';
                header("Location:  admin_business_partners.php?bpid={$_GET['bpid']}");
            } else {
                $error_string[] = 'Could not add requested terms.  Unknown error.';
            }
        }
    } elseif ($_POST['AddTerms']) {
        $error_string[] = 'Partner ID was not part of the URL.';
    }

    global $ADDITIONAL_RC_JAVASCRIPT;
    if (!is_array($ADDITIONAL_RC_JAVASCRIPT)) {
        $ADDITIONAL_RC_JAVASCRIPT = array();
    }
    $ADDITIONAL_RC_JAVASCRIPT[] = 'datepicker.js';
    include_once('include/header.php');
?>

<h2 class="PageTitle">Business Partners</h2>
<?php if (is_array($error_string)) { 
        foreach ($error_string as $err) { ?>
    <h4 style="color: red"><?php echo $err ?></h4>
<?php }
}
    if ($_GET['bpid']) {
        admin_one_business_partner($_GET['bpid'], $_GET['sd']);
    } else {
        admin_all_business_partners($franchise_id);
    }

	include_once 'include/footer.php';

?>
<?php 

function admin_all_business_partners($franchise_id) { 
    $partners = get_business_partners($franchise_id);
?>
<h3><?php echo count($partners) ?> Request<?php if (count($partners) != 1) { echo 's'; } ?> Found</h3>

<table border="1"><tr><th>Partner ID</th><th>Partner Name</th></tr>
<?php
    if (count($partners)) {
        foreach ($partners as $partner) {
            $bp_id = $partner['BusinessPartnerID'];
            echo "<tr><td>{$bp_id}</td><td>" .
                 "<a href=\"admin_business_partners.php?bpid={$bp_id}\">{$partner['Name']}</a></td>" .
                 "<td><a href=\"business_partner_invoice.php?id={$bp_id}\">Invoice</a></td></tr>";
        }
    }
?>
</table>

<h3>Add Business Partner</h3>
<form action="" method="POST">
    Name: <input type="text" name="PartnerName" size="20" /><br />
    <input type="Submit" name="AddPartner" value="Add Partner" />
</form>

<?php

}


function admin_one_business_partner($bp_id, $start_date = NULL) {
    $partner_record = get_business_partner_record($bp_id);
    $terms = get_business_partner_active_terms($bp_id);

    $travel_types = array('TO_ONLY' => 'To', 'FROM_ONLY' => 'From', 'TO_AND_FROM' => 'To and From');
    $payment_types = array('PERCENTAGE' => 'Percentage', 'FLAT_AMOUNT' => 'Flat Amount');

    if (!is_null($start_date)) {
        $terms = get_business_partner_terms_on_date($bp_id, $start_date);
    } else {
        $terms = array( 'StartDate' => $_POST['StartDate'],
                        'EndDate' => $_POST['EndDate'],
                        'TravelType' => $_POST['TravelType'],
                        'PaymentType' => $_POST['PaymentType'],
                        'PaymentDetails' => $_POST['PaymentDetails'] );
    }
    

    echo "<h3>Business Partner:  {$partner_record['Name']}</h3>"; 
?>
<h4>Add New Terms</h4>
<form action="" method="POST">
    Start Date: <input class=jq_datepicker type="text" name="StartDate" id="StartDate" size="8" value="<?php 
                                                            echo $terms['StartDate'] ?>" /><br />
    End Date:  <input class=jq_datepicker type="text" name="EndDate" id="EndDate" size="8" value="<?php
                                                            echo $terms['EndDate'] ?>" /><br />
    Travel Type: <select name="TravelType"><?php echo create_options_from_array($travel_types, $terms['TravelType']);
                        ?></select><br />
    Payment Type: <select name="PaymentType"><?php echo create_options_from_array($payment_types, $terms['PaymentType']);
                        ?></select><br />
    Payment Details: <input type="text" name="PaymentDetails" size="3" value="<?php
                                                            echo $terms['PaymentDetails'] 
        ?>" /> (percentage or number of cents)<br />
    <input type="submit" name="AddTerms" value="Add Terms" /><br />
    <script type="text/javascript">
    // <![CDATA[  
//        var startOpts = {                            
//                formElements:{"StartDate":"Y-ds-n-ds-j"},
//                statusFormat:"l-cc-sp-d-sp-F-sp-Y"
//            };           
//        var endOpts = {                            
//                formElements:{"EndDate":"Y-ds-n-ds-j"},
//                statusFormat:"l-cc-sp-d-sp-F-sp-Y"
//            };           
//        datePickerController.createDatePicker(startOpts);
//        datePickerController.createDatePicker(endOpts);

    // ]]>
    </script>
</form>
<h4>Current Terms</h4>
<?php
    $active_terms = get_business_partner_active_terms($bp_id);
    if ($active_terms && count($active_terms) > 0) { ?>
    <table border="1">
        <tr><th>&nbsp;</th><th>Start Date</th><th>End Date</th><th>Travel Type</th><th>Payment Type</th><th>Payment Details</th></tr>
<?php
        foreach ($active_terms as $term) {
            echo "<tr><td><a href=\"admin_business_partners.php?bpid=$bp_id&sd={$term['StartDate']}\">" .
                 "edit</a></td><td>{$term['StartDate']}</td><td>{$term['EndDate']}</td>" .
                 "<td>{$term['TravelType']}</td><td>{$term['PaymentType']}</td>" .
                 "<td>{$term['PaymentDetails']}</td></tr>";
        }
        echo '</table>';
    } else {
        echo '<p>No active terms found</p>';
    }
?>
<h4>Past (Expired) Terms</h4>
<?php
    $past_terms = get_business_partner_past_terms($bp_id);
    if ($past_terms && count($past_terms) > 0) { ?>
    <table border="1">
        <tr><th>Start Date</th><th>End Date</th><th>Travel Type</th><th>Payment Type</th><th>Payment Details</th></tr>
<?php
        foreach ($past_terms as $term) {
            echo "<tr><td>{$term['StartDate']}</td><td>{$term['EndDate']}</td>" .
                 "<td>{$term['TravelType']}</td><td>{$term['PaymentType']}</td>" .
                 "<td>{$term['PaymentDetails']}</td></tr>";
        }
        echo '</table>';
    } else {
        echo '<p>No past terms found</p>';
    }
?>
<br />
<a href="admin_business_partners.php">Back to All Business Partners</a>
<?php
}
?>
