<?php
	include_once 'include/user.php';
	include_once 'include/link_price.php';
	redirect_if_not_logged_in();
	
	$franchise = get_current_user_franchise();
	
	if(!current_user_has_role(1 , "FullAdmin") && !current_user_has_role($franchise, "Franchisee")){
		header("Location: home.php");
		die();	
    }
    
    if(count($_POST) > 0) {
        $sql = "delete from care_facility_additional_use_charge where FranchiseID = $franchise and EffectiveFrom = '".$_POST["EffectiveFrom"][0]."'";
        // echo $sql."<BR>";
        mysql_query($sql);

        for($i = 0; $i < count($_POST["MinimumRideCount"]); $i++) {
            if($_POST["MinimumRideCount"][$i] == '') continue;
            $sql = "insert into care_facility_additional_use_charge (FranchiseID, MinimumRideCount, ChargeCents, EffectiveFrom, EffectiveTo) values ($franchise, "
                .$_POST["MinimumRideCount"][$i].",".$_POST["ChargeCents"][$i].",'".$_POST["EffectiveFrom"][$i]."',"
                .($_POST["EffectiveTo"][$i] != "" ? "'".$_POST["EffectiveTo"][$i]."'" : "null")
                .")";
            // echo $sql."<BR>";
            mysql_query($sql);
        }

    }

    $sql = "select * from care_facility_additional_use_charge where FranchiseID = $franchise 
        and not EffectiveTo is null
        and EffectiveTo = (select max(EffectiveTo) from care_facility_additional_use_charge where FranchiseID = $franchise)
        order by MinimumRideCount";
    $r = mysql_query($sql);
    $oldcard = array();
	while($rs = mysql_fetch_array($r)) {
        $oldcard[] = $rs;
    }

    $sql = "select * from care_facility_additional_use_charge where FranchiseID = $franchise 
        and (EffectiveTo is null or CURRENT_DATE between EffectiveFrom and EffectiveTo)
        order by MinimumRideCount";
    $r = mysql_query($sql);
    $card = array();
	while($rs = mysql_fetch_array($r)) {
        $card[] = $rs;
    }
	
	include_once 'include/header.php';
?>
<form method=POST>
<h1>Care Facility Additional Use Charge</h1>
Specify the parameters of the Care Facility Additional Use Charges:<br><br>
<input type=button value="Add New Row" onClick="addNewRow()">
<br><br>
<table id=AUCtable>
<tr valign=bottom align=center><th>Minimum<Br>Ride<bR>Count</th><th>Charge<br>Cents</th><th>Effective From</th><th>Effective To</th></tr>
<?php
if(count($card) > 0) for($i = 0; $i < count($card); $i++) {
    echo "<tr>";
    echo "<td><input type=text size=4 name=MinimumRideCount[] value=".$card[$i]["MinimumRideCount"]."></td>";
    echo "<td><input type=text size=4 name=ChargeCents[] value=".$card[$i]["ChargeCents"]."></td>";
    echo "<td><input type=text size=10 name=EffectiveFrom[] value=\"".$card[$i]["EffectiveFrom"]."\"></td>";
    echo "<td><input type=text size=10 name=EffectiveTo[] value=\"".$card[$i]["EffectiveTo"]."\"></td>";
    echo "</tr>";
} else {
    echo "<tr>";
    echo "<td><input type=text size=4 name=MinimumRideCount[] value=></td>";
    echo "<td><input type=text size=4 name=ChargeCents[] value=></td>";
    echo "<td><input type=text size=10 name=EffectiveFrom[] value=\"\"></td>";
    echo "<td><input type=text size=10 name=EffectiveTo[] value=\"\"></td>";
    echo "</tr>";    
}
?>

</table><br><br>
<input type=submit value=Submit>
<br><br>
<b>Notes:</b><br>
<li> Leave Effective To field blank to represent "Current" Charges</li>
<li> Set Effective To field to some past date to <i>archive</i> charges. The screen will refresh with new, blank, values.</li>
</form>
<br><br>
For Reference, here are the prior values:
<table>
<tr valign=bottom align=center><th>Minimum<Br>Ride<bR>Count</th><th>Charge<br>Cents</th><th>Effective From</th><th>Effective To</th></tr>
<?php
for($i = 0; $i < count($oldcard); $i++) {
    echo "<tr>";
    echo "<td>".$oldcard[$i]["MinimumRideCount"]."</td>";
    echo "<td>".$oldcard[$i]["ChargeCents"]."</td>";
    echo "<td>".$oldcard[$i]["EffectiveFrom"]."</td>";
    echo "<td>".$oldcard[$i]["EffectiveTo"]."</td>";
    echo "</tr>";
}

?>
</table>
<script>
function addNewRow() {
    $newrow = jQuery(jQuery('#AUCtable').find('tr')[1]).clone();
    $newrow.find('input').val('');
    jQuery('#AUCtable').append($newrow);
}
</script>
<?php
	include_once 'include/footer.php';
?>