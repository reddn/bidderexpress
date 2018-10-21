<?php


function addRoundsToUserlist(){
	global $dbh;
	global $bidarray;
	global $userlist;
	global $round_counter;
	global $bidround;
	global $bid_group_id;
	
	$bidround = $dbh->prepare("select * from bid_round where bid_group_id = :bid_group_id order by number");
	$bidround->bindParam(":bid_group_id",$bid_group_id);
	$bidround->execute();
	$bidround= $bidround->fetchAll();
	
	foreach($bidround as $round){
	$bid_round_id = $round['id'];
	$bid_round_type = $round['type'];
	$bid_round_number = $round['number'];
	$bid_round_name = $round['name'];
	$bidarray[]= array("id" => $bid_round_id,"type" => $bid_round_type,"number" => $bid_round_number,"name" => $bid_round_name);
	
	
	$bid_trackerResult = $dbh->query("select * from bid_tracker where bid_round_id = $bid_round_id order by bid_user_id asc")->fetchAll();
	
	foreach($userlist as $key => $user){
		$bid_user_id = $user['bid_user_id'];
		// $userlist[$key]['rounds'] = array();
		foreach($bid_trackerResult as $trackerkey => $trackerresult){
			if($bid_user_id == $trackerresult['bid_user_id']){
					$newkeytext = "round" . $bid_round_number;
					$userlist[$key][$newkeytext . "status"] = $trackerresult['status'];
					$userlist[$key][$newkeytext . "scheduled_time"] = $trackerresult['scheduled_time'];
					$userlist[$key]["rounds"][$bid_round_number]= array("status" => $trackerresult['status'],
					"scheduled_time" => $trackerresult['scheduled_time'] );
					
					unset($bid_trackerResult[$trackerkey]);
					break 1;
				}
			}
			reset($userlist);
		}
	
		$round_counter++;
	}
	
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



?>