<?php

function uuid() {
	//uuid version 4
	return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
		// 32 bits for "time_low"
		mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

		// 16 bits for "time_mid"
		mt_rand( 0, 0xffff ),

		// 16 bits for "time_hi_and_version",
		// four most significant bits holds version number 4
		mt_rand( 0, 0x0fff ) | 0x4000,

		// 16 bits, 8 bits for "clk_seq_hi_res",
		// 8 bits for "clk_seq_low",
		// two most significant bits holds zero and one for variant DCE1.1
		mt_rand( 0, 0x3fff ) | 0x8000,

		// 48 bits for "node"
		mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
	);
}

function generate_insert_lines($domain_uuid, $device_uuid, $accounts) {
    
    foreach ($accounts as $k => $v) {
        $insert_line = "INSERT INTO v_device_lines (domain_uuid, device_line_uuid, device_uuid, line_number, server_address, display_name, user_id, auth_id, password, sip_port, sip_transport, register_expires, enabled)";
        $insert_line .= " VALUES ('$domain_uuid', '" . uuid() . "', '$device_uuid', " . ($k + 2) . ",";
        $insert_line .= " '10.1.10.253', 'CC Line " . ($k + 1) . "', '" . $v[0] . "', '" . $v[0] . "',";
        $insert_line .= " '" . $v[1] . "', 5060, 'tcp', 80, 'true');";
        
        print($insert_line . "\n");
    }
}

function generate_insert_settings($domain_uuid, $device_uuid, $accounts) {
    foreach ($accounts as $k => $v) {
        $insert_line = "INSERT INTO v_device_settings (device_setting_uuid, device_uuid, domain_uuid, device_setting_subcategory, device_setting_value, device_setting_enabled)";
        $insert_line .= " VALUES ('" . uuid() . "', '$device_uuid', '$domain_uuid',";
        $insert_line .= " 'yealink_account_" . ($k + 2) . "_shared_line', '1', 'true');";
        
        print($insert_line . "\n");
    }
    
    $insert_line = "INSERT INTO v_device_settings (device_setting_uuid, device_uuid, domain_uuid, device_setting_subcategory, device_setting_value, device_setting_enabled)";
    $insert_line .= " VALUES ('" . uuid() . "', '$device_uuid', '$domain_uuid',";
    $insert_line .= " 'yealink_line_count', '10', 'true');";
    
    print($insert_line . "\n");
}

$accounts = array(
    ['2501','8!S7!pkE4D'],
    ['2502','?PiE!X!8C!'],
    ['2503','!ujCwrY%..'],
    ['2504','W1yU!.r^0.'],
    ['2505','.2S!.nW.x.'],
    ['2506','!e2z6!!dG9'],
    ['2507','SuiEbbsZIq'],
    ['2508','xA.p%bdhp?']
);

$domain_uuid = '0f22f23e-23ee-4bf2-af6c-27dd39cc18fe';

$device_uuids = ['d2b8a192-9ad5-42be-975b-efea349968fd', '4758b887-3849-4074-a377-e3060b0d37a2', '1f040b3c-8ab2-40ed-8b77-2ff9750d24bb', '1c7bf29b-2941-4eed-a55c-f0535a958709'];

foreach ($device_uuids as $device_uuid) {
    generate_insert_lines($domain_uuid, $device_uuid, $accounts);
    generate_insert_settings($domain_uuid, $device_uuid, $accounts);
}

?>