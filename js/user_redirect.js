window.addEvent('domready',function(){
	var links = $$('a.User_Redirect');
	links.each( function(item){
		item.href = "/xhr/affected_user_redirect.php?redirect=" + encodeURI(item.href) + "&userid=" + item.id;
	});
});