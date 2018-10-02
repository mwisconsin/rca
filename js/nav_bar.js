$links = $$('#nav a');
$location = document.location.toString();
$links.each(function(item){
	if( $location.match(item.href))
		item.addClass('Active_Page');
});
//$('nav_bar').setStyle('position','fixed');
//$('nav_bar').setStyle('top',0);
//$('nav_bar').setStyle('left',0);
$current_position = "top";
window.document.addEvent('scroll', function(evt){
	//$('nav_bar').setStyle('top', (window.pageYOffset > 90) ? window.pageYOffset - 90 : 0);
	if(window.pageYOffset >= 90 && $current_position != "float"){
		$('nav_bar').setStyle('position','fixed');
		$('nav_bar').setStyle('top',0);
		$('nav_bar').setStyle('left',0);
		$current_position = "float";
	} else if(window.pageYOffset < 90 && $current_position != "top") {
		$('nav_bar').setStyle('position','relative');
		$('nav_bar').setStyle('width', "100%");
		$current_position = "top";
	}
	
});