<?php
// $time_start = microtime(true); 

// error_reporting(-1);
// error_reporting(E_ALL|E_STRICT);
// ini_set('display_errors', 'On');

require_once('Player.php');
require_once('Server.php');
require_once('Page.php');

$db = new PDO('mysql:host=localhost;dbname=kag', 'kag', 'FeEQPyh88CyzcCq5GRqP');
$db->query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'");

header('Content-Type: text/plain; charset=utf-8');

$response = array();
$response['messages'] = array();
// echo $_POST['msg'];
$player = Page::getLoggedPlayer();

// if($player->name=='Vonetcher') $player = false;

if($player) {
	$db->query("insert into websiteActivity ( playerID ) values ( ".$player->ID." ) on duplicate key update seen = now()");
	$result = $db->query('select playerID from websiteActivity where seen > date_sub(now(), interval 10 second)');
	$list = '';
	$count = 0;
	while($row = $result->fetch()) {
		$list.= Player::createByID($row['playerID'], $db)->getA().", ";
		$count++;
	}
	if ($list != '') $response['visiting'] = "Visiting the site: ".substr($list, 0, -2)." (".$count." player".($count > 1 ? "s" : "").").";
}

if(isset($_POST['msg']) and $player and htmlspecialchars(trim($_POST['msg'])) != '') {
	// if ($player->name == 'Konfitur') $isAdmin = true;
	// else $isAdmin = false;
	// $playerID = $player->ID;
	// if ($isAdmin and strip_tags(trim($_POST['msg'])) == '/refresh') $playerID = '0';
	$db->prepare("insert into shoutbox values (NULL, ".$player->ID.", ?, NULL)")->execute(array(htmlspecialchars(trim($_POST['msg']))));
}
if(isset($_POST['lastMsgID'])) {
	$lastMsgID = intval($_POST['lastMsgID']);
	$result = $db->query("select messageID, playerID, message, unix_timestamp(stamp) as stamp from shoutbox where messageID > ".$lastMsgID);
}
else {
	$result = $db->query("select * from (select messageID, playerID, message, unix_timestamp(stamp) as stamp from shoutbox order by messageID desc limit 50) x order by messageID");
}
while($row = $result->fetch()) {
	$player = Player::createByID($row['playerID'], $db);
	if ($player->name == 'Konfitur') $isAdmin = true;
	elseif ($player->name == 'Anszej') $isAdmin = true;
	elseif ($player->name == 'ThomasTK') $isAdmin = true;
	elseif ($player->name == 'Harlekin') $isAdmin = true;
	elseif ($player->name == 'sinnertie') $isAdmin = true;
	else $isAdmin = false;
	if ($isAdmin and $row['message'] == '/refresh') $response['messages'][$row['messageID']] = array( ';', $row['message'], $row['stamp'] );
	elseif ($isAdmin and $row['message'] == '/skip') $response['messages'][$row['messageID']] = array( ';', $row['message'], $row['stamp'] );
	else $response['messages'][$row['messageID']] = array( $player->getA(), $row['message'], $row['stamp'] );
}
$response['status'] = 'success';
// else $response['status'] = 'error';

echo json_encode($response);