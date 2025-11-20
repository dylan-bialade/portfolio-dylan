<?php
$currentPage = 'projects';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <title>Projets – Bialadev Studio | Développement web &amp; outils métiers</title>
  <meta name="description"
        content="Sélection de projets réalisés par Bialadev Studio : applications Symfony, APIs, outils métiers, front-end JS et projets scolaires encadrés." />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="style.css" />
</head>
<body>
<?php include __DIR__ . '/partials/header.php'; ?>
<noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-KH7PTM2B"
        height="0" width="0" style="display:none;visibility:hidden">
    </iframe>
</noscript>
<main>
  <section class="section">
    <div class="container">
      <h1>Projets</h1>
      <p class="section-intro">
        Quelques projets représentatifs (scolaires, personnels ou prototypes) montrant les technologies
        que j’utilise : PHP/Symfony, Java, C#, APIs, front-end…
      </p>

      <div class="filters">
        <button class="filter-btn active" data-filter="all">Tous</button>
        <button class="filter-btn" data-filter="php">PHP / Symfony</button>
        <button class="filter-btn" data-filter="js">JavaScript / Front</button>
        <button class="filter-btn" data-filter="java">Java</button>
        <button class="filter-btn" data-filter="cs">C# / .NET</button>
        <button class="filter-btn" data-filter="other">Autres</button>
      </div>

      <div class="grid projects-grid">
        <article class="card project-card" data-tech="php">
          <h2>OVH Manager (projet de gestion)</h2>
          <p class="project-tech">PHP · Symfony · MySQL · DataTables</p>
          <p>
            Tableau de bord pour gérer domaines, VPS, factures, clients… avec filtres, pagination,
            exports et interface administrateur.
          </p>
        </article>

        <article class="card project-card" data-tech="php">
          <h2>Garbadge – Gestion de repas / menus</h2>
          <p class="project-tech">Symfony 6 · API Platform · JWT</p>
          <p>
            API back-end pour gérer des repas, menus et stocks, avec authentification par token,
            journalisation et endpoints sécurisés.
          </p>
        </article>

        <article class="card project-card" data-tech="php">
          <h2>PlanningApp – Planification d’équipes</h2>
          <p class="project-tech">Symfony · Doctrine · Calendar UI</p>
          <p>
            Application de génération de plannings automatiques pour employés, groupes et intérimaires,
            avec interface de visualisation des horaires.
          </p>
        </article>

        <article class="card project-card" data-tech="cs">
          <h2>JCDecaux Vélo’v</h2>
          <p class="project-tech">C# · API REST</p>
          <p>
            Application bureau affichant la disponibilité de vélos en temps réel via l’API JCDecaux,
            filtrage par station, statut, etc.
          </p>
        </article>

        <article class="card project-card" data-tech="java">
          <h2>SteamRest – Gestion de jeux / achats</h2>
          <p class="project-tech">Java · Spring Boot · REST</p>
          <p>
            Back-end REST pour gérer une bibliothèque de jeux, les achats et les utilisateurs, avec
            endpoints CRUD, filtres et gestion des relations.
          </p>
        </article>

        <article class="card project-card" data-tech="js">
          <h2>Interface front JS (SPA légère)</h2>
          <p class="project-tech">JavaScript · Fetch API</p>
          <p>
            Front-end léger consommant une API (ex : SteamRest ou projet école) : liste de données,
            détail, filtrage côté client, mise à jour dynamique.
          </p>
        </article>

        <article class="card project-card" data-tech="other">
          <h2>Outils internes &amp; scripts</h2>
          <p class="project-tech">Scripting · Automatisation</p>
          <p>
            Petits scripts et outils internes pour simplifier le quotidien : export de données, petites
            automatisations, etc.
          </p>
        </article>
      </div>
    </div>
  </section>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>

<script>
  // Filtrage simple des projets
  (function () {
    var buttons = document.querySelectorAll('.filter-btn');
    var cards = document.querySelectorAll('.project-card');

    function setFilter(filter) {
      cards.forEach(function (card) {
        var tech = card.getAttribute('data-tech');
        if (filter === 'all' || tech === filter) {
          card.style.display = '';
        } else {
          card.style.display = 'none';
        }
      });
    }

    buttons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        buttons.forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        setFilter(btn.getAttribute('data-filter'));
      });
    });

    setFilter('all');
  })();
</script>
</body>
</html>
