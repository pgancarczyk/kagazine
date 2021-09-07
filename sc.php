<?php

$string = addcslashes($_POST['autocomplete'], "%_");

$db = new PDO('mysql:host=localhost;dbname=kag', 'kag', 'FeEQPyh88CyzcCq5GRqP');
$db->query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'");

$query = $db->prepare("select playerName from players where playerName like = '?%'");
$query->execute(array($string));
$query->bind_result($playerName);
while($query->fetch()) {
    echo $playerName.', ';
}
