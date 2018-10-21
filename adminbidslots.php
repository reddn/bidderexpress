<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../bidderexpress-dbaccess.php");

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

$interval1day = new DateInterval("P1D");

$timezone = new DateTimeZone("America/New_York");

$start_dateobj = DateTime::createFromFormat("Y-m-d",$start_date,$timezone);
$end_dateobj = DateTime::createFromFormat("Y-m-d",$end_date,$timezone);

$end_dateobj->add($interval1day);
if(!isset($_SESSION['bidderexpress_user_id']) ){
	header("Location: login.php");
	die();
}

$dateperiod = new DatePeriod($start_dateobj,$interval1day,$end_dateobj);

$lastmonthnum = $start_dateobj->format("m");
$currentweekdaynum = $start_dateobj->format("w");

$bid_slot = $dbh->prepare("select * from bid_slot where bid_group_id = :bid_group_id");
$bid_slot->bindParam(":bid_group_id",$bid_group_id);
$bid_slot->execute();
$bid_slot = $bid_slot->fetchAll();

?>


<!DOCTYPE html>
<html>
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1">

		<link rel="stylesheet" href="./w3.css">
	
		<title>Bidder Express</title>
		<style>
			table tr *{
				text-align: center !important;
			}
		</style>
		
	</head>
	<body>
		<div class='w3-container'>
			<?php echoHeader(); ?>
			<hr>
			<div class='w3-border'>
				<h3>Bid Slots Admin</h3>
				<h5><?php echo "<a href='adminfacility.php?facility=$facility_id'>$facility_name</a>
				-- <a href='adminarea.php?area=$area_id'>$area_name</a>";?></h5>
				<h4><?php echo "<a href='adminbidviewer.php?bid=$bid_group_id&area=$area_id'>$bid_name <span class='w3-small'>-- $start_date - $end_date</span></a>";?></h4>
			</div>
			
		<!--<div class='w3-border w3-panel'>-->
			<?php
			echoStartTable($start_dateobj);
			foreach($dateperiod as $date){
				$datenum = $date->format("Y-m-d");
				$slots = 0;
				$slotid = "";
				$class = "";
				foreach($bid_slot as $key => $slot){
					if($slot['date'] == $datenum){
						$slots = $slot['slots'];
						$slotid = $slot['id'];
						if($slot['isholiday']) $class .= " w3-yellow ";
						unset($bid_slot[$key]);
					}
				}
				reset($bid_slot);
				// echo " is holiday aray- " . isHoliday($date)[0];
				$holidayreturn = isHoliday($date);
				if($holidayreturn[0]) {//$dbh->query("update bid_slot set isholiday = 1 where date = '$datenum'");
					$holidaytext = $holidayreturn[1];
					$dbh->query("insert into bid_holiday (date,holiday) values ('$datenum','$holidaytext')");
				
				}
				$monthnum = substr($datenum,5,2);
				// $daynum = substr($datenum,8,2);
				$daynum = $date->format("j");
				$yearnum = substr($datenum,0,4);
				
				if($monthnum != $lastmonthnum){
					for($i=$currentweekdaynum;$i<7;$i++){
						echo "<td></td>
						";
					}
					
					echo "</tr></table></div>
					";
						echoStartTable($date);
				}else{
					if($currentweekdaynum == 0) echo "<tr>
					";
				}
				
				$lastmonthnum = $monthnum;
				$classholiday = "daynumbold ";
				
				echo "<td class='$class' data-date='$datenum'>
					<h5 class='$classholiday w3-border '>$daynum</h5>
					<a href='./adminbidslotedit.php?slot=$slotid'><br>$slots</a>
				</td>
				";
				
				$currentweekdaynum++;
				if($currentweekdaynum == 7){
					$currentweekdaynum =0;
					echo "
					</tr>
					";
					// echoStartTable();
				}
				
			}
			
			echo "</tr></table></div>";

			
			

			
			
			?>
			
			<!--</table>-->
		<!--</div>-->
		
		
		
		
		
		</div>
	</body>

</html>
	
<?php
function echoStartTable($date){
	global $currentweekdaynum;
	// $start_dateobj = DateTime::createFromFormat("Y-m-d", $date,new DateTimeZone("America/New_York"));
	
	echo "<div class='w3-border w3-panel w3-center'>
	";
	echo "<h4>" . $date->format("F   Y") . " </h4>
	<table class='w3-table-all'>
	<tr>
		<th>Sun</th>
		<th>Mon</th>
		<th>Tue</th>
		<th>Wed</th>
		<th>Thu</th>
		<th>Fri</th>
		<th>Sat</th>
	</tr>
	";
	for($i=0;$i < $currentweekdaynum;$i++){
		echo "<td class='empty'></td>";
	}
}

?>