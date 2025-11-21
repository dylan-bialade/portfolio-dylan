<?php
// $currentPage peut être défini dans chaque page avant l'include
if (!isset($currentPage)) {
    $currentPage = '';
}
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
      <a href="index.php" class="nav-link <?php echo $currentPage === 'home' ? 'nav-link-active' : ''; ?>">Accueil</a>
      <a href="projects.php" class="nav-link <?php echo $currentPage === 'projects' ? 'nav-link-active' : ''; ?>">Projets</a>
      <a href="demos.php" class="nav-link <?php echo $currentPage === 'demos' ? 'nav-link-active' : ''; ?>">Démos</a>
      <a href="tarifs.php" class="nav-link <?php echo $currentPage === 'tarifs' ? 'nav-link-active' : ''; ?>">Tarifs</a>
      <a href="veille.php" class="nav-link <?php echo $currentPage === 'veille' ? 'nav-link-active' : ''; ?>">Veille</a>
      <a href="contact.php" class="nav-link <?php echo $currentPage === 'contact' ? 'nav-link-active' : ''; ?>">Contact</a>
    </nav>
  </div>
</header>
