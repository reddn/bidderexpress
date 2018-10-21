<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
// print_r($_POST);
include("../bidderexpress-dbaccess.php");

if(!isset($_SESSION['bidderexpress_user_id']) ){
	header("Location: login.php");
	die();
}

include('./functions.php');

$isuseruptobid = isUserUptoBid($dbh);
// print_r($_POST);
if(isset($_POST['action'])){
	$user_id = $_SESSION['bidderexpress_user_id'];
	$bid_line_id = $_POST['bid_line_id'];
	
	$bid_group_id = $_POST['bid_group_id'];
	$bid_round_id = $_POST['bid_round_id'];
	
	 if(count($isuseruptobid) > 0){
		foreach($isuseruptobid as $a){
			$thisbid_group_id = $a['bid_group_id'];
			$thisbidtype = $a['type'];
			$thisbid_round_id = $a['bid_round_id'];
			$bid_user_id = $a['bid_user_id'];
			if($thisbid_group_id == $bid_group_id){
				switch($thisbidtype){
					case "schedule":
						if($_POST['bid_round_type'] == $thisbidtype && $_POST['action'] == "submitschedulebid") {
							//updates bid_line to show this user is now on the line
							$stmt = $dbh->prepare("update  bid_line set bid_user_id = :bid_user_id 
							where id = :bid_line_id and bid_user_id is null and bid_group_id = :bid_group_id");
							$stmt->bindParam(':bid_group_id',$bid_group_id);
							$stmt->bindParam(':bid_line_id',$bid_line_id);
							$stmt->bindParam(':bid_user_id',$bid_user_id);
							$stmt->execute();
							//updates bid_schedule (list of every day on a schedule) column 'bid_user_id' to show this one is selected
							$stmt1 = $dbh->prepare("update bid_schedule set bid_user_id = :bid_user_id 
								where bid_group_id = :bid_group_id AND bid_line_id = :bid_line_id ANDS bid_user_id is null");
							$stmt1->bindParam(":bid_user_id",$bid_user_id);
							$stmt1->bindParam(":bid_line_id",$bid_line_id);
							$stmt1->bindParam(":bid_group_id",$bid_group_id);
							$stmt1->execute();  //fails if the bid_user_id is not null

							//gets the user list of the bid list in order.. sets the next user up
							$stmt4 = $dbh->prepare("select bid_user.id as bid_user_id,user.lastname,
									user.firstname,user.initial,user.BUE,user.bue,user.military_time,user.id,bid_tracker.status
									from user 
				                left join bid_user on bid_user.user_id = user.id
				                left join bid_tracker on bid_tracker.bid_user_id = bid_user.id
				                where bid_tracker.bid_round_id = :bid_round_id
				                order by user.bue asc,user.military_time asc,user.tie_break asc");
                			$stmt4->bindParam(":bid_round_id",$thisbid_round_id);
                			$stmt4->execute();
                			$stmt4 = $stmt4->fetchAll();
							$setnextuserutb = false;
							foreach($stmt4 as $b){
								$status = $b['status'];
								if($setnextuserutb == true){
									if($status =="PE") break;
									$bbid_user_id = $b['bid_user_id'];
									$stmt2 = $dbh->prepare("update bid_tracker set status = 'UTB' where bid_round_id = :bid_round_id AND
										bid_user_id = :bid_user_id");
									$stmt2->bindParam(":bid_round_id",$thisbid_round_id);
									$stmt2->bindParam(":bid_user_id",$bbid_user_id);
									$stmt2->execute();
									
									break;
								}
								if($status == "UTB"){
									$time = new DateTime("now", new DateTimeZone("America/New_York"));
									$time = $time->format("Y-m-d H:i:s");
									$bbid_user_id = $b['bid_user_id'];
									$stmt2 = $dbh->prepare("update bid_tracker set status = 'PENDING-DONE', submitted_time = '$time' where bid_round_id = :bid_round_id AND
										bid_user_id = :bid_user_id");
									$stmt2->bindParam(":bid_round_id",$thisbid_round_id);
									$stmt2->bindParam(":bid_user_id",$bbid_user_id);
									$stmt2->execute();
									
									$setnextuserutb = true;
								}
							}//end foreach stmt4
						}//end if
						break;
					case "prime":
						$thisbid_line_id = $a['bid_line_id'];
						if($_POST['action'] == "submitprimeleave"){
							// print_r($_POST);//testing
							
							//get bid_schedule rows for line
							//get all bid_bid rows for line
							//check if bid week can be used on either side of rdo, or between the rdo's (bid_facility)
							//find week range of first bid day. set that range in an array
							//for each bid day subsequent bid day, check if range is in current week range array, if not, get week range and add it to that array
							//everything above is not used
							
							$bid_round_id = $_POST['bid_round_id'];
							$bid_line_id = $_POST['bid_line_id'];
							$bid_schedule = $dbh->prepare("select * from bid_schedule where bid_line_id = :bid_line_id");
							$bid_schedule->bindParam(":bid_line_id", $bid_line_id);
							$bid_schedule->execute();
							$bid_schedule = $bid_schedule->fetchAll();
							
							//bid_tracker must have P-E or UTB
							
							//check who is next up
							//previous users each should be checked, if UTB, they need to be P-E, Should only be P-E,P-NE, or DONE
							//next users should be evaulated, if the are 'DONE'/P-NE go to the next
							//if NE make them UTB
							//if last user, and if another round is made, OR if auto round creation is on AND all users have
								//passed or bidded, open next round 
							
							$bid_userlist = $dbh->prepare("select bid_user.id as bid_user_id,user.lastname,
									user.firstname,user.initial,user.BUE,user.bue,user.military_time,user.id,bid_tracker.status,
									bid_tracker.id as bid_tracker_id
									from user 
				                left join bid_user on bid_user.user_id = user.id
				                left join bid_tracker on bid_tracker.bid_user_id = bid_user.id
				                where bid_tracker.bid_round_id = :bid_round_id
				                order by user.bue asc,user.military_time asc,user.tie_break asc");
                			$bid_userlist->bindParam(":bid_round_id",$thisbid_round_id);
                			$bid_userlist->execute();
                			$bid_userlist = $bid_userlist->fetchAll();
							
							$countbid_userlist = count($bid_userlist);
							$founduser = false;
							$userkeyid = "";
							for($i=0;$i<$countbid_userlist;$i++){
								$thisbtatus = $bid_userlist[$i]['status'];
								$thisbbid_user_id = $bid_userlist[$i]['bid_user_id'];
								$thisbbid_tracker_id = $bid_userlist[$i]['bid_tracker_id'];
								
								
								if($bid_user_id == $thisbbid_user_id) {
									$founduser = true;
									// echo "found user at line " . __LINE__ . "\n";
									$userkeyid = $i;
									
									// $stmt5 = $dbh->prepare("update bid_tracker set status = 'DONE' where id = :bid_tracker_id");
									// $stmt5->bindParam(":bid_tracker_id",$thisbbid_tracker_id);
									// $stmt5->execute();
									// if($i == ($countbid_userlist -1)){ //if last user in bid
									// 	$bid_tracker_id1 = $bid_userlist[$i+1]['bid_tracker_id'];
									// 	$stmt6 = $dbh->prepare("update bid_tracker set status = 'DONE' 
									// 		where id = :bid_tracker_id and status = 'NE'");
									// 	$stmt6->bindParam(":bid_tracker_id",$bid_tracker_id1);
									// 	$stmt6->execute();
										
									// }  MOVED to the end 
									
								}
								
							}
							if($founduser == false) return; //exit error
							$count = count($bid_schedule);
							// $dates = explode(",",$_POST['mydata']);
							$allbiddaysa = array();
							foreach($_POST['mydata'] as $thedate){
								$keyofdate = "";
								foreach($bid_schedule as $key => $a){
									if($a['date'] == $thedate) {
										$keyofdate = $key;
										break;
									}
								}	
								if($keyofdate =="") return; //THROW ERROR
								reset($bid_schedule);
								$findWorkWeeka = findWorkWeek($bid_schedule,$count,$thedate,$keyofdate);
								array_push($allbiddaysa, array('date' => $thedate, 
									'key' => $keyofdate,
									'weekstartdate' => $findWorkWeeka[0][0],
									'weekstartkey' => $findWorkWeeka[0][1],
									'weekenddate' => $findWorkWeeka[1][0],
									'weekendkey' => $findWorkWeeka[1][1]
									));
								// echo "date is $thedate";
								// print_r($findWorkWeeka);
								// echo "\n\n";
							}
							// print_r($allbiddaysa);
							$uniquebiddays = array();
							foreach($allbiddaysa as $bidday){
								$weekstartkey = $bidday['weekstartkey'];
								$weekendkey = $bidday['weekendkey'];
								$uniquefound = true;
								reset($uniquebiddays);
								foreach($uniquebiddays as $uniquebidday){
									if($uniquebidday['weekstartkey'] == $weekstartkey) $uniquefound = false;
								}
								if($uniquefound ==true){
									array_push($uniquebiddays,array('weekstartkey' => $weekstartkey,'weekendkey' => $weekendkey));
								}
							}
							// echo "unique bid days\n";
							// print_r($uniquebiddays);
							$countofuniqueweeks = count($uniquebiddays);
							// echo "count of unique weeks is $countofuniqueweeks";
							if($countofuniqueweeks >2) {
								echo "\n ERROR too many weeks, try again";
								return;
							}
							reset($allbiddaysa);
							foreach($allbiddaysa as $bidday){
								$biddaykey = $bidday['key'];//bid_schedule key
								
								$stmti = $dbh->prepare("insert into bid_bid (bid_user_id,bid_round_id,bid_line_id,bid_slot_id,date,type)
								VALUES (:bid_user_id,:bid_round_id,:bid_line_id,(select id from bid_slot where date = :date AND bid_group_id = :bid_group_id),:date,'prime')");
								$stmti->bindParam(":bid_user_id",$bid_user_id);
								$stmti->bindParam(":bid_round_id",$bid_round_id);
								$stmti->bindParam(":bid_line_id",$bid_line_id);
								$stmti->bindParam(":bid_group_id",$bid_group_id);
								$stmti->bindParam(":date",$bidday['date']);
								$stmti->execute();
								$stmti  = $dbh->prepare("update bid_schedule set bid_prime_al = 1 
									where bid_user_id = :bid_user_id AND bid_line_id = :bid_line_id AND date = :date");
								$stmti->bindParam(":bid_user_id",$bid_user_id);
								$stmti->bindParam(":bid_line_id",$bid_line_id);
								$stmti->bindParam(":date",$bidday['date']);
								$stmti->execute();
								echo "\nbid day is $biddaykey\n";
								print_r($stmti->errorInfo());
								echo "\n rowcount is " . $stmti->rowCount();
							}
							//complete bid in tracker
							$stmt5 = $dbh->prepare("update bid_tracker set status = 'DONE' where id = :bid_tracker_id");
							$stmt5->bindParam(":bid_tracker_id",$bid_userlist[$userkeyid]['bid_tracker_id']);
							$stmt5->execute();
							if($i == ($countbid_userlist -1)){ //if last user in bid
								//if there is another round, >> start the next round >> close all eligible on this current bid
								$dbh->query("update bid_tracker set status = 'DONE' 
									where bid_round_id = $bid_round_id AND (status = 'P-E' OR status = 'PENDING-DONE' OR status = 'P-NE')");
								$stmt9= $dbh->prepare("select * from bid_round where bid_group_id = :bid_group_id order by number asc");
								$stmt9->bindParam(":bid_group_id",$bid_group_id);
								$stmt9->execute();
								$stmt9 = $stmt9->fetchAll();
								$foundthisbidgroup = false;
								foreach($stmt9 as $key=>$bid_group_linea){
									
									if($foundthisbidgroup && $bid_group_linea['number'] > $bidnumber){//start next round
										$stmt10 = $dbh->prepare("select * from bid_tracker where bid_round_id = :bid_round_id 
											order by scheduled_time asc, bidorder asc");
										$stmt10->bindParam(":bid_round_id",$bid_group_linea['id']);
										$stmt10->execute();
										$stmt10 = $stmt10->fetch();
										$dbh->query("update bid_tracker set status = 'UTB' where id = " . $stmt10['id']);
										
										
										
									}
									if($bid_group_linea['id'] == $bid_group_id){
										$bidnumber = $bid_group_linea['number'];
										$foundthisbidgroup = true;
									}
								}
							}
							
						}
						break;
				}//end switch 
			}
		}
		
	} else {
		$_SESSION['bidderexpress_message'] .= "You are not eligeble to bid.  Notify your Area rep.";
	}
}

// header("Location: displayschedule.php?bid_line_id=$bid_line_id");
// die();


function findWorkWeek($bid_schedule,$count,$date,$keyofdate){//finds the first and last work day of a week for a given date
	$forwarddatefound=false;
	$backwarddatefound = false;
	//go forward
	for($i = $keyofdate;$i <$count;$i++){
		if($bid_schedule[$i]['RDO'] == 1) {
			$forwarddatefound = true;
			$forwarddate = $bid_schedule[$i-1]['date'];
			$forwardkey = $i-1;
			break;
		}
	}
	//go backward
	for($i = $keyofdate;$i>-1;$i--){
		if($bid_schedule[$i]['RDO'] == 1) {
			$backwarddatefound = true;
			$backwarddate = $bid_schedule[$i+1]['date'];
			$backwardkey = $i+1;
			break;
		}
	}
	$returna = array();
	if($backwarddatefound) array_push($returna,array($backwarddate,$backwardkey));
	else array_push($returna,array($bid_schedule[0]['date'],0));
	
	if($forwarddatefound) array_push($returna,array($forwarddate,$forwardkey));
	else array_push($returna,array($bid_schedule[$count-1]['date'],$count-1));
	
	return $returna;
}








?>