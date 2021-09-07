<?php

class Player {
	
	public $ID;
	public $name;
	public $gold;
	public $banned;
	public $registered;
	public $role;
	private $IP;
	public $country;
	private $lat;
	private $lon;
	public $forumID;
	public $avatarID;
	public $avatarExt;
	private $lastChange;
	public $shownNickname;
	public $bgURL;
	public $hideMe;
	
	protected static $urlInfo = "https://api.kag2d.com/player/";
	protected static $infoSufix = "/info";
	
	public static function createByID($playerID, PDO $dbHandler) {
		$instance = new self;
		if ($playerID == 6365) $playerID = 217;
		elseif ($playerID == 217) $playerID = 6365;
		$instance->ID = $playerID;
		$row = $dbHandler->query("select * from players where ID = '$playerID'")->fetch();
		$instance->name = $row['playerName'];
		if (strtolower($row['playerName']) == "pldragon") $instance->name = "PLDrag0N";
		elseif (strtolower($row['playerName']) == "pldrag0n") $instance->name = "PLDragON";
		$instance->gold = $row['gold'];
		$instance->banned = $row['banned'];
		$instance->registered = $row['registered'];
		$instance->role = $row['role'];
		$instance->IP = $row['IP'];
		$instance->country = $row['country'];
		$instance->lat = $row['lat'];
		$instance->lon = $row['lon'];
		$instance->forumID = $row['forumID'];
		$instance->avatarID = $row['avatarID'];
		$instance->avatarExt = $row['avatarExt'];
		$instance->lastChange = $row['lastChange'];
		$instance->shownNickname = $row['shownNickname'];
		$instance->bgURL = $row['bgURL'];
		$instance->hideMe = $row['hideMe'];
		$instance->password = $row['password'];
		return $instance;
	}
	
	public static function createByName($playerName, PDO $dbHandler) {
		$instance = new self;
		if (strtolower($playerName) == "pldrag0n") $playerName = "pldragon";
		elseif (strtolower($playerName) == "pldragon") $playerName = "pldrag0n";
		$row = $dbHandler->query("select password, shownNickname, bgURL, hideMe, playerName, ID, gold, banned, registered, role, IP, country, lat, lon, forumID, avatarID, avatarExt, unix_timestamp(lastChange) as lastChange from players where playerName = '$playerName'")->fetch();
		if ($row['lastChange']) {
			if ((intval($row['lastChange']) + (24*60*60)) < time() ) {
				$contents = Page::curlGet(self::$urlInfo.$playerName.self::$infoSufix);
				$playerInfo = json_decode($contents, true);
				if ($playerInfo['gold']) $playerGold = "true"; else $playerGold = "false";
				if ($playerInfo['banned']) $playerBanned = "true"; else $playerBanned = "false";
				$contents = Page::curlGet("https://api.kag2d.com/player/".$playerName."/avatar/l");
				$playerAvatar = json_decode($contents, true);
				$forumID = 'NULL';
				$avatarID = 'NULL';
				$avatarExt = 'NULL';
				if(isset($playerAvatar['large'])) {
					$playerAvatar = explode("/", $playerAvatar['large']);
					$forumID = "'".(explode(".", $playerAvatar[7])[0])."'";
					$avatarID = "'".$playerAvatar[6]."'";
					$avatarExt = "'".(explode(".", $playerAvatar[7])[1])."'";
				}
				$queryString = "update players set gold = '".$playerGold."', banned = '".$playerBanned."', registered = from_unixtime(".$playerInfo['regUnixTime'].", '%Y-%m-%d'), role = ".$playerInfo['role'].", avatarID = ".$avatarID.", forumID = ".$forumID.", avatarExt = ".$avatarExt.", lastChange = from_unixtime(".time().") where playerName = '".$playerName."'";
				$dbHandler->query($queryString);
				$row['playerGold'] = $playerGold;
				$row['banned'] = $playerBanned;
				$row['role'] = $row['role'];
				$row['avatarID'] = $avatarID;
				$row['forumID'] = $forumID;
				$row['avatarExt'] = $avatarExt;
				$row['registered'] = date("Y-m-d", $playerInfo['regUnixTime']);
				$row['lastChange'] = date("Y-m-d H:i:s", time());
			}
		}
		else {
			$contents = Page::curlGet(self::$urlInfo.$playerName.self::$infoSufix);
			$playerInfo = json_decode($contents, true);
			if (!isset($playerInfo['statusMessage']) and @$playerInfo['message'] !== "Player not found") {
				if ($playerInfo['gold']) $playerGold = "true"; else $playerGold = "false";
				if ($playerInfo['banned']) $playerBanned = "true"; else $playerBanned = "false";
				$contents = Page::curlGet("https://api.kag2d.com/player/".$playerName."/avatar/l");
				$playerAvatar = json_decode($contents, true);
				if(@$playerAvatar['large']) {
					$playerAvatar = explode("/", $playerAvatar['large']);
					$forumID = "'".(explode(".", $playerAvatar[7])[0])."'";
					$avatarID = "'".$playerAvatar[6]."'";
					$avatarExt = "'".(explode(".", $playerAvatar[7])[1])."'";
				}
				else {
					$forumID = 'NULL';
					$avatarID = 'NULL';
					$avatarExt = 'NULL';
				}			
				$queryString = "insert into players values ( NULL, '".$playerName."', '".$playerGold."', '".$playerBanned."', from_unixtime(".$playerInfo['regUnixTime'].", '%Y-%m-%d'), ".$playerInfo['role'].", NULL, NULL, NULL, NULL, ".$forumID.", ".$avatarID.", ".$avatarExt.", NULL, NULL, NULL, NULL, NULL)";
				$dbHandler->query($queryString);
				$row = $dbHandler->query("select shownNickname, bgURL, hideMe, playerName, ID, gold, banned, registered, role, IP, country, lat, lon, forumID, avatarID, avatarExt, unix_timestamp(lastChange) as lastChange from players where playerName = '$playerName'")->fetch();
			}
			else return false;
		}				
		$instance->name = $row['playerName'];
		if (strtolower($row['playerName']) == "pldragon") $instance->name = "PLDrag0N";
		elseif (strtolower($row['playerName']) == "pldrag0n") $instance->name = "PLDragON";
		$instance->ID = $row['ID'];
		$instance->gold = $row['gold'];
		$instance->banned = $row['banned'];
		$instance->registered = $row['registered'];
		$instance->role = $row['role'];
		$instance->IP = $row['IP'];
		$instance->country = $row['country'];
		$instance->lat = $row['lat'];
		$instance->lon = $row['lon'];
		$instance->forumID = $row['forumID'];
		$instance->avatarID = $row['avatarID'];
		$instance->avatarExt = $row['avatarExt'];
		$instance->lastChange = $row['lastChange'];
		$instance->shownNickname = $row['shownNickname'];
		$instance->bgURL = $row['bgURL'];
		$instance->hideMe = $row['hideMe'];
		$instance->password = $row['password'];
		return $instance;
	}
	
	public function getClass() {
		$class = '';
		if ($this->gold == 'true') $class = 'gold';
		if ($this->role == 2) $class = 'green';
		if ($this->role == 1) $class = 'purple';
		if ($this->role == 4) $class = 'purple';
		if ($this->banned == 'true') $class = 'red';
		return ($class != '' ? ' '.$class.'' : '' );
	}
	
	public function getA() {
		return '<a onclick="pPage(event)" title="'.$this->name.'" class="pName '.$this->getClass().'" href="/p/'.$this->name.'">'.($this->shownNickname ? $this->shownNickname : $this->name)."</a>";
	}

}