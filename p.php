<?php
$time_start = microtime(true); 

// error_reporting(-1);
// error_reporting(E_ALL|E_STRICT);
// ini_set('display_errors', 'On');

require_once('Player.php');
require_once('Server.php');
require_once('Page.php');


if (@$_GET['name'] == '') {
	Page::printHeader('kagazine - error', '../');
	echo "<p>Specify player name.</p>";
	Page::printFooter();
	die;
}
$playerName = preg_replace("/[^a-zA-Z0-9-_]+/", "", $_GET['name']);
Page::printHeader('kagazine - '.$playerName, '../');
$db = new PDO('mysql:host=localhost;dbname=kag', 'kag', 'FeEQPyh88CyzcCq5GRqP');
$db->query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8', group_concat_max_len=15000;");

$player = Player::createByName($playerName, $db);
if ($player) {
	if(isset($player->bgURL)) {
		if ($player->bgURL !== '') {
			echo "<style>body { background: url(".$player->bgURL.") no-repeat center center fixed; background-size: cover; }</style>";
		}
	}

	echo "<h3>".$player->getA()."</h3>\n";

	if ($player->avatarID) {
		echo '<div class=avatarMask><img onError="this.src='."'https://forum.thd.vg/styles/default/xenforo/avatars/avatar_l.png'".'" class=avatar src="https://forum.thd.vg/data/avatars/l/'.$player->avatarID."/".$player->forumID.".".$player->avatarExt.'"'."></div>\n";
	}
	else echo '<div class=avatarMask><img class=avatar src="https://forum.thd.vg/styles/default/xenforo/avatars/avatar_l.png"></div>'."\n";
	
	$result = $db->query("select serverID from activity where playerID = ".$player->ID." order by stamp desc limit 1");
	
	if($result->rowCount() > 0) {
		$row = $result->fetch();
		if ($row['serverID'] == 0) {
			echo "<p>".$player->name." isn't playing at the moment.</p>";
		}
		else echo "<p>".$player->name." is playing on ".Server::CreateByID($row['serverID'], $db)->getA()." at the moment.</p>";
	}
	else echo "<p>".$player->name." has no recorded kagazine activity and isn't playing at the moment.</p>";
	
	echo "<p>Name: ".$player->name.".</p>";
	if (strtotime($player->registered)>0) echo '<p>Registered: <time class=timeago datetime="'.date('c',strtotime($player->registered)).'">'.date('M j, Y', strtotime($player->registered))."</time>.</p>\n";
    $logged = Page::getLoggedPlayer();
    if ($logged) {
        if ($logged->ID != $player->ID) {
            $result = $db->query("select * from friends where playerID = ".$logged->ID." and friendID = ".$player->ID);
            if ($result->rowCount() > 0) {
                echo "<p id=addFriend data-name='".$player->name."'>".$player->name." is your friend. You'll get notified whenever they join KAG. <a href=# onclick=".'"removeFriend(event)"'.">Remove from the list</a>.</p>\n";
           
            }
            else {
                echo "<p id=addFriend data-name='".$player->name."'><a href=# onclick=".'"addToFriends(event)"'.">Add ".$player->name." to your friends</a> to get a notification whenever they join KAG.</p>\n";
            }
        }
    }
    $result = $db->query('select friendID from friends where playerID = '.$player->ID);
    $list = '';
    while($row = $result->fetch()) {
        $list.= Player::createByID($row['friendID'], $db)->getA().", ";
    }
    if ($list == '') echo "<p>".$player->name." hasn't added any friends.</p>";
    else echo "<p>Friends of ".$player->name.": ".substr($list, 0, -2).".";
    // var_dump($row);
	$result = $db->query("select group_concat(unix_timestamp(stamp), '=', serverID order by stamp) as list, weekday(stamp) as day from activity where playerID = ".$player->ID." and stamp > DATE_SUB(concat((date(now()) + interval 1 day), ' 00:00:00'), INTERVAL 7 DAY) group by day(stamp) order by stamp");
	$days = array();
	$previous = 0;
	$dayPointer = 1;
	while($row = $result->fetch()) {
		// echo "<p style='word-break: break-all'>".json_encode($row, JSON_PRETTY_PRINT)."</p>";
		// echo "<pre>".json_encode($row, JSON_PRETTY_PRINT)."</pre>";
		$days[$row['day']] = 0;
		$list = explode(",", $row['list']);
		foreach ($list as $element) {
			$element = explode("=", $element);
			if ($previous != 0) $days[$row['day']] += ($element[0]-$previousTime);
			$previous = $element[1];
			$previousTime = $element[0];
		}
		$dayPointer++;
		if ($previous != 0 and $dayPointer-1 < $result->rowCount()) {
			$days[$row['day']] += (strtotime(date('Y-m-d',($previousTime+(24*60*60))).' 00:00:00')-$previousTime);
			$previousTime = strtotime(date('Y-m-d',($previousTime+(24*60*60))).' 00:00:00');
			// echo 'granie przez polnoc w dzien '.$row['day'].', ustawiam previousTime na '.date('l jS F Y h:i:s A',$previousTime).', ';
		}
		else if ($previous != 0) {
			// echo 'granie az do teraz';
			$days[$row['day']] += (time()-$previousTime);
		}
	}
	$weekdays = array("Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday");
	// echo "<pre>".json_encode($row, JSON_PRETTY_PRINT)."</pre>";
	// echo date('l jS F Y h:i:s A', time());
	// echo "<pre>".json_encode($days, JSON_PRETTY_PRINT)."</pre>";
	echo '<h2>Last 7 days in minutes played</h2>
	<div id=canvas><canvas id=minutesPlayed></canvas></div>
	<script>
	var canvasMinutesPlayed = document.getElementById("minutesPlayed").getContext("2d");

	var data = {
		labels: [ '; for($i = intval(date('N', time())); $i < intval(date('N', time()))+7; $i++)  { echo '"'.$weekdays[$i].'", '; } echo '],
		datasets: [
			{
				label: "weekly",
				fillColor: "rgba(220,220,220,0.2)",
				strokeColor: "rgba(220,220,220,1)",
				pointColor: "rgba(220,220,220,1)",
				pointStrokeColor: "#fff",
				pointHighlightFill: "#fff",
				pointHighlightStroke: "rgba(220,220,220,1)",
			data: ['; for($i = intval(date('N', time())); $i < intval(date('N', time()))+7; $i++)  { 
				if ($i == 7) { $j = 0;}
				elseif ($i == 8) { $j = 1;}
				elseif ($i == 9) { $j = 2;}
				elseif ($i == 10) { $j = 3;}
				elseif ($i == 11) { $j = 4;}
				elseif ($i == 12) { $j = 5;}
				elseif ($i == 13) { $j = 6;}
				elseif ($i == 14) { $j = 0;}
				else {$j = $i;}
				if (isset($days[$j])) {echo round($days[$j]/60);}
				else {echo "0";}
				// echo $j;
				echo ", ";
			} echo ']
			}
		]
	};
	var minutesPlayed = new Chart(canvasMinutesPlayed).Line(data, { scaleBeginAtZero: true ,scaleShowGridLines : false,bezierCurveTension : 0.2,responsive: true,maintainAspectRatio: false,});
	</script>';
	

	$sum = 0;
	foreach($days as $day) { $sum+= $day; }
	echo "<p>In total ".round($sum/(60*60), 1)." hours of KAG this week.</p>\n";
	
	echo "<h2>Favourite servers</h2>\n<ol>";
	$result = $db->query("select serverID, unix_timestamp(stamp) as stamp from activity where playerID = ".$player->ID);
	$secondsOnServers = array();
	$row = $result->fetch();
	$lastServerID = $row['serverID'];
	$lastStamp = $row['stamp'];
	while ($row = $result->fetch()) {
		@$secondsOnServers[$lastServerID] += $row['stamp'] - $lastStamp;
		$lastServerID = $row['serverID'];
		$lastStamp = $row['stamp'];
	}
	$secondsOnServers[$lastServerID] += time() - $lastStamp;
	arsort($secondsOnServers);
	// echo "<pre>".json_encode($secondsOnServers, JSON_PRETTY_PRINT)."</pre>";
	$howManyServers = 0;
	$sumOfTime = 0;
	foreach($secondsOnServers as $serverID => $seconds) {
		if ($serverID != 0) {
			if ($howManyServers < 10) {
				echo "<li>".Server::CreateByID($serverID, $db)->getA()." (".round($seconds/60/60, 1)." hour".(round($seconds/60/60, 1) == 1 ? "" : "s").")</li>\n";
			}
			$howManyServers++;
			$sumOfTime += $seconds/60/60;
		}
	}
	echo "</ol>";
	if ($howManyServers > 10) echo "<p>(".($howManyServers-10)." more visited, total time of ".round($sumOfTime)." hours since May 2016)</p>";
	
}
else {
	echo "<h2>Player doesn't exist!</h2>";
}

//echo '<p>Total execution time in seconds: ' . (microtime(true) - $time_start).".</p>\n";

Page::printFooter();