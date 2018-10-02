/**
 * @author Joel Bixby
 */
var Shade = new Class({
	shade: null,
	initialize: function(){
		this.shade = new Element('div',{
			'styles': {
				'background': '#000',
				'width': document.body.getScrollSize().x,
				'height': document.body.getScrollSize().y,
				'opacity': .10,
				'position': 'absolute',
				'top': 0,
				'left': 0
			}
		});
		this.shade.inject(document.body);
	},
	remove: function(){
		this.shade.destroy();
	}
});