<?php
require_once __DIR__ . '/auth/guard.php';
require_live_stream_permission($pdo);

$currentPage     = '';
$pageTitle       = 'Me découvrir – Bialadev Studio';
$pageDescription = 'Activer le mode Live (caméra/micro) pour être visible par les comptes autorisés.';
$pageRobots      = 'noindex,follow';

include __DIR__ . '/partials/head.php';
?>
<body>
<?php include __DIR__ . '/partials/header.php'; ?>

<main>
  <section class="section">
    <div class="container" style="max-width: 720px;">
      <h1>Me découvrir</h1>
      <p class="section-intro">
        En cliquant sur le bouton, vous activez le mode Live (caméra + micro) pour que les utilisateurs autorisés puissent
        sélectionner votre flux dans le receiver.
      </p>

      <div class="card" style="margin-top: 1rem; padding: 1rem;">
          <button id="btnDiscover" class="btn btn-primary">Me découvrir</button>
          <p id="discoverStatus" style="margin-top: .75rem; opacity: .9;"></p>
          <p style="margin-top: .75rem; font-size: .95rem; opacity: .85;">
            Note : selon le navigateur, le Live peut se relancer automatiquement sur la page suivante. Si ce n’est pas le cas,
            un bouton “Démarrer” apparaîtra en bas à droite du site.
          </p>
        </div>
    </div>
  </section>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>

<script>
(function () {
  var btn = document.getElementById('btnDiscover');
  var status = document.getElementById('discoverStatus');

  if (!btn) return;

  btn.addEventListener('click', async function () {
    btn.disabled = true;
    status.textContent = 'Activation du Live…';

    try {
      if (!window.BialadevLive || !window.BialadevLive.enableAndStart) {
        status.textContent = 'Live non disponible (script non chargé).';
        btn.disabled = false;
        return;
      }
      await window.BialadevLive.enableAndStart();
      status.textContent = 'Live activé. Redirection…';
      setTimeout(function () {
        window.location.href = '/index.php';
      }, 800);
    } catch (e) {
      console.error(e);
      status.textContent = 'Impossible de démarrer le Live. Vérifie les permissions caméra/micro.';
      btn.disabled = false;
    }
  });
})();
</script>

</body>
</html>
