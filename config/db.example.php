<?php
// config/db.example.php


$dsn = 'mysql:host=localhost;dbname=VOTRE_BASE;charset=utf8mb4';
$dbUser = 'VOTRE_USER';
$dbPass = 'VOTRE_MOT_DE_PASSE';

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Erreur de connexion à la base.');
}
