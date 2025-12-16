<?php
// live_receiver.php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/config/db.php';

// Protection : connecté
if (empty($_SESSION['user_id'])) {
    header('Location: /auth/login.php?redirect=/live_receiver.php');
    exit;
}

// Protection : droit viewer
$stmt = $pdo->prepare('SELECT can_view_live FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$_SESSION['user_id']]);
$u = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$u || (int)($u['can_view_live'] ?? 0) !== 1) {
    http_response_code(403);
    echo "Accès refusé.";
    exit;
}

// IMPORTANT : désactive l’auto-sender sur cette page (évite boucles sur même poste)
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Live Receiver</title>

  <script>window.__LIVE_DISABLE_AUTOSENDER__ = true;</script>

  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin:0; }
    main { display:grid; grid-template-columns: 360px 1fr; gap:16px; padding:16px; }
    .panel { border:1px solid rgba(0,0,0,.12); border-radius:16px; padding:14px; }
    .streams { display:flex; flex-direction:column; gap:10px; }
    .live-stream-item { border:1px solid rgba(0,0,0,.10); border-radius:14px; padding:10px; }
    .live-stream-item .title { font-weight:700; margin-bottom:4px; }
    .live-stream-item .meta { opacity:.7; font-size: 13px; margin-bottom:8px; }
    .live-stream-item button { padding:10px 12px; border:0; border-radius:12px; cursor:pointer; }
    #receiverVideo { width:100%; max-height: 76vh; border-radius: 18px; background:#000; }
    .row { display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
    .btn { padding:10px 12px; border:0; border-radius:12px; cursor:pointer; }
    .muted { opacity:.7; }
    @media (max-width: 900px) { main { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
<main>
  <section class="panel">
    <div class="row">
      <button class="btn" id="btnRefreshStreams">Actualiser</button>
      <span class="muted" id="streamsStatus">—</span>
    </div>
    <div class="streams" id="streamsList" style="margin-top:12px;"></div>
  </section>

  <section class="panel">
    <div class="row">
      <button class="btn" id="btnLeave" disabled>Quitter</button>
      <button class="btn" id="btnUnmute" disabled>Activer le son</button>
      <span class="muted" id="watchStatus">Déconnecté.</span>
    </div>

    <div style="margin-top:12px;">
      <!-- IMPORTANT : pas de muted dans le HTML -->
      <video id="receiverVideo" autoplay playsinline></video>
    </div>
  </section>
</main>

<script>
  window.__LIVE_CONFIG__ = {
    apiUrl: "/live_api.php",
    signalUrl: "/live_signal.php"
  };
</script>
<script src="/assets/live/live_receiver.js?v=3"></script>
</body>
</html>
