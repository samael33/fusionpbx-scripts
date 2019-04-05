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
$start_date = mktime(0, 0, 0, date("n") - 1, 1); // First day of previous month
$end_date = mktime(23, 59, 59, date("n"), 0); // Last day of previous month

function get_correct_time($time, $init_inc = 30, $inc = 6) {
	if ($time <= 0) {
		return 0;
	}
	if ($time <= $init_inc) {
		return $init_inc;
	}
	$time_corrected = $time - $init_inc;
	$extra_add = ($time_corrected % $inc == 0) ? 0 : 1;
	
	$time_corrected = $init_inc + (floor($time_corrected / $inc) + $extra_add) * $inc;
	return $time_corrected;
}


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

$country_codes_file = fopen("rework_all_codes.csv", "r");

if (!$country_codes_file) {
	die('Cannot open rework_all_codes file');
}

while (($line = fgets($country_codes_file)) !== false) {
	list($code, $country, $rate) = explode(',', $line);
	$country_codes[$code] = array(
		'country' => $country,
		'rate' => $rate,
	);
}
fclose($country_codes_file);

echo "End processing codes....\n";

// End 0

// 1

$sql = "SELECT domain_uuid, domain_name FROM v_domains WHERE domain_enabled = 'true'";

$prep_statement = $db->prepare(check_sql($sql));
$prep_statement->execute();
$domain_list = $prep_statement->fetchAll();
unset ($prep_statement, $sql);

echo "End processing domain_list..\n";

// End 1

/*
select domain_name, domain_uuid from v_domains
select dialplan_uuid from v_dialplans where domain_uuid = '01105399-feb1-4842-b1bb-6e304d2c6dfc' and dialplan_name = 'variables
select dialplan_detail_data from v_dialplan_details where dialplan_uuid = '79764fc7-cd9a-4086-89c9-8eb8db59374b' and dialplan_detail_data like 'client_tech_prefix=%'
*/

// 2

// 2.1
foreach ($domain_list as $k => $domain) {

	$sql = "SELECT dialplan_detail_data FROM v_dialplan_details WHERE dialplan_uuid = (";
	$sql .= "SELECT dialplan_uuid from v_dialplans where domain_uuid = '" . $domain['domain_uuid'] . "' AND dialplan_name = 'variables'";
	$sql .= ") AND dialplan_detail_data LIKE 'client_tech_prefix=%'";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$client_tech_prefix = $prep_statement->fetchAll();
	if (count($client_tech_prefix) != 1) {
		$domain_list[$k]['client_tech_prefix'] = "";
		continue;
	}
	$client_tech_prefix = $client_tech_prefix[0]['dialplan_detail_data'];
	$client_tech_prefix = explode("=", $client_tech_prefix)[1];

	$domain_list[$k]['client_tech_prefix'] = $client_tech_prefix;
}

echo "End adding client_tech_prefix to domain_list...\n";
// End 2.1

// 2.2

/*
select destination_number, billsec from v_xml_cdr where direction = 'outbound' and domain_uuid = 'c15afc7f-c677-4d29-9fdb-c7b4621f0e05' and billsec > 0 and start_epoch
*/

foreach ($domain_list as $k => $domain) {

	echo "Processing domain " . $domain['domain_name'] . "\n";

	$domain_list[$k]['call_list'] = array();

	$sql = "SELECT destination_number, billsec FROM v_xml_cdr WHERE";
	$sql .= " domain_uuid = '" . $domain['domain_uuid'] . "' AND direction = 'outbound'";
	$sql .= " AND billsec > 0 AND start_epoch > " . $start_date . " AND start_epoch < " . $end_date;
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	while (($cdr_line = $prep_statement->fetch(PDO::FETCH_ASSOC)) !== false) {

		// First - cleanup from tech prefix
		$destination_number = $cdr_line['destination_number'];
		$billsec = $cdr_line['billsec'];

		if (substr($destination_number, 0, 4) == $domain['client_tech_prefix']) {
			$destination_number = substr($destination_number, 4);
		}

		// Skip accident internal numbers
		if (strlen ($destination_number) <= 4) {
			continue;
		}

		// Fix strange bug
		if (strlen($destination_number) == 14) {
			$destination_number = substr($destination_number, 4);
		}

		// Normalize to E.164
		if (substr($destination_number, 0, 3) == '011') {
			$destination_number = substr($destination_number, 3);
		} else if (substr($destination_number, 0, 3) == '00') {
			$destination_number = substr($destination_number, 2);
		} else if ($destination_number[0] == '+') {
			$destination_number = substr($destination_number, 1);
		} else if ($destination_number[0] != '1') {
			$destination_number = '1' . $destination_number;
		}


		$billsec = get_correct_time($billsec);

		echo "Processing call to " . $destination_number . " ...";

		for ($i = 0; $i <= strlen($destination_number); $i++) {

			// 123456 -> 12345
			$checked_country_code = substr($destination_number, 0, -$i);
			if (array_key_exists($checked_country_code, $country_codes)) {

				// We found our code to dial.

				$dialed_code = $checked_country_code;
				$dialed_country = isset($country_codes[$dialed_code]['country']) ? $country_codes[$dialed_code]['country'] : "NA";
				$dialed_rate = isset($country_codes[$dialed_code]['rate']) ? trim($country_codes[$dialed_code]['rate']) : 0;

				$call_price = round(((float)$dialed_rate / 60 * $billsec), 4);

				if (!isset($domain_list[$k]['call_list'][$dialed_country])) {
					// Add new dialed country do dial list
					$domain_list[$k]['call_list'][$dialed_country] = array(
						"cost" => $call_price,
						'num_calls' => 1,
						'billsec' => $billsec,
					);
				} else {
					// Append to existing data
					$domain_list[$k]['call_list'][$dialed_country]['cost'] += $call_price;
					$domain_list[$k]['call_list'][$dialed_country]['num_calls'] += 1;
					$domain_list[$k]['call_list'][$dialed_country]['billsec'] += $billsec;
				}

				echo "call to $dialed_country($dialed_code), D: $billsec, R: $dialed_rate, C: $call_price, Total call price for this dest: " .  $domain_list[$k]['call_list'][$dialed_country]['cost'] . "\n";

				break;
			}
		}
	}
}

// End 2.2

// End 2

// Print results

foreach ($domain_list as $k => $domain) {
	echo "------------------------------------------------\nDomain " . $domain['domain_name'] . "\n";
	if (count($domain['call_list']) > 0 ) {
		foreach ($domain['call_list'] as $dialed_country => $call_details) {
			echo "   Calls to " . $dialed_country . "\n";
			echo "         Total duration: " . $call_details['billsec'] . "\n";
			echo "         Call number:    " . $call_details['num_calls'] . "\n";
			echo "         Call Cost:     $" . $call_details['cost'] . "\n";
		}
	}
}
echo "------------------------------------------------\n";

?>
