<?php
$currentPage     = 'templates';
$pageTitle       = 'Modèles de sites vitrines – Bialadev Studio';
$pageDescription = "Exemples de sites vitrines prêts à adapter pour des clients : restaurant, artisan, association, etc.";
$pageRobots      = 'index,follow';

include __DIR__ . '/partials/head.php';
?>
<body>
<?php include __DIR__ . '/partials/header.php'; ?>

<main>
  <section class="section">
    <div class="container">
      <h1>Modèles de sites vitrines</h1>
      <p class="section-intro">
        Pour gagner du temps et proposer rapidement des solutions à mes clients, je maintiens
        une petite bibliothèque de <strong>sites vitrines prêts à adapter</strong> :
        il suffit de changer le contenu (textes, images, couleurs) pour coller à l’identité
        de chaque entreprise.
      </p>

      <div class="grid grid-3">
        <article class="card card-project">
          <h2>Vitrine Restaurant</h2>
          <p>
            Modèle de site pour restaurant : page d’accueil avec mise en avant du lieu,
            menu, horaires, bloc réservation et coordonnées.
          </p>
          <div class="tag-list">
            <span class="tag">HTML</span>
            <span class="tag">CSS</span>
            <span class="tag">Responsive</span>
          </div>
          <p style="margin-top:0.75rem;">
            <a href="/templates/vitrine-restaurant/index.html" class="btn btn-outline" target="_blank">
              Voir le modèle
            </a>
          </p>
        </article>

        <article class="card card-project">
          <h2>Vitrine Artisan / Freelance</h2>
          <p>
            Modèle orienté prestation de services : présentation, services, réalisations,
            témoignages et formulaire de contact.
          </p>
          <div class="tag-list">
            <span class="tag">HTML</span>
            <span class="tag">CSS</span>
            <span class="tag">One-page</span>
          </div>
          <p style="margin-top:0.75rem;">
            <a href="/templates/vitrine-artisan/index.html" class="btn btn-outline" target="_blank">
              Voir le modèle
            </a>
          </p>
        </article>

        <article class="card card-project">
          <h2>Vitrine Association</h2>
          <p>
            Modèle pour association ou club : présentation de l’activité, équipe, événements,
            actualités simples et formulaire de contact.
          </p>
          <div class="tag-list">
            <span class="tag">HTML</span>
            <span class="tag">CSS</span>
            <span class="tag">Association</span>
          </div>
          <p style="margin-top:0.75rem;">
            <a href="/templates/vitrine-association/index.html" class="btn btn-outline" target="_blank">
              Voir le modèle
            </a>
          </p>
        </article>
      </div>

      <p style="margin-top:2rem;">
        Pour chaque nouveau client, je peux partir d’un de ces modèles, l’adapter à son identité
        (charte graphique, textes, photos) et ajouter des fonctionnalités spécifiques
        (formulaire avancé, espace client, intégration de paiement, etc.).
      </p>
    </div>
  </section>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
