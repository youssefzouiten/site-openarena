<?php
$host = '127.0.0.1';
$dbname = 'openarena';
$user = 'openarena_user';
$pass = 'OpenArena123!';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode([
        'success' => false,
        'message' => 'Erreur connexion BDD',
        'debug' => $e->getMessage()
    ]));
}
?>
