<?php

use Swimmer\Controllers\Api;
use Swimmer\Utils\Config;

header('Content-Type: application/json');

spl_autoload_register(function($class_name) {
	$class_name = strtolower(str_replace(['Swimmer\\', '\\'], ['', '/'], $class_name)) . '.php';
	if (file_exists($class_name)) {
		require($class_name);
	}
	else {
    	echo $class_name . '.php does not exist';
    }
});


$api = new Api(Config::API_URL . $_SERVER['REQUEST_URI'], $_SERVER['HTTP_REFERER'] ?? null);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo json_encode($api->post($_POST));
}
else {
    echo $api->get();
}
