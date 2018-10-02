<?php
    include_once 'include/database.php';
	include_once 'include/user.php';
	include_once 'include/date_time.php';
	include_once 'include/link.php';
	error_reporting(E_ALL);
	redirect_if_not_logged_in();
	$franchise = get_current_user_franchise();
	
	if(current_user_has_role(1, "FullAdmin") || current_user_has_role($franchise, "Franchisee")){
		header("Location: home.php");
		die();	
	}
	
	
	include_once 'include/header.php';
	?><!--[if IE]>
	  	<script src="js/excanvas.compiled.js" type="text/javascript"></script>
	  <![endif]-->
	  <script src="js/highcharts.js" type="text/javascript"></script><?php
	$months = array('January','February','March','April','May','June','July','August','September','October','November','December');
	$month_data = array();
	$year = isset($_GET['Year']) ? $_GET['Year'] : date("Y");
	
	for($i = 1; $i <= 12; $i++){
		$month_data[$i] = get_link_monthly_count($franchise, $i, $year);
		unset($month_data[$i][count($month_data[$i]) - 1]);
	}
	$weeks = get_link_weekly_count($franchise, $year);
?>

<style>
	td{
		padding:2px;
	}
</style>


<center><h2><form method="get" id="date_year"><?php 
	get_year_selector(2010, date("Y") + 1, @$_GET['Year']);
echo " Monthly Reports"; ?></form></h2></center>
<script>
	$('date_year').getFirst().addEvent("change", function(){
														  this.getParent().submit();
														  });
</script>
<div id="year-day" style="width: 100%; height: 400px"></div>
<table width="100%" border="1">
	<tr>
		<td></td>
		<?php
			for($i = 1; $i <= 31; $i++)
				echo "<th>$i</th>";
		?>
	</tr>
	<?php
		for($i = 1; $i <= 12; $i++){
			echo '<tr>';
				echo "<th>" . $months[$i - 1] . "</th>";
				$link_count = $month_data[$i];
				foreach($link_count as $link)
					echo "<td>$link</td>";
			echo '</tr>';
		}
	?>
</table>
<center>
	<h2>Links Per Week</h2>
</center>
<table width="100%" border="1">
	<tr>
		<td>Week #</td>
		<?php
			for($i = 1; $i <= 31; $i++)
				echo "<th>$i</th>";
		?>
	</tr>
	<tr>
		<td>Links</td>
		<?php
			for($i = 1; $i < 32; $i++){
				echo "<td>{$weeks[$i]}</td>";
			}
		?>
	</tr>
	<tr>
		<td>Week #</td>
		<?php
			for($i = 32; $i <= 52; $i++)
				echo "<th>$i</th>";
		?>
	</tr>
	<tr>
		<td>Links</td>
		<?php
			for($i = 32; $i < 53; $i++){
				echo "<td>{$weeks[$i]}</td>";
			}
		?>
	</tr>
</table>
<center>
	<h2>Daily percents for last 6 months</h2>
</center>
<div id="daily-average" style="float:right; border:1px solid; width:400px; height:300px;"></div>
<?php
	$percent = get_links_week_6_month_percentages($franchise);
?>
<table border="1">
	<tr>
		<td></td>
		<th>Sunday</th>
		<th>Monday</th>
		<th>Tuesday</th>
		<th>Wednesday</th>
		<th>Thursday</th>
		<th>Friday</th>
		<th>Saturday</th>
	</tr>
	<tr>
		<th>Averages</th>
		<td><?php echo number_format($percent['Sunday'] * 100, 1); ?>%</td>
		<td><?php echo number_format($percent['Monday'] * 100, 1); ?>%</td>
		<td><?php echo number_format($percent['Tuesday'] * 100, 1); ?>%</td>
		<td><?php echo number_format($percent['Wednesday'] * 100, 1); ?>%</td>
		<td><?php echo number_format($percent['Thursday'] * 100, 1); ?>%</td>
		<td><?php echo number_format($percent['Friday'] * 100, 1); ?>%</td>
		<td><?php echo number_format($percent['Saturday'] * 100, 1); ?>%</td>
	</tr>
</table>


<script type="text/javascript">
window.addEvent('domready', function(){
	chart1 = new Highcharts.Chart({
         chart: {
            renderTo: 'year-day',
            defaultSeriesType: 'spline',
			zoomType: 'x'
         },
         title: {
            text: '<?php echo "$year Monthly Reports"; ?>'
         },
         xAxis: {
		 	type: 'datetime',
			maxZoom: 14 * 24 * 3600000,
      		title: null

         },
         yAxis: {
            title: {
               text: 'Links'
            }
         },
		 tooltip: {
		      formatter: function() {
		         return '<b>'+ this.y + " " + (this.point.name || this.series.name) +'</b><br/>'+
		            Highcharts.dateFormat('%A %B %e %Y', this.x);
		      }
		 },

         series: [{
		      type: 'area',
		      name: 'Links per Week',
		      pointInterval: 604800000, // one week
		      <?php 
		          $starting_date_dayOfWeek = date('w', strtotime("$year-1-1"));
                  $days_skip = ($starting_date_dayOfWeek > 0) ? 7 - $starting_date_dayOfWeek : 1;
                  $starting_date = strtotime("$year-1-" . $days_skip);
                  $days_skip > 0 ? $starting_date = $starting_date - 604799 : '';
		      ?>
		      pointStart: Date.UTC(<?php echo date('Y', $starting_date); ?>,<?php echo date('n', $starting_date) - 1; ?>, <?php echo date('d', $starting_date); ?>),
		      data: [ 
			  <?php
			  		foreach($weeks as $week_count)
						echo $week_count . ',';
			  ?>
		      ]
		   },{
		      type: 'area',
		      name: 'Links per Day',
		      pointInterval: 24 * 3600 * 1000, // daily
		      pointStart: Date.UTC(<?php echo $year; ?>, 0, 01),
		      data: [ 
			  <?php 
			  		foreach($month_data as $month){
			  			foreach($month as $day)
							echo $day . ",";
			  		}
			  ?>
		      ]
		   }
		   ]
      });
	  var chart = new Highcharts.Chart({
		   chart: {
		      renderTo: 'daily-average',
		      margin: [25, 100, 25, 65]
		   },
		   title: {
		      text: 'Daily Percents'
		   },
		   plotArea: {
		      shadow: null,
		      borderWidth: null,
		      backgroundColor: null
		   },
		   tooltip: {
		      formatter: function() {
		         return '<b>'+ this.point.name +'</b>: '+ this.y +' %';
		      }
		   },
		   plotOptions: {
		      pie: {
		         allowPointSelect: true,
		         dataLabels: {
		            enabled: true,
		            formatter: function() {
		               if (this.y > 5) return this.point.name;
		            },
		            color: 'white',
		            style: {
		               font: '13px Trebuchet MS, Verdana, sans-serif'
		            }
		         }
		      }
		   },
		   legend: {
		      layout: 'vertical',
		      style: {
		         left: 'auto',
		         bottom: 'auto',
		         right: '0px',
		         top: '100px'
		      }
		   },
		        series: [{
		      type: 'pie',
		      name: 'daily Percents',
		      data: [
		         ['Sunday', <?php echo $percent['Sunday'] * 100; ?>],
		         ['Monday', <?php echo $percent['Monday'] * 100; ?>],
		         ['Tuesday', <?php echo $percent['Tuesday'] * 100; ?>],
		         ['Wednesday', <?php echo $percent['Wednesday'] * 100; ?>],
				 ['Thursday', <?php echo $percent['Thursday'] * 100; ?>],
		         ['Friday', <?php echo $percent['Friday'] * 100; ?>],
		         ['Saturday', <?php echo $percent['Saturday'] * 100; ?>]
		      ]
		   }]
		});


});
</script>
<?php
	include_once 'include/footer.php';
?>
