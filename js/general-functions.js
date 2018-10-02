	function pop_link_notes(object){
		window.open('view_link_note.php?id=' + object.id, "Link Notes", "height=270, width=400 resizable=true");
	}
	function refresh_window(){
		$('theForm').submit();
	}
	
	jQuery(function($) {
		$('.link_note_cell').on('click',function() {
			$($(this).find('img')[0]).trigger('click');
		});
		
		$('#Admin_linkStatusList').on('change',function(ev) {
			var linkStatusList = ev.delegateTarget;
			$.get('/xhr/set_link_status.php',
				{ linkid: $(linkStatusList).attr('linkid'), status: $(linkStatusList).val() },
				function(data) {
					$d = $('<div>The Status has been changed.</div>').dialog({
						modal: true,
						buttons: [
							{ text: 'Ok',
								click: function() { $d.dialog('close'); }
							}
						],
						close: function() { $d.remove(); }
					});
				}
			);
		});
	});
	
	function checkYourself(phone) {
		// Before You Wreck Yourself
		// Check a cookie to see if we've warned the rider off from scheduling their own ride without training
		// If we haven't yet warned them, warn them.
		
		if(Cookies.get("seen_schedule_warning") !== "true")
				jQuery("<div>Please contact the office at "+phone+" to learn how to best use the scheduling system.</div>").dialog({
					title: "Scheduling Instruction",
					width: 500,
					modal: true,
					buttons: {
						"Ok": function() { 
							jQuery(this).dialog("close"); 
							Cookies.set("seen_schedule_warning", "true");
						}
					}
				});		
		
	}