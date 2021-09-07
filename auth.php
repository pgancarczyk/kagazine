<?php

error_reporting(-1);
error_reporting(E_ALL|E_STRICT);
ini_set('display_errors', 'On');

require_once('Page.php');
require_once('Player.php');
require_once('Server.php');

function curlGet($url, $final=false) {
    static $c = null;
    if(!$c)
        $c = curl_init();

    curl_setopt($c, CURLOPT_URL, $url);
    curl_setopt($c, CURLOPT_HEADER, 0);
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 1);
    curl_setopt($c, CURLOPT_TIMEOUT, 2);
    $content = curl_exec($c);

    if($final)
        curl_close($c);
    return $content;

}

$db = new PDO('mysql:host=localhost;dbname=kag', 'kag', 'FeEQPyh88CyzcCq5GRqP');
$db->query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'");

if (isset($_COOKIE['playerID']) and isset($_COOKIE['hash'])) {
	$playerID = intval($_COOKIE['playerID']);
	$hash = preg_replace("/[^a-zA-Z0-9]+/", "", $_COOKIE['hash']);
	$row = $db->query("select hash from auth where playerID = ".$playerID)->fetch();
	if($row) {
		if ($row['hash'] == $hash) {
			if (isset($_GET['logout'])) { // wylogowanie
				unset($_GET['logout']);
				setcookie('playerID', '', time()-3600, '/', '37.187.176.227/kag');
				setcookie('hash', '', time()-3600, '/', '37.187.176.227/kag');
				setcookie('playerID', null, -1, '/');
				setcookie('hash', null, -1, '/');
				header("Location: http://37.187.176.227/kag/login");
				die;
			}
			else { // poprawne autozalogowanie
				header("Location: http://37.187.176.227/kag/me");
				die;
			}
		}
		else { // niepoprawne autozalogowanie
			setcookie('playerID', '', time()-3600, '/', '37.187.176.227/kag');
			setcookie('hash', '', time()-3600, '/', '37.187.176.227/kag');
			setcookie('playerID', null, -1, '/');
			setcookie('hash', null, -1, '/');
			header("Location: http://37.187.176.227/kag/login");
			die;
		}
	}
	else { // próba autozalogowania cookies bez wcześniejszej formy
		setcookie('playerID', '', time()-3600, '/', '37.187.176.227/kag');
		setcookie('hash', '', time()-3600, '/', '37.187.176.227/kag');
		setcookie('playerID', null, -1, '/');
		setcookie('hahs', null, -1, '/');
		header("Location: http://37.187.176.227/kag/login");
		die;
	}
}
elseif (isset($_POST['token']) and isset($_POST['name'])) { // krok 3
	$playerName = preg_replace("/[^a-zA-Z0-9-_]+/", "", $_POST['name']);
	$contents = curlGet("https://api.kag2d.com/v1/player/".$playerName."/token/".$_POST['token']);
	$response = json_decode($contents, true);
	$player = Player::createByName($playerName, $db);
	if($response['playerTokenStatus'] == 'true' and $player) { // krok 3 ok
		$row = $db->query("select hash from auth where playerID = ".$player->ID)->fetch();
		if ($row) { // hash juz jest
			setcookie('playerID', $player->ID, 2000000000, '/', '37.187.176.227/kag');
			setcookie('hash', $row['hash'], 2000000000, '/', '37.187.176.227/kag');				
		}
		else { // hasha nie ma
			$hash = md5(uniqid(rand(), true));
			$row = $db->query("insert into auth values ( ".$player->ID.", '".$hash."' )");
			setcookie('playerID', $player->ID, 2000000000, '/', '37.187.176.227/kag');
			setcookie('hash', $hash, 2000000000, '/', '37.187.176.227/kag');
		}
		header("Location: http://37.187.176.227/kag/me");
		die;
	}
	else { // krok 3 nie ok
		header("Location: http://37.187.176.227/kag/login");
		die;	
	}
}	
elseif (isset($_POST['name'])) { // krok 2
	$playerName = preg_replace("/[^a-zA-Z0-9-_]+/", "", $_POST['name']);
	$player = Player::createByName($playerName, $db);
	if ($player) { // istnieje taki
		Page::printHeader('kagazine - auth', '');
		//echo "<p>Hi ".$player->name.", in order to confirm you are who you are you have to enter your kag2d.com password. It'll be send directly to api.kag2d.com, skipping this site. You can confirm it by viewing source code of this file, but if you're not into that kind of stuff or you're master4523 you can skip to Alternative verification. The choice is yours.</p>";
		//echo '<form id=withPass method=post><input id=pass placeholder="your KAG password" type=password name=pass><input type=submit value=Verify></form>';
		//echo "<h3>Alternative verification</h3>";
		echo '<p>This is rather simple. Go to <a href="https://api.kag2d.com/v1/player/'.$player->name.'/token/new" target="_blank">https://api.kag2d.com/v1/player/'.$player->name.'/token/new</a> (note the URL and protocol, secured connection to api.kag2d.com), login using your KAG account and paste the token value belown. Yes, the long string between "quotes".</p>';
		echo '<form id=withToken method=post><input type=hidden name=name value="'.$player->name.'"><input id=token placeholder="token value" type=text name=token><input type=submit value=Verify></form>';
		echo '<p>You can read about the verification <a href="https://wiki.kag2d.com/wiki/Authentication_Token">here</a> and <a href="https://developers.thd.vg/api/intro.html#intro-authentication">here</a>.</p>';
		Page::printFooter();
		// echo '<script>
		// $( "#withPass" ).submit(function( event ) {
			// event.preventDefault();
			// var pass = $("#pass").val();
			// $.ajax({
				// username: "'; echo $player->name; echo '",
				// password: pass,
				//xhrFields: { withCredentials: true },
				//beforeSend: function (xhr) { xhr.setRequestHeader("Authorization", "Basic " + btoa("'; echo $player->name echo; ':"+pass)); },
				// url: "https://api.kag2d.com/v1/player/'; echo $player->name; echo '/token/new",
				// success: function(result) {
					// var result = jQuery.parseJSON(result);
					// if (result.playerToken) {
						// $("#token").val(result.playerToken);
						// $("#withToken").submit();
					// }
					// else alert("Wrong password. Try again.");
				// }
			// });
		// });
		// </script>';
	}
	else { // nie istnieje taki
		Page::printHeader('kagazine - auth', '../');
		echo "<p>Player doesn't exist!</p>";
		Page::printFooter();
	}
}
else { // krok 1
	Page::printHeader('kagazine - auth', '../');
	echo '<form method=post><input placeholder="your KAG player name" type=text name=name><input type=submit value=Next></form>';
	Page::printFooter();
}