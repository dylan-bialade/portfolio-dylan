<?php
// me_decouvrir.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';

// 1) Doit être connecté
if (empty($_SESSION['user_id'])) {
    header('Location: /auth/login.php?redirect=/me_decouvrir.php');
    exit;
}

// 2) Doit avoir le droit de streamer
$stmt = $pdo->prepare('SELECT can_stream_live, pseudo FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$_SESSION['user_id']]);
$u = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$u || (int)($u['can_stream_live'] ?? 0) !== 1) {
    http_response_code(403);
    echo "Accès refusé.";
    exit;
}

$pseudo = htmlspecialchars($u['pseudo'] ?? 'Utilisateur', ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Me découvrir</title>

    <!-- (Optionnel) ton CSS global si tu en as un -->
    <!-- <link rel="stylesheet" href="/assets/css/style.css"> -->

    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin:0; }
        main { max-width: 900px; margin: 0 auto; padding: 24px; }
        .card { border: 1px solid rgba(0,0,0,.12); border-radius: 16px; padding: 18px; }
        .btn  { padding: 12px 16px; border: 0; border-radius: 12px; cursor: pointer; }
        .muted{ opacity: .75; }
        #discoverMsg { margin-top: 12px; }
    </style>
</head>
<body>

<?php
// Si tu utilises tes partials, décommente ces lignes:
// require_once __DIR__ . '/partials/header.php';
?>

<main>
    <div class="card">
        <h1>Me découvrir</h1>
        <p class="muted">Bonjour <?= $pseudo ?>. Clique pour activer l’expérience.</p>

        <button id="btnDiscover" class="btn">Me découvrir</button>
        <p id="discoverMsg"></p>
    </div>
</main>

<?php
// Si tu utilises tes partials, décommente :
// require_once __DIR__ . '/partials/footer.php';
?>

<script>
/**
 * Important :
 * - On force le live à démarrer UNIQUEMENT après clic.
 * - Donc on enlève le flag d’auto-start ici.
 */
try { localStorage.removeItem("live_sender_enabled"); } catch(e) {}

function ensureLiveSenderLoaded() {
  if (window.LiveSender && typeof window.LiveSender.startFromUserGesture === "function") {
    return Promise.resolve();
  }
  return new Promise((resolve, reject) => {
    const s = document.createElement("script");
    s.src = "/assets/live/live_autosender.js?v=1";
    s.onload = () => resolve();
    s.onerror = () => reject(new Error("Impossible de charger live_autosender.js"));
    document.head.appendChild(s);
  });
}

async function enableAutostreamInDb() {
  const r = await fetch("/live_api.php?action=enable", {
    method: "POST",
    credentials: "same-origin",
    headers: {"Content-Type": "application/x-www-form-urlencoded"},
    body: "label="
  });
  return r.json();
}

document.getElementById("btnDiscover").addEventListener("click", async () => {
  const btn = document.getElementById("btnDiscover");
  const msg = document.getElementById("discoverMsg");

  btn.disabled = true;
  msg.textContent = "Activation en cours...";

  try {
    
    await ensureLiveSenderLoaded();

    
    const en = await enableAutostreamInDb();
    if (!en || en.ok !== true) {
      msg.textContent = "Erreur serveur (enable). Vérifie live_api.php / permissions.";
      btn.disabled = false;
      return;
    }

    
    const ok = await window.LiveSender.startFromUserGesture();
    if (!ok) {
      msg.textContent = "Impossible d’activer la caméra/micro. Vérifie les permissions navigateur.";
      btn.disabled = false;
      return;
    }

    
    await new Promise(r => setTimeout(r, 200));

    window.location.href = "/index.php";
  } catch (e) {
    console.error(e);
    msg.textContent = "Erreur : " + (e?.message || "inconnue");
    btn.disabled = false;
  }
});
</script>

</body>
</html>
