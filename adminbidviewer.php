<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../bidderexpress-dbaccess.php");

if(!isset($_SESSION['bidderexpress_user_id']) ){
	header("Location: login.php");
	die();
}

include("./header.php");
include("./adminfunctions.php");

$bid_group_id = $_GET['bid'];
if(!($bid_group_id >0)) errorPage("Id not valid");
$bid = $dbh->prepare("select bid_group.*,facility.name as facility_name,area.name as area_name,facility.id as facility_id, area.id as area_id from bid_group
	left join area on bid_group.area_id = area.id
	left join facility on area.facility_id = facility.id 
	where bid_group.id = :bid_group_id");
$bid->bindParam(":bid_group_id", $_GET['bid']);
$bid->execute();

try{
	$bid = $bid->fetch();
}catch (Exception $e){
	errorPage("Try and Catch error.");
}

$area_name = $bid['area_name'];
$area_id = $bid['area_id'];
$facility_name = $bid['facility_name'];
$facility_id = $bid['facility_id'];
$bid_name = $bid['name'];
$start_date = $bid['start_date'];
$end_date = $bid['end_date'];

// if(!$bid) errorPage("Not valid");
// if($bid->rowCount() != 1) errorPage("Somethings wrong");

// $userlist = $dbh->prepare("select user_id,user.lastname,user.firstname,user.BUE,user.initial
// 	from bid_user
// 	left join user on bid_user.user_id= user.id
// 	where bid_group_id = :bid_group_id");  // old one
	
$userlist = $dbh->prepare("select bid_user.id as bid_user_id,user.lastname,user.firstname,user.initial,user.BUE,user.SCD,user.military_time,user.id from user 
	left join bid_user on bid_user.user_id = user.id
	where bid_user.bid_group_id = :bid_group_id
	order by user.bue asc,user.military_time asc,user.tie_break asc");	//commented out on 12/4/17 when i added the userlist dbh prepare from bidscheduler.php
	
// $userlist = $dbh->prepare("select user.lastname,user.firstname,user.initial,user.BUE,user.SCD,user.military_time,user.id,bid_tracker.status,
// bid_tracker.scheduled_time from user 
// 	left join bid_user on bid_user.user_id = user.id
// 	left join bid_round on bid_round.bid_group_id = bid_user.bid_group_id 
// 	left join bid_tracker on bid_user.id = bid_tracker.bid_user_id
// 	where bid_user.bid_group_id = :bid_group_id
// 	order by user.bue asc,user.military_time asc,user.tie_break asc");	 //removed 12/10/17 bc it made a row for each entry in bid_tracker and round

$userlist->bindParam(":bid_group_id",$bid_group_id);
$userlist->execute();

try{
	$userlist = $userlist->fetchAll();
}catch(Exception $e){
	echo "error, userlist didnt work";
	die();
}

// $roundcount = $dbh->prepare("select max(number) as max from bid_round where bid_group_id = :bid_group_id");
// $roundcount->bindParam(":bid_group_id",$bid_group_id);
// $roundcount->execute();
// try{
// 	$roundcount = $roundcount->fetch()['max'];
// } catch (Exception $e){
// 	$roundcount= null;
// } // commented out on 17/12/11 bc its not used.. main addround to userlist does this


	
foreach($userlist as $key => $user){
	$userlist[$key]["rounds"] = array();	
}
reset($userlist);

$round_counter = 0;

$bidarray = array();

addRoundsToUserlist();


$emptybueorscd = $dbh->prepare("select user.id, user.firstname,user.lastname,user.initial from user 
left join bid_user on bid_user.user_id = user.id
where bid_user.bid_group_id = :bid_group_id AND (BUE = '0000-00-00' OR bue = null OR SCD = '0000-00-00' OR SCD= null)");
$emptybueorscd->bindParam(":bid_group_id",$bid_group_id);
$emptybueorscd->execute();
$emptybueorscd = $emptybueorscd->fetchAll();

//ERROR funcs

function errorPage($msg){
	$_SESSION['message'] .= $msg;
	// $_SESSION['message']  = "";
	header("Location: adminarea.php?area=" . $_GET['area']);
	die();
}

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
			<?php echoHeader(); 
			if($_SESSION['message'] != "") {
				echo "<br>" . $_SESSION['message'];
				$_SESSION['message'] = "";
			}  
			?>
			
			<hr>
			<div class='w3-border'>

				
				<h3>Main Bid Admin</h3>
				<h5><?php echo "<a href='adminfacility.php?facility=$facility_id'>$facility_name</a>
				-- <a href='adminarea.php?area=$area_id'>$area_name</a>";?></h5>
				<h4><?php echo "<a href='adminbidviewer.php?bid=$bid_group_id&area=$area_id'>$bid_name <span class='w3-small'>-- $start_date - $end_date</span></a>";?></h4>
			</div>
			
			<div class='w3-border w3-panel'>
				<table class='w3-table w3-striped'>
					<tr>
						<th></th>
						<th>BUE</th>
						<th>Last</th>
						<th>First</th>
						<th>Init</th>
						<?php
							foreach($bidarray as $bid){
								$name = $bid['name'];
								$number = $bid['number'];
								$type= $bid['type'];
								echo "<th>$name</th>";
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
					echo "<tr>
					<td>$counter.</td>
					<td><span class='w3-tiny'>$bue</span></td>
					<td>$lastname</td>
					<td>$firstname</td>
					<td class='w3-tiny'>$initial</td>";
					foreach($usera["rounds"] as $roundskey => $round){
						$status = $round['status'];
						$scheduled_time = substr($round['scheduled_time'],0,-3);
						$scheduled_time_orig = $scheduled_time;
						$scheduled_time = substr($scheduled_time,5);
						switch($status){
							case "NE": //NE', 'UTB', 'P-E', 'P-NE', 'DONE
								$statusclass = "w3-grey";
								$smallbox = "<span class='statusbox $statusclass' title='$status'></span>";
								break;
							case "UTB": 
								$statusclass = "w3-green";
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
								$smallbox = "<span class='statusbox'  title='$status' style='background: linear-gradient(90deg, red 50%, #8bc34a 50%)'></span>";
								break;
						}
						
						
						echo "<td class=' w3-tiny' title='$scheduled_time_orig'>$smallbox<span class=''>$scheduled_time</span></td>";
					}
					
					
					// echo "<td class='w3-red'>$round0status - $round0scheduled_time</td>";
					
					echo "\n</tr>";
					$counter++;
				}
				?>
				</table>
			</div>
			
			<div class='w3-border'>
				<h4>Add User</h4>
				<form method='POST' action="adminbidsubmit.php">
					<input type='hidden' name='action' value='adduser'>
					<input type='hidden' name='facility_id' value='<?php echo $facility_id;?>'>
					<input type='hidden' name='bid' value='<?php echo $bid_group_id;?>'>'
					Initials: <input type='text' name='initials' autofocus>
					<input type='submit' value='Add user'>
				</form>
			</div>
			
		<?php  
		if(count($emptybueorscd) >0){
			echo "<div class='w3-panel'>
				<h4>Employees with Empty BUE or SCD</h4>";
				foreach($emptybueorscd as $a){
					$first_name =$a['firstname'];
					$last_name =$a['lastname'];
					$initials  =$a['initial'];
					$theuser_id = $a['id'];
					echo "<a href='adminuserinfo.php?user=$theuser_id'>$last_name, $first_name - $initials</a><br>";
				}
			echo "</div>";
		}
		
		?>
		<div class='w3-panel w3-border'>
		<a href='adminbidscheduleeditor.php?bid=<?php echo $bid_group_id;?>'>Schedule Editor</a> - <a href='adminbidscheduler.php?bid=<?php echo $bid_group_id;?>'>Bid Scheduler</a>
		 - <a href='adminbidslots.php?bid=<?php echo $bid_group_id;?>'>Bid Slots</a>
		</div>
		</div>
		
	</body>
	
</html>
