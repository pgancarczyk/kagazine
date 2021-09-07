<?php
$time_start = microtime(true); 

// error_reporting(-1);
// error_reporting(E_ALL|E_STRICT);
// ini_set('display_errors', 'On');

require_once('Player.php');
require_once('Server.php');
require_once('Page.php');

if (@$_GET['id'] == '') {
	Page::printHeader('kagazine - error', '../');
	echo "<p>Specify server ID.</p>";
	Page::printFooter();
	die;
}

$serverID = $_GET['id'];
$db = new PDO('mysql:host=localhost;dbname=kag', 'kag', 'FeEQPyh88CyzcCq5GRqP');
$db->query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'");

$server = Server::createByID($serverID, $db);
if ($server) {
	Page::printHeader('kagazine - '.$server->name, '../', true);
	
	echo "<h3>".$server->name."</h3>\n";
	
	echo '<div class=map><a target=_blank href="'.$server->getMapUrl().'" download><img onError="this.remove()" alt=minimap src="'.$server->getMapUrl().'"'."></a></div>\n";
	
	echo '<p style="text-align: center">'.$server->description."</p>\n";
	
	$result = $db->query("select playerID from currentPlayers where serverID = ".$server->ID);
	$list = '';
	$count = 0;
	while ($row = $result->fetch()) {
		$list.= Player::createByID($row['playerID'], $db)->getA().", ";
		$count++;
	}
	if ($list != '') echo "<p>".substr($list, 0, -2)." (".$count." player".($count > 1 ? "s" : "").").</p>\n";
	else echo "<p>This server is empty at the moment.</p>\n";
	
	echo "<p>Gamemode: ".$server->gamemode."</p>\n";
	
	$row = $db->query("select UNIX_TIMESTAMP(stamp) as stamp from activity where serverID = ".$server->ID." order by stamp limit 1")->fetch();
	echo '<p>First time recorded: <time class=timeago datetime="'.date('c', $row['stamp']).'">'.date('M j, Y', $row['stamp']).'</time>.</p>'."\n";
	
	$row = $db->query("select (select count(serverID) from activity where serverID = ".$server->ID.") as share, (select count(serverID) from activity) as full")->fetch();
	echo "<p>Popularity: ".round(($row['share']/$row['full'])*100, 2)."%.</p>\n";
	
	$row = $db->query("select count(distinct(playerID)) as howMany from activity where serverID = ".$server->ID)->fetch();
	echo "<p>This server has been visited by ".$row['howMany']." players so far.</p>\n";
	
	?><div id="world-map" style="height: 400px"></div>
<script>
$(function() {
    $("#world-map").vectorMap({
        map: "world_mill_en",
		zoomButtons: false,
		backgroundColor: 'transparent',
		regionStyle: { initial: { fill: '#95E895', stroke: '#333333', "stroke-width": 0.5, "stroke-opacity": 0.5, } },
        scaleColors: ["#C8EEFF", "#0071A4"],
        normalizeFunction: "polynomial",
        hoverOpacity: .7,
        hoverColor: !1,
        markerStyle: {
            initial: {
                fill: "#F8E23B",
                stroke: "#383f47"
            }
        },
        markers: [{
            latLng: [<?php echo $server->lat.",".$server->lon ?>],
            name: "approximate server location"
        }]
    })
	// $("#world-map").vectorMap('get', 'mapObject').setFocus({lat: 33, lon: 77, scale: 2});
});
</script><?php

	$result = $db->query("select playerID, count(playerID) as occurances from activity where serverID = ".$server->ID." group by playerID order by occurances desc limit 5");
	echo "<h2>Most loyal players</h2><ol>\n";
	while ($row = $result->fetch()) {
		echo "<li>".Player::createByID($row['playerID'], $db)->getA()."</li>";
	}
	echo "\n</ol>\n";
	
	$result = $db->query("select stamp as stamp, group_concat(playerID, '=', serverID) as list from activity where (serverID = ".$server->ID." or serverID = 0) and stamp > DATE_SUB(NOW(), INTERVAL 1 DAY) group by hour(stamp) order by stamp");
	$hours = array();
	$players = array();
	while($row = $result->fetch()) {
		$list = explode(",", $row['list']);
		foreach($list as $element) {
			$elements = explode("=", $element);
			$playerID = $elements[0];
			$serverID = @$elements[1];
			if($serverID != 0) {
				$players[$playerID] = true;
			}
			if($serverID == 0 and isset($players[$playerID])) unset($players[$playerID]);
		}
		$hours[$row['stamp']] = count($players);
		// if ($hours[$i] > 20) { var_dump($players); die;}
	}
	// var_dump($hours);
	echo '<h2>Unique players of last 24 hours</h2>
	<div id=canvas><canvas id=rushHours></canvas></div>
	<script>
	var canvasRushHours = document.getElementById("rushHours").getContext("2d");

	var data = {
		labels: ['; foreach($hours as $hour => $value){echo '"'.date('G', strtotime($hour)).':00",';} echo '],
		scaleFontSize: 1,
		datasets: [
			{
				label: "weekly",
				scaleFontSize: 1,
				fillColor: "rgba(220,220,220,0.2)",
				strokeColor: "rgba(220,220,220,1)",
				pointColor: "rgba(220,220,220,1)",
				pointStrokeColor: "#fff",
				pointHighlightFill: "#fff",
				pointHighlightStroke: "rgba(220,220,220,1)",
				data: ['; foreach($hours as $hour){echo $hour.',';} echo ']
			}
		]
	};
	var rushHours = new Chart(canvasRushHours).Line(data, {  showTooltips: true, scaleShowLabels : false, scaleShowGridLines : false,bezierCurveTension : 0.2,responsive: true,maintainAspectRatio: false,});
	</script>';	

}
else {
	Page::printHeader('kagazine - error', '../');
	echo "<h2>Server doesn't exist!</h2>";
	Page::printFooter();
}

// echo '<p>Total execution time in seconds: ' . (microtime(true) - $time_start).".</p>\n";

Page::printFooter();