<?php
require_once('include/database.php');
require_once('include/rc_log.php');
require_once('include/mapquest.php');
require_once('include/destinations.php');
require_once 'include/driver_rate_card.php';
require_once('include/link.php');

$franchise_id = 2;

                    $from_id = 152381;
                    $to_id = 152288;
                    $from = get_link($from_id);
                    $to = get_link($to_id);

										$desiredArrivalTimeMinus5Minutes = date('H:i:s',strtotime("-5 minute",strtotime($to['DesiredArrivalTime'])) ) ;

                    $from_dest = get_destination($from['ToDestinationID']);
                    $to_dest = get_destination($to['FromDestinationID']);

                    // Get distance/cost
                    $distance_and_time = get_mapquest_time_and_distance( $from_dest, $to_dest, TRUE, $to['DesiredArrivalTime'] );
                    $link_distance = $distance_and_time['distance'];
                    $driver_credit_cents = floor($driver_rate_card['CentsPerMile'] * $link_distance);

                    $totalDeltaMinutes = (strtotime($to["DesiredDepartureTime"]) - strtotime("-5 minute",strtotime($from['DesiredArrivalTime']))) / 60;
                    
                    // subtract total transition minutes
                    $deltaMinutes = $totalDeltaMinutes - ($distance_and_time['time']/60);
                    // if remainder of minutes is greater than the value of franchise.deadhead_plus,
                    // then the maximum credit for that time is franchise.deadhead_plus
                    $deltaMinutes = $deltaMinutes < 0 ? 0 : $deltaMinutes;
                    $sql = "select deadhead_plus from franchise where FranchiseID = $franchise_id";
                    $r = mysql_query($sql);
                    $rs = mysql_fetch_array($r);
                    $deltaMintues = $deltaMinutes > $rs["deadhead_plus"] ? $rs["deadhead_plus"] : $deltaMinutes;

echo "deltaMinutes $deltaMinutes<Br>";


?>