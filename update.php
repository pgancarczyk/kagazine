<?php
if ($_SERVER['SERVER_ADDR'] != $_SERVER['REMOTE_ADDR']){
	die;
}

// error_reporting(-1);
// error_reporting(E_ALL|E_STRICT);
// ini_set('display_errors', 'On');
    // $version = curl_version();

// These are the bitfields that can be used 
// to check for features in the curl build
// $bitfields = Array(
            // 'CURL_VERSION_IPV4', 
            // 'CURLOPT_IPRESOLVE'
            // );


// foreach($bitfields as $feature)
// {
    // echo $feature . ($version['features'] & constant($feature) ? ' matches' : ' does not match');
    // echo PHP_EOL;
// }
// die;
function curlGet($url, $final=false) {
	static $c = null;
	if(!$c) $c = curl_init();
	curl_setopt($c, CURLOPT_URL, $url);
	curl_setopt($c, CURLOPT_HEADER, 0);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 1);
	curl_setopt($c, CURLOPT_TIMEOUT, 2);
	curl_setopt($c, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
	curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
	// echo '<pre>'.json_encode(curl_getinfo($c, CURLOPT_IPRESOLVE), JSON_PRETTY_PRINT).'</pre>';
	// die;
	$content = curl_exec($c);
	if($final)	curl_close($c);
	return $content;

}

$urlUpdate = "https://api.kag2d.com/servers/gid/0/current/1/empty/0/connectable/1";
$urlInfo = "https://api.kag2d.com/player/";
$infoSufix = "/info";

// echo date('Y-m-d H:i:s')."\n";

date_default_timezone_set('Europe/Warsaw');
// echo date('Y-m-d H:i:s')."\n";

$contents = curlGet($urlUpdate); 
$contents = utf8_encode($contents);
$results = json_decode($contents, true);
// var_dump($contents);
// die;

header('Content-type: text/plain charset=utf-8');

$db = new mysqli("localhost", "kag", "FeEQPyh88CyzcCq5GRqP", "kag");
$db->query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'");

// echo "SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'\n";

$checkAgain = array();
$checkedPlayers = array();
$result = $db->query("select playerID from checkAgain");

while($row = $result->fetch_assoc()) {
	array_push($checkAgain, $row['playerID']);
}
// var_dump($checkAgain);
// $loaded = false;
$db->query("delete from currentPlayers");
if($results['serverList']) {
    // $loaded = true;
    //$db->query("delete from currentPlayers");
	$db->query("delete from checkAgain");
	foreach($results['serverList'] as $server) {
		//////serwer
		$server['gameMode'] = str_replace("'", '"', $server['gameMode']);
		$server['serverName'] = str_replace("'", '"', $server['serverName']);
		$server['description'] = str_replace("'", '"', $server['description']);
		$result = $db->query("select ID, unix_timestamp(lastChange) as lastChange from servers where hash = '".md5($server['serverIPv4Address'].$server['serverPort'].$server['gameMode'].'sól')."'");
		$row = $result->fetch_assoc();
		if ($row['ID']) {
			///////istnieje
			if ((intval($row['lastChange']) + (24*60*60)) < time() ) {
				$queryString = "update servers set name = '".$server['serverName']."', description = '".$server['description']."', lastChange = from_unixtime(".time().") where ID = ".$row['ID'];
				// echo $queryString."\n";
				$db->query($queryString);
			}
		}
		else {
			///////////nie istnieje
			$contents = curlGet("http://ip-api.com/json/".$server['serverIPv4Address']."?fields=countryCode,lat,lon");
			$geoInfo = json_decode($contents, true);
			// $geoInfo['countryCode'];
			$queryString = "insert into servers values ( NULL, '".$server['serverName']."', '".$server['description']."', '".$server['serverIPv4Address']."', '".$geoInfo['lat']."', '".$geoInfo['lon']."', '".$geoInfo['countryCode']."', ".$server['serverPort'].", '".$server['gameMode']."', '".md5($server['serverIPv4Address'].$server['serverPort'].$server['gameMode'].'sól')."', NULL )";
			// echo $queryString."\n";
			$db->query($queryString);
			$result = $db->query("select ID from servers order by ID desc limit 1");
			$row = $result->fetch_assoc();
		}
		$serverID = $row['ID'];
		
		////////////////////////gracz
		
		foreach($server['playerList'] as $playerName) {
			// echo $playerName;
			$result = $db->query("select ID, unix_timestamp(lastChange) as lastChange from players where playerName = '".$playerName."'");
			// echo "select ID, unix_timestamp(lastChange) as lastChange from players where playerName = '".$playerName."'\n";
			$row = $result->fetch_assoc();
			if ($row['lastChange']) {
				if ((intval($row['lastChange']) + (24*60*60)) < time() ) {
					// echo "updating ".$playerName.' '.(intval($row['lastChange'])+(24*60*60)).' < '.time()."...\n";
					$contents = curlGet($urlInfo.$playerName.$infoSufix);
					$playerInfo = json_decode($contents, true);
					if ($playerInfo['gold']) $playerGold = "true"; else $playerGold = "false";
					if ($playerInfo['banned']) $playerBanned = "true"; else $playerBanned = "false";
					$contents = curlGet("https://api.kag2d.com/player/".$playerName."/avatar/l");
					$playerAvatar = json_decode($contents, true);
					$forumID = 'NULL';
					$avatarID = 'NULL';
					$avatarExt = 'NULL';
					if($playerAvatar['large']) {
						$playerAvatar = explode("/", $playerAvatar['large']);
						$forumID = "'".(explode(".", $playerAvatar[7])[0])."'";
						$avatarID = "'".$playerAvatar[6]."'";
						// $avatarID = get_headers($playerAvatar['large'], 1)[0];
						$avatarExt = "'".(explode(".", $playerAvatar[7])[1])."'";
					}
					///////////////// $queryString = "update players set gold = '".$playerGold."', banned = '".$playerBanned."', role = ".$playerInfo['role']." where playerName = '".$playerName."'";
					$queryString = "update players set gold = '".$playerGold."', banned = '".$playerBanned."', registered = from_unixtime(".$playerInfo['regUnixTime'].", '%Y-%m-%d'), role = ".$playerInfo['role'].", avatarID = ".$avatarID.", forumID = ".$forumID.", avatarExt = ".$avatarExt.", lastChange = from_unixtime(".time().") where playerName = '".$playerName."'";
					// echo $queryString."\n";
					$db->query($queryString);
				}
				else {
					// echo $playerName."is up to date\n";
				}
			}
			else {
				// echo $playerName."does not exist, adding\n";
				$contents = curlGet($urlInfo.$playerName.$infoSufix);
				$playerInfo = json_decode($contents, true);
				if ($playerInfo['gold']) $playerGold = "true"; else $playerGold = "false";
				if ($playerInfo['banned']) $playerBanned = "true"; else $playerBanned = "false";
				$contents = curlGet("https://api.kag2d.com/player/".$playerName."/avatar/l");
				$playerAvatar = json_decode($contents, true);
				if($playerAvatar['large']) {
					$playerAvatar = explode("/", $playerAvatar['large']);
					$forumID = "'".(explode(".", $playerAvatar[7])[0])."'";
					$avatarID = "'".$playerAvatar[6]."'";
					$avatarExt = "'".(explode(".", $playerAvatar[7])[1])."'";
				}
				else {
					$forumID = 'NULL';
					$avatarID = 'NULL';
				}			
				$queryString = "insert into players values ( NULL, '".$playerName."', '".$playerGold."', '".$playerBanned."', from_unixtime(".$playerInfo['regUnixTime'].", '%Y-%m-%d'), ".$playerInfo['role'].", NULL, NULL, NULL, NULL, ".$forumID.", ".$avatarID.", ".$avatarExt.", NULL, NULL, NULL, NULL, NULL)";
				// echo $queryString."\n";
				$db->query($queryString);
				$result = $db->query("select ID from players order by ID desc limit 1");
				// echo $queryString."\n";
				$row = $result->fetch_assoc();
			}
			$playerID = $row['ID'];
			
			$db->query("insert into currentPlayers values ($playerID, $serverID)");
			
			$result = $db->query("select serverID from activity where playerID = ".$playerID." order by stamp desc limit 1");
			// echo "select serverID from activity where playerID = ".$playerID." order by stamp limit 1\n";
			$row = $result->fetch_assoc();
			// var_dump($row['serverID']);
			// var_dump($serverID);
			if (!$row['serverID']) {
				$db->query("insert into activity values ( ".$playerID.", ".$serverID.", NULL )");
				// echo "insert into activity values ( ".$playerID.", ".$serverID.", NULL )\n";
			}
			elseif ( $row['serverID'] != $serverID ) {
				$db->query("insert into activity values ( ".$playerID.", ".$serverID.", NULL )");
				// echo "insert into activity values ( ".$playerID.", ".$serverID.", NULL )\n";
				// if(($key = array_search($, $checkAgain)) !== false) {
					// unset($messages[$key]);
				// }			
			}
			array_push($checkedPlayers, $playerID);
			if(($key = array_search($playerID, $checkAgain)) !== false) {
				unset($checkAgain[$key]);
			}
		}

	}
}

foreach($checkAgain as $playerID) {
	//dodanie tych co byli do sprawdzenia a nie zostali teraz sprawdzeni (czyli 0 im dać)
	$queryString = "insert into activity values ( ".$playerID.", 0, NULL)";
	// echo $queryString."\n";
	$db->query($queryString);
}

// dodanie do checkAgain tych sprawdzonych
foreach($checkedPlayers as $playerID) {
	$queryString = "insert into checkAgain values ( ".$playerID.")";
	// echo $queryString."\n";
	$db->query($queryString);
}

// $queryString = "select * from checkAgain";
// $result = $db->query($queryString);
// echo $queryString."\n";

// while ($row = $result->fetch_assoc()) {
	// if (!in_array($row['playerID'], $checkedPlayers)) {
		// $db->query("insert into activity values ( ".$row['playerID'].", 0, NULL )");
		// echo "insert into activity values ( ".$row['playerID'].", 0, NULL )\n";
	// }
// }

// $queryString = "delete from checkAgain";
// $db->query($queryString);

$db->close();

// echo "koniec";