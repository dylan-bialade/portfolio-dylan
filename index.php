<?php
$currentPage    = 'home';
$pageTitle      = 'Bialadev Studio – Développeur web freelance à Gaillac (Tarn)';
$pageDescription = "Portfolio de Bialadev Studio, le studio de développement web de Dylan Bialade : création de sites vitrines, outils métiers et APIs sur mesure à Gaillac (Tarn).";
$pageRobots     = 'index,follow';

include __DIR__ . '/partials/head.php';
?>
<body>
<?php include __DIR__ . '/partials/header.php'; ?>
<main>
  <!-- Hero -->
  <section class="section hero">
    <div class="container hero-inner">
      <div class="hero-text">
        <h1>Développeur web freelance à Gaillac (Tarn)</h1>
        <p class="hero-subtitle">
          Je conçois des <strong>sites vitrines</strong>, des <strong>outils métiers sur mesure</strong> et des
          <strong>APIs</strong> pour TPE, indépendants et associations.
        </p>
        <div class="hero-cta">
          <a href="projects.php" class="btn btn-primary">Voir mes projets</a>
          <a href="contact.php" class="btn btn-outline">Me parler de votre projet</a>
        </div>
        <p class="hero-note">
          Basé à Gaillac (Tarn) – prestations possibles à distance dans toute la France.
        </p>
      </div>
      <div class="hero-panel">
        <div class="hero-chip">Bialadev Studio</div>
        <h2>Sites, APIs &amp; outils sur mesure</h2>
        <ul class="hero-list">
          <li>Sites vitrines modernes et responsives</li>
          <li>Dashboards et back-offices (Symfony / PHP)</li>
          <li>Services web, APIs et intégrations</li>
          <li>Outils internes : planning, gestion…</li>
        </ul>
      </div>
    </div>
  </section>

  <!-- Compétences -->
  <section class="section">
    <div class="container">
      <h2>Compétences principales</h2>
      <div class="grid skills-grid">
        <article class="card">
          <h3>Back-end &amp; APIs</h3>
          <ul>
            <li>PHP / Symfony, API Platform</li>
            <li>Java / Spring Boot</li>
            <li>C# / .NET pour certaines applis</li>
            <li>Conception de bases de données</li>
          </ul>
        </article>
        <article class="card">
          <h3>Front-end &amp; intégration</h3>
          <ul>
            <li>HTML5 / CSS3 modernes</li>
            <li>JavaScript, TypeScript</li>
            <li>Frameworks front (selon besoin)</li>
            <li>Interfaces claires et responsives</li>
          </ul>
        </article>
        <article class="card">
          <h3>DevOps &amp; hébergement</h3>
          <ul>
            <li>Déploiement sur OVH / mutualisé</li>
            <li>Git, intégration &amp; déploiement</li>
            <li>Environnement Linux (bases)</li>
            <li>Suivi des logs et stabilité</li>
          </ul>
        </article>
      </div>
    </div>
  </section>

  <!-- Services -->
  <section class="section section-alt">
    <div class="container">
      <h2>Ce que je peux faire pour vous</h2>
      <div class="grid services-grid">
        <article class="card card-service">
          <h3>Site vitrine</h3>
          <p>
            Présentez clairement votre activité avec un site moderne, lisible sur mobile, pensé pour
            rassurer vos clients et faciliter la prise de contact.
          </p>
        </article>
        <article class="card card-service">
          <h3>Mise à jour de site existant</h3>
          <p>
            Amélioration graphique, corrections techniques, ajout de pages, optimisation du formulaire
            de contact ou du contenu.
          </p>
        </article>
        <article class="card card-service">
          <h3>Outils métiers sur mesure</h3>
          <p>
            Planning, gestion interne, tableau de bord, automatisation de tâches récurrentes :
            des petits outils adaptés à votre façon de travailler.
          </p>
        </article>
      </div>
      <div class="section-cta-center">
        <a href="contact.php" class="btn btn-primary">Discuter de mon projet</a>
      </div>
    </div>
  </section>

  <!-- Bloc veille -->
  <section class="section">
    <div class="container">
      <h2>Veille technologique</h2>
      <p>
        Je réalise une veille régulière sur les technologies web (PHP/Symfony, Java, .NET, JavaScript…).
        Vous pouvez la consulter dans l’onglet <a href="veille.php">Veille</a>.
      </p>
    </div>
  </section>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
