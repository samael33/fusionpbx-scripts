<?php
/*
	FusionPBX
	Version: MPL 1.1
	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/
	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.
	The Original Code is FusionPBX
	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2018
	the Initial Developer. All Rights Reserved.
	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
// Settings
$document_root = '/var/www/fusionpbx';
$start_date = mktime(0, 0, 0, date("n"), 1); // First day of the month
$end_date = mktime(23, 59, 59, date("n"), date("t")); // Last day of the month

//web server or command line
if(defined('STDIN')) {
        set_include_path($document_root);
        $_SERVER["DOCUMENT_ROOT"] = $document_root;
        $project_path = $_SERVER["DOCUMENT_ROOT"];
        define('PROJECT_PATH', $project_path);
        $_SERVER["PROJECT_ROOT"] = realpath($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH);
        set_include_path(get_include_path() . PATH_SEPARATOR . $_SERVER["PROJECT_ROOT"]);
        require_once "resources/require.php";
        $display_type = 'text'; //html, text
} else {
    include "root.php";
    require_once "resources/require.php";
    require_once "resources/pdo.php";
}

/* 

0. Load country prefix list
1. Ask for domain list / uuid.
2. Foreach client
	1. Ask for tech prefix
	2. Get outbound calls (cdr - outbound)
	3. Get country
	4. Round call duration - ?
	5. Build client array
3. Print / Email results
*/

$country_codes = array();

$country_codes_file = fopen("AllCountryCodes.csv", "r");

if (!$country_codes_file) {
	die('Cannot open AllCountryCodes file');
}

while (($line = fgets($country_codes_file)) !== false) {
	list($code, $country) = explode(',', $line);
	$country_codes[$code] = $country;
}
fclose($country_codes_file);


?>
