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


if(isset($_POST['changeslots'])){
	if(!is_numeric($_POST['changeslots'])) errorred("not a number (NaN)");
	$stmt = $dbh->prepare("update bid_slot set slots = :slots where id = :id");
	$stmt->bindParam(":slots",$_POST['changeslots']);
	$stmt->bindParam(":id",$_POST['slotid']);
	$stmt->execute();
	// header("Location: adminbidslotedit.php?slot=" . $_POST['slotid']);
	header("Location: adminbidslots.php?bid=" . $_POST['bid_group_id']);
	die();
}
$slotid = $_GET['slot'];

function errorred($msg){
	$_SESSION['message'] .= " $msg ";
}


if($_GET['slot'] == "") {
	header("Location: index.php");
	die();
}

$data = $dbh->prepare("select facility.name as facility_name, area.name as area_name, bid_group.name as bid_group_name,
bid_slot.date,bid_slot.slots,bid_slot.isholiday,bid_group.id as bid_group_id from bid_slot
left join bid_group on bid_slot.bid_group_id = bid_group.id 
left join area on bid_group.area_id = area.id
left join facility on area.facility_id = facility.id
where bid_slot.id = :bid_slot_id");
$data->bindParam(":bid_slot_id",$slotid);
$data->execute();
$data = $data->fetch();


$facility_name = $data['facility_name'];
$area_name  = $data['area_name'];
$bid_group_name  = $data['bid_group_name'];
$date  = $data['date'];
$slots  = $data['slots'];
$isholiday  = $data['isholiday'];
$bid_group_id = $data['bid_group_id'];
// echo $bid_group_id;

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
			<?php
			echo "$facility_name - $area_name<br>
			$bid_group_name<br>
			<a href='adminbidslots.php?bid=$bid_group_id'>Back to Bid Slots Admin</a>";
			?>
			
			<div class='w3-panel w3-border'>
				Date: <h4><?php echo $date;?></h4>
				Current slots: <h4><?php echo $slots; ?></h4>
			</div>
			<div class='w3-panel w3-border'>
				<form action="./adminbidslotedit.php" method="POST">
					Change to: <h4><input type='number' name='changeslots' value='<?php echo $slots;?>' autofocus> </h4>	
					<input type='submit' value='Change'>
					<input type='hidden' name='slotid' value='<?php echo $slotid;?>' >
					<input type='hidden' name='bid_group_id' value='<?php echo $bid_group_id;?>'>
				</form>
				
			</div>

			
		</div>
	</body>

</html>
	
	
	