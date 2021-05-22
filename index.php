<?php

use Swimmer\Controllers\Api;
use Swimmer\Cp\Controllers\Admin;
use Swimmer\Utils\Config;

header('Content-Type: application/json');
header('Access-Control-Expose-Headers: X-Total-Count');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Origin: *');

spl_autoload_register(function($class_name) {
	$class_name = strtolower(str_replace(['Swimmer\\', '\\'], ['', '/'], $class_name)) . '.php';
	if (file_exists($class_name)) {
		require($class_name);
	}
	else {
    	echo $class_name . '.php does not exist';
    }
});

if (strpos($_SERVER['REQUEST_URI'], '/cp') === false) {
	$api = new Api(Config::API_URL . $_SERVER['REQUEST_URI'], $_SERVER['HTTP_REFERER'] ?? null);
	echo $api->get();
}
else {
	$admin = new Admin(Config::API_URL . $_SERVER['REQUEST_URI'], $_SERVER['HTTP_REFERER'] ?? null);

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'POST':
		    echo json_encode($admin->post($_POST));
		    break;

		case 'PUT':
		    echo json_encode($admin->put($_POST));
		    break;

		default:
	    	echo $admin->get();
	    	break;
	}
}