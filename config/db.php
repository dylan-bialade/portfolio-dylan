<?php
// config/db.php
// À adapter avec les infos de ta base OVH

$dsn = 'mysql:host=localhost;dbname=bialadcusergamme;charset=utf8mb4';
$dbUser = 'bialadcusergamme';
$dbPass = 'Bilou1978';

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    // En prod, évite d'afficher le message exact, mais pour le moment :
    die('Erreur de connexion à la base : ' . $e->getMessage());
}
