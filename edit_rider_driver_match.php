<?php
	include_once 'include/user.php';
	include_once 'include/name.php';
	include_once 'include/rider.php';
	include_once 'include/driver.php';
	include_once 'include/franchise.php';
	include_once 'include/address.php';
	include_once 'include/email.php';
	include_once 'include/phone.php';
	include_once 'include/care_facility.php';
	redirect_if_not_logged_in();
    
	$franchise = get_current_user_franchise();
	if(isset($_GET['id']) && $_GET['id'] != '')
	{
		if(!current_user_has_role($franchise, 'FullAdmin') && !care_facility_admin_has_rights_over_user($_GET['id']) && !current_user_has_role(1, 'FullAdmin') && !current_user_has_role($franchise, 'FullAdmin')){
			header("Location: home.php");
			die();
		}	
		$user_id = $_GET['id'];
		$edit_url = "&id=" . $user_id;
	}
	else
	{
		$user_id = get_affected_user_id();
		$edit_url = "";
	}
	
	user_string( $user_id, TRUE);
	$account = get_user_account($user_id);
	$Person_name = get_name($account['PersonNameID']);
	$alias = get_user_alias($user_id);
	$email = get_email_address($account['EmailID']);
	include_once 'include/header.php';
	

	
	if ( user_has_role($user_id, $franchise, 'driver') && (current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee'))) {
	  $role = 'driver';
	  $oposite_role = 'rider';
	} else if (user_has_role($user_id, $franchise, 'rider') && (current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee'))) {
	  $role = 'rider';
	  $oposite_role = 'driver';
	} else {
	  header('Location: home.php');
	}
	
	/// user is ok to view page at this point
	
	if (isset($_POST['pref'])) {
	  // submitted for update
	  foreach($_POST['pref'] as $tmpname=>$tmpvalue) {
	    if ($tmpvalue==0) { // delete reference
		  mysql_query("delete from rider_driver_match where self_UserID=".$user_id." and other_UserID='".(int)$tmpname."'");
		} else {
		  // delete reference if it exists then insert
		  mysql_query("delete from rider_driver_match where self_UserID=".$user_id." and other_UserID='".(int)$tmpname."'");
		  mysql_query("insert into rider_driver_match (user_type, self_UserID, other_UserID, rating) values ('".$role."', ".$user_id.", ".(int)$tmpname.", '".$tmpvalue."')");
		}
	  }
	}
	
	
	if ($role == 'driver') {
		$users_result = mysql_query("select x.FirstName, 
       x.LastName, 
       x.UserID, 
       rdm.rating 
from (select pn.FirstName, 
             pn.LastName, 
             u.UserID 
      from users u, 
           rider r, 
           person_name pn, 
           user_role ur,
           user_role ur2
      where ur.UserId=".$user_id."
            and ur.FranchiseID=".$franchise." 
            and ur.Role='Driver'
            and ur2.UserId=u.UserID
            and ur2.Role='Rider'
            and ur2.FranchiseID=".$franchise."
            and r.UserID=u.UserID 
            and u.Status = 'ACTIVE'
            and pn.PersonNameID=u.PersonNameID 
            and r.RiderStatus='Active' and ur.UserID!=ur2.UserID) x 
left join rider_driver_match rdm on rdm.self_UserID=".$user_id." 
                                    and rdm.other_UserID=x.UserID") or die(mysql_error());
		
		
	} else {
	    $users_result = mysql_query("select x.FirstName, 
       x.LastName, 
       x.UserID, 
       rdm.rating 
from (select pn.FirstName, 
             pn.LastName, 
             u.UserID 
      from users u, 
           driver d, 
           person_name pn, 
           user_role ur,
           user_role ur2
      where ur.UserId=".$user_id."
            and ur.FranchiseID=".$franchise." 
            and ur.Role='Rider'
            and ur2.UserId=u.UserID
            and ur2.Role='Driver'
            and ur2.FranchiseID=".$franchise."
            and d.UserID=u.UserID 
            and u.Status = 'ACTIVE'
            and pn.PersonNameID=u.PersonNameID 
            and d.DriverStatus='Active' and ur.UserID!=ur2.UserID) x 
left join rider_driver_match rdm on rdm.self_UserID=".$user_id." 
                                    and rdm.other_UserID=x.UserID") or die(mysql_error());
		
		
	}
	
?>
<h2>Account Information</h2>
<hr />

<p>Directions: Positive values are good, negative values are bad. 0 means indifferent. You may also select all your values first before saving, or save them one at a time.</p>
<form action="edit_rider_driver_match.php" method="post">
<table cellpadding="5" cellspacing="0">
<tr>
  <th><?php echo ucwords($oposite_role); ?></th>
  <th>Rating</th>
  <th></th>
</tr>
<?php
while ($row = mysql_fetch_assoc($users_result)) {
  ?>
  <tr>
    <td><?php echo $row['FirstName'] . ' ' . $row['LastName']; ?></td>
    <td><select name="pref[<?php echo $row['UserID']; ?>]">
        <option value="-3" <?php echo ($row['rating']==-3) ? 'selected="selected"' : ''; ?>>-3</option>
        <option value="-2" <?php echo ($row['rating']==-2) ? 'selected="selected"' : ''; ?>>-2</option>
        <option value="-1" <?php echo ($row['rating']==-1) ? 'selected="selected"' : ''; ?>>-1</option>
        <option value="0" <?php echo (($row['rating']==0) || ($row['rating']==null)) ? 'selected="selected"' : ''; ?>>0</option>
        <option value="1" <?php echo ($row['rating']==1) ? 'selected="selected"' : ''; ?>>1</option>
        <option value="2" <?php echo ($row['rating']==2) ? 'selected="selected"' : ''; ?>>2</option>
        <option value="3" <?php echo ($row['rating']==3) ? 'selected="selected"' : ''; ?>>3</option>
        
      </select></td>
    <td><input type="submit" value="Save" /></td>
  </tr>
  <?php
  //print_r($row);
  //echo '<br /><br />';
}
?>
</table>
</form>








<?php
    include_once 'include/footer.php';
?>