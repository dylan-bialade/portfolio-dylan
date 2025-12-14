<?php
// a-propos.php
// Ajuste simplement les chemins si tes includes ne sont pas dans /includes
$pageTitle = "À propos";
$pageDescription = "Présentation de Dylan Bialade : parcours, compétences, méthode de travail et objectifs en développement.";
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>" />

  <!-- Si tu as déjà un CSS global, laisse-le -->
  <link rel="stylesheet" href="/assets/css/style.css" />
  <!-- CSS spécifique à la page -->
  <link rel="stylesheet" href="/assets/css/about.css" />
</head>

<body>

<?php
// Si tu as un header commun :
if (file_exists(__DIR__ . "/includes/header.php")) {
  require __DIR__ . "/includes/header.php";
}
?>

<main class="about">
  <section class="about__hero">
    <div class="about__container about__heroGrid">
      <div>
        <p class="about__kicker">Développement • Web/API • Automatisation • Mobile/Embarqué</p>
        <h1 class="about__title">Dylan Bialade</h1>
        <p class="about__lead">
          Étudiant en informatique (BTS SIO SLAM), je conçois des solutions pragmatiques et maintenables :
          développement web / API, scripts d’automatisation, et projets Android / Arduino.
        </p>

        <div class="about__actions">
          <a class="about__btn about__btn--primary" href="/contact.php">Me contacter</a>
          <a class="about__btn" href="https://github.com/dylan-bialade" target="_blank" rel="noopener">GitHub</a>
        </div>

        <ul class="about__chips" aria-label="Points clés">
          <li>Polyvalence (web, scripts, mobile)</li>
          <li>Approche qualité & sécurité</li>
          <li>Support / diagnostic terrain</li>
          <li>Esprit logique, transmission</li>
        </ul>
      </div>

      <aside class="about__card">
        <h2 class="about__cardTitle">En bref</h2>
        <dl class="about__facts">
          <div><dt>Profil</dt><dd>Développement & solutions techniques</dd></div>
          <div><dt>Stack</dt><dd>PHP, SQL, Java, Android, JavaScript, PowerShell</dd></div>
          <div><dt>Atouts</dt><dd>Autonomie, adaptation, rigueur</dd></div>
          <div><dt>Objectif</dt><dd>Projets concrets, montée en compétences</dd></div>
        </dl>

        <div class="about__cv">
          <!-- Optionnel : si tu mets ton CV en ligne -->
          <!-- <a class="about__btn about__btn--ghost" href="/assets/docs/cv-dylan-bialade.pdf" target="_blank" rel="noopener">Télécharger mon CV</a> -->
          <p class="about__hint">
            Astuce : ajoute une version PDF dans <code>/assets/docs/</code> pour un lien “Télécharger mon CV”.
          </p>
        </div>
      </aside>
    </div>
  </section>

  <section class="about__section">
    <div class="about__container">
      <h2>Profil professionnel</h2>
      <p>
        Mon parcours combine une base “terrain” (dépannage, ticketing, accompagnement utilisateurs) et une montée en puissance en
        développement (web/API, Android, automatisation). J’accorde une forte importance à la compréhension du besoin, à la qualité
        du code et à la maintenabilité.
      </p>
      <p>
        Je cherche à livrer des solutions robustes et claires, avec une logique d’amélioration continue : comprendre le contexte,
        structurer, implémenter proprement, documenter l’essentiel.
      </p>
    </div>
  </section>

  <section class="about__section about__section--alt">
    <div class="about__container">
      <h2>Ce que je peux apporter</h2>
      <div class="about__grid3">
        <article class="about__miniCard">
          <h3>Développement</h3>
          <p>Web / API, logique métier, intégrations. Priorité : lisibilité, structure et évolution du projet.</p>
        </article>
        <article class="about__miniCard">
          <h3>Automatisation</h3>
          <p>Scripts PowerShell et outillage : fiabiliser, accélérer, standardiser des tâches techniques.</p>
        </article>
        <article class="about__miniCard">
          <h3>Approche “terrain”</h3>
          <p>Diagnostic, résolution, communication : une culture support utile pour produire des solutions réellement exploitables.</p>
        </article>
      </div>
    </div>
  </section>

  <section class="about__section">
    <div class="about__container">
      <h2>Ma méthode</h2>
      <ol class="about__steps">
        <li><strong>Cadrage :</strong> besoin, contraintes, critères de réussite.</li>
        <li><strong>Conception :</strong> structure simple, données propres, choix techniques justifiés.</li>
        <li><strong>Implémentation :</strong> itérations courtes, contrôles, code lisible.</li>
        <li><strong>Livraison :</strong> documentation utile, passation, amélioration continue.</li>
      </ol>
    </div>
  </section>

  <section class="about__section about__section--alt">
    <div class="about__container">
      <h2>Parcours (sélection)</h2>

      <div class="about__timeline">
        <div class="about__tItem">
          <div class="about__tDate">2025</div>
          <div class="about__tContent">
            <h3>Arduino & Android</h3>
            <p>Développement de solutions techniques (embarqué + mobile), avec contraintes et logique RGPD selon les cas.</p>
          </div>
        </div>

        <div class="about__tItem">
          <div class="about__tDate">2024</div>
          <div class="about__tContent">
            <h3>Web / API & PowerShell</h3>
            <p>Travaux web orientés API et automatisation (scripts de configuration serveur, outillage).</p>
          </div>
        </div>

        <div class="about__tItem">
          <div class="about__tDate">2022</div>
          <div class="about__tContent">
            <h3>Support & ticketing</h3>
            <p>Gestion d’incidents, dépannage, interventions orientées utilisateur et continuité de service.</p>
          </div>
        </div>
      </div>

      <p class="about__note">
        Je détaille les projets (stack, objectifs, captures) dans la page “Projets”.
      </p>
    </div>
  </section>

  <section class="about__section">
    <div class="about__container about__cta">
      <div>
        <h2>Travaillons ensemble</h2>
        <p>Si tu cherches un profil polyvalent et sérieux pour un projet web/app/outillage, je suis disponible pour en discuter.</p>
      </div>
      <div class="about__ctaActions">
        <a class="about__btn about__btn--primary" href="/contact.php">Contact</a>
        <a class="about__btn" href="/projets.php">Projets</a>
      </div>
    </div>
  </section>
</main>

<?php
// Footer commun si présent
if (file_exists(__DIR__ . "/includes/footer.php")) {
  require __DIR__ . "/includes/footer.php";
}
?>

</body>
</html>
