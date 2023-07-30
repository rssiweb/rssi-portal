<?php
require_once __DIR__ . '/vendor/autoload.php';

session_start();
date_default_timezone_set('Asia/Kolkata');

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\DependencyFactory;

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
$servername=$_ENV["DB_HOST"];
$username=$_ENV["DB_USER"];
$password=$_ENV["DB_PASSWORD"];
$dbname=$_ENV["DB_NAME"];
$connection_string = "host = $servername user = $username password = $password dbname = $dbname";
$con = pg_connect ( $connection_string );

pg_query($con,"SET timezone TO 'Asia/Calcutta'");
