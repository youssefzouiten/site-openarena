<?php
$host = 'localhost';
$dbname = 'openarena';
$user = 'openarena';
$pass = 'OaPassword123!';

try {
    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch(PDOException $e) {
    http_response_code(500);
    die(json_encode(['error' => $e->getMessage()]));
}
?>
