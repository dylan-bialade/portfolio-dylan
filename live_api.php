<?php
// live_api.php
// API JSON (same-origin) pour piloter le Live (sender) et lister les flux (receiver)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';

header('Content-Type: application/json; charset=utf-8');

function json_out(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function read_json_body(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function current_user(PDO $pdo): ?array {
    if (empty($_SESSION['user_id'])) return null;

    $stmt = $pdo->prepare('SELECT id, email, can_view_live, can_stream_live, live_autostream, live_stream_key, live_label FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);

    return $u ?: null;
}

function ensure_stream_key(PDO $pdo, array $u): string {
    $key = trim((string)($u['live_stream_key'] ?? ''));
    if ($key !== '') return $key;

    // clé courte, stable + suffix aléatoire
    $base = 'u' . (int)$u['id'];
    $key = $base . '-' . substr(bin2hex(random_bytes(4)), 0, 8);

    $stmt = $pdo->prepare('UPDATE users SET live_stream_key = :k WHERE id = :id');
    $stmt->execute([':k' => $key, ':id' => $u['id']]);

    return $key;
}

$action = $_GET['action'] ?? 'status';

// Alias pour compatibilité (sender headless)
if ($action === 'heartbeat_sender') {
    $action = 'heartbeat';
}

$body = read_json_body();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($body['action']) && is_string($body['action'])) {
    // si jamais tu postes action dans le JSON
    $action = $body['action'];
}

$u = current_user($pdo);

// Sender headless: init rapide (évite de dépendre d'un UI)
if ($action === 'bootstrap_sender') {
    if (!$u) {
        json_out(['ok' => false, 'error' => 'not_authenticated'], 401);
    }

    $key = ensure_stream_key($pdo, $u);
    $canStream = (int)($u['can_stream_live'] ?? 0) === 1;

    json_out([
        'ok' => true,
        'canStream' => $canStream,
        'streamKey' => $key,
        'label' => ($u['live_label'] ?? null)
    ]);
}

if ($action === 'status') {
    if (!$u) {
        json_out([
            'loggedIn' => false,
            'canStream' => false,
            'canView' => false,
            'liveAutostream' => false,
            'streamKey' => null,
            'label' => null
        ]);
    }

    $key = ensure_stream_key($pdo, $u);

    // Valeurs normalisées en session (utile pour header.php)
    $_SESSION['can_view_live']   = (int)($u['can_view_live'] ?? 0);
    $_SESSION['can_stream_live'] = (int)($u['can_stream_live'] ?? 0);
    $_SESSION['live_autostream'] = (int)($u['live_autostream'] ?? 0);
    $_SESSION['live_stream_key'] = $key;
    $_SESSION['live_label']      = $u['live_label'] ?? null;

    json_out([
        'loggedIn' => true,
        'canStream' => (int)($u['can_stream_live'] ?? 0) === 1,
        'canView' => (int)($u['can_view_live'] ?? 0) === 1,
        'liveAutostream' => (int)($u['live_autostream'] ?? 0) === 1,
        'streamKey' => $key,
        'label' => $u['live_label'] ?? null
    ]);
}

if ($action === 'enable') {
    if (!$u) json_out(['error' => 'not_authenticated'], 401);

    $canStream = (int)($u['can_stream_live'] ?? 0) === 1;
    if (!$canStream) json_out(['error' => 'no_permission'], 403);

    $key = ensure_stream_key($pdo, $u);

    $label = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $label = trim((string)($body['label'] ?? ($_POST['label'] ?? '')));
    }

    $stmt = $pdo->prepare('UPDATE users SET live_autostream = 1, live_label = :l WHERE id = :id');
    $stmt->execute([':l' => $label, ':id' => $u['id']]);

    $_SESSION['live_autostream'] = 1;
    $_SESSION['live_label']      = $label;

    json_out(['ok' => true, 'streamKey' => $key, 'label' => $label]);
}

if ($action === 'disable') {
    if (!$u) json_out(['error' => 'not_authenticated'], 401);

    $stmt = $pdo->prepare('UPDATE users SET live_autostream = 0 WHERE id = :id');
    $stmt->execute([':id' => $u['id']]);

    $_SESSION['live_autostream'] = 0;

    json_out(['ok' => true]);
}

if ($action === 'heartbeat') {
    if (!$u) json_out(['ok' => false, 'error' => 'not_authenticated'], 401);

    $key = ensure_stream_key($pdo, $u);
    $stmt = $pdo->prepare('UPDATE users SET live_last_seen = NOW() WHERE id = :id');
    $stmt->execute([':id' => $u['id']]);

    json_out(['ok' => true, 'streamKey' => $key]);
}

if ($action === 'list_streams') {
    if (!$u) json_out(['ok' => false, 'error' => 'not_authenticated'], 401);

    $canView = (int)($u['can_view_live'] ?? 0) === 1;
    if (!$canView) json_out(['ok' => false, 'error' => 'no_permission'], 403);

    // 20 sec: considéré "en ligne"
    $stmt = $pdo->query("
      SELECT id, live_stream_key AS streamKey, live_label AS label
      FROM users
      WHERE can_stream_live = 1
        AND live_autostream = 1
        AND live_stream_key IS NOT NULL
        AND live_stream_key <> ''
        AND live_last_seen >= (NOW() - INTERVAL 20 SECOND)
      ORDER BY id DESC
      LIMIT 50
    ");
    $streams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    json_out(['ok' => true, 'streams' => $streams]);
}

json_out(['error' => 'unknown_action'], 400);
