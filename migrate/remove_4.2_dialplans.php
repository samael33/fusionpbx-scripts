<?php

$execute_sql = false;
$config_path = '/etc/fusionpbx/';

$dialplans_to_delete = ['user_exists', 'user_record', 'call_forward_all', 'local_extension'];

require_once $config_path . "config.php";

try {
    if (strlen($db_host) > 0) {
        if (strlen($db_port) == 0) { $db_port = "5432"; }
        $db = new PDO("pgsql:host=$db_host port=$db_port dbname=$db_name user=$db_username password=$db_password");
    }
    else {
        $db = new PDO("pgsql:dbname=$db_name user=$db_username password=$db_password");
    }
}
catch (PDOException $error) {
    print "error: " . $error->getMessage() . "<br/>";
    die();
}

foreach ($dialplans_to_delete as $dialplan_name) {
    // Here we will get all dialplan_uuid for every domain
    $sql = "SELECT dialplan_uuid FROM v_dialplans WHERE dialplan_name = '$dialplan_name'";
    $prep_statement = $db->prepare($sql);
	$prep_statement->execute();
    $result = $prep_statement->fetchAll();
    var_dump($result);
}

?>