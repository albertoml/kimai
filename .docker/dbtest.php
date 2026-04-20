<?php
$DATABASE_HOST = urldecode($argv[1]);
$DATABASE_BASE = urldecode($argv[2]);
$DATABASE_PORT = $argv[3];
$DATABASE_USER = urldecode($argv[4]);
$DATABASE_PASS = urldecode($argv[5]);
$DATABASE_DRIVER = isset($argv[6]) ? $argv[6] : 'mysql';

echo "Testing DB:";

try {
    $pdo = new \PDO("$DATABASE_DRIVER:host=$DATABASE_HOST;dbname=$DATABASE_BASE;port=$DATABASE_PORT", "$DATABASE_USER", "$DATABASE_PASS", [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
    ]);
    echo "Connected\n";
} catch(\Exception $ex) {
    $message = $ex->getMessage();

    // Database does not exist - Kimai will create it, so treat as success
    $dbNotFound =
        // MySQL: Unknown database (1049)
        $ex->getCode() == 1049 ||
        stripos($message, 'Unknown database') !== false ||
        stripos($message, 'SQLSTATE[HY000] [1049]') !== false ||
        // PostgreSQL: database "xyz" does not exist
        (stripos($message, 'does not exist') !== false && stripos($message, 'database') !== false) ||
        stripos($message, 'SQLSTATE[3D000]') !== false;

    if ($dbNotFound) {
        echo "Database not found (will be created)\n";
        exit(0);
    }

    // Access denied / authentication failure - stop retrying immediately
    $accessDenied =
        $ex->getCode() == 1045 ||
        stripos($message, 'Access denied') !== false ||
        stripos($message, 'SQLSTATE[28000]') !== false ||
        // PostgreSQL password auth failure
        stripos($message, 'password authentication failed') !== false ||
        stripos($message, 'SQLSTATE[28P01]') !== false;

    if ($accessDenied) {
        echo 'Access denied: ' . $message;
        die(1);
    }

    // All other errors (connection timeout, refused, etc.) - caller will retry
    echo $message;
    die(7);
}
