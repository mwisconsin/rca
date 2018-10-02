<?php
	include_once 'include/user.php';
	include_once 'include/name.php';
	include_once 'include/email.php';
	include_once 'include/address.php';
	include_once 'include/db_donation.php';
	
	
	redirect_if_not_logged_in();
	require_once 'include/franchise.php';
	
	$franchise = get_current_user_franchise();
	
	if(current_user_has_role(1, 'FullAdmin') && current_user_has_role($franchise, "Franchisee")){
		header("Location: home.php");
		die();	
	}
	
	if(isset($_REQUEST['donationid']) && isset($_REQUEST['action'])){
		$donation_id = mysql_real_escape_string($_REQUEST['donationid']);
		$safe_franchise_id = mysql_real_escape_string($franchise);
		if($_REQUEST['action'] == "Payment"){
			$sql = "UPDATE `donation` SET `PaymentReceived` = 'Y' WHERE FranchiseID = $safe_franchise_id AND `DonationID` = $donation_id LIMIT 1 ;";
		} else if($_REQUEST['action'] == "Thanked"){
			$sql = "UPDATE `donation` SET `DonorThanked` = 'Y' WHERE FranchiseID = $safe_franchise_id AND `DonationID` = $donation_id LIMIT 1 ;";
		} else if($_REQUEST['action'] == "Cancel"){
			$sql = "UPDATE `donation` SET `DonationTime` = '0000-00-00' WHERE FranchiseID = $safe_franchise_id AND `DonationID` = $donation_id LIMIT 1 ;";
		} else if($_REQUEST['action'] == "Reinstate"){
			$date = date("Y-m-d");
			$sql = "UPDATE `donation` SET `DonationTime` = '$date' WHERE FranchiseID = $safe_franchise_id AND `DonationID` = $donation_id LIMIT 1 ;";
		}  else if($_REQUEST['action'] == "emailthank"){
			$sql = "UPDATE `donation` SET `DonorThanked` = 'Y' WHERE FranchiseID = $safe_franchise_id AND `DonationID` = $donation_id LIMIT 1 ;";
			$email_sql = "SELECT DonorEmailID, DonorNameID, DonationCents FROM donation WHERE FranchiseID = $safe_franchise_id AND `DonationID` = $donation_id LIMIT 1";
			$result = mysql_query($email_sql) or die(mysql_error());
			
			$link = site_url() . "documents/donation_thank_you.php?id=$donation_id&hash=" . get_donation_hash($donation_id);
			
			if($result){
				$result = mysql_fetch_array( $result );
				$email = get_email_address( $result['DonorEmailID'] );
				$name = get_name($result[DonorNameID]);
				
				$message = "Dear {$name['FirstName']} {$name['LastName']}, \n";
				$message .= "\nThank you for your recent gift in support of Riders Club of America.\n";
				$message .= "\nYour gift is greatly appreciated and does make a difference in strengthening 
							   the services we provide to those in need of transportation.\n";
				$message .= "\nGifts from friends of Riders Club of America are increasingly essential for 
							   helping us fulfill our mission. Much of our reputation has depended on, and 
							   will more and more depend on private gifts and your own valued participation. 
							   Again, Thank you.\n";
				$message .= "\nPlease download and print off this PDF for your records.\n$link\n";
				$message .= "\nSincerely,\n";
				$message .= "\nJim Balvanz\n";
				$message .= "\nTreasurer.\n";
				mail($email['EmailAddress'],'Riders Club of America - Donation',$message, DEFAULT_EMAIL_FROM);
			}
			
		} else if($_REQUEST['action'] == "CheckNumber" && isset($_REQUEST['checknumber'])){
			$safe_check_number = mysql_real_escape_string($_REQUEST['checknumber']);
			$sql = "UPDATE `donation` SET `CheckNumber` = '$safe_check_number' WHERE `DonationID` = $donation_id LIMIT 1 ;";
		}
		if($_REQUEST['action'] == "emailremind"){
			$email_sql = "SELECT DonorEmailID, DonorNameID, DonationCents FROM donation WHERE `DonationID` = $donation_id LIMIT 1";
			$result = mysql_query($email_sql);
			$link = site_url() . "documents/donation_reminder.php?id=$donation_id&hash=" . get_donation_hash($donation_id);
			if($result){
				$email = mysql_fetch_array( $result );
				$email = get_email_address( $email['DonorEmailID'] );
				
				$message .= "\nThank you for indicating your desire to make a donation to Riders Club of America, in the amount 
							   of $" . ($result['DonationCents'] / 100) . ".00. By offering to support our efforts, you are 
							   changing the lives of others, and giving them back their sense of independence in transportation.\n"; 
				$message .= "\nGifts from friends of Riders Club of America are increasingly essential for helping us fulfill our 
							   mission. Much of our reputation has depended on, and will more and more depend on private gifts and 
							   your own valued participation. Again, Thank you.\n";
				$message .= "\nYour gift is greatly appreciated and does make a difference in strengthening the services we provide 
							   to those in need of transportation.\n";
				$message .= "\nPlease send your contribution to:\n
							 \nRiders Club of America\n222 Third Avenue SE\nSuite 220\nCedar Rapids, IA 52401-1524\n";
				$message .= "\nPlease download and print off this PDF for your records.\n$link\n";
				$message .= "\n\nSincerely,\n
							 \nMartin Wissenberg\nExecutive Director\nRiders Club of America";
				mail($email['EmailAddress'],'Riders Club of America - Donation',$message, DEFAULT_EMAIL_FROM);
			}
			
		} else{
			mysql_query($sql) or die(mysql_error());
		}
		
	}
	
	
	include_once 'include/header.php';
	
	if($_GET['type'] == 'completed')
	{
?>
<h2><center>Completed Donations</center></h2>

<table width="100%" border="1px">
	<tr>
		<td>Name</td>
		<td>Email</td>
		<td>address</td>
		<td>Donation Type</td>
		<td>Method</td>
		<td>Amount</td>
		<td>#</td>
		<td>Status</td>
	</tr>
	<?php
		$safe_franchise_id = mysql_real_escape_string($franchise);
		$sql = "SELECT * FROM donation WHERE FranchiseID = $safe_franchise_id AND PaymentReceived = 'Y' AND DonorThanked = 'Y' AND `DonationTime` != '0000-00-00';";
		$result = mysql_query($sql) or die($sql);
		
		while($row = mysql_fetch_array($result)){
			$name = get_name($row['DonorNameID']);
			$email = get_email_address($row['DonorEmailID']);
			$address = get_address($row['DonorAddressID']);
			?>
			<tr>
				<td><?php echo "{$name['FirstName']} {$name['LastName']}"; ?></td>
				<td><?php echo $email['EmailAddress']; ?></td>
				<td><?php create_html_display_address($address['AddressID']); ?></td>
				<td><?php if($row['DonationType'] == 'RCAGENERAL') echo 'RCA'; else echo 'RSF'; ?></td>
				<td><?php echo $row['PaymentType']; ?></td>
				<td><?php echo ($row['DonationCents'] / 100) . ".00"; ?></td>
				<td><?php echo $row['CheckNumber']; ?></td>
				<td>Completed</td>
			</tr>
			<?php
		}
	?>	
</table>
<?php
	} else if($_GET['type'] == 'pending'){
?>
<script type="text/javascript">
	var $DonationID;
	var $Action;
	function verify($what, $id){
		$('verify').setStyle('display','');
		$DonationID = $id;
		$Action = $what;
		if($what == 'Payment'){
			$('verify_question').innerHTML = "Are you sure payment has been recieved?";
			$('actionButton').value = 'Yes';
		} else if($what == 'Thanked'){
			$('verify_question').innerHTML = "Are you sure donor has been thanked?";
			$('actionButton').value = 'Yes';
		} else if($what == 'Cancel'){
			$('verify_question').innerHTML = "Are you sure you want to cancel this donation?";
			$('actionButton').value = 'Yes';
		} else if($what == 'CheckNumber'){
			$('verify_question').innerHTML = 'Please enter the check Number: <input type="text" id="CheckNumber" size="3">';
			$('actionButton').value = 'Set Check Number';
		}
			
	}
	function cancelVerify(){
		$('verify').setStyle('display','none');
	}
	function action(){
		var myRequest = new Request({
			url: 'donations.php?type=pending',
			onSuccess: function(){
					location.reload(true);
				}
		});
		
		if($Action == 'CheckNumber'){
			var checknumber = '&checknumber=' + $('CheckNumber').value;
		} else var checknumber = '';
 
		myRequest.send({
    		method: 'post',
    		data: 'donationid=' + $DonationID + '&action=' + $Action + checknumber
		});


	}
</script>
<h2><center>Pending Donations</center></h2><br>
<table style="margin:auto; display:none;" id="verify">
	<tr>
		<td colspan="2" id="verify_question">Are you sure payment has been recieved?</td>
	</tr>
	<tr>
		<td><input type="button" onclick="cancelVerify();" value="Cancel"></td>
		<td class="alignright"><input id="actionButton" type="button" onclick="action();" value="Yes"></td>
	</tr>
	
</table><br>
<table width="100%" border="1px">
	<tr>
		<td>Name</td>
		<td>Email</td>
		<td>address</td>
		<td>Donation Type</td>
		<td>Method</td>
		<td>Amount</td>
		<td>#</td>
		<td>Status</td>
		<td>Action</td>
	</tr>
	<?php
	$safe_franchise_id = mysql_real_escape_string($franchise);
		$sql = "SELECT * FROM donation WHERE FranchiseID = $safe_franchise_id AND PaymentReceived = 'N' OR DonorThanked = 'N' AND `DonationTime` != '0000-00-00';";
		$result = mysql_query($sql);
		
		while($row = mysql_fetch_array($result)){
			$name = get_name($row['DonorNameID']);
			$email = get_email_address($row['DonorEmailID']);
			$address = get_address($row['DonorAddressID']);
			if($row['PaymentReceived'] == 'N'){
				$status = "Waiting For Payment<br>";
				if($row['DonorEmailID'] != NULL)
					$status .= "<a href=\"?type=pending&donationid={$row['DonationID']}&action=emailremind\"><button>Email-Remind</button></a>";
				$status .= "<a href=\"documents/donation_reminder.php?id={$row['DonationID']}\"><button>Remind</button></a>";
				$verify = "Payment";
			} else if($row['DonorThanked'] == 'N'){
				$status = "Needs Thanking<br>";
				if($row['DonorEmailID'] != NULL)
					$status .= "<a href=\"?type=pending&donationid={$row['DonationID']}&action=emailthank\"><button>Email-Thank</button></a>";
				$status .= "<a href=\"documents/donation_thank_you.php?id={$row['DonationID']}\"><button>Thank</button></a>";
				$verify = "Thanked";
			}
	?>
			<tr>
				<td><?php echo "{$name['FirstName']} {$name['LastName']}"; ?></td>
				<td><?php echo $email['EmailAddress']; ?></td>
				<td><?php create_html_display_address($address['AddressID']); ?></td>
				<td><?php if($row['DonationType'] == 'RCAGENERAL') echo 'RCA'; else echo 'RSF'; ?></td>
				<td><?php echo $row['PaymentType']; ?></td>
				<td><?php echo ($row['DonationCents'] / 100) . ".00"; ?></td>
				<td><?php echo $row['CheckNumber']; ?></td>
				<td>
					<?php echo $status; ?>
				</td>
				<td>
					<button onclick="verify('<?php echo $verify; ?>','<?php echo $row['DonationID']; ?>');"><?php if($verify == 'Payment') echo "Payment Received"; else echo "Thank You Sent"; ?></button><br>
					<button type="button" onclick="verify('CheckNumber','<?php echo $row['DonationID']; ?>');">Set Check Number</button><br>
					<button type="button" onclick="verify('Cancel','<?php echo $row['DonationID']; ?>');">Cancel</button>
				</td>
			</tr>
			<?php
		}
	?>
</table>
<?php
	} else if($_GET['type'] == 'canceled'){
?>
<script type="text/javascript">
	var $DonationID;
	var $Action;
	function verify($what, $id){
		$('verify').setStyle('display','');
		$DonationID = $id;
		$Action = $what;
		$('verify_question').innerHTML = "Are you sure you want to reinstate this donation?";
	}
	function cancelVerify(){
		$('verify').setStyle('display','none');
	}
	function action(){
		var myRequest = new Request({
			url: 'donations.php?type=pending',
			onSuccess: function(){
					location.reload(true);
				}
		});
 
		myRequest.send({
    		method: 'post',
    		data: 'donationid=' + $DonationID + '&action=' + $Action
		});


	}
</script>
<h2><center>Canceled Donations</center></h2><br>
<table style="margin:auto; display:none;" id="verify">
	<tr>
		<td colspan="2" id="verify_question">Are you sure payment has been recieved?</td>
	</tr>
	<tr>
		<td><input type="button" onclick="cancelVerify();" value="No"></td>
		<td class="alignright"><input type="button" onclick="action();" value="Yes"></td>
	</tr>
	
</table><br>
<table width="100%" border="1px">
	<tr>
		<td>Name</td>
		<td>Email</td>
		<td>address</td>
		<td>Donation Type</td>
		<td>Method</td>
		<td>Amount</td>
		<td>Status</td>
		<td>Action</td>
	</tr>
	<?php
		$sql = "SELECT * FROM donation WHERE FranchiseID = $safe_franchise_id AND `DonationTime` = '0000-00-00';";
		$result = mysql_query($sql);
		
		while($row = mysql_fetch_array($result)){
			$name = get_name($row['DonorNameID']);
			$email = get_email_address($row['DonorEmailID']);
			$address = get_address($row['DonorAddressID']);
			if($row['PaymentReceived'] == 'N'){
				$status = "Waiting For Payment";
			} else if($row['DonorThanked'] == 'N'){
				$status = "Needs Thanking";
			}
	?>
			<tr>
				<td><?php echo "{$name['FirstName']} {$name['LastName']}"; ?></td>
				<td><?php echo $email['EmailAddress']; ?></td>
				<td><?php create_html_display_address($address['AddressID']); ?></td>
				<td><?php if($row['DonationType'] == 'RCAGENERAL') echo 'RCA'; else echo 'RSF'; ?></td>
				<td><?php echo $row['PaymentType']; ?></td>
				<td><?php echo ($row['DonationCents'] / 100) . ".00"; ?></td>
				<td>
					<?php echo $status; ?>
				</td>
				<td>
					<button onclick="verify('Reinstate','<?php echo $row['DonationID']; ?>');">Reinstate</button><br>
				</td>
			</tr>
			<?php
		}
	?>
</table>
<?php
	}
?>
Key: RCA = Riders Club of America | RSF = Ridership Fund | # = Check Number
<?php
	include_once 'include/footer.php';
?>
