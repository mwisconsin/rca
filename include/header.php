<?php 
//error_reporting(E_ALL);
//error_reporting(0);
$page_arr = preg_split('/\//', $_SERVER['PHP_SELF']);
$page = $page_arr[sizeof($page_arr)-1];
    include_once 'include/functions.php';
	include_once 'include/user.php';
	require_once('include/rider.php');
	require_once('include/date_time.php');
    require_once('include/large_facility.php');
	include_once 'include/franchise.php';

// TODO:  If user is admin, load admin JS after mootools JS.

//session_start();
// TODO:  Some pages header-ize after tryin to call LF funcs that expect LF_Facility_ID to be set.
$_SESSION['WORKINGFRANCHISE'] = 2;
unset ($_SESSION['LF_Facility_ID']);

$franchise_id = 0;

if(is_logged_in()){
	$franchise_id = get_current_user_franchise(FALSE);
if (user_has_role(get_affected_user_id(), $franchise_id, 'LargeFacilityAdmin')) {
    if ($lf_id = get_large_facility_id_for_user(get_affected_user_id())) {
        $_SESSION['LF_Facility_ID'] = $lf_id;
    }
}
	
}
if (isset($_GET['action']) && ($_GET['action']=='post_note') && (isset($_POST['note'])) && ($_POST['note']!='')) {
	  $cn = new ContactNarrative();
	  $cn->saveUserNarrative($user_id, $_SESSION['UserID'], $_POST['note']);
}
set_franchise_timezone(2);  // TODO:  Per-user/franchise TZ
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" style="width:100%;">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Riders Club of America</title>
		<meta name="google-site-verification" content="1zjWTBav57yrjS9D1xBLAxrx6BLWOR92z-ESZtCJiVw" />
		<meta name="description" content="Riders Club of America provides rides for seniors who prefer no longer to drive. By Coordinating volunteer drivers and senior riders, we allow people to maintain their freedom after they no longer drive a car." />
		<meta name="keywords" content="rides,america,non-profit,volunteers,riders,drivers,supporters,franchise,elders,seniors,drive,driving,freedom,car,club,convenience,501.c.3,ride,cedar rapids,donate,myridersclub,independence,savings,service,security,support,transportation,mobile,impaired,quaility,ride-share" />
		<link rel="icon" type="image/ico" href="/favicon.ico?v=2"/>
        <link rel="stylesheet" type="text/css" href="/css/main.css" />
        <link rel="stylesheet" type="text/css" href="/css/datepicker.css" />

    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>   
		<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/themes/smoothness/jquery-ui.css" />
		<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/jquery-ui.min.js"></script> 
    <script>jQuery.noConflict();</script>
    <script>
    jQuery(function($) {
    	$('.jq_datepicker').filter(function() {
    		var dval = $(this).val();
    		$(this).datepicker({
	    		language: "en",
	    		showOtherMonths: true,
	      	selectOtherMonths: true,
	      	numberOfMonths: 2,
	      	showCurrentAtPos: 0    			
    		});
    		var dp = $(this).datepicker().data('datepicker');
    		if(dval !== '') dp.selectDate(new Date(dval));
    	});
    });	
    </script>
		<script src="/js/mootools.js" type="text/javascript"></script>
		<script src="/js/mootools-more.js" type="text/javascript"></script>
		<script src="/js/js-cookie.js" type="text/javascript"></script>
		<script src="/js/general-functions.js" type="text/javascript"></script>
		<link rel="stylesheet" href="/css/air-datepicker/datepicker.min.css" />
		<script src="/js/air-datepicker/datepicker.min.js" type="text/javascript"></script>
		<script src="/js/air-datepicker/i18n/datepicker.en.js"></script>
<?php if (is_logged_in() && (current_user_has_role($franchise_id,'FullAdmin') || current_user_has_role($franchise_id,'Franchisee'))) { ?>
        <script src="/js/user_redirect.js" type="text/javascript"></script>
<?php } ?>
<?php
    global $ADDITIONAL_RC_JAVASCRIPT;
    if (is_array($ADDITIONAL_RC_JAVASCRIPT)) { 
		foreach ($ADDITIONAL_RC_JAVASCRIPT as $ARJ) { ?>
            <script src="/js/<?php echo $ARJ ?>" type="text/javascript"></script>
<?php   } 
    } ?>
    <!--script src="/js/jquery.js" type="text/javascript">jQuery.noConflict();</script//-->
		<script>
		  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
		
		  ga('create', 'UA-37552240-2', 'auto');
		  ga('send', 'pageview');
		
		</script>
    </head>
    <body <?php echo ($page=='login.php') ? ' onload="document.getElementById(\'txt_user_name\').focus();"' : ''; ?>>
    <div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
    	<?php 
			if (is_logged_in() && get_current_user_franchise(FALSE) && (current_user_has_role(1 , 'FullAdmin') || current_user_has_role($franchise_id, 'Franchisee')))
				include_once 'user_selector.php';
		?>
        <div id="container">
            <div id="header" class="noprint">
                <div id="logo">
                    <div id="header_logo_left">
                        <img src="<?php 
						  echo site_url(); 
						  
						  echo getFranchiseLogo($franchise_id);
						?>" alt="">
                        
                    </div>
                    <div id="header_logo_top_right">
                    	<br><?php if ($_SERVER['PHP_SELF'] != '/support_list.php') { ?> 
						<button onclick="window.location = '<?php 
                            if (is_logged_in()) {
                                echo 'support_list.php';
                            } else { 
                                echo site_url() . "donate.php";
                            } ?>';" style="width:230px; height:35px; margin:5px;">Donations</button><?php } ?>
                    </div>
                    <div id="header_logo_right">
                        24 hours a day, 7 days a week, connecting volunteers and riders
                    </div>
                </div>
                <div class="float_clear nav_place_holder">
                <div id="nav_bar" style="position:relative;">
   	                <div id="header_topline"></div>
                    <div id="nav">
                        <?php 
                            get_navigation_bar( get_affected_user_id() );
                        ?>
                    </div>
                    <div id="header_bottomline"></div>
                </div>
                </div>
            </div>
            <div id="body"><p style="margin: 0; font-size: smaller;"><?php echo date('F j, Y g:iA'); ?></p>
            <?php user_string( get_affected_user_id() );?>
