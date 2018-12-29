<?php
	include_once 'include/user.php';
    include_once 'include/address.php';
	include_once 'include/destinations.php';
	include_once 'include/public_destination_selector.php';	
	redirect_if_not_logged_in();
	
	$franchise = get_current_user_franchise();
	
	if(!user_has_role(get_affected_user_id(),$franchise, 'Rider'))
		die("<script type='text/javascript'>self.close();</script>");
?>
<html>
<head>
	<title>Add a New Destination</title>
</head>
<body>
<h2><center>New Destination</center></h2>
<script src="<?php echo site_url(); ?>js/mootools.js" type="text/javascript"></script>
<center><span id="error"></span></center>
<form id="form" action="xhr/create_destination.php" method="post">
	<table style="margin:auto;">
		<tr>
			<td style="padding-left:4px;" colspan="2">
				*Destination Name<br>
				<input type="text" name="Destination" value="<?php echo $_POST['Destination']; ?>" style="width:250px;">
			</td>
		</tr>
		<tr>
			<td style="padding-left:4px;" colspan="2">
				Destination Details<br>
				<input type="text" name="DestinationDetail" value="<?php echo $_POST['DestinationDetail']; ?>" style="width:250px;">
			</td>
		</tr>
		<tr>
			<td style="padding-left:4px;" colspan="2">
				Destination Type<br>
				<select name="DestinationGroup" size=1>
					<option value=-1></option>
				<?php
				$sql = "select DestinationGroupID, Name from destination_group order by Name";
				$r = mysql_query($sql);
				while($rs = mysql_fetch_array($r))
					echo "<option value=$rs[DestinationGroupID] "
						.($place["DestinationGroupID"] == $rs["DestinationGroupID"] ? "selected" : "")
						.">$rs[Name]</option>\n";
				
				?>	
				</select>
			</td>			
		</tr>
		<tr>
			<td style="padding-left:4px;" colspan="2">
				Destination Phone Number:&nbsp;&nbsp;Ext:<br/>
				<input type="text" name="DestinationPhone" value="<?php echo $_POST['DestinationPhone']; ?>"  style="width:180px;" />
				<input type="text" name="DestinationPhoneExt" value="<?php echo $_POST['DestinationPhoneExt'] ?>" style="width: 66px;">
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<?php
					create_html_address_table();
				?>
			</td>
		</tr>
		<tr>
			<td>Share This Location <input type="checkbox" name="Public"></td>
			<td class="alignright"><input type="button" onClick="$('form').send();" value="Add Address"></td>
		</tr>
	</table>
</form>
</body>
</html>
<script type="text/javascript">
	$('form').set('send', {
		onSuccess: function(responseText){
			
			var myJSON = JSON.decode(responseText);
			if(myJSON.error == 0){
				notifyOpener(myJSON.destinationID, myJSON.destinationName, myJSON.destinationAddress);
			} else {
				$('error').innerHTML = myJSON.errorMessage;
			}
		}
	});
	
	function notifyOpener(DestinationID, DestinationName, DestinationAddress) {
		if(self.opener || !self.opener.popupWin) {
			self.opener.new_destination(DestinationID, DestinationName, DestinationAddress);
        }
		window.close();
	}
</script>
