<?php
require_once __DIR__ . '/vendor/autoload.php';

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
