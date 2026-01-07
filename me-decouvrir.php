<script>
function ensureLiveSenderLoaded() {
  if (window.LiveSender && typeof window.LiveSender.startFromUserGesture === "function") {
    return Promise.resolve();
  }
  return new Promise((resolve, reject) => {
    const s = document.createElement("script");
    s.src = "/assets/live/live_autosender.js?v=2";
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

async function heartbeatNow() {
  try {
    await fetch("/live_api.php?action=heartbeat_sender", { credentials: "same-origin" });
  } catch {}
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

    // heartbeat immédiat pour apparaître en ligne directement
    await heartbeatNow();

    msg.textContent = "Activé. Redirection…";
    await new Promise(r => setTimeout(r, 150));

    window.location.href = "/index.php";
  } catch (e) {
    console.error(e);
    msg.textContent = "Erreur : " + (e?.message || "inconnue");
    btn.disabled = false;
  }
});
</script>
