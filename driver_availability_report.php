<?php
	include_once 'include/user.php';
	redirect_if_not_logged_in();
	
	$franchise = get_current_user_franchise();
	
	if(!current_user_has_role(1 , "FullAdmin") && !current_user_has_role($franchise, "Franchisee")){
		header("Location: home.php");
		die();	
	}
	
	
	
	include_once 'include/header.php';

?>
	<center><h2>Driver Availability Report</h2></center>
	
    <?php 
    $weekday = array('Sun', 'Mon', 'Tue', 'Wed', 'Thr','Fri','Sat');

    $today = date('m/d/y');
	$today1 = date('m/d/y', time()+86400);
	$today2 = date('m/d/y', time()+86400*2);
	$today3 = date('m/d/y', time()+86400*3);
	$today4 = date('m/d/y', time()+86400*4);
	$today5 = date('m/d/y', time()+86400*5);

	
	$driver_query = "select ur.FranchiseID,  u.UserID, pn.FirstName, pn.LastName, ds.OtherNotes, ds.OnCall from driver d, driver_settings ds, users u, person_name pn, user_role ur  where d.DriverStatus='Active' and u.UserID=d.UserID and pn.PersonNameID=u.PersonNameID and ur.UserID=d.UserID and ur.Role='Driver' and ds.UserID=d.UserID and ur.FranchiseID=$franchise order by pn.LastName ";
	$driver_result = mysql_query($driver_query);




	?>
<style>
.scrollTableHeader {
  position: fixed;
  top: 29px;
  visibility: hidden;	
  background-color: white;
}	
</style>
<script>
jQuery(function($) {
	$('.scrollTableHeader').width( $('#daTable').width() );
	var $ind = 0;
	$('.scrollTableHeader th').each(function(k,v) {
		$(v).width( $($('#daTable th')[$ind]).width() );
		$ind++;
	});
 	$(window)
  	.scroll(UpdateTableHeaders)
  	.trigger("scroll");
});	
function UpdateTableHeaders() {
	el						 = jQuery(jQuery('#daTable tr')[0]);
	floatingHeader = jQuery('.scrollTableHeader');
	offset         = el.offset();
	scrollTop      = jQuery(window).scrollTop();
	
	if (scrollTop > offset.top + el.height()) {
	   floatingHeader.css({
	    "visibility": "visible"
	   });
	} else {
	   floatingHeader.css({
	    "visibility": "hidden"
	   });      
	}
}	
</script>
 <div class=scrollTableHeader>
 	<table cellpadding="2" cellspacing="0" width="100%">
       <tr class="table_da_title_row">
        <th style="border-right:1px solid gray;">Name</th>
        <th style="border-right:1px solid gray;">CL ID</th>
        <th style="border-right:1px solid gray;">US ID</th>
        <th style="border-right:1px solid gray;">Cell</th>
        <th style="border-right:1px solid gray;">Home</th>
        <th style="border-right:1px solid gray;">Work</th>
        <th style="border-right:1px solid gray;">Notes</th>
        <th style="border-right:1px solid gray;">Vaca</th>
        <th style="border-right:1px solid gray;">hrs day</th>
        <th style="border-right:1px solid gray;">hrs wk</th>
        <th>hrs mo</th>
        <th style="border-left:1px solid black;border-right:1px solid gray;">day wk</th>
        <th style="border-right:1px solid black;">day mo</th>
        <th style="border-right:1px solid black;">last upt</th>
        <th style="border-right:1px solid black;">on call</th>
        <th style="border-right:1px solid gray;" align="center"><?php 
        	echo date('m/d/y') . "<BR>" . date('D');
		  ?></th>
        <th style="border-right:1px solid gray;" align="center"><?php 
        	echo date('m/d/y',strtotime('+1 day')) . "<BR>" . date('D',strtotime('+1 day'));
		  ?></th>
        <th style="border-right:1px solid gray;" align="center"><?php 
        	echo date('m/d/y',strtotime('+2 days')) . "<BR>" . date('D',strtotime('+2 days'));
		  ?></th>
        <th style="border-right:1px solid gray;" align="center"><?php 
        	echo date('m/d/y',strtotime('+3 days')) . "<BR>" . date('D',strtotime('+3 days'));
		  ?></th>
        <th style="border-right:1px solid gray;" align="center"><?php 
        	echo date('m/d/y',strtotime('+4 days')) . "<BR>" . date('D',strtotime('+4 days'));
		  ?></th>
        <th style="border-right:1px solid gray;" align="center"><?php 
        	echo date('m/d/y',strtotime('+5 days')) . "<BR>" . date('D',strtotime('+5 days'));;
		  ?></th>
      </tr>	
 </table>
 </div>   
    <table cellpadding="2" cellspacing="0" width="100%" id=daTable>
      <tr class="table_da_title_row">
        <th style="border-right:1px solid gray;">Name</th>
        <th style="border-right:1px solid gray;">CL ID</th>
        <th style="border-right:1px solid gray;">US ID</th>
        <th style="border-right:1px solid gray;">Cell</th>
        <th style="border-right:1px solid gray;">Home</th>
        <th style="border-right:1px solid gray;">Work</th>
        <th style="border-right:1px solid gray;">Notes</th>
        <th style="border-right:1px solid gray;">Vaca</th>
        <th style="border-right:1px solid gray;">hrs day</th>
        <th style="border-right:1px solid gray;">hrs wk</th>
        <th>hrs mo</th>
        <th style="border-left:1px solid black;border-right:1px solid gray;">day wk</th>
        <th style="border-right:1px solid black;">day mo</th>
        <th style="border-right:1px solid black;">last upt</th>
        <th style="border-right:1px solid black;">on call</th>
        <th style="border-right:1px solid gray;" align="center"><?php 
        	echo date('m/d/y') . "<BR>" . date('D');
		  ?></th>
        <th style="border-right:1px solid gray;" align="center"><?php 
        	echo date('m/d/y',strtotime('+1 day')) . "<BR>" . date('D',strtotime('+1 day'));
		  ?></th>
        <th style="border-right:1px solid gray;" align="center"><?php 
        	echo date('m/d/y',strtotime('+2 days')) . "<BR>" . date('D',strtotime('+2 days'));
		  ?></th>
        <th style="border-right:1px solid gray;" align="center"><?php 
        	echo date('m/d/y',strtotime('+3 days')) . "<BR>" . date('D',strtotime('+3 days'));
		  ?></th>
        <th style="border-right:1px solid gray;" align="center"><?php 
        	echo date('m/d/y',strtotime('+4 days')) . "<BR>" . date('D',strtotime('+4 days'));
		  ?></th>
        <th style="border-right:1px solid gray;" align="center"><?php 
        	echo date('m/d/y',strtotime('+5 days')) . "<BR>" . date('D',strtotime('+5 days'));;
		  ?></th>
      </tr>
      <?php
	  $driver_count = 0;
	  while($driver = mysql_fetch_assoc($driver_result)) {
    	  
		  $vacation_str = '';
		  $vaca_query = "select dv.StartDate, dv.EndDate from driver_vacation dv where dv.UserID = '".$driver['UserID']."' and (dv.StartDate>='".date('Y-m-d', strtotime($today))."' or dv.EndDate>='".date('Y-m-d', strtotime($today))."')";
		  $vaca_result = mysql_query($vaca_query);
		  $vaca_count = 0;
		  $vaca_array = array();
		  while($vaca_row = mysql_fetch_assoc($vaca_result)) {
		    if ($vaca_count==0) {
    		    $vacation_str .= date('m/d/y', strtotime($vaca_row['StartDate'])) . '- ' . date('m/d/y', strtotime($vaca_row['EndDate']));
				
		    } else {
			    $vacation_str .= '<br />'.date('m/d/y', strtotime($vaca_row['StartDate'])) . '- ' . date('m/d/y', strtotime($vaca_row['EndDate']));
		    }
			$vaca_array[] = array('start'=>strtotime($vaca_row['StartDate'].' 00:00:00'), 'end'=>strtotime($vaca_row['EndDate'].' 23:59:59'));
			$vaca_count++;
		  }
		  
		 $dw_query = "select DaysPerTime from driver_settings where UserID='".$driver['UserID']."' and DaysTimeUnit='Week'";
		 $dw_result = mysql_query($dw_query);		  
		 $dw_row = mysql_fetch_array($dw_result);
		 
		 $sql = "select count(LinkID) from link where DesiredArrivalTime >= '".date('y-m-d',strtotime('last Sunday'))."' and DesiredArrivalTime <= '".date('y-m-d',strtotime('next Saturday'))."' and AssignedDriverUserID = '".$driver['UserID']."'";
		 $rcount_rs = mysql_fetch_array(mysql_query($sql));
		 $rcount = $rcount_rs[0];
		 $sql = "select count(LinkID) from link_history where DesiredArrivalTime >= '".date('Y-m-d',strtotime('last Sunday'))."' and DesiredArrivalTime <= '".date('Y-m-d',strtotime('next Saturday'))."' and DriverUserID = '".$driver['UserID']."'";
		 $rcount_rs = mysql_fetch_array(mysql_query($sql));
		 $rcount += $rcount_rs[0];
		 
		 /* DaysPerTime is measured in hours, average ride time is 15 minutes */
		 $rcount = $rcount / 4;
		 
		 $current_week_count = -1;
		 if($dw_row["DaysPerTime"] > 0) $current_week_count = $dw_row["DaysPerTime"] - $rcount;
		 
		 $dw_query = "select DaysPerTime from driver_settings where UserID='".$driver['UserID']."' and DaysTimeUnit='Month'";
		 $dw_result = mysql_query($dw_query);		  
		 $dw_row = mysql_fetch_array($dw_result);		
		   
		 $sql = "select count(LinkID) from link where DesiredArrivalTime >= '".date('y-m-01')."' and DesiredArrivalTime <= '".date('y-m-t')."' and AssignedDriverUserID = '".$driver['UserID']."'";
		 $rcount_rs = mysql_fetch_array(mysql_query($sql));
		 $rcount = $rcount_rs[0];
		 $sql = "select count(LinkID) from link_history where DesiredArrivalTime >= '".date('y-m-01')."' and DesiredArrivalTime <= '".date('y-m-t')."' and DriverUserID = '".$driver['UserID']."'";
		 $rcount_rs = mysql_fetch_array(mysql_query($sql));
		 $rcount += $rcount_rs[0];	
		 
		 /* DaysPerTime is measured in hours, average ride time is 15 minutes */
		 $rcount = $rcount / 4;
		 
		 $current_month_count = -1;
		 if($dw_row["DaysPerTime"] > 0) $current_month_count = $dw_row["DaysPerTime"] - $rcount;		 	
		   
		  $hide_row = array(false, false, false, false, false, false);
		  
		  $date_columns = '<td style="border-right:1px solid gray;';
      foreach($vaca_array as $vaca_item) 
        if(strtotime("midnight") >= $vaca_item['start'] && strtotime("midnight") <= $vaca_item['end']) {
			    $date_columns .= 'background-color:#444444;border:2px solid #000000;';
				  $hide_row[0] = true;
				}
			if(($current_week_count == 0 || $current_month_count == 0) && time() < strtotime('next Sunday')) {
		    $date_columns .= 'background-color:#444444;border:2px solid #000000;';
			  $hide_row[0] = true;				
			}
		  $date_columns .= '" align="center"><a href="manifest.php?userid='.$driver['UserID'].'&date='.date('Y-m-d', strtotime($today)).'">'; 
		    $day = getdate(strtotime($today));
			$day_query = "select * from driver_availability where UserID='".$driver['UserID']."' and DayOfWeek='".$day['weekday']."'";
			$day_result = mysql_query($day_query);
			$row_count = 0;
			while ($day_row = mysql_fetch_assoc($day_result)) {
			  if ($row_count ==0) {
	              $date_columns .= convertAMPM(substr($day_row['StartTime'], 0, 5)) . ' - ' . convertAMPM(substr($day_row['EndTime'],0,5));
              } else {
			      $date_columns .= '<br />'.convertAMPM(substr($day_row['StartTime'],0,5)) . ' - ' . convertAMPM(substr($day_row['EndTime'],0,5));
			  }
			  $row_count++;
			}
		  $date_columns .= '</a></td>
          <td style="border-right:1px solid gray;';
      $nday = strtotime('+1 day');
      $nday = strtotime('midnight',$nday);
      foreach($vaca_array as $vaca_item) 
        if($nday >= $vaca_item['start'] && strtotime('+1 day') <= $vaca_item['end']) {
			    $date_columns .= 'background-color:#444444;border:2px solid #000000;';
				  $hide_row[1] = true;
				}
			if(($current_week_count == 0 || $current_month_count == 0) && strtotime('+1 day') < strtotime('next Sunday')) {
		    $date_columns .= 'background-color:#444444;border:2px solid #000000;';
			  $hide_row[1] = true;				
			}

		  $date_columns .= '" align="center"><a href="manifest.php?userid='.$driver['UserID'].'&date='.date('Y-m-d', strtotime($today1)).'">'; 
		    $day = getdate(strtotime($today1));
			$day_query = "select * from driver_availability where UserID='".$driver['UserID']."' and DayOfWeek='".$day['weekday']."'";
			$day_result = mysql_query($day_query);
			$row_count = 0;
			while ($day_row = mysql_fetch_assoc($day_result)) {
			  if ($row_count ==0) {
	              $date_columns .= convertAMPM(substr($day_row['StartTime'], 0, 5)) . ' - ' . convertAMPM(substr($day_row['EndTime'],0,5));
              } else {
			      $date_columns .= '<br />'.convertAMPM(substr($day_row['StartTime'],0,5)) . ' - ' . convertAMPM(substr($day_row['EndTime'],0,5));
			  }
			  $row_count++;
			}
		  $date_columns .= '</a></td>
          <td style="border-right:1px solid gray;';
      $nday = strtotime('+2 days');
      $nday = strtotime('midnight',$nday);
      foreach($vaca_array as $vaca_item) 
        if($nday >= $vaca_item['start'] && $nday <= $vaca_item['end']) {
			    $date_columns .= 'background-color:#444444;border:2px solid #000000;';
				  $hide_row[2] = true;
				}
			if(($current_week_count == 0 || $current_month_count == 0) && strtotime('+2 days') < strtotime('next Sunday')) {
		    $date_columns .= 'background-color:#444444;border:2px solid #000000;';
			  $hide_row[2] = true;				
			}

		  $date_columns .= '" align="center"><a href="manifest.php?userid='.$driver['UserID'].'&date='.date('Y-m-d', strtotime($today2)).'">';
		    $day = getdate(strtotime($today2));
			$day_query = "select * from driver_availability where UserID='".$driver['UserID']."' and DayOfWeek='".$day['weekday']."'";
			$day_result = mysql_query($day_query);
			$row_count = 0;
			while ($day_row = mysql_fetch_assoc($day_result)) {
			  if ($row_count ==0) {
	              $date_columns .= convertAMPM(substr($day_row['StartTime'], 0, 5)) . ' - ' . convertAMPM(substr($day_row['EndTime'],0,5));
              } else {
			      $date_columns .= '<br />'.convertAMPM(substr($day_row['StartTime'],0,5)) . ' - ' . convertAMPM(substr($day_row['EndTime'],0,5));
			  }
			  $row_count++;
			}
		  $date_columns .= '</a></td>
          <td style="border-right:1px solid gray;';
      $nday = strtotime('+3 days');
      $nday = strtotime('midnight',$nday);
      foreach($vaca_array as $vaca_item) 
        if($nday >= $vaca_item['start'] && $nday <= $vaca_item['end']) {
			    $date_columns .= 'background-color:#444444;border:2px solid #000000;';
				  $hide_row[3] = true;
				}
			if(($current_week_count == 0 || $current_month_count == 0) && strtotime('+3 days') < strtotime('next Sunday')) {
		    $date_columns .= 'background-color:#444444;border:2px solid #000000;';
			  $hide_row[3] = true;				
			}

		  $date_columns .= '" align="center"><a href="manifest.php?userid='.$driver['UserID'].'&date='.date('Y-m-d', strtotime($today3)).'">';
		    $day = getdate(strtotime($today3));
			$day_query = "select * from driver_availability where UserID='".$driver['UserID']."' and DayOfWeek='".$day['weekday']."'";
			$day_result = mysql_query($day_query);
			$row_count = 0;
			while ($day_row = mysql_fetch_assoc($day_result)) {
			  if ($row_count ==0) {
	              $date_columns .= convertAMPM(substr($day_row['StartTime'], 0, 5)) . ' - ' . convertAMPM(substr($day_row['EndTime'],0,5));
              } else {
			      $date_columns .= '<br />'.convertAMPM(substr($day_row['StartTime'],0,5)) . ' - ' . convertAMPM(substr($day_row['EndTime'],0,5));
			  }
			  $row_count++;
			}
		  $date_columns .= '</a></td>
          <td style="border-right:1px solid gray;';
      $nday = strtotime('+4 days');
      $nday = strtotime('midnight',$nday);
      foreach($vaca_array as $vaca_item) 
        if($nday >= $vaca_item['start'] && $nday <= $vaca_item['end']) {
			    $date_columns .= 'background-color:#444444;border:2px solid #000000;';
				  $hide_row[4] = true;
				}
			if(($current_week_count == 0 || $current_month_count == 0) && strtotime('+4 days') < strtotime('next Sunday')) {
		    $date_columns .= 'background-color:#444444;border:2px solid #000000;';
			  $hide_row[4] = true;				
			}

		  $date_columns .= '" align="center"><a href="manifest.php?userid='.$driver['UserID'].'&date='.date('Y-m-d', strtotime($today4)).'">';
		    $day = getdate(strtotime($today4));
			$day_query = "select * from driver_availability where UserID='".$driver['UserID']."' and DayOfWeek='".$day['weekday']."'";
			$day_result = mysql_query($day_query);
			$row_count = 0;
			while ($day_row = mysql_fetch_assoc($day_result)) {
			  if ($row_count ==0) {
	              $date_columns .= convertAMPM(substr($day_row['StartTime'], 0, 5)) . ' - ' . convertAMPM(substr($day_row['EndTime'],0,5));
              } else {
			      $date_columns .= '<br />'.convertAMPM(substr($day_row['StartTime'],0,5)) . ' - ' . convertAMPM(substr($day_row['EndTime'],0,5));
			  }
			  $row_count++;
			}
		  $date_columns .= '</a></td>
          <td style="border-right:1px solid gray;';
      $nday = strtotime('+5 days');
      $nday = strtotime('midnight',$nday);
      foreach($vaca_array as $vaca_item) 
        if($nday >= $vaca_item['start'] && $nday <= $vaca_item['end']) {
			    $date_columns .= 'background-color:#444444;border:2px solid #000000;';
				  $hide_row[5] = true;
				}
			if(($current_week_count == 0 || $current_month_count == 0) && strtotime('+5 days') < strtotime('next Sunday')) {
		    $date_columns .= 'background-color:#444444;border:2px solid #000000;';
			  $hide_row[5] = true;				
			}

		  $date_columns .= '" align="center"><a href="manifest.php?userid='.$driver['UserID'].'&date='.date('Y-m-d', strtotime($today5)).'">';
		    $day = getdate(strtotime($today5));
			$day_query = "select * from driver_availability where UserID='".$driver['UserID']."' and DayOfWeek='".$day['weekday']."'";
			$day_result = mysql_query($day_query);
			$row_count = 0;
			while ($day_row = mysql_fetch_assoc($day_result)) {
			  if ($row_count ==0) {
	              $date_columns .= convertAMPM(substr($day_row['StartTime'], 0, 5)) . ' - ' . convertAMPM(substr($day_row['EndTime'],0,5));
              } else {
			      $date_columns .= '<br />'.convertAMPM(substr($day_row['StartTime'],0,5)) . ' - ' . convertAMPM(substr($day_row['EndTime'],0,5));
			  }
			  $row_count++;
			}
		  $date_columns .= '</a></td>';
		  
		  
		  
	  ?>
        <tr class="<?php
          if ($hide_row[0] && $hide_row[1] && $hide_row[2] && $hide_row[3] && $hide_row[4] && $hide_row[5]) {
		    echo 'tr_hide';
		  } else {
		    echo 'table_da_row';
			if ($driver_count%2==0) {
			  echo  '_alt';
			} else {
			  echo '';
			}
		  } 
		  ?>">
          <td style="border-right:1px solid gray;"><a href="/xhr/affected_user_redirect.php?redirect=/account.php&userid=<?php echo $driver['UserID']; ?>"><?php echo $driver['FirstName'] . ' ' . $driver['LastName']; ?></a></td>
          <td align="center" style="border-right:1px solid gray;"><?php echo $driver['FranchiseID']; ?></td>
          <td align="center" style="border-right:1px solid gray;"><?php echo $driver['UserID']; ?></td>
          <td style="border-right:1px solid gray;white-space:nowrap"><?php
          $phones_query = "select p.PhoneNumber, up.IsPrimary from user_phone up, phone p where up.UserID='".$driver['UserID']."' and p.PhoneID=up.PhoneID and p.PhoneType='MOBILE'";
		  $phones_result = mysql_query($phones_query);
		  $phones_row = mysql_fetch_assoc($phones_result);
		  echo ($phones_row['IsPrimary']=='Yes') ? '<b>'.$phones_row['PhoneNumber'].'</b>' : $phones_row['PhoneNumber'];
		  ?></td>
          <td style="border-right:1px solid gray;white-space:nowrap"><?php
          $phones_query2 = "select p.PhoneNumber, up.IsPrimary from user_phone up, phone p where up.UserID='".$driver['UserID']."' and p.PhoneID=up.PhoneID and p.PhoneType='HOME'";
		  $phones_result2 = mysql_query($phones_query2);
		  $phones_row2 = mysql_fetch_assoc($phones_result2);
		  echo ($phones_row2['IsPrimary']=='Yes') ? '<b>'.$phones_row2['PhoneNumber'].'</b>' : $phones_row2['PhoneNumber'];
		  ?></td>
          <td style="border-right:1px solid gray;white-space:nowrap"><?php
          $phones_query3 = "select * from user_phone up, phone p where up.UserID='".$driver['UserID']."' and p.PhoneID=up.PhoneID and p.PhoneType='WORK'";
		  $phones_result3 = mysql_query($phones_query3);
		  $phones_row3 = mysql_fetch_assoc($phones_result3);
		  echo ($phones_row3['IsPrimary']=='Yes' ? '<b>' : '')
		  	.$phones_row3['PhoneNumber']
		  	.($phones_row3['Ext'] != '' ? '<br>x'.$phones_row3['Ext'] : '')
		  	.($phones_row3['IsPrimary']=='Yes' ? '</b>' : '');
		  ?></td>
          <td style="border-right:1px solid gray;"><?php echo $driver['OtherNotes']; ?></td>
          
          <td style="border-right:1px solid gray;">
		  <a href="driver_availability.php?userid=<?php echo $driver['UserID']; ?>">
		  <?php
          echo $vacation_str;
		  ?></a></td>
          <td align="center" style="border-right:1px solid gray;"><a href="driver_availability.php?userid=<?php echo $driver['UserID']; ?>"><?php
		  $last_update = '';
          $hours_query = "select HoursPerTime, AvailabilityLastUpdate from driver_settings where UserID='".$driver['UserID']."' and HoursTimeUnit='Day'";
		  $hours_result = mysql_query($hours_query);
		  if (mysql_num_rows($hours_result)>0) {
			  $hours_row = mysql_fetch_assoc($hours_result);
			  echo $hours_row['HoursPerTime'];
			  $last_update = $hours_row['AvailabilityLastUpdate'];
		  }
		  ?></a></td>
          <td align="center" style="border-right:1px solid gray;"><a href="driver_availability.php?userid=<?php echo $driver['UserID']; ?>"><?php
          $week_query = "select HoursPerTime, AvailabilityLastUpdate from driver_settings where UserID='".$driver['UserID']."' and HoursTimeUnit='Week'";
		  $week_result = mysql_query($week_query);
		  if (mysql_num_rows($week_result)>0) {
			  $week_row = mysql_fetch_assoc($week_result);
			  echo $week_row['HoursPerTime'];
			  $last_update = $week_row['AvailabilityLastUpdate'];
		  }
		  ?></a></td>
          <td align="center"><a href="driver_availability.php?userid=<?php echo $driver['UserID']; ?>"><?php
          $month_query = "select HoursPerTime, AvailabilityLastUpdate from driver_settings where UserID='".$driver['UserID']."' and HoursTimeUnit='Month'";
		  $month_result = mysql_query($month_query);
		  if (mysql_num_rows($month_result)) {
		      $month_row = mysql_fetch_assoc($month_result);
		      echo $month_row['HoursPerTime'];
			  $last_update = $month_row['AvailabilityLastUpdate'];
		  }
		  ?></a></td>
          <td style="border-left:1px solid black;border-right:1px solid gray;"><a href="driver_availability.php?userid=<?php echo $driver['UserID']; ?>"><?php
          $dw_query = "select DaysPerTime, AvailabilityLastUpdate from driver_settings where UserID='".$driver['UserID']."' and DaysTimeUnit='Week'";
		  $dw_result = mysql_query($dw_query);
		  if (mysql_num_rows($dw_result)>0) {
		      $dw_row = mysql_fetch_assoc($dw_result);
			  echo $dw_row['DaysPerTime'];
			  $last_update = $dw_row['AvailabilityLastUpdate'];
		  }
		  ?></a></td>
          <td style="border-right:1px solid black;"><a href="driver_availability.php?userid=<?php echo $driver['UserID']; ?>"><?php
          $dm_query = "select DaysPerTime, AvailabilityLastUpdate from driver_settings where UserID='".$driver['UserID']."' and DaysTimeUnit='Month'";
		  $dm_result = mysql_query($dm_query);
		  if (mysql_num_rows($dm_result)>0) {
		      $dm_row = mysql_fetch_assoc($dm_result);
		      echo $dm_row['DaysPerTime'];
			  $last_update = $dm_row['AvailabilityLastUpdate']; 
		  }
		  ?></a></td>
          <td style="border-right:1px solid black;"><a href="driver_availability.php?userid=<?php echo $driver['UserID']; ?>"><?php echo ($last_update) ? date('m/d/y', strtotime($last_update)) : ''; ?></a></td>
          <td style="border-right:1px solid black;"><?php echo driver_oncall_status($driver['UserID']); ?></td>
          <?php echo $date_columns; ?>
        </tr>
      <?php
	    $driver_count++;
	  }
	  ?>
    </table>
<?php
	include_once 'include/footer.php';
	
	function convertAMPM($hourtime) {
	  
	  if ((int)substr($hourtime, 0, 2)>=12) {
	    if ((int)substr($hourtime,0,2)>=13) {
    	    return ((int)substr($hourtime,0,2)-12) . substr($hourtime,2). ' PM';
		} else {
		    return ((int)substr($hourtime,0,2)) . substr($hourtime,2). ' PM';
		}
	  } else {
	    return ((int)substr($hourtime,0,2)) . substr($hourtime,2).' AM';
	  }
	
	}
	
?>