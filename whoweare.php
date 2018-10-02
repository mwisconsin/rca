<?php
    include_once 'include/header.php';
?>
<ul>
	<li class="nav_link small_nav_link" id="Welcome" onclick="$('Welcome_Content').setStyle('display','block'); $('History_Content').setStyle('display','none'); $('Leadership_Content').setStyle('display','none');"><a href="#">Welcome to Riders Club</a></li>
	<li class="nav_link small_nav_link" id="History" onclick="$('Welcome_Content').setStyle('display','none'); $('History_Content').setStyle('display','block'); $('Leadership_Content').setStyle('display','none');"><a href="#">History</a></li>
	<li class="nav_link small_nav_link" id="Leadership" onclick="$('Welcome_Content').setStyle('display','none'); $('History_Content').setStyle('display','none'); $('Leadership_Content').setStyle('display','block');"><a href="#">Leadership</a></li>
</ul>
<div id="Welcome_Content">
	<center><h2>Welcome to Riders Club of America</h2></center>
	Riders Club is more than just a way to get from here to there. Riders Club is a way to maintain freedom. Our mission is very simple:<br>
	<center><p><b>We provide scheduled transportation for mobile seniors or the visually or hearing impaired to improve quality of life.</b></p></center><br>
	By offering a low-cost ride-share program, we hope to ease the burden of trying to live independently without a vehicle. The concept is based on a simple principle:</br>
	<p>
		<ul>
			<b>People helping people.</b>
		</ul>
	</p>
	Our drivers are volunteers, helping others get where they need to go. It's the way we provide you:<br />
	<p>
		<ul>
		<b>Freedom:</b> to get where you need to go, when you need to get there.<br />
		<b>Independence:</b> so you won't need to bother family and friends to get a ride.<br />
		<b>Savings:</b> often costing less than the monthly cost of insurance.<br />
		<b>Service:</b> so you don't have to drive. It's like having your own chauffeur.<br />
		<b>Convenience:</b> we come to your door so you can wait in the comfort of your home.<br />
		<b>Security:</b> of a qualified driver.<br />
		<b>Support:</b> your caregiver travelling the same route at the same time travels at no additional cost.<br />
		</ul>
	</p>
	
</div>
<div style="display:none;" id="History_Content">
	<center><h2>Where we came from</h2></center>
	While attending a rotary meeting in Dubuque, Iowa, Martin Wissenberg listened intently as the presenter spoke of a new concept
	for the city, a ride share program for people who could no longer drive themselves. By coordinating riders with volunteer drivers,
	DuRide of Dubuque could meet a growing need in that community.<br />
	<br />
	The more he thought about it, the more he thought, "I can do that," and Riders Club of America was born. Using his logistics 
	background, Mr. Wissenberg was able to create a powerful software program that managed most aspects of the business.
	By locating volunteer drivers who were available when riders were needed, what was once a chore became a simple process.
	Rather than struggling to get money to Riders Club, an automated pre-payment system means riders can just get in the vehicle
	and go.<br />
	<br />
	Within months of its simple beginnings, Mr. Wissenberg had spoken with senior care providers and city officials in the Cedar
	Rapids, Iowa area and had his plans in place. Riders Club of America was providing rides to those in need.
</div>
<div style="display:none;" id="Leadership_Content">
	<center><h2>Leadership</h2></center>
	<div style="border:1px solid; float:right; height:300px; width:250px;">Picture</div>
	<br /><b>Executive Director</b><br />
	Martin Wissenberg<br />
	<br />
	Mr. Wissenberg comes to Riders Club of America with 12 years experience in 
	designing and polishing process improvements in the Midwest, and 8+ years of 
	sales and sales management. From maximizing margin to process development 
	for both start-up and Fortune 500 companies, Martin has a consistent record of 
	increasing the efficiency and productivity for the fast growing companies with which he 
	works.<br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br />
	<div style="border:1px solid; clear:both; float:left; height:300px; width:250px;">Picture2</div>
	<br /><b>Board Chair</b><br />
	Elizabeth Trcka<br />
	<br />
	Ms. Trcka, founding partner of the Skywalk Group, formed the human resources, 
	training and development, and professional recruiting company in 2002 after 
	working for McLeodUSA for eight years as the Director of Human Resources 
	and Staffing. Elizabeth has over twenty years of proven management and 
	Human Resources experience, working with start-up as well as Fortune 500 
	companies throughout the US and abroad.<br /><br /><br /><br /><br /><br /><br /><br /><br /><br />
	<div style="border:1px solid; clear:both; float:right; height:300px; width:250px;">Picture3</div>
	<br /><br /><b>Treasurer</b><br />
	Jim Balvanz<br />
	<br />
	Mr. Balvanz brings over 30 years of financial management experience in both 
	private and public companies primarily in the communications and contact 
	center industries. He has held several executive level positions, managed 
	virtually all facets of financial operations, and is skilled in the development of 
	fast growing medium sized businesses into larger publicly traded companies.
	Jim has been involved in multiple acquisition and capital rise projects from 
	business planning to implementation.
</div>
<script type="text/javascript">
$('Welcome').addEvent('click', function () {
		$('Welcome_Content').setStyle('display','block');
		$('History_Content').setStyle('display','none');
		$('Leadership_Content').setStyle('display','none');
});
</script>

<?php
	include_once 'include/footer.php';
?>
