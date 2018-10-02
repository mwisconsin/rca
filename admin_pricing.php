<?php
	include_once 'include/user.php';
	include_once 'include/link_price.php';
	redirect_if_not_logged_in();
	
	$franchise = get_current_user_franchise();
	
	if(!current_user_has_role(1 , "FullAdmin") && !current_user_has_role($franchise, "Franchisee")){
		header("Location: home.php");
		die();	
	}
	
	if($_POST['RiderPerMileCents'] && $_POST['DriverPerMileCents'] && $_POST['RiderPerHourWaitCents'] && $_POST['DriverPerHourWaitCents']){
		$date = "{$_POST['OOACardYear']}-{$_POST['OOACardMonth']}-{$_POST['OOACardDay']}";
		$rtn = set_new_out_of_area_rate_card($franchise, $_POST['RiderPerMileCents'], $_POST['DriverPerMileCents'], $_POST['RiderPerHourWaitCents'], $_POST['DriverPerHourWaitCents'], $date);
		if($rtn)
			$announce = "A new out of area rate card has been put into place as of now.";
		else
			$announce = "The system has failed to put a new out of area card into place.";
	}
	if(!$_POST['EditRateCard'] && $_POST['Distance'] && $_POST['Price']){
	   $dist_price = array();
	   for($i = 0; $i < count($_POST['Distance']); $i++){
			$dist_price[] = array('Price' => $_POST['Price'][$i],
								  'Distance' => $_POST['Distance'][$i]);
		}
		$date = "{$_POST['NewCardYear']}-{$_POST['NewCardMonth']}-{$_POST['NewCardDay']}";
		$rtn = set_new_rate_card($franchise, $date,$dist_price);
		if($rtn)
			$announce = "A new rate card has been put into place as of now.";
        else
			$announce = "The system has failed to put a new card into place.";
	}
	if($_POST['Delete']){
	   $effective = array_keys($_POST['Delete']);
	   $replaced = array_keys($_POST['Delete'][$effective[0]]);
	   
	   $announce = "<form method=\"post\">Are you sure you want to delete this rate card? <input type=\"submit\" value=\"Delete\" name=\"DeleteConfirmed[{$effective[0]}][{$replaced[0]}]\">";
    }
    if($_POST['DeleteOOA']){
	   $effective = array_keys($_POST['DeleteOOA']);
	   $replaced = array_keys($_POST['DeleteOOA'][$effective[0]]);
	   
	   $announce = "<form method=\"post\">Are you sure you want to delete this out of area rate card? <input type=\"submit\" value=\"Delete\" name=\"DeleteOOAConfirmed[{$effective[0]}][{$replaced[0]}]\">";
    }
	
	if($_POST['DeleteOOAConfirmed']){
	   $effective = array_keys($_POST['DeleteOOAConfirmed']);
	   $replaced = array_keys($_POST['DeleteOOAConfirmed'][$effective[0]]);
	   $replaced[0] = $replaced[0] == 'null' ? NULL : $replaced[0];
	   $result = delete_out_of_area_rate_card($franchise, $effective[0], $replaced[0]);
	   
	   if($result)
	       $announce = "The out of area rate card has been deleted.";
	   else
	       $announce = "The system has failed to delete the out of area rate card.";
	}
	if($_POST['DeleteConfirmed']){
	   $effective = array_keys($_POST['DeleteConfirmed']);
	   $replaced = array_keys($_POST['DeleteConfirmed'][$effective[0]]);
	   $replaced[0] = $replaced[0] == 'null' ? NULL : $replaced[0];
	   $result = delete_rate_card($franchise, $effective[0], $replaced[0]);
	   
	   if($result)
	       $announce = "The rate card has been deleted.";
	   else
	       $announce = "The system has failed to delete the rate card.";
	}
	
	if($_POST['Edit'] || $_POST['EditOOA']){
        $effective = $_POST['Edit'] ?  array_keys($_POST['Edit']) : array_keys($_POST['EditOOA']);
        $replaced = $_POST['Edit'] ?  array_keys($_POST['Edit'][$effective[0]]) : array_keys($_POST['EditOOA'][$effective[0]]);
        $replaced[0] = $replaced[0] == 'null' ? NULL : $replaced[0];
	}
	
	if($_POST['EditRateCard']){
	   $dist_price = array();
	   for($i = 0; $i < count($_POST['Distance']); $i++){
			$dist_price[] = array('Price' => $_POST['Price'][$i],
								  'Distance' => $_POST['Distance'][$i]);
		}
		$effective = $_POST['effective_date'];
		$replaced = $_POST['replaced_date'] == '' ? NULL : $_POST['replaced_date'];
		$rtn = edit_rate_card($franchise, $effective, $replaced, $dist_price);
		if($rtn)
			$announce = "The rate card was correctly edited.";
        else
			$announce = "The system has failed to edit the card.";
	}
	if($_POST['EditOOARateCard']){
	   $dist_price = array();
	   for($i = 0; $i < count($_POST['Distance']); $i++){
			$dist_price[] = array('Price' => $_POST['Price'][$i],
								  'Distance' => $_POST['Distance'][$i]);
		}
		$effective = $_POST['effective_date'];
		$replaced = $_POST['replaced_date'] == '' ? NULL : $_POST['replaced_date'];
		$rtn = edit_out_of_area_rate_card($franchise, $effective, $replaced, $_POST['RiderPerMileCents'], $_POST['DriverPerMileCents'], $_POST['RiderPerHourWaitCents'], $_POST['DriverPerHourWaitCents']);
		if($rtn)
			$announce = "The out of area rate card was correctly edited.";
        else
			$announce = "The system has failed to edit the card.";
	}
	
	include_once 'include/header.php';
?>
	<center><h2>Rate Cards</h2></center>
	<?php if($announce){ echo "<div class=\"reminder\">$announce</div>"; } ?>
	<div style="float:right; margin-top: -20px;">
		<?php if(!$_POST['EditOOA']){ ?>
		<h3>New Out Of Area Rate Cards</h3>
		<?php } else { ?>
		<h3>Edit Out Of Area Rate Cards</h3>
		<?php 
		$card = get_out_of_area_rate_card($franchise, $effective[0],$replaced[0]);
		} ?> 
		<form method="post">
			<table id="newOOARateCardTable" border="1" width="360px">
				<?php if(!$_POST['EditOOA']){ ?>
				<tr>
					<td colspan="4">Effective: <?php get_date_drop_downs('OOACard'); ?></td>
				</tr>
				<?php } else { ?>
					<input type="hidden" name="effective_date" value="<?php echo $effective[0]; ?>">
	    			<input type="hidden" name="replaced_date" value="<?php echo $replaced[0]; ?>">
				<?php } ?>
				<tr>
					<td>Rider Cost(per mile)</td>
					<td>Driver payout(per mile)</td>
					<td>Rider Waiting Cost(hourly)</td>
					<td>Driver Waiting Payout(hourly)</td>
				</tr>
				<tr>
					<td><input type="text" name="RiderPerMileCents" value="<?php echo $card['RiderPerMileCents']; ?>" size="10"></td>
					<td><input type="text" name="DriverPerMileCents" value="<?php echo $card['DriverPerMileCents']; ?>" size="10"></td>
					<td><input type="text" name="RiderPerHourWaitCents" value="<?php echo $card['RiderPerHourWaitCents']; ?>" size="10"></td>
					<td><input type="text" name="DriverPerHourWaitCents" value="<?php echo $card['DriverPerHourWaitCents']; ?>" size="10"></td>
				</tr>
			</table>
			<?php if(!$_POST['EditOOA']){ ?>
			<input type="submit" name="NewOOARateCard" value="Commit New Pricing">
			<?php } else { ?>
			<input type="submit" name="EditOOARateCard" value="Commit Edited Pricing">
			<?php } ?>
		</form>
		
		<h3>Out Of Area Cards</h3>
		<form method="post">
		<?php $out_of_area_cards = get_past_out_of_area_rate_cards($franchise);
		$t = "Future Card";
		foreach($out_of_area_cards as $card){ ?>
		<table  width="380px" border="1">
			<tr>
				<td colspan="4">
				<?php
		              if(($card['ReplacedDate'] !== NULL && time() >= strtotime($card['EffectiveDate']) && time() < strtotime($card['ReplacedDate']) ) || ($card['ReplacedDate'] === NULL && strtotime($card['EffectiveDate']) <= time() ) ){
		                  echo "Current Card";
		                  $t = "Past Card";
		              } else
		                  echo $t;
		         ?>
				</td>
			</tr>
			<tr>
				<td>Rider Cost(per mile)</td>
				<td>Driver payout(per mile)</td>
				<td>Rider Waiting Cost(hourly)</td>
				<td>Driver Waiting Payout(hourly)</td>
			</tr>
			<tr>
				<td><?php echo $card['RiderPerMileCents']; ?></td>
				<td><?php echo $card['DriverPerMileCents']; ?></td>
				<td><?php echo $card['RiderPerHourWaitCents']; ?></td>
				<td><?php echo $card['DriverPerHourWaitCents']; ?></td>
			</tr>
			<tr>
				<td>Effective:</td>
				<td><?php echo format_date($card['EffectiveDate'],"n/j/Y"); ?></td>
				<td>Replaced:</td>
				<td><?php echo $card['ReplacedDate'] != null ? format_date($card['ReplacedDate'],"n/j/Y") : 'Not Set'; ?></td>
			</tr>
			<tr>
				<td colspan="4">
					<center>
						<input type="submit" name="EditOOA[<?php echo $card['EffectiveDate'];?>][<?php echo $card['ReplacedDate'] == null ? 'null' : $card['ReplacedDate'];?>]" value="Edit"> 
						<input type="submit" name="DeleteOOA[<?php echo $card['EffectiveDate'];?>][<?php echo $card['ReplacedDate'] ? 'null' : $card['ReplacedDate'];?>]" value="Delete">
					</center>
				</td>
			</tr>
		</table><br>
		<?php } ?>
		</form>
	</div>
    <?php if(!$_POST['Edit']){ ?>
	<h3>New Card</h3>
	<?php } else { ?>
	<h3>Edit Card</h3>
	<?php } ?>
	<form method="post">
	    <?php if($_POST['Edit']){ ?>
	    <input type="hidden" name="effective_date" value="<?php echo $effective[0]; ?>">
	    <input type="hidden" name="replaced_date" value="<?php echo $replaced[0]; ?>">
	    <?php } ?>
	    <input type="button" value="Add Distance" onclick="new_distance()">
		<table id="newRateCardTable" border="1" width="320px">
		    <?php if(!$_POST['Edit']){ ?>
		    <tr>
		      <td colspan="2">Effective: <?php get_date_drop_downs('NewCard'); ?></td>
		    </tr>
		    <?php } ?>
		    <tr>
				<td>Max Distance</td>
				<td>Price(cents)</td>
			</tr>
		    <?php if(!$_POST['Edit']){ ?>
                <tr>
				    <td><input type="text" name="Distance[0]"></td>
				    <td><input type="text" name="Price[0]"></td>
				</tr>
            <?php } else { 
                $rate_cards = get_rate_card($franchise, $effective[0], $replaced[0]);
                $i = 0;
                foreach($rate_cards as $dist_mile){ ?>
                    <tr>
				        <td><input type="text" value="<?php echo $dist_mile['MaxDistance'] ?>" name="Distance[<?php echo $i ?>]"></td>
				        <td><input type="text" value="<?php echo $dist_mile['Cents'] ?>" name="Price[<?php echo $i ?>]"></td>
				    </tr>
                    <?php $i++;
                } ?>
			<?php } ?>
		</table>
		<input type="button" value="Add Distnace" onclick="new_distance()"><?php if(!$_POST['Edit']){ ?><input type="submit" name="NewRateCard" value="Commit New Pricing"><?php } else { ?><input type="submit" name="EditRateCard" value="Commit Revised Pricing"><?php } ?>
	</form>
	<h3>Regular Card</h3>
	<form method="post">
	<?php $cards = get_past_rate_cards($franchise);
	       $t = "Future Card";
			foreach($cards as $card){ ?>
		<table  width="325px" border="1">
		    <tr>
		         <td colspan="2">
		         <?php
		              if(($card[0]['ReplacedDate'] !== NULL && time() >= strtotime($card[0]['EffectiveDate']) && time() < strtotime($card[0]['ReplacedDate']) ) || ($card[0]['ReplacedDate'] === NULL && strtotime($card[0]['EffectiveDate']) <= time() ) ){
		                  echo "Current Card";
		                  $t = "Past Card";
		              } else
		                  echo $t;
		         ?>
		         </td> 
		    </tr>
			<tr>
				<td>Max Distance</td>
				<td>Price(cents)</td>
			</tr>
			    <?php foreach($card as $rate){ ?>
				<tr>
				<td><?php echo $rate['MaxDistance']; ?></td>
				<td><?php echo $rate['Cents']; ?></td>
				</tr>
				<?php } ?>
				<tr>
				    <td>Effective Date:</td>
				    <td><?php echo $card[0]['EffectiveDate']; ?></td>
				</tr>
				<tr>
				    <td width="50%">Replaced Date:</td>
				    <td><?php echo $card[0]['ReplacedDate'] == null ? 'Not Set' : $card[0]['ReplacedDate']; ?></td>
				</tr>
				<tr>
				    <td colspan="2"><center><input type="submit" name="Edit[<?php echo $card[0]['EffectiveDate']; ?>][<?php echo $card[0]['ReplacedDate'] == null ? 'null' : $card[0]['ReplacedDate']; ?>]" value="Edit"> <input type="submit" value="Delete" name="Delete[<?php echo $card[0]['EffectiveDate']; ?>][<?php echo $card[0]['ReplacedDate'] == null ? 'null' : $card[0]['ReplacedDate']; ?>]"></center></td>
				</tr>
			</table><br>
			<?php } ?>
    </form>
	<script type="text/javascript">
		$idx = 0;
		var myTable = new HtmlTable($('newRateCardTable'));
		function new_distance($table){
			$idx++;
			myTable.push(['<input type="text" name="Distance[' + $idx + ']">', '<input type="text" name="Price[' + $idx + ']">']);
		}
		
		function remove_row($row){
		  $row.getParent().destroy();
		}
	</script>
<?php
	include_once 'include/footer.php';
?>