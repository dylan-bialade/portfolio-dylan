<?php
// partials/header.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// $currentPage peut être défini dans chaque page avant l'include
if (!isset($currentPage)) {
    $currentPage = '';
}

// Hydrate permissions live en session si manquantes (évite d'afficher un lien receiver à tort)
if (!empty($_SESSION['user_id']) && (!isset($_SESSION['can_view_live']) || !isset($_SESSION['can_stream_live']))) {
    try {
        require_once __DIR__ . '/../config/db.php';
        $stmt = $pdo->prepare('SELECT can_view_live, can_stream_live, live_autostream, live_stream_key, live_label FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$_SESSION['user_id']]);
        if ($u = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $_SESSION['can_view_live']   = (int)($u['can_view_live'] ?? 0);
            $_SESSION['can_stream_live'] = (int)($u['can_stream_live'] ?? 0);
            $_SESSION['live_autostream'] = (int)($u['live_autostream'] ?? 0);
            $_SESSION['live_stream_key'] = $u['live_stream_key'] ?? null;
            $_SESSION['live_label']      = $u['live_label'] ?? null;
        }
    } catch (Throwable $e) {
        // ignore
    }
}

$isLoggedIn = !empty($_SESSION['user_id']);
$canViewLive = (int)($_SESSION['can_view_live'] ?? 0) === 1;
$canStreamLive = $isLoggedIn && ((int)($_SESSION['can_stream_live'] ?? 1) === 1); // par défaut ON si non défini
?>
<header class="topbar">
  <div class="container topbar-inner">
    <div class="logo">
      <span class="logo-mark">&lt;/&gt;</span>
      <div class="logo-text-block">
        <span class="logo-text-main">Bialadev Studio</span>
        <span class="logo-text-sub">Dylan Bialade · Développeur web</span>
      </div>
    </div>

    <nav class="nav">
      <a href="/index.php" class="nav-link <?php echo $currentPage === 'home' ? 'nav-link-active' : ''; ?>">Accueil</a>
      <a href="/projects.php" class="nav-link <?php echo $currentPage === 'projects' ? 'nav-link-active' : ''; ?>">Projets</a>
      <a href="/demos.php" class="nav-link <?php echo $currentPage === 'demos' ? 'nav-link-active' : ''; ?>">Démos</a>
      <a href="/games.php" class="nav-link <?php echo $currentPage === 'games' ? 'nav-link-active' : ''; ?>">Jeux</a>
      <a href="/tarifs.php" class="nav-link <?php echo $currentPage === 'tarifs' ? 'nav-link-active' : ''; ?>">Tarifs</a>
      <a href="/veille.php" class="nav-link <?php echo $currentPage === 'veille' ? 'nav-link-active' : ''; ?>">Veille</a>
      <a href="/contact.php" class="nav-link <?php echo $currentPage === 'contact' ? 'nav-link-active' : ''; ?>">Contact</a>
      <a href="/a-propos.php" class="nav-link <?php echo $currentPage === 'about' ? 'nav-link-active' : ''; ?>">À propos</a>

      <a href="/templates/vitrine-restaurant/index.html" class="nav-link" target="_blank" rel="noopener">Modèles vitrines</a>

      <?php if ($isLoggedIn): ?>
        <?php if ($canStreamLive): ?>
          <a href="/me-decouvrir.php" class="nav-link">Me découvrir</a>
        <?php endif; ?>

        <?php if ($canViewLive): ?>
          <a href="/live_receiver.php" class="nav-link">Live Receiver</a>
        <?php endif; ?>

        <span class="nav-link" style="opacity:.85; cursor:default;">
          <?php echo htmlspecialchars($_SESSION['pseudo'] ?? 'Compte', ENT_QUOTES, 'UTF-8'); ?>
        </span>
        <a href="/auth/logout.php" class="nav-link">Déconnexion</a>
      <?php else: ?>
        <a href="/auth/login.php" class="nav-link">Connexion</a>
        <a href="/auth/register.php" class="nav-link">Inscription</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
