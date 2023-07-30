<?php

return [
    'driver'   => 'pdo_pgsql',
    'host'     => $_ENV["DB_HOST"],
    'user'     => $_ENV["DB_USER"],
    'password' => $_ENV["DB_PASSWORD"],
    'dbname'   => $_ENV["DB_NAME"]
];
