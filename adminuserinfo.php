<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../bidderexpress-dbaccess.php");

if(!isset($_GET['user'])) die();

if(!isset($_SESSION['bidderexpress_user_id']) ){
	header("Location: login.php");
	die();
}

include("./header.php");


$userinfo = $dbh->prepare("select * from user where id = :userid");
$userinfo->bindParam(":userid",$_GET['user']);
$userinfo->execute();

try{
	if($userinfo->rowCount() != 1) {
		errorDie("Unknown User ID");
	}
} catch (Exception $e){
	errorDie("Unknown User ID");
}

$userinfo = $userinfo->fetch();
$userfirstname = $userinfo['firstname'];
$userlastname = $userinfo['lastname'];
$userlogin = $userinfo['login'];
$useractive = $userinfo['active'];
$userphone = $userinfo['phonenumber'];
$useremail = $userinfo['email'];
$useractive = $userinfo['active'];
$userscd = $userinfo['SCD'];
$userbue = $userinfo['BUE'];

function errorDie($msg){
	http_response_code(406);
	header("Comment: $msg");
	echo "Error $msg";
	die();
}
?>


<!DOCTYPE html>
<html>
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1">

		<link rel="stylesheet" href="./w3.css">
	
		<title>Bidder Express</title>
		
		<style>

		</style>
	</head>
	<body>
		<div class='w3-container'>
			<?php echoHeader(); 
			if($_SESSION['message'] !=""){
				echo "<br>" . $_SESSION['message'];
				$_SESSION['message'] = "";
			}
			?>
			<hr>
			<?php
				
				echo "<h3>$userlastname, $userfirstname</h3>
				Firstname: $userfirstname<br>
				Lastname: $userlastname<br>
				Login: $userlogin<br>
				Email: $useremail<br>
				Phone: $userphone<br><br>
				BUE: $userbue<br>
				SCD: $userscd";
			?>
			<br>
			<div class='w3-panel w3-border noformdiv'>
				
					<h3>Edit User info</h3>
					<h6>Only fields entered are modified</h6>
					
					
					<form method="POST" action="adminsubmit.php">
						Firstname: <input type='text' name='firstname' placeholder='<?php echo $userfirstname; ?>'>
						<input type='submit' value='Edit'><br>
						<input type='hidden' name='edituseraction' value='edituserfirstname'>
						<input type='hidden' name='column' value='firstname'>
						<input type='hidden' name='action' value='edituser'> 
						<input type='hidden' name='user_id' value='<?php echo $_GET['user'];?>'>
					</form>
					
					<form method="POST" action="adminsubmit.php">
						Lastname: <input type='text' name='lastname' placeholder='<?php echo $userlastname; ?>'>
						<input type='submit' value='Edit'><br>
						<input type='hidden' name='edituseraction' value='edituserlastname'>
						<input type='hidden' name='column' value='lastname'>
						<input type='hidden' name='action' value='edituser'> 
						<input type='hidden' name='user_id' value='<?php echo $_GET['user'];?>'>
					</form>
					
					<form method="POST" action="adminsubmit.php">
						Login: <input type='text' name='login' placeholder='<?php echo $userlogin; ?>'>
						<input type='submit' value='Edit'>
						<br>
						<input type='hidden' name='edituseraction' value='edituserlogin'>
						<input type='hidden' name='column' value='login'>
						<input type='hidden' name='action' value='edituser'> 
						<input type='hidden' name='user_id' value='<?php echo $_GET['user'];?>'>
					</form>
					
					<form method="POST" action="adminsubmit.php">
						Password: <input type='password' name='password' placeholder=''>
						<input type='submit' value='Edit'>
						<br>
						<input type='hidden' name='column' value='password'>
						<input type='hidden' name='action' value='edituser'> 
						<input type='hidden' name='user_id' value='<?php echo $_GET['user'];?>'>
						<input type='hidden' name='edituseraction' value='edituserpassword'>
					</form>
					
					<form method="POST" action="adminsubmit.php">
						Email: <input type='text' name='email' placeholder='<?php echo $useremail; ?>'>
						<input type='submit' value='Edit'><br>
						<input type='hidden' name='edituseraction' value='edituseremail'>
						<input type='hidden' name='column' value='email'>
						<input type='hidden' name='action' value='edituser'> 
						<input type='hidden' name='user_id' value='<?php echo $_GET['user'];?>'>
					</form>
					
					<form method="POST" action="adminsubmit.php">
						Phone: <input type='text' name='phonenumber' placeholder='<?php echo $userphone;?>'>
						<input type='submit' value='Edit'>
						<br>
						<input type='hidden' name='column' value='phonenumber'>
						<input type='hidden' name='action' value='edituser'> 
						<input type='hidden' name='user_id' value='<?php echo $_GET['user'];?>'>
						<input type='hidden' name='edituseraction' value='edituserphonenumber'>
					</form>
					<form method="POST" action="adminsubmit.php">
						SCD: <input type='text' name='SCD' placeholder='<?php echo $userscd;?>'>
						<input type='submit' value='Edit'>
						<br>
						<input type='hidden' name='column' value='SCD'>
						<input type='hidden' name='action' value='edituser'> 
						<input type='hidden' name='user_id' value='<?php echo $_GET['user'];?>'>
						<input type='hidden' name='edituseraction' value='edituserscd'>
					</form>
					<form method="POST" action="adminsubmit.php">
						BUE: <input type='text' name='BUE' placeholder='<?php echo $userbue;?>'>
						<input type='submit' value='Edit'>
						<br>
						<input type='hidden' name='column' value='BUE'>
						<input type='hidden' name='action' value='edituser'> 
						<input type='hidden' name='user_id' value='<?php echo $_GET['user'];?>'>
						<input type='hidden' name='edituseraction' value='edituserbue'>
					</form>
					
					
					
			</div>
			
		</div>
	</body>

</html>
	
	
	