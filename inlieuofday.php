<?php

function getInLieuOfDay($day,$rdopair){
	
	$dayobj = DateTime::createFromFormat("Y-m-d",$day,new DateTimeZone("America/New_York"));
	
	$matrix = array("five" => array(),
"four" => array());
$matrix["five"]["sat/sun"] = array("sat" => "preceding friday","sun"=> "following monday");
$matrix["five"]["sun/mon"] = array("sun" => "following tuesday","mon"=> "preceding saturday");
$matrix["five"]["mon/tue"] = array("mon" => "following wednesday","tue"=> "preceding sunday");
$matrix["five"]["tue/wed"] = array("tue" => "following thursday","wed"=> "preceding monday");
$matrix["five"]["wed/thu"] = array("wed" => "following friday","thu"=> "preceding tuesday");
$matrix["five"]["thu/fri"] = array("thu" => "following saturday","fri"=> "preceding wednesday");
$matrix["five"]["fri/sat"] = array("fri" => "following sunday","sat"=> "preceding thursday");

$dayweeknum = $dayobj->format("w");

$dayabbrev = strtolower($dayobj->format("D"));
// echo "dayabbrev $dayabbrev";
$inlieu = $matrix["five"][$rdopair][$dayabbrev];
 //echo "Your inlieu of day is " . $inlieu;
 $inlieua = explode(" ",$inlieu);

	if($inlieua[0] == "preceding")$inlieua[0] = "last";
	if($inlieua[0] == "following")$inlieua[0] = "next";
	$whichway = $inlieua[0];
	$lieuday = $inlieua[1];
	$newday = new DateTime("$whichway $lieuday $day");
	// echo "the inlieuday of $day is " . $newday->format("Y-m-d");
	return  $newday->format("Y-m-d");
 }

// getInlieuOfDay("2017-01-16","mon/tue");




// echo "<pre>";
// print_r($matrix);
// echo "</pre>";
?>