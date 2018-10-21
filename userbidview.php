<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../bidderexpress-dbaccess.php");



if(!isset($_SESSION['bidderexpress_user_id']) ){
	header("Location: login.php");
	die();
}

$bid_group_id = $_GET['bid_group'];
include("./header.php");
include('./functions.php');

$bid_line = $dbh->prepare("select bid_line.*,user.firstname as firstname,user.lastname as lastname,user.initial as initial from bid_line
 left join bid_user on bid_line.bid_user_id = bid_user.id
 left join user on user.id = bid_user.user_id 
 where bid_line.bid_group_id = :bid_group_id
 order by bid_line.line asc");
$bid_line->bindParam(":bid_group_id",$bid_group_id);
$bid_line->execute();
$bid_line = $bid_line->fetchAll();

$bid_group = $dbh->prepare("select bid_group.*,facility.name as facility_name,area.name as area_name from bid_group
left join area on area.id = bid_group.area_id
left join facility on facility.id = area.facility_id 
where bid_group.id = :bid_group_id");
$bid_group->bindParam(":bid_group_id",$bid_group_id);
$bid_group->execute();
$bid_group = $bid_group->fetch();

$facility_name = $bid_group['facility_name'];
$area_name = $bid_group['area_name'];
$bid_group_name = $bid_group['name'];
$bid_group_start_date = $bid_group['start_date'];
$bid_group_end_date = $bid_group['end_date'];
// bid book

$bid_dates = $dbh->prepare("select * from bid_slot where bid_slot.bid_group_id = :bid_group_id");
$bid_dates->bindParam(":bid_group_id", $bid_group_id);
$bid_dates->execute();
$bid_dates = $bid_dates->fetchAll();

$bids = $dbh->prepare("select bid_bid.*,user.firstname,user.lastname,user.initial,bid_round.number from bid_bid
left join bid_round on bid_round.id = bid_bid.bid_round_id
left join bid_user on bid_bid.bid_user_id = bid_user.id
left join user on bid_user.user_id = user.id
where bid_round.bid_group_id = :bid_group_id 
order by date asc");
$bids->bindParam(":bid_group_id",$bid_group_id);
$bids->execute();
$bids = $bids->fetchAll();

$timezone = new DateTimeZone("America/New_York");
$bid_date_first_dateobj = DateTime::createFromFormat("Y-m-d",$bid_dates[0]['date'],$timezone);



$isuseruptobid = isUserUptoBid($dbh);

?>



<!DOCTYPE html>
<html>
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1">

		<link rel="stylesheet" href="./w3.css">
	
		<title>Bidder Express</title>
		<style>
			.holidayhidden{
				display: none !important;
			}
			.selectday{
				background: green;
			}
			.emptyslot{
				height: 1.6em;
			}
			.biddate{
				overflow: hidden;
			}
			.fixedtable{
				table-layout: fixed;
			}
			table tr .daynum {
				text-align: center !important;
			}
		</style>

	</head> 
	<body>
		<div class='w3-container'>
			<?php echoHeader(); 
			if(count($isuseruptobid) > 0){
				foreach($isuseruptobid as $a){
					$thisbid_group_id = $a['bid_group_id'];
					$thisbidtype = $a['type'];
					if($thisbid_group_id == $bid_group_id){
						switch($thisbidtype){
							case "schedule":
								echo "<h4 class='w3-center'>Your up to bid a line.  To bid the line, open the line page by clicking on the line number</h4>";
								break;
							case "prime":
								$thisbid_line_id = $a['bid_line_id'];
								echo "<h4 class='w3-center'><a href='./displayschedule.php?bid_line_id=$thisbid_line_id'>Your up to bid Leave.</a></h4>";
								break;
						}
					}
				}
				
			}
			
			?>
			<hr>
			<?php
			echo "<h5>$facility_name - $area_name</h5>
			<h4>$bid_group_name <span class='w3-small'>-- $bid_group_start_date - $bid_group_end_date</span></h4>";
			?>
			
			<table class='w3-table-all'>
			<tr>
				<th>Line</th>
				<th>Name</th>
				<th>Sun</th>
				<th>Mon</th>
				<th>Tue</th>
				<th>Wed</th>
				<th>Thur</th>
				<th>Fri</th>
				<th>Sat</th>
				<th>Notes</th>
				<th class='w3-small' title='Rotate (in weeks)'>Rot</th>
				<th class='w3-small' title='Holidays that are RDOs are shown first, then in order'>Holidays<button id='holidaybutton' onClick='holidayToggle()' class='w3-tiny'>Show</button></th>
			</tr>
			<?php
			foreach($bid_line as $line){
				$lineid = $line['id'];
				$linenum = $line['line'];
				$firstname = $line['firstname'];
				$lastname = $line['lastname'];//isset($line['lastname']) ? $line['lastname']: "";
				$initial = $line['initial'];
				// $sun = substr($line['sun'],0,-3);
				// $mon = substr($line['mon'],0,-3);
				// $tue = substr($line['tue'],0,-3);
				// $wed = substr($line['wed'],0,-3);
				// $thur = substr($line['thur'],0,-3);
				// $fri = substr($line['fri'],0,-3);
				// $sat = substr($line['sat'],0,-3);
			
				$sun = (substr($line['sun'],0,2)) . (substr($line['sun'],3,2));
				$mon = (substr($line['mon'],0,2)) . (substr($line['mon'],3,2));
				$tue = (substr($line['tue'],0,2)) . (substr($line['tue'],3,2));
				$wed = (substr($line['wed'],0,2)) . (substr($line['wed'],3,2));
				$thur =(substr($line['thur'],0,2)) .(substr($line['thur'],3,2));
				$fri = (substr($line['fri'],0,2)) . (substr($line['fri'],3,2));
				$sat = (substr($line['sat'],0,2)) . (substr($line['sat'],3,2));
				
				$sunclass = "";
				$monclass = "";
				$tueclass = "";
				$wedclass = "";
				$thurclass = "";
				$friclass = "";
				$satclass = "";
				
				$rdocolor = " w3-grey ";
				if($sun == "0000") {
					$sunclass .= $rdocolor;
					$sun = "rdo";
				}
				if($mon == "0000") {
					$monclass .= $rdocolor;
					$mon = "rdo";
				}
				if($tue == "0000") {
					$tueclass .= $rdocolor;
					$tue = "rdo";
				}
				if($wed == "0000") {
					$wedclass .= $rdocolor;
					$wed = "rdo";
				}
				if($thur == "0000") {
					$thurclass .= $rdocolor;
					$thur = "rdo";
				}
				if($fri == "0000") {
					$friclass .= $rdocolor;
					$fri = "rdo";
				}
				if($sat == "0000") {
					$satclass .= $rdocolor;
					$sat = "rdo";
				}
				$notes = $line['notes'];
				$rotate = $line['rotate'] == "0"? "":$line['rotate'];
				$holiday = $line['holiday_text'];
				
				echo "<tr>
				<td><a href='./displayschedule.php?bid_line_id=$lineid'>$linenum</a></td>
				<td title='$initial'>$lastname<span class='w3-tiny'>$firstname</span></td>
				<td class='$sunclass'>$sun</td>
				<td class='$monclass'>$mon</td>
				<td class='$tueclass'>$tue</td>
				<td class='$wedclass'>$wed</td>
				<td class='$thurclass'>$thur</td>
				<td class='$friclass'>$fri</td>
				<td class='$satclass'>$sat</td>
				<td class='w3-border'>$notes</td>
				<td>$rotate</td>
				<td class='w3-tiny holiday holidayhidden'>$holiday</td>
				</tr>";
			}
			?>
			</table>
			
			
			
			
			<h4>Bid Book</h4>
			<div class='w3-panel w3-border'>
					<?php
					$lastmonthnum = $bid_date_first_dateobj->format("m");
					$currentweekdaynum = $bid_date_first_dateobj->format("w");
					echoStartTable($bid_date_first_dateobj);					
					foreach($bid_dates as $daterow){
						$datenum = $daterow['date'];
						$isholiday = $daterow['isholiday'];
						$monthnum = substr($datenum,5,2);
						$daynum = substr($datenum,8,2); //with leading zero
						$daynum = substr($daynum,0,1) =="0"? substr($daynum,1,1) : $daynum;
						$yearnum = substr($datenum,0,4);
						$slots = $daterow['slots'];
						$class = "";
						$classholiday = "";
							
						if($monthnum != $lastmonthnum){
							for($i=$currentweekdaynum;$i<7;$i++){
								echo "<td></td>\n";
							}
							echo "</tr></table></div>\n";
							echoStartTable(DateTime::createFromFormat("Y-m-d",$datenum,$timezone));
						}else{
							if($currentweekdaynum == 0) echo "<tr>\n";
						}
						//goes throught all of the bids, finds the ones for today, puts them in a local array, and 
						//removes them from the global array of all bids (so they are not looked at again)
						$bidsforday = array();
						foreach($bids as $key => $bid){
							if($bid['date'] == $datenum){
								$bidsforday[] = $bid;
								unset($bids[$key]);
							}
						}
						reset($bids);
						
						$countofbids = count($bidsforday);
						
						if($isholiday) $classholiday .= " w3-yellow ";
						echo "<td class='$class biddate' data-date='$datenum'>
							<h5 class='$classholiday daynum'>$daynum</h5>\n";
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
							echo "<h5 class='$forclass' title='$eachbidinitial - $eachbidlastname, $eachbidfirstname - Round $eachbidnumber'>$eachbiddisplayname</h5>\n";
						}	
						if($counter < $slots){ 
							$emptyslots = $slots - $counter;
							for($e = $emptyslots;$e>0;$e--){
								echo "<h5 class='w3-border emptyslot'> </h5>\n";
							}
						}
						echo "</td>\n";
						
						$currentweekdaynum++;
						if($currentweekdaynum == 7){
							$currentweekdaynum =0;
							echo "
							</tr>
							";
						}
						
						$lastmonthnum = $monthnum;
					}
					?>
					
					
					
					
				</table>
			</div>
			
			
		</div>
	</body>

		<script>
			var holidaybutton = document.getElementById("holidaybutton");
			var holidayclass = document.getElementsByClassName("holiday");
			// var holidayclass = document.querySelectorAll(".holiday");
			function holidayToggle(){
				for(var i = holidayclass.length-1;i>-1;i--){
					if(holidaybutton.innerText == "Show"){
						holidayclass[i].classList.remove("holidayhidden") ;
					} else holidayclass[i].classList.add("holidayhidden") ;
				}
				if(holidaybutton.innerText == "Show") holidaybutton.innerText = "Hide";
				else holidaybutton.innerText = "Show";
			}
		</script>

</html>
	
	
<?php
function echoStartTable($date){
	global $currentweekdaynum;
	// $start_dateobj = DateTime::createFromFormat("Y-m-d", $date,new DateTimeZone("America/New_York"));
	
	echo "<div class='w3-border w3-panel w3-center'>
	";
	echo "<h4>" . $date->format("F   Y") . " </h4>
	<table class='w3-table-all fixedtable'>
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
	
	