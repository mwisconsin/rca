/* TODO remove dependency on foreign array of items in the list so that the list can just be managed by its contents */
/* TODO remove dependency on MooTools. Dear God */
/* TODO: Just dispense with this tomfoolery and use Datatables? */

function xhr_set_affected_user(userID){
	if(userID === null)
		userID = "SETCURRENTUSER";
	var req = new Request({
	        method: 'get',
	        url: 'xhr/set_admin_work_as_user.php',
	        data: { 'uid': userID },
	        onComplete:  function(response) {
	            if (response.length != 0) {
					//$('AdminCurrentUser').setStyle('display', '');
					//$('AdminCurrentUserInfo').innerHTML = response;
				} else {
					//$('AdminCurrentUser').setStyle('display', 'none');
	            }
	            if (typeof(skip_reload_on_effective_user_change) == "undefined" ||
	                !(skip_reload_on_effective_user_change == true)) {
	                window.location = window.location.href;
	            }
	        }
	    }).send();
}
function button_up(evt){
	if(evt.key == 'down' || evt.key == 'up' || evt.key == 'enter')
		  return;
	var table = jQuery('#User_Selector_Table').DataTable();
	table.search( this.value ).draw();
  }
function eliminate_bad_matches(){
	if($selected_user != null)
		$possible_users[$selected_user].removeClass('User_Selector_Selected');
      $possible_users = [];
      $i = -1;
      $alt = 1;/*
      while($current_row.getNext('tr') != null){
          $current_row = $current_row.getNext('tr');
          if(!selector_row_is_valid($current_row, get_selector_col_ids(), get_selector_option_values() ) )
              $current_row.setStyle('display','none');
		else {
              $current_row.setStyle('display','');
              $i++;
              $available_users[$i] = $current_row;
              if($alt == 1){
              	$current_row.addClass('even');
              	$alt--;
              } else {
                  $alt++;
                  $current_row.removeClass('even');
              }
          }
      }*/
      Array.each($available_users, function(item){
      	
      	if(!selector_row_is_valid(item, get_selector_col_ids(), get_selector_option_values() ) )
              item.setStyle('display','none');
		else {
              item.setStyle('display','');
              $i++;
              $possible_users[$i] = item;
              if($alt == 1){
              	item.addClass('even');
              	$alt--;
              } else {
                  $alt++;
              	item.removeClass('even');
              }
          }
      });
	set_selected_user(0);
}
function selector_row_is_valid($row, $col_ids, $values){
	valid = true;
	var ss = '';
	$col_ids.each(function(value, index) { ss += ' '+$row.getChildren()[value].innerHTML.toLowerCase() } );
//	console.log($values);
	if(ss.match(escapeRegExp($values[0].toLowerCase())) == null) valid = false;
	if(valid && $values[1] != "")
		if(ss.match(escapeRegExp($values[1].toLowerCase())) == null) valid = false;
//	$col_ids.each(function(value, index){
//		if($row.getChildren()[value].innerHTML.toLowerCase().escapeRegExp().match($values[index].toLowerCase().escapeRegExp()) == null)
//			valid = false;
//	}); 
	return valid;		
}
function options_drop_down_change(evt){
	// Cookie.write(this.id, this.selectedIndex);
	// eliminate_bad_matches();
	// $('User_Selector_Input').focus();

	var table = jQuery('#User_Selector_Table').dataTable();
	if(this.value == 'All Available') table.fnFilter('');
	else table.fnFilter(this.value,3,true);
}
function get_selector_col_ids(){
	return [2,4,5];
}
function get_selector_option_values(){
	return [$('User_Selector_Input').value, $('User_Selector_Options_Role').value ];
}
function button_check(evt){
    if(evt.key == 'enter') {
		var table = jQuery('#User_Selector_Table').DataTable();
		var first_row = jQuery('#User_Selector_Table tbody tr')[0];
		var data = table.row( first_row ).data();
		xhr_set_affected_user(data[0]);
	}
}
function clicked_in_selector(){
	click_in_selector = true;
}

function hide_user_selector(){
	if(click_in_selector){
		click_in_selector = false;
		return;
	}
		
	document.body.removeEvent('click', hide_user_selector);
	$('User_Selector').removeEvent('click',clicked_in_selector);

	$('User_Selector').setStyle('height', '');
	$('User_Selector').setStyle('overflow-y', '');
	$('User_Selector_Table').setStyle('display','none');
	$('User_Selector_Option_Button').setStyle('display','none');
	click_in_selector = false;
	var table = jQuery('#User_Selector_Table').dataTable();
	table.fnFilter('');
	var table = jQuery('#User_Selector_Table').DataTable();
	table.search('').draw();
	jQuery('#User_Selector_Input').val('');
	$('User_Selector_Input').addEvent('focus', show_user_selector);
}


function show_user_selector(){
	$('User_Selector_Input').removeEvent('focus', show_user_selector);
	
	$('User_Selector').setStyle('height', window.getScrollSize().y - 3);
	$('User_Selector').setStyle('overflow-y', 'scroll');
	$('User_Selector_Table').setStyle('display','');
	$('User_Selector_Option_Button').setStyle('display','');
	$('User_Selector_Input').value = "";
	
	
	document.body.addEvent('click', hide_user_selector);
	$('User_Selector').addEvent('click',clicked_in_selector);
}

function set_selected_user($id){
	if($selected_user != null && $possible_users[$selected_user] != null)
          $possible_users[$selected_user].removeClass('User_Selector_Selected');
	if($possible_users[$id] == null) {
	    $selected_user = null;
	    return;
	}
	$possible_users[$id].addClass('User_Selector_Selected');
    $selected_user = $id;
}
function add_available_user(index, row){
	$available_users[index] = row;
}
$selected_user = null;
$available_users = [];
$possible_users = [];

var click_in_selector = false;
window.addEvent('domready',function(){


	

	
	$('User_Selector_Input').addEvent('focus', clear_selector_text);

	$('User_Selector_Options_Role').addEvent('change', options_drop_down_change);
	
	if($('User_Selector_Button_Reset')){
		$('User_Selector_Button_Reset').addEvent('click', function(){
			xhr_set_affected_user(null);
		});
		$('User_Selector_Button_Find_Drivers2').addEvent('click', function(){
			document.location = "admin_driver_links.php";
		});
		$('User_Selector_Button_Find_Drivers').addEvent('click', function(){
			document.location = "admin_driver_links.php";
		});
	}
	$('User_Selector').setStyle('height', '');
	
	
	$('User_Selector_Option_Button').addEvent('click', function(){
		$('User_Selector_Options').getStyle('display') != 'none' ? $('User_Selector_Options').setStyle('display', 'none') : $('User_Selector_Options').setStyle('display', '');
	});
	$('User_Selector_Options_Role').selectedIndex = Cookie.read('User_Selector_Options_Role');
});

function clear_selector_text(){
	this.value = "";
	$('User_Selector_Options').setStyle('display','none');
	if($('User_Selector_Table').hasClass('Hidden')){
		$('User_Selector_Table').removeClass('Hidden');
		$('User_Selector_Options').removeClass('Hidden');
		$('User_Selector_Option_Button').removeClass('Hidden');
	}
	$('User_Selector').setStyle('height', window.getScrollSize().y - 3);
	$('User_Selector').setStyle('overflow-y', 'scroll');
	$('User_Selector_Table').setStyle('display','');
	$('User_Selector_Option_Button').setStyle('display','');
	window.addEvent('keydown', button_check);
	$('User_Selector_Input').addEvent('keyup', button_up);
	

	var table = jQuery('#User_Selector_Table').dataTable();
	table.fnFilter('');
	var table = jQuery('#User_Selector_Table').DataTable();
	table.search('').draw();
	this.removeEvent('focus', clear_selector_text);

	document.body.addEvent('click',hide_user_selector);
	$('User_Selector').addEvent('click',clicked_in_selector);
}
	
jQuery(function($) {
	$('#User_Selector_Input').on('keyup',function(e) {
		if(e.keyCode == 27) {
			e.preventDefault();
			hide_user_selector();
			$('#User_Selector_Input').blur();
		}
	});
	$('body').on('keydown',function(e) {
		if(e.keyCode == 123 && e.shiftKey) { 
			$('#User_Selector_Input').focus();
		}	
	});
	
	$('#Datepicker_hidden1').datepicker({
    language: "en",
    position: 'left top',
    onSelect: function(dt,ob) {
    	var ds = new Date(dt);
    	document.location = 'admin_driver_links.php?Year='+ds.getFullYear()+'&Month='+(ds.getMonth()+1)+'&Day='+ds.getDate();
    }
    });
	$('#Datepicker_hidden2').datepicker({
    language: "en",
    position: 'left top',
    onSelect: function(dt,ob) {
    	var ds = new Date(dt);
    	document.location = 'admin_driver_links.php?Year='+ds.getFullYear()+'&Month='+(ds.getMonth()+1)+'&Day='+ds.getDate();
    }
	});
	
	var table = $('#User_Selector_Table').DataTable({
		"order" : [[ 1, "asc" ]],
		"paging" : false,
        "columnDefs": [
            {
                "targets": [ 2,3 ],
                "visible": false,
                "searchable": true
            }
		],
		"dom": 't'
	});

	$('#User_Selector_Table tbody').on('click','tr',function() {
		var data = table.row( this ).data();
		xhr_set_affected_user( data[0] );
	});
	

});

function escapeRegExp(str) {
  return str.replace(/[-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
}
