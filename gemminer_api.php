<?php
// gemminer_api.php
session_start();

// Connexion PDO (config/db.php lit ton .env et fournit $pdo)
require __DIR__ . '/config/db.php';

header('Content-Type: application/json; charset=utf-8');

function json_response(array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// État par défaut côté serveur
function gemminer_default_state(): array {
    return [
        'gems'            => 0,
        'totalGems'       => 0,
        'currentOre'      => [
            'tier'  => 0,
            'hp'    => 100,
            'maxHp' => 100,
            'name'  => 'Quartz terne'
        ],
        'miningUpgrades'    => new stdClass(),
        'logisticsUpgrades' => new stdClass(),
        'salesUpgrades'     => new stdClass(),
        'prestigePoints'    => 0,
        'prestigeUpgrades'  => new stdClass(),
        'stats'             => [
            'minedPerSec'       => 0,
            'transportedPerSec' => 0,
            'soldPerSec'        => 0
        ],
        'createdAt'         => date('c'),
    ];
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

$userId = $_SESSION['user_id'] ?? null;
$pseudo = $_SESSION['pseudo'] ?? null;

// ===================== LOAD =====================
if ($action === 'load') {
    if (!$userId) {
        // Invité : pas de sauvegarde serveur
        json_response([
            'ok'        => true,
            'loggedIn'  => false,
            'canSave'   => false,
            'state'     => gemminer_default_state(),
            'pseudo'    => null,
        ]);
    }

    try {
        $stmt = $pdo->prepare('SELECT state_json, total_gems, prestige_points FROM gemminer_profiles WHERE user_id = ?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
    } catch (PDOException $e) {
        json_response([
            'ok'      => false,
            'error'   => 'Erreur SQL (load)',
            'details' => $e->getMessage(),
        ], 500);
    }

    if (!$row) {
        // Première fois qu’il lance le jeu
        $stateArr  = gemminer_default_state();
        $stateJson = json_encode($stateArr);

        try {
            $insert = $pdo->prepare('INSERT INTO gemminer_profiles (user_id, state_json, total_gems, prestige_points) VALUES (?, ?, 0, 0)');
            $insert->execute([$userId, $stateJson]);
        } catch (PDOException $e) {
            json_response([
                'ok'      => false,
                'error'   => 'Erreur SQL (insert default)',
                'details' => $e->getMessage(),
            ], 500);
        }

        $state = $stateArr;
    } else {
        $decoded = json_decode($row['state_json'], true);
        if (!is_array($decoded)) {
            $state = gemminer_default_state();
        } else {
            $state = $decoded;
            // Sync des champs au cas où
            if (!isset($state['totalGems'])) {
                $state['totalGems'] = (int)($row['total_gems'] ?? 0);
            }
            if (!isset($state['prestigePoints'])) {
                $state['prestigePoints'] = (int)($row['prestige_points'] ?? 0);
            }
        }
    }

    json_response([
        'ok'        => true,
        'loggedIn'  => true,
        'canSave'   => true,
        'state'     => $state,
        'pseudo'    => $pseudo,
    ]);
}

// ===================== SAVE =====================
if ($action === 'save' && $method === 'POST') {
    if (!$userId) {
        json_response([
            'ok'      => false,
            'canSave' => false,
            'error'   => 'Utilisateur non connecté',
        ], 200);
    }

    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);

    if (!is_array($payload) || !isset($payload['state']) || !is_array($payload['state'])) {
        json_response([
            'ok'    => false,
            'error' => 'Payload invalide',
        ], 400);
    }

    $state = $payload['state'];

    $totalGems      = isset($state['totalGems']) ? (int)$state['totalGems'] : 0;
    $prestigePoints = isset($state['prestigePoints']) ? (int)$state['prestigePoints'] : 0;

    $stateJson = json_encode($state);

    try {
        $stmt = $pdo->prepare('UPDATE gemminer_profiles SET state_json = ?, total_gems = ?, prestige_points = ? WHERE user_id = ?');
        $stmt->execute([$stateJson, $totalGems, $prestigePoints, $userId]);
    } catch (PDOException $e) {
        json_response([
            'ok'      => false,
            'error'   => 'Erreur SQL (save)',
            'details' => $e->getMessage(),
        ], 500);
    }

    json_response([
        'ok'      => true,
        'canSave' => true,
        'savedAt' => date('c'),
    ]);
}

// ===================== LEADERBOARD =====================
// Classement équitable : score = total_gems / jours_actifs
// => nouveaux joueurs peuvent rattraper, anciens récompensés sur la durée
if ($action === 'leaderboard') {
    try {
        $sql = "
            SELECT
              u.pseudo,
              p.total_gems,
              p.prestige_points,
              GREATEST(1, DATEDIFF(NOW(), p.first_play_at) + 1) AS days_active,
              (p.total_gems / GREATEST(1, DATEDIFF(NOW(), p.first_play_at) + 1)) AS score
            FROM gemminer_profiles p
            JOIN users u ON u.id = p.user_id
            WHERE p.total_gems > 0
            ORDER BY score DESC
            LIMIT 10
        ";
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll();
    } catch (PDOException $e) {
        json_response([
            'ok'      => false,
            'error'   => 'Erreur SQL (leaderboard)',
            'details' => $e->getMessage(),
        ], 500);
    }

    $items = [];
    foreach ($rows as $r) {
        $items[] = [
            'pseudo'         => $r['pseudo'],
            'totalGems'      => (int)$r['total_gems'],
            'prestigePoints' => (int)$r['prestige_points'],
            'daysActive'     => (int)$r['days_active'],
            'score'          => (float)$r['score'],
        ];
    }

    json_response([
        'ok'    => true,
        'items' => $items,
    ]);
}

// ===================== Action inconnue =====================
json_response([
    'ok'    => false,
    'error' => 'Action inconnue',
], 400);
