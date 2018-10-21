<?php
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




?>


<!DOCTYPE html>
<html>
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="stylesheet" href="./w3.css">
		<title>Bidder Express</title>
		<style>
			.statusbox{
				width: 1em;
				display: inline-block;
				height: 1em;
				margin-right: 1em;
			}
			
		
		</style>
		
		
	</head>
	<body>
		<div class='w3-container'>
		<?php echoHeader(); ?>
		<hr>
		Insert cool shit here 
		<div class='w3-panel w3-border'>
			<h4>Senority List</h4>

			<?php
			foreach($bidsuserisin as $bid){
				$bid_name = $bid['name'];
				$start_date = $bid['start_date'];
				$end_date = $bid['end_date'];
				$facility_name = $bid['facility_name'];
				$area_name = $bid['area_name'];
				echo "<div class='w3-border w3-panel' ><h5>$facility_name: $area_name - $bid_name</h5>";

				$userlist = $dbh->prepare("select bid_user.id as bid_user_id,user.lastname,user.firstname,user.initial,user.BUE,user.bue,user.military_time,user.id from user 
				left join bid_user on bid_user.user_id = user.id
				where bid_user.bid_group_id = :bid_group_id
				order by user.bue asc,user.military_time asc,user.tie_break asc");	
				$bid_group_id = $bid['id'];
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
			
				$counter = 1;
				foreach($userlist as $usera){
					$firstname = $usera['firstname'];
					$lastname = $usera['lastname'];
					$initial = $usera['initial'];
					echo "$counter. $lastname, $firstname - $initial<br>";
					// print_r($usera);
					$counter++;
				}
				echo "</div>";
				

			}

			?>
		</div>
		
		
	
		
		
		
		</div>
	</body>

</html>

<?php


?>
	
	