<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$date = new DateTimeImmutable();
$dateplus2 = $date->add(new DateInterval("PT2M"));
echo "date now is " . $date->format("Y-m-d H:i:s") . " \ndate plus2 is " . $dateplus2->format("Y-m-d H:i:s"). "\n";
