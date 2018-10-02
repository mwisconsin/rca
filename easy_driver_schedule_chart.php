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
?>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.1/themes/smoothness/jquery-ui.css" />
<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.1/jquery-ui.min.js"></script>

<link rel="stylesheet" type="text/css" href="extensions/TableTools/css/dataTables.tableTools.css">
<link rel="stylesheet" type="text/css" href="extensions/Editor-1.3.3/css/dataTables.editor.css">
<link rel="stylesheet" type="text/css" href="//cdn.datatables.net/plug-ins/a5734b29083/integration/jqueryui/dataTables.jqueryui.css">

<script type="text/javascript" language="javascript" charset="utf-8" src="media/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" language="javascript" charset="utf-8" src="extensions/TableTools/js/dataTables.tableTools.min.js"></script>
<script type="text/javascript" language="javascript" charset="utf-8" src="extensions/Editor-1.3.3/js/dataTables.editor.min.js"></script>
<script type="text/javascript" language="javascript" charset="utf-8" src="//cdn.datatables.net/plug-ins/a5734b29083/integration/jqueryui/dataTables.jqueryui.js"></script>
<script type="text/javascript" language="javascript" charset="utf-8" src="js/table.user.js"></script>
<style>
.nowrapcell {
	white-space: nowrap;
}	
	
</style>
<center>Specify Date: <input id=datePicker type="date"></center>
<table cellpadding="0" cellspacing="0" border="0" class="display" id="user" width="100%">
	<thead>
		<tr>
			<th>y/n</th>
			<th>DR#</th>
			<th>Name</th>
			<th>Hours</th>
			<th>C/T</th>
			<th>hr/</th>
			<th>d/</th>
			<th>EndTime</th>
			<th>DayofWeek</th>
			<th>Mobile</th>
			<th>Home</th>
			<th>Work</th>
			<th>Svc<br>Dog?</th>
			<th>Driver<br>Notes</th>
		</tr>
	</thead>
</table>