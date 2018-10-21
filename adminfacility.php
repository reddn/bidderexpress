<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../bidderexpress-dbaccess.php");

if(!isset($_GET['facility'])) die();

if(!isset($_SESSION['bidderexpress_user_id']) ){
	header("Location: login.php");
	die();
}

include("./header.php");



$facility = $dbh->prepare('select id,name from facility where id = :facilityid');
$facility->bindParam(":facilityid", $_GET['facility']);
$facility->execute();
$facility = $facility->fetch();
$facilityid= $facility['id'];
$facilityname = $facility['name'];


$area = $dbh->prepare("select * from area where facility_id = :facility");
$area->bindParam(":facility", $_GET['facility']);
$area->execute();



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
			Facility: <?php  
				echo $facilityname;
			?>
			
			<div class='w3-panel'>
				Areas:<br>
				<?php foreach($area as $a){
					$areaname = $a['name'];
					$areaid = $a['id'];
					echo "-<a href='./adminarea.php?area=$areaid'>$areaname</a><br>";
				
				}
				?>
				
			</div>
			
			
			
			<div class='w3-panel w3-border'>
				New Area
				<form method="POST" action='./adminsubmit.php'>
					<input type='hidden' name='action' value='new_area'>
					<input type='hidden' name='facility_id' value='<?php echo $facilityid;?>'>
					<input type='text' name='area'>
					<input type='submit' value='Submit'>
				</form>
			</div>
			
			
			
			
		</div>
	</body>

</html>
	
	