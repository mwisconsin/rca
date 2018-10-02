<?php
    require_once('include/user.php');
    require_once('include/address.php');
    require_once('include/large_facility.php');
    require_once('include/franchise.php');
    redirect_if_not_logged_in();
	
	$franchise = get_current_user_franchise();
	
    if(!current_user_has_role(1 , "FullAdmin") && !current_user_has_role($franchise, "Franchisee")){
		header("Location: home.php");
		die();	
	}
    
    include_once 'include/header.php';

    $large_facilities = get_large_facilities(get_current_user_franchise()); 
    
?>
<center><h2>Large Facilities</h2></center>
<div style="margin:10px 50px 10px 50px;">
<table border="1"><tr><th>Name</th><th>Address</th><th>Actions</th></tr>
<?php if (!count($large_facilities)) { ?>
    <tr><td colspan="3">No Facilities Found</td></tr>
<?php
} else { 
    foreach ($large_facilities as $id => $facility) { ?>
    <tr><td><?php echo $facility['LargeFacilityName'] ?></td><td><?php
        create_html_display_address($facility['FacilityAddressID']); ?></td>
        <td><a href="admin_large_facility.php?id=<?php echo $id ?>">View</a><br />
            <a href="admin_large_facility.php?action=edit&id=<?php echo $id ?>">Edit</a>
            <br />Delete<br />Links<br /></td>
    </tr><?php
    }
} ?>
</table>
</div>

   <div style="padding:3px;">
        <a href="admin_large_facility.php?action=add">Add Large Facility</a>
    </div>
</div>
<?php
    include_once 'include/footer.php';
?>
