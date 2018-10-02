
window.addEvent('domready',function(){
									
	var editable_dates = $$('.editable_date');
	var editor_cell = null;
	var editor_open = false;
		
	editable_dates.each(function(item){
		item.addEvent('mousedown',function(){
			if(editor_open == true)
				return;
			editor_open = true;
			editor_cell = item;
			html = item.innerHTML;
			item.innerHTML = "";
			var button = new Element('button', {
				'type': 'button',
				'class': 'editable_date_button',
				'html': 'Save Date',
				'events': {
					'click': function(){
						update_date(item.getParent().id, item.id, input.value, item);
						item.innerHTML = input.value;
						editor_open = false;
					}
				}
			});
			var cancel_button = new Element('button', {
				'type': 'button',
				'class': 'editable_date_cancel',
				'html': 'Cancel',
				'events': {
					'click': function(){
						item.innerHTML = html;
						editor_open = false;
					}
				}
			});
			var set_as_today_button = new Element('button', {
				'type': 'button',
				'class': 'editable_date_set_as_today',
				'html': 'Set As Today',
				'events': {
					'click': function(){
						var date = new Date();
						input.value = (date.getMonth() + 1) + "/" + date.getDate()  + "/" + date.getFullYear().toString().slice(2);
						update_date(item.getParent().id, item.id, input.value, item);
						item.innerHTML = input.value;
						editor_open = false;
					}
				}
			});
			var input = new Element('input', {
				'type': 'text',
				'class': 'editable_date_input',
				'value': html
			});
			set_as_today_button.inject(item, 'top');
			button.inject(item, 'top');
			input.inject(item, 'top');
			cancel_button.inject(item, 'top');
			if(input.value == '')
				input.focus();
			else
				input.select();
		});
	});
	function set_cell($value){
		editor_cell.innerHTML = $value;
	}
	function edit_cell(){
		editor_cell.fireEvent('mousedown');
	}
	function update_date($userid, $field, $value, $table_cell){

		var myRequest = new Request({
			method: 'get',
			url: 'xhr/update_dates.php',
			onSuccess: function(text){
				if(text.indexOf('!Error!') != -1){
					edit_cell();
					alert(text);
				} else if(text.indexOf('!NOROW!') != -1){
					edit_cell();
					alert(text);
				} else
					set_cell(text);
			}
		});
		myRequest.send('userid=' + $userid + '&field=' + $field + '&value=' + $value);
	}
});