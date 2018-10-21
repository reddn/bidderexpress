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
	case "addbid":
		addBid($dbh);
		break;
}




function addBid($dbh){
	$name = $_POST['name'];
	$start_date = $_POST['start_date'];
	$end_date = $_POST['end_date'];
	if($name == "" || $start_date == "" || $end_date == "") errorAddBid("Something was blank, try again",$name,$start_date,$end_date);
	$user_id = $_SESSION['bidderexpress_user_id'];
	$stmt = $dbh->prepare("insert into bid_group (name,start_date,end_date,created_by,area_id) 
	values (:name,:start_date,:end_date,$user_id,:area_id)");
	$stmt->bindParam(":name",$name);
	$stmt->bindParam(":start_date",$start_date);
	$stmt->bindParam(":end_date",$end_date);
	$stmt->bindParam(":area_id",$_POST['area_id']);
	$stmt->execute();
	
	if(!$stmt) errorAddBid("Bid was not created, stmt was false",$name,$start_date,$end_date);
	if($stmt->rowCount() !=1 ) errorAddBid("Bid was not created, rowcount was not 1",$name,$start_date,$end_date);
	header("Location: adminarea.php?area=" . $_POST['area_id']);
	die();
}



//error section
function errorAddBid($msg,$name,$start_date,$end_date){
	$_SESSION['message'] .= $msg;
	$area_id = $_POST['area_id'];
	header("Location: adminarea.php?area=$area_id&name=$name&start_date=$start_date&end_date=$end_date");
	die();
}
?>