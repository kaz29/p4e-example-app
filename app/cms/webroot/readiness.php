<?php

$dsn = sprintf("pgsql:dbname=%s host=%s port=%s",
    getenv('DB_NAME'),
    getenv('DB_HOST'),
    getenv('DB_PORT')
);

try {
    $pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASSWORD'));
    echo 'OK';
} catch (\Exception $e) {
    echo 'NG';
    header('HTTP', true, 500);
}
