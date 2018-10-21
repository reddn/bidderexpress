<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require('inlieuofday.php');

include("../bidderexpress-dbaccess.php");


$sql = "select date,bid_line_id from bid_schedule where holiday = 1 and RDO = 1 and bid_line_id = 71";
$stmt = $dbh->query($sql)->fetch();

$date = $stmt['date'];
$bid_line_id = $stmt['bid_line_id'];


echo "date is $date";


$dayobj = DateTimeImmutable::createFromFormat("Y-m-d", $date);
$dayweeknum = $dayobj->format("w");

$onedayinterval = new DateInterval("P1D");

$sundayofweek = $dayobj->sub(new DateInterval("P$dayweeknum" . "D"));

$weekdate = $sundayofweek->format("Y-m-d");
$sun = $dbh->query("select RDO from bid_schedule where bid_line_id = $bid_line_id and date = '$weekdate'")->fetch()['RDO'];
$weekdate = $sundayofweek->add(new DateInterval("P1D"))->format("Y-m-d");
$mon = $dbh->query("select RDO from bid_schedule where bid_line_id = $bid_line_id and date = '$weekdate'")->fetch()['RDO'];
$weekdate = $sundayofweek->add(new DateInterval("P2D"))->format("Y-m-d");
$tue = $dbh->query("select RDO from bid_schedule where bid_line_id = $bid_line_id and date = '$weekdate'")->fetch()['RDO'];
$weekdate = $sundayofweek->add(new DateInterval("P3D"))->format("Y-m-d");
$wed = $dbh->query("select RDO from bid_schedule where bid_line_id = $bid_line_id and date = '$weekdate'")->fetch()['RDO'];
$weekdate = $sundayofweek->add(new DateInterval("P4D"))->format("Y-m-d");
$thu = $dbh->query("select RDO from bid_schedule where bid_line_id = $bid_line_id and date = '$weekdate'")->fetch()['RDO'];
$weekdate = $sundayofweek->add(new DateInterval("P5D"))->format("Y-m-d");
$fri = $dbh->query("select RDO from bid_schedule where bid_line_id = $bid_line_id and date = '$weekdate'")->fetch()['RDO'];
$weekdate = $sundayofweek->add(new DateInterval("P6D"))->format("Y-m-d");
$sat = $dbh->query("select RDO from bid_schedule where bid_line_id = $bid_line_id and date = '$weekdate'")->fetch()['RDO'];

echo "sun $sun mon $mon tue $tue wed $wed thu $thu fri $fri sat $sat";


if($sat ==1 && $sun==1) $rdos = "sat/sun";
if($sun ==1 && $mon ==1) $rdos = "sun/mon";
if($mon ==1 && $tue ==1) $rdos = "mon/tue";
if($tue ==1 && $wed ==1) $rdos = "tue/wed";
if($wed ==1 && $thu ==1) $rdos = "wed/thu";
if($thu ==1 && $fri ==1) $rdos = "thu/fri";
if($fri ==1 && $sat ==1) $rdos = "fri/sat";





echo getInLieuOfDay($date,$rdos);

?>