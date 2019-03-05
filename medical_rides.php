<?php
    include_once 'include/user.php';
	redirect_if_not_logged_in();

    require_once('include/rider.php');
    require_once('include/link.php');
    require_once('include/rc_log.php');
	require_once 'include/franchise.php';
	
	if (isset($_GET['uid']) && current_user_has_role(1, 'FullAdmin')) {
	  set_affected_user_id($_GET['uid']);
	}
	

	
	$franchise = get_current_user_franchise();
	if(!user_has_role(get_affected_user_id(), $franchise, 'Rider'))
		header("location: home.php");
		
	$current_user_roles = get_user_roles(get_current_user_id(), $franchise);
	$ReadOnly = 0;
	if(current_user_has_role($franchise, 'Franchisee'))
		foreach($current_user_roles as $role) 
			if($role['Role'] == 'Franchisee') $ReadOnly = $role['ReadOnly'];
	if(get_current_user_id() == get_affected_user_id()) $ReadOnly = 0;

	include_once('include/header.php');
?>
<link rel="stylesheet" href="//cdn.datatables.net/1.10.15/css/jquery.dataTables.min.css" />
<link rel="stylesheet" href="//cdn.datatables.net/buttons/1.3.1/css/buttons.dataTables.min.css" />
<script src=//cdn.datatables.net/1.10.15/js/jquery.dataTables.min.js></script>
<script src=//cdn.datatables.net/buttons/1.3.1/js/dataTables.buttons.min.js></script>
<script src=//cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js></script>
<script src=//cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/pdfmake.min.js></script>
<script src=//cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/vfs_fonts.js></script>
<script src=//cdn.datatables.net/buttons/1.2.4/js/buttons.html5.min.js></script>
<?php
	$user_id = get_affected_user_id();
  $rider_info = get_user_rider_info( $user_id );
  $user = get_user_account( $user_id );
	$name = get_name( $user['PersonNameID'] );
 ?>
<script>
jQuery(function($) {
$.fn.dataTable.ext.afnFiltering.push(
	function( oSettings, aData, iDataIndex ) {
		var s_iFini = document.getElementById('min').value;
		var s_iFfin = document.getElementById('max').value;
		var iStartDateCol = 6;
		var iEndDateCol = 7;

		var iFini = $.datepicker.parseDate("mm/dd/yy", s_iFini);
		var iFfin = $.datepicker.parseDate("mm/dd/yy", s_iFfin);
		
		var datofini = $.datepicker.parseDate("mm/dd/yy", aData[iStartDateCol]);
		var datoffin = $.datepicker.parseDate("mm/dd/yy", aData[iEndDateCol]);

		if ( s_iFini === "" || s_iFfin === "" )
		{
			return true;
		}
		else if ( iFini.getTime() <= datofini.getTime() && s_iFfin === "")
		{
			return true;
		}
		else if ( iFfin.getTime() >= datoffin.getTime() && s_iFini === "")
		{
			return true;
		}
		else if (iFini.getTime() <= datofini.getTime() && iFfin.getTime() >= datoffin.getTime())
		{
			return true;
		}
		return false;
	}
);

	$('#min').datepicker({
		onSelect: function() {
			var table = $('#tableMedRides').DataTable();
			table.draw();
		},
		language: 'en'
	});
	$('#max').datepicker({
		onSelect: function() {
			var table = $('#tableMedRides').DataTable();
			table.draw();
		},
		language: 'en'
	});

	$('#tableMedRides').DataTable({
		dom: 'Bflrtip',
		buttons: [
        'copy', 'excel',
        {
        	extend: 'pdfHtml5',
        	filename: '<?php echo $name["FirstName"]." ".$name["LastName"]." - Medical Rides - ".date("Y-m-d",time()); ?>',
        	orientation: 'landscape'
        }
    ],
    "columnDefs": [
        {
            "targets": [ 6,7 ],
            "visible": false,
            "searchable": true,
            "type": "date"
        }
    ],
    "pagingType": "full_numbers"
  });
});	
</script>
<?php
	$sql = "select GetFamilyTree(ParentGroupID) from destination_group where ParentGroupID = 3";
	$rs = mysql_fetch_array(mysql_query($sql));
	$ar = explode(',',$rs[0]);
	$ar[] = 3;

 	$past_rides = get_rider_past_links( $user_id, FALSE, FALSE, $ar, array('COMPLETE','CANCELEDLATE') );
	$total_past_rides = get_rider_total_past_links($user_id, $ar, array('COMPLETE','CANCELEDLATE'));

    if (count($past_rides) == 0) {
?>
<div style="font-size:18px; text-align:center; margin-top:120px;">
	You have no past rides.<br />
</div>
<?php 
    } else {
?>
<div style="text-align:center;"><h2 id="past_rides">Past Medical Rides</h2></div>
<div>
<?php pagelet(); ?>
<center>From Date: <input type=text id=min size=10>&nbsp;&nbsp;&nbsp;To Date: <input type=text id=max size=10></center><br><br>
<table border="1" id=tableMedRides>
<?php
		echo "<thead>";
    echo '<tr>';
    echo get_user_link_table_headings(FALSE, TRUE, FALSE, FALSE, TRUE);
    if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee'))
    	echo "<TH width=300>Notes</TH>";
    echo '</tr>';
    echo "</thead>";
		echo "<tbody>";
    $can_delete_all = current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee');
    $next_schedulable = get_next_user_schedulable_link_time();
    foreach ($past_rides as $past) {
        $link_time = get_link_arrival_time($past);

        echo '<tr>';
        echo get_link_as_user_link_table_row($past, FALSE, TRUE, TRUE, FALSE, FALSE, TRUE);
        if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee')) {
	        echo "<td class=link_note_cell><img style='display: none;' id=\"{$past['LinkID']}\" src=\"images/trans.gif\" onclick=\"pop_link_notes(this)\" class='" . 
	                (isset($past['LinkNote']) || isset($rider_settings['OtherNotes']) ? 'LinkNoteFilled' : 'LinkNoteBlank') . "' alt=\"df\" /> " .
	    		    (	isset($past['LinkNote']) || isset($rider_settings['OtherNotes']) 
	    		    		? $past['LinkNote'] . 
	    		    			($rider_settings['OtherNotes'] != '' && $past['LinkNote'] != '' ? "<br>...<br>" : "") . 
	    		    			$rider_settings['OtherNotes']
	    		    		: "") 
	    		   .($past['LinkFlexFlag'] != 0 ? '<br><b>Time is Flexible</b>' : '')
	    		   . "  </td>";        	
        	
        }
        echo '</tr>';
    }

?>
</tbody>
</table>
<?php pagelet(); ?>
</div>
<?php
    }
?>



<div style="clear:both">&nbsp;</div>

<?php

function pagelet(){
	global $total_past_rides;
?>
<?php if($_GET['p'] && $_GET['p'] >= 1){ ?>
<a href="medical_rides.php?p=<?php echo $_GET['p'] - 1; ?>#past_rides">Previous</a>&nbsp;
<?php } 
$p = $_GET['p'] ? $_GET['p'] : 0;
$start = $p <= 3 ? 1 : ($p >= floor($total_past_rides / 15) - 3 ? floor($total_past_rides / 15) - 6 : $p - 3);
if($start < 1)
	$start = 1;
//echo $start;
//echo ($total_past_rides / 15);
for($i = $start; $i < $start + 7 && $total_past_rides / 15 > $i; $i++){
	//echo $p;
	echo "<a href=\"medical_rides.php?p=$i#past_rides\"" . ($i-1 == $p ? " class=\"pagelet_selected\"": "") . ">$i</a>&nbsp;";	
}


 if(($total_past_rides / 15) > 1 && $p < floor($total_past_rides / 15)){ ?>
<a href="medical_rides.php?p=<?php echo $p+1; ?>#past_rides">Next</a>
<?php } 	
}
?>
<style>
	.pagelet_selected {
		font-size:1.15em;
	}
</style>

<?php 
	include_once 'include/footer.php';
?>
