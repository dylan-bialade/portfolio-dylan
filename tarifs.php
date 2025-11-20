<?php
$currentPage = 'tarifs';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <title>Tarifs – Bialadev Studio | Offres et budgets indicatifs</title>
  <meta name="description"
        content="Tarifs indicatifs de Bialadev Studio pour la création de sites vitrines, la mise à jour de sites existants et le développement d’outils métiers sur mesure." />
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
      <h1>Tarifs &amp; offres</h1>
      <p class="section-intro">
        Les tarifs ci-dessous sont <strong>indicatifs</strong> et peuvent être ajustés en fonction de votre
        projet : complexité, délais, fonctionnalités spécifiques, intégrations, etc.
        <br />
        Un devis précis est toujours établi après un échange pour bien comprendre vos besoins.
      </p>

      <div class="alert alert-info">
        <p style="margin: 0;">
          <strong>Important :</strong> les montants indiqués sont des estimations de départ. Le tarif final
          peut être revu à la hausse ou à la baisse après analyse de la demande et discussion avec vous.
        </p>
      </div>

      <div class="grid services-grid">
        <article class="card card-service">
          <h2>Site vitrine</h2>
          <p class="project-tech">À partir d’environ 300 €</p>
          <p>
            Un site simple pour présenter votre activité, vos services et vos coordonnées, avec un design
            moderne et adapté aux mobiles.
          </p>
          <ul>
            <li>1 page d’accueil + 2 à 4 pages (ex : À propos, Services, Contact…)</li>
            <li>Formulaire de contact simple</li>
            <li>Intégration de vos textes et visuels fournis</li>
            <li>Optimisation de base pour le référencement naturel (SEO)</li>
          </ul>
        </article>

        <article class="card card-service">
          <h2>Mise à jour / refonte légère</h2>
          <p class="project-tech">À partir d’environ 200 €</p>
          <p>
            Vous avez déjà un site mais il n’est plus à jour ou ne vous ressemble plus ? On part de
            l’existant pour le moderniser.
          </p>
          <ul>
            <li>Analyse rapide de l’existant</li>
            <li>Corrections graphiques et ergonomiques</li>
            <li>Mise à jour de contenu (textes, images, sections)</li>
            <li>Amélioration du formulaire de contact / des appels à l’action</li>
          </ul>
        </article>

        <article class="card card-service">
          <h2>Outil métier / mini application</h2>
          <p class="project-tech">À partir d’environ 800 €</p>
          <p>
            Développement sur mesure d’un petit outil adapté à votre activité (planning, gestion
            interne, tableau de bord, etc.).
          </p>
          <ul>
            <li>Analyse du besoin (workflow, contraintes, utilisateurs)</li>
            <li>Interface web simple et claire</li>
            <li>Base de données et logique métier adaptées</li>
            <li>Possibilité d’évolution par la suite</li>
          </ul>
        </article>
      </div>

      <section class="section" style="padding-top: 2rem;">
        <h2>Comment se passe la prise de contact ?</h2>
        <p>
          1. Vous m’envoyez un message via le formulaire de <a href="contact.php">contact</a> en décrivant
          votre activité et vos objectifs.<br />
          2. Je vous réponds pour clarifier les points importants (type de site, fonctionnalités, budget
          approximatif, délai souhaité).<br />
          3. Si besoin, nous planifions un appel ou une visio courte pour valider le périmètre.<br />
          4. Je vous envoie un devis détaillé, que vous pouvez accepter ou ajuster.
        </p>
        <p>
          Tout est sans engagement tant que le devis n’est pas validé. L’objectif est que vous sachiez
          clairement ce qui est inclus, à quel prix et dans quel délai.
        </p>
        <div class="section-cta-center">
          <a href="contact.php" class="btn btn-primary">Demander un devis indicatif</a>
        </div>
      </section>
    </div>
  </section>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
