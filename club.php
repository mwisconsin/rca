<?php
include_once 'include/user.php';
include_once 'include/franchise.php';
redirect_if_not_logged_in();
 
$franchise = get_current_user_franchise();

if(!current_user_has_role(1, 'FullAdmin') && !current_user_has_role($franchise, 'Franchisee')){
	header("location: home.php");
}


if($_POST['removeZip']){
	$zip = array_keys($_POST['removeZip']);
	$removed = remove_franchise_service_zip($franchise, $zip[0]);
	if($removed)
		$removed = "<div class=\"reminder\">You have sucessfully removed the zip from your serice area.</div>";
	else
		$removed = "<div class=\"reminder\">A problem occured when trying to delete the zip code.</div>";
}

if($_POST['addZipService']){
	$added = add_franchise_service_area($franchise, $_POST['addZip']);
	if($added)
		$added = "<div class=\"reminder\">You have sucessfully added the zip to your serice area.</div>";
	else
		$added = "<div class=\"reminder\">A problem occured when trying to add the zip code.</div>";
}


if (isset($_POST['FranchiseName']) && isset($_POST['OfficeHours']) && isset($_POST['ProcessingFee'])) {

  updateFranchise($franchise, $_POST['FranchiseName'], $_POST['OfficeHours'], 
  	$_POST['OfficeTimeZone'], $_POST['ProcessingFee'], $_POST['MainPhoneNumber'], $_POST['EmergencyPhoneNumber'], 
  	(isset($_POST['ClubID'])) ? $_POST['ClubID'] : '',  
  	$_POST['Address1'], $_POST['Address2'], $_POST['City'], $_POST['State'], $_POST['ZIP5'], $_POST['ZIP4'],
  	$_POST['UserID']);
  
  if ($_FILES['LogoSRC']['name']!='') {
   
	
	move_uploaded_file($_FILES['LogoSRC']['tmp_name'], '/home/rcauser/myridersclub.com/web/images/logos/'.$_FILES['LogoSRC']['name']);
	updateFranchiseLogo($franchise, $_FILES['LogoSRC']['name']);
  }
  
  updateFranchiseEmails($franchise, $_POST);
 
}

if (isset($_POST['AnnualFee']) && isset($_POST['MinThreshold']) && isset($_POST["PaymentReminderTiming"])) {
    savePriceVariables($franchise, $_POST['AnnualFee'], $_POST['MinThreshold'], $_POST["PaymentReminderTiming"]);
}


$franchise_result = mysql_query(
  "select f.FranchiseName, f.OfficeHours, f.OfficeTimeZone, f.ProcessingFee, f.ClubID, f.LogoSRC, f.AddressID, f.AnnualFee, f.MinThreshold,
          a.Address1, a.Address2, a.City, a.State, a.ZIP5, a.ZIP4, a.Latitude, a.Longitude, a.IsVerified, a.VerifySource,
		  f.MainPhoneID, p1.PhoneType as MainPhoneType, p1.PhoneNumber as MainPhoneNumber,
		  f.EmergencyPhoneID, p2.PhoneType as EmergencyPhoneType, p2.PhoneNumber as EmergencyPhoneNumber,
		  UserID as PrimaryContact, PaymentReminderTiming
   from franchise f left join address a on a.AddressID=f.AddressID
                    left join phone p1 on f.MainPhoneID=p1.PhoneID
					left join phone p2 on f.EmergencyPhoneID=p2.PhoneID
   where FranchiseID='".(int)$franchise."'") or die(mysql_error());
$franchise_row = mysql_fetch_assoc($franchise_result);
//print_r($franchise_row);
include_once 'include/header.php';
?>

<h3>Club Name & Hours</h3>

<form method="post" enctype="multipart/form-data">
<table>
<tr>
  <td valign="top" width="55%">
	<table>
    	<tr>
        	<td align="right"><b>Name:</b></td>
            <td colspan="5"><input type="text" name="FranchiseName" value="<?php echo $franchise_row['FranchiseName']; ?>" style="width:320px;"></td>
        </tr>
        <tr>
        	<td align="right"><b>Address1:</b></td>
            <td colspan="5"><input type="text" name="Address1" value="<?php echo $franchise_row['Address1']; ?>" style="width:320px;"></td>
        </tr>
        <tr>
        	<td align="right"><b>Address2:</b></td>
            <td colspan="5"><input type="text" name="Address2" value="<?php echo $franchise_row['Address2']; ?>" style="width:320px;"></td>
        </tr>
        <tr>
        	<td align="right"><b>City:</b></td>
            <td><input type="text" name="City" value="<?php echo $franchise_row['City']; ?>" style="width:100px;"></td>
            <td align="right"><b>State:</b></td>
            <td><?php echo get_state_dropdown(NULL, $franchise_row['State']); ?></td>
            <td align="right"><b>Zip:</b></td>
            <td><input type="text" name="ZIP5" value="<?php echo $franchise_row['ZIP5']; ?>" style="width:40px;"> - <input type="text" name="ZIP4" value="<?php echo $franchise_row['ZIP4']; ?>" style="width:35px;" /></td>
        </tr>
        <tr>
        	<td align="right"><b>Office Hours:</b></td>
            <td colspan="5"><input type="text" name="OfficeHours" value="<?php echo $franchise_row['OfficeHours']; ?>"></td>
        </tr>
        <tr>
        	<td align="right"><b>Office Time Zone:</b></td>
            <td colspan="5"><select size=1 name="OfficeTimeZone">
            	<option value=CST <?php echo $franchise_row["OfficeTimeZone"] == "CST" ? "selected" : ""; ?>>CST</option>
            	<option value=EST <?php echo $franchise_row["OfficeTimeZone"] == "EST" ? "selected" : ""; ?>>EST</option>
            	<option value=MST <?php echo $franchise_row["OfficeTimeZone"] == "MST" ? "selected" : ""; ?>>MST</option>
            	<option value=PST <?php echo $franchise_row["OfficeTimeZone"] == "PST" ? "selected" : ""; ?>>PST</option>
            	<option value=AKST <?php echo $franchise_row["OfficeTimeZone"] == "AKST" ? "selected" : ""; ?>>AKST</option>
            	<option value=HST <?php echo $franchise_row["OfficeTimeZone"] == "HST" ? "selected" : ""; ?>>HST</option>
            	</select></td>
        </tr>
        <tr>
            <td align="right"><b>Main Phone:</b></td>
            <td colspan="5"><input type="text" name="MainPhoneNumber" value="<?php echo $franchise_row['MainPhoneNumber']; ?>" /></td>
            </td>
        </tr>
        <tr>
            <td align="right"><b>Emergency Phone:</b></td>
            <td colspan="5"><input type="text" name="EmergencyPhoneNumber" value="<?php echo $franchise_row['EmergencyPhoneNumber']; ?>" /></td>
        </tr>
        
        
    </table>
  </td>
  <td valign="top" width="45%">
    <table>
      <tr>
        <td align="right"><b>Club ID:</b></td>
        <td><?php 
		  if (current_user_has_role(1, 'FullAdmin')) {
		    ?>
            <input type="text" name="ClubID" value="<?php echo $franchise_row['ClubID']; ?>" />
            <?php
		  } else {
		    echo $franchise_row['ClubID']; 
		  }
		?></td>
      </tr>
      <tr>
        <td align="right"><b>Web Logo:</b></td>
        <td><table>
		      <tr>
			    <td><?php echo $franchise_row['LogoSRC']; ?></td>
                <td><?php
					  if (isset($franchise_row['LogoSRC']) && ($franchise_row['LogoSRC']!='')) {
						?><img src="/images/logos/<?php echo $franchise_row['LogoSRC']; ?>" width="75" height="auto" /><?php
					  }
					  ?></td>
              </tr>
              <tr>
                <td colspan="2"><input type="file" name="LogoSRC" /></td>
              </tr>
            </table>
          </td>
      </tr>
      <tr>
      	<td align=right><b>Primary Contact</b></td>	
      	<td><select name=UserID>
					<?php
						$sql = "select UserID, FirstName, LastName
							from person_name natural join users
								natural join user_role
							where Role in ('Franchisee','FullAdmin','SuperUser','DbAdmin')
							order by LastName, FirstName";
						$r = mysql_query($sql);
						while($rs = mysql_fetch_assoc($r)) {
							echo "<option value=$rs[UserID] ".($rs["UserID"] == $franchise_row["PrimaryContact"] ? "selected" : "").">$rs[FirstName] $rs[LastName]</option>";
						}
					
					?>      		
      		</select></td>
      </tr>

    </table>
  </td>
</tr>
</table>
<br /><br />
    
    <table>
        
        <!--tr>
        	<td align="right"><b>Email:</b></td>
            <td><input type="text" name="Email" value="<?php echo $franchise_row['Email']; ?>"></td>
        </tr//-->
        <tr>
        	<td colspan="2" class="alignright"><input type="submit" value="Update" name="UpdateFranchise"></td>
        </tr>
   </table>
   
   <?php 
   
   $email_query = "select fe.EmailType, e.EmailAddress as EmailAddress1, e2.EmailAddress as EmailAddress2, vacation_end, vacation_duration from franchise_email_settings fe, email e, email e2 where e.EmailID=fe.EmailID1 and e2.EmailID=fe.EmailID2 and fe.FranchiseID=".(int)$franchise;
   $email_result = mysql_query($email_query) or die(mysql_error());
   $email_list = array();
   if (mysql_num_rows($email_result)>0) {
     while($email_row = mysql_fetch_assoc($email_result)) {
       $email_list[$email_row['EmailType']] = array($email_row['EmailAddress1'],$email_row['EmailAddress2']);
       if($email_row['EmailType'] == 'de_vacation') {
       	//print_r($email_row);
       	$email_list['vacation_duration'] = $email_row['vacation_duration'];
       	$email_list['vacation_end'] = $email_row['vacation_end'];
       }
     }
   }
   ?>
   
<table cellpadding="0" cellspacing="0">
<tr>
  <td><h2>System Email Information</h2></td>
  <td style="width:20px;"></td>
  <td><a href="javascript:copyPrimaryEmail('<?php echo $franchise_row['PrimaryContactEmail']; ?>');">Assign Primary Email to All</a></td>
</tr>
</table>
<table cellpadding="0" cellspacing="0">
  <tr><td width="200"><span id="CC" style="font-size:1.2em">+ Credit Card</span></td><td style="width:100px;">&nbsp;</td><td><b>Processing Fee:</b> <?php 
		  if (current_user_has_role(1, 'FullAdmin')) {
		    ?><input type="text" name="ProcessingFee" value="<?php echo $franchise_row['ProcessingFee']; ?>">
            <?php
          } else {
		    echo $franchise_row['ProcessingFee'];
		  
		  }
		  ?>
          </td></tr></table><br>
<ul id="CC_collapse">
    <table cellpadding="5" cellspacing="0">
    <tr><th width="200"></th><th>Email</th><th>Secondary Email</th></tr>
    <tr><td>Processing (day of)</td><td><input type="text" name="cc_processing1" id="cc_processing1" value="<?php echo ($email_list['cc_processing']) ? $email_list['cc_processing'][0] : ''; ?>" /></td><td><input type="text" name="cc_processing2" id="cc_processing2" value="<?php echo ($email_list['cc_processing']) ? $email_list['cc_processing'][1] : ''; ?>" /></td></tr>
    <tr><td>Thursday Batch Pmt</td><td><input type="text" name="cc_thursday1" id="cc_thursday1" value="<?php echo ($email_list['cc_thursday']) ? $email_list['cc_thursday'][0] : ''; ?>" /></td><td><input type="text" name="cc_thursday2" id="cc_thursday2" value="<?php echo ($email_list['cc_thursday']) ? $email_list['cc_thursday'][1] : ''; ?>" /></td></tr>
    </table>
</ul>
<br />
<span id="Billing" style="font-size:1.2em">+ Billing</span><br>
<ul id="Billing_collapse">
    <table cellpadding="5" cellspacing="0">
    <tr><th width="200"></th><th>Email</th><th>Secondary Email</th></tr>
    <tr><td>Contact</td><td><input type="text" name="billing_contact1" id="billing_contact1" value="<?php echo ($email_list['billing_contact']) ? $email_list['billing_contact'][0] : ''; ?>" /></td><td><input type="text" name="billing_contact2" id="billing_contact2" value="<?php echo ($email_list['billing_contact']) ? $email_list['billing_contact'][1] : ''; ?>" /></td></tr>
    </table>
</ul>
<br />
<span id="RiderAlerts" style="font-size:1.2em">+ Rider Alerts</span><br>
<ul id="RiderAlerts_collapse">
    <table cellpadding="5" cellspacing="0">
    <tr><th width="200"></th><th>Email</th><th>Secondary Email</th></tr>
    <tr><td>Below Threshold</td><td><input type="text" name="ra_threshold1" id="ra_threshold1" value="<?php echo ($email_list['ra_threshold']) ? $email_list['ra_threshold'][0] : ''; ?>" /></td><td><input type="text" name="ra_threshold2" id="ra_threshold2" value="<?php echo ($email_list['ra_threshold']) ? $email_list['ra_threshold'][1] : ''; ?>" /></td></tr>
    <tr><td>Annual Fee Due*</td><td><input type="text" name="ra_annual_fee1" id="ra_annual_fee1" value="<?php echo ($email_list['ra_annual_fee']) ? $email_list['ra_annual_fee'][0] : ''; ?>" /></td><td><input type="text" name="ra_annual_fee2" id="ra_annual_fee2" value="<?php echo ($email_list['ra_annual_fee']) ? $email_list['ra_annual_fee'][1] : ''; ?>" /></td></tr>
    </table>
</ul>
<br />
<span id="DriverEvents" style="font-size:1.2em">+ Driver Events</span><br>
<ul id="DriverEvents_collapse">
    <table cellpadding="5" cellspacing="0">
    <tr><th width="200"></th><th>Email</th><th>Secondary Email</th></tr>
    <tr><td>Availability Change</td><td><input type="text" name="de_availability_change1" id="de_availability_change1" value="<?php echo ($email_list['de_availability_change']) ? $email_list['de_availability_change'][0] : ''; ?>" /></td><td><input type="text" name="de_availability_change2" id="de_availability_change2" value="<?php echo ($email_list['de_availability_change']) ? $email_list['de_availability_change'][1] : ''; ?>" /></td></tr>
    <tr><td>Driver Takes Ride</td><td><input type="text" name="de_ride_taken1" id="de_ride_taken1" value="<?php echo ($email_list['de_ride_taken']) ? $email_list['de_ride_taken'][0] : ''; ?>" /></td><td><input type="text" name="de_ride_taken2" id="de_ride_taken2" value="<?php echo ($email_list['de_ride_taken']) ? $email_list['de_ride_taken'][1] : ''; ?>" /></td></tr>
    <tr><td>Fix Map</td><td><input type="text" name="de_fix_map1" id="de_fix_map1" value="<?php echo ($email_list['de_fix_map']) ? $email_list['de_fix_map'][0] : ''; ?>" /></td><td><input type="text" name="de_fix_map2" id="de_fix_map2" value="<?php echo ($email_list['de_fix_map']) ? $email_list['de_fix_map'][1] : ''; ?>" /></td></tr>
    <tr><td>Month End Allocation</td><td><input type="text" name="de_month_end_allocation1" id="de_month_end_allocation1" value="<?php echo ($email_list['de_month_end_allocation']) ? $email_list['de_month_end_allocation'][0] : ''; ?>" /></td><td><input type="text" name="de_month_end_allocation2" id="de_month_end_allocation2" value="<?php echo ($email_list['de_month_end_allocation']) ? $email_list['de_month_end_allocation'][1] : ''; ?>" /></td></tr>
    <tr><td>Insurance 1 Year Old**</td><td><input type="text" name="de_insurance_1_yr1" id="de_insurance_1_yr1" value="<?php echo ($email_list['de_insurance_1_yr']) ? $email_list['de_insurance_1_yr'][0] : ''; ?>" /></td><td><input type="text" name="de_insurance_1_yr2" id="de_insurance_1_yr2" value="<?php echo ($email_list['de_insurance_1_yr']) ? $email_list['de_insurance_1_yr'][1] : ''; ?>" /></td></tr>
    <tr><td>Driver License Exp.**</td><td><input type="text" name="de_driver_license_exp1" id="de_driver_license_exp1" value="<?php echo ($email_list['de_driver_license_exp']) ? $email_list['de_driver_license_exp'][0] : ''; ?>" /></td><td><input type="text" name="de_driver_license_exp2" id="de_driver_license_exp2" value="<?php echo ($email_list['de_driver_license_exp']) ? $email_list['de_driver_license_exp'][1] : ''; ?>" /></td></tr>
    <tr><td>Vacation End</td><td><input type="text" name="de_vacation1" id="de_vacation1" value="<?php echo ($email_list['de_vacation']) ? $email_list['de_vacation'][0] : ''; ?>" /></td><td><input type="text" name="de_vacation2" id="de_vacation2" value="<?php echo ($email_list['de_vacation']) ? $email_list['de_vacation'][1] : ''; ?>" /></td></tr>
    <tr><td>&nbsp;</td><td>Vacation Days for Warning: <input type=text size=3 name="vacation_end" value="<? echo $email_list['vacation_end']; ?>"></td><td>Length of Vacation: <input type=text size=3 name="vacation_duration" value="<?php echo $email_list['vacation_duration']; ?>"></td></tr>
    </table>
</ul>
<p>* Send email 30 days prior to rider and listed contact</p>
<p>** Send email 30 days prior to driver and listed contact</p>

<br />

 <table>
        
        <!--tr>
        	<td align="right"><b>Email:</b></td>
            <td><input type="text" name="Email" value="<?php echo $franchise_row['Email']; ?>"></td>
        </tr//-->
        <tr>
        	<td colspan="2" class="alignright"><input type="submit" value="Update" name="UpdateFranchise"></td>
        </tr>
   </table>
</form>


<h3>Service Area</h3>
<?php echo $added . $removed; ?>

<table border="1" width="200px">
	<tr>
    	<th width="170px">Zip</th>
        <th>Action</th>
    </tr>
    <form method="post">
<?php $zips = get_franchise_service_zips($franchise); 
	foreach($zips as $zip){
		echo "<tr><td>$zip</td><td><input type=\"submit\" name=\"removeZip[$zip]\" value=\"Remove\"></td></tr>";
	}
?>
	</form>
    <form method="post">
	<tr>
    	<td><input type="text" width="100%" name="addZip"></td>
        <td><input type="submit" name="addZipService" value="Add"></td>
    </tr>
    </form>
</table>

<form method="post">
<h3>Price Variables</h3>
<table cellpadding="0" cellspacing="2">
  <tr>
    <td align="right"><b>Annual Fee:</b></td>
    <td><input type="text" name="AnnualFee" value="<?php echo number_format($franchise_row['AnnualFee']/100,2); ?>" /></td>
    <td></td>
  </tr>
  <tr>
    <td align=right><b>Min. Threshold:</b></td>
    <td><input type="text" name="MinThreshold" value="<?php echo number_format($franchise_row['MinThreshold']/100,2); ?>" /></td>
    <td align=left><i>(negative values allowed, the club is held responsible)</i></td>
  </tr>
  <tr>
    <td><b>Payment Reminder Timing:</b></td>
    <td><input type="text" name="PaymentReminderTiming" value="<?php echo $franchise_row['PaymentReminderTiming']; ?>" /></td>
    <td><i>(number of hours before a rider is reminded, again, that they owe us money)</i></td>
  </tr>
</table>
<input type="submit" value="Update Price Variables" />
</form>



<br /><br />
<h2>Links</h2>

<span id="Pricing" style="font-size:1.2em">+ Pricing</span><br>
<ul id="Pricing_collapse">
    <li><a href="admin_pricing.php">Rate Cards</a></li>
    <li><a href="admin_driver_payout.php">Driver Rate Cards</a></li>
    <li><a href="deadhead_plus.php">Deadhead Plus</a></li>
</ul>
<br />
<span id="Scheduling" style="font-size:1.2em">+ Scheduling</span><br>
<ul id="Scheduling_collapse">
    <li><a href="admin_scheduling_lockout.php">Lockouts</a></li>
    <li><a href="admin_ridersclub_afterdark.php">After Hours Grid</a></li>
    <li><a href="#">TOD Delays</a> - not linked yet</li>
    <li><a href="admin_weather_delay.php">Weather Delays</a></li>
</ul>
<br />
<span id="CSM" style="font-size:1.2em">+ Club System Maintenance</span><br>
<ul id="CSM_collapse">
    <li><a href="admin_perform_daily_link_maintenance.php">Daily Maintenance</a></li>
    <li><a href="admin_perform_month_end_driver_allocation.php">Driver Month End Allocation</a></li>
    <li><a href="admin_perform_month_end_additional_use_charge.php">CF Additional Use Charge</a></li>
</ul>
<br />
<span id="BC" style="font-size:1.2em">+ Background Checks</span><br>
<ul id="BC_collapse">
    <li><a href="http://www.iowasexoffender.com/search/" target="_blank">Sex Offender Registry</a></li>
    <li><a href="https://www.iowacourts.state.ia.us/ESAWebApp/SelectFrame" target="_blank">Iowa Courts Online</a></li>
</ul>
<br />


<script type="text/javascript">
	var collapsables = ['Pricing','Scheduling','CSM','BC','CC', 'Billing', 'RiderAlerts', 'DriverEvents'];
	collapsables.each( function(item){
	    
	    document.getElementById(item).className = 'hidden_dropdown';
		$(item).addEvent('click', function(e){
			if($(this.id + '_collapse').getStyle('display') == 'none'){
				$(this.id + '_collapse').setStyle('display','block');
				e.target.className = 'visible_dropdown';
			} else {
				$(this.id + '_collapse').setStyle('display','none');
				e.target.className = 'hidden_dropdown';
			}
		});
		$(item + '_collapse').setStyle('display','none');
		$(item).setStyle('cursor','pointer');
		
	});
	
	
	
	function copyPrimaryEmail (address) {
	  document.getElementById('cc_processing1').value = address;

	  document.getElementById('cc_thursday1').value = address;

	  document.getElementById('billing_contact1').value = address;

	  document.getElementById('ra_threshold1').value = address;

	  document.getElementById('ra_annual_fee1').value = address;

	  document.getElementById('de_availability_change1').value = address;

	  document.getElementById('de_month_end_allocation1').value = address;

	  document.getElementById('de_insurance_1_yr1').value = address;

	  document.getElementById('de_driver_license_exp1').value = address;
	  
	  document.getElementById('de_vacation1').value = address;

	}
</script>

<?php

include_once 'include/footer.php';

?>