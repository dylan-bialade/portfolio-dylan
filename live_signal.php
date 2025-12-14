<?php
// live_signal.php
// Signalisation WebRTC via base de données (polling HTTP) — compatible OVH mutualisé.

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
    $stmt = $pdo->prepare('SELECT id, pseudo, can_view_live, can_stream_live, live_stream_key FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    return $u ?: null;
}

$action = $_GET['action'] ?? '';

$u = current_user($pdo);
if (!$u) json_out(['error' => 'not_authenticated'], 401);

$canStream = (int)($u['can_stream_live'] ?? 0) === 1;
$canView   = (int)($u['can_view_live'] ?? 0) === 1;

if ($action === 'send') {
    $body = read_json_body();
    $room = trim((string)($body['room'] ?? $_POST['room'] ?? ''));
    $fromRole = trim((string)($body['fromRole'] ?? $_POST['fromRole'] ?? ''));
    $direction = trim((string)($body['direction'] ?? $_POST['direction'] ?? '')); // to_sender | to_viewer
    $msgType = trim((string)($body['msgType'] ?? $_POST['msgType'] ?? '')); // offer|answer|ice|bye
    $payload = $body['payload'] ?? ($_POST['payload'] ?? null);
    $viewerId = trim((string)($body['viewerId'] ?? $_POST['viewerId'] ?? ''));

    if ($room === '' || $fromRole === '' || $direction === '' || $msgType === '') {
        json_out(['error' => 'bad_request'], 400);
    }

    // Permissions
    if ($fromRole === 'sender') {
        if (!$canStream) json_out(['error' => 'no_permission'], 403);
        if (($u['live_stream_key'] ?? '') !== $room) json_out(['error' => 'room_mismatch'], 403);
        if ($direction !== 'to_viewer') json_out(['error' => 'bad_direction'], 400);
    } elseif ($fromRole === 'viewer') {
        if (!$canView) json_out(['error' => 'no_permission'], 403);
        if ($direction !== 'to_sender') json_out(['error' => 'bad_direction'], 400);
        if ($viewerId === '') json_out(['error' => 'viewer_id_required'], 400);
    } else {
        json_out(['error' => 'bad_role'], 400);
    }

    // Payload en JSON (texte)
    $payloadText = is_string($payload) ? $payload : json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($payloadText === null) $payloadText = '{}';

    try {
        $stmt = $pdo->prepare('INSERT INTO live_signals (room, viewer_id, direction, msg_type, payload) VALUES (:room, :viewer_id, :direction, :msg_type, :payload)');
        $stmt->execute([
            ':room' => $room,
            ':viewer_id' => ($viewerId !== '' ? $viewerId : null),
            ':direction' => $direction,
            ':msg_type' => $msgType,
            ':payload' => $payloadText
        ]);
        json_out(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
    } catch (PDOException $e) {
        json_out(['error' => 'db_error'], 500);
    }
}

if ($action === 'receive') {
    $room = trim((string)($_GET['room'] ?? ''));
    $role = trim((string)($_GET['role'] ?? '')); // sender | viewer
    $sinceId = (int)($_GET['since_id'] ?? 0);
    $viewerId = trim((string)($_GET['viewerId'] ?? ''));

    // Long-poll max (ms) — garde une marge sous 30s pour OVH mutualisé.
    $timeoutMs = (int)($_GET['timeout'] ?? 15000);
    if ($timeoutMs < 1000) $timeoutMs = 1000;
    if ($timeoutMs > 20000) $timeoutMs = 20000;

    if ($room === '' || $role === '') json_out(['error' => 'bad_request'], 400);

    if ($role === 'sender') {
        if (!$canStream) json_out(['error' => 'no_permission'], 403);
        if (($u['live_stream_key'] ?? '') !== $room) json_out(['error' => 'room_mismatch'], 403);
        $direction = 'to_sender';
        // Le sender reçoit toutes les offres/ICE, et choisit côté JS quel viewer prendre.
    } elseif ($role === 'viewer') {
        if (!$canView) json_out(['error' => 'no_permission'], 403);
        $direction = 'to_viewer';
        if ($viewerId === '') json_out(['error' => 'viewer_id_required'], 400);
    } else {
        json_out(['error' => 'bad_role'], 400);
    }

    $deadline = microtime(true) + ($timeoutMs / 1000.0);
    $messages = [];
    $lastId = $sinceId;

    // Purge légère (1 fois par requête, sans bloquer)
    try {
        $pdo->exec("DELETE FROM live_signals WHERE created_at < (NOW() - INTERVAL 1 DAY)");
    } catch (Throwable $e) {
        // ignore
    }

    while (microtime(true) < $deadline) {
        try {
            if ($role === 'viewer') {
                $stmt = $pdo->prepare('SELECT id, msg_type, payload, viewer_id, created_at FROM live_signals WHERE room = :room AND direction = :direction AND viewer_id = :viewer_id AND id > :since ORDER BY id ASC LIMIT 50');
                $stmt->execute([
                    ':room' => $room,
                    ':direction' => $direction,
                    ':viewer_id' => $viewerId,
                    ':since' => $sinceId
                ]);
            } else {
                $stmt = $pdo->prepare('SELECT id, msg_type, payload, viewer_id, created_at FROM live_signals WHERE room = :room AND direction = :direction AND id > :since ORDER BY id ASC LIMIT 50');
                $stmt->execute([
                    ':room' => $room,
                    ':direction' => $direction,
                    ':since' => $sinceId
                ]);
            }

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($rows) {
                foreach ($rows as $r) {
                    $payload = json_decode($r['payload'] ?? '{}', true);
                    if (!is_array($payload)) $payload = ['raw' => $r['payload']];

                    $messages[] = [
                        'id' => (int)$r['id'],
                        'msgType' => $r['msg_type'],
                        'payload' => $payload,
                        'viewerId' => $r['viewer_id'] ?? null,
                        'createdAt' => $r['created_at'] ?? null
                    ];
                    $lastId = max($lastId, (int)$r['id']);
                }
                break;
            }
        } catch (PDOException $e) {
            json_out(['error' => 'db_error'], 500);
        }

        // attente courte (1 seconde) pour limiter la charge DB
        usleep(1000000);
    }

    json_out(['ok' => true, 'messages' => $messages, 'lastId' => $lastId]);
}

json_out(['error' => 'unknown_action'], 400);
