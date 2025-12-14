<footer class="footer">
  <div class="container footer-inner">
    <p>
      © <span id="year"></span> – Bialadev Studio (Dylan Bialade). Tous droits réservés.
      · <a href="mentions-legales.php">Mentions légales &amp; Confidentialité</a>
    </p>
  </div>
</footer>


<!-- Live widget (visible uniquement pour les comptes autorisés à streamer) -->
<div id="liveWidget" class="live-widget" hidden>
  <div class="live-widget-row">
    <strong>Live</strong>
    <span id="liveState" class="live-state">…</span>

    <button id="liveStart" type="button">Démarrer</button>
    <button id="liveStop" type="button" disabled>Stop</button>
    <button id="liveSwitch" type="button" disabled>Switch</button>

    <button id="liveHide" type="button" class="live-mini">Masquer</button>
  </div>
  <div id="liveInfo" class="live-muted" style="margin-top:8px;"></div>
</div>

<script>
  // Config Live (same-origin)
  window.__LIVE_CONFIG__ = {
    apiUrl: "/live_api.php",
    signalUrl: "/live_signal.php"
  };
</script>
<script type="module" src="/assets/live/live_autosender.js"></script>

<script>
  // Année auto
  (function () {
    var yearSpan = document.getElementById('year');
    if (yearSpan) {
      yearSpan.textContent = new Date().getFullYear();
    }
  })();
</script>
