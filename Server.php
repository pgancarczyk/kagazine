<?php

class Server {
	
	public $ID;
	public $name;
	public $description;
	public $IP;
	public $port;
	public $gamemode;
	private $hash;
	private $lastChange;
	
	public static function createByID($serverID, PDO $dbHandler) {
		$instance = new self;
		$instance->ID = $serverID;
		$row = $dbHandler->query("select * from servers where ID = '$serverID'");
		if($row->rowCount() > 0) $row = $row->fetch();
		else return false;
		$instance->name = str_replace('"', "'", $row['name']);
		$instance->description = str_replace("\n", "<br>", str_replace('"', "'", $row['description']));
		$instance->IP = $row['IP'];
		$instance->country = $row['country'];
		$instance->lat = $row['lat'];
		$instance->lon = $row['lon'];
		$instance->port = $row['port'];
		$instance->gamemode = str_replace('"', "'", $row['gamemode']);
		$instance->hash = $row['hash'];
		$instance->lastChange = $row['lastChange'];
		return $instance;
	}
	
	public function getA() {
		return '<a onclick="sPage(event)" class=sName data-serverid='.$this->ID.' href="/s/'.$this->ID.'">'.$this->name."</a>";
	}
	
	public function getMapUrl() {
		return "https://api.kag2d.com/server/ip/".$this->IP."/port/".$this->port."/minimap";
	}

}