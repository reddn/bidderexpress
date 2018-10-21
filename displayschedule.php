<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../bidderexpress-dbaccess.php");

// if(!isset($_GET['facility'])) die();
 
if(!isset($_SESSION['bidderexpress_user_id']) ){
	header("Location: login.php");
	die();
}

include("./header.php");
include('./functions.php');

$bid_line_id = $_GET['bid_line_id'];


$stmt = $dbh->prepare("select bid_group.start_date,bid_group.end_date, bid_group.id as bid_group_id,
 bid_line.*, bid_group.name as bid_name, facility.name as facility_name, area.name as area_name, user.lastname, user.firstname,
 bid_user.id as bid_user_id
 from bid_line 
 left join bid_group on bid_group.id = bid_line.bid_group_id
 left join area on area.id = bid_group.area_id 
 left join facility on facility.id = area.facility_id
 left join bid_user on bid_user.id = bid_line.bid_user_id
 left join user on user.id = bid_user.user_id
 where bid_line.id = :bid_line_id
");
$stmt->bindParam(":bid_line_id",$bid_line_id);
$stmt->execute();
$bid_data =  $stmt->fetch();


$bid_name = $bid_data['bid_name'];
$area_name = $bid_data['area_name'];
$facility_name = $bid_data['facility_name'];
$line = $bid_data['line'];
$bid_group_id = $bid_data['bid_group_id'];
$bid_group_start_date = $bid_data['start_date'];
$bid_group_end_date = $bid_data['end_date'];
$bid_user_id = $bid_data['bid_user_id'];

if($bid_data['lastname'] != "") $user_name = $bid_data['lastname'] . ", " . $bid_data['firstname'];// $bid_data['user_name'];
else $user_name = "";
$sun = $bid_data['sun'] == "00:00:00" ? "RDO" : substr($bid_data['sun'],0,-3);
$mon = $bid_data['mon'] == "00:00:00" ? "RDO" : substr($bid_data['mon'],0,-3);
$tue = $bid_data['tue'] == "00:00:00" ? "RDO" : substr($bid_data['tue'],0,-3);
$wed = $bid_data['wed'] == "00:00:00" ? "RDO" : substr($bid_data['wed'],0,-3);
$thur= $bid_data['thur'] == "00:00:00" ? "RDO" : substr($bid_data['thur'],0,-3);
$fri = $bid_data['fri'] == "00:00:00" ? "RDO" : substr($bid_data['fri'],0,-3);
$sat = $bid_data['sat'] == "00:00:00" ? "RDO" : substr($bid_data['sat'],0,-3);
$notes = $bid_data['notes'];
$rotate = $bid_data['rotate'];
$holiday_text = $bid_data['holiday_text'];


$bid_schedule = $dbh->prepare("select bid_schedule.*,bid_slot.slots from bid_schedule
left join bid_slot  on bid_slot.bid_group_id = bid_schedule.bid_group_id and bid_slot.date = bid_schedule.date
where bid_line_id = :bid_line_id

order by date asc");
$bid_schedule->bindParam(":bid_line_id",$bid_line_id);
$bid_schedule->execute();
$bid_schedule = $bid_schedule->fetchAll();

$start_dateobj = DateTime::createFromFormat("Y-m-d", $bid_data['start_date'],new DateTimeZone("America/New_York"));

try{
	$start_datedaynum = $start_dateobj->format("w");
} catch (Exception $e){
	$start_datedaynum = 0;
}
$bid_scheduleCount = count($bid_schedule);


$bids = $dbh->prepare("select bid_bid.*,user.firstname,user.lastname,user.initial,bid_round.number from bid_bid
left join bid_round on bid_round.id = bid_bid.bid_round_id
left join bid_user on bid_bid.bid_user_id = bid_user.id
left join user on bid_user.user_id = user.id
where bid_round.bid_group_id = :bid_group_id 
order by date asc");
$bids->bindParam(":bid_group_id",$bid_group_id);
$bids->execute();
$bids = $bids->fetchAll();

$isuseruptobid = isUserUptoBid($dbh);


$isuptobidleave = false;
$isuptobidschedule = false;

foreach($isuseruptobid as $a){
	$thisbid_round_id = $a['bid_round_id'];
	$thisbid_group_id = $a['bid_group_id'];
	$thisbidtype = $a['type'];
	if($thisbid_group_id == $bid_group_id){
		switch($thisbidtype){
			case "schedule":
				$isuptobidschedule = true;
				$schedulebid_round_id = $thisbid_round_id;
				break;
			case "prime":
				$isuptobidleave = true;
				$primebid_round_id = $thisbid_round_id;
				$primebid_number = $a['number'];
				$primebid_line_id = $a['bid_line_id'];
				break;
		}
		
	}
		
		
}
if($isuptobidleave){
	if($bid_line_id == $primebid_line_id) $isuptobidleavethisround = true;
	else $isuptobidleavethisround = false;
}else $isuptobidleavethisround = false;
?>


<!DOCTYPE html>
<html>
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1">

		<link rel="stylesheet" href="./w3.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
		<title>Bidder Express</title>
		<style>
			#submitleave{
				display: none;
				position: fixed;
				right: 5px;
				bottom: 5px;
				background: purple;
				opacity:.8;
				border-radius: 3em;
				/*padding-bottom: 1em;*/
				padding: 2em;
			}
			.holiday{
				width: 100%;
				display: inline-block;
				
			}
			.caltable{
				table-layout: fixed;
			}
			table tr td{
				text-align: center !important;
				white-space: nowrap;
				overflow: hidden;
			}
			table tr th{
				text-align: center !important;
			}
			.daynumbold{
				font-weight: bold;
			}
			.holidayhidden{
				display: none !important;
			}
			.selectdate{
				background: green;
			}
			.daysselecteddiv{
				display: none;
				position: fixed;
				top: 0;
				right: 0;
				pointer-events: none;
			    opacity: .7;
			    background: red;
			    border-radius: 20px;
			}
			.emptyslot{
				height: 1.6em;
			}
			.emptyslotfill{
				height: 1.6em;
			}
			#bottomspacer{
				margin-bottom:8em !important;
			}
			.displaynone{
				display:none;
			}
		</style>
		
		
	</head>
	<body>
		
		<div class='w3-container'>
			<?php echoHeader(); ?>
			<hr>
			<?php echo "<h4><span class='w3-normal'>$facility_name</span> - $area_name</h4>
			<h3><a href='./userbidview.php?bid_group=$bid_group_id'>$bid_name</a>
			<span class='w3-small'>-- $bid_group_start_date - $bid_group_end_date</span></h3><br>"; 
			?>
			<div class=" w3-panel w3-border daysselecteddiv">
				Days selected:<br>
				<span id='daysselected'></span>
			</div>
			<div class=''><table class='w3-table-all w3-small '>
			<tr>
				<th>Line</th>
				<th class='w3-hide-small'>Name</th>
				<th>Sun</th>
				<th>Mon</th>
				<th>Tue</th>
				<th>Wed</th>
				<th>Thur</th>
				<th>Fri</th>
				<th>Sat</th>
				<th>Notes</th>
				<th class='w3-small w3-hide-small' title='Rotate (in weeks)'>Rot</th>
				<th class='w3-left-align'>Holidays <button id='holidaybutton' onClick='holidayToggle()' class='w3-tiny w3-button'>Show</button></th>
			</tr>
			<tr>
				<?php
				echo "<td>$line</td>
				<td class='w3-hide-small'>$user_name</td>
				<td>$sun</td>
				<td>$mon</td>
				<td>$tue</td>
				<td>$wed</td>
				<td>$thur</td>
				<td>$fri</td>
				<td>$sat</td>
				<td>$notes</td>
				<td class='w3-hide-small'>$rotate</td>
				<td><div class='holidaytd holidayhidden'>$holiday_text</div></td>";
				?>
			</tr>
			</table>
			</div>
			<?php
			
			if($isuptobidschedule && $bid_user_id == ""){
				
				 echo "<div class='w3-panel w3-border w3-padding submitschedulebid'>
					<h4>Your up to bid a schedule</h4>
					<form method='POST' action='./displayschedulesubmit.php'>
					<button id='bidthisline' class='w3-button '>Select this line<?php echo $line;?></button>
					<input type='submit' value='Submitplease'>
					<input type='hidden' name='bid_group_id' value='$bid_group_id'>
					<input type='hidden' name='bid_line_id' value='$bid_line_id'>
					<input type='hidden' name='bid_user_id' value='$bid_user_id'>
					<input type='hidden' name='bid_round_id' value='$schedulebid_round_id'>
					<input type='hidden' name='action' value='submitschedulebid'>
					<input type='hidden' name='bid_round_type' value='schedule'>
					</form>
				</div>";
			} else $isuptobidschedule = false;
			
			if($isuptobidleavethisround) echo "<div class='w3-panel w3-border w3-padding submitleavebid'>
				<h4>Your up to bid Leave for round $primebid_number</h4>
			</div><span id='dataobj' class='displaynone' data-bidgroupid='$bid_group_id' data-bidlineid='$bid_line_id' data-bidroundid='$primebid_round_id'></span>";//** is $leaveround need to get round number
				// <button id='bidthisline' class='w3-button '>Place Leave bid</button>
			
			?>
			
				<?php
					$currentweekdaynum = $start_dateobj->format("w");
					$lastmonthnum = $start_dateobj->format("m");
					function echoStartTable($date){
						global $currentweekdaynum;
						$start_dateobj = DateTime::createFromFormat("Y-m-d", $date,new DateTimeZone("America/New_York"));
						
						echo "<div class='w3-border w3-margin-top w3-center scheduletable'><h4>" . $start_dateobj->format("F   Y") . " </h4>";
						echo "<table class='w3-table w3-striped w3-bordered caltable'>
						<tr>
							<th>Sun</th>
							<th>Mon</th>
							<th>Tue</th>
							<th>Wed</th>
							<th>Thu</th>
							<th>Fri</th>
							<th>Sat</th>
						</tr>
						<tr>";
						for($i=0;$i < $currentweekdaynum;$i++){
							echo "<td class='empty'></td>";
						}
					}
					echoStartTable( $bid_data['start_date']);
				?>
				
				
				<?php
				foreach ($bid_schedule as $bidday){
					$class = "";
					$classdaynum = "daynumbold ";
					$isHoliday = $bidday['holiday'];
					$isInLieu = $bidday['holiday_inlieu'];
					$date = $bidday['date'];
					$slots = $bidday['slots'];
					$currentmonthnum = substr($date,5,2);
					$currentdaynum = substr($date,8,2);
					$userhasbidthisday = false;
					if(substr($currentdaynum,0,1) == "0") $currentdaynum = substr($currentdaynum,1,1);
					
					//goes throught all of the bids, finds the ones for today, puts them in a local array, and 
					//removes them from the global array of all bids (so they are not looked at again)
					$bidsforday = array();
					foreach($bids as $key => $bid){
						if($bid['date'] == $date){
							$bidsforday[] = $bid;
							if($bid['bid_user_id'] == $bid_user_id) $userhasbidthisday = true;
							unset($bids[$key]);
						}
					}
					reset($bids);
					
					$countofbids = count($bidsforday);
						
					
					
					if($lastmonthnum != $currentmonthnum){
						echo "</tr></table></div>";
						echoStartTable($date);
					}else{
						if($currentdaynum == 0) echo "<tr>";
					}
					if($isHoliday){
						$classdaynum .= "w3-yellow holiday ";
						$class .= " w3-border ";
					} 
					if($isInLieu){
						$classdaynum .= "w3-pink holiday ";
						$class .= " w3-border ";
					}
					$shift = substr($bidday['start_time'],0,-3);
					if($shift == "00:00") {
						$shift = "RDO";
						$class .= " w3-grey RDO noselect ";
					}
					// echo $date;
					echo "<td class='$class' data-date='$date'>
						<span class='$classdaynum'>$currentdaynum</span>
						<br>$shift";
						
					$counter = 0;
					foreach($bidsforday as $eachbid){
						$counter++;
						$forclass = "";
						$eachbidfirstname = $eachbid['firstname'];
						$eachbidlastname  = $eachbid['lastname'];
						$eachbidinitial  = $eachbid['initial'];
						$eachbidnumber  = $eachbid['number'];
						$eachbiddisplayname = $eachbidlastname .", " . substr($eachbidfirstname,0,1);
						if($counter <= $countofbids) $forclass .= " w3-border ";
						echo "<h5 class='slots $forclass' title='$eachbidinitial - $eachbidlastname, $eachbidfirstname - Round $eachbidnumber'>$eachbiddisplayname</h5>\n";
					}	
					if($counter < $slots){ 
						$emptyslots = $slots - $counter;
						if(!$userhasbidthisday) $emptyslotclass = "emptyslot";
						else $emptyslotclass = "";
						for($e = $emptyslots;$e>0;$e--){
							echo "<h5 class='slots w3-border emptyslotfill $emptyslotclass'> </h5>\n";
						}
					}	
					
					echo "</td>";
					
					// if($currentweekdaynum == "6");
					
					$lastmonthnum = $currentmonthnum;
					$currentweekdaynum++;
					if($currentweekdaynum == 7){
						$currentweekdaynum =0;
						echo "</tr>";
						// echoStartTable();
					}
					
				}
				?>	
				</table>
				</div>
				
			<!--</div>-->
			
		</div>
		<div id='submitleave' class='w3-panel'><button id='submitleavebtn'>Submit Leave</button>
			<br> 
		
		</div>
		<div  id='bottomspacer'></div>
	</body>
	<script>
			var isuptobidleave = <?php if($isuptobidleave) echo "true"; else echo "false"; ?>

	</script>
<script src='./js/displayschedule.js'></script>

</html>
	
	
	