<?php
// $currentPage peut être défini dans chaque page avant l'include
if (!isset($currentPage)) {
    $currentPage = '';
}
?>
<header class="topbar">
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','GTM-KH7PTM2B');
    </script>
    <!-- End Google Tag Manager -->
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
      <a href="tarifs.php" class="nav-link <?php echo $currentPage === 'tarifs' ? 'nav-link-active' : ''; ?>">Tarifs</a>
      <a href="veille.php" class="nav-link <?php echo $currentPage === 'veille' ? 'nav-link-active' : ''; ?>">Veille</a>
      <a href="contact.php" class="nav-link <?php echo $currentPage === 'contact' ? 'nav-link-active' : ''; ?>">Contact</a>
    </nav>
  </div>
</header>
