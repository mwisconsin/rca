/**
 * @author Joel Bixby
 */

var Address = new Class({
	initialize: function(){
		document.write('<script type="text/javascript" src="js/shade.js"></script>');
		var $address_tables = document.getElementsByName('Address_Table');

		for(var $i = 0; $i < $address_tables.length; $i++){
			
			var $wrong_try = 0;
			$element = $address_tables[$i];
			
			$element = $element.getParent('form');
			$element.set('onsubmit', 'return false;');
			
			$element.addEvent('submit',function(event){
				
				function address_input($name){
					$inputs = $element.getElements('input');
					for(var $j = 0; $j < $inputs.length; $j++){
						if($inputs[$j].get('name') == $name)
							return $inputs[$j].value;
					}
					$inputs = $element.getElements('select');
					for(var $j = 0; $j < $inputs.length; $j++){
						if($inputs[$j].get('name') == $name)
							return $inputs[$j].value;
							
					}
					return false;
				}
				$address = { Address1: address_input('data[Emergency][Address1]'),
							 Address2: address_input('data[Emergency][Address2]'),
							 City: address_input('data[Emergency][City]'),
							 State: address_input('data[Emergency][State]'),
							 ZIP5: address_input('data[Emergency][Zip5]')
						   }
				var shade = new Shade();
				//alert ($address.Address1);
				// check to makre sure we are not submitting blank info
				if (($address.Address1=='')&&($address.Address1=='')&&($address.Address1=='')&&($address.Address1=='')&&($address.Address1=='')) {
				    $element.submit();
					shade.remove();
				} else {
					address.verify($address,function(Success){
						if (Success) {
							$element.submit();
							shade.remove();
						}
						else {
							$wrong_try++;
							alert('This address cannot be verified. Please try again. [attempt ' + $wrong_try + ']');
							shade.remove();
						}
							
					});
				}
			});
		}
	},
	verify: function( $address, $onComplete){
		var $myRequest = new Request({
							url: 'xhr/validate_address2.php', 
							method: 'get',
							onComplete: function(response){
								if(response == 'true')
									$onComplete(true);
								else
									$onComplete(false);
							},
							onFailure:function(xhr){
								alert('An Error Has Occurred')
							}}).send('Address1=' + escape($address.Address1) + 
									 '&Address2=' + escape($address.Address2) + 
									 '&City=' + escape($address.City) + 
									 '&State=' + escape($address.State) + 
									 '&ZIP5=' + escape($address.ZIP5));
	}
});
if(address == null)
	var address = new Address();
	


