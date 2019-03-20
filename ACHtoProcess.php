<?php
	include_once 'include/user.php';
	include_once 'include/care_facility.php';
	redirect_if_not_logged_in();
	
	$franchise = get_current_user_franchise();
	
	if(!current_user_has_role(1 , "FullAdmin") && !current_user_has_role($franchise, "Franchisee")){
		header("Location: home.php");
		die();	
	}
	
	
	
	include_once 'include/header.php';
	//error_reporting(E_ALL);
?>
<style>
table.dataTable tbody tr.futureWorld {
	background-color: goldenrod;
}	
</style>
<link rel="stylesheet" href="//cdn.datatables.net/1.10.5/css/jquery.dataTables.min.css" />
<script src=//cdn.datatables.net/1.10.5/js/jquery.dataTables.min.js></script>

<script>
jQuery(function($) {
	
	$('#achtable').dataTable({
		"order": [[ 2, "asc" ]],
    "columnDefs": [
        {
            "targets": [ 5,7 ],
            "visible": false
        }
    ],
    "pageLength": 25,
    "fnRowCallback": function( nRow, aData ) {
    	var cDate = new Date(aData[2]);
    	if(cDate.getTime() > (new Date()).getTime()) $(nRow).addClass('futureWorld');
    }
	});
	
	$('#achtable tbody').on( 'click', 'tr', function () {
        $(this).toggleClass('selected');
    } );
  
  $('#process_button').button().on('click',function() {

  		var table = $('#achtable').DataTable();
  		var datarows = new Array();
  		for(var i = 0; i < table.rows('.selected').data().length; i++)
  			datarows.push( table.rows('.selected').data()[i] );
  		var dr = JSON.stringify(datarows);
  		
  		$.post('/ACHtoProcess_submit.php',
  			{ datarows: dr,
  				effective_date: $('#effective_date').val() },
  			function(data) {
  				//console.log(data);
  				location.reload();
  			});

  });
  
  $('#delete_button').button().on('click',function() {

  		var table = $('#achtable').DataTable();
  		var datarows = new Array();
  		for(var i = 0; i < table.rows('.selected').data().length; i++)
  			datarows.push( table.rows('.selected').data()[i] );
  		var dr = JSON.stringify(datarows);
  		//console.log(dr);
  		$.post('/ACHtoProcess_submit.php',
  			{ datarows: dr,
  				effective_date: $('#effective_date').val(),
  				special_action: 'delete' },
  			function(data) {
  				//console.log(data);
  				location.reload();
  			});

  });
  

});	
	
	
	
</script>
<br><br><br><a href=/make_payment.php>Make Payment</a><br><br>
<table id=achtable>
	<thead>
		<th>Rider</th>	
		<th>RiderID</th>
		<th>Draw Date</th>
		<th>Draw Amount</th>
		<th>Pay<br>Type</th>
		<th>UserID</th>
		<th>User</th>
		<th>ID</th>
		<th>EFT</th>
	</thead>	
	<tbody>
<?php
$r = mysql_query("select * from ach_to_process where status = 1");
while($rs = mysql_fetch_assoc($r)) {
	$user_id = $rs["userid"];
  $uname = mysql_fetch_assoc(mysql_query("select FirstName,LastName,RechargePaymentType from users,person_name where person_name.PersonNameID = users.PersonNameID and UserID = $user_id"));
  $user_name = $uname["FirstName"].' '.$uname["LastName"];
	$rider_id = 0;
	$rider_info = array();
	$rider_name = "";
	if (user_has_role($user_id, $franchise, 'Rider')) {
    $rider_id = get_user_rider_id($user_id);
    $rider_info = get_user_rider_info($user_id);
    $rname = mysql_fetch_assoc(mysql_query("select * from person_name where PersonNameID = $rider_info[PersonNameID]"));
    $rider_name = get_displayable_person_name_string($rname);
  } else if (user_has_role($user_id, $franchise, 'CareFacilityAdmin')) {
  	$cf = get_care_facility( get_user_current_care_facility( $user_id ) );
  	$rider_name = "Care Facility: ".$cf['CareFacilityName'];
  }
	echo "<tr>";
	echo "<td>$rider_name</td>";
	echo "<td>$rider_id</td>";
	echo "<td>".date('m/d/Y',strtotime($rs["dts"]))."</td>";
	echo "<td>".($rs["paytype"] == 'ADD_TO_ACCOUNT' ? '' : '(').
		"$".number_format($rs[amount],2).($rs["paytype"] == 'ADD_TO_ACCOUNT' ? '' : ')')
		."</td>";
	echo "<td>".($rs["paytype"] == 'ADD_TO_ACCOUNT' ? 'Add' : 'AF')."</td>";
	echo "<td>$user_id</td>";
	echo "<td>$user_name</td>";
	echo "<td>$rs[id]</td>";
	echo "<td>".($uname["RechargePaymentType"] == "SendChecks" ? "Yes" : "No")."</td>";
	echo "</tr>";
}
?>
</tbody>
</table>	

<button id=process_button>Process Selected Events</button> (Effective Date: <input type=text id=effective_date size=8 value="<?php 
//	if( date('G',time()) > 14 )
//		echo date('m/d/Y',strtotime('+2 days'));
//	else echo date('m/d/Y',strtotime('+1 day'));
	echo date('m/d/Y',time());
?>" class=jq_datepicker >)<br>
<button id=delete_button>Delete Selected Events</button>