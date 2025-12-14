<?php
require_once __DIR__ . '/auth/guard.php';
require_live_view_permission($pdo);

$currentPage     = '';
$pageTitle       = 'Live Receiver – Bialadev Studio';
$pageDescription = 'Sélectionner et regarder les flux Live des utilisateurs.';
$pageRobots      = 'noindex,nofollow';

include __DIR__ . '/partials/head.php';
?>
<body>
<?php include __DIR__ . '/partials/header.php'; ?>

<main>
  <section class="section">
    <div class="container">
      <h1>Live Receiver</h1>
      <p class="section-intro">
        Cette page est réservée aux comptes autorisés. Sélectionnez un flux pour le regarder.
      </p>

      <div class="live-receiver-grid">
        <div class="live-receiver-panel">
          <div class="live-receiver-toolbar">
            <button id="btnRefreshStreams" class="btn btn-outline">Rafraîchir</button>
            <span id="streamsStatus" class="live-muted"></span>
          </div>

          <div id="streamsList" class="live-streams-list">
            <!-- rempli en JS -->
          </div>
        </div>

        <div class="live-receiver-player">
          <video id="receiverVideo" autoplay playsinline controls></video>
          <div class="live-receiver-actions">
            <button id="btnLeave" class="btn btn-outline" disabled>Quitter</button>
            <span id="watchStatus" class="live-muted"></span>
          </div>
        </div>
      </div>

    </div>
  </section>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
<script type="module" src="/assets/live/live_receiver.js"></script>
</body>
</html>
