<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../bidderexpress-dbaccess.php");

if(!isset($_SESSION['bidderexpress_user_id']) ){
	header("Location: login.php");
	die();
}

include("./header.php");

$facility = $dbh->query("select * from facility");

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
			<?php echoHeader(); ?>
			<hr>
			<div class='w3-panel'>
				<?php foreach($facility as $fac){
					$facname = $fac['name'];
					$facid = $fac['id'];
					echo "<a href='adminfacility.php?facility=$facid'>$facname</a><br>";
				}
				?>
			</div>
			
			
			
			<div class='w3-panel w3-border'>
				New Facility
				<form method="POST" action='./adminsubmit.php'>
					<input type='hidden' name='action' value='new_facility'>
					<input type='text' name='facility'>
					<input type='submit' value='Submit'>
				</form>
			</div>
			
			
			
			
		</div>
	</body>

</html>
	
	
	