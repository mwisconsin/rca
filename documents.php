<?php
    include_once 'include/header.php';
?>

<h2>
<?php
$driver_info = get_user_driver_info( get_current_user_id() );
$is_driver = is_array($driver_info) && $driver_info["DriverStatus"] == "Active";

if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise_id, "Franchisee") || $is_driver) {
?>
<ul>
<li><a target=_blank href="/documents/Driver Agreement.pdf">Driver Agreement</a><br><br></li>	
<li><a target=_blank href="/documents/RiderWaiver.pdf">Rider Waiver</a><br><br></li>	
<li><a target=_blank href="/documents/PoliciesAndProcedures.pdf">Policies and Procedures</a><br><br></li>	
</ul>	
<?php } else {
?>
<ul>
<li><a target=_blank href="/documents/RiderWaiver.pdf">Rider Waiver</a><br><br></li>	
</ul>	
<?php
}
?>
</h2>
<?php
    include_once 'include/footer.php';
?>