<?php
	require_once 'include/user.php';
	require_once 'include/franchise.php';
	require_once 'include/contact_narrative.php';
	
$affected_user = get_affected_user_id();

$franchise = get_current_user_franchise();
$tomm_arr = getdate(time()+86400);
?>
<div style="position:relative;z-index:10" class="noprint">
<button type="button" id="User_Selector_Button_Find_Drivers" onclick="document.location = 'admin_driver_links.php'">Find Drivers</button>
<input type=button id=Datepicker_hidden1 class=Datepicker_hidden value="+">
<?php if(count(get_user_franchises(get_current_user_id())) > 1){ ?>
<button type="button" id="User_Selector_Button_Select_Franchise" onclick="document.location = 'select_club.php'">Select Club</button>



<?php } ?>
<div id="User_Selector" class="noprint">
	
	<input id="User_Selector_Input" type="text" tabindex="-1" value="" />
	<?php
		if(get_affected_user_id() != get_current_user_id()){?>
	Currently working as 
	<span id="AdminCurrentUserInfo">
		<?php
            $person_name = get_user_person_name($affected_user);
            echo "{$person_name['FirstName']} ".($person_name["NickName"] != '' ? "($person_name[NickName]) " : "")."{$person_name['LastName']} {$person_name['MiddleInitial']} (".
                 $affected_user . ')';
        ?>
	</span>
	<?php
		echo "<button type=\"button\" id=\"User_Selector_Button_Reset\">Work As Self</button>";
		echo "<button type=\"button\" id=\"User_Selector_Button_Find_Drivers2\" onclick=\"document.location = 'admin_driver_links.php'\">Find Drivers</button>";
		echo "<input type=button id=Datepicker_hidden2 class=Datepicker_hidden value=\"+\">";
		#echo "<button type=\"button\" id=\"User_Selector_button_Find_Drivers_Tomorrow2\" onclick=\"document.location = 'admin_driver_links.php?Year=".$tomm_arr['year']."&Month=".$tomm_arr['mon']."&Day=".$tomm_arr['mday']."'\">+</button>";
		  ?>
          
          <?php
		}
	?>
    <button type="button" id="User_Selector_Option_Button" class="Hidden">Options + </button>
	<div id="User_Selector_Options" class="Hidden">
     Roles
     <select id='User_Selector_Options_Role'>
     	<option value="">All Available</option>
     	<option value="Rider">Rider</option>
        <option value="Driver">Driver</option>
        <option value="FullAdmin">Full Admin</option>
        <option value="Franchisee">Club Admin</option>
        <option value="Supporter">Supporting Friend</option>
        <option value="EmergencyContact">Emergency Contact</option>
     </select>
     
	</div>
    <div class="float_clear"></div>
	<table id="User_Selector_Table" class="Hidden">
		<tr id="User_Selector_RW_Start" class="User_Selector_Table_Header">
			<th>ID</th>
            <th class="Hidden">Franchise ID</th>
			<th>Name</th>
			<th class="User_Selector_Table_Header_Action">Action</th>
            <th class="Hidden">Roles</th>
		</tr>
	</table>
</div>


<?php 
if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee')){
?>
<div id="note_holder" style="" class="noprint">
<button id="button_view_notes">View Notes</button>
  <div style="width:220px;text-align:left;">
    
   
    <div id="panel_view_notes" style="/*display:none;*/width:200px;border:1px solid black;box-shadow:4px 4px 4px rgba(0,0,0,0.6);border-radius:5px;padding:5px;background-color:white;">
<p align="right"><a href="#" id="button_close2" style="display: none;">X</a></p>
<p><b>Add Note:</b></p>
  <form action="home.php?action=post_note" method="post">
        Note: <input type="text" name="note" /> <input type="submit" value="Save" />
      </form>
<?php
	$cn = new ContactNarrative();
	$cn_list = $cn->getUserNarrative($affected_user);
  if(current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise, 'Franchisee')){
    ?>
    
    <?php
	if (sizeof($cn_list)>0) {
	  ?>
		<div style="margin-top:5px;padding:2px; border-bottom:1px solid; margin-bottom:5px; font-size:.9em;">User Notes:
			<?php
			$rider_info = get_user_rider_info($affected_user);
			if($rider_info['OnHold'] == 1) echo " <b style='color: red;'>RIDER ON HOLD</b>";
			?></div>
        <?php 
		foreach ($cn_list as $item) {
		  echo '<p style="font-size:10px;">'.date('m/d/Y', strtotime($item['NoteTimestamp'])).'';
		  echo ' '.$item['NoteText'].'</p>';
		}
        ?>
        
        <?php
	}
  }
?>
  </div>
</div>
</div>
<?php
}
?>

<script type="text/javascript" src="<?php echo site_url(); ?>js/user_selector.js"></script>
<script language="javascript">
	window.addEvent('domready',function(){
//	$('button_add_note').addEventListener('click', function () {
//	    $('panel_note').setStyle('display', 'inline-block');
//		$('panel_view_notes').setStyle('display', 'none');
//	    document.getElementById('panel_note').style.display='inline-block';
//	});
	$('button_view_notes').addEventListener('click', function () {
//	    $('panel_view_notes').setStyle('display', 'inline-block');
//		$('panel_note').setStyle('display', 'none');
	jQuery('#panel_view_notes').toggle();
	return false;
	});
	
//	$('button_close').addEventListener('click', function () {
//	    $('panel_note').setStyle('display', 'none');
//	});
	$('button_close2').addEventListener('click', function () {
	    $('panel_view_notes').setStyle('display', 'none');
	});
	
	});
	</script>
</div>