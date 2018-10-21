<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

ini_set('display_startup_errors', 1);

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
$bid->bindParam(":bid_group_id", $bid_group_id);
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

$roundcount = $dbh->prepare("select max(number) as maxround from bid_round where bid_group_id = :bid_group_id and type = 'prime'");
$roundcount->bindParam(":bid_group_id", $bid_group_id);
$roundcount->execute();
$roundcount = $roundcount->fetch()['maxround'];
if($roundcount == "") $roundcount = 0;

$results = array();
$sqladdon1 = "";
$sqladdon2 = "";
for($i =1;$i<($roundcount+1);$i++){
	// // echo "$i is the iterator<br>";
	// $sqladdon1 .= " , bid_tracker_round$i.scheduled_time as round$i". "scheduled_time 
	// ";
	// // $sqladdon2 .= "
	// // left join bid_tracker as bid_tracker_round$i on bid_tracker_round$i.bid_group_id = bid_round.id 
	// // 	and bid_tracker_round$i.type = 'prime' 
	// // 	and bid_tracker_round$i.number = $i";
		
	// $sqladdon2 .= "left join bid_tracker as bid_tracker_$i on bid_tracker_$i.bid_group_id = bid_tracker.bid_group_id 
	// 	and bid_tracker_$i.type = 'prime' 
	// 	AND bid_tracker_$i.number = $i";
	
	$stmt = $dbh->prepare("select bid_user_id,scheduled_time,status from bid_tracker
	left join bid_round on bid_tracker.bid_round_id = bid_round.id
	left join bid_group on bid_round.bid_group_id = bid_group.id
	where bid_group.id = :bid_group_id AND bid_round.number = :number");
	$stmt->bindParam(":number",$i);
	$stmt->bindParam(":bid_group_id",$bid_group_id);
	$stmt->execute();
	$results[$i] = $stmt->fetchAll();
	
}

// echo "<pre>sql1 is $sqladdon1";
// echo "sql2 is $sqladdon2</pre>";


// echo "<pre>";
// print_r($results);
// echo "</pre>";


// $sql = "select user.lastname,user.firstname,user.initial,user.BUE,user.SCD,user.military_time,user.id,bid_tracker.status,
// bid_tracker.scheduled_time $sqladdon1 from user 
// 	left join bid_user on bid_user.user_id = user.id
// 	left join bid_round on bid_round.bid_group_id = bid_user.bid_group_id 
// 		and bid_round.type = 'scheudle'
// 	left join bid_tracker on bid_user.id = bid_tracker.bid_user_id 
// 	$sqladdon2 
// 	where bid_user.bid_group_id = :bid_group_id
// 	order by user.bue asc,user.military_time asc,user.tie_break asc";

// $sql = "select user.lastname,user.firstname,user.initial,user.BUE,user.SCD,user.military_time,user.id,bid_tracker.status,
// bid_tracker.scheduled_time  from user 
// 	left join bid_user on bid_user.user_id = user.id
// 	left join bid_round on bid_round.bid_group_id = bid_user.bid_group_id 
// 		and bid_round.type = 'scheudle'
// 	left join bid_tracker on bid_user.id = bid_tracker.bid_user_id 

// 	where bid_user.bid_group_id = :bid_group_id
// 	order by user.bue asc,user.military_time asc,user.tie_break asc";
// echo "<pre>$sql</pre>";

$sql = "select user.lastname,user.firstname,user.initial,user.BUE,user.SCD,user.military_time,user.id,bid_user.id as bid_user_id from user 
	left join bid_user on bid_user.user_id = user.id
	where bid_user.bid_group_id = :bid_group_id
	order by user.bue asc,user.military_time asc,user.tie_break asc";

$userlist = $dbh->prepare($sql);	

$userlist->bindParam(":bid_group_id",$bid_group_id);
$userlist->execute();



try{
	$userlist = $userlist->fetchAll();
	
}catch(Exception $e){
	echo "error, userlist didnt work";
	die();
}


addRoundsToUserlist();



$schedulelist = $dbh->prepare("select * from bid_line where bid_group_id = :bid_group_id");
$schedulelist->bindParam(":bid_group_id",$bid_group_id);
$schedulelist->execute();
$schedulelist = $schedulelist->fetchAll();

$bidRoundArray = returnBidRoundArray($dbh,$bid_group_id);
$bidRoundArrayCount = count($bidRoundArray);

// $maxline = $dbh->prepare("select max(line) as max from bid_line where bid_group_id = :bid_group_id");
// $maxline->bindParam(":bid_group_id",$bid_group_id);
// $maxline->execute();
// try{
// 	if($maxline->rowCount()==1) $maxline = $maxline->fetch()['max'];
// }catch (Exception $e){
// 	$maxline = 0;
// }

function returnBidArray($dbh,$bid_group_id){
	$bid = $dbh->prepare("select bid_group.*,facility.name as facility_name,area.name as area_name,facility.id as facility_id from bid_group
	left join area on bid_group.area_id = area.id
	left join facility on area.facility_id = facility.id 
	where bid_group.id = :bid_group_id");
	$bid->bindParam(":bid_group_id", $bid_group_id);
	$bid->execute();
	return $bid->fetchAll();
}

function returnBidRoundArray($dbh,$bid_group_id){
	$bid = $dbh->prepare("select bid_round.name,bid_round.type,bid_round.number from bid_round 
	left join bid_group on bid_group.id = bid_round.bid_group_id
	where bid_group.id = :bid_group_id
	order by bid_round.type asc, bid_round.name asc");
	$bid->bindParam(":bid_group_id", $bid_group_id);
	$bid->execute();
	return $bid->fetchAll();
}


//ERROR FUNCTIONS
function errorPage($msg){
	$_SESSION['message'] .= $msg;
	$_SESSION['message']  = "";
	header("Location: adminarea.php?area=" . $_GET['area']);
	die();
}



?>



<!DOCTYPE html>
<html>
	<head>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="stylesheet" href="./w3.css">
		<title>Bidder Express</title>
		<style>
			.formInline{
				display:inline;
			}
			.statusbox{
				width: 1em;
				display: inline-block;
				height: 1em;
				margin-right: 1em;
			}
			.datecontrol{
				float: right;
				display: none;
				
			}
			.datecontrol tr td{
				padding: 0px;
				margin: 0px;
			}
			#submitnewtimes{
				display:none;
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
				<h3>Bid Scheduler</h3>
				<h5><?php echo "<a href='adminfacility.php?facility=$facility_id'>$facility_name</a>
				-- <a href='adminarea.php?area=$area_id'>$area_name</a>";?></h5>
				<h4><?php echo "<a href='adminbidviewer.php?bid=$bid_group_id&area=$area_id'>$bid_name <span class='w3-small'>-- $start_date - $end_date</span></a>";?></h4>
			</div>
			
			<div class='w3-border w3-panel'>
				<table class='w3-table'>
					<tr>
						<td>
							<form action="adminbidsubmit.php" method='POST' class='formInline'>
							<input type='hidden' name='bid' value='<?php echo $bid_group_id;?>'>
							<input type='hidden' name='action' value='createschedulebidwithtimes'>
							<input type='submit' value='Add Schedule bid With Times'>

						</td>
						<td>
							<span class='w3-small'>Date</span><br>
							<input class='w3-input' placeholder='YYYY/MM/DD' name='startdate'  maxlength='10' length='10' width='10'>
						</td>
						<td>
								<span class='w3-small'>Start time</span><br>
								<input class='w3-input' placeholder='HHMM' name='starttime'  maxlength='4' length='4'>
							</td>
						<td>
							<span class='w3-small'>End time</span><br>
							<input class='w3-input' placeholder='HHMM' name='endtime' maxlength='4' length='4'>
						</td>
						<td>
								<span class='w3-small'>Interval (minutes)</span><br>
								<input class='w3-input' placeholder='##' name='interval'  maxlength='4' length='4'>
						</td>
							</form>
						<td>
							<form action="adminbidsubmit.php" method='POST' class='formInline'>
							<input type='hidden' name='bid' value='<?php echo $bid_group_id;?>'>
							<input type='hidden' name='action' value='leavebidwithtimes'>
							<input type='submit' value='Add Leave bid With Times'>
						</td>
						<td>
								<span class='w3-small'>Date</span><br>
								<input class='w3-input' placeholder='YYYY/MM/DD' name='startdate' maxlength='10' length='10'>
						</td>
						<td>
							
								<span class='w3-small'>Start time</span><br>
								<input class='w3-input' placeholder='HHMM' name='starttime' maxlength='4' length='4'>
						</td>
						<td>
								<span class='w3-small'>End time</span><br>
								<input class='w3-input' placeholder='HHMM' name='endtime' maxlength='4'>
						</td>
						<td>
								<span class='w3-small'>Interval (minutes)</span><br>
								<input class='w3-input' placeholder='##' name='interval'  maxlength='4' length='4'>
						</td>
				</form>
				</tr>
				<tr>
				<td>
				<form action="adminbidsubmit.php" method='POST' class='formInline'>
					<input type='hidden' name='bid' value='<?php echo $bid_group_id;?>'>
					<input type='hidden' name='action' value='schedulebidwithouttimes'>
					<input type='submit' value='Add Schedule bid WithOUT Times'>
				</form>
				</td>
				<td>
					<form action="adminbidsubmit.php" method='POST' class='formInline'>
					<input type='hidden' name='bid' value='<?php echo $bid_group_id;?>'>
					<input type='hidden' name='action' value='leavebidwithouttimes'>
					<input type='submit' value='Add Leave bid WithOUT Times'>
				</form>
					</td>
					
				</tr>
				</table>
					
			</div>
			
			
			<div class='w3-border w3-panel'>
				<h4>Current Employees
					<span id='toggledatesbtnspan' class='w3-tiny'>
					<span class='datecontrol'><input id='ripplecheck' type='checkbox' checked>Ripple
					<button id="submitnewtimes">Submit New Bid Times</button></span> 
					<button class='w3-center' id='toggledatesbtn'>Show Date Control</button></span>
					</h4>
				<table class='w3-table-all w3-small ' id='maintable' style='overflow: auto;'>
					<tr>
						<th>#</th>
						<th>BUE</th>
						<th>Name</th>
					
						<?php
						
							foreach($bidarray as $bid){
								$name = $bid['name'];
								$number = $bid['number'];
								$type= $bid['type'];
								echo "<th>$name</th>";
							}
						
						
						
						?>
						<!--<th>Schedule</th>-->
					</tr>
					<?php
					
					
					// $counter = 1;
					// foreach($userlist as $usera){
					// 	$lastname = $usera['lastname'];
					// 	$firstname = $usera['firstname'];
					// 	$name = $usera['lastname'] . ", " .substr($usera['firstname'],0,1) . ".";
					// 	$initial = $usera['initial'];
					// 	$bue = $usera['BUE'];
					// 	// $scheduled_time = $usera['scheduled_time'];
					// 	// $scheduled_time_status = $usera['status'];
					// 	// echo "$counter.<span class='w3-tiny'>$bue</span> $lastname, $firstname - $initial<br>";
					// 	echo "<tr>
					// 	<td>$counter</td>
					// 	<td class='w3-tiny'>$bue</td>
					// 	<td title='$lastname,$firstname'>$name</td>
					// 	<td class='w3-small'>$initial</td>";
					// 	// <td>$scheduled_time - $scheduled_time_status</td>
						
					// 	echo "</tr>";
					// 	$counter++;
					// }
				$counter = 1;
				foreach($userlist as $usera){
					
					$firstname = $usera['firstname'];
					$lastname = $usera['lastname'];
					$initial = $usera['initial'];
					$bue = $usera['BUE'];
					// $round0status=$usera['round0status'];
					// $round0scheduled_time=substr($usera['round0scheduled_time'],0,-3);
					echo "\n<tr>\n<td>$counter.</td>
					<td><span class='w3-tiny'>$bue</span></td>
					<td>$lastname, $firstname<span class='w3-tiny'> -$initial</span></td>\n";
					foreach($usera["rounds"] as $roundskey => $round){
						$status = $round['status'];
						$scheduled_time = substr($round['scheduled_time'],0,-3);
						$scheduled_time_orig = $scheduled_time;
						$scheduled_time = substr($scheduled_time,5);
						switch($status){
							case "NE": //NE', 'UTB', 'P-E', 'P-NE', 'DONE
								$statusclass = "w3-grey";
								$smallbox = "<span class='statusbox $statusclass'></span>";
								break;
							case "UTB": 
								$statusclass = "w3-green";
								$smallbox = "<span class='statusbox $statusclass'></span>";
								break;
							case "P-E":
								$statusclass = "w3-yellow";
								$smallbox = "<span class='statusbox $statusclass'></span>";
								break;
							case "P-NE":
								$statusclass = "w3-blue";
								$smallbox = "<span class='statusbox $statusclass'></span>";
								break;
							case "DONE":
								$statusclass = "w3-red";
								$smallbox = "<span class='statusbox $statusclass'></span>";
								break;
							case "PENDING-DONE":
								$smallbox = "<span class='statusbox' style='background: linear-gradient(90deg, red 50%, #8bc34a 50%)'></span>";
								break;
						}
						
						
						echo "\n<td class=' smallboxtd w3-tiny' title='$scheduled_time_orig'>$smallbox
						<span class='bidtime'>$scheduled_time</span>
						<div class='datecontrol'>
						\n<table style='padding:0px; margin:0px; overflow: auto;'>
						<tr>
						<td><button id='dayup' class='dayadd' style=''>Day+</button></td>
						<td><button id='hourup'  class='houradd'>Hour+</button></td>
						<td><button id='minuteup' class='minadd'>Min+</button></td>
						</tr>
						<tr>
						<td><button id='daydown'  class='daysub' style=''>Day-</button></td>
						<td><button id='hourdown' class='hoursub'>Hour-</button></td>
						<td><button id='mindown' class='minsub'>Min-</button></td>
						</tr>
						</table>
						</div>
						</td>";
					
						
					}
					
					
					// echo "<td class='w3-red'>$round0status - $round0scheduled_time</td>";
					
					echo "\n</tr>";
					$counter++;
				}
				
					
					?>
				</table>
			</div>
		</div>
	</body>
	<script>
		$("#toggledatesbtn").on("click", function(e){
			let datecontrol = $(".datecontrol");
			datecontrol.toggle();
			if(datecontrol.css("display") == "none") $("#toggledatesbtn").text("Show Date Control");
			else $("#toggledatesbtn").text("Hide Date Control");
			
			// $(".datecontrol").toggle();	
		});
		$(".dayadd").on("click",function(e){
			$('#submitnewtimes').show();
			let parent = $(this).parent().parent().parent().parent().parent().parent();
			let newdate = addDays(parent.attr("title"),1);
			let columnnum= parent.index();
			parent.find(".bidtime").text(newdate.substr(5));
			parent.attr("title", newdate);
			if($("#ripplecheck:checkbox:checked").length ==1) {
				// console.log("rippple checked");
				// let newparent = parent.parent().parent();
				// console.log(newparent);
				let rows = $("#maintable > tbody > tr");
				let currentindex = parent.parent().index();
				let rowcount = rows.length;
				for(let i =currentindex+1;i<rowcount;i++){
					let currenttext= rows.eq(i).children().eq(columnnum).attr("title");
					let newfulltext = addDays(currenttext,1);
					
					rows.eq(i).children().eq(columnnum).attr("title",newfulltext)
					rows.eq(i).children().eq(columnnum).children().eq(1).text(newfulltext.substr(5));
				}
			}
		});
		$(".daysub").on("click",function(e){
			$('#submitnewtimes').show();
			let parent = $(this).parent().parent().parent().parent().parent().parent();
			let newdate = addDays(parent.attr("title"),-1);
			let columnnum= parent.index();
			parent.find(".bidtime").text(newdate.substr(5));
			parent.attr("title", newdate);
			
			if($("#ripplecheck:checkbox:checked").length ==1) {
				// console.log("rippple checked");
				// let newparent = parent.parent().parent();
				// console.log(newparent);
				let rows = $("#maintable > tbody > tr");
				let currentindex = parent.parent().index();
				let rowcount = rows.length;
				for(let i =currentindex+1;i<rowcount;i++){
					let currenttext= rows.eq(i).children().eq(columnnum).attr("title");
					let newfulltext = addDays(currenttext,-1);
					
					rows.eq(i).children().eq(columnnum).attr("title",newfulltext)
					rows.eq(i).children().eq(columnnum).children().eq(1).text(newfulltext.substr(5));
				}
			}
			
		});
		$(".houradd").on("click",function(e){
			$('#submitnewtimes').show();
			let parent = $(this).parent().parent().parent().parent().parent().parent();
			let newdate = addHours(parent.attr("title"),1);
			let columnnum= parent.index();
			parent.find(".bidtime").text(newdate.substr(5));
			parent.attr("title", newdate);
			
			if($("#ripplecheck:checkbox:checked").length ==1) {
				// console.log("rippple checked");
				// let newparent = parent.parent().parent();
				// console.log(newparent);
				let rows = $("#maintable > tbody > tr");
				let currentindex = parent.parent().index();
				let rowcount = rows.length;
				for(let i =currentindex+1;i<rowcount;i++){
					let currenttext= rows.eq(i).children().eq(columnnum).attr("title");
					let newfulltext = addHours(currenttext,1);
					
					rows.eq(i).children().eq(columnnum).attr("title",newfulltext)
					rows.eq(i).children().eq(columnnum).children().eq(1).text(newfulltext.substr(5));
				}
			}
			
		});
		$(".hoursub").on("click",function(e){
			$('#submitnewtimes').show();
			let parent = $(this).parent().parent().parent().parent().parent().parent();
			let newdate = addHours(parent.attr("title"),-1);
			let columnnum= parent.index();
			parent.find(".bidtime").text(newdate.substr(5));
			parent.attr("title", newdate);
			
			if($("#ripplecheck:checkbox:checked").length ==1) {
				// console.log("rippple checked");
				// let newparent = parent.parent().parent();
				// console.log(newparent);
				let rows = $("#maintable > tbody > tr");
				let currentindex = parent.parent().index();
				let rowcount = rows.length;
				for(let i =currentindex+1;i<rowcount;i++){
					let currenttext= rows.eq(i).children().eq(columnnum).attr("title");
					let newfulltext = addHours(currenttext,-1);
					
					rows.eq(i).children().eq(columnnum).attr("title",newfulltext)
					rows.eq(i).children().eq(columnnum).children().eq(1).text(newfulltext.substr(5));
				}
			}
			
		});
		$(".minadd").on("click",function(e){
			$('#submitnewtimes').show();
			let parent = $(this).parent().parent().parent().parent().parent().parent();
			let newdate = addMinutes(parent.attr("title"),1);
			let columnnum= parent.index();
			parent.find(".bidtime").text(newdate.substr(5));
			parent.attr("title", newdate);
			
			if($("#ripplecheck:checkbox:checked").length ==1) {
				// console.log("rippple checked");
				// let newparent = parent.parent().parent();
				// console.log(newparent);
				let rows = $("#maintable > tbody > tr");
				let currentindex = parent.parent().index();
				let rowcount = rows.length;
				for(let i =currentindex+1;i<rowcount;i++){
					let currenttext= rows.eq(i).children().eq(columnnum).attr("title");
					let newfulltext = addMinutes(currenttext,1);
					
					rows.eq(i).children().eq(columnnum).attr("title",newfulltext)
					rows.eq(i).children().eq(columnnum).children().eq(1).text(newfulltext.substr(5));
				}
			}
		});
		$(".minsub").on("click",function(e){
			$('#submitnewtimes').show();
			let parent = $(this).parent().parent().parent().parent().parent().parent();
			let columnnum= parent.index();
			let newdate = addMinutes(parent.attr("title"),-1);
			parent.find(".bidtime").text(newdate.substr(5));
			parent.attr("title", newdate);
			
			if($("#ripplecheck:checkbox:checked").length ==1) {
				// console.log("rippple checked");
				// let newparent = parent.parent().parent();
				// console.log(newparent);
				let rows = $("#maintable > tbody > tr");
				let currentindex = parent.parent().index();
				let rowcount = rows.length;
				for(let i =currentindex+1;i<rowcount;i++){
					let currenttext= rows.eq(i).children().eq(columnnum).attr("title");
					let newfulltext = addMinutes(currenttext,-1);
					
					rows.eq(i).children().eq(columnum).attr("title",newfulltext)
					rows.eq(i).children().eq(columnum).children().eq(1).text(newfulltext.substr(5));
				}
			}
		});
		function addMinutes(date,minutestoadd){
			let dateobj = new Date(date);
			dateobj.setMinutes(dateobj.getMinutes() + minutestoadd);
			return makeDateText(dateobj);
		}
		function addHours(date,hourstoadd){
			let dateobj = new Date(date); 
			dateobj.setHours(dateobj.getHours() + hourstoadd);
			return makeDateText(dateobj);
		}
		function addDays(date,daystoadd){
			let dateobj = new Date(date);
			dateobj.setDate(dateobj.getDate() + daystoadd);
			return makeDateText(dateobj);
		}
		function makeDateText(dateobj){
			let year = dateobj.getFullYear();
			let month = dateobj.getMonth() + 1;
			let day= dateobj.getDate();
			let hour= dateobj.getHours();
			let min= dateobj.getMinutes();
			let sec= dateobj.getSeconds();
			if(month <10) month= "0" + month;
			if(day < 10 ) day = "0" + day;
			if(hour <10) hour = "0" + hour;
			if(min <10) min = "0" + min;
			return year + "-" + month + "-" + day + " " + hour + ":" + min;
		}
		
	</script>
</html>