<?php
require_once __DIR__ . '/vendor/autoload.php';

// Determine the session name dynamically based on the URL or script path
$sessionName = 'default_session'; // Default session name if no match is found

if (strpos($_SERVER['REQUEST_URI'], '/rssi-student/') !== false) {
    $sessionName = 'rssi_student_session';
} elseif (strpos($_SERVER['REQUEST_URI'], '/rssi-member/') !== false) {
    $sessionName = 'rssi_member_session';
} elseif (strpos($_SERVER['REQUEST_URI'], '/tap/') !== false) {
    $sessionName = 'tap_session';
}

// Set the session name
session_name($sessionName);
session_start(); // Start the session after assigning the name
date_default_timezone_set('Asia/Kolkata');

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;

$config = ORMSetup::createAnnotationMetadataConfiguration(
    paths: array(__DIR__ . "/models"),
    isDevMode: true,
);

// database configuration parameters
$dbParams = array(
    'driver'   => 'pdo_pgsql',
    'host'     => $_ENV["DB_HOST"],
    'user'     => $_ENV["DB_USER"],
    'password' => $_ENV["DB_PASSWORD"],
    'dbname'   => $_ENV["DB_NAME"],
);
// obtaining the entity manager
$entityManager = EntityManager::create($dbParams, $config);


// legacy db connection object
$servername = $_ENV["DB_HOST"];
$username = $_ENV["DB_USER"];
$password = $_ENV["DB_PASSWORD"];
$dbname = $_ENV["DB_NAME"];
$connection_string = "host = $servername user = $username password = $password dbname = $dbname";
$con = pg_connect($connection_string);

pg_query($con, "SET timezone TO 'Asia/Calcutta'");
