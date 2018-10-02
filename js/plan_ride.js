
  function get_bracketed_index(str) {
        var match = /\[(\d+)\]/.exec(str);

        if (match.length >= 2) {
            return match[1];
        }
        return -1;
    }
	

    function get_last_row_index() {
        // var rows = $('tr[stop_row]');
        var rows = $$('tr[stop_row]');
        var last_index=0;

        $each(rows, function(item, index) {
            var row_index = item.getProperty('stop_row');

            if (row_index >= last_index) {
                last_index = row_index;
            }
        });

        return last_index;
    }
    
	function remove_row(row){
		if($('stop[' + row + ']') != null)
			$('stop[' + row + ']').destroy();
	}
	

    function decorate_radio_buttons() {
        $each($$('input[type="radio"]'), function(item) {
            item.addEvents( {
                'change': change_destination_selector
            });
        });
    }
	
	function decorate_stay_time_input(i){
		if( i != 0){
			$('LocationTime[' + i + ']').addEvent('focus',function(evt){
				str = evt.target.name;
				str = str.substr(str.indexOf("[") + 1);
				str = str.substr(0, str.length - 1);
				//alert(str);
				//alert($('DepartureTimeType[' + str +']').length);
			});
		}
		
	}
function addRadioButtonChangeEvent() {
	jQuery('.radio_selector_span select').on('change',function() {
		jQuery(this).parent('span').parent('span').find('input[type="radio"]').prop('checked',true);
	});
}
jQuery(function($) {

	addRadioButtonChangeEvent();
});	
    function decorate_buttons() {
        $$('input.AddNewLocationRow').each(function(item){
        	item.addEvents( {
                'click' : function(evt) {
                        evt.stop();
                        add_location_row();
                }
        	});
        });

        decorate_radio_buttons();
        addRadioButtonChangeEvent();
		
		for(var i = 0; i <= get_last_row_index(); i++){
			//decorate_public_destinations(i);
			create_public_destination_selector(i);
			decorate_location_selector(i);
			decorate_stay_time_input(i);
		}
        
        //decorate_public_destinations(1);
		//decorate_location_selector(1);
    }
/*
    function decorate_public_destinations(index) {
        $each($$('div#DestinationWidget' + index + ' a'), function(item) {
            item.addEvents( {
                'click' : function(evt) {
                    evt.stop();
                    $('DestinationWidget' + index + '').setStyle('display', 'none');
                    var link = evt.target;
                    var href = link.href;
                    destinationID = href.substring(href.lastIndexOf('/') + 1, href.length);
                    var text = link.innerHTML;
					var phone= link.title;
                    set_destination(index, text, destinationID, phone);
                }
            });
        });
    }
*/
    function decorate_location_selector(index) {
        $('destination_selector[' + index + ']').addEvents( {
                'change' : function(evt) {
                    var b_index = get_bracketed_index(evt.target.name);
                    var sel = evt.target;
                    var opt = sel.options[sel.selectedIndex];
					if(opt.value != 'NOTSET')
                    	set_destination(b_index, opt.text, opt.value, opt.title);
                }
        });
        if($('LocationType[' + index + '][franchise]').checked)
        	$('destination_selector[' + index + ']').setStyle('display','none');
		else
			$('destination_selector[' + index + ']').fireEvent('change', { 'target': $('destination_selector[' + index + ']')});
    }

    function change_destination_selector(evt, obj) {
        if(obj != null)
            var evt = { target: obj };
        var b_index = get_bracketed_index(evt.target.name);
        if (evt.target.value == 'Franchise') {
            $('destination_selector[' + b_index + ']').setStyle('display', 'none');
            if($('DestinationWidget' + b_index + '').getStyle('display') == 'none')
                $('DestinationWidget' + b_index + '').setStyle('display', '');
            else
            	$('DestinationWidget' + b_index + '').setStyle('display', 'none');
        } else if (evt.target.value == 'Favorite') {
            $('destination_selector[' + b_index + ']').setStyle('display', '');
            $('DestinationWidget' + b_index + '').setStyle('display', 'none');
        }
    }

    function set_destination(index, description, id, phone) {
        $('LocationName[' + index + ']').set('text', description + ' - ' + phone);
        $('Location[' + index + ']').value = id;
        $('LocationText[' + index + ']').value = description + ' - ' + phone;
    }

    function hide_last_departure_cell() {
        var last_row_index = get_last_row_index();
        $('DepartCell[' + last_row_index + ']').setStyle('visibility', 'hidden');
    }

    window.addEvent('domready', decorate_buttons);
    window.addEvent('domready', hide_last_departure_cell);

	function new_destination(DestinationID, DestinationName, DestinationAddress){
		var insert_point = user_destination_template.indexOf('<option value="NOTSET">Select a destination...</option>') + 55;
		var new_user_destination_template = user_destination_template.substring(0,insert_point);
		new_user_destination_template = new_user_destination_template + '<option value="-' + DestinationID + '">' + DestinationName + ' - ' + DestinationAddress + '</option>' + user_destination_template.substring(insert_point);
		user_destination_template = new_user_destination_template;
		
		for(var i = 0; i <= get_last_row_index(); i++){
			new Element('option',{'html': DestinationName + ' - ' + DestinationAddress, 'value': '-' + DestinationID}).inject($('destination_selector[' + i + ']'), 'bottom');
			if($('destination_selector[' + i + ']').value == 'NOTSET'){
				$('destination_selector[' + i + ']').selectedIndex = $('destination_selector[' + i + ']').length - 1;
				set_destination(i, DestinationAddress, DestinationID);
			}
		}
		
	}
	function toggle_public_destination_group($name){
	    if($($name).getStyle('display') == 'none')
	        $($name).setStyle('display', '');
	    else
	        $($name).setStyle('display','none');
	}
	
	$('PrevDay').addEvent('click', function(){
		var jsonRequest = new Request.JSON({url: 'xhr/next_schedulable_ride_date.php', onSuccess: function(date){
			if(date.Allowed != "FALSE")
				set_date(date.Year, date.Month, date.Day);
    	}}).get({'Direction': 'previous', 'Year': $('TravelYear').value, 'Month': $('TravelMonth').value, 'Day': $('TravelDay').value });
	});
	
	$('NextDay').addEvent('click', function(){
		var jsonRequest = new Request.JSON({url: 'xhr/next_schedulable_ride_date.php', onSuccess: function(date,text){
			if(date.Allowed != "FALSE")
				set_date(date.Year, date.Month, date.Day);
    	}}).get({'Direction': 'next', 'Year': $('TravelYear').value, 'Month': $('TravelMonth').value, 'Day': $('TravelDay').value });
	});
	
	function set_date($year, $month, $day){
		$('TravelYear').value = $year;
		$('TravelMonth').value = $month;
		$('TravelDay').value = $day;
	}