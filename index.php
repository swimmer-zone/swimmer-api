<?php

header('Content-Type: application/json');

require_once('controllers/api.php');
require_once('models/interface.php');
require_once('models/abstract.php');
require_once('models/blog.php');
require_once('models/link.php');
require_once('models/template.php');
require_once('models/track.php');
require_once('models/website.php');
require_once('utils/config.php');
require_once('utils/getid3/getid3.php');


$api = new \Swimmer\Controllers\Api(API_URL . $_SERVER['REQUEST_URI'], $_SERVER['HTTP_REFERER'] ?? null);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo json_encode($api->post($_POST));
}
else {
    echo $api->get();
}