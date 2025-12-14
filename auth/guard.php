<?php
// auth/guard.php
// Helpers d'authentification + permissions Live (viewer/sender)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

function require_login(): void {
    if (empty($_SESSION['user_id'])) {
        $redirect = $_SERVER['REQUEST_URI'] ?? '/index.php';
        header('Location: /auth/login.php?redirect=' . urlencode($redirect));
        exit;
    }
}

function hydrate_live_permissions(PDO $pdo): array {
    require_login();
    $stmt = $pdo->prepare('SELECT id, pseudo, can_view_live, can_stream_live, live_autostream, live_stream_key, live_label, live_last_seen FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$u) {
        http_response_code(403);
        exit('Accès refusé.');
    }

    // Normalise en session pour éviter des requêtes inutiles
    $_SESSION['pseudo'] = $_SESSION['pseudo'] ?? ($u['pseudo'] ?? '');
    $_SESSION['can_view_live']   = (int)($u['can_view_live'] ?? 0);
    $_SESSION['can_stream_live'] = (int)($u['can_stream_live'] ?? 0);
    $_SESSION['live_autostream'] = (int)($u['live_autostream'] ?? 0);
    $_SESSION['live_stream_key'] = $u['live_stream_key'] ?? null;
    $_SESSION['live_label']      = $u['live_label'] ?? null;

    return $u;
}

function require_live_view_permission(PDO $pdo): void {
    hydrate_live_permissions($pdo);
    if ((int)($_SESSION['can_view_live'] ?? 0) !== 1) {
        http_response_code(403);
        exit('Accès refusé (receiver).');
    }
}

function require_live_stream_permission(PDO $pdo): void {
    hydrate_live_permissions($pdo);
    if ((int)($_SESSION['can_stream_live'] ?? 0) !== 1) {
        http_response_code(403);
        exit('Accès refusé (sender).');
    }
}
