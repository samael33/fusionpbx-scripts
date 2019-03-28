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

// 0

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

// End 0

// 1

$sql = "SELECT domain_uuid, domain_name FROM v_domains";

$prep_statement = $db->prepare(check_sql($sql));
$prep_statement->execute();
$domain_list = $prep_statement->fetchAll();
unset ($prep_statement, $sql);

// End 1

/*
select domain_name, domain_uuid from v_domains
select dialplan_uuid from v_dialplans where domain_uuid = '01105399-feb1-4842-b1bb-6e304d2c6dfc' and dialplan_name = 'variables
select dialplan_detail_data from v_dialplan_details where dialplan_uuid = '79764fc7-cd9a-4086-89c9-8eb8db59374b' and dialplan_detail_data like 'client_tech_prefix=%'
*/

// 2

foreach ($domain_list as $domain) {
	// 2.1
	$sql = "SELECT dialplan_detail_data FROM v_dialplan_details WHERE dialplan_uuid = (";
	$sql .= "SELECT dialplan_uuid from v_dialplans where domain_uuid = '" . $domain['domain_uuid'] . "' AND dialplan_name = 'variables'";
	$sql .= ") AND dialplan_detail_data LIKE 'client_tech_prefix=%'";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$client_tech_prefix = $prep_statement->fetchAll();
	if (count($client_tech_prefix) != 1) {
		echo "Domain " . $domain['domain_name'] . " cannot be processed, count:" . count($client_tech_prefix) . "\n";
		continue;
	}
	$client_tech_prefix = $client_tech_prefix[0]['dialplan_detail_data'];
	echo "Domain: " . $domain['domain_name'] . " -> " . $client_tech_prefix . "\n";
}

// End 2

?>
