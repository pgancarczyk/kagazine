<?php

error_reporting(-1);
error_reporting(E_ALL|E_STRICT);
ini_set('display_errors', 'On');

require_once('Player.php');
require_once('Server.php');
require_once('Page.php');

if(isset($_POST['friendName'])) {
    $friendName = $_POST['friendName'];
    $db = new PDO('mysql:host=localhost;dbname=kag', 'kag', 'FeEQPyh88CyzcCq5GRqP');
    $db->query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8', group_concat_max_len=15000;");
    $friend = Player::createByName($friendName, $db);
    $player = Page::getLoggedPlayer();
    if ($friend and $player) {
        $db->query("delete from friends where playerID = ".$player->ID." and friendID = ".$friend->ID);
        echo "<a href=# onclick=".'"addToFriends(event)"'.">Add ".$friend->name." to your friends</a> to get a notification whenever they join KAG.";
        die;
    }
}

echo "Something went wrong. Please refresh the site and try again (contact me if it's still happening).";