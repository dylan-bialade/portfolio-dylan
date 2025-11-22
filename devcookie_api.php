<?php
// devcookie_api.php
session_start();

// Connexion PDO (utilise ton config/db.php qui lit .env)
require __DIR__ . '/config/db.php';

header('Content-Type: application/json; charset=utf-8');

// Petite fonction de réponse JSON
function json_response(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// État par défaut côté serveur (au cas où)
function devcookie_default_state(): array {
    return [
        'lines'           => 0,
        'totalLines'      => 0,
        'manualClicks'    => 0,
        'upgrades'        => new stdClass(), // sera complété côté front
        'prestigePoints'  => 0,
        'prestigeUpgrades'=> new stdClass(), // idem
    ];
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

$userId   = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['pseudo'] ?? null;

// --------- ACTION: LOAD ---------
if ($action === 'load') {
    // Invité : on renvoie juste un état par défaut, sans sauvegarde serveur
    if (!$userId) {
        json_response([
            'ok'        => true,
            'loggedIn'  => false,
            'canSave'   => false,
            'state'     => devcookie_default_state(),
            'pseudo'    => null,
        ]);
    }

    // Joueur connecté : on charge depuis la base
    try {
        $stmt = $pdo->prepare('SELECT state_json, total_lines, prestige_points FROM devcookie_profiles WHERE user_id = ?');
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
        // Première fois : on crée une ligne avec l’état par défaut
        $stateArr = devcookie_default_state();
        $stateJson = json_encode($stateArr);

        try {
            $insert = $pdo->prepare('INSERT INTO devcookie_profiles (user_id, state_json, total_lines, prestige_points) VALUES (?, ?, 0, 0)');
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
        $stateJson = $row['state_json'];
        $decoded   = json_decode($stateJson, true);

        if (!is_array($decoded)) {
            $state = devcookie_default_state();
        } else {
            $state = $decoded;
        }

        // On synchronise éventuellement prestigePoints depuis la colonne
        if (!isset($state['prestigePoints'])) {
            $state['prestigePoints'] = (int)($row['prestige_points'] ?? 0);
        }
    }

    json_response([
        'ok'        => true,
        'loggedIn'  => true,
        'canSave'   => true,
        'state'     => $state,
        'pseudo'    => $userName,
    ]);
}

// --------- ACTION: SAVE ---------
if ($action === 'save' && $method === 'POST') {
    // Si pas connecté, on ne sauvegarde pas
    if (!$userId) {
        // On ne considère pas ça comme une "erreur" côté front, juste canSave=false
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

    // On récupère totalLines / prestigePoints côté serveur pour le classement
    $totalLines     = isset($state['totalLines']) ? (int)$state['totalLines'] : 0;
    $prestigePoints = isset($state['prestigePoints']) ? (int)$state['prestigePoints'] : 0;

    $stateJson = json_encode($state);

    try {
        $stmt = $pdo->prepare('UPDATE devcookie_profiles SET state_json = ?, total_lines = ?, prestige_points = ? WHERE user_id = ?');
        $stmt->execute([$stateJson, $totalLines, $prestigePoints, $userId]);
    } catch (PDOException $e) {
        json_response([
            'ok'      => false,
            'error'   => 'Erreur SQL (save)',
            'details' => $e->getMessage(),
        ], 500);
    }

    json_response([
        'ok'        => true,
        'canSave'   => true,
        'savedAt'   => date('c'),
    ]);
}

// --------- ACTION: LEADERBOARD ---------
if ($action === 'leaderboard') {
    try {
        $stmt = $pdo->query(
            'SELECT u.pseudo, p.total_lines, p.prestige_points
             FROM devcookie_profiles p
             JOIN users u ON u.id = p.user_id
             WHERE p.total_lines > 0
             ORDER BY p.total_lines DESC
             LIMIT 10'
        );

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
            'totalLines'     => (int)$r['total_lines'],
            'prestigePoints' => (int)$r['prestige_points'],
        ];
    }

    json_response([
        'ok'       => true,
        'items'    => $items,
    ]);
}

// --------- ACTION inconnu ---------
json_response([
    'ok'    => false,
    'error' => 'Action inconnue',
], 400);
