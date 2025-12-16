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

function current_user(PDO $pdo): ?array {
    if (empty($_SESSION['user_id'])) return null;
    $stmt = $pdo->prepare('SELECT id, pseudo, can_view_live, can_stream_live, live_autostream, live_stream_key, live_label, live_last_seen FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    return $u ?: null;
}

function ensure_stream_key(PDO $pdo, array $u): string {
    $key = $u['live_stream_key'] ?? '';
    if ($key !== '') return $key;

    $key = str_pad((string)$u['id'], 3, '0', STR_PAD_LEFT);

    // En cas d'énorme collision (très improbable), on ajoute un suffixe.
    // (utile si vous migrez des IDs)
    try {
        $stmt = $pdo->prepare('UPDATE users SET live_stream_key = :k WHERE id = :id');
        $stmt->execute([':k' => $key, ':id' => $u['id']]);
    } catch (PDOException $e) {
        $key = $key . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
        $stmt = $pdo->prepare('UPDATE users SET live_stream_key = :k WHERE id = :id');
        $stmt->execute([':k' => $key, ':id' => $u['id']]);
    }

    return $key;
}

$action = $_GET['action'] ?? '';

if ($action === 'status') {
    if (empty($_SESSION['user_id'])) {
        json_out([
            'loggedIn' => false,
            'canStream' => false,
            'canView' => false,
            'liveAutostream' => false,
            'streamKey' => null,
            'label' => null
        ]);
    }

    $u = current_user($pdo);
    if (!$u) json_out(['error' => 'user_not_found'], 401);

    $key = ensure_stream_key($pdo, $u);

    // Valeurs normalisées en session (utile pour header.php)
    $_SESSION['can_view_live']   = (int)($u['can_view_live'] ?? 0);
    $_SESSION['can_stream_live'] = (int)($u['can_stream_live'] ?? 0);
    $_SESSION['live_autostream'] = (int)($u['live_autostream'] ?? 0);
    $_SESSION['live_stream_key'] = $key;
    $_SESSION['live_label']      = $u['live_label'] ?? null;

    json_out([
        'loggedIn' => true,
        'userId' => (int)$u['id'],
        'pseudo' => $u['pseudo'] ?? null,
        'canStream' => (int)($u['can_stream_live'] ?? 0) === 1,
        'canView' => (int)($u['can_view_live'] ?? 0) === 1,
        'liveAutostream' => (int)($u['live_autostream'] ?? 0) === 1,
        'streamKey' => $key,
        'label' => $u['live_label'] ?? (($u['pseudo'] ?? 'User') . ' (' . $key . ')')
    ]);
}

$u = current_user($pdo);
$canStream = $u && (int)($u['can_stream_live'] ?? 0) === 1;
$canView   = $u && (int)($u['can_view_live'] ?? 0) === 1;

if ($action === 'enable') {
    if (!$canStream) json_out(['error' => 'no_permission'], 403);

    $key = ensure_stream_key($pdo, $u);

    $label = trim((string)($_POST['label'] ?? ''));
    if ($label === '') {
        $label = ($u['pseudo'] ?? 'User') . ' (' . $key . ')';
    }
    if (mb_strlen($label) > 64) {
        $label = mb_substr($label, 0, 64);
    }

    $stmt = $pdo->prepare('UPDATE users SET live_autostream = 1, live_label = :label, live_last_seen = NOW() WHERE id = :id');
    $stmt->execute([':label' => $label, ':id' => $u['id']]);

    $_SESSION['live_autostream'] = 1;
    $_SESSION['live_stream_key'] = $key;
    $_SESSION['live_label'] = $label;

    json_out(['ok' => true, 'streamKey' => $key, 'label' => $label]);
}

if ($action === 'disable') {
    if (!$canStream) json_out(['error' => 'no_permission'], 403);
    $stmt = $pdo->prepare('UPDATE users SET live_autostream = 0 WHERE id = :id');
    $stmt->execute([':id' => $u['id']]);
    $_SESSION['live_autostream'] = 0;
    json_out(['ok' => true]);
}

if ($action === 'heartbeat') {
    if (!$canStream) json_out(['error' => 'no_permission'], 403);
    $stmt = $pdo->prepare('UPDATE users SET live_last_seen = NOW() WHERE id = :id');
    $stmt->execute([':id' => $u['id']]);
    json_out(['ok' => true]);
}

if ($action === 'list_streams') {
    if (!$canView) json_out(['error' => 'no_permission'], 403);

    // Online = autostream ON + heartbeat récent (30s)
    $sql = "SELECT id, pseudo, live_stream_key, live_label, live_last_seen
FROM users
WHERE can_stream_live = 1
AND live_autostream = 1
AND live_last_seen IS NOT NULL
AND live_last_seen > (NOW() - INTERVAL 30 SECOND)
ORDER BY live_last_seen DESC
LIMIT 50";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $streams = array_map(function($r) {
        return [
            'userId' => (int)$r['id'],
            'pseudo' => $r['pseudo'] ?? null,
            'streamKey' => $r['live_stream_key'] ?? null,
            'label' => $r['live_label'] ?? (($r['pseudo'] ?? 'User') . ' (' . ($r['live_stream_key'] ?? '') . ')'),
            'lastSeen' => $r['live_last_seen'] ?? null
        ];
    }, $rows);

    json_out(['ok' => true, 'streams' => $streams]);
}

json_out(['error' => 'unknown_action'], 400);
