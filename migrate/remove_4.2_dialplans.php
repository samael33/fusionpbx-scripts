<?php

$config_path = '/etc/fusionpbx/';

$dialplans_to_delete = ['user_exists', 'user_record', 'call_forward_all', 'local_extension'];

function remove_dialplan_details($db, $dialplan_uuid) {
    $sql = "DELETE FROM v_dialplan_details ";
    $sql .= "WHERE dialplan_uuid = '".$dialplan_uuid."' ";
    $db->exec($sql);
    //print($sql."\n");
}
function remove_dialplan($db, $dialplan_uuid) {
    $sql = "DELETE FROM v_dialplans ";
    $sql .= "WHERE dialplan_uuid = '".$dialplan_uuid."' ";
    $db->exec($sql);
    //print($sql."\n");
}
function check_name($db, $dialplan_uuid) {
    $sql = "SELECT dialplan_name FROM v_dialplans";
    $sql .= " WHERE dialplan_uuid = '$dialplan_uuid'";
    $prep_statement = $db->prepare($sql);
	$prep_statement->execute();
    //var_dump($prep_statement->fetchAll());
}


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
    $uuid_list = $prep_statement->fetchAll();
    foreach ($uuid_list as $uuid) {
        //check_name($db, $uuid['dialplan_uuid']);
        remove_dialplan_details($db, $uuid['dialplan_uuid']);
        remove_dialplan($db, $uuid['dialplan_uuid']);
    }
}

?>