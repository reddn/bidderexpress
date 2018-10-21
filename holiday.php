<?php


error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include("../bidderexpress-dbaccess.php");



$sun =	"14:00";
$mon="14:00";
$tue="14:00";
$wed="09:30";
$thur="06:20";
$fri="00:00";
$sat ="00:00";

$startdate = "2017-01-01";
$enddate = "2018-01-01";
$year = 2017;

$startdate = DateTime::createFromFormat("Y-m-d",$startdate);
$enddate = DateTime::createFromFormat("Y-m-d",$enddate);


$dateinterval = new DateInterval("P1D");
$dateperiod = new DatePeriod($startdate,$dateinterval,$enddate);

$currentdaynum = $startdate->format("w");

$weekschedule = array($sun,$mon,$tue,$wed,$thur,$fri,$sat);

foreach($dateperiod as $date){
	$dateformat = $date->format("Y-m-d");
	$monthnum = $date->format("m");
	$yearnum = $date->format("Y");
	$daynum = $date->format("d");
	$holiday = "";
	switch($monthnum){
		case "1":
			//look for Jan 1  New years
			if($date->format("Y-m-d") == ($yearnum . "-01-01")) $holiday = "New Years ";
			//look for thrid monday - MLK day
			$test = new DateTime("third monday of jan $yearnum");
			if($date->format("Y-m-d") == $test->format("Y-m-d")) $holiday = "MLK Day";
			break;
		case "2":
			//third monday in feb - washingtons birthday day
			$test = new DateTime("third monday of feb $yearnum");
			if($date->format("Y-m-d") == $test->format("Y-m-d")) $holiday = "George Washingtons Birthday ";
			break;
		// case "3":
		// 	break;
		// case "4":
		// 	break;
		case "5":
			//memorial day last monday in may
			$test = new DateTime("last monday of may $yearnum");
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
			$test = new DateTime("first monday of september $yearnum");
			if($date->format("Y-m-d") == $test->format("Y-m-d")) $holiday = "Labor Day ";
			break;
		case "10":
			//columbus day, 2nd monday
			$test = new DateTime("second monday of oct $yearnum");
			if($date->format("Y-m-d") == $test->format("Y-m-d")) $holiday = "Columbus Day ";
			break;
		case "11":
			//ventreans day 11th
			if($date->format("Y-m-d") == ($yearnum . "-11-11")) $holiday = "Vetreans Day ";
			//thanksgiving 4th thursday
			$test = new DateTime("fourth thursday of nov $yearnum");
			if($date->format("Y-m-d") == $test->format("Y-m-d")) $holiday = "Thanksgiving ";
			break;
		case "12":
			//christmas  25th
			if($date->format("Y-m-d") == ($yearnum . "-12-25")) $holiday = "Christmas ";
			break;
		default:
	}
	
	if($holiday != "") $holtest = true;
	else $holtest = false;
	if($holtest) echo "Date: $dateformat is $holiday " . $date->format("L");
	if($holtest && $weekschedule[$currentdaynum] == "00:00") echo  " RDO";
	if($holtest) echo "<br>";
	
	if($currentdaynum == 6) $currentdaynum =0;
	else $currentdaynum++;
}



?>