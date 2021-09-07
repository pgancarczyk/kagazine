<?php
$url = explode('/', $_GET['q']);

$h = fopen('/var/www/kag/logtestowy.txt', 'a');
fwrite($h, $_SERVER['REMOTE_ADDR'].': '.json_encode($response, JSON_PRETTY_PRINT)."\n");
fclose($h);