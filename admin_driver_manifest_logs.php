<?php
include_once('include/user.php');
require_once('include/driver.php');


redirect_if_not_logged_in();

$franchise_id = get_current_user_franchise();
if(!current_user_has_role(1 , "FullAdmin") && !current_user_has_role($franchise_id, "Franchisee")){
	header("Location: home.php");
	die();	
}

include_once('include/header.php');

?>
<script>
jQuery(function($) {
    $('#log_date').datepicker({
        onSelect: function(formattedDate, date, inst) {
            window.location.href = '/admin_driver_manifest_logs.php?Date=' + formattedDate;
        }
    })
});

</script>
<style>
.table {
    width: 90%;
    border-collapse: collapse;
}
.table th, .table td {
    border: 1px solid #999;
    padding: 0px 3px 0px 3px;
}
</style>

<p>Date: <input type="text" class=jq_datepicker id="log_date" <?php if(isset($_GET["Date"])) echo "value=\"".$_GET["Date"]."\""; ?>></p>

<?php
    $date = date('m/d/Y');
    if(isset($_GET["Date"])) $date = $_GET["Date"];
    $sql = "select users.UserID, FirstName, NickName, LastName, DATE_FORMAT(DesiredArrivalTime, '%m/%d/%Y') as LinkDate
        , Max(DriverConfirmedDTS) as DriverConfirmedDTS, Max(manifest_log.Visit) as Visit
        from link, users natural join person_name
        left join manifest_log on users.UserID = manifest_log.UserID and DATE_FORMAT(DateViewed, '%m/%d/%Y') = '{$date}'
        where AssignedDriverUserID = users.UserID
        and DATE_FORMAT(DesiredArrivalTime, '%m/%d/%Y') = '{$date}'
        group by users.UserID, FirstName, NickName, LastName, DATE_FORMAT(DesiredArrivalTime, '%m/%d/%Y')
        order by LastName, FirstName";
    $r = mysql_query($sql);
?>
<table class=table>
    <thead>
        <tr>
            <th scope="col">UserID</th>
            <th scope="col">Name</th>
            <th scope="col">Manifest Release DTS</th>
            <th scope="col">Driver Viewed DTS</th>
        </tr>
    </thead>
    <tbody>
<?php
    while($rs = mysql_fetch_assoc($r)) {
        echo "<tr>";
        echo "<th scope=row>{$rs["UserID"]}</th>";
        echo "<td>".$rs["FirstName"]." ".($rs["NickName"] != "" ? "(<b>".$rs["NickName"]."</b>) " : "").$rs["LastName"]."</td>";
        echo "<td>{$rs["DriverConfirmedDTS"]}</td>";
        echo "<td style='background-color: ".
            ($rs["Visit"] == "" ? "Tomato" :
                (strtotime($rs["Visit"]) > strtotime($rs["DriverConfirmedDTS"]) ? 'MediumSeaGreen' : 'Tomato'))
            .";'>{$rs["Visit"]}</td>";
        // echo "<td>".date("Y-m-d H:i:s",$rs["DriverConfirmedDTS"])."</td>";
        // echo "<td>".date("Y-m-d H:i:s",$rs["Visit"])."</td>";
        echo "</tr>";
    }
?>
    </tbody>
</table>

<?php include_once('include/footer.php');

?>