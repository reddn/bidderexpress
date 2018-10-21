<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../bidderexpress-dbaccess.php");



if(!($_GET['area'] >0)) {
	header("Location: admin.php");
	die();

}
if(!isset($_SESSION['bidderexpress_user_id']) ){
	header("Location: login.php");
	die();
}

include("./header.php");



$area = $dbh->prepare("select area.id,area.name,facility.name as facilityname, facility.id as facility_id from area 
	left join facility on facility.id=area.facility_id where area.id = :areaid");
$area->bindParam(":areaid", $_GET['area']);
$area->execute();
$area = $area->fetch();
$areaid= $area['id'];
$areaname = $area['name'];
$facilityname=$area['facilityname'];
$facility_id = $area['facility_id'];
// $facility_id = $area['facility_id'];

$area = $dbh->prepare("select * from area where facility_id = :facility");
$area->bindParam(":facility", $_GET['facility']);
$area->execute();

$bidsactive = $dbh->query("select * from bid_group where area_id = $areaid and active = 1");
$bidsclosed = $dbh->query("select * from bid_group where area_id = $areaid and active = 0");
if(isset($_GET['name'])){
	$getname = $_GET['name'];
	$getstart_date = $_GET['start_date'];
	$getend_date = $_GET['end_date'];
} else {
	$getname = "";
	$getstart_date = "";
	$getend_date = "";
}
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
			if($_SESSION['message'] != "") {
				echo "<br>" . $_SESSION['message'];
				$_SESSION['message'] = "";
			}  
			?>
			
			<hr>
			<h3>Area Admin</h3>
			<h4><?php echo "<a href='./adminfacility.php?facility=$facility_id'>$facilityname</a> -- $areaname"; ?></h4>
			<div class='w3-panel w3-border'>
				<h4>Open bids</h4>
				<?php
				foreach($bidsactive as $bid){
					$area_id = $bid['area_id'];
					$bid_id= $bid['id'];
					$name = $bid['name'];
					$start_date = $bid['start_date'];
					$end_date = $bid['end_date'];
					echo "<a href='adminbidviewer.php?bid=$bid_id&area=$area_id'>$name ($start_date - $end_date)</a><br>";
					
				}
				
				?>
				
			</div>
			
			<div class='w3-panel w3-border'>
				<h4>Create a bid</h4>
				<form method='POST' action='adminareasubmit.php'>
					Name: <input type='text' name='name' value='<?php echo $getname;?>'><br>
					Start Date: <input type='text' name='start_date' value='<?php echo $getstart_date;?>'><br>
					End Date: <input type='text' name='end_date' value='<?php echo $getend_date;?>'><br>
					<input type='hidden' name='area_id' value='<?php echo $areaid;?>'>
					<input type='submit' value='Create'>
					<input type='hidden' name='action' value='addbid'>
				</form>
				
			</div>
			
			
		</div>
	</body>
</html>