<?php
// live_signal.php — OVH mutualisé friendly (polling DB) + NO session lock

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$userId = $_SESSION['user_id'] ?? null;
// IMPORTANT: libère le verrou de session pour ne pas bloquer le site pendant le long-poll
session_write_close();

require_once __DIR__ . '/config/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

function json_out(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!$userId) json_out(['error' => 'not_authenticated'], 401);

function read_json_body(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function normalize_direction(string $dir): string {
    $dir = trim($dir);
    if ($dir === 'viewer_to_sender') return 'to_sender';
    if ($dir === 'sender_to_viewer') return 'to_viewer';
    return $dir;
}

$body = read_json_body();
$action = trim((string)($_GET['action'] ?? ($body['action'] ?? '')));
if ($action === 'poll') $action = 'receive';

$stmt = $pdo->prepare('SELECT id, can_view_live, can_stream_live, live_stream_key FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$u = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$u) json_out(['error' => 'user_not_found'], 401);

$canStream = (int)($u['can_stream_live'] ?? 0) === 1;
$canView   = (int)($u['can_view_live'] ?? 0) === 1;

if ($action === 'send') {
    $room      = trim((string)($body['room'] ?? ''));
    $fromRole  = trim((string)($body['fromRole'] ?? ''));
    $direction = normalize_direction((string)($body['direction'] ?? ''));
    $msgType   = trim((string)($body['msgType'] ?? ($body['msg_type'] ?? '')));
    $payload   = $body['payload'] ?? null;
    $viewerId  = trim((string)($body['viewerId'] ?? ($body['viewer_id'] ?? '')));

    if ($room === '' || $fromRole === '' || $direction === '' || $msgType === '') {
        json_out(['error' => 'bad_request'], 400);
    }

    if ($fromRole === 'sender') {
        if (!$canStream) json_out(['error' => 'no_permission'], 403);
        if (($u['live_stream_key'] ?? '') !== $room) json_out(['error' => 'room_mismatch'], 403);
        if ($direction !== 'to_viewer') json_out(['error' => 'bad_direction'], 400);
        if ($viewerId === '') json_out(['error' => 'viewer_id_required'], 400);
    } elseif ($fromRole === 'viewer') {
        if (!$canView) json_out(['error' => 'no_permission'], 403);
        if ($direction !== 'to_sender') json_out(['error' => 'bad_direction'], 400);
        if ($viewerId === '') json_out(['error' => 'viewer_id_required'], 400);
    } else {
        json_out(['error' => 'bad_role'], 400);
    }

    $payloadText = is_string($payload)
        ? $payload
        : json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($payloadText === null) $payloadText = '{}';

    $stmt = $pdo->prepare('
        INSERT INTO live_signals (room, viewer_id, direction, msg_type, payload)
        VALUES (:room, :viewer_id, :direction, :msg_type, :payload)
    ');
    $stmt->execute([
        ':room' => $room,
        ':viewer_id' => $viewerId,
        ':direction' => $direction,
        ':msg_type' => $msgType,
        ':payload' => $payloadText
    ]);

    json_out(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
}

if ($action === 'receive') {
    $room     = trim((string)($_GET['room'] ?? ($body['room'] ?? '')));
    $role     = trim((string)($_GET['role'] ?? ($body['role'] ?? ''))); // sender|viewer
    $viewerId = trim((string)($_GET['viewerId'] ?? ($body['viewerId'] ?? '')));
    $sinceId  = (int)($_GET['since_id'] ?? ($body['since_id'] ?? ($body['sinceId'] ?? 0)));
    $timeoutMs = (int)($_GET['timeout'] ?? ($body['timeout'] ?? 15000));
    if ($timeoutMs < 1000) $timeoutMs = 1000;
    if ($timeoutMs > 20000) $timeoutMs = 20000;

    if ($room === '' || $role === '') json_out(['error' => 'bad_request'], 400);

    if ($role === 'sender') {
        if (!$canStream) json_out(['error' => 'no_permission'], 403);
        if (($u['live_stream_key'] ?? '') !== $room) json_out(['error' => 'room_mismatch'], 403);
        $direction = 'to_sender';
    } elseif ($role === 'viewer') {
        if (!$canView) json_out(['error' => 'no_permission'], 403);
        if ($viewerId === '') json_out(['error' => 'viewer_id_required'], 400);
        $direction = 'to_viewer';
    } else {
        json_out(['error' => 'bad_role'], 400);
    }

    $deadline = microtime(true) + ($timeoutMs / 1000.0);
    $messages = [];
    $lastId = $sinceId;

    while (microtime(true) < $deadline) {
        if ($role === 'sender') {
            $stmt = $pdo->prepare('
                SELECT id, msg_type, payload, viewer_id, created_at
                FROM live_signals
                WHERE room = :room AND direction = :direction AND id > :since
                ORDER BY id ASC
                LIMIT 50
            ');
            $stmt->execute([':room' => $room, ':direction' => $direction, ':since' => $sinceId]);
        } else {
            $stmt = $pdo->prepare('
                SELECT id, msg_type, payload, viewer_id, created_at
                FROM live_signals
                WHERE room = :room AND direction = :direction AND viewer_id = :viewer_id AND id > :since
                ORDER BY id ASC
                LIMIT 50
            ');
            $stmt->execute([':room' => $room, ':direction' => $direction, ':viewer_id' => $viewerId, ':since' => $sinceId]);
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            foreach ($rows as $r) {
                $payload = json_decode($r['payload'] ?? '{}', true);
                if (!is_array($payload)) $payload = ['raw' => $r['payload']];

                $messages[] = [
                    'id' => (int)$r['id'],
                    'msgType' => $r['msg_type'],
                    'msg_type' => $r['msg_type'],
                    'payload' => $payload,
                    'viewerId' => $r['viewer_id'] ?? null,
                    'createdAt' => $r['created_at'] ?? null
                ];
                $lastId = max($lastId, (int)$r['id']);
            }
            break;
        }

        usleep(200000);
    }

    json_out(['ok' => true, 'messages' => $messages, 'lastId' => $lastId]);
}

json_out(['error' => 'unknown_action'], 400);
