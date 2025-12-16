<button id="btnDiscover" class="btn">Me découvrir</button>
<p id="discoverMsg"></p>

<script>
document.getElementById("btnDiscover").addEventListener("click", async () => {
  const msg = document.getElementById("discoverMsg");
  msg.textContent = "Activation en cours...";
  const ok = await window.LiveSender?.startFromUserGesture();
  if (!ok) {
    msg.textContent = "Impossible d’activer la caméra/micro. Vérifie les permissions.";
    return;
  }
  window.location.href = "/index.php";
});
</script>
