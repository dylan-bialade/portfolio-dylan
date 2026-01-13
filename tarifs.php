<?php
$currentPage     = 'tarifs';
$pageTitle       = 'Tarifs – Bialadev Studio | Offres et budgets indicatifs';
$pageDescription = "Tarifs indicatifs de Bialadev Studio : sites vitrines, refonte, outils métiers, automatisation/IA et accompagnement (coaching BTS dev/réseau).";
$pageRobots      = 'index,follow';

include __DIR__ . '/partials/head.php';
?>
<body>
<?php include __DIR__ . '/partials/header.php'; ?>

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

      <article class="card card-project">
        <h2>Site vitrine basé sur un modèle existant</h2>
        <p>
          Pour les projets avec un budget serré ou un besoin rapide, je propose des
          <strong>sites vitrines basés sur des modèles que j’ai déjà conçus</strong>.
          Cela permet de gagner du temps (et donc de réduire le coût) tout en gardant un
          rendu moderne et professionnel.
        </p>
        <ul>
          <li>Structure déjà prête (restaurant, artisan, association…)</li>
          <li>Personnalisation des textes, couleurs, photos et sections</li>
          <li>Possibilité d’ajouter des fonctionnalités spécifiques (formulaire, réservation, etc.)</li>
        </ul>
        <p class="muted" style="font-size:0.9rem;">
          Les tarifs sont <strong>indicatifs</strong> et peuvent évoluer selon la complexité
          du projet (pages supplémentaires, formulaires avancés, intégrations externes…).
        </p>
        <p style="margin-top:0.75rem;">
          Exemples visibles directement :
          <br>
          – <a href="/templates/vitrine-restaurant/index.html" target="_blank">Modèle vitrine restaurant</a><br>
          – <a href="/templates/vitrine-artisan/index.html" target="_blank">Modèle vitrine artisan / freelance</a><br>
          – <a href="/templates/vitrine-association/index.html" target="_blank">Modèle vitrine association</a>
        </p>
      </article>

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

        <!-- NOUVEAU -->
        <article class="card card-service">
          <h2>Mise en place IA &amp; automatisation</h2>
          <p class="project-tech">À partir d’environ 450 € (sur devis)</p>
          <p>
            Automatisation de tâches répétitives et intégration de solutions IA dans votre entreprise
            (gain de temps, réduction d’erreurs, meilleure organisation).
          </p>
          <ul>
            <li>Analyse des processus (ce qui peut être automatisé)</li>
            <li>Automatisations : formulaires, emails, tri, reporting, workflows</li>
            <li>IA : assistant interne, FAQ, aide à la rédaction, classification de messages</li>
            <li>Respect des contraintes : sécurité, accès, confidentialité</li>
          </ul>
        </article>

        <!-- NOUVEAU -->
        <article class="card card-service">
          <h2>Coaching / remise à niveau (BTS dev ou réseau)</h2>
          <p class="project-tech">À partir de 25 € / heure</p>
          <p>
            Vous avez un projet de reconversion ou vous préparez un niveau BTS (développement ou réseau) :
            je vous aide à vous mettre à niveau, du minimum indispensable jusqu’à un niveau solide.
          </p>
          <ul>
            <li>Plan de progression (selon ton niveau et ton objectif)</li>
            <li>Exercices guidés + corrections + méthodes</li>
            <li>Préparation aux projets / oraux / bonnes pratiques</li>
            <li>Sessions à distance, ou en présentiel si possible</li>
          </ul>
        </article>
      </div>

      <section class="section" style="padding-top: 2rem;">
        <h2>Comment se passe la prise de contact ?</h2>
        <p>
          1. Vous m’envoyez un message via le formulaire de <a href="contact.php">contact</a> en décrivant
          votre besoin.<br />
          2. Je vous réponds pour clarifier les points importants (objectif, budget approximatif, délai souhaité).<br />
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
