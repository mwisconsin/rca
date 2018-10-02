<?php
    require_once('include/user.php');
    session_start();

	redirect_if_not_logged_in();

    include_once 'include/header.php';

?>
<h1>Riders Club Payment Processing</h1>
<iframe src="hps/CheckoutPageNew.php" width="100%" height="500" />
<?php // TODO:  Try to get client viewport size? ?>

<?php
    include_once 'include/footer.php';
?>
