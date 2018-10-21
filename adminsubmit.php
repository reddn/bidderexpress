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
	case "new_facility":
		newFacility($dbh,$_POST);
		break;
	case "new_area":
		newArea($dbh);
		break;
	case "adduser":
		addUser($dbh);
		break;
	case "edituser":  //edituser firstname lastname login password email phone active
		editUser($dbh);
		break;
	
	
	
	
}



header("Location: admin.php");
die();




function newFacility($dbh,$thePOST){
	if($thePOST['facility'] == "") errorFault("facility empty");
	$newfacility = $thePOST['facility'];
	$stmt = $dbh->prepare("insert into facility (name) values (:name)");
	$stmt->bindParam(":name",$thePOST['facility']);
	$stmt->execute();
	try{
		$rowcount = $stmt->rowCount();
	} catch(Exception $e){
		$rowcount = 0;
	}
	if($rowcount !=1 ){
		errorFault("New Facilty did not insert");
	}
}

function newArea($dbh){
	if($_POST['area'] == "") errorFault("area empty");
	$newfacility = $_POST['area'];
	$stmt = $dbh->prepare("insert into area (facility_id,name) values (:facility_id,:name)");
	$stmt->bindParam(":name",$_POST['area']);
	$stmt->bindParam(":facility_id",$_POST['facility_id']);
	$stmt->execute();
	try{
		$rowcount = $stmt->rowCount();
	} catch(Exception $e){
		$rowcount = 0;
	}
	if($rowcount !=1 ){
		errorFault("New Facilty did not insert");
	}
}

function addUser($dbh){
	$firstname = $_POST['firstname'];
	$lastname = $_POST['lastname'];
	$login = $_POST['login'];
	$password = $_POST['password'];
	$email = $_POST['email'];
	$phone = $_POST['phone'];
	if($lastname == "" && $login == "")errorAddUser("Lastname and login can not be blank",$firstname,$lastname,$login,$email,$phone);
	if($login == ""){
		if(strlen($firstname) >0 ) $login .= substr($firstname,0,1);
		if(strlen($lastname) >0 ) $login .= $lastname;
	}
	for($i=0;$i<100;$i++){
		if($i != 0){
			$forlogin = $login . $i;
		} else $forlogin = $login;
		
		if(!checkIfLoginExists($dbh,$forlogin)){
			$login = $forlogin;
			break;	
		} 
	}
	
	if($password == "") $password = generatePassword();
	$login = strtolower($login);
	$stmt = $dbh->prepare("insert into user (firstname,lastname,login,password,email,phonenumber) 
	values (:firstname,:lastname,:login,:password,:email,:phone)");
	$stmt->bindParam(":firstname",$firstname);
	$stmt->bindParam(":lastname",$lastname);
	$stmt->bindParam(":login",$login);
	$stmt->bindParam(":password",$password);
	$stmt->bindParam(":email",$email);
	$stmt->bindParam(":phone",$phone);
	$stmt->execute();
	if(!$stmt) errorAddUser("New User did not insert. stmt flase",$firstname,$lastname,$login,$email,$phone);
	if($stmt->rowCount !=1) errorAddUser("New User did not insert. rowcount not 1",$firstname,$lastname,$login,$email,$phone);
	$_SESSION['message'] .= "User $login added";
}

function editUser($dbh){
	if(!($_POST['user_id'] >0)) errorPage("no user id set");
	if(!($_POST['column'] == "firstname" || $_POST['column'] == "lastname" || $_POST['column'] == "login" || $_POST['column'] == "password"
	|| $_POST['column'] == "email" || $_POST['column'] == "phonenumber"|| $_POST['column'] == "active"|| $_POST['column'] == "SCD"|| $_POST['column'] == "BUE")) errorEditUser("Somethings wrong, try again");
	$column = $_POST['column'];
	if($column == "login"){
		if($_POST['login'] == "") errorEditUser("Login can not be blank");
	}
	$stmt = $dbh->prepare("update user set $column = :value where id = :user_id");
	// $stmt->bindParam(":column",$_POST['column']);
	$stmt->bindParam(":value",$_POST[$_POST['column']]);
	$stmt->bindParam(":user_id",$_POST['user_id']);
	$stmt->execute();
	
	print_r($stmt->errorInfo());
	
	print_r($_POST);
	if(!$stmt) errorEditUser("DB entry false");
	if($stmt->rowCount() != 1) errorEditUser("Nothing was changed");
	$_SESSION['message'] .= "User info Edited";
	$userid = $_POST['user_id'];
	header("Location: adminuserinfo.php?user=$userid");
	die();
	
}























function errorFault($msg){
	// header(406);
	http_response_code(406);
	header("Comment: $msg");
	die();
}
function errorAddUser($msg,$firstname,$lastname,$login,$email,$phone){
	$_SESSION['message'] .= "ERROR: User $login **NOT** added $msg";
	header("Location: adminuser.php?showadduserget=1&firstname=$firstname&lastname=$lastname&login=$login&email=$email&phone=$phone");
	die();
}
function errorEditUser($msg){
	$_SESSION['message'] .= "ERROR: User info **NOT** edited.  $msg";
	header("Location: adminuserinfo.php?user=" . $_POST['user_id']);
	die();
}


function checkIfLoginExists($dbh,$login){
	$stmt = $dbh->prepare("select id from user where login = :login");
	$stmt->bindParam(":login",$login);
	$stmt->execute();
	if($stmt == false) return true;
	if($stmt->rowCount() > 0) return true;
	return false;
}

function generatePassword(){
	$lowerLetters = range('a', 'z');
	$upperLetters = range('A','Z');
	$numbers = range(0,9);
	$fullarray = array_merge($lowerLetters,$upperLetters,$numbers);
	$countofarray = count($fullarray);
	$password = "";
	for($i=0;$i<7;$i++){
		$password .= $fullarray[rand(0,$countofarray)];
	}
	return $password;
}




?>
