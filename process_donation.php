<?php
    include_once 'include/header.php';
	session_start();
	if($_GET['type'] == 'credit')
	{
?>
<iframe src="hps/donor_checkout_page.php" width="750" height="900" />
<?php
	} else {
		?>
		<center><h2>Thank You!</h2></center>
		Thank You for donating! Since you have selected to send in your money, please send it to the address Below.<br>
		<center>
			Riders Club of America<br>
			1700 B Ave NE #213<br>
			Cedar Rapids, IA 52402
		</center>
		<?
	}
    include_once 'include/footer.php';
?>
