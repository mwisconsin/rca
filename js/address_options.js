/**
 * @author Joel Bixby
 */
var address_display = new Class({
	initialize: function(){
		$tables = document.getElementsByName('address_display');
		for(var $i = 0; $i < $tables.length; $i++){
			$tables[$i].addEvent('mouseenter', function(){
				new Element('td', {
					'colspan': 3,
					'html': '<a href=\"#' + this.id + '\" onclick="window.open(\'mapquest_map_location.php?id=' + this.id + '\',\'Window1\', \'menubar=no,width=700,height=400,toolbar=no\');">View Map</a>',
					'styles': {
	        			'font-size': '.9 em',
	        			'text-align': 'right'
	    			}
				}).inject(new Element('tr', {
					'id': 'address_options_row'
				}).inject(this));
			});
			$tables[$i].addEvent('mouseleave', function(){
				$('address_options_row').dispose();
			});
		}
	}
});
document.addEvent('domready',function(){
	var address_options = new address_display();
});


