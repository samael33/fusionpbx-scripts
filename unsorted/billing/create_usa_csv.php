<?php

$country_codes_file = fopen("rework_int.csv", "r");

if (!$country_codes_file) {
	die('rework_int.csv');
}

while (($line = fgets($country_codes_file)) !== false) {

    list($code, $country, $price) = explode(',', $line, 3);

    $price = str_replace(["$", "\"", "\'"], "", $price);
    $price = str_replace(",", ".", $price);
    $price = trim($price);

    file_put_contents("rework_int_1.csv", "$code,$country,$price\n", FILE_APPEND);
}
fclose($country_codes_file);