<?php

$execute_sql = true;
$document_root = '/var/www/fusionpbx';

$dialplans_to_delete = ['user_exists', 'user_record', 'call_forward_all', 'local_extension'];

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

foreach ($dialplans_to_delete as $dialplan_name) {
    // Here we will get all dialplan_uuid for every domain
    $sql = "SELECT dialplan_uuid FROM v_dialplans WHERE dialplan_name = '$dialplan_name'";
    $prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
    $result = $prep_statement->fetchAll();
    var_dump($result);
}

?>