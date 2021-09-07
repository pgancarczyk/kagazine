<?php

error_reporting(-1);
error_reporting(E_ALL|E_STRICT);
ini_set('display_errors', 'On');

require_once('Player.php');
require_once('Server.php');
require_once('Page.php');

Page::printHeader('kagazine', '', true);

?>
<style>
#header {
	display: none;
}
body {
	background: white;
	height: 100%;
}
#sidebar {
	display: none;
}
#shoutbox {
	display: block;
	width: 100%;
	height: 90%;
}
#content {
	position: initial;
	width: 100%;
	right: 0;
	height: initial;
	border: none;
	height: 100%;
}
#messages {
	height: 100%;
}
#input {
	width: 100%;
}
.msg {
	width: 100%;
}
</style>
<?php Page::printFooter(); ?>