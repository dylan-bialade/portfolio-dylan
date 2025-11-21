<?php
// devcookie_api.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error'   => 'not_logged_in',
    ]);
    exit;
}

require __DIR__ . '/config/db.php';

$userId = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'load') {
    $stmt = $pdo->prepare('SELECT state_json FROM devcookie_profiles WHERE user_id = :uid');
    $stmt->execute([':uid' => $userId]);
    $row = $stmt->fetch();

    if ($row) {
        echo json_encode([
            'success' => true,
            'state'   => json_decode($row['state_json'], true),
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'state'   => null, // aucune sauvegarde encore
        ]);
    }
    exit;
}

if ($action === 'save') {
    $stateJson = $_POST['state'] ?? '';

    if ($stateJson === '') {
        echo json_encode([
            'success' => false,
            'error'   => 'missing_state',
        ]);
        exit;
    }

    $stmt = $pdo->prepare('
        INSERT INTO devcookie_profiles (user_id, state_json)
        VALUES (:uid, :state)
        ON DUPLICATE KEY UPDATE state_json = VALUES(state_json), updated_at = CURRENT_TIMESTAMP
    ');

    $stmt->execute([
        ':uid'   => $userId,
        ':state' => $stateJson,
    ]);

    echo json_encode([
        'success' => true,
    ]);
    exit;
}

echo json_encode([
    'success' => false,
    'error'   => 'invalid_action',
]);
