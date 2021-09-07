<?php
$url = explode('/',$_GET['q']);
$rest = new stdClass();
for($i = 0; $i < count($url)/2; $i++) {
    $rest->$url[$i*2] = $url[$i*2+1];
}

$query = "select ID, IPv4Address, description, serverName, port, build, gold, currentPlayers, gameMode, maxPlayers, password, playerList, lastUpdate, (currentPlayers/maxPlayers) as percentage from master_servers where lastUpdate > date_sub(now(), interval 30 second)".(isset($rest->gold) ? " and gold = '".($rest->gold == "1" ? "true" : "false")."'" : "").(isset($rest->password) ? " and password = '".($rest->password == "1" ? "true" : "false")."'" : "").(isset($rest->gameMode) ? " and gameMode = '".$rest->gameMode."'" : "").((isset($rest->minPlayerPercentage) OR isset($rest->maxPlayerPercentage)) ? " having ID > 0" : "").(isset($rest->minPlayerPercentage) ? " and percentage >= ".$rest->minPlayerPercentage : "").(isset($rest->maxPlayerPercentage) ? " and percentage <= ".$rest->maxPlayerPercentage : "");


$db = new PDO('mysql:host=localhost;dbname=kag', 'kag', 'FeEQPyh88CyzcCq5GRqP');
$db->query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8', group_concat_max_len=15000;");

$response["serverList"] = array();
// $response["query"] = $query;

$result = $db->query($query);
while($row = $result->fetch()) {
    $server = new stdClass();
    $server->build = intval($row["build"]);
    $server->currentPlayers = intval($row["currentPlayers"]);
    $server->description = $row["description"];
    $server->gameMode = $row["gameMode"];
    $server->gold = ($row["gold"] == 'true' ? 1 : 0);
    $server->maxPlayers = intval($row["maxPlayers"]);
    $server->password = ($row["password"] == 'true' ? 1 : 0);
    $objList = json_decode($row["playerList"]);
    $server->playerList = array();
    foreach($objList as $key=>$value) {
        array_push($server->playerList, $key);
    }
    $server->serverIPv4Address = $row["IPv4Address"];
    $server->serverName = $row["serverName"];
    $server->serverPort = intval($row["port"]);
    array_push($response["serverList"], $server);
}



echo json_encode($response, JSON_PRETTY_PRINT);


// $h = fopen('/var/www/kag/logtestowy.txt', 'a');
// fwrite($h, json_encode($response, JSON_PRETTY_PRINT)."\n");
// fclose($h);


// {
  // "serverList": [
    // {
      // "build": 591,
      // "currentPlayers": 0,
      // "description": "test server",
      // "gameMode": "RolePlay",
      // "gold": 0,
      // "maxPlayers": 32,
      // "password": 0,
      // "playerList": [],
      // "serverIPv4Address": "37.187.176.227",
      // "serverName": "konfs",
      // "serverPort": 50301,
    // }
  // ]
// }
