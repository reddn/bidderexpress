<?php

function isUserUptoBid($dbh){
	$userid = $_SESSION['bidderexpress_user_id'];
	$stmt = $dbh->query("select bid_group.id as id,bid_group.id as bid_group_id,
			bid_round.type,bid_round.id as bid_round_id, 
			bid_line.id as bid_line_id, bid_user.id as bid_user_id,
			bid_round.number as number
		from bid_user 
	left join bid_group on bid_group.id = bid_user.bid_group_id
	left join bid_tracker on bid_tracker.bid_user_id = bid_user.id
	left join user on user.id = bid_user.user_id 
	left join bid_round on bid_round.id = bid_tracker.bid_round_id 
	left join bid_line on bid_line.bid_group_id = bid_group.id AND bid_user.id = bid_line.bid_user_id 
	where (bid_tracker.status = 'UTB' OR bid_tracker.status = 'P-E') AND
		bid_group.active = 1 AND user.id = $userid 
	");
	return $stmt->fetchAll();
}



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




?>