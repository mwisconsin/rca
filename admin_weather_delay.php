<?php
	include_once 'include/user.php';
	include_once 'include/franchise.php';
	include_once 'include/weather.php';
	include_once 'include/time_delay.php';
	include_once 'include/repeat_date.php';
	
	redirect_if_not_logged_in();
	$franchise = get_current_user_franchise();
	
	if(!current_user_has_role(1 , "FullAdmin") && !current_user_has_role($franchise, "Franchisee")){
		header("Location: home.php");
		die();	
	}

	if($_POST['ZipCode']){
		$_SESSION['FranchiseZip'] = $_POST['ZipCode'];
	}
	
	if($_POST['SaveDelays']){
		$keys = array_keys($_POST['DelaySelect']);
		
		foreach($keys as $key){
			set_weather_time_delay($franchise, $key, $_POST['DelaySelect'][$key], 'WEATHER' );	
		}
	}
	
	function weather_date_to_reg($date){
		return date("Y-n-j", strtotime($date . date(" Y")));
	}
	
	function get_selector($date){
		global $franchise;
		$date = weather_date_to_reg($date);
		$delay = get_weather_time_delay($franchise, $date, 'WEATHER');
		$str = "<select name=\"DelaySelect[$date]\">";
		
		for($i = 0; $i < 100; $i += 5)
			$str .= "<option value=\"1." . str_pad($i,2,"0", STR_PAD_LEFT) . "\"" . (($delay == "1." . str_pad($i,2,"0", STR_PAD_LEFT)) ? "SELECTED" : "") . ">$i %</option>";
		
		$str .= "</select>";
		return $str;
	}
	$zips = get_franchise_service_zips($franchise);
	$zips = array_keys($zips);
	$weather = get_weather($_SESSION['FranchiseZip'] ? $_SESSION['FranchiseZip'] : $zips[0] );
	
	include_once 'include/header.php';
?>
<h2>Weather Delay</h2>

<form method="post">
	Weather is currently being selected for zip code: 
    <select name="ZipCode" onChange="this.getParent().submit();">
        <?php
        foreach($zips as $zip){
            ?>
        <option value="<?php echo $zip ?>" <?php if($_SESSION['FranchiseZip'] == $zip) echo 'SELECTED'; ?>><?php echo $zip; ?> </option>
        <?php } ?>
    </select>
</form>
<?php
print_r($weather);
?>
<!-- cut and paste the below code into your HTML editor -->
<div id="wx_module_5665">
   <a href="http://www.weather.com/weather/local/52401">Cedar Rapids
Weather Forecast, IA (52401)</a>
</div>
<script type="text/javascript">

   /* Locations can be edited manually by updating 'wx_locID' below.
Please also update */
   /* the location name and link in the above div (wx_module) to
reflect any changes made. */
   var wx_locID = '52401';

   /* If you are editing locations manually and are adding multiple
modules to one page, each */
   /* module must have a unique div id.  Please append a unique # to
the div above, as well */
   /* as the one referenced just below.  If you use the builder to
create individual modules */
   /* you will not need to edit these parameters. */
   var wx_targetDiv = 'wx_module_5665';

   /* Please do not change the configuration value [wx_config]
manually - your module */
   /* will no longer function if you do.  If at any time you wish to
modify this */
   /* configuration please use the graphical configuration tool found at */
   /* https://registration.weather.com/ursa/wow/step2 */
   var wx_config='SZ=300x250*WX=FHC*LNK=WWLD*UNT=F*BGI=seasonal1*MAP=null|null*DN=myridersclub.com*TIER=0*PID=1298914118*MD5=0d37feb29ffcf65a3690847466a70d76';
   var protocol = 'http:'; // document.location.protocol
   document.write('<scr'+'ipt src="'+protocol+'//wow.weather.com/weather/wow/module/'+wx_locID+'?config='+wx_config+'&proto='+protocol+'&target='+wx_targetDiv+'"></scr'+'ipt>');
//alert(document.location.protocol+'//wow.weather.com/weather/wow/module/'+wx_locID+'?config='+wx_config+'&proto='+document.location.protocol+'&target='+wx_targetDiv);
   </script>


<div>
	<span style="font-size:1.5em">Current Conditions</span><br>
    <span style="font-size:1em">Last Updated: <?php echo $weather['cc']['lsup']; ?></span><br>
    <img src="images/weather_icons/93x93/<?php echo $weather['cc']['icon']; ?>.png" alt="" >
    <span style="font-size:1.3em"><?php echo $weather['cc']['tmp']; ?>&deg;</span><br>
    <?php echo $weather['cc']['t']; ?>
</div><br>
<hr>
<form method="post">
    <div style="float:left; width:19%; border-right:1px #000 solid; padding:4px;">
        <?php echo date('l m/d/Y', time()); ?>
        <span style="font-size:1.3em;"><?php echo $weather['dayf']['day'][0]['@attributes']['t'] . ", " . $weather['dayf']['day'][0]['@attributes']['dt']; ?></span><br>
        <img src="images/weather_icons/93x93/<?php echo $weather['dayf']['day'][0]['part'][0]['icon']; ?>.png" alt=""><br>
        High: <?php echo $weather['dayf']['day'][0]['hi'];?>&deg;<br>
        Low: <?php echo $weather['dayf']['day'][0]['low'];?>&deg;<br>
        <br>
        Morning: <?php echo $weather['dayf']['day'][0]['part'][0]['t'];?><br>
        Night: <?php echo $weather['dayf']['day'][0]['part'][1]['t'];?><br>
        <br>
        Weather Delay: <?php echo get_selector($weather['dayf']['day'][0]['@attributes']['dt']); ?>
    </div>
    <div style="float:left; width:19%; border-right:1px #000 solid; padding:4px;">
        <?php echo date('l m/d/Y', time()+86400); ?>
        <span style="font-size:1.3em;"><?php echo $weather['dayf']['day'][1]['@attributes']['t'] . ", " . $weather['dayf']['day'][1]['@attributes']['dt']; ?></span>
        <img src="images/weather_icons/93x93/<?php echo $weather['dayf']['day'][1]['part'][0]['icon']; ?>.png" alt=""><br>
        High: <?php echo $weather['dayf']['day'][1]['hi'];?>&deg;<br>
        Low: <?php echo $weather['dayf']['day'][1]['low'];?>&deg;<br>
        <br>
        Morning: <?php echo $weather['dayf']['day'][1]['part'][0]['t'];?><br>
        Night: <?php echo $weather['dayf']['day'][1]['part'][1]['t'];?><br>
        <br>
        Weather Delay: <?php echo get_selector($weather['dayf']['day'][1]['@attributes']['dt']); ?>
    </div>
    <div style="float:left; width:19%; border-right:1px #000 solid; padding:4px;">
        <?php echo date('l m/d/Y', time()+86400*2); ?>
        <span style="font-size:1.3em;"><?php echo $weather['dayf']['day'][2]['@attributes']['t'] . ", " . $weather['dayf']['day'][2]['@attributes']['dt']; ?></span>
        <img src="images/weather_icons/93x93/<?php echo $weather['dayf']['day'][2]['part'][0]['icon']; ?>.png" alt=""><br>
        High: <?php echo $weather['dayf']['day'][2]['hi'];?>&deg;<br>
        Low: <?php echo $weather['dayf']['day'][2]['low'];?>&deg;<br>
        <br>
        Morning: <?php echo $weather['dayf']['day'][2]['part'][0]['t'];?><br>
        Night: <?php echo $weather['dayf']['day'][2]['part'][1]['t'];?><br>
        <br>
        Weather Delay: <?php echo get_selector($weather['dayf']['day'][2]['@attributes']['dt']); ?>
    </div>
    <div style="float:left; width:19%; border-right:1px #000 solid; padding:4px;">
        <?php echo date('l m/d/Y', time()+86400*3); ?>
        <span style="font-size:1.3em;"><?php echo $weather['dayf']['day'][3]['@attributes']['t'] . ", " . $weather['dayf']['day'][3]['@attributes']['dt']; ?></span>
        <img src="images/weather_icons/93x93/<?php echo $weather['dayf']['day'][3]['part'][0]['icon']; ?>.png" alt=""><br>
        High: <?php echo $weather['dayf']['day'][3]['hi'];?>&deg;<br>
        Low: <?php echo $weather['dayf']['day'][3]['low'];?>&deg;<br>
        <br>
        Morning: <?php echo $weather['dayf']['day'][3]['part'][0]['t'];?><br>
        Night: <?php echo $weather['dayf']['day'][3]['part'][1]['t'];?><br>
        <br>
        Weather Delay: <?php echo get_selector($weather['dayf']['day'][3]['@attributes']['dt']); ?>
    </div>
    <div style="float:left; width:19%; padding:4px;">
        <?php echo date('l m/d/Y', time()+86400*4); ?>
        <span style="font-size:1.3em;"><?php echo $weather['dayf']['day'][4]['@attributes']['t'] . ", " . $weather['dayf']['day'][4]['@attributes']['dt']; ?></span>
        <img src="images/weather_icons/93x93/<?php echo $weather['dayf']['day'][4]['part'][0]['icon']; ?>.png" alt=""><br>
        High: <?php echo $weather['dayf']['day'][4]['hi'];?>&deg;<br>
        Low: <?php echo $weather['dayf']['day'][4]['low'];?>&deg;<br>
        <br>
        Morning: <?php echo $weather['dayf']['day'][4]['part'][0]['t'];?><br>
        Night: <?php echo $weather['dayf']['day'][4]['part'][1]['t'];?><br>
        <br>
        Weather Delay: <?php echo get_selector($weather['dayf']['day'][4]['@attributes']['dt']); ?>
    </div>
    <input style="clear:both; float:right;" type="submit" name="SaveDelays" value="Save Delays">
</form>
<?php
	include_once 'include/footer.php';
?>