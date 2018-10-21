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

											$bid_group_id = $_GET['bid'];
if(!($bid_group_id >0)) errorPage("Id not valid");
$bid = $dbh->prepare("select bid_group.*,facility.name as facility_name,area.name as area_name,facility.id as facility_id from bid_group
	left join area on bid_group.area_id = area.id
	left join facility on area.facility_id = facility.id 
	where bid_group.id = :bid_group_id");
$bid->bindParam(":bid_group_id", $_GET['bid']);
$bid->execute();

try{
$bid = $bid->fetch();
	
}catch (Exception $e){
	errorPage("Try and Catch error.");
}

$area_name = $bid['area_name'];
$area_id = $bid['area_id'];
$facility_name = $bid['facility_name'];
$facility_id = $bid['facility_id'];
$bid_name = $bid['name'];
$start_date = $bid['start_date'];
$end_date = $bid['end_date'];

// if(!$bid) errorPage("Not valid");
// if($bid->rowCount() != 1) errorPage("Somethings wrong");

$userlist = $dbh->prepare("select user_id,user.lastname,user.firstname,user.BUE,user.initial
	from bid_user
	left join user on bid_user.user_id= user.id
	where bid_group_id = :bid_group_id");
$userlist->bindParam(":bid_group_id",$bid_group_id);
$userlist->execute();

try{
	$userlist = $userlist->fetchAll();
	
}catch(Exception $e){
	echo "error, userlist didnt work";
	die();
}


$schedulelist = $dbh->prepare("select * from bid_line where bid_group_id = :bid_group_id");
$schedulelist->bindParam(":bid_group_id",$bid_group_id);
$schedulelist->execute();
$schedulelist = $schedulelist->fetchAll();

$maxline = $dbh->prepare("select max(line) as max from bid_line where bid_group_id = :bid_group_id");
$maxline->bindParam(":bid_group_id",$bid_group_id);
$maxline->execute();
try{
if($maxline->rowCount()==1) $maxline = $maxline->fetch()['max'];
}catch (Exception $e){
	$maxline = 0;
}
//ERROR FUNCTIONS
function errorPage($msg){
	$_SESSION['message'] .= $msg;
	$_SESSION['message']  = "";
	header("Location: adminarea.php?area=" . $_GET['area']);
	die();
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
			<div class='w3-border'>
				<h3>Schedule Editor</h3>
				<h5><?php echo "<a href='adminfacility.php?facility=$facility_id'>$facility_name</a>
				-- <a href='adminarea.php?area=$area_id'>$area_name</a>";?></h5>
				<h4><?php echo "<a href='adminbidviewer.php?bid=$bid_group_id&area=$area_id'>$bid_name <span class='w3-small'>-- $start_date - $end_date</span></a>";?></h4>
			</div>
			<div class='w3-row'>
			<div class='w3-border w3-col l8 m12 s12'>
				<h4>Insert Schedule</h4>
				<h6 class='w3-small'> Leave RDO's blank or type in 'RDO'</h6>
				<form method="post" action="adminbidsubmit.php">
					<input type='hidden' name='action' value='addschedule'>
					<input type='hidden' name='bid' value='<?php echo $bid_group_id;?>'>
					<table class='w3-table'>
						<tr>
							<th>Line</th>
							<th>Sun</th>
							<th>Mon</th>
							<th>Tue</th>
							<th>Wed</th>
							<th>Thur</th>
							<th>Fri</th>
							<th>Sat</th>
							<th>Rotate<span class='w3-tiny'>(wks)</span></th>
							<th>Notes</th>
							<th class='w3-small'>How Many</th>
						</tr>
						<tr>
							<td><input class='w3-input' type='text' name='line' value='<?php echo $maxline+1;?>'></td>
							<td><input class='w3-input' type='text' name='sun' autofocus></td>
							<td><input class='w3-input' type='text' name='mon'></td>
							<td><input class='w3-input' type='text' name='tue'></td>
							<td><input class='w3-input' type='text' name='wed'></td>
							<td><input class='w3-input' type='text' name='thur'></td>
							<td><input class='w3-input' type='text' name='fri'></td>
							<td><input class='w3-input' type='text' name='sat'></td>
							<td><input class='w3-input' type='text' name='rotate' value='0' readonly='readonly'></td>
							<td><input class='w3-input' type='text' name='notes'></td>
							<td><input class='w3-input' type='text' name='howmany' value='1'></td>
							<td><input class='w3-input' type='submit' value='Add'></td>
						</tr>
					</table>
					
					
				</form>
				</div>
			</div>
			<div class='w3-border w3-panel'>
				<h4>Current Schedule Lines</h4>
				<table class='w3-table'>
					<tr>
						<th>Line</th>
						<th>Sun</th>
						<th>Mon</th>
						<th>Tue</th>
						<th>Wed</th>
						<th>Thur</th>
						<th>Fri</th>
						<th>Sat</th>
						<th>Rotate(wks)</th>
						<th>Notes</th>
					</tr>
					<?php
						foreach($schedulelist as $linea){
							$linenumber = $linea['line'];
							$sun = substr($linea['sun'],0,5);
							$mon = substr($linea['mon'],0,5);
							$tue = substr($linea['tue'],0,5);
							$wed = substr($linea['wed'],0,5);
							$thur =substr( $linea['thur'],0,5);
							$fri = substr($linea['fri'],0,5);
							$sat = substr($linea['sat'],0,5);
							$rotate = $linea['rotate'];
							$notes = $linea['notes'];
							
							echo "<tr>
							<td>$linenumber</td>
							<td>$sun</td>
							<td>$mon</td>
							<td>$tue</td>
							<td>$wed</td>
							<td>$thur</td>
							<td>$fri</td>
							<td>$sat</td>
							<td>$rotate</td>
							<td>$notes</td>
							<td>
							<form method='POST' action='adminbidsubmit.php'><input type='submit' value='EDIT'></form>
							</td>
							<td>
							<form method='POST' action='adminbidsubmit.php'><input type='submit' value='Delete'></form>
							</td>
							</tr>";
							
							
						}
					
					
					?>
				</table>
			
			</div>
		</div>
	</body>
</html>