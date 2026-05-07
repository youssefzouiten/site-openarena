<?php
require 'config.php';
$hash = password_hash('admin123',PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE joueurs SET password=? WHERE pseudo='Admin'");
$stmt->execute([$hash]);
echo "MDP mis a jour !";
?>
