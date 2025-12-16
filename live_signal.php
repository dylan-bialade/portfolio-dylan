<?php
// live_signal.php
// Signalisation WebRTC via base de données (polling HTTP) — compatible OVH mutualisé.
// Robustesse:
// - action accepté en GET (?action=...) OU en JSON body {"action": "..."}
// - alias: poll -> receive
// - si room absent: fallback session (si possible)
// - côté viewer receive: récupère aussi les messages viewer_id IS NULL (si sender n'envoie pas viewerId)

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

function read_json_body_once(): array {
    static $cached = null;
    if ($cached !== null) return $cached;

    $raw = file_get_contents('php://input');
    if (!$raw) return $cached = [];
    $data = json_decode($raw, true);
    return $cached = (is_array($data) ? $data : []);
}

function current_user(PDO $pdo): ?array {
    if (empty($_SESSION['user_id'])) return null;
    $stmt = $pdo->prepare('SELECT id, pseudo, can_view_live, can_stream_live, live_stream_key FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    return $u ?: null;
}

function normalize_direction(string $dir): string {
    $dir = trim($dir);
    if ($dir === 'viewer_to_sender') return 'to_sender';
    if ($dir === 'sender_to_viewer') return 'to_viewer';
    return $dir;
}

$body = read_json_body_once();

$action = $_GET['action'] ?? ($body['action'] ?? '');
$action = trim((string)$action);
if ($action === 'poll') $action = 'receive';

$u = current_user($pdo);
if (!$u) json_out(['error' => 'not_authenticated'], 401);

$canStream = (int)($u['can_stream_live'] ?? 0) === 1;
$canView   = (int)($u['can_view_live'] ?? 0) === 1;

function fallback_room_from_session(): string {
    if (!empty($_SESSION['live_receiver_last_room'])) return (string)$_SESSION['live_receiver_last_room'];
    if (!empty($_SESSION['live_sender_last_room'])) return (string)$_SESSION['live_sender_last_room'];
    return '';
}

/**
 * SEND
 */
if ($action === 'send') {
    $room      = trim((string)($body['room'] ?? $_POST['room'] ?? ($body['streamKey'] ?? '')));
    $fromRole  = trim((string)($body['fromRole'] ?? $_POST['fromRole'] ?? ''));
    $direction = normalize_direction((string)($body['direction'] ?? $_POST['direction'] ?? ''));
    $msgType   = trim((string)($body['msgType'] ?? $_POST['msgType'] ?? ($body['msg_type'] ?? '')));
    $payload   = $body['payload'] ?? ($_POST['payload'] ?? null);
    $viewerId  = trim((string)($body['viewerId'] ?? $_POST['viewerId'] ?? ($body['viewer_id'] ?? '')));

    if ($room === '') {
        $room = fallback_room_from_session();
    }

    if ($room === '' || $fromRole === '' || $direction === '' || $msgType === '') {
        json_out(['error' => 'missing_fields', 'missing' => [
            'room' => ($room === ''),
            'fromRole' => ($fromRole === ''),
            'direction' => ($direction === ''),
            'msgType' => ($msgType === ''),
        ]], 400);
    }

    if ($fromRole === 'sender') {
        if (!$canStream) json_out(['error' => 'no_permission'], 403);
        if (($u['live_stream_key'] ?? '') !== $room) json_out(['error' => 'room_mismatch'], 403);
        if ($direction !== 'to_viewer') json_out(['error' => 'bad_direction'], 400);

        // viewerId optionnel (on supporte viewer_id NULL côté receive)
        $_SESSION['live_sender_last_room'] = $room;

    } elseif ($fromRole === 'viewer') {
        if (!$canView) json_out(['error' => 'no_permission'], 403);
        if ($direction !== 'to_sender') json_out(['error' => 'bad_direction'], 400);
        if ($viewerId === '') json_out(['error' => 'viewer_id_required'], 400);

        $_SESSION['live_receiver_last_room'] = $room;
        $_SESSION['live_receiver_last_viewer'] = $viewerId;

    } else {
        json_out(['error' => 'bad_role'], 400);
    }

    $payloadText = is_string($payload)
        ? $payload
        : json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($payloadText === null) $payloadText = '{}';

    try {
        $stmt = $pdo->prepare('
            INSERT INTO live_signals (room, viewer_id, direction, msg_type, payload)
            VALUES (:room, :viewer_id, :direction, :msg_type, :payload)
        ');
        $stmt->execute([
            ':room'      => $room,
            ':viewer_id' => ($viewerId !== '' ? $viewerId : null),
            ':direction' => $direction,
            ':msg_type'  => $msgType,
            ':payload'   => $payloadText
        ]);

        json_out(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
    } catch (PDOException $e) {
        json_out(['error' => 'db_error'], 500);
    }
}

/**
 * RECEIVE (polling)
 */
if ($action === 'receive') {
    $room     = trim((string)($_GET['room'] ?? ($body['room'] ?? ($body['streamKey'] ?? ''))));
    $role     = trim((string)($_GET['role'] ?? ($body['role'] ?? ''))); // sender|viewer (peut être vide)
    $viewerId = trim((string)($_GET['viewerId'] ?? ($body['viewerId'] ?? ($body['viewer_id'] ?? ''))));

    $sinceId = (int)($_GET['since_id'] ?? ($body['since_id'] ?? ($body['sinceId'] ?? 0)));

    $timeoutMs = (int)($_GET['timeout'] ?? ($body['timeout'] ?? 15000));
    if ($timeoutMs < 1000) $timeoutMs = 1000;
    if ($timeoutMs > 20000) $timeoutMs = 20000;

    if ($room === '') {
        $room = fallback_room_from_session();
    }

    // Déduction rôle si absent
    if ($role === '') {
        if ($viewerId !== '' && $canView) {
            $role = 'viewer';
        } elseif ($canStream && ($u['live_stream_key'] ?? '') === $room) {
            $role = 'sender';
        }
    }

    if ($room === '' || $role === '') {
        json_out(['error' => 'missing_room_or_role'], 400);
    }

    if ($role === 'sender') {
        if (!$canStream) json_out(['error' => 'no_permission'], 403);
        if (($u['live_stream_key'] ?? '') !== $room) json_out(['error' => 'room_mismatch'], 403);
        $direction = 'to_sender';
        $_SESSION['live_sender_last_room'] = $room;
    } elseif ($role === 'viewer') {
        if (!$canView) json_out(['error' => 'no_permission'], 403);
        if ($viewerId === '') {
            // fallback viewerId session si dispo
            if (!empty($_SESSION['live_receiver_last_viewer'])) {
                $viewerId = (string)$_SESSION['live_receiver_last_viewer'];
            } else {
                json_out(['error' => 'viewer_id_required'], 400);
            }
        }
        $direction = 'to_viewer';
        $_SESSION['live_receiver_last_room'] = $room;
        $_SESSION['live_receiver_last_viewer'] = $viewerId;
    } else {
        json_out(['error' => 'bad_role'], 400);
    }

    // Purge légère (best-effort)
    try {
        $pdo->exec("DELETE FROM live_signals WHERE created_at < (NOW() - INTERVAL 1 DAY)");
    } catch (Throwable $e) {
        // ignore
    }

    $deadline = microtime(true) + ($timeoutMs / 1000.0);
    $messages = [];
    $lastId = $sinceId;

    while (microtime(true) < $deadline) {
        try {
            if ($role === 'sender') {
                $stmt = $pdo->prepare('
                    SELECT id, msg_type, payload, viewer_id, created_at
                    FROM live_signals
                    WHERE room = :room AND direction = :direction AND id > :since
                    ORDER BY id ASC
                    LIMIT 50
                ');
                $stmt->execute([
                    ':room' => $room,
                    ':direction' => $direction,
                    ':since' => $sinceId
                ]);
            } else {
                // viewer: accepte viewer_id = viewerId OU NULL (si sender n'envoie pas viewerId)
                $stmt = $pdo->prepare('
                    SELECT id, msg_type, payload, viewer_id, created_at
                    FROM live_signals
                    WHERE room = :room AND direction = :direction
                      AND (viewer_id = :viewer_id OR viewer_id IS NULL)
                      AND id > :since
                    ORDER BY id ASC
                    LIMIT 50
                ');
                $stmt->execute([
                    ':room' => $room,
                    ':direction' => $direction,
                    ':viewer_id' => $viewerId,
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
                        'msg_type' => $r['msg_type'],
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

        usleep(200000); // 200ms
    }

    json_out([
        'ok' => true,
        'messages' => $messages,
        'lastId' => $lastId
    ]);
}

json_out(['error' => 'unknown_action'], 400);
