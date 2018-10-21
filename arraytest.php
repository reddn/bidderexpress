<?php


echo "<pre>";


$testarray1 = array(1,2,3,4,5,6,7,8,9);
$testarray2 = array(100,101,102,103,104,105);
// print_r($testarray1);

foreach($testarray1 as $key => $val){
	if($val == 3) unset($testarray2[3]);
	print_r($testarray2);
	reset($testarray2);
}



print_r($testarray1);




echo "</pre>";