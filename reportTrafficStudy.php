<?php
	include_once 'include/database.php';
	include_once 'include/user.php';
	include_once 'include/rider.php';
	include_once 'include/link.php';
		
// CONFIRM CLUB

	redirect_if_not_logged_in();
	$franchise = get_current_user_franchise();
	
// ADMIN ONLY (Full or Club)CAN view report information
	
	if(current_user_has_role(1, "FullAdmin") || current_user_has_role($franchise, "Franchisee")){
		header("Location: home.php");
		die();	
	}	

if(count($_POST) > 0) {

	$fields_headers = array();
	list($startMonth,$startYear) = explode('/',$_POST["startDate"]);
	list($endMonth,$endYear) = explode('/',$_POST["endDate"]);
	$sDate = strtotime($startMonth.'/01/'.$startYear);
	$eDate = strtotime($endMonth.'/01/'.$endYear);
	$fields_headers[] = '';
	$fields_headers[] = date('F Y',$sDate);
	while(date('F Y',$sDate) != date('F Y',$eDate)) {
		$sDate = strtotime(date('F Y',$sDate).' +1 month');
		$fields_headers[] = date('F Y',$sDate);
	}
	$lines = array(
		$fields_headers
	);
	
	$sDate = strtotime($startMonth.'/01/'.$startYear);
	$eDate = strtotime($endMonth.'/01/'.$endYear);	
	$num_fees = array('Number of Annual Fees');
	while(strtotime(date('F Y',$sDate)) <= strtotime(date('F Y',$eDate))) {	
		$sql = "select count(LedgerEntryID) from ledger where EffectiveDate between '".date('Y-m-d',$sDate)."' and '".date('Y-m-t',$sDate)."'
			and Description like '%annual rider fee%'";

		$rs = mysql_fetch_assoc(mysql_query($sql));
		$num_fees[] = $rs['count(LedgerEntryID)'];
		$sDate = strtotime(date('F Y',$sDate).' +1 month');
	}
	$lines[] = $num_fees;
	
	$sDate = strtotime($startMonth.'/01/'.$startYear);
	$eDate = strtotime($endMonth.'/01/'.$endYear);	
	$unique_riders = array('Unique Riders');
	while(strtotime(date('F Y',$sDate)) <= strtotime(date('F Y',$eDate))) {
		$riders = get_number_of_paying_riders($franchise, date('Y-m-d',$sDate), date('Y-m-t 23:59:59',$sDate));
		$unique_riders[] = '$'.$riders["Total"].'.00';
		$sDate = strtotime(date('F Y',$sDate).' +1 month');
	}
	$lines[] = $unique_riders;
	
	$sDate = strtotime($startMonth.'/01/'.$startYear);
	$eDate = strtotime($endMonth.'/01/'.$endYear);	
	$total_links = array('Link Costs');
	while(strtotime(date('F Y',$sDate)) <= strtotime(date('F Y',$eDate))) {

		$ls = get_number_of_links($franchise, date('Y-m-d',$sDate), date('Y-m-t 23:59:59',$sDate));
		$total_links[] = '$'.(($ls['SCHEDULED'] + $ls['COMPLETE'] +  $ls['CANCELEDLATE'])*.5);
		$sDate = strtotime(date('F Y',$sDate).' +1 month');
	}
	$lines[] = $total_links;
			
	$software_cost = array('Total Software Cost');
	for($x = 1; $x < count($unique_riders); $x++)
		$software_cost[] = '$'.(str_replace('$','',$unique_riders[$x]) + str_replace('$','',$total_links[$x]));
	$lines[] = $software_cost;

	$sDate = strtotime($startMonth.'/01/'.$startYear);
	$eDate = strtotime($endMonth.'/01/'.$endYear);	
	$assoc_fee = array('Assoc Fee');
	while(strtotime(date('F Y',$sDate)) <= strtotime(date('F Y',$eDate))) {	
		$sql = "select count(LedgerEntryID) from ledger where EffectiveDate between '".date('Y-m-d',$sDate)."' and '".date('Y-m-t',$sDate)."'
			and Description like '%annual rider fee%'";

		$rs = mysql_fetch_assoc(mysql_query($sql));
		$total_annual_fees = $rs['count(LedgerEntryID)'];
		
		$rev = get_revenue_of_links($franchise, date('Y-m-d 00:00:00',$sDate), date('Y-m-t 23:59:59',$sDate));
		
		$assoc_fee[] = '$'.(
			(75 * $total_annual_fees + 
				(($rev['SCHEDULED'] + $rev['COMPLETE'] + $rev['CANCELEDLATE'])/100)
			) * 0.02);	
		$sDate = strtotime(date('F Y',$sDate).' +1 month');
	}
	$lines[] = $assoc_fee;
	
	$total = array('Total');
	for($x = 1; $x < count($assoc_fee); $x++)
		$total[] = '$'.(str_replace('$','',$software_cost[$x]) + str_replace('$','',$assoc_fee[$x]));
	$lines[] = $total;	
	
	header("Content-Type: text/csv");
	header("Content-Disposition: attachment; filename=reportTS.csv");
	header("Cache-Control: no-cache, no-store, must-revalidate");
	header("Pragma: no-cache");
	header("Expires: 0");
	
	outputCSV( $lines );
	exit();
	
} /* POST */

function outputCSV($data) {
    $output = fopen("php://output", "w");
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
}

include_once 'include/header.php';	
?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css">
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>
<script>
var $j = jQuery.noConflict();
jQuery(function() {
     jQuery('.date-picker').datepicker(
                    {
                        dateFormat: "mm/yy",
                        changeMonth: true,
                        changeYear: true,
                        showButtonPanel: true,
                        onClose: function(dateText, inst) {


                            function isDonePressed(){
                                return (jQuery('#ui-datepicker-div').html().indexOf('ui-datepicker-close ui-state-default ui-priority-primary ui-corner-all ui-state-hover') > -1);
                            }

                            if (isDonePressed()){
                                var month = jQuery("#ui-datepicker-div .ui-datepicker-month :selected").val();
                                var year = jQuery("#ui-datepicker-div .ui-datepicker-year :selected").val();
                                jQuery(this).datepicker('setDate', new Date(year, month, 1)).trigger('change');
                                
                                 jQuery('.date-picker').focusout()//Added to remove focus from datepicker input box on selecting date
                            }
                        },
                        beforeShow : function(input, inst) {

                            inst.dpDiv.addClass('month_year_datepicker')

                            if ((datestr = jQuery(this).val()).length > 0) {
                                year = datestr.substring(datestr.length-4, datestr.length);
                                month = datestr.substring(0, 2);
                                jQuery(this).datepicker('option', 'defaultDate', new Date(year, month-1, 1));
                                jQuery(this).datepicker('setDate', new Date(year, month-1, 1));
                                jQuery(".ui-datepicker-calendar").hide();
                            }
                        }
                    })
});	
</script>
<style>
.ui-datepicker-calendar {
    display: none;
}	
</style>
<h1>Traffic Study Report</h1>
<form method=POST onSubmit="return jQuery('#startDate').val() != '' && jQuery('#endDate').val() != '';">
    <label for="startDate">Pick a Start Month/Year:</label>
    <input name="startDate" id="startDate" class="date-picker" size=8 
    	value="<?php echo date('m/Y',strtotime('-1 month')); ?>"/>
    
    <label for="endDate">Pick a End Month/Year:</label>
    <input name="endDate" id="endDate" class="date-picker" size=8 
    	value="<?php echo date('m/Y',strtotime('-1 month')); ?>"/>    
    
    <input type=submit value="Submit Request">
</form>
