<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include("../bidderexpress-dbaccess.php");

if(isset($_POST['user'])){
	$stmt = $dbh->prepare("select id from user where login=:user and password = :pass and active = 1");
	$stmt->bindParam(":pass",$_POST['pass']);
	$stmt->bindParam(":user",$_POST['user']);
	$stmt->execute();
	try{
		$rowcount = $stmt->rowCount();
	} catch(Exception $e){
		$rowcount = 0;
	}
	if($rowcount == 1){
		$_SESSION['bidderexpress_user_id'] =  $stmt->fetch()['id'];
		
	}
	
}

if(isset($_SESSION['bidderexpress_user_id']) ){
	if(!isset($_SESSION['message'])) $_SESSION['message'] = "";
	header("Location: index.php");
	die();
}


?>


<!DOCTYPE html>
<html>
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1">

		<link rel="stylesheet" href="./w3.css">
	
		<title>Bidder Express - Login</title>
		
		
	</head>
	<body>
	<div class='w3-container'>
		Login<br>
		<form method='POST' action='login.php'>
			<input type='text' name='user' autofocus><br>
			<input type='password' name='pass'><br>
			<input type='submit' name='submit' value='Login'>
		</form>
	</div>
	</body>

</html>
	
	
	