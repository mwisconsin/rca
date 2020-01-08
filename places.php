<?php
	
    require_once('include/user.php');
    require_once('include/address.php');
    require_once('include/phone.php');
    require_once('include/business_partners.php');
    require_once('include/html_util.php');
	require_once 'include/franchise.php';
	
    redirect_if_not_logged_in();
	$franchise_id = get_current_user_franchise();
	
	if(!current_user_has_role(1, "FullAdmin") && !current_user_has_role($franchise_id, "Franchisee")){
		header("Location: home.php");
		die();
	}
   
	$safe_franchise_id = mysql_real_escape_string( $franchise_id );
	if(isset($_REQUEST['CANCEL']))
	   header("Location: places.php");
	if(isset($_POST['Group'])){
		if($_POST['GroupName'] == '' || $_POST['SubGroup'] == ''){
			$group_error = "You were missing some of the fields.";
		} else {
			$safe_group_name = mysql_real_escape_string($_POST['GroupName']);
			$safe_subgroup = mysql_real_escape_string($_POST['SubGroup']);
			$sql = "INSERT INTO `destination_group` (`DestinationGroupID` ,`ParentGroupID` ,`Name` ,`FranchiseID`)
											 VALUES (NULL , '$safe_subgroup', '$safe_group_name', '$safe_franchise_id');";
			mysql_query($sql) or die(mysql_error());
		}
	}
	if(isset($_POST['Destination'])){
		$check = array('DestinationName','Address1','City','State','Zip5','DestinationGroup');
		
		$destination_error = FALSE;
		
		foreach($check as $k => $v){
			if($_POST[$v] == '' || $_POST[$v] == NULL || $_POST[$v] == 0)
				$destination_error = TRUE;
			
		}
		if(!$destination_error){
			unset($destination_error);
			$address = array('Address1' => $_POST['Address1'],
							 'Address2' => $_POST['Address2'],
							 'City' => $_POST['City'],
							 'State' => $_POST['State'],
							 'ZIP5' => $_POST['Zip5'],
							 'ZIP4' => $_POST['Zip4'],
							 'Longitude' => $_POST['Longitude'],
							 'Latitude' => $_POST['Latitude']);
			$destination_id = create_new_destination($_POST['DestinationName'], $address, $franchise_id,TRUE,TRUE,$_POST['DestinationGroup'],$_POST['DestinationPhone'], $_POST['DestinationDetail']);
			
			$posted_tags = array();
            if (isset($_POST['Tag'])) {
                foreach ($_POST['Tag'] as $posted_tag_id => $posted_tag_value) {
                    if ($posted_tag_value) {
                        $posted_tags[] = array('TagID' => $posted_tag_id,
                                           'TagInfo1' => $_POST['TagInfo1'][$posted_tag_id],
                                           'TagInfo2' => $_POST['TagInfo2'][$posted_tag_id]);
                    }
                }
            }
            if (db_start_transaction()) {
                // Don't want to delete and not add.
                set_destination_tags($destination_id, $posted_tags);
                db_commit_transaction();
            }

		} else {
			$destination_error = "You did not fill in all the required fields.";
		}
	}
	if(isset($_POST['Delete'])){
		if($_POST['Type'] == 'Destination')
			delete_public_destination( $_POST['DestinationID/GroupID'] );
		if($_POST['Type'] == 'Group')
			delete_public_group( $_POST['DestinationID/GroupID'] );
	}
	if(isset($_POST['destination']) ){
		$destination = get_destination($_POST['id']);
		$address = array('Address1' => $_POST['Address1'],
						 'Address2' => $_POST['Address2'],
						 'City' => $_POST['City'],
						 'State' => $_POST['State'],
						 'ZIP5' => $_POST['Zip5'],
						 'ZIP4' => $_POST['Zip4'],
						 'Latitude' => $_POST['Latitude'],
						 'Longitude' => $_POST['Longitude'],
						 'id' => $destination['AddressID']);
		
		if ( $_POST['DestinationGroup'] == 0)
		{
			$update_destination_error = "No Group selected";
			
		} else {
			
			edit_destination(
				$destination['DestinationID'], $_POST['DestinationName'], $address, 
				$destination['FranchiseID'], $_POST['DestinationGroup'],$_POST['DestinationPhone'], 
				$_POST['DestinationDetail'],'NOEDIT');

        	$posted_tags = array();
        	if (isset($_POST['Tag'])) {
            	foreach ($_POST['Tag'] as $posted_tag_id => $posted_tag_value) {
               	 	if ($posted_tag_value) {
                    	$posted_tags[] = array('TagID' => $posted_tag_id,
                                           		'TagInfo1' => $_POST['TagInfo1'][$posted_tag_id],
                                          		 'TagInfo2' => $_POST['TagInfo2'][$posted_tag_id]);
               		 }
           		 }
       		 }
        	if (db_start_transaction()) {
           	 	// Don't want to delete and not add.
            	set_destination_tags($destination['DestinationID'], $posted_tags);
            	db_commit_transaction();
        	}
        	if($_POST['approve'] == 'true'){
            	accept_deny_public_destination($destination['DestinationID'], 'accept', $_POST['DestinationGroup']);
        	}
		}
	}
	if(isset($_GET['Approval']) && $_GET['Approval'] == 'Deny'){
		accept_deny_public_destination($_GET['destination'], 'deny');
		header("Location: places.php");
	}
	if(isset($_POST['EditGroup'])){
		$sql = "UPDATE destination_group SET `ParentGroupID` = '{$_POST['SubGroup']}',
											 `Name` = '{$_POST['GroupName']}' 
				WHERE `DestinationGroupID` = '{$_POST['id']}' LIMIT 1 ;";
		mysql_query($sql);
	}
	include_once 'include/header.php';
?>
<h2><center>Public Places</center></h2>
<?php
	$groups = array();

	function build_destination_group($franchise_id, $parent_id = 0){
		$safe_franchise_id = mysql_real_escape_string( $franchise_id );
		$safe_parent_id = mysql_real_escape_string( $parent_id );
		$sql = "SELECT Name, DestinationGroupID FROM `destination_group` WHERE ParentGroupID = $safe_parent_id AND FranchiseID = $safe_franchise_id ORDER BY Name";
		$result = mysql_query($sql);
		global $groups;
		
		while($row = mysql_fetch_array($result)){
			echo "<li>";
				echo "<span id=\"GROUP{$row['DestinationGroupID']}\">" . $row['Name'] . ' - Group</span> ' . "<a href=\"#\" onclick=\"delete_destination({$row['DestinationGroupID']},'Group')\">Delete</a>";
				$groups[] = 'GROUP' . $row['DestinationGroupID'];
				echo " <a href=\"?editgroup={$row['DestinationGroupID']}\">Edit</a>";
				echo "<ul id=\"GROUP{$row['DestinationGroupID']}_collapse\">";
				build_destination_group($franchise_id,$row['DestinationGroupID']);
				$sql = "SELECT * FROM `destination` WHERE IsPublicApproved = 'Yes' AND IsPublic = 'Yes' AND DestinationGroupID = {$row['DestinationGroupID']} ORDER BY Name";
				$secondResult = mysql_query($sql) or die(mysql_error());
				while($row2 = mysql_fetch_array($secondResult)){
					$phone = get_phone_number($row2['PhoneID']);
					echo '<li title="' . $phone['PhoneNumber'] . '">';
					echo $row2['Name'] . ' - ' . $row2['DestinationDetail'] . ' - Place ' . "<a href=\"#\" onclick=\"delete_destination({$row2['DestinationID']},'Destination')\">Delete</a>";
					echo " <a href=\"?destination={$row2['DestinationID']}\">Edit</a>";
					echo '</li>';
				}
				echo '</ul>';
			echo '</li>';
		}		
	}
	function get_javascript_group_datalist($franchise_id, $parent_id = 0 ){
		$safe_franchise_id = mysql_real_escape_string( $franchise_id );
		$safe_parent_id = mysql_real_escape_string( $parent_id );
		$sql = "SELECT Name, DestinationGroupID FROM `destination_group` WHERE ParentGroupID = $safe_parent_id AND FranchiseID = $safe_franchise_id ORDER BY Name";
		$result = mysql_query($sql);
		$rows = mysql_num_rows($result);
		$count = 0;
		while($row = mysql_fetch_array($result)){
			echo "['" . $row['Name'] . "', [";
			echo get_javascript_group_datalist($franchise_id, $row['DestinationGroupID']);
			echo "] ]";
			$count++;
			if($rows != $count)
				echo ',';
		}
	}
	
?>
<script type="text/javascript">
	
	function delete_destination( public_id, public_type ){
		if(public_type == 'Group'){
			$('Delete').setStyle('display','block');
			$('DestinationID/GroupID').value = public_id;
			$('Type').value = 'Group';
		} else if(public_type == 'Destination'){
			$('Delete').setStyle('display','block');
			$('DestinationID/GroupID').value = public_id;
			$('Type').value = 'Destination';
		}
	}
</script>
<div style="float:right; padding:5px; border-left:1px solid; clear:both;">
	<div id="Delete" style="display:none;">
		<span style="font:1.5em bold;">Delete Public Destination/Group</span><br>
		<br>
		<form method="post">
			<input type="hidden" name="DestinationID/GroupID" id="DestinationID/GroupID">
			<input type="hidden" name="Type" id="Type">
			<table style="margin-left:30px; width:250px;">
				<tr>
					<td colspan="2">Are You Sure you want to delete this?</td>
				</tr>
				<tr>
					<td><input type="button" value="Cancel" onclick="$('Delete').setStyle('display','none');"></td>
					<td class="alignright"><input type="submit" value="Delete" name="Delete"></td>
				</tr>
			</table>
		</form>
		<br>
		<br>
	</div>
<?php if(isset($_GET['editgroup'])){ 
	$sql = "SELECT * FROM destination_group WHERE DestinationGroupID = '{$_GET['editgroup']}' LIMIT 1;";
	$result = mysql_fetch_array( mysql_query( $sql ) );
?>
	<span style="font:1.5em bold;">Edit Public Destination Group</span><br>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
		<input type="hidden" name="id" value="<?php echo $_GET['editgroup']; ?>">
		<table>
			<tr>
				<td colspan="2">Destination Group Name:</td>
			</tr>
			<tr>
				<td colspan="2"><input type="text" name="GroupName" value="<?php echo $result['Name']; ?>" style="width:300px;"></td>
			</tr>
			<tr>
				<td>Group:</td>
				<td>
					<select name="SubGroup">
						<option value="0" <?php if($result['ParentGroupID'] == 0) echo 'SELECTED'; ?> >Main</option>
						<?php
							$sql = "SELECT Name, DestinationGroupID FROM destination_group WHERE DestinationGroupID != {$result['DestinationGroupID']} AND FranchiseID = $safe_franchise_id";
							$result2 = mysql_query($sql);
							while($row = mysql_fetch_array($result2)){
								echo "<option value=\"{$row['DestinationGroupID']}\"";
								if($row['ParentGroupID'] == $result['ParentGroupID'])
									echo " SELECTED ";
								echo ">{$row['Name']}</option>";
							}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td><input type="submit" name="EditGroup" value="Save Group"></td>
				<td><input type="submit" name="CANCEL" value="Cancel"></td>
			</tr>
		</table><br><br>
	</form>

<?php
}
 	if(isset($_GET['destination']) || isset($_GET['editApprove'])){ 
	$destination = get_destination($_GET['destination']);
    $destination_tags = get_destination_tags($destination['DestinationID']);
    $business_partners = get_business_partners($franchise_id);

?>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
		<input type="hidden" name="id" value="<?php echo $_GET['destination']; ?>">
		<?php if($_GET['editApprove']) { ?><input type="hidden" name="approve" value="true"><?php } ?>
		<span style="font:1.5em bold;"><?php if($_GET['editApprove']) { ?>Approve<?php } else {?>Edit<?php }?> Public Destination</span>
		<table>
			<tr>
				<td>Destination Name:</td>
			</tr>
			<tr>
				<td colspan="2"><input type="text" name="DestinationName" value="<?php echo $destination['Name']; ?>" style="width:300px;"></td>
			</tr>
			<tr>
				<td>Destination Detail:</td>
			</tr>
			<tr>
				<td colspan="2"><input type="text" name="DestinationDetail" value="<?php echo $destination['DestinationDetail']; ?>" style="width:300px;"></td>
			</tr>
			<tr>
				<td>Destination Phone Number:</td>
			</tr>
			<tr>
				<td colspan="2"><input type="text" name="DestinationPhone" value="<?php $phone = get_phone_number($destination['PhoneID']); echo $phone['PhoneNumber']; ?>" style="width:300px;"></td>
			</tr>
			<tr>
				<td colspan="2">
					<?php create_html_address_table( NULL, get_address( $destination['AddressID'] ) ); ?>
				</td>
			</tr>
			<tr>
				<td>Group</td>
				<td>
					<?php if($destination['DestinationGroupID'] == NULL && !isset($_GET['editApprove']) ){
						echo 'None<input type="hidden" name="DestinationGroup" value="NULL">';
					} else { ?>
					<select name="DestinationGroup">
					   <option value="0" <?php if($destination['DestinationGroupID'] == 0) echo 'SELECTED'; ?>> --Select Below--</option>
						<?php
							$sql = "SELECT Name, DestinationGroupID FROM destination_group WHERE FranchiseID = $safe_franchise_id ORDER BY Name";
							$result2 = mysql_query($sql);
							while($row = mysql_fetch_array($result2)){
								echo "<option value=\"{$row['DestinationGroupID']}\"";
								if($row['DestinationGroupID'] == $destination['DestinationGroupID'])
									echo " SELECTED ";
								echo ">{$row['Name']}</option>";
							}
						?>
					</select>
					<?php } ?>
				</td>
			</tr>
            <tr><td valign="top">Tags</td>
                <td><?
                    $bp_selects = array();
                    if ($business_partners) {
                        foreach ($business_partners as $partner) {
                            $bp_selects[$partner['BusinessPartnerID']] = $partner['Name'];
                        }
                    }

                    $tags = get_all_destination_tags_alphabetical($safe_franchise_id);
                    if ($tags) {
                        foreach ($tags as $tag_entry) {
                            $checked = (isset($destination_tags[$tag_entry['TagName']])) ? 
                                       ' checked="checked"' : '';       

                            echo "<input type=\"checkbox\" name=\"Tag[{$tag_entry['TagID']}]\" {$checked} />"; 
                            echo $tag_entry['TagName'];
                           
                            if ($tag_entry['TagName'] == 'BUSINESS_PARTNER' && $business_partners) {
                                echo '&nbsp;&nbsp;<select name="TagInfo1[' . $tag_entry['TagID'] . ']">';
                                echo create_options_from_array($bp_selects, 
                                                               (int)($destination_tags[$tag_entry['TagName']]['TagInfo1']));
                                echo '</select>';
                            }
                                        
                            echo '<br />';
                        }
                    } ?>
            </td>
            </tr>
      <tr>
      	<td colspan=2>
      		Geocode (optional):<br>
      		&nbsp;&nbsp;&nbsp;<input type=text name=Latitude value="<?php echo $destination['Latitude']; ?>">&nbsp;&nbsp;<input type=text name=Longitude value="<?php echo $destination['Longitude']; ?>">
      	</td>	
      </tr>
			<tr>
				<td>
					<input type="hidden" name="destination" value="Edited">
					<input type="submit" value="<?php if($_GET['editApprove']) { ?>Approve<?php } else {?>Save<?php }?> Destination">
				</td>
				<td><input type="submit" name="CANCEL" value="Cancel"></td>
			</tr>
		</table><br><br>
	</form>
<?php } ?>

	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
		<span id="NewDestination" style="font:1.5em bold;">Add New Public Destination</span><br>
		<?php echo $destination_error ?>
		<table id="NewDestination_collapse">
			<tr>
				<td>Destination Name:</td>
			</tr>
			<tr>
				<td colspan="2"><input type="text" name="DestinationName" style="width:300px;"></td>
			</tr>
			<tr>
				<td>Destination Detail:</td>
			</tr>
			<tr>
				<td colspan="2"><input type="text" name="DestinationDetail" style="width:300px;"></td>
			</tr>
			<tr>
				<td>Destination Phone Number:</td>
			</tr>
			<tr>
				<td colspan="2"><input type="text" name="DestinationPhone" style="width:300px;"></td>
			</tr>
			<tr>
				<td colspan="2">
					<?php create_html_address_table(); ?>
				</td>
			</tr>
			<tr>
				<td>Group</td>
				<td>
					<select name="DestinationGroup">
						<?php
							$sql = "SELECT Name, DestinationGroupID FROM destination_group WHERE FranchiseID = $safe_franchise_id ORDER BY Name";
							$result2 = mysql_query($sql);
							while($row = mysql_fetch_array($result2)){
								echo "<option value=\"{$row['DestinationGroupID']}\">{$row['Name']}</option>";
							}
						?>
					</select>
					<?php echo $sql;?>
				</td>
			</tr>
			<tr><td valign="top">Tags</td>
                <td><?
                    $bp_selects = array();
                    if ($business_partners) {
                        foreach ($business_partners as $partner) {
                            $bp_selects[$partner['BusinessPartnerID']] = $partner['Name'];
                        }
                    }

                    $tags = get_all_destination_tags_alphabetical($safe_franchise_id);
                    if ($tags) {
                        foreach ($tags as $tag_entry) {
                            $checked = (isset($destination_tags[$tag_entry['TagName']])) ? 
                                       ' checked="checked"' : '';       

                            echo "<input type=\"checkbox\" name=\"Tag[{$tag_entry['TagID']}]\" {$checked} />"; 
                            echo $tag_entry['TagName'];
                           
                            if ($tag_entry['TagName'] == 'BUSINESS_PARTNER' && $business_partners) {
                                echo '&nbsp;&nbsp;<select name="TagInfo1[' . $tag_entry['TagID'] . ']">';
                                echo create_options_from_array($bp_selects, 
                                                               (int)($destination_tags[$tag_entry['TagName']]['TagInfo1']));
                                echo '</select>';
                            }
                                        
                            echo '<br />';
                        }
                    } ?>
            </td>
            </tr>
			<tr>
				<td>
					<input type="hidden" name="Destination" value="New">
					<input type="submit" value="Add New Destination">
				</td>
				<td><input type="submit" name="CANCEL" value="Cancel"></td>
			</tr>
		</table>
	</form>
	<form method="post">
		<span id="NewGroup" style="font:1.5em bold;">Add New Public Group</span><br><br>
		<?php echo $group_error; ?>
		<table id="NewGroup_collapse">
			<tr>
				<td>Group Name:</td>
			</tr>
			<tr>
				<td colspan="2"><input type="text" name="GroupName" style="width:300px;"></td>
			</tr>
			<tr>
				<td>Sub Group of:</td>
				<td>
					<select name="SubGroup">
						<option value="0">Main</option>
						<?php
							$sql = "SELECT Name, DestinationGroupID FROM destination_group WHERE FranchiseID = $safe_franchise_id";
							$result = mysql_query($sql);
							while($row = mysql_fetch_array($result)){
								echo "<option value=\"{$row['DestinationGroupID']}\">{$row['Name']}</option>";
							}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td><input type="submit" name="Group" value="Add New Group"></td>
				<td><input type="submit" name="CANCEL" value="Cancel"></td>
			</tr>
		</table>
	</form>
</div>

<ul>
	<?php build_destination_group($franchise); 
	$sql = "SELECT * FROM destination WHERE IsPublic = 'Yes' AND IsPublicApproved = 'No' and FranchiseID=".$franchise;
	$result = mysql_query($sql);
	?>
</ul>
<h2 style="clear:both;">Suggestions (  <?php echo mysql_num_rows($result); ?> )</h2>
<table border="1px" style="width:100%;">
	<tr>
		<th>Destination</th>
		<th>Phone</th>
		<th>Address</th>
		<th>Verified</th>
		<th style="width:100px;">actions</th>
	</tr>
	<?php
		
		
		
		while($row = mysql_fetch_array($result)){
		      $phone = get_phone_number($row['PhoneID']);
			echo '<tr>';
			echo "<td>{$row['Name']}<br>{$row['DestinationDetail']}</td>";
			echo "<td>{$phone['PhoneNumber']}</td>";
			echo "<td>";
				create_html_display_address($row['AddressID']);
			echo "</td>";
			echo "<td>{$address['IsVerified']}</td>";
			echo "<td>";
			?>
			<form method="get">
				<input type="submit" value="Accept" style="width:100px;"><br>
				<input type="hidden" value="<?php echo $row['DestinationID']; ?>" name="destination">
				<input type="hidden" value="true" name="editApprove">
				<input type="submit" value="Deny"  name="Approval" style="width:100px;">
			</form>
			<?php
			echo "</td>";
			echo '<tr>';
		}
	?>
</table>
<?php
	$groups_string = "[";
	while(current($groups)){
		$groups_string .= "'" . current($groups) . "'";
		if(next($groups))
			$groups_string .= ",";
	}
	$groups_string .= "]";
?>
<script type="text/javascript">
	var collapsables = ['NewGroup','NewDestination'];
	collapsables.each( function(item){
		$(item).addEvent('click', function(){
			if($(this.id + '_collapse').getStyle('display') == 'none'){
				$(this.id + '_collapse').setStyle('display','block');
			} else {
				$(this.id + '_collapse').setStyle('display','none');
			}
		});
		$(item + '_collapse').setStyle('display','none');
		$(item).setStyle('cursor','pointer');
	});
	
	var groups = <?php echo $groups_string; ?>;
	groups.each( function(item){
		$(item).addEvent('click', function(){
			if($(this.id + '_collapse').getStyle('display') == 'none'){
				$(this.id + '_collapse').setStyle('display','block');
			} else {
				$(this.id + '_collapse').setStyle('display','none');
			}
		});
		$(item + '_collapse').setStyle('display','none');
		$(item).setStyle('cursor','pointer');
	});
</script>
<?php
	include_once 'include/footer.php';
?>
