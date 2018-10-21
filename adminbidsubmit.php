<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../bidderexpress-dbaccess.php");

if(!isset($_SESSION['bidderexpress_user_id']) ){
	header("Location: login.php");
	die();
}

switch($_POST['action']){
	case "addschedule":
		addSchedule($dbh);
		break;
	case "adduser":
		addUser($dbh);
		break;
	case "createschedulebidwithtimes":
		echo "Create schedulebid with times";
		createScheduleBidWithTimes($dbh);
		break;
	case "createschedulebidwithouttimes":
		createScheduleBidWithoutTimes($dbh);
		break;
	case "createleavebidwithtimes":
		createLeaveBidWithTimes($dbh);
		break;
	case "leavebidwithouttimes":
		echo "line 32";
		createLeaveBidWithoutTimes($dbh);
		break;
}

function addSchedule($dbh){
	$howmany = $_POST['howmany'];
	$linenumber = (int)$_POST['line'];
	$bid_group_id = $_POST['bid'];
	if($linenumber == "" || $linenumber == "0"){
		// echo "in the if .. linenumber is $linenumber at the beginning";
		$maxline = $dbh->prepare("select max(line) as max from bid_line where bid_group_id = :bid_group_id");
		$maxline->bindParam(":bid_group_id",$bid_group_id);
		$maxline->execute();
		try{
			if($maxline->rowCount()==1) $linenumber = (int)$maxline->fetch()['max'] +1;
		}catch (Exception $e){
			$linenumber = 1;
		}
		// echo "  line number is $linenumber";
	}

		$sun = trim($_POST['sun']) . ":00";
		$mon = trim($_POST['mon']) . ":00";
		$tue = trim($_POST['tue']) . ":00";
		$wed = trim($_POST['wed']) . ":00";
		$thur = trim($_POST['thur']) . ":00";
		$fri = trim($_POST['fri']) . ":00";
		$sat = trim($_POST['sat']) . ":00";
		$rotate = trim($_POST['rotate']);
		$notes = trim($_POST['notes']);
		$stmt = $dbh->prepare("insert into bid_line (bid_group_id,sun,mon,tue,wed,thur,fri,sat,line,notes,rotate)
		values (:bid_group_id, :sun, :mon, :tue, :wed, :thur, :fri, :sat, :linenumber, :notes, :rotate)");
		$origlinenumber = $linenumber;
	for($i =0;$i<$howmany;$i++){
		$linenumber = $origlinenumber + $i;
		
		$stmt->bindParam(":bid_group_id",$bid_group_id);
		$stmt->bindParam(":sun",$sun);
		$stmt->bindParam(":mon",$mon);
		$stmt->bindParam(":tue",$tue);
		$stmt->bindParam(":wed",$wed);
		$stmt->bindParam(":thur",$thur);
		$stmt->bindParam(":fri",$fri);
		$stmt->bindParam(":sat",$sat);
		$stmt->bindParam(":linenumber",$linenumber);
		$stmt->bindParam(":notes",$notes);
		$stmt->bindParam(":rotate",$rotate);
		$stmt->execute();
		$bid_line_id = $dbh->lastInsertId();
		$biddata = $dbh->prepare("select * from bid_group where id = :bid_group_id");
		$biddata->bindParam(":bid_group_id",$bid_group_id);
		$biddata->execute();
		$biddata = $biddata->fetch();
		$startdate = DateTime::createFromFormat("Y-m-d",$biddata['start_date'],new DateTimeZone("America/New_York")); //171216
		$enddate = DateTime::createFromFormat("Y-m-d",$biddata['end_date'],new DateTimeZone("America/New_York")); //171216
		$dateinterval1day =new DateInterval("P1D");
		$enddate->add($dateinterval1day);  //add 1 day to end date so dateperiod will work right. it cuts off the last day
		$dateperiod = new DatePeriod($startdate,$dateinterval1day,$enddate);
		//id	bid_group_id	bid_user_id	bid_line_id	date	start_time	shift_length	RDO	mid	note	holiday
		$stmt1 = $dbh->prepare("insert into bid_schedule (bid_group_id,bid_line_id,date,start_time,shift_length,RDO,mid,holiday)
		values (:bid_group_id,:bid_line_id,:date,:start_time,:shift_length,:RDO,:mid,:holiday)");
		$weekschedule = array($sun,$mon,$tue,$wed,$thur,$fri,$sat);
		$holidayonrdo = "";
		$holidaynotonrdo = "";
		foreach($dateperiod as $date){
			$daynum = $date->format("w");
			$dateformat = $date->format("Y-m-d");
			$firsttwoofshifttime = substr($weekschedule[$daynum],0,2);
			// echo "first two of shift time " . $firsttwoofshifttime . " full one is " . $weekschedule[$daynum];
			if($firsttwoofshifttime != "00") {
				$isRDO = 0;
				$shift_length = 8;
			}
			else {
				$isRDO = 1;
				$shift_length = 0;
			}
			$holiday = isHoliday($date);
			if($firsttwoofshifttime >21) $ismid = 1;
			else $ismid =0;
			
			$stmt1->bindParam(":bid_group_id",$bid_group_id);
			$stmt1->bindParam(":bid_line_id",$bid_line_id);
			$stmt1->bindParam(":date",$dateformat);
			$stmt1->bindParam(":start_time",$weekschedule[$daynum]);
			$stmt1->bindParam(":shift_length",$shift_length);
			$stmt1->bindParam(":RDO",$isRDO);
			$stmt1->bindParam(":mid",$ismid);
			$stmt1->bindParam(":holiday",$holiday[0]);
			$stmt1->execute();
			if($holiday[0] ==1){
				if($isRDO == 0) $holidaynotonrdo .= " -" .$holiday[1] . "->" .substr($weekschedule[$daynum],0,5);
				else $holidayonrdo .= " -" .$holiday[1] . "->RDO";
			}
		}
		$holidaytext = $holidayonrdo . " ** " . $holidaynotonrdo;
		$stmt2 = $dbh->prepare("update bid_line set holiday_text = :holiday_text where bid_line.id = $bid_line_id");
		$stmt2->bindParam(":holiday_text",$holidaytext);
		$stmt2->execute();

	}
	header("Location: adminbidscheduleeditor.php?bid=$bid_group_id");
	die();
}

function addUser($dbh){
	$facility_id = $_POST['facility_id'];
	$bid_group_id = $_POST['bid'];
	$initials = $_POST['initials'];
	$stmt = $dbh->prepare("select id from user where initial = :initials and active = 1 and facility_id = :facility_id");
	$stmt->bindParam(":initials",$initials);
	$stmt->bindParam(":facility_id",$facility_id);
	$stmt->execute();
	if($stmt->rowCount() !=1) errorAddUser("No user with those initials in your facility",$bid_group_id);
	$theuser_id = $stmt->fetch()['id'];
	// $stmt = $dbh->prepare("insert into bid_user (bid_group_id,user_id) 
	// Select * from (select :bid_group_id,:user_id) as tmp
	// where not exists (
	// 	select user_id from bid_user where bid_group_id = :bid_group_id AND
	// 	user_id = :user_id
	// 	) LIMIT 1;");
	// $stmt->bindParam(":bid_group_id",$_POST['bid']);
	// $stmt->bindParam(":user_id",$theuser_id);
	// $stmt->execute();   //im just going to do a db call, then respond from that in php... doing a one call is not working
	// print_r($stmt->errorInfo());
	// print_r($stmt->debugDumpParams());
	$stmt = $dbh->prepare("select bid_user.id from bid_user where bid_group_id = :bid_group_id AND user_id = :user_id");
	$stmt->bindParam(":bid_group_id",$_POST['bid']);
	$stmt->bindParam(":user_id",$theuser_id);
	$stmt->execute();
	if(!$stmt) {
		header("Location: adminbidviewer.php?bid=$bid_group_id&area=$area_id");
	die();
	}
	if($stmt->rowCount() ==1){
		header("Location: adminbidviewer.php?bid=$bid_group_id&area=$area_id");
		die();
	}
	$stmt = $dbh->prepare("insert into bid_user (bid_group_id,user_id) values (:bid_group_id,:user_id)");
	$stmt->bindParam(":bid_group_id",$_POST['bid']);
	$stmt->bindParam(":user_id",$theuser_id);
	$stmt->execute();
	// echo "user id is $theuser_id<br>";
	// header("Location: adminbidviewer.php?bid=$bid_group_id&area=$area_id");
	// die();
	
}

function reOrderBid($dbh,$bid_group_id){
	$stmt = $dbh->prepare("select user.bue,user.military_time,user.id from user 
	left join bid_user on bid_user.user_id = user.id
	where bid_user.bid_group_id = :bid_group_id
	order by user.bue asc,user.military_time asc,user.tie_break asc");
}

function createScheduleBidWithTimes($dbh){ //starttime startdate endtime bid interval
	$bid_group_id = $_POST['bid'];
	$starttime = $_POST['starttime'];
	$startdate = $_POST['startdate'];
	$endtime = $_POST['endtime'];
	$interval = $_POST['interval'];
	// $bid = returnBidArray($dbh,$bid_group_id);
	$userlist = returnUserBidGroupArray($dbh,$bid_group_id);
	$bid_round = $dbh->prepare("insert into bid_round(bid_group_id,name,type) 
	values (:bid_group_id,'Schedule Bid','schedule')");
	$bid_round->bindParam(":bid_group_id",$bid_group_id);
	$bid_round->execute();
	if($bid_round->rowCount() ==1) $bid_round_id = $dbh->lastInsertId();
	else {
		echo "error";
		die();
	}
	$dt = DateTime::createFromFormat("Y/m/d Hi",($startdate . " " .$starttime),new DateTimeZone("America/New_York")); //171216
	$dtinterval = new DateInterval("PT". $interval. "M");
	$dtoneday = new DateInterval("P1D");
	$starttime_hour = substr($starttime,0,2);
	$starttime_minute = substr($starttime,2,2);
	// print_r($bid);
	foreach($userlist as $biduser){
		$bid_user_id = $biduser['bid_user_id'];
		$scheduled_time = $dt->format("Y/m/d H:i:s");
		
		$stmt = $dbh->prepare("insert into bid_tracker (bid_user_id,bid_round_id,scheduled_time,status)
		values (:bid_user_id,:bid_round_id,:scheduled_time,'NE')");
		$stmt->bindParam(":bid_user_id",$bid_user_id);
		$stmt->bindParam(":bid_round_id",$bid_round_id);
		$stmt->bindParam(":scheduled_time",$scheduled_time);
		$dt->add($dtinterval);
		if($dt->format("Hm") > $endtime){
			$dt->add($dtoneday);
			$dt->setTime($starttime_hour,$starttime_minute);
		}
		$stmt->execute();		
	}
	
}

function createScheduleBidWithoutTimes(){
	
}

function createLeaveBidWithTimes(){
	
}

function createLeaveBidWithoutTimes($dbh){
	$bid_group_id = $_POST['bid'];
	$nextround = $dbh->prepare("select max(number) as number,id from bid_round where bid_group_id = :bid_group_id");
	$nextround->bindParam(":bid_group_id",$bid_group_id);
	$nextround->execute();
	if($nextround->rowCount() == 1) {
		$nextround = $nextround->fetch();
		$nextround = $nextround['number'] +1;
		
	}
	else $nextround = 1;
	
	$userlist = returnUserBidGroupArray($dbh,$bid_group_id);
	$bid_round = $dbh->prepare("insert into bid_round(bid_group_id,name,type,number,use_timed_bids) 
	values (:bid_group_id,'Round $nextround','prime',$nextround,0)");
	$bid_round->bindParam(":bid_group_id",$bid_group_id);
	$bid_round->execute();
	$newbid_round_id = $dbh->lastInsertId();
	$bid_users = returnUserBidGroupArray($dbh,$bid_group_id);
	// $dbh->beginTransaction();
	$stmtuser = $dbh->prepare("insert into bid_tracker (bid_group_id,bid_user_id,bidorder,status,bid_round_id)
		VALUES (:bid_group_id,:bid_user_id,:bidorder,:status,:bid_round_id)");
	$counter = 1;
	$status = "NE";
	$stmtuser->bindParam(":bid_group_id",$bid_group_id);
	$stmtuser->bindParam(":status",$status);
	$stmtuser->bindParam(":bid_round_id",$newbid_round_id);
	foreach($bid_users as $user){
		$stmtuser->bindParam(":bid_user_id",$user['bid_user_id']);
		$stmtuser->bindParam(":bidorder",$counter);
		$stmtuser->execute();
		print_r($stmtuser->errorInfo());
		$counter++;
	}
	// $dbh->commit();
}


function returnBidArray($dbh,$bid_group_id){
	$bid = $dbh->prepare("select bid_group.*,facility.name as facility_name,area.name as area_name,
		facility.id as facility_id from bid_group
	left join area on bid_group.area_id = area.id
	left join facility on area.facility_id = facility.id 
	where bid_group.id = :bid_group_id");
	$bid->bindParam(":bid_group_id", $bid_group_id);
	$bid->execute();
	return $bid->fetchAll();
}



function returnUserBidGroupArray($dbh,$bid_group_id){
		
$userlist = $dbh->prepare("select user.lastname,user.firstname,user.initial,user.BUE,user.SCD,
		user.military_time,user.id,bid_user.id as bid_user_id from user 
	left join bid_user on bid_user.user_id = user.id
	where bid_user.bid_group_id = :bid_group_id
	order by user.bue asc,user.military_time asc,user.tie_break asc");	
$userlist->bindParam(":bid_group_id",$bid_group_id);
$userlist->execute();
return $userlist->fetchAll();
}

function isHoliday($date){
	//date is the object
	$dateformat = $date->format("Y-m-d");
	$monthnum = $date->format("m");
	$yearnum = $date->format("Y");
	$daynum = $date->format("d");
	$holiday = "";
	switch($monthnum){
		case "1":
			//look for Jan 1  New years
			if($date->format("Y-m-d") == ($yearnum . "-01-01")) {$holiday = "New Years";
				break;
			}
			//look for thrid monday - MLK day
			$test = new DateTime("third monday of jan $yearnum",new DateTimeZone("America/New_York")); //171216
			if($date->format("Y-m-d") == $test->format("Y-m-d")) $holiday = "MLK Birthday";
			break;
		case "2":
			//third monday in feb - washingtons birthday day
			$test = new DateTime("third monday of feb $yearnum",new DateTimeZone("America/New_York")); //171216
			if($date->format("Y-m-d") == $test->format("Y-m-d")) $holiday = "George Washington's Birthday";
			break;
		// case "3":
		// 	break;
		// case "4":
		// 	break;
		case "5":
			//memorial day last monday in may
			$test = new DateTime("last monday of may $yearnum",new DateTimeZone("America/New_York")); //171216
			if($date->format("Y-m-d") == $test->format("Y-m-d")) $holiday = "Memorial Day";
			break;
		// case "6":
		// 	break;
		case "7":
			//jul 4th
			if($date->format("Y-m-d") == ($yearnum . "-07-04")) $holiday = "4th of July";
			break;
		// case "8":
		// 	break;
		case "9":
			//labor day first monday
			$test = new DateTime("first monday of september $yearnum",new DateTimeZone("America/New_York")); //171216
			if($date->format("Y-m-d") == $test->format("Y-m-d")) $holiday = "Labor Day";
			break;
		case "10":
			//columbus day, 2nd monday
			$test = new DateTime("second monday of oct $yearnum",new DateTimeZone("America/New_York")); //171216
			if($date->format("Y-m-d") == $test->format("Y-m-d")) $holiday = "Columbus Day";
			break;
		case "11":
			//ventreans day 11th
			if($date->format("Y-m-d") == ($yearnum . "-11-11")) $holiday = "Veterans Day";
			//thanksgiving 4th thursday
			$test = new DateTime("fourth thursday of nov $yearnum",new DateTimeZone("America/New_York")); //171216
			if($date->format("Y-m-d") == $test->format("Y-m-d")) $holiday = "Thanksgiving";
			break;
		case "12":
			//christmas  25th
			if($date->format("Y-m-d") == ($yearnum . "-12-25")) $holiday = "Christmas";
			break;
		default:
	}
			
			if($holiday == "") return array(0);
			return array(1,$holiday);
	
	//returns true or false
}


//error and complete section

function errorAddUser($msg,$bid_group_id){
	$_SESSION['message'] .= $msg;

	header("Location: adminbidviewer.php?bid=$bid_group_id&area=$area_id");
	die();
}

function errorBid($msg,$name,$start_date,$end_date){
	$_SESSION['message'] .= $msg;
	$area_id = $_POST['area_id'];
	header("Location: adminbidsubmit.php?area=$area_id&name=$name&start_date=$start_date&end_date=$end_date");
	die();
}



?>