<?php
// games.php – page de choix des jeux
session_start();

$currentPage     = 'demos'; // ou 'games' si tu as un onglet spécifique
$pageTitle       = 'Arcade – Jeux Bialadev Studio';
$pageDescription = 'Choisissez un mini-jeu : Cookie Dev ou Gem Miner Tycoon.';
$pageRobots      = 'index,follow';

include __DIR__ . '/partials/head.php';
?>
<body>
<?php include __DIR__ . '/partials/header.php'; ?>

<main>
  <section class="section">
    <div class="container">
      <h1>Arcade Bialadev Studio</h1>
      <p class="section-intro">
        Choisissez un jeu et testez mes compétences en développement web, front et back.
      </p>

      <?php if (!isset($_SESSION['user_id'])): ?>
        <p class="alert alert-info">
          Vous pouvez jouer en invité, mais la sauvegarde et le classement ne sont disponibles
          qu’en étant connecté.
          <a href="/auth/register.php">Créer un compte</a> ou
          <a href="/auth/login.php?redirect=/games.php">Se connecter</a>.
        </p>
      <?php else: ?>
        <p class="alert alert-info">
          Connecté en tant que
          <strong><?php echo htmlspecialchars($_SESSION['pseudo'] ?? 'Joueur', ENT_QUOTES, 'UTF-8'); ?></strong>.
          Choisissez un jeu ci-dessous.
        </p>
      <?php endif; ?>

      <div class="gm-game-grid">
        <article class="card gm-game-card">
          <h2>Cookie Dev</h2>
          <p>
            Jeu de type clicker dans l’univers du développement informatique : cliquez pour écrire
            du code, achetez des améliorations, débloquez du prestige et grimpez dans le classement.
          </p>
          <p class="muted">Technos : PHP, MySQL, JavaScript, gestion de comptes, sauvegarde.</p>
          <a href="/devcookie.php" class="btn btn-primary">Jouer à Cookie Dev</a>
        </article>

        <article class="card gm-game-card">
          <h2>Gem Miner Tycoon</h2>
          <p>
            Jeu de minage et de gestion : extrayez des gemmes, optimisez la logistique et la vente,
            débloquez des compétences de prestige et bâtissez un empire de mineurs.
          </p>
          <p class="muted">Technos : PHP, MySQL, JavaScript, état JSON, classement équitable.</p>
          <a href="/gemminer.php" class="btn btn-primary">Jouer à Gem Miner Tycoon</a>
        </article>
      </div>
    </div>
  </section>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>

<style>
.gm-game-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1.5rem;
}

@media (max-width: 900px) {
  .gm-game-grid {
    grid-template-columns: 1fr;
  }
}

.gm-game-card h2 {
  margin-top: 0;
}
</style>

</body>
</html>
