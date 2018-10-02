<?php
    include_once 'include/header.php';
    include_once 'include/user.php';
    include_once 'include/ledger.php';
    include_once 'include/hps_transactions.php';
	
	redirect_if_not_logged_in();
	
	
    $user_id = get_affected_user_id();
?>
<h1>Add Account Credit</h1>


<?php if (isset($_POST['AddCreditAmount'])) {
    // TODO:  Validate amount as a dollar/cents amount
    // Process the payment with HPS
    echo "<p>Adding {$_POST['AddCreditAmount']}</p>";
    session_start();
    $_SESSION['AddCreditAmount'] = $_POST['AddCreditAmount'];
    // TODO:  Generate a real transaction number
    $_SESSION['TransactionNumber'] = date('mdHis');

    //echo "<p>Adding {$_SESSION['AddCreditAmount']}</p>";
?>
<iframe src="hps/CheckoutPage.php" width="700" height="850" />
<?php // TODO:  Try to get client viewport size? ?>

<?php
} else {  // AddCreditAmount is not in POST
    // TODO:  This is very sloppy for proof of concept/test
?>
<p>Your current balance is $<?php
    $balance = calculate_user_ledger_balance( $user_id );

    echo sprintf("%d.%02d", $balance/100, $balance%100);
?></p>
<form action="<?php $_SERVER['SCRIPT_URI']; ?>" method='POST'>
    Amount to Add: <input name="AddCreditAmount" id="AddCreditAmount" type="text" /><br />
    <input name="Submit" type="submit" value="Submit" />
</form>
<?php 
}
?>


<?php
    include_once 'include/footer.php';
?>
