<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include("../bidderexpress-dbaccess.php");



$bid_group_id = 4;

// $users = $dbh->query("select * from user")->fetchAll();
$bid_group = $dbh->query("select bid_group.start_date,bid_group.end_date from bid_group
where bid_group.id = $bid_group_id")->fetch();

$users = $dbh->query("select user.firstname,user.lastname,user.id as user_id,user.SCD,bid_user.id as bid_user_id
from bid_group
left join bid_user on bid_user.bid_group_id = bid_group.id 
left join user on bid_user.user_id = user.id 
where bid_group.id = $bid_group_id")->fetchAll();



getHoursOfAL($dbh);

function getHoursOfAL($dbh){
	global $users;	
	global $dbh;
	global $bid_group;
	$enddate = DateTime::createFromFormat("Y-m-d",$bid_group['start_date']);
	$startdate = DateTime::createFromFormat("Y-m-d",$bid_group['end_date']);
	
	
	
	
	$ppstartref = DateTime::createFromFormat("Y-m-d","2017-11-26");
	$daystostartdate = $startdate->diff($ppstartref)->days;
	$daystostartdate14 = (int)$daystostartdate / 14;
	$daystostartdatemod =$daystostartdate % 14;
	$firstpayperiodstart = $ppstartref->add(new DateInterval("P" . ($daystostartdate - $daystostartdatemod) . "D"));
	$now = new DateTime();
	$firstpayperiodstart = DateTimeImmutable::createFromMutable($firstpayperiodstart);
	$firstpayperiodenddate = $firstpayperiodstart->add(new DateInterval("P13D"));
	echo "days firstpayperioddate diff enddate in days " . $firstpayperiodenddate->diff($enddate)->days . "<br>";
	$totalpp = (int)($firstpayperiodenddate->diff($enddate)->days / 14);
	$lastppenddate = $firstpayperiodenddate->add(new DateInterval("P" . ($totalpp *14) . "D"));
	
	echo "Start Date " .$startdate->format("Y-m-d") . "<br>";
	echo "End Date " .$enddate->format("Y-m-d") . "<br>";
	echo "total pp : $totalpp<br>";
	foreach($users as $key => $user){
		$totalal =0;
		$mytext = "";
		$firstname = $user['firstname'];
		$lastname = $user['lastname'];
		$usedfirstpp = $firstpayperiodstart;
		$SCDdate = $user['SCD'];

		if($totalpp == 0){
			$users[$key]['hoursofal'] = 0;
			continue;
		}
		if($SCDdate != ""){
			$scd = DateTimeImmutable::createFromFormat("Y-m-d",$SCDdate);
		} else{
			$scd = new DateTimeImmutable();
		}
		$timeinagencyatfirstpp = $scd->diff($firstpayperiodstart)->format('%y');
		
		// $totalal= returnAmountOfAL($timeinagencyatfirstpp);
		echo "scd date " . $scd->format("Y-m-d");
		for($i = 0;$i < $totalpp; $i++ ){
			$amounttoadd = "P" . ($i *14). "D";
			$usedfirstpp = $firstpayperiodstart->add(new DateInterval($amounttoadd));
			$years = $scd->diff($usedfirstpp)->format('%y');
			
			$result = returnAmountOfAL($years);
			$totalal = $totalal + $result;
			$mytext .= "PPstartDate= " . $usedfirstpp->format("Y-m-d") . " with Years:$years gives $result hours TOTALAL$totalal<br>" ;
			
			
		}	
		echo "$mytext totalpp is $totalpp $firstname  $lastname Total AL: $totalal<br>";
		echo "firstpayperiodenddate is " . $firstpayperiodenddate->format("Y-m-d") . "<br>";

		$users[$key]['hoursofal'] = $totalal;
		$dbh->query("update bid_user set total_al_accrued = $totalal where id = " . $user['bid_user_id']);
		echo "<br>";
	}
	
}

function returnAmountOfAL($yrs){

	if($yrs >14){
		return 8;
	}elseif($yrs>5){
		return 6;
	} else {
		return 4;
	}
	
}
?>