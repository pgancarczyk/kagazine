<?php

require_once('Player.php');
require_once('Server.php');
require_once('Page.php');

error_reporting(-1);
error_reporting(E_ALL|E_STRICT);
ini_set('display_errors', 'On');

header('Content-Type: application/json');

$url = explode('/', $_GET['q']);
// echo $_GET['q'];
// die;

$db = new PDO('mysql:host=localhost;dbname=kag', 'kag', 'FeEQPyh88CyzcCq5GRqP');
$db->query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'");

if (isset($_SERVER['PHP_AUTH_USER']) and isset($_SERVER['PHP_AUTH_USER'])) {
    $login = $_SERVER['PHP_AUTH_USER'];
    $password = $_SERVER['PHP_AUTH_PW'];
    $player = Player::createByName($login, $db);
    if (isset($player->ID)) {
        if(isset($player->password)) {
            if (hash('sha256', $password.'5IoC7GXHAhGwhax5pTWi') == $player->password) {
                if($url[1] == 'myinfo') {
                    $response["active"] = true;
                    $response["banExpiration"] = null;
                    $response["banReason"] = null;
                    $response["banned"] = false;
                    $response["gold"] = true;
                    $response["rating"] = floatval(0);
                    $response["receiveEmails"] = true;
                    $response["regUnixTime"] = 1342356514;
                    $response["registered"] = "2012-07-15 12:48:34";
                    $response["role"] = (isset($player->role) ? intval($player->role) : 0);
                    $response["termsAccepted"] = true;
                    $response["username"] = $player->name;
                }
                elseif($url[1] == 'token' and $url[2] == 'new') {
                    $response["playerToken"] = $player->password;
                }
                // else $response = 'instead of myinfo or token got: '.$url[1];
            }
            // else $response = 'wrong password';
        }
        // else $response = 'password not set for '.$player->name;
    }
    else {
        if($url[1] == 'myinfo') {
            $response["active"] = true;
            $response["banExpiration"] = null;
            $response["banReason"] = null;
            $response["banned"] = false;
            $response["gold"] = false;
            $response["rating"] = floatval(0);
            $response["receiveEmails"] = true;
            $response["regUnixTime"] = 1342356514;
            $response["registered"] = "2012-07-15 12:48:34";
            $response["role"] = 0;
            $response["termsAccepted"] = true;
            $response["username"] = $url[0];
        }
        elseif($url[1] == 'token' and $url[2] == 'new') {
            $response["playerToken"] = 'asdf';
        }
    }
}
else {
    $player = Player::createByName($url[0], $db);
    if(isset($player->ID)) {
        if($url[1] == 'token') {
            $token = $url[2];
            if($token == $player->password) {
                $response["playerTokenStatus"] = true;
            }
            else {
                $response["playerTokenStatus"] = false;
            }
        }
        elseif($url[1] == 'info') {
            $response["active"] = true;
            $response["username"] = $player->name;
            $response["banned"] = false;
            $response["role"] = (isset($player->role) ? intval($player->role) : 0);
            $response["gold"] = true;
        }
    }
    elseif($url[1] == 'token') {
        $response["playerTokenStatus"] = true;
    }
    elseif($url[1] == 'info') {
            $response["active"] = true;
            $response["username"] = $url[0];
            $response["banned"] = false;
            $response["role"] = 0;
            $response["gold"] = false;
    }
}

// if  (!isset($response)) $response = $_GET['q'];

if(isset($response)) echo json_encode($response, JSON_PRETTY_PRINT);

$h = fopen('/var/www/kag/logtestowy.txt', 'a');
fwrite($h, $_SERVER['REMOTE_ADDR'].': '.json_encode($response, JSON_PRETTY_PRINT)."\n");
fclose($h);