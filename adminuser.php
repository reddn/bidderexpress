<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../bidderexpress-dbaccess.php");

// if(!isset($_GET['facility'])) die();

if(!isset($_SESSION['bidderexpress_user_id']) ){
	header("Location: login.php");
	die();
}

include("./header.php");

$userarray = $dbh->query("select * from user order by lastname asc")->fetchAll();

?>


<!DOCTYPE html>
<html>
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1">

		<link rel="stylesheet" href="./w3.css">
	
		<title>Bidder Express</title>
		
		
	</head>
	<body>
		<div class='w3-container'>
			<?php echoHeader();
			echo "<br>" . $_SESSION['message'];
			unset($_SESSION['message']);
			?>
		
			
			<hr>
			<div class='w3-panel'>
				<?php foreach($userarray as $user){
					$userlogin = $user['login'];
					$userfirstname = $user['firstname'];
					$userlastname = $user['lastname'];
					$userid = $user['id'];
					
					
					echo "-<a href='./adminuserinfo.php?user=$userid'>$userlastname, $userfirstname - $userlogin</a><br>";
				}
				?>
			</div>
			
			<div class='w3-panel w3-border'>
				<h4>Add User</h4>
				<form method='POST' action='adminsubmit.php'>
					<input type='hidden' name='action' value='adduser'>
					Firstname: <input type='text' name='firstname'><br>
					Lastname: <input type='text' name='lastname'><br>
					Login: <input type='text' name='login'><br>
					Password: <input type='password' name='password'><br>
					Email: <input type='text' name='email'><br>
					Phone: <input type='text' name='phone'><br>
					<input type='submit' value='Add User'>
				</form>
				
			</div>
		
		
		</div>
	</body>

</html>
	
	
	