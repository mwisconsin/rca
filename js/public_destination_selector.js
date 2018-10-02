$avaiable = [];
var dataLoaded = false;
var selectors = [];
var numSelectors = 0;

function match_one($string, $key){
	$array = $string.toUpperCase().split(",");
	$match = false;
	
	for($i = 0; $i < $array.length; $i++){
		if($key.toUpperCase().indexOf($array[$i]) > -1 && ($key != '' && $array[$i] !=''))
			$match = true;
	}
	return $match;
}

function find_possible_destinations($string, $idx){
	$children = $('DestinationSelectorResult[' + $idx + ']').getChildren();
	$first_index = true;
	Array.each($children, function(item, index){
			if(item.hasClass('Destination_Selected'))
				item.removeClass('Destination_Selected');
			$data = item.getChildren();
			$name = $data[0].innerHTML;
			$description =  $data[1].innerHTML;
			$groups = $data[2].innerHTML;
			//alert($name.toUpperCase()+ " " + $string.toUpperCase());
			//$('RESULTS').innerHTML += "||" + $name.toUpperCase().indexOf($string.toUpperCase())  + " " + match_one($description, $string) + match_one($groups, $string);
			if($name.toUpperCase().indexOf($string.toUpperCase()) > -1 || $description.toUpperCase().indexOf($string.toUpperCase()) > -1 || match_one($groups, $string) ){
				item.setStyle('display', '');
				if($first_index === true)
					$first_index = index;
			} else
				item.setStyle('display','none');
	});
	if($first_index !== true)
		$children[$first_index].addClass('Destination_Selected');
}



function alloc_public_destination_selector($idx){
	
    $input_object = $('DestinationSelector[' + $idx + ']');
	new Element('div', {
    id: 'DestinationSelectorResult[' + $idx + ']',
    html: '',
    styles: {
        display: 'block',
        border: '1px solid black',
		background: '#fff',
		position: 'absolute',
		width: 300,
		height:130,
		display: 'none',
		overflow: 'hidden',
		'overflow-y': 'scroll'
    	}
	}).inject($input_object, 'after');
	new Element('input', {
		id: 'DestinationSelectorCancel[' + $idx + ']',
		type: 'button',
		value: 'Cancel',
		styles: {
			postion: 'absolue',
			display: 'none'
			},
		events: {
			'click': function(){
				$('DestinationSelectorCancel[' + $idx + ']').setStyle('display', 'none');
				$('DestinationSelectorResult[' + $idx + ']').setStyle('display', 'none');
			}
		}
	}).inject($input_object, 'after');
	
	$avaiable.each(function(item, index){
		new Element('div', {
			html: '<div class="">' + URLDecode(item.name) + '</div>' + '<div class="' + ((item.description == '') ? 'Hidden ' : '') + 'Destination_Selector_Description">' +URLDecode(item.description) + '</div>'+ '<div class="Hidden">' + URLDecode(item.groups) + '</div>',
			styles: {
				'border-bottom': '1px solid'
				},
			events: {
				'click': function(){
					//alert("click");
					$('DestinationSelectorResult[' + $idx + ']').setStyle("display", 'none');
					$('DestinationSelector[' + $idx + ']').value = URLDecode(item.name);
					$('DestinationSelectorCancel[' + $idx + ']').setStyle("display", 'none');
					set_destination($idx, URLDecode( item.name + " - " + item.description + " - " + item.address), URLDecode( item.id), URLDecode( item.phone));
					if($('ArrivalTimeType[' + $idx + ']') != null)
						$('ArrivalTimeType[' + $idx + ']').focus();
					else if($('DepartureTimeType[' + $idx + ']') != null)
						$('DepartureTimeType[' + $idx + ']').focus();
				}
			}
		}).inject($('DestinationSelectorResult[' + $idx + ']'));
	});
	
	$input_object.addEvent('focus', function(){
			this.value = "";
			this.removeEvents('focus');
	});
	$input_object.addEvent('keydown', function(evt){
		//evt.stop();
			if(evt.key == 'enter'){
				$('DestinationSelectorResult[' + $idx + ']').getChildren('.Destination_Selected')[0].fireEvent('click');
				this.blur();
				//evt.stop();
			}
	});
	$input_object.addEvent('keyup', function(evt){
			 if(evt.key == 'down'){
				$selected = $('DestinationSelectorResult[' + $idx + ']').getChildren('div[class*="Destination_Selected"]')[0];
				if($selected != null)
					$new_selected = $selected.getNext('div[style$="solid;"]');
				if($new_selected != null){
					$new_selected.addClass('Destination_Selected');
					$selected.removeClass('Destination_Selected');
					$('DestinationSelectorResult[' + $idx + ']').scrollTo(0,$('DestinationSelectorResult[' + $idx + ']').getScroll().y + 17 + (($selected.getChildren()[1].hasClass('Hidden')) ? 0 : 15));
					//alert($new_selected.getChildren()[1].hasClass('Hidden'));
				}
			} else if(evt.key == 'up'){
				$selected = $('DestinationSelectorResult[' + $idx + ']').getChildren('div[class*="Destination_Selected"]')[0];
				if($selected != null)
					$new_selected = $selected.getPrevious('div[style$="solid;"]');
				if($new_selected != null){
					$new_selected.addClass('Destination_Selected');
					$selected.removeClass('Destination_Selected');
					$('DestinationSelectorResult[' + $idx + ']').scrollTo(0,$('DestinationSelectorResult[' + $idx + ']').getScroll().y - 17 - (($selected.getChildren()[1].hasClass('Hidden')) ? 0 : 15));
				}
			}  else {
				$('DestinationSelectorResult[' + $idx + ']').scrollTo(0,0);
				$('DestinationSelectorResult[' + $idx + ']').setStyle("display", '');
				$('DestinationSelectorCancel[' + $idx + ']').setStyle("display", '');
				find_possible_destinations($('DestinationSelector[' + $idx + ']').value, $idx);
			}
	});
	
}

function load_public_destination_data($franchise){
    var jsonRequest = new Request.JSON({url: 'xhr/get_public_destinations.php', onSuccess: function(data, text){
        $avaiable = data;
        alloc_all_cached_selectors();
        dataLoaded = true;
        $$('span[id=PublicDestText]').each(function(item2){
            item2.removeEvents('click');
        });
    }, onError: function(t,e){
}}).get({'franchise': $franchise});

}

function create_public_destination_selector($idx){
	if(dataLoaded){
	   alloc_public_destination_selector($idx);
	} else {
	   selectors[numSelectors] = $idx;
	   numSelectors++;
	}
}


function alloc_all_cached_selectors(){
    for(var i = 0; i < numSelectors; i++){
        alloc_public_destination_selector(i);
    }
    numSelectors = 0;
    selectors = [];
}
function URLDecode(psEncodeString)
{
  // Create a regular expression to search all +s in the string
  var lsRegExp = /\+/g;
  // Return the decoded string
  return unescape(String(psEncodeString).replace(lsRegExp, " "));
}