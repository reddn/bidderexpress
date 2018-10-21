<?php

//created on 2017/12/11 at 1646 to make the status display the same as adminbidviewer.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include("../bidderexpress-dbaccess.php");


if(!isset($_SESSION['bidderexpress_user_id']) ){
	header("Location: login.php");
	die();
}

$user_id = $_SESSION['bidderexpress_user_id'];
include("./header.php");
include("./functions.php");





$bidsuserisin = $dbh->query("select facility.name as facility_name, area.name as area_name, bid_group.name,bid_group.id,bid_group.start_date,bid_group.end_date from bid_group
left join bid_user on bid_user.bid_group_id = bid_group.id
left join area on bid_group.area_id = area.id
left join facility on facility.id = area.facility_id 
where bid_group.active = 1 AND bid_user.user_id = $user_id
order by bid_group.end_date desc")->fetchAll();
// print_r($bidsuserisin);

$isuseruptobid = isUserUptoBid($dbh);

?>


<!DOCTYPE html>
<html>
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="stylesheet" href="./w3.css">
		<title>Bidder Express</title>
		<style>
			.statusbox{
				width: 1.5em;
				display: inline-block;
				/*display: relative;*/
				height: 1.5em;
				margin-right: 1em;
				
			}
			table tr th{
				font-size: .9em !important;
			}
			@media(max-width: 600px){
				table tr th {
					font-size: .8em !important;
				}
				.smallth{
					display: unset;
				}
				.notsmallth{
					display: none;
				}
				.tdsmalltext{
					font-size: .8em !important;
				}
				table tr td{
					padding: 8px 3px !important;
				}
				table tr th{
					padding: 8px 3px !important;
				}
			}
			@media(min-width: 601px){
				.smallth{
					display: none;
				}
				
			}
		
		</style>
		
		
	</head>
	<body>
		<div class='w3-container'>
		<?php echoHeader(); 
		// print_r($isuseruptobid);
		if(count($isuseruptobid) >0){
			// $counter1=0;
			foreach($isuseruptobid as $a){
				// if($isuseruptobid[0]['type'] == 'prime'){
					// $this_bid_line_id = $isuseruptobid[0]['bid_line_id'];
				if($a['type'] == 'prime'){	
					$this_bid_line_id = $a['bid_line_id'];
					echo "<h4 class='w3-center'><a href = './displayschedule.php?bid_line_id=$this_bid_line_id'>Your Up to bid Leave</a></h4>";
				} else {
					// $this_bid_group_id = $isuseruptobid[0]['id'];
					$this_bid_group_id = $a['id'];
					echo "<h4 class='w3-center'><a href = './userbidview.php?bid_group=$this_bid_group_id'>Your Up to bid a Schedule</a></h4>";
				}
				// echo $counter1++;
			}	
		}
		?>
		
		<hr>

		<!--<div class='w3-panel w3-border'>-->
		<!--	<h4>Senority List</h4>-->

			<?php
			foreach($bidsuserisin as $bid){
				$bid_name = $bid['name'];
				$start_date = $bid['start_date'];
				$end_date = $bid['end_date'];
				$facility_name = $bid['facility_name'];
				$bid_group_id = $bid['id'];
				$area_name = $bid['area_name'];
				echo "<div class='w3-border w3-panel' ><a href='./userbidview.php?bid_group=$bid_group_id'><h5>$facility_name: $area_name - $bid_name</h5></a>";

				$userlist = $dbh->prepare("select bid_user.id as bid_user_id,user.lastname,user.firstname,user.initial,user.BUE,user.SCD,user.military_time,user.id from user 
				left join bid_user on bid_user.user_id = user.id
				where bid_user.bid_group_id = :bid_group_id
				order by user.bue asc,user.military_time asc,user.tie_break asc");	
				$userlist->bindParam(":bid_group_id",$bid['id']);
				$userlist->execute();
				$userlist = $userlist->fetchAll();
			
				foreach($userlist as $key => $user){
					$userlist[$key]["rounds"] = array();	
				}
				reset($userlist);
				
				$round_counter = 0;
				
				$bidarray = array();
				addRoundsToUserlist();
			
				echo "<table class='w3-table w3-striped'>"; ?>
				
									<tr>
						<th>#</th>
						<th class='w3-hide-small'>BUE</th>
						<th>Last</th>
						<th class='w3-hide-small'>First</th>
						<th>Init</th>
						<?php
							foreach($bidarray as $bid){
								$name = $bid['name'];
								$number = $bid['number'];
								$type= $bid['type'];
								$shortname = substr($name,0,2);
								if($number == "0") $number = "";
								echo "<th><span class='notsmallth'>$name</span><span class='smallth'>$shortname $number</span></th>";
							}
						
						?>
					</tr>
				
				<?php
			
				$counter = 1;
				foreach($userlist as $usera){
					
					$firstname = $usera['firstname'];
					$lastname = $usera['lastname'];
					$initial = $usera['initial'];
					$bue = $usera['BUE'];
					// $round0status=$usera['round0status'];
					// $round0scheduled_time=substr($usera['round0scheduled_time'],0,-3);
					
					echo "<tr><td>$counter.</td>
					<td class='w3-hide-small'><span class='w3-tiny'>$bue</span></td>
					<td class='tdsmalltext'>$lastname</td>
					<td class='w3-hide-small'>$firstname</td>
					<td class='w3-tiny'>$initial</td>";
					foreach($usera["rounds"] as $roundskey => $round){
						$status = $round['status'];
						$scheduled_time = substr($round['scheduled_time'],0,-3);
						$scheduled_time_orig = $scheduled_time;
						$scheduled_time = substr($scheduled_time,5);
						switch($status){
							case "NE": //NE', 'UTB', 'P-E', 'P-NE', 'DONE
								$statusclass = "w3-dark-gray";
								$smallbox = "<span class='statusbox $statusclass' title='$status'></span>";
								break;
							case "UTB": 
								$statusclass = "w3-light-green";
								$smallbox = "<span class='statusbox $statusclass' title='$status'></span>";
								break;
							case "P-E":
								$statusclass = "w3-yellow";
								$smallbox = "<span class='statusbox $statusclass' title='$status'></span>";
								break;
							case "P-NE":
								$statusclass = "w3-blue";
								$smallbox = "<span class='statusbox $statusclass' title='$status'></span>";
								break;
							case "DONE":
								$statusclass = "w3-red";
								$smallbox = "<span class='statusbox $statusclass' title='$status'></span>";
								break;
							case "PENDING-DONE":
								// $statusclass = "w3-orange";
								// $smallbox = "<span class='statusbox $statusclass'></span>";
								$smallbox = "<span class='statusbox' title='$status' style='background: linear-gradient(90deg, red 50%, #8bc34a 50%)'></span>";
								
								break;
							case "":
								$statusclass = "w3-red";
								$smallbox = "<span  title='$status'class='statusbox $statusclass'></span>";
								break;
							default:
								$statusclass = "w3-red";
								$smallbox = "<span  title='$status'class='statusbox $statusclass'></span>";
								break;
						}
						
						
						echo "<td class=' w3-tiny' title='$scheduled_time_orig'>$smallbox<span class=''>$scheduled_time</span></td>";
					}
					
					
					// echo "<td class='w3-red'>$round0status - $round0scheduled_time</td>";
					
					echo "</tr>";
					$counter++;
					
					
					
					
				}
				
				echo "</table></div>";
				

			}

			?>
		</div>
		
		
	
		
		
		
		<!--</div>-->
	</body>

</html>

<?php


?>
	
	